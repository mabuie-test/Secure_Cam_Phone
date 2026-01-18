<?php
// api/register_and_login.php
// Cria conta e faz login automático (Postgres).
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

function reply($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

function bad($msg, $code = 400) {
    reply(['success' => false, 'error' => $msg], $code);
}

// Ler input (JSON ou form)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input');
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode($raw, true) ?? [];
} else {
    $data = $_POST;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$fullname = trim($data['fullname'] ?? '');
$device_name = trim($data['device_name'] ?? 'unknown_device');

$logdir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/../logs';
@mkdir($logdir, 0755, true);

// validações
if ($email === '' || $password === '') {
    bad('email e password obrigatórios', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    bad('email inválido', 400);
}
if (strlen($password) < 8) {
    bad('password demasiado curta (mínimo 8 caracteres)', 400);
}

// rate-limit simples por IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$maxAttempts = 10;
$windowSeconds = 3600; // 1 hora

try {
    $pdo = getPDO();

    // garante a tabela de attempts
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS registration_attempts (
            id SERIAL PRIMARY KEY,
            ip VARCHAR(64) NOT NULL,
            created_at TIMESTAMPTZ DEFAULT now()
        )
    ");

    // conta tentativas recentes desse ip
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registration_attempts WHERE ip = :ip AND created_at >= (now() - INTERVAL '{$windowSeconds} seconds')");
    $stmt->execute([':ip' => $ip]);
    $count = (int)$stmt->fetchColumn();
    if ($count >= $maxAttempts) {
        bad('Muitas tentativas de registo a partir deste IP. Tenta mais tarde.', 429);
    }

    // regista tentativa
    $ins = $pdo->prepare("INSERT INTO registration_attempts (ip) VALUES (:ip)");
    $ins->execute([':ip' => $ip]);

    // verifica se email já existe
    $q = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $q->execute([':email' => $email]);
    if ($q->fetch()) {
        bad('Email já registado', 409);
    }

    // cria user e device numa transação
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->beginTransaction();

    // cria utilizador
    $insUser = $pdo->prepare("INSERT INTO users (email, password_hash, fullname, created_at) VALUES (:email, :hash, :fullname, now()) RETURNING id");
    $insUser->execute([
        ':email' => $email,
        ':hash' => $passwordHash,
        ':fullname' => $fullname
    ]);
    $userId = $insUser->fetchColumn();

    // gera token seguro
    $token = bin2hex(random_bytes(32));

    // tenta criar device (se a tabela existir)
    $deviceId = null;
    try {
        // create devices table if not exists (safe)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS devices (
              id SERIAL PRIMARY KEY,
              user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
              device_token VARCHAR(128) NOT NULL UNIQUE,
              device_name VARCHAR(255),
              last_seen TIMESTAMPTZ DEFAULT now(),
              status VARCHAR(32) DEFAULT 'active'
            )
        ");

        $insDev = $pdo->prepare("INSERT INTO devices (user_id, device_token, device_name, last_seen, status) VALUES (:uid, :token, :dname, now(), 'active') RETURNING id");
        $insDev->execute([
            ':uid' => $userId,
            ':token' => $token,
            ':dname' => $device_name
        ]);
        $deviceId = $insDev->fetchColumn();
    } catch (Throwable $e) {
        // se insert devices falhar, registramos mas não abortamos
        @file_put_contents($logdir . '/auth_error.log', date('c') . " - device insert failed during register: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }

    $pdo->commit();

    // log de sucesso
    @file_put_contents($logdir . '/auth.log', date('c') . " - registered & logged in - IP: {$ip} - user_id: {$userId} - email: {$email}\n", FILE_APPEND);

    $out = ['success' => true, 'user_id' => (int)$userId, 'token' => $token];
    if ($deviceId) $out['device_id'] = (int)$deviceId;

    reply($out, 201);

} catch (PDOException $ex) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    @file_put_contents($logdir . '/auth_error.log', date('c') . " - DB error register: " . $ex->getMessage() . PHP_EOL, FILE_APPEND);
    bad('Erro no servidor ao criar conta', 500);
} catch (Throwable $t) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    @file_put_contents($logdir . '/auth_error.log', date('c') . " - register error: " . $t->getMessage() . PHP_EOL, FILE_APPEND);
    bad('Erro interno', 500);
}
