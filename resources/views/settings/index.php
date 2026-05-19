<?php $pageTitle = 'System Settings'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">System Settings</h4><p class="text-muted mb-0 small">Configure platform settings</p></div>
</div>

<div class="row g-4">
  <!-- Left Nav -->
  <div class="col-12 col-md-3">
    <div class="card">
      <div class="list-group list-group-flush">
        <a href="#company" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
          <i class="fas fa-building me-2"></i>Company
        </a>
        <a href="#departments" class="list-group-item list-group-item-action" data-bs-toggle="tab">
          <i class="fas fa-sitemap me-2"></i>Departments
        </a>
        <a href="#designations" class="list-group-item list-group-item-action" data-bs-toggle="tab">
          <i class="fas fa-id-badge me-2"></i>Designations
        </a>
        <a href="#leave-types" class="list-group-item list-group-item-action" data-bs-toggle="tab">
          <i class="fas fa-umbrella-beach me-2"></i>Leave Types
        </a>
        <a href="#holidays" class="list-group-item list-group-item-action" data-bs-toggle="tab">
          <i class="fas fa-calendar-alt me-2"></i>Holidays
        </a>
        <a href="#tax-settings" class="list-group-item list-group-item-action" data-bs-toggle="tab">
          <i class="fas fa-file-invoice-dollar me-2"></i>Tax Slabs
        </a>
        <a href="/settings/users" class="list-group-item list-group-item-action">
          <i class="fas fa-users-cog me-2"></i>Users
        </a>
      </div>
    </div>
  </div>

  <!-- Right Content -->
  <div class="col-12 col-md-9">
    <div class="tab-content">

      <!-- Company Settings -->
      <div class="tab-pane fade show active" id="company">
        <div class="card">
          <div class="card-header py-2"><h6 class="mb-0 fw-bold"><i class="fas fa-building me-1"></i>Company Information</h6></div>
          <div class="card-body">
            <form method="POST" action="/settings/company" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Company Name *</label>
                  <input type="text" name="company_name" class="form-control" value="<?= e($settings['company_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Registration Number</label>
                  <input type="text" name="company_reg_no" class="form-control" value="<?= e($settings['company_reg_no'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">NTN</label>
                  <input type="text" name="company_ntn" class="form-control" value="<?= e($settings['company_ntn'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">EOBI Employer Number</label>
                  <input type="text" name="company_eobi" class="form-control" value="<?= e($settings['company_eobi'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Phone</label>
                  <input type="text" name="company_phone" class="form-control" value="<?= e($settings['company_phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Email</label>
                  <input type="email" name="company_email" class="form-control" value="<?= e($settings['company_email'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Address</label>
                  <textarea name="company_address" class="form-control" rows="2"><?= e($settings['company_address'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Currency Symbol</label>
                  <input type="text" name="currency_symbol" class="form-control" value="<?= e($settings['currency_symbol'] ?? 'PKR') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Fiscal Year Start</label>
                  <select name="fiscal_year_start" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($settings['fiscal_year_start'] ?? 7) == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Company Settings</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Departments -->
      <div class="tab-pane fade" id="departments">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-sitemap me-1"></i>Departments</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal"><i class="fas fa-plus me-1"></i>Add</button>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Name</th><th>Manager</th><th>Employees</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($departments ?? [] as $dept): ?>
                <tr>
                  <td class="small fw-semibold"><?= e($dept['name']) ?></td>
                  <td class="small"><?= e($dept['manager_name'] ?? '—') ?></td>
                  <td class="small"><?= $dept['employee_count'] ?? 0 ?></td>
                  <td><?= statusBadge($dept['status'] ?? 'active') ?></td>
                  <td class="text-end">
                    <button class="btn btn-outline-secondary btn-xs" onclick="editDept(<?= htmlspecialchars(json_encode($dept)) ?>)"><i class="fas fa-edit"></i></button>
                    <form method="POST" action="/settings/departments/<?= $dept['id'] ?>/delete" class="d-inline">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-outline-danger btn-xs" data-confirm="Delete this department?"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Designations -->
      <div class="tab-pane fade" id="designations">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-id-badge me-1"></i>Designations</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDesigModal"><i class="fas fa-plus me-1"></i>Add</button>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Title</th><th>Department</th><th>Grade</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($designations ?? [] as $d): ?>
                <tr>
                  <td class="small fw-semibold"><?= e($d['title']) ?></td>
                  <td class="small"><?= e($d['department_name'] ?? 'All') ?></td>
                  <td class="small"><?= e($d['grade'] ?? '—') ?></td>
                  <td class="text-end">
                    <button class="btn btn-outline-secondary btn-xs" onclick="editDesig(<?= htmlspecialchars(json_encode($d)) ?>)"><i class="fas fa-edit"></i></button>
                    <form method="POST" action="/settings/designations/<?= $d['id'] ?>/delete" class="d-inline">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-outline-danger btn-xs" data-confirm="Delete?"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Leave Types -->
      <div class="tab-pane fade" id="leave-types">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-umbrella-beach me-1"></i>Leave Types</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLeaveTypeModal"><i class="fas fa-plus me-1"></i>Add</button>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Name</th><th>Days/Year</th><th>Carry Forward</th><th>Paid</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($leaveTypes ?? [] as $lt): ?>
                <tr>
                  <td class="small fw-semibold"><?= e($lt['name']) ?></td>
                  <td class="small"><?= $lt['max_days'] ?></td>
                  <td class="small"><?= $lt['carry_forward'] ? '<span class="text-success">Yes</span>' : 'No' ?></td>
                  <td class="small"><?= $lt['is_paid'] ? '<span class="text-success">Paid</span>' : '<span class="text-warning">Unpaid</span>' ?></td>
                  <td class="text-end">
                    <button class="btn btn-outline-secondary btn-xs" onclick="editLeaveType(<?= htmlspecialchars(json_encode($lt)) ?>)"><i class="fas fa-edit"></i></button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Holidays -->
      <div class="tab-pane fade" id="holidays">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-1"></i>Public Holidays</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHolidayModal"><i class="fas fa-plus me-1"></i>Add</button>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Holiday</th><th>Date</th><th>Year</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($holidays ?? [] as $h): ?>
                <tr>
                  <td class="small fw-semibold"><?= e($h['name']) ?></td>
                  <td class="small"><?= formatDate($h['date']) ?></td>
                  <td class="small"><?= date('Y', strtotime($h['date'])) ?></td>
                  <td class="text-end">
                    <form method="POST" action="/settings/holidays/<?= $h['id'] ?>/delete" class="d-inline">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-outline-danger btn-xs" data-confirm="Delete?"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- FBR Tax Slabs -->
      <div class="tab-pane fade" id="tax-settings">
        <div class="card">
          <div class="card-header py-2"><h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-1"></i>FBR Tax Slabs 2024-25</h6></div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Annual Income From</th><th>Annual Income To</th><th>Fixed Tax</th><th>Rate %</th></tr></thead>
              <tbody>
                <?php foreach ($taxSlabs ?? [] as $slab): ?>
                <tr>
                  <td class="small"><?= formatCurrency($slab['min_income']) ?></td>
                  <td class="small"><?= $slab['max_income'] ? formatCurrency($slab['max_income']) : 'Above' ?></td>
                  <td class="small"><?= formatCurrency($slab['fixed_tax']) ?></td>
                  <td class="small"><span class="badge bg-warning text-dark"><?= $slab['rate'] ?>%</span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- /tab-content -->
  </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header border-0"><h6 class="modal-title">Add Department</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="/settings/departments"><div class="modal-body"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label small">Name *</label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small">Code</label><input type="text" name="code" class="form-control"></div>
    <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
  </form>
</div></div></div>

<!-- Add Designation Modal -->
<div class="modal fade" id="addDesigModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header border-0"><h6 class="modal-title">Add Designation</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="/settings/designations"><div class="modal-body"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label small">Title *</label><input type="text" name="title" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small">Department</label>
      <select name="department_id" class="form-select"><option value="">All Departments</option>
        <?php foreach ($departments ?? [] as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3"><label class="form-label small">Grade</label><input type="text" name="grade" class="form-control"></div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
  </form>
</div></div></div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="addHolidayModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header border-0"><h6 class="modal-title">Add Holiday</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="/settings/holidays"><div class="modal-body"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label small">Holiday Name *</label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small">Date *</label><input type="date" name="date" class="form-control" required></div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
  </form>
</div></div></div>

<script>
function editDept(d) { /* Populate edit modal */ }
function editDesig(d) { /* Populate edit modal */ }
function editLeaveType(lt) { /* Populate edit modal */ }
// Tab persistence
document.querySelectorAll('.list-group-item[data-bs-toggle="tab"]').forEach(t => {
  t.addEventListener('click', function() {
    document.querySelectorAll('.list-group-item').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
  });
});
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
