<?php
/**
 * View Employees List & CRUD
 */
$employees = $data['employees'] ?? [];
$departments = $data['departments'] ?? [];
$action = $data['action'] ?? 'list';
$editingEmployee = $data['editingEmployee'] ?? null;
$filters = $data['filters'] ?? [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Direktori Kepegawaian RS</h1>
            <p class="page-subtitle text-muted font-md">Daftar lengkap seluruh staf rumah sakit, termasuk dokter spesialis, perawat, apoteker, administrasi, dan staf pendukung.</p>
        </div>
        <div class="page-action" style="display: flex; gap: 8px;">
            <?php if ($action === 'list'): ?>
                <a href="<?= url('hr/employees?action=add') ?>" class="btn btn-primary font-bold font-md">
                    ➕ Tambah Pegawai Baru
                </a>
            <?php else: ?>
                <a href="<?= url('hr/employees') ?>" class="btn btn-outline-secondary font-bold font-md">
                    ◀️ Kembali ke Daftar
                </a>
            <?php endif; ?>
            <a href="<?= url('hr/attendance') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-calendar-check"></i> Monitor Presensi Kerja
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
            <strong><?= $flash['type'] === 'error' ? 'Perhatian!' : 'Sukses!' ?></strong> <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- CRUD Form (Inline Page Action) -->
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <?php
        $isEdit = ($action === 'edit');
        $formAction = $isEdit ? url('hr/employees/update/' . $editingEmployee['id']) : url('hr/employees/store');
        $formTitle = $isEdit ? '📝 Edit Data Pegawai' : '📝 Pendaftaran Pegawai Baru';
        $submitBtnText = $isEdit ? '💾 Simpan Perubahan' : '💾 Daftarkan Pegawai';
        
        $valFullName = $isEdit ? $editingEmployee['full_name'] : '';
        $valNik = $isEdit ? $editingEmployee['nik'] : '';
        $valBirthPlace = $isEdit ? $editingEmployee['birth_place'] : '';
        $valBirthDate = $isEdit ? $editingEmployee['birth_date'] : '';
        $valGender = $isEdit ? $editingEmployee['gender'] : 'male';
        $valPosition = $isEdit ? $editingEmployee['position'] : '';
        $valDeptId = $isEdit ? $editingEmployee['department_id'] : '';
        $valJoinDate = $isEdit ? $editingEmployee['join_date'] : date('Y-m-d');
        $valType = $isEdit ? $editingEmployee['employment_type'] : 'permanent';
        $valStatus = $isEdit ? $editingEmployee['employment_status'] : 'active';
        $valEmail = $isEdit ? $editingEmployee['email'] : '';
        $valPhone = $isEdit ? $editingEmployee['phone'] : '';
        ?>
        <div class="card accessible-card border-primary mt-4 mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="card-title font-lg text-white mb-0"><?= $formTitle ?></h2>
            </div>
            <div class="card-body">
                <form action="<?= $formAction ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <h3 class="font-md font-bold text-primary border-bottom pb-2 mb-3">Informasi Pribadi</h3>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label font-md font-bold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" id="full_name" name="full_name" value="<?= e($valFullName) ?>" class="form-control form-control-accessible" required>
                            </div>

                            <div class="mb-3">
                                <label for="nik" class="form-label font-md font-bold">NIK KTP <span class="text-danger">*</span></label>
                                <input type="text" id="nik" name="nik" value="<?= e($valNik) ?>" class="form-control form-control-accessible" max_length="16" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="birth_place" class="form-label font-md font-bold">Tempat Lahir</label>
                                    <input type="text" id="birth_place" name="birth_place" value="<?= e($valBirthPlace) ?>" class="form-control form-control-accessible">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label font-md font-bold">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" id="birth_date" name="birth_date" value="<?= e($valBirthDate) ?>" class="form-control form-control-accessible" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="gender" class="form-label font-md font-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select id="gender" name="gender" class="form-control form-control-accessible" required>
                                    <option value="male" <?= $valGender === 'male' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="female" <?= $valGender === 'female' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label font-md font-bold">Email Pegawai</label>
                                <input type="email" id="email" name="email" value="<?= e($valEmail) ?>" class="form-control form-control-accessible" placeholder="name@hospital.local">
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label font-md font-bold">No. Telp / WA</label>
                                <input type="text" id="phone" name="phone" value="<?= e($valPhone) ?>" class="form-control form-control-accessible">
                            </div>
                        </div>

                        <div>
                            <h3 class="font-md font-bold text-primary border-bottom pb-2 mb-3">Informasi Kepegawaian</h3>

                            <div class="mb-3">
                                <label for="position" class="form-label font-md font-bold">Jabatan / Profesi <span class="text-danger">*</span></label>
                                <input type="text" id="position" name="position" value="<?= e($valPosition) ?>" class="form-control form-control-accessible" placeholder="Dokter Spesialis, Perawat Pelaksana..." required>
                            </div>

                            <div class="mb-3">
                                <label for="department_id" class="form-label font-md font-bold">Unit / Departemen Kerja</label>
                                <select id="department_id" name="department_id" class="form-control form-control-accessible">
                                    <option value="">-- Pilih Departemen --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= $valDeptId == $dept['id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="join_date" class="form-label font-md font-bold">Tanggal Mulai Bekerja <span class="text-danger">*</span></label>
                                <input type="date" id="join_date" name="join_date" value="<?= e($valJoinDate) ?>" class="form-control form-control-accessible" required>
                            </div>

                            <div class="mb-3">
                                <label for="employment_type" class="form-label font-md font-bold">Tipe Kontrak Kerja</label>
                                <select id="employment_type" name="employment_type" class="form-control form-control-accessible">
                                    <option value="permanent" <?= $valType === 'permanent' ? 'selected' : '' ?>>Pegawai Tetap (Permanent)</option>
                                    <option value="contract" <?= $valType === 'contract' ? 'selected' : '' ?>>Pegawai Kontrak (Contract)</option>
                                    <option value="temporary" <?= $valType === 'temporary' ? 'selected' : '' ?>>Pegawai Honorer (Temporary)</option>
                                    <option value="intern" <?= $valType === 'intern' ? 'selected' : '' ?>>Magang (Intern)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="employment_status" class="form-label font-md font-bold">Status Keaktifan</label>
                                <select id="employment_status" name="employment_status" class="form-control form-control-accessible">
                                    <option value="active" <?= $valStatus === 'active' ? 'selected' : '' ?>>Aktif Bekerja</option>
                                    <option value="inactive" <?= $valStatus === 'inactive' ? 'selected' : '' ?>>Cuti Panjang / Nonaktif Sementara</option>
                                    <option value="resigned" <?= $valStatus === 'resigned' ? 'selected' : '' ?>>Resign (Keluar)</option>
                                    <option value="retired" <?= $valStatus === 'retired' ? 'selected' : '' ?>>Pensiun</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary font-bold font-md py-2 px-4">
                            <?= $submitBtnText ?>
                        </button>
                        <a href="<?= url('hr/employees') ?>" class="btn btn-outline-secondary font-bold font-md py-2 px-4" style="text-decoration: none;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Filters -->
        <div class="card accessible-card mt-4 mb-4">
            <div class="card-body">
                <form action="<?= url('hr/employees') ?>" method="GET" class="row align-items-end">
                    <input type="hidden" name="url" value="hr/employees">
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
                                <th scope="col" class="font-md">NIK / JK</th>
                                <th scope="col" class="font-md">Unit / Departemen</th>
                                <th scope="col" class="font-md">Jabatan / Profesi</th>
                                <th scope="col" class="font-md text-center">Tipe Kontrak</th>
                                <th scope="col" class="font-md">Masa Kerja</th>
                                <th scope="col" class="font-md text-center">Akses Sistem</th>
                                <th scope="col" class="font-md text-center">Status</th>
                                <th scope="col" class="font-md text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Tidak ditemukan data pegawai.</td>
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
                                                <?= $emp['gender'] === 'male' ? 'L' : 'P' ?>
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
                                        <td class="text-center">
                                            <div style="display: flex; gap: 4px; justify-content: center;">
                                                <a href="<?= url('hr/employees?action=edit&id=' . $emp['id']) ?>" class="btn btn-sm btn-info font-bold" style="padding: 4px 8px; font-size: 13px; text-decoration: none;" title="Edit data">
                                                    ✏️ Edit
                                                </a>
                                                <form action="<?= url('hr/employees/delete/' . $emp['id']) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pegawai ini?');">
                                                    <?= CSRF::getField() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger font-bold" style="padding: 4px 8px; font-size: 13px; border: none;" title="Hapus data">
                                                        🗑️ Hapus
                                                    </button>
                                                </form>
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
    <?php endif; ?>
</div>
