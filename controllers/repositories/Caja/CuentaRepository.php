<?php

require_once __DIR__ . '/../../models/Caja/Cuenta.php';

class CuentaRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function buscarPorId(int $id): ?Cuenta
    {
        $stmt = $this->db->query(
            'SELECT * FROM Cuentas WHERE id_cuenta = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        $fila = $stmt->fetch();
        return $fila ? Cuenta::fromArray($fila) : null;
    }

    public function buscarPorNombre(string $nombre): ?Cuenta
    {
        $stmt = $this->db->query(
            'SELECT * FROM Cuentas WHERE nombre = :n AND deleted_at IS NULL LIMIT 1',
            [':n' => trim($nombre)]
        );
        $fila = $stmt->fetch();
        return $fila ? Cuenta::fromArray($fila) : null;
    }

    /** @return Cuenta[] */
    public function listar(): array
    {
        $stmt = $this->db->query('SELECT * FROM Cuentas WHERE deleted_at IS NULL ORDER BY nombre');
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = Cuenta::fromArray($fila);
        }
        return $lista;
    }

    public function guardar(Cuenta $cuenta): Cuenta
    {
        if ($cuenta->getIdCuenta() === null) {
            $this->db->query(
                'INSERT INTO Cuentas (nombre, tipo, saldo_actual, estado, created_at, created_by)
                 VALUES (:n, :t, :s, :e, :c, :u)',
                [
                    ':n' => $cuenta->getNombre(),
                    ':t' => $cuenta->getTipo(),
                    ':s' => $cuenta->getSaldoActual(),
                    ':e' => $cuenta->getEstado(),
                    ':c' => $cuenta->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':u' => $cuenta->getCreatedBy(),
                ]
            );
            $cuenta->assignId((int) $this->db->lastInsertId());
            return $cuenta;
        }
        $this->db->query(
            'UPDATE Cuentas SET nombre = :n, tipo = :t, saldo_actual = :s, estado = :e,
                updated_at = :ua, updated_by = :ub, deleted_at = :da
             WHERE id_cuenta = :id',
            [
                ':n'  => $cuenta->getNombre(),
                ':t'  => $cuenta->getTipo(),
                ':s'  => $cuenta->getSaldoActual(),
                ':e'  => $cuenta->getEstado(),
                ':ua' => $cuenta->getUpdatedAt() ? $cuenta->getUpdatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ':ub' => $cuenta->getUpdatedBy(),
                ':da' => $cuenta->getDeletedAt() ? $cuenta->getDeletedAt()->format('Y-m-d H:i:s') : null,
                ':id' => $cuenta->getIdCuenta(),
            ]
        );
        return $cuenta;
    }
}
