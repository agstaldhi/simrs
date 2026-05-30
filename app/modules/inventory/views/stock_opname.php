<?php
/**
 * View Stock Opname Records
 */
$opnames = $data['opnames'] ?? [];
$items = $data['items'] ?? [];
$filters = $data['filters'] ?? [];
$action = $data['action'] ?? 'list';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Pencatatan Stock Opname</h1>
            <p class="page-subtitle text-muted font-md">Riwayat penyesuaian stok berkala antara catatan sistem SIMRS dengan perhitungan fisik di gudang.</p>
        </div>
        <div class="page-action" style="display: flex; gap: 8px;">
            <?php if ($action === 'list'): ?>
                <a href="<?= url('inventory/stock-opname?action=add') ?>" class="btn btn-primary font-bold font-md">
                    ➕ Buat Stock Opname
                </a>
                <a href="<?= url('inventory/items') ?>" class="btn btn-outline-primary font-bold font-md">
                    <i class="fa fa-boxes"></i> Lihat Stok Saat Ini
                </a>
            <?php else: ?>
                <a href="<?= url('inventory/stock-opname') ?>" class="btn btn-outline-secondary font-bold font-md">
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

    <!-- Add Stock Opname Form -->
    <?php if ($action === 'add'): ?>
        <div class="card accessible-card border-primary mt-4 mb-4" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">
                <h2 class="card-title font-lg text-white mb-0">📅 Buat Stock Opname Baru</h2>
            </div>
            <div class="card-body">
                <form action="<?= url('inventory/stock-opname/store') ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="opname_date" class="form-label font-md font-bold">Tanggal Opname <span class="text-danger">*</span></label>
                            <input type="date" id="opname_date" name="opname_date" value="<?= date('Y-m-d') ?>" class="form-control form-control-accessible" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label font-md font-bold">Lokasi Gudang <span class="text-danger">*</span></label>
                            <input type="text" id="location" name="location" class="form-control form-control-accessible" placeholder="Contoh: Gudang Utama" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="item_id" class="form-label font-md font-bold">Pilih Barang/Item Medis <span class="text-danger">*</span></label>
                        <select id="item_id" name="item_id" class="form-control form-control-accessible" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?= $item['id'] ?>">
                                    <?= e($item['name']) ?> (Kode: <?= e($item['code']) ?>) - Stok Sistem: <?= e($item['current_stock']) ?> <?= e($item['unit']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="physical_stock" class="form-label font-md font-bold">Stok Fisik Sebenarnya <span class="text-danger">*</span></label>
                        <input type="number" id="physical_stock" name="physical_stock" class="form-control form-control-accessible" placeholder="Ketik jumlah perhitungan fisik..." required>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label font-md font-bold">Catatan Penyesuaian</label>
                        <textarea id="notes" name="notes" rows="3" class="form-control form-control-accessible" placeholder="Alasan perbedaan stok atau keterangan lainnya..."></textarea>
                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary font-bold font-md py-2 px-4">
                            💾 Simpan Penyesuaian
                        </button>
                        <a href="<?= url('inventory/stock-opname') ?>" class="btn btn-outline-secondary font-bold font-md py-2 px-4" style="text-decoration: none;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters (Only on list) -->
    <?php if ($action === 'list'): ?>
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
    <?php endif; ?>
</div>
