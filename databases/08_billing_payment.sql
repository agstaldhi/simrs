-- =====================================================
-- SIMRS Database Schema
-- BILLING & PAYMENT
-- =====================================================

-- Tabel: service_tariffs
-- Menyimpan daftar tarif layanan
CREATE TABLE service_tariffs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    category ENUM('consultation', 'procedure', 'laboratory', 'radiology', 'room', 'emergency', 'operation', 'other') NOT NULL,
    
    description TEXT,
    
    -- Tarif berdasarkan kelas
    tariff_class_vip DECIMAL(12,2) DEFAULT 0,
    tariff_class_1 DECIMAL(12,2) DEFAULT 0,
    tariff_class_2 DECIMAL(12,2) DEFAULT 0,
    tariff_class_3 DECIMAL(12,2) DEFAULT 0,
    tariff_general DECIMAL(12,2) DEFAULT 0,
    
    unit VARCHAR(50) DEFAULT 'kali',
    
    is_active TINYINT(1) DEFAULT 1,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    INDEX idx_code (code),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: invoices
-- Menyimpan tagihan pasien
CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT UNSIGNED NOT NULL,
    visit_id INT UNSIGNED NOT NULL,
    
    invoice_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date DATE,
    
    invoice_type ENUM('outpatient', 'inpatient', 'emergency') DEFAULT 'outpatient',
    payment_status ENUM('unpaid', 'partial', 'paid', 'cancelled', 'refunded') DEFAULT 'unpaid',
    
    subtotal DECIMAL(12,2) DEFAULT 0 COMMENT 'Subtotal sebelum diskon',
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    tax_percentage DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0 COMMENT 'Total setelah diskon dan pajak',
    
    paid_amount DECIMAL(12,2) DEFAULT 0 COMMENT 'Jumlah yang sudah dibayar',
    outstanding_amount DECIMAL(12,2) DEFAULT 0 COMMENT 'Sisa yang harus dibayar',
    
    -- Insurance/BPJS
    insurance_coverage DECIMAL(12,2) DEFAULT 0 COMMENT 'Ditanggung asuransi',
    patient_responsibility DECIMAL(12,2) DEFAULT 0 COMMENT 'Tanggungan pasien',
    
    notes TEXT,
    
    -- Billing officer
    billed_by INT UNSIGNED,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE CASCADE,
    
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_id (visit_id),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: invoice_items
-- Detail item dalam tagihan
CREATE TABLE invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    
    item_type ENUM('consultation', 'medicine', 'laboratory', 'radiology', 'procedure', 'room', 'other') NOT NULL,
    item_code VARCHAR(50),
    item_name VARCHAR(200) NOT NULL,
    
    quantity INT DEFAULT 1,
    unit_price DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) DEFAULT 0,
    
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    
    total DECIMAL(12,2) DEFAULT 0,
    
    reference_id INT UNSIGNED COMMENT 'ID dari tabel referensi (prescription_id, lab_order_id, dll)',
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_item_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: payments
-- Menyimpan data pembayaran
CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(20) NOT NULL UNIQUE,
    invoice_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('cash', 'debit_card', 'credit_card', 'bank_transfer', 'insurance', 'bpjs', 'other') NOT NULL,
    
    amount DECIMAL(12,2) NOT NULL,
    
    -- For card/transfer
    card_number VARCHAR(50) COMMENT 'Last 4 digits untuk keamanan',
    bank_name VARCHAR(100),
    transaction_reference VARCHAR(100) COMMENT 'No referensi bank/kartu',
    
    -- For insurance
    insurance_policy_number VARCHAR(100),
    insurance_claim_number VARCHAR(100),
    
    payment_status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'approved',
    
    notes TEXT,
    
    -- Cashier
    received_by INT UNSIGNED,
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_payment_number (payment_number),
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: payment_refunds
-- Menyimpan data pengembalian pembayaran
CREATE TABLE payment_refunds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refund_number VARCHAR(20) NOT NULL UNIQUE,
    payment_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    
    refund_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    refund_amount DECIMAL(12,2) NOT NULL,
    refund_method ENUM('cash', 'bank_transfer', 'return_to_card') NOT NULL,
    
    reason TEXT NOT NULL,
    
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_refund_number (refund_number),
    INDEX idx_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Service Tariffs
-- =====================================================

