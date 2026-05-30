<?php
/**
 * Patient Create View
 * Form pendaftaran pasien baru
 */
?>

<div class="patients-container mt-3">

    <!-- Page Header -->
    <div class="page-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="font-xl font-bold">➕ Daftar Pasien Baru</h1>
            <p class="text-muted font-md">Isi formulir di bawah ini untuk mendaftarkan pasien baru ke sistem SIMRS.</p>
        </div>
        <a href="<?= url('patient') ?>" class="btn btn-secondary" style="text-decoration: none; padding: 10px 20px; font-weight: bold;">
            ← Kembali ke Daftar Pasien
        </a>
    </div>

    <form action="<?= url('patient/store') ?>" method="POST" id="formDaftarPasien">
        <?= CSRF::getField() ?>

        <!-- ===================== -->
        <!-- SECTION: Data Identitas -->
        <!-- ===================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background: #f8f9fa; padding: 16px 20px; border-bottom: 2px solid #dee2e6;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">👤 Data Identitas Pasien</h3>
            </div>
            <div class="card-body" style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">

                    <div class="form-group">
                        <label for="nik" class="form-label">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" id="nik" name="nik" class="form-control"
                               placeholder="16 digit NIK" maxlength="16"
                               value="<?= e(old('nik')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">Gelar / Sapaan <span style="color: red;">*</span></label>
                        <select id="title" name="title" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach (['Tn', 'Ny', 'Nn', 'By', 'An'] as $t): ?>
                                <option value="<?= $t ?>" <?= old('title') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="full_name" class="form-label">Nama Lengkap <span style="color: red;">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control"
                               placeholder="Nama lengkap sesuai KTP" required
                               value="<?= e(old('full_name')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="birth_place" class="form-label">Tempat Lahir</label>
                        <input type="text" id="birth_place" name="birth_place" class="form-control"
                               placeholder="Kota tempat lahir"
                               value="<?= e(old('birth_place')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="birth_date" class="form-label">Tanggal Lahir <span style="color: red;">*</span></label>
                        <input type="date" id="birth_date" name="birth_date" class="form-control" required
                               value="<?= e(old('birth_date')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="gender" class="form-label">Jenis Kelamin <span style="color: red;">*</span></label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="male"   <?= old('gender') === 'male'   ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="female" <?= old('gender') === 'female' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="blood_type" class="form-label">Golongan Darah</label>
                        <select id="blood_type" name="blood_type" class="form-control">
                            <option value="unknown" <?= old('blood_type', 'unknown') === 'unknown' ? 'selected' : '' ?>>Tidak Diketahui</option>
                            <?php foreach (['A', 'B', 'AB', 'O'] as $bt): ?>
                                <option value="<?= $bt ?>" <?= old('blood_type') === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="religion" class="form-label">Agama</label>
                        <select id="religion" name="religion" class="form-control">
                            <?php
                            $religions = ['islam' => 'Islam', 'kristen' => 'Kristen', 'katolik' => 'Katolik',
                                          'hindu' => 'Hindu', 'buddha' => 'Buddha', 'konghucu' => 'Konghucu', 'lainnya' => 'Lainnya'];
                            foreach ($religions as $val => $label): ?>
                                <option value="<?= $val ?>" <?= old('religion', 'islam') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="education" class="form-label">Pendidikan Terakhir</label>
                        <select id="education" name="education" class="form-control">
                            <?php
                            $educations = ['sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA/SMK',
                                           'd3' => 'D3', 's1' => 'S1', 's2' => 'S2', 's3' => 'S3', 'lainnya' => 'Lainnya'];
                            foreach ($educations as $val => $label): ?>
                                <option value="<?= $val ?>" <?= old('education', 'sma') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marital_status" class="form-label">Status Pernikahan</label>
                        <select id="marital_status" name="marital_status" class="form-control">
                            <?php
                            $statuses = ['belum_kawin' => 'Belum Kawin', 'kawin' => 'Kawin',
                                         'cerai_hidup' => 'Cerai Hidup', 'cerai_mati' => 'Cerai Mati'];
                            foreach ($statuses as $val => $label): ?>
                                <option value="<?= $val ?>" <?= old('marital_status', 'belum_kawin') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="occupation" class="form-label">Pekerjaan</label>
                        <input type="text" id="occupation" name="occupation" class="form-control"
                               placeholder="Pekerjaan pasien"
                               value="<?= e(old('occupation')) ?>">
                    </div>

                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- SECTION: Alamat -->
        <!-- ===================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background: #f8f9fa; padding: 16px 20px; border-bottom: 2px solid #dee2e6;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">🏠 Alamat Tempat Tinggal</h3>
            </div>
            <div class="card-body" style="padding: 24px;">
                <div class="form-group mb-3">
                    <label for="address" class="form-label">Alamat Lengkap</label>
                    <textarea id="address" name="address" class="form-control" rows="3"
                              placeholder="Nama jalan, nomor rumah, dll."><?= e(old('address')) ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="rt" class="form-label">RT</label>
                        <input type="text" id="rt" name="rt" class="form-control"
                               placeholder="000" maxlength="3"
                               value="<?= e(old('rt')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="rw" class="form-label">RW</label>
                        <input type="text" id="rw" name="rw" class="form-control"
                               placeholder="000" maxlength="3"
                               value="<?= e(old('rw')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="kelurahan" class="form-label">Kelurahan / Desa</label>
                        <input type="text" id="kelurahan" name="kelurahan" class="form-control"
                               placeholder="Kelurahan"
                               value="<?= e(old('kelurahan')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="kecamatan" class="form-label">Kecamatan</label>
                        <input type="text" id="kecamatan" name="kecamatan" class="form-control"
                               placeholder="Kecamatan"
                               value="<?= e(old('kecamatan')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="city" class="form-label">Kota / Kabupaten</label>
                        <input type="text" id="city" name="city" class="form-control"
                               placeholder="Kota"
                               value="<?= e(old('city')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="province" class="form-label">Provinsi</label>
                        <input type="text" id="province" name="province" class="form-control"
                               placeholder="Provinsi"
                               value="<?= e(old('province')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="postal_code" class="form-label">Kode Pos</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control"
                               placeholder="00000" maxlength="5"
                               value="<?= e(old('postal_code')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- SECTION: Kontak -->
        <!-- ===================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background: #f8f9fa; padding: 16px 20px; border-bottom: 2px solid #dee2e6;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">📞 Informasi Kontak</h3>
            </div>
            <div class="card-body" style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor Telepon Rumah</label>
                        <input type="text" id="phone" name="phone" class="form-control"
                               placeholder="021xxxxxxxx"
                               value="<?= e(old('phone')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="mobile" class="form-label">Nomor HP / WhatsApp <span style="color: red;">*</span></label>
                        <input type="text" id="mobile" name="mobile" class="form-control"
                               placeholder="08xxxxxxxxxx" required
                               value="<?= e(old('mobile')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="contoh@email.com"
                               value="<?= e(old('email')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- SECTION: Data Jaminan -->
        <!-- ===================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background: #f8f9fa; padding: 16px 20px; border-bottom: 2px solid #dee2e6;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">💳 Data Jaminan Kesehatan</h3>
            </div>
            <div class="card-body" style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="insurance_type" class="form-label">Jenis Jaminan</label>
                        <select id="insurance_type" name="insurance_type" class="form-control">
                            <?php
                            $insurances = ['umum' => 'Umum (Mandiri)', 'bpjs' => 'BPJS Kesehatan', 'asuransi' => 'Asuransi Swasta'];
                            foreach ($insurances as $val => $label): ?>
                                <option value="<?= $val ?>" <?= old('insurance_type', 'umum') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="insurance_number" class="form-label">Nomor Jaminan / Polis</label>
                        <input type="text" id="insurance_number" name="insurance_number" class="form-control"
                               placeholder="Nomor BPJS / polis asuransi"
                               value="<?= e(old('insurance_number')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- SECTION: Kontak Darurat -->
        <!-- ===================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background: #f8f9fa; padding: 16px 20px; border-bottom: 2px solid #dee2e6;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">🚨 Kontak Darurat</h3>
            </div>
            <div class="card-body" style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="emergency_contact_name" class="form-label">Nama Kontak Darurat</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control"
                               placeholder="Nama keluarga / wali"
                               value="<?= e(old('emergency_contact_name')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_relation" class="form-label">Hubungan</label>
                        <input type="text" id="emergency_contact_relation" name="emergency_contact_relation" class="form-control"
                               placeholder="Ibu / Ayah / Suami / Istri / dll"
                               value="<?= e(old('emergency_contact_relation')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_phone" class="form-label">Nomor HP Darurat</label>
                        <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control"
                               placeholder="08xxxxxxxxxx"
                               value="<?= e(old('emergency_contact_phone')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- Tombol Aksi -->
        <!-- ===================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-body" style="padding: 20px; display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap;">
                <a href="<?= url('patient') ?>" class="btn btn-secondary"
                   style="text-decoration: none; padding: 12px 28px; font-weight: bold; font-size: 15px;">
                    ✕ Batal
                </a>
                <button type="submit" class="btn btn-primary"
                        style="padding: 12px 32px; font-weight: bold; font-size: 15px;">
                    💾 Simpan Data Pasien
                </button>
            </div>
        </div>

    </form>
</div>
