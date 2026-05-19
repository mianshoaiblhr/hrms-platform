<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 — Access Denied</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  body { background: #fef2f2; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .error-code { font-size: 120px; font-weight: 900; line-height: 1; color: #fecaca; }
  .accent { color: #dc2626; }
</style>
</head>
<body>
<div class="text-center p-4">
  <div class="error-code"><span class="accent">4</span>0<span class="accent">3</span></div>
  <h2 class="fw-bold mt-0">Access Denied</h2>
  <p class="text-muted mb-4">You do not have permission to access this page.<br>Contact your administrator if you believe this is a mistake.</p>
  <div class="d-flex gap-2 justify-content-center">
    <a href="/" class="btn btn-danger"><i class="fas fa-home me-1"></i>Dashboard</a>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Go Back</a>
  </div>
</div>
</body>
</html>
