<?php
/**
 * Create patient view
 * Form untuk pendaftaran pasien baru.
 */
?>

<div class="create-patient-container mt-3">

    <!-- Page Header -->
    <div class="page-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="font-xl font-bold">➕ Daftar Pasien Baru</h1>
            <p class="text-muted font-md">Isi formulir di bawah ini untuk mendaftarkan pasien baru ke dalam sistem SIMRS.</p>
        </div>
        <a href="<?= url('patient') ?>" class="btn btn-light btn-accessible-lg" style="text-decoration: none; padding: 12px 24px; border: 1px solid #ccc; font-weight: bold; display: inline-flex; align-items: center; gap: 8px;">
            ◀ Kembali ke Daftar Pasien
        </a>
    </div>

    <form method="POST" action="<?= url('patient/store') ?>" novalidate>
        <?= CSRF::getField() ?>

        <!-- ============================================================ -->
        <!-- SECTION 1: Data Identitas                                     -->
        <!-- ============================================================ -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background-color: #1c7ed6; color: #fff; padding: 14px 20px; border-radius: 4px 4px 0 0;">
                <h2 class="font-md font-bold mb-0" style="margin: 0;">🪪 Data Identitas Pasien</h2>
            </div>
            <div class="card-body p-4">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">

                    <!-- NIK -->
                    <div class="form-group">
                        <label for="nik" class="font-md font-bold mb-1 d-block">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" id="nik" name="nik"
                               value="<?= e(old('nik')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Masukkan 16 digit NIK"
                               maxlength="16"
                               style="width: 100%;">
                        <small class="text-muted font-sm">Kosongkan jika belum memiliki KTP.</small>
                    </div>

                    <!-- Gelar (Title) -->
                    <div class="form-group">
                        <label for="title" class="font-md font-bold mb-1 d-block">Gelar / Sapaan <span style="color: #e03131;">*</span></label>
                        <select id="title" name="title" class="form-control form-control-accessible" style="width: 100%;" required>
                            <option value="">-- Pilih Gelar --</option>
                            <option value="Tn"  <?= old('title') === 'Tn'  ? 'selected' : '' ?>>Tn. (Tuan)</option>
                            <option value="Ny"  <?= old('title') === 'Ny'  ? 'selected' : '' ?>>Ny. (Nyonya)</option>
                            <option value="Nn"  <?= old('title') === 'Nn'  ? 'selected' : '' ?>>Nn. (Nona)</option>
                            <option value="An"  <?= old('title') === 'An'  ? 'selected' : '' ?>>An. (Anak)</option>
                            <option value="By"  <?= old('title') === 'By'  ? 'selected' : '' ?>>By. (Bayi)</option>
                        </select>
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="full_name" class="font-md font-bold mb-1 d-block">Nama Lengkap <span style="color: #e03131;">*</span></label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?= e(old('full_name')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Masukkan nama lengkap sesuai KTP"
                               style="width: 100%;"
                               required>
                    </div>

                    <!-- Tempat Lahir -->
                    <div class="form-group">
                        <label for="birth_place" class="font-md font-bold mb-1 d-block">Tempat Lahir</label>
                        <input type="text" id="birth_place" name="birth_place"
                               value="<?= e(old('birth_place')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: Jakarta"
                               style="width: 100%;">
                    </div>

                    <!-- Tanggal Lahir -->
                    <div class="form-group">
                        <label for="birth_date" class="font-md font-bold mb-1 d-block">Tanggal Lahir <span style="color: #e03131;">*</span></label>
                        <input type="date" id="birth_date" name="birth_date"
                               value="<?= e(old('birth_date')) ?>"
                               class="form-control form-control-accessible"
                               style="width: 100%;"
                               required>
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="form-group">
                        <label for="gender" class="font-md font-bold mb-1 d-block">Jenis Kelamin <span style="color: #e03131;">*</span></label>
                        <select id="gender" name="gender" class="form-control form-control-accessible" style="width: 100%;" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="male"   <?= old('gender') === 'male'   ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="female" <?= old('gender') === 'female' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>

                    <!-- Golongan Darah -->
                    <div class="form-group">
                        <label for="blood_type" class="font-md font-bold mb-1 d-block">Golongan Darah</label>
                        <select id="blood_type" name="blood_type" class="form-control form-control-accessible" style="width: 100%;">
                            <option value="unknown" <?= (old('blood_type', 'unknown') === 'unknown') ? 'selected' : '' ?>>Tidak Diketahui</option>
                            <option value="A"  <?= old('blood_type') === 'A'  ? 'selected' : '' ?>>A</option>
                            <option value="B"  <?= old('blood_type') === 'B'  ? 'selected' : '' ?>>B</option>
                            <option value="AB" <?= old('blood_type') === 'AB' ? 'selected' : '' ?>>AB</option>
                            <option value="O"  <?= old('blood_type') === 'O'  ? 'selected' : '' ?>>O</option>
                        </select>
                    </div>

                    <!-- Agama -->
                    <div class="form-group">
                        <label for="religion" class="font-md font-bold mb-1 d-block">Agama</label>
                        <select id="religion" name="religion" class="form-control form-control-accessible" style="width: 100%;">
                            <option value="islam"     <?= (old('religion', 'islam') === 'islam')     ? 'selected' : '' ?>>Islam</option>
                            <option value="kristen"   <?= old('religion') === 'kristen'              ? 'selected' : '' ?>>Kristen Protestan</option>
                            <option value="katolik"   <?= old('religion') === 'katolik'              ? 'selected' : '' ?>>Katolik</option>
                            <option value="hindu"     <?= old('religion') === 'hindu'                ? 'selected' : '' ?>>Hindu</option>
                            <option value="buddha"    <?= old('religion') === 'buddha'               ? 'selected' : '' ?>>Buddha</option>
                            <option value="konghucu"  <?= old('religion') === 'konghucu'             ? 'selected' : '' ?>>Konghucu</option>
                            <option value="lainnya"   <?= old('religion') === 'lainnya'              ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>

                    <!-- Pendidikan -->
                    <div class="form-group">
                        <label for="education" class="font-md font-bold mb-1 d-block">Pendidikan Terakhir</label>
                        <select id="education" name="education" class="form-control form-control-accessible" style="width: 100%;">
                            <option value="tidak_sekolah" <?= old('education') === 'tidak_sekolah'   ? 'selected' : '' ?>>Tidak Sekolah</option>
                            <option value="sd"            <?= old('education') === 'sd'              ? 'selected' : '' ?>>SD</option>
                            <option value="smp"           <?= old('education') === 'smp'             ? 'selected' : '' ?>>SMP</option>
                            <option value="sma"           <?= (old('education', 'sma') === 'sma')    ? 'selected' : '' ?>>SMA / SMK</option>
                            <option value="d3"            <?= old('education') === 'd3'              ? 'selected' : '' ?>>Diploma (D3)</option>
                            <option value="s1"            <?= old('education') === 's1'              ? 'selected' : '' ?>>Sarjana (S1)</option>
                            <option value="s2"            <?= old('education') === 's2'              ? 'selected' : '' ?>>Magister (S2)</option>
                            <option value="s3"            <?= old('education') === 's3'              ? 'selected' : '' ?>>Doktor (S3)</option>
                        </select>
                    </div>

                    <!-- Status Pernikahan -->
                    <div class="form-group">
                        <label for="marital_status" class="font-md font-bold mb-1 d-block">Status Pernikahan</label>
                        <select id="marital_status" name="marital_status" class="form-control form-control-accessible" style="width: 100%;">
                            <option value="belum_kawin" <?= (old('marital_status', 'belum_kawin') === 'belum_kawin') ? 'selected' : '' ?>>Belum Kawin</option>
                            <option value="kawin"       <?= old('marital_status') === 'kawin'                        ? 'selected' : '' ?>>Kawin</option>
                            <option value="cerai_hidup" <?= old('marital_status') === 'cerai_hidup'                  ? 'selected' : '' ?>>Cerai Hidup</option>
                            <option value="cerai_mati"  <?= old('marital_status') === 'cerai_mati'                   ? 'selected' : '' ?>>Cerai Mati</option>
                        </select>
                    </div>

                    <!-- Pekerjaan -->
                    <div class="form-group">
                        <label for="occupation" class="font-md font-bold mb-1 d-block">Pekerjaan</label>
                        <input type="text" id="occupation" name="occupation"
                               value="<?= e(old('occupation')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: Wiraswasta, PNS, IRT"
                               style="width: 100%;">
                    </div>

                </div><!-- end grid -->
            </div><!-- end card-body -->
        </div><!-- end card -->


        <!-- ============================================================ -->
        <!-- SECTION 2: Alamat                                             -->
        <!-- ============================================================ -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background-color: #2b8a3e; color: #fff; padding: 14px 20px; border-radius: 4px 4px 0 0;">
                <h2 class="font-md font-bold mb-0" style="margin: 0;">🏠 Alamat Domisili</h2>
            </div>
            <div class="card-body p-4">

                <!-- Alamat lengkap -->
                <div class="form-group mb-3">
                    <label for="address" class="font-md font-bold mb-1 d-block">Alamat Lengkap (Jalan/Gang/Nomor)</label>
                    <textarea id="address" name="address"
                              class="form-control form-control-accessible"
                              placeholder="Contoh: Jl. Merdeka No. 10, RT 002/RW 005"
                              rows="3"
                              style="width: 100%; resize: vertical;"><?= e(old('address')) ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px;">

                    <!-- RT -->
                    <div class="form-group">
                        <label for="rt" class="font-md font-bold mb-1 d-block">RT</label>
                        <input type="text" id="rt" name="rt"
                               value="<?= e(old('rt')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: 002"
                               maxlength="5"
                               style="width: 100%;">
                    </div>

                    <!-- RW -->
                    <div class="form-group">
                        <label for="rw" class="font-md font-bold mb-1 d-block">RW</label>
                        <input type="text" id="rw" name="rw"
                               value="<?= e(old('rw')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: 005"
                               maxlength="5"
                               style="width: 100%;">
                    </div>

                    <!-- Kelurahan / Desa -->
                    <div class="form-group">
                        <label for="kelurahan" class="font-md font-bold mb-1 d-block">Kelurahan / Desa</label>
                        <input type="text" id="kelurahan" name="kelurahan"
                               value="<?= e(old('kelurahan')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Nama kelurahan"
                               style="width: 100%;">
                    </div>

                    <!-- Kecamatan -->
                    <div class="form-group">
                        <label for="kecamatan" class="font-md font-bold mb-1 d-block">Kecamatan</label>
                        <input type="text" id="kecamatan" name="kecamatan"
                               value="<?= e(old('kecamatan')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Nama kecamatan"
                               style="width: 100%;">
                    </div>

                    <!-- Kota / Kabupaten -->
                    <div class="form-group">
                        <label for="city" class="font-md font-bold mb-1 d-block">Kota / Kabupaten</label>
                        <input type="text" id="city" name="city"
                               value="<?= e(old('city')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Nama kota/kabupaten"
                               style="width: 100%;">
                    </div>

                    <!-- Provinsi -->
                    <div class="form-group">
                        <label for="province" class="font-md font-bold mb-1 d-block">Provinsi</label>
                        <input type="text" id="province" name="province"
                               value="<?= e(old('province')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Nama provinsi"
                               style="width: 100%;">
                    </div>

                    <!-- Kode Pos -->
                    <div class="form-group">
                        <label for="postal_code" class="font-md font-bold mb-1 d-block">Kode Pos</label>
                        <input type="text" id="postal_code" name="postal_code"
                               value="<?= e(old('postal_code')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: 12345"
                               maxlength="10"
                               style="width: 100%;">
                    </div>

                </div><!-- end grid -->
            </div><!-- end card-body -->
        </div><!-- end card -->


        <!-- ============================================================ -->
        <!-- SECTION 3: Kontak                                             -->
        <!-- ============================================================ -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background-color: #e67700; color: #fff; padding: 14px 20px; border-radius: 4px 4px 0 0;">
                <h2 class="font-md font-bold mb-0" style="margin: 0;">📞 Informasi Kontak</h2>
            </div>
            <div class="card-body p-4">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">

                    <!-- Nomor HP (Mobile) -->
                    <div class="form-group">
                        <label for="mobile" class="font-md font-bold mb-1 d-block">Nomor HP / WhatsApp <span style="color: #e03131;">*</span></label>
                        <input type="tel" id="mobile" name="mobile"
                               value="<?= e(old('mobile')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: 08123456789"
                               style="width: 100%;"
                               required>
                    </div>

                    <!-- Nomor Telepon Rumah -->
                    <div class="form-group">
                        <label for="phone" class="font-md font-bold mb-1 d-block">Nomor Telepon Rumah</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?= e(old('phone')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: 021-1234567"
                               style="width: 100%;">
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="font-md font-bold mb-1 d-block">Alamat Email</label>
                        <input type="email" id="email" name="email"
                               value="<?= e(old('email')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: pasien@email.com"
                               style="width: 100%;">
                    </div>

                </div><!-- end grid -->
            </div><!-- end card-body -->
        </div><!-- end card -->


        <!-- ============================================================ -->
        <!-- SECTION 4: Data Jaminan                                       -->
        <!-- ============================================================ -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background-color: #5c2d91; color: #fff; padding: 14px 20px; border-radius: 4px 4px 0 0;">
                <h2 class="font-md font-bold mb-0" style="margin: 0;">🛡️ Data Jaminan Kesehatan</h2>
            </div>
            <div class="card-body p-4">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">

                    <!-- Jenis Jaminan -->
                    <div class="form-group">
                        <label for="insurance_type" class="font-md font-bold mb-1 d-block">Jenis Jaminan</label>
                        <select id="insurance_type" name="insurance_type" class="form-control form-control-accessible" style="width: 100%;" onchange="toggleInsuranceNumber(this.value)">
                            <option value="umum"    <?= (old('insurance_type', 'umum') === 'umum')    ? 'selected' : '' ?>>Umum (Mandiri / Bayar Sendiri)</option>
                            <option value="bpjs"    <?= old('insurance_type') === 'bpjs'              ? 'selected' : '' ?>>BPJS Kesehatan</option>
                            <option value="asuransi" <?= old('insurance_type') === 'asuransi'         ? 'selected' : '' ?>>Asuransi Swasta</option>
                        </select>
                    </div>

                    <!-- Nomor Jaminan / Kartu -->
                    <div class="form-group" id="insurance-number-wrapper">
                        <label for="insurance_number" class="font-md font-bold mb-1 d-block">Nomor Kartu / Polis Jaminan</label>
                        <input type="text" id="insurance_number" name="insurance_number"
                               value="<?= e(old('insurance_number')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Nomor kartu BPJS atau polis asuransi"
                               style="width: 100%;">
                        <small class="text-muted font-sm">Kosongkan jika pasien membayar mandiri (Umum).</small>
                    </div>

                </div><!-- end grid -->
            </div><!-- end card-body -->
        </div><!-- end card -->


        <!-- ============================================================ -->
        <!-- SECTION 5: Kontak Darurat                                     -->
        <!-- ============================================================ -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background-color: #c92a2a; color: #fff; padding: 14px 20px; border-radius: 4px 4px 0 0;">
                <h2 class="font-md font-bold mb-0" style="margin: 0;">🚨 Kontak Darurat</h2>
            </div>
            <div class="card-body p-4">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">

                    <!-- Nama Kontak Darurat -->
                    <div class="form-group">
                        <label for="emergency_contact_name" class="font-md font-bold mb-1 d-block">Nama Kontak Darurat</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                               value="<?= e(old('emergency_contact_name')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Nama lengkap kontak darurat"
                               style="width: 100%;">
                    </div>

                    <!-- Hubungan dengan Pasien -->
                    <div class="form-group">
                        <label for="emergency_contact_relation" class="font-md font-bold mb-1 d-block">Hubungan dengan Pasien</label>
                        <input type="text" id="emergency_contact_relation" name="emergency_contact_relation"
                               value="<?= e(old('emergency_contact_relation')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: Suami, Istri, Ayah, Ibu, Anak"
                               style="width: 100%;">
                    </div>

                    <!-- Nomor HP Kontak Darurat -->
                    <div class="form-group">
                        <label for="emergency_contact_phone" class="font-md font-bold mb-1 d-block">Nomor HP Kontak Darurat</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                               value="<?= e(old('emergency_contact_phone')) ?>"
                               class="form-control form-control-accessible"
                               placeholder="Contoh: 08198765432"
                               style="width: 100%;">
                    </div>

                </div><!-- end grid -->
            </div><!-- end card-body -->
        </div><!-- end card -->


        <!-- ============================================================ -->
        <!-- Action Buttons                                                -->
        <!-- ============================================================ -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4" style="display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                <a href="<?= url('patient') ?>"
                   class="btn btn-light btn-accessible-lg"
                   style="text-decoration: none; padding: 12px 28px; border: 1px solid #ccc; font-weight: bold; display: inline-flex; align-items: center; gap: 8px;">
                    ✖ Batal
                </a>
                <button type="submit"
                        class="btn btn-primary btn-accessible-lg"
                        style="padding: 12px 32px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px;">
                    💾 Simpan Data Pasien
                </button>
            </div>
        </div>

    </form>
</div>

<script>
/**
 * Tampilkan / sembunyikan field nomor jaminan berdasarkan jenis jaminan yang dipilih.
 * Jika pasien memilih 'Umum', field nomor kartu tidak perlu diisi.
 */
function toggleInsuranceNumber(insuranceType) {
    var wrapper = document.getElementById('insurance-number-wrapper');
    if (!wrapper) return;

    if (insuranceType === 'umum') {
        wrapper.style.opacity = '0.5';
        wrapper.style.pointerEvents = 'none';
        document.getElementById('insurance_number').value = '';
    } else {
        wrapper.style.opacity = '1';
        wrapper.style.pointerEvents = 'auto';
    }
}

// Jalankan saat halaman pertama kali dimuat untuk mencocokkan kondisi awal select
(function () {
    var insuranceSelect = document.getElementById('insurance_type');
    if (insuranceSelect) {
        toggleInsuranceNumber(insuranceSelect.value);
    }
})();
</script>
