<?php

/**
 * Header Template
 */

$user = Auth::user();
$hospitalName = config('app.app_name', 'SIMRS');
?>

<header class="header" role="banner">
    <div class="header-container">
        <!-- Logo -->
        <div class="header-logo">
            <a href="<?= url('dashboard') ?>">
                <img src="<?= asset('images/logo.png') ?>" alt="Logo SIMRS" class="logo-img">
                <div class="logo-text-group">
                    <span class="logo-text"><?= e($hospitalName) ?></span>
                    <span class="logo-subtitle">Sistem Informasi Manajemen Rumah Sakit</span>
                </div>
            </a>
        </div>

        <?php if ($user): ?>
            <div class="header-user">
                <div class="user-dropdown">
                    <button class="user-button" aria-haspopup="true" aria-expanded="false">
                        <span class="user-avatar">
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        </span>
                        <span class="user-name"><?= e($user['full_name']) ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>

                    <div class="dropdown-menu" role="menu">
                        <div class="dropdown-header">
                            <strong><?= e($user['full_name']) ?></strong>
                            <small><?= e($user['email']) ?></small>
                        </div>

                        <a href="<?= url('profile') ?>" class="dropdown-item">
                            Profil Saya
                        </a>

                        <a href="<?= url('auth/change-password') ?>" class="dropdown-item">
                            Ubah Password
                        </a>

                        <?php if (Auth::hasRole('admin')): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= url('settings') ?>" class="dropdown-item">
                                Pengaturan Sistem
                            </a>
                        <?php endif; ?>

                        <div class="dropdown-divider"></div>

                        <form action="<?= url('auth/logout') ?>" method="POST">
                            <?= CSRF::getField() ?>
                            <button type="submit" class="dropdown-item logout-btn">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>