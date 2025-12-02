<?php

/**
 * SIMRS - Entry Point
 * 
 * Main application entry point
 */

// Start output buffering
ob_start();

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Load configuration
$appConfig = require ROOT_PATH . '/config/app.php';

// Define constants from config
define('APP_ENV', $appConfig['environment'] ?? 'production');
define('APP_DEBUG', $appConfig['debug'] ?? false);
define('BASE_URL', $appConfig['app_url'] ?? 'http://localhost');
define('SESSION_NAME', $appConfig['session_name'] ?? 'SIMRS_SESSION');
define('SESSION_LIFETIME', $appConfig['session_lifetime'] ?? 7200);
define('LOGIN_MAX_ATTEMPTS', $appConfig['login_max_attempts'] ?? 5);
define('LOGIN_LOCKOUT_MINUTES', $appConfig['login_lockout_minutes'] ?? 15);

// Error reporting based on environment
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Set timezone
date_default_timezone_set($appConfig['timezone'] ?? 'Asia/Jakarta');

// Set error log
ini_set('log_errors', 1);
ini_set('error_log', STORAGE_PATH . '/logs/php_errors.log');

// Custom error handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $errorLog = sprintf(
        "[%s] Error %d: %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );

    error_log($errorLog, 3, STORAGE_PATH . '/logs/app_errors.log');

    if (APP_DEBUG) {
        echo "<b>Error [{$errno}]:</b> {$errstr} in <b>{$errfile}</b> on line <b>{$errline}</b><br>";
    } else {
        echo "An error occurred. Please contact administrator.";
    }

    return true;
});

// Custom exception handler
set_exception_handler(function ($exception) {
    $errorLog = sprintf(
        "[%s] Exception: %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );

    error_log($errorLog, 3, STORAGE_PATH . '/logs/app_errors.log');

    if (APP_DEBUG) {
        echo "<h1>Exception</h1>";
        echo "<p><b>Message:</b> " . $exception->getMessage() . "</p>";
        echo "<p><b>File:</b> " . $exception->getFile() . "</p>";
        echo "<p><b>Line:</b> " . $exception->getLine() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        http_response_code(500);
        require APP_PATH . '/templates/errors/500.php';
    }
});

// Load core classes
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Session.php';
require_once APP_PATH . '/core/CSRF.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/core/Validator.php';
require_once APP_PATH . '/core/Router.php';
require_once APP_PATH . '/core/App.php';

// Load helper functions
require_once APP_PATH . '/helpers/functions.php';

// Initialize session
Session::init();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSP header (adjust as needed)
if (APP_ENV === 'production') {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
}

// HTTPS enforcement in production
if (APP_ENV === 'production' && !isset($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// HSTS header for production
if (APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Autoload middleware (if exists)
$middlewareDir = APP_PATH . '/middleware/';
if (is_dir($middlewareDir)) {
    foreach (glob($middlewareDir . '*.php') as $file) {
        require_once $file;
    }
}

// Initialize and run application
try {
    $app = new App();
} catch (Exception $e) {
    // Log exception
    error_log("Application Error: " . $e->getMessage());

    if (APP_DEBUG) {
        throw $e;
    } else {
        http_response_code(500);
        require APP_PATH . '/templates/errors/500.php';
    }
}

// Flush output buffer
ob_end_flush();
