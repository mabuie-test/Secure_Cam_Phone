<?php
// includes/config.php
// Config central: preparado para Postgres no Render.
// Usa variáveis de ambiente em produção; mantém fallback para desenvolvimento.

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql'); // 'pgsql' ou 'mysql'
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', intval(getenv('DB_PORT') ?: (DB_DRIVER === 'pgsql' ? 5432 : 3306)));
define('DB_NAME', getenv('DB_NAME') ?: 'repurposed_db');
define('DB_USER', getenv('DB_USER') ?: 'rep_user');
define('DB_PASS', getenv('DB_PASS') ?: 'rep_pass');

define('UPLOAD_BASE', getenv('UPLOAD_BASE') ?: __DIR__ . '/../uploads');
define('MAX_UPLOAD_SIZE', intval(getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024));
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:10000');

define('LOG_DIR', getenv('LOG_DIR') ?: __DIR__ . '/../logs');

@mkdir(UPLOAD_BASE, 0755, true);
@mkdir(LOG_DIR, 0755, true);
