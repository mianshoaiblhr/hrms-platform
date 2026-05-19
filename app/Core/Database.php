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

    private function __construct()
    {
        // Read Railway's injected vars first, fall back to DB_* from .env
        $host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '127.0.0.1';
        $port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';
        $dbname   = getenv('MYSQLDATABASE') ?: getenv('DB_DATABASE') ?: 'hrms_db';
        $username = getenv('MYSQLUSER')     ?: getenv('DB_USERNAME') ?: 'root';
        $password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
        $charset  = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]);
        } catch (PDOException $e) {
            // Show a friendly page instead of a raw 500
            http_response_code(503);
            $host_display = htmlspecialchars($host);
            echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>HRMS — Database Not Connected</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card shadow-sm" style="max-width:480px;width:100%">
  <div class="card-body p-5 text-center">
    <div class="display-1 mb-3">🗄️</div>
    <h4 class="fw-bold">Database Not Connected</h4>
    <p class="text-muted mb-4">
      The HRMS app is running but cannot reach MySQL at
      <code>{$host_display}</code>.
    </p>
    <div class="alert alert-info text-start small mb-4">
      <strong>On Railway:</strong><br>
      1. Click <strong>+ New → Database → MySQL</strong><br>
      2. Wait ~30 seconds for it to provision<br>
      3. Click <strong>Redeploy</strong> on this service<br>
      4. Refresh this page
    </div>
    <button onclick="location.reload()" class="btn btn-primary">
      🔄 Refresh
    </button>
  </div>
</div>
</body>
</html>
HTML;
            exit;
        }
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Query helpers ──────────────────────────────────────────────────────

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
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
        $set   = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $cond  = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
        $stmt  = $this->query(
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
        $total   = (int) $this->fetchColumn("SELECT COUNT(*) FROM ({$sql}) AS t", $params);
        $offset  = ($page - 1) * $perPage;
        $rows    = $this->fetchAll("{$sql} LIMIT {$perPage} OFFSET {$offset}", $params);
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
