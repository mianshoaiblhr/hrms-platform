<?php $pageTitle = 'Leave Calendar'; ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h4 class="mb-1 fw-bold">Leave Calendar</h4><p class="text-muted mb-0 small">View team leave schedule</p></div>
  <div class="d-flex gap-2">
    <a href="/leaves" class="btn btn-outline-secondary btn-sm"><i class="fas fa-list me-1"></i>List View</a>
    <a href="/leaves/apply" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Apply Leave</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div id="leaveCalendar"></div>
  </div>
</div>

<!-- Legend -->
<div class="card mt-3">
  <div class="card-body py-2 d-flex flex-wrap gap-3">
    <div class="d-flex align-items-center gap-1"><span style="width:14px;height:14px;border-radius:3px;background:#22c55e;display:inline-block"></span><span class="small">Approved Leave</span></div>
    <div class="d-flex align-items-center gap-1"><span style="width:14px;height:14px;border-radius:3px;background:#f59e0b;display:inline-block"></span><span class="small">Pending Approval</span></div>
    <div class="d-flex align-items-center gap-1"><span style="width:14px;height:14px;border-radius:3px;background:#ef4444;display:inline-block"></span><span class="small">Rejected</span></div>
    <div class="d-flex align-items-center gap-1"><span style="width:14px;height:14px;border-radius:3px;background:#6366f1;display:inline-block"></span><span class="small">Holiday</span></div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const events = <?= json_encode($calendarEvents ?? []) ?>;
  
  // Add public holidays
  const holidays = <?= json_encode($holidays ?? []) ?>;
  holidays.forEach(h => {
    events.push({
      title: h.name,
      start: h.date,
      color: '#6366f1',
      allDay: true
    });
  });

  const cal = new FullCalendar.Calendar(document.getElementById('leaveCalendar'), {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,listMonth'
    },
    events: events,
    eventClick: function(info) {
      const e = info.event;
      alert(`${e.title}\n${e.start ? e.start.toDateString() : ''} ${e.end ? '→ ' + e.end.toDateString() : ''}\nStatus: ${e.extendedProps.status || 'N/A'}`);
    },
    aspectRatio: 2.2,
    dayMaxEvents: 4,
    eventDisplay: 'block',
    eventTimeFormat: { hour: 'numeric', meridiem: false }
  });
  cal.render();
});
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/resources/views/layouts/main.php'; ?>
