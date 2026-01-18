<?php
// config.php — versão segura: não produz output e evita re-definições
// Coloca este ficheiro no local actual e usa require_once para o incluir.

if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    // Uploads
    if (!defined('UPLOAD_BASE')) {
        define('UPLOAD_BASE', getenv('UPLOAD_BASE') ?: __DIR__ . '/uploads');
    }
    if (!defined('MAX_UPLOAD_SIZE')) {
        define('MAX_UPLOAD_SIZE', intval(getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024));
    }

    // Database
    if (!defined('DB_HOST')) {
        define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    }
    if (!defined('DB_PORT')) {
        define('DB_PORT', intval(getenv('DB_PORT') ?: 5432));
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', getenv('DB_NAME') ?: 'repurposed_db');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', getenv('DB_USER') ?: 'rep_user');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', getenv('DB_PASS') ?: 'rep_pass');
    }

    // Base URL / logs
    if (!defined('BASE_URL')) {
        define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost');
    }
    if (!defined('LOG_DIR')) {
        define('LOG_DIR', getenv('LOG_DIR') ?: __DIR__ . '/logs');
    }

    // garante pastas sem emitir output
    @mkdir(UPLOAD_BASE, 0755, true);
    @mkdir(LOG_DIR, 0755, true);
}
