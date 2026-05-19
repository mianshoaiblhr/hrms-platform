<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance    = null;
    private static ?string   $driverName  = null;
    private PDO $pdo;

    private function __construct()
    {
        $host = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '';
        if ($host && $host !== '127.0.0.1' && $host !== 'localhost') {
            $this->connectMySQL($host);
        } else {
            $this->connectSQLite();
        }
    }

    private function connectMySQL(string $host): void
    {
        $port = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';
        $db   = getenv('MYSQLDATABASE') ?: getenv('DB_DATABASE') ?: 'hrms_db';
        $user = getenv('MYSQLUSER')     ?: getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
        try {
            $this->pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false,
                 PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                 PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
            );
            self::$driverName = 'mysql';
        } catch (PDOException $e) {
            error_log("MySQL failed: {$e->getMessage()}, falling back to SQLite");
            $this->connectSQLite();
        }
    }

    private function connectSQLite(): void
    {
        $path = (defined('STORAGE_PATH') ? STORAGE_PATH : '/var/www/html/storage') . '/hrms.sqlite';
        $isNew = !file_exists($path);
        $this->pdo = new PDO("sqlite:{$path}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec("PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;");
        self::$driverName = 'sqlite';
        if ($isNew) {
            $mig = dirname($path) . '/migrate_sqlite.php';
            if (file_exists($mig)) { $db = $this->pdo; require $mig; migrate($db); }
        }
    }

    public static function getInstance(): self
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public static function getDriver(): string { return self::$driverName ?? 'sqlite'; }

    // ── Translate MySQL SQL → SQLite ──────────────────────────────────────
    private static function sqlite(string $sql): string
    {
        // 1. CONCAT(a, b, c) → (a || b || c)
        $sql = preg_replace_callback(
            '/CONCAT\s*\(([^()]*(?:\([^()]*\)[^()]*)*)\)/i',
            function($m) {
                $parts = preg_split('/,(?![^(]*\))/', $m[1]);
                return '(' . implode(' || ', array_map('trim', $parts)) . ')';
            }, $sql
        );
        // 2. IFNULL → COALESCE
        $sql = preg_replace('/\bIFNULL\s*\(/i', 'COALESCE(', $sql);
        // 3. SUM(col='val') → SUM(CASE WHEN…)
        $sql = preg_replace_callback(
            "/SUM\s*\(\s*`?(\w+)`?\s*=\s*'([^']*)'\s*\)/i",
            fn($m) => "SUM(CASE WHEN {$m[1]}='{$m[2]}' THEN 1 ELSE 0 END)",
            $sql
        );
        // 4. DATE_FORMAT
        $sql = preg_replace_callback(
            "/DATE_FORMAT\s*\(\s*(.+?)\s*,\s*'([^']*)'\s*\)/i",
            function($m) {
                $e = str_ireplace(['CURDATE()', 'NOW()'], ["'now'", "'now'"], trim($m[1]));
                return "strftime('" . str_replace('%i', '%M', $m[2]) . "', {$e})";
            }, $sql
        );
        // 5. DATE_SUB / DATE_ADD
        $sql = preg_replace_callback(
            '/DATE_(SUB|ADD)\s*\(\s*[^,]+\s*,\s*INTERVAL\s+(\d+)\s+(SECOND|MINUTE|HOUR|DAY|MONTH|YEAR)S?\s*\)/i',
            function($m) {
                $sign = strtoupper($m[1]) === 'SUB' ? '-' : '+';
                $unit = strtolower($m[3]) . 's';
                $fn   = in_array(strtoupper($m[3]), ['DAY','MONTH','YEAR']) ? 'date' : 'datetime';
                return "{$fn}('now', '{$sign}{$m[2]} {$unit}')";
            }, $sql
        );
        // 6. DATEDIFF
        $sql = preg_replace_callback(
            '/DATEDIFF\s*\(\s*(.+?)\s*,\s*(.+?)\s*\)/i',
            fn($m) => "CAST(julianday({$m[1]})-julianday({$m[2]}) AS INTEGER)",
            $sql
        );
        // 7. MONTH/YEAR/DAY
        $sql = preg_replace_callback(
            '/\b(MONTH|YEAR|DAY)\s*\(\s*(.+?)\s*\)/i',
            function($m) {
                $f = ['MONTH'=>'%m','YEAR'=>'%Y','DAY'=>'%d'][strtoupper($m[1])];
                $e = str_ireplace(['CURDATE()', 'NOW()'], ["'now'", "'now'"], trim($m[2]));
                return "CAST(strftime('{$f}', {$e}) AS INTEGER)";
            }, $sql
        );
        // 8. Simple swaps
        return str_ireplace(
            ['UNIX_TIMESTAMP()', 'NOW()', 'CURDATE()'],
            ["strftime('%s','now')", "datetime('now')", "date('now')"],
            $sql
        );
    }

    // ── Query API ─────────────────────────────────────────────────────────

    public function query(string $sql, array $params = []): \PDOStatement
    {
        if (self::$driverName === 'sqlite') $sql = self::sqlite($sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll() ?: [];
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $r = $this->query($sql, $params)->fetch();
        return $r ?: null;
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function insert(string $table, array $data): int
    {
        if (empty($data)) return 0;
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO {$table} ({$cols}) VALUES ({$phs})", array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array|string $where, array $wp = []): int
    {
        if (empty($data)) return 0;
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        if (is_array($where)) {
            $cond   = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
            $params = [...array_values($data), ...array_values($where)];
        } else {
            $cond   = $where;
            $params = [...array_values($data), ...$wp];
        }
        return $this->query("UPDATE {$table} SET {$set} WHERE {$cond}", $params)->rowCount();
    }

    public function softDelete(string $table, int $id): int
    {
        return $this->update($table, ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    public function paginate(string $sql, array $params, int $page, int $perPage = 25): array
    {
        $total  = (int)$this->fetchColumn("SELECT COUNT(*) FROM ({$sql}) AS _t", $params);
        $offset = ($page - 1) * $perPage;
        $rows   = $this->fetchAll("{$sql} LIMIT {$perPage} OFFSET {$offset}", $params);
        return ['data' => $rows, 'total' => $total, 'per_page' => $perPage,
                'current_page' => $page, 'last_page' => max(1, (int)ceil($total / $perPage))];
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollback(): void         { $this->pdo->rollBack(); }
    public function lastInsertId(): int      { return (int)$this->pdo->lastInsertId(); }
}
