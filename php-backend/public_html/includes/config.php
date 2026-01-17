<?php
// php-backend/public_html/includes/config.php
// Configuração central — usa variáveis de ambiente quando disponível

// Uploads
$uploads_default = __DIR__ . '/../uploads';
define('UPLOAD_BASE', getenv('UPLOAD_BASE') ?: $uploads_default);
define('MAX_UPLOAD_SIZE', intval(getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024));

// Database (fallback para os valores antigos)
define('DB_HOST', getenv('DB_HOST') ?: 'sql100.ezyro.com');
define('DB_NAME', getenv('DB_NAME') ?: 'ezyro_40918309_vigia');
define('DB_USER', getenv('DB_USER') ?: 'ezyro_40918309');
define('DB_PASS', getenv('DB_PASS') ?: 'ab55674c779e4f5');

// Base URL público
define('BASE_URL', getenv('BASE_URL') ?: 'https://vigia.unaux.com');

// Log dir
define('LOG_DIR', getenv('LOG_DIR') ?: __DIR__ . '/../logs');

// cria pastas se possível
@mkdir(UPLOAD_BASE, 0755, true);
@mkdir(LOG_DIR, 0755, true);
