<?php
/**
 * View Laporan Rekapitulasi Keuangan
 */
$incomeDetails = $data['incomeDetails'] ?? [];
$paymentMethods = $data['paymentMethods'] ?? [];
$filters = $data['filters'] ?? [];
$month = (int)($filters['month'] ?? date('m'));
$year = (int)($filters['year'] ?? date('Y'));

$monthsIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Calculate total revenue from categories
$totalCategoryIncome = 0;
foreach ($incomeDetails as $detail) {
    $totalCategoryIncome += $detail['total_amount'];
}

// Calculate total payment received from payment methods
$totalPaymentReceived = 0;
foreach ($paymentMethods as $method) {
    $totalPaymentReceived += $method['total_amount'];
}
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Laporan Rekapitulasi Keuangan</h1>
            <p class="page-subtitle text-muted font-md">Analisis performa finansial berdasarkan jenis layanan (Apotek, Laboratorium, Konsultasi) dan kanal pembayaran.</p>
        </div>
        <div class="page-action">
            <button onclick="window.print();" class="btn btn-secondary font-bold font-md">
                <i class="fa fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="stats-grid mt-4">
        <div class="stat-card">
            <div class="stat-icon bg-soft-success text-success">💵</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Omset Tindakan & Layanan</span>
                <span class="stat-number font-xl font-bold"><?= formatRupiah($totalCategoryIncome) ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-primary text-primary">💳</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Dana Kasir Diterima</span>
                <span class="stat-number font-xl font-bold"><?= formatRupiah($totalPaymentReceived) ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-info text-info">📊</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Periode Analisis</span>
                <span class="stat-number font-xl font-bold"><?= e($monthsIndo[$month] ?? '') ?> <?= e($year) ?></span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('report/financial') ?>" method="GET" class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="month" class="form-label font-md font-bold">Bulan</label>
                    <select id="month" name="month" class="form-control form-control-accessible">
                        <?php foreach ($monthsIndo as $num => $name): ?>
                            <option value="<?= sprintf('%02d', $num) ?>" <?= $num == $month ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="year" class="form-label font-md font-bold">Tahun</label>
                    <select id="year" name="year" class="form-control form-control-accessible">
                        <?php 
                        $currentYear = date('Y');
                        for ($y = $currentYear - 5; $y <= $currentYear; $y++): 
                        ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Saring Keuangan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tables Grid -->
    <div class="row">
        <!-- Revenue by Service/Item Type -->
        <div class="col-md-6 mb-4">
            <div class="card accessible-card h-100">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Pendapatan Berdasarkan Layanan</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="font-md">Jenis Layanan</th>
                                    <th scope="col" class="font-md text-center">Jumlah Item</th>
                                    <th scope="col" class="font-md text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($incomeDetails)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Tidak ada transaksi terbayar pada periode ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($incomeDetails as $detail): ?>
                                        <?php 
                                            // Format Indonesian friendly labels
                                            $label = str_replace('_', ' ', $detail['item_type']);
                                            $label = ucwords($label);
                                            if ($detail['item_type'] === 'medicine') $label = 'Apotek / Farmasi';
                                            if ($detail['item_type'] === 'laboratory') $label = 'Laboratorium';
                                            if ($detail['item_type'] === 'consultation') $label = 'Konsultasi Dokter';
                                            if ($detail['item_type'] === 'registration') $label = 'Pendaftaran Pasien';
                                            if ($detail['item_type'] === 'treatment') $label = 'Tindakan Medis';
                                        ?>
                                        <tr>
                                            <td class="font-bold text-primary"><?= e($label) ?></td>
                                            <td class="text-center"><?= number_format($detail['item_count']) ?> kali</td>
                                            <td class="text-right font-bold"><?= formatRupiah($detail['total_amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue by Payment Method -->
        <div class="col-md-6 mb-4">
            <div class="card accessible-card h-100">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Pendapatan Berdasarkan Metode Bayar</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="font-md">Kanal Pembayaran</th>
                                    <th scope="col" class="font-md text-center">Transaksi</th>
                                    <th scope="col" class="font-md text-right">Total Setoran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paymentMethods)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Tidak ada dana masuk pada periode ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <tr>
                                            <td class="font-bold">
                                                <span class="badge bg-soft-info text-info font-bold">
                                                    <?= strtoupper(e($method['payment_method'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?= number_format($method['tx_count']) ?> Trx</td>
                                            <td class="text-right font-bold text-success"><?= formatRupiah($method['total_amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
