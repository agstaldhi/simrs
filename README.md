# SIMRS - Sistem Informasi Manajemen Rumah Sakit

## 📋 Daftar Isi

1. [Pendahuluan](#pendahuluan)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Struktur Direktori](#struktur-direktori)
4. [Database Schema](#database-schema)
5. [Instalasi](#instalasi)
6. [Konfigurasi](#konfigurasi)
7. [Keamanan](#keamanan)
8. [Testing](#testing)
9. [Deployment](#deployment)
10. [Maintenance](#maintenance)

---

## 📖 Pendahuluan

SIMRS adalah aplikasi web full-stack untuk manajemen rumah sakit yang dibangun dengan:

- **Backend**: PHP 8.x Native (tanpa framework)
- **Database**: PDO (support MySQL/MariaDB/PostgreSQL/SQLite/SQL Server)
- **Frontend**: HTML5, CSS3 (Responsive), Vanilla JavaScript
- **Arsitektur**: Modular Programming dengan OOP

### Fitur Utama

- ✅ Multi-role authentication & authorization (9 roles)
- ✅ Manajemen pasien & rekam medis elektronik
- ✅ Penjadwalan dokter & antrian
- ✅ Laboratorium & radiologi
- ✅ Farmasi & inventory
- ✅ Billing & pembayaran
- ✅ HR & kepegawaian
- ✅ Dashboard & reporting
- ✅ Backup & restore otomatis

---

## 🏗️ Arsitektur Sistem

### Pattern: MVC Modular

```
Request → Router → Controller → Model → Database
                      ↓
                    View → Response
```

### Komponen Utama

1. **Core**: Router, Database, Auth, Middleware
2. **Modules**: Fitur-fitur terpisah per domain
3. **Templates**: Layout & partials reusable
4. **Public**: Entry point & assets

---

## 📁 Struktur Direktori

```
simrs/
├── app/
│   ├── core/
│   │   ├── App.php              # Bootstrap aplikasi
│   │   ├── Router.php           # URL routing
│   │   ├── Controller.php       # Base controller
│   │   ├── Model.php            # Base model
│   │   ├── Database.php         # PDO connection
│   │   ├── Auth.php             # Authentication handler
│   │   ├── Session.php          # Session management
│   │   ├── CSRF.php             # CSRF protection
│   │   ├── Validator.php        # Input validation
│   │   └── Middleware.php       # Middleware base
│   │
│   ├── middleware/
│   │   ├── AuthMiddleware.php   # Login check
│   │   ├── RoleMiddleware.php   # RBAC check
│   │   └── RateLimitMiddleware.php
│   │
│   ├── modules/
│   │   ├── auth/
│   │   │   ├── AuthController.php
│   │   │   ├── AuthModel.php
│   │   │   └── views/
│   │   │       ├── login.php
│   │   │       ├── forgot-password.php
│   │   │       └── reset-password.php
│   │   │
│   │   ├── dashboard/
│   │   │   ├── DashboardController.php
│   │   │   ├── DashboardModel.php
│   │   │   └── views/
│   │   │       └── index.php
│   │   │
│   │   ├── patient/
│   │   │   ├── PatientController.php
│   │   │   ├── PatientModel.php
│   │   │   └── views/
│   │   │       ├── index.php
│   │   │       ├── create.php
│   │   │       ├── edit.php
│   │   │       └── detail.php
│   │   │
│   │   ├── medical_record/
│   │   │   ├── MedicalRecordController.php
│   │   │   ├── MedicalRecordModel.php
│   │   │   └── views/
│   │   │
│   │   ├── appointment/
│   │   │   ├── AppointmentController.php
│   │   │   ├── AppointmentModel.php
│   │   │   └── views/
│   │   │
│   │   ├── laboratory/
│   │   │   ├── LaboratoryController.php
│   │   │   ├── LaboratoryModel.php
│   │   │   └── views/
│   │   │
│   │   ├── pharmacy/
│   │   │   ├── PharmacyController.php
│   │   │   ├── PharmacyModel.php
│   │   │   └── views/
│   │   │
│   │   ├── billing/
│   │   │   ├── BillingController.php
│   │   │   ├── BillingModel.php
│   │   │   └── views/
│   │   │
│   │   ├── inventory/
│   │   │   ├── InventoryController.php
│   │   │   ├── InventoryModel.php
│   │   │   └── views/
│   │   │
│   │   ├── hr/
│   │   │   ├── HRController.php
│   │   │   ├── HRModel.php
│   │   │   └── views/
│   │   │
│   │   └── report/
│   │       ├── ReportController.php
│   │       ├── ReportModel.php
│   │       └── views/
│   │
│   ├── templates/
│   │   ├── layout.php           # Main layout wrapper
│   │   ├── header.php           # Header dengan navbar
│   │   ├── footer.php           # Footer
│   │   ├── sidebar.php          # Sidebar (desktop)
│   │   └── mobile-menu.php      # Hamburger menu (mobile)
│   │
│   └── helpers/
│       ├── functions.php        # Helper functions
│       ├── upload.php           # File upload handler
│       └── pdf.php              # PDF generation
│
├── config/
│   ├── app.php                  # App configuration
│   ├── db.php                   # Database configuration
│   ├── mail.php                 # Mail configuration
│   └── .env.example             # Environment template
│
├── public/
│   ├── index.php                # Entry point
│   ├── .htaccess                # Apache rewrite rules
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css
│   │   │   └── responsive.css
│   │   ├── js/
│   │   │   ├── app.js
│   │   │   └── mobile-menu.js
│   │   └── images/
│   │       └── logo.png
│   └── uploads/                 # User uploads (dilindungi)
│
├── storage/
│   ├── logs/                    # Application logs
│   ├── backups/                 # Database backups
│   └── cache/                   # Cache files
│
├── migrations/
│   ├── 001_create_tables.sql
│   ├── 002_seed_data.sql
│   └── 003_add_audit_logs.sql
│
├── tests/
│   ├── unit/
│   │   ├── AuthTest.php
│   │   ├── PatientTest.php
│   │   └── DatabaseTest.php
│   └── integration/
│       └── PatientFlowTest.php
│
├── scripts/
│   ├── backup.php               # Backup script
│   ├── restore.php              # Restore script
│   └── cron-backup.sh           # Cron job untuk backup
│
├── docs/
│   ├── ERD.png                  # Entity Relationship Diagram
│   ├── API.md                   # API Documentation
│   └── USER_GUIDE.md            # User manual
│
├── .gitignore
├── composer.json                # (opsional) untuk autoload
├── README.md
└── LICENSE
```

---

## 🗄️ Database Schema

### ERD High-Level

**Core Tables:**

- users
- roles
- permissions
- role_permissions
- user_roles

**Master Data:**

- hospital_info
- departments
- rooms
- doctors
- polyclinics

**Patient Management:**

- patients
- patient_visits
- medical_records
- diagnoses (ICD-10)
- vital_signs
- allergies
- attachments

**Scheduling:**

- doctor_schedules
- appointments
- queues

**Laboratory:**

- lab_orders
- lab_results
- lab_templates

**Pharmacy:**

- medicines
- prescriptions
- prescription_items
- medicine_stock

**Inventory:**

- inventory_items
- suppliers
- purchase_orders
- stock_movements

**Billing:**

- invoices
- invoice_items
- payments

**HR:**

- employees
- shifts
- attendances

**Audit:**

- audit_logs
- login_attempts

### Relasi Utama

```
users ←→ user_roles ←→ roles ←→ role_permissions ←→ permissions
patients ←→ patient_visits ←→ medical_records ←→ diagnoses
patient_visits ←→ lab_orders ←→ lab_results
patient_visits ←→ prescriptions ←→ prescription_items ←→ medicines
patient_visits ←→ invoices ←→ payments
doctors ←→ doctor_schedules ←→ appointments
```

---

## 🚀 Instalasi

### Persyaratan Sistem

- PHP >= 8.0
- MySQL >= 5.7 / MariaDB >= 10.3 / PostgreSQL >= 12
- Apache 2.4+ atau Nginx 1.18+
- Ekstensi PHP: PDO, pdo_mysql, mbstring, openssl, gd, fileinfo

### Langkah Instalasi

#### 1. Clone/Download Repository

```bash
git clone https://github.com/yourorg/simrs.git
cd simrs
```

#### 2. Setup Database

```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Buat user
CREATE USER 'simrs_user'@'localhost' IDENTIFIED BY 'password_kuat_123';
GRANT ALL PRIVILEGES ON simrs.* TO 'simrs_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u simrs_user -p simrs < migrations/001_create_tables.sql
mysql -u simrs_user -p simrs < migrations/002_seed_data.sql
```

#### 3. Konfigurasi File

```bash
# Copy environment template
cp config/.env.example config/.env

# Edit konfigurasi database
nano config/db.php
```

#### 4. Set Permission

```bash
# Linux/Mac
chmod -R 755 public/
chmod -R 777 storage/
chmod -R 777 public/uploads/

# Set owner ke web server user
sudo chown -R www-data:www-data /var/www/simrs
```

#### 5. Setup Virtual Host

**Apache:**

```apache
<VirtualHost *:80>
    ServerName simrs.local
    DocumentRoot /var/www/simrs/public

    <Directory /var/www/simrs/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/simrs_error.log
    CustomLog ${APACHE_LOG_DIR}/simrs_access.log combined
</VirtualHost>
```

**Nginx:**

```nginx
server {
    listen 80;
    server_name simrs.local;
    root /var/www/simrs/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(jpg|jpeg|png|gif|css|js|ico|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 6. Restart Web Server

```bash
# Apache
sudo systemctl restart apache2

# Nginx + PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

#### 7. Testing

Buka browser: `http://simrs.local`

**Default Login:**

- Admin: `admin@simrs.local` / `Admin123!`
- Dokter: `dokter@simrs.local` / `Dokter123!`

---

## ⚙️ Konfigurasi

### config/db.php

```php
<?php
return [
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=simrs;charset=utf8mb4',
    'user' => getenv('DB_USER') ?: 'simrs_user',
    'pass' => getenv('DB_PASS') ?: 'password_kuat_123',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];
```

### config/app.php

```php
<?php
return [
    'app_name' => 'SIMRS - Sistem Informasi Rumah Sakit',
    'app_url' => getenv('APP_URL') ?: 'http://localhost',
    'environment' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true' ? true : false,
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id_ID',

    // Session
    'session_lifetime' => 7200, // 2 jam
    'session_name' => 'SIMRS_SESSION',

    // Security
    'csrf_token_name' => '_token',
    'password_min_length' => 8,
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 15,

    // Upload
    'upload_max_size' => 5242880, // 5MB
    'upload_allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],

    // Pagination
    'per_page' => 20,
];
```

### config/.env.example

```bash
# Database
DB_DSN="mysql:host=localhost;dbname=simrs;charset=utf8mb4"
DB_USER="simrs_user"
DB_PASS="password_kuat_123"

# Application
APP_ENV="production"
APP_DEBUG="false"
APP_URL="https://simrs.example.com"

# Mail (untuk forgot password)
MAIL_HOST="smtp.gmail.com"
MAIL_PORT="587"
MAIL_USERNAME="noreply@simrs.com"
MAIL_PASSWORD="mail_password"
MAIL_FROM_ADDRESS="noreply@simrs.com"
MAIL_FROM_NAME="SIMRS"

# Backup
BACKUP_PATH="/var/backups/simrs"
BACKUP_RETENTION_DAYS="30"
```

---

## 🔒 Keamanan

### Checklist Keamanan Wajib

#### ✅ Database Security

- [x] Gunakan prepared statements untuk semua query
- [x] Tidak ada string concatenation di SQL
- [x] Connection pooling dengan PDO
- [x] Database user dengan privilege minimal

#### ✅ Authentication & Authorization

- [x] Password hashing dengan `password_hash()`
- [x] Session regeneration setelah login
- [x] Session timeout (2 jam)
- [x] Role-Based Access Control (RBAC)
- [x] Audit log untuk semua aksi kritis

#### ✅ Input Validation

- [x] Server-side validation untuk semua input
- [x] Whitelist validation untuk file upload
- [x] Sanitasi input dengan filter functions
- [x] Type checking ketat

#### ✅ XSS Prevention

- [x] Output escaping dengan `htmlspecialchars()`
- [x] Content Security Policy (CSP) headers
- [x] X-XSS-Protection header

#### ✅ CSRF Protection

- [x] Token unik per form
- [x] Validasi token setiap POST request
- [x] Double-submit cookie pattern

#### ✅ File Upload Security

- [x] Whitelist ekstensi file
- [x] Validasi MIME type
- [x] Rename file dengan hash
- [x] Store di luar webroot atau dengan .htaccess protection
- [x] Limit ukuran file

#### ✅ Session Security

```php
// Konfigurasi session aman
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS only
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
```

#### ✅ HTTP Headers

```php
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

#### ✅ Rate Limiting

- [x] Login attempts: max 5 dalam 15 menit
- [x] API calls: 100 request/menit per IP
- [x] Password reset: 3 request/jam per email

#### ✅ Error Handling

```php
// Production: jangan tampilkan error detail
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Log semua error ke file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');
```

#### ✅ HTTPS Enforcement

```php
// Force HTTPS di production
if (APP_ENV === 'production' && !isset($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

---

## 🧪 Testing

### Unit Tests

```bash
# Jalankan semua unit tests
php tests/run_unit_tests.php

# Test spesifik
php tests/unit/AuthTest.php
```

### Integration Tests

```bash
php tests/run_integration_tests.php
```

### Manual Testing Checklist

- [ ] Login dengan berbagai role
- [ ] CRUD pasien
- [ ] Buat appointment
- [ ] Input rekam medis
- [ ] Generate invoice
- [ ] Export laporan
- [ ] File upload
- [ ] Responsive design (mobile + desktop)
- [ ] CSRF protection
- [ ] Session timeout

---

## 🚢 Deployment

### Production Environment

#### LAMP Stack (Apache)

1. Install LAMP

```bash
sudo apt update
sudo apt install apache2 mysql-server php8.1 php8.1-mysql php8.1-gd php8.1-mbstring php8.1-xml
```

2. Konfigurasi PHP

```bash
sudo nano /etc/php/8.1/apache2/php.ini

# Edit:
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
display_errors = Off
log_errors = On
```

3. Enable Apache modules

```bash
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

4. Setup SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d simrs.example.com
```

#### LEMP Stack (Nginx)

1. Install LEMP

```bash
sudo apt update
sudo apt install nginx mysql-server php8.1-fpm php8.1-mysql php8.1-gd php8.1-mbstring php8.1-xml
```

2. Konfigurasi PHP-FPM

```bash
sudo nano /etc/php/8.1/fpm/php.ini
# Same settings as above

sudo systemctl restart php8.1-fpm
```

3. SSL dengan Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d simrs.example.com
```

### Optimisasi Production

#### PHP OPcache

```ini
; /etc/php/8.1/mods-available/opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

#### Database Optimization

```sql
-- MySQL Configuration
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 200
query_cache_size = 64M
```

### Backup Automation

```bash
# Tambahkan ke crontab
crontab -e

# Backup setiap hari jam 2 pagi
0 2 * * * /usr/bin/php /var/www/simrs/scripts/backup.php

# Cleanup backup lama setiap minggu
0 3 * * 0 find /var/backups/simrs -type f -mtime +30 -delete
```

---

## 🔧 Maintenance

### Backup Manual

```bash
php scripts/backup.php
```

### Restore Database

```bash
mysql -u simrs_user -p simrs < storage/backups/backup_2024-01-01.sql
```

### Log Monitoring

```bash
# Error logs
tail -f storage/logs/app_error.log

# Access logs (Apache)
tail -f /var/log/apache2/simrs_access.log

# PHP errors
tail -f storage/logs/php_errors.log
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

### Troubleshooting Common Issues

#### Database Connection Error

- Cek kredensial di `config/db.php`
- Cek MySQL service: `sudo systemctl status mysql`
- Cek firewall: `sudo ufw status`

#### Session Not Working

- Cek permission folder session
- Cek `session.save_path` di php.ini
- Regenerate session: logout dan login ulang

#### Upload File Error

- Cek permission folder `public/uploads`
- Cek `upload_max_filesize` di php.ini
- Cek disk space: `df -h`

---

## 📞 Support & Contact

- **Email**: support@simrs.com
- **Documentation**: https://docs.simrs.com
- **Bug Report**: https://github.com/yourorg/simrs/issues

---

## 📄 License

MIT License - lihat file LICENSE untuk detail

---

**Versi Dokumentasi**: 1.0.0  
**Terakhir Update**: 2024-01-01
