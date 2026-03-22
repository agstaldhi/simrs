<?php

/**
 * Custom Autoloader
 * 
 * Automatically loads classes from core, middleware, and modules directories
 * to replace manual require_once statements.
 */

spl_autoload_register(function ($className) {
    // 1. Check core directory
    $coreFile = APP_PATH . '/core/' . $className . '.php';
    if (file_exists($coreFile)) {
        require_once $coreFile;
        return;
    }

    // 2. Check middleware directory
    $middlewareFile = APP_PATH . '/middleware/' . $className . '.php';
    if (file_exists($middlewareFile)) {
        require_once $middlewareFile;
        return;
    }

    // 3. Check modules directory
    // Extract module name from class name (e.g. DashboardController -> dashboard)
    $moduleName = strtolower(str_replace(['Controller', 'Model'], '', $className));
    $moduleFile = APP_PATH . '/modules/' . $moduleName . '/' . $className . '.php';
    
    if (file_exists($moduleFile)) {
        require_once $moduleFile;
        return;
    }
});
