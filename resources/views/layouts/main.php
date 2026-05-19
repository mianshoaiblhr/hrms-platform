<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= csrf_token() ?>">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(env('APP_NAME','HRMS')) ?></title>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
</head>
<body>
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><i class="fas fa-building"></i></div>
    <span class="brand-text"><?= e(env('APP_NAME','HRMS')) ?></span>
  </div>
  <div class="sidebar-menu">
    <a href="/dashboard" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/dashboard')!==false?'active':'' ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
    <?php if (can('employees.view')): ?>
    <div class="menu-section">WORKFORCE</div>
    <a href="/employees" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/employees')!==false?'active':'' ?>"><i class="fas fa-users"></i><span>Employees</span></a>
    <?php endif; ?>
    <?php if (can('attendance.view')): ?>
    <a href="/attendance" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/attendance')!==false?'active':'' ?>"><i class="fas fa-fingerprint"></i><span>Attendance</span></a>
    <?php endif; ?>
    <?php if (can('leaves.view')): ?>
    <a href="/leaves" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/leaves')!==false?'active':'' ?>"><i class="fas fa-calendar-minus"></i><span>Leave Management</span></a>
    <?php endif; ?>
    <?php if (can('payroll.view')): ?>
    <div class="menu-section">FINANCE</div>
    <a href="/payroll" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/payroll')!==false?'active':'' ?>"><i class="fas fa-money-bill-wave"></i><span>Payroll</span></a>
    <a href="/salary/structure" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/salary')!==false?'active':'' ?>"><i class="fas fa-sliders-h"></i><span>Salary Structure</span></a>
    <?php endif; ?>
    <?php if (can('documents.view')): ?>
    <div class="menu-section">DOCUMENTS</div>
    <a href="/documents" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/documents')!==false?'active':'' ?>"><i class="fas fa-folder-open"></i><span>Documents</span></a>
    <?php endif; ?>
    <?php if (can('tasks.view')): ?>
    <a href="/tasks" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/tasks')!==false?'active':'' ?>"><i class="fas fa-tasks"></i><span>Tasks</span></a>
    <?php endif; ?>
    <?php if (can('reports.view')): ?>
    <div class="menu-section">ANALYTICS</div>
    <a href="/reports" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/reports')!==false?'active':'' ?>"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
    <?php endif; ?>
    <?php if (can('audit.view')): ?>
    <a href="/audit" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/audit')!==false?'active':'' ?>"><i class="fas fa-shield-alt"></i><span>Audit Logs</span></a>
    <?php endif; ?>
    <?php if (can('settings.view') || can('users.view') || can('roles.view')): ?>
    <div class="menu-section">ADMINISTRATION</div>
    <?php if (can('users.view')): ?><a href="/users" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/users')!==false?'active':'' ?>"><i class="fas fa-user-cog"></i><span>User Accounts</span></a><?php endif; ?>
    <?php if (can('roles.view')): ?><a href="/roles" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/roles')!==false?'active':'' ?>"><i class="fas fa-user-shield"></i><span>Roles & Permissions</span></a><?php endif; ?>
    <?php if (can('settings.view')): ?><a href="/settings" class="menu-item <?= strpos($_SERVER['REQUEST_URI'],'/settings')!==false?'active':'' ?>"><i class="fas fa-cog"></i><span>Settings</span></a><?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<div class="main-wrapper" id="mainWrapper">
  <div class="topbar">
    <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="topbar-search d-none d-md-flex align-items-center">
      <input type="text" class="form-control form-control-sm" id="globalSearch" placeholder="Search..." autocomplete="off" style="width:240px">
      <div class="search-results shadow" id="searchResults"></div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <button class="btn btn-link topbar-btn" onclick="toggleDarkMode()" title="Dark Mode"><i class="fas fa-moon" id="darkModeIcon"></i></button>
      <div class="dropdown">
        <button class="btn btn-link topbar-btn position-relative" data-bs-toggle="dropdown"><i class="fas fa-bell"></i>
          <?php $nc=\App\Core\Database::getInstance()->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL",[(int)(authUser()['id']??0)]); if($nc>0): ?><span class="notification-badge"><?=min($nc,99)?></span><?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto">
          <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0">Notifications</h6>
            <a href="#" class="small text-primary" onclick="markAllRead(event)">Mark all read</a>
          </div>
          <div id="notificationList"><div class="text-center text-muted py-3 small">Loading...</div></div>
        </div>
      </div>
      <?php $u=authUser(); ?>
      <div class="dropdown">
        <button class="btn btn-link p-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown">
          <div class="avatar avatar-sm"><?=strtoupper(substr($u['username']??'U',0,2))?></div>
          <span class="d-none d-md-block small fw-semibold"><?=e($u['username']??'')?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li class="px-3 py-2"><div class="fw-semibold"><?=e($u['username']??'')?></div><small class="text-muted"><?=e($u['role_name']??'')?></small></li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2 text-muted"></i>My Profile</a></li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><form action="/logout" method="POST" style="margin:0"><?=csrf_field()?><button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</button></form></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="page-content">
    <?php if($flash=\App\Core\Session::getFlash('success')): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?=e($flash)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if($flash=\App\Core\Session::getFlash('error')): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?=e($flash)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if($flash=\App\Core\Session::getFlash('warning')): ?><div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?=e($flash)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?= $content ?? '' ?>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
