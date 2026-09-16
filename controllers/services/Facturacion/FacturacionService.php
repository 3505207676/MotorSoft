<?php

require_once __DIR__ . '/../../models/Facturacion/Factura.php';
require_once __DIR__ . '/../../models/Facturacion/DetalleFactura.php';
require_once __DIR__ . '/../../models/Facturacion/Venta.php';
require_once __DIR__ . '/../../models/Facturacion/DetalleItem.php';
require_once __DIR__ . '/../../models/Inventario/MovimientoStock.php';
require_once __DIR__ . '/../../repositories/Facturacion/FacturaRepository.php';
require_once __DIR__ . '/../../repositories/Facturacion/DetalleFacturaRepository.php';
require_once __DIR__ . '/../../repositories/Facturacion/VentaRepository.php';
require_once __DIR__ . '/../../repositories/Facturacion/DetalleItemRepository.php';
require_once __DIR__ . '/../../repositories/Clientes/ClienteRepository.php';
require_once __DIR__ . '/../../repositories/Ordenes/OrdenServicioRepository.php';
require_once __DIR__ . '/../Configuracion/ConfiguracionService.php';
require_once __DIR__ . '/../Inventario/InventarioService.php';
require_once __DIR__ . '/../Ordenes/OrdenesService.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';
require_once __DIR__ . '/../Caja/CajaService.php';

class FacturacionService
{
    private const ESTADOS_OT_FACTURABLE = ['Pendiente Pago', 'Completada'];

    private Database $db;
    private FacturaRepository $facturas;
    private DetalleFacturaRepository $detallesFactura;
    private VentaRepository $ventas;
    private DetalleItemRepository $detalleItems;
    private ClienteRepository $clientes;
    private OrdenServicioRepository $ordenes;
    private ConfiguracionService $config;
    private InventarioService $inventario;
    private OrdenesService $ordenesService;
    private AuthService $auth;
    private CajaService $caja;

    public function __construct(
        Database $db,
        FacturaRepository $facturas,
        DetalleFacturaRepository $detallesFactura,
        VentaRepository $ventas,
        DetalleItemRepository $detalleItems,
        ClienteRepository $clientes,
        OrdenServicioRepository $ordenes,
        ConfiguracionService $config,
        InventarioService $inventario,
        OrdenesService $ordenesService,
        AuthService $auth,
        CajaService $caja
    ) {
        $this->db               = $db;
        $this->facturas         = $facturas;
        $this->detallesFactura  = $detallesFactura;
        $this->ventas           = $ventas;
        $this->detalleItems     = $detalleItems;
        $this->clientes         = $clientes;
        $this->ordenes          = $ordenes;
        $this->config           = $config;
        $this->inventario       = $inventario;
        $this->ordenesService   = $ordenesService;
        $this->auth             = $auth;
        $this->caja             = $caja;
        $this->facturas->setPrefijo($this->config->prefijoFactura());
    }

    /** @return Factura[] */
    public function listarFacturas(array $filtros = []): array
    {
        return $this->facturas->listar($filtros);
    }

    public function buscarFactura(int $id): Factura
    {
        $factura = $this->facturas->buscarPorId($id);
        if (!$factura) {
            throw new AppException('Factura no encontrada', HTTP_NOT_FOUND);
        }
        return $factura;
    }

    /** @return Venta[] */
    public function listarVentas(): array
    {
        return $this->ventas->listar();
    }

    /** @return array[] */
    public function listarVentasComoArray(): array
    {
        $lista = [];
        foreach ($this->ventas->listar() as $venta) {
            $lista[] = $this->serializarVenta($venta);
        }
        return $lista;
    }

    public function buscarVenta(int $id): Venta
    {
        $venta = $this->ventas->buscarPorId($id);
        if (!$venta) {
            throw new AppException('Venta no encontrada', HTTP_NOT_FOUND);
        }
        return $venta;
    }

