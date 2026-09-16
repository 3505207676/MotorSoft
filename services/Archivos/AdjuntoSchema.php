<?php

class AdjuntoSchema
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
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS Adjuntos (
                id_adjunto      INT          PRIMARY KEY AUTO_INCREMENT,
                entidad_tipo    VARCHAR(20)  NOT NULL,
                entidad_id      INT          NOT NULL,
                id_usuario      INT          NULL,
                id_cliente      INT          NULL,
                nombre_original VARCHAR(255),
                nombre_sistema  VARCHAR(255) NOT NULL,
                ruta            TEXT         NOT NULL,
                extension       VARCHAR(10)  NOT NULL,
                peso_bytes      BIGINT       NOT NULL,
                fecha_subida    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at      DATETIME     NULL
            )"
        );
        $this->agregarColumnaSiFalta('Adjuntos', 'id_cliente', 'INT NULL');
        try {
            $this->db->query('ALTER TABLE Adjuntos MODIFY id_usuario INT NULL');
        } catch (Throwable $e) {
            error_log('AdjuntoSchema id_usuario: ' . $e->getMessage());
        }
        $this->agregarColumnaSiFalta('Mensajes', 'id_adjunto', 'INT NULL');
        self::$listo = true;
    }

    private function agregarColumnaSiFalta(string $tabla, string $columna, string $definicion): void
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS n
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND LOWER(TABLE_NAME) = LOWER(:t)
               AND LOWER(COLUMN_NAME) = LOWER(:c)',
            [':t' => $tabla, ':c' => $columna]
        )->fetch();
        if ((int) ($fila['n'] ?? 0) > 0) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE {$tabla} ADD COLUMN {$columna} {$definicion}");
        } catch (Throwable $e) {
            error_log('AdjuntoSchema ALTER ' . $tabla . '.' . $columna . ': ' . $e->getMessage());
        }
    }
}
