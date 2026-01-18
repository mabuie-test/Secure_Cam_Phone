<?php
// api/get_frame.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

header_remove(); // evita headers duplicados

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$uid = (int)$_SESSION['user_id'];
$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
if ($device_id <= 0) {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE id = :did AND user_id = :uid LIMIT 1");
    $stmt->execute([':did' => $device_id, ':uid' => $uid]);
    $d = $stmt->fetch();
    if (!$d) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    // caminho onde as frames são guardadas (convenção)
    $dir = rtrim(UPLOAD_BASE, '/\\') . '/device_' . $device_id;
    if (!is_dir($dir)) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }

    // encontra o ficheiro mais recente jpeg (suporta .jpg ou .jpeg)
    $files = glob($dir . '/*.{jpg,jpeg}', GLOB_BRACE);
    if (!$files) {
        http_response_code(404);
        echo 'No frames';
        exit;
    }

    // order by modified time desc
    usort($files, function($a,$b){
        return filemtime($b) - filemtime($a);
    });
    $latest = $files[0];

    // devolve imagem com header apropriado (cache-control para evitar cache)
    header('Content-Type: image/jpeg');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($latest);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    error_log("get_frame error: " . $e->getMessage());
    echo 'Server error';
    exit;
}
