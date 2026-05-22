<?php
/**
 * View Input Lab Results
 */
$order = $data['order'] ?? null;
$items = $data['items'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Input Hasil Pemeriksaan Laboratorium</h1>
            <p class="page-subtitle text-muted font-md">Masukkan hasil tes laboratorium, tentukan flag nilai rujukan, dan berikan interpretasi klinis.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('laboratory/orders') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-arrow-left"></i> Kembali ke Antrian
            </a>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Patient Summary Card -->
        <div class="col-lg-4 mb-4">
            <div class="card accessible-card position-sticky" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title font-lg mb-0 text-white">Detail Permintaan</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted font-md d-block">Nomor Order Lab</span>
                        <strong class="font-lg text-primary"><?= e($order['order_number']) ?></strong>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted font-md d-block">Pasien</span>
                        <strong class="font-md"><?= e($order['patient_name']) ?></strong>
                        <span class="badge bg-soft-secondary text-secondary ml-1"><?= e($order['medical_record_number']) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted font-md d-block">Jenis Kelamin / Umur</span>
                        <span class="font-md font-bold"><?= $order['gender'] === 'male' ? 'Laki-laki' : 'Perempuan' ?> / <?= calculateAge($order['birth_date']) ?> Tahun</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted font-md d-block">Dokter Pengirim</span>
                        <span class="font-md font-bold">Dr. <?= e($order['doctor_name']) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted font-md d-block">Tanggal Order</span>
                        <span class="font-md"><?= date('d-m-Y H:i', strtotime($order['order_date'])) ?></span>
                    </div>
                    <?php if (!empty($order['clinical_info'])): ?>
                        <div class="alert alert-warning mt-2 mb-0 font-sm py-2">
                            <strong><i class="fa fa-info-circle"></i> Info Klinis Pengirim:</strong><br>
                            <?= e($order['clinical_info']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Input Results Form -->
        <div class="col-lg-8">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h3 class="card-title font-lg text-primary mb-0">Formulir Pengisian Hasil Tes</h3>
                </div>
                <div class="card-body">
                    <form action="<?= url('laboratory/results/input/' . $order['id']) ?>" method="POST">
                        <?= CSRF::getField() ?>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" style="width: 30%;">Parameter Pemeriksaan</th>
                                        <th scope="col" style="width: 25%;">Nilai Hasil</th>
                                        <th scope="col" style="width: 15%;">Satuan</th>
                                        <th scope="col" style="width: 15%;">Nilai Rujukan</th>
                                        <th scope="col" style="width: 15%;">Flag</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td class="font-bold">
                                                <?= e($item['test_name']) ?>
                                                <small class="text-muted d-block">Kategori: <?= e(ucfirst($item['category'])) ?></small>
                                            </td>
                                            <td>
                                                <input 
                                                    type="text" 
                                                    name="results[<?= $item['id'] ?>][value]" 
                                                    value="<?= e($item['result_value'] ?? '') ?>" 
                                                    class="form-control form-control-accessible" 
                                                    placeholder="Ketik hasil pemeriksaan..."
                                                    required>
                                            </td>
                                            <td>
                                                <span class="text-muted font-bold"><?= e($item['result_unit'] ?: $item['template_unit']) ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted font-bold"><?= e($item['reference_range'] ?: $item['template_range']) ?></span>
                                            </td>
                                            <td>
                                                <select name="results[<?= $item['id'] ?>][flag]" class="form-control select-accessible py-1">
                                                    <option value="normal" <?= ($item['result_flag'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normal</option>
                                                    <option value="low" <?= ($item['result_flag'] ?? '') === 'low' ? 'selected' : '' ?>>Low (Rendah)</option>
                                                    <option value="high" <?= ($item['result_flag'] ?? '') === 'high' ? 'selected' : '' ?>>High (Tinggi)</option>
                                                    <option value="critical" <?= ($item['result_flag'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="py-1 bg-soft-light border-top-0">
                                                <div class="row align-items-center">
                                                    <div class="col-md-2 text-md-right">
                                                        <small class="font-bold text-muted">Catatan/Interpretasi:</small>
                                                    </div>
                                                    <div class="col-md-10">
                                                        <input 
                                                            type="text" 
                                                            name="results[<?= $item['id'] ?>][interpretation]" 
                                                            value="<?= e($item['result_interpretation'] ?? '') ?>" 
                                                            class="form-control form-control-sm py-1" 
                                                            placeholder="Catatan interpretasi khusus parameter ini (Opsional)...">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info">
                            <h5 class="font-bold text-primary mb-1"><i class="fa fa-shield-alt"></i> Kebijakan Validasi Laboratorium</h5>
                            <p class="font-sm mb-0">Klik <strong>Simpan Draft</strong> untuk menyimpan data sementara. Klik <strong>Validasi & Selesaikan</strong> untuk melakukan verifikasi oleh Dokter Penanggung Jawab Lab / Patolog Klinis dan memasukkannya ke rekam medis serta billing pasien secara otomatis.</p>
                        </div>

                        <div class="form-actions d-flex justify-content-end mt-4">
                            <a href="<?= url('laboratory/orders') ?>" class="btn btn-outline-secondary font-bold mr-2">Batal</a>
                            <button type="submit" name="action" value="save" class="btn btn-outline-primary font-bold mr-2">
                                <i class="fa fa-save"></i> Simpan Draft
                            </button>
                            <button type="submit" name="action" value="verify" class="btn btn-success font-bold px-4">
                                <i class="fa fa-check-circle"></i> Validasi & Selesaikan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