INSERT INTO service_tariffs (code, name, category, tariff_class_vip, tariff_class_1, tariff_class_2, tariff_class_3, tariff_general) VALUES
-- Consultations
('CONS-UMUM', 'Konsultasi Dokter Umum', 'consultation', 100000, 75000, 50000, 35000, 30000),
('CONS-SPESIALIS', 'Konsultasi Dokter Spesialis', 'consultation', 250000, 200000, 150000, 100000, 75000),
('CONS-SUBSPESIALIS', 'Konsultasi Dokter Sub-Spesialis', 'consultation', 350000, 300000, 250000, 150000, 100000),

-- Room Rates (per hari)
('ROOM-VIP', 'Kamar VIP', 'room', 1000000, 0, 0, 0, 0),
('ROOM-KELAS1', 'Kamar Kelas 1', 'room', 0, 500000, 0, 0, 0),
('ROOM-KELAS2', 'Kamar Kelas 2', 'room', 0, 0, 300000, 0, 0),
('ROOM-KELAS3', 'Kamar Kelas 3', 'room', 0, 0, 0, 150000, 0),
('ROOM-ICU', 'Kamar ICU', 'room', 2000000, 1500000, 1200000, 1000000, 800000),

-- Emergency
('IGD-TRIAGE', 'Pelayanan IGD & Triage', 'emergency', 150000, 150000, 150000, 150000, 100000),
('AMBULANCE', 'Ambulance', 'emergency', 500000, 500000, 500000, 500000, 300000),

-- Procedures
('INFUS', 'Pemasangan Infus', 'procedure', 100000, 75000, 50000, 35000, 25000),
('INJEKSI-IM', 'Injeksi Intramuskuler', 'procedure', 50000, 40000, 30000, 20000, 15000),
('INJEKSI-IV', 'Injeksi Intravena', 'procedure', 75000, 60000, 45000, 30000, 20000),
('JAHIT-LUKA', 'Jahit Luka (per jahitan)', 'procedure', 100000, 75000, 50000, 35000, 25000),
('GANTI-PERBAN', 'Ganti Perban', 'procedure', 50000, 40000, 30000, 20000, 15000),
('NEBULIZER', 'Terapi Nebulizer', 'procedure', 75000, 60000, 45000, 30000, 25000),
('EKG', 'Elektrokardiogram (EKG)', 'procedure', 150000, 120000, 100000, 75000, 50000),

-- Operations (sample)
('OP-MINOR', 'Operasi Kecil', 'operation', 5000000, 4000000, 3000000, 2000000, 1500000),
('OP-MEDIUM', 'Operasi Sedang', 'operation', 15000000, 12000000, 10000000, 7500000, 5000000),
('OP-MAJOR', 'Operasi Besar', 'operation', 30000000, 25000000, 20000000, 15000000, 10000000),

-- Laboratory (reference dari lab_templates jika perlu)
('LAB-ADMIN', 'Administrasi Laboratorium', 'laboratory', 10000, 10000, 10000, 10000, 5000),

-- Radiology
('RAD-XRAY', 'Rontgen (per foto)', 'radiology', 200000, 150000, 120000, 100000, 75000),
('RAD-USG', 'USG', 'radiology', 300000, 250000, 200000, 150000, 100000),
('RAD-CTSCAN', 'CT Scan', 'radiology', 2000000, 1800000, 1500000, 1200000, 1000000),
('RAD-MRI', 'MRI', 'radiology', 3500000, 3000000, 2500000, 2000000, 1500000);

-- =====================================================
-- Seed Data: Sample Invoices
-- =====================================================

-- Invoice untuk kunjungan 1 (ISPA)
INSERT INTO invoices (
    invoice_number, patient_id, visit_id,
    invoice_date, invoice_type, payment_status,
    subtotal, total_amount, paid_amount, outstanding_amount
) VALUES
(
    'INV-2024-0001',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0001'),
    '2024-01-15 12:00:00',
    'outpatient',
    'paid',
    305000,  -- Konsultasi 75k + Lab 75k + Resep 30k + Admin Lab 10k
    305000,
    305000,
    0
);

-- Invoice untuk kunjungan 2 (Kehamilan)
INSERT INTO invoices (
    invoice_number, patient_id, visit_id,
    invoice_date, invoice_type, payment_status,
    subtotal, total_amount, paid_amount, outstanding_amount
) VALUES
(
    'INV-2024-0002',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0002'),
    '2024-01-15 13:00:00',
    'outpatient',
    'paid',
    391000,  -- Konsultasi SpOG 200k + USG 100k + Resep 16k
    391000,
    391000,
    0
);

