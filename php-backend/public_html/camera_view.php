<?php
// view single camera by token param
$token = $_GET['token'] ?? '';
if (!$token) { echo "Token missing"; exit; }
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Camera View</title></head>
<body>
<h3>Camera</h3>
<img src="mjpeg_stream.php?camera=<?php echo htmlspecialchars($token); ?>" alt="stream">
</body></html>
