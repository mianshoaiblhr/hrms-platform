<?php $pageTitle = 'Dashboard'; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'fas fa-users','bg'=>'rgba(79,70,229,.1)','color'=>'#4f46e5','label'=>'Active Employees','value'=>$stats['total_employees']??0],
    ['icon'=>'fas fa-clock','bg'=>'rgba(245,158,11,.1)','color'=>'#f59e0b','label'=>'Pending Leaves','value'=>$stats['pending_leaves']??0],
    ['icon'=>'fas fa-circle-check','bg'=>'rgba(16,185,129,.1)','color'=>'#10b981','label'=>'Present Today','value'=>$stats['today_present']??0],
    ['icon'=>'fas fa-circle-xmark','bg'=>'rgba(239,68,68,.1)','color'=>'#ef4444','label'=>'Absent Today','value'=>$stats['today_absent']??0],
    ['icon'=>'fas fa-list-check','bg'=>'rgba(99,102,241,.1)','color'=>'#6366f1','label'=>'Pending Tasks','value'=>$stats['pending_tasks']??0],
    ['icon'=>'fas fa-bell','bg'=>'rgba(168,85,247,.1)','color'=>'#a855f7','label'=>'Notifications','value'=>$stats['unread_notifs']??0],
  ];
  foreach($cards as $c):
  ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?=$c['bg']?>">
        <i class="<?=$c['icon']?>" style="color:<?=$c['color']?>"></i>
      </div>
      <div class="stat-value"><?= number_format($c['value']) ?></div>
      <div class="stat-label"><?= $c['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <!-- Attendance Chart -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header">
        <span><i class="fas fa-chart-line me-2 text-primary"></i>Attendance — Last 7 Days</span>
      </div>
      <div class="card-body">
        <canvas id="attChart" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- Department Breakdown -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <span><i class="fas fa-chart-pie me-2 text-primary"></i>By Department</span>
      </div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <?php if (!empty($deptChart)): ?>
        <canvas id="deptChart" height="180"></canvas>
        <?php else: ?>
        <div class="text-center text-muted py-4">
          <i class="fas fa-sitemap fa-2x d-block mb-2 opacity-25"></i>No data yet
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Recent Leaves -->
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <span><i class="fas fa-calendar-minus me-2 text-primary"></i>Recent Leave Requests</span>
        <a href="/leaves" class="btn btn-sm btn-outline-primary" style="font-size:.72rem;padding:4px 10px;border-radius:8px">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($recentLeaves)): ?>
        <div class="text-center text-muted py-4 small">
          <i class="fas fa-calendar-check fa-2x d-block mb-2 opacity-25"></i>No leave requests
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead><tr><th>Employee</th><th>Type</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($recentLeaves as $l): ?>
            <tr>
              <td class="fw-semibold"><?= e($l['employee_name']??'—') ?></td>
              <td><?= e($l['leave_type']??'—') ?></td>
              <td class="text-muted"><?= $l['days']??1 ?> day<?= ($l['days']??1)>1?'s':'' ?></td>
              <td><span class="badge status-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent Employees -->
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <span><i class="fas fa-user-plus me-2 text-primary"></i>Recently Added</span>
        <a href="/employees" class="btn btn-sm btn-outline-primary" style="font-size:.72rem;padding:4px 10px;border-radius:8px">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($recentEmployees)): ?>
        <div class="text-center text-muted py-4 small">
          <i class="fas fa-users fa-2x d-block mb-2 opacity-25"></i>No employees yet
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead><tr><th>Name</th><th>Department</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($recentEmployees as $e): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm"><?= strtoupper(substr($e['first_name']??'?',0,1)) ?></div>
                  <div>
                    <div class="fw-semibold" style="font-size:.83rem"><?= e(($e['first_name']??'').' '.($e['last_name']??'')) ?></div>
                    <div class="text-muted x-small"><?= e($e['employee_code']??'') ?></div>
                  </div>
                </div>
              </td>
              <td class="text-muted" style="font-size:.83rem"><?= e($e['dept_name']??'—') ?></td>
              <td><span class="badge status-<?= $e['status']??'active' ?>"><?= ucfirst($e['status']??'active') ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Payroll Summary -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><span><i class="fas fa-money-check-dollar me-2 text-primary"></i>Payroll Summary</span></div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted small">Total Payrolls Run</span>
          <span class="fw-semibold"><?= number_format($payrollSummary['total_payrolls']??0) ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span class="text-muted small">Total Net Paid</span>
          <span class="fw-semibold text-success">PKR <?= number_format($payrollSummary['total_net']??0) ?></span>
        </div>
        <a href="/payroll" class="btn btn-primary btn-sm w-100 mt-3">
          <i class="fas fa-plus me-1"></i>Process Payroll
        </a>
      </div>
    </div>
  </div>

  <!-- Upcoming Birthdays -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><span><i class="fas fa-cake-candles me-2 text-primary"></i>Upcoming Birthdays</span></div>
      <div class="card-body p-0">
        <?php if(empty($birthdays)): ?>
        <div class="text-center text-muted py-4 small px-3">
          <i class="fas fa-cake-candles fa-2x d-block mb-2 opacity-25"></i>No birthdays this week
        </div>
        <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach($birthdays as $b): ?>
          <li class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
            <div class="avatar avatar-sm"><?= strtoupper(substr($b['first_name']??'?',0,1)) ?></div>
            <div>
              <div class="fw-semibold small"><?= e(($b['first_name']??'').' '.($b['last_name']??'')) ?></div>
              <div class="x-small text-muted"><?= $b['date_of_birth'] ? date('d M', strtotime($b['date_of_birth'])) : '' ?></div>
            </div>
            <i class="fas fa-gift text-pink ms-auto" style="color:#ec4899"></i>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><span><i class="fas fa-bolt me-2 text-primary"></i>Quick Actions</span></div>
      <div class="card-body d-flex flex-column gap-2">
        <?php if(can('employees.create')): ?>
        <a href="/employees/create" class="btn btn-outline-primary btn-sm text-start">
          <i class="fas fa-user-plus me-2"></i>Add Employee
        </a>
        <?php endif; ?>
        <?php if(can('payroll.process')): ?>
        <a href="/payroll" class="btn btn-outline-success btn-sm text-start">
          <i class="fas fa-money-check-dollar me-2"></i>Process Payroll
        </a>
        <?php endif; ?>
        <a href="/leaves/apply" class="btn btn-outline-warning btn-sm text-start">
          <i class="fas fa-calendar-plus me-2"></i>Apply for Leave
        </a>
        <a href="/reports" class="btn btn-outline-secondary btn-sm text-start">
          <i class="fas fa-chart-bar me-2"></i>Generate Report
        </a>
      </div>
    </div>
  </div>
