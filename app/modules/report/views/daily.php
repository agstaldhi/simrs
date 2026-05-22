<?php
/**
 * View Laporan Pendapatan Harian
 */
$revenue = $data['revenue'] ?? [];
$totals = $data['totals'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Laporan Pendapatan Harian</h1>
            <p class="page-subtitle text-muted font-md">Rekapitulasi total penerimaan transaksi kasir harian berdasarkan kategori pembayaran.</p>
        </div>
        <div class="page-action">
            <button onclick="window.print();" class="btn btn-secondary font-bold font-md">
                <i class="fa fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="stats-grid mt-4">
        <div class="stat-card">
            <div class="stat-icon bg-soft-primary text-primary">📅</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Hari Aktif</span>
                <span class="stat-number font-xl font-bold"><?= number_format($totals['active_days'] ?? 0) ?> Hari</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-success text-success">💳</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Total Transaksi</span>
                <span class="stat-number font-xl font-bold"><?= number_format($totals['total_tx'] ?? 0) ?> Trx</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-warning text-warning">💰</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Total Pendapatan</span>
                <span class="stat-number font-xl font-bold"><?= formatRupiah($totals['total_rev'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('report/daily') ?>" method="GET" class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="start_date" class="form-label font-md font-bold">Tanggal Mulai</label>
                    <input 
                        type="date" 
                        id="start_date" 
                        name="start_date" 
                        value="<?= e($filters['start_date'] ?? '') ?>" 
                        class="form-control form-control-accessible">
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="end_date" class="form-label font-md font-bold">Tanggal Akhir</label>
                    <input 
                        type="date" 
                        id="end_date" 
                        name="end_date" 
                        value="<?= e($filters['end_date'] ?? '') ?>" 
                        class="form-control form-control-accessible">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Saring Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Daily Revenue Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Rincian Pendapatan Harian</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">Tanggal</th>
                            <th scope="col" class="font-md">Metode Pembayaran</th>
                            <th scope="col" class="font-md text-center">Jumlah Transaksi</th>
                            <th scope="col" class="font-md text-right">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($revenue)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Tidak ditemukan data transaksi untuk rentang tanggal yang dipilih.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($revenue as $row): ?>
                                <tr>
                                    <td class="font-bold"><?= date('d F Y', strtotime($row['payment_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-soft-primary text-primary font-bold">
                                            <?= strtoupper(e($row['payment_method'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center font-bold"><?= number_format($row['transaction_count']) ?></td>
                                    <td class="text-right font-bold text-success"><?= formatRupiah($row['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
