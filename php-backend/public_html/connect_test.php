<?php
// connect_test.php - diagnóstico rápido (coloca em public_html e abre no browser)
require_once __DIR__ . '/includes/config.php';

$host = DB_HOST;
$port = DB_PORT;
$user = DB_USER;
$pass = DB_PASS;
$db   = DB_NAME;

header('Content-Type: text/plain; charset=utf-8');

echo "TESTE DE LIGAÇÃO AO DB\n";
echo "Host: $host Port: $port DB: $db User: $user\n\n";

// 1) test TCP socket
$timeout = 5;
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($fp) {
    echo "TCP: CONEXÃO OK ao {$host}:{$port}\n";
    fclose($fp);
} else {
    echo "TCP: ERRO ao conectar a {$host}:{$port} -> ($errno) $errstr\n";
    echo "Se falhar aqui, o container/Render não consegue alcançar o servidor MySQL (firewall / host bloqueado).\n";
    exit;
}

// 2) tenta mysqli
$mysqli = @mysqli_init();
if (!$mysqli) {
    echo "MySQLi: não foi possível inicializar mysqli\n";
    exit;
}
$ok = @mysqli_real_connect($mysqli, $host, $user, $pass, $db, $port, '', 5);
if ($ok) {
    echo "MySQLi: ligação bem sucedida. Versão servidor: " . mysqli_get_server_info($mysqli) . "\n";
    mysqli_close($mysqli);
} else {
    echo "MySQLi: falha na ligação. Erro: (" . mysqli_connect_errno() . ") " . mysqli_connect_error() . "\n";
    echo "Isto indica credenciais erradas, user não autorizado para host remoto, ou conta bloqueada.\n";
}
