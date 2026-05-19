<?php
/**
 * HRMS Health Check — used by Railway to verify deployment
 * Accessible at: yourapp.up.railway.app/health.php
 */
header('Content-Type: application/json');

$checks = [];
$allOk  = true;

// 1) PHP version
$checks['php'] = [
    'status' => PHP_VERSION_ID >= 70400 ? 'ok' : 'fail',
    'value'  => PHP_VERSION,
];

// 2) Required extensions
$required = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'json'];
foreach ($required as $ext) {
    $ok = extension_loaded($ext);
    $checks['ext_' . $ext] = ['status' => $ok ? 'ok' : 'fail'];
    if (!$ok) $allOk = false;
}

// 3) DB connection
define('ROOT_PATH', dirname(__DIR__));
$env = file_exists(ROOT_PATH . '/.env') ? parse_ini_file(ROOT_PATH . '/.env') : [];
foreach ($env as $k => $v) { $_ENV[$k] = $v; }

$host = $_ENV['MYSQLHOST']     ?? $_ENV['DB_HOST']     ?? null;
$db   = $_ENV['MYSQLDATABASE'] ?? $_ENV['DB_DATABASE'] ?? null;
$user = $_ENV['MYSQLUSER']     ?? $_ENV['DB_USERNAME'] ?? null;
$pass = $_ENV['MYSQLPASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['MYSQLPORT']     ?? $_ENV['DB_PORT']     ?? 3306;

if ($host && $db && $user) {
    try {
        $pdo  = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $tables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db'")->fetchColumn();
        $checks['database'] = ['status' => 'ok', 'tables' => $tables, 'host' => $host];
    } catch (Exception $e) {
        $checks['database'] = ['status' => 'fail', 'error' => $e->getMessage()];
        $allOk = false;
    }
} else {
    $checks['database'] = ['status' => 'skip', 'note' => 'No DB credentials configured'];
}

// 4) Storage writable
$storagePath = ROOT_PATH . '/storage';
$checks['storage'] = [
    'status' => is_writable($storagePath) ? 'ok' : 'fail',
    'path'   => $storagePath,
];

http_response_code($allOk ? 200 : 503);
echo json_encode([
    'status'  => $allOk ? 'healthy' : 'degraded',
    'app'     => 'HRMS Enterprise Platform',
    'time'    => date('Y-m-d H:i:s'),
    'checks'  => $checks,
], JSON_PRETTY_PRINT);
