<?php

require_once __DIR__ . '/../../models/Inventario/MovimientoStock.php';

class MovimientoStockRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function guardar(MovimientoStock $movimiento): MovimientoStock
    {
        $this->db->query(
            'INSERT INTO Movimientos (id_producto, tipo_movimiento, cantidad, cantidad_antes, cantidad_despues, motivo, fecha_hora, referencia_documento, created_by)
             VALUES (:p, :t, :c, :a, :d, :m, :f, :r, :u)',
            [
                ':p' => $movimiento->getIdProducto(),
                ':t' => $movimiento->getTipoMovimiento(),
                ':c' => $movimiento->getCantidad(),
                ':a' => $movimiento->getCantidadAntes(),
                ':d' => $movimiento->getCantidadDespues(),
                ':m' => $movimiento->getMotivo(),
                ':f' => $movimiento->getFechaHora()->format('Y-m-d H:i:s'),
                ':r' => $movimiento->getReferenciaDocumento(),
                ':u' => $movimiento->getCreatedBy(),
            ]
        );
        $movimiento->assignId((int) $this->db->lastInsertId());
        return $movimiento;
    }

    /** @return MovimientoStock[] */
    public function listarPorProducto(int $idProducto): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Movimientos WHERE id_producto = :id ORDER BY fecha_hora DESC, id_movimiento DESC',
            [':id' => $idProducto]
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = MovimientoStock::fromArray($fila);
        }
        return $lista;
    }
}
