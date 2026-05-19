<?php
$pageTitle = 'Forgot Password';
$subtitle  = 'Reset your password';
ob_start();
?>
<form method="POST" action="/forgot-password">
  <?= csrf_field() ?>
  <p class="text-muted small mb-4">Enter your registered email address and we'll send you a reset link.</p>
  <div class="form-floating mb-4">
    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required autofocus>
    <label for="email"><i class="fas fa-envelope me-1"></i>Email Address</label>
  </div>
  <button type="submit" class="btn btn-primary btn-auth mb-3">
    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
  </button>
  <div class="text-center">
    <a href="/login" class="text-muted small"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
  </div>
</form>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
