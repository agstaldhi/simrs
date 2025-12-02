<?php

/**
 * Login Page View
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
                <p class="login-subtitle">Sistem Informasi Manajemen Rumah Sakit</p>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash = Session::getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($timeout) && $timeout): ?>
                <div class="alert alert-warning">
                    Sesi Anda telah berakhir. Silakan login kembali.
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= url('auth/do-login') ?>" method="POST" class="login-form">
                <?= CSRF::getField() ?>

                <div class="form-group">
                    <label for="username" class="form-label">Username atau Email</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username atau email"
                        value="<?= e(old('username')) ?>"
                        required
                        autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required>
                </div>

                <div class="form-group form-check">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="form-check-input"
                        value="1">
                    <label for="remember" class="form-check-label">
                        Ingat saya
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Login
                </button>

                <div class="login-links">
                    <a href="<?= url('auth/forgot-password') ?>" class="link">
                        Lupa password?
                    </a>
                </div>
            </form>

            <!-- Info Box -->
            <div class="login-info">
                <h3>Informasi Login</h3>
                <p><strong>Default Admin:</strong></p>
                <p>Username: admin@simrs.local</p>
                <p>Password: Admin123!</p>

                <p class="mt-3"><strong>Default Dokter:</strong></p>
                <p>Username: dokter@simrs.local</p>
                <p>Password: Dokter123!</p>
            </div>

        </div>
    </div>

    <footer class="login-footer">
        <p>&copy; <?= date('Y') ?> SIMRS. All rights reserved.</p>
    </footer>

</body>

</html>