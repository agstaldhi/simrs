<?php

/**
 * SIMRS - ICD-10 Database Importer & Seeder
 * 
 * Usage:
 * php scripts/import_icd10.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Load Composer Autoloader
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Load Environment Variables (.env)
if (class_exists('Dotenv\Dotenv') && file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

// Load Autoloader & Helpers
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/core/Database.php';

echo "===========================================\n";
echo "SIMRS ICD-10 Catalog Importer\n";
echo "===========================================\n";
echo "Environment: " . APP_ENV . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    echo "ERROR: Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 1. Create target table
try {
    echo "Creating 'icds' table... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `icds` (
      `code` VARCHAR(20) NOT NULL PRIMARY KEY,
      `name_en` TEXT NOT NULL,
      `name_id` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "OK\n";
} catch (PDOException $e) {
    echo "FAILED\n";
    echo "ERROR: Failed to create icds table: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check if already populated
try {
    $countResult = Database::fetchOne("SELECT COUNT(*) as count FROM icds");
    if ($countResult['count'] > 10000) {
        echo "ICD-10 table is already populated (count: {$countResult['count']}). Skipping import.\n";
        exit(0);
    }
} catch (Exception $e) {
    // Table might have issues, let's truncate/recreate
}

// 3. Download raw SQL dump
$url = 'https://raw.githubusercontent.com/fendis0709/icd-10/master/master_icd_x.sql';
echo "Downloading ICD-10 SQL database from $url...\n";

$ctx = stream_context_create(['http' => ['timeout' => 60]]);
$sql = @file_get_contents($url, false, $ctx);

if ($sql === false) {
    echo "ERROR: Failed to download SQL file. Please check internet connection.\n";
    exit(1);
}

echo "Downloaded " . number_format(strlen($sql)) . " bytes.\n";

// 4. Parse INSERT statements
echo "Parsing INSERT queries... ";
preg_match_all('/INSERT INTO `icds` .*?;/is', $sql, $matches);
$queries = $matches[0] ?? [];
$totalQueries = count($queries);

if ($totalQueries === 0) {
    echo "FAILED\n";
    echo "ERROR: No INSERT statements found in SQL file.\n";
    exit(1);
}
echo "OK (Found {$totalQueries} insert statements).\n";

// 5. Run imports inside transaction
$start = microtime(true);
echo "Importing records into database...\n";

try {
    Database::beginTransaction();
    
    // Clear table first to ensure clean state
    $pdo->exec("TRUNCATE TABLE `icds`");
    
    foreach ($queries as $index => $query) {
        echo "  Executing batch " . ($index + 1) . "/{$totalQueries}... ";
        $pdo->exec($query);
        echo "OK\n";
    }
    
    Database::commit();
    $elapsed = round(microtime(true) - $start, 4);
    
    $finalCountResult = Database::fetchOne("SELECT COUNT(*) as count FROM icds");
    echo "\nSuccess! Imported {$finalCountResult['count']} ICD-10 records in {$elapsed} seconds.\n";
    exit(0);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        Database::rollback();
    }
    echo "FAILED\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
