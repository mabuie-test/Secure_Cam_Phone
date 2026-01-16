<?php
session_start();
require_once __DIR__ . '/includes/db.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT id, device_name, device_token, status FROM devices WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$devices = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<h2>Dashboard</h2>
<p><a href="logout.php">Logout</a></p>
<table border="1" cellpadding="6">
<tr><th>ID</th><th>Nome</th><th>Status</th><th>Último frame</th><th>Ações</th></tr>
<?php foreach($devices as $d): ?>
<tr>
    <td><?php echo htmlspecialchars($d['id']); ?></td>
    <td><?php echo htmlspecialchars($d['device_name']); ?></td>
    <td><?php echo htmlspecialchars($d['status']); ?></td>
    <td>
        <img src="mjpeg_stream.php?camera=<?php echo urlencode($d['device_token']); ?>" width="320" height="180" alt="stream">
    </td>
    <td>
        <form method="post" action="api/delete_camera.php" onsubmit="return confirm('Eliminar?');">
            <input type="hidden" name="device_token" value="<?php echo htmlspecialchars($d['device_token']); ?>">
            <button type="submit">Eliminar</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
