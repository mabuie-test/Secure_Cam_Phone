<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

function ensure_camera_upload_dir($cameraId) {
    $dir = UPLOAD_BASE . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cameraId);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function save_frame_file($cameraId, $tmpPath) {
    $dir = ensure_camera_upload_dir($cameraId);
    $dest = $dir . '/frame_latest.jpg';
    if (file_exists($tmpPath)) {
        move_uploaded_file($tmpPath, $dest);
        return $dest;
    }
    return false;
}

function record_frame_meta($device_id, $filename) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO frames_meta (device_id, filename, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$device_id, $filename]);
}
