<?php

/**
 * Application Configuration
 */

return [
    // Application name
    'app_name' => 'SIMRS - Sistem Informasi Manajemen Rumah Sakit',

    // Application URL
    'app_url' => getenv('APP_URL') ?: 'http://localhost',

    // Environment: development, testing, production
    'environment' => getenv('APP_ENV') ?: 'production',

    // Debug mode
    'debug' => getenv('APP_DEBUG') === 'true' ? true : false,

    // Timezone
    'timezone' => 'Asia/Jakarta',

    // Locale
    'locale' => 'id_ID',

    // Date format
    'date_format' => 'd-m-Y',
    'datetime_format' => 'd-m-Y H:i',
    'time_format' => 'H:i',

    // Currency
    'currency' => 'IDR',
    'currency_symbol' => 'Rp',

    // Session configuration
    'session_lifetime' => 7200, // 2 hours in seconds
    'session_name' => 'SIMRS_SESSION',

    // Security
    'csrf_token_name' => '_token',
    'password_min_length' => 8,
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 15,

    // File upload
    'upload_max_size' => 5242880, // 5MB in bytes
    'upload_allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],

    // Pagination
    'per_page' => 20,
    'pagination_links' => 5,

    // Medical Record Number format
    'mrn_prefix' => 'RM',
    'mrn_format' => 'RM-YYYY-NNNN', // RM-2024-0001

    // Invoice format
    'invoice_prefix' => 'INV',
    'invoice_format' => 'INV-YYYY-NNNN',

    // Cache
    'cache_enabled' => false,
    'cache_lifetime' => 3600, // 1 hour

    // Maintenance mode
    'maintenance_mode' => false,
    'maintenance_ips' => ['127.0.0.1'], // IPs allowed during maintenance
];
