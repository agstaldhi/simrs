<?php

/**
 * Forgot Password Page View
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
                <p class="login-subtitle">Lupa Password Akun SIMRS</p>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash = Session::getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <p class="mb-4 text-center text-muted font-md">
                Masukkan alamat email yang terdaftar pada akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang password Anda.
            </p>

            <!-- Forgot Password Form -->
            <form action="<?= url('auth/do-forgot-password') ?>" method="POST" class="login-form">
                <?= CSRF::getField() ?>

                <div class="form-group mb-4">
                    <label for="email" class="form-label font-bold">Alamat Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control form-control-accessible"
                        placeholder="contoh: user@simrs.local"
                        value="<?= e(old('email')) ?>"
                        required
                        autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-accessible-lg mb-3">
                    Kirim Link Reset Password
                </button>

                <div class="login-links text-center">
                    <a href="<?= url('auth/login') ?>" class="link font-bold">
                        &larr; Kembali ke Halaman Login
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
