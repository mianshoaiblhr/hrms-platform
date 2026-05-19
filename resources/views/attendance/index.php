<?php $pageTitle = 'Attendance Management'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Attendance Management</h4><p class="text-muted mb-0 small">Track and manage employee attendance</p></div>
  <div class="d-flex gap-2">
    <?php if(can('attendance.create')): ?>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
      <i class="fas fa-plus me-1"></i>Mark Attendance
    </button>
    <?php endif; ?>
    <?php if(can('attendance.import')): ?>
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
      <i class="fas fa-upload me-1"></i>Import
    </button>
    <?php endif; ?>
    <?php if(can('attendance.export')): ?>
    <a href="/attendance/export?<?= http_build_query($filters ?? []) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-download me-1"></i>Export
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
  <?php
  $attStats = [
    ['label'=>'Present Today','value'=>$stats['present_today']??0,'class'=>'success','icon'=>'check-circle'],
    ['label'=>'Absent Today','value'=>$stats['absent_today']??0,'class'=>'danger','icon'=>'times-circle'],
    ['label'=>'Late Today','value'=>$stats['late_today']??0,'class'=>'warning','icon'=>'clock'],
    ['label'=>'On Leave','value'=>$stats['on_leave']??0,'class'=>'info','icon'=>'umbrella-beach'],
  ];
  foreach ($attStats as $s):
  ?>
  <div class="col-6 col-md-3">
    <div class="card stat-card stat-<?= $s['class'] ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div><div class="stat-value"><?= $s['value'] ?></div><div class="stat-label"><?= $s['label'] ?></div></div>
          <i class="fas fa-<?= $s['icon'] ?> fa-lg opacity-25"></i>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" action="/attendance" class="row g-2 align-items-center">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Employee name/code..." value="<?= e($filters['search'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <select name="department_id" class="form-select form-select-sm">
          <option value="">All Departments</option>
          <?php foreach ($departments ?? [] as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? date('Y-m-01')) ?>">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="present" <?= ($filters['status'] ?? '') === 'present' ? 'selected' : '' ?>>Present</option>
          <option value="absent" <?= ($filters['status'] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option>
          <option value="late" <?= ($filters['status'] ?? '') === 'late' ? 'selected' : '' ?>>Late</option>
          <option value="half_day" <?= ($filters['status'] ?? '') === 'half_day' ? 'selected' : '' ?>>Half Day</option>
          <option value="holiday" <?= ($filters['status'] ?? '') === 'holiday' ? 'selected' : '' ?>>Holiday</option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
        <a href="/attendance" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Attendance Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th><th>Date</th>
            <th>Check In</th><th>Check Out</th><th>Hours</th>
            <th>Overtime</th><th>Status</th>
            <?php if(can('attendance.edit')): ?><th class="text-end">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data['data'])): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="fas fa-clock fa-2x mb-2 d-block opacity-25"></i>No attendance records found
          </td></tr>
          <?php else: foreach ($data['data'] as $att): ?>
          <tr>
            <td>
              <div class="fw-semibold small"><?= e($att['employee_name'] ?? '—') ?></div>
              <div class="x-small text-muted"><?= e($att['employee_code'] ?? '') ?></div>
            </td>
            <td class="small"><?= e($att['department_name'] ?? '—') ?></td>
            <td class="small"><?= formatDate($att['date']) ?></td>
            <td class="small">
              <?= $att['check_in'] ? '<span class="text-success">' . date('h:i A', strtotime($att['check_in'])) . '</span>' : '<span class="text-muted">—</span>' ?>
            </td>
            <td class="small">
              <?= $att['check_out'] ? '<span class="text-info">' . date('h:i A', strtotime($att['check_out'])) . '</span>' : '<span class="text-muted">—</span>' ?>
            </td>
            <td class="small"><?= $att['working_hours'] ? number_format($att['working_hours'], 1) . 'h' : '—' ?></td>
            <td class="small"><?= ($att['overtime_hours'] ?? 0) > 0 ? '<span class="text-warning">' . number_format($att['overtime_hours'], 1) . 'h</span>' : '—' ?></td>
            <td><?= statusBadge($att['status'] ?? 'present') ?></td>
            <?php if(can('attendance.edit')): ?>
            <td class="text-end">
              <button class="btn btn-outline-secondary btn-xs" onclick="editAttendance(<?= htmlspecialchars(json_encode($att)) ?>)">
                <i class="fas fa-edit"></i>
              </button>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if (($data['last_page'] ?? 1) > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= count($data['data'] ?? []) ?> of <?= $data['total'] ?> records</small>
    <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/attendance') ?>
  </div>
  <?php endif; ?>
</div>

<!-- Add Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header border-0"><h5 class="modal-title"><i class="fas fa-clock me-2 text-primary"></i>Mark Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="/attendance">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-semibold">Employee *</label>
            <select name="employee_id" class="form-select" required>
              <option value="">Select Employee</option>
              <?php foreach ($employees ?? [] as $emp): ?>
              <option value="<?= $emp['id'] ?>"><?= e($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Date *</label>
            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Check In</label>
            <input type="time" name="check_in" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Check Out</label>
            <input type="time" name="check_out" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Status *</label>
            <select name="status" class="form-select" required>
              <option value="present">Present</option>
              <option value="absent">Absent</option>
              <option value="late">Late</option>
              <option value="half_day">Half Day</option>
              <option value="leave">On Leave</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Optional notes...">
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header border-0"><h5 class="modal-title"><i class="fas fa-edit me-2 text-warning"></i>Edit Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" id="editAttForm">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12" id="editEmpName"></div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Check In</label>
            <input type="time" name="check_in" id="editCheckIn" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Check Out</label>
            <input type="time" name="check_out" id="editCheckOut" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" id="editStatus" class="form-select">
              <option value="present">Present</option>
              <option value="absent">Absent</option>
              <option value="late">Late</option>
              <option value="half_day">Half Day</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-save me-1"></i>Update</button>
      </div>
    </form>
  </div></div>
</div>

<script>
function editAttendance(att) {
  document.getElementById('editAttForm').action = '/attendance/' + att.id + '/update';
  document.getElementById('editEmpName').innerHTML = '<div class="alert alert-light py-2 small"><strong>' + (att.employee_name || '') + '</strong> &mdash; ' + att.date + '</div>';
  document.getElementById('editCheckIn').value = att.check_in ? att.check_in.slice(0, 5) : '';
  document.getElementById('editCheckOut').value = att.check_out ? att.check_out.slice(0, 5) : '';
  document.getElementById('editStatus').value = att.status || 'present';
  new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
}
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
