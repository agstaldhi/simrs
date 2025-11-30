-- =====================================================
-- SIMRS Database Schema
-- SCHEDULING & APPOINTMENTS
-- =====================================================

-- Tabel: doctor_schedules
-- Menyimpan jadwal praktek dokter
CREATE TABLE doctor_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT UNSIGNED NOT NULL,
    polyclinic_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED,
    
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    
    quota INT DEFAULT 20 COMMENT 'Kuota pasien per sesi',
    booking_available TINYINT(1) DEFAULT 1 COMMENT 'Bisa booking online atau tidak',
    
    effective_from DATE NOT NULL COMMENT 'Berlaku mulai tanggal',
    effective_to DATE COMMENT 'Berlaku sampai tanggal (NULL = tanpa batas)',
    
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    updated_by INT UNSIGNED,
    
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (polyclinic_id) REFERENCES polyclinics(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_polyclinic_id (polyclinic_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_is_active (is_active),
    INDEX idx_dates (effective_from, effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: schedule_exceptions
-- Menyimpan pengecualian jadwal (libur, cuti dokter, dll)
CREATE TABLE schedule_exceptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT UNSIGNED NOT NULL,
    exception_date DATE NOT NULL,
    
    exception_type ENUM('holiday', 'leave', 'training', 'emergency', 'other') NOT NULL DEFAULT 'leave',
    reason TEXT,
    
    is_all_day TINYINT(1) DEFAULT 1,
    start_time TIME,
    end_time TIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_exception_date (exception_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: appointments
-- Menyimpan data appointment/perjanjian pasien
CREATE TABLE appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_number VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    polyclinic_id INT UNSIGNED NOT NULL,
    
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    estimated_duration INT DEFAULT 15 COMMENT 'Estimasi durasi dalam menit',
    
    appointment_type ENUM('new', 'follow_up', 'consultation', 'checkup') DEFAULT 'new',
    booking_method ENUM('online', 'phone', 'walk_in', 'referral') DEFAULT 'walk_in',
    
    status ENUM('scheduled', 'confirmed', 'arrived', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    
    chief_complaint TEXT,
    notes TEXT,
    
    -- Konfirmasi
    is_confirmed TINYINT(1) DEFAULT 0,
    confirmed_at DATETIME,
    confirmed_by INT UNSIGNED,
    
    -- Pembatalan
    cancelled_at DATETIME,
    cancelled_by INT UNSIGNED,
    cancellation_reason TEXT,
    
    -- Reminder
    reminder_sent TINYINT(1) DEFAULT 0,
    reminder_sent_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (polyclinic_id) REFERENCES polyclinics(id) ON DELETE CASCADE,
    
    INDEX idx_appointment_number (appointment_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_polyclinic_id (polyclinic_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_appointment_time (appointment_time),
    INDEX idx_status (status),
    INDEX idx_date_time (appointment_date, appointment_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: queues
-- Menyimpan antrian pasien hari ini
CREATE TABLE queues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue_date DATE NOT NULL,
    queue_number INT NOT NULL COMMENT 'Nomor antrian',
    patient_id INT UNSIGNED NOT NULL,
    polyclinic_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED,
    appointment_id INT UNSIGNED COMMENT 'Jika dari appointment',
    visit_id INT UNSIGNED COMMENT 'Link ke patient_visits setelah registrasi',
    
    queue_type ENUM('appointment', 'walk_in', 'emergency') DEFAULT 'walk_in',
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    
    status ENUM('waiting', 'called', 'in_service', 'completed', 'cancelled', 'no_show') DEFAULT 'waiting',
    
    -- Waktu
    registration_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    called_time DATETIME COMMENT 'Waktu dipanggil',
    service_start_time DATETIME COMMENT 'Waktu mulai dilayani',
    service_end_time DATETIME COMMENT 'Waktu selesai dilayani',
    
    -- Counter/loket pelayanan
    counter_number VARCHAR(10) COMMENT 'Nomor loket/ruang periksa',
    called_by INT UNSIGNED COMMENT 'Petugas yang memanggil',
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (polyclinic_id) REFERENCES polyclinics(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_queue_date_number (queue_date, polyclinic_id, queue_number),
    INDEX idx_queue_date (queue_date),
    INDEX idx_patient_id (patient_id),
    INDEX idx_polyclinic_id (polyclinic_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_status (status),
    INDEX idx_queue_number (queue_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Doctor Schedules
-- =====================================================

-- Jadwal dr. John Doe (Poli Penyakit Dalam)
INSERT INTO doctor_schedules (
    doctor_id, polyclinic_id, room_id,
    day_of_week, start_time, end_time, quota, effective_from
) VALUES
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM rooms WHERE code = 'POLI-PD'),
    'monday', '08:00:00', '12:00:00', 20, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM rooms WHERE code = 'POLI-PD'),
    'tuesday', '08:00:00', '12:00:00', 20, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM rooms WHERE code = 'POLI-PD'),
    'wednesday', '13:00:00', '17:00:00', 15, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM rooms WHERE code = 'POLI-PD'),
    'thursday', '08:00:00', '12:00:00', 20, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM rooms WHERE code = 'POLI-PD'),
    'friday', '08:00:00', '12:00:00', 20, '2024-01-01'
);

-- Jadwal dr. Jane Smith (Poli Bedah)
INSERT INTO doctor_schedules (
    doctor_id, polyclinic_id, room_id,
    day_of_week, start_time, end_time, quota, effective_from
) VALUES
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-002'),
    (SELECT id FROM polyclinics WHERE code = 'BEDAH'),
    (SELECT id FROM rooms WHERE code = 'POLI-BED'),
    'monday', '13:00:00', '17:00:00', 15, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-002'),
    (SELECT id FROM polyclinics WHERE code = 'BEDAH'),
    (SELECT id FROM rooms WHERE code = 'POLI-BED'),
    'wednesday', '08:00:00', '12:00:00', 20, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-002'),
    (SELECT id FROM polyclinics WHERE code = 'BEDAH'),
    (SELECT id FROM rooms WHERE code = 'POLI-BED'),
    'friday', '13:00:00', '17:00:00', 15, '2024-01-01'
);

-- Jadwal dr. Lisa Anderson (Poli Anak)
INSERT INTO doctor_schedules (
    doctor_id, polyclinic_id, room_id,
    day_of_week, start_time, end_time, quota, effective_from
) VALUES
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM rooms WHERE code = 'POLI-AK'),
    'monday', '08:00:00', '12:00:00', 25, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM rooms WHERE code = 'POLI-AK'),
    'tuesday', '08:00:00', '12:00:00', 25, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM rooms WHERE code = 'POLI-AK'),
    'wednesday', '08:00:00', '12:00:00', 25, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM rooms WHERE code = 'POLI-AK'),
    'thursday', '08:00:00', '12:00:00', 25, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM rooms WHERE code = 'POLI-AK'),
    'friday', '08:00:00', '12:00:00', 25, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM rooms WHERE code = 'POLI-AK'),
    'saturday', '08:00:00', '12:00:00', 15, '2024-01-01'
);

