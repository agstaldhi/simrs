<?php
/**
 * View Medical Record Detail Page (Electronic Medical Record / RME)
 */
$record = $data['record'] ?? [];
$vitalSigns = $data['vitalSigns'] ?? [];
$diagnoses = $data['diagnoses'] ?? [];
$allergies = $data['allergies'] ?? [];
$prescriptions = $data['prescriptions'] ?? [];
$labOrders = $data['labOrders'] ?? [];

// Helper functions for age
$birthDate = new DateTime($record['birth_date']);
$today = new DateTime($record['visit_date'] ?? 'now');
$age = $birthDate->diff($today);

$consciousnessLevels = [
    'compos_mentis' => 'Compos Mentis (Sadar Penuh)',
    'apatis' => 'Apatis (Apatik)',
    'somnolen' => 'Somnolen (Mengantuk)',
    'sopor' => 'Sopor (Stupor)',
    'koma' => 'Koma'
];

$reactionSeverities = [
    'mild' => 'Ringan',
    'moderate' => 'Sedang',
    'severe' => 'Berat'
];
?>

<div class="dashboard-container">
    <!-- Header -->
    <div class="page-header mb-4">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Rekam Medis Elektronik (RME)</h1>
            <p class="page-subtitle text-muted font-md">Nomor Pemeriksaan Kunjungan: <strong class="text-primary"><?= e($record['visit_number'] ?? 'UMUM') ?></strong></p>
        </div>
        <div class="page-action">
            <a href="<?= url('medical-record') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary font-bold font-md">
                <i class="fa fa-print"></i> Cetak Lembar RME
            </button>
        </div>
    </div>

    <!-- Patient Header Card -->
    <div class="card accessible-card mb-4" style="border-left: 5px solid var(--primary-color);">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block font-md">NOMOR REKAM MEDIS</small>
                    <strong class="font-lg text-primary"><?= e($record['medical_record_number']) ?></strong>
                    <small class="text-muted d-block font-md mt-2">NAMA PASIEN</small>
                    <strong class="font-md d-block"><?= e($record['patient_name']) ?> (<?= $record['gender'] === 'male' ? 'L' : 'P' ?>)</strong>
                </div>
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block font-md">UMUR / TANGGAL LAHIR</small>
                    <strong class="font-md d-block"><?= $age->y ?> Tahun, <?= $age->m ?> Bulan / <?= date('d-m-Y', strtotime($record['birth_date'])) ?></strong>
                    <small class="text-muted d-block font-md mt-2">METODE PEMBAYARAN</small>
                    <strong class="font-md d-block text-highlight"><?= strtoupper(e($record['insurance_type'] ?? 'UMUM')) ?></strong>
                </div>
                <div class="col-md-3 border-end">
                    <small class="text-muted d-block font-md">DOKTER PEMERIKSA</small>
                    <strong class="font-md d-block">Dr. <?= e($record['doctor_name']) ?></strong>
                    <small class="text-muted d-block font-md mt-2">SPESIALISASI</small>
                    <strong class="font-md d-block text-muted"><?= e($record['specialization'] ?: 'Dokter Umum') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block font-md">GOLONGAN DARAH</small>
                    <strong class="font-md d-block"><?= e($record['blood_type'] ?? '-') ?></strong>
                    <small class="text-muted d-block font-md mt-2">TANGGAL KUNJUNGAN</small>
                    <strong class="font-md d-block"><?= date('d/m/Y H:i', strtotime($record['created_at'])) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Allergies Warning Block (Clinical Risk Standard) -->
    <?php if (!empty($allergies)): ?>
        <div class="alert alert-danger mb-4 py-3" role="alert" style="background-color: #fce8e6; border-left: 6px solid #d93025; color: #a51d24;">
            <div class="d-flex align-items-center">
                <i class="fa fa-exclamation-triangle font-lg me-3"></i>
                <div>
                    <h4 class="font-md font-bold mb-1" style="color: #a51d24;">PERINGATAN RISIKO KLINIS: RIWAYAT ALERGI PASIEN</h4>
                    <ul class="mb-0 font-md">
                        <?php foreach ($allergies as $allergy): ?>
                            <li>
                                <strong><?= e($allergy['allergen']) ?></strong> (Kategori: <?= ucfirst($allergy['allergy_type']) ?>) - 
                                Reaksi: <em><?= e($allergy['reaction'] ?: 'Tidak spesifik') ?></em> - 
                                Tingkat Keparahan: <strong class="text-danger"><?= $reactionSeverities[$allergy['severity']] ?? ucfirst($allergy['severity']) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left: SOAP details -->
        <div class="col-lg-8">
            <!-- SOAP Card -->
            <div class="card accessible-card mb-4">
                <div class="card-header bg-light border-bottom">
                    <h2 class="card-title font-lg text-primary mb-0"><i class="fa fa-notes-medical"></i> Catatan Klinis SOAP</h2>
                </div>
                <div class="card-body">
                    <!-- S -->
                    <div class="mb-4">
                        <h3 class="font-md font-bold text-primary mb-2" style="border-bottom: 2px solid #e8f0fe; padding-bottom: 5px;">
                            <span class="badge bg-primary text-white me-2">S</span> SUBJEKTIF (Subjective)
                        </h3>
                        <p class="font-md text-dark whitespace-pre-line bg-light p-3 rounded" style="min-height: 80px;"><?= e($record['subjective'] ?: 'Tidak ada keluhan subjektif yang diinput.') ?></p>
                    </div>

                    <!-- O -->
                    <div class="mb-4">
                        <h3 class="font-md font-bold text-success mb-2" style="border-bottom: 2px solid #e6f4ea; padding-bottom: 5px;">
                            <span class="badge bg-success text-white me-2">O</span> OBJEKTIF (Objective)
                        </h3>
                        <p class="font-md text-dark whitespace-pre-line bg-light p-3 rounded mb-3" style="min-height: 80px;"><?= e($record['objective'] ?: 'Tidak ada hasil pemeriksaan objektif yang diinput.') ?></p>
                        
                        <div class="row bg-light p-3 rounded mx-0">
                            <div class="col-md-6 mb-2">
                                <strong>Keadaan Umum:</strong> <span><?= e($record['general_condition'] ?: '-') ?></span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Tingkat Kesadaran:</strong> <span><?= $consciousnessLevels[$record['consciousness_level']] ?? '-' ?></span>
                            </div>
                            <?php if (!empty($record['physical_exam_notes'])): ?>
                                <div class="col-12 mt-2">
                                    <strong>Catatan Pemeriksaan Fisik Tambahan:</strong>
                                    <div class="p-2 border rounded mt-1 bg-white font-md text-muted"><?= e($record['physical_exam_notes']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- A -->
                    <div class="mb-4">
                        <h3 class="font-md font-bold text-warning mb-2" style="border-bottom: 2px solid #fef7e0; padding-bottom: 5px;">
                            <span class="badge bg-warning text-white me-2">A</span> ASESMEN / DIAGNOSA (Assessment)
                        </h3>
                        <p class="font-md text-dark whitespace-pre-line bg-light p-3 rounded mb-3" style="min-height: 60px;"><?= e($record['assessment'] ?: 'Tidak ada interpretasi klinis.') ?></p>
                        
                        <h4 class="font-md font-bold text-muted mb-2">Diagnosa ICD-10 Terkait</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Kode ICD-10</th>
                                        <th>Nama Diagnosa</th>
                                        <th>Tipe Diagnosa</th>
                                        <th>Catatan Dokter</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($diagnoses)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-2 text-muted">Belum ada kode diagnosa yang diinput.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($diagnoses as $diag): ?>
                                            <tr>
                                                <td class="font-bold text-danger"><?= e($diag['icd10_code']) ?></td>
                                                <td class="font-bold"><?= e($diag['diagnosis_name']) ?></td>
                                                <td>
                                                    <span class="badge <?= $diag['diagnosis_type'] === 'primary' ? 'bg-soft-danger text-danger' : 'bg-soft-secondary text-secondary' ?>">
                                                        <?= $diag['diagnosis_type'] === 'primary' ? 'Primer' : 'Sekunder' ?>
                                                    </span>
                                                </td>
                                                <td><small class="text-muted"><?= e($diag['notes'] ?: '-') ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- P -->
                    <div class="mb-2">
                        <h3 class="font-md font-bold text-info mb-2" style="border-bottom: 2px solid #e8f0fe; padding-bottom: 5px;">
                            <span class="badge bg-info text-white me-2">P</span> PENATALAKSANAAN / TERAPI (Plan)
                        </h3>
                        <p class="font-md text-dark whitespace-pre-line bg-light p-3 rounded mb-3" style="min-height: 80px;"><?= e($record['plan'] ?: 'Tidak ada rencana terapi.') ?></p>
                        
                        <div class="row bg-light p-3 rounded mx-0">
                            <?php if (!empty($record['doctor_instructions'])): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>Instruksi Dokter:</strong>
                                    <p class="text-muted mb-0"><?= e($record['doctor_instructions']) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($record['follow_up_plan'])): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>Rencana Kontrol / Rujukan:</strong>
                                    <p class="text-muted mb-0"><?= e($record['follow_up_plan']) ?> 
                                        <?php if ($record['follow_up_date']): ?>
                                            pada tanggal <strong><?= date('d-m-Y', strtotime($record['follow_up_date'])) ?></strong>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Vital Signs & Interventions -->
        <div class="col-lg-4">
            <!-- Vital Signs Card -->
            <div class="card accessible-card mb-4">
                <div class="card-header bg-light border-bottom">
                    <h2 class="card-title font-lg text-primary mb-0"><i class="fa fa-heartbeat"></i> Tanda-tanda Vital</h2>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($vitalSigns)): ?>
                        <div class="text-center py-4 text-muted">Data tanda vital tidak tersedia.</div>
                    <?php else: ?>
                        <table class="table table-striped mb-0 font-md">
                            <tbody>
                                <tr>
                                    <th class="py-3 ps-3">Tekanan Darah</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?= $vitalSigns['blood_pressure_systolic'] ?? '-' ?>/<?= $vitalSigns['blood_pressure_diastolic'] ?? '-' ?> mmHg
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 ps-3">Denyut Nadi (HR)</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?= $vitalSigns['heart_rate'] ?? '-' ?> x/menit
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 ps-3">Frekuensi Nafas (RR)</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?= $vitalSigns['respiratory_rate'] ?? '-' ?> x/menit
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 ps-3">Suhu Tubuh</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?= $vitalSigns['temperature'] ?? '-' ?> &deg;C
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 ps-3">Saturasi Oksigen (SpO2)</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?= $vitalSigns['oxygen_saturation'] ?? '-' ?> %
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 ps-3">Berat / Tinggi Badan</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?= $vitalSigns['weight'] ?? '-' ?> kg / <?= $vitalSigns['height'] ?? '-' ?> cm
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 ps-3">BMI / IMT</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?= $vitalSigns['bmi'] ?? '-' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 ps-3">Skala Nyeri (0-10)</th>
                                    <td class="py-3 text-end pe-3 font-bold">
                                        <?php if ($vitalSigns['pain_scale'] !== null): ?>
                                            <span class="badge <?= $vitalSigns['pain_scale'] > 4 ? 'bg-danger' : 'bg-warning' ?> text-white font-md px-2">
                                                <?= (int)$vitalSigns['pain_scale'] ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <?php if (!empty($vitalSigns['notes'])): ?>
                            <div class="p-3 border-top bg-light">
                                <strong class="d-block mb-1">Catatan Tanda Vital:</strong>
                                <small class="text-muted"><?= e($vitalSigns['notes']) ?></small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Prescriptions Integration -->
            <div class="card accessible-card mb-4">
                <div class="card-header bg-light border-bottom">
                    <h2 class="card-title font-lg text-primary mb-0"><i class="fa fa-prescription-bottle-alt"></i> Resep Farmasi</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($prescriptions)): ?>
                        <p class="text-muted mb-0 font-md">Belum ada resep obat yang diorder dari kunjungan ini.</p>
                    <?php else: ?>
                        <?php foreach ($prescriptions as $pres): ?>
                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong class="text-primary font-md"><?= e($pres['prescription_number']) ?></strong>
                                    <span class="badge bg-soft-info text-info"><?= ucfirst($pres['status']) ?></span>
                                </div>
                                <ul class="list-unstyled mb-0 font-md">
                                    <?php foreach ($pres['items'] as $item): ?>
                                        <li class="mb-1 border-bottom pb-1">
                                            <span class="font-bold text-dark"><?= e($item['medicine_name']) ?></span> (<?= $item['quantity'] ?> <?= e($item['unit_price'] ? 'tablet' : 'pcs') ?>)
                                            <div class="text-muted"><small>Dosis: <?= e($item['dosage']) ?> | <?= e($item['frequency']) ?></small></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lab Orders Integration -->
            <div class="card accessible-card mb-4">
                <div class="card-header bg-light border-bottom">
                    <h2 class="card-title font-lg text-primary mb-0"><i class="fa fa-vials"></i> Hasil Laboratorium</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($labOrders)): ?>
                        <p class="text-muted mb-0 font-md">Belum ada hasil lab yang diorder pada kunjungan ini.</p>
                    <?php else: ?>
                        <?php foreach ($labOrders as $order): ?>
                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong class="text-primary font-md"><?= e($order['order_number']) ?></strong>
                                    <span class="badge bg-soft-success text-success"><?= ucfirst($order['order_status']) ?></span>
                                </div>
                                <ul class="list-unstyled mb-0 font-md">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li class="mb-2 border-bottom pb-1">
                                            <div class="font-bold text-dark"><?= e($item['test_name']) ?></div>
                                            <div class="d-flex justify-content-between font-md">
                                                <span>Hasil: <strong class="<?= $item['result_flag'] !== 'normal' ? 'text-danger' : 'text-success' ?>"><?= e($item['result_value'] ?? '-') ?></strong> <?= e($item['result_unit']) ?></span>
                                                <small class="text-muted">Rujukan: <?= e($item['reference_range']) ?></small>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
