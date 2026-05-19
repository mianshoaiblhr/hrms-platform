<?php
/**
 * Global Helper Functions
 */

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false) return $default;
        switch (strtolower($val)) {
            case 'true':  return true;
            case 'false': return false;
            case 'null':  return null;
        }
        return $val;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        static $configs = [];
        $parts = explode('.', $key, 2);
        $file  = $parts[0];
        if (!isset($configs[$file])) {
            $path = CONFIG_PATH . DS . $file . '.php';
            $configs[$file] = file_exists($path) ? require $path : [];
        }
        if (!isset($parts[1])) return $configs[$file];
        $keys = explode('.', $parts[1]);
        $val  = $configs[$file];
        foreach ($keys as $k) {
            if (!is_array($val) || !array_key_exists($k, $val)) return $default;
            $val = $val[$k];
        }
        return $val;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $code = 302): void {
        header("Location: $url", true, $code);
        exit;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('sanitize')) {
    function sanitize($value): string {
        if (is_array($value)) return '';
        return htmlspecialchars(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('e')) {
    function e($value): string {
        return sanitize($value);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return \App\Core\Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('auth')) {
    function auth(): \App\Core\Auth {
        return \App\Core\Auth::getInstance();
    }
}

if (!function_exists('authUser')) {
    function authUser(): ?array {
        return \App\Core\Auth::getInstance()->user();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool {
        return \App\Core\Auth::getInstance()->can($permission);
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, string $symbol = 'PKR'): string {
        return $symbol . ' ' . number_format((float)$amount, 2);
    }
}

if (!function_exists('formatDate')) {
    function formatDate(?string $date, string $format = 'd M Y'): string {
        if (empty($date) || $date === '0000-00-00') return '-';
        try {
            return (new DateTime($date))->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }
}

if (!function_exists('timeSince')) {
    function timeSince(string $datetime): string {
        $time = time() - strtotime($datetime);
        if ($time < 60) return $time . 's ago';
        if ($time < 3600) return floor($time/60) . 'm ago';
        if ($time < 86400) return floor($time/3600) . 'h ago';
        if ($time < 604800) return floor($time/86400) . 'd ago';
        return formatDate($datetime);
    }
}

if (!function_exists('statusBadge')) {
    function statusBadge(string $status): string {
        $classes = [
            'active'      => 'badge-active',
            'inactive'    => 'badge-inactive',
            'terminated'  => 'badge-terminated',
            'pending'     => 'badge-pending',
            'approved'    => 'badge-approved',
            'rejected'    => 'badge-rejected',
            'processed'   => 'badge-processed',
            'disbursed'   => 'badge-disbursed',
            'draft'       => 'badge-draft',
            'on_leave'    => 'badge-pending',
        ];
        $cls = $classes[strtolower($status)] ?? 'badge-secondary';
        return '<span class="badge ' . $cls . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }
}

if (!function_exists('generatePassword')) {
    function generatePassword(int $length = 12): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, 3)), 0, $length);
    }
}

if (!function_exists('generateToken')) {
    function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('safeFilename')) {
    function safeFilename(string $name): string {
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        return substr($name, 0, 100);
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize(int $bytes): string {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes/1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes/1048576, 1) . ' MB';
        return round($bytes/1073741824, 1) . ' GB';
    }
}

if (!function_exists('paginator')) {
    function paginator(int $total, int $perPage, int $current, string $url): string {
        $pages = (int)ceil($total / $perPage);
        if ($pages <= 1) return '';
        $sep = strpos($url, '?') !== false ? '&' : '?';
        $html = '<nav><ul class="pagination pagination-sm mb-0">';
        $html .= '<li class="page-item' . ($current <= 1 ? ' disabled' : '') . '">'
            . '<a class="page-link" href="' . $url . $sep . 'page=' . ($current - 1) . '">&laquo;</a></li>';
        $start = max(1, $current - 2);
        $end   = min($pages, $current + 2);
        if ($start > 1) $html .= '<li class="page-item"><a class="page-link" href="' . $url . $sep . 'page=1">1</a></li>'
            . ($start > 2 ? '<li class="page-item disabled"><a class="page-link">…</a></li>' : '');
        for ($i = $start; $i <= $end; $i++) {
            $html .= '<li class="page-item' . ($i === $current ? ' active' : '') . '">'
                . '<a class="page-link" href="' . $url . $sep . 'page=' . $i . '">' . $i . '</a></li>';
        }
        if ($end < $pages) {
            $html .= ($end < $pages - 1 ? '<li class="page-item disabled"><a class="page-link">…</a></li>' : '')
                . '<li class="page-item"><a class="page-link" href="' . $url . $sep . 'page=' . $pages . '">' . $pages . '</a></li>';
        }
        $html .= '<li class="page-item' . ($current >= $pages ? ' disabled' : '') . '">'
            . '<a class="page-link" href="' . $url . $sep . 'page=' . ($current + 1) . '">&raquo;</a></li>';
        $html .= '</ul></nav>';
        return $html;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars): void {
        foreach ($vars as $var) {
            echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:15px;margin:10px;border-radius:5px;">';
            echo htmlspecialchars(print_r($var, true));
            echo '</pre>';
        }
        die();
    }
}
