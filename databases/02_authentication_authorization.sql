-- =====================================================
-- SIMRS Database Schema
-- AUTHENTICATION & AUTHORIZATION
-- =====================================================

-- Tabel: users
-- Menyimpan data pengguna sistem
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    email_verified_at DATETIME,
    last_login_at DATETIME,
    last_login_ip VARCHAR(45),
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    updated_by INT UNSIGNED,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: roles
-- Menyimpan daftar role/jabatan
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: permissions
-- Menyimpan daftar hak akses/permission
CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: role_permissions
-- Mapping role dengan permissions (many-to-many)
CREATE TABLE role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: user_roles
-- Mapping user dengan roles (many-to-many)
-- Satu user bisa punya banyak role
CREATE TABLE user_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT UNSIGNED,
    UNIQUE KEY unique_user_role (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Default Roles
-- =====================================================

INSERT INTO roles (name, display_name, description) VALUES
('admin', 'Administrator', 'Super admin dengan akses penuh ke sistem'),
('doctor', 'Dokter', 'Dokter yang menangani pasien'),
('nurse', 'Perawat', 'Perawat yang membantu dokter'),
('receptionist', 'Resepsionis', 'Petugas pendaftaran pasien'),
('lab_staff', 'Petugas Laboratorium', 'Petugas yang menangani pemeriksaan lab'),
('pharmacist', 'Apoteker', 'Petugas farmasi/apotek'),
('cashier', 'Kasir/Billing', 'Petugas pembayaran dan billing'),
('hr_staff', 'HR Staff', 'Petugas kepegawaian'),
('patient', 'Pasien', 'Pasien rumah sakit');

-- =====================================================
-- Seed Data: Default Permissions
-- =====================================================

INSERT INTO permissions (name, display_name, module) VALUES
-- Dashboard
('dashboard.view', 'Lihat Dashboard', 'dashboard'),

-- User Management
('users.view', 'Lihat Daftar User', 'users'),
('users.create', 'Tambah User Baru', 'users'),
('users.edit', 'Edit Data User', 'users'),
('users.delete', 'Hapus User', 'users'),

-- Role & Permission Management
('roles.view', 'Lihat Daftar Role', 'roles'),
('roles.create', 'Tambah Role Baru', 'roles'),
('roles.edit', 'Edit Role', 'roles'),
('roles.delete', 'Hapus Role', 'roles'),
('permissions.manage', 'Kelola Permission', 'permissions'),

-- Patient Management
('patients.view', 'Lihat Daftar Pasien', 'patients'),
('patients.create', 'Daftar Pasien Baru', 'patients'),
('patients.edit', 'Edit Data Pasien', 'patients'),
('patients.delete', 'Hapus Data Pasien', 'patients'),
('patients.view_detail', 'Lihat Detail Pasien', 'patients'),

-- Medical Records
('medical_records.view', 'Lihat Rekam Medis', 'medical_records'),
('medical_records.create', 'Buat Rekam Medis', 'medical_records'),
('medical_records.edit', 'Edit Rekam Medis', 'medical_records'),
('medical_records.delete', 'Hapus Rekam Medis', 'medical_records'),
('medical_records.view_all', 'Lihat Semua Rekam Medis', 'medical_records'),

-- Appointments
('appointments.view', 'Lihat Jadwal Appointment', 'appointments'),
('appointments.create', 'Buat Appointment', 'appointments'),
('appointments.edit', 'Edit Appointment', 'appointments'),
('appointments.cancel', 'Batalkan Appointment', 'appointments'),
('appointments.approve', 'Approve Appointment', 'appointments'),

-- Queue Management
('queues.view', 'Lihat Antrian', 'queues'),
('queues.manage', 'Kelola Antrian', 'queues'),
('queues.call', 'Panggil Antrian', 'queues'),

-- Laboratory
('lab.view_orders', 'Lihat Order Lab', 'laboratory'),
('lab.create_order', 'Buat Order Lab', 'laboratory'),
('lab.input_results', 'Input Hasil Lab', 'laboratory'),
('lab.view_results', 'Lihat Hasil Lab', 'laboratory'),
('lab.approve_results', 'Approve Hasil Lab', 'laboratory'),

-- Pharmacy
('pharmacy.view_prescriptions', 'Lihat Resep', 'pharmacy'),
('pharmacy.create_prescription', 'Buat Resep', 'pharmacy'),
('pharmacy.dispense', 'Serahkan Obat', 'pharmacy'),
('pharmacy.view_stock', 'Lihat Stok Obat', 'pharmacy'),
('pharmacy.manage_stock', 'Kelola Stok Obat', 'pharmacy'),

-- Billing & Payment
('billing.view_invoices', 'Lihat Invoice', 'billing'),
('billing.create_invoice', 'Buat Invoice', 'billing'),
('billing.process_payment', 'Proses Pembayaran', 'billing'),
('billing.void_payment', 'Void Pembayaran', 'billing'),

-- Inventory
('inventory.view', 'Lihat Inventory', 'inventory'),
('inventory.create', 'Tambah Item Inventory', 'inventory'),
('inventory.edit', 'Edit Item Inventory', 'inventory'),
('inventory.delete', 'Hapus Item Inventory', 'inventory'),
('inventory.stock_opname', 'Stock Opname', 'inventory'),

-- HR & Employee
('hr.view_employees', 'Lihat Data Pegawai', 'hr'),
('hr.create_employee', 'Tambah Pegawai', 'hr'),
('hr.edit_employee', 'Edit Data Pegawai', 'hr'),
('hr.delete_employee', 'Hapus Pegawai', 'hr'),
('hr.manage_attendance', 'Kelola Absensi', 'hr'),
('hr.manage_shifts', 'Kelola Shift', 'hr'),

-- Reports
('reports.view', 'Lihat Laporan', 'reports'),
('reports.export', 'Export Laporan', 'reports'),
('reports.financial', 'Laporan Keuangan', 'reports'),

-- System Settings
('settings.view', 'Lihat Pengaturan Sistem', 'settings'),
('settings.edit', 'Edit Pengaturan Sistem', 'settings'),
('settings.backup', 'Backup Database', 'settings'),
('settings.restore', 'Restore Database', 'settings'),

-- Audit Logs
('audit.view', 'Lihat Audit Log', 'audit');

-- =====================================================
-- Mapping: Admin Role - Full Access
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id
FROM permissions;

-- =====================================================
-- Mapping: Doctor Role
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'doctor'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'patients.view', 'patients.view_detail',
    'medical_records.view', 'medical_records.create', 'medical_records.edit',
    'appointments.view', 'appointments.edit',
    'queues.view', 'queues.call',
    'lab.view_orders', 'lab.create_order', 'lab.view_results',
    'pharmacy.view_prescriptions', 'pharmacy.create_prescription',
    'billing.view_invoices'
);

