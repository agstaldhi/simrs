<?php
/**
 * View Laporan Kunjungan Bulanan
 */
$monthlyStats = $data['monthlyStats'] ?? [];
$year = $data['year'] ?? date('Y');

// Calculate totals for summary cards
$totalVisits = 0;
$totalOutpatient = 0;
$totalInpatient = 0;
$totalEmergency = 0;

foreach ($monthlyStats as $stat) {
    $totalVisits += $stat['total_visits'];
    $totalOutpatient += $stat['outpatient'];
    $totalInpatient += $stat['inpatient'];
    $totalEmergency += $stat['emergency'];
}
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Laporan Kunjungan Pasien</h1>
            <p class="page-subtitle text-muted font-md">Statistik kunjungan bulanan pasien berdasarkan klasifikasi pelayanan Rawat Jalan, Rawat Inap, dan IGD.</p>
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
            <div class="stat-icon bg-soft-primary text-primary">👥</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Total Kunjungan</span>
                <span class="stat-number font-xl font-bold"><?= number_format($totalVisits) ?> Pasien</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-success text-success">🚶</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Rawat Jalan (Poli)</span>
                <span class="stat-number font-xl font-bold"><?= number_format($totalOutpatient) ?> Pasien</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-warning text-warning">🏥</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">Rawat Inap</span>
                <span class="stat-number font-xl font-bold"><?= number_format($totalInpatient) ?> Pasien</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-danger text-danger">🚨</div>
            <div class="stat-details">
                <span class="stat-label font-md text-muted">IGD (Gawat Darurat)</span>
                <span class="stat-number font-xl font-bold"><?= number_format($totalEmergency) ?> Pasien</span>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('report/monthly') ?>" method="GET" class="row align-items-end">
                <div class="col-md-8 mb-3 mb-md-0">
                    <label for="year" class="form-label font-md font-bold">Pilih Tahun Analisis</label>
                    <select id="year" name="year" class="form-control form-control-accessible">
                        <?php 
                        $currentYear = date('Y');
                        for ($y = $currentYear - 5; $y <= $currentYear; $y++): 
                        ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Saring Tahun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Monthly Statistics Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Tabel Kunjungan Bulanan (Tahun <?= e($year) ?>)</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">Bulan</th>
                            <th scope="col" class="font-md text-center text-success">Rawat Jalan</th>
                            <th scope="col" class="font-md text-center text-warning">Rawat Inap</th>
                            <th scope="col" class="font-md text-center text-danger">IGD</th>
                            <th scope="col" class="font-md text-center font-bold">Total Kunjungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthlyStats as $monthNum => $stat): ?>
                            <tr>
                                <td class="font-bold"><?= e($stat['month_name']) ?></td>
                                <td class="text-center font-bold text-success"><?= number_format($stat['outpatient']) ?> Pasien</td>
                                <td class="text-center font-bold text-warning"><?= number_format($stat['inpatient']) ?> Pasien</td>
                                <td class="text-center font-bold text-danger"><?= number_format($stat['emergency']) ?> Pasien</td>
                                <td class="text-center font-bold text-primary font-lg">
                                    <?= number_format($stat['total_visits']) ?> Pasien
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
