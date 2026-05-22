<?php
/**
 * View Medical Record Create Page (SOAP Assessment)
 */
$visits = $data['visits'] ?? [];
$selectedVisit = $data['selectedVisit'] ?? null;
$allergies = $data['allergies'] ?? [];
$icd10 = $data['icd10'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Pemeriksaan Pasien (SOAP)</h1>
            <p class="page-subtitle text-muted font-md">Input rekam medis elektronik, tanda-tanda vital, dan diagnosa primer pasien.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('medical-record') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php if (!$selectedVisit): ?>
        <!-- Selection List of Waiting Patients -->
        <div class="card accessible-card mt-4">
            <div class="card-header bg-light">
                <h2 class="card-title font-lg text-primary mb-0">Pilih Pasien dari Antrian Aktif</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="font-md">No. Kunjungan</th>
                                <th scope="col" class="font-md">Pasien</th>
                                <th scope="col" class="font-md">Poliklinik / Dokter</th>
                                <th scope="col" class="font-md text-center">Status</th>
                                <th scope="col" class="font-md text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($visits)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Tidak ada kunjungan pasien yang menunggu pemeriksaan saat ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($visits as $v): ?>
                                    <tr>
                                        <td class="font-bold"><?= e($v['visit_number']) ?></td>
                                        <td>
                                            <div class="font-bold"><?= e($v['patient_name']) ?></div>
                                            <small class="text-muted font-bold">No. RM: <?= e($v['medical_record_number']) ?></small>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= e($v['polyclinic_name']) ?></div>
                                            <small class="text-muted">Dr. <?= e($v['doctor_name'] ?? 'Belum Ditentukan') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-soft-warning text-warning"><?= e(ucfirst($v['visit_status'])) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= url('medical-record/create?visit_id=' . $v['id']) ?>" class="btn btn-primary btn-sm font-bold">
                                                <i class="fa fa-stethoscope"></i> Periksa Pasien
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Examination SOAP Form -->
        <div class="row mt-4">
            <!-- Patient Info Summary Panel -->
            <div class="col-lg-4 mb-4">
                <div class="card accessible-card position-sticky" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title font-lg mb-0 text-white">Informasi Pasien</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle-lg bg-soft-primary text-primary font-bold mr-3" style="width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size: 24px;">
                                <?= strtoupper(substr($selectedVisit['patient_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h4 class="font-lg font-bold mb-0"><?= e($selectedVisit['patient_name']) ?></h4>
                                <span class="badge bg-soft-secondary text-secondary font-bold"><?= e($selectedVisit['medical_record_number']) ?></span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <span class="text-muted font-md d-block">Jenis Kelamin / Umur</span>
                            <span class="font-md font-bold"><?= $selectedVisit['gender'] === 'male' ? 'Laki-laki' : 'Perempuan' ?> / <?= calculateAge($selectedVisit['birth_date']) ?> Tahun</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted font-md d-block">Golongan Darah</span>
                            <span class="font-md font-bold"><?= e($selectedVisit['blood_type'] ?? 'Tidak Diketahui') ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted font-md d-block">Poliklinik</span>
                            <span class="font-md font-bold text-primary"><?= e($selectedVisit['polyclinic_name']) ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted font-md d-block">Dokter Pemeriksa</span>
                            <span class="font-md font-bold">Dr. <?= e($selectedVisit['doctor_name']) ?></span>
                        </div>

                        <?php if (!empty($allergies)): ?>
                            <div class="alert alert-danger mt-3 mb-0">
                                <h5 class="font-bold text-danger mb-1"><i class="fa fa-exclamation-triangle"></i> Peringatan Alergi:</h5>
                                <ul class="pl-3 mb-0 font-sm">
                                    <?php foreach ($allergies as $allergy): ?>
                                        <li><strong><?= e($allergy['allergen']) ?></strong> (Severity: <?= e($allergy['severity']) ?>) - <?= e($allergy['reaction']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mt-3 mb-0 font-sm py-2">
                                <i class="fa fa-check-circle"></i> Tidak ada riwayat alergi terdaftar.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Examination Form -->
            <div class="col-lg-8">
                <div class="card accessible-card">
                    <div class="card-header bg-light">
                        <h3 class="card-title font-lg text-primary mb-0">Formulir SOAP & Vital Signs</h3>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('medical-record/store') ?>" method="POST">
                            <?= CSRF::getField() ?>
                            <input type="hidden" name="visit_id" value="<?= e($selectedVisit['id']) ?>">

                            <!-- Tanda Vital Section -->
                            <h4 class="font-lg font-bold text-primary mb-3"><i class="fa fa-heartbeat"></i> 1. Tanda-Tanda Vital (Vital Signs)</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="systolic" class="form-label font-bold">Sistolik (mmHg)</label>
                                    <input type="number" id="systolic" name="systolic" class="form-control" placeholder="Contoh: 120" min="40" max="300">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="diastolic" class="form-label font-bold">Diastolik (mmHg)</label>
                                    <input type="number" id="diastolic" name="diastolic" class="form-control" placeholder="Contoh: 80" min="30" max="200">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="heart_rate" class="form-label font-bold">Denyut Nadi (bpm)</label>
                                    <input type="number" id="heart_rate" name="heart_rate" class="form-control" placeholder="Contoh: 80" min="20" max="250">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="respiratory_rate" class="form-label font-bold">Laju Pernapasan (tpm)</label>
                                    <input type="number" id="respiratory_rate" name="respiratory_rate" class="form-control" placeholder="Contoh: 18" min="8" max="60">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="temperature" class="form-label font-bold">Suhu (°C)</label>
                                    <input type="number" step="0.1" id="temperature" name="temperature" class="form-control" placeholder="Contoh: 36.5" min="30" max="45">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="oxygen_saturation" class="form-label font-bold">Saturasi Oksigen (%)</label>
                                    <input type="number" id="oxygen_saturation" name="oxygen_saturation" class="form-control" placeholder="Contoh: 98" min="50" max="100">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="weight" class="form-label font-bold">Berat Badan (kg)</label>
                                    <input type="number" step="0.1" id="weight" name="weight" class="form-control" placeholder="Contoh: 65">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="height" class="form-label font-bold">Tinggi Badan (cm)</label>
                                    <input type="number" id="height" name="height" class="form-control" placeholder="Contoh: 170">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="pain_scale" class="form-label font-bold">Skala Nyeri (0 - 10)</label>
                                    <select id="pain_scale" name="pain_scale" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        <?php for($i=0; $i<=10; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?> (<?= $i == 0 ? 'Tidak Sakit' : ($i <= 3 ? 'Ringan' : ($i <= 7 ? 'Sedang' : 'Berat')) ?>)</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="vital_notes" class="form-label font-bold">Catatan Tanda Vital Tambahan</label>
                                    <textarea id="vital_notes" name="vital_notes" class="form-control" rows="2" placeholder="Catatan khusus tanda vital..."></textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Pemeriksaan Fisik General -->
                            <h4 class="font-lg font-bold text-primary mb-3"><i class="fa fa-user-md"></i> 2. Kondisi Fisik Umum</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="general_condition" class="form-label font-bold">Keadaan Umum</label>
                                    <input type="text" id="general_condition" name="general_condition" class="form-control" placeholder="Contoh: Tampak Sakit Ringan, Baik">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="consciousness_level" class="form-label font-bold">Tingkat Kesadaran</label>
                                    <select id="consciousness_level" name="consciousness_level" class="form-control">
                                        <option value="compos_mentis">Compos Mentis (Sadar Penuh)</option>
                                        <option value="apatis">Apatis (Acuh Tak Acuh)</option>
                                        <option value="somnolen">Somnolen (Mengantuk)</option>
                                        <option value="sopor">Sopor (Kantuk Dalam)</option>
                                        <option value="koma">Koma (Tidak Sadar)</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="physical_exam_notes" class="form-label font-bold">Catatan Pemeriksaan Fisik (Fokus/Lokal)</label>
                                    <textarea id="physical_exam_notes" name="physical_exam_notes" class="form-control" rows="3" placeholder="Pemeriksaan kepala, dada, abdomen, ekstremitas, dll..."></textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- SOAP Section -->
                            <h4 class="font-lg font-bold text-primary mb-3"><i class="fa fa-file-invoice"></i> 3. Narasi SOAP Pasien</h4>
                            <div class="mb-3">
                                <label for="subjective" class="form-label font-bold"><span class="badge bg-soft-primary text-primary mr-1">S</span> Subjektif (Anamnesis/Keluhan Utama)</label>
                                <textarea id="subjective" name="subjective" class="form-control" rows="3" required placeholder="Keluhan utama, riwayat penyakit sekarang, dll..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="objective" class="form-label font-bold"><span class="badge bg-soft-success text-success mr-1">O</span> Objektif (Hasil Pemeriksaan Fisik & Penunjang)</label>
                                <textarea id="objective" name="objective" class="form-control" rows="3" required placeholder="Hasil pemeriksaan fisik objektif, keadaan luka, dll..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="assessment" class="form-label font-bold"><span class="badge bg-soft-warning text-warning mr-1">A</span> Asesmen (Kesimpulan Medis / Diagnosis)</label>
                                <textarea id="assessment" name="assessment" class="form-control" rows="2" required placeholder="Kesimpulan penilaian klinis..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="plan" class="form-label font-bold"><span class="badge bg-soft-info text-info mr-1">P</span> Rencana Pelaksanaan (Therapy, Tindakan, KIE)</label>
                                <textarea id="plan" name="plan" class="form-control" rows="3" required placeholder="Rencana terapi obat, tindakan medis, edukasi pasien..."></textarea>
                            </div>

                            <hr class="my-4">

                            <!-- Diagnosa ICD-10 Section -->
                            <h4 class="font-lg font-bold text-primary mb-3"><i class="fa fa-notes-medical"></i> 4. Klasifikasi Diagnosa Primer (ICD-10)</h4>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="icd10_code" class="form-label font-bold">Kode ICD-10</label>
                                    <input type="text" id="icd10_code" name="icd10_code" class="form-control" placeholder="Contoh: A90 / K29.7">
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="diagnosis_name" class="form-label font-bold">Nama Diagnosa Resmi</label>
                                    <input type="text" id="diagnosis_name" name="diagnosis_name" class="form-control" required placeholder="Contoh: Dengue Hemorrhagic Fever / Gastritis">
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Tindak Lanjut & Instruksi Dokter -->
                            <h4 class="font-lg font-bold text-primary mb-3"><i class="fa fa-info-circle"></i> 5. Instruksi & Tindak Lanjut</h4>
                            <div class="mb-3">
                                <label for="doctor_instructions" class="form-label font-bold">Instruksi Medis Khusus</label>
                                <textarea id="doctor_instructions" name="doctor_instructions" class="form-control" rows="2" placeholder="Instruksi tambahan untuk perawat atau pasien..."></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="follow_up_plan" class="form-label font-bold">Rencana Tindak Lanjut Pasien</label>
                                    <input type="text" id="follow_up_plan" name="follow_up_plan" class="form-control" placeholder="Contoh: Kontrol poliklinik 1 minggu lagi, Rujuk internal">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="follow_up_date" class="form-label font-bold">Tanggal Kontrol Kembali</label>
                                    <input type="date" id="follow_up_date" name="follow_up_date" class="form-control" min="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="form-actions d-flex justify-content-end mt-4">
                                <a href="<?= url('medical-record') ?>" class="btn btn-outline-secondary font-bold font-md mr-2">Batal</a>
                                <button type="submit" class="btn btn-primary font-bold font-md py-2 px-4">
                                    <i class="fa fa-save"></i> Simpan Rekam Medis (SOAP)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
