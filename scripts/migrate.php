<?php

/**
 * SIMRS - CLI Database Migration Runner
 * 
 * Usage:
 * php scripts/migrate.php
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
require_once APP_PATH . '/core/Autoloader.php';

echo "===========================================\n";
echo "SIMRS Database Migrator\n";
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

// 1. Ensure migrations table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (PDOException $e) {
    echo "ERROR: Failed to create/check migrations table: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Fetch applied migrations
try {
    $applied = Database::fetchAll("SELECT migration FROM migrations");
    $appliedList = array_column($applied, 'migration');
} catch (Exception $e) {
    echo "ERROR: Failed to fetch applied migrations: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Scan migrations directory
$migrationsDir = ROOT_PATH . '/migrations';
if (!is_dir($migrationsDir)) {
    echo "ERROR: Migrations directory 'migrations/' not found.\n";
    exit(1);
}

$files = glob($migrationsDir . '/*.sql');
sort($files);

$pending = [];
foreach ($files as $file) {
    $filename = basename($file);
    if (!in_array($filename, $appliedList)) {
        $pending[] = $file;
    }
}

if (empty($pending)) {
    echo "No pending migrations to apply.\n";
    exit(0);
}

// Get the next batch number
$maxBatchResult = Database::fetchOne("SELECT MAX(batch) as max_batch FROM migrations");
$nextBatch = ($maxBatchResult['max_batch'] !== null) ? intval($maxBatchResult['max_batch']) + 1 : 1;

echo "Found " . count($pending) . " pending migration(s). Running batch {$nextBatch}...\n\n";

// Helper function to execute a migration file with SQL statement parsing
function runMigrationFile($pdo, $filePath) {
    $sql = file_get_contents($filePath);
    
    // Remove SQL comments
    $sql = preg_replace('/--.*\n/', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $lines = explode("\n", $sql);
    $queries = [];
    $currentQuery = '';
    $delimiter = ';';
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;
        
        // Match DELIMITER commands
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $matches)) {
            $delimiter = trim($matches[1]);
            continue;
        }
        
        $currentQuery .= $line . "\n";
        
        // Check if query ends with the current delimiter
        if (substr(rtrim($trimmed), -strlen($delimiter)) === $delimiter) {
            $queryToExecute = rtrim($trimmed);
            $queryToExecute = substr($queryToExecute, 0, -strlen($delimiter));
            
            // Reconstruct query without delimiter
            $queries[] = substr($currentQuery, 0, -strlen($line) - 1) . $queryToExecute;
            $currentQuery = '';
        }
    }
    
    if (trim($currentQuery) !== '') {
        $queries[] = $currentQuery;
    }
    
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query === '') continue;
        $pdo->exec($query);
    }
}

$successCount = 0;
foreach ($pending as $file) {
    $filename = basename($file);
    echo "Migrating: {$filename} ... ";
    
    try {
        runMigrationFile($pdo, $file);
        
        // Log migration
        Database::insert('migrations', [
            'migration' => $filename,
            'batch' => $nextBatch
        ]);
        
        echo "OK\n";
        $successCount++;
    } catch (Exception $e) {
        echo "FAILED\n";
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "Migration sequence aborted.\n";
        exit(1);
    }
}

echo "\nMigration sequence completed successfully! Applied {$successCount} migration(s).\n";
exit(0);
