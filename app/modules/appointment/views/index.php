<?php
/**
 * View Appointments List Page
 */
$appointments = $data['appointments'] ?? [];
$polyclinics = $data['polyclinics'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Perjanjian Rawat Jalan (Appointment)</h1>
            <p class="page-subtitle text-muted font-md">Kelola jadwal kunjungan dan registrasi awal pasien rawat jalan.</p>
        </div>
        <div>
            <?php if (Auth::can('appointments.create')): ?>
                <a href="<?= url('appointment/create') ?>" class="btn btn-primary btn-accessible-lg">
                    + Buat Perjanjian Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('appointment') ?>" class="d-flex flex-wrap gap-2 align-items-end">
                <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
                    <label for="filter-date" class="form-label font-md font-bold">Tanggal Kunjungan</label>
                    <input 
                        type="date" 
                        id="filter-date" 
                        name="date" 
                        value="<?= e($filters['date']) ?>" 
                        class="form-control form-control-accessible">
                </div>
                <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
                    <label for="filter-poly" class="form-label font-md font-bold">Poliklinik</label>
                    <select id="filter-poly" name="polyclinic_id" class="form-control form-control-accessible">
                        <option value="">-- Semua Poliklinik --</option>
                        <?php foreach ($polyclinics as $poly): ?>
                            <option value="<?= $poly['id'] ?>" <?= $filters['polyclinic_id'] == $poly['id'] ? 'selected' : '' ?>><?= e($poly['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0" style="flex: 1; min-width: 150px;">
                    <label for="filter-status" class="form-label font-md font-bold">Status</label>
                    <select id="filter-status" name="status" class="form-control form-control-accessible">
                        <option value="">-- Semua Status --</option>
                        <option value="scheduled" <?= $filters['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="confirmed" <?= $filters['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="arrived" <?= $filters['status'] === 'arrived' ? 'selected' : '' ?>>Arrived / Check-in</option>
                        <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="no_show" <?= $filters['status'] === 'no_show' ? 'selected' : '' ?>>No Show</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-accessible-lg">Filter</button>
                    <a href="<?= url('appointment') ?>" class="btn btn-secondary btn-accessible-lg">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Jadwal Perjanjian Kunjungan</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--gray-300); text-align: left; background-color: var(--gray-100);">
                            <th class="p-3 font-bold font-md">Nomor / Waktu</th>
                            <th class="p-3 font-bold font-md">Pasien</th>
                            <th class="p-3 font-bold font-md">Poliklinik & Dokter</th>
                            <th class="p-3 font-bold font-md">Keluhan Utama</th>
                            <th class="p-3 font-bold font-md">Status</th>
                            <th class="p-3 font-bold font-md text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-muted font-md">Tidak ada data perjanjian untuk kriteria pencarian ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $apt): ?>
                                <tr style="border-bottom: 1px solid var(--gray-200);">
                                    <td class="p-3 font-md">
                                        <strong class="text-primary font-bold"><?= e($apt['appointment_number']) ?></strong>
                                        <div class="text-muted font-xs mt-1">
                                            <?= date('d-m-Y', strtotime($apt['appointment_date'])) ?> | <?= date('H:i', strtotime($apt['appointment_time'])) ?>
                                        </div>
                                        <span class="badge badge-secondary font-xs" style="margin-top: 5px;"><?= strtoupper(e($apt['booking_method'])) ?></span>
                                    </td>
                                    <td class="p-3 font-md">
                                        <strong class="text-dark"><?= e($apt['patient_name']) ?></strong>
                                        <div class="text-muted font-xs mt-1">RM: <?= e($apt['medical_record_number']) ?></div>
                                        <div class="text-muted font-xs"><?= $apt['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>, <?= TIMESTAMPDIFF_YEAR($apt['birth_date']) ?> Tahun</div>
                                    </td>
                                    <td class="p-3 font-md">
                                        <strong class="text-dark"><?= e($apt['polyclinic_name']) ?></strong>
                                        <div class="text-muted font-xs mt-1">Dr. <?= e($apt['doctor_name']) ?></div>
                                    </td>
                                    <td class="p-3 font-md" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= e($apt['chief_complaint'] ?: '-') ?>
                                    </td>
                                    <td class="p-3 font-md">
                                        <?php
                                        $statusClass = 'background-color: #e5e7eb; color: #1f2937;';
                                        if ($apt['status'] === 'scheduled') {
                                            $statusClass = 'background-color: #dbeafe; color: #1e40af;'; // blue
                                        } elseif ($apt['status'] === 'confirmed') {
                                            $statusClass = 'background-color: #cffafe; color: #0369a1;'; // light blue
                                        } elseif ($apt['status'] === 'arrived') {
                                            $statusClass = 'background-color: #ecfeff; color: #0891b2;'; // cyan
                                        } elseif ($apt['status'] === 'in_progress') {
                                            $statusClass = 'background-color: #fef3c7; color: #d97706;'; // warning/yellow
                                        } elseif ($apt['status'] === 'completed') {
                                            $statusClass = 'background-color: #d1fae5; color: #065f46;'; // green
                                        } elseif ($apt['status'] === 'cancelled') {
                                            $statusClass = 'background-color: #fee2e2; color: #b91c1c;'; // red
                                        } elseif ($apt['status'] === 'no_show') {
                                            $statusClass = 'background-color: #f3f4f6; color: #4b5563;'; // dark gray
                                        }
                                        ?>
                                        <span class="badge font-bold font-sm" style="<?= $statusClass ?> padding: 6px 12px; border-radius: 4px;">
                                            <?= strtoupper(e($apt['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <?php if ($apt['status'] === 'scheduled'): ?>
                                                <form action="<?= url('appointment/update-status/' . $apt['id']) ?>" method="POST" class="d-inline">
                                                    <?= CSRF::getField() ?>
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="btn btn-sm btn-success font-sm">Konfirmasi</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (in_array($apt['status'], ['scheduled', 'confirmed'])): ?>
                                                <form action="<?= url('appointment/update-status/' . $apt['id']) ?>" method="POST" class="d-inline">
                                                    <?= CSRF::getField() ?>
                                                    <input type="hidden" name="status" value="arrived">
                                                    <button type="submit" class="btn btn-sm btn-primary font-sm">Pasien Datang (Check-in)</button>
                                                </form>

                                                <button type="button" class="btn btn-sm btn-danger font-sm" onclick="cancelAppointment(<?= $apt['id'] ?>, '<?= e($apt['appointment_number']) ?>')">Batal</button>
                                            <?php endif; ?>
                                            
                                            <?php if ($apt['status'] === 'cancelled'): ?>
                                                <small class="text-muted font-xs">Batal: <?= e($apt['cancellation_reason'] ?: '-') ?></small>
                                            <?php endif; ?>
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

<!-- Simple cancellation form overlay/box (shows only when clicked) -->
<div id="cancel-box-wrapper" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
    <div class="card accessible-card" style="width: 100%; max-width: 500px; background-color: var(--white); border-radius: var(--border-radius);">
        <div class="card-header bg-light">
            <h3 class="card-title font-lg text-danger mb-0" id="cancel-title">Batalkan Janji Temu</h3>
        </div>
        <div class="card-body">
            <form id="cancel-form" action="" method="POST">
                <?= CSRF::getField() ?>
                <input type="hidden" name="status" value="cancelled">
                
                <div class="form-group mb-4">
                    <label for="cancellation_reason" class="form-label font-md font-bold">Alasan Pembatalan</label>
                    <textarea 
                        id="cancellation_reason" 
                        name="cancellation_reason" 
                        rows="3" 
                        class="form-control form-control-accessible" 
                        placeholder="Contoh: Pasien berhalangan hadir / Dokter ada jadwal darurat"
                        required></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger btn-accessible-lg" style="flex: 1;">Batalkan Janji</button>
                    <button type="button" class="btn btn-secondary btn-accessible-lg" onclick="closeCancelBox()" style="flex: 1;">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cancelAppointment(id, number) {
    document.getElementById('cancel-title').innerText = 'Batalkan Janji Temu: ' + number;
    const form = document.getElementById('cancel-form');
    form.action = '<?= url('appointment/update-status/') ?>' + id;
    
    document.getElementById('cancel-box-wrapper').style.display = 'flex';
}

function closeCancelBox() {
    document.getElementById('cancel-box-wrapper').style.display = 'none';
}
</script>

<?php
// PHP helper logic inside view for age calculation
function TIMESTAMPDIFF_YEAR($birthDate) {
    $birth = new DateTime($birthDate);
    $now = new DateTime();
    return $now->diff($birth)->y;
}
?>
