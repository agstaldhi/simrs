<?php
/**
 * Patient listing view
 * Displays a list of all active patients with search and pagination.
 */
?>

<div class="patients-container mt-3">
    
    <!-- Page Header -->
    <div class="page-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="font-xl font-bold">👤 Manajemen Data Pasien</h1>
            <p class="text-muted font-md">Cari, daftarkan, dan kelola informasi rekam medis serta identitas sosial pasien.</p>
        </div>
        
        <?php if (in_array('patients.create', $_SESSION['permissions'] ?? [])): ?>
            <a href="<?= url('patient/create') ?>" class="btn btn-primary btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px;">
                ➕ Daftar Pasien Baru
            </a>
        <?php endif; ?>
    </div>

    <!-- Search Form Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('patient') ?>" class="d-flex flex-wrap gap-2 align-items-end" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                    <label for="search" class="font-md font-bold mb-1 d-block">Cari Pasien (Nama, No. RM, NIK, HP)</label>
                    <input type="text" id="search" name="search" value="<?= e($search) ?>" 
                           class="form-control form-control-accessible" style="width: 100%;" 
                           placeholder="Ketik nama pasien, nomor RM, NIK, atau nomor HP...">
                </div>
                <button type="submit" class="btn btn-primary btn-accessible-lg" style="padding: 12px 24px; font-weight: bold;">
                    🔍 Cari Data
                </button>
                <?php if (!empty($search)): ?>
                    <a href="<?= url('patient') ?>" class="btn btn-light btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; border: 1px solid #ccc; font-weight: bold; line-height: 24px;">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Patients List Table Card -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 16px; font-weight: bold;" class="font-md">No. RM</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">NIK</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Nama Pasien</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">L/P</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Tgl Lahir / Umur</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Nomor HP</th>
                            <th style="padding: 16px; font-weight: bold;" class="font-md">Jenis Jaminan</th>
                            <th style="padding: 16px; font-weight: bold; text-align: center;" class="font-md">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients['data'])): ?>
                            <tr>
                                <td colspan="8" style="padding: 32px; text-align: center;" class="text-muted font-md">
                                    Tidak ditemukan data pasien yang sesuai dengan kata kunci pencarian.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($patients['data'] as $p): ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 16px;" class="font-md">
                                        <span class="badge bg-soft-primary text-primary font-bold" style="background-color: #e7f5ff; color: #1c7ed6; padding: 4px 8px; border-radius: 4px;">
                                            <?= e($p['medical_record_number']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 16px;" class="font-md text-muted"><?= e($p['nik'] ?: '-') ?></td>
                                    <td style="padding: 16px;" class="font-md">
                                        <strong><?= e($p['title']) ?>. <?= e($p['full_name']) ?></strong>
                                    </td>
                                    <td style="padding: 16px;" class="font-md">
                                        <?= $p['gender'] === 'male' ? 'Laki-laki (L)' : 'Perempuan (P)' ?>
                                    </td>
                                    <td style="padding: 16px;" class="font-md">
                                        <?= date('d-m-Y', strtotime($p['birth_date'])) ?> 
                                        <span class="text-muted font-sm">(<?= calculateAge($p['birth_date']) ?> Thn)</span>
                                    </td>
                                    <td style="padding: 16px;" class="font-md"><?= e($p['mobile'] ?: $p['phone'] ?: '-') ?></td>
                                    <td style="padding: 16px;" class="font-md">
                                        <?php if ($p['insurance_type'] === 'bpjs'): ?>
                                            <span style="color: #2b8a3e; font-weight: bold;">🟢 BPJS Kesehatan</span>
                                        <?php elseif ($p['insurance_type'] === 'umum'): ?>
                                            <span style="color: #495057; font-weight: bold;">⚪ Umum (Mandiri)</span>
                                        <?php else: ?>
                                            <span style="color: #e03131; font-weight: bold;">🟡 Asuransi Swasta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;" class="font-md">
                                        <div style="display: inline-flex; gap: 8px; justify-content: center;">
                                            <a href="<?= url('patient/detail/' . $p['id']) ?>" class="btn btn-info btn-accessible-lg" style="text-decoration: none; padding: 8px 12px; font-size: 14px; font-weight: bold; background-color: #17a2b8; color: #fff; border-radius: 4px;">
                                                👁️ Detail
                                            </a>
                                            <?php if (in_array('patients.edit', $_SESSION['permissions'] ?? [])): ?>
                                                <a href="<?= url('patient/edit/' . $p['id']) ?>" class="btn btn-warning btn-accessible-lg" style="text-decoration: none; padding: 8px 12px; font-size: 14px; font-weight: bold; background-color: #ffc107; color: #212529; border-radius: 4px;">
                                                    ✏️ Edit
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (!empty($patients['data']) && $patients['total_pages'] > 1): ?>
                <div class="card-footer p-3 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #dee2e6; padding: 16px; background-color: #f8f9fa;">
                    <div class="text-muted font-sm">
                        Menampilkan Halaman <strong><?= (int)$patients['current_page'] ?></strong> dari <strong><?= (int)$patients['total_pages'] ?></strong> (Total <strong><?= (int)$patients['total'] ?></strong> Pasien)
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <?php if ($patients['has_prev']): ?>
                            <a href="<?= url('patient?page=' . ($patients['current_page'] - 1) . '&search=' . urlencode($search)) ?>" class="btn btn-light btn-accessible-lg" style="text-decoration: none; border: 1px solid #ccc; padding: 8px 16px; font-weight: bold;">
                                ◀️ Sebelumnya
                            </a>
                        <?php endif; ?>
                        <?php if ($patients['has_next']): ?>
                            <a href="<?= url('patient?page=' . ($patients['current_page'] + 1) . '&search=' . urlencode($search)) ?>" class="btn btn-light btn-accessible-lg" style="text-decoration: none; border: 1px solid #ccc; padding: 8px 16px; font-weight: bold;">
                                Selanjutnya ▶️
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
