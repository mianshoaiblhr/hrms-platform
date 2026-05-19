<?php $pageTitle = 'Attendance Report'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h4 class="fw-bold mb-1">Attendance Report</h4><p class="text-muted small mb-0">Daily attendance summary by employee</p></div>
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
    <div class="col-md-3"><label class="form-label">Department</label>
      <select name="dept" class="form-select">
        <option value="">All</option>
        <?php foreach ($departments ?? [] as $d): ?>
        <option value="<?= $d['id'] ?>" <?= ($dept??0)==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Generate</button>
    </div>
  </form>
</div></div>
<?php if (empty($data ?? [])): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
  <i class="fas fa-fingerprint fa-3x mb-3 opacity-25 d-block"></i>No attendance data found. Mark attendance first.
</div></div>
<?php else: ?>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0">
  <thead><tr><th>Emp Code</th><th>Name</th><th>Department</th>
    <th class="text-center text-success">Present</th><th class="text-center text-danger">Absent</th>
    <th class="text-center text-warning">Late</th><th class="text-center">Half Day</th>
    <th class="text-center">On Leave</th><th class="text-center">Avg Hours</th></tr></thead>
  <tbody>
  <?php foreach ($data as $r): ?>
  <tr>
    <td><code><?= e($r['employee_code']) ?></code></td>
    <td class="fw-semibold"><?= e($r['name']) ?></td>
    <td><?= e($r['dept'] ?? '—') ?></td>
    <td class="text-center text-success fw-semibold"><?= $r['present'] ?? 0 ?></td>
    <td class="text-center text-danger"><?= $r['absent'] ?? 0 ?></td>
    <td class="text-center text-warning"><?= $r['late'] ?? 0 ?></td>
    <td class="text-center"><?= $r['half_day'] ?? 0 ?></td>
    <td class="text-center"><?= $r['on_leave'] ?? 0 ?></td>
    <td class="text-center"><?= $r['avg_hours'] ?? '—' ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div></div>
<?php endif; ?>
