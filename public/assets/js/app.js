/**
 * HRMS Enterprise Platform - Main Application JS
 * Handles: sidebar, dark mode, notifications, AJAX, CSRF, search
 */

(function () {
  'use strict';

  // ============================================================
  // CSRF AJAX HELPER
  // ============================================================
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  window.hrmsAjax = function (url, method, data, callback) {
    const opts = {
      method: method || 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF_TOKEN,
        'X-Requested-With': 'XMLHttpRequest'
      }
    };
    if (data && method !== 'GET') opts.body = JSON.stringify(data);
    fetch(url, opts)
      .then(r => r.json())
      .then(callback)
      .catch(err => console.error('AJAX Error:', err));
  };

  // jQuery AJAX CSRF setup
  if (typeof $ !== 'undefined') {
    $.ajaxSetup({
      headers: {
        'X-CSRF-Token': CSRF_TOKEN,
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
  }

  // ============================================================
  // SIDEBAR TOGGLE
  // ============================================================
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const mainContent = document.getElementById('mainContent');

  function toggleSidebar() {
    if (!sidebar) return;
    const isMobile = window.innerWidth < 992;
    if (isMobile) {
      sidebar.classList.toggle('show');
      sidebarOverlay?.classList.toggle('show');
    } else {
      sidebar.classList.toggle('collapsed');
      mainContent?.classList.toggle('expanded');
      localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    }
  }

  sidebarToggle?.addEventListener('click', toggleSidebar);
  sidebarOverlay?.addEventListener('click', () => {
    sidebar?.classList.remove('show');
    sidebarOverlay?.classList.remove('show');
  });

  // Restore sidebar state on desktop
  if (window.innerWidth >= 992 && localStorage.getItem('sidebar_collapsed') === 'true') {
    sidebar?.classList.add('collapsed');
    mainContent?.classList.add('expanded');
  }

  // Active nav highlighting
  const currentPath = window.location.pathname.split('/')[1];
  document.querySelectorAll('.nav-link[href]').forEach(link => {
    const linkPath = link.getAttribute('href').split('/')[1];
    if (linkPath && currentPath === linkPath) {
      link.classList.add('active');
      const parent = link.closest('.nav-submenu');
      if (parent) {
        parent.classList.add('show');
        parent.previousElementSibling?.classList.add('active');
      }
    }
  });

  // Submenu toggles
  document.querySelectorAll('.nav-link[data-submenu]').forEach(toggle => {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.dataset.submenu);
      if (!target) return;
      const wasOpen = target.classList.contains('show');
      document.querySelectorAll('.nav-submenu.show').forEach(sub => sub.classList.remove('show'));
      document.querySelectorAll('.nav-link[data-submenu].active').forEach(t => t.classList.remove('active'));
      if (!wasOpen) {
        target.classList.add('show');
        this.classList.add('active');
      }
    });
  });

  // ============================================================
  // DARK MODE
  // ============================================================
  const darkToggle = document.getElementById('darkModeToggle');
  const darkIcon = document.getElementById('darkModeIcon');

  function applyDarkMode(dark) {
    document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
    document.body.classList.toggle('dark-mode', dark);
    if (darkIcon) darkIcon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
    localStorage.setItem('dark_mode', dark ? '1' : '0');
  }

  // Init dark mode
  const savedDark = localStorage.getItem('dark_mode') === '1';
  applyDarkMode(savedDark);

  darkToggle?.addEventListener('click', () => {
    const isDark = document.body.classList.contains('dark-mode');
    applyDarkMode(!isDark);
    // Save preference via AJAX
    hrmsAjax('/profile/theme', 'POST', { dark_mode: !isDark }, () => {});
  });

  // ============================================================
  // NOTIFICATIONS
  // ============================================================
  const notifBadge = document.getElementById('notifBadge');
  const notifDropdown = document.getElementById('notifDropdown');

  function loadNotifications() {
    if (!notifDropdown) return;
    hrmsAjax('/notifications/unread', 'GET', null, function (data) {
      if (!data.notifications) return;
      notifBadge.style.display = data.count > 0 ? 'inline-block' : 'none';
      notifBadge.textContent = data.count > 9 ? '9+' : data.count;
      let html = '';
      if (data.notifications.length === 0) {
        html = '<div class="dropdown-item text-center text-muted py-3"><i class="fas fa-bell-slash me-1"></i>No new notifications</div>';
      } else {
        data.notifications.forEach(n => {
          html += `<a class="dropdown-item notif-item py-2 px-3 ${n.read_at ? '' : 'unread'}" href="${n.url || '#'}" data-id="${n.id}">
            <div class="d-flex gap-2">
              <div class="notif-icon ${n.type || 'info'}"><i class="fas fa-${n.icon || 'bell'}"></i></div>
              <div class="flex-grow-1">
                <div class="small fw-semibold">${e(n.title)}</div>
                <div class="x-small text-muted">${e(n.message)}</div>
                <div class="x-small text-muted mt-1">${n.time_ago}</div>
              </div>
            </div>
          </a>`;
        });
        html += '<div class="dropdown-divider my-0"></div><a href="/notifications" class="dropdown-item text-center small text-primary py-2">View all notifications</a>';
      }
      notifDropdown.innerHTML = html;

      // Mark read on click
      notifDropdown.querySelectorAll('.notif-item').forEach(item => {
        item.addEventListener('click', function () {
          hrmsAjax('/notifications/mark-read', 'POST', { id: this.dataset.id }, () => loadNotifications());
        });
      });
    });
  }

  // Load notifications on page load and every 60 seconds
  loadNotifications();
  setInterval(loadNotifications, 60000);

  // Mark all read
  document.getElementById('markAllRead')?.addEventListener('click', function (e) {
    e.preventDefault();
    hrmsAjax('/notifications/mark-all-read', 'POST', {}, () => loadNotifications());
  });

  // ============================================================
  // GLOBAL SEARCH
  // ============================================================
  const globalSearch = document.getElementById('globalSearch');
  const searchResults = document.getElementById('searchResults');
  let searchTimer;

  globalSearch?.addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) {
      searchResults.classList.remove('show');
      searchResults.innerHTML = '';
      return;
    }
    searchTimer = setTimeout(() => {
      hrmsAjax('/search?q=' + encodeURIComponent(q), 'GET', null, function (data) {
        if (!data.results || data.results.length === 0) {
          searchResults.innerHTML = '<div class="dropdown-item text-muted small">No results found</div>';
        } else {
          let html = '';
          data.results.forEach(r => {
            html += `<a class="dropdown-item py-2" href="${r.url}">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary-subtle text-secondary">${e(r.type)}</span>
                <span class="small">${e(r.title)}</span>
              </div>
            </a>`;
          });
          searchResults.innerHTML = html;
        }
        searchResults.classList.add('show');
      });
    }, 300);
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('#searchWrapper')) {
      searchResults?.classList.remove('show');
    }
  });

  // ============================================================
  // FLASH MESSAGE AUTO-DISMISS
  // ============================================================
  setTimeout(() => {
    document.querySelectorAll('.alert-dismissible.auto-dismiss').forEach(alert => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert?.close();
    });
  }, 5000);

  // ============================================================
  // CONFIRM DIALOGS
  // ============================================================
  window.confirmAction = function (message, form) {
    if (confirm(message)) {
      if (form) form.submit();
      return true;
    }
    return false;
  };

  // Data-confirm attribute handling
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
  });

  // ============================================================
  // FORM ENHANCEMENTS
  // ============================================================

  // Auto-format CNIC
  document.querySelectorAll('input[name="cnic"]').forEach(input => {
    input.addEventListener('input', function () {
      let val = this.value.replace(/\D/g, '');
      if (val.length > 5 && val.length <= 12) val = val.slice(0, 5) + '-' + val.slice(5);
      if (val.length > 13) val = val.slice(0, 13) + '-' + val.slice(13);
      this.value = val.slice(0, 15);
    });
  });

  // Auto-format phone
  document.querySelectorAll('input[type="tel"]').forEach(input => {
    input.addEventListener('input', function () {
      this.value = this.value.replace(/[^\d\+\-\s]/g, '');
    });
  });

  // Prevent double-submit
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function () {
      const btn = this.querySelector('[type="submit"]');
      if (btn && !btn.dataset.noLock) {
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = btn.dataset.originalText;
        }, 8000);
      }
    });
  });

  // ============================================================
  // DATATABLES ENHANCEMENT
  // ============================================================
  window.initDataTable = function (selector, opts) {
    if (typeof $.fn?.DataTable === 'undefined') return;
    $(selector).DataTable(Object.assign({
      responsive: true,
      pageLength: 25,
      language: {
        search: '<i class="fas fa-search me-1"></i>',
        searchPlaceholder: 'Search...'
      }
    }, opts || {}));
  };

  // ============================================================
  // TOOLTIPS & POPOVERS
  // ============================================================
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
    new bootstrap.Popover(el);
  });

  // ============================================================
  // SELECT2 INIT
  // ============================================================
  window.initSelect2 = function (selector) {
    if (typeof $.fn?.select2 === 'undefined') return;
    $(selector || '.select2').select2({ theme: 'bootstrap-5', width: '100%' });
  };

  // ============================================================
  // DATE PICKER
  // ============================================================
  window.initDatePicker = function (selector) {
    if (typeof $.fn?.datepicker === 'undefined') return;
    $(selector || '.datepicker').datepicker({ format: 'yyyy-mm-dd', autoclose: true });
  };

  // ============================================================
  // PRINT HELPERS
  // ============================================================
  window.printSection = function (id) {
    const content = document.getElementById(id)?.innerHTML;
    if (!content) return;
    const w = window.open('', '_blank');
    w.document.write('<html><head><title>Print</title>');
    w.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">');
    w.document.write('</head><body>' + content + '</body></html>');
    w.document.close();
    w.print();
  };

  // ============================================================
  // UTILITY: HTML Escape
  // ============================================================
  function e(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
  }

  // ============================================================
  // SESSION TIMEOUT WARNING
  // ============================================================
  let sessionTimer;
  const SESSION_TIMEOUT = 28 * 60 * 1000; // 28 min warning (30 min timeout)

  function resetSessionTimer() {
    clearTimeout(sessionTimer);
    sessionTimer = setTimeout(() => {
      const modal = document.getElementById('sessionModal');
      if (modal) new bootstrap.Modal(modal).show();
    }, SESSION_TIMEOUT);
  }

  document.addEventListener('click', resetSessionTimer);
  document.addEventListener('keypress', resetSessionTimer);
  resetSessionTimer();

  document.getElementById('sessionExtend')?.addEventListener('click', () => {
    hrmsAjax('/session/extend', 'POST', {}, () => {
      resetSessionTimer();
      bootstrap.Modal.getInstance(document.getElementById('sessionModal'))?.hide();
    });
  });

  // ============================================================
  // RESPONSIVE TABLE SCROLL INDICATOR
  // ============================================================
  document.querySelectorAll('.table-responsive').forEach(wrapper => {
    if (wrapper.scrollWidth > wrapper.clientWidth) {
      wrapper.classList.add('has-scroll');
    }
  });

  // ============================================================
  // KEYBOARD SHORTCUTS
  // ============================================================
  document.addEventListener('keydown', function (e) {
    // Ctrl+/ or Cmd+/ = focus search
    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
      e.preventDefault();
      globalSearch?.focus();
    }
    // Escape = close modals, clear search
    if (e.key === 'Escape') {
      searchResults?.classList.remove('show');
    }
  });

  console.log('%cHRMS Enterprise Platform', 'color:#4f46e5;font-size:16px;font-weight:bold;');
  console.log('%cSecured with CSRF, XSS protection, RBAC', 'color:#10b981;font-size:12px;');

})();
