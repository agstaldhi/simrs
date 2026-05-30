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
            <h3 class="font-md font-bold mb-1" style="color: #1c7ed6;">ℹ️ Manajemen Hak Akses (Role-Based Access Control)</h3>
            <p class="font-sm mb-0" style="color: #495057;">
                Matriks di bawah dapat langsung diubah oleh Administrator. Perubahan hak akses akan disimpan secara real-time saat Anda mencentang atau mengosongkan kotak pilihan.
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
                                                ?>
                                                <input type="checkbox" 
                                                       class="permission-checkbox" 
                                                       data-role-id="<?= (int)$role['id'] ?>" 
                                                       data-permission-id="<?= (int)$perm['id'] ?>" 
                                                       <?= $hasPerm ? 'checked' : '' ?>
                                                       style="width: 18px; height: 18px; cursor: pointer; vertical-align: middle;"
                                                       onchange="handlePermissionToggle(this)">
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

<script>
function handlePermissionToggle(checkbox) {
    const roleId = checkbox.getAttribute('data-role-id');
    const permissionId = checkbox.getAttribute('data-permission-id');
    const assign = checkbox.checked ? 1 : 0;
    
    // Disable checkbox temporarily during fetch
    checkbox.disabled = true;
    
    const formData = new FormData();
    formData.append('role_id', roleId);
    formData.append('permission_id', permissionId);
    formData.append('assign', assign);
    formData.append('_token', '<?= CSRF::getToken() ?>');
    
    fetch('<?= url("settings/roles/toggle-permission") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        checkbox.disabled = false;
        if (!data.success) {
            alert(data.message || 'Gagal memperbarui hak akses.');
            // Revert state
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(err => {
        checkbox.disabled = false;
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
        // Revert state
        checkbox.checked = !checkbox.checked;
    });
}
</script>
</div>