    public function previewOrden(int $idOrden): array
    {
        $orden = $this->ordenes->buscarPorId($idOrden, true);
        if (!$orden) {
            throw new AppException('Orden no encontrada', HTTP_NOT_FOUND);
        }
        $vehiculo = $this->ordenes->obtenerVehiculoConCliente($orden->getIdVehiculo());
        $repuestos = $this->detalleItems->listarPorPadre(DetalleItem::TIPO_ORDEN, $idOrden);
        $lineas = [];
        $subtotal = 0.0;
        foreach ($orden->getDetalles() as $det) {
            $nombre = $det->getServicio() ? $det->getServicio()->getNombre() : ('Servicio #' . $det->getIdServicio());
            $lineas[] = [
                'tipo'        => DetalleFactura::REF_SERVICIO,
                'id'          => $det->getIdServicio(),
                'descripcion' => $nombre . ' x' . $det->getCantidad(),
                'cantidad'    => $det->getCantidad(),
                'precio'      => $det->getPrecio(),
                'monto'       => $det->getSubtotal(),
            ];
            $subtotal += $det->getSubtotal();
        }
        foreach ($repuestos as $item) {
            $nombre = $item->getProducto() ? $item->getProducto()->getNombre() : ('Producto #' . $item->getIdProducto());
            $lineas[] = [
                'tipo'        => DetalleFactura::REF_PRODUCTO,
                'id'          => $item->getIdProducto(),
                'descripcion' => $nombre . ' x' . $item->getCantidad(),
                'cantidad'    => $item->getCantidad(),
                'precio'      => $item->getPrecioUnitario(),
                'monto'       => $item->subtotal(),
            ];
            $subtotal += $item->subtotal();
        }
        $totales = $this->config->calcularTotales($subtotal);
        $idFactura = $this->detallesFactura->facturaActivaPorReferencia(DetalleFactura::REF_ORDEN, $idOrden);
        return array_merge($totales, [
            'id_orden'        => $idOrden,
            'estado_orden'    => $orden->getEstado(),
            'facturable'      => in_array($orden->getEstado(), self::ESTADOS_OT_FACTURABLE, true) && $idFactura === null,
            'ya_facturada'    => $idFactura !== null,
            'id_factura'      => $idFactura,
            'id_cliente'      => $vehiculo ? (int) $vehiculo['id_cliente'] : 0,
            'cliente'         => $vehiculo['cliente_nombre'] ?? null,
            'vehiculo'        => $vehiculo,
            'lineas'          => $lineas,
        ]);
    }

    /** @return OrdenServicio[] */
    public function ordenesFacturables(): array
    {
        $ordenes = $this->ordenes->listar(['estados' => self::ESTADOS_OT_FACTURABLE]);
        $out = [];
        foreach ($ordenes as $orden) {
            $id = (int) $orden->getIdOrden();
            if ($this->detallesFactura->facturaActivaPorReferencia(DetalleFactura::REF_ORDEN, $id)) {
                continue;
            }
            $out[] = $orden;
        }
        return $out;
    }

    /** @return array[] */
    public function ordenesFacturablesComoArray(): array
    {
        $lista = [];
        foreach ($this->ordenesFacturables() as $orden) {
            $lista[] = $this->ordenesService->serializarOrden($orden);
        }
        return $lista;
    }

