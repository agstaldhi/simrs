<?php
/**
 * View Inventory Items
 */
$items = $data['items'] ?? [];
$filters = $data['filters'] ?? [];
$action = $data['action'] ?? 'list';
$editingItem = $data['editingItem'] ?? null;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Inventaris & BMHP Gudang</h1>
            <p class="page-subtitle text-muted font-md">Monitor persediaan Barang Medis Habis Pakai (BMHP), alat kesehatan, elektronik, dan aset logistik non-obat lainnya.</p>
        </div>
        <div class="page-action" style="display: flex; gap: 8px;">
            <?php if ($action === 'list'): ?>
                <a href="<?= url('inventory/items?action=add') ?>" class="btn btn-primary font-bold font-md">
                    ➕ Tambah Item Logistik
                </a>
                <a href="<?= url('inventory/purchase-orders') ?>" class="btn btn-outline-primary font-bold font-md">
                    <i class="fa fa-shopping-cart"></i> Pengadaan Baru (PO)
                </a>
            <?php else: ?>
                <a href="<?= url('inventory/items') ?>" class="btn btn-outline-secondary font-bold font-md">
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

    <!-- Add/Edit Form -->
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="card accessible-card border-primary mt-4 mb-4" style="max-width: 700px;">
            <div class="card-header bg-primary text-white">
                <h2 class="card-title font-lg text-white mb-0">
                    <?= $action === 'add' ? '📦 Tambah Barang Inventaris Baru' : '✏️ Edit Barang Inventaris' ?>
                </h2>
            </div>
            <div class="card-body">
                <form action="<?= $action === 'add' ? url('inventory/items/store') : url('inventory/items/update/' . $editingItem['id']) ?>" method="POST">
                    <?= CSRF::getField() ?>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label font-md font-bold">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" value="<?= e($editingItem['name'] ?? '') ?>" class="form-control form-control-accessible" placeholder="Contoh: Handscone Steril, Jarum Suntik 3cc..." required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="category" class="form-label font-md font-bold">Kategori <span class="text-danger">*</span></label>
                            <select id="category" name="category" class="form-control form-control-accessible" required>
                                <option value="">-- Pilih --</option>
                                <option value="medical_equipment" <?= ($editingItem['category'] ?? '') === 'medical_equipment' ? 'selected' : '' ?>>Peralatan Medis</option>
                                <option value="consumable" <?= ($editingItem['category'] ?? '') === 'consumable' ? 'selected' : '' ?>>Bahan Habis Pakai (Consumable)</option>
                                <option value="office_supplies" <?= ($editingItem['category'] ?? '') === 'office_supplies' ? 'selected' : '' ?>>ATK / Alat Kantor</option>
                                <option value="furniture" <?= ($editingItem['category'] ?? '') === 'furniture' ? 'selected' : '' ?>>Mebel / Furniture</option>
                                <option value="electronics" <?= ($editingItem['category'] ?? '') === 'electronics' ? 'selected' : '' ?>>Elektronik</option>
                                <option value="other" <?= ($editingItem['category'] ?? '') === 'other' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="unit" class="form-label font-md font-bold">Satuan (Unit)</label>
                            <input type="text" id="unit" name="unit" value="<?= e($editingItem['unit'] ?? 'pcs') ?>" class="form-control form-control-accessible" placeholder="Contoh: pcs, box, strip..." required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="purchase_price" class="form-label font-md font-bold">Harga Beli (Rp)</label>
                            <input type="number" step="0.01" id="purchase_price" name="purchase_price" value="<?= e($editingItem['purchase_price'] ?? 0) ?>" class="form-control form-control-accessible" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="selling_price" class="form-label font-md font-bold">Harga Jual (Rp)</label>
                            <input type="number" step="0.01" id="selling_price" name="selling_price" value="<?= e($editingItem['selling_price'] ?? 0) ?>" class="form-control form-control-accessible" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_stock" class="form-label font-md font-bold">Stok Awal / Saat Ini</label>
                            <input type="number" id="current_stock" name="current_stock" value="<?= e($editingItem['current_stock'] ?? 0) ?>" class="form-control form-control-accessible" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="minimum_stock" class="form-label font-md font-bold">Stok Minimal</label>
                            <input type="number" id="minimum_stock" name="minimum_stock" value="<?= e($editingItem['minimum_stock'] ?? 0) ?>" class="form-control form-control-accessible" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="reorder_level" class="form-label font-md font-bold">Reorder Level</label>
                            <input type="number" id="reorder_level" name="reorder_level" value="<?= e($editingItem['reorder_level'] ?? 0) ?>" class="form-control form-control-accessible" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label font-md font-bold">Lokasi Penyimpanan (Rak/Gudang)</label>
                        <input type="text" id="location" name="location" value="<?= e($editingItem['location'] ?? '') ?>" class="form-control form-control-accessible" placeholder="Contoh: Gudang Utama Rak A-3">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label font-md font-bold">Deskripsi Barang</label>
                        <textarea id="description" name="description" rows="3" class="form-control form-control-accessible" placeholder="Penjelasan detail barang..."><?= e($editingItem['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions mt-4 pt-3 border-top" style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary font-bold font-md py-2 px-4">
                            💾 Simpan Barang
                        </button>
                        <a href="<?= url('inventory/items') ?>" class="btn btn-outline-secondary font-bold font-md py-2 px-4" style="text-decoration: none;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters (Only show on list) -->
    <?php if ($action === 'list'): ?>
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
                                <th scope="col" class="font-md text-center" style="width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Tidak ditemukan data barang inventaris.</td>
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
                                        <td class="text-center">
                                            <div style="display: flex; gap: 4px; justify-content: center;">
                                                <a href="<?= url('inventory/items?action=edit&id=' . $item['id']) ?>" class="btn btn-sm btn-outline-primary font-bold" style="padding: 2px 6px; font-size: 12px;">
                                                    ✏️ Edit
                                                </a>
                                                <form action="<?= url('inventory/items/delete/' . $item['id']) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?');">
                                                    <?= CSRF::getField() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger font-bold" style="padding: 2px 6px; font-size: 12px; border: none;">
                                                        🗑️ Hapus
                                                    </button>
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
    <?php endif; ?>
</div>
