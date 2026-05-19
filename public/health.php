<?php
/**
 * HRMS Health Check — always returns 200 if Apache + PHP are running.
 * DB status is reported but never causes a non-200 response.
 */
header('Content-Type: application/json');

$checks = [];
$phpOk  = true;

// 1) PHP version
$checks['php'] = [
    'status' => PHP_VERSION_ID >= 70400 ? 'ok' : 'warn',
    'value'  => PHP_VERSION,
];

// 2) Required extensions
foreach (['pdo', 'pdo_mysql', 'mbstring', 'gd', 'json'] as $ext) {
    $ok = extension_loaded($ext);
    if (!$ok) $phpOk = false;
    $checks['ext_' . $ext] = ['status' => $ok ? 'ok' : 'fail'];
}

// 3) DB connection — informational only, never fails the healthcheck
$envFile = dirname(__DIR__) . '/.env';
$env     = file_exists($envFile) ? (parse_ini_file($envFile) ?: []) : [];
foreach ($env as $k => $v) { $_ENV[$k] = $_ENV[$k] ?? $v; }

$host = $_ENV['MYSQLHOST']     ?? $_ENV['DB_HOST']     ?? null;
$db   = $_ENV['MYSQLDATABASE'] ?? $_ENV['DB_DATABASE'] ?? null;
$user = $_ENV['MYSQLUSER']     ?? $_ENV['DB_USERNAME'] ?? null;
$pass = $_ENV['MYSQLPASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['MYSQLPORT']     ?? $_ENV['DB_PORT']     ?? 3306;

if ($host && $db && $user) {
    try {
        $pdo    = new PDO(
            "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_TIMEOUT => 3]
        );
        $tables = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db'"
        )->fetchColumn();
        $checks['database'] = ['status' => 'ok', 'tables' => $tables, 'host' => $host];
    } catch (Exception $e) {
        // DB down = informational warning, NOT a failure
        $checks['database'] = ['status' => 'warn', 'note' => 'DB not yet available'];
    }
} else {
    $checks['database'] = ['status' => 'warn', 'note' => 'No DB credentials in env'];
}

// 4) Storage writable — informational only
$checks['storage'] = [
    'status' => is_writable(dirname(__DIR__) . '/storage') ? 'ok' : 'warn',
];

// Always 200 — healthcheck only cares that Apache + PHP are alive
http_response_code(200);
echo json_encode([
    'status'  => $phpOk ? 'ok' : 'degraded',
    'app'     => 'HRMS Enterprise Platform',
    'time'    => date('Y-m-d H:i:s'),
    'port'    => $_SERVER['SERVER_PORT'] ?? 'unknown',
    'checks'  => $checks,
], JSON_PRETTY_PRINT);
