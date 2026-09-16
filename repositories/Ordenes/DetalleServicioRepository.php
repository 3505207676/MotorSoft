<?php

require_once __DIR__ . '/../../models/Ordenes/DetalleServicio.php';
require_once __DIR__ . '/ServicioRepository.php';

class DetalleServicioRepository
{
    private Database $db;
    private ServicioRepository $servicios;

    public function __construct(Database $db, ?ServicioRepository $servicios = null)
    {
        $this->db        = $db;
        $this->servicios = $servicios ?: new ServicioRepository($db);
    }

    public function buscarPorId(int $id): ?DetalleServicio
    {
        $stmt = $this->db->query(
            'SELECT * FROM Detalles_Servicios WHERE id_detalle = :id LIMIT 1',
            [':id' => $id]
        );
        $fila = $stmt->fetch();
        return $fila ? $this->hydrate($fila) : null;
    }

    /** @return DetalleServicio[] */
    public function listarPorOrden(int $idOrden): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Detalles_Servicios WHERE id_orden = :id ORDER BY id_detalle',
            [':id' => $idOrden]
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = $this->hydrate($fila);
        }
        return $lista;
    }

    /**
     * @param int[] $idsOrden
     * @return array<int, DetalleServicio[]>
     */
    public function listarPorOrdenes(array $idsOrden): array
    {
        $agrupado = [];
        foreach ($idsOrden as $id) {
            $agrupado[(int) $id] = [];
        }
        if (empty($idsOrden)) {
            return $agrupado;
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($idsOrden) as $i => $id) {
            $key = ':id' . $i;
            $placeholders[] = $key;
            $params[$key] = (int) $id;
        }

        $sql = 'SELECT * FROM Detalles_Servicios WHERE id_orden IN (' . implode(',', $placeholders) . ')';
        $stmt = $this->db->query($sql, $params);
        foreach ($stmt->fetchAll() as $fila) {
            $detalle = $this->hydrate($fila);
            $agrupado[$detalle->getIdOrden()][] = $detalle;
        }
        return $agrupado;
    }

    public function guardar(DetalleServicio $detalle): DetalleServicio
    {
        $detalle->calcularSubtotal();

        if ($detalle->getIdDetalle() === null) {
            $sql = 'INSERT INTO Detalles_Servicios (
                        id_orden, id_servicio, precio, cantidad, subtotal, created_at, created_by
                    ) VALUES (
                        :id_orden, :id_servicio, :precio, :cantidad, :subtotal, :created_at, :created_by
                    )';
            $this->db->query($sql, [
                ':id_orden'    => $detalle->getIdOrden(),
                ':id_servicio' => $detalle->getIdServicio(),
                ':precio'      => $detalle->getPrecio(),
                ':cantidad'    => $detalle->getCantidad(),
                ':subtotal'    => $detalle->getSubtotal(),
                ':created_at'  => $detalle->getCreatedAt()->format('Y-m-d H:i:s'),
                ':created_by'  => $detalle->getCreatedBy(),
            ]);
            $detalle->assignId((int) $this->db->lastInsertId());
            return $detalle;
        }

        $sql = 'UPDATE Detalles_Servicios SET
                    id_servicio = :id_servicio,
                    precio = :precio,
                    cantidad = :cantidad,
                    subtotal = :subtotal
                WHERE id_detalle = :id';
        $this->db->query($sql, [
            ':id_servicio' => $detalle->getIdServicio(),
            ':precio'      => $detalle->getPrecio(),
            ':cantidad'    => $detalle->getCantidad(),
            ':subtotal'    => $detalle->getSubtotal(),
            ':id'          => $detalle->getIdDetalle(),
        ]);
        return $detalle;
    }

    public function eliminar(int $idDetalle): void
    {
        $this->db->query(
            'DELETE FROM Detalles_Servicios WHERE id_detalle = :id',
            [':id' => $idDetalle]
        );
    }

    public function eliminarPorOrden(int $idOrden): void
    {
        $this->db->query(
            'DELETE FROM Detalles_Servicios WHERE id_orden = :id',
            [':id' => $idOrden]
        );
    }

    private function hydrate(array $fila): DetalleServicio
    {
        $servicio = !empty($fila['id_servicio'])
            ? $this->servicios->buscarPorId((int) $fila['id_servicio'])
            : null;
        return DetalleServicio::fromArray($fila, $servicio);
    }
}
