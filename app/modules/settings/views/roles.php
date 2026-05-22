<?php
/**
 * Roles and Permissions Matrix View
 * Shows a visual cross-reference grid mapping all modules and actions to active system roles.
 */

// Module code translations to human-readable Indonesian titles
$moduleNames = [
    'users' => '👥 Manajemen User',
    'roles' => '🔑 Matriks Peran',
    'patient' => '🏥 Data Pasien',
    'patients' => '🏥 Data Pasien',
    'appointment' => '📅 Jadwal & Antrian',
    'medical_record' => '📋 Rekam Medis Elektronik',
    'laboratory' => '🔬 Laboratorium Klinik',
    'pharmacy' => '💊 Apotek & Resep',
    'billing' => '💳 Billing & Kasir',
    'inventory' => '📦 Logistik / BMHP',
    'hr' => '👔 Kepegawaian (HR)',
    'reports' => '📈 Laporan Keuangan & Kunjungan',
    'settings' => '⚙️ Pengaturan Global',
    'audit' => '🔍 Log Audit Keamanan'
];

// Group permissions by module
$groupedPermissions = [];
foreach ($permissions as $perm) {
    $modKey = strtolower($perm['module']);
    $groupedPermissions[$modKey][] = $perm;
}

?>

<div class="roles-container mt-3">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1 class="font-xl font-bold">🔑 Matriks Hak Akses (Role-Based Access Control)</h1>
        <p class="text-muted font-md">Visualisasi pemetaan fungsi operasional dan hak perizinan untuk masing-masing peran standar rumah sakit.</p>
    </div>

    <!-- Info Box -->
    <div class="card accessible-helper-card mb-4" style="background-color: #e7f5ff; border-left: 5px solid #228be6;">
        <div class="card-body" style="padding: 16px;">
            <h3 class="font-md font-bold mb-1" style="color: #1c7ed6;">ℹ️ Standar Keamanan SIMRS</h3>
            <p class="font-sm mb-0" style="color: #495057;">
                Matriks di bawah bersifat <strong>BACA SAJA (Read-Only)</strong> untuk menjamin konsistensi regulasi perizinan rekam medis. Perubahan pemetaan izin hanya dapat dilakukan oleh database administrator melalui konsol migrasi database utama demi alasan keamanan data medis.
            </p>
        </div>
    </div>

    <!-- Matrix Card Grid -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse; text-align: left; border: 1px solid #dee2e6;">
                    <thead>
                        <tr style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6; text-align: center;">
                            <th style="padding: 16px; text-align: left; font-weight: bold; width: 300px;" class="font-md">Modul & Hak Izin (Permissions)</th>
                            <?php foreach ($roles as $role): ?>
                                <th style="padding: 16px; font-weight: bold; min-width: 110px;" class="font-md">
                                    <?= e($role['display_name']) ?><br>
                                    <small class="text-muted font-sm" style="font-weight: normal; font-size: 11px;">(<?= e($role['name']) ?>)</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($groupedPermissions)): ?>
                            <tr>
                                <td colspan="<?= count($roles) + 1 ?>" style="padding: 24px; text-align: center;" class="text-muted font-md">Belum ada data permission yang dimuat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($groupedPermissions as $moduleKey => $perms): ?>
                                <!-- Module Group Header -->
                                <tr style="background-color: #e9ecef; font-weight: bold;">
                                    <td colspan="<?= count($roles) + 1 ?>" style="padding: 12px 16px;" class="font-md">
                                        <strong><?= e($moduleNames[$moduleKey] ?? ucfirst($moduleKey)) ?></strong>
                                    </td>
                                </tr>
                                
                                <?php foreach ($perms as $perm): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 12px 16px 12px 28px;" class="font-md">
                                            <strong><?= e($perm['display_name']) ?></strong><br>
                                            <small class="text-muted font-sm" style="font-size: 12px;"><?= e($perm['name']) ?></small>
                                        </td>
                                        <?php foreach ($roles as $role): ?>
                                            <td style="padding: 12px; text-align: center; vertical-align: middle;" class="font-md">
                                                <?php 
                                                $hasPerm = isset($rolePermissions[$role['id']][$perm['id']]);
                                                if ($hasPerm): 
                                                ?>
                                                    <span class="text-success font-bold" style="color: #2b8a3e; font-size: 20px;" title="Diizinkan">✔</span>
                                                <?php else: ?>
                                                    <span class="text-muted" style="color: #adb5bd; font-size: 18px;" title="Ditolak">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
