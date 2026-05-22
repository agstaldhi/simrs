<?php
/**
 * View Purchase Orders
 */
$orders = $data['orders'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Purchase Orders (PO) Pengadaan</h1>
            <p class="page-subtitle text-muted font-md">Kelola pemesanan pembelian logistik dan obat-obatan dari distributor / supplier luar rumah sakit.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('inventory/suppliers') ?>" class="btn btn-outline-primary font-bold font-md">
                <i class="fa fa-truck"></i> Kelola Supplier Vendor
            </a>
        </div>
    </div>

    <!-- Filters -->
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ditemukan purchase order.</td>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
