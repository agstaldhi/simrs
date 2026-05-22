# SIMRS - Sistem Informasi Manajemen Rumah Sakit

Aplikasi web full-stack untuk manajemen rumah sakit yang dibangun dengan PHP Native, PDO, dan arsitektur modular.

## 📋 Fitur Utama

### ✅ Autentikasi & Otorisasi

- Multi-role: Admin, Dokter, Perawat, Resepsionis, Lab, Apoteker, Kasir, HR, Pasien
- Role-Based Access Control (RBAC)
- Session management dengan timeout
- Rate limiting untuk login
- Audit log aktivitas user

### 👥 Manajemen Pasien

- Pendaftaran pasien baru
- Pencarian pasien (NIK, NoRM, nama, telepon)
- Rekam medis elektronik (EMR)
- Riwayat kunjungan
- Data alergi pasien
- Upload file lampiran

### 📅 Appointment & Antrian

- Jadwal dokter per poli
- Booking online/manual
- Sistem antrian real-time
- Notifikasi (opsional)

### 🔬 Laboratorium

- Order pemeriksaan lab
- Input hasil lab
- Template hasil pemeriksaan
- Export report PDF

### 💊 Farmasi

- Manajemen resep
- Stok obat
- Purchase order
- Stock opname

### 💰 Billing & Pembayaran

- Generate invoice otomatis
- Multiple payment methods
- Tracking tunggakan
- Laporan keuangan

### 📦 Inventory & Pembelian

- Manajemen barang & supplier
- Purchase order
- Penerimaan barang
- Stock movement tracking

### 👔 HR & Kepegawaian

- Data pegawai
- Shift scheduling
- Absensi
- Cuti & overtime

### 📊 Dashboard & Reporting

- Dashboard role-specific
- Laporan harian/bulanan
- Export CSV/PDF
- Statistik real-time

## 🛠️ Teknologi

- **Backend**: PHP 8.0+ (Native, tanpa framework)
- **Database**: MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 12+
- **Architecture**: MVC Modular
- **Security**: PDO Prepared Statements, CSRF Protection, XSS Prevention
- **Frontend**: HTML5, CSS3 (Responsive), Vanilla JavaScript
- **Design**: Mobile-first responsive design dengan hamburger menu

## 📦 Struktur Proyek

```
simrs/
├── app/
│   ├── core/              # Core classes (Database, Router, Auth, etc.)
│   ├── middleware/        # Middleware (Auth, Role, RateLimit)
│   ├── modules/           # Feature modules
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── patient/
│   │   └── ...
│   ├── templates/         # Layout templates
│   └── helpers/           # Helper functions
├── config/                # Configuration files
├── public/                # Public assets & entry point
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── storage/               # Logs, backups, cache
├── migrations/            # Database migrations
├── scripts/               # Utility scripts
├── tests/                 # Unit & integration tests
└── docs/                  # Documentation
```

## 🚀 Instalasi

### Persyaratan Sistem

- PHP >= 8.0
- MySQL >= 5.7 / MariaDB >= 10.3 / PostgreSQL >= 12
- Apache 2.4+ / Nginx 1.18+
- Ekstensi PHP: PDO, pdo_mysql, mbstring, openssl, gd, fileinfo

### Langkah Instalasi

#### 1. Clone/Download Repository

```bash
cd /var/www
git clone https://github.com/agstaldhi/simrs.git
cd simrs
```

#### 2. Setup Database

```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simrs_user'@'localhost' IDENTIFIED BY 'password_kuat_123';
GRANT ALL PRIVILEGES ON simrs.* TO 'simrs_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u simrs_user -p simrs < migrations/01_drop_tables.sql
mysql -u simrs_user -p simrs < migrations/02_authentication_authorization.sql
mysql -u simrs_user -p simrs < migrations/03_master_data.sql
mysql -u simrs_user -p simrs < migrations/04_patient_management.sql
mysql -u simrs_user -p simrs < migrations/05_scheduling_appointments.sql
mysql -u simrs_user -p simrs < migrations/06_laboratory.sql
mysql -u simrs_user -p simrs < migrations/07_pharmacy.sql
mysql -u simrs_user -p simrs < migrations/08_billing_payment.sql
mysql -u simrs_user -p simrs < migrations/09_inventory_purchasing.sql
mysql -u simrs_user -p simrs < migrations/10_hr_kepegawaian.sql
mysql -u simrs_user -p simrs < migrations/11_audit_logs.sql
```

