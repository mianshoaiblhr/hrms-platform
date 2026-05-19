<?php
$pageTitle = 'Log In';
ob_start();
?>

<form method="POST" action="/login" id="loginForm" style="display:flex;flex-direction:column;gap:14px">
  <?= csrf_field() ?>

  <input
    type="text"
    name="username"
    class="fb-input"
    placeholder="Username or email address"
    value="<?= e($_POST['username'] ?? '') ?>"
    autocomplete="username"
    required
    autofocus
  >

  <div class="pw-wrap">
    <input
      type="password"
      name="password"
      id="fb-pw"
      class="fb-input"
      placeholder="Password"
      autocomplete="current-password"
      required
    >
    <button type="button" class="pw-toggle" onclick="togglePw()" title="Show password">
      <i class="fas fa-eye" id="pw-icon"></i>
    </button>
  </div>

  <button type="submit" class="fb-btn" id="loginBtn">Log in</button>

  <div class="fb-row">
    <label class="fb-check">
      <input type="checkbox" name="remember" value="1">
      Remember me
    </label>
    <a href="/forgot-password" class="fb-link">Forgot password?</a>
  </div>

  <div class="fb-divider"></div>

  <div style="text-align:center">
    <a href="#" style="display:inline-block;padding:0">
      <button type="button" class="fb-btn-new" style="padding:14px 24px;border-radius:6px">
        Create new account
      </button>
    </a>
  </div>
</form>

<script>
function togglePw(){
  const f=document.getElementById('fb-pw');
  const i=document.getElementById('pw-icon');
  f.type=f.type==='password'?'text':'password';
  i.className=f.type==='password'?'fas fa-eye':'fas fa-eye-slash';
}
document.getElementById('loginForm').addEventListener('submit',function(){
  const btn=document.getElementById('loginBtn');
  btn.disabled=true;
  btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i> Logging in...';
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
