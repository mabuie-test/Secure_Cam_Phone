<?php
// htdocs/includes/config.php
// Usa variáveis de ambiente quando disponíveis, senão usa os valores existentes (fallback).

// Uploads
$uploads_default = __DIR__ . '/../uploads';
define('UPLOAD_BASE', getenv('UPLOAD_BASE') ?: $uploads_default);
define('MAX_UPLOAD_SIZE', intval(getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024)); // 5 MB por frame

// Database (podem ser definidas no Render como env vars)
define('DB_HOST', getenv('DB_HOST') ?: 'sql100.ezyro.com');
define('DB_NAME', getenv('DB_NAME') ?: 'ezyro_40918309_vigia');
define('DB_USER', getenv('DB_USER') ?: 'ezyro_40918309');
define('DB_PASS', getenv('DB_PASS') ?: 'ab55674c779e4f5');

// Base url público (usado pelo Android/links)
define('BASE_URL', getenv('BASE_URL') ?: 'https://vigia.unaux.com');

// Opcional: diretório de logs (padrão htdocs/logs)
define('LOG_DIR', getenv('LOG_DIR') ?: __DIR__ . '/../logs');

// Garante que pastas existem quando possível (não falha se o host não permitir)
@mkdir(UPLOAD_BASE, 0755, true);
@mkdir(LOG_DIR, 0755, true);
