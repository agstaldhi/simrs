-- =====================================================
-- SIMRS Database Schema
-- MASTER DATA
-- =====================================================

-- Tabel: hospital_info
-- Menyimpan informasi rumah sakit
CREATE TABLE hospital_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    logo VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(255),
    fax VARCHAR(20),
    operating_hours TEXT,
    description TEXT,
    slogan VARCHAR(255),
    director_name VARCHAR(100),
    license_number VARCHAR(100),
    accreditation VARCHAR(50),
    established_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: departments
-- Menyimpan daftar departemen/unit di rumah sakit
CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    head_name VARCHAR(100),
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: rooms
-- Menyimpan daftar ruangan di rumah sakit
CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    type ENUM('outpatient', 'inpatient', 'icu', 'emergency', 'operating', 'laboratory', 'pharmacy', 'other') NOT NULL,
    floor VARCHAR(10),
    building VARCHAR(50),
    capacity INT DEFAULT 1,
    available_beds INT DEFAULT 1,
    facilities TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_type (type),
    INDEX idx_is_active (is_active),
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: polyclinics
-- Menyimpan daftar poli/klinik
CREATE TABLE polyclinics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    room_id INT UNSIGNED,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: doctors
-- Menyimpan data dokter
CREATE TABLE doctors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    employee_number VARCHAR(50) UNIQUE,
    specialization VARCHAR(100),
    license_number VARCHAR(100) UNIQUE,
    sip_number VARCHAR(100),
    education TEXT,
    experience_years INT,
    biography TEXT,
    consultation_fee DECIMAL(12,2) DEFAULT 0,
    photo VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_employee_number (employee_number),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: doctor_polyclinics
