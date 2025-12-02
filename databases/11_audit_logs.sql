-- =====================================================
-- SIMRS Database Schema
-- AUDIT LOGS & SECURITY
-- =====================================================

-- Tabel: audit_logs
-- Menyimpan log aktivitas user untuk audit trail
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    username VARCHAR(50),
    
    action VARCHAR(100) NOT NULL COMMENT 'create, read, update, delete, login, logout, dll',
    module VARCHAR(50) NOT NULL COMMENT 'patient, medical_record, billing, dll',
    
    table_name VARCHAR(100),
    record_id INT UNSIGNED COMMENT 'ID record yang diakses',
    
    description TEXT COMMENT 'Deskripsi aktivitas',
    
    -- Request Info
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_method VARCHAR(10) COMMENT 'GET, POST, PUT, DELETE',
    request_url TEXT,
    
    -- Data Changes (for update/delete)
    old_values TEXT COMMENT 'Data sebelum perubahan (JSON)',
    new_values TEXT COMMENT 'Data setelah perubahan (JSON)',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at),
    INDEX idx_record_id (record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: login_attempts
-- Menyimpan percobaan login untuk rate limiting & security
CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    email VARCHAR(100),
    ip_address VARCHAR(45) NOT NULL,
    
    attempt_result ENUM('success', 'failed', 'blocked') NOT NULL,
    failure_reason VARCHAR(200) COMMENT 'Wrong password, account locked, dll',
    
    user_agent TEXT,
    
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_attempt_result (attempt_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: system_settings
-- Menyimpan konfigurasi sistem
CREATE TABLE system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    
    category VARCHAR(50) COMMENT 'general, security, email, backup, dll',
    description TEXT,
    
    is_public TINYINT(1) DEFAULT 0 COMMENT 'Bisa diakses tanpa login atau tidak',
    
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: notifications
-- Menyimpan notifikasi untuk user
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    
    notification_type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    
    link VARCHAR(500) COMMENT 'Link ke halaman terkait',
    
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: backups
-- Menyimpan riwayat backup database
CREATE TABLE backups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_number VARCHAR(20) NOT NULL UNIQUE,
    
    backup_type ENUM('full', 'incremental', 'differential') DEFAULT 'full',
    backup_method ENUM('manual', 'scheduled', 'automatic') DEFAULT 'manual',
    
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED COMMENT 'Ukuran file dalam bytes',
    
    backup_start DATETIME NOT NULL,
    backup_end DATETIME,
    backup_duration INT COMMENT 'Durasi dalam detik',
    
    status ENUM('in_progress', 'completed', 'failed') DEFAULT 'in_progress',
    error_message TEXT,
    
    notes TEXT,
    
    created_by INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_backup_number (backup_number),
    INDEX idx_backup_start (backup_start),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: system_logs
-- Menyimpan log sistem (errors, warnings, dll)
CREATE TABLE system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    log_level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
    log_type VARCHAR(50) COMMENT 'database, application, security, dll',
    
    message TEXT NOT NULL,
    context TEXT COMMENT 'Informasi tambahan dalam format JSON',
    
    file_path VARCHAR(500),
    line_number INT,
    
    stack_trace TEXT,
    
    ip_address VARCHAR(45),
    user_id INT UNSIGNED,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_log_level (log_level),
    INDEX idx_log_type (log_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: System Settings
-- =====================================================

INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES
-- General Settings
('app_name', 'SIMRS - Sistem Informasi Manajemen Rumah Sakit', 'string', 'general', 'Nama aplikasi', 1),
('app_version', '1.0.0', 'string', 'general', 'Versi aplikasi', 1),
('timezone', 'Asia/Jakarta', 'string', 'general', 'Zona waktu', 0),
('date_format', 'd-m-Y', 'string', 'general', 'Format tanggal', 0),
('time_format', 'H:i', 'string', 'general', 'Format waktu', 0),
('currency', 'IDR', 'string', 'general', 'Mata uang', 0),

-- Security Settings
('session_lifetime', '7200', 'number', 'security', 'Durasi session dalam detik (2 jam)', 0),
('login_max_attempts', '5', 'number', 'security', 'Maksimal percobaan login', 0),
('login_lockout_duration', '900', 'number', 'security', 'Durasi lockout dalam detik (15 menit)', 0),
('password_min_length', '8', 'number', 'security', 'Panjang minimal password', 0),
('require_password_change', 'false', 'boolean', 'security', 'Wajib ganti password setelah login pertama', 0),
('password_expiry_days', '0', 'number', 'security', 'Password expired setelah X hari (0 = tidak expired)', 0),

-- Email Settings
('smtp_host', 'smtp.gmail.com', 'string', 'email', 'SMTP host', 0),
('smtp_port', '587', 'number', 'email', 'SMTP port', 0),
('smtp_username', '', 'string', 'email', 'SMTP username', 0),
('smtp_password', '', 'string', 'email', 'SMTP password', 0),
('mail_from_address', 'noreply@simrs.local', 'string', 'email', 'Email pengirim', 0),
('mail_from_name', 'SIMRS', 'string', 'email', 'Nama pengirim', 0),

-- Backup Settings
('backup_enabled', 'true', 'boolean', 'backup', 'Aktifkan backup otomatis', 0),
('backup_schedule', 'daily', 'string', 'backup', 'Jadwal backup: daily, weekly, monthly', 0),
('backup_time', '02:00', 'string', 'backup', 'Jam backup otomatis', 0),
('backup_retention_days', '30', 'number', 'backup', 'Berapa lama backup disimpan (hari)', 0),
('backup_path', '/var/backups/simrs', 'string', 'backup', 'Path penyimpanan backup', 0),

-- Business Settings
('hospital_name', 'Rumah Sakit Umum Sehat Sentosa', 'string', 'business', 'Nama rumah sakit', 1),
('hospital_phone', '021-12345678', 'string', 'business', 'Telepon rumah sakit', 1),
('hospital_email', 'info@rssehatsentosa.co.id', 'string', 'business', 'Email rumah sakit', 1),
('hospital_address', 'Jl. Kesehatan No. 123, Jakarta Pusat', 'string', 'business', 'Alamat rumah sakit', 1),

-- Patient Settings
('auto_generate_mrn', 'true', 'boolean', 'patient', 'Generate nomor RM otomatis', 0),
('mrn_prefix', 'RM', 'string', 'patient', 'Prefix nomor rekam medis', 0),
('mrn_format', 'RM-YYYY-NNNN', 'string', 'patient', 'Format nomor rekam medis', 0),

-- Appointment Settings
('appointment_duration', '15', 'number', 'appointment', 'Durasi default appointment (menit)', 0),
('max_appointment_per_slot', '1', 'number', 'appointment', 'Maksimal pasien per slot waktu', 0),
('allow_online_booking', 'true', 'boolean', 'appointment', 'Izinkan booking online', 0),

-- Billing Settings
('tax_percentage', '11', 'number', 'billing', 'Persentase pajak (%)', 0),
('invoice_due_days', '7', 'number', 'billing', 'Jatuh tempo invoice (hari)', 0);

-- =====================================================
-- Seed Data: Sample Audit Logs
-- =====================================================

INSERT INTO audit_logs (user_id, username, action, module, table_name, record_id, description, ip_address, request_method) VALUES
(
    (SELECT id FROM users WHERE username = 'admin'),
    'admin',
    'login',
    'auth',
    'users',
    (SELECT id FROM users WHERE username = 'admin'),
    'Login berhasil',
    '127.0.0.1',
    'POST'
),
(
    (SELECT id FROM users WHERE username = 'admin'),
    'admin',
    'create',
    'patient',
    'patients',
    1,
    'Membuat data pasien baru: Ahmad Susanto',
    '127.0.0.1',
    'POST'
),
(
    (SELECT id FROM users WHERE username = 'dokter'),
    'dokter',
    'login',
    'auth',
    'users',
    (SELECT id FROM users WHERE username = 'dokter'),
    'Login berhasil',
    '127.0.0.1',
    'POST'
),
(
    (SELECT id FROM users WHERE username = 'dokter'),
    'dokter',
    'create',
    'medical_record',
    'medical_records',
    1,
    'Membuat rekam medis untuk pasien RM-2024-0001',
    '127.0.0.1',
    'POST'
);

-- =====================================================
-- Seed Data: Sample Login Attempts
-- =====================================================

INSERT INTO login_attempts (username, ip_address, attempt_result, attempted_at) VALUES
('admin', '127.0.0.1', 'success', '2024-01-15 08:00:00'),
('dokter', '127.0.0.1', 'success', '2024-01-15 08:05:00'),
('wronguser', '192.168.1.100', 'failed', '2024-01-15 09:00:00'),
('admin', '192.168.1.150', 'failed', '2024-01-15 09:10:00');

-- =====================================================
-- View: Recent User Activities
-- =====================================================

CREATE OR REPLACE VIEW v_recent_activities AS
SELECT 
    al.id,
    al.user_id,
    al.username,
    al.action,
    al.module,
    al.table_name,
    al.record_id,
    al.description,
    al.ip_address,
    al.created_at,
    u.full_name AS user_full_name
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 100;

-- =====================================================
-- View: Failed Login Attempts (Last 24 Hours)
-- =====================================================

CREATE OR REPLACE VIEW v_failed_logins_24h AS
SELECT 
    username,
    ip_address,
    COUNT(*) AS attempt_count,
    MAX(attempted_at) AS last_attempt,
    failure_reason
FROM login_attempts
WHERE attempt_result = 'failed'
AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY username, ip_address, failure_reason
ORDER BY attempt_count DESC, last_attempt DESC;

-- =====================================================
-- View: System Statistics
-- =====================================================

CREATE OR REPLACE VIEW v_system_statistics AS
SELECT 
    (SELECT COUNT(*) FROM patients WHERE is_active = 1) AS total_patients,
    (SELECT COUNT(*) FROM patient_visits WHERE DATE(visit_date) = CURDATE()) AS today_visits,
    (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status IN ('scheduled', 'confirmed')) AS today_appointments,
    (SELECT COUNT(*) FROM employees WHERE employment_status = 'active') AS active_employees,
    (SELECT COUNT(*) FROM invoices WHERE payment_status = 'unpaid') AS unpaid_invoices,
    (SELECT SUM(outstanding_amount) FROM invoices WHERE payment_status = 'unpaid') AS total_outstanding,
    (SELECT COUNT(*) FROM lab_orders WHERE order_status IN ('pending', 'in_progress')) AS pending_lab_orders,
    (SELECT COUNT(*) FROM prescriptions WHERE status IN ('pending', 'verified')) AS pending_prescriptions,
    (SELECT COUNT(*) FROM queues WHERE queue_date = CURDATE() AND status = 'waiting') AS waiting_queue,
    (SELECT COUNT(*) FROM users WHERE is_active = 1) AS active_users;

-- =====================================================
-- Stored Procedure: Clean Old Audit Logs
-- =====================================================

DELIMITER $$

CREATE PROCEDURE sp_clean_old_audit_logs(IN days_to_keep INT)
BEGIN
    DECLARE rows_deleted INT;
    
    DELETE FROM audit_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    SET rows_deleted = ROW_COUNT();
    
    SELECT CONCAT('Deleted ', rows_deleted, ' old audit log records') AS result;
END$$

DELIMITER ;

-- =====================================================
-- Stored Procedure: Clean Old Login Attempts
-- =====================================================

DELIMITER $$

CREATE PROCEDURE sp_clean_old_login_attempts(IN days_to_keep INT)
BEGIN
    DECLARE rows_deleted INT;
    
    DELETE FROM login_attempts 
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    SET rows_deleted = ROW_COUNT();
    
    SELECT CONCAT('Deleted ', rows_deleted, ' old login attempt records') AS result;
END$$

DELIMITER ;

-- =====================================================
-- Stored Procedure: Get Dashboard Statistics
-- =====================================================

DELIMITER $$

CREATE PROCEDURE sp_get_dashboard_stats()
BEGIN
    -- Daily statistics
    SELECT 
        'Today' AS period,
        COUNT(DISTINCT pv.id) AS total_visits,
        COUNT(DISTINCT CASE WHEN pv.visit_type = 'outpatient' THEN pv.id END) AS outpatient_visits,
        COUNT(DISTINCT CASE WHEN pv.visit_type = 'inpatient' THEN pv.id END) AS inpatient_visits,
        COUNT(DISTINCT CASE WHEN pv.visit_type = 'emergency' THEN pv.id END) AS emergency_visits,
        COUNT(DISTINCT p.id) AS total_patients,
        COALESCE(SUM(i.total_amount), 0) AS total_revenue,
        COALESCE(SUM(i.paid_amount), 0) AS paid_revenue,
        COALESCE(SUM(i.outstanding_amount), 0) AS outstanding_revenue
    FROM patient_visits pv
    LEFT JOIN patients p ON pv.patient_id = p.id
    LEFT JOIN invoices i ON pv.id = i.visit_id
    WHERE DATE(pv.visit_date) = CURDATE();
    
    -- Queue status
    SELECT 
        status,
        COUNT(*) AS count
    FROM queues
    WHERE queue_date = CURDATE()
    GROUP BY status;
    
    -- Pending tasks
    SELECT 
        'Lab Orders' AS task_type,
        COUNT(*) AS pending_count
    FROM lab_orders
    WHERE order_status IN ('pending', 'in_progress')
    UNION ALL
    SELECT 
        'Prescriptions' AS task_type,
        COUNT(*) AS pending_count
    FROM prescriptions
    WHERE status IN ('pending', 'verified')
    UNION ALL
    SELECT 
        'Appointments' AS task_type,
        COUNT(*) AS pending_count
    FROM appointments
    WHERE appointment_date = CURDATE() 
    AND status IN ('scheduled', 'confirmed');
END$$

DELIMITER ;

-- =====================================================
-- Trigger: Log Patient Updates
-- =====================================================

DELIMITER $$

CREATE TRIGGER tr_patients_update_log
AFTER UPDATE ON patients
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (
        user_id,
        action,
        module,
        table_name,
        record_id,
        description,
        old_values,
        new_values
    ) VALUES (
        NEW.updated_by,
        'update',
        'patient',
        'patients',
        NEW.id,
        CONCAT('Update data pasien: ', NEW.full_name),
        JSON_OBJECT(
            'full_name', OLD.full_name,
            'phone', OLD.phone,
            'address', OLD.address
        ),
        JSON_OBJECT(
            'full_name', NEW.full_name,
            'phone', NEW.phone,
            'address', NEW.address
        )
    );
END$$

DELIMITER ;

-- =====================================================
-- Event: Auto Clean Old Logs (Run Daily at 3 AM)
-- =====================================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

DELIMITER $$

CREATE EVENT IF NOT EXISTS evt_clean_old_logs
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE, '03:00:00')
DO
BEGIN
    -- Clean audit logs older than 90 days
    CALL sp_clean_old_audit_logs(90);
    
    -- Clean login attempts older than 30 days
    CALL sp_clean_old_login_attempts(30);
    
    -- Clean old system logs
    DELETE FROM system_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$

DELIMITER ;

-- =====================================================
-- END OF AUDIT LOGS & SECURITY
-- =====================================================

-- =====================================================
-- SUMMARY: Database Schema Completion
-- =====================================================

/*
SIMRS Database Schema - COMPLETE

Total Tables Created: 60+ tables
Categories:
1. Authentication & Authorization (5 tables)
2. Master Data (6 tables)
3. Patient Management (8 tables)
4. Scheduling & Appointments (5 tables)
5. Laboratory (4 tables)
6. Pharmacy (5 tables)
7. Billing & Payment (5 tables)
8. Inventory & Purchasing (10 tables)
9. HR & Kepegawaian (8 tables)
10. Audit & Security (7 tables)

Views: 15+ views untuk reporting
Stored Procedures: 3 procedures
Triggers: 1 trigger
Events: 1 scheduled event

Next Steps:
1. Run all migration files in sequence (01 to 11)
2. Verify data integrity
3. Test all views and stored procedures
4. Configure backup schedule
5. Implement application layer (PHP)

For production:
- Review and adjust all sample data
- Configure proper backup strategy
- Set up monitoring and alerts
- Implement proper security measures
- Regular maintenance and optimization
*/