<?php
/**
 * St. Monica Junior School CMS - CLI Smoke Test
 * Lightweight sanity check (not a full test suite): PHP syntax, DB connectivity,
 * required tables, and a handful of critical routes.
 *
 * Usage: php ADMIN/tests/smoke-test.php [base_url]
 *   base_url defaults to http://localhost/St.monica - pass a different one if
 *   your local setup uses a different path. Route checks are skipped gracefully
 *   if the site isn't reachable at that URL.
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

define('CMS_ROOT', dirname(__DIR__));
$failures = 0;
$warnings = 0;

function pass(string $msg): void { echo "  [PASS] {$msg}\n"; }
function fail(string $msg): void { global $failures; $failures++; echo "  [FAIL] {$msg}\n"; }
function warn(string $msg): void { global $warnings; $warnings++; echo "  [WARN] {$msg}\n"; }

echo "=== St. Monica CMS Smoke Test ===\n\n";

// 1. PHP syntax check across all ADMIN PHP files
echo "1. PHP Syntax Check\n";
$phpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CMS_ROOT, RecursiveDirectoryIterator::SKIP_DOTS));
$syntaxErrors = 0;
$checked = 0;
foreach ($phpFiles as $file) {
    if ($file->getExtension() !== 'php') continue;
    if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) continue;
    $checked++;
    $output = [];
    $exitCode = 0;
    exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        $syntaxErrors++;
        fail('Syntax error in ' . $file->getPathname());
    }
}
if ($syntaxErrors === 0) {
    pass("All {$checked} PHP files parsed cleanly.");
}

// 2. Database connectivity
echo "\n2. Database Connectivity\n";
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
try {
    $pdo = Database::getConnection();
    pass('Connected to database (server version: ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ').');
} catch (Exception $e) {
    fail('Could not connect to the database.');
}

// 3. Required tables exist
echo "\n3. Required Tables\n";
$requiredTables = [
    'admins', 'hero_slides', 'homepage_sections', 'why_choose_us_items', 'statistics',
    'staff', 'news_events', 'gallery', 'about_content', 'core_values', 'facilities',
    'contact_information', 'activity_logs', 'admissions', 'admission_info', 'testimonials',
    'media_library', 'seo_settings', 'admission_notes', 'enquiries', 'notifications',
    'announcements', 'site_settings', 'email_templates', 'admin_sessions',
    'password_resets', 'remember_tokens'
];
try {
    $existingTables = Database::fetchColumnAll("SHOW TABLES");
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables, true)) {
            pass("Table `{$table}` exists.");
        } else {
            fail("Table `{$table}` is missing. Run: php ADMIN/database/setup.php");
        }
    }
} catch (Exception $e) {
    fail('Could not list database tables.');
}

// 4. Critical admin routes reachable (best-effort; skipped if server unreachable)
echo "\n4. Critical Route Reachability\n";
$baseUrl = $argv[1] ?? 'http://localhost/St.monica';
$routes = [
    '/ADMIN/login/login.php',
    '/ADMIN/api/homepage/',
    '/ADMIN/api/staff/',
    '/ADMIN/api/news-events/',
    '/ADMIN/api/contact/',
];

if (function_exists('curl_init')) {
    $reachable = false;
    foreach ($routes as $route) {
        $ch = curl_init($baseUrl . $route);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_NOBODY => false]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 0) {
            warn("Could not reach {$baseUrl}{$route} (server may not be running). Skipping remaining route checks.");
            break;
        }
        $reachable = true;
        if ($code >= 200 && $code < 400) {
            pass("{$route} responded with HTTP {$code}.");
        } else {
            fail("{$route} responded with unexpected HTTP {$code}.");
        }
    }
    if (!$reachable) {
        warn('Route reachability checks skipped - pass a base URL as an argument if your site runs elsewhere, e.g.: php ADMIN/tests/smoke-test.php http://localhost/St.monica');
    }
} else {
    warn('cURL extension not available - skipping route reachability checks.');
}

// Summary
echo "\n=== Summary ===\n";
echo "Failures: {$failures}\n";
echo "Warnings: {$warnings}\n";

exit($failures > 0 ? 1 : 0);