-- =====================================================
-- Mapping: Nurse Role
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'nurse'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'patients.view', 'patients.view_detail',
    'medical_records.view', 'medical_records.create',
    'appointments.view',
    'queues.view', 'queues.manage',
    'lab.view_orders', 'lab.view_results',
    'pharmacy.view_prescriptions'
);

-- =====================================================
-- Mapping: Receptionist Role
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'receptionist'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'patients.view', 'patients.create', 'patients.edit', 'patients.view_detail',
    'appointments.view', 'appointments.create', 'appointments.edit',
    'queues.view', 'queues.manage'
);

-- =====================================================
-- Mapping: Lab Staff Role
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'lab_staff'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'patients.view_detail',
    'lab.view_orders', 'lab.input_results', 'lab.view_results', 'lab.approve_results'
);

-- =====================================================
-- Mapping: Pharmacist Role
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'pharmacist'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'patients.view_detail',
    'pharmacy.view_prescriptions', 'pharmacy.dispense',
    'pharmacy.view_stock', 'pharmacy.manage_stock',
    'inventory.view', 'inventory.create', 'inventory.edit'
);

-- =====================================================
-- Mapping: Cashier Role
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'cashier'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'patients.view_detail',
    'billing.view_invoices', 'billing.create_invoice', 'billing.process_payment',
    'reports.view', 'reports.financial'
);

-- =====================================================
-- Mapping: HR Staff Role
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'hr_staff'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'hr.view_employees', 'hr.create_employee', 'hr.edit_employee',
    'hr.manage_attendance', 'hr.manage_shifts',
    'reports.view'
);

-- =====================================================
-- Mapping: Patient Role (limited access)
-- =====================================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'patient'),
    id
FROM permissions
WHERE name IN (
    'dashboard.view',
    'appointments.view', 'appointments.create',
    'medical_records.view',
    'lab.view_results',
    'billing.view_invoices'
);

-- =====================================================
-- Seed Data: Default Admin User
-- Password: Admin123! (hashed dengan password_hash)
-- =====================================================

INSERT INTO users (username, email, password, full_name, phone, is_active, email_verified_at)
VALUES (
    'admin',
    'admin@simrs.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin123!
    'Administrator',
    '081234567890',
    1,
    NOW()
);

-- Assign Admin role to admin user
INSERT INTO user_roles (user_id, role_id)
SELECT 
    (SELECT id FROM users WHERE username = 'admin'),
    (SELECT id FROM roles WHERE name = 'admin');

-- =====================================================
-- Seed Data: Sample Doctor User
-- Password: Dokter123! (hashed)
-- =====================================================

INSERT INTO users (username, email, password, full_name, phone, is_active, email_verified_at)
VALUES (
    'dokter',
    'dokter@simrs.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Dokter123!
    'Dr. John Doe, Sp.PD',
    '081234567891',
    1,
    NOW()
);

-- Assign Doctor role
INSERT INTO user_roles (user_id, role_id)
SELECT 
    (SELECT id FROM users WHERE username = 'dokter'),
    (SELECT id FROM roles WHERE name = 'doctor');

-- =====================================================
-- END OF AUTHENTICATION & AUTHORIZATION
-- =====================================================