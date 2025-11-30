-- =====================================================
-- SIMRS Database Schema
-- PHARMACY (Farmasi & Resep)
-- =====================================================

-- Tabel: medicines
-- Menyimpan data obat/farmasi
CREATE TABLE medicines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    generic_name VARCHAR(200),
    
    category ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream', 'ointment', 'drop', 'inhaler', 'suppository', 'other') NOT NULL,
    form VARCHAR(100) COMMENT 'Bentuk sediaan: tablet, kaplet, sirup, dll',
    strength VARCHAR(100) COMMENT 'Kekuatan: 500mg, 10ml, dll',
    
    manufacturer VARCHAR(200),
    supplier VARCHAR(200),
    
    unit VARCHAR(50) DEFAULT 'tablet' COMMENT 'Satuan terkecil',
    unit_in_box INT DEFAULT 1 COMMENT 'Jumlah satuan per box',
    
    -- Pricing
    purchase_price DECIMAL(12,2) DEFAULT 0 COMMENT 'Harga beli',
    selling_price DECIMAL(12,2) DEFAULT 0 COMMENT 'Harga jual',
    margin_percentage DECIMAL(5,2) DEFAULT 0 COMMENT 'Margin keuntungan %',
    
    -- Stock
    minimum_stock INT DEFAULT 0 COMMENT 'Stok minimum',
    maximum_stock INT DEFAULT 0 COMMENT 'Stok maksimum',
    reorder_level INT DEFAULT 0 COMMENT 'Level reorder',
    
    -- Classification
    is_generic TINYINT(1) DEFAULT 0,
    is_prescription_required TINYINT(1) DEFAULT 1 COMMENT 'Perlu resep dokter',
    is_narcotic TINYINT(1) DEFAULT 0 COMMENT 'Obat narkotika',
    is_psychotropic TINYINT(1) DEFAULT 0 COMMENT 'Obat psikotropika',
    
    storage_requirements TEXT COMMENT 'Cara penyimpanan',
    usage_instructions TEXT COMMENT 'Cara penggunaan',
    side_effects TEXT COMMENT 'Efek samping',
    contraindications TEXT COMMENT 'Kontraindikasi',
    
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    updated_by INT UNSIGNED,
    
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_generic_name (generic_name),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    FULLTEXT idx_fulltext_search (name, generic_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: medicine_stock
-- Menyimpan stok obat per lokasi
CREATE TABLE medicine_stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    location ENUM('main_pharmacy', 'inpatient_pharmacy', 'emergency_pharmacy') DEFAULT 'main_pharmacy',
    
    quantity INT DEFAULT 0,
    batch_number VARCHAR(50),
    expiry_date DATE,
    
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED,
    
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    
    INDEX idx_medicine_id (medicine_id),
    INDEX idx_location (location),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_batch_number (batch_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: prescriptions
-- Menyimpan data resep
CREATE TABLE prescriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_number VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT UNSIGNED NOT NULL,
    visit_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    
    prescription_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    prescription_type ENUM('outpatient', 'inpatient', 'emergency') DEFAULT 'outpatient',
    
    status ENUM('pending', 'verified', 'dispensed', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Dispensing
    dispensed_at DATETIME,
    dispensed_by INT UNSIGNED COMMENT 'Apoteker yang menyerahkan',
    
    -- Payment
    is_paid TINYINT(1) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    
    notes TEXT,
    pharmacist_notes TEXT COMMENT 'Catatan apoteker',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE RESTRICT,
    
    INDEX idx_prescription_number (prescription_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_prescription_date (prescription_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: prescription_items
-- Detail item obat dalam resep
CREATE TABLE prescription_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    
    quantity INT NOT NULL COMMENT 'Jumlah yang diresepkan',
    dispensed_quantity INT DEFAULT 0 COMMENT 'Jumlah yang diserahkan',
    
    dosage VARCHAR(200) COMMENT 'Dosis: 3x1, 2x1, dll',
    frequency VARCHAR(100) COMMENT 'Frekuensi: setiap 8 jam, dll',
    duration VARCHAR(100) COMMENT 'Durasi: 5 hari, 1 minggu, dll',
    route VARCHAR(50) COMMENT 'Cara pemberian: oral, injeksi, topikal',
    
    instructions TEXT COMMENT 'Instruksi penggunaan detail',
    
    unit_price DECIMAL(12,2) DEFAULT 0,
    total_price DECIMAL(12,2) DEFAULT 0,
    
    status ENUM('pending', 'available', 'out_of_stock', 'dispensed', 'cancelled') DEFAULT 'pending',
    
    notes TEXT,
    substitution_note TEXT COMMENT 'Catatan jika ada penggantian obat',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT,
    
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_medicine_id (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: medicine_stock_movements
-- Menyimpan pergerakan stok obat (masuk/keluar)
CREATE TABLE medicine_stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    
    movement_type ENUM('in', 'out', 'adjustment', 'expired', 'damaged', 'return') NOT NULL,
    reference_type ENUM('purchase', 'prescription', 'stock_opname', 'transfer', 'other') NOT NULL,
    reference_id INT UNSIGNED COMMENT 'ID dari tabel referensi',
    reference_number VARCHAR(50),
    
    quantity INT NOT NULL COMMENT 'Positif untuk masuk, negatif untuk keluar',
    unit_price DECIMAL(12,2) DEFAULT 0,
    total_price DECIMAL(12,2) DEFAULT 0,
    
    batch_number VARCHAR(50),
    expiry_date DATE,
    
    location ENUM('main_pharmacy', 'inpatient_pharmacy', 'emergency_pharmacy') DEFAULT 'main_pharmacy',
    
    stock_before INT DEFAULT 0,
    stock_after INT DEFAULT 0,
    
    movement_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    created_by INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    
    INDEX idx_medicine_id (medicine_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_reference_type (reference_type),
    INDEX idx_movement_date (movement_date),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Medicines (Common Drugs)
-- =====================================================

INSERT INTO medicines (
    code, name, generic_name, category, form, strength,
    manufacturer, unit, unit_in_box, purchase_price, selling_price,
    is_prescription_required, minimum_stock, reorder_level
) VALUES
-- Antibiotics
('AMX500', 'Amoxicillin 500mg', 'Amoxicillin', 'capsule', 'Kaplet', '500mg', 'Kimia Farma', 'tablet', 100, 500, 800, 1, 500, 200),
('AMXCL', 'Amoxicillin Clavulanat', 'Amoxicillin + Clavulanic Acid', 'tablet', 'Tablet', '625mg', 'Dexa Medica', 'tablet', 100, 3000, 4500, 1, 200, 100),
('AZITH', 'Azithromycin 500mg', 'Azithromycin', 'tablet', 'Tablet', '500mg', 'Kalbe Farma', 'tablet', 60, 4000, 6000, 1, 150, 75),
('CIPRO', 'Ciprofloxacin 500mg', 'Ciprofloxacin', 'tablet', 'Tablet', '500mg', 'Kalbe Farma', 'tablet', 100, 1500, 2500, 1, 200, 100),

-- Analgesics & Antipyretics
('PARA500', 'Paracetamol 500mg', 'Paracetamol', 'tablet', 'Tablet', '500mg', 'Kimia Farma', 'tablet', 1000, 100, 200, 0, 2000, 500),
('PARAS', 'Paracetamol Sirup', 'Paracetamol', 'syrup', 'Sirup', '120mg/5ml', 'Kalbe Farma', 'botol', 50, 8000, 12000, 0, 100, 50),
('IBUPRO', 'Ibuprofen 400mg', 'Ibuprofen', 'tablet', 'Tablet', '400mg', 'Dexa Medica', 'tablet', 100, 300, 500, 0, 500, 200),
('ASPI', 'Aspirin 100mg', 'Acetylsalicylic Acid', 'tablet', 'Tablet', '100mg', 'Bayer', 'tablet', 100, 200, 400, 0, 300, 150),

-- Antihypertensives
('AMLO5', 'Amlodipine 5mg', 'Amlodipine', 'tablet', 'Tablet', '5mg', 'Dexa Medica', 'tablet', 100, 300, 600, 1, 500, 200),
('CAPTO', 'Captopril 25mg', 'Captopril', 'tablet', 'Tablet', '25mg', 'Kimia Farma', 'tablet', 100, 200, 400, 1, 500, 200),
('ATEN', 'Atenolol 50mg', 'Atenolol', 'tablet', 'Tablet', '50mg', 'Kalbe Farma', 'tablet', 100, 400, 700, 1, 300, 150),

-- Antidiabetics
('METF500', 'Metformin 500mg', 'Metformin HCl', 'tablet', 'Tablet', '500mg', 'Dexa Medica', 'tablet', 100, 200, 400, 1, 1000, 300),
('GLIBEN', 'Glibenclamide 5mg', 'Glibenclamide', 'tablet', 'Tablet', '5mg', 'Kalbe Farma', 'tablet', 100, 300, 600, 1, 500, 200),

-- Antiulcer
('OMEP', 'Omeprazole 20mg', 'Omeprazole', 'capsule', 'Kapsul', '20mg', 'Dexa Medica', 'tablet', 100, 1000, 1800, 1, 300, 150),
('RANI', 'Ranitidine 150mg', 'Ranitidine', 'tablet', 'Tablet', '150mg', 'Kimia Farma', 'tablet', 100, 400, 700, 0, 400, 150),
('ANTAC', 'Antasida Tablet', 'Aluminium Hydroxide', 'tablet', 'Tablet', '500mg', 'Kalbe Farma', 'tablet', 100, 100, 250, 0, 500, 200),

-- Antihistamine
('CTM', 'Chlorpheniramine 4mg', 'Chlorpheniramine Maleate', 'tablet', 'Tablet', '4mg', 'Kimia Farma', 'tablet', 1000, 50, 150, 0, 1000, 300),
('CETIR', 'Cetirizine 10mg', 'Cetirizine', 'tablet', 'Tablet', '10mg', 'Dexa Medica', 'tablet', 100, 500, 900, 0, 300, 150),

-- Cough & Cold
('OBH', 'OBH Sirup', 'Dextromethorphan', 'syrup', 'Sirup', '100ml', 'Combiphar', 'botol', 50, 10000, 15000, 0, 100, 50),
('WOODS', 'Woods Sirup', 'Guaiphenesin', 'syrup', 'Sirup', '60ml', 'Kalbe Farma', 'botol', 50, 15000, 22000, 0, 80, 40),

-- Vitamins & Supplements
('VITC', 'Vitamin C 500mg', 'Ascorbic Acid', 'tablet', 'Tablet', '500mg', 'Kalbe Farma', 'tablet', 100, 200, 400, 0, 500, 200),
('VITB', 'Vitamin B Complex', 'B Complex', 'tablet', 'Tablet', '-', 'Dexa Medica', 'tablet', 100, 300, 600, 0, 300, 150),
('MULTIV', 'Multivitamin', 'Multivitamin', 'tablet', 'Tablet', '-', 'Kalbe Farma', 'tablet', 100, 500, 900, 0, 300, 150),

-- Topical
('BETADIN', 'Betadine Solution', 'Povidone Iodine', 'other', 'Solution', '100ml', 'Mundi Pharma', 'botol', 20, 20000, 30000, 0, 50, 20),
('SALEP-H', 'Salep Hidrokortison', 'Hydrocortisone', 'cream', 'Salep', '2.5%', 'Kimia Farma', 'tube', 50, 8000, 12000, 1, 100, 50);

-- =====================================================
-- Seed Data: Medicine Stock (Initial Stock)
-- =====================================================

INSERT INTO medicine_stock (medicine_id, location, quantity, batch_number, expiry_date) 
SELECT id, 'main_pharmacy', 1000, CONCAT('BATCH-', DATE_FORMAT(NOW(), '%Y%m')), DATE_ADD(NOW(), INTERVAL 24 MONTH)
FROM medicines
WHERE code IN ('AMX500', 'PARA500', 'AMLO5', 'METF500', 'OMEP', 'CTM', 'VITC', 'BETADIN');

INSERT INTO medicine_stock (medicine_id, location, quantity, batch_number, expiry_date) 
SELECT id, 'main_pharmacy', 500, CONCAT('BATCH-', DATE_FORMAT(NOW(), '%Y%m')), DATE_ADD(NOW(), INTERVAL 18 MONTH)
FROM medicines
WHERE code IN ('AZITH', 'CIPRO', 'IBUPRO', 'CAPTO', 'GLIBEN', 'CETIR', 'VITB', 'SALEP-H');

INSERT INTO medicine_stock (medicine_id, location, quantity, batch_number, expiry_date) 
SELECT id, 'main_pharmacy', 300, CONCAT('BATCH-', DATE_FORMAT(NOW(), '%Y%m')), DATE_ADD(NOW(), INTERVAL 12 MONTH)
FROM medicines
WHERE code IN ('AMXCL', 'PARAS', 'ATEN', 'RANI', 'OBH', 'WOODS', 'MULTIV', 'ANTAC');

-- =====================================================
-- Seed Data: Sample Prescriptions
-- =====================================================

INSERT INTO prescriptions (
    prescription_number, patient_id, visit_id, doctor_id,
    prescription_date, prescription_type, status, total_amount
) VALUES
-- Resep untuk pasien dengan ISPA
(
    'RX-2024-0001',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0001'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    '2024-01-15 10:00:00',
    'outpatient',
    'completed',
    12600
),
-- Resep untuk anak dengan demam
(
    'RX-2024-0002',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0003'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    '2024-01-16 10:00:00',
    'outpatient',
    'completed',
    18000
),
-- Resep untuk pasien hipertensi
(
    'RX-2024-0003',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0005'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0005'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    '2024-01-17 11:00:00',
    'outpatient',
    'pending',
    18000
);

-- =====================================================
-- Seed Data: Prescription Items
-- =====================================================

-- Resep 1: ISPA (Amoxicillin, Paracetamol, OBH)
INSERT INTO prescription_items (
    prescription_id, medicine_id, quantity, dispensed_quantity,
    dosage, frequency, duration, route, instructions, unit_price, total_price, status
) VALUES
(
    (SELECT id FROM prescriptions WHERE prescription_number = 'RX-2024-0001'),
    (SELECT id FROM medicines WHERE code = 'AMX500'),
    15,
    15,
    '3 x 1',
    'Setiap 8 jam',
    '5 hari',
    'Oral',
    'Diminum setelah makan',
    800,
    12000,
    'dispensed'
),
(
    (SELECT id FROM prescriptions WHERE prescription_number = 'RX-2024-0001'),
    (SELECT id FROM medicines WHERE code = 'PARA500'),
    15,
    15,
    '3 x 1',
    'Setiap 8 jam jika demam',
    '5 hari',
    'Oral',
    'Diminum setelah makan',
    200,
    3000,
    'dispensed'
),
(
    (SELECT id FROM prescriptions WHERE prescription_number = 'RX-2024-0001'),
    (SELECT id FROM medicines WHERE code = 'OBH'),
    1,
    1,
    '3 x 1 sendok makan',
    'Setiap 8 jam',
    'Habiskan',
    'Oral',
    'Kocok dahulu sebelum diminum',
    15000,
    15000,
    'dispensed'
);

-- Resep 2: Anak demam (Paracetamol Sirup)
INSERT INTO prescription_items (
    prescription_id, medicine_id, quantity, dispensed_quantity,
    dosage, frequency, duration, route, instructions, unit_price, total_price, status
) VALUES
(
    (SELECT id FROM prescriptions WHERE prescription_number = 'RX-2024-0002'),
    (SELECT id FROM medicines WHERE code = 'PARAS'),
    1,
    1,
    '3-4 x 1 sendok teh (5ml)',
    'Setiap 6-8 jam jika demam',
    '3 hari',
    'Oral',
    'Kocok dahulu sebelum diminum. Hentikan jika demam turun.',
    12000,
    12000,
    'dispensed'
),
(
    (SELECT id FROM prescriptions WHERE prescription_number = 'RX-2024-0002'),
    (SELECT id FROM medicines WHERE code = 'VITC'),
    10,
    10,
    '1 x 1',
    'Setelah makan pagi',
    '10 hari',
    'Oral',
    'Untuk mempercepat pemulihan',
    400,
    4000,
    'dispensed'
);

-- Resep 3: Hipertensi (Amlodipine, Captopril)
INSERT INTO prescription_items (
    prescription_id, medicine_id, quantity, dispensed_quantity,
    dosage, frequency, duration, route, instructions, unit_price, total_price, status
) VALUES
(
    (SELECT id FROM prescriptions WHERE prescription_number = 'RX-2024-0003'),
    (SELECT id FROM medicines WHERE code = 'AMLO5'),
    30,
    0,
    '1 x 1',
    'Setiap pagi',
    '30 hari',
    'Oral',
    'Diminum rutin setiap hari di waktu yang sama',
    600,
    18000,
    'pending'
),
(
    (SELECT id FROM prescriptions WHERE prescription_number = 'RX-2024-0003'),
    (SELECT id FROM medicines WHERE code = 'CAPTO'),
    30,
    0,
    '2 x 1',
    'Pagi dan sore',
    '30 hari',
    'Oral',
    'Diminum sebelum makan',
    400,
    12000,
    'pending'
);

-- Update prescription yang sudah diserahkan
UPDATE prescriptions 
SET status = 'dispensed', 
    dispensed_at = '2024-01-15 11:00:00',
    is_paid = 1
WHERE prescription_number = 'RX-2024-0001';

UPDATE prescriptions 
SET status = 'dispensed', 
    dispensed_at = '2024-01-16 11:00:00',
    is_paid = 1
WHERE prescription_number = 'RX-2024-0002';

-- =====================================================
-- Seed Data: Medicine Stock Movements (Dispensing)
-- =====================================================

-- Stock movement untuk resep yang sudah diserahkan
INSERT INTO medicine_stock_movements (
    medicine_id, movement_type, reference_type, reference_number,
    quantity, unit_price, total_price, location, movement_date, notes
) VALUES
(
    (SELECT id FROM medicines WHERE code = 'AMX500'),
    'out',
    'prescription',
    'RX-2024-0001',
    -15,
    800,
    12000,
    'main_pharmacy',
    '2024-01-15 11:00:00',
    'Diserahkan ke pasien RM-2024-0001'
),
(
    (SELECT id FROM medicines WHERE code = 'PARA500'),
    'out',
    'prescription',
    'RX-2024-0001',
    -15,
    200,
    3000,
    'main_pharmacy',
    '2024-01-15 11:00:00',
    'Diserahkan ke pasien RM-2024-0001'
),
(
    (SELECT id FROM medicines WHERE code = 'PARAS'),
    'out',
    'prescription',
    'RX-2024-0002',
    -1,
    12000,
    12000,
    'main_pharmacy',
    '2024-01-16 11:00:00',
    'Diserahkan ke pasien RM-2024-0003'
);

-- Update stok setelah dispensing
UPDATE medicine_stock 
SET quantity = quantity - 15
WHERE medicine_id = (SELECT id FROM medicines WHERE code = 'AMX500')
AND location = 'main_pharmacy';

UPDATE medicine_stock 
SET quantity = quantity - 15
WHERE medicine_id = (SELECT id FROM medicines WHERE code = 'PARA500')
AND location = 'main_pharmacy';

UPDATE medicine_stock 
SET quantity = quantity - 1
WHERE medicine_id = (SELECT id FROM medicines WHERE code = 'PARAS')
AND location = 'main_pharmacy';

-- =====================================================
-- View: Current Medicine Stock
-- =====================================================

CREATE OR REPLACE VIEW v_medicine_stock_current AS
SELECT 
    m.id AS medicine_id,
    m.code,
    m.name,
    m.generic_name,
    m.category,
    m.strength,
    m.unit,
    SUM(ms.quantity) AS total_stock,
    m.minimum_stock,
    m.reorder_level,
    CASE 
        WHEN SUM(ms.quantity) = 0 THEN 'out_of_stock'
        WHEN SUM(ms.quantity) <= m.minimum_stock THEN 'low_stock'
        WHEN SUM(ms.quantity) <= m.reorder_level THEN 'reorder_needed'
        ELSE 'normal'
    END AS stock_status,
    m.selling_price,
    MIN(ms.expiry_date) AS nearest_expiry_date,
    DATEDIFF(MIN(ms.expiry_date), CURDATE()) AS days_to_expire
FROM medicines m
LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id
WHERE m.is_active = 1
GROUP BY m.id
ORDER BY stock_status DESC, days_to_expire ASC;

-- =====================================================
-- View: Prescription Summary
-- =====================================================

CREATE OR REPLACE VIEW v_prescriptions_summary AS
SELECT 
    p.id,
    p.prescription_number,
    p.prescription_date,
    p.prescription_type,
    p.status,
    pt.medical_record_number,
    pt.full_name AS patient_name,
    d.employee_number AS doctor_employee_number,
    u.full_name AS doctor_name,
    pv.visit_number,
    p.total_amount,
    p.is_paid,
    p.dispensed_at,
    COUNT(pi.id) AS total_items,
    SUM(CASE WHEN pi.status = 'dispensed' THEN 1 ELSE 0 END) AS dispensed_items
FROM prescriptions p
JOIN patients pt ON p.patient_id = pt.id
JOIN patient_visits pv ON p.visit_id = pv.id
JOIN doctors d ON p.doctor_id = d.id
JOIN users u ON d.user_id = u.id
LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
GROUP BY p.id
ORDER BY p.prescription_date DESC;

-- =====================================================
-- END OF PHARMACY
-- =====================================================