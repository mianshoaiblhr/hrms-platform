<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>500 — Server Error</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  body { background: #fffbeb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .error-code { font-size: 120px; font-weight: 900; line-height: 1; color: #fde68a; }
  .accent { color: #d97706; }
</style>
</head>
<body>
<div class="text-center p-4">
  <div class="error-code"><span class="accent">5</span>0<span class="accent">0</span></div>
  <h2 class="fw-bold mt-0">Server Error</h2>
  <p class="text-muted mb-4">Something went wrong on our end. Our team has been notified.<br>Please try again in a few moments.</p>
  <div class="d-flex gap-2 justify-content-center">
    <a href="/" class="btn btn-warning text-white"><i class="fas fa-home me-1"></i>Dashboard</a>
    <a href="javascript:location.reload()" class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i>Try Again</a>
  </div>
  <?php if (defined('APP_DEBUG') && APP_DEBUG && !empty($error)): ?>
  <div class="mt-4 text-start bg-dark text-white rounded p-3" style="max-width:700px;margin:auto;font-size:12px;font-family:monospace;overflow:auto">
    <div class="text-danger fw-bold"><?= e($error['message'] ?? '') ?></div>
    <div class="text-muted mt-1"><?= e($error['file'] ?? '') ?>:<?= $error['line'] ?? '' ?></div>
    <pre class="text-warning mt-2 small"><?= e($error['trace'] ?? '') ?></pre>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
