<?php
/**
 * View Employee Shift Schedules
 */
$shifts = $data['shifts'] ?? [];
$employees = $data['employees'] ?? [];
$shiftTypes = $data['shiftTypes'] ?? [];
$action = $data['action'] ?? 'list';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Jadwal Shift Kerja Pegawai</h1>
            <p class="page-subtitle text-muted font-md">Rencana penugasan jadwal dinas kerja (Pagi, Siang, Malam, Libur) bagi perawat, dokter, dan staf operasional lainnya.</p>
        </div>
        <div class="page-action" style="display: flex; gap: 8px;">
            <?php if ($action === 'list'): ?>
                <a href="<?= url('hr/shifts?action=add') ?>" class="btn btn-primary font-bold font-md">
                    ➕ Jadwalkan Shift Baru
                </a>
            <?php else: ?>
                <a href="<?= url('hr/shifts') ?>" class="btn btn-outline-secondary font-bold font-md">
                    ◀️ Kembali ke Daftar
                </a>
            <?php endif; ?>
            <a href="<?= url('hr/attendance') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-calendar-check"></i> Portal Presensi
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
            <strong><?= $flash['type'] === 'error' ? 'Gagal!' : 'Sukses!' ?></strong> <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- CRUD Form (Inline Page Action) -->
    <?php if ($action === 'add'): ?>
        <div class="card accessible-card border-primary mt-4 mb-4" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">
                <h2 class="card-title font-lg text-white mb-0">📅 Jadwalkan Dinas Kerja Baru</h2>
            </div>
            <div class="card-body">
                <form action="<?= url('hr/shifts/store') ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div class="mb-3">
                        <label for="employee_id" class="form-label font-md font-bold">Pegawai / Staf <span class="text-danger">*</span></label>
                        <select id="employee_id" name="employee_id" class="form-control form-control-accessible" required>
                            <option value="">-- Pilih Pegawai --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= e($emp['full_name']) ?> (NIP: <?= e($emp['employee_number']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="shift_id" class="form-label font-md font-bold">Shift Jaga <span class="text-danger">*</span></label>
                        <select id="shift_id" name="shift_id" class="form-control form-control-accessible" required>
                            <option value="">-- Pilih Shift --</option>
                            <?php foreach ($shiftTypes as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= e($st['name']) ?> (<?= date('H:i', strtotime($st['start_time'])) ?> - <?= date('H:i', strtotime($st['end_time'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="shift_date" class="form-label font-md font-bold">Tanggal Tugas <span class="text-danger">*</span></label>
                        <input type="date" id="shift_date" name="shift_date" value="<?= date('Y-m-d') ?>" class="form-control form-control-accessible" required>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label font-md font-bold">Catatan Penugasan</label>
                        <textarea id="notes" name="notes" rows="3" class="form-control form-control-accessible" placeholder="Contoh: Menggantikan perawat A, dinas di poli anak..."></textarea>
                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary font-bold font-md py-2 px-4">
                            💾 Simpan Jadwal
                        </button>
                        <a href="<?= url('hr/shifts') ?>" class="btn btn-outline-secondary font-bold font-md py-2 px-4" style="text-decoration: none;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Shifts Table -->
        <div class="card accessible-card mt-4">
            <div class="card-header bg-light">
                <h2 class="card-title font-lg text-primary mb-0">Roster Jaga Shift Aktif</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="font-md">Tanggal Tugas</th>
                                <th scope="col" class="font-md">NIP / Nama Karyawan</th>
                                <th scope="col" class="font-md">Unit / Departemen</th>
                                <th scope="col" class="font-md">Nama Shift</th>
                                <th scope="col" class="font-md text-center">Jam Kerja Jaga</th>
                                <th scope="col" class="font-md text-center">Status</th>
                                <th scope="col" class="font-md text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shifts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada roster shift kerja yang dijadwalkan dalam waktu dekat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shifts as $sh): ?>
                                    <tr>
                                        <td class="font-bold text-dark">
                                            <?= date('d/m/Y', strtotime($sh['shift_date'])) ?>
                                            <small class="text-muted d-block"><?= date('l', strtotime($sh['shift_date'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= e($sh['employee_name']) ?></div>
                                            <small class="text-muted font-bold">NIP: <?= e($sh['employee_number']) ?></small>
                                        </td>
                                        <td class="font-bold"><?= e($sh['department_name'] ?: 'Umum') ?></td>
                                        <td>
                                            <span class="badge bg-soft-primary text-primary font-bold">
                                                <?= e($sh['shift_name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center font-bold text-dark">
                                            <?= date('H:i', strtotime($sh['start_time'])) ?> - <?= date('H:i', strtotime($sh['end_time'])) ?> WIB
                                        </td>
                                        <td class="text-center">
                                            <?php if ($sh['status'] === 'completed'): ?>
                                                <span class="badge bg-soft-success text-success font-bold"><i class="fa fa-check"></i> Selesai Jaga</span>
                                            <?php elseif ($sh['status'] === 'scheduled'): ?>
                                                <span class="badge bg-soft-info text-info font-bold">Terjadwal</span>
                                            <?php else: ?>
                                                <span class="badge bg-soft-warning text-warning font-bold"><?= ucfirst($sh['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <form action="<?= url('hr/shifts/delete/' . $sh['id']) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal roster shift ini?');">
                                                <?= CSRF::getField() ?>
                                                <button type="submit" class="btn btn-sm btn-danger font-bold" style="padding: 4px 8px; font-size: 13px; border: none;" title="Hapus jadwal">
                                                    🗑️ Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
