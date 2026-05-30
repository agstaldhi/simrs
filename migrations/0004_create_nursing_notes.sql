-- Migrasi: Tabel Catatan Keperawatan (Nursing Notes)
-- Menyimpan catatan SOAP dan tindakan asuhan keperawatan pasien rawat inap

CREATE TABLE IF NOT EXISTS nursing_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    nurse_id INT UNSIGNED NOT NULL COMMENT 'User ID perawat yang bertugas',
    note_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- SOAP Keperawatan
    subjective TEXT COMMENT 'Subjective (Keluhan pasien)',
    objective TEXT COMMENT 'Objective (Pemeriksaan fisik/vital sign)',
    assessment TEXT COMMENT 'Assessment (Diagnosa keperawatan)',
    plan TEXT COMMENT 'Plan (Rencana asuhan)',
    
    -- Evaluasi & Tindakan
    intervention TEXT COMMENT 'Tindakan keperawatan yang dilakukan',
    evaluation TEXT COMMENT 'Evaluasi hasil tindakan',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (nurse_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_visit_id (visit_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_nurse_id (nurse_id),
    INDEX idx_note_date (note_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
