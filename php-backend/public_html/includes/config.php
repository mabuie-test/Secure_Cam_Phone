<?php
// includes/config.php
// Versão segura: preparada para Postgres, evita redefinições e não produz output.
// Substitui o ficheiro atual por este e usa apenas require_once ao incluí-lo.

if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    // Driver DB: 'pgsql' ou 'mysql'
    if (!defined('DB_DRIVER')) {
        define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql');
    }

    // Database
    if (!defined('DB_HOST')) {
        define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    }
    if (!defined('DB_PORT')) {
        $defaultPort = (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') ? 5432 : 3306;
        define('DB_PORT', intval(getenv('DB_PORT') ?: $defaultPort));
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

    // Uploads & limits
    if (!defined('UPLOAD_BASE')) {
        define('UPLOAD_BASE', getenv('UPLOAD_BASE') ?: __DIR__ . '/../uploads');
    }
    if (!defined('MAX_UPLOAD_SIZE')) {
        define('MAX_UPLOAD_SIZE', intval(getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024));
    }

    // Base URL / logs
    if (!defined('BASE_URL')) {
        define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:10000');
    }
    if (!defined('LOG_DIR')) {
        define('LOG_DIR', getenv('LOG_DIR') ?: __DIR__ . '/../logs');
    }

    // cria pastas sem emitir output (suprime erros caso não tenha permissão)
    @mkdir(UPLOAD_BASE, 0755, true);
    @mkdir(LOG_DIR, 0755, true);
}
