<?php

/**
 * Sidebar / Mobile Menu Template
 */

$user = Auth::user();
?>

<!-- Sidebar (Desktop) & Mobile Menu Overlay -->
<aside class="sidebar" id="sidebar" role="navigation">
    <div class="sidebar-content">

        <!-- Mobile Menu Header -->
        <div class="mobile-menu-header">
            <span class="mobile-menu-title">Menu</span>
            <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close Menu">
                &times;
            </button>
        </div>

        <!-- User Info (Mobile Only) -->
        <div class="sidebar-user mobile-only">
            <div class="user-avatar-large">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <strong><?= e($user['full_name']) ?></strong>
                <small><?= e($user['email']) ?></small>
            </div>
        </div>

        <!-- Main Navigation -->
        <nav class="sidebar-nav">
            <ul class="sidebar-menu">

                <!-- Dashboard -->
                <li class="menu-item <?= activeClass('dashboard') ?>">
                    <a href="<?= url('dashboard') ?>" class="menu-link">
                        <span class="menu-icon">🏠</span>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>

                <!-- Master Data (Admin Only) -->
                <?php if (Auth::hasRole('admin')): ?>
                    <li class="menu-item has-submenu">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">⚙️</span>
                            <span class="menu-text">Master Data</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('master/hospital') ?>">Data RS</a></li>
                            <li><a href="<?= url('master/department') ?>">Departemen</a></li>
                            <li><a href="<?= url('master/doctor') ?>">Dokter</a></li>
                            <li><a href="<?= url('master/polyclinic') ?>">Poliklinik</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Patient Management -->
                <?php if (Auth::can('patients.view')): ?>
                    <li class="menu-item has-submenu <?= activeClass('patient') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">👤</span>
                            <span class="menu-text">Pasien</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('patient') ?>">Daftar Pasien</a></li>
                            <?php if (Auth::can('patients.create')): ?>
                                <li><a href="<?= url('patient/create') ?>">Daftar Pasien Baru</a></li>
                            <?php endif; ?>
                            <li><a href="<?= url('patient/search') ?>">Cari Pasien</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Appointment & Queue -->
                <?php if (Auth::can('appointments.view')): ?>
                    <li class="menu-item has-submenu <?= activeClass('appointment') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">📅</span>
                            <span class="menu-text">Appointment</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('appointment') ?>">Daftar Appointment</a></li>
                            <li><a href="<?= url('appointment/create') ?>">Buat Appointment</a></li>
                            <li><a href="<?= url('queue') ?>">Antrian Hari Ini</a></li>
                            <li><a href="<?= url('schedule') ?>">Jadwal Dokter</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Medical Records -->
                <?php if (Auth::can('medical_records.view')): ?>
                    <li class="menu-item <?= activeClass('medical-record') ?>">
                        <a href="<?= url('medical-record') ?>" class="menu-link">
                            <span class="menu-icon">📋</span>
                            <span class="menu-text">Rekam Medis</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Laboratory -->
                <?php if (Auth::can('lab.view_orders')): ?>
                    <li class="menu-item has-submenu <?= activeClass('laboratory') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">🔬</span>
                            <span class="menu-text">Laboratorium</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('laboratory/orders') ?>">Order Lab</a></li>
                            <li><a href="<?= url('laboratory/results') ?>">Hasil Lab</a></li>
                            <li><a href="<?= url('laboratory/templates') ?>">Template Pemeriksaan</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Pharmacy -->
                <?php if (Auth::can('pharmacy.view_prescriptions')): ?>
                    <li class="menu-item has-submenu <?= activeClass('pharmacy') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">💊</span>
                            <span class="menu-text">Farmasi</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('pharmacy/prescriptions') ?>">Resep</a></li>
                            <li><a href="<?= url('pharmacy/medicines') ?>">Daftar Obat</a></li>
                            <li><a href="<?= url('pharmacy/stock') ?>">Stok Obat</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Billing -->
                <?php if (Auth::can('billing.view_invoices')): ?>
                    <li class="menu-item has-submenu <?= activeClass('billing') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">💰</span>
                            <span class="menu-text">Billing</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('billing/invoices') ?>">Tagihan</a></li>
                            <li><a href="<?= url('billing/payments') ?>">Pembayaran</a></li>
                            <li><a href="<?= url('billing/outstanding') ?>">Tunggakan</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Inventory (Admin/Pharmacist) -->
                <?php if (Auth::can('inventory.view')): ?>
                    <li class="menu-item has-submenu <?= activeClass('inventory') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">📦</span>
                            <span class="menu-text">Inventori</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('inventory/items') ?>">Daftar Item</a></li>
                            <li><a href="<?= url('inventory/purchase-orders') ?>">Purchase Order</a></li>
                            <li><a href="<?= url('inventory/suppliers') ?>">Supplier</a></li>
                            <li><a href="<?= url('inventory/stock-opname') ?>">Stock Opname</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- HR (Admin/HR Staff) -->
                <?php if (Auth::can('hr.view_employees')): ?>
                    <li class="menu-item has-submenu <?= activeClass('hr') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">👥</span>
                            <span class="menu-text">Kepegawaian</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('hr/employees') ?>">Daftar Pegawai</a></li>
                            <li><a href="<?= url('hr/attendance') ?>">Absensi</a></li>
                            <li><a href="<?= url('hr/shifts') ?>">Jadwal Shift</a></li>
                            <li><a href="<?= url('hr/leaves') ?>">Cuti</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Reports -->
                <?php if (Auth::can('reports.view')): ?>
                    <li class="menu-item has-submenu <?= activeClass('report') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">📊</span>
                            <span class="menu-text">Laporan</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('report/daily') ?>">Laporan Harian</a></li>
                            <li><a href="<?= url('report/monthly') ?>">Laporan Bulanan</a></li>
                            <li><a href="<?= url('report/financial') ?>">Laporan Keuangan</a></li>
                            <li><a href="<?= url('report/custom') ?>">Laporan Custom</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Settings (Admin Only) -->
                <?php if (Auth::hasRole('admin')): ?>
                    <li class="menu-item has-submenu <?= activeClass('settings') ?>">
                        <a href="#" class="menu-link">
                            <span class="menu-icon">⚙️</span>
                            <span class="menu-text">Pengaturan</span>
                            <span class="menu-arrow">▼</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?= url('settings/general') ?>">Umum</a></li>
                            <li><a href="<?= url('settings/users') ?>">Manajemen User</a></li>
                            <li><a href="<?= url('settings/roles') ?>">Role & Permission</a></li>
                            <li><a href="<?= url('settings/backup') ?>">Backup & Restore</a></li>
                            <li><a href="<?= url('settings/audit') ?>">Audit Log</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>

        <!-- Mobile Only: Additional Links -->
        <div class="sidebar-footer mobile-only">
            <a href="<?= url('profile') ?>" class="footer-link">Profil Saya</a>
            <a href="<?= url('auth/change-password') ?>" class="footer-link">Ubah Password</a>
            <form action="<?= url('auth/logout') ?>" method="POST" style="display: inline;">
                <?= CSRF::getField() ?>
                <button type="submit" class="footer-link logout-btn">Logout</button>
            </form>
        </div>

    </div>
</aside>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>