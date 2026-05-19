<?php $pageTitle='Employees'; ob_start(); ?>
<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Employee Management</h4><p class="text-muted mb-0 small">Manage your workforce</p></div>
  <div class="d-flex gap-2">
    <?php if(can('employees.export')): ?><a href="/employees/export" class="btn btn-outline-secondary btn-sm"><i class="fas fa-download me-1"></i>Export</a><?php endif; ?>
    <?php if(can('employees.create')): ?><a href="/employees/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Employee</a><?php endif; ?>
  </div>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" action="/employees" class="row g-2 align-items-center">
      <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, code, CNIC..." value="<?=e($filters['search']??'')?>"></div>
      <div class="col-md-2">
        <select name="dept" class="form-select form-select-sm">
          <option value="">All Departments</option>
          <?php foreach($departments as $d): ?><option value="<?=$d['id']?>" <?=($filters['department_id']==$d['id'])?'selected':''?>><?=e($d['name'])?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="active" <?=($filters['status']??'')==='active'?'selected':''?>>Active</option>
          <option value="inactive" <?=($filters['status']??'')==='inactive'?'selected':''?>>Inactive</option>
          <option value="terminated" <?=($filters['status']??'')==='terminated'?'selected':''?>>Terminated</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
          <option value="">All Types</option>
          <option value="permanent">Permanent</option>
          <option value="contract">Contract</option>
          <option value="part_time">Part Time</option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
        <a href="/employees" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-2 mb-3">
  <?php
  $db = \App\Core\Database::getInstance();
  $statRows = [
    ['label'=>'Total','value'=>$data['total'],'class'=>'primary'],
    ['label'=>'Active','value'=>$db->fetchColumn("SELECT COUNT(*) FROM employees WHERE status='active' AND deleted_at IS NULL"),'class'=>'success'],
    ['label'=>'On Leave','value'=>$db->fetchColumn("SELECT COUNT(*) FROM employees WHERE status='on_leave' AND deleted_at IS NULL"),'class'=>'warning'],
    ['label'=>'Terminated','value'=>$db->fetchColumn("SELECT COUNT(*) FROM employees WHERE status='terminated' AND deleted_at IS NULL"),'class'=>'danger'],
  ];
  foreach($statRows as $s): ?>
  <div class="col-6 col-md-3"><div class="card text-center py-2"><div class="fw-bold h5 mb-0 text-<?=$s['class']?>"><?=$s['value']?></div><div class="small text-muted"><?=$s['label']?></div></div></div>
  <?php endforeach; ?>
</div>

<!-- Employee Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th style="width:40px"><input type="checkbox" id="selectAll" class="form-check-input"></th>
            <th>Employee</th><th>Department</th><th>Designation</th>
            <th>Type</th><th>Joining Date</th><th>Status</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($data['data'])): ?>
          <tr><td colspan="8" class="text-center text-muted py-5">
            <i class="fas fa-users fa-2x mb-2 d-block opacity-25"></i>No employees found
          </td></tr>
          <?php else: foreach($data['data'] as $emp): ?>
          <tr>
            <td><input type="checkbox" class="form-check-input emp-check" value="<?=$emp['id']?>"></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm"><?=strtoupper(substr($emp['first_name'],0,1).substr($emp['last_name'],0,1))?></div>
                <div>
                  <div class="fw-semibold small"><?=e($emp['first_name'].' '.$emp['last_name'])?></div>
                  <div class="text-muted x-small"><?=e($emp['employee_code'])?></div>
                </div>
              </div>
            </td>
            <td class="small"><?=e($emp['department_name']??'—')?></td>
            <td class="small"><?=e($emp['designation_title']??'—')?></td>
            <td><span class="badge bg-light text-dark border small"><?=ucfirst(str_replace('_',' ',$emp['employment_type']??''))?></span></td>
            <td class="small text-muted"><?=formatDate($emp['joining_date'])?></td>
            <td><?=statusBadge($emp['status']??'active')?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <?php if(can('employees.view')): ?><a href="/employees/<?=$emp['id']?>" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a><?php endif; ?>
                <?php if(can('employees.edit')): ?><a href="/employees/<?=$emp['id']?>/edit" class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a><?php endif; ?>
                <?php if(can('employees.delete') && $emp['status']!=='terminated'): ?>
                <button type="button" class="btn btn-outline-danger" title="Terminate" onclick="terminateEmployee(<?=$emp['id']?>,<?=json_encode($emp['first_name'].' '.$emp['last_name'])?>)"><i class="fas fa-user-times"></i></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if(($data['last_page']??1) > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?=count($data['data']??[])?> of <?=$data['total']?> employees</small>
    <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/employees?search='.urlencode($filters['search']??'')) ?>
  </div>
  <?php endif; ?>
</div>

<!-- Terminate Modal -->
<div class="modal fade" id="terminateModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="fas fa-user-times me-2"></i>Terminate Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" id="terminateForm">
      <?=csrf_field()?>
      <div class="modal-body">
        <p>Are you sure you want to terminate <strong id="terminateName"></strong>?</p>
        <div class="mb-3">
          <label class="form-label small">Termination Date *</label>
          <input type="date" name="termination_date" class="form-control" value="<?=date('Y-m-d')?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label small">Reason</label>
          <textarea name="reason" class="form-control" rows="3" placeholder="Reason for termination..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-check me-1"></i>Confirm Terminate</button>
      </div>
    </form>
  </div></div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change',function(){
  document.querySelectorAll('.emp-check').forEach(c=>c.checked=this.checked);
});
function terminateEmployee(id, name) {
  document.getElementById('terminateName').textContent = name;
  document.getElementById('terminateForm').action = '/employees/'+id+'/terminate';
  new bootstrap.Modal(document.getElementById('terminateModal')).show();
}
</script>
<?php $content=ob_get_clean(); include ROOT_PATH.'/resources/views/layouts/main.php'; ?>
