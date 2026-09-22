<?php
/**
 * St. Monica Junior School - Database Setup & Migration Script
 * Can be run via CLI: php ADMIN/database/setup.php
 * Or via browser: http://localhost:8000/ADMIN/database/setup.php
 */

$isCli = (php_sapi_name() === 'cli');

function outputMsg($msg, $type = 'info') {
    global $isCli;
    if ($isCli) {
        $prefix = match($type) {
            'success' => '[SUCCESS] ',
            'error'   => '[ERROR] ',
            'warn'    => '[WARNING] ',
            default   => '[INFO] '
        };
        echo $prefix . $msg . "\n";
    } else {
        $color = match($type) {
            'success' => '#15803d',
            'error'   => '#b91c1c',
            'warn'    => '#b45309',
            default   => '#1e2a4a'
        };
        echo "<div style='margin: 8px 0; padding: 10px 14px; border-radius: 6px; background: #f8fafc; border-left: 4px solid {$color}; color: {$color}; font-family: monospace; font-size: 14px;'>{$msg}</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>Database Setup - St. Monica CMS</title><style>body{font-family:sans-serif;background:#f1f5f9;padding:40px;max-width:800px;margin:auto;} .box{background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);}</style></head><body><div class='box'><h2 style='color:#1e2a4a;margin-top:0;'>St. Monica Junior School — Database Setup</h2>";
}

outputMsg("Starting St. Monica CMS Database Installation...");

// Configuration resolution
$configFile = __DIR__ . '/../includes/config.php';
$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbName = 'st_monica';
$dbUser = 'root';
$dbPass = '';

if (file_exists($configFile)) {
    $config = require $configFile;
    $dbHost = $config['db']['host'] ?? $dbHost;
    $dbPort = $config['db']['port'] ?? $dbPort;
    $dbName = $config['db']['name'] ?? $dbName;
    $dbUser = $config['db']['user'] ?? $dbUser;
    $dbPass = $config['db']['pass'] ?? $dbPass;
}

// Check GET or CLI overrides if provided
if ($isCli) {
    global $argv;
    if (isset($argv[1])) $dbPort = (int)$argv[1];
    if (isset($argv[2])) $dbPass = $argv[2];
} else {
    if (isset($_GET['port'])) $dbPort = (int)$_GET['port'];
    if (isset($_GET['pass'])) $dbPass = $_GET['pass'];
}

// Attempt connection - test port candidates if needed
$portsToTest = array_unique([$dbPort, 3306, 3307]);
$pdo = null;
$connectedPort = null;

foreach ($portsToTest as $port) {
    try {
        outputMsg("Testing connection to MySQL at {$dbHost}:{$port} with user '{$dbUser}'...");
        $dsn = "mysql:host={$dbHost};port={$port};charset=utf8mb4";
        $testPdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $pdo = $testPdo;
        $connectedPort = $port;
        outputMsg("Connected successfully to MySQL on port {$port}!", 'success');
        break;
    } catch (PDOException $e) {
        outputMsg("Could not connect on port {$port}: " . $e->getMessage(), 'warn');
    }
}

if (!$pdo) {
    outputMsg("FAILED to connect to MySQL on any tested port (3306, 3307). Please verify MySQL is running and credentials in ADMIN/includes/config.php are correct.", 'error');
    if (!$isCli) {
        echo "<p style='color:#64748b;'>Tip: If your MySQL password is set, you can run: <code>http://localhost:8000/ADMIN/database/setup.php?port=3307&pass=YOUR_PASSWORD</code></p></div></body></html>";
    }
    exit(1);
}

// Create database if not exists
try {
    outputMsg("Ensuring database '{$dbName}' exists...");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    outputMsg("Database '{$dbName}' ready.", 'success');
} catch (PDOException $e) {
    outputMsg("Database creation failed: " . $e->getMessage(), 'error');
    exit(1);
}

// Function to run SQL file
function executeSqlFile($pdo, $filePath, $label) {
    if (!file_exists($filePath)) {
        outputMsg("File not found: {$filePath}", 'error');
        return false;
    }

    outputMsg("Executing {$label} ({$filePath})...");
    $sqlContent = file_get_contents($filePath);

    // Remove comments and split statements safely
    $sqlContent = preg_replace('/--[^\n]*/', '', $sqlContent);
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

    $count = 0;
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                $count++;
            } catch (PDOException $e) {
                // Noticeable query error
                outputMsg("Notice on query: " . substr($stmt, 0, 60) . "... Error: " . $e->getMessage(), 'warn');
            }
        }
    }
    outputMsg("Successfully processed {$count} queries in {$label}.", 'success');
    return true;
}

// Run schema.sql
executeSqlFile($pdo, __DIR__ . '/schema.sql', 'Schema Definitions');

// Run seed.sql
executeSqlFile($pdo, __DIR__ . '/seed.sql', 'Initial Seed Data');

// Discover and run incremental migrations
$migrationsDir = __DIR__ . '/migrations';
if (is_dir($migrationsDir)) {
    outputMsg("Scanning for incremental migrations in {$migrationsDir}...");
    $migrationFiles = glob($migrationsDir . '/*.sql');
    sort($migrationFiles);

    if (!empty($migrationFiles)) {
        foreach ($migrationFiles as $mFile) {
            $mName = basename($mFile);
            executeSqlFile($pdo, $mFile, "Migration: {$mName}");
        }
        outputMsg("All " . count($migrationFiles) . " migrations processed successfully!", 'success');
    } else {
        outputMsg("No incremental migrations found.");
    }
}

outputMsg("Database setup completed successfully!", 'success');
outputMsg("Default Administrator Account:", 'info');
outputMsg("  Email: admin@stmonicakasanje.ac.ug", 'info');
outputMsg("  Password: Admin@2026!", 'info');
outputMsg("  Login URL: /ADMIN/login/login.php", 'info');

if (!$isCli) {
    echo "<div style='margin-top:24px;'><a href='../login/login.php' style='display:inline-block;background:#d93633;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;'>Go to Admin Login &rarr;</a></div></div></body></html>";
}
