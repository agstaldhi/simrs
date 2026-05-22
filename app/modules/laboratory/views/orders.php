<?php
/**
 * View Laboratory Orders Queue
 */
$orders = $data['orders'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Antrian Laboratorium</h1>
            <p class="page-subtitle text-muted font-md">Daftar permintaan pemeriksaan laboratorium dari dokter poliklinik/UGD.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('laboratory/templates') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-list"></i> Katalog & Parameter Lab
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="d-flex flex-wrap gap-2 mt-4 mb-3 border-bottom pb-2">
        <a href="<?= url('laboratory/orders?status=pending') ?>" class="btn btn-sm <?= ($filters['status'] === 'pending') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold mr-2">
            Permintaan Baru (Pending)
        </a>
        <a href="<?= url('laboratory/orders?status=in_progress') ?>" class="btn btn-sm <?= ($filters['status'] === 'in_progress') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold mr-2">
            Dalam Proses
        </a>
        <a href="<?= url('laboratory/orders?status=completed') ?>" class="btn btn-sm <?= ($filters['status'] === 'completed') ? 'btn-primary' : 'btn-outline-secondary' ?> font-bold">
            Selesai & Valid
        </a>
    </div>

    <!-- Search Card -->
    <div class="card accessible-card mb-4">
        <div class="card-body py-3">
            <form action="<?= url('laboratory/orders') ?>" method="GET" class="row align-items-center">
                <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
                <div class="col-md-9 mb-2 mb-md-0">
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= e($filters['search'] ?? '') ?>" 
                            class="form-control form-control-accessible" 
                            placeholder="Cari berdasarkan Nama Pasien, No RM, atau No Order...">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold">
                        <i class="fa fa-search"></i> Cari Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Permintaan Uji Laboratorium</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">No. Order / Tanggal</th>
                            <th scope="col" class="font-md">No. Kunjungan / RM</th>
                            <th scope="col" class="font-md">Pasien</th>
                            <th scope="col" class="font-md">Dokter Pengirim</th>
                            <th scope="col" class="font-md">Parameter Pemeriksaan</th>
                            <th scope="col" class="font-md text-center">Prioritas</th>
                            <th scope="col" class="font-md text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan order laboratorium.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold text-primary"><?= e($order['order_number']) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($order['visit_number']) ?></div>
                                        <small class="badge bg-soft-secondary text-secondary font-bold">RM: <?= e($order['medical_record_number']) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($order['patient_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="font-bold">Dr. <?= e($order['doctor_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <span class="badge bg-soft-info text-info font-sm mr-1 mb-1"><?= e($item['test_name']) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($order['priority'] === 'stat' || $order['priority'] === 'urgent'): ?>
                                            <span class="badge bg-danger text-white font-bold">CITO / Cepat</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-success text-success font-bold">Rutin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($order['order_status'] !== 'completed'): ?>
                                            <a href="<?= url('laboratory/results?order_id=' . $order['id']) ?>" class="btn btn-primary btn-sm font-bold">
                                                <i class="fa fa-edit"></i> Input Hasil
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= url('medical-record/detail/' . $order['visit_id']) ?>" class="btn btn-outline-success btn-sm font-bold">
                                                <i class="fa fa-check-circle"></i> Hasil Valid
                                            </a>
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
