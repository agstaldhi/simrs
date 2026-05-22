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
        
        <?php if ($action !== 'add'): ?>
            <a href="<?= url('settings/users?action=add') ?>" class="btn btn-primary btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px;">
                ➕ Tambah User Baru
            </a>
        <?php else: ?>
            <a href="<?= url('settings/users') ?>" class="btn btn-light btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; font-weight: bold; border: 1px solid #ccc;">
                ◀️ Kembali ke Daftar
            </a>
        <?php endif; ?>
    </div>

    <!-- FORM TAMBAH USER BARU (Inline Page Action) -->
    <?php if ($action === 'add'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white" style="padding: 16px;">
                <h2 class="font-lg font-bold mb-0" style="color: #fff;">📝 Formulir Pendaftaran User Pegawai</h2>
            </div>
            <div class="card-body p-4">
                <form action="<?= url('settings/users/store') ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; flex-wrap: wrap;">
                        
                        <!-- Left Side Inputs -->
                        <div class="grid-col">
                            <div class="form-group mb-3">
                                <label for="username" class="font-md font-bold mb-1 d-block">Username / ID Pegawai <span class="text-danger">*</span></label>
                                <input type="text" id="username" name="username" 
                                       value="<?= e(old('username')) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" required placeholder="Contoh: dr_budi, perawat_siti">
                                <small class="text-muted d-block mt-1">Digunakan untuk kredensial login masuk sistem. Unik dan tanpa spasi.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="full_name" class="font-md font-bold mb-1 d-block">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?= e(old('full_name')) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" required placeholder="Contoh: dr. Budi Santoso, Sp.PD">
                            </div>

                            <div class="form-group mb-3">
                                <label for="email" class="font-md font-bold mb-1 d-block">Email Institusi / Pribadi <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" 
                                       value="<?= e(old('email')) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" required placeholder="Contoh: budi@rssehat.co.id">
                            </div>
                        </div>

                        <!-- Right Side Inputs -->
                        <div class="grid-col">
                            <div class="form-group mb-3">
                                <label for="phone" class="font-md font-bold mb-1 d-block">Nomor Telepon / WhatsApp</label>
                                <input type="text" id="phone" name="phone" 
                                       value="<?= e(old('phone')) ?>" 
                                       class="form-control form-control-accessible" style="width: 100%;" placeholder="Contoh: 081234567890">
                            </div>

                            <div class="form-group mb-3">
                                <label for="password" class="font-md font-bold mb-1 d-block">Kata Sandi (Password) <span class="text-danger">*</span></label>
                                <input type="password" id="password" name="password" 
                                       class="form-control form-control-accessible" style="width: 100%;" required placeholder="Minimal 8 karakter">
                                <small class="text-muted d-block mt-1">Gunakan kombinasi huruf besar, huruf kecil, dan angka.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="role_id" class="font-md font-bold mb-1 d-block">Peran / Hak Akses (Role) <span class="text-danger">*</span></label>
                                <select id="role_id" name="role_id" class="form-control form-control-accessible" style="width: 100%;" required>
                                    <option value="">-- Pilih Hak Akses --</option>
                                    <?php 
                                    $oldRole = old('role_id');
                                    foreach ($roles as $role): 
                                    ?>
                                        <option value="<?= (int)$role['id'] ?>" <?= $oldRole == $role['id'] ? 'selected' : '' ?>>
                                            <?= e($role['display_name']) ?> (<?= e($role['name']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Menentukan menu dan modul apa saja yang boleh diakses pegawai ini.</small>
                            </div>
                        </div>

                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary btn-accessible-lg font-md" style="padding: 12px 30px;">
                            💾 Daftarkan Pegawai
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 24px; text-align: center;" class="text-muted font-md">Belum ada data user terdaftar.</td>
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
                                            <?php if ($user['is_active'] == 1): ?>
                                                <span class="text-success font-bold" style="color: #2b8a3e;">🟢 Aktif</span>
                                            <?php else: ?>
                                                <span class="text-danger font-bold" style="color: #c92a2a;">🔴 Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 16px;" class="font-md text-muted">
                                            <?= $user['last_login_at'] ? e(formatDatetime($user['last_login_at'])) : 'Belum pernah' ?>
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
