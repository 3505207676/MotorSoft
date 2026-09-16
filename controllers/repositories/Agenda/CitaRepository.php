<?php

require_once __DIR__ . '/../../models/Agenda/Cita.php';
require_once __DIR__ . '/HorarioRepository.php';

class CitaRepository
{
    private Database $db;
    private HorarioRepository $horarios;

    public function __construct(Database $db, HorarioRepository $horarios)
    {
        $this->db       = $db;
        $this->horarios = $horarios;
    }

    private const SELECT = 'SELECT c.*,
            cl.nombre AS cliente_nombre,
            v.placa AS vehiculo_placa,
            v.tipo AS vehiculo_tipo,
            v.marca AS vehiculo_marca,
            v.modelo AS vehiculo_modelo,
            s.nombre AS servicio_nombre,
            u.nombre AS mecanico_nombre
         FROM Citas c
         INNER JOIN Clientes cl ON cl.id_cliente = c.id_cliente
         INNER JOIN Vehiculo v ON v.id_vehiculo = c.id_vehiculo
         INNER JOIN Servicios s ON s.id_servicio = c.id_servicio
         INNER JOIN Agenda_Disponible h ON h.id_horario = c.id_horario
         INNER JOIN Usuarios u ON u.id_usuario = h.id_usuario';

    public function buscarPorId(int $id): ?Cita
    {
        $fila = $this->db->query(
            self::SELECT . ' WHERE c.id_cita = :id AND c.deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        )->fetch();
        return $this->hydrate($fila ?: null);
    }

    public function contarActivasPorHorario(int $idHorario): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS total FROM Citas
             WHERE id_horario = :h AND deleted_at IS NULL
               AND estado_cita IN (:p, :c)',
            [
                ':h' => $idHorario,
                ':p' => Cita::PROGRAMADA,
                ':c' => Cita::CONFIRMADA,
            ]
        )->fetch();
        return (int) ($fila['total'] ?? 0);
    }

    /** @return Cita[] */
    public function listar(array $filtros = []): array
    {
        $sql = self::SELECT . ' WHERE c.deleted_at IS NULL';
        $params = [];
        if (!empty($filtros['id_cliente'])) {
            $sql .= ' AND c.id_cliente = :cli';
            $params[':cli'] = (int) $filtros['id_cliente'];
        }
        if (!empty($filtros['id_mecanico'])) {
            $sql .= ' AND h.id_usuario = :mec';
            $params[':mec'] = (int) $filtros['id_mecanico'];
        }
        if (!empty($filtros['fecha'])) {
            $sql .= ' AND h.fecha = :fecha';
            $params[':fecha'] = $filtros['fecha'];
        }
        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $sql .= ' AND c.estado_cita = :est';
            $params[':est'] = $filtros['estado'];
        }
        $sql .= ' ORDER BY h.fecha DESC, h.hora_inicio DESC, c.id_cita DESC';
        $lista = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $lista[] = $this->hydrate($fila);
        }
        return $lista;
    }

    public function guardar(Cita $cita): Cita
    {
        if ($cita->getIdCita() === null) {
            $this->db->query(
                'INSERT INTO Citas
                    (id_cliente, id_vehiculo, id_orden, id_servicio, id_horario, motivo,
                     estado_cita, created_at, created_by)
                 VALUES
                    (:id_cliente, :id_vehiculo, :id_orden, :id_servicio, :id_horario, :motivo,
                     :estado_cita, :created_at, :created_by)',
                [
                    ':id_cliente'  => $cita->getIdCliente(),
                    ':id_vehiculo' => $cita->getIdVehiculo(),
                    ':id_orden'    => $cita->getIdOrden(),
                    ':id_servicio' => $cita->getIdServicio(),
                    ':id_horario'  => $cita->getIdHorario(),
                    ':motivo'      => $cita->getMotivo(),
                    ':estado_cita' => $cita->getEstadoCita(),
                    ':created_at'  => $cita->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':created_by'  => $cita->getCreatedBy(),
                ]
            );
            $cita->assignId((int) $this->db->lastInsertId());
            return $this->buscarPorId((int) $cita->getIdCita()) ?: $cita;
        }
        $this->db->query(
            'UPDATE Citas SET
                id_vehiculo = :id_vehiculo,
                id_orden = :id_orden,
                id_servicio = :id_servicio,
                id_horario = :id_horario,
                motivo = :motivo,
                estado_cita = :estado_cita,
                updated_at = :updated_at,
                updated_by = :updated_by,
                deleted_at = :deleted_at
             WHERE id_cita = :id',
            [
                ':id_vehiculo' => $cita->getIdVehiculo(),
                ':id_orden'    => $cita->getIdOrden(),
                ':id_servicio' => $cita->getIdServicio(),
                ':id_horario'  => $cita->getIdHorario(),
                ':motivo'      => $cita->getMotivo(),
                ':estado_cita' => $cita->getEstadoCita(),
                ':updated_at'  => $cita->getUpdatedAt()
                    ? $cita->getUpdatedAt()->format('Y-m-d H:i:s')
                    : date('Y-m-d H:i:s'),
                ':updated_by'  => $cita->getUpdatedBy(),
                ':deleted_at'  => $cita->getDeletedAt()
                    ? $cita->getDeletedAt()->format('Y-m-d H:i:s')
                    : null,
                ':id'          => $cita->getIdCita(),
            ]
        );
        return $this->buscarPorId((int) $cita->getIdCita()) ?: $cita;
    }

    private function hydrate(?array $fila): ?Cita
    {
        if (!$fila) {
            return null;
        }
        $horario = !empty($fila['id_horario'])
            ? $this->horarios->buscarPorId((int) $fila['id_horario'])
            : null;
        $cita = Cita::fromArray($fila, $horario);
        if ($horario) {
            $cita->setHorario($horario);
        }
        return $cita;
    }
}
