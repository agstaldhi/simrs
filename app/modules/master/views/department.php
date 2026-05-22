<?php
/**
 * View Department Page
 */
$departments = $data['departments'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Daftar Departemen / Unit</h1>
            <p class="page-subtitle text-muted font-md">Kelola unit kerja dan instalasi pelayanan medis maupun non-medis di rumah sakit.</p>
        </div>
    </div>

    <div class="row mt-4">
        <!-- List Departments -->
        <div class="col-lg-8 mb-4">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Daftar Unit Aktif</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--gray-300); text-align: left; background-color: var(--gray-100);">
                                    <th class="p-3 font-bold font-md">Kode</th>
                                    <th class="p-3 font-bold font-md">Nama Departemen</th>
                                    <th class="p-3 font-bold font-md">Kepala Unit</th>
                                    <th class="p-3 font-bold font-md">Telepon</th>
                                    <th class="p-3 font-bold font-md">Status</th>
                                    <th class="p-3 font-bold font-md text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departments)): ?>
                                    <tr>
                                        <td colspan="6" class="p-4 text-center text-muted font-md">Belum ada data departemen.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <tr style="border-bottom: 1px solid var(--gray-200);">
                                            <td class="p-3 font-md font-bold text-dark"><?= e($dept['code']) ?></td>
                                            <td class="p-3 font-md"><?= e($dept['name']) ?></td>
                                            <td class="p-3 font-md"><?= e($dept['head_name'] ?: '-') ?></td>
                                            <td class="p-3 font-md"><?= e($dept['phone'] ?: '-') ?></td>
                                            <td class="p-3 font-md">
                                                <?php if ($dept['is_active']): ?>
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
                                                        onclick="editDept(<?= htmlspecialchars(json_encode($dept)) ?>)"
                                                        title="Ubah data">
                                                        Ubah
                                                    </button>
                                                    <?php if ($dept['is_active']): ?>
                                                        <form action="<?= url('master/department/delete/' . $dept['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan departemen ini?');">
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
                    <h2 class="card-title font-lg text-primary mb-0" id="form-title">Tambah Departemen</h2>
                </div>
                <div class="card-body">
                    <form id="dept-form" action="<?= url('master/department/store') ?>" method="POST" class="accessible-form">
                        <?= CSRF::getField() ?>

                        <div class="form-group mb-3">
                            <label for="code" class="form-label font-md font-bold">Kode Unit</label>
                            <input 
                                type="text" 
                                id="code" 
                                name="code" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: IGD, POLI, LAB" 
                                required>
                            <small class="text-muted" id="code-help">Kode harus unik dan tidak boleh diubah setelah disimpan.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name" class="form-label font-md font-bold">Nama Departemen</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: Instalasi Rawat Jalan" 
                                required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="head_name" class="form-label font-md font-bold">Kepala Unit</label>
                            <input 
                                type="text" 
                                id="head_name" 
                                name="head_name" 
                                class="form-control form-control-accessible" 
                                placeholder="Nama & Gelar Kepala Unit">
                        </div>

                        <div class="form-group mb-3">
                            <label for="phone" class="form-label font-md font-bold">Nomor Kontak / Ext</label>
                            <input 
                                type="text" 
                                id="phone" 
                                name="phone" 
                                class="form-control form-control-accessible" 
                                placeholder="Nomor telepon internal / ekstensi">
                        </div>

                        <div class="form-group mb-3">
                            <label for="is_active" class="form-label font-md font-bold">Status Keaktifan</label>
                            <select id="is_active" name="is_active" class="form-control form-control-accessible">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="form-group mb-4">
                            <label for="description" class="form-label font-md font-bold">Deskripsi Unit</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="3" 
                                class="form-control form-control-accessible" 
                                placeholder="Fungsi dan tanggung jawab unit..."></textarea>
                        </div>

                        <div class="form-actions d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-accessible-lg" id="submit-btn" style="flex: 1;">
                                Simpan Unit
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
function editDept(dept) {
    document.getElementById('form-title').innerText = 'Ubah Departemen: ' + dept.name;
    const form = document.getElementById('dept-form');
    form.action = '<?= url('master/department/update/') ?>' + dept.id;
    
    document.getElementById('code').value = dept.code;
    document.getElementById('code').readOnly = true;
    document.getElementById('code').style.backgroundColor = 'var(--gray-100)';
    document.getElementById('code-help').innerText = 'Kode departemen tidak dapat diubah.';
    
    document.getElementById('name').value = dept.name;
    document.getElementById('head_name').value = dept.head_name || '';
    document.getElementById('phone').value = dept.phone || '';
    document.getElementById('is_active').value = dept.is_active;
    document.getElementById('description').value = dept.description || '';
    
    document.getElementById('submit-btn').innerText = 'Simpan Perubahan';
    document.getElementById('cancel-btn').style.display = 'inline-block';
    
    // Scroll form into view for mobile
    document.getElementById('form-card').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Tambah Departemen';
    const form = document.getElementById('dept-form');
    form.action = '<?= url('master/department/store') ?>';
    
    document.getElementById('code').value = '';
    document.getElementById('code').readOnly = false;
    document.getElementById('code').style.backgroundColor = '';
    document.getElementById('code-help').innerText = 'Kode harus unik dan tidak boleh diubah setelah disimpan.';
    
    form.reset();
    
    document.getElementById('submit-btn').innerText = 'Simpan Unit';
    document.getElementById('cancel-btn').style.display = 'none';
}
</script>
