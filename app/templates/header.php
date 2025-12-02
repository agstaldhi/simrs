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
                <span class="logo-text"><?= e($hospitalName) ?></span>
            </a>
        </div>

        <?php if ($user): ?>
            <!-- Desktop Navigation -->
            <nav class="header-nav desktop-nav" role="navigation">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?= url('dashboard') ?>" class="nav-link <?= activeClass('dashboard') ?>">
                            Dashboard
                        </a>
                    </li>

                    <?php if (Auth::can('patients.view')): ?>
                        <li class="nav-item">
                            <a href="<?= url('patient') ?>" class="nav-link <?= activeClass('patient') ?>">
                                Pasien
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Auth::can('appointments.view')): ?>
                        <li class="nav-item">
                            <a href="<?= url('appointment') ?>" class="nav-link <?= activeClass('appointment') ?>">
                                Appointment
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Auth::can('medical_records.view')): ?>
                        <li class="nav-item">
                            <a href="<?= url('medical-record') ?>" class="nav-link <?= activeClass('medical-record') ?>">
                                Rekam Medis
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Auth::can('lab.view_orders')): ?>
                        <li class="nav-item">
                            <a href="<?= url('laboratory') ?>" class="nav-link <?= activeClass('laboratory') ?>">
                                Laboratorium
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Auth::can('pharmacy.view_prescriptions')): ?>
                        <li class="nav-item">
                            <a href="<?= url('pharmacy') ?>" class="nav-link <?= activeClass('pharmacy') ?>">
                                Farmasi
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Auth::can('billing.view_invoices')): ?>
                        <li class="nav-item">
                            <a href="<?= url('billing') ?>" class="nav-link <?= activeClass('billing') ?>">
                                Billing
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- User Menu -->
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