#### 3. Konfigurasi

```bash
# Edit konfigurasi database
nano config/db.php
# Sesuaikan: dsn, user, pass

# Edit konfigurasi aplikasi
nano config/app.php
# Sesuaikan: app_url, timezone, dll
```

#### 4. Set Permission

```bash
chmod -R 755 public/
chmod -R 777 storage/
chmod -R 777 public/uploads/
sudo chown -R www-data:www-data /var/www/simrs
```

#### 5. Setup Backup Otomatis

```bash
# Make script executable
chmod +x scripts/cron-backup.sh

# Add to crontab
crontab -e

# Add line (backup setiap hari jam 2 pagi):
0 2 * * * /var/www/simrs/scripts/cron-backup.sh
```

## 🔐 Default Login

### Admin

- **Email**: admin@simrs.local
- **Password**: Admin123!

### Dokter

- **Email**: dokter@simrs.local
- **Password**: Dokter123!

**⚠️ PENTING**: Ubah password default setelah login pertama!

## 🔒 Keamanan

### Fitur Keamanan Terimplementasi

- ✅ PDO Prepared Statements (SQL Injection Prevention)
- ✅ Password Hashing dengan bcrypt
- ✅ CSRF Token Protection
- ✅ XSS Prevention (Output Escaping)
- ✅ Session Security (Regeneration, Timeout, HttpOnly)
- ✅ Rate Limiting (Login & API)
- ✅ File Upload Validation
- ✅ HTTP Security Headers (CSP, HSTS, X-Frame-Options)
- ✅ Input Validation & Sanitization
- ✅ Audit Logging
- ✅ Role-Based Access Control (RBAC)

### Checklist Keamanan Production

- [ ] Force HTTPS
- [ ] Configure Firewall
- [ ] Disable directory listing
- [ ] Hide PHP version
- [ ] Set proper file permissions
- [ ] Enable PHP opcache
- [ ] Configure rate limiting
- [ ] Set up SSL/TLS certificates
- [ ] Regular security updates
- [ ] Database backup schedule

## 📱 Responsive Design

- ✅ Mobile-first approach
- ✅ Hamburger menu untuk mobile/tablet
- ✅ Touch-friendly interface
- ✅ Readable fonts (untuk usia lanjut)
- ✅ High contrast colors
- ✅ Large buttons & inputs
- ✅ Breakpoints: 768px (tablet), 1024px (desktop)

## 🧪 Testing

### Unit Tests

```bash
php tests/run_unit_tests.php
```

### Integration Tests

```bash
php tests/run_integration_tests.php
```

### Manual Testing Checklist

- [ ] Login dengan berbagai role
- [ ] CRUD Pasien
- [ ] Buat appointment
- [ ] Input rekam medis
- [ ] Generate invoice
- [ ] Export laporan
- [ ] File upload
- [ ] Mobile responsive
- [ ] CSRF protection
- [ ] Session timeout

## 🔧 Maintenance

### Backup Manual

```bash
php scripts/backup.php
```

### Restore Database

```bash
mysql -u simrs_user -p simrs < storage/backups/backup_simrs_2024-01-01_02-00-00.sql
```

### View Logs

```bash
# Application logs
tail -f storage/logs/app_errors.log

# PHP errors
tail -f storage/logs/php_errors.log

# Backup logs
tail -f storage/logs/backup_success.log
```

### Database Maintenance

```sql
-- Optimize tables
OPTIMIZE TABLE patients, medical_records, appointments;

-- Analyze tables
ANALYZE TABLE patients, medical_records;

-- Check tables
CHECK TABLE patients, medical_records;
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

; Upload limits
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
query_cache_size = 64M
```

## 🗺️ Roadmap

- [ ] Email notifications
- [ ] SMS gateway integration
- [ ] Mobile app (iOS/Android)
- [ ] Telemedicine module
- [ ] Pharmacy POS integration
- [ ] BPJS integration
- [ ] Lab equipment integration
- [ ] Multi-language support
