<?php

require_once __DIR__ . '/../../models/Inventario/Stock.php';

class StockRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function buscarPorProducto(int $idProducto): ?Stock
    {
        $stmt = $this->db->query(
            'SELECT * FROM Stock WHERE id_producto = :id ORDER BY id_stock DESC LIMIT 1',
            [':id' => $idProducto]
        );
        $fila = $stmt->fetch();
        return $fila ? Stock::fromArray($fila) : null;
    }

    public function guardar(Stock $stock): Stock
    {
        if ($stock->getIdStock() === null) {
            $this->db->query(
                'INSERT INTO Stock (id_producto, cantidad, stock_minimo, precio_compra, ubicacion, created_at, created_by)
                 VALUES (:p, :c, :m, :pc, :u, :ca, :cb)',
                [
                    ':p'  => $stock->getIdProducto(),
                    ':c'  => $stock->getCantidad(),
                    ':m'  => $stock->getStockMinimo(),
                    ':pc' => $stock->getPrecioCompra(),
                    ':u'  => $stock->getUbicacion(),
                    ':ca' => $stock->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':cb' => $stock->getCreatedBy(),
                ]
            );
            $stock->assignId((int) $this->db->lastInsertId());
            return $stock;
        }
        $this->db->query(
            'UPDATE Stock SET
                cantidad = :c, stock_minimo = :m, precio_compra = :pc,
                ubicacion = :u, updated_at = :ua, updated_by = :ub
             WHERE id_stock = :id',
            [
                ':c'  => $stock->getCantidad(),
                ':m'  => $stock->getStockMinimo(),
                ':pc' => $stock->getPrecioCompra(),
                ':u'  => $stock->getUbicacion(),
                ':ua' => $stock->getUpdatedAt() ? $stock->getUpdatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ':ub' => $stock->getUpdatedBy(),
                ':id' => $stock->getIdStock(),
            ]
        );
        return $stock;
    }
}
