<?php
/**
 * View Hospital Info Page
 */
$hospital = $data['hospital'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Informasi Rumah Sakit</h1>
            <p class="page-subtitle text-muted font-md">Kelola informasi profil, izin operasional, akreditasi, dan kontak resmi rumah sakit.</p>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Ubah Profil Rumah Sakit</h2>
                </div>
                
                <div class="card-body">
                    <form action="<?= url('master/hospital/update') ?>" method="POST" class="accessible-form">
                        <?= CSRF::getField() ?>

                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="name" class="form-label font-md font-bold">Nama Rumah Sakit</label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['name'] ?? '') ?>" 
                                        required>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="slogan" class="form-label font-md font-bold">Slogan / Motto</label>
                                    <input 
                                        type="text" 
                                        id="slogan" 
                                        name="slogan" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['slogan'] ?? '') ?>">
                                </div>

                                <div class="form-group mb-4">
                                    <label for="director_name" class="form-label font-md font-bold">Nama Direktur Utama</label>
                                    <input 
                                        type="text" 
                                        id="director_name" 
                                        name="director_name" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['director_name'] ?? '') ?>" 
                                        required>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="license_number" class="form-label font-md font-bold">Nomor Izin Operasional</label>
                                    <input 
                                        type="text" 
                                        id="license_number" 
                                        name="license_number" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['license_number'] ?? '') ?>">
                                </div>

                                <div class="form-group mb-4">
                                    <label for="accreditation" class="form-label font-md font-bold">Status Akreditasi</label>
                                    <select id="accreditation" name="accreditation" class="form-control form-control-accessible">
                                        <option value="">-- Pilih Akreditasi --</option>
                                        <option value="Paripurna" <?= ($hospital['accreditation'] ?? '') === 'Paripurna' ? 'selected' : '' ?>>Paripurna (Bintang 5)</option>
                                        <option value="Utama" <?= ($hospital['accreditation'] ?? '') === 'Utama' ? 'selected' : '' ?>>Utama (Bintang 4)</option>
                                        <option value="Madya" <?= ($hospital['accreditation'] ?? '') === 'Madya' ? 'selected' : '' ?>>Madya (Bintang 3)</option>
                                        <option value="Pratama" <?= ($hospital['accreditation'] ?? '') === 'Pratama' ? 'selected' : '' ?>>Pratama (Bintang 2)</option>
                                        <option value="Belum Terakreditasi" <?= ($hospital['accreditation'] ?? '') === 'Belum Terakreditasi' ? 'selected' : '' ?>>Belum Terakreditasi</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="phone" class="form-label font-md font-bold">Nomor Telepon Resmi</label>
                                    <input 
                                        type="text" 
                                        id="phone" 
                                        name="phone" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['phone'] ?? '') ?>" 
                                        required>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="email" class="form-label font-md font-bold">Email Resmi</label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['email'] ?? '') ?>" 
                                        required>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="website" class="form-label font-md font-bold">Website Resmi</label>
                                    <input 
                                        type="url" 
                                        id="website" 
                                        name="website" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['website'] ?? '') ?>">
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group mb-4">
                                            <label for="city" class="form-label font-md font-bold">Kota / Kabupaten</label>
                                            <input 
                                                type="text" 
                                                id="city" 
                                                name="city" 
                                                class="form-control form-control-accessible" 
                                                value="<?= e($hospital['city'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mb-4">
                                            <label for="province" class="form-label font-md font-bold">Provinsi</label>
                                            <input 
                                                type="text" 
                                                id="province" 
                                                name="province" 
                                                class="form-control form-control-accessible" 
                                                value="<?= e($hospital['province'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="postal_code" class="form-label font-md font-bold">Kode Pos</label>
                                    <input 
                                        type="text" 
                                        id="postal_code" 
                                        name="postal_code" 
                                        class="form-control form-control-accessible" 
                                        value="<?= e($hospital['postal_code'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Address Full-width -->
                            <div class="col-12">
                                <div class="form-group mb-4">
                                    <label for="address" class="form-label font-md font-bold">Alamat Lengkap</label>
                                    <textarea 
                                        id="address" 
                                        name="address" 
                                        rows="3" 
                                        class="form-control form-control-accessible" 
                                        required><?= e($hospital['address'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="form-actions mt-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-accessible-lg">
                                Simpan Perubahan
                            </button>
                            <a href="<?= url('dashboard') ?>" class="btn btn-secondary btn-accessible-lg">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
