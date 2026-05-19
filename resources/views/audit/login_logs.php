<?php $pageTitle = 'Login Logs'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold">Login Logs</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
      <li class="breadcrumb-item"><a href="/audit">Audit Logs</a></li>
      <li class="breadcrumb-item active">Login Logs</li>
    </ol></nav>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="card text-center py-2"><div class="fw-bold h5 text-success mb-0"><?= $stats['successful_today'] ?? 0 ?></div><div class="small text-muted">Successful Today</div></div></div>
  <div class="col-6 col-md-3"><div class="card text-center py-2"><div class="fw-bold h5 text-danger mb-0"><?= $stats['failed_today'] ?? 0 ?></div><div class="small text-muted">Failed Today</div></div></div>
  <div class="col-6 col-md-3"><div class="card text-center py-2"><div class="fw-bold h5 text-warning mb-0"><?= $stats['unique_ips'] ?? 0 ?></div><div class="small text-muted">Unique IPs Today</div></div></div>
  <div class="col-6 col-md-3"><div class="card text-center py-2"><div class="fw-bold h5 text-info mb-0"><?= $stats['active_sessions'] ?? 0 ?></div><div class="small text-muted">Active Sessions</div></div></div>
</div>

<!-- Filter -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="Username or IP..." value="<?= e($filters['search'] ?? '') ?>"></div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All</option>
          <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
          <option value="failed"  <?= ($filters['status'] ?? '') === 'failed'  ? 'selected' : '' ?>>Failed</option>
          <option value="locked"  <?= ($filters['status'] ?? '') === 'locked'  ? 'selected' : '' ?>>Locked</option>
        </select>
      </div>
      <div class="col-md-2"><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? date('Y-m-01')) ?>"></div>
      <div class="col-md-2"><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? date('Y-m-d')) ?>"></div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
        <a href="/audit/login-logs" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr><th>Timestamp</th><th>User</th><th>IP Address</th><th>Browser/Device</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($data['data'])): ?>
          <tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-sign-in-alt fa-2x d-block mb-2 opacity-25"></i>No login records found</td></tr>
          <?php else: foreach ($data['data'] as $log): ?>
          <tr>
            <td class="small text-muted"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
            <td><div class="small fw-semibold"><?= e($log['username'] ?? '—') ?></div></td>
            <td class="small font-monospace"><?= e($log['ip_address'] ?? '—') ?></td>
            <td class="small text-muted" style="max-width:200px">
              <div class="text-truncate" title="<?= e($log['user_agent'] ?? '') ?>"><?= e(substr($log['user_agent'] ?? '—', 0, 50)) ?></div>
            </td>
            <td>
              <?php if (($log['status'] ?? '') === 'success'): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check me-1"></i>Success</span>
              <?php elseif (($log['status'] ?? '') === 'locked'): ?>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fas fa-lock me-1"></i>Locked</span>
              <?php else: ?>
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-times me-1"></i>Failed</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if (($data['last_page'] ?? 1) > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= count($data['data'] ?? []) ?> of <?= $data['total'] ?> records</small>
    <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/audit/login-logs') ?>
  </div>
  <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
