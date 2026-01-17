<?php
// connect_test.php - diagnostico para Postgres/MySQL
require_once __DIR__ . '/includes/config.php';

$driver = DB_DRIVER;
$host = DB_HOST;
$port = DB_PORT;
$user = DB_USER;
$pass = DB_PASS;
$db   = DB_NAME;

header('Content-Type: text/plain; charset=utf-8');

echo "TESTE DE LIGAÇÃO AO DB\n";
echo "Driver: $driver Host: $host Port: $port DB: $db User: $user\n\n";

$timeout = 5;
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($fp) {
    echo "TCP: CONEXÃO OK ao {$host}:{$port}\n";
    fclose($fp);
} else {
    echo "TCP: ERRO ao conectar a {$host}:{$port} -> ($errno) $errstr\n";
    exit;
}

if ($driver === 'pgsql') {
    $connStr = "host=$host port=$port dbname=$db user=$user password=$pass";
    $pg = @pg_connect($connStr);
    if ($pg) {
        echo "Postgres: ligação OK. Versão: " . pg_version($pg)['server'] . "\n";
        pg_close($pg);
    } else {
        echo "Postgres: falha na ligação. Erro: " . error_get_last()['message'] . "\n";
    }
} else {
    $mysqli = @mysqli_init();
    $ok = @mysqli_real_connect($mysqli, $host, $user, $pass, $db, $port, '', 5);
    if ($ok) {
        echo "MySQLi: ligação bem sucedida. Versão servidor: " . mysqli_get_server_info($mysqli) . "\n";
        mysqli_close($mysqli);
    } else {
        echo "MySQLi: falha na ligação. Erro: (" . mysqli_connect_errno() . ") " . mysqli_connect_error() . "\n";
    }
}
