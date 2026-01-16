<?php
// api/auth.php - usado pelo dispositivo para trocar credenciais por token
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_functions.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$device_name = $_POST['device_name'] ?? ('device_' . bin2hex(random_bytes(4)));

if (!$email || !$password) {
    echo json_encode(['success'=>false,'error'=>'missing']);
    exit;
}
$uid = verify_user_credentials($email, $password);
if ($uid) {
    $token = create_device_token($uid, $device_name);
    echo json_encode(['success'=>true, 'token'=>$token]);
} else {
    echo json_encode(['success'=>false,'error'=>'invalid']);
}
