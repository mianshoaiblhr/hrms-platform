<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= csrf_token() ?>">
<title><?= isset($pageTitle) ? e($pageTitle).' — ' : '' ?>ORBIT HRMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --sidebar-w:240px;
  --topbar-h:60px;
  --primary:#4f46e5;
  --primary-dark:#4338ca;
  --sidebar-bg:#0f172a;
  --sidebar-text:rgba(255,255,255,.7);
  --sidebar-hover:rgba(255,255,255,.08);
  --sidebar-active:rgba(79,70,229,.25);
}
*{box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f1f5f9;margin:0;min-height:100vh}

/* ── Sidebar ── */
.sidebar{
  position:fixed;top:0;left:0;height:100vh;width:var(--sidebar-w);
  background:var(--sidebar-bg);z-index:1000;
  display:flex;flex-direction:column;
  transition:transform .25s ease;overflow:hidden;
}
.sidebar-brand{
  padding:18px 20px;display:flex;align-items:center;gap:10px;
  border-bottom:1px solid rgba(255,255,255,.07);flex-shrink:0;
}
.sidebar-brand .brand-icon{
  width:34px;height:34px;background:linear-gradient(135deg,#4f46e5,#7c3aed);
  border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.sidebar-brand .brand-icon i{color:#fff;font-size:14px}
.sidebar-brand .brand-name{font-size:.95rem;font-weight:700;color:#fff;letter-spacing:-.3px}
.sidebar-brand .brand-name span{color:#818cf8}

.sidebar-nav{flex:1;overflow-y:auto;padding:12px 0}
.sidebar-nav::-webkit-scrollbar{width:3px}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px}

.nav-section{
  font-size:.65rem;font-weight:600;letter-spacing:.1em;
  color:rgba(255,255,255,.3);padding:16px 20px 6px;text-transform:uppercase;
}
.nav-link{
  display:flex;align-items:center;gap:10px;
  padding:9px 20px;color:var(--sidebar-text);
  text-decoration:none;font-size:.83rem;font-weight:500;
  border-radius:0;transition:all .15s;margin:1px 8px;border-radius:8px;
}
.nav-link i{width:18px;text-align:center;font-size:.85rem;opacity:.8}
.nav-link:hover{background:var(--sidebar-hover);color:#fff}
.nav-link.active{background:var(--sidebar-active);color:#fff;font-weight:600}
.nav-link.active i{opacity:1}

.sidebar-footer{
  padding:14px 16px;border-top:1px solid rgba(255,255,255,.07);flex-shrink:0;
}
.user-pill{
  display:flex;align-items:center;gap:10px;padding:8px 10px;
  border-radius:10px;background:rgba(255,255,255,.05);cursor:pointer;
  transition:background .15s;
}
.user-pill:hover{background:rgba(255,255,255,.1)}
.user-avatar{
  width:32px;height:32px;border-radius:50%;
  background:linear-gradient(135deg,#4f46e5,#7c3aed);
  display:flex;align-items:center;justify-content:center;
  font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;
}
.user-info .name{font-size:.8rem;font-weight:600;color:#fff;line-height:1.2}
.user-info .role{font-size:.7rem;color:rgba(255,255,255,.4)}

/* ── Topbar ── */
.topbar{
  position:fixed;top:0;left:var(--sidebar-w);right:0;height:var(--topbar-h);
  background:#fff;border-bottom:1px solid #e2e8f0;z-index:999;
  display:flex;align-items:center;padding:0 24px;gap:16px;
}
.topbar-title{font-size:1rem;font-weight:600;color:#0f172a;flex:1}
.topbar-actions{display:flex;align-items:center;gap:8px}
.icon-btn{
  width:36px;height:36px;border-radius:9px;border:none;
  background:transparent;color:#64748b;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  position:relative;transition:background .15s;font-size:.9rem;
}
.icon-btn:hover{background:#f1f5f9;color:#0f172a}
.notif-badge{
  position:absolute;top:5px;right:5px;width:8px;height:8px;
  background:#ef4444;border-radius:50%;border:2px solid #fff;
}

/* ── Main content ── */
.main-content{
  margin-left:var(--sidebar-w);margin-top:var(--topbar-h);
  padding:24px;min-height:calc(100vh - var(--topbar-h));
}

/* ── Cards & stats ── */
.stat-card{
  background:#fff;border-radius:14px;padding:20px 22px;
  border:1px solid #e2e8f0;transition:box-shadow .2s;
}
.stat-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.07)}
.stat-icon{
  width:46px;height:46px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;font-size:1.1rem;
  margin-bottom:12px;
}
.stat-value{font-size:1.8rem;font-weight:700;color:#0f172a;line-height:1}
.stat-label{font-size:.78rem;color:#64748b;margin-top:4px;font-weight:500}

.card{border:1px solid #e2e8f0;border-radius:14px;background:#fff}
.card-header{
  padding:16px 20px;border-bottom:1px solid #f1f5f9;
  font-size:.85rem;font-weight:600;color:#0f172a;background:transparent;
  display:flex;align-items:center;justify-content:space-between;
}
.card-body{padding:20px}

/* ── Tables ── */
.table{font-size:.835rem}
.table thead th{
  font-size:.72rem;font-weight:600;text-transform:uppercase;
  letter-spacing:.05em;color:#64748b;border-bottom:2px solid #f1f5f9;
  padding:10px 16px;background:#fafafa;
}
.table td{padding:12px 16px;vertical-align:middle;border-bottom:1px solid #f8fafc;color:#374151}
.table tbody tr:hover{background:#fafafe}
.table tbody tr:last-child td{border-bottom:none}

/* ── Badges ── */
.badge{font-size:.7rem;font-weight:500;padding:4px 9px;border-radius:20px}

/* ── Alerts / Flash ── */
.flash-msg{
  position:fixed;top:72px;right:20px;z-index:9999;
  padding:12px 18px;border-radius:10px;font-size:.875rem;
  box-shadow:0 8px 24px rgba(0,0,0,.12);max-width:340px;
  display:flex;align-items:center;gap:10px;animation:slideIn .3s ease;
}
@keyframes slideIn{from{transform:translateX(120%)}to{transform:translateX(0)}}
.flash-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.flash-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.flash-warning{background:#fffbeb;color:#d97706;border:1px solid #fde68a}

/* ── Status badges ── */
.status-active{background:#dcfce7;color:#166534}
.status-inactive{background:#f1f5f9;color:#475569}
.status-pending{background:#fef9c3;color:#854d0e}
.status-approved{background:#dcfce7;color:#166534}
.status-rejected{background:#fee2e2;color:#991b1b}
.status-terminated{background:#fee2e2;color:#991b1b}
.status-draft{background:#f1f5f9;color:#475569}
.status-paid{background:#dcfce7;color:#166534}

/* ── Form controls ── */
.form-control,.form-select{
  border:1.5px solid #e2e8f0;border-radius:9px;
  font-size:.875rem;padding:9px 13px;
  transition:border-color .15s,box-shadow .15s;
}
.form-control:focus,.form-select:focus{
  border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1);outline:none;
}
.form-label{font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px}

/* ── Buttons ── */
.btn-primary{background:#4f46e5;border-color:#4f46e5}
.btn-primary:hover{background:#4338ca;border-color:#4338ca}
.btn-sm{font-size:.78rem;padding:6px 14px;border-radius:8px}
.btn-xs{font-size:.72rem;padding:4px 10px;border-radius:7px}

/* ── Mobile ── */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.show{transform:translateX(0)}
  .topbar,.main-content{left:0;margin-left:0}
}

/* ── Utility ── */
.text-muted{color:#94a3b8 !important}
.fw-semibold{font-weight:600}
.x-small{font-size:.72rem}
.avatar{
  width:38px;height:38px;border-radius:50%;
  background:linear-gradient(135deg,#4f46e5,#7c3aed);
  display:inline-flex;align-items:center;justify-content:center;
  font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0;
}
.avatar-sm{width:30px;height:30px;font-size:.65rem}
.avatar-xl{width:80px;height:80px;font-size:1.4rem}
</style>
</head>
<body>

<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$userName   = \App\Core\Session::get('full_name') ?: \App\Core\Session::get('username') ?: 'Admin';
$userRole   = \App\Core\Session::get('role_name') ?: 'User';
$initials   = strtoupper(substr(explode(' ',$userName)[0],0,1) . (substr(explode(' ',$userName)[1] ?? '',0,1)));
$isSuper    = \App\Core\Auth::getInstance()->user()['is_super_admin'] ?? false;

function navActive(string $path): string {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
}
?>

<!-- ════ SIDEBAR ════ -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fas fa-circle-nodes"></i></div>
    <div class="brand-name">ORBIT <span>HRMS</span></div>
  </div>

  <nav class="sidebar-nav">
    <a href="/dashboard" class="nav-link <?= navActive('/dashboard') ?>">
      <i class="fas fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <div class="nav-section">Workforce</div>
    <?php if(can('employees.view')): ?>
    <a href="/employees" class="nav-link <?= navActive('/employees') ?>">
      <i class="fas fa-users"></i><span>Employees</span>
    </a>
    <?php endif; ?>
    <?php if(can('attendance.view')): ?>
    <a href="/attendance" class="nav-link <?= navActive('/attendance') ?>">
      <i class="fas fa-fingerprint"></i><span>Attendance</span>
    </a>
    <?php endif; ?>
    <?php if(can('leaves.view')): ?>
    <a href="/leaves" class="nav-link <?= navActive('/leaves') ?>">
      <i class="fas fa-calendar-minus"></i><span>Leave Management</span>
    </a>
    <?php endif; ?>
    <?php if(can('documents.view')): ?>
    <a href="/documents" class="nav-link <?= navActive('/documents') ?>">
      <i class="fas fa-folder-open"></i><span>Documents</span>
    </a>
    <?php endif; ?>

    <div class="nav-section">Payroll</div>
    <?php if(can('payroll.view')): ?>
    <a href="/payroll" class="nav-link <?= navActive('/payroll') ?>">
      <i class="fas fa-money-check-dollar"></i><span>Payroll</span>
    </a>
    <?php endif; ?>
    <?php if(can('advances.view')): ?>
    <a href="/advances" class="nav-link <?= navActive('/advances') ?>">
      <i class="fas fa-hand-holding-dollar"></i><span>Advances</span>
    </a>
    <?php endif; ?>

    <div class="nav-section">Reports</div>
    <?php if(can('reports.view')): ?>
    <a href="/reports" class="nav-link <?= navActive('/reports') ?>">
      <i class="fas fa-chart-bar"></i><span>Reports</span>
    </a>
    <?php endif; ?>

    <?php if($isSuper || can('roles.view')): ?>
    <div class="nav-section">Administration</div>
    <a href="/roles" class="nav-link <?= navActive('/roles') ?>">
      <i class="fas fa-shield-halved"></i><span>Roles & Permissions</span>
    </a>
    <a href="/settings" class="nav-link <?= navActive('/settings') ?>">
      <i class="fas fa-gear"></i><span>Settings</span>
    </a>
    <a href="/audit" class="nav-link <?= navActive('/audit') ?>">
      <i class="fas fa-history"></i><span>Audit Logs</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-pill" onclick="window.location='/profile'">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-info">
        <div class="name"><?= e(explode(' ', $userName)[0]) ?></div>
        <div class="role"><?= e($userRole) ?></div>
      </div>
      <i class="fas fa-chevron-right ms-auto" style="color:rgba(255,255,255,.3);font-size:.7rem"></i>
    </div>
  </div>
</div>

<!-- ════ TOPBAR ════ -->
<header class="topbar">
  <button class="icon-btn d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
    <i class="fas fa-bars"></i>
  </button>
  <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
  <div class="topbar-actions">
    <form class="d-none d-md-flex" style="position:relative">
      <input type="text" class="form-control form-control-sm" placeholder="Search..." style="width:200px;padding-left:32px">
      <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.75rem"></i>
    </form>
    <button class="icon-btn" title="Notifications">
      <i class="fas fa-bell"></i>
      <span class="notif-badge"></span>
    </button>
    <div class="dropdown">
      <button class="icon-btn" data-bs-toggle="dropdown">
        <div class="user-avatar" style="width:32px;height:32px;font-size:.7rem"><?= $initials ?></div>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border:1px solid #e2e8f0;border-radius:12px;font-size:.85rem">
        <li><div class="px-3 py-2 border-bottom"><div class="fw-semibold"><?= e($userName) ?></div><div class="text-muted small"><?= e($userRole) ?></div></div></li>
        <li><a class="dropdown-item py-2" href="/profile"><i class="fas fa-user me-2 text-muted"></i>My Profile</a></li>
        <li><a class="dropdown-item py-2" href="/settings"><i class="fas fa-gear me-2 text-muted"></i>Settings</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item py-2 text-danger" href="/logout"><i class="fas fa-right-from-bracket me-2"></i>Sign Out</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ════ FLASH MESSAGES ════ -->
<?php if ($msg = \App\Core\Session::getFlash('success')): ?>
  <div class="flash-msg flash-success" id="flashMsg"><i class="fas fa-circle-check"></i><?= e($msg) ?></div>
<?php elseif ($msg = \App\Core\Session::getFlash('error')): ?>
  <div class="flash-msg flash-error" id="flashMsg"><i class="fas fa-circle-xmark"></i><?= e($msg) ?></div>
<?php elseif ($msg = \App\Core\Session::getFlash('alert_message')): ?>
  <div class="flash-msg flash-warning" id="flashMsg"><i class="fas fa-triangle-exclamation"></i><?= e($msg) ?></div>
<?php endif; ?>

<!-- ════ MAIN CONTENT ════ -->
<main class="main-content">
  <?= $content ?? '' ?>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Auto-dismiss flash messages
setTimeout(()=>{const f=document.getElementById('flashMsg');if(f)f.style.opacity='0',setTimeout(()=>f.remove(),400)},4000);

// CSRF for AJAX
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content||'';
const $ajax = (url,data)=>fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(data)}).then(r=>r.json());

// statusBadge helper for JS
window.statusBadge = s => {
  const map = {active:'success',inactive:'secondary',pending:'warning',approved:'success',rejected:'danger',terminated:'danger',draft:'secondary',paid:'success'};
  return `<span class="badge status-${s||'active'}">${s||'active'}</span>`;
};
</script>
</body>
</html>
