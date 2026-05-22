<?php
/**
 * View Employee Leaves Approvals
 */
$pendingLeaves = $data['pendingLeaves'] ?? [];
$historyLeaves = $data['historyLeaves'] ?? [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Persetujuan Cuti Pegawai</h1>
            <p class="page-subtitle text-muted font-md">Tinjau dan proses permohonan cuti tahunan, cuti sakit, maupun cuti melahirkan yang diajukan staf medis dan umum.</p>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
            <strong><?= $flash['type'] === 'error' ? 'Gagal!' : 'Sukses!' ?></strong> <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Pending Leaves -->
    <div class="card accessible-card border-warning mt-4">
        <div class="card-header bg-soft-warning">
            <h2 class="card-title font-lg text-warning mb-0"><i class="fa fa-clock"></i> Pengajuan Cuti Menunggu Persetujuan</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">No. Dokumen / NIP</th>
                            <th scope="col" class="font-md">Nama Karyawan</th>
                            <th scope="col" class="font-md">Unit / Jabatan</th>
                            <th scope="col" class="font-md">Jenis Cuti</th>
                            <th scope="col" class="font-md">Tanggal Cuti</th>
                            <th scope="col" class="font-md text-center">Durasi</th>
                            <th scope="col" class="font-md">Alasan Cuti</th>
                            <th scope="col" class="font-md text-center" style="width: 250px;">Aksi Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingLeaves)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ada pengajuan cuti yang perlu diproses saat ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingLeaves as $lv): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold text-primary"><?= e($lv['leave_number']) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($lv['requested_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold font-lg"><?= e($lv['full_name']) ?></div>
                                        <small class="text-muted">NIP: <?= e($lv['employee_number']) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($lv['department_name'] ?: 'Umum') ?></div>
                                        <div><?= e($lv['position']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark font-bold font-md">
                                            <?= strtoupper($lv['leave_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= date('d/m/Y', strtotime($lv['start_date'])) ?></div>
                                        <small class="text-muted">s/d <?= date('d/m/Y', strtotime($lv['end_date'])) ?></small>
                                    </td>
                                    <td class="text-center font-bold font-lg text-primary"><?= e($lv['total_days']) ?> Hari</td>
                                    <td><small><?= e($lv['reason']) ?></small></td>
                                    <td>
                                        <!-- Actions direct form (No Modals) -->
                                        <div class="p-2 border rounded bg-light">
                                            <form action="<?= url('hr/leaves') ?>" method="POST" class="mb-2">
                                                <?= CSRF::getField() ?>
                                                <input type="hidden" name="leave_id" value="<?= e($lv['id']) ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm btn-block font-bold">
                                                    <i class="fa fa-check"></i> Setujui (Approve)
                                                </button>
                                            </form>
                                            <form action="<?= url('hr/leaves') ?>" method="POST">
                                                <?= CSRF::getField() ?>
                                                <input type="hidden" name="leave_id" value="<?= e($lv['id']) ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="rejection_reason" class="form-control" placeholder="Alasan tolak..." required>
                                                    <div class="input-group-append">
                                                        <button type="submit" class="btn btn-danger font-bold">Tolak</button>
                                                    </div>
                                                </div>
                                            </form>
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

    <!-- History Leaves -->
    <div class="card accessible-card mt-4">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Riwayat Pengajuan Cuti Sebelumnya (Terakhir)</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">No. Dokumen</th>
                            <th scope="col" class="font-md">Karyawan</th>
                            <th scope="col" class="font-md">Jenis Cuti</th>
                            <th scope="col" class="font-md">Masa Cuti</th>
                            <th scope="col" class="font-md text-center">Durasi</th>
                            <th scope="col" class="font-md">Keputusan Oleh</th>
                            <th scope="col" class="font-md text-center">Status Akhir</th>
                            <th scope="col" class="font-md">Alasan Tolak (Jika Ditolak)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historyLeaves)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat cuti diproses.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historyLeaves as $hlv): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold"><?= e($hlv['leave_number']) ?></div>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($hlv['full_name']) ?></div>
                                        <small class="text-muted">NIP: <?= e($hlv['employee_number']) ?></small>
                                    </td>
                                    <td><?= strtoupper($hlv['leave_type']) ?></td>
                                    <td>
                                        <div><?= date('d/m/Y', strtotime($hlv['start_date'])) ?> s/d <?= date('d/m/Y', strtotime($hlv['end_date'])) ?></div>
                                    </td>
                                    <td class="text-center font-bold"><?= e($hlv['total_days']) ?> Hari</td>
                                    <td>
                                        <div><?= e($hlv['approved_by_name'] ?: '-') ?></div>
                                        <small class="text-muted"><?= $hlv['approved_at'] ? date('d/m/Y H:i', strtotime($hlv['approved_at'])) : '-' ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($hlv['status'] === 'approved'): ?>
                                            <span class="badge bg-soft-success text-success font-bold">Disetujui</span>
                                        <?php elseif ($hlv['status'] === 'rejected'): ?>
                                            <span class="badge bg-soft-danger text-danger font-bold">Ditolak</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted font-bold"><?= strtoupper($hlv['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-danger"><?= e($hlv['rejection_reason'] ?: '-') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
