<?php $pageTitle = 'Edit Role: ' . e($role['name']); ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold">Edit Role: <?= e($role['name']) ?></h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
      <li class="breadcrumb-item"><a href="/roles">Roles</a></li>
      <li class="breadcrumb-item active"><?= e($role['name']) ?></li>
    </ol></nav>
  </div>
  <a href="/roles" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" action="/roles/<?= $role['id'] ?>/permissions">
  <?= csrf_field() ?>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-body">
          <label class="form-label small fw-semibold">Role Name *</label>
          <input type="text" name="name" class="form-control" value="<?= e($role['name']) ?>" <?= $role['is_system'] ? 'readonly' : '' ?>>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-body">
          <label class="form-label small fw-semibold">Description</label>
          <input type="text" name="description" class="form-control" value="<?= e($role['description'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Permissions by Module -->
  <div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="fas fa-key me-1 text-warning"></i>Permissions</h6>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success btn-xs" onclick="toggleAll(true)">Select All</button>
        <button type="button" class="btn btn-outline-danger btn-xs" onclick="toggleAll(false)">Clear All</button>
      </div>
    </div>
    <div class="card-body">
      <?php foreach ($permissionsByModule ?? [] as $module => $perms): ?>
      <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="fw-semibold text-capitalize" style="width:160px"><?= str_replace('_', ' ', $module) ?></div>
          <label class="form-check-label small text-muted ms-auto">
            <input type="checkbox" class="form-check-input module-toggle" data-module="<?= $module ?>"> Select All
          </label>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($perms as $perm): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input perm-check" type="checkbox" name="permissions[]"
              value="<?= $perm['id'] ?>"
              id="perm_<?= $perm['id'] ?>"
              data-module="<?= $module ?>"
              <?= in_array($perm['id'], $rolePermissions ?? []) ? 'checked' : '' ?>
              <?= $role['is_super_admin'] ?? false ? 'checked disabled' : '' ?>>
            <label class="form-check-label small" for="perm_<?= $perm['id'] ?>">
              <?= e(ucfirst(str_replace([$module . '.', '_'], ['', ' '], $perm['slug']))) ?>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
        <hr class="mt-2 mb-0">
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mt-3">
    <a href="/roles" class="btn btn-outline-secondary">Cancel</a>
    <?php if(!($role['is_super_admin'] ?? false)): ?>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Permissions</button>
    <?php endif; ?>
  </div>
</form>

<script>
document.querySelectorAll('.module-toggle').forEach(toggle => {
  toggle.addEventListener('change', function() {
    const module = this.dataset.module;
    document.querySelectorAll(`.perm-check[data-module="${module}"]`).forEach(cb => {
      if (!cb.disabled) cb.checked = this.checked;
    });
  });
});

function toggleAll(state) {
  document.querySelectorAll('.perm-check:not(:disabled)').forEach(cb => cb.checked = state);
  document.querySelectorAll('.module-toggle').forEach(cb => cb.checked = state);
}

// Sync module toggles on load
document.querySelectorAll('.module-toggle').forEach(toggle => {
  const module = toggle.dataset.module;
  const boxes = document.querySelectorAll(`.perm-check[data-module="${module}"]`);
  const checked = [...boxes].filter(c => c.checked).length;
  toggle.checked = checked === boxes.length;
  toggle.indeterminate = checked > 0 && checked < boxes.length;
});
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
