<?php

/**
 * Conexión a base de datos (local XAMPP + InfinityFree).
 */

if (!defined('APP_BOOTSTRAP')) {
    die('Acceso no permitido');
}

$httpHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$esLocal = in_array($httpHost, ['localhost', '127.0.0.1'], true)
    || str_contains($httpHost, 'localhost')
    || str_contains(__DIR__, 'xampp');

if ($esLocal) {
    // XAMPP local
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'localhost');
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', 'db_taller');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', 'root');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', '');
    }
} else {
    // InfinityFree — datos del panel MySQL Databases
    // Host: NO uses "localhost". Copia el hostname exacto (ej. sql306.infinityfree.com).
    // Pass: la misma contraseña del panel / FTP de InfinityFree.
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'sql306.infinityfree.com');
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', 'if0_42927589_db_taller');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', 'if0_42927589');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', 'OQKiRdMvlKJJdR');
    }
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // InfinityFree: NUNCA persistent (rompe con max_user_connections)
                PDO::ATTR_PERSISTENT         => false,
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connection->exec("SET NAMES '" . DB_CHARSET . "'");
        } catch (PDOException $e) {
            error_log('Error de conexión BD: ' . $e->getMessage());
            logError('Error de conexión BD', ['msg' => $e->getMessage(), 'host' => DB_HOST, 'db' => DB_NAME]);
            $msg = (defined('ENTORNO') && ENTORNO === 'development')
                ? ('Error conectando a la base de datos: ' . $e->getMessage())
                : 'Error conectando a la base de datos. Revisa host, nombre, usuario y contraseña en controllers/config/database.php';
            throw new Exception($msg);
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error en consulta: ' . $e->getMessage());
            $detalle = (defined('ENTORNO') && ENTORNO === 'development')
                ? $e->getMessage()
                : 'Error ejecutando consulta';
            throw new Exception($detalle);
        }
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollback();
    }

    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    private function __clone() {}

    public function __wakeup() {}
}

function getDB() {
    return Database::getInstance();
}
