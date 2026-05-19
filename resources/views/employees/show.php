<?php $pageTitle = e($employee['first_name'] . ' ' . $employee['last_name']); ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-1 fw-bold">Employee Profile</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
      <li class="breadcrumb-item"><a href="/employees">Employees</a></li>
      <li class="breadcrumb-item active"><?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></li>
    </ol></nav>
  </div>
  <div class="d-flex gap-2">
    <?php if(can('employees.edit')): ?>
    <a href="/employees/<?= $employee['id'] ?>/edit" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
    <?php endif; ?>
    <a href="/employees" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
  </div>
</div>

<div class="row g-4">
  <!-- Left Column: Profile Card -->
  <div class="col-12 col-lg-4">
    <!-- Profile Card -->
    <div class="card mb-3">
      <div class="card-body text-center py-4">
        <div class="avatar avatar-xl mx-auto mb-3">
          <?php if (!empty($employee['avatar'])): ?>
          <img src="<?= asset('uploads/avatars/' . e($employee['avatar'])) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
          <?php else: ?>
          <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <h5 class="fw-bold mb-0"><?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></h5>
        <div class="text-muted small"><?= e($employee['designation_title'] ?? '—') ?></div>
        <div class="mt-2"><?= statusBadge($employee['status'] ?? 'active') ?></div>
        <hr>
        <div class="text-start">
          <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted"><i class="fas fa-id-badge me-1"></i>Code</span>
            <span class="fw-semibold"><?= e($employee['employee_code']) ?></span>
          </div>
          <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted"><i class="fas fa-building me-1"></i>Department</span>
            <span class="fw-semibold"><?= e($employee['department_name'] ?? '—') ?></span>
          </div>
          <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted"><i class="fas fa-calendar me-1"></i>Joined</span>
            <span class="fw-semibold"><?= formatDate($employee['joining_date']) ?></span>
          </div>
          <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted"><i class="fas fa-briefcase me-1"></i>Type</span>
            <span class="fw-semibold"><?= ucfirst(str_replace('_', ' ', $employee['employment_type'] ?? '')) ?></span>
          </div>
          <?php if (!empty($employee['personal_email'])): ?>
          <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted"><i class="fas fa-envelope me-1"></i>Email</span>
            <a href="mailto:<?= e($employee['personal_email']) ?>" class="fw-semibold small text-truncate"><?= e($employee['personal_email']) ?></a>
          </div>
          <?php endif; ?>
          <?php if (!empty($employee['personal_phone'])): ?>
          <div class="d-flex justify-content-between small">
            <span class="text-muted"><i class="fas fa-phone me-1"></i>Phone</span>
            <span class="fw-semibold"><?= e($employee['personal_phone']) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Leave Balances -->
    <?php if (!empty($leaveBalances)): ?>
    <div class="card mb-3">
      <div class="card-header py-2">
        <h6 class="mb-0 small fw-bold"><i class="fas fa-calendar-check me-1 text-success"></i>Leave Balances</h6>
      </div>
      <div class="card-body py-2">
        <?php foreach ($leaveBalances as $lb): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="small"><?= e($lb['leave_type_name']) ?></span>
          <div class="d-flex gap-1 align-items-center">
            <span class="badge bg-success-subtle text-success"><?= $lb['balance'] ?> left</span>
            <span class="x-small text-muted">/ <?= $lb['allocated'] ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right Column: Tabs -->
  <div class="col-12 col-lg-8">
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabPersonal"><i class="fas fa-user me-1"></i>Personal</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabPayroll"><i class="fas fa-money-bill me-1"></i>Payroll</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAttendance"><i class="fas fa-clock me-1"></i>Attendance</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabLeaves"><i class="fas fa-umbrella-beach me-1"></i>Leaves</a></li>
      <?php if(can('documents.view')): ?>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabDocs"><i class="fas fa-file me-1"></i>Documents</a></li>
      <?php endif; ?>
    </ul>

    <div class="tab-content">
      <!-- Personal Tab -->
      <div class="tab-pane fade show active" id="tabPersonal">
        <div class="card">
          <div class="card-body">
            <div class="row g-3">
              <?php
              $personalFields = [
                'Father\'s Name' => $employee['father_name'] ?? '—',
                'CNIC' => $employee['cnic'] ?? '—',
                'Date of Birth' => formatDate($employee['date_of_birth'] ?? ''),
                'Gender' => ucfirst($employee['gender'] ?? '—'),
                'Marital Status' => ucfirst($employee['marital_status'] ?? '—'),
                'Nationality' => $employee['nationality'] ?? '—',
                'NTN' => $employee['ntn_number'] ?? '—',
                'EOBI No' => $employee['eobi_number'] ?? '—',
                'PESSI No' => $employee['pessi_number'] ?? '—',
                'Bank' => $employee['bank_name'] ?? '—',
                'Account No' => $employee['bank_account'] ?? '—',
              ];
              foreach ($personalFields as $label => $val):
              ?>
              <div class="col-md-6">
                <div class="p-2 bg-light rounded">
                  <div class="x-small text-muted text-uppercase fw-semibold"><?= e($label) ?></div>
                  <div class="small fw-semibold mt-1"><?= e($val) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php if (!empty($employee['present_address'])): ?>
              <div class="col-12">
                <div class="p-2 bg-light rounded">
                  <div class="x-small text-muted text-uppercase fw-semibold">Present Address</div>
                  <div class="small fw-semibold mt-1"><?= nl2br(e($employee['present_address'])) ?></div>
                </div>
              </div>
              <?php endif; ?>
              <?php if (!empty($employee['emergency_contact_name'])): ?>
              <div class="col-md-6">
                <div class="p-2 bg-light rounded">
                  <div class="x-small text-muted text-uppercase fw-semibold">Emergency Contact</div>
                  <div class="small fw-semibold mt-1"><?= e($employee['emergency_contact_name']) ?> — <?= e($employee['emergency_contact_phone'] ?? '') ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Payroll Tab -->
      <div class="tab-pane fade" id="tabPayroll">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 small fw-bold">Salary History</h6>
            <?php if(can('payroll.view')): ?>
            <a href="/payroll?employee=<?= $employee['id'] ?>" class="btn btn-outline-primary btn-xs">View Payslips</a>
            <?php endif; ?>
          </div>
          <div class="card-body p-0">
            <?php if (empty($salaryHistory)): ?>
            <div class="text-center text-muted py-4 small"><i class="fas fa-money-bill-wave fa-2x d-block mb-2 opacity-25"></i>No salary records</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead><tr><th>Period</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($salaryHistory as $s): ?>
                  <tr>
                    <td class="small"><?= e($s['period_label'] ?? $s['payroll_month'] ?? '—') ?></td>
                    <td class="small"><?= formatCurrency($s['gross_salary'] ?? 0) ?></td>
                    <td class="small text-danger"><?= formatCurrency($s['total_deductions'] ?? 0) ?></td>
                    <td class="small fw-semibold text-success"><?= formatCurrency($s['net_salary'] ?? 0) ?></td>
                    <td><?= statusBadge($s['status'] ?? 'draft') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Attendance Tab -->
      <div class="tab-pane fade" id="tabAttendance">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 small fw-bold">Recent Attendance</h6>
            <a href="/attendance?employee=<?= $employee['id'] ?>" class="btn btn-outline-primary btn-xs">Full Report</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($recentAttendance)): ?>
            <div class="text-center text-muted py-4 small"><i class="fas fa-clock fa-2x d-block mb-2 opacity-25"></i>No attendance records</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach (array_slice($recentAttendance, 0, 15) as $a): ?>
                  <tr>
                    <td class="small"><?= formatDate($a['date']) ?></td>
                    <td class="small"><?= $a['check_in'] ? date('h:i A', strtotime($a['check_in'])) : '—' ?></td>
                    <td class="small"><?= $a['check_out'] ? date('h:i A', strtotime($a['check_out'])) : '—' ?></td>
                    <td class="small"><?= $a['working_hours'] ? number_format($a['working_hours'], 1) . 'h' : '—' ?></td>
                    <td><?= statusBadge($a['status'] ?? 'present') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Leaves Tab -->
      <div class="tab-pane fade" id="tabLeaves">
        <div class="card">
          <div class="card-header py-2"><h6 class="mb-0 small fw-bold">Leave Applications</h6></div>
          <div class="card-body p-0">
            <?php if (empty($leaveApplications)): ?>
            <div class="text-center text-muted py-4 small"><i class="fas fa-umbrella-beach fa-2x d-block mb-2 opacity-25"></i>No leave records</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($leaveApplications as $l): ?>
                  <tr>
                    <td class="small"><?= e($l['leave_type_name'] ?? '—') ?></td>
                    <td class="small"><?= formatDate($l['start_date']) ?></td>
                    <td class="small"><?= formatDate($l['end_date']) ?></td>
                    <td class="small"><?= $l['days'] ?></td>
                    <td><?= statusBadge($l['status']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Documents Tab -->
      <?php if(can('documents.view')): ?>
      <div class="tab-pane fade" id="tabDocs">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 small fw-bold">Documents</h6>
            <?php if(can('documents.upload')): ?>
            <a href="/documents/upload?employee=<?= $employee['id'] ?>" class="btn btn-primary btn-xs"><i class="fas fa-plus me-1"></i>Upload</a>
            <?php endif; ?>
          </div>
          <div class="card-body p-0">
            <?php if (empty($documents)): ?>
            <div class="text-center text-muted py-4 small"><i class="fas fa-folder-open fa-2x d-block mb-2 opacity-25"></i>No documents</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead><tr><th>Document</th><th>Type</th><th>Uploaded</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($documents as $doc): ?>
                  <tr>
                    <td><div class="d-flex align-items-center gap-2">
                      <i class="fas fa-file-pdf text-danger"></i>
                      <span class="small"><?= e($doc['title']) ?></span>
                    </div></td>
                    <td><span class="badge bg-light text-dark border small"><?= e($doc['document_type']) ?></span></td>
                    <td class="small text-muted"><?= formatDate($doc['created_at'] ?? '') ?></td>
                    <td>
                      <a href="/documents/<?= $doc['id'] ?>/download" class="btn btn-outline-primary btn-xs"><i class="fas fa-download"></i></a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div><!-- /tab-content -->
  </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
