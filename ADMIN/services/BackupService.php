<?php
/**
 * St. Monica Junior School CMS
 * Database Backup & Restore Service
 *
 * Prefers the mysqldump/mysql CLI clients (credentials passed via the
 * MYSQL_PWD environment variable so they never appear in a process list),
 * and falls back to a pure-PHP dumper/importer when the CLI tools aren't
 * available on the host. Backup files are stored outside the public web
 * root and must always be downloaded through an authenticated admin script.
 */

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

require_once CMS_ROOT . '/includes/database.php';

class BackupService {
    private static array $dbConfig = [];
    private static array $backupConfig = [];

    private static function loadConfig(): void {
        if (empty(self::$dbConfig)) {
            $all = require CMS_ROOT . '/includes/config.php';
            self::$dbConfig = $all['db'];
            self::$backupConfig = $all['backup'];
        }
    }

    public static function backupDir(): string {
        self::loadConfig();
        $dir = self::$backupConfig['dir'];
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // Deny direct web access if this directory is ever placed under a web root
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
        }
        return $dir;
    }

    /**
     * Locate a CLI binary: explicit config path, else PATH lookup.
     */
    private static function findBinary(string $configuredPath, string $binName): ?string {
        if ($configuredPath && is_file($configuredPath)) {
            return $configuredPath;
        }
        $isWindows = stripos(PHP_OS, 'WIN') === 0;
        $finder = $isWindows ? 'where' : 'which';
        $result = @shell_exec($finder . ' ' . escapeshellarg($binName) . ' 2>' . ($isWindows ? 'NUL' : '/dev/null'));
        if ($result) {
            $first = trim(explode("\n", trim($result))[0]);
            if ($first !== '' && is_file($first)) {
                return $first;
            }
        }
        return null;
    }

    /**
     * Create a new backup file. Returns the filename on success, or null on failure.
     */
    public static function create(): ?string {
        self::loadConfig();
        $dir = self::backupDir();
        $filename = 'st_monica_' . date('Y-m-d_His') . '.sql';
        $destination = $dir . '/' . $filename;

        $dumpBin = self::findBinary(self::$backupConfig['mysqldump_bin'] ?? '', 'mysqldump');

        if ($dumpBin && function_exists('exec')) {
            if (self::createViaCli($dumpBin, $destination)) {
                return $filename;
            }
        }

        // Fallback: pure PHP dump
        if (self::createViaPhp($destination)) {
            return $filename;
        }

        return null;
    }

    private static function createViaCli(string $dumpBin, string $destination): bool {
        $cfg = self::$dbConfig;
        $cmd = escapeshellarg($dumpBin)
            . ' --host=' . escapeshellarg($cfg['host'])
            . ' --port=' . escapeshellarg((string)$cfg['port'])
            . ' --user=' . escapeshellarg($cfg['user'])
            . ' --single-transaction --routines --triggers '
            . escapeshellarg($cfg['name'])
            . ' --result-file=' . escapeshellarg($destination);

        $prevPwd = getenv('MYSQL_PWD');
        putenv('MYSQL_PWD=' . ($cfg['pass'] ?? ''));
        exec($cmd . ' 2>&1', $output, $exitCode);
        putenv($prevPwd !== false ? 'MYSQL_PWD=' . $prevPwd : 'MYSQL_PWD');

        return $exitCode === 0 && file_exists($destination) && filesize($destination) > 0;
    }

    private static function createViaPhp(string $destination): bool {
        try {
            $pdo = Database::getConnection();
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            $fh = fopen($destination, 'w');
            if (!$fh) return false;

            fwrite($fh, "-- St. Monica CMS PHP-based database dump\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $table) {
                $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                $createSql = $createRow['Create Table'] ?? null;
                if (!$createSql) continue;

                fwrite($fh, "-- Table: {$table}\n");
                fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($fh, $createSql . ";\n\n");

                $rowCount = 0;
                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cols = array_map(fn($c) => "`{$c}`", array_keys($row));
                    $vals = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote((string)$v);
                    }, array_values($row));
                    fwrite($fh, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n");
                    $rowCount++;
                }
                if ($rowCount > 0) fwrite($fh, "\n");
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);
            return file_exists($destination) && filesize($destination) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * List available backups, newest first.
     */
    public static function list(): array {
        $dir = self::backupDir();
        $files = glob($dir . '/*.sql') ?: [];
        $items = [];
        foreach ($files as $path) {
            $items[] = [
                'filename' => basename($path),
                'size'     => filesize($path),
                'created'  => filemtime($path)
            ];
        }
        usort($items, fn($a, $b) => $b['created'] <=> $a['created']);
        return $items;
    }

    /**
     * Resolve a safe absolute path for a backup filename, rejecting traversal attempts.
     */
    public static function resolvePath(string $filename): ?string {
        $safeName = basename($filename);
        if (!preg_match('/^[A-Za-z0-9_\-.]+\.sql$/', $safeName)) {
            return null;
        }
        $path = self::backupDir() . '/' . $safeName;
        return file_exists($path) ? $path : null;
    }

    public static function delete(string $filename): bool {
        $path = self::resolvePath($filename);
        if (!$path) return false;
        return @unlink($path);
    }

    /**
     * Restore the database from a backup file. Extremely destructive -
     * callers must already have obtained explicit, typed confirmation and
     * created a pre-restore safety backup before calling this.
     */
    public static function restore(string $filename): bool {
        self::loadConfig();
        $path = self::resolvePath($filename);
        if (!$path) return false;

        $mysqlBin = self::findBinary(self::$backupConfig['mysql_bin'] ?? '', 'mysql');
        if ($mysqlBin && function_exists('exec')) {
            $cfg = self::$dbConfig;
            $cmd = escapeshellarg($mysqlBin)
                . ' --host=' . escapeshellarg($cfg['host'])
                . ' --port=' . escapeshellarg((string)$cfg['port'])
                . ' --user=' . escapeshellarg($cfg['user'])
                . ' ' . escapeshellarg($cfg['name']);

            $prevPwd = getenv('MYSQL_PWD');
            putenv('MYSQL_PWD=' . ($cfg['pass'] ?? ''));
            $isWindows = stripos(PHP_OS, 'WIN') === 0;
            if ($isWindows) {
                exec($cmd . ' < ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);
            } else {
                exec($cmd . ' < ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);
            }
            putenv($prevPwd !== false ? 'MYSQL_PWD=' . $prevPwd : 'MYSQL_PWD');

            if ($exitCode === 0) return true;
        }

        // Fallback: naive statement-splitting execution (consistent with
        // ADMIN/database/setup.php's own migration runner approach)
        try {
            $pdo = Database::getConnection();
            $sql = file_get_contents($path);
            $sql = preg_replace('/--[^\n]*/', '', $sql);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
