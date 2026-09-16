<?php
/**
 * Migración de una sola ejecución para InfinityFree/Linux.
 * Corrige nombres de tablas importados en minúsculas sin borrar datos.
 * Elimina este archivo del servidor después de usarlo.
 */
declare(strict_types=1);

require_once __DIR__ . '/controllers/config/config.php';
require_once __DIR__ . '/controllers/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$canonicas = [
    'Roles',
    'Permisos',
    'Usuarios',
    'Permisos_Rol',
    'Especialidades',
    'Usuario_Especialidad',
    'Sesiones',
    'Logs_Actoria',
    'Contratos',
    'Nominas',
    'Nomina_Rol',
    'Clientes',
    'Vehiculo',
    'Servicios',
    'Orden_Servicio',
    'Detalles_Servicios',
    'Categorias',
    'Productos',
    'Stock',
    'Ventas',
    'Detalle_Items',
    'Movimientos',
    'Facturas',
    'Detalles_Factura',
    'Proveedores',
    'Conversaciones',
    'Mensajes',
    'Adjuntos',
    'Agenda_Disponible',
    'Citas',
    'Cuentas',
    'Sesiones_Caja',
    'Conceptos_Financieros',
    'Configuracion',
    'Transacciones_Caja',
];

$ok = false;
$mensaje = '';
$error = '';
$cambios = [];
$lowerCaseTableNames = null;

try {
    $pdo = getDB()->getConnection();
    $lowerCaseTableNames = (int) $pdo->query('SELECT @@lower_case_table_names')->fetchColumn();
    $reales = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $porMinuscula = [];
    foreach ($reales as $real) {
        $porMinuscula[strtolower((string) $real)] = (string) $real;
    }

    foreach ($canonicas as $canonica) {
        $real = $porMinuscula[strtolower($canonica)] ?? null;
        if ($real !== null && $real !== $canonica) {
            $cambios[$real] = $canonica;
        }
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (($_POST['confirmar'] ?? '') !== 'NORMALIZAR') {
            throw new RuntimeException('Confirmación inválida.');
        }
        if ($lowerCaseTableNames !== 0) {
            throw new RuntimeException(
                'El servidor usa lower_case_table_names=' . $lowerCaseTableNames
                . '; en este modo no se deben renombrar las tablas.'
            );
        }
        if (!$cambios) {
            $ok = true;
            $mensaje = 'Los nombres ya estaban correctos. No se hizo ningún cambio.';
        } else {
            // Un único RENAME TABLE es atómico. Los nombres temporales permiten
            // cambiar solo mayúsculas/minúsculas de forma segura en Linux.
            $partes = [];
            $i = 0;
            foreach ($cambios as $actual => $destino) {
                $i++;
                $tmp = '__motorsoft_case_' . $i;
                $actualSql = str_replace('`', '``', $actual);
                $destinoSql = str_replace('`', '``', $destino);
                $tmpSql = str_replace('`', '``', $tmp);
                $partes[] = "`{$actualSql}` TO `{$tmpSql}`";
                $partes[] = "`{$tmpSql}` TO `{$destinoSql}`";
            }
            $pdo->exec('RENAME TABLE ' . implode(', ', $partes));
            $ok = true;
            $mensaje = count($cambios) . ' tablas fueron normalizadas correctamente.';
            $cambios = [];
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Normalizar tablas · MotorSoft</title>
    <style>
        body{font-family:system-ui,sans-serif;max-width:760px;margin:2rem auto;padding:0 1rem;line-height:1.5}
        code{background:#f3f4f6;padding:.15rem .4rem;border-radius:4px}
        .ok{color:#15803d}.bad{color:#b91c1c}.warn{color:#a16207}
        button{padding:.75rem 1rem;border:0;border-radius:8px;background:#ff9800;color:#111;font-weight:700;cursor:pointer}
        table{width:100%;border-collapse:collapse;margin:1rem 0}
        th,td{text-align:left;border-bottom:1px solid #ddd;padding:.45rem}
    </style>
</head>
<body>
    <h1>Normalizar nombres de tablas</h1>
    <p>Servidor: <code>lower_case_table_names=<?= htmlspecialchars((string) $lowerCaseTableNames, ENT_QUOTES, 'UTF-8') ?></code></p>

    <?php if ($error !== ''): ?>
        <p class="bad"><strong>Error:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif ($ok): ?>
        <p class="ok"><strong><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p>Prueba nuevamente el login y los módulos. Después elimina <code>fix-table-names.php</code> y <code>check-db.php</code>.</p>
    <?php elseif ($lowerCaseTableNames !== 0): ?>
        <p class="warn">Este servidor no distingue las mayúsculas de las tablas. No ejecutes esta migración; el problema está en otra parte.</p>
    <?php elseif (!$cambios): ?>
        <p class="ok">Todos los nombres ya coinciden con el código. No hay nada que cambiar.</p>
    <?php else: ?>
        <p>Se detectaron <strong><?= count($cambios) ?></strong> tablas cuyo uso de mayúsculas no coincide con el código:</p>
        <table>
            <thead><tr><th>Nombre actual</th><th>Nombre requerido</th></tr></thead>
            <tbody>
            <?php foreach ($cambios as $actual => $destino): ?>
                <tr>
                    <td><code><?= htmlspecialchars($actual, ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><code><?= htmlspecialchars($destino, ENT_QUOTES, 'UTF-8') ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>La operación solo cambia nombres; no elimina tablas ni registros.</p>
        <form method="post">
            <input type="hidden" name="confirmar" value="NORMALIZAR">
            <button type="submit">Corregir nombres de tablas</button>
        </form>
    <?php endif; ?>
</body>
</html>
