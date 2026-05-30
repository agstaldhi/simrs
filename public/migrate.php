<?php
/**
 * SIMRS Database Migration & Seeding Runner
 * Supports CLI and Web modes. Handles DELIMITER parsing for stored procedures/triggers.
 */

// Define path constants
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Load Composer Autoloader
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Load Environment Variables (.env)
if (class_exists('Dotenv\Dotenv') && file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

// Determine if running under CLI
$isCli = (php_sapi_name() === 'cli');

// Load DB Config
$dbConfig = require ROOT_PATH . '/config/db.php';

// Helper to sanitize outputs for Web
function esc($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Function to parse and run SQL files with DELIMITER support
function executeSqlFile($pdo, $filePath, &$outputLog) {
    if (!file_exists($filePath)) {
        $outputLog[] = ['status' => 'error', 'message' => "File not found: " . basename($filePath)];
        return false;
    }

    $sql = file_get_contents($filePath);
    
    // Remove SQL comments (single line -- or /* ... */)
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
    
    // Append any trailing query
    if (trim($currentQuery) !== '') {
        $queries[] = $currentQuery;
    }
    
    $successCount = 0;
    $totalCount = count($queries);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query === '') continue;
        try {
            $pdo->exec($query);
            $successCount++;
        } catch (PDOException $e) {
            $outputLog[] = [
                'status' => 'error',
                'message' => "Error in " . basename($filePath) . ": " . $e->getMessage() . " | Query: " . substr($query, 0, 150) . "..."
            ];
            return false;
        }
    }
    
    $outputLog[] = [
        'status' => 'success',
        'message' => "Imported " . basename($filePath) . " successfully ({$successCount}/{$totalCount} queries)."
    ];
    return true;
}

$status = 'idle';
$logs = [];
$dbConnected = false;
$connError = '';

// Test Connection
try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['options']);
    $dbConnected = true;
} catch (PDOException $e) {
    $connError = $e->getMessage();
}

// Action: Run Migration
if ($isCli || (isset($_POST['action']) && $_POST['action'] === 'migrate')) {
    if (!$dbConnected) {
        if ($isCli) {
            echo "Database connection failed: {$connError}\n";
            exit(1);
        } else {
            $status = 'error';
            $logs[] = ['status' => 'error', 'message' => "Cannot migrate: Database connection failed. ({$connError})"];
        }
    } else {
        $status = 'running';
        $sqlFiles = [
            ROOT_PATH . '/databases/01_drop_tables.sql',
            ROOT_PATH . '/databases/02_authentication_authorization.sql',
            ROOT_PATH . '/databases/03_master_data.sql',
            ROOT_PATH . '/databases/04_patient_management.sql',
            ROOT_PATH . '/databases/05_scheduling_appointments.sql',
            ROOT_PATH . '/databases/06_laboratory.sql',
            ROOT_PATH . '/databases/07_pharmacy.sql',
            ROOT_PATH . '/databases/08_billing_payment.sql',
            ROOT_PATH . '/databases/09_inventory_purchasing.sql',
            ROOT_PATH . '/databases/10_hr_kepegawaian.sql',
            ROOT_PATH . '/databases/11_audit_logs.sql'
        ];
        
        $migrationSuccess = true;
        foreach ($sqlFiles as $file) {
            if ($isCli) {
                echo "Importing " . basename($file) . "... ";
            }
            $fileLogs = [];
            $res = executeSqlFile($pdo, $file, $fileLogs);
            $logs = array_merge($logs, $fileLogs);
            if ($isCli) {
                if ($res) {
                    echo "OK\n";
                } else {
                    echo "FAILED\n";
                    foreach ($fileLogs as $fl) {
                        if ($fl['status'] === 'error') echo "  Error: " . $fl['message'] . "\n";
                    }
                    exit(1);
                }
            }
            if (!$res) {
                $migrationSuccess = false;
                break;
            }
        }
        
        if ($migrationSuccess) {
            $status = 'success';
            if ($isCli) {
                echo "\nMigration and Seeding Completed Successfully!\n";
            }
        } else {
            $status = 'error';
        }
    }
}

// Action: Self Destruct (Delete this file)
if (!$isCli && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    unlink(__FILE__);
    header("Location: http://simrs.local/auth/login");
    exit;
}

