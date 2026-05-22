<?php
/**
 * View Employees List
 */
$employees = $data['employees'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Direktori Kepegawaian RS</h1>
            <p class="page-subtitle text-muted font-md">Daftar lengkap seluruh staf rumah sakit, termasuk dokter spesialis, perawat, apoteker, administrasi, dan staf pendukung.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('hr/attendance') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-calendar-check"></i> Monitor Presensi Kerja
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('hr/employees') ?>" method="GET" class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Karyawan / Unit / Jabatan</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nama, NIP, spesialisasi, poli, departemen...">
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="status" class="form-label font-md font-bold">Status Keaktifan</label>
                    <select id="status" name="status" class="form-control form-control-accessible">
                        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif Bekerja</option>
                        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                        <option value="resigned" <?= ($filters['status'] ?? '') === 'resigned' ? 'selected' : '' ?>>Resign / Keluar</option>
                        <option value="retired" <?= ($filters['status'] ?? '') === 'retired' ? 'selected' : '' ?>>Pensiun</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Saring Pegawai
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Pegawai Aktif</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">NIP / Nama</th>
                            <th scope="col" class="font-md">NIK / Jenis Kelamin</th>
                            <th scope="col" class="font-md">Unit / Departemen</th>
                            <th scope="col" class="font-md">Jabatan / Profesi</th>
                            <th scope="col" class="font-md text-center">Tipe Pegawai</th>
                            <th scope="col" class="font-md">Masa Kerja</th>
                            <th scope="col" class="font-md text-center">Akses Sistem</th>
                            <th scope="col" class="font-md text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ditemukan data pegawai.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold text-primary"><?= e($emp['employee_number']) ?></div>
                                        <div class="font-bold font-lg"><?= e($emp['full_name']) ?></div>
                                        <small class="text-muted"><?= e($emp['email'] ?: '-') ?></small>
                                    </td>
                                    <td>
                                        <div><?= e($emp['nik'] ?: '-') ?></div>
                                        <small class="badge bg-soft-secondary text-secondary font-bold">
                                            <?= $emp['gender'] === 'male' ? 'Laki-laki' : 'Perempuan' ?>
                                        </small>
                                    </td>
                                    <td class="font-bold"><?= e($emp['department_name'] ?: 'Umum') ?></td>
                                    <td><?= e($emp['position']) ?></td>
                                    <td class="text-center font-bold">
                                        <span class="badge bg-light text-dark">
                                            <?= strtoupper($emp['employment_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?= e($emp['years_of_service']) ?> Tahun</div>
                                        <small class="text-muted">Mulai: <?= date('d/m/Y', strtotime($emp['join_date'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($emp['has_system_access']): ?>
                                            <span class="badge bg-soft-info text-info font-bold"><i class="fa fa-key"></i> Ya (<?= e($emp['username']) ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">Tidak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($emp['employment_status'] === 'active'): ?>
                                            <span class="badge bg-soft-success text-success font-bold">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-danger text-danger font-bold"><?= strtoupper($emp['employment_status']) ?></span>
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
