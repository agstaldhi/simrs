<?php
/**
 * View Polyclinic Page
 */
$polyclinics = $data['polyclinics'] ?? [];
$rooms = $data['rooms'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Pengaturan Poliklinik</h1>
            <p class="page-subtitle text-muted font-md">Kelola unit pelayanan poli rawat jalan dan penempatan ruang periksa.</p>
        </div>
    </div>

    <div class="row mt-4">
        <!-- List Polyclinics -->
        <div class="col-lg-8 mb-4">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Daftar Poliklinik Aktif</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--gray-300); text-align: left; background-color: var(--gray-100);">
                                    <th class="p-3 font-bold font-md">Kode</th>
                                    <th class="p-3 font-bold font-md">Nama Poliklinik</th>
                                    <th class="p-3 font-bold font-md">Ruangan Pemeriksaan</th>
                                    <th class="p-3 font-bold font-md">Status</th>
                                    <th class="p-3 font-bold font-md text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($polyclinics)): ?>
                                    <tr>
                                        <td colspan="5" class="p-4 text-center text-muted font-md">Belum ada data poliklinik.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($polyclinics as $poly): ?>
                                        <tr style="border-bottom: 1px solid var(--gray-200);">
                                            <td class="p-3 font-md font-bold text-dark"><?= e($poly['code']) ?></td>
                                            <td class="p-3 font-md font-bold text-primary"><?= e($poly['name']) ?></td>
                                            <td class="p-3 font-md"><?= e($poly['room_name'] ?: '-') ?></td>
                                            <td class="p-3 font-md">
                                                <?php if ($poly['is_active']): ?>
                                                    <span class="badge text-success font-sm font-bold" style="background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px;">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge text-danger font-sm font-bold" style="background-color: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px;">Non-Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-sm btn-info btn-accessible-sm" 
                                                        onclick="editPolyclinic(<?= htmlspecialchars(json_encode($poly)) ?>)"
                                                        title="Ubah data poli">
                                                        Ubah
                                                    </button>
                                                    <?php if ($poly['is_active']): ?>
                                                        <form action="<?= url('master/polyclinic/delete/' . $poly['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan poliklinik ini?');">
                                                            <?= CSRF::getField() ?>
                                                            <button type="submit" class="btn btn-sm btn-danger btn-accessible-sm">
                                                                Nonaktifkan
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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

        <!-- Add / Edit Form -->
        <div class="col-lg-4 mb-4">
            <div class="card accessible-card" id="form-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0" id="form-title">Tambah Poliklinik Baru</h2>
                </div>
                <div class="card-body">
                    <form id="poly-form" action="<?= url('master/polyclinic/store') ?>" method="POST" class="accessible-form">
                        <?= CSRF::getField() ?>

                        <div class="form-group mb-3">
                            <label for="code" class="form-label font-md font-bold">Kode Poli</label>
                            <input 
                                type="text" 
                                id="code" 
                                name="code" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: POLI-GIGI, POLI-ANAK" 
                                required>
                            <small class="text-muted" id="code-help">Kode harus unik dan tidak dapat diubah setelah disimpan.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name" class="form-label font-md font-bold">Nama Poliklinik</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: Poliklinik Spesialis Mata" 
                                required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="room_id" class="form-label font-md font-bold">Ruangan Penugasan</label>
                            <select id="room_id" name="room_id" class="form-control form-control-accessible">
                                <option value="">-- Pilih Ruangan Pemeriksaan --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= $room['id'] ?>"><?= e($room['name']) ?> (<?= e($room['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="is_active" class="form-label font-md font-bold">Status Keaktifan</label>
                            <select id="is_active" name="is_active" class="form-control form-control-accessible">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="form-group mb-4">
                            <label for="description" class="form-label font-md font-bold">Deskripsi / Keterangan</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="3" 
                                class="form-control form-control-accessible" 
                                placeholder="Keterangan singkat tentang poliklinik..."></textarea>
                        </div>

                        <div class="form-actions d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-accessible-lg" id="submit-btn" style="flex: 1;">
                                Simpan Poliklinik
                            </button>
                            <button type="button" class="btn btn-secondary btn-accessible-lg" id="cancel-btn" onclick="resetForm()" style="display: none;">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editPolyclinic(poly) {
    document.getElementById('form-title').innerText = 'Ubah Poliklinik: ' + poly.name;
    const form = document.getElementById('poly-form');
    form.action = '<?= url('master/polyclinic/update/') ?>' + poly.id;
    
    document.getElementById('code').value = poly.code;
    document.getElementById('code').readOnly = true;
    document.getElementById('code').style.backgroundColor = 'var(--gray-100)';
    document.getElementById('code-help').innerText = 'Kode poliklinik tidak dapat diubah.';
    
    document.getElementById('name').value = poly.name;
    document.getElementById('room_id').value = poly.room_id || '';
    document.getElementById('is_active').value = poly.is_active;
    document.getElementById('description').value = poly.description || '';
    
    document.getElementById('submit-btn').innerText = 'Simpan Perubahan';
    document.getElementById('cancel-btn').style.display = 'inline-block';
    
    // Scroll form into view for mobile
    document.getElementById('form-card').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Tambah Poliklinik Baru';
    const form = document.getElementById('poly-form');
    form.action = '<?= url('master/polyclinic/store') ?>';
    
    document.getElementById('code').value = '';
    document.getElementById('code').readOnly = false;
    document.getElementById('code').style.backgroundColor = '';
    document.getElementById('code-help').innerText = 'Kode harus unik dan tidak dapat diubah setelah disimpan.';
    
    form.reset();
    
    document.getElementById('submit-btn').innerText = 'Simpan Poliklinik';
    document.getElementById('cancel-btn').style.display = 'none';
}
</script>
