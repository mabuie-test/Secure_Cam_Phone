<?php
// register_form.php
// Formulário web para criar conta + login automático (usa includes/db.php e includes/config.php)
// Segurança: define REGISTRATION_SECRET no ambiente para proteger o formulário (opcional).

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Helpers
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
$err = null;
$success = null;
$showForm = true;

$secret_env = getenv('REGISTRATION_SECRET') ?: '';

// processamento do POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // verifica secret se estiver definido
    if ($secret_env) {
        $provided = $_POST['reg_secret'] ?? '';
        if (!$provided || !hash_equals($secret_env, $provided)) {
            $err = "Segredo inválido. Registo bloqueado.";
            $showForm = true;
        }
    }

    if (!$err) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $fullname = trim($_POST['fullname'] ?? '');
        $device_name = trim($_POST['device_name'] ?? 'telefone_web');

        // validações
        if ($email === '' || $password === '') {
            $err = "Email e password são obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = "Email inválido.";
        } elseif ($password !== $password2) {
            $err = "As passwords não coincidem.";
        } elseif (strlen($password) < 8) {
            $err = "Password demasiado curta (mínimo 8 caracteres).";
        }

        if (!$err) {
            try {
                $pdo = getPDO();
                $logdir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/logs';
                @mkdir($logdir, 0755, true);

                // rate-limit: evita criação maciça (por IP)
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $maxAttempts = 10;
                $windowSeconds = 3600; // 1 hora

                // garante tabela
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS registration_attempts (
                        id SERIAL PRIMARY KEY,
                        ip VARCHAR(64) NOT NULL,
                        created_at TIMESTAMPTZ DEFAULT now()
                    )
                ");

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM registration_attempts WHERE ip = :ip AND created_at >= (now() - INTERVAL '{$windowSeconds} seconds')");
                $stmt->execute([':ip' => $ip]);
                $count = (int)$stmt->fetchColumn();
                if ($count >= $maxAttempts) {
                    $err = "Muitas tentativas de registo a partir deste IP. Tente mais tarde.";
                } else {
                    // regista tentativa
                    $ins = $pdo->prepare("INSERT INTO registration_attempts (ip) VALUES (:ip)");
                    $ins->execute([':ip' => $ip]);

                    // verifica se email existe
                    $q = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                    $q->execute([':email' => $email]);
                    if ($q->fetch()) {
                        $err = "Email já registado.";
                    } else {
                        // cria user + device em transacção
                        $pdo->beginTransaction();
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                        $insUser = $pdo->prepare("INSERT INTO users (email, password_hash, fullname, created_at) VALUES (:email, :hash, :fullname, now()) RETURNING id");
                        $insUser->execute([
                            ':email' => $email,
                            ':hash' => $passwordHash,
                            ':fullname' => $fullname
                        ]);
                        $userId = $insUser->fetchColumn();

                        // assegura tabela devices
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

                        $token = bin2hex(random_bytes(32));
                        $insDev = $pdo->prepare("INSERT INTO devices (user_id, device_token, device_name, last_seen, status) VALUES (:uid, :token, :dname, now(), 'active') RETURNING id");
                        $insDev->execute([
                            ':uid' => $userId,
                            ':token' => $token,
                            ':dname' => $device_name
                        ]);
                        $deviceId = $insDev->fetchColumn();

                        $pdo->commit();

                        // log
                        @file_put_contents($logdir . '/auth.log', date('c') . " - registered via form - IP: {$ip} - user_id: {$userId} - email: {$email}\n", FILE_APPEND);

                        $success = [
                            'user_id' => (int)$userId,
                            'token' => $token,
                            'device_id' => (int)$deviceId
                        ];
                        $showForm = false;
                    }
                }
            } catch (Throwable $e) {
                if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
                $err = "Erro no servidor: " . h($e->getMessage());
                @file_put_contents(__DIR__ . '/logs/auth_error.log', date('c') . " - register_form error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }
    }
}

// HTML abaixo
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Registo / Login — Formulário</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f3f6f6;color:#222;padding:12px}
    .card{max-width:720px;margin:14px auto;padding:18px;background:#fff;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.06)}
    input,button,select{width:100%;padding:10px;margin-top:8px;border-radius:8px;border:1px solid #ddd;box-sizing:border-box}
    button{background:#2E7D6E;color:#fff;border:0;font-weight:600}
    .row{display:flex;gap:8px}
    .col{flex:1}
    .hint{font-size:13px;color:#666;margin-top:6px}
    pre{background:#111;color:#dfd;padding:10px;border-radius:6px;overflow:auto}
    .err{background:#ffecec;color:#900;padding:8px;border-radius:6px}
    .ok{background:#e8f6ef;color:#056; padding:8px;border-radius:6px}
    label{font-weight:600}
  </style>
</head>
<body>
  <div class="card">
    <h2>Registar nova conta (e login automático)</h2>

    <?php if ($err): ?>
      <div class="err"><?= h($err) ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
      <form method="post" autocomplete="off">
        <?php if ($secret_env): ?>
          <label for="reg_secret">Segredo de registo</label>
          <input id="reg_secret" name="reg_secret" type="text" required placeholder="Segredo">
          <div class="hint">O formulário está protegido por um segredo (variável REGISTRATION_SECRET).</div>
        <?php endif; ?>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= h($_POST['email'] ?? '') ?>">

        <label for="fullname">Nome completo (opcional)</label>
        <input id="fullname" name="fullname" type="text" value="<?= h($_POST['fullname'] ?? '') ?>">

        <div class="row">
          <div class="col">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
          </div>
          <div class="col">
            <label for="password2">Confirmar Password</label>
            <input id="password2" name="password2" type="password" required>
          </div>
        </div>

        <label for="device_name">Nome do dispositivo (opcional)</label>
        <input id="device_name" name="device_name" type="text" value="<?= h($_POST['device_name'] ?? '') ?>" placeholder="ex: meu_telefone">

        <button type="submit">Registar e Entrar</button>
      </form>

      <p class="hint">Após registo o token de sessão será mostrado — guarda-o no teu client. Apaga este formulário do servidor quando terminar.</p>

    <?php else: ?>

      <div class="ok">
        Conta criada e login efectuado com sucesso.
      </div>

      <h3>Detalhes</h3>
      <pre><?php echo "user_id: " . h($success['user_id']) . "\n"; 
                 echo "device_id: " . h($success['device_id']) . "\n";
                 echo "token: " . h($success['token']) . "\n"; ?></pre>

      <p class="hint">Copia o token para o teu app (SharedPreferences). Depois apaga este ficheiro do servidor ou protege com REGISTRATION_SECRET.</p>

    <?php endif; ?>
  </div>
</body>
</html>
