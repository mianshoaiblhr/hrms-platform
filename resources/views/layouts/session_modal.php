<!-- Session Timeout Warning Modal — included in layouts/main.php -->
<div class="modal fade" id="sessionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content border-warning">
    <div class="modal-header bg-warning-subtle border-0 py-2">
      <h6 class="modal-title fw-bold text-warning"><i class="fas fa-clock me-2"></i>Session Expiring</h6>
    </div>
    <div class="modal-body text-center py-3">
      <p class="small mb-3">Your session will expire in <strong id="sessionCountdown">2:00</strong> minutes due to inactivity.</p>
      <div class="d-flex gap-2 justify-content-center">
        <button id="sessionExtend" class="btn btn-primary btn-sm"><i class="fas fa-refresh me-1"></i>Stay Logged In</button>
        <a href="/logout" class="btn btn-outline-secondary btn-sm">Logout</a>
      </div>
    </div>
  </div></div>
</div>

<script>
// Session countdown timer
let countdown = 120;
const countdownEl = document.getElementById('sessionCountdown');
let countdownTimer;

document.getElementById('sessionModal')?.addEventListener('shown.bs.modal', function() {
  countdown = 120;
  countdownTimer = setInterval(() => {
    countdown--;
    const m = Math.floor(countdown / 60);
    const s = countdown % 60;
    if (countdownEl) countdownEl.textContent = m + ':' + String(s).padStart(2,'0');
    if (countdown <= 0) {
      clearInterval(countdownTimer);
      window.location.href = '/logout?reason=timeout';
    }
  }, 1000);
});

document.getElementById('sessionModal')?.addEventListener('hidden.bs.modal', function() {
  clearInterval(countdownTimer);
  countdown = 120;
});
</script>
