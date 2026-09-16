<?php

require_once __DIR__ . '/../../models/Facturacion/Factura.php';
require_once __DIR__ . '/DetalleFacturaRepository.php';
require_once __DIR__ . '/../Clientes/ClienteRepository.php';

class FacturaRepository
{
    private Database $db;
    private DetalleFacturaRepository $detalles;
    private ClienteRepository $clientes;
    private string $prefijo = 'FAC';

    public function __construct(Database $db, DetalleFacturaRepository $detalles, ClienteRepository $clientes)
    {
        $this->db       = $db;
        $this->detalles = $detalles;
        $this->clientes = $clientes;
    }

    public function setPrefijo(string $prefijo): void
    {
        $this->prefijo = $prefijo !== '' ? $prefijo : 'FAC';
    }

    public function buscarPorId(int $id): ?Factura
    {
        $stmt = $this->db->query(
            'SELECT * FROM Facturas WHERE id_factura = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        return $this->hydrate($stmt->fetch() ?: null);
    }

    /** @return Factura[] */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT * FROM Facturas WHERE deleted_at IS NULL';
        $params = [];
        if (!empty($filtros['estado'])) {
            $sql .= ' AND estado = :est';
            $params[':est'] = $filtros['estado'];
        }
        if (!empty($filtros['tipo_factura'])) {
            $sql .= ' AND tipo_factura = :tf';
            $params[':tf'] = $filtros['tipo_factura'];
        }
        if (!empty($filtros['id_cliente'])) {
            $sql .= ' AND id_cliente = :cli';
            $params[':cli'] = (int) $filtros['id_cliente'];
        }
        if (!empty($filtros['desde'])) {
            $sql .= ' AND fecha_emision >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $sql .= ' AND fecha_emision < DATE_ADD(:hasta, INTERVAL 1 DAY)';
            $params[':hasta'] = $filtros['hasta'];
        }
        $sql .= ' ORDER BY fecha_emision DESC, id_factura DESC';
        $conDetalles = $filtros['con_detalles'] ?? true;
        $lista = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $factura = $this->hydrate($fila, (bool) $conDetalles);
            if ($factura) {
                $lista[] = $factura;
            }
        }
        return $lista;
    }

    public function guardarCabecera(Factura $factura): Factura
    {
        if ($factura->getIdFactura() === null) {
            $this->db->query(
                'INSERT INTO Facturas (
                    id_cliente, fecha_emision, subtotal, IVA, total, metodo_pago, estado, tipo_factura, created_at, created_by
                 ) VALUES (
                    :cli, :fe, :sub, :iva, :tot, :mp, :est, :tf, :ca, :cb
                 )',
                [
                    ':cli' => $factura->getIdCliente(),
                    ':fe'  => $factura->getFechaEmision()->format('Y-m-d H:i:s'),
                    ':sub' => $factura->getSubtotal(),
                    ':iva' => $factura->getIva(),
                    ':tot' => $factura->getTotal(),
                    ':mp'  => $factura->getMetodoPago(),
                    ':est' => $factura->getEstado(),
                    ':tf'  => $factura->getTipoFactura(),
                    ':ca'  => $factura->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':cb'  => $factura->getCreatedBy(),
                ]
            );
            $factura->assignId((int) $this->db->lastInsertId());
            return $factura;
        }
        $this->db->query(
            'UPDATE Facturas SET
                metodo_pago = :mp, estado = :est, updated_at = :ua, updated_by = :ub, deleted_at = :da
             WHERE id_factura = :id',
            [
                ':mp' => $factura->getMetodoPago(),
                ':est'=> $factura->getEstado(),
                ':ua' => $factura->getUpdatedAt() ? $factura->getUpdatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ':ub' => $factura->getUpdatedBy(),
                ':da' => $factura->getDeletedAt() ? $factura->getDeletedAt()->format('Y-m-d H:i:s') : null,
                ':id' => $factura->getIdFactura(),
            ]
        );
        return $factura;
    }

    private function hydrate(?array $fila, bool $conDetalles = true): ?Factura
    {
        if (!$fila) {
            return null;
        }
        $cliente  = !empty($fila['id_cliente']) ? $this->clientes->buscarPorId((int) $fila['id_cliente'], false) : null;
        $detalles = ($conDetalles && !empty($fila['id_factura']))
            ? $this->detalles->listarPorFactura((int) $fila['id_factura'])
            : [];
        $factura  = Factura::fromArray($fila, $detalles, $cliente);
        $factura->setPrefijo($this->prefijo);
        return $factura;
    }
}
