<?php
/**
 * Sube este archivo a htdocs y ábrelo en el navegador:
 * https://TU-DOMINIO/check.php
 * Sirve para ver si InfinityFree está leyendo tu carpeta correcta.
 */
header('Content-Type: text/html; charset=utf-8');
$checks = [
    'index.php' => is_file(__DIR__ . '/index.php'),
    'views/bienvenida/index.php' => is_file(__DIR__ . '/views/bienvenida/index.php'),
    'assets/' => is_dir(__DIR__ . '/assets'),
    'controllers/' => is_dir(__DIR__ . '/controllers'),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Check MotorSoft</title>
    <style>
        body{font-family:system-ui,sans-serif;max-width:640px;margin:2rem auto;padding:0 1rem}
        .ok{color:#15803d}.bad{color:#b91c1c}
        code{background:#f3f4f6;padding:.1rem .35rem;border-radius:4px}
    </style>
</head>
<body>
    <h1>Check MotorSoft / InfinityFree</h1>
    <p>Si ves esta página, el dominio apunta a esta carpeta y PHP funciona.</p>
    <p><strong>Carpeta real del servidor:</strong><br><code><?= htmlspecialchars(__DIR__, ENT_QUOTES, 'UTF-8') ?></code></p>
    <ul>
        <?php foreach ($checks as $nombre => $ok): ?>
            <li class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FALTA' ?> — <code><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></code></li>
        <?php endforeach; ?>
    </ul>
    <p><a href="views/bienvenida/index.php">Probar bienvenida directa</a> · <a href="index.php">Probar index.php</a></p>
    <p><small>Esto no usa la base de datos. Un 404 de InfinityFree casi nunca es por MySQL.</small></p>
</body>
</html>
