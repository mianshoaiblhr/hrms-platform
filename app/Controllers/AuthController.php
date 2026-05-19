<?php
// ============================================================
// app/Controllers/AuthController.php
// ============================================================

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Core\AuditLogger;

class AuthController extends Controller
{
    // --------------------------------------------------------
    // SHOW LOGIN FORM
    // --------------------------------------------------------
    public function loginForm(): void
    {
        $this->view('auth.login', [
            'title'      => 'Login | HRMS Platform',
            'csrf_token' => Session::csrfToken(),
        ], 'auth');
    }

    // --------------------------------------------------------
    // PROCESS LOGIN
    // --------------------------------------------------------
    public function login(): void
    {
        $this->verifyCsrf();

        $username = $this->input('username', '');
        $password = $this->input('password', '');
        $remember = (bool)$this->input('remember_me', false);

        if (empty($username) || empty($password)) {
            $this->flash('danger', 'Username and password are required.');
            $this->redirect('/login');
            return;
        }

        $result = $this->auth->attempt($username, $password, $remember);

        if ($result['success']) {
            if (!empty($result['force_password_change'])) {
                $this->redirect('/change-password?forced=1');
                return;
            }
            if (!empty($result['password_expired'])) {
                $this->flash('warning', 'Your password has expired. Please set a new password.');
                $this->redirect('/change-password?expired=1');
                return;
            }

            $redirectTo = Session::get('redirect_after_login') ?? '/dashboard';
            Session::forget('redirect_after_login');
            $this->redirect($redirectTo);
            return;
        }

        if (!empty($result['requires_2fa'])) {
            $this->redirect('/2fa');
            return;
        }

        $this->flash('danger', $result['message'] ?? 'Invalid credentials.');
        $this->redirect('/login');
    }

    // --------------------------------------------------------
    // LOGOUT
    // --------------------------------------------------------
    public function logout(): void
    {
        $this->auth->logout();
        $this->flash('success', 'You have been securely logged out.');
        $this->redirect('/login');
    }

    // --------------------------------------------------------
    // FORGOT PASSWORD
    // --------------------------------------------------------
    public function forgotPasswordForm(): void
    {
        $this->view('auth.forgot-password', [
            'title'      => 'Forgot Password | HRMS',
            'csrf_token' => Session::csrfToken(),
        ], 'auth');
    }

    public function forgotPassword(): void
    {
        $this->verifyCsrf();

        $email = filter_var($this->input('email', ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $this->flash('danger', 'Please enter a valid email address.');
            $this->redirect('/forgot-password');
            return;
        }

        $user = $this->db->fetchOne(
            "SELECT id, full_name, email FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL",
            [$email]
        );

        // Always show success to prevent user enumeration
        $this->flash('success', 'If this email exists, you will receive a password reset link shortly.');

        if ($user) {
            $token = $this->auth->generatePasswordResetToken($user['id']);
            $resetUrl = getenv('APP_URL') . '/reset-password?token=' . $token;

            // Send email (implement EmailService)
            // EmailService::send($user['email'], 'Password Reset', 'emails.password-reset', ['url' => $resetUrl, 'name' => $user['full_name']]);

            AuditLogger::log('password_reset_requested', 'auth', $user['id'], 'user', 'Password reset requested');
        }

        $this->redirect('/forgot-password');
    }

    // --------------------------------------------------------
    // RESET PASSWORD
    // --------------------------------------------------------
    public function resetPasswordForm(): void
    {
        $token = $this->input('token', '');
        if (empty($token)) {
            $this->redirect('/login');
            return;
        }

        $this->view('auth.reset-password', [
            'title'      => 'Reset Password | HRMS',
            'token'      => $token,
            'csrf_token' => Session::csrfToken(),
        ], 'auth');
    }

    public function resetPassword(): void
    {
        $this->verifyCsrf();

        $token    = $this->input('token', '');
        $password = $this->input('password', '');
        $confirm  = $this->input('password_confirm', '');

        if ($password !== $confirm) {
            $this->flash('danger', 'Passwords do not match.');
            $this->redirect("/reset-password?token={$token}");
            return;
        }

        $passwordErrors = $this->auth->validatePassword($password);
        if (!empty($passwordErrors)) {
            $this->flash('danger', implode(' ', $passwordErrors));
            $this->redirect("/reset-password?token={$token}");
            return;
        }

        if ($this->auth->resetPassword($token, $password)) {
            $this->flash('success', 'Password reset successfully. Please log in.');
            $this->redirect('/login');
        } else {
            $this->flash('danger', 'Invalid or expired reset token. Please request a new one.');
            $this->redirect('/forgot-password');
        }
    }

    // --------------------------------------------------------
    // CHANGE PASSWORD
    // --------------------------------------------------------
    public function changePasswordForm(): void
    {
        if (!$this->auth->check()) {
            $this->redirect('/login');
            return;
        }

        $this->view('auth.change-password', [
            'title'      => 'Change Password | HRMS',
            'forced'     => (bool)$this->input('forced', false),
            'expired'    => (bool)$this->input('expired', false),
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    public function changePassword(): void
    {
        $this->verifyCsrf();

        if (!$this->auth->check()) {
            $this->redirect('/login');
            return;
        }

        $userId      = $this->auth->id();
        $current     = $this->input('current_password', '');
        $newPassword = $this->input('new_password', '');
        $confirm     = $this->input('confirm_password', '');

        $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);

        if (!password_verify($current, $user['password'])) {
            $this->flash('danger', 'Current password is incorrect.');
            $this->redirect('/change-password');
            return;
        }

        if ($newPassword !== $confirm) {
            $this->flash('danger', 'New passwords do not match.');
            $this->redirect('/change-password');
            return;
        }

        $errors = $this->auth->validatePassword($newPassword);
        if (!empty($errors)) {
            $this->flash('danger', implode(' ', $errors));
            $this->redirect('/change-password');
            return;
        }

        $this->db->update('users', [
            'password'              => $this->auth->hashPassword($newPassword),
            'password_changed_at'   => date('Y-m-d H:i:s'),
            'password_expires_at'   => date('Y-m-d H:i:s', strtotime('+90 days')),
            'force_password_change' => 0,
        ], 'id = ?', [$userId]);

        AuditLogger::log('password_changed', 'auth', $userId, 'user', 'Password changed by user');

        $this->flash('success', 'Password changed successfully.');
        $this->redirect('/dashboard');
    }
}
