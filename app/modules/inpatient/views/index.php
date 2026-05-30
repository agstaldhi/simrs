<?php
/**
 * Inpatient (Rawat Inap) Dashboard View
 */
?>

<div class="inpatient-container mt-3">
    
    <!-- Page Header -->
    <div class="page-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="font-xl font-bold">🏥 Pelayanan Rawat Inap (Ranap)</h1>
            <p class="text-muted font-md">Kelola pasien rawat inap, monitoring kapasitas bed (*Bed Occupancy Rate*), dan asuhan keperawatan.</p>
        </div>
        
        <div style="display: flex; gap: 8px;">
            <a href="<?= url('inpatient/beds') ?>" class="btn btn-light btn-accessible-lg" style="text-decoration: none; padding: 12px 20px; border: 1px solid #ccc; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; background-color: #f8f9fa;">
                🛏️ Status Bed
            </a>
            <?php if (in_array('patients.create', $_SESSION['permissions'] ?? [])): ?>
                <a href="<?= url('inpatient/create') ?>" class="btn btn-primary btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; color: #fff; background-color: #007bff; border-radius: 4px;">
                    ➕ Admisi Rawat Inap
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <!-- BOR Card -->
        <div class="card shadow-sm" style="background-color: #e3faf2; border-left: 5px solid #0ca678; border-radius: 6px; padding: 16px;">
            <span class="text-muted font-sm font-bold d-block" style="color: #0ca678 !important;">BED OCCUPANCY RATE (BOR)</span>
            <h2 class="font-xxl font-bold mt-2" style="color: #0ca678;"><?= e($stats['bor']) ?>%</h2>
            <small class="text-muted font-sm">Efektivitas penggunaan tempat tidur rawat inap.</small>
        </div>
        <!-- Active Patients Card -->
        <div class="card shadow-sm" style="background-color: #e7f5ff; border-left: 5px solid #1c7ed6; border-radius: 6px; padding: 16px;">
            <span class="text-muted font-sm font-bold d-block" style="color: #1c7ed6 !important;">PASIEN AKTIF RAWAT INAP</span>
            <h2 class="font-xxl font-bold mt-2" style="color: #1c7ed6;"><?= e($stats['occupied_beds']) ?></h2>
            <small class="text-muted font-sm">Pasien yang saat ini sedang dirawat.</small>
        </div>
        <!-- Available Beds Card -->
        <div class="card shadow-sm" style="background-color: #fff4e6; border-left: 5px solid #f76707; border-radius: 6px; padding: 16px;">
            <span class="text-muted font-sm font-bold d-block" style="color: #f76707 !important;">TEMPAT TIDUR KOSONG</span>
            <h2 class="font-xxl font-bold mt-2" style="color: #f76707;"><?= e($stats['available_beds']) ?></h2>
            <small class="text-muted font-sm">Kapasitas kosong siap diisi pasien.</small>
        </div>
        <!-- Total Capacity Card -->
        <div class="card shadow-sm" style="background-color: #f8f9fa; border-left: 5px solid #495057; border-radius: 6px; padding: 16px;">
            <span class="text-muted font-sm font-bold d-block" style="color: #495057 !important;">TOTAL KAPASITAS BED</span>
            <h2 class="font-xxl font-bold mt-2" style="color: #495057;"><?= e($stats['total_beds']) ?></h2>
            <small class="text-muted font-sm">Jumlah seluruh tempat tidur rawat inap.</small>
        </div>
    </div>

    <!-- Active Admissions Table Card -->
    <div class="card shadow-sm">
        <div class="card-header p-3" style="background-color: #f1f3f5; border-bottom: 1px solid #dee2e6;">
            <h3 class="font-md font-bold mb-0">📋 Daftar Pasien Aktif Rawat Inap</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 16px; font-weight: bold;" class="font-md">No. Registrasi</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">No. RM</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Nama Pasien</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Kamar / Bed</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Tanggal Masuk</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Dokter DPJP</th>
                            <th style="padding: 16px; font-weight: bold; text-align: center;" class="font-md">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admissions)): ?>
                            <tr>
                                <td colspan="7" style="padding: 32px; text-align: center;" class="text-muted font-md">
                                    Tidak ada pasien aktif di rawat inap saat ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admissions as $adm): ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 16px;" class="font-md">
                                        <span style="font-family: monospace; color: #495057; font-weight: bold;"><?= e($adm['visit_number']) ?></span>
                                    </td>
                                    <td style="padding: 16px;" class="font-md">
                                        <span class="badge" style="background-color: #e7f5ff; color: #1c7ed6; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                                            <?= e($adm['medical_record_number']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 16px;" class="font-md">
                                        <strong><?= e($adm['patient_name']) ?></strong>
                                        <span class="text-muted font-sm d-block"><?= $adm['gender'] === 'male' ? 'Laki-laki' : 'Perempuan' ?> (<?= e($adm['age']) ?> Thn)</span>
                                    </td>
                                    <td style="padding: 16px;" class="font-md">
                                        <strong style="color: #2b8a3e;"><?= e($adm['room_name']) ?></strong>
                                        <span class="text-muted font-sm d-block">Kode: <?= e($adm['room_code']) ?> (Lantai <?= e($adm['floor']) ?> - <?= e($adm['building']) ?>)</span>
                                    </td>
                                    <td style="padding: 16px;" class="font-md">
                                        <?= date('d-m-Y H:i', strtotime($adm['visit_date'])) ?>
                                    </td>
                                    <td style="padding: 16px;" class="font-md">
                                        <?= e($adm['doctor_name']) ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;" class="font-md">
                                        <a href="<?= url('inpatient/detail/' . $adm['id']) ?>" class="btn btn-info" style="text-decoration: none; padding: 8px 16px; font-size: 14px; font-weight: bold; background-color: #17a2b8; color: #fff; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                            📝 Asuhan & SOAP
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
