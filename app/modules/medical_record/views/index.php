<?php
/**
 * View Medical Records Index Page
 */
$records = $data['records'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Rekam Medis Pasien</h1>
            <p class="page-subtitle text-muted font-md">Pencarian rekam medis elektronik (RME) pasien, keluhan SOAP, vital signs, dan diagnosa.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('medical-record/create') ?>" class="btn btn-primary font-bold font-md">
                <i class="fa fa-plus-circle"></i> Input SOAP Baru
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('medical-record') ?>" method="GET" class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Pasien / No. RM</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nama pasien atau nomor rekam medis...">
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="date" class="form-label font-md font-bold">Tanggal Pemeriksaan</label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        value="<?= e($filters['date'] ?? '') ?>" 
                        class="form-control form-control-accessible">
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-search"></i> Cari Rekam Medis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Records Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Riwayat Elektronik Rekam Medis</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">Tanggal & Kunjungan</th>
                            <th scope="col" class="font-md">No. RM & Nama Pasien</th>
                            <th scope="col" class="font-md">Dokter Pemeriksa</th>
                            <th scope="col" class="font-md">Ringkasan Medis (SOAP)</th>
                            <th scope="col" class="font-md text-center">Status</th>
                            <th scope="col" class="font-md text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Tidak ditemukan riwayat rekam medis yang sesuai kriteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $rec): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold"><?= date('d/m/Y H:i', strtotime($rec['created_at'])) ?></div>
                                        <small class="text-highlight font-bold"><?= e($rec['visit_number'] ?? 'Tanpa Registrasi') ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold"><?= e($rec['patient_name']) ?></div>
                                        <small class="text-muted font-bold">No. RM: <?= e($rec['medical_record_number']) ?></small>
                                    </td>
                                    <td>
                                        <div class="font-bold">Dr. <?= e($rec['doctor_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><span class="badge bg-soft-primary text-primary font-bold">S</span> <?= e(mb_strimwidth($rec['subjective'] ?? '', 0, 50, '...')) ?></div>
                                        <div class="mb-1"><span class="badge bg-soft-success text-success font-bold">O</span> <?= e(mb_strimwidth($rec['objective'] ?? '', 0, 50, '...')) ?></div>
                                        <div class="mb-1"><span class="badge bg-soft-warning text-warning font-bold">A</span> <?= e(mb_strimwidth($rec['assessment'] ?? '', 0, 50, '...')) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-soft-success text-success">Lengkap</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= url('medical-record/detail/' . $rec['id']) ?>" class="btn btn-outline-primary btn-sm font-bold">
                                            <i class="fa fa-folder-open"></i> Buka RME
                                        </a>
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
