<?php
/**
 * St. Monica Junior School CMS
 * Central Database Connection & PDO Wrapper
 */

if (!defined('CMS_ROOT')) {
    if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));
}

class Database {
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Get or initialize PDO singleton connection
     */
    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (empty(self::$config)) {
            $configFile = CMS_ROOT . '/includes/config.php';
            if (!file_exists($configFile)) {
                throw new RuntimeException("Configuration file missing at {$configFile}");
            }
            $allConfig = require $configFile;
            self::$config = $allConfig['db'];
        }

        $host = self::$config['host'];
        $port = self::$config['port'];
        $name = self::$config['name'];
        $user = self::$config['user'];
        $pass = self::$config['pass'];
        $charset = self::$config['charset'] ?? 'utf8mb4';
        $options = self::$config['options'] ?? [];

        // Try primary port first; if it fails with connection refused, try fallback port
        $portsToTry = array_unique([$port, 3306, 3307]);
        $lastException = null;

        foreach ($portsToTry as $tryPort) {
            try {
                $dsn = "mysql:host={$host};port={$tryPort};dbname={$name};charset={$charset}";
                $pdo = new PDO($dsn, $user, $pass, $options);
                self::$instance = $pdo;
                return self::$instance;
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        // If connection failed because database doesn't exist yet, attempt to connect to server without dbname
        foreach ($portsToTry as $tryPort) {
            try {
                $dsn = "mysql:host={$host};port={$tryPort};charset={$charset}";
                $serverPdo = new PDO($dsn, $user, $pass, $options);
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $serverPdo->exec("USE `{$name}`");
                self::$instance = $serverPdo;
                return self::$instance;
            } catch (PDOException $e) {
                // Keep looking
            }
        }

        throw new RuntimeException("Database connection failed: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
    }

    /**
     * Execute a prepared query and return PDOStatement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all matching records
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single row or null
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * Fetch a single column value (e.g. COUNT(*))
     */
    public static function fetchColumn(string $sql, array $params = [], int $col = 0): mixed {
        return self::query($sql, $params)->fetchColumn($col);
    }

    /**
     * Fetch all values of a single column as a flat indexed array
     */
    public static function fetchColumnAll(string $sql, array $params = [], int $col = 0): array {
        $result = self::query($sql, $params)->fetchAll(PDO::FETCH_COLUMN, $col);
        return $result !== false ? $result : [];
    }

    /**
     * Helper to insert a record into a table
     */
    public static function insert(string $table, array $data): int {
        $pdo = self::getConnection();
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);

        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $table,
            implode('`, `', $fields),
            implode(', ', $placeholders)
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Helper to update records in a table
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $pdo = self::getConnection();
        $setClauses = [];
        $params = [];

        foreach ($data as $key => $val) {
            $setClauses[] = "`{$key}` = :set_{$key}";
            $params["set_{$key}"] = $val;
        }

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE %s",
            $table,
            implode(', ', $setClauses),
            $where
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Helper to delete records
     */
    public static function delete(string $table, string $where, array $whereParams = []): int {
        $pdo = self::getConnection();
        $sql = sprintf("DELETE FROM `%s` WHERE %s", $table, $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($whereParams);
        return $stmt->rowCount();
    }
}

/**
 * Global shortcut for Database::getConnection()
 */
function get_db(): PDO {
    return Database::getConnection();
}
