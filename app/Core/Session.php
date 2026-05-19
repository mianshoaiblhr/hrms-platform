<?php
// ============================================================
// app/Core/Session.php - Secure Session Management
// ============================================================

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // Secure session configuration
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure',   '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime',  '86400');
        ini_set('session.use_trans_sid',   '0');

        session_name('HRMS_SESSION');
        session_start();

        self::$started = true;

        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::set("_flash_{$key}", $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = self::get("_flash_{$key}", $default);
        self::forget("_flash_{$key}");
        return $value;
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        self::$started = false;
    }

    // CSRF Token management
    public static function csrfToken(): string
    {
        if (!self::has('_csrf_token')) {
            self::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('_csrf_token');
    }

    public static function verifyCsrf(string $token): bool
    {
        return hash_equals(self::csrfToken(), $token);
    }

    public static function regenerateCsrf(): void
    {
        self::set('_csrf_token', bin2hex(random_bytes(32)));
    }
}
