<?php $pageTitle = 'Reports'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Reports & Analytics</h4><p class="text-muted mb-0 small">Generate and export system reports</p></div>
</div>

<div class="row g-4">
  <?php
  $reportCards = [
    ['title'=>'Payroll Report','desc'=>'Monthly salary summary, tax deductions, and net payments','icon'=>'money-check-alt','color'=>'primary','url'=>'/reports/payroll','perm'=>'reports.payroll'],
    ['title'=>'Attendance Report','desc'=>'Employee attendance, late arrivals, absences, and overtime','icon'=>'clock','color'=>'success','url'=>'/reports/attendance','perm'=>'reports.attendance'],
    ['title'=>'Tax Report (FBR)','desc'=>'Income tax deductions per employee for FBR compliance','icon'=>'file-invoice-dollar','color'=>'warning','url'=>'/reports/tax','perm'=>'reports.tax'],
    ['title'=>'EOBI / PESSI Report','desc'=>'Social security contributions for compliance filing','icon'=>'shield-alt','color'=>'info','url'=>'/reports/eobi','perm'=>'reports.eobi'],
    ['title'=>'Employee Report','desc'=>'Employee demographics, departments, and employment status','icon'=>'users','color'=>'secondary','url'=>'/reports/employees','perm'=>'reports.employees'],
    ['title'=>'Leave Report','desc'=>'Leave utilization, balances, and trend analysis','icon'=>'umbrella-beach','color'=>'danger','url'=>'/reports/leaves','perm'=>'reports.leaves'],
  ];
  foreach ($reportCards as $r):
    if (!can($r['perm'])) continue;
  ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100 card-hover">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-3 p-3 bg-<?= $r['color'] ?>-subtle">
            <i class="fas fa-<?= $r['icon'] ?> fa-lg text-<?= $r['color'] ?>"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-0"><?= $r['title'] ?></h6>
            <div class="small text-muted"><?= $r['desc'] ?></div>
          </div>
        </div>
        <a href="<?= $r['url'] ?>" class="btn btn-<?= $r['color'] ?> btn-sm w-100">
          <i class="fas fa-chart-bar me-1"></i>Generate Report
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<style>.card-hover:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.1);transition:all .2s}</style>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
