<?php
// ============================================================
// app/Middleware/AuthMiddleware.php - Authentication Guard
// ============================================================

namespace App\Middleware;

use App\Core\Session;
use App\Core\Auth;

class AuthMiddleware
{
    public function handle(): bool
    {
        Session::start();
        $auth = new Auth();

        if (!$auth->check()) {
            Session::flash('alert_type', 'warning');
            Session::flash('alert_message', 'Please log in to access this page.');
            Session::set('redirect_after_login', $_SERVER['REQUEST_URI']);

            header('Location: /login');
            exit;
        }

        // Security headers on every authenticated request
        $this->setSecurityHeaders();

        return true;
    }

    private function setSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:;");
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    }
}

// ============================================================
// app/Middleware/GuestMiddleware.php - Only for non-logged-in
// ============================================================

namespace App\Middleware;

use App\Core\Session;
use App\Core\Auth;

class GuestMiddleware
{
    public function handle(): bool
    {
        Session::start();
        $auth = new Auth();

        if ($auth->check()) {
            header('Location: /dashboard');
            exit;
        }

        return true;
    }
}

// ============================================================
// app/Middleware/RBACMiddleware.php - Permission Enforcement
// ============================================================

namespace App\Middleware;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Session;

class RBACMiddleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(): bool
    {
        $auth = new Auth();

        if (!$auth->can($this->permission)) {
            AuditLogger::log(
                'unauthorized_access',
                'security',
                null,
                null,
                "Blocked access to: {$this->permission}",
                [],
                [],
                'error'
            );

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied.', 'code' => 403]);
                exit;
            }

            Session::flash('alert_type', 'danger');
            Session::flash('alert_message', 'You do not have permission to access this area.');
            header('Location: /dashboard');
            exit;
        }

        return true;
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

// ============================================================
// app/Middleware/CsrfMiddleware.php - CSRF Protection
// ============================================================

namespace App\Middleware;

use App\Core\Session;
use App\Core\AuditLogger;

class CsrfMiddleware
{
    private array $excludedRoutes = ['/api/webhook'];

    public function handle(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Only verify POST/PUT/DELETE
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }

        // Check excluded routes
        foreach ($this->excludedRoutes as $route) {
            if (str_starts_with($uri, $route)) {
                return true;
            }
        }

        $token = $_POST['_csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if (!Session::verifyCsrf($token)) {
            AuditLogger::log('csrf_violation', 'security', null, null, "CSRF violation on: {$uri}", [], [], 'error');

            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CSRF validation failed.']);
            } else {
                include dirname(__DIR__, 2) . '/resources/views/errors/403.php';
            }
            exit;
        }

        // Regenerate CSRF token after use
        Session::regenerateCsrf();

        return true;
    }
}

// ============================================================
// app/Middleware/MaintenanceMiddleware.php
// ============================================================

namespace App\Middleware;

use App\Core\Database;

class MaintenanceMiddleware
{
    public function handle(): bool
    {
        try {
            $db = Database::getInstance();
            $mode = $db->fetchColumn(
                "SELECT config_value FROM system_configs WHERE config_key = 'maintenance_mode'"
            );

            if ($mode == '1') {
                $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                if ($uri !== '/maintenance' && !str_starts_with($uri, '/admin/maintenance')) {
                    header('Location: /maintenance');
                    exit;
                }
            }
        } catch (\Throwable $e) {
            // If DB not accessible, allow through
        }

        return true;
    }
}
