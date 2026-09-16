<?php

require_once __DIR__ . '/../../models/Facturacion/DetalleFactura.php';
require_once __DIR__ . '/../../models/Facturacion/Factura.php';

class DetalleFacturaRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function guardar(DetalleFactura $detalle): DetalleFactura
    {
        $this->db->query(
            'INSERT INTO Detalles_Factura (
                id_factura, tipo_referencia, id_referencia, descripcion, monto, created_at, created_by
             ) VALUES (:f, :t, :r, :d, :m, :c, :u)',
            [
                ':f' => $detalle->getIdFactura(),
                ':t' => $detalle->getTipoReferencia(),
                ':r' => $detalle->getIdReferencia(),
                ':d' => $detalle->getDescripcion(),
                ':m' => $detalle->getMonto(),
                ':c' => date('Y-m-d H:i:s'),
                ':u' => $detalle->getCreatedBy(),
            ]
        );
        $detalle->assignId((int) $this->db->lastInsertId());
        return $detalle;
    }

    /** @return DetalleFactura[] */
    public function listarPorFactura(int $idFactura): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Detalles_Factura WHERE id_factura = :id AND deleted_at IS NULL ORDER BY id_detalle_factura',
            [':id' => $idFactura]
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = DetalleFactura::fromArray($fila);
        }
        return $lista;
    }

    public function facturaPorReferencia(string $tipo, int $idReferencia): ?int
    {
        $stmt = $this->db->query(
            'SELECT f.id_factura
             FROM Detalles_Factura d
             INNER JOIN Facturas f ON f.id_factura = d.id_factura
             WHERE d.tipo_referencia = :t
               AND d.id_referencia = :r
               AND f.deleted_at IS NULL
             ORDER BY f.id_factura DESC
             LIMIT 1',
            [':t' => $tipo, ':r' => $idReferencia]
        );
        $fila = $stmt->fetch();
        return $fila ? (int) $fila['id_factura'] : null;
    }

    public function facturaActivaPorReferencia(string $tipo, int $idReferencia): ?int
    {
        $stmt = $this->db->query(
            'SELECT f.id_factura
             FROM Detalles_Factura d
             INNER JOIN Facturas f ON f.id_factura = d.id_factura
             WHERE d.tipo_referencia = :t
               AND d.id_referencia = :r
               AND f.deleted_at IS NULL
               AND f.estado <> :an
             LIMIT 1',
            [':t' => $tipo, ':r' => $idReferencia, ':an' => Factura::ANULADA]
        );
        $fila = $stmt->fetch();
        return $fila ? (int) $fila['id_factura'] : null;
    }
}
