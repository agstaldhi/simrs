<?php
/**
 * Audit Trail Log View
 * Displays the security-related activities and system events with pagination and filtering.
 */

$auditLogs = $data['auditLogs'] ?? [];
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$totalRecords = $data['totalRecords'] ?? 0;
$actions = $data['actions'] ?? [];
$filters = $data['filters'] ?? [];

// Helper to determine action badges styling
function getActionBadgeStyle($action)
{
    $action = strtolower($action);
    switch ($action) {
        case 'create':
        case 'insert':
            return 'background-color: #d3f9d8; color: #2b8a3e; border: 1px solid #b2f2bb;';
        case 'update':
        case 'edit':
            return 'background-color: #fff4e6; color: #d9480f; border: 1px solid #ffe8cc;';
        case 'delete':
        case 'remove':
            return 'background-color: #fff5f5; color: #c92a2a; border: 1px solid #ffc9c9;';
        case 'login':
            return 'background-color: #e7f5ff; color: #1c7ed6; border: 1px solid #a5d8ff;';
        case 'logout':
            return 'background-color: #f8f9fa; color: #495057; border: 1px solid #e9ecef;';
        case 'backup':
            return 'background-color: #f3f0ff; color: #6f2db8; border: 1px solid #eebefa;';
        default:
            return 'background-color: #f1f3f5; color: #495057; border: 1px solid #dee2e6;';
    }
}
?>

