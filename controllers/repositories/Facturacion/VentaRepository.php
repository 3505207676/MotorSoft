<?php

require_once __DIR__ . '/../../models/Facturacion/Venta.php';
require_once __DIR__ . '/DetalleItemRepository.php';
require_once __DIR__ . '/../Clientes/ClienteRepository.php';

class VentaRepository
{
    private Database $db;
    private DetalleItemRepository $items;
    private ClienteRepository $clientes;

    public function __construct(Database $db, DetalleItemRepository $items, ClienteRepository $clientes)
    {
        $this->db       = $db;
        $this->items    = $items;
        $this->clientes = $clientes;
    }

    public function buscarPorId(int $id): ?Venta
    {
        $stmt = $this->db->query(
            'SELECT * FROM Ventas WHERE id_venta = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        return $this->hydrate($stmt->fetch() ?: null);
    }

    /** @return Venta[] */
    public function listar(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Ventas WHERE deleted_at IS NULL ORDER BY fecha DESC, id_venta DESC'
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $venta = $this->hydrate($fila);
            if ($venta) {
                $lista[] = $venta;
            }
        }
        return $lista;
    }

    public function guardarCabecera(Venta $venta): Venta
    {
        $this->db->query(
            'INSERT INTO Ventas (id_cliente, fecha, total, created_at, created_by)
             VALUES (:c, :f, :t, :ca, :cb)',
            [
                ':c'  => $venta->getIdCliente(),
                ':f'  => $venta->getFecha()->format('Y-m-d H:i:s'),
                ':t'  => $venta->getTotal(),
                ':ca' => $venta->getCreatedAt()->format('Y-m-d H:i:s'),
                ':cb' => $venta->getCreatedBy(),
            ]
        );
        $venta->assignId((int) $this->db->lastInsertId());
        return $venta;
    }

    private function hydrate(?array $fila): ?Venta
    {
        if (!$fila) {
            return null;
        }
        $cliente = !empty($fila['id_cliente']) ? $this->clientes->buscarPorId((int) $fila['id_cliente'], false) : null;
        $items   = !empty($fila['id_venta'])
            ? $this->items->listarPorPadre(DetalleItem::TIPO_VENTA, (int) $fila['id_venta'])
            : [];
        $venta = Venta::fromArray($fila, $items, $cliente);
        return $venta;
    }
}
