<?php $pageTitle = 'Reset Password'; ob_start(); ?>
<?php include ROOT_PATH . '/resources/views/layouts/auth.php'; ?>

<!-- Reset Password Form -->
<div class="auth-card">
  <div class="text-center mb-4">
    <div class="auth-logo">HR<span>MS</span></div>
    <h5 class="fw-bold mt-3 mb-1">Reset Your Password</h5>
    <p class="text-muted small">Enter your new password below.</p>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger small py-2"><?= implode('<br>', array_map('e', $errors)) ?></div>
  <?php endif; ?>

  <form method="POST" action="/auth/reset-password">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
    <input type="hidden" name="email" value="<?= e($email ?? '') ?>">

    <div class="mb-3">
      <label class="form-label small fw-semibold">New Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
        <input type="password" name="password" id="newPassword" class="form-control" required minlength="8" placeholder="Min 8 characters">
        <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('newPassword', this)"><i class="fas fa-eye"></i></button>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label small fw-semibold">Confirm Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
        <input type="password" name="password_confirmation" id="confirmPassword" class="form-control" required placeholder="Repeat password">
        <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('confirmPassword', this)"><i class="fas fa-eye"></i></button>
      </div>
    </div>

    <!-- Password Strength -->
    <div class="mb-3">
      <div class="d-flex justify-content-between small mb-1"><span>Password Strength</span><span id="strengthLabel" class="fw-semibold"></span></div>
      <div class="progress" style="height:5px"><div id="strengthBar" class="progress-bar" style="width:0%"></div></div>
    </div>

    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-key me-1"></i>Set New Password</button>
  </form>

  <div class="text-center mt-3">
    <a href="/login" class="small text-muted"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
  </div>
</div>

<script>
document.getElementById('newPassword').addEventListener('input', function() {
  const v = this.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const labels = ['','Weak','Fair','Good','Strong'];
  const colors = ['','bg-danger','bg-warning','bg-info','bg-success'];
  document.getElementById('strengthBar').style.width = (score * 25) + '%';
  document.getElementById('strengthBar').className = 'progress-bar ' + (colors[score] || '');
  document.getElementById('strengthLabel').textContent = labels[score] || '';
  document.getElementById('strengthLabel').className = 'fw-semibold text-' + (colors[score]?.replace('bg-','') || 'muted');
});
function togglePwd(id, btn) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
  btn.querySelector('i').className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
