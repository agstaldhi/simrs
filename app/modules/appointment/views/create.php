<?php
/**
 * Create Appointment Page
 */
$patients = $data['patients'] ?? [];
$doctors = $data['doctors'] ?? [];
$polyclinics = $data['polyclinics'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Buat Perjanjian Baru</h1>
            <p class="page-subtitle text-muted font-md">Daftarkan kunjungan berobat pasien untuk jadwal rawat jalan.</p>
        </div>
    </div>

    <div class="row mt-4 justify-content-center">
        <div class="col-lg-8 mb-4">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Formulir Registrasi Kunjungan</h2>
                </div>
                <div class="card-body">
                    <form action="<?= url('appointment/store') ?>" method="POST" class="accessible-form">
                        <?= CSRF::getField() ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label font-md font-bold">Pasien Terdaftar <span class="text-danger">*</span></label>
                                <select id="patient_id" name="patient_id" class="form-control form-control-accessible" required>
                                    <option value="">-- Pilih Pasien --</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?= $patient['id'] ?>">
                                            [RM: <?= e($patient['medical_record_number']) ?>] <?= e($patient['full_name']) ?> - Tgl Lahir: <?= date('d/m/Y', strtotime($patient['birth_date'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Jika pasien belum terdaftar, silakan <a href="<?= url('patient/create') ?>" target="_blank" class="text-highlight">Registrasi Pasien Baru</a> terlebih dahulu.</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="polyclinic_id" class="form-label font-md font-bold">Poliklinik Tujuan <span class="text-danger">*</span></label>
                                <select id="polyclinic_id" name="polyclinic_id" class="form-control form-control-accessible" required>
                                    <option value="">-- Pilih Poliklinik --</option>
                                    <?php foreach ($polyclinics as $poly): ?>
                                        <option value="<?= $poly['id'] ?>"><?= e($poly['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label font-md font-bold">Dokter Spesialis <span class="text-danger">*</span></label>
                                <select id="doctor_id" name="doctor_id" class="form-control form-control-accessible" required>
                                    <option value="">-- Pilih Dokter --</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?= $doc['id'] ?>">Dr. <?= e($doc['doctor_name']) ?> (<?= e($doc['specialization'] ?: 'Umum') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="appointment_type" class="form-label font-md font-bold">Jenis Kunjungan</label>
                                <select id="appointment_type" name="appointment_type" class="form-control form-control-accessible">
                                    <option value="new">Kunjungan Baru (Pertama Kali)</option>
                                    <option value="follow_up">Kontrol Rutin / Follow Up</option>
                                    <option value="consultation">Konsultasi Rujukan</option>
                                    <option value="checkup">Medical Check-Up</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="appointment_date" class="form-label font-md font-bold">Tanggal Perjanjian <span class="text-danger">*</span></label>
                                <input 
                                    type="date" 
                                    id="appointment_date" 
                                    name="appointment_date" 
                                    value="<?= date('Y-m-d') ?>" 
                                    class="form-control form-control-accessible" 
                                    min="<?= date('Y-m-d') ?>"
                                    required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="appointment_time" class="form-label font-md font-bold">Waktu Praktek / Jam <span class="text-danger">*</span></label>
                                <input 
                                    type="time" 
                                    id="appointment_time" 
                                    name="appointment_time" 
                                    value="08:00" 
                                    class="form-control form-control-accessible" 
                                    required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="booking_method" class="form-label font-md font-bold">Metode Pendaftaran</label>
                                <select id="booking_method" name="booking_method" class="form-control form-control-accessible">
                                    <option value="walk_in">Walk-in (Datang Langsung)</option>
                                    <option value="phone">Telepon / Chat</option>
                                    <option value="online">Online App / Website</option>
                                    <option value="referral">Rujukan Faskes Lain</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="chief_complaint" class="form-label font-md font-bold">Keluhan Utama / Diagnosa Awal <span class="text-danger">*</span></label>
                            <textarea 
                                id="chief_complaint" 
                                name="chief_complaint" 
                                rows="3" 
                                class="form-control form-control-accessible" 
                                placeholder="Tulis keluhan utama yang dirasakan pasien..." 
                                required></textarea>
                        </div>

                        <div class="form-group mb-4">
                            <label for="notes" class="form-label font-md font-bold">Catatan Tambahan (Opsional)</label>
                            <textarea 
                                id="notes" 
                                name="notes" 
                                rows="2" 
                                class="form-control form-control-accessible" 
                                placeholder="Catatan internal resepsionis, alergi obat penting, dll..."></textarea>
                        </div>

                        <div class="form-actions d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-accessible-lg" style="flex: 1;">
                                Jadwalkan Perjanjian
                            </button>
                            <a href="<?= url('appointment') ?>" class="btn btn-secondary btn-accessible-lg" style="flex: 1;">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
