<?php $pageTitle='Dashboard'; ob_start(); ?>
<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Dashboard</h4>
  <p class="text-muted mb-0 small">Welcome back, <strong><?=e(authUser()['username']??'')?></strong> &mdash; <?=date('l, d F Y')?></p></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="stat-card stat-card-blue"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-value"><?=number_format($stats['total_employees'])?></div><div class="stat-label">Total Employees</div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card stat-card-green"><div class="stat-icon"><i class="fas fa-user-check"></i></div><div class="stat-value"><?=number_format($stats['today_present'])?></div><div class="stat-label">Present Today</div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card stat-card-orange"><div class="stat-icon"><i class="fas fa-calendar-times"></i></div><div class="stat-value"><?=number_format($stats['pending_leaves'])?></div><div class="stat-label">Pending Leaves</div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-card stat-card-purple"><div class="stat-icon"><i class="fas fa-tasks"></i></div><div class="stat-value"><?=number_format($stats['pending_tasks'])?></div><div class="stat-label">Pending Tasks</div></div></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Attendance Trend (Last 7 Days)</h6></div><div class="card-body"><canvas id="attendanceChart" height="110"></canvas></div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>By Department</h6></div><div class="card-body d-flex align-items-center justify-content-center"><canvas id="deptChart"></canvas></div></div></div>
</div>
<div class="row g-3">
  <?php if(!empty($myLeaveBalance)): ?>
  <div class="col-lg-4"><div class="card"><div class="card-header"><h6 class="mb-0"><i class="fas fa-umbrella-beach me-2 text-info"></i>My Leave Balance</h6></div>
  <div class="card-body p-0"><div class="list-group list-group-flush">
  <?php foreach($myLeaveBalance as $lb): $pct=$lb['total_days']>0?($lb['remaining_days']/$lb['total_days'])*100:0; ?>
  <div class="list-group-item px-3 py-2">
    <div class="d-flex justify-content-between mb-1"><span class="small fw-semibold"><?=e($lb['leave_type'])?></span><span class="text-success fw-bold small"><?=$lb['remaining_days']?>/<?=$lb['total_days']?> days</span></div>
    <div class="progress" style="height:4px"><div class="progress-bar" style="width:<?=$pct?>%;background:<?=e($lb['color']??'#2563eb')?>"></div></div>
  </div><?php endforeach; ?>
  </div></div></div></div>
  <?php endif; ?>
  <div class="col-lg-<?=!empty($myLeaveBalance)?'8':'12'?>">
    <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="fas fa-calendar-alt me-2 text-warning"></i>Recent Leave Applications</h6>
      <?php if(can('leaves.view')): ?><a href="/leaves" class="btn btn-sm btn-outline-secondary">View All</a><?php endif; ?>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0 small">
      <thead><tr><th>Employee</th><th>Type</th><th>Period</th><th>Days</th><th>Status</th></tr></thead>
      <tbody><?php if(empty($recentLeaves)): ?><tr><td colspan="5" class="text-center text-muted py-4">No leave applications</td></tr>
      <?php else: foreach($recentLeaves as $l): ?>
      <tr><td class="fw-semibold"><?=e($l['employee_name'])?></td><td><span class="badge bg-light text-dark border"><?=e($l['leave_type'])?></span></td>
      <td><?=formatDate($l['from_date'],'d M')?> &ndash; <?=formatDate($l['to_date'],'d M Y')?></td><td class="text-center"><?=$l['days']?></td><td><?=statusBadge($l['status'])?></td></tr>
      <?php endforeach; endif; ?></tbody>
    </table></div></div></div>
  </div>
</div>
<?php if(!empty($pendingApprovals)): ?>
<div class="card mt-3 border-warning"><div class="card-header bg-warning bg-opacity-10"><h6 class="mb-0 text-warning"><i class="fas fa-clock me-2"></i>Pending Approvals</h6></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table mb-0 small">
<thead><tr><th>Type</th><th>Employee</th><th>Date</th><th>Submitted</th><th></th></tr></thead>
<tbody><?php foreach($pendingApprovals as $a): ?>
<tr><td><span class="badge bg-primary"><?=e($a['type'])?></span></td><td class="fw-semibold"><?=e($a['name'])?></td>
<td><?=formatDate($a['date'])?></td><td class="text-muted"><?=timeSince($a['created_at'])?></td>
<td><a href="/leaves/<?=$a['id']?>" class="btn btn-sm btn-outline-primary">Review</a></td></tr>
<?php endforeach; ?></tbody></table></div></div></div>
<?php endif; ?>
<script>
new Chart(document.getElementById('attendanceChart'),{type:'line',data:{labels:<?=json_encode(array_column($attendanceChart,'attendance_date'))?>,datasets:[{label:'Present',data:<?=json_encode(array_column($attendanceChart,'present'))?>,borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,.1)',fill:true,tension:.4},{label:'Absent',data:<?=json_encode(array_column($attendanceChart,'absent'))?>,borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,.1)',fill:true,tension:.4},{label:'Late',data:<?=json_encode(array_column($attendanceChart,'late'))?>,borderColor:'#f59e0b',backgroundColor:'rgba(245,158,11,.1)',fill:true,tension:.4}]},options:{responsive:true,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('deptChart'),{type:'doughnut',data:{labels:<?=json_encode(array_column($deptChart,'name'))?>,datasets:[{data:<?=json_encode(array_column($deptChart,'cnt'))?>,backgroundColor:['#2563eb','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899']}]},options:{responsive:true,cutout:'65%',plugins:{legend:{position:'bottom',labels:{boxWidth:10,font:{size:11}}}}}});
</script>
<?php $content=ob_get_clean(); include ROOT_PATH.'/resources/views/layouts/main.php'; ?>
