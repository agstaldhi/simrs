<?php
/**
 * View Billing Outstanding Invoices
 */
$outstanding = $data['outstanding'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Piutang & Tunggakan Pasien</h1>
            <p class="page-subtitle text-muted font-md">Daftar tagihan pasien yang belum lunas atau baru dibayar sebagian, terurut berdasarkan hari keterlambatan.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('billing/invoices') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-list"></i> Kembali ke Semua Tagihan
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('billing/outstanding') ?>" method="GET" class="row align-items-end">
                <div class="col-md-9 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Pasien / No. Invoice</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nama pasien, No RM, atau No Invoice...">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-danger btn-block font-bold font-md py-2">
                        <i class="fa fa-search"></i> Cari Tunggakan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Outstanding Table -->
    <div class="card accessible-card border-danger">
        <div class="card-header bg-soft-danger">
            <h2 class="card-title font-lg text-danger mb-0"><i class="fa fa-exclamation-triangle"></i> Daftar Piutang Aktif</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">No. Invoice / Tanggal</th>
                            <th scope="col" class="font-md">Keterlambatan</th>
                            <th scope="col" class="font-md">No. RM / Pasien</th>
                            <th scope="col" class="font-md">Kontak Pasien</th>
                            <th scope="col" class="font-md text-right">Total Tagihan</th>
                            <th scope="col" class="font-md text-right">Telah Dibayar</th>
                            <th scope="col" class="font-md text-right">Sisa Tunggakan</th>
                            <th scope="col" class="font-md text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($outstanding)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ditemukan tunggakan aktif saat ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($outstanding as $row): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold text-danger"><?= e($row['invoice_number']) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($row['invoice_date'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger font-bold text-white py-1 px-2">
                                            <?= e($row['days_overdue']) ?> Hari
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($row['patient_name']) ?></div>
                                        <small class="badge bg-soft-secondary text-secondary font-bold">RM: <?= e($row['medical_record_number']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= e($row['mobile'] ?: ($row['phone'] ?: '-')) ?></div>
                                    </td>
                                    <td class="text-right font-bold">
                                        <?= formatRupiah($row['total_amount']) ?>
                                    </td>
                                    <td class="text-right font-bold text-success">
                                        <?= formatRupiah($row['paid_amount']) ?>
                                    </td>
                                    <td class="text-right font-bold text-danger">
                                        <?= formatRupiah($row['outstanding_amount']) ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= url('billing/payments?invoice_id=' . $row['id']) ?>" class="btn btn-primary btn-sm font-bold">
                                            <i class="fa fa-cash-register"></i> Bayar
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
</div>
