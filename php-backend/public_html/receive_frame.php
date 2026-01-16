<?php
// receive_frame.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/camera_functions.php';

$token = $_POST['device_token'] ?? null;
$cameraId = $_POST['camera_id'] ?? null;

if (!$token || !$cameraId) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'missing token or camera_id']);
    exit;
}

$user = get_user_by_token($token);
if (!$user) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'invalid token']);
    exit;
}

if (!isset($_FILES['frame'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'no frame uploaded']);
    exit;
}

$file = $_FILES['frame'];
if ($file['size'] > MAX_UPLOAD_SIZE) {
    http_response_code(413);
    echo json_encode(['success'=>false,'error'=>'file too large']);
    exit;
}

$tmp = $file['tmp_name'];
$dest = save_frame_file($cameraId, $tmp);
if ($dest) {
    record_frame_meta($user['device_id'], $dest);
    echo json_encode(['success'=>true]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'save failed']);
}
