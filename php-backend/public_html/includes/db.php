<?php
// php-backend/public_html/includes/db.php
// Localiza config.php em caminhos possíveis e carrega-o; escreve erro amigável se falhar.

$possible = [
    __DIR__ . '/config.php',
    __DIR__ . '/../includes/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/../../includes/config.php',
    __DIR__ . '/../../config.php',
];

$config_found = false;
foreach ($possible as $p) {
    if (file_exists($p)) {
        require_once $p;
        $config_found = true;
        break;
    }
}

if (!$config_found) {
    // tenta escrever um log para ajudar debugging
    $logdir = __DIR__ . '/../logs';
    if (!is_dir($logdir)) @mkdir($logdir, 0755, true);
    $msg  = date('c') . " - config.php not found. Tried paths:\n";
    foreach ($possible as $p) $msg .= " - $p\n";
    $msg .= "Please ensure config.php exists in php-backend/public_html/includes or adjust paths.\n";
    @file_put_contents($logdir . '/missing_config.log', $msg, FILE_APPEND);

    // devolve resposta legível em HTTP (evita fatal)
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode([
        'success' => false,
        'error' => 'Server misconfiguration: config.php not found. Check logs/missing_config.log on server.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Se chegamos aqui, config foi carregado - cria a conexão PDO
function getPDO() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $host = DB_HOST;
        $db = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $user, $pass, $opts);
        return $pdo;
    } catch (PDOException $e) {
        // log do erro
        $logdir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/../logs';
        if (!is_dir($logdir)) @mkdir($logdir, 0755, true);
        $err = date('c') . " - DB CONNECT ERROR: " . $e->getMessage() . PHP_EOL;
        @file_put_contents($logdir . '/db_connect_error.log', $err, FILE_APPEND);

        header('Content-Type: application/json; charset=utf-8', true, 500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection error. Check logs/db_connect_error.log on server.'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
