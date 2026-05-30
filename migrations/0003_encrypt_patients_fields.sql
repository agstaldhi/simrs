-- Migrasi: Enkripsi Data Sensitif Pasien
-- Menyesuaikan tabel patients agar mendukung enkripsi (ciphertext) dan pseudonymization search hashes

ALTER TABLE patients MODIFY nik VARCHAR(255) NULL;
ALTER TABLE patients MODIFY insurance_number VARCHAR(255) NULL;

-- Tambahkan search hashes
ALTER TABLE patients ADD COLUMN nik_hash VARCHAR(64) NULL AFTER nik;
ALTER TABLE patients ADD COLUMN insurance_number_hash VARCHAR(64) NULL AFTER insurance_number;

-- Tambahkan indeks untuk performa pencarian hash
ALTER TABLE patients ADD UNIQUE INDEX idx_nik_hash (nik_hash);
ALTER TABLE patients ADD INDEX idx_insurance_number_hash (insurance_number_hash);

-- Tambahkan satusehat_id jika belum ada
ALTER TABLE patients ADD COLUMN satusehat_id VARCHAR(64) NULL AFTER insurance_number_hash;
