<?php
/**
 * View Laboratory templates catalog
 */
$templates = $data['templates'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Katalog Parameter Pemeriksaan Laboratorium</h1>
            <p class="page-subtitle text-muted font-md">Daftar jenis tes laboratorium yang didukung, nilai rujukan normal, satuan, serta harga pemeriksaan.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('laboratory/orders') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-arrow-left"></i> Kembali ke Antrian
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('laboratory/templates') ?>" method="GET" class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Nama Tes / Kode</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nama atau kode pemeriksaan...">
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="category" class="form-label font-md font-bold">Kategori Pemeriksaan</label>
                    <select id="category" name="category" class="form-control form-control-accessible">
                        <option value="">-- Semua Kategori --</option>
                        <option value="hematology" <?= ($filters['category'] ?? '') === 'hematology' ? 'selected' : '' ?>>Hematologi</option>
                        <option value="clinical_chemistry" <?= ($filters['category'] ?? '') === 'clinical_chemistry' ? 'selected' : '' ?>>Kimia Klinik</option>
                        <option value="immunology" <?= ($filters['category'] ?? '') === 'immunology' ? 'selected' : '' ?>>Imunologi / Serologi</option>
                        <option value="microbiology" <?= ($filters['category'] ?? '') === 'microbiology' ? 'selected' : '' ?>>Mikrobiologi</option>
                        <option value="urinalysis" <?= ($filters['category'] ?? '') === 'urinalysis' ? 'selected' : '' ?>>Urinanalisis</option>
                        <option value="other" <?= ($filters['category'] ?? '') === 'other' ? 'selected' : '' ?>>Kategori Lain</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Filter Katalog
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Parameter Lab Aktif</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md" style="width: 12%;">Kode</th>
                            <th scope="col" class="font-md" style="width: 25%;">Nama Pemeriksaan</th>
                            <th scope="col" class="font-md" style="width: 15%;">Kategori</th>
                            <th scope="col" class="font-md" style="width: 15%;">Bahan / Sampel</th>
                            <th scope="col" class="font-md">Nilai Rujukan / Satuan</th>
                            <th scope="col" class="font-md text-right" style="width: 15%;">Tarif / Harga</th>
                            <th scope="col" class="font-md text-center" style="width: 10%;">Puasa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan parameter laboratorium dalam katalog.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($templates as $t): ?>
                                <tr>
                                    <td class="font-bold text-highlight"><?= e($t['code']) ?></td>
                                    <td>
                                        <div class="font-bold"><?= e($t['name']) ?></div>
                                        <small class="text-muted"><?= e($t['description'] ?: 'Tidak ada deskripsi tambahan') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-primary text-primary font-bold"><?= e(ucfirst(str_replace('_', ' ', $t['category']))) ?></span>
                                    </td>
                                    <td>
                                        <span class="font-bold"><i class="fa fa-vial text-danger mr-1"></i> <?= e($t['sample_type'] ?: 'Darah') ?></span>
                                    </td>
                                    <td>
                                        <div class="font-bold text-dark"><?= e($t['reference_range'] ?: '-') ?></div>
                                        <small class="text-muted font-bold">Satuan: <?= e($t['unit'] ?: '-') ?></small>
                                    </td>
                                    <td class="text-right font-bold text-success">
                                        <?= formatRupiah($t['price']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['requires_fasting']): ?>
                                            <span class="badge bg-soft-warning text-warning font-bold"><i class="fa fa-clock"></i> Ya (10-12 Jam)</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-secondary text-secondary">Tidak</span>
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