-- Invoice untuk kunjungan 3 (Anak demam)
INSERT INTO invoices (
    invoice_number, patient_id, visit_id,
    invoice_date, invoice_type, payment_status,
    subtotal, total_amount, paid_amount, outstanding_amount
) VALUES
(
    'INV-2024-0003',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0003'),
    '2024-01-16 14:00:00',
    'outpatient',
    'paid',
    346000,  -- Konsultasi Sp.A 200k + Lab Widal 50k + Lab DL 75k + Resep 16k + Admin Lab 5k
    346000,
    346000,
    0
);

-- Invoice untuk kunjungan 4 (Mata - belum bayar)
INSERT INTO invoices (
    invoice_number, patient_id, visit_id,
    invoice_date, invoice_type, payment_status,
    subtotal, total_amount, outstanding_amount
) VALUES
(
    'INV-2024-0004',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0004'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0004'),
    '2024-01-16 15:00:00',
    'outpatient',
    'unpaid',
    200000,  -- Konsultasi Sp.M 200k
    200000,
    200000
);

-- Invoice untuk kunjungan 5 (Hipertensi - belum bayar)
INSERT INTO invoices (
    invoice_number, patient_id, visit_id,
    invoice_date, invoice_type, payment_status,
    subtotal, total_amount, outstanding_amount
) VALUES
(
    'INV-2024-0005',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0005'),
    (SELECT id FROM patient_visits WHERE visit_number = 'REG-2024-0005'),
    '2024-01-17 12:00:00',
    'outpatient',
    'unpaid',
    230000,  -- Konsultasi 75k + Lab 120k + Resep 30k + Admin Lab 5k
    230000,
    230000
);

-- =====================================================
-- Seed Data: Invoice Items
-- =====================================================

-- Items untuk Invoice 1
INSERT INTO invoice_items (invoice_id, item_type, item_code, item_name, quantity, unit_price, subtotal, total) VALUES
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0001'),
    'consultation',
    'CONS-SPESIALIS',
    'Konsultasi dr. John Doe, Sp.PD',
    1,
    75000,
    75000,
    75000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0001'),
    'laboratory',
    'DL',
    'Darah Lengkap',
    1,
    75000,
    75000,
    75000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0001'),
    'laboratory',
    'LAB-ADMIN',
    'Administrasi Laboratorium',
    1,
    10000,
    10000,
    10000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0001'),
    'medicine',
    'AMX500',
    'Amoxicillin 500mg x15',
    1,
    12000,
    12000,
    12000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0001'),
    'medicine',
    'PARA500',
    'Paracetamol 500mg x15',
    1,
    3000,
    3000,
    3000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0001'),
    'medicine',
    'OBH',
    'OBH Sirup 100ml',
    1,
    15000,
    15000,
    15000
);

-- Items untuk Invoice 2
INSERT INTO invoice_items (invoice_id, item_type, item_code, item_name, quantity, unit_price, subtotal, total) VALUES
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0002'),
    'consultation',
    'CONS-SPESIALIS',
    'Konsultasi dr. Michael Brown, Sp.OG',
    1,
    200000,
    200000,
    200000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0002'),
    'radiology',
    'RAD-USG',
    'USG Kehamilan',
    1,
    100000,
    100000,
    100000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0002'),
    'medicine',
    'MULTIV',
    'Suplemen Kehamilan',
    1,
    16000,
    16000,
    16000
);

-- Items untuk Invoice 3
INSERT INTO invoice_items (invoice_id, item_type, item_code, item_name, quantity, unit_price, subtotal, total) VALUES
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0003'),
    'consultation',
    'CONS-SPESIALIS',
    'Konsultasi dr. Lisa Anderson, Sp.A',
    1,
    200000,
    200000,
    200000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0003'),
    'laboratory',
    'WIDAL',
    'Widal Test',
    1,
    50000,
    50000,
    50000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0003'),
    'laboratory',
    'DL',
    'Darah Lengkap',
    1,
    75000,
    75000,
    75000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0003'),
    'laboratory',
    'LAB-ADMIN',
    'Administrasi Laboratorium',
    1,
    5000,
    5000,
    5000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0003'),
    'medicine',
    'PARAS',
    'Paracetamol Sirup',
    1,
    12000,
    12000,
    12000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0003'),
    'medicine',
    'VITC',
    'Vitamin C x10',
    1,
    4000,
    4000,
    4000
);

