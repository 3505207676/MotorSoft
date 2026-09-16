<?php

require_once __DIR__ . '/../../models/Facturacion/Factura.php';
require_once __DIR__ . '/../../models/Agenda/Cita.php';

/**
 * Lecturas agregadas del panel administrador. Sin reglas de negocio.
 */
class DashboardRepository
{
    private const ESTADOS_CERRADOS = ['Completada', 'Cancelada', 'Finalizada'];

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /** @return array<string,int> */
    public function conteoOrdenes(): array
    {
        $sql = 'SELECT o.estado, COUNT(*) AS total
                FROM Orden_Servicio o
                WHERE o.deleted_at IS NULL
                GROUP BY o.estado';
        $porEstado = [];
        foreach ($this->db->query($sql)->fetchAll() as $fila) {
            $porEstado[(string) $fila['estado']] = (int) $fila['total'];
        }
        $activas = 0;
        foreach ($porEstado as $estado => $n) {
            if (!$this->esCerrada($estado)) {
                $activas += $n;
            }
        }
        return [
            'total'            => array_sum($porEstado),
            'activas'          => $activas,
            'pendiente'        => (int) ($porEstado['Pendiente'] ?? 0) + (int) ($porEstado['Diagnóstico'] ?? 0),
            'en_proceso'       => (int) ($porEstado['En Proceso'] ?? 0) + (int) ($porEstado['En Progreso'] ?? 0),
            'pendiente_pago'   => (int) ($porEstado['Pendiente Pago'] ?? 0),
            'completadas'      => (int) ($porEstado['Completada'] ?? 0) + (int) ($porEstado['Finalizada'] ?? 0),
            'por_estado'       => $porEstado,
        ];
    }

    public function vehiculosEnTaller(): int
    {
        $in = $this->inCerrados();
        $fila = $this->db->query(
            'SELECT COUNT(DISTINCT o.id_vehiculo) AS total
             FROM Orden_Servicio o
             WHERE o.deleted_at IS NULL
               AND o.estado NOT IN (' . $in['sql'] . ')',
            $in['params']
        )->fetch();
        return (int) ($fila['total'] ?? 0);
    }

    /** @return array{hoy:float,ayer:float} */
    public function ingresosHoyAyer(): array
    {
        $fila = $this->db->query(
            "SELECT
                COALESCE(SUM(CASE WHEN DATE(f.fecha_emision) = CURDATE() THEN f.total ELSE 0 END), 0) AS hoy,
                COALESCE(SUM(CASE WHEN DATE(f.fecha_emision) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN f.total ELSE 0 END), 0) AS ayer
             FROM Facturas f
             WHERE f.deleted_at IS NULL
               AND f.estado = :pagada
               AND DATE(f.fecha_emision) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            [':pagada' => Factura::PAGADA]
        )->fetch();
        return [
            'hoy'  => (float) ($fila['hoy'] ?? 0),
            'ayer' => (float) ($fila['ayer'] ?? 0),
        ];
    }

