<?php
/**
 * Create Inpatient Admission Form View
 */
?>

<div class="inpatient-create-container mt-3" style="max-width: 800px; margin: 0 auto;">
    
    <!-- Page Header -->
    <div class="page-header mb-4">
        <a href="<?= url('inpatient') ?>" style="text-decoration: none; font-weight: bold; color: #1c7ed6; font-size: 14px; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px;">
            ⬅️ Kembali ke Dashboard
        </a>
        <h1 class="font-xl font-bold">🏥 Pendaftaran Admisi Rawat Inap</h1>
        <p class="text-muted font-md">Registrasikan pasien untuk mendapatkan perawatan rawat inap (*opname*) dan alokasi kamar/tempat tidur.</p>
    </div>

    <!-- Admission Form Card -->
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= url('inpatient/store') ?>">
                <?= CSRF::getField() ?>

                <!-- Patient Selection -->
                <div class="form-group mb-3">
                    <label for="patient_id" class="font-md font-bold mb-1 d-block">Pilih Pasien <span style="color: red;">*</span></label>
                    <select id="patient_id" name="patient_id" class="form-control form-control-accessible" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ced4da;" required>
                        <option value="">-- Pilih Pasien Yang Belum Dirawat --</option>
                        <?php foreach ($patients as $pat): ?>
                            <option value="<?= e($pat['id']) ?>" <?= old('patient_id') == $pat['id'] ? 'selected' : '' ?>>
                                <?= e($pat['medical_record_number']) ?> - <?= e($pat['full_name']) ?> (<?= e(calculateAge($pat['birth_date'])) ?> Thn, <?= $pat['gender'] === 'male' ? 'L' : 'P' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Pasien yang ditampilkan adalah pasien aktif yang saat ini tidak terdaftar dalam rawat inap.</small>
                </div>

                <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <!-- Room / Bed Selection -->
                    <div class="form-group">
                        <label for="room_id" class="font-md font-bold mb-1 d-block">Kamar & Tempat Tidur <span style="color: red;">*</span></label>
                        <select id="room_id" name="room_id" class="form-control form-control-accessible" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ced4da;" required>
                            <option value="">-- Pilih Kamar (Bed Tersedia) --</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= e($room['id']) ?>" <?= old('room_id') == $room['id'] ? 'selected' : '' ?>>
                                    <?= e($room['building']) ?> - Lantai <?= e($room['floor']) ?> - <?= e($room['name']) ?> (Sisa Bed: <?= e($room['available_beds']) ?>/<?= e($room['capacity']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Doctor Selection -->
                    <div class="form-group">
                        <label for="doctor_id" class="font-md font-bold mb-1 d-block">Dokter Penanggung Jawab (DPJP) <span style="color: red;">*</span></label>
                        <select id="doctor_id" name="doctor_id" class="form-control form-control-accessible" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ced4da;" required>
                            <option value="">-- Pilih Dokter DPJP --</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?= e($doc['id']) ?>" <?= old('doctor_id') == $doc['id'] ? 'selected' : '' ?>>
                                    <?= e($doc['doctor_name']) ?> (Spesialis: <?= e($doc['specialization']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Chief Complaint -->
                <div class="form-group mb-3">
                    <label for="chief_complaint" class="font-md font-bold mb-1 d-block">Keluhan Utama <span style="color: red;">*</span></label>
                    <textarea id="chief_complaint" name="chief_complaint" class="form-control" rows="3" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ced4da;" placeholder="Ketik alasan utama pasien masuk rawat inap..." required><?= e(old('chief_complaint')) ?></textarea>
                </div>

                <!-- Notes -->
                <div class="form-group mb-4">
                    <label for="notes" class="font-md font-bold mb-1 d-block">Catatan Tambahan (Opsional)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ced4da;" placeholder="Catatan admisi, instruksi dokter pengirim, atau riwayat rujukan..."><?= e(old('notes')) ?></textarea>
                </div>

                <!-- Form Actions -->
                <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #dee2e6; padding-top: 20px;">
                    <a href="<?= url('inpatient') ?>" class="btn btn-light" style="text-decoration: none; padding: 12px 24px; border: 1px solid #ccc; font-weight: bold; background-color: #fff; border-radius: 4px;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-weight: bold; background-color: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                        💾 Simpan & Admisi Pasien
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
