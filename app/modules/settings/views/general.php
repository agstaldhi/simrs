<?php
/**
 * General Settings View
 * Sectioned settings with url-based tabs for high-contrast accessibility
 */
?>

<div class="settings-container mt-3">
    <!-- Header Page -->
    <div class="page-header mb-4">
        <h1 class="font-xl font-bold">⚙️ Pengaturan Sistem</h1>
        <p class="text-muted font-md">Kelola parameter global, profil rumah sakit, tarif billing, keamanan, dan setelan email sistem.</p>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-navigation mb-4" role="tablist" style="display: flex; gap: 8px; border-bottom: 2px solid #ddd; padding-bottom: 8px; flex-wrap: wrap;">
        <?php
        $tabs = [
            'general' => '⚙️ Umum',
            'business' => '🏥 Profil RS',
            'billing' => '💳 Billing & Pajak',
            'security' => '🔒 Keamanan',
            'email' => '📧 Email / SMTP',
            'backup' => '💾 Backup Otomatis'
        ];
        foreach ($tabs as $key => $label):
            $isActive = ($activeTab === $key);
            $btnClass = $isActive ? 'btn-primary' : 'btn-light';
            $ariaSelected = $isActive ? 'true' : 'false';
        ?>
            <a href="<?= url('settings/general?tab=' . $key) ?>" 
               class="btn <?= $btnClass ?> btn-accessible-lg" 
               role="tab" 
               aria-selected="<?= $ariaSelected ?>" 
               style="text-decoration: none; padding: 12px 20px; font-weight: bold; border-radius: 6px;">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Settings Form -->
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form action="<?= url('settings/general/update') ?>" method="POST">
                <?= CSRF::getField() ?>
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">

                <!-- Tab: GENERAL -->
                <?php if ($activeTab === 'general'): ?>
                    <h2 class="font-lg font-bold mb-3 border-bottom pb-2">Konfigurasi Umum Aplikasi</h2>
                    
                    <div class="form-group mb-3">
                        <label for="app_name" class="font-md font-bold mb-1 d-block">Nama Aplikasi (App Name)</label>
                        <input type="text" id="app_name" name="settings[app_name]" 
                               value="<?= e($settings['general']['app_name']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 600px;" required>
                        <small class="text-muted d-block mt-1">Nama sistem utama yang tampil di title bar dan beranda.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="app_version" class="font-md font-bold mb-1 d-block">Versi Aplikasi</label>
                        <input type="text" id="app_version" name="settings[app_version]" 
                               value="<?= e($settings['general']['app_version']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" readonly>
                        <small class="text-muted d-block mt-1">Versi sistem saat ini (hanya dapat dibaca).</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="timezone" class="font-md font-bold mb-1 d-block">Zona Waktu (Timezone)</label>
                        <select id="timezone" name="settings[timezone]" class="form-control form-control-accessible" style="width: 100%; max-width: 400px;">
                            <?php
                            $timezones = ['Asia/Jakarta' => 'WIB (Asia/Jakarta)', 'Asia/Makassar' => 'WITA (Asia/Makassar)', 'Asia/Jayapura' => 'WIT (Asia/Jayapura)'];
                            $currentTimezone = $settings['general']['timezone']['setting_value'] ?? 'Asia/Jakarta';
                            foreach ($timezones as $tzKey => $tzName):
                            ?>
                                <option value="<?= e($tzKey) ?>" <?= $currentTimezone === $tzKey ? 'selected' : '' ?>><?= e($tzName) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Zona waktu default untuk pencatatan transaksi medis dan antrian.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="date_format" class="font-md font-bold mb-1 d-block">Format Tanggal</label>
                        <input type="text" id="date_format" name="settings[date_format]" 
                               value="<?= e($settings['general']['date_format']['setting_value'] ?? 'd-m-Y') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" required>
                        <small class="text-muted d-block mt-1">Contoh: d-m-Y (hari-bulan-tahun) atau Y-m-d.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="currency" class="font-md font-bold mb-1 d-block">Mata Uang</label>
                        <input type="text" id="currency" name="settings[currency]" 
                               value="<?= e($settings['general']['currency']['setting_value'] ?? 'IDR') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" required>
                    </div>
                <?php endif; ?>

                <!-- Tab: BUSINESS (Profile RS) -->
                <?php if ($activeTab === 'business'): ?>
                    <h2 class="font-lg font-bold mb-3 border-bottom pb-2">Profil Rumah Sakit / Instansi</h2>
                    
                    <div class="form-group mb-3">
                        <label for="hospital_name" class="font-md font-bold mb-1 d-block">Nama Rumah Sakit</label>
                        <input type="text" id="hospital_name" name="settings[hospital_name]" 
                               value="<?= e($settings['business']['hospital_name']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 600px;" required>
                        <small class="text-muted d-block mt-1">Nama ini dicetak pada kwitansi pembayaran dan laporan.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="hospital_phone" class="font-md font-bold mb-1 d-block">Nomor Telepon RS</label>
                        <input type="text" id="hospital_phone" name="settings[hospital_phone]" 
                               value="<?= e($settings['business']['hospital_phone']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 400px;" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="hospital_email" class="font-md font-bold mb-1 d-block">Email Instansi</label>
                        <input type="email" id="hospital_email" name="settings[hospital_email]" 
                               value="<?= e($settings['business']['hospital_email']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 400px;" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="hospital_address" class="font-md font-bold mb-1 d-block">Alamat Lengkap</label>
                        <textarea id="hospital_address" name="settings[hospital_address]" 
                                  class="form-control form-control-accessible" style="width: 100%; max-width: 600px; height: 100px;" required><?= e($settings['business']['hospital_address']['setting_value'] ?? '') ?></textarea>
                    </div>
                <?php endif; ?>

                <!-- Tab: BILLING -->
                <?php if ($activeTab === 'billing'): ?>
                    <h2 class="font-lg font-bold mb-3 border-bottom pb-2">Pengaturan Billing & Perpajakan</h2>

                    <div class="form-group mb-3">
                        <label for="tax_percentage" class="font-md font-bold mb-1 d-block">Persentase Pajak / PPN (%)</label>
                        <input type="number" id="tax_percentage" name="settings[tax_percentage]" 
                               value="<?= e($settings['billing']['tax_percentage']['setting_value'] ?? '0') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" min="0" max="100" required>
                        <small class="text-muted d-block mt-1">PPN default yang diterapkan pada faktur/billing kasir.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="invoice_due_days" class="font-md font-bold mb-1 d-block">Batas Jatuh Tempo Invoice (Hari)</label>
                        <input type="number" id="invoice_due_days" name="settings[invoice_due_days]" 
                               value="<?= e($settings['billing']['invoice_due_days']['setting_value'] ?? '7') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" min="1" required>
                        <small class="text-muted d-block mt-1">Batas waktu pelunasan tagihan sebelum dinyatakan lewat tempo.</small>
                    </div>
                <?php endif; ?>

                <!-- Tab: SECURITY -->
                <?php if ($activeTab === 'security'): ?>
                    <h2 class="font-lg font-bold mb-3 border-bottom pb-2">Setelan Keamanan Sistem</h2>

                    <div class="form-group mb-3">
                        <label for="session_lifetime" class="font-md font-bold mb-1 d-block">Durasi Sesi / Session Lifetime (Detik)</label>
                        <input type="number" id="session_lifetime" name="settings[session_lifetime]" 
                               value="<?= e($settings['security']['session_lifetime']['setting_value'] ?? '7200') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" min="60" required>
                        <small class="text-muted d-block mt-1">Pengguna akan keluar otomatis setelah X detik tidak aktif (7200 detik = 2 jam).</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="login_max_attempts" class="font-md font-bold mb-1 d-block">Maksimal Percobaan Login Salah</label>
                        <input type="number" id="login_max_attempts" name="settings[login_max_attempts]" 
                               value="<?= e($settings['security']['login_max_attempts']['setting_value'] ?? '5') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" min="1" required>
                        <small class="text-muted d-block mt-1">Jumlah kegagalan input sandi sebelum IP diblokir sementara.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="login_lockout_duration" class="font-md font-bold mb-1 d-block">Durasi Lockout IP (Detik)</label>
                        <input type="number" id="login_lockout_duration" name="settings[login_lockout_duration]" 
                               value="<?= e($settings['security']['login_lockout_duration']['setting_value'] ?? '900') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" min="10" required>
                        <small class="text-muted d-block mt-1">Lama pemblokiran login bagi IP yang melampaui batas salah sandi (900 detik = 15 menit).</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="password_min_length" class="font-md font-bold mb-1 d-block">Panjang Minimal Password Pegawai</label>
                        <input type="number" id="password_min_length" name="settings[password_min_length]" 
                               value="<?= e($settings['security']['password_min_length']['setting_value'] ?? '8') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" min="6" required>
                    </div>
                <?php endif; ?>

                <!-- Tab: EMAIL -->
                <?php if ($activeTab === 'email'): ?>
                    <h2 class="font-lg font-bold mb-3 border-bottom pb-2">Konfigurasi Pengiriman Email SMTP</h2>

                    <div class="form-group mb-3">
                        <label for="smtp_host" class="font-md font-bold mb-1 d-block">SMTP Host</label>
                        <input type="text" id="smtp_host" name="settings[smtp_host]" 
                               value="<?= e($settings['email']['smtp_host']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 500px;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="smtp_port" class="font-md font-bold mb-1 d-block">SMTP Port</label>
                        <input type="number" id="smtp_port" name="settings[smtp_port]" 
                               value="<?= e($settings['email']['smtp_port']['setting_value'] ?? '587') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="smtp_username" class="font-md font-bold mb-1 d-block">SMTP Username</label>
                        <input type="text" id="smtp_username" name="settings[smtp_username]" 
                               value="<?= e($settings['email']['smtp_username']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 400px;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="smtp_password" class="font-md font-bold mb-1 d-block">SMTP Password</label>
                        <input type="password" id="smtp_password" name="settings[smtp_password]" 
                               value="<?= e($settings['email']['smtp_password']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 400px;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="mail_from_address" class="font-md font-bold mb-1 d-block">Alamat Pengirim (Mail From Email)</label>
                        <input type="email" id="mail_from_address" name="settings[mail_from_address]" 
                               value="<?= e($settings['email']['mail_from_address']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 400px;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="mail_from_name" class="font-md font-bold mb-1 d-block">Nama Pengirim (Mail From Name)</label>
                        <input type="text" id="mail_from_name" name="settings[mail_from_name]" 
                               value="<?= e($settings['email']['mail_from_name']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 400px;">
                    </div>
                <?php endif; ?>

                <!-- Tab: BACKUP -->
                <?php if ($activeTab === 'backup'): ?>
                    <h2 class="font-lg font-bold mb-3 border-bottom pb-2">Pengaturan Pencadangan Otomatis</h2>

                    <div class="form-group mb-3">
                        <label for="backup_enabled" class="font-md font-bold mb-1 d-block">Aktifkan Backup Otomatis</label>
                        <select id="backup_enabled" name="settings[backup_enabled]" class="form-control form-control-accessible" style="width: 100%; max-width: 200px;">
                            <?php
                            $isEnabled = $settings['backup']['backup_enabled']['setting_value'] ?? 'true';
                            ?>
                            <option value="true" <?= $isEnabled === 'true' ? 'selected' : '' ?>>Aktif</option>
                            <option value="false" <?= $isEnabled === 'false' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="backup_schedule" class="font-md font-bold mb-1 d-block">Jadwal Backup</label>
                        <select id="backup_schedule" name="settings[backup_schedule]" class="form-control form-control-accessible" style="width: 100%; max-width: 200px;">
                            <?php
                            $schedule = $settings['backup']['backup_schedule']['setting_value'] ?? 'daily';
                            $options = ['daily' => 'Setiap Hari', 'weekly' => 'Setiap Minggu', 'monthly' => 'Setiap Bulan'];
                            foreach ($options as $optKey => $optVal):
                            ?>
                                <option value="<?= e($optKey) ?>" <?= $schedule === $optKey ? 'selected' : '' ?>><?= e($optVal) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="backup_time" class="font-md font-bold mb-1 d-block">Waktu Eksekusi Backup (Jam)</label>
                        <input type="text" id="backup_time" name="settings[backup_time]" 
                               value="<?= e($settings['backup']['backup_time']['setting_value'] ?? '02:00') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" placeholder="HH:ii" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="backup_retention_days" class="font-md font-bold mb-1 d-block">Retensi Berkas Backup (Hari)</label>
                        <input type="number" id="backup_retention_days" name="settings[backup_retention_days]" 
                               value="<?= e($settings['backup']['backup_retention_days']['setting_value'] ?? '30') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 200px;" min="1" required>
                        <small class="text-muted d-block mt-1">Berkas backup yang lebih lama dari X hari akan dihapus otomatis.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="backup_path" class="font-md font-bold mb-1 d-block">Direktori Penyimpanan Backup</label>
                        <input type="text" id="backup_path" name="settings[backup_path]" 
                               value="<?= e($settings['backup']['backup_path']['setting_value'] ?? '') ?>" 
                               class="form-control form-control-accessible" style="width: 100%; max-width: 600px;" required>
                    </div>
                <?php endif; ?>

                <!-- Form Submit and Actions -->
                <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary btn-accessible-lg font-md" style="padding: 12px 30px;">
                        💾 Simpan Perubahan
                    </button>
                    <a href="<?= url('dashboard') ?>" class="btn btn-light btn-accessible-lg font-md" style="padding: 12px 30px; text-decoration: none; display: inline-block; line-height: 24px; text-align: center;">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
