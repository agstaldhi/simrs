<?php
/**
 * Verify Backup Download View
 * Prompts user for password before letting them download database backups.
 */
?>

<div class="verify-container mt-5" style="max-width: 500px; margin: 0 auto;">
    <!-- Card Panel -->
    <div class="card shadow" style="border-top: 4px solid #fa5252; border-radius: 8px; overflow: hidden;">
        <div class="card-header bg-light" style="padding: 20px 24px; border-bottom: 1px solid #e9ecef;">
            <h1 class="font-lg font-bold mb-0" style="display: flex; align-items: center; gap: 10px; margin: 0; color: #fa5252;">
                🔒 Verifikasi Keamanan Tambahan
            </h1>
        </div>
        <div class="card-body" style="padding: 28px 24px;">
            <p class="text-muted font-sm mb-4">
                Anda mencoba mengunduh berkas cadangan database sensitif: <br>
                <code style="background-color: #f1f3f5; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 6px; font-family: monospace; font-size: 13px; color: #495057; font-weight: bold;">
                    <?= e($file) ?>
                </code>
            </p>

            <div class="alert alert-warning mb-4" style="background-color: #fff9db; border: 1px solid #ffe066; color: #f59f00; padding: 14px; border-radius: 6px; font-size: 13px;">
                <strong>Peringatan Keamanan:</strong> Berkas cadangan database mengandung riwayat medis pasien, data pengguna, dan kredensial penting. Harap verifikasi kata sandi Anda untuk melanjutkan proses pengunduhan.
            </div>

            <!-- Flash messages -->
            <?php if (Session::hasFlash('error')): ?>
                <div class="alert alert-danger mb-4" style="background-color: #fff5f5; border: 1px solid #ffc9c9; color: #fa5252; padding: 12px; border-radius: 6px; font-size: 14px;">
                    <?= Session::getFlash('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= url('settings/backup/download') ?>" method="POST">
                <?= CSRF::getField() ?>
                <input type="hidden" name="file" value="<?= e($file) ?>">

                <div class="form-group mb-4">
                    <label for="password" class="form-label font-bold font-sm mb-2" style="display: block; color: #343a40;">Masukkan Kata Sandi Akun Anda</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Masukkan password login Anda" required autofocus
                           style="width: 100%; padding: 12px 14px; border: 1px solid #ced4da; border-radius: 6px; font-size: 15px; transition: border-color 0.15s ease-in-out; box-sizing: border-box;">
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center;">
                    <a href="<?= url('settings/backup') ?>" class="btn btn-light" 
                       style="text-decoration: none; padding: 10px 20px; font-size: 14px; border: 1px solid #ced4da; border-radius: 6px; font-weight: bold; color: #495057;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-danger" 
                            style="padding: 10px 24px; font-size: 14px; background-color: #fa5252; color: #fff; border: 1px solid #fa5252; border-radius: 6px; font-weight: bold; cursor: pointer;">
                        Verify & Download
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
