# SIMRS - Sistem Informasi Manajemen Rumah Sakit

Aplikasi web full-stack untuk manajemen rumah sakit yang dibangun dengan PHP Native, PDO, dan arsitektur MVC modular — tanpa framework eksternal. Dilengkapi integrasi BPJS Kesehatan V-Claim, Kemenkes SatuSehat FHIR R4, enkripsi data pasien sesuai UU PDP, dan cetak invoice/kuitansi PDF.

## 📋 Fitur Utama

### ✅ Autentikasi & Otorisasi

- Multi-role: Admin, Dokter, Perawat, Resepsionis, Lab, Apoteker, Kasir, HR
- Role-Based Access Control (RBAC) dengan permission matrix yang bisa diedit dari UI
- Session management dengan timeout otomatis
- Rate limiting login berbasis database (bukan session) — max 5 percobaan, lockout 15 menit
- Rate limiting request per IP (max 100 req/60 detik)
- Audit log seluruh aktivitas pengguna (CRUD, login, backup, perubahan permission)

### 👥 Manajemen Pasien

- Pendaftaran pasien dengan nomor rekam medis auto-generate (thread-safe, sequence table)
- Pencarian pasien (NIK, NoRM, nama, telepon)
- Rekam medis elektronik (EMR) format SOAP dengan verifikasi/kunci dokter (Permenkes 24/2022)
- ICD-10 autocomplete (14.000+ kode) di form diagnosa
- Riwayat kunjungan, data alergi, tanda vital
- NIK dan nomor BPJS dienkripsi AES-256-CBC di database (UU PDP No. 27/2022)

### 🏥 Rawat Inap

- Admisi pasien ke ruangan dengan bed locking (mencegah double-booking concurrent)
- Bed management dengan BOR (Bed Occupancy Rate) real-time
- Nursing notes SOAPIE per shift perawat
- Discharge pasien dengan kalkulasi lama rawat (Length of Stay)
- Status ruangan: Rawat Inap, ICU, IGD

### 📅 Appointment & Antrian

- Jadwal dokter per poli
- Booking kunjungan manual/resepsionis
- Sistem antrian real-time dengan display layar (AJAX polling, auto-refresh)
- Panggil nomor antrian dari UI petugas

### 🔬 Laboratorium

- Order pemeriksaan lab dari dokter
- Input hasil lab oleh petugas dengan template
- Hasil diverifikasi otomatis menambah item ke invoice billing

### 💊 Farmasi

- Manajemen resep dokter
- Dispensasi obat oleh apoteker dengan pengurangan stok transaksional
- Stok obat dan notifikasi stok minimum
- Stock opname

### 💰 Billing & Pembayaran

- Generate invoice otomatis dari kunjungan (konsultasi, lab, obat, tindakan)
- Multiple payment methods (tunai, transfer, kartu, BPJS)
- Tracking piutang & tunggakan pasien
- Cetak invoice A4 PDF (mPDF)
- Cetak kuitansi kasir thermal 80mm PDF (mPDF)

### 📦 Inventory & Pembelian

- Manajemen barang & supplier
- Purchase order
- Penerimaan barang & stock movement tracking
- Stock opname

### 👔 HR & Kepegawaian

- Data pegawai
- Shift scheduling
- Absensi
- Cuti & overtime

### 📊 Dashboard & Reporting

- Dashboard role-specific dengan statistik real-time (Chart.js)
- Laporan harian, bulanan, keuangan, custom
- Export PDF (mPDF) dan Excel (PhpSpreadsheet)

## 🛠️ Teknologi

- **Backend**: PHP 8.0+ (Native, tanpa framework)
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Architecture**: MVC Modular (custom framework)
- **Security**: PDO Prepared Statements, CSRF, XSS Prevention, AES-256-CBC Encryption
- **PDF**: mPDF ^8.3
- **Spreadsheet**: PhpSpreadsheet ^5.7
- **Environment**: vlucas/phpdotenv ^5.6
- **Testing**: PHPUnit ^11.5
- **Frontend**: HTML5, CSS3 (Mobile-first), Vanilla JavaScript ES6+
- **Integrasi**: BPJS V-Claim API, Kemenkes SatuSehat FHIR R4

## 📦 Struktur Proyek

