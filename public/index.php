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
        'App\\Controllers\\' => APP_PATH . DS . 'Controllers' . DS,
        'App\\Models\\'      => APP_PATH . DS . 'Models' . DS,
        'App\\Services\\'    => APP_PATH . DS . 'Services' . DS,
        'App\\Middleware\\'  => APP_PATH . DS . 'Middleware' . DS,
        'App\\Core\\'        => APP_PATH . DS . 'Core' . DS,
        'App\\Helpers\\'     => APP_PATH . DS . 'Helpers' . DS,
    ];

    foreach ($prefixes as $prefix => $base) {
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) continue;
        $relative = substr($class, strlen($prefix));
        $file = $base . str_replace('\\', DS, $relative) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
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
    // Log the error
    $logFile = STORAGE_PATH . DS . 'logs' . DS . 'app_error.log';
    $logMsg = date('[Y-m-d H:i:s]') . ' ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
        . $e->getTraceAsString() . PHP_EOL . PHP_EOL;
    @file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);

    // Show appropriate error page
    http_response_code(500);
    $errView = RESOURCE_PATH . DS . 'views' . DS . 'errors' . DS . '500.php';
    if (file_exists($errView)) {
        include $errView;
    } else {
        echo '<h1>500 - Internal Server Error</h1>';
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
    }
}
