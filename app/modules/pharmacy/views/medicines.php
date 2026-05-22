<?php
/**
 * View Master Medicines Catalog
 */
$medicines = $data['medicines'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Katalog & Formularium Obat</h1>
            <p class="page-subtitle text-muted font-md">Daftar obat resmi rumah sakit, nama generik, bentuk sediaan, serta standar harga jual obat.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('pharmacy/prescriptions') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-arrow-left"></i> Kembali ke Antrian
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('pharmacy/medicines') ?>" method="GET" class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Obat / Kode / Nama Generik</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nama obat, kode, generic...">
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="category" class="form-label font-md font-bold">Bentuk Sediaan</label>
                    <select id="category" name="category" class="form-control form-control-accessible">
                        <option value="">-- Semua Sediaan --</option>
                        <option value="tablet" <?= ($filters['category'] ?? '') === 'tablet' ? 'selected' : '' ?>>Tablet</option>
                        <option value="capsule" <?= ($filters['category'] ?? '') === 'capsule' ? 'selected' : '' ?>>Kapsul</option>
                        <option value="syrup" <?= ($filters['category'] ?? '') === 'syrup' ? 'selected' : '' ?>>Sirup / Liquid</option>
                        <option value="injection" <?= ($filters['category'] ?? '') === 'injection' ? 'selected' : '' ?>>Injeksi / Vial</option>
                        <option value="cream" <?= ($filters['category'] ?? '') === 'cream' ? 'selected' : '' ?>>Krim</option>
                        <option value="ointment" <?= ($filters['category'] ?? '') === 'ointment' ? 'selected' : '' ?>>Salep</option>
                        <option value="drop" <?= ($filters['category'] ?? '') === 'drop' ? 'selected' : '' ?>>Tetes Mata/Telinga</option>
                        <option value="inhaler" <?= ($filters['category'] ?? '') === 'inhaler' ? 'selected' : '' ?>>Inhaler</option>
                        <option value="other" <?= ($filters['category'] ?? '') === 'other' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Saring Obat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Medicines Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Formularium Obat Aktif</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md" style="width: 12%;">Kode</th>
                            <th scope="col" class="font-md" style="width: 30%;">Nama Obat / Generik</th>
                            <th scope="col" class="font-md" style="width: 15%;">Sediaan</th>
                            <th scope="col" class="font-md" style="width: 10%;">Satuan</th>
                            <th scope="col" class="font-md text-right" style="width: 15%;">Harga Beli</th>
                            <th scope="col" class="font-md text-right" style="width: 15%;">Harga Jual (Tarif)</th>
                            <th scope="col" class="font-md text-center" style="width: 10%;">Resep</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($medicines)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan data obat dalam katalog.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($medicines as $m): ?>
                                <tr>
                                    <td class="font-bold text-highlight"><?= e($m['code']) ?></td>
                                    <td>
                                        <div class="font-bold"><?= e($m['name']) ?></div>
                                        <small class="text-muted font-bold">Generik: <?= e($m['generic_name'] ?: 'Non-Generik') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-primary text-primary font-bold"><?= e(ucfirst($m['category'])) ?></span>
                                        <small class="text-muted d-block">Kekuatan: <?= e($m['strength'] ?: '-') ?></small>
                                    </td>
                                    <td class="font-bold text-dark"><?= e($m['unit']) ?></td>
                                    <td class="text-right text-muted"><?= formatRupiah($m['purchase_price']) ?></td>
                                    <td class="text-right font-bold text-success">
                                        <?= formatRupiah($m['selling_price']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($m['is_prescription_required']): ?>
                                            <span class="badge bg-soft-danger text-danger font-bold">Wajib</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-secondary text-secondary">Bebas</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
