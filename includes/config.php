<?php
// Database configuration
// Copy this file to config.local.php and adjust values for your environment

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'stromtracker');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Contract / billing period settings
// Set to the month number (1-12) when your electricity contract year starts.
// 1  = January  (standard calendar year)
// 2  = February (Feb–Jan, typical for many German contracts)
define('CONTRACT_START_MONTH', 2);

// App settings
define('APP_VERSION', '1.0.0');
define('SESSION_NAME', 'stromtracker_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Timezone
date_default_timezone_set('Europe/Berlin');

// Base URL path for subdirectory installs (auto-detected via SCRIPT_NAME)
// e.g. '' for root install, '/strom' for https://domain.de/strom/
// Works correctly for scripts at any depth (/, /api/, etc.)
// Override via APP_BASE_PATH env var (useful when nginx alias strips the prefix from SCRIPT_NAME)
if (!defined('BASE_PATH')) {
    if (($__envPath = getenv('APP_BASE_PATH')) !== false) {
        define('BASE_PATH', rtrim($__envPath, '/'));
        unset($__envPath);
    }
}
if (!defined('BASE_PATH')) {
    // How many directory levels is the current script below the app root?
    $__appRoot   = str_replace('\\', '/', dirname(__DIR__));
    $__scriptDir = str_replace('\\', '/', dirname(
        realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: ($_SERVER['SCRIPT_FILENAME'] ?? '')
    ));
    if (strpos($__scriptDir, $__appRoot) === 0) {
        $__rel    = ltrim(substr($__scriptDir, strlen($__appRoot)), '/');
        $__levels = $__rel === '' ? 0 : (substr_count($__rel, '/') + 1);
    } else {
        $__levels = 0;
    }
    // Walk up that many levels from SCRIPT_NAME to find the app URL root
    $__urlDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    for ($__i = 0; $__i < $__levels; $__i++) {
        $__urlDir = dirname($__urlDir);
    }
    define('BASE_PATH', $__urlDir === '/' ? '' : rtrim($__urlDir, '/'));
    unset($__appRoot, $__scriptDir, $__rel, $__levels, $__urlDir, $__i);
}
