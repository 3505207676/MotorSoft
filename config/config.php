<?php
/**
 * Configuración general del sistema.
 */

if (!defined('APP_BOOTSTRAP')) {
    define('APP_BOOTSTRAP', true);
}

// Entorno: en InfinityFree usa production; en XAMPP development
$httpHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$esLocal = in_array($httpHost, ['localhost', '127.0.0.1'], true)
    || str_contains($httpHost, 'localhost')
    || str_contains(__DIR__, 'xampp');

define('ENTORNO', $esLocal ? 'development' : 'production');

date_default_timezone_set('America/Bogota');

define('BASE_URL', '');
define('API_BASE_URL', '/controllers/api');

define('SESSION_NAME', 'motorsoft_session');
define('SESSION_LIFETIME', 86400);
define('CLIENT_TOKEN_SECRET', 'motorsoft-cli-token-v1');

define('UPLOAD_MAX_SIZE', 5242880);
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

define('LOG_ERRORS', true);
define('LOG_FILE', __DIR__ . '/../logs/error.log');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function logError($message, $context = []) {
    if (!LOG_ERRORS) {
        return;
    }
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    @file_put_contents(LOG_FILE, "[{$timestamp}] {$message}{$contextStr}\n", FILE_APPEND | LOCK_EX);
}

if (ENTORNO === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_INTERNAL_ERROR', 500);
