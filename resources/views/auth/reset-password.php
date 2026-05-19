<?php $pageTitle = 'Reset Password'; ?>

<div class="form-header">
  <h2>Set new password</h2>
  <p>Choose a strong password for your account</p>
</div>

<form method="POST" action="/reset-password" id="resetForm">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
  <input type="hidden" name="email" value="<?= e($email ?? '') ?>">

  <div class="input-group-orbit">
    <label>New Password</label>
    <div class="pw-field">
      <input type="password" name="password" id="newpw" class="orbit-input"
             placeholder="Minimum 8 characters" required minlength="8">
      <button type="button" class="pw-toggle" onclick="tpw('newpw',this)"><i class="fas fa-eye"></i></button>
    </div>
  </div>

  <div class="input-group-orbit">
    <label>Confirm Password</label>
    <div class="pw-field">
      <input type="password" name="password_confirmation" id="confpw" class="orbit-input"
             placeholder="Repeat password" required>
      <button type="button" class="pw-toggle" onclick="tpw('confpw',this)"><i class="fas fa-eye"></i></button>
    </div>
  </div>

  <button type="submit" class="orbit-btn">
    <i class="fas fa-key"></i> Set New Password
  </button>
</form>

<script>
function tpw(id,btn){
  const f=document.getElementById(id);
  f.type=f.type==='password'?'text':'password';
  btn.querySelector('i').className=f.type==='password'?'fas fa-eye':'fas fa-eye-slash';
}
</script>
