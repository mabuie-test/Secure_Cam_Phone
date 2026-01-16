<?php
// mjpeg_stream.php?camera={device_token}
require_once __DIR__ . '/includes/config.php';
$camera = $_GET['camera'] ?? '';
if (!$camera) { http_response_code(400); echo "camera missing"; exit; }
$dir = UPLOAD_BASE . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $camera);
$framePath = $dir . '/frame_latest.jpg';

if (!file_exists($framePath)) {
    // serve a placeholder image or 404
    header('Content-Type: image/jpeg');
    readfile(__DIR__ . '/assets/images/placeholder.jpg'); // optional, ensure file exists
    exit;
}

header("Cache-Control: no-cache");
$boundary = "frameboundary";
header("Content-Type: multipart/x-mixed-replace; boundary=$boundary");

while (true) {
    if (connection_aborted()) break;
    if (file_exists($framePath)) {
        $jpg = file_get_contents($framePath);
        echo "--$boundary\r\n";
        echo "Content-Type: image/jpeg\r\n";
        echo "Content-Length: " . strlen($jpg) . "\r\n\r\n";
        echo $jpg . "\r\n";
        flush();
    }
    // sleep to limit CPU (adjust for desired frame rate)
    usleep(500000); // 0.5s -> ~2fps
}
