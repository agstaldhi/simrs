<?php
/**
 * View Pharmacy Prescriptions Queue
 */
$prescriptions = $data['prescriptions'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Resep & Pelayanan Obat</h1>
            <p class="page-subtitle text-muted font-md">Kelola antrian resep masuk dari dokter poliklinik, penyiapan obat, verifikasi stok, dan penyerahan obat.</p>
        </div>
        <div class="page-action d-flex">
            <a href="<?= url('pharmacy/stock') ?>" class="btn btn-outline-primary font-bold font-md mr-2">
                <i class="fa fa-boxes"></i> Monitoring Stok Depo
            </a>
            <a href="<?= url('pharmacy/medicines') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-pills"></i> Master Data Obat
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="d-flex flex-wrap gap-2 mt-4 mb-3 border-bottom pb-2">
        <a href="<?= url('pharmacy/prescriptions?status=pending') ?>" class="btn btn-sm <?= ($filters['status'] === 'pending') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold mr-2">
            Antrian Baru (Pending)
        </a>
        <a href="<?= url('pharmacy/prescriptions?status=dispensed') ?>" class="btn btn-sm <?= ($filters['status'] === 'dispensed') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold">
            Sudah Diserahkan (Selesai)
        </a>
    </div>

    <!-- Search Card -->
    <div class="card accessible-card mb-4">
        <div class="card-body py-3">
            <form action="<?= url('pharmacy/prescriptions') ?>" method="GET" class="row align-items-center">
                <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
                <div class="col-md-9 mb-2 mb-md-0">
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= e($filters['search'] ?? '') ?>" 
                            class="form-control form-control-accessible" 
                            placeholder="Cari berdasarkan Nama Pasien, No RM, atau No Resep...">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold">
                        <i class="fa fa-search"></i> Cari Resep
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prescriptions Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Permintaan Resep Masuk</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">No. Resep / Tanggal</th>
                            <th scope="col" class="font-md">Kunjungan / RM</th>
                            <th scope="col" class="font-md">Pasien</th>
                            <th scope="col" class="font-md">Dokter Penulis</th>
                            <th scope="col" class="font-md" style="width: 35%;">Item Obat & Aturan Pakai (Signa)</th>
                            <th scope="col" class="font-md text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prescriptions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ditemukan resep dalam antrian.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prescriptions as $pres): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold text-primary"><?= e($pres['prescription_number']) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($pres['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($pres['visit_number']) ?></div>
                                        <small class="badge bg-soft-secondary text-secondary font-bold">RM: <?= e($pres['medical_record_number']) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($pres['patient_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="font-bold">Dr. <?= e($pres['doctor_name']) ?></div>
                                    </td>
                                    <td>
                                        <ul class="pl-3 mb-0 font-sm">
                                            <?php foreach ($pres['items'] as $item): ?>
                                                <li class="mb-1">
                                                    <strong><?= e($item['medicine_name']) ?></strong> (Qty: <?= e($item['quantity']) ?> <?= e($item['unit'] ?? 'pcs') ?>)
                                                    <div class="text-muted italic">Signa: <?= e($item['instructions'] ?? '-') ?></div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pres['status'] === 'pending'): ?>
                                            <form action="<?= url('pharmacy/prescriptions/dispense/' . $pres['id']) ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin semua obat telah sesuai dan siap diserahkan ke pasien? Stok obat akan dipotong secara otomatis.');">
                                                <?= CSRF::getField() ?>
                                                <button type="submit" class="btn btn-success btn-sm font-bold">
                                                    <i class="fa fa-hand-holding-medical"></i> Serahkan Obat
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-soft-success text-success font-bold"><i class="fa fa-check"></i> Sudah Diserahkan</span>
                                            <small class="text-muted d-block font-sm">Dispensed: <?= date('d/m/Y H:i', strtotime($pres['dispensed_at'])) ?></small>
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
