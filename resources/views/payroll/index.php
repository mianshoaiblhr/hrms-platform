<?php $pageTitle = 'Payroll Management'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Payroll Management</h4><p class="text-muted mb-0 small">Process and manage employee salaries</p></div>
  <div class="d-flex gap-2">
    <?php if(can('payroll.process')): ?>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newPeriodModal">
      <i class="fas fa-plus me-1"></i>New Payroll Period
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card stat-primary">
      <div class="card-body">
        <div class="stat-value"><?= formatCurrency($stats['total_gross'] ?? 0) ?></div>
        <div class="stat-label">Total Gross (This Month)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card stat-success">
      <div class="card-body">
        <div class="stat-value"><?= formatCurrency($stats['total_net'] ?? 0) ?></div>
        <div class="stat-label">Total Net Payable</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card stat-warning">
      <div class="card-body">
        <div class="stat-value"><?= formatCurrency($stats['total_tax'] ?? 0) ?></div>
        <div class="stat-label">Total Tax (FBR)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card stat-info">
      <div class="card-body">
        <div class="stat-value"><?= $stats['total_employees'] ?? 0 ?></div>
        <div class="stat-label">Employees Processed</div>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" action="/payroll" class="row g-2 align-items-center">
      <div class="col-md-3">
        <select name="month" class="form-select form-select-sm">
          <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= ($filters['month'] ?? date('n')) == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="year" class="form-select form-select-sm">
          <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
          <option value="<?= $y ?>" <?= ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="processed" <?= ($filters['status'] ?? '') === 'processed' ? 'selected' : '' ?>>Processed</option>
          <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filter</button>
      </div>
    </form>
  </div>
</div>

<!-- Payroll Periods Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Period</th><th>Employees</th><th>Gross Salary</th>
            <th>Deductions</th><th>Net Payable</th><th>Status</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($periods['data'])): ?>
          <tr><td colspan="7" class="text-center text-muted py-5">
            <i class="fas fa-file-invoice-dollar fa-2x mb-2 d-block opacity-25"></i>No payroll periods found
          </td></tr>
          <?php else: foreach ($periods['data'] as $p): ?>
          <tr>
            <td>
              <div class="fw-semibold small"><?= e($p['period_label'] ?? $p['payroll_month'] ?? '—') ?></div>
              <div class="x-small text-muted"><?= formatDate($p['created_at'] ?? '') ?></div>
            </td>
            <td class="small"><?= $p['employee_count'] ?? 0 ?></td>
            <td class="small fw-semibold"><?= formatCurrency($p['total_gross'] ?? 0) ?></td>
            <td class="small text-danger"><?= formatCurrency($p['total_deductions'] ?? 0) ?></td>
            <td class="small fw-bold text-success"><?= formatCurrency($p['total_net'] ?? 0) ?></td>
            <td><?= statusBadge($p['status'] ?? 'draft') ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="/payroll/<?= $p['id'] ?>" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                <?php if(can('payroll.approve') && ($p['status'] ?? '') === 'processed'): ?>
                <form method="POST" action="/payroll/<?= $p['id'] ?>/approve" class="d-inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-outline-success" title="Approve" data-confirm="Approve this payroll?"><i class="fas fa-check"></i></button>
                </form>
                <?php endif; ?>
                <a href="/payroll/<?= $p['id'] ?>/export" class="btn btn-outline-secondary" title="Export"><i class="fas fa-download"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if (($periods['last_page'] ?? 1) > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= count($periods['data'] ?? []) ?> of <?= $periods['total'] ?> periods</small>
    <?= paginator($periods['total'], $periods['per_page'], $periods['current_page'], '/payroll') ?>
  </div>
  <?php endif; ?>
</div>

<!-- New Period Modal -->
<?php if(can('payroll.process')): ?>
<div class="modal fade" id="newPeriodModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header border-0">
      <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Create Payroll Period</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" action="/payroll/create">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Month *</label>
            <select name="month" class="form-select" required>
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= date('n') == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Year *</label>
            <select name="year" class="form-select" required>
              <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
              <option value="<?= $y ?>" <?= date('Y') == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Department (optional)</label>
            <select name="department_id" class="form-select">
              <option value="">All Departments</option>
              <?php foreach ($departments ?? [] as $d): ?>
              <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <div class="alert alert-info small mb-0">
              <i class="fas fa-info-circle me-1"></i>
              Payroll will be calculated using FBR 2024-25 tax slabs, EOBI (PKR 320 employee / 1600 employer), and PESSI contributions.
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-cogs me-1"></i>Process Payroll</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
