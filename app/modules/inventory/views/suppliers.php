<?php
/**
 * View Suppliers & Vendor Management
 */
$suppliers = $data['suppliers'] ?? [];
$filters = $data['filters'] ?? [];
$action = $data['action'] ?? 'list';
$editingSupplier = $data['editingSupplier'] ?? null;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']); // Clean up flash
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Mitra Vendor & Supplier</h1>
            <p class="page-subtitle text-muted font-md">Kelola daftar pihak ketiga penyuplai obat-obatan, BHP medis, dan peralatan logistik rumah sakit.</p>
        </div>
        <?php if ($action === 'edit'): ?>
            <div class="page-action">
                <a href="<?= url('inventory/suppliers') ?>" class="btn btn-outline-secondary font-bold font-md">
                    ◀️ Kembali ke Tambah Baru
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
            <strong><?= $flash['type'] === 'error' ? 'Perhatian!' : 'Sukses!' ?></strong> <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="row mt-4">
        <!-- Add / Edit Supplier Form -->
        <div class="col-lg-4 mb-4">
            <div class="card accessible-card border-primary">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title font-lg mb-0 text-white">
                        <i class="fa <?= $action === 'edit' ? 'fa-edit' : 'fa-plus-circle' ?>"></i> 
                        <?= $action === 'edit' ? 'Edit Supplier' : 'Tambah Supplier Baru' ?>
                    </h2>
                </div>
                <div class="card-body">
                    <form action="<?= $action === 'edit' ? url('inventory/suppliers/update/' . $editingSupplier['id']) : url('inventory/suppliers') ?>" method="POST">
                        <?= CSRF::getField() ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label font-md font-bold">Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" value="<?= e($editingSupplier['name'] ?? '') ?>" class="form-control form-control-accessible" required placeholder="PT. Kimia Farma Tbk...">
                        </div>

                        <div class="mb-3">
                            <label for="contact_person" class="form-label font-md font-bold">Kontak Person (CP)</label>
                            <input type="text" id="contact_person" name="contact_person" value="<?= e($editingSupplier['contact_person'] ?? '') ?>" class="form-control form-control-accessible" placeholder="Budi Santoso...">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label font-md font-bold">No. Telp / Handphone</label>
                            <input type="text" id="phone" name="phone" value="<?= e($editingSupplier['phone'] ?? '') ?>" class="form-control form-control-accessible" placeholder="021-xxxxxxxx / 0812...">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label font-md font-bold">Email</label>
                            <input type="email" id="email" name="email" value="<?= e($editingSupplier['email'] ?? '') ?>" class="form-control form-control-accessible" placeholder="sales@vendor.com...">
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label font-md font-bold">Kategori Utama</label>
                            <select id="category" name="category" class="form-control form-control-accessible">
                                <option value="medicine" <?= ($editingSupplier['category'] ?? '') === 'medicine' ? 'selected' : '' ?>>Obat-obatan (Apotek)</option>
                                <option value="medical_equipment" <?= ($editingSupplier['category'] ?? '') === 'medical_equipment' ? 'selected' : '' ?>>Alat Kesehatan / Medis</option>
                                <option value="consumable" <?= ($editingSupplier['category'] ?? '') === 'consumable' ? 'selected' : '' ?>>Habis Pakai (Consumable)</option>
                                <option value="office_supplies" <?= ($editingSupplier['category'] ?? '') === 'office_supplies' ? 'selected' : '' ?>>ATK / Logistik Umum</option>
                                <option value="general" <?= ($editingSupplier['category'] ?? '') === 'general' ? 'selected' : '' ?>>Umum</option>
                                <option value="other" <?= ($editingSupplier['category'] ?? '') === 'other' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label font-md font-bold">Alamat Kantor</label>
                            <textarea id="address" name="address" rows="3" class="form-control form-control-accessible" placeholder="Jalan Raya No. 123..."><?= e($editingSupplier['address'] ?? '') ?></textarea>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                                <i class="fa fa-save"></i> <?= $action === 'edit' ? 'Perbarui Supplier' : 'Simpan Supplier' ?>
                            </button>
                            <?php if ($action === 'edit'): ?>
                                <a href="<?= url('inventory/suppliers') ?>" class="btn btn-outline-secondary font-bold font-md py-2" style="text-decoration: none; text-align: center; width: 80px;">
                                    Batal
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Supplier List -->
        <div class="col-lg-8">
            <div class="card accessible-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="card-title font-lg text-primary mb-0">Daftar Mitra Vendor</h2>
                    <form action="<?= url('inventory/suppliers') ?>" method="GET" class="d-flex">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= e($filters['search'] ?? '') ?>" 
                            class="form-control form-control-accessible form-control-sm mr-2" 
                            placeholder="Cari nama/kontak...">
                        <button type="submit" class="btn btn-primary btn-sm font-bold font-md"><i class="fa fa-search"></i></button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="font-md">Kode</th>
                                    <th scope="col" class="font-md">Nama Vendor</th>
                                    <th scope="col" class="font-md">CP / Kontak</th>
                                    <th scope="col" class="font-md">Kategori</th>
                                    <th scope="col" class="font-md">Alamat</th>
                                    <th scope="col" class="font-md text-center">Status</th>
                                    <th scope="col" class="font-md text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data supplier terdaftar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($suppliers as $sup): ?>
                                        <tr>
                                            <td class="font-bold text-primary"><?= e($sup['code']) ?></td>
                                            <td>
                                                <div class="font-bold"><?= e($sup['name']) ?></div>
                                                <small class="text-muted"><?= e($sup['email'] ?: '-') ?></small>
                                            </td>
                                            <td>
                                                <div class="font-bold"><?= e($sup['contact_person'] ?: '-') ?></div>
                                                <small class="text-muted"><?= e($sup['phone'] ?: ($sup['mobile'] ?: '-')) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark font-bold">
                                                    <?= strtoupper($sup['category']) ?>
                                                </span>
                                            </td>
                                            <td><small class="text-muted"><?= e($sup['address'] ?: '-') ?></small></td>
                                            <td class="text-center">
                                                <?php if ($sup['is_active']): ?>
                                                    <span class="badge bg-soft-success text-success font-bold">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-soft-danger text-danger font-bold">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div style="display: flex; gap: 4px; justify-content: center;">
                                                    <a href="<?= url('inventory/suppliers?action=edit&id=' . $sup['id']) ?>" class="btn btn-sm btn-outline-primary font-bold" style="padding: 2px 6px; font-size: 12px;">
                                                        ✏️ Edit
                                                    </a>
                                                    <form action="<?= url('inventory/suppliers/delete/' . $sup['id']) ?>" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');">
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
        </div>
    </div>
</div>