if ($isCli) {
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Installer & Migrator - SIMRS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: var(--spacing-lg, 32px) var(--spacing-md, 16px);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            width: 100%;
            max-width: 680px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            overflow: hidden;
            border-top: 8px solid var(--primary);
        }

        .header {
            padding: 32px 32px 24px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .logo-box {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        .title {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            color: var(--text);
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 1rem;
            color: var(--text-muted);
            margin: 8px 0 0;
        }

        .content {
            padding: 32px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 24px;
        }

        .status-badge.connected {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.failed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .db-info {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
        }

        .db-info-title {
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        .db-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            font-size: 0.95rem;
        }

        .db-item strong {
            color: var(--text-muted);
            display: block;
            margin-bottom: 2px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 16px 24px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .log-box {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: monospace;
            padding: 20px;
            border-radius: 12px;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 24px;
            font-size: 0.9rem;
            line-height: 1.5;
            text-align: left;
        }

        .log-item {
            margin-bottom: 8px;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 8px;
        }

        .log-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .log-success { color: #34d399; }
        .log-error { color: #f87171; }

        .warning-banner {
            background-color: #fffbeb;
            border: 1px solid #fef3c7;
            color: #92400e;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-box">🏥</div>
            <h1 class="title">Installer & Migrator Database</h1>
            <p class="subtitle">Sistem Informasi Manajemen Rumah Sakit (SIMRS)</p>
        </div>

        <div class="content">
            <?php if ($dbConnected): ?>
                <div class="status-badge connected">
                    <span>🟢</span> Database Terhubung ke MySQL
                </div>
            <?php else: ?>
                <div class="status-badge failed">
                    <span>🔴</span> Gagal Menghubungkan Database
                </div>
                <div class="warning-banner">
                    <strong>Peringatan Koneksi:</strong> Aplikasi gagal terhubung ke database. Harap periksa apakah MySQL di XAMPP Anda sudah menyala dan kredensial di <code>config/db.php</code> sudah benar.<br>
                    <small style="display:block; margin-top:8px; color:#b45309;">Error detail: <?= esc($connError) ?></small>
                </div>
            <?php endif; ?>

            <div class="db-info">
                <div class="db-info-title">Detail Konfigurasi Saat Ini:</div>
                <div class="db-grid">
                    <div class="db-item">
                        <strong>DSN Koneksi</strong>
                        <span><?= esc($dbConfig['dsn']) ?></span>
                    </div>
                    <div class="db-item">
                        <strong>Username</strong>
                        <span><?= esc($dbConfig['user']) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($status === 'idle'): ?>
                <div class="warning-banner">
                    <strong>⚠️ PENTING:</strong> Menjalankan migrasi ini akan menghapus tabel-tabel lama dan mengisi ulang database <code>simrs</code> dengan data awal (seed). Semua data lama akan hilang!
                </div>
                
                <?php if ($dbConnected): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="migrate">
                        <button type="submit" class="btn btn-primary">
                            Mulai Migrasi & Seeding
                        </button>
                    </form>
                <?php endif; ?>

            <?php elseif ($status === 'success' || $status === 'error'): ?>
                
                <?php if ($status === 'success'): ?>
                    <div class="warning-banner" style="background-color: #ecfdf5; border-color: #a7f3d0; color: #065f46;">
                        <strong>🎉 Sukses!</strong> Migrasi dan pengisian database selesai dilakukan. Akun default telah terbuat:<br>
                        • Admin: <code>admin@simrs.local</code> / Password: <code>Admin123!</code><br>
                        • Dokter: <code>dokter@simrs.local</code> / Password: <code>Dokter123!</code>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- Action to cleanup the installer for security -->
                        <form method="POST">
                            <input type="hidden" name="action" value="cleanup">
                            <button type="submit" class="btn btn-success">
                                Bersihkan Skrip & Buka Halaman Login
                            </button>
                        </form>
                        <p style="text-align: center; margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                            Tombol di atas akan menghapus berkas <code>public/migrate.php</code> secara otomatis demi keamanan.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="warning-banner" style="background-color: #fef2f2; border-color: #fca5a5; color: #991b1b;">
                        <strong>❌ Terjadi Kesalahan!</strong> Proses migrasi berhenti di tengah jalan. Silakan periksa log di bawah untuk detail kesalahan.
                    </div>
                    <a href="migrate.php" class="btn btn-danger">Coba Ulang</a>
                <?php endif; ?>

                <div class="log-box">
                    <div style="font-weight: 700; margin-bottom: 12px; color: #94a3b8; border-bottom: 1px solid #334155; padding-bottom: 6px;">LOG PROSES MIGRASI:</div>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item log-<?= esc($log['status']) ?>">
                            [<?= esc(strtoupper($log['status'])) ?>] <?= esc($log['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
