<?php

/**
 * Dashboard View
 * Redesigned with premium aesthetics and accessible layout for all ages
 */

$user = Auth::user();
$displayName = $user['full_name'] ?? 'User';

// Primary role label map
$roleLabels = [
    'admin' => 'Administrator Utama',
    'doctor' => 'Dokter Spesialis',
    'nurse' => 'Perawat Medis',
    'receptionist' => 'Resepsionis / Pendaftaran',
    'lab_staff' => 'Petugas Laboratorium',
    'pharmacist' => 'Apoteker / Farmasi',
    'cashier' => 'Kasir / Keuangan',
    'hr' => 'Staf Kepegawaian',
    'patient' => 'Pasien'
];

$primaryRole = 'User';
if (!empty($user['roles'])) {
    $primaryRole = $roleLabels[$user['roles'][0]] ?? ucfirst($user['roles'][0]);
}

// Stats definition list
$stat_definitions = [
    'today_visits' => ['label' => 'Total Kunjungan Hari Ini', 'icon' => '🏥', 'color' => 'info'],
    'today_queue' => ['label' => 'Antrian Menunggu', 'icon' => '🚶', 'color' => 'warning'],
    
    // Admin Stats
    'total_patients' => ['label' => 'Total Pasien Aktif', 'icon' => '👥', 'color' => 'primary'],
    'active_employees' => ['label' => 'Karyawan Aktif', 'icon' => '👔', 'color' => 'info'],
    'unpaid_invoices' => ['label' => 'Tagihan Belum Lunas', 'icon' => '⏳', 'color' => 'danger'],
    'outstanding_amount' => ['label' => 'Total Piutang', 'icon' => '💸', 'color' => 'warning', 'is_currency' => true],
    'today_revenue' => ['label' => 'Pendapatan Hari Ini', 'icon' => '📈', 'color' => 'success', 'is_currency' => true],
    'pending_appointments' => ['label' => 'Janji Temu Hari Ini', 'icon' => '📅', 'color' => 'primary'],
    
    // Doctor Stats
    'my_appointments_today' => ['label' => 'Jadwal Saya Hari Ini', 'icon' => '📅', 'color' => 'primary'],
    'my_queue_today' => ['label' => 'Antrian Saya Hari Ini', 'icon' => '⏳', 'color' => 'warning'],
    'pending_medical_records' => ['label' => 'Draft Rekam Medis', 'icon' => '📋', 'color' => 'danger'],
    'my_patients_today' => ['label' => 'Pasien Saya Hari Ini', 'icon' => '👥', 'color' => 'success'],
    
    // Nurse Stats
    'inpatients' => ['label' => 'Pasien Rawat Inap', 'icon' => '🛌', 'color' => 'info'],
    'emergency_patients' => ['label' => 'UGD Hari Ini', 'icon' => '🚨', 'color' => 'danger'],
    
    // Receptionist Stats
    'today_registrations' => ['label' => 'Pendaftaran Baru', 'icon' => '📝', 'color' => 'success'],
    'new_patients_today' => ['label' => 'Pasien Baru Hari Ini', 'icon' => '🆕', 'color' => 'primary'],
    
    // Lab Stats
    'pending_lab_orders' => ['label' => 'Order Lab Tertunda', 'icon' => '🔬', 'color' => 'warning'],
    'in_progress_orders' => ['label' => 'Pemeriksaan Berjalan', 'icon' => '⏳', 'color' => 'info'],
    'completed_today' => ['label' => 'Selesai Hari Ini', 'icon' => '✅', 'color' => 'success'],
    
    // Pharmacy Stats
    'pending_prescriptions' => ['label' => 'Resep Menunggu', 'icon' => '💊', 'color' => 'warning'],
    'low_stock_medicines' => ['label' => 'Stok Obat Menipis', 'icon' => '⚠️', 'color' => 'danger'],
    'dispensed_today' => ['label' => 'Resep Dilayani', 'icon' => '✅', 'color' => 'success'],
    
    // Cashier Stats
    'today_payments' => ['label' => 'Transaksi Hari Ini', 'icon' => '💳', 'color' => 'success']
];


