<?php $pageTitle = 'Leave Management'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Leave Management</h4><p class="text-muted mb-0 small">Manage employee leave applications</p></div>
  <div class="d-flex gap-2">
    <a href="/leaves/balances" class="btn btn-outline-info btn-sm"><i class="fas fa-balance-scale me-1"></i>Balances</a>
    <a href="/leaves/calendar" class="btn btn-outline-secondary btn-sm"><i class="fas fa-calendar-alt me-1"></i>Calendar</a>
    <a href="/leaves/apply" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Apply Leave</a>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php
  $leaveStats = [
    ['label'=>'Pending Approval','value'=>$stats['pending']??0,'class'=>'warning','icon'=>'hourglass-half'],
    ['label'=>'Approved','value'=>$stats['approved']??0,'class'=>'success','icon'=>'check-circle'],
    ['label'=>'Rejected','value'=>$stats['rejected']??0,'class'=>'danger','icon'=>'times-circle'],
    ['label'=>'This Month','value'=>$stats['this_month']??0,'class'=>'info','icon'=>'calendar'],
  ];
  foreach ($leaveStats as $s):
  ?>
  <div class="col-6 col-md-3">
    <div class="card stat-card stat-<?= $s['class'] ?>">
      <div class="card-body">
        <div class="stat-value"><?= $s['value'] ?></div>
        <div class="stat-label"><?= $s['label'] ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" action="/leaves" class="row g-2 align-items-center">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Employee name..." value="<?= e($filters['search'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
          <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="leave_type_id" class="form-select form-select-sm">
          <option value="">All Types</option>
          <?php foreach ($leaveTypes ?? [] as $lt): ?>
          <option value="<?= $lt['id'] ?>" <?= ($filters['leave_type_id'] ?? '') == $lt['id'] ? 'selected' : '' ?>><?= e($lt['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
        <a href="/leaves" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Leave Applications Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Employee</th><th>Leave Type</th><th>From</th><th>To</th>
            <th>Days</th><th>Reason</th><th>Applied</th><th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data['data'])): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="fas fa-umbrella-beach fa-2x mb-2 d-block opacity-25"></i>No leave applications found
          </td></tr>
          <?php else: foreach ($data['data'] as $leave): ?>
          <tr>
            <td>
              <div class="fw-semibold small"><?= e($leave['employee_name'] ?? '—') ?></div>
              <div class="x-small text-muted"><?= e($leave['department_name'] ?? '') ?></div>
            </td>
            <td>
              <span class="badge bg-primary-subtle text-primary"><?= e($leave['leave_type_name'] ?? '—') ?></span>
            </td>
            <td class="small"><?= formatDate($leave['start_date']) ?></td>
            <td class="small"><?= formatDate($leave['end_date']) ?></td>
            <td><span class="badge bg-secondary"><?= $leave['days'] ?> day<?= $leave['days'] > 1 ? 's' : '' ?></span></td>
            <td class="small text-muted" style="max-width:160px">
              <div class="text-truncate" title="<?= e($leave['reason'] ?? '') ?>"><?= e($leave['reason'] ?? '—') ?></div>
            </td>
            <td class="small text-muted"><?= formatDate($leave['created_at'] ?? '') ?></td>
            <td><?= statusBadge($leave['status']) ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if(can('leaves.approve') && $leave['status'] === 'pending'): ?>
                <form method="POST" action="/leaves/<?= $leave['id'] ?>/approve" class="d-inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-outline-success" title="Approve" data-confirm="Approve this leave?"><i class="fas fa-check"></i></button>
                </form>
                <button type="button" class="btn btn-outline-danger" title="Reject" onclick="rejectLeave(<?= $leave['id'] ?>)"><i class="fas fa-times"></i></button>
                <?php endif; ?>
                <?php if($leave['status'] === 'pending' && (authUser()['employee_id'] ?? 0) == $leave['employee_id']): ?>
                <form method="POST" action="/leaves/<?= $leave['id'] ?>/cancel" class="d-inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-outline-secondary" title="Cancel" data-confirm="Cancel this leave?"><i class="fas fa-ban"></i></button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if (($data['last_page'] ?? 1) > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= count($data['data'] ?? []) ?> of <?= $data['total'] ?> applications</small>
    <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/leaves') ?>
  </div>
  <?php endif; ?>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <div class="modal-header border-0"><h6 class="modal-title text-danger"><i class="fas fa-times-circle me-2"></i>Reject Leave</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" id="rejectForm">
      <?= csrf_field() ?>
      <div class="modal-body">
        <label class="form-label small">Rejection Reason *</label>
        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Reason for rejection..."></textarea>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
      </div>
    </form>
  </div></div>
</div>

<script>
function rejectLeave(id) {
  document.getElementById('rejectForm').action = '/leaves/' + id + '/reject';
  new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
