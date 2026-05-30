# Arsitektur Teknis SIMRS
**Sistem Informasi Manajemen Rumah Sakit**  
Versi Dokumen: 1.0 — Terakhir diperbarui: 2025

---

## Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Technology Stack](#2-technology-stack)
3. [Arsitektur Aplikasi](#3-arsitektur-aplikasi)
4. [Struktur Direktori](#4-struktur-direktori)
5. [Alur Request](#5-alur-request)
6. [Skema Database](#6-skema-database)
7. [Sistem Keamanan](#7-sistem-keamanan)
8. [Integrasi Eksternal](#8-integrasi-eksternal)
9. [Panduan Deploy — Development](#9-panduan-deploy--development)
10. [Panduan Deploy — Production (VPS/Server)](#10-panduan-deploy--production-vpsserver)
11. [Konfigurasi Web Server](#11-konfigurasi-web-server)
12. [Variabel Environment (.env)](#12-variabel-environment-env)
13. [Migrasi Database](#13-migrasi-database)
14. [Backup & Restore](#14-backup--restore)
15. [Monitoring & Logging](#15-monitoring--logging)
16. [Menjalankan Unit Test](#16-menjalankan-unit-test)
17. [Troubleshooting Umum](#17-troubleshooting-umum)
18. [Spesifikasi Server Minimum](#18-spesifikasi-server-minimum)

---

## 1. Gambaran Umum Sistem

SIMRS adalah aplikasi web berbasis PHP Native yang dirancang mengikuti pola arsitektur **MVC (Model-View-Controller)** dengan framework custom tanpa dependensi framework pihak ketiga seperti Laravel atau Symfony. Pendekatan ini dipilih untuk memberikan kontrol penuh atas performa, keamanan, dan portabilitas.

### Cakupan Modul

| Modul | Deskripsi |
|---|---|
| Autentikasi & RBAC | Login, logout, reset password, manajemen peran dan izin |
| Manajemen Pasien | Registrasi, rekam medis, riwayat kunjungan |
| Rawat Jalan | Penjadwalan, antrian real-time, rekam medis SOAP |
| Rawat Inap | Admisi, bed management, nursing notes, discharge |
| Laboratorium | Order pemeriksaan, input hasil, template |
| Farmasi | Resep, dispensasi obat, manajemen stok |
| Billing & Keuangan | Invoice, pembayaran, kuitansi & invoice PDF |
| Inventori | Barang, supplier, purchase order, stock opname |
| HR & Kepegawaian | Data pegawai, shift, absensi, cuti |
| Laporan | Harian, bulanan, keuangan, custom |
| Pengaturan | Konfigurasi sistem, user, role, backup, audit log |
| Integrasi BPJS | V-Claim API: kepesertaan, rujukan, SEP |
| Integrasi SatuSehat | FHIR R4: Encounter, Observation, Condition |

---

## 2. Technology Stack

### Backend

| Komponen | Teknologi | Versi |
|---|---|---|
| Bahasa | PHP | >= 8.0 |
| Database | MySQL / MariaDB | >= 5.7 / >= 10.3 |
| Database Abstraction | PDO (PHP Data Objects) | Built-in |
| Dependency Manager | Composer | >= 2.0 |
| PDF Generator | mPDF | ^8.3 |
| Spreadsheet Export | PhpSpreadsheet | ^5.7 |
| Environment Config | vlucas/phpdotenv | ^5.6 |
| Unit Testing | PHPUnit | ^11.5 |

### Frontend

| Komponen | Teknologi |
|---|---|
| Markup | HTML5 |
| Styling | CSS3 (Custom, Mobile-first) |
| Interaktivitas | Vanilla JavaScript (ES6+) |
| Chart & Visualisasi | Chart.js (CDN) |
| Icon | Font Awesome (CDN) |

### Ekstensi PHP yang Dibutuhkan

```
pdo
pdo_mysql
mbstring
openssl
gd / imagick
fileinfo
curl
json
zip
```

---

## 3. Arsitektur Aplikasi

### Pola MVC Custom

```
Request HTTP
    │
    ▼
public/index.php          ← Entry point tunggal
    │
    ├── Load .env
    ├── Load config/
    ├── Init Session
    ├── Set security headers
    │
    ▼
app/core/Router.php       ← Routing engine
    │
    ├── Cocokkan URL dengan app/routes.php
    ├── Jalankan middleware (Auth, RateLimit, Role)
    │
    ▼
app/modules/[modul]/[Modul]Controller.php   ← Controller
    │
    ├── Validasi input (app/core/Validator.php)
    ├── Cek permission (app/core/Auth.php)
    ├── Panggil Model / Database::
    │
    ▼
app/modules/[modul]/[Modul]Model.php        ← Model (opsional)
    │
    ├── Query via app/core/Database.php (PDO Singleton)
    │
    ▼
app/modules/[modul]/views/[view].php        ← View
    │
    └── Render via app/templates/layout.php
```

### Komponen Core

| File | Tanggung Jawab |
|---|---|
| `App.php` | Bootstrap aplikasi |
| `Router.php` | URL dispatch, middleware runner |
| `Controller.php` | Base controller (view, redirect, flash, validate) |
| `Model.php` | Base model (CRUD generik) |
| `Database.php` | PDO Singleton, query builder sederhana |
| `Auth.php` | Autentikasi, session management, hashing |
| `Session.php` | Session abstraction layer |
| `CSRF.php` | Token generation dan validasi |
| `Validator.php` | Validasi input dengan rules |
| `Middleware.php` | Abstract base class middleware |
| `Crypt.php` | Enkripsi/dekripsi AES-256-CBC (UU PDP) |
| `HttpClient.php` | cURL wrapper untuk API eksternal |
| `BpjsService.php` | BPJS V-Claim & Antrean API client |
| `BpjsCrypt.php` | HMAC-SHA1 signature untuk BPJS |
| `SatuSehatService.php` | Kemenkes SatuSehat FHIR R4 API client |

### Middleware yang Tersedia

| Middleware | Fungsi |
|---|---|
| `AuthMiddleware` | Memastikan user sudah login |
| `RoleMiddleware` | Memastikan user punya permission yang dibutuhkan |
| `RateLimitMiddleware` | Membatasi request per IP (berbasis database) |

---

## 4. Struktur Direktori

```
simrs/
├── app/
│   ├── core/                   # Kelas inti framework
│   │   ├── App.php
│   │   ├── Auth.php
│   │   ├── Autoloader.php
│   │   ├── BpjsCrypt.php
│   │   ├── BpjsService.php
│   │   ├── Controller.php
│   │   ├── Crypt.php           # AES-256 enkripsi data sensitif
│   │   ├── CSRF.php
│   │   ├── Database.php        # PDO singleton
│   │   ├── HttpClient.php
│   │   ├── Middleware.php      # Abstract base
│   │   ├── Model.php
│   │   ├── Router.php
│   │   ├── SatuSehatService.php
│   │   ├── Session.php
│   │   └── Validator.php
│   │
│   ├── helpers/
│   │   └── functions.php       # Fungsi helper global
│   │
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── RoleMiddleware.php
│   │
│   ├── modules/                # Modul fitur
│   │   ├── appointment/
│   │   ├── auth/
│   │   ├── billing/
│   │   ├── dashboard/
│   │   ├── hr/
│   │   ├── inpatient/
│   │   ├── inventory/
│   │   ├── laboratory/
│   │   ├── master/
│   │   ├── medical_record/
│   │   ├── patient/
│   │   ├── pharmacy/
│   │   ├── report/
│   │   └── settings/
│   │
│   ├── templates/              # Layout HTML
│   │   ├── layout.php
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── sidebar.php
│   │   ├── mobile-menu.php
│   │   └── errors/
│   │       ├── 403.php
│   │       ├── 404.php
│   │       └── 500.php
│   │
│   └── routes.php              # Definisi semua URL routes
│
├── config/
│   ├── app.php                 # Konfigurasi umum aplikasi
│   ├── bpjs.php                # Konfigurasi BPJS API
│   ├── db.php                  # Konfigurasi database
│   └── satusehat.php           # Konfigurasi SatuSehat API
│
├── databases/                  # File SQL skema awal (seed + structure)
│   ├── 01_drop_tables.sql
│   ├── 02_authentication_authorization.sql
│   ├── 03_master_data.sql
│   ├── 04_patient_management.sql
│   ├── 05_scheduling_appointments.sql
│   ├── 06_laboratory.sql
│   ├── 07_pharmacy.sql
│   ├── 08_billing_payment.sql
│   ├── 09_inventory_purchasing.sql
│   ├── 10_hr_kepegawaian.sql
│   └── 11_audit_logs.sql
│
├── docs/                       # Dokumentasi teknis (folder ini)
│
├── migrations/                 # Migrasi skema tambahan (urut)
│   ├── 0001_create_sequences_table.sql
│   ├── 0002_create_icds_table.sql
│   ├── 0003_encrypt_patients_fields.sql
│   └── 0004_create_nursing_notes.sql
│
├── public/                     # Web root (hanya folder ini yang diekspos)
│   ├── index.php               # Entry point
│   ├── migrate.php             # Web-based migration runner
│   ├── .htaccess               # Apache URL rewrite rules
│   ├── uploads/                # File upload user (writable)
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
│
├── scripts/                    # Script CLI
│   ├── backup.php              # Manual backup database
│   ├── cron-backup.sh          # Cron job backup otomatis
│   ├── import_icd10.php        # Import data ICD-10 (14.000+ kode)
│   └── migrate.php             # CLI migration runner
│
├── storage/                    # Data runtime (tidak di-commit ke git)
│   ├── logs/
│   │   ├── php_errors.log
│   │   ├── app_errors.log
│   │   ├── bpjs.log
│   │   └── satusehat.log
│   └── backups/                # File backup SQL terenkripsi
│
├── tests/
│   ├── bootstrap.php
│   ├── unit/
│   │   ├── AuthTest.php
│   │   ├── DatabaseTest.php
│   │   ├── ValidatorTest.php
│   │   └── SIMRSEnhancementsTest.php
│   └── integration/            # (dalam pengembangan)
│
├── .env                        # Environment variables (JANGAN di-commit)
├── .env.example                # Template .env
├── .gitignore
├── composer.json
├── composer.lock
└── phpunit.xml
```

---

## 5. Alur Request

### Contoh: Kasir mengakses halaman pembayaran

```
Browser
  │  GET /billing/payments?invoice_id=123
  ▼
public/.htaccess
  │  RewriteRule ^(.*)$ index.php?url=$1
  ▼
public/index.php
  │  1. Load .env → set APP_ENV, DB credentials, BPJS keys
  │  2. Init Session
  │  3. Set security headers (CSP, HSTS, X-Frame-Options)
  │  4. Instantiate Router
  ▼
app/core/Router.php
  │  Cocokkan "GET /billing/payments" dengan routes.php
  │  Temukan: route group ['middleware' => 'auth']
  │            → jalankan AuthMiddleware::handle()
  │               └── Cek $_SESSION['user_id'] → valid, lanjut
  │
  │  Instantiate BillingController
  ▼
app/modules/billing/BillingController.php :: payments()
  │  1. requirePermission('billing.receive_payments')
  │     └── Auth::hasPermission() → cek role_permissions di DB
  │  2. Query invoice dari DB via Database::fetchOne()
  │  3. Query invoice_items, payments history
  │  4. $this->view('billing/views/payments', $data)
  ▼
app/modules/billing/views/payments.php
  │  Render HTML dengan data dari controller
  ▼
app/templates/layout.php
  │  Wrap dengan header, sidebar, footer
  ▼
Browser ← Response HTML
```

### Alur POST (Simpan Pembayaran)

```
Browser
  │  POST /billing/payments/store
  │  Body: invoice_id, amount, payment_method, _token
  ▼
Router → BillingController::storePayment()
  │  1. requirePermission('billing.receive_payments')
  │  2. requireCsrf() → validasi _token
  │  3. Validasi amount > 0 dan <= outstanding
  │  4. Database::beginTransaction()
  │  5. INSERT payments
  │  6. UPDATE invoices (paid_amount, outstanding_amount, status)
  │  7. Database::commit()
  │  8. logAudit('create', 'billing', ...)
  │  9. setFlash('success', '...')
  │  10. redirect('billing/payments?invoice_id=...')
  ▼
Browser ← 302 Redirect
```

---

## 6. Skema Database

### Tabel Utama

| Tabel | Keterangan |
|---|---|
| `users` | Data user sistem (termasuk staf dan dokter) |
| `roles` | Definisi peran (Admin, Dokter, Perawat, dll) |
| `permissions` | Daftar izin akses per modul |
| `role_permissions` | Mapping peran ↔ izin |
| `user_roles` | Mapping user ↔ peran |
| `patients` | Data demografis pasien (NIK dan nomor BPJS dienkripsi) |
| `patient_visits` | Setiap kunjungan pasien (rawat jalan & rawat inap) |
| `medical_records` | Catatan SOAP dokter per kunjungan |
| `vital_signs` | Tanda vital per kunjungan |
| `diagnoses` | Diagnosa ICD-10 per kunjungan |
| `allergies` | Data alergi pasien |
| `polyclinics` | Data poli / unit pelayanan |
| `doctors` | Data dokter (relasi ke users) |
| `rooms` | Data ruangan (rawat inap, ICU, UGD) |
| `appointments` | Jadwal kunjungan |
| `queues` | Antrian pasien per poli per hari |
| `lab_orders` | Order pemeriksaan laboratorium |
| `lab_results` | Hasil pemeriksaan laboratorium |
| `prescriptions` | Resep dokter |
| `medicines` | Master obat |
| `medicine_stocks` | Stok obat per gudang |
| `invoices` | Tagihan pasien per kunjungan |
| `invoice_items` | Rincian item tagihan |
| `payments` | Transaksi pembayaran |
| `inventory_items` | Master barang inventori |
| `purchase_orders` | Purchase order ke supplier |
| `employees` | Data kepegawaian |
| `nursing_notes` | Catatan asuhan keperawatan (SOAPIE) |
| `icds` | Master kode ICD-10 (14.000+ entri) |
| `sequences` | Counter sequence untuk nomor dokumen (thread-safe) |
| `rate_limits` | Counter rate limiting per IP |
| `audit_logs` | Log aktivitas seluruh pengguna |
| `login_attempts` | Riwayat percobaan login (rate limiting auth) |
| `system_settings` | Konfigurasi sistem (key-value) |

### Enkripsi Data Sensitif

Kolom berikut dienkripsi menggunakan AES-256-CBC sebelum disimpan ke database (sesuai UU PDP No. 27/2022):

| Tabel | Kolom | Keterangan |
|---|---|---|
| `patients` | `nik` | Nomor Induk Kependudukan |
| `patients` | `insurance_number` | Nomor kartu BPJS / asuransi |
| `patients` | `nik_hash` | HMAC-SHA256 untuk pencarian |
| `patients` | `insurance_number_hash` | HMAC-SHA256 untuk pencarian |

Kunci enkripsi diambil dari variabel `DB_ENCRYPTION_KEY` di file `.env`. **Kunci ini wajib diset — sistem akan menolak berjalan tanpa kunci ini.**

### Konvensi Sequence Nomor Dokumen

Semua nomor dokumen (nomor rekam medis, nomor kunjungan, nomor invoice, nomor pembayaran) digenerate menggunakan tabel `sequences` dengan mekanisme `SELECT ... FOR UPDATE` untuk mencegah duplikasi dalam kondisi concurrent. Format: `PREFIX-YYYY-NNNN` (contoh: `RM-2025-0001`, `INV-2025-0042`).

---

## 7. Sistem Keamanan

### Lapisan Keamanan

| Lapisan | Mekanisme |
|---|---|
| SQL Injection | PDO Prepared Statements di semua query |
| XSS | `htmlspecialchars()` via fungsi `e()` di semua output |
| CSRF | Token per-session, validasi `hash_equals()` di setiap POST |
| Session Hijacking | Session regeneration saat login, HttpOnly + Secure cookie |
| Brute Force Login | Rate limiting: max 5 percobaan, lockout 15 menit, berbasis DB |
| HTTP Request Flood | RateLimitMiddleware: max 100 req/60 detik per IP, berbasis DB |
| Clickjacking | Header `X-Frame-Options: DENY` |
| MIME Sniffing | Header `X-Content-Type-Options: nosniff` |
| Protocol Downgrade | Header `Strict-Transport-Security` (HTTPS only di production) |
| Content Injection | Header `Content-Security-Policy` (production) |
| Data Sensitif | Enkripsi AES-256-CBC untuk NIK dan nomor BPJS |
| Backup Ekspos | File backup dienkripsi AES-256, download wajib verifikasi password |
| RBAC | Permission-based access control, divalidasi di setiap method controller |

### Hashing Password

Menggunakan `password_hash()` PHP dengan algoritma `PASSWORD_BCRYPT` (cost factor default: 10). Verifikasi menggunakan `password_verify()`.

---

## 8. Integrasi Eksternal

### BPJS Kesehatan — V-Claim API

| Endpoint | Fungsi | Status |
|---|---|---|
| `GET Peserta/nik/{nik}` | Cek kepesertaan by NIK | ✅ |
| `GET Peserta/nokartu/{no}` | Cek kepesertaan by nomor kartu | ✅ |
| `GET Rujukan/{noRujukan}` | Cek data rujukan | ✅ |
| `POST SEP/2.0/insert` | Buat Surat Eligibilitas Peserta | ✅ |
| `POST antrean/update` | Update task ID antrean online | ✅ |

**Autentikasi BPJS:** HMAC-SHA1 signature dari `cons_id + "&" + timestamp`, di-encode Base64, dikirim via header `X-Signature`.

**Mode Simulasi:** Set `BPJS_ENABLED=false` di `.env` untuk mengaktifkan mock mode. Semua request akan di-log ke `storage/logs/bpjs.log` dan mengembalikan data simulasi yang realistis. Berguna untuk development dan demo tanpa koneksi ke server BPJS.

### Kemenkes SatuSehat — FHIR R4

| Resource | Fungsi | Status |
|---|---|---|
| `Patient` | Cari UUID pasien by NIK | ✅ |
| `Practitioner` | Cari UUID dokter by NIK | ✅ |
| `Encounter` | Buat/selesaikan kunjungan | ✅ |
| `Observation` | Kirim tanda vital (LOINC codes) | ✅ |
| `Condition` | Kirim diagnosa ICD-10 | ✅ |

**Autentikasi SatuSehat:** OAuth 2.0 Client Credentials. Token di-cache di session dengan refresh otomatis 5 menit sebelum expired.

**Mode Simulasi:** Set `SATUSEHAT_ENABLED=false` di `.env`. Request di-log ke `storage/logs/satusehat.log`.

---

## 9. Panduan Deploy — Development

### Persyaratan Lokal

- PHP >= 8.0 (dengan ekstensi yang dibutuhkan)
- MySQL >= 5.7 atau MariaDB >= 10.3
- Composer >= 2.0
- Apache atau Nginx (atau PHP built-in server untuk testing cepat)

### Langkah Setup

```bash
# 1. Clone repository
git clone https://github.com/agstaldhi/simrs.git
cd simrs

# 2. Install dependensi PHP
composer install

# 3. Siapkan file environment
cp .env.example .env
```

Edit file `.env` sesuaikan setidaknya:

```env
APP_ENV=development
APP_URL=http://localhost/simrs/public
DB_DSN="mysql:host=localhost;dbname=simrs;charset=utf8mb4"
DB_USER=root
DB_PASS=
DB_ENCRYPTION_KEY=isi_dengan_32_karakter_atau_lebih_acak
DB_BACKUP_KEY=isi_dengan_32_karakter_atau_lebih_acak
```

```bash
# 4. Buat database
mysql -u root -p -e "CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Import skema dan data awal
for f in databases/*.sql; do
  mysql -u root -p simrs < "$f"
done

# 6. Jalankan migrasi tambahan
for f in migrations/*.sql; do
  mysql -u root -p simrs < "$f"
done

# 7. Import ICD-10 (opsional, butuh file icd10.csv)
php scripts/import_icd10.php

# 8. Buat folder storage
mkdir -p storage/logs storage/backups
touch storage/logs/.gitkeep storage/backups/.gitkeep

# 9. Set permission
chmod -R 755 public/
chmod -R 777 storage/ public/uploads/
```

Untuk Apache dengan XAMPP/Laragon, pastikan `AllowOverride All` aktif dan buka `http://localhost/simrs/public`.

---

## 10. Panduan Deploy — Production (VPS/Server)

### Persyaratan Server

| Komponen | Minimum | Rekomendasi |
|---|---|---|
| CPU | 2 vCore | 4 vCore |
| RAM | 2 GB | 4–8 GB |
| Storage | 20 GB SSD | 50 GB SSD |
| OS | Ubuntu 20.04 LTS | Ubuntu 22.04 LTS |
| Web Server | Apache 2.4 / Nginx 1.18 | Nginx 1.24 |
| PHP | 8.0 | 8.2 + PHP-FPM |
| MySQL | 5.7 | 8.0 |

### Langkah Deploy di Ubuntu

```bash
# --- 1. Update sistem ---
sudo apt update && sudo apt upgrade -y

# --- 2. Install PHP 8.2 + ekstensi ---
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-openssl php8.2-gd php8.2-curl php8.2-json php8.2-zip \
    php8.2-xml php8.2-fileinfo php8.2-intl

# --- 3. Install Nginx ---
sudo apt install -y nginx

# --- 4. Install MySQL ---
sudo apt install -y mysql-server
sudo mysql_secure_installation

# --- 5. Install Composer ---
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# --- 6. Deploy kode ---
cd /var/www
sudo git clone https://github.com/agstaldhi/simrs.git
sudo chown -R www-data:www-data simrs/
cd simrs

# --- 7. Install dependensi (tanpa dev) ---
composer install --no-dev --optimize-autoloader

# --- 8. Setup environment ---
cp .env.example .env
sudo nano .env
# Isi semua variabel — lihat Bagian 12

# --- 9. Setup database ---
sudo mysql -u root -p
```

```sql
CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simrs_user'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON simrs.* TO 'simrs_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# --- 10. Import skema ---
for f in /var/www/simrs/databases/*.sql; do
  mysql -u simrs_user -p simrs < "$f"
done

for f in /var/www/simrs/migrations/*.sql; do
  mysql -u simrs_user -p simrs < "$f"
done

# --- 11. Buat folder runtime ---
mkdir -p /var/www/simrs/storage/logs /var/www/simrs/storage/backups
sudo chown -R www-data:www-data /var/www/simrs/storage/
sudo chmod -R 755 /var/www/simrs/public/
sudo chmod -R 777 /var/www/simrs/storage/ /var/www/simrs/public/uploads/

# --- 12. Setup backup otomatis ---
chmod +x /var/www/simrs/scripts/cron-backup.sh
crontab -e
# Tambahkan baris:
# 0 2 * * * /var/www/simrs/scripts/cron-backup.sh >> /var/www/simrs/storage/logs/cron.log 2>&1
```

---

## 11. Konfigurasi Web Server

### Apache — VirtualHost

File: `/etc/apache2/sites-available/simrs.conf`

```apache
<VirtualHost *:80>
    ServerName simrs.rumahsakit.id
    DocumentRoot /var/www/simrs/public

    <Directory /var/www/simrs/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/simrs_error.log
    CustomLog ${APACHE_LOG_DIR}/simrs_access.log combined

    # Redirect HTTP ke HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName simrs.rumahsakit.id
    DocumentRoot /var/www/simrs/public

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/simrs.rumahsakit.id/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/simrs.rumahsakit.id/privkey.pem

    <Directory /var/www/simrs/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/simrs_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/simrs_ssl_access.log combined
</VirtualHost>
```

```bash
sudo a2enmod rewrite ssl
sudo a2ensite simrs.conf
sudo systemctl reload apache2
```

### Nginx — Server Block

File: `/etc/nginx/sites-available/simrs`

```nginx
server {
    listen 80;
    server_name simrs.rumahsakit.id;

    # Redirect semua HTTP ke HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name simrs.rumahsakit.id;

    root /var/www/simrs/public;
    index index.php;

    # SSL Certificate (Let's Encrypt)
    ssl_certificate     /etc/letsencrypt/live/simrs.rumahsakit.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/simrs.rumahsakit.id/privkey.pem;

    # SSL Security
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;

    # Sembunyikan versi Nginx
    server_tokens off;

    # Blokir akses ke file sensitif
    location ~ /\. {
        deny all;
    }

    location ~ ^/(app|config|databases|migrations|scripts|storage|tests|vendor)/ {
        deny all;
        return 403;
    }

    # URL Rewriting (mirip Apache mod_rewrite)
    location / {
        try_files $uri $uri/ /index.php?url=$uri&$args;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Batas upload
        client_max_body_size 10M;
        fastcgi_read_timeout 300;
    }

    # Cache aset statis
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/simrs /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### SSL dengan Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d simrs.rumahsakit.id
# Atau untuk Apache:
# sudo certbot --apache -d simrs.rumahsakit.id
```

---

## 12. Variabel Environment (.env)

Berikut penjelasan lengkap semua variabel environment yang tersedia:

```env
# ============================================================
# APLIKASI
# ============================================================
APP_NAME="SIMRS - Sistem Informasi Manajemen Rumah Sakit"
APP_ENV=production          # development | testing | production
APP_URL=https://simrs.rumahsakit.id
APP_DEBUG=false             # false di production — WAJIB

# ============================================================
# DATABASE
# ============================================================
DB_DSN="mysql:host=localhost;dbname=simrs;charset=utf8mb4"
DB_USER=simrs_user
DB_PASS=password_yang_sangat_kuat_di_sini

# ============================================================
# ENKRIPSI (UU PDP) — WAJIB DISET, TIDAK BOLEH KOSONG
# ============================================================
# Kunci enkripsi NIK dan nomor BPJS pasien (AES-256-CBC)
# Minimal 32 karakter acak. Simpan dengan aman. Jika hilang,
# seluruh data NIK dan BPJS yang tersimpan tidak bisa dibaca.
DB_ENCRYPTION_KEY=buat_string_acak_minimal_32_karakter_di_sini

# Kunci enkripsi file backup database (AES-256-CBC)
# Minimal 32 karakter acak. Berbeda dari DB_ENCRYPTION_KEY.
DB_BACKUP_KEY=buat_string_acak_lain_minimal_32_karakter_di_sini

# ============================================================
# INTEGRASI BPJS KESEHATAN
# ============================================================
BPJS_ENABLED=true           # false = mode simulasi/mock
BPJS_BASE_URL="https://apijkn.bpjs-kesehatan.go.id/vclaim-rest"
# Development/sandbox: https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev
BPJS_CONS_ID=                    # Consumer ID dari BPJS
BPJS_SECRET_KEY=                 # Secret key dari BPJS
BPJS_USER_KEY_VCLAIM=            # User key V-Claim
BPJS_USER_KEY_ANTREAN=           # User key Antrean Online

# ============================================================
# INTEGRASI KEMENKES SATUSEHAT
# ============================================================
SATUSEHAT_ENABLED=true      # false = mode simulasi/mock
SATUSEHAT_BASE_URL="https://api.kemkes.go.id"
# Sandbox: https://api-sandbox.kemkes.go.id
SATUSEHAT_CLIENT_ID=             # Client ID dari platform SatuSehat
SATUSEHAT_CLIENT_SECRET=         # Client secret dari platform SatuSehat
SATUSEHAT_ORG_ID=                # Organization ID RS di SatuSehat
```

> **Catatan Keamanan:** File `.env` tidak boleh pernah di-commit ke repository git. Pastikan `.env` ada di baris `.gitignore`. Untuk deployment, isi file ini langsung di server production.

---

## 13. Migrasi Database

### Urutan Import Pertama Kali

Untuk instalasi baru, jalankan file SQL dalam urutan berikut:

```bash
# Skema utama + seed data
mysql -u simrs_user -p simrs < databases/01_drop_tables.sql
mysql -u simrs_user -p simrs < databases/02_authentication_authorization.sql
mysql -u simrs_user -p simrs < databases/03_master_data.sql
mysql -u simrs_user -p simrs < databases/04_patient_management.sql
mysql -u simrs_user -p simrs < databases/05_scheduling_appointments.sql
mysql -u simrs_user -p simrs < databases/06_laboratory.sql
mysql -u simrs_user -p simrs < databases/07_pharmacy.sql
mysql -u simrs_user -p simrs < databases/08_billing_payment.sql
mysql -u simrs_user -p simrs < databases/09_inventory_purchasing.sql
mysql -u simrs_user -p simrs < databases/10_hr_kepegawaian.sql
mysql -u simrs_user -p simrs < databases/11_audit_logs.sql

# Migrasi tambahan (jalankan setelah skema utama)
mysql -u simrs_user -p simrs < migrations/0001_create_sequences_table.sql
mysql -u simrs_user -p simrs < migrations/0002_create_icds_table.sql
mysql -u simrs_user -p simrs < migrations/0003_encrypt_patients_fields.sql
mysql -u simrs_user -p simrs < migrations/0004_create_nursing_notes.sql

# Import ICD-10 (opsional tapi direkomendasikan)
php scripts/import_icd10.php
```

### Update Skema di Production

Untuk menambahkan kolom atau perubahan skema pada instalasi yang sudah berjalan, buat file baru di folder `migrations/` dengan nomor urut berikutnya:

```sql
-- migrations/0005_nama_perubahan.sql
-- Deskripsi: Jelaskan perubahan apa yang dilakukan

ALTER TABLE nama_tabel ADD COLUMN kolom_baru VARCHAR(100) NULL AFTER kolom_sebelumnya;
```

Kemudian jalankan hanya file migrasi baru tersebut:

```bash
mysql -u simrs_user -p simrs < migrations/0005_nama_perubahan.sql
```

> **Penting:** Selalu backup database sebelum menjalankan migrasi di production.

---

## 14. Backup & Restore

### Backup Manual (via UI)

Login sebagai Admin → Pengaturan → Backup & Restore → klik "Buat Backup Sekarang". File backup akan dienkripsi AES-256 dan disimpan di `storage/backups/`. Download memerlukan verifikasi password admin.

### Backup Manual (via CLI)

```bash
php /var/www/simrs/scripts/backup.php
```

### Backup Otomatis (Cron)

```bash
crontab -e
```

Tambahkan:

```cron
# Backup setiap hari pukul 02:00 dini hari
0 2 * * * /var/www/simrs/scripts/cron-backup.sh >> /var/www/simrs/storage/logs/cron.log 2>&1

# Hapus backup lebih dari 30 hari (opsional)
0 3 * * * find /var/www/simrs/storage/backups/ -name "*.sql" -mtime +30 -delete
```

### Restore Database

```bash
# 1. Download file backup dari UI (sudah terdekripsi otomatis saat download)
# 2. Restore:
mysql -u simrs_user -p simrs < backup_simrs_2025-01-15_02-00-00.sql
```

Atau untuk restore langsung dari file terenkripsi di server, gunakan script:

```bash
# Lihat isi file backup terenkripsi
head -c 100 storage/backups/backup_simrs_2025-01-15_02-00-00.sql
# Format: base64(IV)::AES256(sql_content)
```

---

## 15. Monitoring & Logging

### Lokasi File Log

| File | Isi |
|---|---|
| `storage/logs/php_errors.log` | Error PHP runtime |
| `storage/logs/app_errors.log` | Exception aplikasi dengan stack trace |
| `storage/logs/bpjs.log` | Request/response BPJS (termasuk mock mode) |
| `storage/logs/satusehat.log` | Request/response SatuSehat (termasuk mock mode) |
| `storage/logs/cron.log` | Output cron backup otomatis |

### Monitor Log Real-time

```bash
# Error aplikasi
tail -f /var/www/simrs/storage/logs/app_errors.log

# Error PHP
tail -f /var/www/simrs/storage/logs/php_errors.log

# Log BPJS (development/mock mode)
tail -f /var/www/simrs/storage/logs/bpjs.log
```

### Audit Log via UI

Login sebagai Admin → Pengaturan → Audit Log. Mencatat seluruh aktivitas CRUD, login/logout, backup, perubahan permission, dan operasi sensitif lainnya.

### MySQL Query Monitoring

```sql
-- Tampilkan koneksi aktif
SHOW PROCESSLIST;

-- Tampilkan variabel status
SHOW STATUS LIKE 'Threads%';
SHOW STATUS LIKE 'Connections%';

-- Cek ukuran database
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE table_schema = 'simrs'
GROUP BY table_schema;
```

---

## 16. Menjalankan Unit Test

### Konfigurasi

File `phpunit.xml` sudah dikonfigurasi untuk:
- Test suite: `tests/unit/`
- Bootstrap: `tests/bootstrap.php`

### Menjalankan Semua Test

```bash
cd /var/www/simrs
./vendor/bin/phpunit
```

### Menjalankan Test Tertentu

```bash
# Satu file test
./vendor/bin/phpunit tests/unit/AuthTest.php

# Satu test case
./vendor/bin/phpunit --filter testPasswordHashingAndVerification

# Dengan output detail
./vendor/bin/phpunit --verbose
```

### Test yang Tersedia

| File | Test Case | Mencakup |
|---|---|---|
| `AuthTest.php` | 6 test | Login, logout, rate limiting, password reset lifecycle |
| `ValidatorTest.php` | 8+ test | Semua validation rules |
| `DatabaseTest.php` | 4 test | Insert, update, delete, transaction rollback |
| `SIMRSEnhancementsTest.php` | 5 test | User CRUD, role-permission toggle, ICD-10 search, EMR locking, billing query |

> **Catatan:** Semua test menggunakan database transaction yang di-rollback setelah setiap test, sehingga tidak meninggalkan data di database.

---

## 17. Troubleshooting Umum

### Halaman menampilkan 404 untuk semua URL

Penyebab paling umum: `mod_rewrite` Apache belum aktif, atau `AllowOverride` tidak `All`.

```bash
# Apache
sudo a2enmod rewrite
sudo systemctl reload apache2

# Cek .htaccess di public/
cat /var/www/simrs/public/.htaccess
```

### Error "Database connection failed"

1. Cek kredensial di `.env` — `DB_USER`, `DB_PASS`, `DB_DSN` harus benar.
2. Pastikan service MySQL berjalan: `sudo systemctl status mysql`
3. Test koneksi manual: `mysql -u simrs_user -p simrs`

### Error 403 di halaman tertentu setelah login

Permission user di database tidak sesuai dengan yang didefinisikan di controller. Login sebagai Admin → Pengaturan → Peran & Izin, pastikan peran user memiliki izin yang dibutuhkan.

### Upload file gagal

```bash
# Cek permission folder uploads
ls -la /var/www/simrs/public/uploads/
# Harus: drwxrwxrwx atau dimiliki www-data

sudo chown -R www-data:www-data /var/www/simrs/public/uploads/
sudo chmod 777 /var/www/simrs/public/uploads/
```

### PDF tidak bisa digenerate

mPDF membutuhkan GD atau Imagick dan permission write ke folder temp:

```bash
# Cek ekstensi GD
php -m | grep gd

# Install jika belum ada
sudo apt install php8.2-gd
sudo systemctl restart php8.2-fpm
```

### Session langsung expired / logout sendiri

Cek `SESSION_LIFETIME` dan pastikan filesystem temp bisa di-write:

```bash
php -i | grep "session.save_path"
ls -la /var/lib/php/sessions/
sudo chmod 777 /var/lib/php/sessions/
```

### BPJS/SatuSehat tidak connect

1. Pastikan `BPJS_ENABLED=true` dan semua credential di `.env` sudah diisi.
2. Cek server bisa akses URL BPJS: `curl -I https://apijkn.bpjs-kesehatan.go.id`
3. Cek log error: `tail -f storage/logs/bpjs.log`
4. Jika ingin testing tanpa koneksi ke server BPJS, set `BPJS_ENABLED=false` untuk mode simulasi.

---

## 18. Spesifikasi Server Minimum

### Untuk Demo / Development

| Komponen | Spesifikasi |
|---|---|
| CPU | 1 vCore |
| RAM | 1 GB |
| Storage | 10 GB |
| Bandwidth | 100 Mbps |
| Harga estimasi | ~Rp 50.000–80.000/bulan (VPS lokal) |

### Untuk Klinik / RS Kecil (< 50 bed)

| Komponen | Spesifikasi |
|---|---|
| CPU | 2 vCore |
| RAM | 2–4 GB |
| Storage | 40 GB SSD |
| Backup | Offsite (S3/cloud) otomatis |
| Harga estimasi | ~Rp 200.000–400.000/bulan |

### Untuk RS Tipe C/D (50–200 bed)

| Komponen | Spesifikasi |
|---|---|
| CPU | 4 vCore |
| RAM | 8 GB |
| Storage | 100 GB SSD + backup terpisah |
| Database | Dedicated server atau managed DB |
| Harga estimasi | ~Rp 800.000–1.500.000/bulan |

### Rekomendasi Provider VPS Lokal

- **Biznet Gio** — latency rendah, data center Indonesia
- **IDCloudHost** — harga terjangkau, support lokal
- **Niagahoster Cloud** — mudah di-setup untuk pemula
- **AWS ap-southeast-1 (Singapore)** — untuk kebutuhan SLA tinggi

---

*Dokumen ini diperbarui seiring perkembangan project. Untuk pertanyaan teknis, buka issue di repository atau hubungi developer.*
