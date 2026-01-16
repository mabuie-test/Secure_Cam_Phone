<?php
// elimina frames/ficheiros mais antigos que X dias para não encher o disco
require_once __DIR__ . '/../../php-backend/public_html/includes/config.php';
$days = 7;
$base = UPLOAD_BASE;
$cut = strtotime("-{$days} days");
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
foreach ($it as $f) {
    if ($f->isFile()) {
        if (filemtime($f->getPathname()) < $cut) {
            @unlink($f->getPathname());
        }
    }
}
echo "Purge done\n";
