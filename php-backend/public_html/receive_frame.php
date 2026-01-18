<?php
// api/receive_frame.php
// Aceita: multipart/form-data (file field 'frame') OR raw image/jpeg in body.
// Token: X-Device-Token header OR device_token POST/GET field.
// camera_id optional (se forneceres, será verificado contra device id).
// Salva em UPLOAD_BASE/device_{id}/frame_<ts>_<rand>.jpg
// Atualiza devices.last_seen e regista metadados em frames_meta (ou usa record_frame_meta()).

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';               // getPDO()
require_once __DIR__ . '/../includes/auth_functions.php';   // opcional
require_once __DIR__ . '/../includes/camera_functions.php'; // opcional (may have record_frame_meta)

header('Content-Type: application/json; charset=utf-8');

$log = function($m) {
    $dir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/../logs';
    @mkdir($dir, 0755, true);
    @file_put_contents($dir . '/receive_frame.log', date('c') . " - " . $m . PHP_EOL, FILE_APPEND);
};

try {
    // obtain headers (compat)
    $headers = [];
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k,5)))));
                $headers[$name] = $v;
            }
        }
    }

    // get token from header or POST/GET
    $token = '';
    if (!empty($headers['X-Device-Token'])) $token = trim($headers['X-Device-Token']);
    if (!$token && !empty($_POST['device_token'])) $token = trim($_POST['device_token']);
    if (!$token && isset($_GET['device_token'])) $token = trim($_GET['device_token']);

    $cameraId = null;
    if (!empty($_POST['camera_id'])) $cameraId = trim($_POST['camera_id']);
    if ($cameraId === null && isset($_GET['camera_id'])) $cameraId = trim($_GET['camera_id']);

    if (!$token) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'missing token']);
        exit;
    }

    // Authenticate device by token
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id AS device_id, user_id FROM devices WHERE device_token = :t LIMIT 1");
    $stmt->execute([':t' => $token]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$device) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'invalid token']);
        exit;
    }
    $deviceId = (int)$device['device_id'];

    // if cameraId provided, verify it matches deviceId (if cameraId is numeric)
    if ($cameraId !== null && is_numeric($cameraId) && (int)$cameraId !== $deviceId) {
        // optionally allow cameraId != deviceId if your model has cameras per device; here we reject mismatch
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'camera_id does not match device']);
        exit;
    }

    // Determine upload source: multipart file 'frame' or raw body
    $imageData = null;
    $size = 0;

    if (!empty($_FILES['frame']) && isset($_FILES['frame']['error']) && $_FILES['frame']['error'] === UPLOAD_ERR_OK) {
        $size = (int)$_FILES['frame']['size'];
        $tmp = $_FILES['frame']['tmp_name'];
        $imageData = file_get_contents($tmp);
    } else {
        // raw body
        $raw = file_get_contents('php://input');
        if ($raw !== false) {
            $imageData = $raw;
            $size = strlen($raw);
        }
    }

    if (!$imageData || $size < 20) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'no image data']);
        exit;
    }

    if ($size > MAX_UPLOAD_SIZE) {
        http_response_code(413);
        echo json_encode(['success'=>false,'error'=>'file too large']);
        exit;
    }

    // optional: quick magic bytes check for JPEG (FF D8)
    $isJpeg = (substr($imageData, 0, 2) === "\xFF\xD8");
    if (!$isJpeg) {
        // not strictly required; log and continue — some clients may send png
        $log("warning: upload not JPEG, size={$size}, device={$deviceId}");
    }

    // prepare destination
    $dir = rtrim(UPLOAD_BASE, '/\\') . '/device_' . $deviceId;
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            http_response_code(500);
            $log("mkdir failed for $dir");
            echo json_encode(['success'=>false,'error'=>'server error (mkdir)']);
            exit;
        }
    }

    $filename = 'frame_' . time() . '_' . bin2hex(random_bytes(6)) . '.jpg';
    $dest = $dir . '/' . $filename;

    // write file atomically
    $tmpfile = $dir . '/.tmp_' . bin2hex(random_bytes(6));
    if (file_put_contents($tmpfile, $imageData) === false || !@rename($tmpfile, $dest)) {
        // fallback attempt
        @unlink($tmpfile);
        if (file_put_contents($dest, $imageData) === false) {
            http_response_code(500);
            $log("write failed for $dest");
            echo json_encode(['success'=>false,'error'=>'write failed']);
            exit;
        }
    }

    // Update last_seen
    try {
        $upd = $pdo->prepare("UPDATE devices SET last_seen = now() WHERE id = :id");
        $upd->execute([':id' => $deviceId]);
    } catch (Throwable $e) {
        $log("warning: update last_seen failed: " . $e->getMessage());
    }

    // Record meta: prefer existing helper record_frame_meta(device_id, path)
    $relpath = 'device_' . $deviceId . '/' . $filename; // relative to UPLOAD_BASE
    if (function_exists('record_frame_meta')) {
        try {
            record_frame_meta($deviceId, $dest);
        } catch (Throwable $e) {
            $log("record_frame_meta failed: " . $e->getMessage());
            // fallback to DB insert below
            $recorded = false;
        }
    }

    // Fallback: insert into frames_meta if function not present or failed
    if (!isset($recorded) || $recorded !== true) {
        try {
            $ins = $pdo->prepare("INSERT INTO frames_meta (device_id, filename, created_at) VALUES (:did, :fname, now())");
            $ins->execute([':did' => $deviceId, ':fname' => $relpath]);
        } catch (Throwable $e) {
            // If table doesn't exist or insert fails, log and continue
            $log("frames_meta insert failed: " . $e->getMessage());
        }
    }

    // success
    echo json_encode(['success'=>true,'file'=>$relpath]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    $log("fatal: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['success'=>false,'error'=>'server error']);
    exit;
}
