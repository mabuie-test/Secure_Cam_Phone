<?php
// lista cameras de um user (autenticação por session obrigatória)
session_start();
require_once __DIR__ . '/../includes/db.php';
if (empty($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false]); exit; }
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT id, device_name, device_token, status FROM devices WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$c = $stmt->fetchAll();
echo json_encode(['success'=>true, 'devices'=>$c]);
