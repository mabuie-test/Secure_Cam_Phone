<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/camera_functions.php';
if (empty($_SESSION['user_id'])) { http_response_code(403); echo "Forbidden"; exit; }
$token = $_POST['device_token'] ?? '';
if (!$token) { echo "Missing"; exit; }

$pdo = getPDO();
$stmt = $pdo->prepare("DELETE FROM devices WHERE device_token = ? AND user_id = ?");
$stmt->execute([$token, $_SESSION['user_id']]);

// remove files
$dir = UPLOAD_BASE . '/' . preg_replace('/[^a-zA-Z0-9_\-]/','_', $token);
if (is_dir($dir)) {
    $files = glob($dir . '/*');
    foreach($files as $f) @unlink($f);
    @rmdir($dir);
}
header('Location: ../dashboard.php');
exit;
