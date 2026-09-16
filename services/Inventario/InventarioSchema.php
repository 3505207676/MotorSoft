<?php

/**
 * Extiende Movimientos para guardar cantidad y saldo, sin romper BD ya poblada.
 */
class InventarioSchema
{
    private Database $db;
    private static bool $listo = false;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function asegurar(): void
    {
        if (self::$listo) {
            return;
        }
        $this->agregarColumnaSiFalta('Movimientos', 'cantidad', 'INT NOT NULL DEFAULT 0');
        $this->agregarColumnaSiFalta('Movimientos', 'cantidad_antes', 'INT NULL');
        $this->agregarColumnaSiFalta('Movimientos', 'cantidad_despues', 'INT NULL');
        $this->agregarColumnaSiFalta('Movimientos', 'motivo', 'VARCHAR(200) NULL');
        self::$listo = true;
    }

    private function agregarColumnaSiFalta(string $tabla, string $columna, string $definicion): void
    {
        if ($this->tieneColumna($tabla, $columna)) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE {$tabla} ADD COLUMN {$columna} {$definicion}");
        } catch (Throwable $e) {
            error_log('InventarioSchema ALTER ' . $tabla . '.' . $columna . ': ' . $e->getMessage());
        }
    }

    private function tieneColumna(string $tabla, string $columna): bool
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS n
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND LOWER(TABLE_NAME) = LOWER(:t)
               AND LOWER(COLUMN_NAME) = LOWER(:c)',
            [':t' => $tabla, ':c' => $columna]
        )->fetch();
        return (int) ($fila['n'] ?? 0) > 0;
    }
}