-- Items untuk Invoice 4
INSERT INTO invoice_items (invoice_id, item_type, item_code, item_name, quantity, unit_price, subtotal, total) VALUES
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0004'),
    'consultation',
    'CONS-SPESIALIS',
    'Konsultasi dr. Sarah Wilson, Sp.M',
    1,
    200000,
    200000,
    200000
);

-- Items untuk Invoice 5
INSERT INTO invoice_items (invoice_id, item_type, item_code, item_name, quantity, unit_price, subtotal, total) VALUES
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0005'),
    'consultation',
    'CONS-UMUM',
    'Konsultasi Dokter Umum',
    1,
    75000,
    75000,
    75000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0005'),
    'laboratory',
    'GDS',
    'Gula Darah Sewaktu',
    1,
    35000,
    35000,
    35000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0005'),
    'laboratory',
    'CHOL',
    'Kolesterol Total',
    1,
    45000,
    45000,
    45000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0005'),
    'laboratory',
    'URIC',
    'Asam Urat',
    1,
    40000,
    40000,
    40000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0005'),
    'laboratory',
    'LAB-ADMIN',
    'Administrasi Laboratorium',
    1,
    5000,
    5000,
    5000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0005'),
    'medicine',
    'AMLO5',
    'Amlodipine 5mg x30 (belum diambil)',
    1,
    18000,
    18000,
    18000
),
(
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0005'),
    'medicine',
    'CAPTO',
    'Captopril 25mg x30 (belum diambil)',
    1,
    12000,
    12000,
    12000
);

-- =====================================================
-- Seed Data: Payments
-- =====================================================

-- Payment untuk Invoice 1
INSERT INTO payments (
    payment_number, invoice_id, patient_id,
    payment_date, payment_method, amount, payment_status
) VALUES
(
    'PAY-2024-0001',
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0001'),
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    '2024-01-15 12:15:00',
    'bpjs',
    305000,
    'approved'
);

-- Payment untuk Invoice 2
INSERT INTO payments (
    payment_number, invoice_id, patient_id,
    payment_date, payment_method, amount, payment_status
) VALUES
(
    'PAY-2024-0002',
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0002'),
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    '2024-01-15 13:15:00',
    'cash',
    391000,
    'approved'
);

-- Payment untuk Invoice 3
INSERT INTO payments (
    payment_number, invoice_id, patient_id,
    payment_date, payment_method, amount, payment_status
) VALUES
(
    'PAY-2024-0003',
    (SELECT id FROM invoices WHERE invoice_number = 'INV-2024-0003'),
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    '2024-01-16 14:15:00',
    'bpjs',
    346000,
    'approved'
);

-- =====================================================
-- View: Invoice Summary
-- =====================================================

CREATE OR REPLACE VIEW v_invoices_summary AS
SELECT 
    i.id,
    i.invoice_number,
    i.invoice_date,
    i.invoice_type,
    i.payment_status,
    p.medical_record_number,
    p.full_name AS patient_name,
    p.phone,
    pv.visit_number,
    i.total_amount,
    i.paid_amount,
    i.outstanding_amount,
    COUNT(ii.id) AS total_items,
    i.created_at
FROM invoices i
JOIN patients p ON i.patient_id = p.id
JOIN patient_visits pv ON i.visit_id = pv.id
LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
GROUP BY i.id
ORDER BY i.invoice_date DESC;

-- =====================================================
-- View: Outstanding Invoices
-- =====================================================

CREATE OR REPLACE VIEW v_outstanding_invoices AS
SELECT 
    i.id,
    i.invoice_number,
    i.invoice_date,
    DATEDIFF(CURDATE(), i.invoice_date) AS days_overdue,
    p.medical_record_number,
    p.full_name AS patient_name,
    p.phone,
    p.mobile,
    i.total_amount,
    i.paid_amount,
    i.outstanding_amount
FROM invoices i
JOIN patients p ON i.patient_id = p.id
WHERE i.payment_status IN ('unpaid', 'partial')
AND i.outstanding_amount > 0
ORDER BY i.invoice_date ASC;

-- =====================================================
-- View: Daily Revenue Summary
-- =====================================================

CREATE OR REPLACE VIEW v_daily_revenue AS
SELECT 
    DATE(payment_date) AS payment_date,
    payment_method,
    COUNT(id) AS transaction_count,
    SUM(amount) AS total_revenue
FROM payments
WHERE payment_status = 'approved'
GROUP BY DATE(payment_date), payment_method
ORDER BY payment_date DESC, payment_method;

-- =====================================================
-- END OF BILLING & PAYMENT
-- =====================================================