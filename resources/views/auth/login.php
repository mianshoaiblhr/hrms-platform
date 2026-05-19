<?php
$pageTitle = 'Login';
$subtitle  = 'Sign in to your account';
ob_start();
?>
<form method="POST" action="/login" id="loginForm">
  <?= csrf_field() ?>

  <div class="form-floating mb-3">
    <input type="text" class="form-control" id="username" name="username"
           placeholder="Username" value="<?= e($_POST['username'] ?? '') ?>"
           autocomplete="username" required autofocus>
    <label for="username"><i class="fas fa-user me-1"></i>Username or Email</label>
  </div>

  <div class="form-floating mb-3 position-relative">
    <input type="password" class="form-control" id="password" name="password"
           placeholder="Password" autocomplete="current-password" required>
    <label for="password"><i class="fas fa-lock me-1"></i>Password</label>
    <button type="button" class="btn-toggle-pw" onclick="togglePassword()" title="Show/Hide">
      <i class="fas fa-eye" id="pwIcon"></i>
    </button>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
      <label class="form-check-label small" for="remember">Remember me</label>
    </div>
    <a href="/forgot-password" class="text-primary text-decoration-none small fw-semibold">Forgot Password?</a>
  </div>

  <button type="submit" class="btn btn-primary btn-auth" id="loginBtn">
    <i class="fas fa-sign-in-alt me-2"></i>Sign In
  </button>
</form>

<style>
.btn-toggle-pw{position:absolute;right:15px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;z-index:10;padding:4px}
</style>
<script>
function togglePassword() {
  const inp = document.getElementById('password');
  const ico = document.getElementById('pwIcon');
  if (inp.type === 'password') { inp.type='text'; ico.className='fas fa-eye-slash'; }
  else { inp.type='password'; ico.className='fas fa-eye'; }
}
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
