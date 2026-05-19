<?php $pageTitle = 'Apply for Leave'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold">Apply for Leave</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
      <li class="breadcrumb-item"><a href="/leaves">Leave Management</a></li>
      <li class="breadcrumb-item active">Apply</li>
    </ol></nav>
  </div>
  <a href="/leaves" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="row">
  <div class="col-12 col-lg-7">
    <div class="card">
      <div class="card-header py-2"><h6 class="mb-0 fw-bold">Leave Application Form</h6></div>
      <div class="card-body">
        <form method="POST" action="/leaves">
          <?= csrf_field() ?>
          <div class="row g-3">
            <?php if(can('leaves.create_for_others')): ?>
            <div class="col-12">
              <label class="form-label small fw-semibold">Employee</label>
              <select name="employee_id" class="form-select" required>
                <option value="<?= authUser()['employee_id'] ?? '' ?>">Myself — <?= e(authUser()['name'] ?? '') ?></option>
                <?php foreach ($employees ?? [] as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= e($emp['employee_code'] . ' — ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="employee_id" value="<?= authUser()['employee_id'] ?? '' ?>">
            <?php endif; ?>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Leave Type *</label>
              <select name="leave_type_id" class="form-select" required id="leaveTypeSelect">
                <option value="">Select Leave Type</option>
                <?php foreach ($leaveTypes ?? [] as $lt): ?>
                <option value="<?= $lt['id'] ?>" data-max="<?= $lt['max_days'] ?>">
                  <?= e($lt['name']) ?> (<?= $lt['max_days'] ?> days/year)
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Leave Balance</label>
              <div class="form-control bg-light" id="leaveBalanceDisplay">
                <span class="text-muted small">Select leave type to see balance</span>
              </div>
            </div>

            <div class="col-md-5">
              <label class="form-label small fw-semibold">Start Date *</label>
              <input type="date" name="start_date" id="startDate" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-md-5">
              <label class="form-label small fw-semibold">End Date *</label>
              <input type="date" name="end_date" id="endDate" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-md-2">
              <label class="form-label small fw-semibold">Days</label>
              <div class="form-control bg-light text-center fw-bold text-primary" id="daysCount">0</div>
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold">Reason *</label>
              <textarea name="reason" class="form-control" rows="3" required placeholder="Describe your reason for leave..."></textarea>
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold">Attachment (optional)</label>
              <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
              <small class="text-muted">Medical certificate or other supporting document (PDF, JPG, PNG, max 5MB)</small>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="/leaves" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Submit Application</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <!-- Leave Balances Card -->
    <div class="card mb-3">
      <div class="card-header py-2"><h6 class="mb-0 small fw-bold"><i class="fas fa-balance-scale me-1 text-primary"></i>My Leave Balances</h6></div>
      <div class="card-body">
        <?php foreach ($myBalances ?? [] as $bal): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="small"><?= e($bal['leave_type_name']) ?></span>
          <div class="d-flex gap-1 align-items-center">
            <div class="progress" style="width:80px;height:6px">
              <?php $pct = $bal['allocated'] > 0 ? min(100, ($bal['used'] / $bal['allocated']) * 100) : 0; ?>
              <div class="progress-bar <?= $pct > 80 ? 'bg-danger' : ($pct > 50 ? 'bg-warning' : 'bg-success') ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="badge bg-<?= $bal['balance'] > 0 ? 'success' : 'danger' ?>-subtle text-<?= $bal['balance'] > 0 ? 'success' : 'danger' ?>">
              <?= $bal['balance'] ?> left
            </span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Leave Policy Info -->
    <div class="card">
      <div class="card-header py-2"><h6 class="mb-0 small fw-bold"><i class="fas fa-info-circle me-1 text-info"></i>Leave Policy</h6></div>
      <div class="card-body">
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-2"><i class="fas fa-check text-success me-1"></i>Applications must be submitted at least 1 day in advance</li>
          <li class="mb-2"><i class="fas fa-check text-success me-1"></i>Medical leave requires a certificate for 2+ consecutive days</li>
          <li class="mb-2"><i class="fas fa-check text-success me-1"></i>Leave approval is subject to manager discretion</li>
          <li class="mb-2"><i class="fas fa-check text-success me-1"></i>Approved leave is deducted from your annual balance</li>
          <li><i class="fas fa-info text-info me-1"></i>Contact HR for emergency or special leaves</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
const balances = <?= json_encode(array_column($myBalances ?? [], 'balance', 'leave_type_id')) ?>;

document.getElementById('leaveTypeSelect')?.addEventListener('change', function() {
  const bal = balances[this.value] ?? null;
  const display = document.getElementById('leaveBalanceDisplay');
  if (bal !== null) {
    display.innerHTML = `<span class="fw-semibold ${bal > 0 ? 'text-success' : 'text-danger'}">${bal} day(s) remaining</span>`;
  } else {
    display.innerHTML = '<span class="text-muted small">N/A</span>';
  }
});

function calcDays() {
  const s = document.getElementById('startDate').value;
  const e = document.getElementById('endDate').value;
  if (s && e) {
    const diff = Math.round((new Date(e) - new Date(s)) / 86400000) + 1;
    document.getElementById('daysCount').textContent = diff > 0 ? diff : 0;
  }
}

document.getElementById('startDate')?.addEventListener('change', function() {
  const endDate = document.getElementById('endDate');
  if (!endDate.value || endDate.value < this.value) endDate.value = this.value;
  calcDays();
});

document.getElementById('endDate')?.addEventListener('change', calcDays);
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
