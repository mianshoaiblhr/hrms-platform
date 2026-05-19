<?php
/**
 * HRMS Enterprise Platform
 * Application Entry Point
 *
 * @version 1.0.0
 * @author  Enterprise HRMS
 */

declare(strict_types=1);

// ==============================================================
// ENVIRONMENT & PATH SETUP
// ==============================================================
define('HRMS_START', microtime(true));
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . DS . 'app');
define('CONFIG_PATH', ROOT_PATH . DS . 'config');
define('RESOURCE_PATH', ROOT_PATH . DS . 'resources');
define('STORAGE_PATH', ROOT_PATH . DS . 'storage');
define('PUBLIC_PATH', __DIR__);
define('UPLOAD_PATH', STORAGE_PATH . DS . 'uploads');

// ==============================================================
// ERROR HANDLING
// ==============================================================
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . DS . 'logs' . DS . 'php_error.log');

// ==============================================================
// SECURITY HEADERS (fallback if Apache mod_headers unavailable)
// ==============================================================
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header_remove('X-Powered-By');
}

// ==============================================================
// LOAD ENVIRONMENT VARIABLES
// ==============================================================
$envFile = ROOT_PATH . DS . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!empty($key) && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// ==============================================================
// AUTOLOADER
// ==============================================================
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\Controllers\\' => APP_PATH . DS . 'Controllers',
        'App\\Models\\'      => APP_PATH . DS . 'Models',
        'App\\Services\\'    => APP_PATH . DS . 'Services',
        'App\\Middleware\\'  => APP_PATH . DS . 'Middleware',
        'App\\Core\\'        => APP_PATH . DS . 'Core',
        'App\\Helpers\\'     => APP_PATH . DS . 'Helpers',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) continue;

        // 1) Try PSR-4 exact match first (ClassName.php)
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . DS . str_replace('\\', DS, $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }

        // 2) Fallback: scan every .php in the directory and require_once
        //    until the class is defined (handles multi-class files)
        foreach (glob($baseDir . DS . '*.php') ?: [] as $phpFile) {
            if (!class_exists($class, false) && !interface_exists($class, false)) {
                require_once $phpFile;
            }
            if (class_exists($class, false) || interface_exists($class, false)) {
                return;
            }
        }
        return;
    }
});

// ==============================================================
// LOAD HELPERS
// ==============================================================
$helperFiles = [
    APP_PATH . DS . 'Helpers' . DS . 'functions.php',
    APP_PATH . DS . 'Helpers' . DS . 'view.php',
];
foreach ($helperFiles as $helper) {
    if (file_exists($helper)) require_once $helper;
}

// ==============================================================
// BOOTSTRAP APPLICATION
// ==============================================================
use App\Core\Session;
use App\Core\Router;

// Start secure session
Session::start();

// Check maintenance mode
if (file_exists(STORAGE_PATH . DS . 'maintenance.flag')) {
    if (!isset($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] !== ($_ENV['ADMIN_IP'] ?? '')) {
        http_response_code(503);
        if (file_exists(RESOURCE_PATH . DS . 'views' . DS . 'errors' . DS . '503.php')) {
            include RESOURCE_PATH . DS . 'views' . DS . 'errors' . DS . '503.php';
        } else {
            echo '<h1>503 - Service Under Maintenance</h1><p>We will be back shortly.</p>';
        }
        exit;
    }
}

// ==============================================================
// LOAD AND DISPATCH ROUTES
// ==============================================================
$router = new Router();
require ROOT_PATH . DS . 'routes' . DS . 'web.php';

try {
    $router->dispatch();
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<html><body style="font-family:monospace;padding:2rem;background:#1a1a2e;color:#eee">';
    echo '<h2 style="color:#e94560">&#9888; HRMS Error (debug mode)</h2>';
    echo '<p style="color:#f5a623;font-size:1.1em">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p style="color:#aaa">' . htmlspecialchars($e->getFile()) . ' : line ' . $e->getLine() . '</p>';
    echo '<pre style="background:#0f0f23;padding:1rem;overflow:auto;font-size:0.8em;color:#7ec8e3">'
        . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '<p style="color:#555;font-size:0.75em">Remove debug output once issue is resolved.</p>';
    echo '</body></html>';
}
