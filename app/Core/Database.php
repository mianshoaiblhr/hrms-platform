<?php
// ============================================================
// app/Core/Database.php - Secure PDO Database Layer
// ============================================================

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private string $host;
    private string $port;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset;

    private function __construct()
    {
        $this->host     = getenv('DB_HOST')     ?: 'localhost';
        $this->port     = getenv('DB_PORT')     ?: '3306';
        $this->dbname   = getenv('DB_DATABASE') ?: 'hrms_db';
        $this->username = getenv('DB_USERNAME') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->charset  = getenv('DB_CHARSET')  ?: 'utf8mb4';

        $this->connect();
    }

    private function connect(): void
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            $this->pdo->exec("SET time_zone = '+05:00'");
            $this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            Logger::critical('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed. Please check configuration.');
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query with bound parameters (SQL injection safe)
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single record
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all records
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single column value
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert a record
     */
    public function insert(string $table, array $data): int
    {
        $table   = $this->sanitizeIdentifier($table);
        $columns = implode(', ', array_map([$this, 'sanitizeIdentifier'], array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update records
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $table = $this->sanitizeIdentifier($table);
        $set   = implode(', ', array_map(
            fn($col) => $this->sanitizeIdentifier($col) . ' = ?',
            array_keys($data)
        ));

        $sql    = "UPDATE {$table} SET {$set} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        $stmt   = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Soft delete
     */
    public function softDelete(string $table, int $id): int
    {
        return $this->update($table, ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Paginate query results
     */
    public function paginate(string $sql, array $params = [], int $page = 1, int $perPage = 20): array
    {
        $countSql  = "SELECT COUNT(*) FROM ({$sql}) AS count_query";
        $total     = (int)$this->fetchColumn($countSql, $params);
        $totalPages= (int)ceil($total / $perPage);
        $offset    = ($page - 1) * $perPage;

        $dataSql   = $sql . " LIMIT {$perPage} OFFSET {$offset}";
        $data      = $this->fetchAll($dataSql, $params);

        return [
            'data'        => $data,
            'total'       => $total,
            'per_page'    => $perPage,
            'current_page'=> $page,
            'last_page'   => $totalPages,
            'from'        => $offset + 1,
            'to'          => min($offset + $perPage, $total),
        ];
    }

    /**
     * Sanitize column/table identifiers
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid SQL identifier: {$identifier}");
        }
        return "`{$identifier}`";
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() { throw new \RuntimeException("Cannot unserialize singleton."); }
}
