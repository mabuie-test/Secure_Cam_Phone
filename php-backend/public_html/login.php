<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    $uid = verify_user_credentials($email, $pass);
    if ($uid) {
        $_SESSION['user_id'] = $uid;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Credenciais inválidas";
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<h2>Login</h2>
<?php if(!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
    <label>Email<br><input name="email" type="email" required></label><br>
    <label>Password<br><input name="password" type="password" required></label><br>
    <button type="submit">Entrar</button>
</form>
</body>
</html>
