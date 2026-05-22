<?php

/**
 * User Profile Page View
 * Accessible design for all ages, high contrast, clean structure
 */

$user = $data['user'] ?? [];
$roles = $_SESSION['roles'] ?? [];
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Profil Saya</h1>
            <p class="page-subtitle text-muted font-md">Informasi akun Anda di Sistem Informasi Manajemen Rumah Sakit (SIMRS).</p>
        </div>
    </div>

    <div class="row mt-4">
        <!-- User Info Detail -->
        <div class="col-md-4 mb-4">
            <div class="card accessible-card">
                <div class="card-body text-center">
                    <div class="user-avatar-large mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%; background-color: var(--primary-color); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold;">
                        <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <h2 class="font-lg font-bold mb-1"><?= e($user['full_name'] ?? '') ?></h2>
                    <p class="text-muted font-md mb-3">@<?= e($user['username'] ?? '') ?></p>
                    
                    <div class="roles-badges mb-3">
                        <?php foreach ($roles as $role): ?>
                            <span class="badge badge-primary font-sm" style="display: inline-block; margin: 2px; padding: 5px 10px; border-radius: 20px; font-weight: bold; background-color: var(--primary-color); color: white;">
                                <?= e(ucfirst($role)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-left font-sm text-muted">
                        <div class="mb-2"><strong>Daftar Tanggal:</strong> <?= formatDate($user['created_at'] ?? '') ?></div>
                        <div class="mb-2"><strong>Login Terakhir:</strong> <?= formatDatetime($user['last_login_at'] ?? '') ?></div>
                        <div><strong>IP Login Terakhir:</strong> <?= e($user['last_login_ip'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Profile Form -->
        <div class="col-md-8 mb-4">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Ubah Informasi Profil</h2>
                </div>
                
                <div class="card-body">
                    <form action="<?= url('profile/update') ?>" method="POST" class="accessible-form">
                        <?= CSRF::getField() ?>

                        <!-- Username (Read-Only) -->
                        <div class="form-group mb-4">
                            <label for="username" class="form-label font-md font-bold text-muted">Username (Tidak dapat diubah)</label>
                            <input 
                                type="text" 
                                id="username" 
                                class="form-control form-control-accessible" 
                                value="<?= e($user['username'] ?? '') ?>" 
                                readonly 
                                style="background-color: var(--gray-100); cursor: not-allowed;">
                        </div>

                        <!-- Full Name -->
                        <div class="form-group mb-4">
                            <label for="full_name" class="form-label font-md font-bold">Nama Lengkap</label>
                            <div class="input-desc mb-2 text-muted">Masukkan nama lengkap Anda sesuai KTP/tanda pengenal resmi.</div>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-control form-control-accessible" 
                                value="<?= e($user['full_name'] ?? '') ?>" 
                                required>
                        </div>

                        <!-- Email -->
                        <div class="form-group mb-4">
                            <label for="email" class="form-label font-md font-bold">Alamat Email</label>
                            <div class="input-desc mb-2 text-muted">Masukkan alamat email aktif Anda.</div>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control form-control-accessible" 
                                value="<?= e($user['email'] ?? '') ?>" 
                                required>
                        </div>

                        <!-- Phone -->
                        <div class="form-group mb-4">
                            <label for="phone" class="form-label font-md font-bold">Nomor Telepon</label>
                            <div class="input-desc mb-2 text-muted">Nomor telepon/HP yang dapat dihubungi.</div>
                            <input 
                                type="text" 
                                id="phone" 
                                name="phone" 
                                class="form-control form-control-accessible" 
                                value="<?= e($user['phone'] ?? '') ?>" 
                                required>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-actions mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-accessible-lg">
                                Simpan Perubahan Profil
                            </button>
                            <a href="<?= url('dashboard') ?>" class="btn btn-secondary btn-accessible-lg">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
