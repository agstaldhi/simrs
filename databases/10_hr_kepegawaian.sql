-- =====================================================
-- SIMRS Database Schema
-- HR & KEPEGAWAIAN
-- =====================================================

-- Tabel: employees
-- Menyimpan data pegawai rumah sakit
CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED UNIQUE COMMENT 'Link ke users table jika punya akses sistem',
    employee_number VARCHAR(20) NOT NULL UNIQUE,
    
    nik VARCHAR(16) UNIQUE COMMENT 'NIK KTP',
    full_name VARCHAR(150) NOT NULL,
    
    birth_place VARCHAR(100),
    birth_date DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    
    religion ENUM('islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya') DEFAULT 'islam',
    marital_status ENUM('single', 'married', 'divorced', 'widowed') DEFAULT 'single',
    blood_type ENUM('A', 'B', 'AB', 'O', 'unknown') DEFAULT 'unknown',
    
    -- Kontak
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    email VARCHAR(100),
    
    -- Emergency Contact
    emergency_contact_name VARCHAR(150),
    emergency_contact_relation VARCHAR(50),
    emergency_contact_phone VARCHAR(20),
    
    -- Employment
    department_id INT UNSIGNED,
    position VARCHAR(100) NOT NULL COMMENT 'Jabatan',
    employment_type ENUM('permanent', 'contract', 'temporary', 'intern') DEFAULT 'permanent',
    employment_status ENUM('active', 'inactive', 'resigned', 'terminated', 'retired') DEFAULT 'active',
    
    join_date DATE NOT NULL,
    resign_date DATE,
    
    -- Education
    last_education ENUM('sma', 'diploma', 'sarjana', 'magister', 'doktor', 'other') DEFAULT 'sarjana',
    major VARCHAR(150) COMMENT 'Jurusan',
    university VARCHAR(200),
    
    -- Certification/License (for medical staff)
    license_number VARCHAR(100),
    license_expiry_date DATE,
    
    -- Salary
    basic_salary DECIMAL(12,2) DEFAULT 0,
    allowances DECIMAL(12,2) DEFAULT 0 COMMENT 'Tunjangan',
    
    -- BPJS
    bpjs_kesehatan_number VARCHAR(50),
    bpjs_ketenagakerjaan_number VARCHAR(50),
    npwp VARCHAR(30),
    
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(150),
    
    photo VARCHAR(255),
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    
    INDEX idx_employee_number (employee_number),
    INDEX idx_nik (nik),
    INDEX idx_full_name (full_name),
    INDEX idx_department_id (department_id),
    INDEX idx_employment_status (employment_status),
    FULLTEXT idx_fulltext_search (full_name, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: shifts
-- Menyimpan data shift kerja
CREATE TABLE shifts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    
    shift_type ENUM('regular', 'night', 'weekend') DEFAULT 'regular',
    
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    
    break_duration INT DEFAULT 60 COMMENT 'Durasi istirahat dalam menit',
    
    working_hours DECIMAL(4,2) DEFAULT 8.0 COMMENT 'Jam kerja efektif',
    
    is_active TINYINT(1) DEFAULT 1,
    description TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_shift_type (shift_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: employee_shifts
-- Jadwal shift pegawai
CREATE TABLE employee_shifts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    shift_id INT UNSIGNED NOT NULL,
    
    shift_date DATE NOT NULL,
    
    status ENUM('scheduled', 'confirmed', 'completed', 'absent', 'cancelled') DEFAULT 'scheduled',
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    
    INDEX idx_employee_id (employee_id),
    INDEX idx_shift_id (shift_id),
    INDEX idx_shift_date (shift_date),
    INDEX idx_status (status),
    UNIQUE KEY unique_employee_shift_date (employee_id, shift_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: attendances
-- Menyimpan data kehadiran/absensi pegawai
CREATE TABLE attendances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    shift_id INT UNSIGNED,
    
    attendance_date DATE NOT NULL,
    
    check_in_time DATETIME,
    check_in_location VARCHAR(100),
    check_in_notes TEXT,
    
    check_out_time DATETIME,
    check_out_location VARCHAR(100),
    check_out_notes TEXT,
    
    status ENUM('present', 'late', 'absent', 'leave', 'sick', 'permission', 'holiday', 'business_trip') DEFAULT 'present',
    
    working_hours DECIMAL(4,2) DEFAULT 0 COMMENT 'Jam kerja aktual',
    overtime_hours DECIMAL(4,2) DEFAULT 0 COMMENT 'Jam lembur',
    
    notes TEXT,
    
    verified_by INT UNSIGNED,
    verified_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    
    INDEX idx_employee_id (employee_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_status (status),
    INDEX idx_check_in_time (check_in_time),
    UNIQUE KEY unique_employee_attendance_date (employee_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: leaves
-- Menyimpan data cuti pegawai
CREATE TABLE leaves (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    leave_number VARCHAR(20) NOT NULL UNIQUE,
    
    leave_type ENUM('annual', 'sick', 'maternity', 'paternity', 'unpaid', 'marriage', 'bereavement', 'other') NOT NULL,
    
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    
    reason TEXT NOT NULL,
    
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    
    attachment VARCHAR(255) COMMENT 'File pendukung (surat dokter, dll)',
    
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    rejection_reason TEXT,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    
    INDEX idx_employee_id (employee_id),
    INDEX idx_leave_number (leave_number),
    INDEX idx_leave_type (leave_type),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: overtimes
-- Menyimpan data lembur pegawai
CREATE TABLE overtimes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    overtime_number VARCHAR(20) NOT NULL UNIQUE,
    
    overtime_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    
    total_hours DECIMAL(4,2) NOT NULL,
    overtime_rate DECIMAL(12,2) DEFAULT 0 COMMENT 'Tarif per jam',
    total_amount DECIMAL(12,2) DEFAULT 0,
    
    reason TEXT NOT NULL,
    
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    
    INDEX idx_employee_id (employee_id),
    INDEX idx_overtime_number (overtime_number),
    INDEX idx_overtime_date (overtime_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: payrolls
-- Menyimpan data penggajian (simplified)
CREATE TABLE payrolls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_number VARCHAR(20) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    
    period_month INT NOT NULL COMMENT 'Bulan (1-12)',
    period_year INT NOT NULL COMMENT 'Tahun',
    
    basic_salary DECIMAL(12,2) DEFAULT 0,
    allowances DECIMAL(12,2) DEFAULT 0,
    overtime_pay DECIMAL(12,2) DEFAULT 0,
    bonuses DECIMAL(12,2) DEFAULT 0,
    
    gross_salary DECIMAL(12,2) DEFAULT 0 COMMENT 'Gaji kotor',
    
    -- Deductions
    tax DECIMAL(12,2) DEFAULT 0,
    bpjs_kesehatan DECIMAL(12,2) DEFAULT 0,
    bpjs_ketenagakerjaan DECIMAL(12,2) DEFAULT 0,
    other_deductions DECIMAL(12,2) DEFAULT 0,
    
    total_deductions DECIMAL(12,2) DEFAULT 0,
    net_salary DECIMAL(12,2) DEFAULT 0 COMMENT 'Gaji bersih (take home pay)',
    
    payment_date DATE,
    payment_method ENUM('cash', 'bank_transfer') DEFAULT 'bank_transfer',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    
    INDEX idx_payroll_number (payroll_number),
    INDEX idx_employee_id (employee_id),
    INDEX idx_period (period_year, period_month),
    INDEX idx_payment_status (payment_status),
    UNIQUE KEY unique_employee_period (employee_id, period_year, period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Sample Employees
-- =====================================================

-- Employee yang sudah ada user (doctors)
UPDATE employees SET 
    employee_number = 'EMP-' || LPAD(id, 4, '0'),
    nik = CONCAT('320101', LPAD(id, 10, '0')),
    position = 'Dokter Spesialis'
WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'dr.%');

-- Tambah employee non-medical staff
INSERT INTO employees (
    employee_number, nik, full_name, birth_place, birth_date, gender,
    address, city, phone, mobile, email,
    department_id, position, employment_type, employment_status, join_date,
    last_education, basic_salary
) VALUES
('EMP-1001', '3201011990010001', 'Nurul Hidayah', 'Jakarta', '1990-01-15', 'female',
 'Jl. Sudirman No. 100', 'Jakarta', '021-5551001', '081234561001', 'nurul@simrs.local',
 (SELECT id FROM departments WHERE code = 'ADMIN'), 'Kepala Administrasi', 'permanent', 'active', '2020-01-01',
 'sarjana', 7500000),

('EMP-1002', 'Rina Wijayanti', 'Jakarta', '1992-03-20', 'female',
 'Jl. Thamrin No. 50', 'Jakarta', '021-5551002', '081234561002', 'rina@simrs.local',
 (SELECT id FROM departments WHERE code = 'ADMIN'), 'Staff Administrasi', 'permanent', 'active', '2020-06-01',
 'diploma', 5000000),

('EMP-1003', '3201011993050003', 'Agus Prasetyo', 'Bandung', '1993-05-10', 'male',
 'Jl. Asia Afrika No. 25', 'Jakarta', '021-5551003', '081234561003', 'agus@simrs.local',
 (SELECT id FROM departments WHERE code = 'HR'), 'Staff HR', 'permanent', 'active', '2021-01-15',
 'sarjana', 6000000),

('EMP-1004', '3201011995080004', 'Siti Nurjanah', 'Surabaya', '1995-08-22', 'female',
 'Jl. Pahlawan No. 45', 'Jakarta', '021-5551004', '081234561004', 'siti.n@simrs.local',
 (SELECT id FROM departments WHERE code = 'POLI'), 'Perawat', 'permanent', 'active', '2021-03-01',
 'diploma', 5500000),

('EMP-1005', '3201011994070005', 'Budi Santoso', 'Semarang', '1994-07-15', 'male',
 'Jl. Pemuda No. 12', 'Jakarta', '021-5551005', '081234561005', 'budi.s@simrs.local',
 (SELECT id FROM departments WHERE code = 'LAB'), 'Analis Laboratorium', 'permanent', 'active', '2020-09-01',
 'sarjana', 6500000),

('EMP-1006', '3201011996020006', 'Dewi Lestari', 'Yogyakarta', '1996-02-28', 'female',
 'Jl. Malioboro No. 88', 'Jakarta', '021-5551006', '081234561006', 'dewi@simrs.local',
 (SELECT id FROM departments WHERE code = 'FARM'), 'Apoteker', 'permanent', 'active', '2021-05-01',
 'sarjana', 7000000),

('EMP-1007', '3201011997110007', 'Ahmad Fauzi', 'Medan', '1997-11-12', 'male',
 'Jl. Merdeka No. 33', 'Jakarta', '021-5551007', '081234561007', 'ahmad.f@simrs.local',
 (SELECT id FROM departments WHERE code = 'ADMIN'), 'Kasir', 'contract', 'active', '2022-01-01',
 'sma', 4500000),

('EMP-1008', '3201011998040008', 'Linda Sari', 'Palembang', '1998-04-18', 'female',
 'Jl. Ampera No. 77', 'Jakarta', '021-5551008', '081234561008', 'linda@simrs.local',
 (SELECT id FROM departments WHERE code = 'POLI'), 'Resepsionis', 'permanent', 'active', '2021-07-01',
 'diploma', 4800000);

-- =====================================================
-- Seed Data: Shifts
-- =====================================================

INSERT INTO shifts (code, name, shift_type, start_time, end_time, working_hours) VALUES
('SHIFT-PAGI', 'Shift Pagi', 'regular', '07:00:00', '15:00:00', 7.0),
('SHIFT-SIANG', 'Shift Siang', 'regular', '15:00:00', '23:00:00', 7.0),
('SHIFT-MALAM', 'Shift Malam', 'night', '23:00:00', '07:00:00', 7.0),
('SHIFT-NORMAL', 'Shift Normal (Non-Shift)', 'regular', '08:00:00', '16:00:00', 7.0);

-- =====================================================
-- Seed Data: Sample Attendances
-- =====================================================

-- Attendance hari ini untuk beberapa pegawai
INSERT INTO attendances (
    employee_id, shift_id, attendance_date,
    check_in_time, status, working_hours
) VALUES
(
    (SELECT id FROM employees WHERE employee_number = 'EMP-1001'),
    (SELECT id FROM shifts WHERE code = 'SHIFT-NORMAL'),
    CURDATE(),
    CONCAT(CURDATE(), ' 08:05:00'),
    'present',
    7.0
),
(
    (SELECT id FROM employees WHERE employee_number = 'EMP-1002'),
    (SELECT id FROM shifts WHERE code = 'SHIFT-NORMAL'),
    CURDATE(),
    CONCAT(CURDATE(), ' 08:15:00'),
    'late',
    7.0
),
(
    (SELECT id FROM employees WHERE employee_number = 'EMP-1004'),
    (SELECT id FROM shifts WHERE code = 'SHIFT-PAGI'),
    CURDATE(),
    CONCAT(CURDATE(), ' 07:00:00'),
    'present',
    7.0
),
(
    (SELECT id FROM employees WHERE employee_number = 'EMP-1005'),
    (SELECT id FROM shifts WHERE code = 'SHIFT-PAGI'),
    CURDATE(),
    CONCAT(CURDATE(), ' 07:05:00'),
    'present',
    7.0
);

-- =====================================================
-- Seed Data: Sample Leave Request
-- =====================================================

INSERT INTO leaves (
    employee_id, leave_number, leave_type,
    start_date, end_date, total_days, reason, status
) VALUES
(
    (SELECT id FROM employees WHERE employee_number = 'EMP-1002'),
    'LEAVE-2024-0001',
    'annual',
    '2024-02-01',
    '2024-02-03',
    3,
    'Liburan keluarga',
    'pending'
),
(
    (SELECT id FROM employees WHERE employee_number = 'EMP-1008'),
    'LEAVE-2024-0002',
    'sick',
    '2024-01-18',
    '2024-01-19',
    2,
    'Sakit demam',
    'approved'
);

-- =====================================================
-- View: Employee List
-- =====================================================

CREATE OR REPLACE VIEW v_employees_list AS
SELECT 
    e.id,
    e.employee_number,
    e.nik,
    e.full_name,
    e.gender,
    e.birth_date,
    TIMESTAMPDIFF(YEAR, e.birth_date, CURDATE()) AS age,
    e.position,
    d.name AS department_name,
    e.employment_type,
    e.employment_status,
    e.join_date,
    TIMESTAMPDIFF(YEAR, e.join_date, CURDATE()) AS years_of_service,
    e.mobile,
    e.email,
    u.username,
    u.is_active AS has_system_access
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN users u ON e.user_id = u.id
ORDER BY e.employment_status ASC, e.full_name ASC;

-- =====================================================
-- View: Today's Attendance
-- =====================================================

CREATE OR REPLACE VIEW v_attendance_today AS
SELECT 
    a.id,
    e.employee_number,
    e.full_name,
    e.position,
    d.name AS department_name,
    s.name AS shift_name,
    a.check_in_time,
    a.check_out_time,
    a.status,
    a.working_hours
FROM attendances a
JOIN employees e ON a.employee_id = e.id
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN shifts s ON a.shift_id = s.id
WHERE a.attendance_date = CURDATE()
ORDER BY a.check_in_time ASC;

-- =====================================================
-- View: Pending Leave Requests
-- =====================================================

CREATE OR REPLACE VIEW v_pending_leaves AS
SELECT 
    l.id,
    l.leave_number,
    e.employee_number,
    e.full_name,
    e.position,
    d.name AS department_name,
    l.leave_type,
    l.start_date,
    l.end_date,
    l.total_days,
    l.reason,
    l.requested_at
FROM leaves l
JOIN employees e ON l.employee_id = e.id
LEFT JOIN departments d ON e.department_id = d.id
WHERE l.status = 'pending'
ORDER BY l.requested_at ASC;

-- =====================================================
-- END OF HR & KEPEGAWAIAN
-- =====================================================