    public function stockBajo(): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM Productos p
             INNER JOIN Stock s ON s.id_producto = p.id_producto
             WHERE p.deleted_at IS NULL
               AND p.estado = :activo
               AND s.cantidad <= s.stock_minimo',
            [':activo' => 'Activo']
        )->fetch();
        return (int) ($fila['total'] ?? 0);
    }

    public function facturasPendientes(): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS total FROM Facturas
             WHERE deleted_at IS NULL AND estado = :pend',
            [':pend' => Factura::PENDIENTE]
        )->fetch();
        return (int) ($fila['total'] ?? 0);
    }

    public function citasHoy(): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM Citas c
             INNER JOIN Agenda_Disponible h ON h.id_horario = c.id_horario
             WHERE c.deleted_at IS NULL
               AND h.fecha = CURDATE()
               AND c.estado_cita IN (:p, :c)',
            [':p' => Cita::PROGRAMADA, ':c' => Cita::CONFIRMADA]
        )->fetch();
        return (int) ($fila['total'] ?? 0);
    }

    /** @return array<int,array<string,mixed>> */
    public function citasDelDia(int $limite = 8): array
    {
        return $this->citasProximas($limite, null);
    }

    /**
     * Citas abiertas desde hoy (oficina y mecánico).
     *
     * @return array<int,array<string,mixed>>
     */
    public function citasProximas(int $limite = 8, ?int $idMecanico = null): array
    {
        $limite = max(1, min(20, $limite));
        $sql = 'SELECT
                    c.id_cita, c.estado_cita, c.motivo,
                    cl.nombre AS cliente_nombre,
                    v.placa, v.marca, v.modelo, v.tipo,
                    h.hora_inicio, h.fecha,
                    u.nombre AS mecanico_nombre,
                    s.nombre AS servicio_nombre
                FROM Citas c
                INNER JOIN Agenda_Disponible h ON h.id_horario = c.id_horario
                INNER JOIN Clientes cl ON cl.id_cliente = c.id_cliente
                INNER JOIN Vehiculo v ON v.id_vehiculo = c.id_vehiculo
                LEFT JOIN Usuarios u ON u.id_usuario = h.id_usuario
                LEFT JOIN Servicios s ON s.id_servicio = c.id_servicio
                WHERE c.deleted_at IS NULL
                  AND h.fecha >= CURDATE()
                  AND c.estado_cita NOT IN (:canc, :comp)';
        $params = [
            ':canc' => Cita::CANCELADA,
            ':comp' => Cita::COMPLETADA,
        ];
        if ($idMecanico !== null && $idMecanico > 0) {
            $sql .= ' AND h.id_usuario = :u';
            $params[':u'] = $idMecanico;
        }
        $sql .= ' ORDER BY h.fecha ASC, h.hora_inicio ASC, c.id_cita ASC LIMIT ' . $limite;
        $out = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $out[] = $this->mapCitaPanel($fila);
        }
        return $out;
    }

    /** @param array<string,mixed> $fila */
    private function mapCitaPanel(array $fila): array
    {
        $hora = substr((string) ($fila['hora_inicio'] ?? ''), 0, 5);
        $fecha = substr((string) ($fila['fecha'] ?? ''), 0, 10);
        $vehiculo = trim(($fila['marca'] ?? '') . ' ' . ($fila['modelo'] ?? ''));
        return [
            'id_cita'         => (int) $fila['id_cita'],
            'estado'          => $fila['estado_cita'],
            'estado_cita'     => $fila['estado_cita'],
            'motivo'          => $fila['motivo'],
            'cliente_nombre'  => $fila['cliente_nombre'],
            'placa'           => $fila['placa'],
            'vehiculo_tipo'   => $fila['tipo'] ?? null,
            'vehiculo_label'  => $vehiculo !== '' ? $vehiculo : (string) ($fila['placa'] ?? ''),
            'hora'            => $hora,
            'hora_inicio'     => $hora,
            'fecha'           => $fecha,
            'mecanico_nombre' => $fila['mecanico_nombre'] ?? null,
            'servicio_nombre' => $fila['servicio_nombre'] ?? null,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function ordenesRecientes(int $limite = 8): array
    {
        $limite = max(1, min(30, $limite));
        $sql = 'SELECT
                    o.id_orden, o.estado, o.fecha_ingreso, o.id_usuario, o.id_vehiculo,
                    c.nombre AS cliente_nombre,
                    v.placa, v.marca, v.modelo, v.tipo
                FROM Orden_Servicio o
                INNER JOIN Vehiculo v ON v.id_vehiculo = o.id_vehiculo
                INNER JOIN Clientes c ON c.id_cliente = v.id_cliente
                WHERE o.deleted_at IS NULL
                ORDER BY o.fecha_ingreso DESC, o.id_orden DESC
                LIMIT ' . $limite;
        $out = [];
        foreach ($this->db->query($sql)->fetchAll() as $fila) {
            $out[] = [
                'id_orden'       => (int) $fila['id_orden'],
                'estado'         => $fila['estado'],
                'fecha_ingreso'  => $fila['fecha_ingreso'],
                'id_usuario'     => (int) $fila['id_usuario'],
                'id_vehiculo'    => (int) $fila['id_vehiculo'],
                'cliente_nombre' => $fila['cliente_nombre'],
                'placa'          => $fila['placa'],
                'vehiculo_tipo'  => $fila['tipo'] ?? null,
                'vehiculo_label' => trim(($fila['marca'] ?? '') . ' ' . ($fila['modelo'] ?? '')),
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    public function mecanicos(): array
    {
        $in = $this->inCerrados();
        $sql = 'SELECT
                    u.id_usuario, u.nombre, u.estado, r.nombre AS rol_nombre,
                    (
                        SELECT COUNT(*) FROM Orden_Servicio o
                        WHERE o.id_usuario = u.id_usuario
                          AND o.deleted_at IS NULL
                          AND o.estado NOT IN (' . $in['sql'] . ')
                    ) AS ordenes_activas
                FROM Usuarios u
                INNER JOIN Roles r ON r.id_rol = u.id_rol
                WHERE u.deleted_at IS NULL
                  AND u.estado = :activo
                  AND LOWER(r.nombre) LIKE :rol
                ORDER BY u.nombre';
        $params = array_merge($in['params'], [
            ':activo' => 'Activo',
            ':rol'    => '%mecanic%',
        ]);
        $out = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $out[] = [
                'id_usuario'      => (int) $fila['id_usuario'],
                'nombre'          => $fila['nombre'],
                'estado'          => $fila['estado'],
                'rol'             => $fila['rol_nombre'],
                'ordenes_activas' => (int) $fila['ordenes_activas'],
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    public function actividad(int $limite = 8): array
    {
        $limite = max(1, min(30, $limite));
        $sql = 'SELECT l.id_log, l.accion, l.tabla_afectada, l.registro_id, l.fecha,
                       u.nombre AS usuario_nombre
                FROM Logs_Actoria l
                LEFT JOIN Usuarios u ON u.id_usuario = l.id_usuario
                WHERE l.accion <> :login
                ORDER BY l.fecha DESC, l.id_log DESC
                LIMIT ' . $limite;
        $out = [];
        foreach ($this->db->query($sql, [':login' => 'LOGIN'])->fetchAll() as $fila) {
            $out[] = [
                'id_log'          => (int) $fila['id_log'],
                'accion'          => $fila['accion'],
                'tabla_afectada'  => $fila['tabla_afectada'],
                'registro_id'     => (int) $fila['registro_id'],
                'fecha'           => $fila['fecha'],
                'usuario_nombre'  => $fila['usuario_nombre'] ?: 'Sistema',
            ];
        }
        return $out;
    }

    /** @return array<int,array{clave:string,total:float}> */
    public function ingresosSerie(string $periodo): array
    {
        if ($periodo === 'year') {
            $filas = $this->db->query(
                "SELECT DATE_FORMAT(f.fecha_emision, '%Y-%m') AS clave, COALESCE(SUM(f.total), 0) AS total
                 FROM Facturas f
                 WHERE f.deleted_at IS NULL
                   AND f.estado = :pagada
                   AND f.fecha_emision >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
                 GROUP BY DATE_FORMAT(f.fecha_emision, '%Y-%m')
                 ORDER BY clave",
                [':pagada' => Factura::PAGADA]
            )->fetchAll();
        } elseif ($periodo === 'month') {
            $filas = $this->db->query(
                "SELECT DATE_FORMAT(f.fecha_emision, '%Y-%m-%d') AS clave, COALESCE(SUM(f.total), 0) AS total
                 FROM Facturas f
                 WHERE f.deleted_at IS NULL
                   AND f.estado = :pagada
                   AND DATE(f.fecha_emision) >= DATE_SUB(CURDATE(), INTERVAL 27 DAY)
                 GROUP BY DATE(f.fecha_emision)
                 ORDER BY clave",
                [':pagada' => Factura::PAGADA]
            )->fetchAll();
        } else {
            $filas = $this->db->query(
                "SELECT DATE(f.fecha_emision) AS clave, COALESCE(SUM(f.total), 0) AS total
                 FROM Facturas f
                 WHERE f.deleted_at IS NULL
                   AND f.estado = :pagada
                   AND DATE(f.fecha_emision) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY DATE(f.fecha_emision)
                 ORDER BY clave",
                [':pagada' => Factura::PAGADA]
            )->fetchAll();
        }
        $map = [];
        foreach ($filas as $fila) {
            $map[(string) $fila['clave']] = (float) $fila['total'];
        }
        return $this->rellenarSerie($periodo, $map);
    }

    /** @param array<string,float> $map */
    private function rellenarSerie(string $periodo, array $map): array
    {
        $hoy = new DateTimeImmutable('today');
        $out = [];
        if ($periodo === 'year') {
            for ($i = 11; $i >= 0; $i--) {
                $d = $hoy->modify('-' . $i . ' months');
                $clave = $d->format('Y-m');
                $out[] = ['clave' => $clave, 'total' => (float) ($map[$clave] ?? 0)];
            }
            return $out;
        }
        if ($periodo === 'month') {
            for ($i = 3; $i >= 0; $i--) {
                $fin = $hoy->modify('-' . ($i * 7) . ' days');
                $ini = $fin->modify('-6 days');
                $total = 0.0;
                $cursor = $ini;
                while ($cursor <= $fin) {
                    $k = $cursor->format('Y-m-d');
                    $total += (float) ($map[$k] ?? 0);
                    $cursor = $cursor->modify('+1 day');
                }
                $out[] = ['clave' => $ini->format('Y-m-d'), 'total' => $total];
            }
            return $out;
        }
        for ($i = 6; $i >= 0; $i--) {
            $d = $hoy->modify('-' . $i . ' days');
            $clave = $d->format('Y-m-d');
            $out[] = ['clave' => $clave, 'total' => (float) ($map[$clave] ?? 0)];
        }
        return $out;
    }

    /** @return array<string,int> */
    public function conteoOrdenesMecanico(int $idUsuario): array
    {
        $porEstado = [];
        foreach ($this->db->query(
            'SELECT o.estado, COUNT(*) AS total
             FROM Orden_Servicio o
             WHERE o.deleted_at IS NULL AND o.id_usuario = :u
             GROUP BY o.estado',
            [':u' => $idUsuario]
        )->fetchAll() as $fila) {
            $porEstado[(string) $fila['estado']] = (int) $fila['total'];
        }
        $activas = 0;
        foreach ($porEstado as $estado => $n) {
            if (!$this->esCerrada($estado)) {
                $activas += $n;
            }
        }
        return [
            'total'          => array_sum($porEstado),
            'activas'        => $activas,
            'pendiente'      => (int) ($porEstado['Pendiente'] ?? 0) + (int) ($porEstado['Diagnóstico'] ?? 0),
            'en_proceso'     => (int) ($porEstado['En Proceso'] ?? 0) + (int) ($porEstado['En Progreso'] ?? 0),
            'completadas'    => (int) ($porEstado['Completada'] ?? 0) + (int) ($porEstado['Finalizada'] ?? 0),
            'por_estado'     => $porEstado,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function ordenesRecientesMecanico(int $idUsuario, int $limite = 5): array
    {
        $limite = max(1, min(20, $limite));
        $sql = 'SELECT
                    o.id_orden, o.estado, o.fecha_ingreso, o.id_usuario, o.id_vehiculo, o.descripcion,
                    c.nombre AS cliente_nombre,
                    v.placa, v.marca, v.modelo, v.tipo
                FROM Orden_Servicio o
                INNER JOIN Vehiculo v ON v.id_vehiculo = o.id_vehiculo
                INNER JOIN Clientes c ON c.id_cliente = v.id_cliente
                WHERE o.deleted_at IS NULL AND o.id_usuario = :u
                ORDER BY o.fecha_ingreso DESC, o.id_orden DESC
                LIMIT ' . $limite;
        $out = [];
        foreach ($this->db->query($sql, [':u' => $idUsuario])->fetchAll() as $fila) {
            $out[] = [
                'id_orden'       => (int) $fila['id_orden'],
                'estado'         => $fila['estado'],
                'fecha_ingreso'  => $fila['fecha_ingreso'],
                'id_usuario'     => (int) $fila['id_usuario'],
                'id_vehiculo'    => (int) $fila['id_vehiculo'],
                'descripcion'    => $fila['descripcion'],
                'cliente_nombre' => $fila['cliente_nombre'],
                'placa'          => $fila['placa'],
                'vehiculo_tipo'  => $fila['tipo'] ?? null,
                'vehiculo_label' => trim(($fila['marca'] ?? '') . ' ' . ($fila['modelo'] ?? '')),
            ];
        }
        return $out;
    }

    public function citasHoyMecanico(int $idUsuario): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM Citas c
             INNER JOIN Agenda_Disponible h ON h.id_horario = c.id_horario
             WHERE c.deleted_at IS NULL
               AND h.fecha = CURDATE()
               AND h.id_usuario = :u
               AND c.estado_cita IN (:p, :c)',
            [
                ':u' => $idUsuario,
                ':p' => Cita::PROGRAMADA,
                ':c' => Cita::CONFIRMADA,
            ]
        )->fetch();
        return (int) ($fila['total'] ?? 0);
    }

    /** @return array<int,array<string,mixed>> */
    public function citasDelDiaMecanico(int $idUsuario, int $limite = 8): array
    {
        return $this->citasProximas($limite, $idUsuario);
    }

    /** @return array{sql:string,params:array<string,string>} */
    private function inCerrados(): array
    {
        $keys = [];
        $params = [];
        foreach (self::ESTADOS_CERRADOS as $i => $estado) {
            $key = ':cerr' . $i;
            $keys[] = $key;
            $params[$key] = $estado;
        }
        return ['sql' => implode(', ', $keys), 'params' => $params];
    }

    private function esCerrada(string $estado): bool
    {
        return in_array($estado, self::ESTADOS_CERRADOS, true);
    }
}
