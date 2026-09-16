<?php
/**
 * Entrada del proyecto.
 * No usa RewriteRule. Si ves esta pantalla, PHP y htdocs están bien.
 * Si InfinityFree muestra su 404 propio, los archivos NO están dentro de htdocs.
 */
declare(strict_types=1);

$relativo = 'views/bienvenida/index.php';
$existe   = is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativo));

$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$base   = rtrim(str_replace('\\', '/', dirname($script)), '/');
if ($base === '.' || $base === '\\') {
    $base = '';
}
$ruta = ($base === '' ? '' : $base) . '/' . $relativo;

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$destino = ($host !== '')
    ? (($https ? 'https' : 'http') . '://' . $host . $ruta)
    : $ruta;

// Redirección HTTP solo si el archivo existe
if ($existe && $host !== '') {
    header('Location: ' . $destino, true, 302);
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

header('Content-Type: text/html; charset=utf-8');
$destinoEsc = htmlspecialchars($destino, ENT_QUOTES, 'UTF-8');
$dirEsc     = htmlspecialchars(__DIR__, ENT_QUOTES, 'UTF-8');
$scriptEsc  = htmlspecialchars($script, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MotorSoft · Entrada</title>
    <?php if ($existe): ?>
    <meta http-equiv="refresh" content="0;url=<?= $destinoEsc ?>">
    <script>location.replace(<?= json_encode($destino, JSON_UNESCAPED_SLASHES) ?>);</script>
    <?php endif; ?>
    <style>
        body{font-family:system-ui,sans-serif;max-width:640px;margin:2rem auto;padding:0 1rem;line-height:1.45}
        code{background:#f3f4f6;padding:.1rem .35rem;border-radius:4px;word-break:break-all}
        .ok{color:#15803d}.bad{color:#b91c1c}
        a.btn{display:inline-block;margin-top:1rem;padding:.7rem 1.1rem;background:#ff9800;color:#111;text-decoration:none;border-radius:8px;font-weight:700}
        ul{padding-left:1.2rem}
    </style>
</head>
<body>
    <h1>MotorSoft</h1>
    <?php if ($existe): ?>
        <p class="ok">PHP funciona. Redirigiendo a la bienvenida…</p>
        <p><a class="btn" href="<?= $destinoEsc ?>">Ir a la bienvenida</a></p>
    <?php else: ?>
        <p class="bad"><strong>No está la página de bienvenida en este servidor.</strong></p>
        <p>Falta el archivo <code><?= htmlspecialchars($relativo, ENT_QUOTES, 'UTF-8') ?></code> junto a este <code>index.php</code>.</p>
        <p>En el File Manager de InfinityFree, dentro de <code>htdocs</code>, debes ver algo así:</p>
        <ul>
            <li><code>index.php</code> (este archivo)</li>
            <li><code>views/bienvenida/index.php</code></li>
            <li><code>assets/</code></li>
            <li><code>controllers/</code></li>
        </ul>
        <p><strong>Importante:</strong> todo debe estar <em>dentro</em> de <code>htdocs</code>, no al lado.</p>
    <?php endif; ?>
    <hr>
    <p><small>Diagnóstico: <code>__DIR__=<?= $dirEsc ?></code><br>
    <code>SCRIPT_NAME=<?= $scriptEsc ?></code><br>
    <code>destino=<?= $destinoEsc ?></code></small></p>
</body>
</html>