?>

<div class="dashboard-container">
    
    <!-- Welcome Banner with High-Contrast Text -->
    <div class="welcome-banner mt-3 mb-4">
        <div class="welcome-content">
            <h1 class="welcome-title font-xl">Selamat Datang Kembali, <span class="text-highlight"><?= e($displayName) ?></span></h1>
            <p class="welcome-subtitle font-md">
                Anda masuk sebagai: <strong class="badge badge-primary"><?= e($primaryRole) ?></strong>
            </p>
        </div>
        <div class="welcome-time text-right font-md">
            <div class="date-today"><span class="icon">📅</span> <?= e($stats['today'] ?? date('d F Y')) ?></div>
            <div class="clock-today"><span class="icon">🕒</span> <span id="liveClock"><?= e($stats['current_time'] ?? date('H:i')) ?></span> WIB</div>
        </div>
    </div>

    <div class="dashboard-layout-grid">
        
        <!-- Left Side: Statistics and Information -->
        <div class="dashboard-main-column">
            
            <h2 class="section-title font-lg mb-3">Ringkasan Aktivitas & Statistik</h2>
            
            <!-- Statistics Grid -->
            <div class="stats-cards-grid">
                <?php 
                $shownStatsCount = 0;
                foreach ($stat_definitions as $key => $def): 
                    if (isset($stats[$key])): 
                        $shownStatsCount++;
                        $value = $stats[$key];
                        $displayVal = isset($def['is_currency']) ? formatRupiah($value) : $value;
                        $colorClass = 'card-border-' . $def['color'];
                        $bgIconClass = 'icon-bg-' . $def['color'];
                ?>
                    <div class="stats-card-premium <?= $colorClass ?>" tabindex="0">
                        <div class="stats-card-body">
                            <div class="stats-card-info">
                                <span class="stats-card-label font-md text-muted"><?= e($def['label']) ?></span>
                                <h3 class="stats-card-number font-xl mt-1"><?= e($displayVal) ?></h3>
                            </div>
                            <div class="stats-card-icon-wrapper <?= $bgIconClass ?>">
                                <span class="stats-card-emoji-icon"><?= $def['icon'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif; 
                endforeach; 

                // If no specific stats shown, show a friendly notification
                if ($shownStatsCount === 0):
                ?>
                    <div class="alert alert-info w-100">
                        Belum ada data statistik spesifik untuk role Anda hari ini.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Accessibility Note Box -->
            <div class="card accessible-helper-card mt-4">
                <div class="card-body">
                    <h3 class="font-md font-bold mb-2">💡 Tips Aksesibilitas</h3>
                    <p class="font-sm text-muted mb-0">
                        Halaman ini didesain agar mudah dibaca oleh semua orang. Anda dapat menavigasi elemen menggunakan tombol <strong>Tab</strong> pada keyboard dan menekan <strong>Enter</strong> untuk berinteraksi. Font yang besar dan kontras tinggi memastikan kenyamanan membaca bagi staf medis dari segala rentang usia.
                    </p>
                </div>
            </div>

        </div>
        
        <!-- Right Side: Quick Actions & System Log -->
        <div class="dashboard-side-column">
            
            <!-- Quick Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h2 class="card-title font-md font-bold text-primary mb-0">⚡ Akses Cepat</h2>
                </div>
                <div class="card-body py-3">
                    <div class="quick-action-buttons">
                        
                        <?php if (Auth::can('patients.create')): ?>
                            <a href="<?= url('patient/create') ?>" class="action-btn-accessible">
                                <span class="action-btn-icon">🆕</span>
                                <span class="action-btn-text font-md">Daftar Pasien Baru</span>
                            </a>
                        <?php endif; ?>

                        <?php if (Auth::can('patients.view')): ?>
                            <a href="<?= url('patient') ?>" class="action-btn-accessible">
                                <span class="action-btn-icon">👥</span>
                                <span class="action-btn-text font-md">Lihat Daftar Pasien</span>
                            </a>
                            <a href="<?= url('patient/search') ?>" class="action-btn-accessible">
                                <span class="action-btn-icon">🔍</span>
                                <span class="action-btn-text font-md">Cari Data Pasien</span>
                            </a>
                        <?php endif; ?>

                        <a href="<?= url('auth/change-password') ?>" class="action-btn-accessible">
                            <span class="action-btn-icon">🔒</span>
                            <span class="action-btn-text font-md">Ubah Password Akun</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Timeline (Loaded Dynamically) -->
            <?php if (Auth::can('audit.view')): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h2 class="card-title font-md font-bold text-primary mb-0">📜 Aktivitas Sistem Terbaru</h2>
                        <button id="refreshActivities" class="btn btn-secondary btn-sm" aria-label="Refresh aktivitas">🔄 Segarkan</button>
                    </div>
                    <div class="card-body p-0">
                        <div id="activityContainer" class="activity-timeline-container">
                            <div class="text-center p-4" id="activityLoader">
                                <div class="spinner mb-2"></div>
                                <p class="text-muted font-sm mb-0">Memuat data aktivitas...</p>
                            </div>
                            <ul class="activity-timeline-list hidden" id="activityList">
                                <!-- JS-generated activity items -->
                            </ul>
                            <div class="p-3 text-center hidden" id="activityEmptyState">
                                <p class="text-muted font-sm mb-0">Tidak ada log aktivitas sistem terbaru.</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Live Clock
    const clockElement = document.getElementById('liveClock');
    if (clockElement) {
        setInterval(() => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            clockElement.textContent = `${hours}:${minutes}`;
        }, 1000);
    }

    // 2. Fetch Recent Activities dynamically via AJAX if container exists
    const activityContainer = document.getElementById('activityContainer');
    if (activityContainer) {
        const loader = document.getElementById('activityLoader');
        const list = document.getElementById('activityList');
        const emptyState = document.getElementById('activityEmptyState');
        const refreshBtn = document.getElementById('refreshActivities');

        function fetchActivities() {
            loader.classList.remove('hidden');
            list.classList.add('hidden');
            emptyState.classList.add('hidden');

            fetch('<?= url("dashboard/recent-activities") ?>')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Gagal memuat log audit');
                    }
                    return response.json();
                })
                .then(result => {
                    loader.classList.add('hidden');
                    if (result.success && result.data && result.data.length > 0) {
                        list.innerHTML = '';
                        result.data.forEach(item => {
                            const date = new Date(item.created_at);
                            const formattedTime = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                            const formattedDate = date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                            
                            const li = document.createElement('li');
                            li.className = 'activity-timeline-item';
                            li.innerHTML = `
                                <div class="activity-time-badge font-xs">
                                    <strong>${formattedTime}</strong>
                                    <span>${formattedDate}</span>
                                </div>
                                <div class="activity-content-box">
                                    <div class="activity-user-badge font-sm">
                                        <span class="user-icon">👤</span> <strong>${escapeHtml(item.user_full_name || item.username)}</strong>
                                    </div>
                                    <p class="activity-desc font-sm mt-1 mb-0">${escapeHtml(item.description)}</p>
                                    <div class="activity-meta font-xs mt-1 text-muted">
                                        Modul: <span class="badge badge-secondary font-xs">${escapeHtml(item.module)}</span> 
                                        &bull; IP: <code>${escapeHtml(item.ip_address)}</code>
                                    </div>
                                </div>
                            `;
                            list.appendChild(li);
                        });
                        list.classList.remove('hidden');
                    } else {
                        emptyState.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching activities:', error);
                    loader.classList.add('hidden');
                    emptyState.innerHTML = '<p class="text-danger font-sm p-3">Gagal memuat data log audit. Silakan hubungi admin.</p>';
                    emptyState.classList.remove('hidden');
                });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Initial fetch
        fetchActivities();

        // Refresh action
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fetchActivities();
            });
        }
    }
});
</script>