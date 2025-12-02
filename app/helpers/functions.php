<?php

/**
 * Helper Functions
 * 
 * Global helper functions used throughout the application
 */

/**
 * Escape HTML output
 * 
 * @param string $string String to escape
 * @return string
 */
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate URL
 * 
 * @param string $path URL path
 * @return string
 */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Generate asset URL
 * 
 * @param string $path Asset path
 * @return string
 */
function asset($path)
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url)
{
    header('Location: ' . url($url));
    exit;
}

/**
 * Redirect back
 */
function back()
{
    $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
    header('Location: ' . $referer);
    exit;
}

/**
 * Get old input value (after validation error)
 * 
 * @param string $key Input key
 * @param mixed $default Default value
 * @return mixed
 */
function old($key, $default = '')
{
    return $_SESSION['old_input'][$key] ?? $default;
}

/**
 * Flash old input to session
 * 
 * @param array $data Input data
 */
function flashOld($data)
{
    $_SESSION['old_input'] = $data;
}

/**
 * Clear old input
 */
function clearOld()
{
    unset($_SESSION['old_input']);
}

/**
 * Format currency (Indonesian Rupiah)
 * 
 * @param float $amount Amount
 * @return string
 */
function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format date (Indonesian)
 * 
 * @param string $date Date string
 * @param string $format Format
 * @return string
 */
function formatDate($date, $format = 'd-m-Y')
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime (Indonesian)
 * 
 * @param string $datetime Datetime string
 * @param string $format Format
 * @return string
 */
function formatDatetime($datetime, $format = 'd-m-Y H:i')
{
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Calculate age from birth date
 * 
 * @param string $birthDate Birth date
 * @return int Age in years
 */
function calculateAge($birthDate)
{
    if (empty($birthDate)) {
        return 0;
    }
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $birth->diff($today)->y;
}

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string
 */
function generateRandomString($length = 10)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate medical record number
 * 
 * @return string
 */
function generateMRN()
{
    $prefix = 'RM';
    $year = date('Y');

    // Get last MRN for this year
    $query = "SELECT medical_record_number FROM patients 
              WHERE medical_record_number LIKE ? 
              ORDER BY id DESC LIMIT 1";

    $result = Database::fetchOne($query, ["{$prefix}-{$year}-%"]);

    if ($result) {
        // Extract number and increment
        preg_match('/-(\d+)$/', $result['medical_record_number'], $matches);
        $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
    } else {
        $number = 1;
    }

    return sprintf("%s-%s-%04d", $prefix, $year, $number);
}

/**
 * Generate unique number for various documents
 * 
 * @param string $prefix Prefix (INV, LAB, RX, etc)
 * @param string $table Table name
 * @param string $column Column name
 * @return string
 */
function generateDocumentNumber($prefix, $table, $column)
{
    $year = date('Y');

    $query = "SELECT {$column} FROM {$table} 
              WHERE {$column} LIKE ? 
              ORDER BY id DESC LIMIT 1";

    $result = Database::fetchOne($query, ["{$prefix}-{$year}-%"]);

    if ($result) {
        preg_match('/-(\d+)$/', $result[$column], $matches);
        $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
    } else {
        $number = 1;
    }

    return sprintf("%s-%s-%04d", $prefix, $year, $number);
}

/**
 * Check if current route matches
 * 
 * @param string $route Route to check
 * @return bool
 */
function isActiveRoute($route)
{
    $currentUrl = $_SERVER['REQUEST_URI'];
    return strpos($currentUrl, $route) !== false;
}

/**
 * Get active class for navigation
 * 
 * @param string $route Route to check
 * @param string $class Class to return if active
 * @return string
 */
function activeClass($route, $class = 'active')
{
    return isActiveRoute($route) ? $class : '';
}

/**
 * Dump and die (for debugging)
 * 
 * @param mixed $var Variable to dump
 */
function dd($var)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Dump variable (for debugging)
 * 
 * @param mixed $var Variable to dump
 */
function dump($var)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

/**
 * Get config value
 * 
 * @param string $key Config key (dot notation)
 * @param mixed $default Default value
 * @return mixed
 */
function config($key, $default = null)
{
    static $config = [];

    if (empty($config)) {
        // Load all config files
        $configDir = __DIR__ . '/../../config/';
        foreach (glob($configDir . '*.php') as $file) {
            $name = basename($file, '.php');
            $config[$name] = require $file;
        }
    }

    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }

    return $value;
}

/**
 * Get environment variable
 * 
 * @param string $key Variable key
 * @param mixed $default Default value
 * @return mixed
 */
function env($key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert string booleans
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }

    return $value;
}

/**
 * Sanitize input
 * 
 * @param mixed $data Data to sanitize
 * @return mixed
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjax()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request is POST
 * 
 * @return bool
 */
function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 * 
 * @return bool
 */
function isGet()
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get uploaded file info
 * 
 * @param string $name Input name
 * @return array|null
 */
function uploadedFile($name)
{
    if (!isset($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    return [
        'name' => $_FILES[$name]['name'],
        'type' => $_FILES[$name]['type'],
        'tmp_name' => $_FILES[$name]['tmp_name'],
        'size' => $_FILES[$name]['size'],
        'error' => $_FILES[$name]['error']
    ];
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @return string
 */
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Truncate text
 * 
 * @param string $text Text to truncate
 * @param int $length Max length
 * @param string $suffix Suffix to append
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...')
{
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . $suffix;
}

/**
 * Convert to slug
 * 
 * @param string $text Text to convert
 * @return string
 */
function slug($text)
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Pluralize word
 * 
 * @param int $count Count
 * @param string $singular Singular form
 * @param string|null $plural Plural form
 * @return string
 */
function pluralize($count, $singular, $plural = null)
{
    if ($count == 1) {
        return $singular;
    }

    return $plural ?? $singular . 's';
}

/**
 * Get client IP address
 * 
 * @return string
 */
function getClientIP()
{
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    return $ip;
}

/**
 * Generate breadcrumb
 * 
 * @param array $items Breadcrumb items
 * @return string
 */
function breadcrumb($items)
{
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

    $count = count($items);
    $i = 1;

    foreach ($items as $label => $url) {
        if ($i == $count) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . e($label) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . url($url) . '">' . e($label) . '</a></li>';
        }
        $i++;
    }

    $html .= '</ol></nav>';

    return $html;
}

/**
 * Get user-friendly day name in Indonesian
 * 
 * @param string $dayEn English day name
 * @return string
 */
function dayNameId($dayEn)
{
    $days = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu'
    ];

    return $days[strtolower($dayEn)] ?? $dayEn;
}

/**
 * Get month name in Indonesian
 * 
 * @param int $month Month number (1-12)
 * @return string
 */
function monthNameId($month)
{
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    return $months[$month] ?? '';
}
