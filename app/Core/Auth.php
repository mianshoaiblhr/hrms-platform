<?php
// ============================================================
// app/Core/Auth.php - Authentication & Session Management
// ============================================================

namespace App\Core;

class Auth
{
    private static ?Auth    $instance    = null;
    private static ?array   $currentUser = null;
    private Database $db;

    private int $maxAttempts;
    private int $lockoutDuration;
    private int $sessionLifetime;
    private int $bcryptRounds;

    public function __construct()
    {
        $this->db              = Database::getInstance();
        $this->maxAttempts     = (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: 5);
        $this->lockoutDuration = (int)(getenv('LOCKOUT_DURATION') ?: 900);
        $this->sessionLifetime = (int)(getenv('SESSION_LIFETIME') ?: 120);
        $this->bcryptRounds    = (int)(getenv('BCRYPT_ROUNDS') ?: 12);
    }

    /** Singleton accessor used by helpers and views */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // --------------------------------------------------------
    // LOGIN
    // --------------------------------------------------------

    public function attempt(string $username, string $password, bool $remember = false): array
    {
        $ip        = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check brute force lockout
        if ($this->isIPLocked($ip)) {
            $this->logLogin(null, $username, $ip, $userAgent, 'blocked', 'IP temporarily blocked');
            return ['success' => false, 'message' => 'Too many failed attempts. Please try again after 15 minutes.'];
        }

        // Find user
        $user = $this->db->fetchOne(
            "SELECT u.*, r.slug AS role_slug, r.hierarchy_level, r.name AS role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE (u.username = ? OR u.email = ?) 
               AND u.deleted_at IS NULL",
            [$username, $username]
        );

        if (!$user) {
            $this->recordFailedAttempt($ip);
            $this->logLogin(null, $username, $ip, $userAgent, 'failed', 'User not found');
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Check account active
        if (!$user['is_active']) {
            $this->logLogin($user['id'], $username, $ip, $userAgent, 'failed', 'Account disabled');
            return ['success' => false, 'message' => 'Your account has been disabled. Contact administrator.'];
        }

        // Check individual lockout
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = round((strtotime($user['locked_until']) - time()) / 60);
            $this->logLogin($user['id'], $username, $ip, $userAgent, 'blocked', 'Account locked');
            return ['success' => false, 'message' => "Account locked. Try again in {$remaining} minutes."];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->incrementLoginAttempts($user['id'], $ip);
            $this->logLogin($user['id'], $username, $ip, $userAgent, 'failed', 'Wrong password');
            $remaining = $this->maxAttempts - ($user['login_attempts'] + 1);
            return [
                'success' => false,
                'message' => "Invalid credentials. {$remaining} attempts remaining."
            ];
        }

        // Check allowed IPs
        if ($user['allowed_ips']) {
            $allowedIPs = json_decode($user['allowed_ips'], true) ?: [];
            if (!empty($allowedIPs) && !in_array($ip, $allowedIPs)) {
                $this->logLogin($user['id'], $username, $ip, $userAgent, 'blocked', 'IP not allowed');
                return ['success' => false, 'message' => 'Access from your IP is not allowed.'];
            }
        }

        // Check 2FA
        if ($user['two_factor_enabled']) {
            Session::set('pending_2fa_user_id', $user['id']);
            return ['success' => false, 'requires_2fa' => true, 'message' => 'Please enter your 2FA code.'];
        }

        // SUCCESS - Create session
        $this->createSession($user, $remember);
        $this->resetLoginAttempts($user['id']);
        $this->logLogin($user['id'], $username, $ip, $userAgent, 'success');

        // Check forced password change
        if ($user['force_password_change']) {
            return ['success' => true, 'force_password_change' => true];
        }

        // Check password expiry
        if ($user['password_expires_at'] && strtotime($user['password_expires_at']) < time()) {
            return ['success' => true, 'password_expired' => true];
        }

        return ['success' => true];
    }

    // --------------------------------------------------------
    // SESSION CREATION
    // --------------------------------------------------------

