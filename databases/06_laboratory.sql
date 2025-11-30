-- =====================================================
-- SIMRS Database Schema
-- LABORATORY
-- =====================================================

-- Tabel: lab_templates
-- Menyimpan template/jenis pemeriksaan laboratorium
CREATE TABLE lab_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    category ENUM('hematology', 'clinical_chemistry', 'immunology', 'microbiology', 'urinalysis', 'other') NOT NULL,
    
    description TEXT,
    sample_type VARCHAR(100) COMMENT 'Jenis sampel: darah, urin, feses, dll',
    unit VARCHAR(50) COMMENT 'Satuan hasil',
    reference_range VARCHAR(200) COMMENT 'Nilai normal',
    
    price DECIMAL(12,2) DEFAULT 0,
    duration_minutes INT DEFAULT 60 COMMENT 'Estimasi waktu pemeriksaan',
    
    requires_fasting TINYINT(1) DEFAULT 0 COMMENT 'Perlu puasa atau tidak',
    special_instructions TEXT,
    
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    INDEX idx_code (code),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: lab_orders
-- Menyimpan order/permintaan pemeriksaan lab
CREATE TABLE lab_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT UNSIGNED NOT NULL,
    visit_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    order_status ENUM('pending', 'sample_collected', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    
    priority ENUM('routine', 'urgent', 'stat') DEFAULT 'routine',
    clinical_info TEXT COMMENT 'Informasi klinis dari dokter',
    
    -- Sample collection
    sample_collected_at DATETIME,
    sample_collected_by INT UNSIGNED,
    sample_condition VARCHAR(100) COMMENT 'Kondisi sampel: baik, hemolisis, dll',
    
    -- Completion
    completed_at DATETIME,
    completed_by INT UNSIGNED,
    
    -- Approval/Verification
    verified_by INT UNSIGNED,
    verified_at DATETIME,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE RESTRICT,
    
    INDEX idx_order_number (order_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_order_date (order_date),
    INDEX idx_order_status (order_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: lab_order_items
-- Detail item pemeriksaan dalam order
CREATE TABLE lab_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lab_order_id INT UNSIGNED NOT NULL,
    lab_template_id INT UNSIGNED NOT NULL,
    
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    
    price DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lab_order_id) REFERENCES lab_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_template_id) REFERENCES lab_templates(id) ON DELETE RESTRICT,
    
    INDEX idx_lab_order_id (lab_order_id),
    INDEX idx_lab_template_id (lab_template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: lab_results
-- Menyimpan hasil pemeriksaan laboratorium
CREATE TABLE lab_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lab_order_item_id INT UNSIGNED NOT NULL,
    lab_order_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    
    result_value VARCHAR(500) COMMENT 'Nilai hasil',
    result_unit VARCHAR(50) COMMENT 'Satuan',
    reference_range VARCHAR(200) COMMENT 'Nilai normal',
    
    result_flag ENUM('normal', 'low', 'high', 'critical') DEFAULT 'normal',
    result_interpretation TEXT COMMENT 'Interpretasi hasil',
    
    result_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    performed_by INT UNSIGNED COMMENT 'Analis yang melakukan',
    
    -- Validation
    validated_by INT UNSIGNED,
    validated_at DATETIME,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lab_order_item_id) REFERENCES lab_order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_order_id) REFERENCES lab_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_lab_order_item_id (lab_order_item_id),
    INDEX idx_lab_order_id (lab_order_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_result_date (result_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Lab Templates (Common Tests)
-- =====================================================

INSERT INTO lab_templates (code, name, category, sample_type, unit, reference_range, price, requires_fasting) VALUES
-- Hematology
('HB', 'Hemoglobin', 'hematology', 'Darah EDTA', 'g/dL', 'L: 13-17, P: 12-15', 25000, 0),
('LEUKO', 'Leukosit', 'hematology', 'Darah EDTA', '/µL', '4000-10000', 25000, 0),
('TROMBO', 'Trombosit', 'hematology', 'Darah EDTA', '/µL', '150000-400000', 25000, 0),
('HEMA', 'Hematokrit', 'hematology', 'Darah EDTA', '%', 'L: 40-50, P: 35-45', 25000, 0),
('LED', 'LED (Laju Endap Darah)', 'hematology', 'Darah EDTA', 'mm/jam', 'L: 0-15, P: 0-20', 30000, 0),
('DL', 'Darah Lengkap', 'hematology', 'Darah EDTA', '-', 'Lihat komponen', 75000, 0),

-- Clinical Chemistry
('GDS', 'Gula Darah Sewaktu', 'clinical_chemistry', 'Darah vena', 'mg/dL', '<200', 35000, 0),
('GDP', 'Gula Darah Puasa', 'clinical_chemistry', 'Darah vena', 'mg/dL', '70-110', 35000, 1),
('GD2PP', 'Gula Darah 2 Jam PP', 'clinical_chemistry', 'Darah vena', 'mg/dL', '<140', 35000, 0),
('HBA1C', 'HbA1c', 'clinical_chemistry', 'Darah EDTA', '%', '<5.7', 150000, 0),
('CHOL', 'Kolesterol Total', 'clinical_chemistry', 'Darah vena', 'mg/dL', '<200', 45000, 1),
('TRIG', 'Trigliserida', 'clinical_chemistry', 'Darah vena', 'mg/dL', '<150', 45000, 1),
('HDL', 'HDL Kolesterol', 'clinical_chemistry', 'Darah vena', 'mg/dL', '>40', 50000, 1),
('LDL', 'LDL Kolesterol', 'clinical_chemistry', 'Darah vena', 'mg/dL', '<130', 50000, 1),
('UREUM', 'Ureum', 'clinical_chemistry', 'Darah vena', 'mg/dL', '10-50', 40000, 0),
('CREAT', 'Kreatinin', 'clinical_chemistry', 'Darah vena', 'mg/dL', 'L: 0.7-1.3, P: 0.6-1.1', 40000, 0),
('URIC', 'Asam Urat', 'clinical_chemistry', 'Darah vena', 'mg/dL', 'L: 3.5-7, P: 2.6-6', 40000, 0),
('SGOT', 'SGOT/AST', 'clinical_chemistry', 'Darah vena', 'U/L', '<40', 45000, 0),
('SGPT', 'SGPT/ALT', 'clinical_chemistry', 'Darah vena', 'U/L', '<41', 45000, 0),

-- Immunology
('WIDAL', 'Widal Test', 'immunology', 'Darah vena', '-', 'Negatif', 50000, 0),
('DENGUE', 'Dengue NS1 Antigen', 'immunology', 'Darah vena', '-', 'Negatif', 150000, 0),
('DENGUE-IGG', 'Dengue IgG/IgM', 'immunology', 'Darah vena', '-', 'Negatif', 150000, 0),
('HBSAG', 'HBsAg', 'immunology', 'Darah vena', '-', 'Non-reaktif', 75000, 0),
('ANTIHBS', 'Anti-HBs', 'immunology', 'Darah vena', 'mIU/mL', '>10', 100000, 0),
('HIV', 'HIV Rapid Test', 'immunology', 'Darah vena', '-', 'Non-reaktif', 75000, 0),

-- Urinalysis
('URINE', 'Urinalisis Lengkap', 'urinalysis', 'Urin sewaktu', '-', 'Lihat komponen', 30000, 0),
('URINE-PH', 'pH Urin', 'urinalysis', 'Urin sewaktu', '-', '4.5-8.0', 15000, 0),
('URINE-PRO', 'Protein Urin', 'urinalysis', 'Urin sewaktu', '-', 'Negatif', 20000, 0),

-- Microbiology
('FESES', 'Feses Rutin', 'microbiology', 'Feses', '-', 'Normal', 35000, 0),
('CULTURE-URINE', 'Kultur Urin', 'microbiology', 'Urin steril', '-', 'Steril', 150000, 0),
('CULTURE-BLOOD', 'Kultur Darah', 'microbiology', 'Darah vena', '-', 'Steril', 200000, 0);

-- =====================================================
-- Seed Data: Sample Lab Orders
-- =====================================================

INSERT INTO lab_orders (
    order_number, patient_id, visit_id, doctor_id,
    order_date, order_status, priority, clinical_info
) VALUES
(
    'LAB-2024-0001',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0001'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    '2024-01-15 09:30:00',
    'completed',
    'routine',
    'Pasien dengan keluhan demam dan batuk, suspek infeksi'
),
(
    'LAB-2024-0002',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0003'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    '2024-01-16 09:00:00',
    'completed',
    'urgent',
    'Anak demam tinggi, suspek typhoid'
),
(
    'LAB-2024-0003',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0005'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0005'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    '2024-01-17 10:00:00',
    'in_progress',
    'routine',
    'Pasien hipertensi, kontrol rutin'
);

-- =====================================================
-- Seed Data: Lab Order Items
-- =====================================================

-- Order 1: Darah Lengkap
INSERT INTO lab_order_items (lab_order_id, lab_template_id, status, price) VALUES
(
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0001'),
    (SELECT id FROM lab_templates WHERE code = 'DL'),
    'completed',
    75000
);

-- Order 2: Widal Test
INSERT INTO lab_order_items (lab_order_id, lab_template_id, status, price) VALUES
(
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0002'),
    (SELECT id FROM lab_templates WHERE code = 'WIDAL'),
    'completed',
    50000
),
(
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0002'),
    (SELECT id FROM lab_templates WHERE code = 'DL'),
    'completed',
    75000
);

-- Order 3: Cek Gula Darah, Kolesterol, dan Asam Urat
INSERT INTO lab_order_items (lab_order_id, lab_template_id, status, price) VALUES
(
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0003'),
    (SELECT id FROM lab_templates WHERE code = 'GDS'),
    'in_progress',
    35000
),
(
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0003'),
    (SELECT id FROM lab_templates WHERE code = 'CHOL'),
    'in_progress',
    45000
),
(
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0003'),
    (SELECT id FROM lab_templates WHERE code = 'URIC'),
    'in_progress',
    40000
);

-- =====================================================
-- Seed Data: Lab Results
-- =====================================================

-- Hasil Darah Lengkap - Order 1
INSERT INTO lab_results (
    lab_order_item_id, lab_order_id, patient_id,
    result_value, result_unit, reference_range, result_flag, result_date
) VALUES
-- Hemoglobin normal
(
    (SELECT loi.id FROM lab_order_items loi 
     JOIN lab_orders lo ON loi.lab_order_id = lo.id 
     WHERE lo.order_number = 'LAB-2024-0001' LIMIT 1),
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0001'),
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    '14.5',
    'g/dL',
    'L: 13-17, P: 12-15',
    'normal',
    '2024-01-15 14:30:00'
);

-- Hasil Widal Test - Order 2
INSERT INTO lab_results (
    lab_order_item_id, lab_order_id, patient_id,
    result_value, result_unit, reference_range, result_flag, result_interpretation, result_date
) VALUES
(
    (SELECT loi.id FROM lab_order_items loi 
     JOIN lab_orders lo ON loi.lab_order_id = lo.id 
     JOIN lab_templates lt ON loi.lab_template_id = lt.id
     WHERE lo.order_number = 'LAB-2024-0002' AND lt.code = 'WIDAL'),
    (SELECT id FROM lab_orders WHERE order_number = 'LAB-2024-0002'),
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    'Salmonella typhi O: 1/160, H: 1/320',
    '-',
    'Negatif',
    'high',
    'Hasil menunjukkan kemungkinan infeksi Typhoid. Disarankan pemeriksaan lanjutan.',
    '2024-01-16 15:00:00'
);

-- Update status order yang sudah selesai
UPDATE lab_orders 
SET order_status = 'completed', 
    completed_at = '2024-01-15 14:30:00'
WHERE order_number = 'LAB-2024-0001';

UPDATE lab_orders 
SET order_status = 'completed',
    completed_at = '2024-01-16 15:00:00'
WHERE order_number = 'LAB-2024-0002';

-- =====================================================
-- View: Lab Orders with Patient Details
-- =====================================================

CREATE OR REPLACE VIEW v_lab_orders AS
SELECT 
    lo.id,
    lo.order_number,
    lo.order_date,
    lo.order_status,
    lo.priority,
    p.medical_record_number,
    p.full_name AS patient_name,
    p.birth_date,
    TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age,
    p.gender,
    d.employee_number AS doctor_employee_number,
    u.full_name AS doctor_name,
    pv.visit_number,
    lo.clinical_info,
    lo.sample_collected_at,
    lo.completed_at,
    lo.created_at
FROM lab_orders lo
JOIN patients p ON lo.patient_id = p.id
JOIN patient_visits pv ON lo.visit_id = pv.id
JOIN doctors d ON lo.doctor_id = d.id
JOIN users u ON d.user_id = u.id
ORDER BY lo.order_date DESC;

-- =====================================================
-- View: Lab Results Summary
-- =====================================================

CREATE OR REPLACE VIEW v_lab_results_summary AS
SELECT 
    lr.id,
    lo.order_number,
    p.medical_record_number,
    p.full_name AS patient_name,
    lt.code AS test_code,
    lt.name AS test_name,
    lt.category,
    lr.result_value,
    lr.result_unit,
    lr.reference_range,
    lr.result_flag,
    lr.result_interpretation,
    lr.result_date,
    u.full_name AS doctor_name
FROM lab_results lr
JOIN lab_order_items loi ON lr.lab_order_item_id = loi.id
JOIN lab_templates lt ON loi.lab_template_id = lt.id
JOIN lab_orders lo ON lr.lab_order_id = lo.id
JOIN patients p ON lr.patient_id = p.id
JOIN doctors d ON lo.doctor_id = d.id
JOIN users u ON d.user_id = u.id
ORDER BY lr.result_date DESC;

-- =====================================================
-- END OF LABORATORY
-- =====================================================