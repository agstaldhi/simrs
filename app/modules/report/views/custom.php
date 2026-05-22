<?php
/**
 * View Ekspor Laporan Kustom
 */
$results = $data['results'] ?? [];
$filters = $data['filters'] ?? [];
$reportType = $filters['report_type'] ?? 'visits';
$startDate = $filters['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $filters['end_date'] ?? date('Y-m-d');
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Pencarian & Ekspor Kustom</h1>
            <p class="page-subtitle text-muted font-md">Ekspor data transaksional rumah sakit secara terperinci berdasarkan kriteria rentang tanggal.</p>
        </div>
        <div class="page-action">
            <button onclick="window.print();" class="btn btn-secondary font-bold font-md">
                <i class="fa fa-download"></i> Ekspor PDF / Cetak
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('report/custom') ?>" method="GET" class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="report_type" class="form-label font-md font-bold">Kategori Laporan</label>
                    <select id="report_type" name="report_type" class="form-control form-control-accessible">
                        <option value="visits" <?= $reportType === 'visits' ? 'selected' : '' ?>>Kunjungan Pasien</option>
                        <option value="prescriptions" <?= $reportType === 'prescriptions' ? 'selected' : '' ?>>Resep Farmasi (Apotek)</option>
                        <option value="lab" <?= $reportType === 'lab' ? 'selected' : '' ?>>Order Pemeriksaan Lab</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="start_date" class="form-label font-md font-bold">Mulai Tanggal</label>
                    <input 
                        type="date" 
                        id="start_date" 
                        name="start_date" 
                        value="<?= e($startDate) ?>" 
                        class="form-control form-control-accessible">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="end_date" class="form-label font-md font-bold">Hingga Tanggal</label>
                    <input 
                        type="date" 
                        id="end_date" 
                        name="end_date" 
                        value="<?= e($endDate) ?>" 
                        class="form-control form-control-accessible">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-search"></i> Cari Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dynamic Results Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">
                <?php 
                if ($reportType === 'visits') echo 'Data Kunjungan Pasien';
                elseif ($reportType === 'prescriptions') echo 'Data Resep Farmasi';
                elseif ($reportType === 'lab') echo 'Data Order Laboratorium';
                ?>
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <?php if ($reportType === 'visits'): ?>
                    <!-- Patient Visits Table -->
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="font-md">No. Kunjungan</th>
                                <th scope="col" class="font-md">Tanggal Visit</th>
                                <th scope="col" class="font-md">Nama Pasien / No. RM</th>
                                <th scope="col" class="font-md">Dokter DPJP</th>
                                <th scope="col" class="font-md">Layanan</th>
                                <th scope="col" class="font-md">Penjamin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ditemukan data kunjungan pada rentang tanggal tersebut.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td class="font-bold text-primary"><?= e($row['visit_number']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['visit_date'])) ?></td>
                                        <td>
                                            <div class="font-bold"><?= e($row['patient_name']) ?></div>
                                            <small class="text-muted font-bold">No. RM: <?= e($row['medical_record_number']) ?></small>
                                        </td>
                                        <td><?= e($row['doctor_name'] ?: '-') ?></td>
                                        <td>
                                            <span class="badge bg-soft-info text-info font-bold">
                                                <?= strtoupper(str_replace('_', ' ', $row['visit_type'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-soft-primary text-primary font-bold">
                                                <?= strtoupper(e($row['payment_method'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($reportType === 'prescriptions'): ?>
                    <!-- Prescriptions Table -->
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="font-md">No. Resep</th>
                                <th scope="col" class="font-md">Tanggal Resep</th>
                                <th scope="col" class="font-md">Nama Pasien / No. RM</th>
                                <th scope="col" class="font-md">Dokter Pengirim</th>
                                <th scope="col" class="font-md text-right">Total Biaya</th>
                                <th scope="col" class="font-md text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ditemukan data resep pada rentang tanggal tersebut.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($results as $row): ?>
                                    <?php 
                                        $statusClass = 'bg-soft-warning text-warning';
                                        if ($row['status'] === 'completed' || $row['status'] === 'dispensed') {
                                            $statusClass = 'bg-soft-success text-success';
                                        } elseif ($row['status'] === 'cancelled') {
                                            $statusClass = 'bg-soft-danger text-danger';
                                        }
                                    ?>
                                    <tr>
                                        <td class="font-bold text-primary"><?= e($row['prescription_number']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['prescription_date'])) ?></td>
                                        <td>
                                            <div class="font-bold"><?= e($row['patient_name']) ?></div>
                                            <small class="text-muted font-bold">No. RM: <?= e($row['medical_record_number']) ?></small>
                                        </td>
                                        <td><?= e($row['doctor_name'] ?: '-') ?></td>
                                        <td class="text-right font-bold"><?= formatRupiah($row['total_amount']) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $statusClass ?> font-bold"><?= strtoupper(e($row['status'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($reportType === 'lab'): ?>
                    <!-- Lab Orders Table -->
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="font-md">No. Order Lab</th>
                                <th scope="col" class="font-md">Tanggal Permintaan</th>
                                <th scope="col" class="font-md">Nama Pasien / No. RM</th>
                                <th scope="col" class="font-md">Dokter DPJP</th>
                                <th scope="col" class="font-md text-center">Prioritas</th>
                                <th scope="col" class="font-md text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ditemukan order laboratorium pada rentang tanggal tersebut.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($results as $row): ?>
                                    <?php 
                                        $prioClass = 'bg-soft-info text-info';
                                        if ($row['priority'] === 'urgent') $prioClass = 'bg-soft-warning text-warning';
                                        elseif ($row['priority'] === 'stat') $prioClass = 'bg-soft-danger text-danger';

                                        $statusClass = 'bg-soft-warning text-warning';
                                        if ($row['order_status'] === 'completed') $statusClass = 'bg-soft-success text-success';
                                        elseif ($row['order_status'] === 'cancelled') $statusClass = 'bg-soft-danger text-danger';
                                    ?>
                                    <tr>
                                        <td class="font-bold text-primary"><?= e($row['order_number']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['order_date'])) ?></td>
                                        <td>
                                            <div class="font-bold"><?= e($row['patient_name']) ?></div>
                                            <small class="text-muted font-bold">No. RM: <?= e($row['medical_record_number']) ?></small>
                                        </td>
                                        <td><?= e($row['doctor_name'] ?: '-') ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $prioClass ?> font-bold"><?= strtoupper(e($row['priority'])) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $statusClass ?> font-bold"><?= strtoupper(e($row['order_status'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
