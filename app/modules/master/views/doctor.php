<?php
/**
 * View Doctor Page
 */
$doctors = $data['doctors'] ?? [];
$availableUsers = $data['availableUsers'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Daftar Dokter Spesialis</h1>
            <p class="page-subtitle text-muted font-md">Kelola profil dokter, nomor SIP, spesialisasi, dan biaya jasa konsultasi.</p>
        </div>
    </div>

    <div class="row mt-4">
        <!-- List Doctors -->
        <div class="col-lg-8 mb-4">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Daftar Dokter Aktif</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--gray-300); text-align: left; background-color: var(--gray-100);">
                                    <th class="p-3 font-bold font-md">NIP</th>
                                    <th class="p-3 font-bold font-md">Nama Dokter</th>
                                    <th class="p-3 font-bold font-md">Spesialisasi</th>
                                    <th class="p-3 font-bold font-md">No. SIP</th>
                                    <th class="p-3 font-bold font-md text-right">Tarif Jasa</th>
                                    <th class="p-3 font-bold font-md">Status</th>
                                    <th class="p-3 font-bold font-md text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($doctors)): ?>
                                    <tr>
                                        <td colspan="7" class="p-4 text-center text-muted font-md">Belum ada data dokter spesialis.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($doctors as $doc): ?>
                                        <tr style="border-bottom: 1px solid var(--gray-200);">
                                            <td class="p-3 font-md font-bold text-dark"><?= e($doc['employee_number']) ?></td>
                                            <td class="p-3 font-md font-bold text-primary"><?= e($doc['full_name']) ?></td>
                                            <td class="p-3 font-md"><?= e($doc['specialization'] ?: '-') ?></td>
                                            <td class="p-3 font-sm text-muted"><?= e($doc['sip_number'] ?: '-') ?></td>
                                            <td class="p-3 font-md text-right font-bold"><?= formatRupiah($doc['consultation_fee'] ?? 0) ?></td>
                                            <td class="p-3 font-md">
                                                <?php if ($doc['is_active']): ?>
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
                                                        onclick="editDoctor(<?= htmlspecialchars(json_encode($doc)) ?>)"
                                                        title="Ubah profil dokter">
                                                        Ubah
                                                    </button>
                                                    <?php if ($doc['is_active']): ?>
                                                        <form action="<?= url('master/doctor/delete/' . $doc['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan profil dokter ini?');">
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
                    <h2 class="card-title font-lg text-primary mb-0" id="form-title">Daftarkan Profil Dokter</h2>
                </div>
                <div class="card-body">
                    <form id="doc-form" action="<?= url('master/doctor/store') ?>" method="POST" class="accessible-form">
                        <?= CSRF::getField() ?>

                        <!-- User ID Dropdown (Only for Create) -->
                        <div class="form-group mb-3" id="user-select-group">
                            <label for="user_id" class="form-label font-md font-bold">Pilih Akun Pengguna (Role Dokter)</label>
                            <?php if (empty($availableUsers)): ?>
                                <div class="alert alert-warning font-sm p-2 mb-2">
                                    Semua akun dengan peran dokter sudah memiliki profil. Silakan buat akun dokter baru di menu Pengaturan User terlebih dahulu.
                                </div>
                                <input type="hidden" name="user_id" value="">
                            <?php else: ?>
                                <select id="user_id" name="user_id" class="form-control form-control-accessible" required>
                                    <option value="">-- Pilih Akun --</option>
                                    <?php foreach ($availableUsers as $userDoc): ?>
                                        <option value="<?= $userDoc['id'] ?>"><?= e($userDoc['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Employee Number / NIP -->
                        <div class="form-group mb-3" id="emp-num-group">
                            <label for="employee_number" class="form-label font-md font-bold">NIP / Nomor Pegawai</label>
                            <input 
                                type="text" 
                                id="employee_number" 
                                name="employee_number" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: DOK-005" 
                                required>
                        </div>

                        <!-- Specialization -->
                        <div class="form-group mb-3">
                            <label for="specialization" class="form-label font-md font-bold">Spesialisasi</label>
                            <input 
                                type="text" 
                                id="specialization" 
                                name="specialization" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: Spesialis Anak, Spesialis Bedah" 
                                required>
                        </div>

                        <!-- License Number / SIP -->
                        <div class="form-group mb-3">
                            <label for="license_number" class="form-label font-md font-bold">No. Izin Kerja Dokter (STR)</label>
                            <input 
                                type="text" 
                                id="license_number" 
                                name="license_number" 
                                class="form-control form-control-accessible" 
                                placeholder="Nomor STR resmi" 
                                required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="sip_number" class="form-label font-md font-bold">Nomor SIP (Surat Izin Praktek)</label>
                            <input 
                                type="text" 
                                id="sip_number" 
                                name="sip_number" 
                                class="form-control form-control-accessible" 
                                placeholder="Nomor SIP resmi">
                        </div>

                        <!-- Consultation Fee -->
                        <div class="form-group mb-3">
                            <label for="consultation_fee" class="form-label font-md font-bold">Tarif Jasa Konsultasi (Rp)</label>
                            <input 
                                type="number" 
                                id="consultation_fee" 
                                name="consultation_fee" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: 150000" 
                                min="0" 
                                required>
                        </div>

                        <!-- Experience Years -->
                        <div class="form-group mb-3">
                            <label for="experience_years" class="form-label font-md font-bold">Lama Pengalaman Kerja (Tahun)</label>
                            <input 
                                type="number" 
                                id="experience_years" 
                                name="experience_years" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: 5" 
                                min="0">
                        </div>

                        <!-- Education -->
                        <div class="form-group mb-3">
                            <label for="education" class="form-label font-md font-bold">Riwayat Pendidikan</label>
                            <textarea 
                                id="education" 
                                name="education" 
                                rows="2" 
                                class="form-control form-control-accessible" 
                                placeholder="Contoh: Lulusan Universitas Indonesia Sp.A (2018)"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="is_active" class="form-label font-md font-bold">Status Keaktifan</label>
                            <select id="is_active" name="is_active" class="form-control form-control-accessible">
                                <option value="1">Aktif</option>
                                <option value="0">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="form-actions d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-accessible-lg" id="submit-btn" style="flex: 1;">
                                Daftarkan Dokter
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
function editDoctor(doc) {
    document.getElementById('form-title').innerText = 'Ubah Dokter: ' + doc.full_name;
    const form = document.getElementById('doc-form');
    form.action = '<?= url('master/doctor/update/') ?>' + doc.id;
    
    // Hide user select group and employee number group when updating
    document.getElementById('user-select-group').style.display = 'none';
    document.getElementById('emp-num-group').style.display = 'none';
    document.getElementById('user_id').removeAttribute('required');
    document.getElementById('employee_number').removeAttribute('required');
    
    document.getElementById('specialization').value = doc.specialization || '';
    document.getElementById('license_number').value = doc.license_number || '';
    document.getElementById('sip_number').value = doc.sip_number || '';
    document.getElementById('consultation_fee').value = Math.round(doc.consultation_fee || 0);
    document.getElementById('experience_years').value = doc.experience_years || 0;
    document.getElementById('education').value = doc.education || '';
    document.getElementById('is_active').value = doc.is_active;
    
    document.getElementById('submit-btn').innerText = 'Simpan Perubahan';
    document.getElementById('cancel-btn').style.display = 'inline-block';
    
    // Scroll form into view for mobile
    document.getElementById('form-card').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Daftarkan Profil Dokter';
    const form = document.getElementById('doc-form');
    form.action = '<?= url('master/doctor/store') ?>';
    
    document.getElementById('user-select-group').style.display = 'block';
    document.getElementById('emp-num-group').style.display = 'block';
    document.getElementById('user_id').setAttribute('required', 'required');
    document.getElementById('employee_number').setAttribute('required', 'required');
    
    form.reset();
    
    document.getElementById('submit-btn').innerText = 'Daftarkan Dokter';
    document.getElementById('cancel-btn').style.display = 'none';
}
</script>