-- Jadwal dr. Michael Brown (Poli Kandungan)
INSERT INTO doctor_schedules (
    doctor_id, polyclinic_id, room_id,
    day_of_week, start_time, end_time, quota, effective_from
) VALUES
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-004'),
    (SELECT id FROM polyclinics WHERE code = 'OBGYN'),
    (SELECT id FROM rooms WHERE code = 'POLI-OBGYN'),
    'tuesday', '08:00:00', '12:00:00', 15, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-004'),
    (SELECT id FROM polyclinics WHERE code = 'OBGYN'),
    (SELECT id FROM rooms WHERE code = 'POLI-OBGYN'),
    'wednesday', '13:00:00', '17:00:00', 15, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-004'),
    (SELECT id FROM polyclinics WHERE code = 'OBGYN'),
    (SELECT id FROM rooms WHERE code = 'POLI-OBGYN'),
    'thursday', '08:00:00', '12:00:00', 15, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-004'),
    (SELECT id FROM polyclinics WHERE code = 'OBGYN'),
    (SELECT id FROM rooms WHERE code = 'POLI-OBGYN'),
    'saturday', '08:00:00', '12:00:00', 10, '2024-01-01'
);

-- Jadwal dr. Sarah Wilson (Poli Mata)
INSERT INTO doctor_schedules (
    doctor_id, polyclinic_id, room_id,
    day_of_week, start_time, end_time, quota, effective_from
) VALUES
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-005'),
    (SELECT id FROM polyclinics WHERE code = 'MATA'),
    (SELECT id FROM rooms WHERE code = 'POLI-MATA'),
    'monday', '13:00:00', '17:00:00', 20, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-005'),
    (SELECT id FROM polyclinics WHERE code = 'MATA'),
    (SELECT id FROM rooms WHERE code = 'POLI-MATA'),
    'tuesday', '13:00:00', '17:00:00', 20, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-005'),
    (SELECT id FROM polyclinics WHERE code = 'MATA'),
    (SELECT id FROM rooms WHERE code = 'POLI-MATA'),
    'thursday', '13:00:00', '17:00:00', 20, '2024-01-01'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-005'),
    (SELECT id FROM polyclinics WHERE code = 'MATA'),
    (SELECT id FROM rooms WHERE code = 'POLI-MATA'),
    'friday', '13:00:00', '17:00:00', 20, '2024-01-01'
);

