<?php
/**
 * View Stock Opname Records
 */
$opnames = $data['opnames'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Pencatatan Stock Opname</h1>
            <p class="page-subtitle text-muted font-md">Riwayat penyesuaian stok berkala antara catatan sistem SIMRS dengan perhitungan fisik di gudang.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('inventory/items') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-boxes"></i> Lihat Stok Saat Ini
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('inventory/stock-opname') ?>" method="GET" class="row align-items-end">
                <div class="col-md-9 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Nomor Opname / Lokasi</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nomor opname atau nama gudang lokasi...">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-search"></i> Cari Opname
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Opname Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Riwayat Penyesuaian Stok (Opname)</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">No. Opname / Tanggal</th>
                            <th scope="col" class="font-md">Lokasi Gudang</th>
                            <th scope="col" class="font-md">Jenis Opname</th>
                            <th scope="col" class="font-md text-center">Total Item</th>
                            <th scope="col" class="font-md text-center">Total Selisih</th>
                            <th scope="col" class="font-md">Petugas Pelaksana</th>
                            <th scope="col" class="font-md text-center">Status</th>
                            <th scope="col" class="font-md">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($opnames)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ditemukan riwayat stock opname.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($opnames as $op): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold text-primary"><?= e($op['opname_number']) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($op['opname_date'])) ?></small>
                                    </td>
                                    <td><div class="font-bold"><?= e($op['location']) ?></div></td>
                                    <td>
                                        <span class="badge bg-light text-dark font-bold">
                                            <?= strtoupper($op['opname_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center font-bold"><?= e($op['total_items']) ?> item</td>
                                    <td class="text-center font-bold <?= $op['total_discrepancy'] != 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= e($op['total_discrepancy']) ?>
                                    </td>
                                    <td><?= e($op['performed_by_name'] ?: 'System') ?></td>
                                    <td class="text-center">
                                        <?php if ($op['status'] === 'approved'): ?>
                                            <span class="badge bg-soft-success text-success font-bold"><i class="fa fa-check-double"></i> Approved</span>
                                        <?php elseif ($op['status'] === 'completed'): ?>
                                            <span class="badge bg-soft-primary text-primary font-bold">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-warning text-warning font-bold"><?= ucfirst($op['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= e($op['notes'] ?: '-') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