<div class="audit-container mt-3">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1 class="font-xl font-bold">🔍 Jejak Audit Keamanan (Audit Trail)</h1>
        <p class="text-muted font-md">Memantau riwayat aktivitas transaksi medis, perubahan konfigurasi, dan aktivitas login pengguna SIMRS demi kepatuhan regulasi data rekam medis.</p>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('settings/audit') ?>" method="GET" class="row align-items-end" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="url" value="settings/audit">
                
                <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                    <label for="start_date" class="form-label font-md font-bold mb-1 d-block">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" value="<?= e($filters['start_date'] ?? '') ?>" class="form-control form-control-accessible" style="width: 100%; height: 40px; padding: 6px 12px; border-radius: 4px; border: 1px solid #ccc;">
                </div>

                <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                    <label for="end_date" class="form-label font-md font-bold mb-1 d-block">Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date" value="<?= e($filters['end_date'] ?? '') ?>" class="form-control form-control-accessible" style="width: 100%; height: 40px; padding: 6px 12px; border-radius: 4px; border: 1px solid #ccc;">
                </div>

                <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                    <label for="username" class="form-label font-md font-bold mb-1 d-block">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($filters['username'] ?? '') ?>" class="form-control form-control-accessible" style="width: 100%; height: 40px; padding: 6px 12px; border-radius: 4px; border: 1px solid #ccc;" placeholder="Cari user...">
                </div>

                <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                    <label for="action" class="form-label font-md font-bold mb-1 d-block">Aksi</label>
                    <select id="action" name="action" class="form-control form-control-accessible" style="width: 100%; height: 40px; padding: 6px 12px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="">-- Semua Aksi --</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= e($act) ?>" <?= ($filters['action'] ?? '') === $act ? 'selected' : '' ?>><?= strtoupper(e($act)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="display: flex; gap: 8px; margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary font-bold font-md" style="padding: 10px 20px; height: 40px; border-radius: 4px; background-color: #007bff; border: none; color: #fff; cursor: pointer;">
                        🔍 Filter
                    </button>
                    <a href="<?= url('settings/audit') ?>" class="btn btn-light font-bold font-md" style="padding: 10px 20px; height: 40px; border: 1px solid #ccc; text-decoration: none; text-align: center; color: #333; line-height: 20px; border-radius: 4px; background-color: #f8f9fa;">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Listing Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light" style="padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h2 class="font-md font-bold text-primary mb-0">📜 Catatan Aktivitas Sistem</h2>
            <span class="badge badge-light text-muted font-sm" style="font-size: 13px; font-weight: normal; padding: 4px 8px; border: 1px solid #dee2e6; border-radius: 4px;">
                Total Log Ditemukan: <strong><?= number_format($totalRecords) ?></strong> baris
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed;">
                    <thead>
                        <tr style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 12px; font-weight: bold; width: 180px;" class="font-md">Waktu Log</th>
                            <th style="padding: 12px; font-weight: bold; width: 130px;" class="font-md">Username</th>
                            <th style="padding: 12px; font-weight: bold; width: 110px; text-align: center;" class="font-md">Aksi</th>
                            <th style="padding: 12px; font-weight: bold; width: 130px;" class="font-md">Modul</th>
                            <th style="padding: 12px; font-weight: bold; width: 160px;" class="font-md">Tabel (Record ID)</th>
                            <th style="padding: 12px; font-weight: bold; width: 320px;" class="font-md">Deskripsi / Detail Aktivitas</th>
                            <th style="padding: 12px; font-weight: bold; width: 130px;" class="font-md">IP Address</th>
                            <th style="padding: 12px; font-weight: bold; width: 220px;" class="font-md">User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($auditLogs)): ?>
                            <tr>
                                <td colspan="8" style="padding: 24px; text-align: center;" class="text-muted font-md">Tidak ditemukan data log aktivitas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr style="border-bottom: 1px solid #dee2e6; font-size: 14px;">
                                    <!-- Timestamp -->
                                    <td style="padding: 12px; vertical-align: top; white-space: nowrap;" class="text-muted font-sm">
                                        <?= e(formatDatetime($log['created_at'])) ?>
                                    </td>
                                    
                                    <!-- User -->
                                    <td style="padding: 12px; vertical-align: top; font-weight: bold; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="font-sm">
                                        <?= e($log['username'] ?: 'System') ?>
                                    </td>
                                    
                                    <!-- Action Badge -->
                                    <td style="padding: 12px; vertical-align: top; text-align: center;">
                                        <span class="badge" style="font-size: 12px; padding: 4px 8px; font-weight: bold; border-radius: 4px; display: inline-block; text-transform: uppercase; <?= getActionBadgeStyle($log['action']) ?>">
                                            <?= e($log['action']) ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Module -->
                                    <td style="padding: 12px; vertical-align: top; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="font-sm">
                                        <code><?= e($log['module']) ?></code>
                                    </td>
                                    
                                    <!-- Table & ID -->
                                    <td style="padding: 12px; vertical-align: top; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="font-sm text-muted">
                                        <?php if ($log['table_name']): ?>
                                            <?= e($log['table_name']) ?> (ID: <?= (int)$log['record_id'] ?>)
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Description -->
                                    <td style="padding: 12px; vertical-align: top; word-wrap: break-word;" class="font-sm">
                                        <?= e($log['description']) ?>
                                    </td>
                                    
                                    <!-- IP Address -->
                                    <td style="padding: 12px; vertical-align: top; white-space: nowrap;" class="font-sm text-muted">
                                        <?= e($log['ip_address'] ?: '127.0.0.1') ?>
                                    </td>
                                    
                                    <!-- User Agent -->
                                    <td style="padding: 12px; vertical-align: top; font-size: 11px; color: #868e96; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($log['user_agent']) ?>">
                                        <?= e($log['user_agent']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-light p-3" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #dee2e6; flex-wrap: wrap; gap: 12px;">
                <div class="text-muted font-sm">
                    Menampilkan Halaman <strong><?= $currentPage ?></strong> dari <strong><?= $totalPages ?></strong> (Total <strong><?= number_format($totalRecords) ?></strong> log)
                </div>
                <div style="display: flex; gap: 4px;">
                    <?php 
                    $queryParams = $_GET;
                    
                    // Helper to get page url
                    $getPageUrl = function($p) use ($queryParams) {
                        $queryParams['page'] = $p;
                        return url('settings/audit') . '?' . http_build_query($queryParams);
                    };
                    
                    if ($currentPage > 1): 
                    ?>
                        <a href="<?= $getPageUrl($currentPage - 1) ?>" class="btn btn-sm btn-outline-primary" style="padding: 6px 12px; font-weight: bold; text-decoration: none; border: 1px solid #007bff; border-radius: 4px; color: #007bff; background-color: #fff;">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php 
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    for ($p = $startPage; $p <= $endPage; $p++): 
                    ?>
                        <a href="<?= $getPageUrl($p) ?>" class="btn btn-sm <?= $p == $currentPage ? 'btn-primary' : 'btn-outline-primary' ?>" style="padding: 6px 12px; font-weight: bold; text-decoration: none; border: 1px solid #007bff; border-radius: 4px; <?= $p == $currentPage ? 'background-color: #007bff; color: #fff;' : 'background-color: #fff; color: #007bff;' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= $getPageUrl($currentPage + 1) ?>" class="btn btn-sm btn-outline-primary" style="padding: 6px 12px; font-weight: bold; text-decoration: none; border: 1px solid #007bff; border-radius: 4px; color: #007bff; background-color: #fff;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
