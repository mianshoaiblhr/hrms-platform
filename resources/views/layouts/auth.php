<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle).' — ' : '' ?>ORBIT HRMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Inter',sans-serif}

/* ── Layout ─────────────────────────────────────────── */
.orbit-wrap{display:flex;min-height:100vh}

/* ── LEFT PANEL ─────────────────────────────────────── */
.orbit-left{
  width:48%;
  background:linear-gradient(145deg,#060d1f 0%,#0d1b3e 40%,#112050 70%,#0a1628 100%);
  position:relative;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  padding:48px 52px;
}

/* Orbit rings animation */
.orbit-rings{
  position:absolute;
  right:-120px;
  top:50%;
  transform:translateY(-50%);
  width:560px;
  height:560px;
  pointer-events:none;
}
.ring{
  position:absolute;
  border-radius:50%;
  border:1px solid rgba(99,179,237,0.12);
  top:50%;left:50%;
  transform:translate(-50%,-50%);
}
.ring-1{width:200px;height:200px;border-color:rgba(129,140,248,.25);animation:spin 18s linear infinite}
.ring-2{width:320px;height:320px;border-color:rgba(99,179,237,.15);animation:spin 28s linear infinite reverse}
.ring-3{width:440px;height:440px;border-color:rgba(139,92,246,.1);animation:spin 40s linear infinite}
.ring-4{width:560px;height:560px;border-color:rgba(99,179,237,.06);animation:spin 55s linear infinite reverse}

/* Orbit dot */
.orbit-dot{
  position:absolute;
  width:10px;height:10px;
  border-radius:50%;
  background:#818cf8;
  box-shadow:0 0 12px #818cf8,0 0 24px rgba(129,140,248,.5);
  top:50%;left:100%;
  transform:translate(-50%,-50%);
  animation:orbit-dot1 18s linear infinite;
}
.orbit-dot2{
  width:7px;height:7px;
  background:#67e8f9;
  box-shadow:0 0 10px #67e8f9;
  animation:orbit-dot2 28s linear infinite reverse;
  top:0;left:50%;
}
@keyframes spin{to{transform:translate(-50%,-50%) rotate(360deg)}}
@keyframes orbit-dot1{
  0%{top:50%;left:100%;transform:translate(-50%,-50%)}
  25%{top:0;left:50%;transform:translate(-50%,-50%)}
  50%{top:50%;left:0;transform:translate(-50%,-50%)}
  75%{top:100%;left:50%;transform:translate(-50%,-50%)}
  100%{top:50%;left:100%;transform:translate(-50%,-50%)}
}
@keyframes orbit-dot2{
  0%{top:0;left:50%}
  25%{top:50%;left:100%}
  50%{top:100%;left:50%}
  75%{top:50%;left:0}
  100%{top:0;left:50%}
}

/* Glow blob */
.glow-blob{
  position:absolute;
  border-radius:50%;
  filter:blur(80px);
  opacity:.18;
}
.blob-1{width:300px;height:300px;background:#4f46e5;top:-60px;left:-80px}
.blob-2{width:250px;height:250px;background:#7c3aed;bottom:-40px;left:100px}
.blob-3{width:200px;height:200px;background:#0ea5e9;top:40%;right:0}

/* Logo */
.orbit-logo{position:relative;z-index:2}
.orbit-logo .logo-icon{
  display:inline-flex;
  align-items:center;
  gap:12px;
  margin-bottom:16px;
}
.orbit-logo .icon-wrap{
  width:48px;height:48px;
  background:linear-gradient(135deg,#4f46e5,#7c3aed);
  border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 8px 24px rgba(79,70,229,.4);
}
.orbit-logo .icon-wrap i{color:#fff;font-size:20px}
.orbit-logo .brand-name{
  font-size:1.6rem;
  font-weight:800;
  color:#fff;
  letter-spacing:-.5px;
}
.orbit-logo .brand-name span{color:#818cf8}
.orbit-logo .tagline{
  font-size:.9rem;
  color:rgba(255,255,255,.5);
  font-weight:400;
  letter-spacing:.02em;
}

/* Feature cards */
.feature-cards{position:relative;z-index:2;display:flex;flex-direction:column;gap:12px}
.feat-card{
  background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.08);
  border-radius:14px;
  padding:16px 20px;
  display:flex;
  align-items:center;
  gap:16px;
  backdrop-filter:blur(10px);
  transition:background .2s;
}
.feat-card:hover{background:rgba(255,255,255,.08)}
.feat-icon{
  width:40px;height:40px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;font-size:16px;
}
.fi-purple{background:rgba(129,140,248,.2);color:#818cf8}
.fi-teal{background:rgba(103,232,249,.2);color:#67e8f9}
.fi-green{background:rgba(52,211,153,.2);color:#34d399}
.fi-amber{background:rgba(251,191,36,.2);color:#fbbf24}
.feat-card h4{font-size:.8rem;font-weight:600;color:#fff;margin-bottom:2px}
.feat-card p{font-size:.73rem;color:rgba(255,255,255,.45);margin:0}

/* Stats row */
.stats-row{position:relative;z-index:2;display:flex;gap:20px}
.stat-item{text-align:center}
.stat-item .num{font-size:1.4rem;font-weight:800;color:#fff}
.stat-item .lbl{font-size:.7rem;color:rgba(255,255,255,.4);margin-top:2px}
.stat-divider{width:1px;background:rgba(255,255,255,.1)}

/* ── RIGHT PANEL ─────────────────────────────────────── */
.orbit-right{
  flex:1;
  background:#fff;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  padding:60px 48px;
  position:relative;
}
.orbit-right::before{
  content:'';
  position:absolute;
  top:0;right:0;
  width:320px;height:320px;
  background:radial-gradient(circle,rgba(79,70,229,.04) 0%,transparent 70%);
  pointer-events:none;
}

.form-wrap{width:100%;max-width:400px}

.form-header{margin-bottom:36px}
.form-header h2{font-size:1.75rem;font-weight:800;color:#0f172a;margin-bottom:6px;letter-spacing:-.5px}
.form-header p{font-size:.9rem;color:#64748b;font-weight:400}

/* Input groups */
.input-group-orbit{margin-bottom:20px}
.input-group-orbit label{display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:7px;letter-spacing:.01em}
.orbit-input{
  width:100%;
  height:50px;
  padding:0 16px;
  border:1.5px solid #e2e8f0;
  border-radius:10px;
  font-size:.9375rem;
  font-family:'Inter',sans-serif;
  color:#0f172a;
  background:#fafafa;
  outline:none;
  transition:border-color .15s,background .15s,box-shadow .15s;
}
.orbit-input:focus{
  border-color:#4f46e5;
  background:#fff;
  box-shadow:0 0 0 3px rgba(79,70,229,.1);
}
.orbit-input::placeholder{color:#94a3b8}

.pw-field{position:relative}
.pw-field .orbit-input{padding-right:48px}
.pw-toggle{
  position:absolute;right:14px;top:50%;transform:translateY(-50%);
  background:none;border:none;color:#94a3b8;cursor:pointer;
  font-size:.85rem;padding:4px;
  transition:color .15s;
}
.pw-toggle:hover{color:#4f46e5}

/* Row: remember + forgot */
.form-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.remember-check{display:flex;align-items:center;gap:8px;cursor:pointer}
.remember-check input{width:16px;height:16px;accent-color:#4f46e5;cursor:pointer}
.remember-check span{font-size:.8125rem;color:#64748b}
.forgot-link{font-size:.8125rem;color:#4f46e5;text-decoration:none;font-weight:500}
.forgot-link:hover{text-decoration:underline}

/* Sign in button */
.orbit-btn{
  width:100%;height:50px;
  background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);
  color:#fff;border:none;border-radius:10px;
  font-size:1rem;font-weight:600;
  font-family:'Inter',sans-serif;
  cursor:pointer;
  transition:opacity .15s,transform .1s;
  display:flex;align-items:center;justify-content:center;gap:8px;
  box-shadow:0 4px 14px rgba(79,70,229,.35);
  letter-spacing:.01em;
}
.orbit-btn:hover{opacity:.92}
.orbit-btn:active{transform:scale(.99)}
.orbit-btn:disabled{opacity:.65;cursor:not-allowed}

/* Divider */
.or-divider{
  display:flex;align-items:center;gap:12px;
  margin:24px 0;color:#cbd5e1;font-size:.8rem;
}
.or-divider::before,.or-divider::after{content:'';flex:1;height:1px;background:#e2e8f0}

/* Alerts */
.orbit-alert{
  padding:12px 16px;border-radius:10px;
  font-size:.85rem;margin-bottom:20px;
  display:flex;align-items:center;gap:10px;
}
.orbit-alert.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.orbit-alert.success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}

/* Footer */
.right-footer{
  position:absolute;bottom:24px;
  font-size:.75rem;color:#94a3b8;
  text-align:center;
}

/* ── Mobile ──────────────────────────────────────────── */
@media(max-width:900px){
  .orbit-left{display:none}
  .orbit-right{padding:40px 24px}
  .form-wrap{max-width:100%}
  .mobile-logo{
    display:flex;align-items:center;gap:12px;
    margin-bottom:32px;
  }
  .mobile-logo .icon-wrap{
    width:44px;height:44px;border-radius:12px;
    background:linear-gradient(135deg,#4f46e5,#7c3aed);
    display:flex;align-items:center;justify-content:center;
  }
  .mobile-logo .icon-wrap i{color:#fff;font-size:18px}
  .mobile-logo span{font-size:1.3rem;font-weight:800;color:#0f172a}
  .mobile-logo span em{color:#4f46e5;font-style:normal}
}
@media(min-width:901px){.mobile-logo{display:none}}
</style>
</head>
<body>
<div class="orbit-wrap">

  <!-- ════ LEFT PANEL ════ -->
  <div class="orbit-left">
    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>
    <div class="glow-blob blob-3"></div>

    <!-- Orbit rings -->
    <div class="orbit-rings">
      <div class="ring ring-1"><div class="orbit-dot"></div></div>
      <div class="ring ring-2"><div class="orbit-dot orbit-dot2"></div></div>
      <div class="ring ring-3"></div>
      <div class="ring ring-4"></div>
    </div>

    <!-- Logo -->
    <div class="orbit-logo">
      <div class="logo-icon">
        <div class="icon-wrap"><i class="fas fa-circle-nodes"></i></div>
        <span class="brand-name">ORBIT <span>HRMS</span></span>
      </div>
      <p class="tagline">Enterprise Human Resource Management Platform</p>
    </div>

    <!-- Feature cards -->
    <div class="feature-cards">
      <div class="feat-card">
        <div class="feat-icon fi-purple"><i class="fas fa-money-check-alt"></i></div>
        <div>
          <h4>FBR-Compliant Payroll</h4>
          <p>Automated tax calculations with 2024-25 slabs, EOBI & PESSI</p>
        </div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-teal"><i class="fas fa-users-cog"></i></div>
        <div>
          <h4>Smart HR Management</h4>
          <p>Employee lifecycle, attendance tracking and leave workflows</p>
        </div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-green"><i class="fas fa-chart-line"></i></div>
        <div>
          <h4>Real-time Analytics</h4>
          <p>Live dashboards, custom reports and compliance exports</p>
        </div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-amber"><i class="fas fa-shield-halved"></i></div>
        <div>
          <h4>Role-Based Access</h4>
          <p>8 permission levels with full audit trail and session security</p>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-item"><div class="num">30+</div><div class="lbl">Modules</div></div>
      <div class="stat-divider"></div>
      <div class="stat-item"><div class="num">100%</div><div class="lbl">FBR Compliant</div></div>
      <div class="stat-divider"></div>
      <div class="stat-item"><div class="num">99.9%</div><div class="lbl">Uptime SLA</div></div>
    </div>
  </div>

  <!-- ════ RIGHT PANEL ════ -->
  <div class="orbit-right">

    <!-- Mobile logo -->
    <div class="mobile-logo form-wrap">
      <div class="icon-wrap"><i class="fas fa-circle-nodes"></i></div>
      <span>ORBIT <em>HRMS</em></span>
    </div>

    <div class="form-wrap">
      <div class="form-header">
        <h2>Welcome back</h2>
        <p>Sign in to your ORBIT HRMS account</p>
      </div>

      <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
        <div class="orbit-alert error"><i class="fas fa-circle-xmark"></i><?= e($flash) ?></div>
      <?php endif; ?>
      <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
        <div class="orbit-alert success"><i class="fas fa-circle-check"></i><?= e($flash) ?></div>
      <?php endif; ?>
      <?php if ($flash = \App\Core\Session::getFlash('alert_message')): ?>
        <div class="orbit-alert error"><i class="fas fa-triangle-exclamation"></i><?= e($flash) ?></div>
      <?php endif; ?>

      <?= $content ?? '' ?>
    </div>

    <div class="right-footer">
      © <?= date('Y') ?> ORBIT HRMS &nbsp;·&nbsp; All rights reserved
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
