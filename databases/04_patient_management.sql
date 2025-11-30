-- =====================================================
-- SIMRS Database Schema
-- PATIENT MANAGEMENT
-- =====================================================

-- Tabel: patients
-- Menyimpan data pasien
CREATE TABLE patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medical_record_number VARCHAR(20) NOT NULL UNIQUE COMMENT 'Nomor Rekam Medis',
    nik VARCHAR(16) UNIQUE COMMENT 'NIK KTP',
    title ENUM('Tn.', 'Ny.', 'An.', 'Nn.') DEFAULT 'Tn.',
    full_name VARCHAR(150) NOT NULL,
    birth_place VARCHAR(100),
    birth_date DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    blood_type ENUM('A', 'B', 'AB', 'O', 'unknown') DEFAULT 'unknown',
    rhesus ENUM('+', '-', 'unknown') DEFAULT 'unknown',
    religion ENUM('islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya') DEFAULT 'islam',
    education ENUM('tidak_sekolah', 'sd', 'smp', 'sma', 'diploma', 'sarjana', 'magister', 'doktor') DEFAULT 'sma',
    marital_status ENUM('belum_kawin', 'kawin', 'cerai_hidup', 'cerai_mati') DEFAULT 'belum_kawin',
    occupation VARCHAR(100),
    nationality VARCHAR(50) DEFAULT 'Indonesia',
    
    -- Alamat
    address TEXT,
    rt VARCHAR(5),
    rw VARCHAR(5),
    kelurahan VARCHAR(100),
    kecamatan VARCHAR(100),
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    
    -- Kontak
    phone VARCHAR(20),
    mobile VARCHAR(20),
    email VARCHAR(100),
    
    -- Keluarga/Penanggung Jawab
    emergency_contact_name VARCHAR(150),
    emergency_contact_relation VARCHAR(50),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_address TEXT,
    
    -- Asuransi
    insurance_type ENUM('bpjs', 'asuransi_swasta', 'umum') DEFAULT 'umum',
    insurance_number VARCHAR(50),
    insurance_name VARCHAR(100),
    
    -- Status
    is_active TINYINT(1) DEFAULT 1,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    photo VARCHAR(255),
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    updated_by INT UNSIGNED,
    
    INDEX idx_medical_record_number (medical_record_number),
    INDEX idx_nik (nik),
    INDEX idx_full_name (full_name),
    INDEX idx_birth_date (birth_date),
    INDEX idx_is_active (is_active),
    INDEX idx_phone (phone),
    INDEX idx_mobile (mobile),
    FULLTEXT idx_fulltext_search (full_name, address, phone, mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: patient_visits
-- Menyimpan data kunjungan pasien
CREATE TABLE patient_visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_number VARCHAR(20) NOT NULL UNIQUE COMMENT 'Nomor registrasi kunjungan',
    patient_id INT UNSIGNED NOT NULL,
    polyclinic_id INT UNSIGNED,
    doctor_id INT UNSIGNED,
    room_id INT UNSIGNED,
    
    visit_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    visit_type ENUM('outpatient', 'inpatient', 'emergency', 'referral') NOT NULL DEFAULT 'outpatient',
    visit_status ENUM('registered', 'waiting', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'registered',
    
    -- Untuk rawat inap
    admission_date DATETIME,
    discharge_date DATETIME,
    length_of_stay INT COMMENT 'Lama rawat dalam hari',
    
    -- Rujukan
    referral_from VARCHAR(200) COMMENT 'Rujukan dari RS/Klinik lain',
    referral_to VARCHAR(200) COMMENT 'Dirujuk ke RS/Klinik lain',
    referral_reason TEXT,
    
    -- Payment
    payment_method ENUM('cash', 'bpjs', 'insurance', 'credit_card', 'debit_card') DEFAULT 'cash',
    
    chief_complaint TEXT COMMENT 'Keluhan utama',
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    updated_by INT UNSIGNED,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (polyclinic_id) REFERENCES polyclinics(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    
    INDEX idx_visit_number (visit_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_date (visit_date),
    INDEX idx_visit_type (visit_type),
    INDEX idx_visit_status (visit_status),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_polyclinic_id (polyclinic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: medical_records
-- Menyimpan rekam medis pasien per kunjungan
CREATE TABLE medical_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    visit_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    
    -- Anamnesis
    subjective TEXT COMMENT 'Subjective - Keluhan pasien (SOAP)',
    objective TEXT COMMENT 'Objective - Pemeriksaan fisik (SOAP)',
    assessment TEXT COMMENT 'Assessment - Diagnosa (SOAP)',
    plan TEXT COMMENT 'Plan - Rencana tindakan (SOAP)',
    
    -- Physical Examination
    general_condition VARCHAR(100),
    consciousness_level ENUM('compos_mentis', 'apatis', 'somnolen', 'sopor', 'koma') DEFAULT 'compos_mentis',
    physical_exam_notes TEXT,
    
    -- Instructions
    doctor_instructions TEXT,
    follow_up_plan TEXT,
    follow_up_date DATE,
    
    -- Status
    record_status ENUM('draft', 'completed', 'reviewed', 'verified') DEFAULT 'draft',
    verified_by INT UNSIGNED,
    verified_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE RESTRICT,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: vital_signs
-- Menyimpan tanda-tanda vital pasien
CREATE TABLE vital_signs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    visit_id INT UNSIGNED NOT NULL,
    medical_record_id INT UNSIGNED,
    
    measured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    measured_by INT UNSIGNED COMMENT 'User ID perawat/petugas',
    
    -- Vital Signs
    blood_pressure_systolic INT COMMENT 'Tekanan darah sistolik (mmHg)',
    blood_pressure_diastolic INT COMMENT 'Tekanan darah diastolik (mmHg)',
    heart_rate INT COMMENT 'Denyut nadi (bpm)',
    respiratory_rate INT COMMENT 'Laju pernapasan (per menit)',
    temperature DECIMAL(4,1) COMMENT 'Suhu tubuh (Celsius)',
    oxygen_saturation INT COMMENT 'Saturasi oksigen SpO2 (%)',
    weight DECIMAL(5,2) COMMENT 'Berat badan (kg)',
    height DECIMAL(5,2) COMMENT 'Tinggi badan (cm)',
    bmi DECIMAL(5,2) COMMENT 'Body Mass Index',
    
    pain_scale TINYINT COMMENT 'Skala nyeri 0-10',
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_measured_at (measured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: diagnoses
-- Menyimpan diagnosa penyakit (ICD-10)
CREATE TABLE diagnoses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medical_record_id INT UNSIGNED NOT NULL,
    
    icd10_code VARCHAR(10) COMMENT 'Kode ICD-10',
    diagnosis_name VARCHAR(255) NOT NULL,
    diagnosis_type ENUM('primary', 'secondary', 'complication') DEFAULT 'primary',
    
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    
    INDEX idx_medical_record_id (medical_record_id),
    INDEX idx_icd10_code (icd10_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: allergies
-- Menyimpan data alergi pasien
CREATE TABLE allergies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    
    allergen VARCHAR(200) NOT NULL COMMENT 'Zat penyebab alergi',
    allergy_type ENUM('drug', 'food', 'environmental', 'other') NOT NULL,
    severity ENUM('mild', 'moderate', 'severe') DEFAULT 'mild',
    reaction TEXT COMMENT 'Reaksi yang timbul',
    
    noted_date DATE,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_allergy_type (allergy_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: attachments
-- Menyimpan file lampiran rekam medis (PDF, gambar, dll)
CREATE TABLE attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    visit_id INT UNSIGNED,
    medical_record_id INT UNSIGNED,
    
    file_name VARCHAR(255) NOT NULL,
    file_original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT UNSIGNED COMMENT 'Ukuran file dalam bytes',
    mime_type VARCHAR(100),
    
    title VARCHAR(200),
    description TEXT,
    category ENUM('lab_result', 'xray', 'ct_scan', 'mri', 'ecg', 'photo', 'document', 'other') DEFAULT 'document',
    
    uploaded_by INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE SET NULL,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE SET NULL,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Sample Patients
-- =====================================================

INSERT INTO patients (
    medical_record_number, nik, title, full_name, birth_place, birth_date, 
    gender, blood_type, rhesus, religion, education, marital_status, occupation,
    address, rt, rw, kelurahan, kecamatan, city, province, postal_code,
    phone, mobile, email,
    emergency_contact_name, emergency_contact_relation, emergency_contact_phone,
    insurance_type, insurance_number
) VALUES 
(
    'RM-2024-0001', '3201012345670001', 'Tn.', 'Ahmad Susanto', 'Jakarta', '1980-05-15',
    'male', 'O', '+', 'islam', 'sarjana', 'kawin', 'Pegawai Swasta',
    'Jl. Melati No. 10', '001', '005', 'Menteng', 'Menteng', 'Jakarta Pusat', 'DKI Jakarta', '10110',
    '021-87654321', '081234567801', 'ahmad.susanto@email.com',
    'Siti Aminah', 'Istri', '081234567802',
    'bpjs', '0001234567890'
),
(
    'RM-2024-0002', '3201012345670002', 'Ny.', 'Siti Rahmawati', 'Bandung', '1985-08-20',
    'female', 'A', '+', 'islam', 'sma', 'kawin', 'Ibu Rumah Tangga',
    'Jl. Mawar No. 25', '002', '003', 'Cikini', 'Menteng', 'Jakarta Pusat', 'DKI Jakarta', '10110',
    '021-87654322', '081234567803', 'siti.rahmawati@email.com',
    'Bambang Susanto', 'Suami', '081234567804',
    'umum', NULL
),
(
    'RM-2024-0003', '3201012345670003', 'An.', 'Budi Santoso', 'Jakarta', '2015-03-10',
    'male', 'B', '+', 'islam', 'sd', 'belum_kawin', 'Pelajar',
    'Jl. Anggrek No. 5', '003', '007', 'Gondangdia', 'Menteng', 'Jakarta Pusat', 'DKI Jakarta', '10110',
    '021-87654323', '081234567805', NULL,
    'Dewi Anggraini', 'Ibu', '081234567806',
    'bpjs', '0001234567891'
),
(
    'RM-2024-0004', '3201012345670004', 'Nn.', 'Putri Maharani', 'Surabaya', '1992-11-25',
    'female', 'AB', '+', 'kristen', 'sarjana', 'belum_kawin', 'Marketing',
    'Jl. Dahlia No. 15', '005', '002', 'Kebon Sirih', 'Menteng', 'Jakarta Pusat', 'DKI Jakarta', '10110',
    '021-87654324', '081234567807', 'putri.maharani@email.com',
    'Maharani Indah', 'Ibu', '081234567808',
    'asuransi_swasta', 'ASW-123456789'
),
(
    'RM-2024-0005', '3201012345670005', 'Tn.', 'Joko Widodo', 'Semarang', '1975-06-30',
    'male', 'O', '+', 'islam', 'diploma', 'kawin', 'Wiraswasta',
    'Jl. Kenanga No. 20', '004', '006', 'Cikini', 'Menteng', 'Jakarta Pusat', 'DKI Jakarta', '10110',
    '021-87654325', '081234567809', 'joko.widodo@email.com',
    'Sri Mulyani', 'Istri', '081234567810',
    'bpjs', '0001234567892'
);

-- =====================================================
-- Seed Data: Sample Patient Visits
-- =====================================================

INSERT INTO patient_visits (
    visit_number, patient_id, polyclinic_id, doctor_id, room_id,
    visit_date, visit_type, visit_status, payment_method, chief_complaint
) VALUES
(
    'REG-2024-0001',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM rooms WHERE code = 'POLI-PD'),
    '2024-01-15 09:00:00',
    'outpatient',
    'completed',
    'bpjs',
    'Demam dan batuk selama 3 hari'
),
(
    'REG-2024-0002',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    (SELECT id FROM polyclinics WHERE code = 'OBGYN'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-004'),
    (SELECT id FROM rooms WHERE code = 'POLI-OBGYN'),
    '2024-01-15 10:00:00',
    'outpatient',
    'completed',
    'cash',
    'Kontrol kehamilan rutin'
),
(
    'REG-2024-0003',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    (SELECT id FROM rooms WHERE code = 'POLI-AK'),
    '2024-01-16 08:30:00',
    'outpatient',
    'completed',
    'bpjs',
    'Demam tinggi dan muntah'
),
(
    'REG-2024-0004',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0004'),
    (SELECT id FROM polyclinics WHERE code = 'MATA'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-005'),
    (SELECT id FROM rooms WHERE code = 'POLI-MATA'),
    '2024-01-16 11:00:00',
    'outpatient',
    'ongoing',
    'insurance',
    'Mata merah dan berair'
),
(
    'REG-2024-0005',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0005'),
    (SELECT id FROM polyclinics WHERE code = 'UMUM'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM rooms WHERE code = 'POLI-UM'),
    '2024-01-17 09:30:00',
    'outpatient',
    'waiting',
    'bpjs',
    'Kontrol tekanan darah tinggi'
);

-- =====================================================
-- Seed Data: Sample Medical Records
-- =====================================================

INSERT INTO medical_records (
    patient_id, visit_id, doctor_id,
    subjective, objective, assessment, plan,
    general_condition, consciousness_level, record_status
) VALUES
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0001'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    'Pasien mengeluh demam 3 hari, batuk berdahak, badan terasa lemas. Tidak ada sesak napas.',
    'TD: 120/80 mmHg, Nadi: 88x/menit, RR: 20x/menit, Suhu: 38.5°C. Pemeriksaan paru: ronki (+)',
    'ISPA (Infeksi Saluran Pernapasan Atas)',
    'Terapi: Amoxicillin 500mg 3x1, Paracetamol 500mg 3x1, Obat batuk. Kontrol 3 hari lagi jika tidak membaik.',
    'Sakit sedang',
    'compos_mentis',
    'completed'
),
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0002'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-004'),
    'Pasien hamil 20 minggu, kontrol rutin. Tidak ada keluhan.',
    'TD: 110/70 mmHg, Nadi: 80x/menit, BB: 58kg (naik 3kg dari bulan lalu). TFU: 18cm. DJJ: 142x/menit.',
    'Gravida 2 Para 1 Abortus 0, hamil 20 minggu, keadaan baik',
    'Lanjutkan suplemen Fe dan Kalsium. Kontrol 1 bulan lagi. USG dijadwalkan minggu depan.',
    'Baik',
    'compos_mentis',
    'completed'
),
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0003'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    'Anak demam tinggi 39°C sejak 2 hari lalu, muntah 3x. Nafsu makan menurun.',
    'TD: 90/60 mmHg, Nadi: 110x/menit, RR: 24x/menit, Suhu: 39.2°C, BB: 18kg. Turgor kulit baik, tidak ada tanda dehidrasi.',
    'Suspect Demam Tifoid. Perlu pemeriksaan Widal test.',
    'Rawat jalan. Terapi Paracetamol drop 1ml 3-4x/hari. Order lab Widal. Jika demam tidak turun dalam 2 hari, rawat inap.',
    'Sakit sedang',
    'compos_mentis',
    'completed'
);

-- =====================================================
-- Seed Data: Sample Vital Signs
-- =====================================================

INSERT INTO vital_signs (
    patient_id, visit_id, medical_record_id, measured_at,
    blood_pressure_systolic, blood_pressure_diastolic, heart_rate, respiratory_rate,
    temperature, oxygen_saturation, weight, height, bmi
) VALUES
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0001'),
    (SELECT id FROM medical_records WHERE visit_id = (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0001')),
    '2024-01-15 09:05:00',
    120, 80, 88, 20, 38.5, 98, 70.5, 170, 24.4
),
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0002'),
    (SELECT id FROM medical_records WHERE visit_id = (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0002')),
    '2024-01-15 10:05:00',
    110, 70, 80, 18, 36.8, 99, 58.0, 160, 22.7
),
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0003'),
    (SELECT id FROM medical_records WHERE visit_id = (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0003')),
    '2024-01-16 08:35:00',
    90, 60, 110, 24, 39.2, 98, 18.0, 115, 13.6
);

-- =====================================================
-- Seed Data: Sample Diagnoses
-- =====================================================

INSERT INTO diagnoses (medical_record_id, icd10_code, diagnosis_name, diagnosis_type) VALUES
(
    (SELECT id FROM medical_records WHERE visit_id = (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0001')),
    'J06.9',
    'Infeksi Saluran Pernapasan Akut Atas (ISPA)',
    'primary'
),
(
    (SELECT id FROM medical_records WHERE visit_id = (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0002')),
    'Z34.8',
    'Pemeriksaan kehamilan normal',
    'primary'
),
(
    (SELECT id FROM medical_records WHERE visit_id = (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0003')),
    'A01.0',
    'Suspek Demam Tifoid',
    'primary'
);

-- =====================================================
-- Seed Data: Sample Allergies
-- =====================================================

INSERT INTO allergies (patient_id, allergen, allergy_type, severity, reaction, noted_date) VALUES
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    'Penisilin',
    'drug',
    'severe',
    'Ruam merah di seluruh tubuh, gatal, bengkak',
    '2020-05-10'
),
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    'Seafood',
    'food',
    'moderate',
    'Gatal-gatal dan mual',
    '2022-08-15'
),
(
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0004'),
    'Debu',
    'environmental',
    'mild',
    'Bersin-bersin dan mata gatal',
    '2023-01-20'
);

-- =====================================================
-- END OF PATIENT MANAGEMENT
-- =====================================================