    private function createSession(array $user, bool $remember = false): void
    {
        // Regenerate session ID to prevent fixation attacks
        session_regenerate_id(true);

        $sessionId = session_id();

        // Store session data
        Session::set('user_id',       $user['id']);
        Session::set('username',      $user['username'] ?? '');
        Session::set('full_name',     $user['full_name'] ?? $user['name'] ?? $user['username'] ?? '');
        Session::set('email',         $user['email'] ?? '');
        Session::set('role_id',       $user['role_id'] ?? null);
        Session::set('role_slug',     $user['role_slug'] ?? '');
        Session::set('role_name',     $user['role_name'] ?? '');
        Session::set('department_id', $user['department_id'] ?? null);
        Session::set('avatar',        $user['avatar'] ?? null);
        Session::set('logged_in',     true);
        Session::set('login_time',    time());
        Session::set('last_activity', time());
        Session::set('ip_address',    $this->getClientIP());
        Session::set('session_id',    $sessionId);

        // Update DB
        $this->db->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->getClientIP(),
            'login_attempts'=> 0,
            'locked_until'  => null,
            'is_online'     => 1,
            'last_seen_at'  => date('Y-m-d H:i:s'),
            'session_id'    => $sessionId,
        ], 'id = ?', [$user['id']]);

        // Remember me cookie
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $this->db->update('users', ['remember_token' => hash('sha256', $token)], 'id = ?', [$user['id']]);
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
        }
    }

    // --------------------------------------------------------
    // LOGOUT
    // --------------------------------------------------------

    public function logout(): void
    {
        $userId = Session::get('user_id');

        if ($userId) {
            $this->db->update('users', [
                'is_online'  => 0,
                'session_id' => null,
            ], 'id = ?', [$userId]);

            AuditLogger::log('logout', 'auth', $userId, null, 'User logged out');
        }

        Session::destroy();

        // Remove remember-me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    }

    // --------------------------------------------------------
    // SESSION VALIDATION
    // --------------------------------------------------------

    public function check(): bool
    {
        if (!Session::has('logged_in') || !Session::get('logged_in')) {
            return $this->checkRememberMe();
        }

        // Session timeout check
        $lastActivity = Session::get('last_activity');
        $lifetime     = $this->sessionLifetime * 60;

        if ((time() - $lastActivity) > $lifetime) {
            $this->logout();
            return false;
        }

        // Update last activity
        Session::set('last_activity', time());
        $this->db->update('users', ['last_seen_at' => date('Y-m-d H:i:s')], 'id = ?', [Session::get('user_id')]);

        return true;
    }

    private function checkRememberMe(): bool
    {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }

        $token = $_COOKIE['remember_token'];
        $hash  = hash('sha256', $token);

        $user = $this->db->fetchOne(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name 
             FROM users u JOIN roles r ON u.role_id = r.id 
             WHERE u.remember_token = ? AND u.is_active = 1 AND u.deleted_at IS NULL",
            [$hash]
        );

        if ($user) {
            $this->createSession($user, true);
            return true;
        }

        return false;
    }

    // --------------------------------------------------------
    // CURRENT USER
    // --------------------------------------------------------

    public function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $userId = Session::get('user_id');
        if (!$userId) return null;

        self::$currentUser = $this->db->fetchOne(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.hierarchy_level,
                    d.name AS department_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.id = ? AND u.is_active = 1 AND u.deleted_at IS NULL",
            [$userId]
        );

        return self::$currentUser;
    }

    public function id(): ?int
    {
        return Session::get('user_id');
    }

    public function roleSlug(): ?string
    {
        return Session::get('role_slug');
    }

    public function isSuperAdmin(): bool
    {
        if ($this->roleSlug() === 'super_admin') return true;
        $u = $this->user();
        return !empty($u['is_super_admin']);
    }

    // --------------------------------------------------------
    // PERMISSIONS
    // --------------------------------------------------------

    public function can(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin()) return true;

        $userId = $this->id();
        if (!$userId) return false;

        $roleId = Session::get('role_id');

        $result = $this->db->fetchOne(
            "SELECT rp.id FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role_id = ? AND p.slug = ?",
            [$roleId, $permissionSlug]
        );

        return $result !== null;
    }

    public function canAny(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->can($perm)) return true;
        }
        return false;
    }

    public function canAll(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (!$this->can($perm)) return false;
        }
        return true;
    }

    // --------------------------------------------------------
    // PASSWORD MANAGEMENT
    // --------------------------------------------------------

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->bcryptRounds]);
    }

    public function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        return $errors;
    }

    public function generatePasswordResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600);

        $this->db->update('users', [
            'otp_code'       => hash('sha256', $token),
            'otp_expires_at' => $expiry,
        ], 'id = ?', [$userId]);

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $hash = hash('sha256', $token);
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE otp_code = ? AND otp_expires_at > NOW()",
            [$hash]
        );

        if (!$user) return false;

        $this->db->update('users', [
            'password'            => $this->hashPassword($newPassword),
            'otp_code'            => null,
            'otp_expires_at'      => null,
            'password_changed_at' => date('Y-m-d H:i:s'),
            'password_expires_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
            'force_password_change'=> 0,
        ], 'id = ?', [$user['id']]);

        return true;
    }

    // --------------------------------------------------------
    // BRUTE FORCE PROTECTION
    // --------------------------------------------------------

    private function isIPLocked(string $ip): bool
    {
        $attempts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM login_logs 
             WHERE ip_address = ? AND status = 'failed' 
               AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$ip]
        );
        return (int)$attempts >= ($this->maxAttempts * 3);
    }

    private function recordFailedAttempt(string $ip): void
    {
        // Logged via logLogin()
    }

    private function incrementLoginAttempts(int $userId, string $ip): void
    {
        $user = $this->db->fetchOne("SELECT login_attempts FROM users WHERE id = ?", [$userId]);
        $attempts = ($user['login_attempts'] ?? 0) + 1;

        $update = ['login_attempts' => $attempts];
        if ($attempts >= $this->maxAttempts) {
            $update['locked_until'] = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
        }

        $this->db->update('users', $update, 'id = ?', [$userId]);
    }

    private function resetLoginAttempts(int $userId): void
    {
        $this->db->update('users', ['login_attempts' => 0, 'locked_until' => null], 'id = ?', [$userId]);
    }

    // --------------------------------------------------------
    // LOGGING
    // --------------------------------------------------------

    private function logLogin(?int $userId, string $username, string $ip, string $ua, string $status, string $reason = ''): void
    {
        $this->db->insert('login_logs', [
            'user_id'          => $userId,
            'username_attempt' => $username,
            'ip_address'       => $ip,
            'user_agent'       => substr($ua, 0, 500),
            'status'           => $status,
            'failure_reason'   => $reason ?: null,
            'session_id'       => session_id() ?: null,
        ]);
    }

    // --------------------------------------------------------
    // HELPERS
    // --------------------------------------------------------

    private function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
