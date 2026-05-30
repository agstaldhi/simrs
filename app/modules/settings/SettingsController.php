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

        $editingUser = null;
        $action = $this->get('action', 'list');
        if ($action === 'edit') {
            $editId = (int)$this->get('id');
            $editingUser = Database::fetchOne("SELECT u.*, ur.role_id FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id WHERE u.id = ?", [$editId]);
            if (!$editingUser) {
                $this->setFlash('error', 'User tidak ditemukan.');
                $this->redirect('settings/users');
            }
        }

        $data = [
            'title' => 'Manajemen User - SIMRS',
            'users' => $users,
            'roles' => $roles,
            'action' => $action,
            'editingUser' => $editingUser
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
     * Update an existing user
     */
    public function updateUser($id)
    {
        $this->requirePermission('users.edit');
        $this->requireCsrf();

        $id = (int)$id;
        $user = Database::fetchOne("SELECT id, username FROM users WHERE id = ?", [$id]);
        if (!$user) {
            $this->setFlash('error', 'User tidak ditemukan.');
            $this->redirect('settings/users');
        }

        $email = trim($this->post('email', ''));
        $fullName = trim($this->post('full_name', ''));
        $phone = trim($this->post('phone', ''));
        $password = $this->post('password', '');
        $roleId = (int)$this->post('role_id', 0);
        $isActive = (int)$this->post('is_active', 1);

        if (empty($email) || empty($fullName) || empty($roleId)) {
            $this->setFlash('error', 'Email, Nama Lengkap, dan Peran wajib diisi.');
            $this->redirect("settings/users?action=edit&id={$id}");
        }

        // Check if email already exists for another user
        $emailCheck = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $id]);
        if ($emailCheck) {
            $this->setFlash('error', 'Email sudah terdaftar untuk user lain.');
            $this->redirect("settings/users?action=edit&id={$id}");
        }

        Database::beginTransaction();
        try {
            $currentUser = $this->getCurrentUser();
            $userId = $currentUser['id'] ?? null;

            $updateData = [
                'email' => $email,
                'full_name' => $fullName,
                'phone' => $phone,
                'is_active' => $isActive
            ];

            // If password is provided, hash and update it
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    throw new Exception('Kata sandi minimal berukuran 8 karakter.');
                }
                $updateData['password'] = Auth::hashPassword($password);
            }

            Database::update('users', $updateData, ['id' => $id]);

            // Update role assignment
            // Delete existing role assignments
            Database::delete('user_roles', ['user_id' => $id]);
            // Insert new role assignment
            Database::insert('user_roles', [
                'user_id' => $id,
                'role_id' => $roleId,
                'assigned_by' => $userId
            ]);

            Database::commit();
            $this->logAudit('update', 'users', 'users', $id, 'Memperbarui data user: ' . $user['username']);
            $this->setFlash('success', 'User ' . htmlspecialchars($user['username']) . ' berhasil diperbarui.');
            $this->redirect('settings/users');
        } catch (Exception $e) {
            Database::rollback();
            $this->setFlash('error', 'Gagal memperbarui data user: ' . $e->getMessage());
            $this->redirect("settings/users?action=edit&id={$id}");
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleActive($id)
    {
        $this->requirePermission('users.edit');
        $this->requireCsrf();

        $id = (int)$id;
        $currentUser = $this->getCurrentUser();
        if ($id === (int)$currentUser['id']) {
            $this->json(['success' => false, 'message' => 'Anda tidak bisa menonaktifkan akun Anda sendiri.'], 400);
        }

        $user = Database::fetchOne("SELECT id, username, is_active FROM users WHERE id = ?", [$id]);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        $newStatus = $user['is_active'] == 1 ? 0 : 1;
        try {
            Database::update('users', ['is_active' => $newStatus], ['id' => $id]);
            
            $statusText = $newStatus == 1 ? 'Aktif' : 'Nonaktif';
            $this->logAudit('update', 'users', 'users', $id, 'Mengubah status user ' . $user['username'] . ' menjadi ' . $statusText);
            
            $this->json([
                'success' => true,
                'message' => 'Status user berhasil diperbarui.',
                'is_active' => $newStatus
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Gagal mengubah status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $this->requirePermission('users.delete');
        $this->requireCsrf();

        $id = (int)$id;
        $currentUser = $this->getCurrentUser();
        if ($id === (int)$currentUser['id']) {
            $this->setFlash('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
            $this->redirect('settings/users');
        }

        $user = Database::fetchOne("SELECT id, username FROM users WHERE id = ?", [$id]);
        if (!$user) {
            $this->setFlash('error', 'User tidak ditemukan.');
            $this->redirect('settings/users');
        }

        Database::beginTransaction();
        try {
            // Delete role mapping first
            Database::delete('user_roles', ['user_id' => $id]);
            // Delete user
            Database::delete('users', ['id' => $id]);

            Database::commit();
            $this->logAudit('delete', 'users', 'users', $id, 'Menghapus user: ' . $user['username']);
            $this->setFlash('success', 'User ' . htmlspecialchars($user['username']) . ' berhasil dihapus.');
        } catch (Exception $e) {
            Database::rollback();
            $this->setFlash('error', 'Gagal menghapus user: ' . $e->getMessage());
        }

        $this->redirect('settings/users');
    }

    /**
     * Toggle a permission for a role
     */
    public function togglePermission()
    {
        $this->requirePermission('roles.edit');
        $this->requireCsrf();

        $roleId = (int)$this->post('role_id');
        $permissionId = (int)$this->post('permission_id');
        $assign = (int)$this->post('assign'); // 1 to assign, 0 to revoke

        if (empty($roleId) || empty($permissionId)) {
            $this->json(['success' => false, 'message' => 'Role ID dan Permission ID wajib diisi.'], 400);
        }

        // Verify role and permission exist
        $role = Database::fetchOne("SELECT id, name FROM roles WHERE id = ?", [$roleId]);
        $permission = Database::fetchOne("SELECT id, name FROM permissions WHERE id = ?", [$permissionId]);

        if (!$role || !$permission) {
            $this->json(['success' => false, 'message' => 'Role atau Permission tidak valid.'], 404);
        }

        try {
            if ($assign === 1) {
                // Check if already mapped
                $exists = Database::fetchOne("SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?", [$roleId, $permissionId]);
                if (!$exists) {
                    Database::insert('role_permissions', [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId
                    ]);
                    $this->logAudit('update', 'roles', 'role_permissions', null, "Memberikan izin {$permission['name']} kepada peran {$role['name']}");
                }
            } else {
                Database::delete('role_permissions', [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ]);
                $this->logAudit('update', 'roles', 'role_permissions', null, "Mencabut izin {$permission['name']} dari peran {$role['name']}");
            }

            $this->json(['success' => true, 'message' => 'Hak akses berhasil diperbarui.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Gagal memperbarui hak akses: ' . $e->getMessage()], 500);
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

        // Download action (Force redirect to secure verify page)
        if ($action === 'download' && !empty($file)) {
            if (file_exists($filePath) && is_file($filePath)) {
                $this->redirect('settings/backup/verify-download?file=' . urlencode($file));
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
     * Show verification page for downloading a database backup
     */
    public function verifyBackupDownload()
    {
        $this->requirePermission('settings.backup');

        $file = $this->get('file');
        if (empty($file)) {
            $this->setFlash('error', 'Berkas tidak ditentukan.');
            $this->redirect('settings/backup');
        }

        $file = basename($file);
        $backupDir = __DIR__ . '/../../../storage/backups';
        $filePath = $backupDir . '/' . $file;

        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->setFlash('error', 'Berkas cadangan tidak ditemukan.');
            $this->redirect('settings/backup');
        }

        $data = [
            'title' => 'Verifikasi Unduhan Backup - SIMRS',
            'file' => $file
        ];

        $this->view('settings/views/verify_download', $data);
    }

    /**
     * Verify password and download decrypted backup
     */
    public function downloadBackup()
    {
        $this->requirePermission('settings.backup');
        
        $this->requireCsrf();

        $file = $this->post('file');
        $password = $this->post('password');

        if (empty($file)) {
            $this->setFlash('error', 'Berkas tidak ditentukan.');
            $this->redirect('settings/backup');
        }

        $file = basename($file);
        $backupDir = __DIR__ . '/../../../storage/backups';
        $filePath = $backupDir . '/' . $file;

        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->setFlash('error', 'Berkas cadangan tidak ditemukan.');
            $this->redirect('settings/backup');
        }

        // Verify password
        $user = Database::fetchOne("SELECT password FROM users WHERE id = ?", [Session::getUserId()]);
        if (!$user || !Auth::verifyPassword($password, $user['password'])) {
            $this->setFlash('error', 'Kata sandi salah. Verifikasi gagal.');
            $this->redirect('settings/backup/verify-download?file=' . urlencode($file));
        }

        // Log audit
        $this->logAudit('download', 'settings', 'system_settings', null, 'Mengunduh berkas cadangan database: ' . $file);

        // Read file contents
        $content = file_get_contents($filePath);
        $decryptedContent = $content;

        // Try decrypting if it is encrypted
        $parts = explode('::', $content, 2);
        if (count($parts) === 2) {
            $iv = base64_decode($parts[0]);
            $ciphertext = $parts[1];
            $key = env('DB_BACKUP_KEY');
            if (empty($key)) {
                throw new Exception("Enkripsi gagal: DB_BACKUP_KEY belum diatur di file .env.");
            }
            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv);
            if ($decrypted !== false) {
                $decryptedContent = $decrypted;
            }
        }

        // Output download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($decryptedContent));
        
        echo $decryptedContent;
        exit;
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

            // Encrypt database backup
            $key = env('DB_BACKUP_KEY');
            if (empty($key)) {
                throw new Exception("Enkripsi gagal: DB_BACKUP_KEY belum diatur di file .env.");
            }
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encryptedSql = openssl_encrypt($sql, 'aes-256-cbc', $key, 0, $iv);
            $fileContent = base64_encode($iv) . '::' . $encryptedSql;

            $fileName = 'backup_simrs_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $backupDir . '/' . $fileName;

            file_put_contents($filePath, $fileContent);

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

        $startDate = $this->get('start_date', '');
        $endDate = $this->get('end_date', '');
        $username = $this->get('username', '');
        $action = $this->get('action', '');
        $page = (int)$this->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $limit = 50;
        $params = [];
        $where = ["1=1"];

        if (!empty($startDate)) {
            $where[] = "DATE(created_at) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(created_at) <= ?";
            $params[] = $endDate;
        }
        if (!empty($username)) {
            $where[] = "username LIKE ?";
            $params[] = "%$username%";
        }
        if (!empty($action)) {
            $where[] = "action = ?";
            $params[] = $action;
        }

        $whereClause = implode(" AND ", $where);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM audit_logs WHERE {$whereClause}";
        $countResult = Database::fetchOne($countQuery, $params);
        $totalRecords = (int)($countResult['total'] ?? 0);

        $totalPages = ceil($totalRecords / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $limit;

        // Fetch logs with limit and offset
        $query = "SELECT * FROM audit_logs WHERE {$whereClause} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
        $auditLogs = Database::fetchAll($query, $params);

        // Fetch distinct actions list for drop-down filter
        $actions = Database::fetchAll("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL ORDER BY action ASC");

        $data = [
            'title' => 'Audit Log - SIMRS',
            'auditLogs' => $auditLogs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'actions' => array_column($actions, 'action'),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'username' => $username,
                'action' => $action
            ]
        ];

        $this->view('settings/views/audit', $data);
    }
}
