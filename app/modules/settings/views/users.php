<?php
/**
 * Users management view
 * Displays a list of all system users and an inline form for registering a new employee user.
 */
?>

<div class="users-container mt-3">
    
    <!-- Page Header -->
    <div class="page-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="font-xl font-bold">👥 Manajemen User</h1>
            <p class="text-muted font-md">Kelola hak login akun pegawai, kata sandi, dan pembagian tugas operasional rumah sakit.</p>
        </div>
        
        <?php if ($action === 'list'): ?>
            <a href="<?= url('settings/users?action=add') ?>" class="btn btn-primary btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px;">
                ➕ Tambah User Baru
            </a>
        <?php else: ?>
            <a href="<?= url('settings/users') ?>" class="btn btn-light btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; font-weight: bold; border: 1px solid #ccc;">
                ◀️ Kembali ke Daftar
            </a>
        <?php endif; ?>
    </div>

    <!-- FORM TAMBAH/EDIT USER (Inline Page Action) -->
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <?php 
        $isEdit = ($action === 'edit');
        $formAction = $isEdit ? url('settings/users/update/' . $editingUser['id']) : url('settings/users/store');
        $formTitle = $isEdit ? '📝 Formulir Edit Data User Pegawai' : '📝 Formulir Pendaftaran User Pegawai';
        $submitBtnText = $isEdit ? '💾 Simpan Perubahan' : '💾 Daftarkan Pegawai';
        
        $valUsername = $isEdit ? $editingUser['username'] : old('username');
        $valFullName = $isEdit ? $editingUser['full_name'] : old('full_name');
        $valEmail = $isEdit ? $editingUser['email'] : old('email');
        $valPhone = $isEdit ? $editingUser['phone'] : old('phone');
        $valRoleId = $isEdit ? $editingUser['role_id'] : old('role_id');
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white" style="padding: 16px;">
                <h2 class="font-lg font-bold mb-0" style="color: #fff;"><?= $formTitle ?></h2>
            </div>
            <div class="card-body p-4">
                <form action="<?= $formAction ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; flex-wrap: wrap;">
                        
                        <!-- Left Side Inputs -->
                        <div class="grid-col">
                            <div class="form-group mb-3">
                                <label for="username" class="font-md font-bold mb-1 d-block">Username / ID Pegawai <span class="text-danger">*</span></label>
                                <input type="text" id="username" name="username" 
                                       value="<?= e($valUsername) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" required placeholder="Contoh: dr_budi, perawat_siti" <?= $isEdit ? 'readonly style="background-color: #e9ecef; cursor: not-allowed;"' : '' ?>>
                                <small class="text-muted d-block mt-1">Digunakan untuk kredensial login masuk sistem. <?= $isEdit ? 'Username tidak dapat diubah.' : 'Unik dan tanpa spasi.' ?></small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="full_name" class="font-md font-bold mb-1 d-block">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?= e($valFullName) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" required placeholder="Contoh: dr. Budi Santoso, Sp.PD">
                            </div>

                            <div class="form-group mb-3">
                                <label for="email" class="font-md font-bold mb-1 d-block">Email Institusi / Pribadi <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" 
                                       value="<?= e($valEmail) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" required placeholder="Contoh: budi@rssehat.co.id">
                            </div>
                        </div>

                        <!-- Right Side Inputs -->
                        <div class="grid-col">
                            <div class="form-group mb-3">
                                <label for="phone" class="font-md font-bold mb-1 d-block">Nomor Telepon / WhatsApp</label>
                                <input type="text" id="phone" name="phone" 
                                       value="<?= e($valPhone) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" placeholder="Contoh: 081234567890">
                            </div>

                            <div class="form-group mb-3">
                                <label for="password" class="font-md font-bold mb-1 d-block">Kata Sandi (Password) <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                                <input type="password" id="password" name="password" 
                                       class="form-control form-control-accessible" style="width: 100%;" <?= $isEdit ? '' : 'required' ?> placeholder="<?= $isEdit ? 'Kosongkan jika tidak ingin mengubah sandi' : 'Minimal 8 karakter' ?>">
                                <small class="text-muted d-block mt-1">Gunakan kombinasi huruf besar, huruf kecil, dan angka.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="role_id" class="font-md font-bold mb-1 d-block">Peran / Hak Akses (Role) <span class="text-danger">*</span></label>
                                <select id="role_id" name="role_id" class="form-control form-control-accessible" style="width: 100%;" required>
                                    <option value="">-- Pilih Hak Akses --</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= (int)$role['id'] ?>" <?= $valRoleId == $role['id'] ? 'selected' : '' ?>>
                                            <?= e($role['display_name']) ?> (<?= e($role['name']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Menentukan menu dan modul apa saja yang boleh diakses pegawai ini.</small>
                            </div>

                            <?php if ($isEdit): ?>
                                <div class="form-group mb-3">
                                    <label for="is_active" class="font-md font-bold mb-1 d-block">Status Akun <span class="text-danger">*</span></label>
                                    <select id="is_active" name="is_active" class="form-control form-control-accessible" style="width: 100%;" required>
                                        <option value="1" <?= $editingUser['is_active'] == 1 ? 'selected' : '' ?>>Aktif</option>
                                        <option value="0" <?= $editingUser['is_active'] == 0 ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary btn-accessible-lg font-md" style="padding: 12px 30px;">
                            <?= $submitBtnText ?>
                        </button>
                        <a href="<?= url('settings/users') ?>" class="btn btn-light btn-accessible-lg font-md" style="padding: 12px 30px; text-decoration: none; text-align: center; border: 1px solid #ccc; line-height: 24px;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <!-- DAFTAR USER UTAMA -->
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                                <th style="padding: 16px; font-weight: bold;" class="font-md">Username</th>
                                <th style="padding: 16px; font-weight: bold;" class="font-md">Nama Lengkap</th>
                                <th style="padding: 16px; font-weight: bold;" class="font-md">Email</th>
                                <th style="padding: 16px; font-weight: bold;" class="font-md">Nomor Telepon</th>
                                <th style="padding: 16px; font-weight: bold;" class="font-md">Hak Akses (Roles)</th>
                                <th style="padding: 16px; font-weight: bold;" class="font-md">Status</th>
                                <th style="padding: 16px; font-weight: bold;" class="font-md">Login Terakhir</th>
                                <th style="padding: 16px; font-weight: bold; text-align: center;" class="font-md">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" style="padding: 24px; text-align: center;" class="text-muted font-md">Belum ada data user terdaftar.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 16px;" class="font-md"><strong><?= e($user['username']) ?></strong></td>
                                        <td style="padding: 16px;" class="font-md"><?= e($user['full_name']) ?></td>
                                        <td style="padding: 16px;" class="font-md"><?= e($user['email']) ?></td>
                                        <td style="padding: 16px;" class="font-md"><?= e($user['phone'] ?? '-') ?></td>
                                        <td style="padding: 16px;" class="font-md">
                                            <span class="badge badge-primary" style="font-size: 14px; padding: 4px 8px; font-weight: bold; border-radius: 4px;">
                                                <?= e($user['role_names'] ?: 'Tidak ada peran') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 16px;" class="font-md">
                                            <form action="<?= url('settings/users/toggle-active/' . $user['id']) ?>" method="POST" style="display:inline;" onsubmit="return handleToggleActive(event, this);">
                                                <?= CSRF::getField() ?>
                                                <button type="submit" class="btn btn-sm <?= $user['is_active'] == 1 ? 'btn-success' : 'btn-danger' ?>" style="font-weight:bold; border-radius: 4px; padding: 4px 10px; border: none; cursor: pointer;" <?= $_SESSION['user_id'] == $user['id'] ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : '' ?>>
                                                    <?= $user['is_active'] == 1 ? '🟢 Aktif' : '🔴 Nonaktif' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td style="padding: 16px;" class="font-md text-muted">
                                            <?= $user['last_login_at'] ? e(formatDatetime($user['last_login_at'])) : 'Belum pernah' ?>
                                        </td>
                                        <td style="padding: 16px; text-align: center;" class="font-md">
                                            <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                                <a href="<?= url('settings/users?action=edit&id=' . $user['id']) ?>" class="btn btn-sm btn-info" style="text-decoration: none; padding: 6px 12px; font-weight: bold; border-radius: 4px; color: #fff; background-color: #17a2b8; border: none;" title="Edit Data">
                                                    ✏️ Edit
                                                </a>
                                                <?php if ($_SESSION['user_id'] != $user['id']): ?>
                                                    <form action="<?= url('settings/users/delete/' . $user['id']) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini? Semua data login user akan dihapus permanen.');">
                                                        <?= CSRF::getField() ?>
                                                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 6px 12px; font-weight: bold; border-radius: 4px; color: #fff; background-color: #dc3545; border: none; cursor: pointer;" title="Hapus User">
                                                            🗑️ Hapus
                                                        </button>
                                                    </form>
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

        <script>
        function handleToggleActive(event, form) {
            event.preventDefault();
            if (confirm('Apakah Anda yakin ingin mengubah status aktif user ini?')) {
                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const btn = form.querySelector('button');
                        if (data.is_active === 1) {
                            btn.className = 'btn btn-sm btn-success';
                            btn.style.backgroundColor = '';
                            btn.innerHTML = '🟢 Aktif';
                        } else {
                            btn.className = 'btn btn-sm btn-danger';
                            btn.style.backgroundColor = '';
                            btn.innerHTML = '🔴 Nonaktif';
                        }
                        alert(data.message);
                    } else {
                        alert(data.message || 'Gagal mengubah status.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan koneksi.');
                });
            }
            return false;
        }
        </script>
    <?php endif; ?>

</div>
