<?php

require_once __DIR__ . '/../../models/Clientes/Vehiculo.php';

class VehiculoRepository
{
    private const SELECT = 'SELECT v.id_vehiculo, v.id_cliente, v.placa, v.marca, v.modelo,
                    v.`año` AS anio, v.tipo, v.estado, v.created_at, v.created_by,
                    v.updated_at, v.updated_by, v.deleted_at,
                    c.nombre AS cliente_nombre
             FROM Vehiculo v
             INNER JOIN Clientes c ON c.id_cliente = v.id_cliente';

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->asegurarTipo();
    }

    private function asegurarTipo(): void
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS n
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND LOWER(TABLE_NAME) = \'vehiculo\'
               AND LOWER(COLUMN_NAME) = \'tipo\''
        )->fetch();
        if ((int) ($fila['n'] ?? 0) > 0) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE Vehiculo ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT 'Automóvil'");
            $this->db->query("UPDATE Vehiculo SET tipo = 'Moto' WHERE placa REGEXP '^[A-Z]{3}[0-9]{2}[A-Z]$'");
        } catch (Throwable $e) {
            error_log('VehiculoRepository ALTER tipo: ' . $e->getMessage());
        }
    }

    public function buscarPorId(int $id): ?Vehiculo
    {
        $stmt = $this->db->query(
            self::SELECT . '
             WHERE v.id_vehiculo = :id AND v.deleted_at IS NULL
             LIMIT 1',
            [':id' => $id]
        );
        $fila = $stmt->fetch();
        return $fila ? Vehiculo::fromArray($fila) : null;
    }

    public function buscarPorPlaca(string $placa, ?int $exceptoId = null): ?Vehiculo
    {
        $sql = self::SELECT . '
                WHERE v.placa = :placa AND v.deleted_at IS NULL';
        $params = [':placa' => strtoupper(trim($placa))];
        if ($exceptoId !== null) {
            $sql .= ' AND v.id_vehiculo <> :id';
            $params[':id'] = $exceptoId;
        }
        $sql .= ' LIMIT 1';
        $fila = $this->db->query($sql, $params)->fetch();
        return $fila ? Vehiculo::fromArray($fila) : null;
    }

    /** @return Vehiculo[] */
    public function listar(array $filtros = []): array
    {
        $sql = self::SELECT . '
                WHERE v.deleted_at IS NULL';
        $params = [];

        if (!empty($filtros['id_cliente'])) {
            $sql .= ' AND v.id_cliente = :id_cliente';
            $params[':id_cliente'] = (int) $filtros['id_cliente'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND v.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= ' AND (v.placa LIKE :q1 OR v.marca LIKE :q2 OR v.modelo LIKE :q3 OR c.nombre LIKE :q4)';
            $like = '%' . $filtros['busqueda'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }

        $sql .= ' ORDER BY v.placa ASC';
        $stmt = $this->db->query($sql, $params);
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = Vehiculo::fromArray($fila);
        }
        return $lista;
    }

    /** @return Vehiculo[] */
    public function listarPorCliente(int $idCliente): array
    {
        return $this->listar(['id_cliente' => $idCliente]);
    }

    public function tieneOrdenesActivas(int $idVehiculo): bool
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM Orden_Servicio
             WHERE id_vehiculo = :id
               AND deleted_at IS NULL
               AND estado NOT IN ('Completada', 'Cancelada')",
            [':id' => $idVehiculo]
        );
        $fila = $stmt->fetch();
        return (int) ($fila['total'] ?? 0) > 0;
    }

    public function guardar(Vehiculo $vehiculo): Vehiculo
    {
        if ($vehiculo->getIdVehiculo() === null) {
            $sql = 'INSERT INTO Vehiculo (
                        id_cliente, placa, marca, modelo, `año`, tipo, estado, created_at, created_by
                    ) VALUES (
                        :id_cliente, :placa, :marca, :modelo, :anio, :tipo, :estado, :created_at, :created_by
                    )';
            $this->db->query($sql, [
                ':id_cliente' => $vehiculo->getIdCliente(),
                ':placa'      => $vehiculo->getPlaca(),
                ':marca'      => $vehiculo->getMarca(),
                ':modelo'     => $vehiculo->getModelo(),
                ':anio'       => $vehiculo->getAnio(),
                ':tipo'       => $vehiculo->getTipo(),
                ':estado'     => $vehiculo->getEstado(),
                ':created_at' => $vehiculo->getCreatedAt()->format('Y-m-d H:i:s'),
                ':created_by' => $vehiculo->getCreatedBy(),
            ]);
            $vehiculo->assignId((int) $this->db->lastInsertId());
            return $vehiculo;
        }

        $sql = 'UPDATE Vehiculo SET
                    id_cliente = :id_cliente,
                    placa = :placa,
                    marca = :marca,
                    modelo = :modelo,
                    `año` = :anio,
                    tipo = :tipo,
                    estado = :estado,
                    updated_at = :updated_at,
                    updated_by = :updated_by,
                    deleted_at = :deleted_at
                WHERE id_vehiculo = :id';
        $this->db->query($sql, [
            ':id_cliente' => $vehiculo->getIdCliente(),
            ':placa'      => $vehiculo->getPlaca(),
            ':marca'      => $vehiculo->getMarca(),
            ':modelo'     => $vehiculo->getModelo(),
            ':anio'       => $vehiculo->getAnio(),
            ':tipo'       => $vehiculo->getTipo(),
            ':estado'     => $vehiculo->getEstado(),
            ':updated_at' => $vehiculo->getUpdatedAt()
                ? $vehiculo->getUpdatedAt()->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s'),
            ':updated_by' => $vehiculo->getUpdatedBy(),
            ':deleted_at' => $vehiculo->getDeletedAt()
                ? $vehiculo->getDeletedAt()->format('Y-m-d H:i:s')
                : null,
            ':id'         => $vehiculo->getIdVehiculo(),
        ]);
        return $vehiculo;
    }
}
