<?php $pageTitle = 'User Management'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold">User Management</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
      <li class="breadcrumb-item"><a href="/settings">Settings</a></li>
      <li class="breadcrumb-item active">Users</li>
    </ol></nav>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
    <i class="fas fa-plus me-1"></i>Add User
  </button>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Name, username, email..." value="<?= e($filters['search'] ?? '') ?>"></div>
      <div class="col-md-2">
        <select name="role_id" class="form-select form-select-sm">
          <option value="">All Roles</option>
          <?php foreach ($roles ?? [] as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($filters['role_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="1" <?= ($filters['status'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= ($filters['status'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
        <a href="/settings/users" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr><th>User</th><th>Role</th><th>Employee</th><th>Last Login</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($data['data'])): ?>
          <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-users-cog fa-2x d-block mb-2 opacity-25"></i>No users found</td></tr>
          <?php else: foreach ($data['data'] as $u): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm"><?= strtoupper(substr($u['name'] ?? 'U', 0, 2)) ?></div>
                <div>
                  <div class="fw-semibold small"><?= e($u['name']) ?></div>
                  <div class="x-small text-muted"><?= e($u['email']) ?></div>
                  <div class="x-small text-muted">@<?= e($u['username']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge bg-primary-subtle text-primary"><?= e($u['role_name'] ?? '—') ?></span></td>
            <td class="small"><?= e($u['employee_name'] ?? '—') ?></td>
            <td class="small text-muted"><?= $u['last_login_at'] ? date('d M Y H:i', strtotime($u['last_login_at'])) : 'Never' ?></td>
            <td><?= $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?></td>
            <td class="text-end">
              <div class="dropdown">
                <button class="btn btn-outline-secondary btn-xs" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><button class="dropdown-item small" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)"><i class="fas fa-edit me-2"></i>Edit</button></li>
                  <li>
                    <form method="POST" action="/settings/users/<?= $u['id'] ?>/toggle-status">
                      <?= csrf_field() ?>
                      <button type="submit" class="dropdown-item small <?= $u['is_active'] ? 'text-warning' : 'text-success' ?>">
                        <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?> me-2"></i><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                      </button>
                    </form>
                  </li>
                  <li>
                    <form method="POST" action="/settings/users/<?= $u['id'] ?>/reset-password">
                      <?= csrf_field() ?>
                      <button type="submit" class="dropdown-item small" data-confirm="Send password reset email?"><i class="fas fa-key me-2"></i>Reset Password</button>
                    </form>
                  </li>
                  <?php if(($u['id'] ?? 0) !== (authUser()['id'] ?? -1)): ?>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form method="POST" action="/settings/users/<?= $u['id'] ?>/force-logout">
                      <?= csrf_field() ?>
                      <button type="submit" class="dropdown-item small text-danger" data-confirm="Force logout this user?"><i class="fas fa-sign-out-alt me-2"></i>Force Logout</button>
                    </form>
                  </li>
                  <?php endif; ?>
                </ul>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if (($data['last_page'] ?? 1) > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= count($data['data'] ?? []) ?> of <?= $data['total'] ?> users</small>
    <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/settings/users') ?>
  </div>
  <?php endif; ?>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title"><i class="fas fa-user-plus me-2 text-primary"></i>Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="/settings/users"><div class="modal-body"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label small fw-semibold">Full Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Username *</label><input type="text" name="username" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Role *</label>
        <select name="role_id" class="form-select" required>
          <?php foreach ($roles ?? [] as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Password *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Link Employee</label>
        <select name="employee_id" class="form-select">
          <option value="">None</option>
          <?php foreach ($employees ?? [] as $emp): ?><option value="<?= $emp['id'] ?>"><?= e($emp['employee_code'] . ' — ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Create User</button></div>
  </form>
</div></div></div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title"><i class="fas fa-user-edit me-2 text-warning"></i>Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="editUserForm"><div class="modal-body"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label small fw-semibold">Full Name *</label><input type="text" name="name" id="eu_name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" id="eu_email" class="form-control"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Role *</label>
        <select name="role_id" id="eu_role" class="form-select" required>
          <?php foreach ($roles ?? [] as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-save me-1"></i>Update</button></div>
  </form>
</div></div></div>

<script>
function editUser(u) {
  document.getElementById('editUserForm').action = '/settings/users/' + u.id + '/update';
  document.getElementById('eu_name').value  = u.name  || '';
  document.getElementById('eu_email').value = u.email || '';
  document.getElementById('eu_role').value  = u.role_id || '';
  new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
