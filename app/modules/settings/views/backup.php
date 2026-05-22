<?php
/**
 * Database Backup & Restore View
 * Provides a manual database dump interface and lists existing backup SQL files.
 */
?>

<div class="backup-container mt-3">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1 class="font-xl font-bold">💾 Pencadangan & Pemulihan Database (Backup & Restore)</h1>
        <p class="text-muted font-md">Cadangkan seluruh tabel database SIMRS secara manual ke dalam folder lokal yang aman atau kelola berkas cadangan yang ada.</p>
    </div>

    <!-- Backup Controls Panel -->
    <div class="card shadow-sm mb-4" style="border-left: 5px solid #2b8a3e;">
        <div class="card-body p-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div style="max-width: 600px;">
                <h2 class="font-lg font-bold mb-2">Jalankan Pencadangan Database Manual</h2>
                <p class="text-muted font-sm mb-0">
                    Proses ini akan mengumpulkan seluruh struktur tabel dan data transaksi medis dalam format SQL. Berkas SQL yang dihasilkan akan disimpan di folder terproteksi <code>storage/backups/</code>.
                </p>
            </div>
            
            <form action="<?= url('settings/backup/run') ?>" method="POST" class="mb-0">
                <?= CSRF::getField() ?>
                <button type="submit" class="btn btn-primary btn-accessible-lg font-bold font-md" style="padding: 14px 28px; background-color: #2b8a3e; border-color: #2b8a3e; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    🔄 Jalankan Backup Sekarang
                </button>
            </form>
        </div>
    </div>

    <!-- Backup Files Directory -->
    <div class="card shadow-sm">
        <div class="card-header bg-light" style="padding: 16px;">
            <h2 class="font-md font-bold text-primary mb-0">📁 Daftar Berkas Cadangan SQL</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Nama Berkas SQL</th>
                            <th style="padding: 16px; font-weight: bold; text-align: right;" class="font-md">Ukuran Berkas</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Tanggal & Waktu Pembuatan</th>
                            <th style="padding: 16px; font-weight: bold; text-align: center;" class="font-md">Aksi Kontrol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="4" style="padding: 24px; text-align: center;" class="text-muted font-md">Belum ada berkas cadangan database yang tersimpan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 16px;" class="font-md">
                                        <code style="background-color: #f1f3f5; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 14px;">
                                            <?= e($backup['name']) ?>
                                        </code>
                                    </td>
                                    <td style="padding: 16px; text-align: right;" class="font-md font-bold">
                                        <?= e(formatFileSize($backup['size'])) ?>
                                    </td>
                                    <td style="padding: 16px;" class="font-md text-muted">
                                        <?= e(date('d-m-Y H:i:s', $backup['created_at'])) ?> WIB
                                    </td>
                                    <td style="padding: 16px; text-align: center;" class="font-md">
                                        <div style="display: inline-flex; gap: 8px;">
                                            <!-- Download Link -->
                                            <a href="<?= url('settings/backup?action=download&file=' . urlencode($backup['name'])) ?>" 
                                               class="btn btn-light" 
                                               style="text-decoration: none; padding: 6px 12px; font-size: 14px; border: 1px solid #ced4da; border-radius: 4px; font-weight: bold; color: #495057;"
                                               title="Unduh berkas cadangan ini">
                                                ⬇️ Unduh SQL
                                            </a>
                                            
                                            <!-- Delete Link (No modals, inline confirmation) -->
                                            <a href="<?= url('settings/backup?action=delete&file=' . urlencode($backup['name'])) ?>" 
                                               class="btn btn-danger" 
                                               style="text-decoration: none; padding: 6px 12px; font-size: 14px; background-color: #fa5252; color: #fff; border-radius: 4px; font-weight: bold;" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus berkas backup <?= e($backup['name']) ?> secara permanen dari server?');"
                                               title="Hapus berkas dari server">
                                                🗑️ Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
