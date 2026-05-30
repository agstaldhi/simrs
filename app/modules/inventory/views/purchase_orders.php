<?php
/**
 * View Purchase Orders
 */
$orders = $data['orders'] ?? [];
$suppliers = $data['suppliers'] ?? [];
$filters = $data['filters'] ?? [];
$action = $data['action'] ?? 'list';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Purchase Orders (PO) Pengadaan</h1>
            <p class="page-subtitle text-muted font-md">Kelola pemesanan pembelian logistik dan obat-obatan dari distributor / supplier luar rumah sakit.</p>
        </div>
        <div class="page-action" style="display: flex; gap: 8px;">
            <?php if ($action === 'list'): ?>
                <a href="<?= url('inventory/purchase-orders?action=add') ?>" class="btn btn-primary font-bold font-md">
                    ➕ Buat PO Baru
                </a>
                <a href="<?= url('inventory/suppliers') ?>" class="btn btn-outline-primary font-bold font-md">
                    <i class="fa fa-truck"></i> Kelola Supplier Vendor
                </a>
            <?php else: ?>
                <a href="<?= url('inventory/purchase-orders') ?>" class="btn btn-outline-secondary font-bold font-md">
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

    <!-- Add PO Form -->
    <?php if ($action === 'add'): ?>
        <div class="card accessible-card border-primary mt-4 mb-4" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">
                <h2 class="card-title font-lg text-white mb-0">🛒 Buat Purchase Order (PO) Baru</h2>
            </div>
            <div class="card-body">
                <form action="<?= url('inventory/purchase-orders/store') ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div class="mb-3">
                        <label for="supplier_id" class="form-label font-md font-bold">Supplier / Vendor <span class="text-danger">*</span></label>
                        <select id="supplier_id" name="supplier_id" class="form-control form-control-accessible" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= e($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="po_date" class="form-label font-md font-bold">Tanggal PO <span class="text-danger">*</span></label>
                            <input type="date" id="po_date" name="po_date" value="<?= date('Y-m-d') ?>" class="form-control form-control-accessible" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expected_delivery_date" class="form-label font-md font-bold">Estimasi Pengiriman</label>
                            <input type="date" id="expected_delivery_date" name="expected_delivery_date" class="form-control form-control-accessible">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="po_type" class="form-label font-md font-bold">Jenis PO</label>
                            <select id="po_type" name="po_type" class="form-control form-control-accessible">
                                <option value="mixed">Campuran / Mixed</option>
                                <option value="medicine">Obat-obatan</option>
                                <option value="medical_supplies">Alat/BMHP Medis</option>
                                <option value="general">Logistik Umum</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="total_amount" class="form-label font-md font-bold">Total Nilai PO (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="total_amount" name="total_amount" value="0" class="form-control form-control-accessible" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label font-md font-bold">Catatan PO</label>
                        <textarea id="notes" name="notes" rows="3" class="form-control form-control-accessible" placeholder="Catatan atau spesifikasi barang yang dipesan..."></textarea>
                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary font-bold font-md py-2 px-4">
                            💾 Simpan PO
                        </button>
                        <a href="<?= url('inventory/purchase-orders') ?>" class="btn btn-outline-secondary font-bold font-md py-2 px-4" style="text-decoration: none;">
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
                <form action="<?= url('inventory/purchase-orders') ?>" method="GET" class="row align-items-end">
                    <div class="col-md-5 mb-3 mb-md-0">
                        <label for="search" class="form-label font-md font-bold">Cari No. PO / Supplier</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            value="<?= e($filters['search'] ?? '') ?>" 
                            class="form-control form-control-accessible" 
                            placeholder="Ketik nomor PO atau nama supplier...">
                    </div>

                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="status" class="form-label font-md font-bold">Status Pemesanan (PO)</label>
                        <select id="status" name="status" class="form-control form-control-accessible">
                            <option value="">-- Semua Status --</option>
                            <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="submitted" <?= ($filters['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Diajukan (Submitted)</option>
                            <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Disetujui (Approved)</option>
                            <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Selesai (Completed)</option>
                            <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                            <i class="fa fa-filter"></i> Saring PO
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- PO Table -->
        <div class="card accessible-card">
            <div class="card-header bg-light">
                <h2 class="card-title font-lg text-primary mb-0">Daftar Purchase Orders</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="font-md">No. PO / Tanggal</th>
                                <th scope="col" class="font-md">Vendor / Supplier</th>
                                <th scope="col" class="font-md">Jenis PO</th>
                                <th scope="col" class="font-md text-center">Jumlah Barang</th>
                                <th scope="col" class="font-md text-right">Estimasi Kirim</th>
                                <th scope="col" class="font-md text-right">Total Nilai PO</th>
                                <th scope="col" class="font-md text-center">Status PO</th>
                                <th scope="col" class="font-md text-center">Status Bayar</th>
                                <th scope="col" class="font-md text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Tidak ditemukan purchase order.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="font-bold text-primary"><?= e($row['po_number']) ?></div>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($row['po_date'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= e($row['supplier_name']) ?></div>
                                            <small class="text-muted font-bold">Telp: <?= e($row['supplier_phone'] ?: '-') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark font-bold">
                                                <?= strtoupper($row['po_type']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center font-bold"><?= e($row['total_items']) ?> item</td>
                                        <td class="text-right">
                                            <?= $row['expected_delivery_date'] ? date('d/m/Y', strtotime($row['expected_delivery_date'])) : '-' ?>
                                        </td>
                                        <td class="text-right font-bold text-primary">
                                            <?= formatRupiah($row['total_amount']) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['po_status'] === 'completed'): ?>
                                                <span class="badge bg-soft-success text-success font-bold">Completed</span>
                                            <?php elseif ($row['po_status'] === 'approved'): ?>
                                                <span class="badge bg-soft-primary text-primary font-bold">Approved</span>
                                            <?php elseif ($row['po_status'] === 'draft'): ?>
                                                <span class="badge bg-soft-secondary text-secondary font-bold">Draft</span>
                                            <?php else: ?>
                                                <span class="badge bg-soft-warning text-warning font-bold"><?= ucfirst($row['po_status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success font-bold text-white py-1">Lunas</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger font-bold text-white py-1">Belum Lunas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <form action="<?= url('inventory/purchase-orders/delete/' . $row['id']) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Purchase Order ini?');">
                                                <?= CSRF::getField() ?>
                                                <button type="submit" class="btn btn-sm btn-danger font-bold" style="padding: 2px 6px; font-size: 12px; border: none;">
                                                    🗑️ Hapus
                                                </button>
                                            </form>
                                        </td>
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
