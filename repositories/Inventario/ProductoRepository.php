<?php

require_once __DIR__ . '/../../models/Inventario/Producto.php';
require_once __DIR__ . '/CategoriaRepository.php';
require_once __DIR__ . '/StockRepository.php';

class ProductoRepository
{
    private Database $db;
    private CategoriaRepository $categorias;
    private StockRepository $stocks;

    public function __construct(Database $db, CategoriaRepository $categorias, StockRepository $stocks)
    {
        $this->db         = $db;
        $this->categorias = $categorias;
        $this->stocks     = $stocks;
    }

    public function buscarPorId(int $id): ?Producto
    {
        $stmt = $this->db->query(
            'SELECT * FROM Productos WHERE id_producto = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        return $this->hydrate($stmt->fetch() ?: null);
    }

    public function referenciaExiste(string $referencia, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM Productos WHERE referencia = :r AND deleted_at IS NULL';
        $params = [':r' => strtoupper(trim($referencia))];
        if ($exceptoId !== null) {
            $sql .= ' AND id_producto <> :id';
            $params[':id'] = $exceptoId;
        }
        $fila = $this->db->query($sql, $params)->fetch();
        return (int) $fila['total'] > 0;
    }

    /** @return Producto[] */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT p.* FROM Productos p WHERE p.deleted_at IS NULL';
        $params = [];
        if (!empty($filtros['id_categoria'])) {
            $sql .= ' AND p.id_categoria = :cat';
            $params[':cat'] = (int) $filtros['id_categoria'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= ' AND (p.nombre LIKE :q1 OR p.referencia LIKE :q2 OR p.descripcion LIKE :q3)';
            $like = '%' . $filtros['busqueda'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }
        $sql .= ' ORDER BY p.nombre ASC';
        $stmt = $this->db->query($sql, $params);
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $producto = $this->hydrate($fila);
            if (!$producto) {
                continue;
            }
            if (!empty($filtros['stock_bajo']) && !$producto->isStockBajo()) {
                continue;
            }
            $lista[] = $producto;
        }
        return $lista;
    }

    public function guardar(Producto $producto): Producto
    {
        if ($producto->getIdProducto() === null) {
            $this->db->query(
                'INSERT INTO Productos (
                    id_categoria, nombre, descripcion, referencia, precio_unitario,
                    tipo, codigo_barras, estado, created_at, created_by
                 ) VALUES (
                    :cat, :n, :d, :r, :p, :t, :cb, :e, :ca, :cu
                 )',
                [
                    ':cat' => $producto->getIdCategoria(),
                    ':n'   => $producto->getNombre(),
                    ':d'   => $producto->getDescripcion(),
                    ':r'   => $producto->getReferencia(),
                    ':p'   => $producto->getPrecioUnitario(),
                    ':t'   => $producto->getTipo(),
                    ':cb'  => $producto->getCodigoBarras(),
                    ':e'   => $producto->getEstado(),
                    ':ca'  => $producto->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':cu'  => $producto->getCreatedBy(),
                ]
            );
            $producto->assignId((int) $this->db->lastInsertId());
            return $producto;
        }
        $this->db->query(
            'UPDATE Productos SET
                id_categoria = :cat, nombre = :n, descripcion = :d, referencia = :r,
                precio_unitario = :p, tipo = :t, codigo_barras = :cb, estado = :e,
                updated_at = :ua, updated_by = :ub, deleted_at = :da
             WHERE id_producto = :id',
            [
                ':cat' => $producto->getIdCategoria(),
                ':n'   => $producto->getNombre(),
                ':d'   => $producto->getDescripcion(),
                ':r'   => $producto->getReferencia(),
                ':p'   => $producto->getPrecioUnitario(),
                ':t'   => $producto->getTipo(),
                ':cb'  => $producto->getCodigoBarras(),
                ':e'   => $producto->getEstado(),
                ':ua'  => $producto->getUpdatedAt() ? $producto->getUpdatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ':ub'  => $producto->getUpdatedBy(),
                ':da'  => $producto->getDeletedAt() ? $producto->getDeletedAt()->format('Y-m-d H:i:s') : null,
                ':id'  => $producto->getIdProducto(),
            ]
        );
        return $producto;
    }

    private function hydrate(?array $fila): ?Producto
    {
        if (!$fila) {
            return null;
        }
        $categoria = !empty($fila['id_categoria'])
            ? $this->categorias->buscarPorId((int) $fila['id_categoria'])
            : null;
        $stock = !empty($fila['id_producto'])
            ? $this->stocks->buscarPorProducto((int) $fila['id_producto'])
            : null;
        return Producto::fromArray($fila, $categoria, $stock);
    }
}
