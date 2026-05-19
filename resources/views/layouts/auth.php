<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle).' — ' : '' ?><?= e(env('APP_NAME','HRMS')) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{min-height:100vh;background:#f0f2f5;font-family:'Segoe UI',Helvetica,Arial,sans-serif;display:flex;flex-direction:column}

  /* ── Page wrapper ── */
  .fb-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:20px 16px;gap:32px;max-width:980px;margin:0 auto;width:100%}

  /* ── Left brand panel ── */
  .fb-brand{flex:1;padding-bottom:60px;display:none}
  .fb-brand .logo{font-size:2.1rem;font-weight:900;color:#1877f2;letter-spacing:-1px;margin-bottom:16px;display:flex;align-items:center;gap:12px}
  .fb-brand .logo i{background:#1877f2;color:#fff;width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
  .fb-brand h2{font-size:1.65rem;font-weight:400;color:#1c1e21;line-height:1.4}

  /* ── Right card ── */
  .fb-card{background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.1),0 8px 16px rgba(0,0,0,.1);padding:20px;width:100%;max-width:396px}

  /* ── Inputs ── */
  .fb-input{width:100%;height:52px;padding:14px 16px;border:1px solid #dddfe2;border-radius:6px;font-size:1.0625rem;color:#1c1e21;background:#fff;outline:none;transition:border-color .1s}
  .fb-input:focus{border-color:#1877f2;box-shadow:0 0 0 2px rgba(24,119,242,.2)}
  .fb-input::placeholder{color:#8a8d91}

  /* ── Login button ── */
  .fb-btn{width:100%;background:#1877f2;color:#fff;border:none;border-radius:6px;font-size:1.25rem;font-weight:700;padding:14px;cursor:pointer;transition:background .1s}
  .fb-btn:hover{background:#166fe5}
  .fb-btn:active{background:#1464d8}
  .fb-btn:disabled{opacity:.7;cursor:not-allowed}

  /* ── Links ── */
  .fb-link{color:#1877f2;text-decoration:none;font-size:.9375rem}
  .fb-link:hover{text-decoration:underline}

  /* ── Divider ── */
  .fb-divider{height:1px;background:#dddfe2;margin:20px 0}

  /* ── Create account button ── */
  .fb-btn-new{background:#42b72a;color:#fff;border:none;border-radius:6px;font-size:1.0625rem;font-weight:700;padding:14px 16px;cursor:pointer;transition:background .1s}
  .fb-btn-new:hover{background:#36a420}

  /* ── Alerts ── */
  .fb-alert{padding:10px 14px;border-radius:6px;font-size:.9rem;margin-bottom:14px;border-left:4px solid}
  .fb-alert.error{background:#fff0f0;border-color:#fa3e3e;color:#c0392b}
  .fb-alert.success{background:#f0fff4;border-color:#42b72a;color:#1e7e34}

  /* ── Footer ── */
  .fb-footer{text-align:center;font-size:.8125rem;color:#737373;padding:20px 16px}
  .fb-footer a{color:#737373;text-decoration:none;margin:0 4px}
  .fb-footer a:hover{text-decoration:underline}
  .fb-footer-dot{margin:0 4px}

  /* ── Password toggle ── */
  .pw-wrap{position:relative}
  .pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#8a8d91;cursor:pointer;font-size:.9rem;padding:4px}

  /* ── Remember + forgot row ── */
  .fb-row{display:flex;align-items:center;justify-content:space-between;margin:12px 0 20px}
  .fb-check{display:flex;align-items:center;gap:6px;font-size:.875rem;color:#1c1e21;cursor:pointer}
  .fb-check input{width:16px;height:16px;cursor:pointer;accent-color:#1877f2}

  /* ── Desktop: show brand panel ── */
  @media(min-width:768px){
    .fb-brand{display:block}
    .fb-card{padding:20px 24px 28px}
  }

  /* ── Mobile card ── */
  @media(max-width:767px){
    .fb-wrap{padding-top:40px;align-items:flex-start}
    .fb-card{box-shadow:none;border:1px solid #dddfe2;padding:16px}
    .mobile-logo{text-align:center;margin-bottom:24px}
    .mobile-logo i{background:#1877f2;color:#fff;width:56px;height:56px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:8px}
    .mobile-logo h1{font-size:1.25rem;font-weight:700;color:#1877f2}
  }
  @media(min-width:768px){.mobile-logo{display:none}}
</style>
</head>
<body>

<div class="fb-wrap">
  <!-- Left: Brand (desktop only) -->
  <div class="fb-brand">
    <div class="logo"><i class="fas fa-building"></i><?= e(env('APP_NAME','HRMS')) ?></div>
    <h2>Connect your team,<br>streamline your workforce<br>management.</h2>
  </div>

  <!-- Right: Card -->
  <div class="fb-card">
    <!-- Mobile logo -->
    <div class="mobile-logo">
      <div><i class="fas fa-building"></i></div>
      <h1><?= e(env('APP_NAME','HRMS')) ?></h1>
    </div>

    <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
      <div class="fb-alert error"><i class="fas fa-exclamation-circle me-1"></i><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
      <div class="fb-alert success"><i class="fas fa-check-circle me-1"></i><?= e($flash) ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
  </div>
</div>

<!-- Footer links -->
<div class="fb-footer">
  <div style="margin-bottom:8px">
    <a href="#">About</a><span class="fb-footer-dot">·</span>
    <a href="#">Privacy</a><span class="fb-footer-dot">·</span>
    <a href="#">Terms</a><span class="fb-footer-dot">·</span>
    <a href="#">Help</a>
  </div>
  <div><?= e(env('APP_NAME','HRMS')) ?> &copy; <?= date('Y') ?></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
