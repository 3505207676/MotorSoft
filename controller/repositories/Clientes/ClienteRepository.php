<?php

require_once __DIR__ . '/../../models/Clientes/Cliente.php';
require_once __DIR__ . '/VehiculoRepository.php';

class ClienteRepository
{
    private Database $db;
    private VehiculoRepository $vehiculos;

    public function __construct(Database $db, ?VehiculoRepository $vehiculos = null)
    {
        $this->db        = $db;
        $this->vehiculos = $vehiculos ?: new VehiculoRepository($db);
    }

    public function buscarPorId(int $id, bool $conVehiculos = true): ?Cliente
    {
        $stmt = $this->db->query(
            'SELECT * FROM Clientes WHERE id_cliente = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        return $this->hydrate($stmt->fetch() ?: null, $conVehiculos);
    }

    public function buscarPorDocumento(string $documento, ?int $exceptoId = null): ?Cliente
    {
        $sql = 'SELECT * FROM Clientes WHERE documento = :documento AND deleted_at IS NULL';
        $params = [':documento' => trim($documento)];
        if ($exceptoId !== null) {
            $sql .= ' AND id_cliente <> :id';
            $params[':id'] = $exceptoId;
        }
        $sql .= ' LIMIT 1';
        return $this->hydrate($this->db->query($sql, $params)->fetch() ?: null, true);
    }

    public function buscarPorTelefono(string $digitos): ?Cliente
    {
        $solo = preg_replace('/\D+/', '', $digitos) ?? '';
        $sufijo = $solo === '' ? '' : substr($solo, -10);
        if ($sufijo === '') {
            return null;
        }
        $fila = $this->db->query(
            'SELECT * FROM Clientes
             WHERE deleted_at IS NULL
               AND REPLACE(REPLACE(REPLACE(REPLACE(telefono, \' \', \'\'), \'-\', \'\'), \'+\', \'\'), \'.\', \'\') LIKE :s
             LIMIT 1',
            [':s' => '%' . $sufijo]
        )->fetch();
        return $this->hydrate($fila ?: null, false);
    }

    /** @return Cliente[] */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT * FROM Clientes WHERE deleted_at IS NULL';
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= ' AND estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= ' AND (nombre LIKE :q1 OR documento LIKE :q2 OR email LIKE :q3 OR telefono LIKE :q4)';
            $like = '%' . $filtros['busqueda'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }

        $sql .= ' ORDER BY nombre ASC';
        $stmt = $this->db->query($sql, $params);
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = $this->hydrate($fila, true);
        }
        return $lista;
    }

    public function guardar(Cliente $cliente): Cliente
    {
        if ($cliente->getIdCliente() === null) {
            $sql = 'INSERT INTO Clientes (
                        nombre, documento, email, telefono, preferencia_contacto,
                        estado, created_at, created_by
                    ) VALUES (
                        :nombre, :documento, :email, :telefono, :preferencia_contacto,
                        :estado, :created_at, :created_by
                    )';
            $this->db->query($sql, [
                ':nombre'               => $cliente->getNombre(),
                ':documento'            => $cliente->getDocumento(),
                ':email'                => $cliente->getEmail(),
                ':telefono'             => $cliente->getTelefono(),
                ':preferencia_contacto' => $cliente->getPreferenciaContacto(),
                ':estado'               => $cliente->getEstado(),
                ':created_at'           => $cliente->getCreatedAt()->format('Y-m-d H:i:s'),
                ':created_by'           => $cliente->getCreatedBy(),
            ]);
            $cliente->assignId((int) $this->db->lastInsertId());
            return $cliente;
        }

        $sql = 'UPDATE Clientes SET
                    nombre = :nombre,
                    documento = :documento,
                    email = :email,
                    telefono = :telefono,
                    preferencia_contacto = :preferencia_contacto,
                    estado = :estado,
                    updated_at = :updated_at,
                    updated_by = :updated_by,
                    deleted_at = :deleted_at
                WHERE id_cliente = :id';
        $this->db->query($sql, [
            ':nombre'               => $cliente->getNombre(),
            ':documento'            => $cliente->getDocumento(),
            ':email'                => $cliente->getEmail(),
            ':telefono'             => $cliente->getTelefono(),
            ':preferencia_contacto' => $cliente->getPreferenciaContacto(),
            ':estado'               => $cliente->getEstado(),
            ':updated_at'           => $cliente->getUpdatedAt()
                ? $cliente->getUpdatedAt()->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s'),
            ':updated_by'           => $cliente->getUpdatedBy(),
            ':deleted_at'           => $cliente->getDeletedAt()
                ? $cliente->getDeletedAt()->format('Y-m-d H:i:s')
                : null,
            ':id'                   => $cliente->getIdCliente(),
        ]);
        return $cliente;
    }

    private function hydrate(?array $fila, bool $conVehiculos): ?Cliente
    {
        if (!$fila) {
            return null;
        }
        $vehiculos = [];
        if ($conVehiculos && !empty($fila['id_cliente'])) {
            $vehiculos = $this->vehiculos->listarPorCliente((int) $fila['id_cliente']);
        }
        return Cliente::fromArray($fila, $vehiculos);
    }
}
