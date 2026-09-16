<?php

require_once __DIR__ . '/../../models/Inventario/Producto.php';
require_once __DIR__ . '/../../models/Inventario/Stock.php';
require_once __DIR__ . '/../../models/Inventario/MovimientoStock.php';
require_once __DIR__ . '/../../repositories/Inventario/ProductoRepository.php';
require_once __DIR__ . '/../../repositories/Inventario/StockRepository.php';
require_once __DIR__ . '/../../repositories/Inventario/MovimientoStockRepository.php';
require_once __DIR__ . '/CategoriaService.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class InventarioService
{
    private Database $db;
    private ProductoRepository $productos;
    private StockRepository $stocks;
    private MovimientoStockRepository $movimientos;
    private CategoriaService $categorias;
    private AuthService $auth;

    public function __construct(
        Database $db,
        ProductoRepository $productos,
        StockRepository $stocks,
        MovimientoStockRepository $movimientos,
        CategoriaService $categorias,
        AuthService $auth
    ) {
        $this->db          = $db;
        $this->productos   = $productos;
        $this->stocks      = $stocks;
        $this->movimientos = $movimientos;
        $this->categorias  = $categorias;
        $this->auth        = $auth;
    }

    /** @return Producto[] */
    public function listarProductos(array $filtros = []): array
    {
        if (empty($filtros['id_categoria']) && !empty($filtros['categoria']) && $filtros['categoria'] !== 'todos') {
            $cat = $this->categorias->buscarPorNombre((string) $filtros['categoria']);
            if (!$cat) {
                return [];
            }
            $filtros['id_categoria'] = (int) $cat->getIdCategoria();
        }
        $stockBajo = !empty($filtros['stock_bajo']) && $filtros['stock_bajo'] !== 'false' && $filtros['stock_bajo'] !== '0';
        $filtros['stock_bajo'] = $stockBajo;
        return $this->productos->listar($filtros);
    }

    public function buscarProducto(int $id): Producto
    {
        $producto = $this->productos->buscarPorId($id);
        if (!$producto) {
            throw new AppException('Producto no encontrado', HTTP_NOT_FOUND);
        }
        return $producto;
    }

    public function guardarProducto(array $data): Producto
    {
        $id = (int) ($data['id_producto'] ?? $data['id'] ?? 0);
        if ($id > 0) {
            return $this->actualizarProducto($id, $data);
        }
        return $this->crearProducto($data);
    }

    public function crearProducto(array $data): Producto
    {
        $nombre    = trim((string) ($data['nombre'] ?? ''));
        $precio    = (float) ($data['precio_unitario'] ?? $data['precio'] ?? 0);
        $createdBy = (int) ($data['created_by'] ?? 0);
        $referencia = $data['referencia'] ?? $data['codigo'] ?? null;

        if ($nombre === '' || $precio < 0 || $createdBy < 1) {
            throw new AppException('nombre, precio y created_by son requeridos', HTTP_BAD_REQUEST);
        }
        if ($referencia && $this->productos->referenciaExiste((string) $referencia)) {
            throw new AppException('La referencia ya está registrada', HTTP_BAD_REQUEST);
        }

        $idCategoria = $this->resolverCategoria($data, $createdBy);
        $producto = new Producto($idCategoria, $nombre, $precio, $createdBy, $referencia ? (string) $referencia : null);
        if (!empty($data['descripcion'])) {
            $producto->setDescripcion((string) $data['descripcion']);
        }
        if (!empty($data['tipo'])) {
            $producto->setTipo((string) $data['tipo']);
        }
        if (!empty($data['codigo_barras'])) {
            $producto->setCodigoBarras((string) $data['codigo_barras']);
        }

        $cantidad = (int) ($data['stock'] ?? $data['cantidad'] ?? 0);
        $minimo   = (int) ($data['stock_minimo'] ?? $data['stockMinimo'] ?? 10);
        $ubicacion = $data['ubicacion'] ?? null;
        $compra    = (float) ($data['precio_compra'] ?? $precio);

        $this->db->beginTransaction();
        try {
            $this->productos->guardar($producto);
            $stock = new Stock(
                (int) $producto->getIdProducto(),
                $createdBy,
                $cantidad,
                $minimo,
                $compra,
                $ubicacion
            );
            $this->stocks->guardar($stock);
            $producto->setStock($stock);

            if ($cantidad > 0) {
                $mov = new MovimientoStock(
                    (int) $producto->getIdProducto(),
                    MovimientoStock::ENTRADA,
                    $createdBy,
                    0,
                    $cantidad,
                    0,
                    $cantidad,
                    'Existencia inicial'
                );
                $this->movimientos->guardar($mov);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Productos',
            'registroId'    => (int) $producto->getIdProducto(),
            'valoresNuevos' => $producto->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);

        return $this->buscarProducto((int) $producto->getIdProducto());
    }

    public function actualizarProducto(int $id, array $data): Producto
    {
        $producto = $this->buscarProducto($id);
        $antes    = $producto->toArray();
        $updatedBy = (int) ($data['updated_by'] ?? $data['created_by'] ?? 0);

        if (isset($data['nombre'])) {
            $producto->setNombre(trim((string) $data['nombre']));
        }
        if (isset($data['precio_unitario']) || isset($data['precio'])) {
            $producto->setPrecioUnitario((float) ($data['precio_unitario'] ?? $data['precio']));
        }
        if (array_key_exists('descripcion', $data)) {
            $producto->setDescripcion($data['descripcion'] !== null ? (string) $data['descripcion'] : null);
        }
        if (isset($data['referencia']) || isset($data['codigo'])) {
            $ref = (string) ($data['referencia'] ?? $data['codigo']);
            if ($this->productos->referenciaExiste($ref, $id)) {
                throw new AppException('La referencia ya está registrada', HTTP_BAD_REQUEST);
            }
            $producto->setReferencia($ref);
        }
        if (!empty($data['id_categoria']) || !empty($data['categoria'])) {
            $producto->setIdCategoria($this->resolverCategoria($data, $updatedBy));
        }
        if (isset($data['estado'])) {
            $producto->setEstado((string) $data['estado']);
        }
        $producto->tocarUpdatedAt($updatedBy ?: null);

        $stock = $producto->getStock();
        if (!$stock) {
            $stock = new Stock((int) $producto->getIdProducto(), $updatedBy ?: $producto->getCreatedBy());
        }
        if (isset($data['stock_minimo']) || isset($data['stockMinimo'])) {
            $stock->setStockMinimo((int) ($data['stock_minimo'] ?? $data['stockMinimo']));
        }
        if (array_key_exists('ubicacion', $data)) {
            $stock->setUbicacion($data['ubicacion'] !== null ? (string) $data['ubicacion'] : null);
        }
        if (isset($data['precio_compra'])) {
            $stock->setPrecioCompra((float) $data['precio_compra']);
        }
        $stock->tocarUpdatedAt($updatedBy ?: null);

        $this->productos->guardar($producto);
        $this->stocks->guardar($stock);

        $this->auth->registrarLog([
            'token'             => $data['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Productos',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $producto->toArray(),
            'ipAddress'         => $data['ip'] ?? null,
        ]);

        return $this->buscarProducto($id);
    }

    public function eliminarProducto(int $id, array $contexto = []): void
    {
        $producto = $this->buscarProducto($id);
        $producto->marcarEliminado($contexto['updated_by'] ?? null);
        $this->productos->guardar($producto);
        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'DELETE',
            'tablaAfectada' => 'Productos',
            'registroId'    => $id,
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
    }

    /**
     * @param array{id_producto:int,tipo:string,cantidad?:int,nueva_cantidad?:int,referencia_documento?:int,motivo?:string} $data
     */
    public function registrarMovimiento(array $data): Producto
    {
        $idProducto = (int) ($data['id_producto'] ?? $data['producto_id'] ?? 0);
        $tipo       = strtoupper((string) ($data['tipo'] ?? $data['tipo_movimiento'] ?? ''));
        $createdBy  = (int) ($data['created_by'] ?? 0);
        $ref        = (int) ($data['referencia_documento'] ?? 0);
        $motivo     = isset($data['motivo']) ? trim((string) $data['motivo']) : null;

        $producto = $this->buscarProducto($idProducto);
        $stock = $producto->getStock();
        if (!$stock) {
            $stock = new Stock($idProducto, $createdBy);
        }

        $antes = $stock->getCantidad();
        $unidades = 0;

        if ($tipo === MovimientoStock::ENTRADA) {
            $unidades = (int) ($data['cantidad'] ?? 0);
            if ($unidades < 1) {
                throw new AppException('Indique cuántas unidades entran al almacén', HTTP_BAD_REQUEST);
            }
            $stock->entrar($unidades);
        } elseif ($tipo === MovimientoStock::SALIDA) {
            $unidades = (int) ($data['cantidad'] ?? 0);
            if ($unidades < 1) {
                throw new AppException('Indique cuántas unidades salen del almacén', HTTP_BAD_REQUEST);
            }
            if ($unidades > $antes) {
                throw new AppException(
                    sprintf(
                        'No hay suficientes unidades de "%s". Disponible: %d. Solicitado: %d.',
                        $producto->getNombre(),
                        $antes,
                        $unidades
                    ),
                    HTTP_BAD_REQUEST
                );
            }
            $stock->salir($unidades);
        } elseif ($tipo === MovimientoStock::AJUSTE) {
            $nueva = (int) ($data['nueva_cantidad'] ?? $data['cantidad'] ?? -1);
            if ($nueva < 0) {
                throw new AppException('El conteo físico no puede ser negativo', HTTP_BAD_REQUEST);
            }
            $unidades = abs($nueva - $antes);
            $stock->ajustar($nueva);
            if ($motivo === null || $motivo === '') {
                $motivo = 'Conteo físico';
            }
        } else {
            throw new AppException('El movimiento debe ser entrada, salida o ajuste', HTTP_BAD_REQUEST);
        }

        $despues = $stock->getCantidad();

        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }
        try {
            $stock->tocarUpdatedAt($createdBy);
            $this->stocks->guardar($stock);
            $mov = new MovimientoStock(
                $idProducto,
                $tipo,
                $createdBy,
                $ref,
                $unidades,
                $antes,
                $despues,
                $motivo
            );
            $this->movimientos->guardar($mov);
            if ($propia) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($propia) {
                $this->db->rollback();
            }
            throw $e;
        }

        return $this->buscarProducto($idProducto);
    }

    /** @return MovimientoStock[] */
    public function listarMovimientos(int $idProducto): array
    {
        $this->buscarProducto($idProducto);
        return $this->movimientos->listarPorProducto($idProducto);
    }

    private function resolverCategoria(array $data, int $actorId): int
    {
        if (!empty($data['id_categoria'])) {
            $cat = $this->categorias->buscarPorId((int) $data['id_categoria']);
            return (int) $cat->getIdCategoria();
        }
        $nombre = trim((string) ($data['categoria'] ?? ''));
        if ($nombre === '') {
            throw new AppException('Seleccione una categoría', HTTP_BAD_REQUEST);
        }
        $cat = $this->categorias->buscarPorNombre($nombre);
        if (!$cat) {
            throw new AppException('Esa categoría no existe. Créela primero desde Inventario.', HTTP_BAD_REQUEST);
        }
        return (int) $cat->getIdCategoria();
    }
}
