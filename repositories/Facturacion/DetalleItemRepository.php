<?php

require_once __DIR__ . '/../../models/Facturacion/DetalleItem.php';
require_once __DIR__ . '/../Inventario/ProductoRepository.php';

class DetalleItemRepository
{
    private Database $db;
    private ProductoRepository $productos;

    public function __construct(Database $db, ProductoRepository $productos)
    {
        $this->db        = $db;
        $this->productos = $productos;
    }

    public function guardar(DetalleItem $item): DetalleItem
    {
        $this->db->query(
            'INSERT INTO Detalle_Items (
                itemable_id, itemable_type, id_producto, cantidad, precio_unitario, descuento, created_at, created_by
             ) VALUES (:iid, :it, :p, :c, :pr, :d, :ca, :cb)',
            [
                ':iid' => $item->getItemableId(),
                ':it'  => $item->getItemableType(),
                ':p'   => $item->getIdProducto(),
                ':c'   => $item->getCantidad(),
                ':pr'  => $item->getPrecioUnitario(),
                ':d'   => $item->getDescuento(),
                ':ca'  => date('Y-m-d H:i:s'),
                ':cb'  => $item->getCreatedBy(),
            ]
        );
        $item->assignId((int) $this->db->lastInsertId());
        return $item;
    }

    /** @return DetalleItem[] */
    public function listarPorPadre(string $tipo, int $idPadre): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Detalle_Items
             WHERE itemable_type = :t AND itemable_id = :id AND deleted_at IS NULL
             ORDER BY id_items',
            [':t' => $tipo, ':id' => $idPadre]
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $producto = $this->productos->buscarPorId((int) $fila['id_producto']);
            $lista[] = DetalleItem::fromArray($fila, $producto);
        }
        return $lista;
    }
}
