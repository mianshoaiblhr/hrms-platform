<?php $pageTitle = 'Payroll Period: ' . ($period['period_label'] ?? ''); ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold">Payroll Period: <?= e($period['period_label'] ?? '') ?></h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
      <li class="breadcrumb-item"><a href="/payroll">Payroll</a></li>
      <li class="breadcrumb-item active"><?= e($period['period_label'] ?? '') ?></li>
    </ol></nav>
  </div>
  <div class="d-flex gap-2">
    <?php if(can('payroll.approve') && ($period['status'] ?? '') === 'processed'): ?>
    <form method="POST" action="/payroll/<?= $period['id'] ?>/approve" class="d-inline">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-success btn-sm" data-confirm="Approve this payroll period?">
        <i class="fas fa-check me-1"></i>Approve
      </button>
    </form>
    <?php endif; ?>
    <a href="/payroll/<?= $period['id'] ?>/export" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-file-excel me-1"></i>Export Excel
    </a>
    <a href="/payroll" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
  </div>
</div>

<!-- Period Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-2">
    <div class="card text-center py-3">
      <div class="fw-bold h5 mb-0 text-primary"><?= $period['employee_count'] ?? 0 ?></div>
      <div class="small text-muted">Employees</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-3">
      <div class="fw-bold h6 mb-0 text-success"><?= formatCurrency($period['total_basic'] ?? 0) ?></div>
      <div class="small text-muted">Basic Salary</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-3">
      <div class="fw-bold h6 mb-0 text-info"><?= formatCurrency($period['total_allowances'] ?? 0) ?></div>
      <div class="small text-muted">Allowances</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-3">
      <div class="fw-bold h6 mb-0 text-warning"><?= formatCurrency($period['total_gross'] ?? 0) ?></div>
      <div class="small text-muted">Gross Salary</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-3">
      <div class="fw-bold h6 mb-0 text-danger"><?= formatCurrency($period['total_deductions'] ?? 0) ?></div>
      <div class="small text-muted">Deductions</div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card text-center py-3 border-success">
      <div class="fw-bold h6 mb-0 text-success"><?= formatCurrency($period['total_net'] ?? 0) ?></div>
      <div class="small text-muted">Net Payable</div>
    </div>
  </div>
</div>

<!-- Deductions Breakdown -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header py-2"><h6 class="mb-0 small fw-bold"><i class="fas fa-tax me-1 text-warning"></i>Tax Summary</h6></div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr><td class="small text-muted">Income Tax (FBR)</td><td class="text-end fw-semibold"><?= formatCurrency($period['total_income_tax'] ?? 0) ?></td></tr>
          <tr><td class="small text-muted">EOBI (Employee)</td><td class="text-end fw-semibold"><?= formatCurrency($period['total_eobi_employee'] ?? 0) ?></td></tr>
          <tr><td class="small text-muted">EOBI (Employer)</td><td class="text-end fw-semibold"><?= formatCurrency($period['total_eobi_employer'] ?? 0) ?></td></tr>
          <tr><td class="small text-muted">PESSI (Employee)</td><td class="text-end fw-semibold"><?= formatCurrency($period['total_pessi_employee'] ?? 0) ?></td></tr>
          <tr class="table-light"><td class="small fw-bold">Total</td><td class="text-end fw-bold text-danger"><?= formatCurrency(($period['total_income_tax'] ?? 0) + ($period['total_eobi_employee'] ?? 0) + ($period['total_pessi_employee'] ?? 0)) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header py-2"><h6 class="mb-0 small fw-bold"><i class="fas fa-chart-pie me-1 text-primary"></i>Allowances</h6></div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr><td class="small text-muted">House Rent</td><td class="text-end"><?= formatCurrency($period['total_house_rent'] ?? 0) ?></td></tr>
          <tr><td class="small text-muted">Medical</td><td class="text-end"><?= formatCurrency($period['total_medical'] ?? 0) ?></td></tr>
          <tr><td class="small text-muted">Transport</td><td class="text-end"><?= formatCurrency($period['total_transport'] ?? 0) ?></td></tr>
          <tr><td class="small text-muted">Overtime</td><td class="text-end"><?= formatCurrency($period['total_overtime'] ?? 0) ?></td></tr>
          <tr class="table-light"><td class="small fw-bold">Total</td><td class="text-end fw-bold text-success"><?= formatCurrency($period['total_allowances'] ?? 0) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header py-2"><h6 class="mb-0 small fw-bold"><i class="fas fa-info-circle me-1 text-info"></i>Period Info</h6></div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr><td class="small text-muted">Status</td><td class="text-end"><?= statusBadge($period['status'] ?? 'draft') ?></td></tr>
          <tr><td class="small text-muted">Created</td><td class="text-end small"><?= formatDate($period['created_at'] ?? '') ?></td></tr>
          <?php if(!empty($period['approved_at'])): ?>
          <tr><td class="small text-muted">Approved</td><td class="text-end small"><?= formatDate($period['approved_at']) ?></td></tr>
          <?php endif; ?>
          <tr><td class="small text-muted">Working Days</td><td class="text-end small"><?= $period['working_days'] ?? '—' ?></td></tr>
          <tr><td class="small text-muted">PF (Employer)</td><td class="text-end small"><?= formatCurrency($period['total_pf_employer'] ?? 0) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Employee Payslips Table -->
<div class="card">
  <div class="card-header py-2 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 small fw-bold">Employee Payslips</h6>
    <input type="text" id="payslipSearch" class="form-control form-control-sm" style="width:200px" placeholder="Search employee...">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="payslipTable">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th><th>Basic</th><th>Allowances</th>
            <th>Gross</th><th>Tax</th><th>EOBI</th><th>Other Ded.</th><th>Net</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($payslips)): ?>
          <tr><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-file-invoice fa-2x d-block mb-2 opacity-25"></i>No payslips found</td></tr>
          <?php else: foreach ($payslips as $ps): ?>
          <tr>
            <td>
              <div class="fw-semibold small"><?= e($ps['employee_name'] ?? '—') ?></div>
              <div class="x-small text-muted"><?= e($ps['employee_code'] ?? '') ?></div>
            </td>
            <td class="small"><?= e($ps['department_name'] ?? '—') ?></td>
            <td class="small"><?= formatCurrency($ps['basic_salary'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($ps['total_allowances'] ?? 0) ?></td>
            <td class="small fw-semibold"><?= formatCurrency($ps['gross_salary'] ?? 0) ?></td>
            <td class="small text-danger"><?= formatCurrency($ps['income_tax'] ?? 0) ?></td>
            <td class="small text-warning"><?= formatCurrency($ps['eobi_employee'] ?? 0) ?></td>
            <td class="small text-danger"><?= formatCurrency(($ps['loan_deduction'] ?? 0) + ($ps['advance_deduction'] ?? 0) + ($ps['other_deductions'] ?? 0)) ?></td>
            <td class="small fw-bold text-success"><?= formatCurrency($ps['net_salary'] ?? 0) ?></td>
            <td class="text-end">
              <a href="/payroll/payslip/<?= $ps['id'] ?>" class="btn btn-outline-primary btn-xs" title="View Payslip"><i class="fas fa-file-alt"></i></a>
              <a href="/payroll/payslip/<?= $ps['id'] ?>/print" target="_blank" class="btn btn-outline-secondary btn-xs" title="Print"><i class="fas fa-print"></i></a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.getElementById('payslipSearch')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#payslipTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
