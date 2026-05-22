<?php
/**
 * View Suppliers & Vendor Management
 */
$suppliers = $data['suppliers'] ?? [];
$filters = $data['filters'] ?? [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']); // Clean up flash
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Mitra Vendor & Supplier</h1>
            <p class="page-subtitle text-muted font-md">Kelola daftar pihak ketiga penyuplai obat-obatan, BHP medis, dan peralatan logistik rumah sakit.</p>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
            <strong><?= $flash['type'] === 'error' ? 'Perhatian!' : 'Sukses!' ?></strong> <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="row mt-4">
        <!-- Add Supplier Form -->
        <div class="col-lg-4 mb-4">
            <div class="card accessible-card border-primary">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title font-lg mb-0 text-white"><i class="fa fa-plus-circle"></i> Tambah Supplier Baru</h2>
                </div>
                <div class="card-body">
                    <form action="<?= url('inventory/suppliers') ?>" method="POST">
                        <?= CSRF::getField() ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label font-md font-bold">Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control form-control-accessible" required placeholder="PT. Kimia Farma Tbk...">
                        </div>

                        <div class="mb-3">
                            <label for="contact_person" class="form-label font-md font-bold">Kontak Person (CP)</label>
                            <input type="text" id="contact_person" name="contact_person" class="form-control form-control-accessible" placeholder="Budi Santoso...">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label font-md font-bold">No. Telp / Handphone</label>
                            <input type="text" id="phone" name="phone" class="form-control form-control-accessible" placeholder="021-xxxxxxxx / 0812...">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label font-md font-bold">Email</label>
                            <input type="email" id="email" name="email" class="form-control form-control-accessible" placeholder="sales@vendor.com...">
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label font-md font-bold">Kategori Utama</label>
                            <select id="category" name="category" class="form-control form-control-accessible">
                                <option value="medicine">Obat-obatan (Apotek)</option>
                                <option value="medical_equipment">Alat Kesehatan / Medis</option>
                                <option value="consumable">Habis Pakai (Consumable)</option>
                                <option value="office_supplies">ATK / Logistik Umum</option>
                                <option value="general">Umum</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label font-md font-bold">Alamat Kantor</label>
                            <textarea id="address" name="address" rows="3" class="form-control form-control-accessible" placeholder="Jalan Raya No. 123..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block font-bold font-md py-2">
                            <i class="fa fa-save"></i> Simpan Supplier
                        </button>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data supplier terdaftar.</td>
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
