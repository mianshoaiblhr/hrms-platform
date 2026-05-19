<?php $pageTitle = 'Role & Permission Management'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Roles & Permissions</h4><p class="text-muted mb-0 small">Manage user roles and access control</p></div>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRoleModal">
    <i class="fas fa-plus me-1"></i>New Role
  </button>
</div>

<div class="row g-3">
  <?php foreach ($roles ?? [] as $role): ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100 <?= $role['is_system'] ? 'border-primary' : '' ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="fw-bold mb-0"><?= e($role['name']) ?></h6>
            <?php if($role['is_system']): ?><span class="badge bg-primary-subtle text-primary x-small">System</span><?php endif; ?>
          </div>
          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-xs" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item small" href="/roles/<?= $role['id'] ?>/edit"><i class="fas fa-edit me-2"></i>Edit Permissions</a></li>
              <?php if(!$role['is_system']): ?>
              <li><form method="POST" action="/roles/<?= $role['id'] ?>/delete">
                <?= csrf_field() ?>
                <button type="submit" class="dropdown-item small text-danger" data-confirm="Delete this role?"><i class="fas fa-trash me-2"></i>Delete</button>
              </form></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <p class="text-muted small mt-2 mb-3"><?= e($role['description'] ?? 'No description') ?></p>
        <div class="d-flex justify-content-between align-items-center">
          <span class="small text-muted"><i class="fas fa-users me-1"></i><?= $role['user_count'] ?? 0 ?> users</span>
          <span class="small text-muted"><i class="fas fa-key me-1"></i><?= $role['permission_count'] ?? 0 ?> permissions</span>
        </div>
      </div>
      <div class="card-footer py-2">
        <a href="/roles/<?= $role['id'] ?>/edit" class="btn btn-outline-primary btn-xs w-100">
          <i class="fas fa-shield-alt me-1"></i>Manage Permissions
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- New Role Modal -->
<div class="modal fade" id="newRoleModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header border-0"><h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Create New Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="/roles">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Role Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Payroll Manager">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Role description..."></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Clone Permissions From</label>
          <select name="clone_from" class="form-select">
            <option value="">Start Fresh</option>
            <?php foreach ($roles ?? [] as $r): ?>
            <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Create Role</button>
      </div>
    </form>
  </div></div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
