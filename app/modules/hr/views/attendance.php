<?php
/**
 * View Employee Attendance & Self Check-In Console
 */
$attendanceToday = $data['attendanceToday'] ?? [];
$myAttendance = $data['myAttendance'] ?? null;
$isEmployeeLinked = $data['isEmployeeLinked'] ?? false;
$filters = $data['filters'] ?? [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Presensi & Absensi Karyawan</h1>
            <p class="page-subtitle text-muted font-md">Portal pencatatan kehadiran harian karyawan rumah sakit dan rekap log check-in/check-out.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('hr/shifts') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-calendar-alt"></i> Roster Shift Kerja
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
            <strong><?= $flash['type'] === 'error' ? 'Gagal!' : 'Sukses!' ?></strong> <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Self Attendance Console -->
    <div class="card accessible-card border-primary mt-4 mb-4">
        <div class="card-header bg-soft-primary">
            <h2 class="card-title font-lg text-primary mb-0"><i class="fa fa-user-clock"></i> Konsol Presensi Mandiri Anda</h2>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="font-md text-muted mb-1">Tanggal Hari Ini</p>
                    <h3 class="font-bold text-dark font-xl"><?= date('l, d F Y') ?></h3>
                    <p class="font-md text-muted mt-2 mb-0">
                        Pukul Jam Kerja: <strong class="text-dark">08:00 - 16:00 WIB</strong>
                    </p>
                </div>

                <div class="col-md-6 text-md-right">
                    <?php if (!$isEmployeeLinked): ?>
                        <div class="alert alert-warning mb-0 text-left font-md">
                            <i class="fa fa-exclamation-triangle"></i> Akun pengguna Anda tidak terhubung ke Profil Pegawai mana pun. Silakan hubungi admin IT untuk menautkan ID User Anda di pengaturan karyawan.
                        </div>
                    <?php else: ?>
                        <form action="<?= url('hr/attendance') ?>" method="POST" class="d-inline">
                            <?= CSRF::getField() ?>

                            <?php if (!$myAttendance): ?>
                                <input type="hidden" name="action" value="check_in">
                                <button type="submit" class="btn btn-success btn-lg px-5 py-3 font-bold font-lg shadow-sm">
                                    <i class="fa fa-sign-in-alt fa-lg"></i> MASUK (Check-In)
                                </button>
                            <?php elseif ($myAttendance['check_out_time'] === null): ?>
                                <input type="hidden" name="action" value="check_out">
                                <div class="mb-3">
                                    <span class="badge bg-soft-success text-success font-bold font-md px-3 py-2 mr-3">
                                        <i class="fa fa-check"></i> Telah Masuk Pukul <?= date('H:i', strtotime($myAttendance['check_in_time'])) ?>
                                    </span>
                                </div>
                                <button type="submit" class="btn btn-danger btn-lg px-5 py-3 font-bold font-lg shadow-sm">
                                    <i class="fa fa-sign-out-alt fa-lg"></i> PULANG (Check-Out)
                                </button>
                            <?php else: ?>
                                <div class="text-left text-md-right">
                                    <span class="badge bg-soft-secondary text-secondary font-bold font-md px-3 py-2 d-inline-block">
                                        <i class="fa fa-check-double"></i> Anda Sudah Melakukan Check-In & Check-Out Hari Ini.
                                    </span>
                                    <div class="mt-2 text-muted font-bold font-md">
                                        Jam Kerja: <?= e($myAttendance['working_hours']) ?> Jam (<?= date('H:i', strtotime($myAttendance['check_in_time'])) ?> - <?= date('H:i', strtotime($myAttendance['check_out_time'])) ?>)
                                    </div>
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Today Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Kehadiran Pegawai Hari Ini</h2>
            <form action="<?= url('hr/attendance') ?>" method="GET" class="d-flex">
                <input 
                    type="text" 
                    name="search" 
                    value="<?= e($filters['search'] ?? '') ?>" 
                    class="form-control form-control-accessible form-control-sm mr-2" 
                    placeholder="Nama, NIP, Departemen...">
                <button type="submit" class="btn btn-primary btn-sm font-bold"><i class="fa fa-search"></i></button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">NIP / Nama</th>
                            <th scope="col" class="font-md">Jabatan</th>
                            <th scope="col" class="font-md">Unit / Departemen</th>
                            <th scope="col" class="font-md">Shift Kerja</th>
                            <th scope="col" class="font-md text-center">Jam Masuk</th>
                            <th scope="col" class="font-md text-center">Jam Pulang</th>
                            <th scope="col" class="font-md text-center">Durasi Kerja</th>
                            <th scope="col" class="font-md text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceToday)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada presensi tercatat untuk hari ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceToday as $row): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold"><?= e($row['full_name']) ?></div>
                                        <small class="text-muted font-bold">NIP: <?= e($row['employee_number']) ?></small>
                                    </td>
                                    <td><?= e($row['position']) ?></td>
                                    <td class="font-bold"><?= e($row['department_name'] ?: 'Umum') ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark font-bold">
                                            <?= e($row['shift_name'] ?: 'Regular') ?>
                                        </span>
                                    </td>
                                    <td class="text-center font-bold text-success">
                                        <?= $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : '-' ?>
                                    </td>
                                    <td class="text-center font-bold text-danger">
                                        <?= $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '-' ?>
                                    </td>
                                    <td class="text-center font-bold">
                                        <?= $row['working_hours'] ? e($row['working_hours']) . ' Jam' : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'present'): ?>
                                            <span class="badge bg-soft-success text-success font-bold">Tepat Waktu</span>
                                        <?php elseif ($row['status'] === 'late'): ?>
                                            <span class="badge bg-soft-warning text-warning font-bold">Terlambat</span>
                                        <?php elseif ($row['status'] === 'leave'): ?>
                                            <span class="badge bg-soft-info text-info font-bold">Cuti Resmi</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-danger text-danger font-bold"><?= strtoupper($row['status']) ?></span>
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
