<?php
/**
 * View Employee Shift Schedules
 */
$shifts = $data['shifts'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Jadwal Shift Kerja Pegawai</h1>
            <p class="page-subtitle text-muted font-md">Rencana penugasan jadwal dinas kerja (Pagi, Siang, Malam, Libur) bagi perawat, dokter, dan staf operasional lainnya.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('hr/attendance') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-calendar-check"></i> Portal Presensi
            </a>
        </div>
    </div>

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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shifts)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada roster shift kerja yang dijadwalkan dalam waktu dekat.</td>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
