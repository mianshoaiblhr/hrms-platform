<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(env('APP_NAME','HRMS')) ?></title>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.auth-wrapper{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 50%,#2563eb 100%);padding:20px}
.auth-card{background:var(--bg-card);border-radius:16px;padding:2.5rem;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.auth-logo{text-align:center;margin-bottom:2rem}
.auth-logo .logo-icon{width:60px;height:60px;background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:.75rem;box-shadow:0 8px 20px rgba(37,99,235,.4)}
.auth-logo .logo-icon i{font-size:28px;color:#fff}
.auth-logo h1{font-size:1.5rem;font-weight:700;color:var(--text-primary);margin:0 0 .25rem}
.auth-logo p{font-size:.85rem;color:var(--text-muted)}
.form-floating>.form-control{border-radius:10px;border:2px solid var(--border-color);background:var(--bg-input)}
.form-floating>.form-control:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.btn-auth{width:100%;padding:.75rem;border-radius:10px;font-size:1rem;font-weight:600;letter-spacing:.025em}
.alert{border-radius:10px;border:none;font-size:.9rem}
</style>
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon"><i class="fas fa-building"></i></div>
      <h1><?= e(env('APP_NAME','Enterprise HRMS')) ?></h1>
      <p><?= isset($subtitle) ? e($subtitle) : 'Secure Human Resource Management' ?></p>
    </div>

    <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($flash) ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
