<?php
/**
 * View Employee Leaves Approvals
 */
$pendingLeaves = $data['pendingLeaves'] ?? [];
$historyLeaves = $data['historyLeaves'] ?? [];
$employees = $data['employees'] ?? [];
$action = $data['action'] ?? 'list';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Persetujuan Cuti Pegawai</h1>
            <p class="page-subtitle text-muted font-md">Tinjau dan proses permohonan cuti tahunan, cuti sakit, maupun cuti melahirkan yang diajukan staf medis dan umum.</p>
        </div>
        <div class="page-action" style="display: flex; gap: 8px;">
            <?php if ($action === 'list'): ?>
                <a href="<?= url('hr/leaves?action=add') ?>" class="btn btn-primary font-bold font-md">
                    ➕ Ajukan Cuti Baru
                </a>
            <?php else: ?>
                <a href="<?= url('hr/leaves') ?>" class="btn btn-outline-secondary font-bold font-md">
                    ◀️ Kembali ke Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
            <strong><?= $flash['type'] === 'error' ? 'Gagal!' : 'Sukses!' ?></strong> <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- CRUD Form (Inline Page Action) -->
    <?php if ($action === 'add'): ?>
        <div class="card accessible-card border-primary mt-4 mb-4" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">
                <h2 class="card-title font-lg text-white mb-0">📝 Pengajuan Cuti Pegawai Baru</h2>
            </div>
            <div class="card-body">
                <form action="<?= url('hr/leaves/store') ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div class="mb-3">
                        <label for="employee_id" class="form-label font-md font-bold">Pegawai / Staf <span class="text-danger">*</span></label>
                        <select id="employee_id" name="employee_id" class="form-control form-control-accessible" required>
                            <option value="">-- Pilih Pegawai --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= e($emp['full_name']) ?> (NIP: <?= e($emp['employee_number']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="leave_type" class="form-label font-md font-bold">Jenis Cuti <span class="text-danger">*</span></label>
                        <select id="leave_type" name="leave_type" class="form-control form-control-accessible" required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            <option value="annual">Cuti Tahunan (Annual)</option>
                            <option value="sick">Cuti Sakit (Sick)</option>
                            <option value="maternity">Cuti Melahirkan (Maternity)</option>
                            <option value="other">Cuti Lainnya (Other)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label font-md font-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" id="start_date" name="start_date" class="form-control form-control-accessible" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label font-md font-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" id="end_date" name="end_date" class="form-control form-control-accessible" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label font-md font-bold">Alasan Cuti <span class="text-danger">*</span></label>
                        <textarea id="reason" name="reason" rows="3" class="form-control form-control-accessible" placeholder="Contoh: Keperluan keluarga, pemulihan kesehatan..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label font-md font-bold">Catatan Tambahan</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control form-control-accessible" placeholder="Catatan opsional..."></textarea>
                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary font-bold font-md py-2 px-4">
                            💾 Ajukan Cuti
                        </button>
                        <a href="<?= url('hr/leaves') ?>" class="btn btn-outline-secondary font-bold font-md py-2 px-4" style="text-decoration: none;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
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
