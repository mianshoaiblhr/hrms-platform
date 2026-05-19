<?php
// ============================================================
// app/Core/Controller.php - Base Controller
// ============================================================

namespace App\Core;

class Controller
{
    protected Database $db;
    protected Auth $auth;

    public function __construct()
    {
        $this->db   = Database::getInstance();
        $this->auth = new Auth();
    }

    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $data['auth'] = $this->auth;
        $data['user'] = $this->auth->user();

        // Make data available as variables
        extract($data);

        $viewPath   = dirname(__DIR__, 2) . '/resources/views/' . str_replace('.', '/', $view) . '.php';
        $layoutPath = dirname(__DIR__, 2) . '/resources/views/layouts/' . $layout . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        // Buffer view content
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        // Render in layout
        if ($layout && file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            echo $content;
        }
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }

    protected function back(): void
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    protected function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $errorView = dirname(__DIR__, 2) . "/resources/views/errors/{$code}.php";
        if (file_exists($errorView)) {
            include $errorView;
        } else {
            echo "<h1>Error {$code}</h1><p>{$message}</p>";
        }
        exit;
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($token)) {
            AuditLogger::log('csrf_violation', 'security', null, null, 'CSRF token mismatch');
            $this->abort(403, 'CSRF token validation failed.');
        }
    }

    protected function requirePermission(string $permission): void
    {
        if (!$this->auth->can($permission)) {
            AuditLogger::log('unauthorized_access', 'security', $this->auth->id(), null,
                "Attempted to access: {$permission}");
            $this->abort(403, 'You do not have permission to perform this action.');
        }
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    protected function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value    = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                [$ruleName, $ruleParam] = array_pad(explode(':', $rule), 2, null);

                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                        }
                        break;
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = 'Invalid email address.';
                        }
                        break;
                    case 'min':
                        if ($value !== null && strlen((string)$value) < (int)$ruleParam) {
                            $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$ruleParam} characters.";
                        }
                        break;
                    case 'max':
                        if ($value !== null && strlen((string)$value) > (int)$ruleParam) {
                            $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$ruleParam} characters.";
                        }
                        break;
                    case 'numeric':
                        if ($value !== null && !is_numeric($value)) {
                            $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number.';
                        }
                        break;
                    case 'date':
                        if ($value && !strtotime($value)) {
                            $errors[$field][] = 'Invalid date format.';
                        }
                        break;
                    case 'unique':
                        [$table, $column] = explode(',', $ruleParam ?? '');
                        $exists = $this->db->fetchColumn(
                            "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ? AND deleted_at IS NULL",
                            [$value]
                        );
                        if ($exists > 0) {
                            $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' already exists.';
                        }
                        break;
                    case 'in':
                        $allowed = explode(',', $ruleParam ?? '');
                        if ($value && !in_array($value, $allowed)) {
                            $errors[$field][] = 'Invalid value selected.';
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    protected function paginate(string $sql, array $params = [], int $perPage = 20): array
    {
        $page = max(1, (int)($this->input('page') ?: 1));
        return $this->db->paginate($sql, $params, $page, $perPage);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash('alert_type', $type);
        Session::flash('alert_message', $message);
    }
    protected function getAllInput(): array
    {
        return array_merge($_GET, $_POST);
    }

    protected function generateTempPassword(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
        $pass  = '';
        for ($i = 0; $i < $length; $i++) {
            $pass .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $pass;
    }

    protected function uploadFile(array $file, string $folder = 'uploads'): ?string
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
        $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || $file['size'] > 10485760) return null;
        $dir = (defined('STORAGE_PATH') ? STORAGE_PATH : '/var/www/html/storage')
             . '/uploads/' . $folder;
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $dest = $dir . '/' . uniqid() . '_' . time() . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dest)
            ? $folder . '/' . basename($dest) : null;
    }

    protected function auditLog(string $action, string $table, $recordId = null,
                                array $old = [], array $new = []): void
    {
        try {
            AuditLogger::log($action, $table, (int)$recordId, $table,
                ucfirst($action) . ' on ' . $table, $old, $new);
        } catch (\Throwable $e) { error_log('[Audit] ' . $e->getMessage()); }
    }

}

// ============================================================
// app/Core/AuditLogger.php - Immutable Audit Trail
// ============================================================

namespace App\Core;


class AuditLogger
{
    public static function log(
        string  $action,
        string  $module,
        ?int    $recordId    = null,
        ?string $recordType  = null,
        string  $description = '',
        array   $oldValues   = [],
        array   $newValues   = [],
        string  $status      = 'success'
    ): void {
        try {
            $db      = Database::getInstance();
            $userId  = Session::get('user_id');
            $userName = Session::get('full_name') ?? Session::get('username');

            $db->insert('audit_logs', [
                'user_id'     => $userId,
                'user_name'   => $userName,
                'action'      => $action,
                'module'      => $module,
                'record_id'   => $recordId,
                'record_type' => $recordType,
                'old_values'  => $oldValues ? json_encode($oldValues) : null,
                'new_values'  => $newValues ? json_encode($newValues) : null,
                'description' => $description,
                'ip_address'  => self::getClientIP(),
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'url'         => substr($_SERVER['REQUEST_URI'] ?? '', 0, 1000),
                'method'      => $_SERVER['REQUEST_METHOD'] ?? null,
                'status'      => $status,
            ]);
        } catch (\Throwable $e) {
            // Fail silently — never crash app due to logging
            error_log('AuditLogger failed: ' . $e->getMessage());
        }
    }

    private static function getClientIP(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) {
                return trim(explode(',', $_SERVER[$h])[0]);
            }
        }
        return '0.0.0.0';
    }
}

// Safe DB query helper available to all controllers
trait SafeQuery
{
    protected function safeQuery(callable $fn, mixed $fallback = null): mixed
    {
        try { return $fn(); }
        catch (\Throwable $e) {
            error_log('[ORBIT] Query failed: ' . $e->getMessage());
            return $fallback;
        }
    }
}
