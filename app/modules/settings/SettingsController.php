<?php

/**
 * Settings Controller
 * 
 * Handles app-wide configuration, user creation, role matrices, SQL backups, and audit logs.
 */
class SettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * General settings page
     */
    public function general()
    {
        $this->requirePermission('settings.view');

        // Fetch settings grouped by category
        $results = Database::fetchAll("SELECT * FROM system_settings ORDER BY category, setting_key");
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['category']][$row['setting_key']] = $row;
        }

        // Active tab selector (default to 'general')
        $activeTab = $this->get('tab', 'general');
        $validTabs = ['general', 'business', 'billing', 'security', 'email', 'backup'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'general';
        }

        $data = [
            'title' => 'Pengaturan Sistem - SIMRS',
            'settings' => $settings,
            'activeTab' => $activeTab
        ];

        $this->view('settings/views/general', $data);
    }

    /**
     * Update general settings
     */
    public function updateGeneral()
    {
        $this->requirePermission('settings.edit');
        
        // CSRF Verification
        $this->requireCsrf();

        $postedSettings = $this->post('settings', []);
        $activeTab = $this->post('tab', 'general');

        if (!empty($postedSettings) && is_array($postedSettings)) {
            Database::beginTransaction();
            try {
                $user = $this->getCurrentUser();
                $userId = $user['id'] ?? null;

                foreach ($postedSettings as $key => $value) {
                    // Check if setting key exists to prevent inserting new keys
                    $exists = Database::fetchOne("SELECT id, setting_value FROM system_settings WHERE setting_key = ?", [$key]);
                    if ($exists) {
                        // Only update if value changed
                        if ($exists['setting_value'] !== $value) {
                            Database::update('system_settings', [
                                'setting_value' => $value,
                                'updated_by' => $userId
                            ], ['setting_key' => $key]);
                        }
                    }
                }
                Database::commit();

                // Log audit trail
                $this->logAudit('update', 'settings', 'system_settings', null, 'Memperbarui konfigurasi sistem kategori: ' . $activeTab);
                
                $this->setFlash('success', 'Pengaturan sistem berhasil diperbarui.');
            } catch (Exception $e) {
                Database::rollback();
                $this->setFlash('error', 'Gagal memperbarui pengaturan: ' . $e->getMessage());
            }
        } else {
            $this->setFlash('error', 'Tidak ada data pengaturan yang dikirim.');
        }

        $this->redirect('settings/general?tab=' . urlencode($activeTab));
    }

    /**
     * Users listing and registration form
     */
    public function users()
    {
        $this->requirePermission('users.view');

        // Fetch users with their role names
        $query = "SELECT u.*, GROUP_CONCAT(r.display_name SEPARATOR ', ') as role_names 
                  FROM users u 
                  LEFT JOIN user_roles ur ON u.id = ur.user_id 
                  LEFT JOIN roles r ON ur.role_id = r.id 
                  GROUP BY u.id 
                  ORDER BY u.created_at DESC";
        $users = Database::fetchAll($query);

        // Fetch active roles for registration dropdown
        $roles = Database::fetchAll("SELECT * FROM roles WHERE is_active = 1 ORDER BY display_name ASC");

        $data = [
            'title' => 'Manajemen User - SIMRS',
            'users' => $users,
            'roles' => $roles,
            'action' => $this->get('action', 'list') // 'list' or 'add'
        ];

        $this->view('settings/views/users', $data);
    }

    /**
     * Register a new user
     */
    public function storeUser()
    {
        $this->requirePermission('users.create');
        
        // CSRF Verification
        $this->requireCsrf();

        $username = trim($this->post('username', ''));
        $email = trim($this->post('email', ''));
        $fullName = trim($this->post('full_name', ''));
        $phone = trim($this->post('phone', ''));
        $password = $this->post('password', '');
        $roleId = (int)$this->post('role_id', 0);

        // Flash old input
        flashOld([
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'phone' => $phone,
            'role_id' => $roleId
        ]);

        // Simple validation
        if (empty($username) || empty($email) || empty($fullName) || empty($password) || empty($roleId)) {
            $this->setFlash('error', 'Semua kolom wajib diisi kecuali Nomor Telepon.');
            $this->redirect('settings/users?action=add');
        }

        if (strlen($password) < 8) {
            $this->setFlash('error', 'Kata sandi minimal berukuran 8 karakter.');
            $this->redirect('settings/users?action=add');
        }

        // Check if username or email already exists
        $userCheck = Database::fetchOne("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1", [$username, $email]);
        if ($userCheck) {
            $this->setFlash('error', 'Username atau Email sudah terdaftar dalam sistem.');
            $this->redirect('settings/users?action=add');
        }

        // Hash password
        $hashedPassword = Auth::hashPassword($password);

        Database::beginTransaction();
        try {
            $currentUser = $this->getCurrentUser();
            $createdBy = $currentUser['id'] ?? null;

            // Insert user
            $userId = Database::insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $fullName,
                'phone' => $phone,
                'is_active' => 1,
                'created_by' => $createdBy
            ]);

            // Assign role
            Database::insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_by' => $createdBy
            ]);

            Database::commit();
            clearOld();

            $this->logAudit('create', 'users', 'users', $userId, 'Mendaftarkan user baru: ' . $username);
            $this->setFlash('success', 'User ' . htmlspecialchars($username) . ' berhasil didaftarkan.');
            $this->redirect('settings/users');
        } catch (Exception $e) {
            Database::rollback();
            $this->setFlash('error', 'Gagal menyimpan data user: ' . $e->getMessage());
            $this->redirect('settings/users?action=add');
        }
    }

    /**
     * Role & Permission Matrix View
     */
    public function roles()
    {
        $this->requirePermission('roles.view');

        // Fetch active roles
        $roles = Database::fetchAll("SELECT * FROM roles WHERE is_active = 1 ORDER BY id ASC");

        // Fetch all permissions grouped by module
        $permissions = Database::fetchAll("SELECT * FROM permissions ORDER BY module ASC, name ASC");

        // Fetch role permissions mapping
        $mappingResults = Database::fetchAll("SELECT role_id, permission_id FROM role_permissions");
        
        $rolePermissions = [];
        foreach ($mappingResults as $map) {
            $rolePermissions[$map['role_id']][$map['permission_id']] = true;
        }

        $data = [
            'title' => 'Matriks Peran & Izin - SIMRS',
            'roles' => $roles,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ];

        $this->view('settings/views/roles', $data);
    }

    /**
     * Backup SQL page and manual operations
     */
    public function backup()
    {
        $this->requirePermission('settings.backup');

        $backupDir = __DIR__ . '/../../../storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $action = $this->get('action');
        $file = $this->get('file');

        // Sanitize file to prevent Directory Traversal attacks
        if (!empty($file)) {
            $file = basename($file);
            $filePath = $backupDir . '/' . $file;
        }

        // Delete action
        if ($action === 'delete' && !empty($file)) {
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
                $this->logAudit('delete', 'settings', 'system_settings', null, 'Menghapus berkas cadangan database: ' . $file);
                $this->setFlash('success', 'Berkas cadangan ' . htmlspecialchars($file) . ' berhasil dihapus.');
            } else {
                $this->setFlash('error', 'Berkas cadangan tidak ditemukan.');
            }
            $this->redirect('settings/backup');
        }

        // Download action
        if ($action === 'download' && !empty($file)) {
            if (file_exists($filePath) && is_file($filePath)) {
                $this->logAudit('download', 'settings', 'system_settings', null, 'Mengunduh berkas cadangan database: ' . $file);
                
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                
                readfile($filePath);
                exit;
            } else {
                $this->setFlash('error', 'Berkas cadangan tidak ditemukan.');
                $this->redirect('settings/backup');
            }
        }

        // List files
        $files = glob($backupDir . '/*.sql');
        $backups = [];
        foreach ($files as $f) {
            $backups[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'created_at' => filemtime($f)
            ];
        }

        // Sort by date created DESC
        usort($backups, function ($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });

        $data = [
            'title' => 'Backup & Restore Database - SIMRS',
            'backups' => $backups
        ];

        $this->view('settings/views/backup', $data);
    }

    /**
     * Run manual PDO-based SQL dump backup
     */
    public function runBackup()
    {
        $this->requirePermission('settings.backup');
        
        // CSRF Verification
        $this->requireCsrf();

        try {
            $pdo = Database::getConnection();

            // Fetch tables
            $tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            if (empty($tables)) {
                throw new Exception("Tidak ada tabel ditemukan di database.");
            }

            $sql = "-- =====================================================\n";
            $sql .= "-- SIMRS DATABASE BACKUP (PURE PHP PDO GENERATOR)\n";
            $sql .= "-- Waktu: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- =====================================================\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Get show create table
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                
                $sql .= "-- -----------------------------------------------------\n";
                $sql .= "-- Struktur Tabel `{$table}`\n";
                $sql .= "-- -----------------------------------------------------\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createRow['Create Table'] . ";\n\n";

                // Get table data
                $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $sql .= "-- Data Tabel `{$table}`\n";
                    foreach ($rows as $row) {
                        $fields = array_keys($row);
                        $escapedFields = array_map(function($f) { return "`{$f}`"; }, $fields);
                        
                        $values = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $values[] = "NULL";
                            } else {
                                $values[] = $pdo->quote($val);
                            }
                        }
                        
                        $sql .= "INSERT INTO `{$table}` (" . implode(', ', $escapedFields) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $backupDir = __DIR__ . '/../../../storage/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }

            $fileName = 'backup_simrs_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $backupDir . '/' . $fileName;

            file_put_contents($filePath, $sql);

            $this->logAudit('backup', 'settings', 'system_settings', null, 'Membuat cadangan database manual: ' . $fileName);
            $this->setFlash('success', 'Berhasil mencadangkan database dengan nama berkas: ' . $fileName);
        } catch (Exception $e) {
            $this->setFlash('error', 'Gagal mencadangkan database: ' . $e->getMessage());
        }

        $this->redirect('settings/backup');
    }

    /**
     * View audit trail logs
     */
    public function audit()
    {
        $this->requirePermission('audit.view');

        // Fetch latest 500 audit logs
        $query = "SELECT * FROM audit_logs ORDER BY id DESC LIMIT 500";
        $auditLogs = Database::fetchAll($query);

        $data = [
            'title' => 'Audit Log - SIMRS',
            'auditLogs' => $auditLogs
        ];

        $this->view('settings/views/audit', $data);
    }
}
