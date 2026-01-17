<?php
// htdocs/includes/db.php
require_once __DIR__ . '/config.php';

function getPDO() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = DB_HOST;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;

    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // regista o erro num ficheiro de log visível em LOG_DIR para debug
        $msg = date('c') . " - DB CONNECT ERROR: " . $e->getMessage() . PHP_EOL;
        @file_put_contents(LOG_DIR . '/db_connect_error.log', $msg, FILE_APPEND);
        throw $e;
    }
    return $pdo;
}