-- Mapping dokter dengan poli (many-to-many)
CREATE TABLE doctor_polyclinics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT UNSIGNED NOT NULL,
    polyclinic_id INT UNSIGNED NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (polyclinic_id) REFERENCES polyclinics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_polyclinic (doctor_id, polyclinic_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_polyclinic_id (polyclinic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Hospital Info
-- =====================================================

INSERT INTO hospital_info (
    name, 
    address, 
    city, 
    province, 
    postal_code, 
    phone, 
    email, 
    website,
    operating_hours,
    description,
    slogan,
    director_name,
    license_number,
    accreditation,
    established_date
) VALUES (
    'Rumah Sakit Umum Sehat Sentosa',
    'Jl. Kesehatan No. 123',
    'Jakarta Pusat',
    'DKI Jakarta',
    '10110',
    '021-12345678',
    'info@rssehatsentosa.co.id',
    'https://www.rssehatsentosa.co.id',
    'Senin - Jumat: 08:00 - 20:00\nSabtu: 08:00 - 14:00\nMinggu & Libur: Tutup\nIGD: 24 Jam',
    'Rumah Sakit Umum Sehat Sentosa adalah rumah sakit yang berkomitmen memberikan pelayanan kesehatan terbaik dengan didukung tenaga medis profesional dan fasilitas modern.',
    'Melayani dengan Hati, Menyembuhkan dengan Profesional',
    'dr. Ahmad Setiawan, Sp.PD, FINASIM',
    '1234/RS/2020',
    'Paripurna',
    '2020-01-15'
);

-- =====================================================
-- Seed Data: Departments
-- =====================================================

INSERT INTO departments (code, name, description, head_name, phone) VALUES
('IGD', 'Instalasi Gawat Darurat', 'Unit pelayanan gawat darurat 24 jam', 'dr. Emergency Specialist', '021-12345678 ext 101'),
('POLI', 'Poliklinik', 'Unit pelayanan rawat jalan', 'dr. Primary Care', '021-12345678 ext 102'),
('RANAP', 'Rawat Inap', 'Unit pelayanan rawat inap', 'dr. Inpatient Care', '021-12345678 ext 103'),
('ICU', 'Intensive Care Unit', 'Unit perawatan intensif', 'dr. Critical Care', '021-12345678 ext 104'),
('OK', 'Kamar Operasi', 'Unit pelayanan operasi', 'dr. Surgical Team', '021-12345678 ext 105'),
('LAB', 'Laboratorium', 'Unit pemeriksaan laboratorium', 'dr. Lab Specialist', '021-12345678 ext 106'),
('RAD', 'Radiologi', 'Unit pemeriksaan radiologi', 'dr. Radiologist', '021-12345678 ext 107'),
('FARM', 'Farmasi', 'Unit pelayanan farmasi/apotek', 'Apt. Pharmacy Head', '021-12345678 ext 108'),
('ADMIN', 'Administrasi', 'Unit administrasi dan keuangan', 'Finance Manager', '021-12345678 ext 109'),
('HR', 'Human Resources', 'Unit kepegawaian', 'HR Manager', '021-12345678 ext 110');

-- =====================================================
-- Seed Data: Rooms
-- =====================================================

INSERT INTO rooms (department_id, code, name, type, floor, building, capacity, available_beds, facilities) VALUES
-- IGD
((SELECT id FROM departments WHERE code = 'IGD'), 'IGD-01', 'Ruang IGD 1', 'emergency', '1', 'Gedung A', 10, 10, 'Bed emergency, Monitor vital sign, Defibrilator'),
((SELECT id FROM departments WHERE code = 'IGD'), 'IGD-02', 'Ruang IGD 2', 'emergency', '1', 'Gedung A', 10, 10, 'Bed emergency, Monitor vital sign, Ventilator'),

-- Poli
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-UM', 'Poli Umum', 'outpatient', '2', 'Gedung A', 1, 1, 'Ruang periksa, Tempat tidur periksa'),
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-PD', 'Poli Penyakit Dalam', 'outpatient', '2', 'Gedung A', 1, 1, 'Ruang periksa, Tempat tidur periksa, EKG'),
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-BED', 'Poli Bedah', 'outpatient', '2', 'Gedung A', 1, 1, 'Ruang periksa, Tempat tidur periksa'),
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-AK', 'Poli Anak', 'outpatient', '2', 'Gedung B', 1, 1, 'Ruang periksa, Mainan anak, Timbangan bayi'),
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-OBGYN', 'Poli Kandungan', 'outpatient', '2', 'Gedung B', 1, 1, 'Ruang periksa, USG, Tempat tidur ginekologi'),
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-MATA', 'Poli Mata', 'outpatient', '3', 'Gedung B', 1, 1, 'Ruang periksa, Alat cek mata'),
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-THT', 'Poli THT', 'outpatient', '3', 'Gedung B', 1, 1, 'Ruang periksa, Alat THT'),
((SELECT id FROM departments WHERE code = 'POLI'), 'POLI-GIGI', 'Poli Gigi', 'outpatient', '3', 'Gedung B', 1, 1, 'Dental unit, X-ray dental'),

-- Rawat Inap
((SELECT id FROM departments WHERE code = 'RANAP'), 'VIP-01', 'Ruang VIP 1', 'inpatient', '3', 'Gedung C', 1, 1, 'Bed elektrik, AC, TV, Kulkas, Kamar mandi dalam'),
((SELECT id FROM departments WHERE code = 'RANAP'), 'VIP-02', 'Ruang VIP 2', 'inpatient', '3', 'Gedung C', 1, 1, 'Bed elektrik, AC, TV, Kulkas, Kamar mandi dalam'),
((SELECT id FROM departments WHERE code = 'RANAP'), 'KLAS1-A', 'Kelas 1 A', 'inpatient', '4', 'Gedung C', 2, 2, 'Bed elektrik, AC, TV'),
((SELECT id FROM departments WHERE code = 'RANAP'), 'KLAS1-B', 'Kelas 1 B', 'inpatient', '4', 'Gedung C', 2, 2, 'Bed elektrik, AC, TV'),
((SELECT id FROM departments WHERE code = 'RANAP'), 'KLAS2-A', 'Kelas 2 A', 'inpatient', '5', 'Gedung C', 4, 4, 'Bed manual, Kipas angin'),
((SELECT id FROM departments WHERE code = 'RANAP'), 'KLAS3-A', 'Kelas 3 A', 'inpatient', '5', 'Gedung C', 6, 6, 'Bed manual'),

-- ICU
((SELECT id FROM departments WHERE code = 'ICU'), 'ICU-01', 'ICU Room 1', 'icu', '6', 'Gedung C', 5, 5, 'Monitor vital sign, Ventilator, Infus pump, Syringe pump'),
((SELECT id FROM departments WHERE code = 'ICU'), 'ICU-02', 'ICU Room 2', 'icu', '6', 'Gedung C', 5, 5, 'Monitor vital sign, Ventilator, Infus pump, Syringe pump'),

-- Kamar Operasi
((SELECT id FROM departments WHERE code = 'OK'), 'OK-01', 'Kamar Operasi 1', 'operating', '7', 'Gedung C', 1, 1, 'Meja operasi, Lampu operasi, Anestesi machine'),
((SELECT id FROM departments WHERE code = 'OK'), 'OK-02', 'Kamar Operasi 2', 'operating', '7', 'Gedung C', 1, 1, 'Meja operasi, Lampu operasi, Anestesi machine'),

-- Laboratorium
((SELECT id FROM departments WHERE code = 'LAB'), 'LAB-01', 'Lab Kimia Klinik', 'laboratory', '1', 'Gedung D', 1, 1, 'Analyzer otomatis, Centrifuge, Mikroskop'),
((SELECT id FROM departments WHERE code = 'LAB'), 'LAB-02', 'Lab Hematologi', 'laboratory', '1', 'Gedung D', 1, 1, 'Hematology analyzer, Mikroskop'),
((SELECT id FROM departments WHERE code = 'LAB'), 'LAB-03', 'Lab Mikrobiologi', 'laboratory', '1', 'Gedung D', 1, 1, 'Inkubator, Mikroskop, Culture media'),

-- Radiologi
((SELECT id FROM departments WHERE code = 'RAD'), 'RAD-01', 'Ruang Rontgen', 'other', '1', 'Gedung D', 1, 1, 'X-Ray machine'),
((SELECT id FROM departments WHERE code = 'RAD'), 'RAD-02', 'Ruang USG', 'other', '1', 'Gedung D', 1, 1, 'USG 4D'),
((SELECT id FROM departments WHERE code = 'RAD'), 'RAD-03', 'Ruang CT Scan', 'other', '1', 'Gedung D', 1, 1, 'CT Scan 64 slice'),

-- Farmasi
((SELECT id FROM departments WHERE code = 'FARM'), 'FARM-01', 'Apotek Rawat Jalan', 'pharmacy', '1', 'Gedung A', 1, 1, 'Etalase obat, Rak penyimpanan, Kulkas vaksin'),
((SELECT id FROM departments WHERE code = 'FARM'), 'FARM-02', 'Apotek Rawat Inap', 'pharmacy', '1', 'Gedung C', 1, 1, 'Etalase obat, Rak penyimpanan');

-- =====================================================
-- Seed Data: Polyclinics
-- =====================================================

INSERT INTO polyclinics (code, name, description, room_id) VALUES
('UMUM', 'Poli Umum', 'Poliklinik untuk pemeriksaan kesehatan umum', (SELECT id FROM rooms WHERE code = 'POLI-UM')),
('DALAM', 'Poli Penyakit Dalam', 'Poliklinik spesialis penyakit dalam', (SELECT id FROM rooms WHERE code = 'POLI-PD')),
('BEDAH', 'Poli Bedah', 'Poliklinik spesialis bedah umum', (SELECT id FROM rooms WHERE code = 'POLI-BED')),
('ANAK', 'Poli Anak', 'Poliklinik spesialis anak', (SELECT id FROM rooms WHERE code = 'POLI-AK')),
('OBGYN', 'Poli Kandungan', 'Poliklinik spesialis kebidanan dan kandungan', (SELECT id FROM rooms WHERE code = 'POLI-OBGYN')),
('MATA', 'Poli Mata', 'Poliklinik spesialis mata', (SELECT id FROM rooms WHERE code = 'POLI-MATA')),
('THT', 'Poli THT', 'Poliklinik spesialis telinga hidung tenggorokan', (SELECT id FROM rooms WHERE code = 'POLI-THT')),
('GIGI', 'Poli Gigi', 'Poliklinik spesialis gigi dan mulut', (SELECT id FROM rooms WHERE code = 'POLI-GIGI'));

-- =====================================================
-- Seed Data: Sample Doctors
-- =====================================================

-- Insert users untuk dokter
INSERT INTO users (username, email, password, full_name, phone, is_active, email_verified_at) VALUES
('dr.john', 'john.doe@simrs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. John Doe, Sp.PD', '081234567892', 1, NOW()),
('dr.jane', 'jane.smith@simrs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. Jane Smith, Sp.B', '081234567893', 1, NOW()),
('dr.lisa', 'lisa.anderson@simrs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. Lisa Anderson, Sp.A', '081234567894', 1, NOW()),
('dr.michael', 'michael.brown@simrs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. Michael Brown, Sp.OG', '081234567895', 1, NOW()),
('dr.sarah', 'sarah.wilson@simrs.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. Sarah Wilson, Sp.M', '081234567896', 1, NOW());

-- Assign doctor role
INSERT INTO user_roles (user_id, role_id)
SELECT id, (SELECT id FROM roles WHERE name = 'doctor')
FROM users 
WHERE username IN ('dr.john', 'dr.jane', 'dr.lisa', 'dr.michael', 'dr.sarah');

-- Insert data dokter
INSERT INTO doctors (user_id, employee_number, specialization, license_number, sip_number, education, experience_years, consultation_fee, is_active) VALUES
((SELECT id FROM users WHERE username = 'dr.john'), 'DOK-001', 'Penyakit Dalam', 'STR-PD-2020-001', 'SIP/001/2020', 'Sp.PD dari Universitas Indonesia', 10, 150000, 1),
((SELECT id FROM users WHERE username = 'dr.jane'), 'DOK-002', 'Bedah Umum', 'STR-BU-2020-002', 'SIP/002/2020', 'Sp.B dari Universitas Gadjah Mada', 8, 200000, 1),
((SELECT id FROM users WHERE username = 'dr.lisa'), 'DOK-003', 'Anak', 'STR-A-2020-003', 'SIP/003/2020', 'Sp.A dari Universitas Airlangga', 12, 150000, 1),
((SELECT id FROM users WHERE username = 'dr.michael'), 'DOK-004', 'Kandungan', 'STR-OG-2020-004', 'SIP/004/2020', 'Sp.OG dari Universitas Padjadjaran', 15, 175000, 1),
((SELECT id FROM users WHERE username = 'dr.sarah'), 'DOK-005', 'Mata', 'STR-M-2020-005', 'SIP/005/2020', 'Sp.M dari Universitas Hasanuddin', 7, 150000, 1);

-- Mapping dokter dengan poli
INSERT INTO doctor_polyclinics (doctor_id, polyclinic_id, is_primary) VALUES
((SELECT id FROM doctors WHERE employee_number = 'DOK-001'), (SELECT id FROM polyclinics WHERE code = 'DALAM'), 1),
((SELECT id FROM doctors WHERE employee_number = 'DOK-002'), (SELECT id FROM polyclinics WHERE code = 'BEDAH'), 1),
((SELECT id FROM doctors WHERE employee_number = 'DOK-003'), (SELECT id FROM polyclinics WHERE code = 'ANAK'), 1),
((SELECT id FROM doctors WHERE employee_number = 'DOK-004'), (SELECT id FROM polyclinics WHERE code = 'OBGYN'), 1),
((SELECT id FROM doctors WHERE employee_number = 'DOK-005'), (SELECT id FROM polyclinics WHERE code = 'MATA'), 1);

-- =====================================================
-- END OF MASTER DATA
-- =====================================================