```
simrs/
├── app/
│   ├── core/              # Core classes (Database, Router, Auth, Crypt, BpjsService, SatuSehatService, dll)
│   ├── middleware/        # Middleware (Auth, Role, RateLimit)
│   ├── modules/           # Feature modules
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── patient/
│   │   ├── inpatient/     # Rawat inap, bed management, nursing notes
│   │   ├── appointment/
│   │   ├── medical_record/
│   │   ├── laboratory/
│   │   ├── pharmacy/
│   │   ├── billing/
│   │   ├── inventory/
│   │   ├── hr/
│   │   ├── report/
│   │   ├── master/
│   │   └── settings/
│   ├── templates/         # Layout templates
│   └── helpers/           # Helper functions
├── config/                # Configuration files (app, db, bpjs, satusehat)
├── databases/             # SQL schema + seed data awal (11 file)
├── migrations/            # Migrasi skema tambahan (urut)
├── public/                # Web root — hanya folder ini yang diekspos
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── scripts/               # CLI scripts (backup, import ICD-10, migrate)
├── storage/               # Logs, backups (tidak di-commit ke git)
├── tests/                 # Unit & integration tests (PHPUnit)
└── docs/                  # Dokumentasi teknis
    └── technical_architecture.md
```

## 🚀 Instalasi

### Persyaratan Sistem

- PHP >= 8.0 dengan ekstensi: PDO, pdo_mysql, mbstring, openssl, gd, fileinfo, curl, zip
- MySQL >= 5.7 / MariaDB >= 10.3
- Apache 2.4+ (mod_rewrite) / Nginx 1.18+
- Composer >= 2.0

### Langkah Instalasi

#### 1. Clone/Download Repository

```bash
cd /var/www
git clone https://github.com/agstaldhi/simrs.git
cd simrs
```

#### 2. Install Dependensi

```bash
composer install
```

#### 3. Setup Environment

```bash
cp .env.example .env
nano .env
```

Sesuaikan minimal variabel berikut:

```env
APP_ENV=development
APP_URL=http://localhost/simrs/public
DB_DSN="mysql:host=localhost;dbname=simrs;charset=utf8mb4"
DB_USER=root
DB_PASS=
DB_ENCRYPTION_KEY=isi_string_acak_minimal_32_karakter
DB_BACKUP_KEY=isi_string_acak_lain_minimal_32_karakter
```

> **⚠️ PENTING:** `DB_ENCRYPTION_KEY` wajib diset. Kunci ini digunakan untuk mengenkripsi NIK dan nomor BPJS pasien. Jika hilang, data sensitif tidak bisa dibaca.

#### 4. Setup Database

```bash
# Buat database
mysql -u root -p -e "CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import skema + seed data
for f in databases/*.sql; do mysql -u root -p simrs < "$f"; done

# Jalankan migrasi tambahan
for f in migrations/*.sql; do mysql -u root -p simrs < "$f"; done

# (Opsional) Import ICD-10 — 14.000+ kode diagnosa
php scripts/import_icd10.php
```

#### 5. Set Permission

```bash
mkdir -p storage/logs storage/backups
chmod -R 755 public/
chmod -R 777 storage/ public/uploads/
sudo chown -R www-data:www-data /var/www/simrs
```

#### 6. Setup Backup Otomatis

```bash
chmod +x scripts/cron-backup.sh
crontab -e
# Tambahkan baris berikut (backup setiap hari jam 02:00):
0 2 * * * /var/www/simrs/scripts/cron-backup.sh >> /var/www/simrs/storage/logs/cron.log 2>&1
```

> Untuk panduan deploy production lengkap (Nginx, Apache VirtualHost, SSL Let's Encrypt, konfigurasi PHP-FPM), lihat [`docs/technical_architecture.md`](docs/technical_architecture.md).

## 🔐 Default Login

| Role | Email | Password |
|---|---|---|
| Admin | admin@simrs.local | Admin123! |
| Dokter | dokter@simrs.local | Dokter123! |

**⚠️ PENTING**: Ubah semua password default segera setelah login pertama melalui Pengaturan → Manajemen User.

## 🔒 Keamanan

### Fitur Keamanan Terimplementasi

- ✅ PDO Prepared Statements (SQL Injection Prevention)
- ✅ Password hashing bcrypt
- ✅ CSRF Token Protection (`hash_equals` validation)
- ✅ XSS Prevention (output escaping via `e()`)
- ✅ Session Security (regeneration saat login, HttpOnly, timeout)
- ✅ Rate Limiting login dan request per IP (berbasis database)
- ✅ Enkripsi AES-256-CBC untuk NIK & nomor BPJS pasien (UU PDP)
- ✅ Search hash HMAC-SHA256 untuk pencarian data terenkripsi
- ✅ HTTP Security Headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options)
- ✅ Backup database dienkripsi AES-256, download wajib verifikasi password
- ✅ Role-Based Access Control (RBAC) di setiap method controller
- ✅ Audit Logging seluruh operasi sensitif
- ✅ Directory traversal prevention pada download backup

### Checklist Keamanan Production

