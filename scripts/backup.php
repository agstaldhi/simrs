<?php

/**
 * Database Backup Script
 * 
 * Usage:
 * php scripts/backup.php
 * 
 * Or via cron:
 * 0 2 * * * /usr/bin/php /var/www/simrs/scripts/backup.php
 */

// Load configuration
require_once __DIR__ . '/../config/db.php';
$config = include __DIR__ . '/../config/db.php';

// Parse DSN to get database info
preg_match('/dbname=([^;]+)/', $config['dsn'], $dbMatch);
preg_match('/host=([^;]+)/', $config['dsn'], $hostMatch);

$dbName = $dbMatch[1] ?? 'simrs';
$dbHost = $hostMatch[1] ?? 'localhost';
$dbUser = $config['user'];
$dbPass = $config['pass'];

// Backup configuration
$backupDir = __DIR__ . '/../storage/backups';
$retentionDays = 30; // Keep backups for 30 days

// Create backup directory if not exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Generate backup filename
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/backup_' . $dbName . '_' . $timestamp . '.sql';
$backupFileGz = $backupFile . '.gz';

echo "===========================================\n";
echo "SIMRS Database Backup\n";
echo "===========================================\n";
echo "Database: $dbName\n";
echo "Host: $dbHost\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

// Start backup
echo "Starting backup...\n";

// Build mysqldump command
$command = sprintf(
    'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    escapeshellarg($dbPass),
    escapeshellarg($dbName),
    escapeshellarg($backupFile)
);

// Execute backup
exec($command, $output, $returnVar);

if ($returnVar !== 0) {
    echo "ERROR: Backup failed!\n";
    echo "Output: " . implode("\n", $output) . "\n";

    // Log error
    $logFile = __DIR__ . '/../storage/logs/backup_errors.log';
    $errorMsg = sprintf(
        "[%s] Backup failed for database %s. Return code: %d. Output: %s\n",
        date('Y-m-d H:i:s'),
        $dbName,
        $returnVar,
        implode(' ', $output)
    );
    file_put_contents($logFile, $errorMsg, FILE_APPEND);

    exit(1);
}

echo "Backup created: $backupFile\n";

// Get file size
$fileSize = filesize($backupFile);
$fileSizeMB = round($fileSize / 1024 / 1024, 2);
echo "File size: $fileSizeMB MB\n";

// Compress backup
echo "Compressing backup...\n";

$gzCommand = sprintf('gzip -f %s', escapeshellarg($backupFile));
exec($gzCommand, $gzOutput, $gzReturnVar);

if ($gzReturnVar === 0 && file_exists($backupFileGz)) {
    $compressedSize = filesize($backupFileGz);
    $compressedSizeMB = round($compressedSize / 1024 / 1024, 2);
    $compressionRatio = round((1 - $compressedSize / $fileSize) * 100, 1);

    echo "Backup compressed: $backupFileGz\n";
    echo "Compressed size: $compressedSizeMB MB\n";
    echo "Compression ratio: $compressionRatio%\n";
} else {
    echo "Warning: Compression failed, keeping uncompressed backup\n";
}

// Clean old backups
echo "\nCleaning old backups (retention: $retentionDays days)...\n";

$files = glob($backupDir . '/backup_*.sql*');
$deletedCount = 0;
$currentTime = time();

foreach ($files as $file) {
    $fileAge = $currentTime - filemtime($file);
    $fileAgeDays = $fileAge / 86400; // Convert to days

    if ($fileAgeDays > $retentionDays) {
        if (unlink($file)) {
            echo "Deleted old backup: " . basename($file) . " (age: " . round($fileAgeDays, 1) . " days)\n";
            $deletedCount++;
        }
    }
}

if ($deletedCount === 0) {
    echo "No old backups to delete\n";
} else {
    echo "Deleted $deletedCount old backup(s)\n";
}

// Log success
$logFile = __DIR__ . '/../storage/logs/backup_success.log';
$successMsg = sprintf(
    "[%s] Backup successful. File: %s, Size: %s MB\n",
    date('Y-m-d H:i:s'),
    basename($backupFileGz),
    $compressedSizeMB ?? $fileSizeMB
);
file_put_contents($logFile, $successMsg, FILE_APPEND);

// Save backup record to database (if needed)
try {
    $pdo = new PDO($config['dsn'], $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        INSERT INTO backups (
            backup_number, backup_type, backup_method,
            file_name, file_path, file_size,
            backup_start, backup_end, status
        ) VALUES (
            ?, 'full', 'scheduled',
            ?, ?, ?,
            ?, NOW(), 'completed'
        )
    ");

    $backupNumber = 'BKP-' . date('Ymd-His');
    $fileName = basename($backupFileGz);
    $filePath = $backupFileGz;
    $fileSize = file_exists($backupFileGz) ? filesize($backupFileGz) : filesize($backupFile);

    $stmt->execute([
        $backupNumber,
        $fileName,
        $filePath,
        $fileSize,
        date('Y-m-d H:i:s')
    ]);

    echo "\nBackup record saved to database\n";
} catch (PDOException $e) {
    echo "\nWarning: Could not save backup record to database: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
echo "Backup completed successfully!\n";
echo "===========================================\n";

exit(0);
