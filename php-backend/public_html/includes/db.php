<?php
// includes/db.php
require_once __DIR__ . '/config.php';

function getPDO() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $driver = DB_DRIVER;
    $host = DB_HOST;
    $port = DB_PORT;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;

    if ($driver === 'pgsql') {
        $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
    } else {
        // fallback mysql
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        $logdir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/../logs';
        if (!is_dir($logdir)) @mkdir($logdir, 0755, true);
        $msg = date('c') . " - DB CONNECT ERROR: " . $e->getMessage() . "\nDSN: $dsn\nUser: $user\n";
        @file_put_contents($logdir . '/db_connect_error.log', $msg, FILE_APPEND);

        header('Content-Type: application/json; charset=utf-8', true, 500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection error. Check logs/db_connect_error.log on server.'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