    public function emitirDesdeOrden(array $data): Factura
    {
        $idOrden   = (int) ($data['id_orden'] ?? $data['orden_id'] ?? 0);
        $createdBy = (int) ($data['created_by'] ?? 0);
        $preview   = $this->previewOrden($idOrden);
        if (!$preview['facturable']) {
            throw new AppException(
                $preview['ya_facturada'] ? 'Esta orden ya tiene una factura activa' : 'La orden no está lista para facturar',
                HTTP_BAD_REQUEST
            );
        }
        $idCliente = (int) $preview['id_cliente'];
        if ($idCliente < 1) {
            throw new AppException('La orden no tiene cliente asociado', HTTP_BAD_REQUEST);
        }

        $repuestosExtra = $data['repuestos'] ?? $data['productos'] ?? [];
        $lineasExtra = $this->normalizarRepuestos($repuestosExtra, $createdBy);
        $data['metodo_pago'] = Factura::normalizarMetodo($data['metodo_pago'] ?? 'Efectivo');
        $estadoPago = $data['estado'] ?? Factura::PENDIENTE;
        $actor = ($estadoPago === Factura::PAGADA) ? $this->actorDe($data) : null;
        if ($actor) {
            $this->caja->exigirSesionCaja($actor);
        }

        $this->db->beginTransaction();
        try {
            foreach ($lineasExtra as $extra) {
                $item = new DetalleItem(
                    $idOrden,
                    DetalleItem::TIPO_ORDEN,
                    $extra['id_producto'],
                    $extra['cantidad'],
                    $extra['precio'],
                    $createdBy
                );
                $this->detalleItems->guardar($item);
                $this->inventario->registrarMovimiento([
                    'id_producto'          => $extra['id_producto'],
                    'tipo'                 => MovimientoStock::SALIDA,
                    'cantidad'             => $extra['cantidad'],
                    'created_by'           => $createdBy,
                    'referencia_documento' => $idOrden,
                    'motivo'               => 'Repuesto extra al facturar OT #' . $idOrden,
                ]);
            }
            $preview = $this->previewOrden($idOrden);
            if (empty($preview['lineas'])) {
                throw new AppException('La orden no tiene servicios ni productos para facturar', HTTP_BAD_REQUEST);
            }
            $factura = $this->persistirFactura(
                $idCliente,
                Factura::TIPO_ORDEN,
                $preview['lineas'],
                $data,
                $createdBy,
                [
                    new DetalleFactura(0, DetalleFactura::REF_ORDEN, $idOrden, 0, $createdBy, 'Orden de servicio #' . $idOrden),
                ]
            );
            if ($estadoPago === Factura::PAGADA && $actor) {
                $this->caja->registrarCobroFactura($factura, $actor, $data);
                $this->ordenesService->cambiarEstado($idOrden, 'Completada', [
                    'updated_by' => $createdBy,
                    'token'      => $data['token'] ?? null,
                    'ip'         => $data['ip'] ?? null,
                ]);
            } elseif ($preview['estado_orden'] !== 'Pendiente Pago' && $preview['estado_orden'] !== 'Completada') {
                $this->ordenesService->cambiarEstado($idOrden, 'Pendiente Pago', [
                    'updated_by' => $createdBy,
                    'token'      => $data['token'] ?? null,
                    'ip'         => $data['ip'] ?? null,
                ]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Facturas',
            'registroId'    => (int) $factura->getIdFactura(),
            'valoresNuevos' => $factura->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);

        return $this->buscarFactura((int) $factura->getIdFactura());
    }

    public function emitirVentaDirecta(array $data): Factura
    {
        $createdBy = (int) ($data['created_by'] ?? 0);
        $idCliente = (int) ($data['id_cliente'] ?? 0);
        if ($idCliente < 1) {
            $mostrador = $this->clientes->buscarPorDocumento('2222222222');
            if (!$mostrador) {
                throw new AppException('Seleccione un cliente o cree el consumidor de mostrador', HTTP_BAD_REQUEST);
            }
            $idCliente = (int) $mostrador->getIdCliente();
        }
        $cliente = $this->clientes->buscarPorId($idCliente, false);
        if (!$cliente || !$cliente->isActivo()) {
            throw new AppException('Cliente no válido', HTTP_BAD_REQUEST);
        }
        $items = $this->normalizarRepuestos($data['items'] ?? $data['productos'] ?? [], $createdBy);
        if (!$items) {
            throw new AppException('Agregue al menos un producto', HTTP_BAD_REQUEST);
        }
        $data['metodo_pago'] = Factura::normalizarMetodo($data['metodo_pago'] ?? 'Efectivo');
        $data['estado'] = $data['estado'] ?? Factura::PAGADA;
        $actor = ($data['estado'] === Factura::PAGADA) ? $this->actorDe($data) : null;
        if ($actor) {
            $this->caja->exigirSesionCaja($actor);
        }

        $this->db->beginTransaction();
        try {
            $venta = new Venta($idCliente, 0, $createdBy);
            $this->ventas->guardarCabecera($venta);
            $lineas = [];
            $subtotal = 0.0;
            foreach ($items as $itemData) {
                $item = new DetalleItem(
                    (int) $venta->getIdVenta(),
                    DetalleItem::TIPO_VENTA,
                    $itemData['id_producto'],
                    $itemData['cantidad'],
                    $itemData['precio'],
                    $createdBy
                );
                $this->detalleItems->guardar($item);
                $nombre = $itemData['nombre'];
                $lineas[] = [
                    'tipo'        => DetalleFactura::REF_PRODUCTO,
                    'id'          => $itemData['id_producto'],
                    'descripcion' => $nombre . ' x' . $itemData['cantidad'],
                    'cantidad'    => $itemData['cantidad'],
                    'precio'      => $itemData['precio'],
                    'monto'       => $item->subtotal(),
                ];
                $subtotal += $item->subtotal();
            }
            $totales = $this->config->calcularTotales($subtotal);
            $venta->setTotal($totales['total']);
            $this->db->query(
                'UPDATE Ventas SET total = :t WHERE id_venta = :id',
                [':t' => $venta->getTotal(), ':id' => $venta->getIdVenta()]
            );

            $factura = $this->persistirFactura(
                $idCliente,
                Factura::TIPO_MOSTRADOR,
                $lineas,
                $data,
                $createdBy,
                [
                    new DetalleFactura(0, DetalleFactura::REF_VENTA, (int) $venta->getIdVenta(), 0, $createdBy, 'Venta de mostrador #' . $venta->getIdVenta()),
                ]
            );
            foreach ($items as $itemData) {
                $this->inventario->registrarMovimiento([
                    'id_producto'          => $itemData['id_producto'],
                    'tipo'                 => MovimientoStock::SALIDA,
                    'cantidad'             => $itemData['cantidad'],
                    'created_by'           => $createdBy,
                    'referencia_documento' => (int) $factura->getIdFactura(),
                    'motivo'               => 'Venta de mostrador',
                ]);
            }
            if ($actor) {
                $this->caja->registrarCobroFactura($factura, $actor, $data);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Ventas',
            'registroId'    => (int) $venta->getIdVenta(),
            'valoresNuevos' => ['id_factura' => $factura->getIdFactura(), 'total' => $venta->getTotal()],
            'ipAddress'     => $data['ip'] ?? null,
        ]);

        return $this->buscarFactura((int) $factura->getIdFactura());
    }

    public function actualizarEstado(int $id, array $data): Factura
    {
        $factura = $this->buscarFactura($id);
        if ($factura->isAnulada()) {
            throw new AppException('La factura está anulada', HTTP_BAD_REQUEST);
        }
        if (isset($data['metodo_pago'])) {
            $factura->setMetodoPago(Factura::normalizarMetodo((string) $data['metodo_pago']));
        }
        $nuevo = $data['estado'] ?? null;
        if ($nuevo === Factura::PAGADA && $factura->getEstado() === Factura::PAGADA) {
            throw new AppException('Esta factura ya está pagada', HTTP_BAD_REQUEST);
        }
        $cobrar = $nuevo === Factura::PAGADA && $factura->getEstado() !== Factura::PAGADA;
        $actor = $cobrar ? $this->actorDe($data) : null;
        if ($actor) {
            $this->caja->exigirSesionCaja($actor);
        }

        $this->db->beginTransaction();
        try {
            if ($cobrar && $actor) {
                $factura->marcarPagada($data['updated_by'] ?? null);
                $this->caja->registrarCobroFactura($factura, $actor, $data);
                $idOrden = $this->idOrdenDeFactura($factura);
                if ($idOrden) {
                    $this->ordenesService->cambiarEstado($idOrden, 'Completada', [
                        'updated_by' => $data['updated_by'] ?? null,
                        'token'      => $data['token'] ?? null,
                    ]);
                }
            }
            $this->facturas->guardarCabecera($factura);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        return $this->buscarFactura($id);
    }

    public function anular(int $id, array $contexto = []): Factura
    {
        $factura = $this->buscarFactura($id);
        $estabaPagada = $factura->getEstado() === Factura::PAGADA;
        $actor = $estabaPagada ? $this->actorDe($contexto) : null;
        if ($actor) {
            $this->caja->exigirSesionCaja($actor);
        }
        $factura->anular($contexto['updated_by'] ?? null);

        $this->db->beginTransaction();
        try {
            foreach ($factura->getDetalles() as $detalle) {
                if ($factura->getTipoFactura() !== Factura::TIPO_MOSTRADOR) {
                    break;
                }
                if ($detalle->getTipoReferencia() !== DetalleFactura::REF_PRODUCTO) {
                    continue;
                }
                $cantidad = $this->cantidadDesdeDescripcion($detalle->getDescripcion() ?? '');
                if ($cantidad < 1) {
                    continue;
                }
                $this->inventario->registrarMovimiento([
                    'id_producto'          => $detalle->getIdReferencia(),
                    'tipo'                 => MovimientoStock::ENTRADA,
                    'cantidad'             => $cantidad,
                    'created_by'           => (int) ($contexto['updated_by'] ?? $factura->getCreatedBy()),
                    'referencia_documento' => (int) $factura->getIdFactura(),
                    'motivo'               => 'Anulación de venta de mostrador',
                ]);
            }
            $this->facturas->guardarCabecera($factura);
            if ($actor) {
                $this->caja->registrarAnulacionFactura($factura, $actor, $contexto);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'DELETE',
            'tablaAfectada' => 'Facturas',
            'registroId'    => $id,
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);

        return $this->buscarFactura($id);
    }

    /**
     * @param array<int,array{tipo:string,id:int,descripcion:string,cantidad:int,precio:float,monto:float}> $lineas
     * @param DetalleFactura[] $extras
     */
    private function persistirFactura(
        int $idCliente,
        string $tipo,
        array $lineas,
        array $data,
        int $createdBy,
        array $extras = []
    ): Factura {
        $subtotal = 0.0;
        foreach ($lineas as $linea) {
            $subtotal += (float) $linea['monto'];
        }
        $totales = $this->config->calcularTotales($subtotal);
        $estado  = (string) ($data['estado'] ?? Factura::PENDIENTE);
        if (!in_array($estado, [Factura::PENDIENTE, Factura::PAGADA], true)) {
            $estado = Factura::PENDIENTE;
        }
        $metodo = Factura::normalizarMetodo($data['metodo_pago'] ?? Factura::METODO_EFECTIVO);
        $factura = new Factura(
            $idCliente,
            $totales['subtotal'],
            $totales['iva'],
            $totales['total'],
            $metodo,
            $tipo,
            $createdBy,
            $estado
        );
        $this->facturas->guardarCabecera($factura);
        $idFactura = (int) $factura->getIdFactura();
        foreach ($extras as $extra) {
            $extra->setIdFactura($idFactura);
            $this->detallesFactura->guardar($extra);
        }
        foreach ($lineas as $linea) {
            $det = new DetalleFactura(
                $idFactura,
                $linea['tipo'],
                (int) $linea['id'],
                (float) $linea['monto'],
                $createdBy,
                (string) $linea['descripcion']
            );
            $this->detallesFactura->guardar($det);
        }
        return $factura;
    }

    /** @return array<int,array{id_producto:int,cantidad:int,precio:float,nombre:string}> */
    private function normalizarRepuestos(array $raw, int $createdBy): array
    {
        $agrupados = [];
        foreach ($raw as $fila) {
            $id = (int) ($fila['id_producto'] ?? $fila['producto_id'] ?? 0);
            $cant = (int) ($fila['cantidad'] ?? 0);
            if ($id < 1 || $cant < 1) {
                continue;
            }
            $agrupados[$id] = ($agrupados[$id] ?? 0) + $cant;
        }
        $out = [];
        foreach ($agrupados as $id => $cant) {
            $producto = $this->inventario->buscarProducto($id);
            $disponible = $producto->getStock() ? $producto->getStock()->getCantidad() : 0;
            if ($disponible < $cant) {
                throw new AppException(sprintf(
                    'No hay suficientes unidades de "%s". Disponible: %d. Solicitado: %d.',
                    $producto->getNombre(),
                    $disponible,
                    $cant
                ), HTTP_BAD_REQUEST);
            }
            $out[] = [
                'id_producto' => $id,
                'cantidad'    => $cant,
                'precio'      => $producto->getPrecioUnitario(),
                'nombre'      => $producto->getNombre(),
            ];
        }
        return $out;
    }

    public function serializarFactura(Factura $factura): array
    {
        $arr = $factura->toArray();
        $idOrden = $this->idOrdenDeFactura($factura);
        if (!$idOrden) {
            return $arr;
        }
        $orden = $this->ordenes->buscarPorId($idOrden, false);
        if (!$orden) {
            $arr['id_orden'] = $idOrden;
            return $arr;
        }
        $vehiculo = $this->ordenes->obtenerVehiculoConCliente($orden->getIdVehiculo()) ?: [];
        $arr['id_orden'] = $idOrden;
        $arr['id_vehiculo'] = $orden->getIdVehiculo();
        $arr['vehiculo'] = $vehiculo ?: null;
        $arr['vehiculo_placa'] = $vehiculo['placa'] ?? null;
        return $arr;
    }

    public function serializarVenta(Venta $venta): array
    {
        $arr = $venta->toArray();
        $idFac = $this->detallesFactura->facturaPorReferencia(
            DetalleFactura::REF_VENTA,
            (int) $venta->getIdVenta()
        );
        if ($idFac) {
            $factura = $this->facturas->buscarPorId($idFac);
            $arr['id_factura'] = $idFac;
            $arr['numero_factura'] = $factura ? $factura->numero() : ('FAC-' . $idFac);
            $arr['estado_factura'] = $factura ? $factura->getEstado() : null;
            $arr['metodo_pago'] = $factura ? $factura->getMetodoPago() : null;
        }
        return $arr;
    }

    private function idOrdenDeFactura(Factura $factura): ?int
    {
        foreach ($factura->getDetalles() as $detalle) {
            if ($detalle->getTipoReferencia() === DetalleFactura::REF_ORDEN) {
                return $detalle->getIdReferencia();
            }
        }
        return null;
    }

    private function cantidadDesdeDescripcion(string $texto): int
    {
        if (preg_match('/x(\d+)/i', $texto, $m)) {
            return (int) $m[1];
        }
        return 1;
    }

    private function actorDe(array $data): Usuario
    {
        if (!empty($data['actor']) && $data['actor'] instanceof Usuario) {
            return $data['actor'];
        }
        throw new AppException('No se identificó el usuario para registrar el movimiento de caja', HTTP_BAD_REQUEST);
    }
}
