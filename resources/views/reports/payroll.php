<?php $pageTitle = 'Payroll Report'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold">Payroll Report</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
      <li class="breadcrumb-item"><a href="/reports">Reports</a></li>
      <li class="breadcrumb-item active">Payroll</li>
    </ol></nav>
  </div>
  <div class="d-flex gap-2">
    <a href="/reports/payroll/export?<?= http_build_query($filters ?? []) ?>" class="btn btn-success btn-sm">
      <i class="fas fa-file-excel me-1"></i>Export Excel
    </a>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-md-2">
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
      <div class="col-md-2">
        <select name="department_id" class="form-select form-select-sm">
          <option value="">All Departments</option>
          <?php foreach ($departments ?? [] as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Generate</button>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($report)): ?>
<!-- Summary -->
<div class="row g-3 mb-4">
  <?php
  $rStats = [
    ['label'=>'Total Employees','value'=>$report['summary']['total_employees']??0,'class'=>'primary'],
    ['label'=>'Total Gross','value'=>formatCurrency($report['summary']['total_gross']??0),'class'=>'warning'],
    ['label'=>'Total Deductions','value'=>formatCurrency($report['summary']['total_deductions']??0),'class'=>'danger'],
    ['label'=>'Total Net','value'=>formatCurrency($report['summary']['total_net']??0),'class'=>'success'],
    ['label'=>'Total Tax (FBR)','value'=>formatCurrency($report['summary']['total_tax']??0),'class'=>'info'],
    ['label'=>'EOBI (Employer)','value'=>formatCurrency($report['summary']['total_eobi']??0),'class'=>'secondary'],
  ];
  foreach ($rStats as $s):
  ?>
  <div class="col-6 col-md-2">
    <div class="card text-center py-2 border-<?= $s['class'] ?>">
      <div class="fw-bold small text-<?= $s['class'] ?>"><?= $s['value'] ?></div>
      <div class="x-small text-muted"><?= $s['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Detailed Table -->
<div class="card" id="printSection">
  <div class="card-header py-2 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 small fw-bold">Payroll Details — <?= date('F Y', mktime(0, 0, 0, $filters['month'] ?? date('n'), 1, $filters['year'] ?? date('Y'))) ?></h6>
    <span class="small text-muted"><?= $report['summary']['total_employees'] ?? 0 ?> employees</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Employee</th><th>Department</th><th>Basic</th>
            <th>Allowances</th><th>Gross</th><th>Tax</th><th>EOBI</th>
            <th>PESSI</th><th>Other Ded.</th><th class="text-success">Net</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($report['rows'] ?? [] as $row): ?>
          <tr>
            <td class="small text-muted"><?= $i++ ?></td>
            <td>
              <div class="small fw-semibold"><?= e($row['employee_name'] ?? '—') ?></div>
              <div class="x-small text-muted"><?= e($row['employee_code'] ?? '') ?></div>
            </td>
            <td class="small"><?= e($row['department_name'] ?? '—') ?></td>
            <td class="small"><?= formatCurrency($row['basic_salary'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($row['total_allowances'] ?? 0) ?></td>
            <td class="small fw-semibold"><?= formatCurrency($row['gross_salary'] ?? 0) ?></td>
            <td class="small text-danger"><?= formatCurrency($row['income_tax'] ?? 0) ?></td>
            <td class="small text-warning"><?= formatCurrency($row['eobi_employee'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($row['pessi_employee'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency(($row['loan_deduction'] ?? 0) + ($row['advance_deduction'] ?? 0)) ?></td>
            <td class="small fw-bold text-success"><?= formatCurrency($row['net_salary'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-secondary fw-bold">
          <tr>
            <td colspan="3" class="text-end small">Totals</td>
            <td class="small"><?= formatCurrency($report['summary']['total_basic'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($report['summary']['total_allowances'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($report['summary']['total_gross'] ?? 0) ?></td>
            <td class="small text-danger"><?= formatCurrency($report['summary']['total_tax'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($report['summary']['total_eobi_emp'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($report['summary']['total_pessi_emp'] ?? 0) ?></td>
            <td class="small"><?= formatCurrency($report['summary']['total_other_ded'] ?? 0) ?></td>
            <td class="small text-success"><?= formatCurrency($report['summary']['total_net'] ?? 0) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
  <i class="fas fa-chart-bar fa-3x mb-3 opacity-25 d-block"></i>
  Select filters and click Generate to view report
</div></div>
<?php endif; ?>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
