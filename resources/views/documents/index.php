<?php $pageTitle = 'Document Management'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Document Management</h4><p class="text-muted mb-0 small">Secure employee document storage</p></div>
  <?php if(can('documents.upload')): ?>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
    <i class="fas fa-upload me-1"></i>Upload Document
  </button>
  <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" action="/documents" class="row g-2 align-items-center">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search documents..." value="<?= e($filters['search'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
          <option value="">All Types</option>
          <option value="contract" <?= ($filters['type'] ?? '') === 'contract' ? 'selected' : '' ?>>Contract</option>
          <option value="cnic" <?= ($filters['type'] ?? '') === 'cnic' ? 'selected' : '' ?>>CNIC</option>
          <option value="certificate" <?= ($filters['type'] ?? '') === 'certificate' ? 'selected' : '' ?>>Certificate</option>
          <option value="offer_letter" <?= ($filters['type'] ?? '') === 'offer_letter' ? 'selected' : '' ?>>Offer Letter</option>
          <option value="termination" <?= ($filters['type'] ?? '') === 'termination' ? 'selected' : '' ?>>Termination</option>
          <option value="other" <?= ($filters['type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <?php if(can('documents.view_all')): ?>
      <div class="col-md-2">
        <select name="department_id" class="form-select form-select-sm">
          <option value="">All Departments</option>
          <?php foreach ($departments ?? [] as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
        <a href="/documents" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Documents Grid -->
<div class="row g-3 mb-3">
  <?php if (empty($data['data'])): ?>
  <div class="col-12">
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="fas fa-folder-open fa-3x mb-3 opacity-25 d-block"></i>No documents found
    </div></div>
  </div>
  <?php else: foreach ($data['data'] as $doc): ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start gap-3">
          <div class="rounded p-2 bg-<?= $doc['file_type'] === 'pdf' ? 'danger' : 'primary' ?>-subtle flex-shrink-0">
            <i class="fas fa-file-<?= $doc['file_type'] === 'pdf' ? 'pdf text-danger' : 'alt text-primary' ?> fa-lg"></i>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold small text-truncate"><?= e($doc['title']) ?></div>
            <div class="x-small text-muted"><?= e($doc['employee_name'] ?? 'N/A') ?></div>
            <div class="d-flex gap-2 mt-1">
              <span class="badge bg-light text-dark border small"><?= e($doc['document_type']) ?></span>
              <span class="x-small text-muted"><?= formatFileSize($doc['file_size'] ?? 0) ?></span>
            </div>
            <div class="x-small text-muted mt-1"><?= formatDate($doc['created_at'] ?? '') ?></div>
          </div>
        </div>
      </div>
      <div class="card-footer py-2 d-flex gap-2">
        <?php if(can('documents.download')): ?>
        <a href="/documents/<?= $doc['id'] ?>/download" class="btn btn-outline-primary btn-xs flex-grow-1"><i class="fas fa-download me-1"></i>Download</a>
        <?php endif; ?>
        <?php if(can('documents.delete')): ?>
        <form method="POST" action="/documents/<?= $doc['id'] ?>/delete" class="d-inline">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-danger btn-xs" data-confirm="Delete this document?"><i class="fas fa-trash"></i></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<?php if (($data['last_page'] ?? 1) > 1): ?>
<div class="d-flex justify-content-center">
  <?= paginator($data['total'], $data['per_page'], $data['current_page'], '/documents') ?>
</div>
<?php endif; ?>

<!-- Upload Modal -->
<?php if(can('documents.upload')): ?>
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header border-0"><h5 class="modal-title"><i class="fas fa-upload me-2 text-primary"></i>Upload Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="/documents/upload" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-semibold">Employee *</label>
            <select name="employee_id" class="form-select" required>
              <option value="">Select Employee</option>
              <?php foreach ($employees ?? [] as $emp): ?>
              <option value="<?= $emp['id'] ?>"><?= e($emp['employee_code'] . ' — ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label small fw-semibold">Document Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Employment Contract 2024">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Type *</label>
            <select name="document_type" class="form-select" required>
              <option value="contract">Contract</option>
              <option value="cnic">CNIC</option>
              <option value="certificate">Certificate</option>
              <option value="offer_letter">Offer Letter</option>
              <option value="termination">Termination</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">File *</label>
            <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <small class="text-muted">Allowed: PDF, DOC, DOCX, JPG, PNG — Max 10MB</small>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input type="checkbox" name="is_confidential" value="1" class="form-check-input" id="confCheck">
              <label class="form-check-label small" for="confCheck"><i class="fas fa-lock me-1 text-warning"></i>Mark as Confidential (Restricted Access)</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i>Upload</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
