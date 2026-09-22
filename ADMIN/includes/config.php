<?php
/**
 * St. Monica Junior School CMS
 * Central Configuration File
 */

// Prevent direct execution outside of PHP environment
if (!defined('CMS_ROOT')) {
    if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));
}

// Load .env file if present in project root
$envFile = dirname(CMS_ROOT) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Detect protocol and host for dynamic base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$adminPos = strpos($scriptName, '/ADMIN');
$baseUrl = ($adminPos !== false) ? substr($scriptName, 0, $adminPos) : '';

return [
    // Database Configuration
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT') ?: 3306), // Automatically falls back or can be 3307
        'name'     => getenv('DB_DATABASE') ?: 'st_monica',
        'user'     => getenv('DB_USERNAME') ?: 'root',
        'pass'     => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ],

    // Application Metadata
    'app' => [
        'name'            => 'St. Monica CMS',
        'title'           => 'St. Monica Junior School — Admin Panel',
        'school_name'     => 'St. Monica Junior School Kasanje',
        'motto'           => 'Always Aim Higher',
        'version'         => '1.0.0',
        'session_name'    => 'st_monica_admin_session',
        'session_timeout' => 7200, // 2 hours
        'debug'           => true
    ],

    // Upload Paths
    'uploads' => [
        'base_dir'    => CMS_ROOT . '/uploads',
        'base_url'    => $baseUrl . '/ADMIN/uploads',
        'max_size'    => 8 * 1024 * 1024, // 8MB
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'allowed_mime'=> ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'subdirs'     => [
            'homepage' => CMS_ROOT . '/uploads/homepage',
            'staff'    => CMS_ROOT . '/uploads/staff',
            'news'     => CMS_ROOT . '/uploads/news',
            'gallery'  => CMS_ROOT . '/uploads/gallery'
        ]
    ],

    // Public Assets Base Path
    'paths' => [
        'admin_root'  => CMS_ROOT,
        'public_root' => dirname(CMS_ROOT),
        'base_url'    => $baseUrl,
        'admin_url'   => $baseUrl . '/ADMIN',
        'api_url'     => $baseUrl . '/ADMIN/api'
    ]
];
