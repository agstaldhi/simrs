<?php
/**
 * View Billing Invoices List
 */
$invoices = $data['invoices'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Tagihan & Billing Pasien</h1>
            <p class="page-subtitle text-muted font-md">Kelola rincian billing kunjungan pasien, tagihan obat, laboratorium, tindakan medis, dan konsultasi dokter.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('billing/outstanding') ?>" class="btn btn-outline-danger font-bold font-md">
                <i class="fa fa-exclamation-triangle"></i> Daftar Tunggakan / Piutang
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('billing/invoices') ?>" method="GET" class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Pasien / No. Invoice</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nama pasien, No RM, atau No Invoice...">
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="status" class="form-label font-md font-bold">Status Pembayaran</label>
                    <select id="status" name="status" class="form-control form-control-accessible">
                        <option value="">-- Semua Status --</option>
                        <option value="unpaid" <?= ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Belum Dibayar (Unpaid)</option>
                        <option value="partial" <?= ($filters['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Dibayar Sebagian (Partial)</option>
                        <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Lunas (Paid)</option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Saring Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Tagihan Pasien</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">No. Invoice / Tanggal</th>
                            <th scope="col" class="font-md">No. Kunjungan / RM</th>
                            <th scope="col" class="font-md">Nama Pasien</th>
                            <th scope="col" class="font-md text-right">Total Tagihan</th>
                            <th scope="col" class="font-md text-right">Sudah Dibayar</th>
                            <th scope="col" class="font-md text-right">Sisa Tagihan</th>
                            <th scope="col" class="font-md text-center">Status</th>
                            <th scope="col" class="font-md text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ditemukan invoice tagihan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold text-primary"><?= e($inv['invoice_number']) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($inv['invoice_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($inv['visit_number']) ?></div>
                                        <small class="badge bg-soft-secondary text-secondary font-bold">RM: <?= e($inv['medical_record_number']) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($inv['patient_name']) ?></div>
                                        <small class="text-muted font-bold"><?= e($inv['phone'] ?: '-') ?></small>
                                    </td>
                                    <td class="text-right font-bold">
                                        <?= formatRupiah($inv['total_amount']) ?>
                                    </td>
                                    <td class="text-right font-bold text-success">
                                        <?= formatRupiah($inv['paid_amount']) ?>
                                    </td>
                                    <td class="text-right font-bold <?= $inv['outstanding_amount'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                        <?= formatRupiah($inv['outstanding_amount']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($inv['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-soft-success text-success font-bold"><i class="fa fa-check-circle"></i> Lunas</span>
                                        <?php elseif ($inv['payment_status'] === 'partial'): ?>
                                            <span class="badge bg-soft-warning text-warning font-bold">Sebagian</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-danger text-danger font-bold">Belum Bayar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center" style="gap: 5px;">
                                            <a href="<?= url('billing/payments?invoice_id=' . $inv['id']) ?>" class="btn btn-primary btn-sm font-bold" title="Detail & Pembayaran">
                                                <i class="fa fa-cash-register"></i> Detail
                                            </a>
                                            <a href="<?= url('billing/invoices/pdf/' . $inv['id']) ?>" class="btn btn-outline-primary btn-sm font-bold" target="_blank" title="Cetak PDF Invoice">
                                                <i class="fa fa-file-pdf"></i> PDF
                                            </a>
                                        </div>
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
