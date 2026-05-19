<?php $pageTitle = 'Salary Advances'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Salary Advances</h4><p class="text-muted mb-0 small">Manage employee salary advance requests</p></div>
  <?php if(can('advances.create')): ?>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestModal">
    <i class="fas fa-plus me-1"></i>Request Advance
  </button>
  <?php endif; ?>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4"><div class="card text-center py-3"><div class="fw-bold h5 text-warning mb-0"><?= $stats['pending'] ?? 0 ?></div><div class="small text-muted">Pending</div></div></div>
  <div class="col-6 col-md-4"><div class="card text-center py-3"><div class="fw-bold h5 text-success mb-0"><?= $stats['approved'] ?? 0 ?></div><div class="small text-muted">Approved</div></div></div>
  <div class="col-12 col-md-4"><div class="card text-center py-3"><div class="fw-bold h5 text-danger mb-0"><?= formatCurrency($stats['total_outstanding'] ?? 0) ?></div><div class="small text-muted">Total Outstanding</div></div></div>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Employee name..." value="<?= e($filters['search'] ?? '') ?>"></div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="pending"  <?= ($filters['status'] ?? '') === 'pending'  ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
          <option value="cleared"  <?= ($filters['status'] ?? '') === 'cleared'  ? 'selected' : '' ?>>Cleared</option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
        <a href="/advances" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr><th>Employee</th><th>Amount</th><th>Monthly Ded.</th><th>Balance</th><th>Repay In</th><th>Reason</th><th>Requested</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($data['data'])): ?>
          <tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-hand-holding-usd fa-2x d-block mb-2 opacity-25"></i>No advance records</td></tr>
          <?php else: foreach ($data['data'] as $adv): ?>
          <tr>
            <td>
              <div class="fw-semibold small"><?= e($adv['employee_name'] ?? '—') ?></div>
              <div class="x-small text-muted"><?= e($adv['employee_code'] ?? '') ?></div>
            </td>
            <td class="small fw-semibold"><?= formatCurrency($adv['amount']) ?></td>
            <td class="small text-warning"><?= formatCurrency($adv['monthly_deduction'] ?? 0) ?></td>
            <td class="small <?= ($adv['balance_amount'] ?? 0) > 0 ? 'text-danger fw-semibold' : 'text-success' ?>"><?= formatCurrency($adv['balance_amount'] ?? 0) ?></td>
            <td class="small"><?= $adv['repay_months'] ?? 1 ?> month<?= ($adv['repay_months'] ?? 1) > 1 ? 's' : '' ?></td>
            <td class="small text-muted" style="max-width:140px"><div class="text-truncate"><?= e($adv['reason'] ?? '—') ?></div></td>
            <td class="small text-muted"><?= formatDate($adv['request_date'] ?? '') ?></td>
            <td><?= statusBadge($adv['status'] ?? 'pending') ?></td>
            <td class="text-end">
              <?php if(can('advances.approve') && $adv['status'] === 'pending'): ?>
              <div class="btn-group btn-group-sm">
                <form method="POST" action="/advances/<?= $adv['id'] ?>/approve" class="d-inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-outline-success btn-xs" data-confirm="Approve this advance?"><i class="fas fa-check"></i></button>
                </form>
                <form method="POST" action="/advances/<?= $adv['id'] ?>/reject" class="d-inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-outline-danger btn-xs" data-confirm="Reject this advance?"><i class="fas fa-times"></i></button>
                </form>
              </div>
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
    <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/advances') ?>
  </div>
  <?php endif; ?>
</div>

<!-- Request Advance Modal -->
<div class="modal fade" id="requestModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title"><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Request Salary Advance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="/advances"><div class="modal-body"><?= csrf_field() ?>
    <div class="row g-3">
      <?php if(can('advances.create_for_others')): ?>
      <div class="col-12"><label class="form-label small fw-semibold">Employee *</label>
        <select name="employee_id" class="form-select" required>
          <?php foreach ($employees ?? [] as $emp): ?>
          <option value="<?= $emp['id'] ?>"><?= e($emp['employee_code'] . ' — ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-6"><label class="form-label small fw-semibold">Amount (PKR) *</label><input type="number" name="amount" class="form-control" required min="1000" step="100"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Repay In (Months) *</label>
        <select name="repay_months" class="form-select" required>
          <?php for($i=1;$i<=12;$i++): ?><option value="<?=$i?>"><?=$i?> month<?=$i>1?'s':''?></option><?php endfor; ?>
        </select>
      </div>
      <div class="col-12"><label class="form-label small fw-semibold">Reason *</label><textarea name="reason" class="form-control" rows="2" required placeholder="Purpose of advance..."></textarea></div>
      <div class="col-12"><div class="alert alert-info small mb-0"><i class="fas fa-info-circle me-1"></i>Advance will be deducted from monthly salary upon approval.</div></div>
    </div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i>Submit Request</button></div>
  </form>
</div></div></div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
