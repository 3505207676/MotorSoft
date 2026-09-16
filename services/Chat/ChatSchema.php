<?php

require_once __DIR__ . '/../../models/Chat/Conversacion.php';
require_once __DIR__ . '/../../models/Chat/Mensaje.php';

/**
 * Crea/actualiza tablas del chat sin romper una BD ya poblada.
 * Se ejecuta una vez por proceso PHP.
 */
class ChatSchema
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

        $this->crearProveedores();
        $this->crearConversaciones();
        $this->extenderMensajes();
        $this->extenderConversaciones();
        $this->sembrarProveedoresDemo();
        $this->backfillConversacionesClientes();
        $this->unificarHilosUsuario();
        $this->limpiarConversacionesHuerfanas();

        self::$listo = true;
    }

    private function crearProveedores(): void
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS Proveedores (
                id_proveedor INT PRIMARY KEY AUTO_INCREMENT,
                nombre       VARCHAR(120) NOT NULL,
                nit          VARCHAR(30)  NULL,
                email        VARCHAR(120) NULL,
                telefono     VARCHAR(30)  NOT NULL,
                direccion    VARCHAR(200) NULL,
                estado       VARCHAR(20)  NOT NULL DEFAULT \'Activo\',
                created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_by   INT          NOT NULL,
                updated_at   DATETIME     NULL,
                updated_by   INT          NULL,
                deleted_at   DATETIME     NULL
            )'
        );
    }

    private function crearConversaciones(): void
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS Conversaciones (
                id_conversacion   INT PRIMARY KEY AUTO_INCREMENT,
                canal             VARCHAR(20)  NOT NULL DEFAULT \'Interno\',
                tipo_contacto     VARCHAR(20)  NOT NULL,
                id_contacto       INT          NOT NULL,
                telefono          VARCHAR(30)  NULL,
                titulo            VARCHAR(120) NULL,
                id_par            INT          NOT NULL DEFAULT 0,
                ultimo_mensaje_at DATETIME     NULL,
                created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_by        INT          NOT NULL,
                deleted_at        DATETIME     NULL,
                UNIQUE KEY uq_conv_par (canal, tipo_contacto, id_contacto, id_par)
            )'
        );
    }

    private function extenderMensajes(): void
    {
        $this->agregarColumnaSiFalta('Mensajes', 'id_conversacion', 'INT NULL');
        $this->agregarColumnaSiFalta('Mensajes', 'canal', "VARCHAR(20) NOT NULL DEFAULT 'Interno'");
        $this->agregarColumnaSiFalta('Mensajes', 'id_externo', 'VARCHAR(80) NULL');
        $this->agregarColumnaSiFalta('Mensajes', 'id_adjunto', 'INT NULL');
    }

    private function extenderConversaciones(): void
    {
        $this->agregarColumnaSiFalta('Conversaciones', 'id_par', 'INT NOT NULL DEFAULT 0');
        $this->eliminarIndiceSiExiste('Conversaciones', 'uq_conv_canal_contacto');
        $this->agregarIndiceUnicoSiFalta(
            'Conversaciones',
            'uq_conv_par',
            'canal, tipo_contacto, id_contacto, id_par'
        );
    }

    private function unificarHilosUsuario(): void
    {
        if (!$this->tieneColumna('Conversaciones', 'id_par')) {
            return;
        }
        try {
            $msgs = $this->db->query(
                "SELECT m.id_mensaje, m.id_conversacion, m.emisor_id, m.receptor_id, m.canal, m.fecha_hora
                 FROM Mensajes m
                 INNER JOIN Usuarios u1 ON u1.id_usuario = m.emisor_id AND u1.deleted_at IS NULL
                 INNER JOIN Usuarios u2 ON u2.id_usuario = m.receptor_id AND u2.deleted_at IS NULL
                 WHERE m.deleted_at IS NULL
                   AND m.tipo_emisor = :tipo
                   AND m.emisor_id > 0
                   AND m.receptor_id > 0
                   AND m.emisor_id <> m.receptor_id",
                [':tipo' => Mensaje::TIPO_USUARIO]
            )->fetchAll();
        } catch (Throwable $e) {
            error_log('ChatSchema unificar listar: ' . $e->getMessage());
            return;
        }

        $grupos = [];
        foreach ($msgs as $m) {
            $par = Conversacion::parUsuarios((int) $m['emisor_id'], (int) $m['receptor_id']);
            $canal = trim((string) ($m['canal'] ?? '')) ?: Conversacion::CANAL_INTERNO;
            $clave = $canal . ':' . $par[0] . ':' . $par[1];
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'canal'   => $canal,
                    'lo'      => $par[0],
                    'hi'      => $par[1],
                    'ids'     => [],
                    'convs'   => [],
                    'ultimo'  => $m['fecha_hora'],
                    'primero' => $m['fecha_hora'],
                ];
            }
            $grupos[$clave]['ids'][] = (int) $m['id_mensaje'];
            $idConv = (int) ($m['id_conversacion'] ?? 0);
            if ($idConv > 0) {
                $grupos[$clave]['convs'][] = $idConv;
            }
            if ((string) $m['fecha_hora'] > (string) $grupos[$clave]['ultimo']) {
                $grupos[$clave]['ultimo'] = $m['fecha_hora'];
            }
            if ((string) $m['fecha_hora'] < (string) $grupos[$clave]['primero']) {
                $grupos[$clave]['primero'] = $m['fecha_hora'];
            }
        }

        foreach ($grupos as $grupo) {
            $this->fusionarGrupoUsuario($grupo);
        }
    }

    private function fusionarGrupoUsuario(array $grupo): void
    {
        $keep = $this->resolverHiloPar($grupo);
        if ($keep <= 0) {
            return;
        }
        $ids = array_values(array_unique(array_filter($grupo['ids'])));
        if ($ids === []) {
            return;
        }
        $ph = [];
        $params = [':idc' => $keep, ':can' => $grupo['canal']];
        foreach ($ids as $i => $id) {
            $k = ':m' . $i;
            $ph[] = $k;
            $params[$k] = $id;
        }
        try {
            $this->db->query(
                'UPDATE Mensajes
                 SET id_conversacion = :idc, canal = :can
                 WHERE id_mensaje IN (' . implode(',', $ph) . ')',
                $params
            );
            $this->db->query(
                'UPDATE Conversaciones
                 SET ultimo_mensaje_at = :u, tipo_contacto = :t, id_contacto = :lo, id_par = :hi
                 WHERE id_conversacion = :id',
                [
                    ':u'  => $grupo['ultimo'],
                    ':t'  => Conversacion::TIPO_USUARIO,
                    ':lo' => $grupo['lo'],
                    ':hi' => $grupo['hi'],
                    ':id' => $keep,
                ]
            );
            $sobrantes = array_values(array_unique(array_filter(
                array_map('intval', $grupo['convs']),
                static fn (int $id): bool => $id !== $keep
            )));
            foreach ($sobrantes as $id) {
                $this->db->query(
                    'UPDATE Conversaciones
                     SET deleted_at = :d
                     WHERE id_conversacion = :id
                       AND deleted_at IS NULL
                       AND tipo_contacto = :t',
                    [
                        ':d'  => date('Y-m-d H:i:s'),
                        ':id' => $id,
                        ':t'  => Conversacion::TIPO_USUARIO,
                    ]
                );
            }
        } catch (Throwable $e) {
            error_log('ChatSchema fusionar grupo: ' . $e->getMessage());
        }
    }

    private function resolverHiloPar(array $grupo): int
    {
        $exacto = $this->db->query(
            "SELECT id_conversacion
             FROM Conversaciones
             WHERE deleted_at IS NULL
               AND canal = :c
               AND tipo_contacto = :t
               AND id_contacto = :lo
               AND id_par = :hi
             LIMIT 1",
            [
                ':c'  => $grupo['canal'],
                ':t'  => Conversacion::TIPO_USUARIO,
                ':lo' => $grupo['lo'],
                ':hi' => $grupo['hi'],
            ]
        )->fetch();
        if ($exacto) {
            return (int) $exacto['id_conversacion'];
        }

        $candidatos = array_values(array_unique(array_filter($grupo['convs'])));
        $keep = $candidatos !== [] ? min($candidatos) : 0;
        if ($keep <= 0) {
            $this->db->query(
                'INSERT INTO Conversaciones
                    (canal, tipo_contacto, id_contacto, id_par, ultimo_mensaje_at, created_at, created_by)
                 VALUES (:c, :t, :lo, :hi, :u, :ca, :cb)',
                [
                    ':c'  => $grupo['canal'],
                    ':t'  => Conversacion::TIPO_USUARIO,
                    ':lo' => $grupo['lo'],
                    ':hi' => $grupo['hi'],
                    ':u'  => $grupo['ultimo'],
                    ':ca' => $grupo['primero'],
                    ':cb' => $grupo['lo'],
                ]
            );
            return (int) $this->db->lastInsertId();
        }

        $this->db->query(
            'UPDATE Conversaciones
             SET tipo_contacto = :t, id_contacto = :lo, id_par = :hi, deleted_at = NULL
             WHERE id_conversacion = :id',
            [
                ':t'  => Conversacion::TIPO_USUARIO,
                ':lo' => $grupo['lo'],
                ':hi' => $grupo['hi'],
                ':id' => $keep,
            ]
        );
        return $keep;
    }

    private function limpiarConversacionesHuerfanas(): void
    {
        try {
            $this->db->query(
                "UPDATE Conversaciones c
                 LEFT JOIN Clientes cli
                        ON cli.id_cliente = c.id_contacto
                       AND cli.deleted_at IS NULL
                 SET c.deleted_at = :d
                 WHERE c.deleted_at IS NULL
                   AND c.tipo_contacto = :t
                   AND cli.id_cliente IS NULL",
                [':d' => date('Y-m-d H:i:s'), ':t' => Conversacion::TIPO_CLIENTE]
            );
        } catch (Throwable $e) {
            error_log('ChatSchema limpiar huerfanas: ' . $e->getMessage());
        }
    }

    private function eliminarIndiceSiExiste(string $tabla, string $indice): void
    {
        if (!$this->tieneIndice($tabla, $indice)) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE {$tabla} DROP INDEX {$indice}");
        } catch (Throwable $e) {
            error_log('ChatSchema DROP INDEX ' . $tabla . '.' . $indice . ': ' . $e->getMessage());
        }
    }

    private function agregarIndiceUnicoSiFalta(string $tabla, string $indice, string $columnas): void
    {
        if ($this->tieneIndice($tabla, $indice)) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE {$tabla} ADD UNIQUE KEY {$indice} ({$columnas})");
        } catch (Throwable $e) {
            error_log('ChatSchema ADD UNIQUE ' . $tabla . '.' . $indice . ': ' . $e->getMessage());
        }
    }

    private function tieneIndice(string $tabla, string $indice): bool
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS n
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND LOWER(TABLE_NAME) = LOWER(:t)
               AND LOWER(INDEX_NAME) = LOWER(:i)',
            [':t' => $tabla, ':i' => $indice]
        )->fetch();
        return (int) ($fila['n'] ?? 0) > 0;
    }

    private function agregarColumnaSiFalta(string $tabla, string $columna, string $definicion): void
    {
        if ($this->tieneColumna($tabla, $columna)) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE {$tabla} ADD COLUMN {$columna} {$definicion}");
        } catch (Throwable $e) {
            error_log('ChatSchema ALTER ' . $tabla . '.' . $columna . ': ' . $e->getMessage());
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

    private function sembrarProveedoresDemo(): void
    {
        $demos = [
            ['nombre' => 'Repuestos Andinos', 'nit' => '900111222', 'telefono' => '3001112233', 'email' => 'ventas@repuestosandinos.test'],
            ['nombre' => 'Lubricantes del Valle', 'nit' => '900333444', 'telefono' => '3004445566', 'email' => 'pedidos@lubrivalles.test'],
        ];
        $ahora = date('Y-m-d H:i:s');
        foreach ($demos as $demo) {
            try {
                $existe = $this->db->query(
                    'SELECT id_proveedor FROM Proveedores WHERE nombre = :n AND deleted_at IS NULL LIMIT 1',
                    [':n' => $demo['nombre']]
                )->fetch();
                if ($existe) {
                    continue;
                }
                $this->db->query(
                    'INSERT INTO Proveedores (nombre, nit, telefono, email, estado, created_at, created_by)
                     VALUES (:n, :nit, :t, :e, :est, :c, :u)',
                    [
                        ':n'   => $demo['nombre'],
                        ':nit' => $demo['nit'],
                        ':t'   => $demo['telefono'],
                        ':e'   => $demo['email'],
                        ':est' => 'Activo',
                        ':c'   => $ahora,
                        ':u'   => 1,
                    ]
                );
            } catch (Throwable $e) {
                error_log('ChatSchema proveedor demo: ' . $e->getMessage());
            }
        }
        $this->deduplicarProveedoresDemo();
    }

    private function deduplicarProveedoresDemo(): void
    {
        try {
            $this->db->query(
                'UPDATE Proveedores p
                 INNER JOIN Proveedores p2
                    ON p.nombre = p2.nombre
                   AND p.telefono = p2.telefono
                   AND p.id_proveedor > p2.id_proveedor
                   AND p2.deleted_at IS NULL
                 SET p.deleted_at = :d
                 WHERE p.deleted_at IS NULL',
                [':d' => date('Y-m-d H:i:s')]
            );
        } catch (Throwable $e) {
            error_log('ChatSchema dedupe proveedores: ' . $e->getMessage());
        }
    }

    private function backfillConversacionesClientes(): void
    {
        if (!$this->tieneColumna('Mensajes', 'id_conversacion')) {
            return;
        }
        $sql = "INSERT IGNORE INTO Conversaciones
                    (canal, tipo_contacto, id_contacto, ultimo_mensaje_at, created_at, created_by)
                SELECT 'Interno', 'Cliente', t.id_cli, MAX(t.fecha_hora), MIN(t.fecha_hora), 1
                FROM (
                    SELECT CASE WHEN tipo_emisor = 'Cliente' THEN emisor_id ELSE receptor_id END AS id_cli,
                           fecha_hora
                    FROM Mensajes
                    WHERE deleted_at IS NULL
                      AND tipo_emisor IN ('Cliente', 'Usuario')
                ) t
                WHERE t.id_cli > 0
                GROUP BY t.id_cli";
        try {
            $this->db->query($sql);
        } catch (Throwable $e) {
            error_log('ChatSchema backfill conversaciones: ' . $e->getMessage());
        }

        $filas = $this->db->query(
            "SELECT id_conversacion, id_contacto
             FROM Conversaciones
             WHERE deleted_at IS NULL AND canal = 'Interno' AND tipo_contacto = 'Cliente'"
        )->fetchAll();
        foreach ($filas as $fila) {
            $idConv = (int) $fila['id_conversacion'];
            $idCli  = (int) $fila['id_contacto'];
            try {
                $this->db->query(
                    'UPDATE Mensajes
                     SET id_conversacion = :idc, canal = :can
                     WHERE deleted_at IS NULL
                       AND (id_conversacion IS NULL OR id_conversacion = 0)
                       AND (
                           (tipo_emisor = :tcli AND emisor_id = :c1)
                           OR (tipo_emisor = :tusr AND receptor_id = :c2)
                       )',
                    [
                        ':idc'  => $idConv,
                        ':can'  => Conversacion::CANAL_INTERNO,
                        ':tcli' => Mensaje::TIPO_CLIENTE,
                        ':c1'   => $idCli,
                        ':tusr' => Mensaje::TIPO_USUARIO,
                        ':c2'   => $idCli,
                    ]
                );
            } catch (Throwable $e) {
                error_log('ChatSchema backfill mensajes: ' . $e->getMessage());
            }
        }
    }
}
