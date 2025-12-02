<?php

/**
 * Database Configuration
 * 
 * PDO Database connection settings
 */

return [
    // Database connection DSN
    // MySQL: mysql:host=localhost;dbname=simrs;charset=utf8mb4
    // PostgreSQL: pgsql:host=localhost;dbname=simrs
    // SQLite: sqlite:/path/to/database.db
    // SQL Server: sqlsrv:Server=localhost;Database=simrs
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=simrs;charset=utf8mb4',

    // Database username
    'user' => getenv('DB_USER') ?: 'simrs_user',

    // Database password
    'pass' => getenv('DB_PASS') ?: 'password_kuat_123',

    // PDO options
    'options' => [
        // Error mode: throw exceptions
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // Default fetch mode: associative array
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Disable emulated prepared statements (security)
        PDO::ATTR_EMULATE_PREPARES => false,

        // Set character set (MySQL/MariaDB)
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",

        // Persistent connection (optional, uncomment if needed)
        // PDO::ATTR_PERSISTENT => false,
    ]
];