</div>

<script>
// Attendance chart
<?php if(!empty($attendanceChart)): ?>
new Chart(document.getElementById('attChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($attendanceChart, 'attendance_date')) ?>,
    datasets: [
      {label:'Present',data:<?= json_encode(array_column($attendanceChart,'present')) ?>,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.1)',tension:.4,fill:true},
      {label:'Absent', data:<?= json_encode(array_column($attendanceChart,'absent'))  ?>,borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,.05)',tension:.4,fill:true},
      {label:'Late',   data:<?= json_encode(array_column($attendanceChart,'late'))    ?>,borderColor:'#f59e0b',backgroundColor:'transparent',tension:.4,borderDash:[4,4]},
    ]
  },
  options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}
});
<?php else: ?>
document.getElementById('attChart').parentElement.innerHTML='<div class="text-center text-muted py-4"><i class="fas fa-chart-line fa-2x d-block mb-2 opacity-25"></i><small>No attendance data yet</small></div>';
<?php endif; ?>

// Dept doughnut
<?php if(!empty($deptChart)): ?>
new Chart(document.getElementById('deptChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($deptChart,'name')) ?>,
    datasets:[{data:<?= json_encode(array_column($deptChart,'cnt')) ?>,backgroundColor:['#4f46e5','#7c3aed','#10b981','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#ec4899']}]
  },
  options:{responsive:true,cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}
});
<?php endif; ?>
</script>
