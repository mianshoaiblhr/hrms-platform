<?php
/**
 * ORBIT HRMS — Live Status Diagnostic
 * Visit: yourapp.railway.app/status.php
 */
header('Content-Type: text/html; charset=utf-8');

$envFile = dirname(__DIR__) . '/.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || !strpos($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db   = $env['DB_DATABASE'] ?? 'hrms_db';
$user = $env['DB_USERNAME'] ?? 'hrms';
$pass = $env['DB_PASSWORD'] ?? 'hrms_secret';

$checks = [];
$pdo = null;

// 1. PHP version
$checks[] = ['PHP Version', PHP_VERSION, PHP_VERSION_ID >= 80000 ? 'ok' : 'warn'];

// 2. Extensions
foreach (['pdo_mysql', 'mbstring', 'gd', 'json'] as $ext) {
    $checks[] = ["ext: $ext", extension_loaded($ext) ? 'loaded' : 'MISSING',
                 extension_loaded($ext) ? 'ok' : 'fail'];
}

// 3. .env file
$checks[] = ['.env file', file_exists($envFile) ? 'found' : 'MISSING',
             file_exists($envFile) ? 'ok' : 'fail'];
$checks[] = ['DB_HOST', $host, 'info'];
$checks[] = ['DB_DATABASE', $db, 'info'];
$checks[] = ['DB_USERNAME', $user, 'info'];

// 4. MySQL connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user, $pass, [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $checks[] = ['MySQL connection', "✅ Connected to $host:$port/$db", 'ok'];
} catch (Exception $e) {
    $checks[] = ['MySQL connection', '❌ ' . $e->getMessage(), 'fail'];
}

// 5. Table count
if ($pdo) {
    $tables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db'")->fetchColumn();
    $checks[] = ['Tables in DB', $tables, $tables >= 15 ? 'ok' : 'fail'];
}

// 6. Admin user
if ($pdo) {
    $admin = $pdo->query("SELECT id, username, is_active, is_super_admin, login_attempts, locked_until, LEFT(password,10) as pw_prefix FROM users WHERE username='admin'")->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $checks[] = ['Admin user', "Found (id={$admin['id']})", 'ok'];
        $checks[] = ['Admin active', $admin['is_active'] ? 'YES' : 'NO', $admin['is_active'] ? 'ok' : 'fail'];
        $checks[] = ['Admin super', $admin['is_super_admin'] ? 'YES' : 'NO', $admin['is_super_admin'] ? 'ok' : 'warn'];
        $checks[] = ['Password prefix', $admin['pw_prefix'].'...', str_starts_with($admin['pw_prefix'], '$2y$') ? 'ok' : 'fail'];
        $checks[] = ['Login attempts', $admin['login_attempts'], (int)$admin['login_attempts'] < 5 ? 'ok' : 'fail'];
        $checks[] = ['Locked until', $admin['locked_until'] ?: 'NOT LOCKED',
                     (!$admin['locked_until'] || strtotime($admin['locked_until']) < time()) ? 'ok' : 'fail'];
        
        // Test password
        $valid = password_verify('Admin@123', $pdo->query("SELECT password FROM users WHERE username='admin'")->fetchColumn());
        $checks[] = ['Password Admin@123', $valid ? '✅ CORRECT' : '❌ WRONG HASH', $valid ? 'ok' : 'fail'];
    } else {
        $checks[] = ['Admin user', '❌ NOT FOUND', 'fail'];
    }
}

// 7. Key tables exist
if ($pdo) {
    foreach (['users','roles','employees','attendance','leave_applications','payroll_periods','payroll_items'] as $t) {
        try {
            $cnt = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            $checks[] = ["table: $t", "$cnt rows", 'ok'];
        } catch (Exception $e) {
            $checks[] = ["table: $t", '❌ ' . $e->getMessage(), 'fail'];
        }
    }
}

// 8. Patches applied
if ($pdo) {
    try {
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$db' AND table_name='users' AND column_name='is_super_admin'")->fetchColumn();
        $checks[] = ['Patch: users.is_super_admin', $has ? 'Applied' : 'NOT applied', $has ? 'ok' : 'fail'];
    } catch (Exception $e) {}
    try {
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.views WHERE table_schema='$db' AND table_name='fbr_tax_slabs'")->fetchColumn();
        $checks[] = ['Patch: fbr_tax_slabs VIEW', $has ? 'Applied' : 'NOT applied', $has ? 'ok' : 'warn'];
    } catch (Exception $e) {}
}

$colors = ['ok'=>'#dcfce7','fail'=>'#fee2e2','warn'=>'#fef9c3','info'=>'#eff6ff'];
$dots   = ['ok'=>'🟢','fail'=>'🔴','warn'=>'🟡','info'=>'🔵'];
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>ORBIT HRMS Status</title>
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;margin:0}
h1{color:#818cf8;font-size:1.5rem}
.subtitle{color:#64748b;margin-bottom:2rem}
table{border-collapse:collapse;width:100%;max-width:700px}
th{background:#1e293b;color:#94a3b8;padding:8px 16px;text-align:left;font-size:.75rem;text-transform:uppercase;letter-spacing:.1em}
td{padding:10px 16px;border-bottom:1px solid #1e293b;font-size:.875rem}
.ok{color:#4ade80}.fail{color:#f87171}.warn{color:#fbbf24}.info{color:#60a5fa}
.fix{background:#1e293b;border-radius:8px;padding:1rem 1.5rem;margin-top:2rem;max-width:700px}
.fix h3{color:#f59e0b;margin:0 0 .5rem}
.fix a{color:#818cf8}
</style></head>
<body>
<h1>🛰 ORBIT HRMS — Live Status</h1>
<div class="subtitle"><?= date('Y-m-d H:i:s') ?> UTC &nbsp;|&nbsp; <?= gethostname() ?></div>

<table>
<tr><th>Check</th><th>Result</th></tr>
<?php foreach ($checks as [$label, $value, $status]): ?>
<tr>
  <td><?= $dots[$status] ?? '⚪' ?> <?= htmlspecialchars($label) ?></td>
  <td class="<?= $status ?>"><?= htmlspecialchars((string)$value) ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php
$fails = array_filter($checks, fn($c) => $c[2] === 'fail');
if ($fails):
?>
<div class="fix">
<h3>⚠ Issues detected — paste this URL in chat for instant fix:</h3>
<a href="<?= $_SERVER['HTTP_HOST'] ?? 'your-app' ?>/status.php"><?= ($_SERVER['HTTP_HOST'] ?? 'your-app') ?>/status.php</a>
<p style="color:#94a3b8;margin:.5rem 0 0">Screenshot this page and send to Claude.</p>
</div>
<?php else: ?>
<div class="fix" style="background:#052e16">
<h3 style="color:#4ade80">✅ All checks passed — login at /login with admin / Admin@123</h3>
</div>
<?php endif; ?>
</body></html>
