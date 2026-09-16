<?php

require_once __DIR__ . '/../../models/Ordenes/Especialidad.php';
require_once __DIR__ . '/../Seguridad/UsuarioRepository.php';

class EspecialidadRepository
{
    private Database $db;
    private UsuarioRepository $usuarios;

    public function __construct(Database $db, UsuarioRepository $usuarios)
    {
        $this->db       = $db;
        $this->usuarios = $usuarios;
    }

    public function buscarPorId(int $id, bool $conUsuarios = false): ?Especialidad
    {
        $stmt = $this->db->query(
            'SELECT * FROM Especialidades WHERE id_especialidad = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }
        $esp = Especialidad::fromArray($fila);
        if ($conUsuarios) {
            $esp->setUsuarios($this->usuariosDeEspecialidad((int) $esp->getIdEspecialidad()));
        }
        return $esp;
    }

    /** @return Especialidad[] */
    public function listar(bool $conUsuarios = false): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Especialidades WHERE deleted_at IS NULL ORDER BY nombre_especialidad'
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $esp = Especialidad::fromArray($fila);
            if ($conUsuarios) {
                $esp->setUsuarios($this->usuariosDeEspecialidad((int) $esp->getIdEspecialidad()));
            }
            $lista[] = $esp;
        }
        return $lista;
    }

    /** @return Especialidad[] */
    public function listarPorUsuario(int $idUsuario): array
    {
        $stmt = $this->db->query(
            'SELECT e.*
             FROM Especialidades e
             INNER JOIN Usuario_Especialidad ue ON ue.id_especialidad = e.id_especialidad
             WHERE ue.id_usuario = :u
               AND e.deleted_at IS NULL
               AND ue.deleted_at IS NULL
             ORDER BY e.nombre_especialidad',
            [':u' => $idUsuario]
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = Especialidad::fromArray($fila);
        }
        return $lista;
    }

    /** @return Usuario[] */
    public function usuariosDeEspecialidad(int $idEspecialidad): array
    {
        $stmt = $this->db->query(
            'SELECT id_usuario FROM Usuario_Especialidad
             WHERE id_especialidad = :id AND deleted_at IS NULL',
            [':id' => $idEspecialidad]
        );
        $usuarios = [];
        foreach ($stmt->fetchAll() as $fila) {
            $usuario = $this->usuarios->buscarPorId((int) $fila['id_usuario'], true);
            if ($usuario) {
                $usuarios[] = $usuario;
            }
        }
        return $usuarios;
    }

    public function buscarPorNombre(string $nombre, ?int $exceptoId = null): ?Especialidad
    {
        $sql = 'SELECT * FROM Especialidades
                WHERE LOWER(nombre_especialidad) = LOWER(:n) AND deleted_at IS NULL';
        $params = [':n' => trim($nombre)];
        if ($exceptoId !== null) {
            $sql .= ' AND id_especialidad <> :id';
            $params[':id'] = $exceptoId;
        }
        $sql .= ' LIMIT 1';
        $fila = $this->db->query($sql, $params)->fetch();
        return $fila ? Especialidad::fromArray($fila) : null;
    }

    public function guardar(Especialidad $esp): Especialidad
    {
        $params = [
            ':nombre'      => $esp->getNombreEspecialidad(),
            ':descripcion' => $esp->getDescripcion(),
            ':estado'      => $esp->getEstado(),
        ];

        if ($esp->getIdEspecialidad() === null) {
            $this->db->query(
                'INSERT INTO Especialidades
                    (nombre_especialidad, descripcion, estado, created_at, created_by)
                 VALUES
                    (:nombre, :descripcion, :estado, :created_at, :created_by)',
                $params + [
                    ':created_at' => $esp->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':created_by' => $esp->getCreatedBy(),
                ]
            );
            $esp->assignId((int) $this->db->lastInsertId());
            return $this->buscarPorId((int) $esp->getIdEspecialidad()) ?: $esp;
        }

        $this->db->query(
            'UPDATE Especialidades SET
                nombre_especialidad = :nombre,
                descripcion = :descripcion,
                estado = :estado,
                updated_at = :updated_at,
                updated_by = :updated_by,
                deleted_at = :deleted_at
             WHERE id_especialidad = :id',
            $params + [
                ':updated_at' => $esp->getUpdatedAt() ? $esp->getUpdatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ':updated_by' => $esp->getUpdatedBy(),
                ':deleted_at' => $esp->getDeletedAt() ? $esp->getDeletedAt()->format('Y-m-d H:i:s') : null,
                ':id'         => $esp->getIdEspecialidad(),
            ]
        );
        return $this->buscarPorId((int) $esp->getIdEspecialidad()) ?: $esp;
    }

    /**
     * @param int[] $idsEspecialidad
     */
    public function reemplazarAsignacionesUsuario(int $idUsuario, array $idsEspecialidad, int $createdBy): void
    {
        $deseados = [];
        foreach ($idsEspecialidad as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $deseados[$id] = true;
            }
        }
        $actuales = [];
        $filas = $this->db->query(
            'SELECT id_user_especialidad, id_especialidad, deleted_at
             FROM Usuario_Especialidad
             WHERE id_usuario = :u',
            [':u' => $idUsuario]
        )->fetchAll();
        foreach ($filas as $fila) {
            $idEsp = (int) $fila['id_especialidad'];
            $actuales[$idEsp] = $fila;
        }

        foreach (array_keys($deseados) as $idEsp) {
            if (!isset($actuales[$idEsp])) {
                $this->db->query(
                    'INSERT INTO Usuario_Especialidad (id_usuario, id_especialidad, created_by)
                     VALUES (:u, :e, :c)',
                    [':u' => $idUsuario, ':e' => $idEsp, ':c' => $createdBy]
                );
                continue;
            }
            if (!empty($actuales[$idEsp]['deleted_at'])) {
                $this->db->query(
                    'UPDATE Usuario_Especialidad SET deleted_at = NULL WHERE id_user_especialidad = :id',
                    [':id' => (int) $actuales[$idEsp]['id_user_especialidad']]
                );
            }
        }

        foreach ($actuales as $idEsp => $fila) {
            if (isset($deseados[$idEsp]) || !empty($fila['deleted_at'])) {
                continue;
            }
            $this->db->query(
                'UPDATE Usuario_Especialidad SET deleted_at = NOW() WHERE id_user_especialidad = :id',
                [':id' => (int) $fila['id_user_especialidad']]
            );
        }
    }

    /**
     * @param int[] $idsUsuario
     */
    public function reemplazarUsuariosEspecialidad(int $idEspecialidad, array $idsUsuario, int $createdBy): void
    {
        $deseados = [];
        foreach ($idsUsuario as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $deseados[$id] = true;
            }
        }
        $actuales = [];
        $filas = $this->db->query(
            'SELECT id_user_especialidad, id_usuario, deleted_at
             FROM Usuario_Especialidad
             WHERE id_especialidad = :e',
            [':e' => $idEspecialidad]
        )->fetchAll();
        foreach ($filas as $fila) {
            $actuales[(int) $fila['id_usuario']] = $fila;
        }

        foreach (array_keys($deseados) as $idUsuario) {
            if (!isset($actuales[$idUsuario])) {
                $this->db->query(
                    'INSERT INTO Usuario_Especialidad (id_usuario, id_especialidad, created_by)
                     VALUES (:u, :e, :c)',
                    [':u' => $idUsuario, ':e' => $idEspecialidad, ':c' => $createdBy]
                );
                continue;
            }
            if (!empty($actuales[$idUsuario]['deleted_at'])) {
                $this->db->query(
                    'UPDATE Usuario_Especialidad SET deleted_at = NULL WHERE id_user_especialidad = :id',
                    [':id' => (int) $actuales[$idUsuario]['id_user_especialidad']]
                );
            }
        }

        foreach ($actuales as $idUsuario => $fila) {
            if (isset($deseados[$idUsuario]) || !empty($fila['deleted_at'])) {
                continue;
            }
            $this->db->query(
                'UPDATE Usuario_Especialidad SET deleted_at = NOW() WHERE id_user_especialidad = :id',
                [':id' => (int) $fila['id_user_especialidad']]
            );
        }
    }

    public function desasignarTodos(int $idEspecialidad): void
    {
        $this->db->query(
            'UPDATE Usuario_Especialidad SET deleted_at = NOW()
             WHERE id_especialidad = :e AND deleted_at IS NULL',
            [':e' => $idEspecialidad]
        );
    }
}