-- =====================================================
-- Seed Data: Schedule Exceptions (Contoh dokter cuti)
-- =====================================================

INSERT INTO schedule_exceptions (
    doctor_id, exception_date, exception_type, reason
) VALUES
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    '2024-12-25',
    'holiday',
    'Hari Natal'
),
(
    (SELECT id FROM doctors WHERE employee_number = 'DOK-002'),
    '2024-01-20',
    'leave',
    'Cuti pribadi'
);

-- =====================================================
-- Seed Data: Sample Appointments
-- =====================================================

INSERT INTO appointments (
    appointment_number, patient_id, doctor_id, polyclinic_id,
    appointment_date, appointment_time, appointment_type, booking_method, status, chief_complaint
) VALUES
(
    'APT-2024-0001',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    '2024-01-20',
    '09:00:00',
    'follow_up',
    'phone',
    'confirmed',
    'Kontrol setelah sakit'
),
(
    'APT-2024-0002',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-004'),
    (SELECT id FROM polyclinics WHERE code = 'OBGYN'),
    '2024-02-15',
    '10:00:00',
    'follow_up',
    'online',
    'scheduled',
    'Kontrol kehamilan bulan depan'
),
(
    'APT-2024-0003',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0004'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-005'),
    (SELECT id FROM polyclinics WHERE code = 'MATA'),
    '2024-01-18',
    '14:00:00',
    'new',
    'online',
    'confirmed',
    'Periksa mata minus'
),
(
    'APT-2024-0004',
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0005'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    '2024-01-19',
    '08:30:00',
    'checkup',
    'walk_in',
    'scheduled',
    'Cek kesehatan rutin'
);

-- =====================================================
-- Seed Data: Sample Queues (Antrian hari ini)
-- =====================================================

-- Untuk keperluan demo, kita set queue_date = current_date
-- Di production, ini akan otomatis terisi saat registrasi

INSERT INTO queues (
    queue_date, queue_number, patient_id, polyclinic_id, doctor_id,
    queue_type, priority, status, registration_time, counter_number
) VALUES
(
    CURDATE(),
    1,
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0001'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    'walk_in',
    'normal',
    'completed',
    CONCAT(CURDATE(), ' 08:00:00'),
    'A1'
),
(
    CURDATE(),
    2,
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0005'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    'appointment',
    'normal',
    'in_service',
    CONCAT(CURDATE(), ' 08:15:00'),
    'A1'
),
(
    CURDATE(),
    3,
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0004'),
    (SELECT id FROM polyclinics WHERE code = 'DALAM'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-001'),
    'walk_in',
    'normal',
    'waiting',
    CONCAT(CURDATE(), ' 08:30:00'),
    NULL
),
(
    CURDATE(),
    1,
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0003'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    'walk_in',
    'urgent',
    'waiting',
    CONCAT(CURDATE(), ' 08:10:00'),
    NULL
),
(
    CURDATE(),
    2,
    (SELECT id FROM patients WHERE medical_record_number = 'RM-2024-0002'),
    (SELECT id FROM polyclinics WHERE code = 'ANAK'),
    (SELECT id FROM doctors WHERE employee_number = 'DOK-003'),
    'walk_in',
    'normal',
    'waiting',
    CONCAT(CURDATE(), ' 08:25:00'),
    NULL
);

-- =====================================================
-- END OF SCHEDULING & APPOINTMENTS
-- =====================================================