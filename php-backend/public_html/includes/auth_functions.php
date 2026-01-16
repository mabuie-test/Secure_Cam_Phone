<?php
require_once __DIR__ . '/db.php';

function verify_user_credentials($email, $password) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u) return false;
    if (password_verify($password, $u['password_hash'])) return $u['id'];
    return false;
}

function create_device_token($user_id, $device_name) {
    $token = bin2hex(random_bytes(24));
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO devices (user_id, device_token, device_name, last_seen, status) VALUES (?, ?, ?, NOW(), 'active')");
    $stmt->execute([$user_id, $token, $device_name]);
    return $token;
}

function get_user_by_token($token) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT d.id as device_id, d.user_id, u.email FROM devices d JOIN users u ON u.id = d.user_id WHERE d.device_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch();
}
