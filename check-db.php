<?php
/**
 * Prueba de conexión MySQL en InfinityFree / local.
 * Ábrelo una vez: https://TU-DOMINIO/check-db.php
 * Luego bórralo del servidor por seguridad.
 */
require_once __DIR__ . '/controllers/config/config.php';
require_once __DIR__ . '/controllers/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$ok = false;
$error = '';
$sqlState = '';
$servidor = '';
$baseSeleccionada = '';
$tablas = [];
$conteos = [];

try {
    // Conexión directa para mostrar el error real que Database oculta en producción.
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false,
    ]);
    $ok = true;
    $info = $db->query('SELECT DATABASE() AS db, VERSION() AS version')->fetch();
    $baseSeleccionada = (string) ($info['db'] ?? '');
    $servidor = (string) ($info['version'] ?? '');
    $tablas = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    // InfinityFree/MariaDB puede devolver los nombres de tabla en minúsculas.
    // Conservamos el nombre real para consultar sin generar falsos "FALTA".
    $tablasPorNombre = [];
    foreach ($tablas as $tablaReal) {
        $tablasPorNombre[strtolower((string) $tablaReal)] = (string) $tablaReal;
    }

    foreach (['Clientes', 'Vehiculo', 'Usuarios', 'Orden_Servicio', 'Facturas'] as $tabla) {
        $tablaReal = $tablasPorNombre[strtolower($tabla)] ?? null;
        if ($tablaReal !== null) {
            $tablaSegura = str_replace('`', '``', $tablaReal);
            $conteos[$tabla] = [
                'nombre_real' => $tablaReal,
                'cantidad' => (int) $db->query('SELECT COUNT(*) FROM `' . $tablaSegura . '`')->fetchColumn(),
            ];
        } else {
            $conteos[$tabla] = [
                'nombre_real' => null,
                'cantidad' => null,
            ];
        }
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
    $sqlState = (string) $e->getCode();
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Check BD MotorSoft</title>
    <style>
        body{font-family:system-ui,sans-serif;max-width:680px;margin:2rem auto;padding:0 1rem;line-height:1.45}
        .ok{color:#15803d}.bad{color:#b91c1c}
        code{background:#f3f4f6;padding:.15rem .4rem;border-radius:4px;word-break:break-all}
        ul{padding-left:1.2rem}
    </style>
</head>
<body>
    <h1>Check base de datos</h1>
    <?php if ($ok): ?>
        <p class="ok"><strong>Conexión OK</strong></p>
        <p>Base seleccionada: <code><?= htmlspecialchars($baseSeleccionada, ENT_QUOTES, 'UTF-8') ?></code></p>
        <p>Servidor MySQL: <code><?= htmlspecialchars($servidor, ENT_QUOTES, 'UTF-8') ?></code></p>
        <p>Tablas en la BD: <strong><?= count($tablas) ?></strong></p>
        <?php if (count($tablas) < 5): ?>
            <p class="bad">Hay pocas tablas. Importa <code>controllers/sql/db_taller.sql</code> desde phpMyAdmin de InfinityFree.</p>
        <?php else: ?>
            <p class="ok">La conexión y la estructura existen.</p>
            <h2>Datos encontrados</h2>
            <ul>
                <?php foreach ($conteos as $tabla => $resultado): ?>
                    <?php $cantidad = $resultado['cantidad']; ?>
                    <li class="<?= $cantidad === null || $cantidad === 0 ? 'bad' : 'ok' ?>">
                        <code><?= htmlspecialchars($tabla, ENT_QUOTES, 'UTF-8') ?></code>:
                        <?= $cantidad === null ? 'FALTA LA TABLA' : $cantidad . ' filas' ?>
                        <?php if ($resultado['nombre_real'] !== null && $resultado['nombre_real'] !== $tabla): ?>
                            (nombre real: <code><?= htmlspecialchars($resultado['nombre_real'], ENT_QUOTES, 'UTF-8') ?></code>)
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (($conteos['Clientes']['cantidad'] ?? 0) === 0 && ($conteos['Usuarios']['cantidad'] ?? 0) === 0): ?>
                <p class="bad"><strong>La estructura está importada, pero no hay datos.</strong> Debes importar una exportación de tu BD local con estructura y datos.</p>
            <?php endif; ?>
            <details>
                <summary>Ver las <?= count($tablas) ?> tablas detectadas</summary>
                <p><code><?= htmlspecialchars(implode(', ', $tablas), ENT_QUOTES, 'UTF-8') ?></code></p>
            </details>
        <?php endif; ?>
    <?php else: ?>
        <p class="bad"><strong>No conectó</strong></p>
        <?php if ($sqlState !== ''): ?><p>SQLSTATE: <code><?= htmlspecialchars($sqlState, ENT_QUOTES, 'UTF-8') ?></code></p><?php endif; ?>
        <p><code><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></code></p>
        <p>Revisa en el panel <strong>MySQL Databases</strong>:</p>
        <ul>
            <li><strong>Host</strong> (ej. <code>sql306.infinityfree.com</code>) — nunca <code>localhost</code></li>
            <li><strong>Database name</strong> (ej. <code>if0_xxxx_db_taller</code>)</li>
            <li><strong>Username</strong> (ej. <code>if0_xxxx</code>)</li>
            <li><strong>Password</strong> = la del panel / FTP de InfinityFree</li>
        </ul>
        <p>Edita <code>controllers/config/database.php</code> con esos datos exactos y vuelve a subir el archivo.</p>
    <?php endif; ?>
    <hr>
    <p><small>Host usado: <code><?= htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8') ?></code> ·
    BD: <code><?= htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') ?></code> ·
    User: <code><?= htmlspecialchars(DB_USER, ENT_QUOTES, 'UTF-8') ?></code></small></p>
    <p><small>Cuando funcione, elimina <code>check-db.php</code> del servidor.</small></p>
</body>
</html>
