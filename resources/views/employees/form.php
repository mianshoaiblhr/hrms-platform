<?php
$isEdit = isset($employee);
$pageTitle = $isEdit ? 'Edit Employee' : 'Add Employee';
ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold"><?= $isEdit ? 'Edit Employee' : 'Add New Employee' ?></h4>
    <p class="text-muted mb-0 small"><?= $isEdit ? 'Update employee information' : 'Create a new employee record' ?></p>
  </div>
  <a href="/employees" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" action="<?= $isEdit ? '/employees/' . $employee['id'] . '/update' : '/employees' ?>" enctype="multipart/form-data" id="empForm">
  <?= csrf_field() ?>

  <!-- Tab Navigation -->
  <ul class="nav nav-tabs mb-3" id="empTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#personal"><i class="fas fa-user me-1"></i>Personal</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#employment"><i class="fas fa-briefcase me-1"></i>Employment</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bank"><i class="fas fa-university me-1"></i>Bank & Tax</a></li>
    <?php if ($isEdit): ?>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#docs"><i class="fas fa-file me-1"></i>Documents</a></li>
    <?php endif; ?>
  </ul>

  <div class="tab-content">
    <!-- Personal Tab -->
    <div class="tab-pane fade show active" id="personal">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12 col-md-4 text-center">
              <div class="avatar avatar-xl mx-auto mb-2" id="avatarPreview">
                <?php if ($isEdit && !empty($employee['avatar'])): ?>
                <img src="<?= asset('uploads/avatars/'.e($employee['avatar'])) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                <?php else: ?>
                <?= $isEdit ? strtoupper(substr($employee['first_name']??'E',0,1)) : '?' ?>
                <?php endif; ?>
              </div>
              <label class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-camera me-1"></i>Upload Photo
                <input type="file" name="avatar" accept="image/*" class="d-none" onchange="previewAvatar(this)">
              </label>
              <small class="d-block text-muted mt-1">JPG, PNG up to 5MB</small>
            </div>
            <div class="col-12 col-md-8">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">First Name *</label>
                  <input type="text" name="first_name" class="form-control" required value="<?= e($employee['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Last Name *</label>
                  <input type="text" name="last_name" class="form-control" required value="<?= e($employee['last_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Father's Name</label>
                  <input type="text" name="father_name" class="form-control" value="<?= e($employee['father_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">CNIC *</label>
                  <input type="text" name="cnic" class="form-control" placeholder="XXXXX-XXXXXXX-X" pattern="\d{5}-\d{7}-\d{1}" required value="<?= e($employee['cnic'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Date of Birth</label>
                  <input type="date" name="date_of_birth" class="form-control" value="<?= e($employee['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Gender</label>
                  <select name="gender" class="form-select">
                    <option value="">Select</option>
                    <option value="male" <?= ($employee['gender']??'') === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= ($employee['gender']??'') === 'female' ? 'selected' : '' ?>>Female</option>
                    <option value="other" <?= ($employee['gender']??'') === 'other' ? 'selected' : '' ?>>Other</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Marital Status</label>
                  <select name="marital_status" class="form-select">
                    <option value="">Select</option>
                    <option value="single" <?= ($employee['marital_status']??'') === 'single' ? 'selected' : '' ?>>Single</option>
                    <option value="married" <?= ($employee['marital_status']??'') === 'married' ? 'selected' : '' ?>>Married</option>
                    <option value="divorced" <?= ($employee['marital_status']??'') === 'divorced' ? 'selected' : '' ?>>Divorced</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Personal Email</label>
              <input type="email" name="personal_email" class="form-control" value="<?= e($employee['personal_email'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Personal Phone</label>
              <input type="text" name="personal_phone" class="form-control" value="<?= e($employee['personal_phone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Nationality</label>
              <input type="text" name="nationality" class="form-control" value="<?= e($employee['nationality'] ?? 'Pakistani') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Emergency Contact Name</label>
              <input type="text" name="emergency_contact_name" class="form-control" value="<?= e($employee['emergency_contact_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Emergency Contact Phone</label>
              <input type="text" name="emergency_contact_phone" class="form-control" value="<?= e($employee['emergency_contact_phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Present Address</label>
              <textarea name="present_address" class="form-control" rows="2"><?= e($employee['present_address'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Permanent Address</label>
              <textarea name="permanent_address" class="form-control" rows="2"><?= e($employee['permanent_address'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Employment Tab -->
    <div class="tab-pane fade" id="employment">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Employee Code</label>
              <input type="text" name="employee_code" class="form-control" value="<?= e($employee['employee_code'] ?? $nextCode ?? '') ?>" <?= $isEdit ? '' : 'readonly' ?>>
              <?php if (!$isEdit): ?><small class="text-muted">Auto-generated</small><?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Department *</label>
              <select name="department_id" class="form-select" required id="deptSelect">
                <option value="">Select Department</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($employee['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Designation</label>
              <select name="designation_id" class="form-select" id="desigSelect">
                <option value="">Select Designation</option>
                <?php foreach ($designations as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($employee['designation_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Employment Type *</label>
              <select name="employment_type" class="form-select" required>
                <option value="permanent" <?= ($employee['employment_type']??'') === 'permanent' ? 'selected' : '' ?>>Permanent</option>
                <option value="contract" <?= ($employee['employment_type']??'') === 'contract' ? 'selected' : '' ?>>Contract</option>
                <option value="probation" <?= ($employee['employment_type']??'') === 'probation' ? 'selected' : '' ?>>Probation</option>
                <option value="part_time" <?= ($employee['employment_type']??'') === 'part_time' ? 'selected' : '' ?>>Part Time</option>
                <option value="intern" <?= ($employee['employment_type']??'') === 'intern' ? 'selected' : '' ?>>Intern</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Joining Date *</label>
              <input type="date" name="joining_date" class="form-control" required value="<?= e($employee['joining_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Confirmation Date</label>
              <input type="date" name="confirmation_date" class="form-control" value="<?= e($employee['confirmation_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Status *</label>
              <select name="status" class="form-select" required>
                <option value="active" <?= ($employee['status']??'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($employee['status']??'') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="probation" <?= ($employee['status']??'') === 'probation' ? 'selected' : '' ?>>Probation</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Notes</label>
              <textarea name="notes" class="form-control" rows="2"><?= e($employee['notes'] ?? '') ?></textarea>
            </div>
            <?php if (!$isEdit): ?>
            <div class="col-12"><hr><h6 class="fw-semibold">Create Login Account</h6></div>
            <div class="col-md-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="createAccount" name="create_account" value="1">
                <label class="form-check-label small" for="createAccount">Create user account</label>
              </div>
            </div>
            <div class="col-md-3" id="usernameField" style="display:none">
              <label class="form-label small fw-semibold">Username</label>
              <input type="text" name="username" class="form-control form-control-sm">
            </div>
            <div class="col-md-3" id="emailField" style="display:none">
              <label class="form-label small fw-semibold">Email</label>
              <input type="email" name="user_email" class="form-control form-control-sm">
            </div>
            <div class="col-md-3" id="roleField" style="display:none">
              <label class="form-label small fw-semibold">Role</label>
              <select name="role_id" class="form-select form-select-sm">
                <?php foreach ($roles ?? [] as $r): ?>
                <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Bank & Tax Tab -->
    <div class="tab-pane fade" id="bank">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12"><h6 class="fw-semibold">Bank Details</h6></div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Bank Name</label>
              <input type="text" name="bank_name" class="form-control" value="<?= e($employee['bank_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Account Number</label>
              <input type="text" name="bank_account" class="form-control" value="<?= e($employee['bank_account'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Branch</label>
              <input type="text" name="bank_branch" class="form-control" value="<?= e($employee['bank_branch'] ?? '') ?>">
            </div>
            <div class="col-12 mt-2"><h6 class="fw-semibold">Tax & Compliance</h6></div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">NTN Number</label>
              <input type="text" name="ntn_number" class="form-control" value="<?= e($employee['ntn_number'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">EOBI Number</label>
              <input type="text" name="eobi_number" class="form-control" value="<?= e($employee['eobi_number'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">PESSI Number</label>
              <input type="text" name="pessi_number" class="form-control" value="<?= e($employee['pessi_number'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <a href="/employees" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $isEdit ? 'Update Employee' : 'Save Employee' ?></button>
  </div>
</form>

<script>
document.getElementById('createAccount')?.addEventListener('change', function() {
  ['usernameField','emailField','roleField'].forEach(id => {
    document.getElementById(id).style.display = this.checked ? '' : 'none';
  });
});

document.getElementById('deptSelect')?.addEventListener('change', function() {
  const deptId = this.value;
  if (!deptId) return;
  fetch('/api/designations?dept=' + deptId)
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById('desigSelect');
      sel.innerHTML = '<option value="">Select Designation</option>';
      data.forEach(d => sel.innerHTML += `<option value="${d.id}">${d.title}</option>`);
    });
});

function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const prev = document.getElementById('avatarPreview');
      prev.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
