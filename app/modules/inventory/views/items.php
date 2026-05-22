<?php
/**
 * View Inventory Items
 */
$items = $data['items'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Inventaris & BMHP Gudang</h1>
            <p class="page-subtitle text-muted font-md">Monitor persediaan Barang Medis Habis Pakai (BMHP), alat kesehatan, elektronik, dan aset logistik non-obat lainnya.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('inventory/purchase-orders') ?>" class="btn btn-primary font-bold font-md">
                <i class="fa fa-shopping-cart"></i> Pengadaan Baru (PO)
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form action="<?= url('inventory/items') ?>" method="GET" class="row align-items-end">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label for="search" class="form-label font-md font-bold">Cari Nama / Kode Barang</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= e($filters['search'] ?? '') ?>" 
                        class="form-control form-control-accessible" 
                        placeholder="Ketik nama atau kode item...">
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="category" class="form-label font-md font-bold">Kategori Barang</label>
                    <select id="category" name="category" class="form-control form-control-accessible">
                        <option value="">-- Semua Kategori --</option>
                        <option value="medical_equipment" <?= ($filters['category'] ?? '') === 'medical_equipment' ? 'selected' : '' ?>>Peralatan Medis</option>
                        <option value="consumable" <?= ($filters['category'] ?? '') === 'consumable' ? 'selected' : '' ?>>Bahan Habis Pakai (Consumable)</option>
                        <option value="office_supplies" <?= ($filters['category'] ?? '') === 'office_supplies' ? 'selected' : '' ?>>ATK / Alat Kantor</option>
                        <option value="furniture" <?= ($filters['category'] ?? '') === 'furniture' ? 'selected' : '' ?>>Mebel / Furniture</option>
                        <option value="electronics" <?= ($filters['category'] ?? '') === 'electronics' ? 'selected' : '' ?>>Elektronik</option>
                        <option value="other" <?= ($filters['category'] ?? '') === 'other' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                        <i class="fa fa-filter"></i> Saring Barang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card accessible-card">
        <div class="card-header bg-light">
            <h2 class="card-title font-lg text-primary mb-0">Daftar Barang Logistik</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="font-md">Kode Item</th>
                            <th scope="col" class="font-md">Nama Item / Kategori</th>
                            <th scope="col" class="font-md">Lokasi Gudang</th>
                            <th scope="col" class="font-md text-center">Stok Minimal</th>
                            <th scope="col" class="font-md text-center">Stok Saat Ini</th>
                            <th scope="col" class="font-md text-right">Harga Beli</th>
                            <th scope="col" class="font-md text-right">Harga Jual</th>
                            <th scope="col" class="font-md text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ditemukan data barang inventaris.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php 
                                    $isLow = $item['current_stock'] <= $item['minimum_stock'];
                                    $statusClass = $isLow ? 'bg-soft-danger text-danger' : 'bg-soft-success text-success';
                                    $statusText = $isLow ? 'Stok Kritis' : 'Normal';
                                ?>
                                <tr>
                                    <td class="font-bold text-primary"><?= e($item['code']) ?></td>
                                    <td>
                                        <div class="font-bold"><?= e($item['name']) ?></div>
                                        <small class="text-muted font-bold"><?= strtoupper(str_replace('_', ' ', $item['category'])) ?></small>
                                    </td>
                                    <td><?= e($item['location'] ?: '-') ?></td>
                                    <td class="text-center font-bold"><?= e($item['minimum_stock']) ?> <?= e($item['unit']) ?></td>
                                    <td class="text-center font-bold <?= $isLow ? 'text-danger' : '' ?>">
                                        <?= e($item['current_stock']) ?> <?= e($item['unit']) ?>
                                    </td>
                                    <td class="text-right"><?= formatRupiah($item['purchase_price']) ?></td>
                                    <td class="text-right font-bold"><?= formatRupiah($item['selling_price']) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $statusClass ?> font-bold"><?= $statusText ?></span>
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
