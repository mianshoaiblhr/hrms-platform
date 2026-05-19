<?php $pageTitle = 'Sign In'; ?>

<form method="POST" action="/login" id="loginForm">
  <?= csrf_field() ?>

  <div class="input-group-orbit">
    <label for="username">Username or email</label>
    <input
      type="text"
      id="username"
      name="username"
      class="orbit-input"
      placeholder="Enter your username or email"
      value="<?= e($_POST['username'] ?? '') ?>"
      autocomplete="username"
      required
      autofocus
    >
  </div>

  <div class="input-group-orbit">
    <label for="password">Password</label>
    <div class="pw-field">
      <input
        type="password"
        id="password"
        name="password"
        class="orbit-input"
        placeholder="Enter your password"
        autocomplete="current-password"
        required
      >
      <button type="button" class="pw-toggle" onclick="togglePw()" title="Show password">
        <i class="fas fa-eye" id="pw-ico"></i>
      </button>
    </div>
  </div>

  <div class="form-meta">
    <label class="remember-check">
      <input type="checkbox" name="remember" value="1">
      <span>Keep me signed in</span>
    </label>
    <a href="/forgot-password" class="forgot-link">Forgot password?</a>
  </div>

  <button type="submit" class="orbit-btn" id="signinBtn">
    <i class="fas fa-right-to-bracket"></i>
    Sign In
  </button>
</form>

<script>
function togglePw(){
  const f=document.getElementById('password');
  const i=document.getElementById('pw-ico');
  f.type=f.type==='password'?'text':'password';
  i.className=f.type==='password'?'fas fa-eye':'fas fa-eye-slash';
}
document.getElementById('loginForm').addEventListener('submit',function(){
  const b=document.getElementById('signinBtn');
  b.disabled=true;
  b.innerHTML='<i class="fas fa-spinner fa-spin"></i> Signing in...';
});
</script>