- [ ] Set `APP_ENV=production` dan `APP_DEBUG=false` di `.env`
- [ ] Set `DB_ENCRYPTION_KEY` dan `DB_BACKUP_KEY` dengan string acak unik
- [ ] Force HTTPS (sudah otomatis jika `APP_ENV=production`)
- [ ] Konfigurasi firewall (izinkan hanya port 80, 443, 22)
- [ ] Disable directory listing di web server
- [ ] Sembunyikan versi PHP (`expose_php = Off`)
- [ ] Set permission file yang benar (lihat langkah instalasi)
- [ ] Enable PHP OPcache
- [ ] Setup SSL/TLS (Let's Encrypt)
- [ ] Jadwalkan backup otomatis (cron)
- [ ] Pastikan folder `storage/` tidak bisa diakses via browser

## 📱 Responsive Design

- ✅ Mobile-first approach
- ✅ Hamburger menu untuk mobile/tablet
- ✅ Touch-friendly interface
- ✅ Font besar dan kontras tinggi (dirancang untuk staf medis segala usia)
- ✅ Input minimal 48px tinggi untuk kemudahan pengisian di tablet
- ✅ Breakpoints: 768px (tablet), 1024px (desktop)

## 🧪 Testing

### Unit Tests

```bash
# Jalankan semua unit test
./vendor/bin/phpunit

# Satu file test
./vendor/bin/phpunit tests/unit/AuthTest.php

# Output detail
./vendor/bin/phpunit --verbose
```

Test tersedia: `AuthTest`, `ValidatorTest`, `DatabaseTest`, `SIMRSEnhancementsTest`. Semua test menggunakan database transaction yang di-rollback setelah setiap test — tidak meninggalkan data di database.

### Integration Tests

Folder `tests/integration/` tersedia untuk pengembangan test integrasi end-to-end.

### Manual Testing Checklist

- [ ] Login dengan berbagai role
- [ ] CRUD Pasien (termasuk cek enkripsi NIK di database)
- [ ] Pendaftaran rawat inap + discharge
- [ ] Input rekam medis + verifikasi dokter
- [ ] Dispensasi obat di farmasi
- [ ] Generate invoice + cetak PDF
- [ ] Proses pembayaran kasir + cetak kuitansi
- [ ] Panggil antrian + display layar
- [ ] Export laporan PDF/Excel
- [ ] Backup database + download + restore
- [ ] Toggle permission role dari UI
- [ ] CSRF protection (coba kirim form tanpa token)
- [ ] Session timeout
- [ ] Mobile responsive

## 🔧 Maintenance

### Backup Manual

```bash
# Via CLI
php scripts/backup.php

# Via UI: Login Admin → Pengaturan → Backup & Restore → Buat Backup Sekarang
```

### Restore Database

```bash
# File backup sudah terdekripsi otomatis saat diunduh via UI
mysql -u simrs_user -p simrs < backup_simrs_2025-01-15_02-00-00.sql
```

### View Logs

```bash
# Error aplikasi
tail -f storage/logs/app_errors.log

# Error PHP
tail -f storage/logs/php_errors.log

# Log request BPJS (termasuk mock mode)
tail -f storage/logs/bpjs.log

# Log request SatuSehat (termasuk mock mode)
tail -f storage/logs/satusehat.log
```

### Database Maintenance

```sql
-- Optimasi tabel yang sering diakses
OPTIMIZE TABLE patients, medical_records, patient_visits, audit_logs;

-- Cek ukuran database
SELECT table_name, ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'simrs'
ORDER BY size_mb DESC;
```

## 📈 Performance Optimization

### Production Settings

```ini
; PHP Configuration (php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0

; Upload & execution limits
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
```

### MySQL Optimization

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 0      ; Nonaktifkan query cache di MySQL 8+
```

## 🗺️ Roadmap

- [x] Integrasi BPJS V-Claim (kepesertaan, rujukan, SEP)
- [x] Integrasi SatuSehat FHIR R4 (Encounter, Observation, Condition)
- [x] Modul rawat inap (bed management, nursing notes)
- [x] Enkripsi data sensitif pasien (UU PDP)
- [x] ICD-10 database lengkap + autocomplete
- [x] Cetak invoice & kuitansi PDF
- [x] Unit test (PHPUnit)
- [ ] Export laporan ke Excel (PhpSpreadsheet) — dalam pengembangan
- [ ] Notifikasi email (appointment reminder, stok minimum)
- [ ] Modul radiologi (RIS)
- [ ] Mobile app (iOS/Android)
- [ ] Telemedicine module
- [ ] Integrasi perangkat lab otomatis
- [ ] Sertifikasi BPPTIK/Kemenkes
