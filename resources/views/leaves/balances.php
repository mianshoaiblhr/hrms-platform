<?php $pageTitle = 'Leave Balances'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Leave Balances</h4><p class="text-muted mb-0 small">Annual leave allocations and remaining balances</p></div>
  <div class="d-flex gap-2">
    <a href="/leaves/calendar" class="btn btn-outline-secondary btn-sm"><i class="fas fa-calendar me-1"></i>Calendar</a>
    <a href="/leaves/apply" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Apply Leave</a>
  </div>
</div>

<!-- Filter -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <?php if(can('leaves.view_all')): ?>
      <div class="col-md-3">
        <select name="department_id" class="form-select form-select-sm">
          <option value="">All Departments</option>
          <?php foreach ($departments ?? [] as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-2">
        <select name="year" class="form-select form-select-sm">
          <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
          <option value="<?= $y ?>" <?= ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="/leaves/balances" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Balances Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Employee</th>
            <?php foreach ($leaveTypes ?? [] as $lt): ?>
            <th class="text-center"><?= e($lt['name']) ?><div class="x-small text-muted fw-normal"><?= $lt['max_days'] ?> days</div></th>
            <?php endforeach; ?>
            <th class="text-center">Total Used</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($balances)): ?>
          <tr><td colspan="20" class="text-center py-4 text-muted">No data found</td></tr>
          <?php else: foreach ($balances as $emp => $data): ?>
          <tr>
            <td>
              <div class="fw-semibold small"><?= e($data['employee_name'] ?? '—') ?></div>
              <div class="x-small text-muted"><?= e($data['department_name'] ?? '') ?></div>
            </td>
            <?php foreach ($leaveTypes ?? [] as $lt):
              $bal = $data['types'][$lt['id']] ?? ['allocated' => $lt['max_days'], 'used' => 0, 'balance' => $lt['max_days']];
              $pct = $bal['allocated'] > 0 ? min(100, ($bal['used'] / $bal['allocated']) * 100) : 0;
            ?>
            <td class="text-center">
              <div class="small fw-semibold <?= $bal['balance'] <= 0 ? 'text-danger' : 'text-success' ?>"><?= $bal['balance'] ?></div>
              <div class="x-small text-muted"><?= $bal['used'] ?>/<?= $bal['allocated'] ?></div>
              <div class="progress mt-1" style="height:3px">
                <div class="progress-bar <?= $pct > 80 ? 'bg-danger' : ($pct > 50 ? 'bg-warning' : 'bg-success') ?>" style="width:<?= $pct ?>%"></div>
              </div>
            </td>
            <?php endforeach; ?>
            <td class="text-center small fw-semibold text-danger"><?= $data['total_used'] ?? 0 ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
