<?php
/**
 * View Pharmacy Stock Levels Monitoring
 */
$stocks = $data['stocks'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Monitoring Stok Obat</h1>
            <p class="page-subtitle text-muted font-md">Informasi persediaan obat per depo/apotek, nomor batch, dan tanggal kedaluwarsa.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('pharmacy/prescriptions') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-arrow-left"></i> Kembali ke Antrian
            </a>
        </div>
    </div>

    <!-- Depo Location Tabs -->
    <div class="d-flex flex-wrap gap-2 mt-4 mb-3 border-bottom pb-2">
        <a href="<?= url('pharmacy/stock?location=main_pharmacy') ?>" class="btn btn-sm <?= ($filters['location'] === 'main_pharmacy') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold mr-2">
            Apotek Utama (Sentral)
        </a>
        <a href="<?= url('pharmacy/stock?location=inpatient_pharmacy') ?>" class="btn btn-sm <?= ($filters['location'] === 'inpatient_pharmacy') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold mr-2">
            Depo Rawat Inap
        </a>
        <a href="<?= url('pharmacy/stock?location=emergency_pharmacy') ?>" class="btn btn-sm <?= ($filters['location'] === 'emergency_pharmacy') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold">
            Depo UGD
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card accessible-card mb-4">
        <div class="card-body py-3">
            <form action="<?= url('pharmacy/stock') ?>" method="GET" class="row align-items-center">
                <input type="hidden" name="location" value="<?= e($filters['location']) ?>">
                <div class="col-md-9 mb-2 mb-md-0">
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= e($filters['search'] ?? '') ?>" 
                            class="form-control form-control-accessible" 
                            placeholder="Cari berdasarkan Nama Obat atau Kode...">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold">
                        <i class="fa fa-search"></i> Cari Stok
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Stok Obat Terdaftar</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">Kode Obat</th>
                            <th scope="col" class="font-md">Nama Obat</th>
                            <th scope="col" class="font-md">Nomor Batch</th>
                            <th scope="col" class="font-md text-center">Jumlah Stok</th>
                            <th scope="col" class="font-md">Satuan</th>
                            <th scope="col" class="font-md">Tanggal Kedaluwarsa</th>
                            <th scope="col" class="font-md text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stocks)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan data stok obat di lokasi ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stocks as $stock): ?>
                                <?php 
                                    $isExpired = strtotime($stock['expiry_date']) < time();
                                    $isNearExpiry = !$isExpired && strtotime($stock['expiry_date']) < strtotime('+6 months');
                                ?>
                                <tr>
                                    <td class="font-bold text-highlight"><?= e($stock['medicine_code']) ?></td>
                                    <td>
                                        <div class="font-bold"><?= e($stock['medicine_name']) ?></div>
                                        <small class="text-muted"><?= e($stock['generic_name'] ?: 'Non-Generik') ?></small>
                                    </td>
                                    <td class="font-bold"><?= e($stock['batch_number']) ?></td>
                                    <td class="text-center font-bold font-lg <?= $stock['quantity'] <= 10 ? 'text-danger' : 'text-primary' ?>">
                                        <?= e($stock['quantity']) ?>
                                    </td>
                                    <td class="font-bold text-muted"><?= e($stock['unit'] ?? 'pcs') ?></td>
                                    <td class="font-bold">
                                        <?= date('d-m-Y', strtotime($stock['expiry_date'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isExpired): ?>
                                            <span class="badge bg-danger text-white font-bold"><i class="fa fa-exclamation-circle"></i> Kadaluwarsa</span>
                                        <?php elseif ($isNearExpiry): ?>
                                            <span class="badge bg-warning text-white font-bold"><i class="fa fa-clock"></i> Hampir Kadaluwarsa</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-success text-success font-bold"><i class="fa fa-check-circle"></i> Aman</span>
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
