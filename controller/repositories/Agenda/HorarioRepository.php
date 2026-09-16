<?php

require_once __DIR__ . '/../../models/Agenda/Horario.php';
require_once __DIR__ . '/../../models/Agenda/Cita.php';

class HorarioRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    private const SELECT = 'SELECT h.*, u.nombre AS mecanico_nombre,
            (SELECT COUNT(*) FROM Citas ci
              WHERE ci.id_horario = h.id_horario
                AND ci.deleted_at IS NULL
                AND ci.estado_cita IN (\'Programada\', \'Confirmada\')) AS ocupacion
         FROM Agenda_Disponible h
         INNER JOIN Usuarios u ON u.id_usuario = h.id_usuario';

    public function buscarPorId(int $id): ?Horario
    {
        $fila = $this->db->query(
            self::SELECT . ' WHERE h.id_horario = :id LIMIT 1',
            [':id' => $id]
        )->fetch();
        return $fila ? Horario::fromArray($fila) : null;
    }

    public function existeDuplicado(int $idUsuario, string $fecha, string $hora, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM Agenda_Disponible
                WHERE id_usuario = :u AND fecha = :f AND hora_inicio = :h AND estado <> :c';
        $params = [
            ':u' => $idUsuario,
            ':f' => $fecha,
            ':h' => Horario::formatearHora($hora),
            ':c' => Horario::CANCELADO,
        ];
        if ($exceptoId !== null) {
            $sql .= ' AND id_horario <> :id';
            $params[':id'] = $exceptoId;
        }
        $fila = $this->db->query($sql, $params)->fetch();
        return (int) ($fila['total'] ?? 0) > 0;
    }

    /** @return Horario[] */
    public function listar(array $filtros = []): array
    {
        $sql = self::SELECT . ' WHERE 1=1';
        $params = [];
        if (!empty($filtros['fecha'])) {
            $sql .= ' AND h.fecha = :fecha';
            $params[':fecha'] = $filtros['fecha'];
        }
        if (!empty($filtros['desde'])) {
            $sql .= ' AND h.fecha >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['id_usuario'])) {
            $sql .= ' AND h.id_usuario = :mec';
            $params[':mec'] = (int) $filtros['id_usuario'];
        }
        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $sql .= ' AND h.estado = :est';
            $params[':est'] = $filtros['estado'];
        } else {
            $sql .= ' AND h.estado <> :canc';
            $params[':canc'] = Horario::CANCELADO;
        }
        $sql .= ' ORDER BY h.fecha, h.hora_inicio, u.nombre';
        $lista = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $lista[] = Horario::fromArray($fila);
        }
        return $lista;
    }

    public function guardar(Horario $horario): Horario
    {
        $params = [
            ':id_usuario'  => $horario->getIdUsuario(),
            ':fecha'       => $horario->getFecha(),
            ':hora_inicio' => $horario->getHoraInicio(),
            ':capacidad'   => $horario->getCapacidad(),
            ':estado'      => $horario->getEstado(),
        ];
        if ($horario->getIdHorario() === null) {
            $this->db->query(
                'INSERT INTO Agenda_Disponible
                    (id_usuario, fecha, hora_inicio, capacidad, estado, created_at, created_by)
                 VALUES
                    (:id_usuario, :fecha, :hora_inicio, :capacidad, :estado, :created_at, :created_by)',
                $params + [
                    ':created_at' => $horario->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':created_by' => $horario->getCreatedBy(),
                ]
            );
            $horario->assignId((int) $this->db->lastInsertId());
            return $this->buscarPorId((int) $horario->getIdHorario()) ?: $horario;
        }
        $this->db->query(
            'UPDATE Agenda_Disponible SET
                id_usuario = :id_usuario,
                fecha = :fecha,
                hora_inicio = :hora_inicio,
                capacidad = :capacidad,
                estado = :estado,
                updated_at = :updated_at,
                updated_by = :updated_by
             WHERE id_horario = :id',
            $params + [
                ':updated_at' => $horario->getUpdatedAt()
                    ? $horario->getUpdatedAt()->format('Y-m-d H:i:s')
                    : date('Y-m-d H:i:s'),
                ':updated_by' => $horario->getUpdatedBy(),
                ':id'         => $horario->getIdHorario(),
            ]
        );
        return $this->buscarPorId((int) $horario->getIdHorario()) ?: $horario;
    }
}
