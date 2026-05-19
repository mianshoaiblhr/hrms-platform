<?php $pageTitle = 'My Profile'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">My Profile</h4><p class="text-muted mb-0 small">Manage your account settings</p></div>
</div>

<div class="row g-4">
  <!-- Left: Profile card -->
  <div class="col-12 col-md-4">
    <div class="card text-center">
      <div class="card-body py-4">
        <div class="avatar avatar-xl mx-auto mb-3" id="avatarPreview">
          <?php if (!empty($user['avatar'])): ?>
          <img src="<?= asset('uploads/avatars/' . e($user['avatar'])) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
          <?php else: ?>
          <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
          <?php endif; ?>
        </div>
        <h5 class="fw-bold mb-0"><?= e($user['name'] ?? '') ?></h5>
        <div class="text-muted small"><?= e($user['role_name'] ?? '') ?></div>
        <div class="mt-2 small text-muted"><i class="fas fa-envelope me-1"></i><?= e($user['email'] ?? '') ?></div>
        <div class="mt-1 small text-muted"><i class="fas fa-clock me-1"></i>Last login: <?= $user['last_login_at'] ? date('d M Y H:i', strtotime($user['last_login_at'])) : 'N/A' ?></div>
        <hr>
        <form method="POST" action="/profile/avatar" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <label class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-camera me-1"></i>Change Photo
            <input type="file" name="avatar" accept="image/*" class="d-none" onchange="previewAndSubmit(this)">
          </label>
        </form>
      </div>
    </div>
  </div>

  <!-- Right: Settings tabs -->
  <div class="col-12 col-md-8">
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabInfo"><i class="fas fa-user me-1"></i>Account Info</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabPassword"><i class="fas fa-lock me-1"></i>Change Password</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabPrefs"><i class="fas fa-sliders-h me-1"></i>Preferences</a></li>
    </ul>

    <div class="tab-content">
      <!-- Account Info Tab -->
      <div class="tab-pane fade show active" id="tabInfo">
        <div class="card">
          <div class="card-body">
            <form method="POST" action="/profile/update">
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Full Name</label>
                  <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Username</label>
                  <input type="text" class="form-control bg-light" value="<?= e($user['username'] ?? '') ?>" readonly>
                  <small class="text-muted">Username cannot be changed</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Email</label>
                  <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Role</label>
                  <input type="text" class="form-control bg-light" value="<?= e($user['role_name'] ?? '') ?>" readonly>
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Changes</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Change Password Tab -->
      <div class="tab-pane fade" id="tabPassword">
        <div class="card">
          <div class="card-body">
            <form method="POST" action="/profile/password">
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-semibold">Current Password *</label>
                  <div class="input-group">
                    <input type="password" name="current_password" class="form-control" id="curPwd" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleField('curPwd',this)"><i class="fas fa-eye"></i></button>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">New Password *</label>
                  <div class="input-group">
                    <input type="password" name="password" class="form-control" id="newPwd" required minlength="8">
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleField('newPwd',this)"><i class="fas fa-eye"></i></button>
                  </div>
                  <div class="progress mt-1" style="height:4px"><div id="pwdStrBar" class="progress-bar" style="width:0%"></div></div>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Confirm New Password *</label>
                  <div class="input-group">
                    <input type="password" name="password_confirmation" class="form-control" id="confPwd" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleField('confPwd',this)"><i class="fas fa-eye"></i></button>
                  </div>
                </div>
                <div class="col-12">
                  <div class="alert alert-info small py-2 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Password must be at least 8 characters and contain uppercase, numbers, and symbols.
                  </div>
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-key me-1"></i>Update Password</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Preferences Tab -->
      <div class="tab-pane fade" id="tabPrefs">
        <div class="card">
          <div class="card-body">
            <form method="POST" action="/profile/preferences">
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Language</label>
                  <select name="language" class="form-select">
                    <option value="en" <?= ($user['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="ur" <?= ($user['language'] ?? '') === 'ur' ? 'selected' : '' ?>>Urdu</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Date Format</label>
                  <select name="date_format" class="form-select">
                    <option value="d M Y" <?= ($user['date_format'] ?? 'd M Y') === 'd M Y' ? 'selected' : '' ?>>01 Jan 2024</option>
                    <option value="d/m/Y" <?= ($user['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>01/01/2024</option>
                    <option value="Y-m-d" <?= ($user['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>2024-01-01</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Notifications</label>
                  <div class="d-flex flex-column gap-2">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" name="notify_leave" id="nLeave" value="1" <?= ($user['notify_leave'] ?? 1) ? 'checked' : '' ?>>
                      <label class="form-check-label small" for="nLeave">Leave approval notifications</label>
                    </div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" name="notify_payroll" id="nPayroll" value="1" <?= ($user['notify_payroll'] ?? 1) ? 'checked' : '' ?>>
                      <label class="form-check-label small" for="nPayroll">Payslip ready notifications</label>
                    </div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" name="notify_birthday" id="nBday" value="1" <?= ($user['notify_birthday'] ?? 1) ? 'checked' : '' ?>>
                      <label class="form-check-label small" for="nBday">Birthday reminders</label>
                    </div>
                  </div>
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Preferences</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function toggleField(id, btn) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
  btn.querySelector('i').className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
document.getElementById('newPwd')?.addEventListener('input', function() {
  const v = this.value;
  let s = 0;
  if (v.length >= 8) s++; if (/[A-Z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
  const bar = document.getElementById('pwdStrBar');
  bar.style.width = (s*25) + '%';
  bar.className = 'progress-bar ' + ['','bg-danger','bg-warning','bg-info','bg-success'][s];
});
function previewAndSubmit(input) {
  input.closest('form').submit();
}
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
