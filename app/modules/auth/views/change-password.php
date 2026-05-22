<?php

/**
 * Change Password Page View
 * Accessible design for all ages
 */
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Ubah Password</h1>
            <p class="page-subtitle text-muted font-md">Silakan ganti kata sandi Anda secara berkala untuk menjaga keamanan akun.</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card accessible-card mt-3">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary">Formulir Ganti Password</h2>
        </div>
        
        <div class="card-body">
            <form action="<?= url('auth/do-change-password') ?>" method="POST" class="accessible-form">
                <?= CSRF::getField() ?>

                <!-- Current Password -->
                <div class="form-group mb-4">
                    <label for="current_password" class="form-label font-md font-bold">Password Saat Ini</label>
                    <div class="input-desc mb-2 text-muted">Masukkan password yang Anda gunakan untuk login saat ini.</div>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-control form-control-accessible" 
                        placeholder="Masukkan password saat ini" 
                        required
                        autocomplete="current-password">
                </div>

                <!-- New Password -->
                <div class="form-group mb-4">
                    <label for="new_password" class="form-label font-md font-bold">Password Baru</label>
                    <div class="input-desc mb-2 text-muted">Masukkan password baru Anda (minimal 8 karakter).</div>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-control form-control-accessible" 
                        placeholder="Masukkan password baru" 
                        required
                        autocomplete="new-password">
                </div>

                <!-- Confirm New Password -->
                <div class="form-group mb-4">
                    <label for="new_password_confirm" class="form-label font-md font-bold">Konfirmasi Password Baru</label>
                    <div class="input-desc mb-2 text-muted">Ulangi password baru Anda untuk memastikan tidak ada kesalahan ketik.</div>
                    <input 
                        type="password" 
                        id="new_password_confirm" 
                        name="new_password_confirm" 
                        class="form-control form-control-accessible" 
                        placeholder="Ulangi password baru" 
                        required
                        autocomplete="new-password">
                </div>

                <!-- Action Buttons -->
                <div class="form-actions mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-accessible-lg">
                        Simpan Password Baru
                    </button>
                    <a href="<?= url('dashboard') ?>" class="btn btn-secondary btn-accessible-lg">
                        Kembali ke Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
