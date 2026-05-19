<?php $pageTitle = 'Audit Logs'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Audit Logs</h4><p class="text-muted mb-0 small">System activity and change history</p></div>
  <div class="d-flex gap-2">
    <a href="/audit/login-logs" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sign-in-alt me-1"></i>Login Logs</a>
    <a href="/audit/export?<?= http_build_query($filters ?? []) ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-download me-1"></i>Export</a>
  </div>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" action="/audit" class="row g-2 align-items-center">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search action, table, user..." value="<?= e($filters['search'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <select name="action" class="form-select form-select-sm">
          <option value="">All Actions</option>
          <option value="create" <?= ($filters['action'] ?? '') === 'create' ? 'selected' : '' ?>>Create</option>
          <option value="update" <?= ($filters['action'] ?? '') === 'update' ? 'selected' : '' ?>>Update</option>
          <option value="delete" <?= ($filters['action'] ?? '') === 'delete' ? 'selected' : '' ?>>Delete</option>
          <option value="login"  <?= ($filters['action'] ?? '') === 'login'  ? 'selected' : '' ?>>Login</option>
          <option value="logout" <?= ($filters['action'] ?? '') === 'logout' ? 'selected' : '' ?>>Logout</option>
          <option value="approve" <?= ($filters['action'] ?? '') === 'approve' ? 'selected' : '' ?>>Approve</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? date('Y-m-01')) ?>">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
        <a href="/audit" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Audit Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th style="width:160px">Timestamp</th>
            <th>User</th>
            <th>Action</th>
            <th>Module / Record</th>
            <th>Changes</th>
            <th>IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data['data'])): ?>
          <tr><td colspan="6" class="text-center py-5 text-muted">
            <i class="fas fa-history fa-2x mb-2 d-block opacity-25"></i>No audit records found
          </td></tr>
          <?php else: foreach ($data['data'] as $log): ?>
          <tr>
            <td class="small text-muted"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
            <td>
              <div class="small fw-semibold"><?= e($log['user_name'] ?? 'System') ?></div>
              <div class="x-small text-muted"><?= e($log['user_role'] ?? '') ?></div>
            </td>
            <td>
              <?php
              $actionColors = [
                'create'  => 'success',
                'update'  => 'primary',
                'delete'  => 'danger',
                'login'   => 'info',
                'logout'  => 'secondary',
                'approve' => 'warning',
                'reject'  => 'danger',
              ];
              $color = $actionColors[$log['action']] ?? 'secondary';
              ?>
              <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> border border-<?= $color ?>-subtle">
                <?= ucfirst($log['action']) ?>
              </span>
            </td>
            <td>
              <div class="small fw-semibold"><?= ucfirst(str_replace('_', ' ', $log['table_name'] ?? '—')) ?></div>
              <?php if (!empty($log['record_id'])): ?>
              <div class="x-small text-muted">ID: <?= $log['record_id'] ?></div>
              <?php endif; ?>
            </td>
            <td style="max-width:260px">
              <?php if (!empty($log['changes'])): ?>
              <?php $changes = is_string($log['changes']) ? json_decode($log['changes'], true) : $log['changes']; ?>
              <?php if (!empty($changes) && is_array($changes)): ?>
              <button class="btn btn-outline-secondary btn-xs" onclick="showChanges(<?= htmlspecialchars(json_encode($changes)) ?>)">
                <i class="fas fa-code me-1"></i><?= count($changes) ?> field<?= count($changes) > 1 ? 's' : '' ?> changed
              </button>
              <?php else: ?>
              <span class="small text-muted"><?= e(substr($log['changes'], 0, 60)) ?></span>
              <?php endif; ?>
              <?php else: ?>
              <span class="x-small text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted font-monospace"><?= e($log['ip_address'] ?? '—') ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if (($data['last_page'] ?? 1) > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= count($data['data'] ?? []) ?> of <?= $data['total'] ?> records</small>
    <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/audit') ?>
  </div>
  <?php endif; ?>
</div>

<!-- Changes Detail Modal -->
<div class="modal fade" id="changesModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header border-0">
      <h6 class="modal-title fw-bold"><i class="fas fa-code me-2 text-primary"></i>Field Changes</h6>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <table class="table table-sm" id="changesTable">
        <thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
        <tbody id="changesBody"></tbody>
      </table>
    </div>
  </div></div>
</div>

<script>
function showChanges(changes) {
  const body = document.getElementById('changesBody');
  body.innerHTML = '';
  Object.entries(changes).forEach(([field, vals]) => {
    const before = vals.old ?? vals.before ?? '—';
    const after  = vals.new ?? vals.after  ?? '—';
    body.innerHTML += `<tr>
      <td class="small fw-semibold">${field.replace(/_/g,' ')}</td>
      <td class="small text-danger">${String(before).substring(0,100)}</td>
      <td class="small text-success">${String(after).substring(0,100)}</td>
    </tr>`;
  });
  new bootstrap.Modal(document.getElementById('changesModal')).show();
}
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
