<?php
// import_sql.php
// Endpoint HTTP que aceita upload .sql e executa no Postgres.
// Segurança: exige IMPORT_SECRET (definido como ENV var no Render).
// Uso seguro: apagar este endpoint depois de usar.

header('Content-Type: text/plain; charset=utf-8');

// configurações
require_once __DIR__ . '/includes/config.php'; // assume que define DB_DRIVER=pgsql, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS etc.
$secret_env = getenv('IMPORT_SECRET') ?: '';

$maxFileSize = 20 * 1024 * 1024; // 20 MB limite; ajusta se precisares

// Helper para retorno amigável em HTML simples caso venha de browser
function reply_html($title, $body) {
    echo "<!doctype html><html><head><meta charset='utf-8'><title>$title</title></head><body><pre>$body</pre></body></html>";
    exit;
}

// 1) valida método e segredo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply_html('Import SQL', "Use POST com multipart/form-data.\nAbra import_form.php no teu browser.");
}

$secret = $_POST['secret'] ?? '';
if (!$secret_env || $secret !== $secret_env) {
    http_response_code(403);
    reply_html('Erro', "Segredo inválido. Define IMPORT_SECRET como variável de ambiente no Render e use esse valor aqui.");
}

if (!isset($_FILES['sqlfile']) || $_FILES['sqlfile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    reply_html('Erro', "Ficheiro não recebido corretamente. Erro code: " . ($_FILES['sqlfile']['error'] ?? 'n/a'));
}

$file = $_FILES['sqlfile'];
if ($file['size'] > $maxFileSize) {
    http_response_code(413);
    reply_html('Erro', "Ficheiro demasiado grande. Limite: {$maxFileSize} bytes");
}

// ver extensão
$fname = $file['name'];
$ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
if ($ext !== 'sql' && $file['type'] !== 'text/plain') {
    // aceitamos .sql ou text/plain
    // apenas aviso, nao bloqueamos estritamente
}

// grava temporariamente
$uploads_dir = __DIR__ . '/logs/imports';
@mkdir($uploads_dir, 0755, true);
$dest = $uploads_dir . '/upload_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $fname);
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    reply_html('Erro', "Falha ao gravar ficheiro no servidor.");
}

// lê conteúdo
$sql = file_get_contents($dest);
if ($sql === false || trim($sql) === '') {
    unlink($dest);
    http_response_code(400);
    reply_html('Erro', "Ficheiro vazio ou ilegível.");
}

// modo dry-run?
$mode = $_POST['mode'] ?? 'execute';
$mode = ($mode === 'dry') ? 'dry' : 'execute';

// conecta ao Postgres (usa config.php)
if ((defined('DB_DRIVER') && DB_DRIVER !== 'pgsql') || getenv('DB_DRIVER') === 'mysql') {
    // caso esteja a usar mysql (fallback) - não implementado aqui
    unlink($dest);
    http_response_code(500);
    reply_html('Erro', "Este endpoint está configurado apenas para Postgres (pgsql). Ajuste includes/config.php se necessário.");
}

$connStr = sprintf(
    "host=%s port=%d dbname=%s user=%s password=%s",
    DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
);

// tenta conectar
$pg = @pg_connect($connStr);
if (!$pg) {
    $err = pg_last_error();
    @file_put_contents(__DIR__ . '/logs/import_error.log', date('c') . " - connect error: $err\n", FILE_APPEND);
    unlink($dest);
    http_response_code(500);
    reply_html('Erro', "Falha ao conectar ao Postgres: $err\nVer logs/import_error.log");
}

// função executa em transação
$resultOutput = "Import start: " . date('c') . "\nMode: $mode\nFile: $dest\n\n";

if ($mode === 'dry') {
    // mostra começo do ficheiro para confirmação
    $snippet = substr($sql, 0, 2000);
    $resultOutput .= "DRY RUN - primeiro trecho do ficheiro (2000 chars):\n\n" . htmlspecialchars($snippet);
    // não executa nada
    pg_close($pg);
    reply_html('Dry run', $resultOutput);
}

// EXECUTE: executa inteiro dentro de transação
pg_query($pg, 'BEGIN');
$exec = @pg_query($pg, $sql);
if ($exec === false) {
    $pgerr = pg_last_error($pg);
    pg_query($pg, 'ROLLBACK');
    @file_put_contents(__DIR__ . '/logs/import_error.log', date('c') . " - exec error: $pgerr\n", FILE_APPEND);
    pg_close($pg);
    // mantemos o ficheiro para investigação — mas podes apagá-lo manualmente
    http_response_code(500);
    reply_html('Erro ao executar SQL', "Erro do Postgres:\n$pgerr\n\nO ficheiro ficou em: $dest\nConsulta logs/import_error.log");
} else {
    pg_query($pg, 'COMMIT');
    $resultOutput .= "SQL executado com sucesso.\n";
    // opcional: apagar ficheiro de upload
    // unlink($dest);
    pg_close($pg);
    reply_html('Sucesso', $resultOutput . "\n\nIMPORT concluído com sucesso.");
}
