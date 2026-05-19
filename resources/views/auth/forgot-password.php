<?php $pageTitle = 'Forgot Password'; ?>

<div class="form-header">
  <h2>Reset password</h2>
  <p>Enter your email and we'll send you a reset link</p>
</div>

<form method="POST" action="/forgot-password" id="forgotForm">
  <?= csrf_field() ?>
  <div class="input-group-orbit">
    <label for="email">Email address</label>
    <input type="email" id="email" name="email" class="orbit-input"
           placeholder="Enter your email" required autofocus>
  </div>
  <button type="submit" class="orbit-btn" id="resetBtn">
    <i class="fas fa-paper-plane"></i> Send Reset Link
  </button>
</form>

<div style="text-align:center;margin-top:24px">
  <a href="/login" style="font-size:.85rem;color:#4f46e5;text-decoration:none">
    <i class="fas fa-arrow-left me-1"></i>Back to sign in
  </a>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit',function(){
  const b=document.getElementById('resetBtn');
  b.disabled=true;
  b.innerHTML='<i class="fas fa-spinner fa-spin"></i> Sending...';
});
</script>
