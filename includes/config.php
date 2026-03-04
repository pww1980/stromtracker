<?php
// Database configuration
// Copy this file to config.local.php and adjust values for your environment

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'stromtracker');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// App settings
define('APP_VERSION', '1.0.0');
define('SESSION_NAME', 'stromtracker_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Timezone
date_default_timezone_set('Europe/Berlin');

// Base URL path for subdirectory installs (auto-detected)
// e.g. '' for root install, '/strom' for https://domain.de/strom/
if (!defined('BASE_PATH')) {
    $__appRoot = dirname(__DIR__);
    $__docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    if ($__docRoot !== '' && strpos($__appRoot, $__docRoot) === 0) {
        $__bp = str_replace('\\', '/', substr($__appRoot, strlen($__docRoot)));
        define('BASE_PATH', rtrim($__bp, '/'));
    } else {
        define('BASE_PATH', '');
    }
    unset($__appRoot, $__docRoot, $__bp);
}
