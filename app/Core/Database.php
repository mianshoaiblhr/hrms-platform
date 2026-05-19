<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private static string $driver = 'mysql';

    private function __construct()
    {
        // Railway injects MYSQLHOST; fall back to DB_HOST from .env
        $mysqlHost = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '';

        if ($mysqlHost && $mysqlHost !== '127.0.0.1') {
            $this->connectMySQL($mysqlHost);
        } else {
            $this->connectSQLite();
        }
    }

    private function connectMySQL(string $host): void
    {
        $port   = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';
        $db     = getenv('MYSQLDATABASE') ?: getenv('DB_DATABASE') ?: 'hrms_db';
        $user   = getenv('MYSQLUSER')     ?: getenv('DB_USERNAME') ?: 'root';
        $pass   = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';

        try {
            $this->pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
                $user, $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                ]
            );
            self::$driver = 'mysql';
        } catch (PDOException $e) {
            // MySQL configured but unreachable — fall back to SQLite
            error_log("MySQL unavailable ({$e->getMessage()}), falling back to SQLite");
            $this->connectSQLite();
        }
    }

    private function connectSQLite(): void
    {
        $path = defined('STORAGE_PATH') ? STORAGE_PATH . '/hrms.sqlite' : '/var/www/html/storage/hrms.sqlite';

        $new  = !file_exists($path);
        $this->pdo = new PDO("sqlite:{$path}", null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        self::$driver = 'sqlite';

        if ($new) {
            $migrationFile = dirname($path) . '/migrate_sqlite.php';
            if (file_exists($migrationFile)) {
                $db = $this->pdo;
                require $migrationFile;
                migrate($db);
            }
        }
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getDriver(): string { return self::$driver; }

    // ── Query helpers ──────────────────────────────────────────────────────

    public function query(string $sql, array $params = []): \PDOStatement
    {
        if (self::$driver === 'sqlite') {
            $sql = self::toSQLite($sql);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Translate MySQL-specific SQL to SQLite-compatible SQL.
     * Handles: DATE_SUB, DATE_ADD, DATE_FORMAT, CURDATE(), NOW(),
     *          UNIX_TIMESTAMP(), backtick identifiers.
     */
    private static function toSQLite(string $sql): string
    {
        // 1. DATE_FORMAT(expr, 'fmt') → strftime('fmt', expr)
        //    Must run before DATE_ADD/DATE_SUB so nested calls resolve correctly
        $sql = preg_replace_callback(
            "/DATE_FORMAT\s*\(\s*(.+?)\s*,\s*'([^']*)'\s*\)/i",
            function ($m) {
                $expr = self::resolveDateExpr(trim($m[1]));
                $fmt  = str_replace('%i', '%M', $m[2]); // MySQL %i = minutes → SQLite %M
                return "strftime('{$fmt}', {$expr})";
            },
            $sql
        );

        // 2. DATE_SUB(expr, INTERVAL n UNIT) → datetime/date('now', '-n units')
        $sql = preg_replace_callback(
            '/DATE_SUB\s*\(\s*(.+?)\s*,\s*INTERVAL\s+(\d+)\s+(SECOND|MINUTE|HOUR|DAY|MONTH|YEAR)S?\s*\)/i',
            fn($m) => self::intervalExpr($m[1], '-', $m[2], $m[3]),
            $sql
        );

        // 3. DATE_ADD(expr, INTERVAL n UNIT) → datetime/date('now', '+n units')
        $sql = preg_replace_callback(
            '/DATE_ADD\s*\(\s*(.+?)\s*,\s*INTERVAL\s+(\d+)\s+(SECOND|MINUTE|HOUR|DAY|MONTH|YEAR)S?\s*\)/i',
            fn($m) => self::intervalExpr($m[1], '+', $m[2], $m[3]),
            $sql
        );

        // 4. Simple replacements (case-insensitive, longest first)
        $search  = ['UNIX_TIMESTAMP()', 'NOW()', 'CURDATE()'];
        $replace = ["strftime('%s','now')", "datetime('now')", "date('now')"];
        foreach ($search as $i => $s) {
            $sql = str_ireplace($s, $replace[$i], $sql);
        }

        return $sql;
    }

    private static function resolveDateExpr(string $expr): string
    {
        if (stripos($expr, 'CURDATE()') !== false) return str_ireplace('CURDATE()', 'now', $expr);
        if (stripos($expr, 'NOW()')    !== false) return str_ireplace('NOW()',    'now', $expr);
        // bare column name → pass through
        return $expr;
    }

    private static function intervalExpr(string $base, string $sign, string $n, string $unit): string
    {
        $base = trim($base);
        // Choose date() vs datetime() based on precision
        $useDateOnly = in_array(strtoupper($unit), ['DAY','MONTH','YEAR'])
                    && stripos($base, 'CURDATE') !== false;
        $fn   = $useDateOnly ? 'date' : 'datetime';
        $mod  = "{$sign}{$n} " . strtolower($unit) . 's';
        return "{$fn}('now', '{$mod}')";
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function insert(string $table, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO {$table} ({$cols}) VALUES ({$phs})", array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $set  = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $cond = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
        $stmt = $this->query(
            "UPDATE {$table} SET {$set} WHERE {$cond}",
            [...array_values($data), ...array_values($where)]
        );
        return $stmt->rowCount();
    }

    public function softDelete(string $table, int $id): int
    {
        return $this->update($table, ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    public function paginate(string $sql, array $params, int $page, int $perPage = 25): array
    {
        $total  = (int) $this->fetchColumn("SELECT COUNT(*) FROM ({$sql}) AS t", $params);
        $offset = ($page - 1) * $perPage;
        $rows   = $this->fetchAll("{$sql} LIMIT {$perPage} OFFSET {$offset}", $params);
        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollback(): void         { $this->pdo->rollBack(); }
    public function lastInsertId(): int      { return (int) $this->pdo->lastInsertId(); }
}
