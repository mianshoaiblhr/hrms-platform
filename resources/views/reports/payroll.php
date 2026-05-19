<?php $pageTitle = 'Payroll Report'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h4 class="fw-bold mb-1">Payroll Report</h4><p class="text-muted small mb-0">Salary disbursement summary</p></div>
  <?php if (!empty($data)): ?>
  <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="btn btn-success btn-sm">
    <i class="fas fa-file-csv me-1"></i>Export CSV
  </a>
  <?php endif; ?>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-3"><label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= e($from ?? date('Y-m-01')) ?>"></div>
      <div class="col-md-3"><label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= e($to ?? date('Y-m-d')) ?>"></div>
      <div class="col-md-3"><label class="form-label">Department</label>
        <select name="dept" class="form-select">
          <option value="">All Departments</option>
          <?php foreach ($departments ?? [] as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($dept??0)==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Generate</button>
      </div>
    </form>
  </div>
</div>

<?php if (empty($data ?? [])): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
  <i class="fas fa-chart-bar fa-3x mb-3 opacity-25 d-block"></i>
  No payroll data found for selected period.
</div></div>
<?php else: ?>
<?php
  $totGross = array_sum(array_column($data,'gross_salary'));
  $totDed   = array_sum(array_column($data,'total_deductions'));
  $totNet   = array_sum(array_column($data,'net_salary'));
?>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card text-center">
    <div class="stat-value text-success">PKR <?= number_format($totGross) ?></div>
    <div class="stat-label">Total Gross</div>
  </div></div>
  <div class="col-md-4"><div class="stat-card text-center">
    <div class="stat-value text-danger">PKR <?= number_format($totDed) ?></div>
    <div class="stat-label">Total Deductions</div>
  </div></div>
  <div class="col-md-4"><div class="stat-card text-center">
    <div class="stat-value text-primary">PKR <?= number_format($totNet) ?></div>
    <div class="stat-label">Total Net Payable</div>
  </div></div>
</div>
<div class="card"><div class="card-body p-0">
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Emp Code</th><th>Name</th><th>Department</th><th>Period</th>
      <th class="text-end">Gross</th><th class="text-end">Deductions</th>
      <th class="text-end">Net</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($data as $r): ?>
    <tr>
      <td><code><?= e($r['employee_code']) ?></code></td>
      <td class="fw-semibold"><?= e($r['name']) ?></td>
      <td><?= e($r['dept'] ?? '—') ?></td>
      <td><?= e($r['period_name'] ?? '—') ?></td>
      <td class="text-end"><?= number_format($r['gross_salary'] ?? 0) ?></td>
      <td class="text-end text-danger"><?= number_format($r['total_deductions'] ?? 0) ?></td>
      <td class="text-end fw-semibold text-success"><?= number_format($r['net_salary'] ?? 0) ?></td>
      <td><span class="badge status-<?= $r['status']??'draft' ?>"><?= ucfirst($r['status']??'draft') ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot class="table-light"><tr>
      <td colspan="4" class="fw-bold">TOTAL</td>
      <td class="text-end fw-bold"><?= number_format($totGross) ?></td>
      <td class="text-end fw-bold text-danger"><?= number_format($totDed) ?></td>
      <td class="text-end fw-bold text-success">PKR <?= number_format($totNet) ?></td>
      <td></td>
    </tr></tfoot>
  </table></div>
</div></div>
<?php endif; ?>
