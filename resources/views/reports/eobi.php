<?php $pageTitle = 'EOBI Report'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h4 class="fw-bold mb-1">EOBI Contribution Report</h4><p class="text-muted small mb-0">Employee & employer EOBI contributions</p></div>
  <?php if (!empty($data)): ?>
  <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="btn btn-success btn-sm">
    <i class="fas fa-file-csv me-1"></i>Export CSV
  </a>
  <?php endif; ?>
</div>
<div class="card mb-4"><div class="card-body">
  <form method="GET" class="row g-3">
    <div class="col-md-3"><label class="form-label">From</label>
      <input type="date" name="from" class="form-control" value="<?= e($from ?? date('Y-m-01')) ?>"></div>
    <div class="col-md-3"><label class="form-label">To</label>
      <input type="date" name="to" class="form-control" value="<?= e($to ?? date('Y-m-d')) ?>"></div>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Generate</button>
    </div>
  </form>
</div></div>
<?php if (empty($data ?? [])): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
  <i class="fas fa-shield-halved fa-3x mb-3 opacity-25 d-block"></i>No EOBI data found for selected period.
</div></div>
<?php else: ?>
<?php $totEmp = array_sum(array_column($data,'employee_contribution'));
      $totEr  = array_sum(array_column($data,'employer_contribution')); ?>
<div class="row g-3 mb-4">
  <div class="col-md-6"><div class="stat-card text-center">
    <div class="stat-value text-primary">PKR <?= number_format($totEmp) ?></div>
    <div class="stat-label">Employee Contribution</div>
  </div></div>
  <div class="col-md-6"><div class="stat-card text-center">
    <div class="stat-value text-success">PKR <?= number_format($totEr) ?></div>
    <div class="stat-label">Employer Contribution</div>
  </div></div>
</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0">
  <thead><tr><th>Emp Code</th><th>Name</th><th>CNIC</th><th>EOBI No.</th><th>DOB</th>
    <th class="text-end">Employee</th><th class="text-end">Employer</th><th class="text-end">Total</th></tr></thead>
  <tbody>
  <?php foreach ($data as $r): ?>
  <tr>
    <td><code><?= e($r['employee_code']) ?></code></td>
    <td class="fw-semibold"><?= e($r['name']) ?></td>
    <td><?= e($r['cnic'] ?? '—') ?></td>
    <td><?= e($r['eobi_number'] ?? '—') ?></td>
    <td><?= $r['date_of_birth'] ? date('d M Y', strtotime($r['date_of_birth'])) : '—' ?></td>
    <td class="text-end"><?= number_format($r['employee_contribution'] ?? 0) ?></td>
    <td class="text-end"><?= number_format($r['employer_contribution'] ?? 0) ?></td>
    <td class="text-end fw-semibold"><?= number_format(($r['employee_contribution']??0)+($r['employer_contribution']??0)) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot class="table-light"><tr>
    <td colspan="5" class="fw-bold">TOTAL</td>
    <td class="text-end fw-bold">PKR <?= number_format($totEmp) ?></td>
    <td class="text-end fw-bold">PKR <?= number_format($totEr) ?></td>
    <td class="text-end fw-bold">PKR <?= number_format($totEmp+$totEr) ?></td>
  </tr></tfoot>
</table></div></div></div>
<?php endif; ?>
