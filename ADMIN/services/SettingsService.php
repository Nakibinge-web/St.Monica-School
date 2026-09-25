<?php
/**
 * St. Monica Junior School CMS
 * Site Settings Service (cached key/value store over `site_settings`)
 */

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

require_once CMS_ROOT . '/includes/database.php';

class SettingsService {
    private static ?array $cache = null;

    private static function loadAll(): array {
        if (self::$cache !== null) {
            return self::$cache;
        }
        try {
            $rows = Database::fetchAll("SELECT `setting_key`, `setting_value` FROM `site_settings`");
            self::$cache = [];
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            self::$cache = [];
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed {
        $all = self::loadAll();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function getBool(string $key, bool $default = false): bool {
        $val = self::get($key, $default ? '1' : '0');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function all(): array {
        return self::loadAll();
    }

    public static function set(string $key, string $value): void {
        Database::query(
            "INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE `setting_value` = :v2",
            ['k' => $key, 'v' => $value, 'v2' => $value]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    public static function setMany(array $pairs): void {
        foreach ($pairs as $key => $value) {
            self::set($key, (string)$value);
        }
    }

    public static function isMaintenanceMode(): bool {
        return self::getBool('maintenance_mode', false);
    }
}
