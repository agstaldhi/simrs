<?php

/**
 * SIMRS - PHPUnit Test Bootstrap File
 */

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Load Composer Autoloader
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Load Environment Variables (.env)
if (class_exists('Dotenv\Dotenv') && file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

// Define App Constants (Override env for testing)
define('APP_ENV', 'testing');
define('APP_DEBUG', true);
define('BASE_URL', 'http://localhost');
define('SESSION_NAME', 'SIMRS_SESSION_TEST');
define('SESSION_LIFETIME', 7200);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Load helper functions
require_once APP_PATH . '/helpers/functions.php';

// Load Autoloader
require_once APP_PATH . '/core/Autoloader.php';

// Start a session for testing to mock Session methods
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Runner';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';

if (session_status() === PHP_SESSION_NONE) {
    // Prevent PHPUnit headers warnings from session cookie settings in CLI
    ini_set('session.use_cookies', 0);
    ini_set('session.use_only_cookies', 0);
    ini_set('session.use_trans_sid', 0);
    
    @session_start();
}
