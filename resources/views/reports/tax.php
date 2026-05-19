<?php $pageTitle = 'Income Tax Report'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h4 class="fw-bold mb-1">FBR Income Tax Report</h4><p class="text-muted small mb-0">Annual tax deductions per employee</p></div>
  <?php if (!empty($data)): ?>
  <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="btn btn-success btn-sm">
    <i class="fas fa-file-csv me-1"></i>Export CSV
  </a>
  <?php endif; ?>
</div>
<div class="card mb-4"><div class="card-body">
  <form method="GET" class="row g-3 align-items-end">
    <div class="col-md-3"><label class="form-label">Tax Year</label>
      <select name="year" class="form-select">
        <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
        <option value="<?= $y ?>" <?= ($year??date('Y'))==$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-3"><button class="btn btn-primary"><i class="fas fa-search me-1"></i>Generate</button></div>
  </form>
</div></div>
<?php if (empty($data ?? [])): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
  <i class="fas fa-receipt fa-3x mb-3 opacity-25 d-block"></i>No tax data for <?= $year ?? date('Y') ?>. Process payroll first.
</div></div>
<?php else: ?>
<?php $total = array_sum(array_column($data,'total_income_tax')); ?>
<div class="stat-card mb-4 d-inline-block px-5">
  <div class="stat-value text-danger">PKR <?= number_format($total) ?></div>
  <div class="stat-label">Total Tax Collected — <?= $year ?? date('Y') ?></div>
</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0">
  <thead><tr><th>Emp Code</th><th>Name</th><th>CNIC</th><th>NTN</th><th class="text-end">Income Tax (PKR)</th></tr></thead>
  <tbody>
  <?php foreach ($data as $r): ?>
  <tr>
    <td><code><?= e($r['employee_code']) ?></code></td>
    <td class="fw-semibold"><?= e($r['name']) ?></td>
    <td><?= e($r['cnic'] ?? '—') ?></td>
    <td><?= e($r['ntn'] ?? '—') ?></td>
    <td class="text-end fw-semibold text-danger"><?= number_format($r['total_income_tax'] ?? 0) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot class="table-light"><tr>
    <td colspan="4" class="fw-bold">TOTAL</td>
    <td class="text-end fw-bold text-danger">PKR <?= number_format($total) ?></td>
  </tr></tfoot>
</table></div></div></div>
<?php endif; ?>
