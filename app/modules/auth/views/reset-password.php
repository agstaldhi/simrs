<?php

/**
 * Reset Password Page View
 * Accessible design for all ages
 */
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/responsive.css') ?>">
</head>

<body class="login-page">

    <div class="login-container">
        <div class="login-box">

            <!-- Logo & Title -->
            <div class="login-header">
                <img src="<?= asset('images/logo.png') ?>" alt="Logo" class="login-logo">
                <h1 class="login-title">SIMRS</h1>
                <p class="login-subtitle">Atur Ulang Password Baru</p>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash = Session::getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <p class="mb-4 text-center text-muted font-md">
                Silakan masukkan password baru Anda di bawah ini. Pastikan aman dan mudah Anda ingat.
            </p>

            <!-- Reset Password Form -->
            <form action="<?= url('auth/do-reset-password') ?>" method="POST" class="login-form">
                <?= CSRF::getField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="form-group mb-4">
                    <label for="password" class="form-label font-bold">Password Baru</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control form-control-accessible"
                        placeholder="Minimal 8 karakter"
                        required
                        autofocus>
                </div>

                <div class="form-group mb-4">
                    <label for="password_confirm" class="form-label font-bold">Ulangi Password Baru</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        class="form-control form-control-accessible"
                        placeholder="Ulangi password baru"
                        required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-accessible-lg mb-3">
                    Simpan Password Baru
                </button>

                <div class="login-links text-center">
                    <a href="<?= url('auth/login') ?>" class="link font-bold">
                        Batal dan Kembali ke Login
                    </a>
                </div>
            </form>

        </div>
    </div>

    <footer class="login-footer">
        <p>&copy; <?= date('Y') ?> SIMRS. All rights reserved.</p>
    </footer>

</body>

</html>
