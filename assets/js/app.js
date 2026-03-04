/* ═══════════════════════════════════════════════════════════════
   StromTracker – Global JS
   ═══════════════════════════════════════════════════════════════ */

// ── Sidebar toggle ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const openBtn  = document.getElementById('sidebarOpen');
  const closeBtn = document.getElementById('sidebarClose');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('show');
  }
  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('show');
  }

  openBtn?.addEventListener('click', openSidebar);
  closeBtn?.addEventListener('click', closeSidebar);
  overlay?.addEventListener('click', closeSidebar);
});

// ── Toast helper ──────────────────────────────────────────────
function showToast(message, type = 'success') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
  }

  const iconMap = {
    success: 'bi-check-circle-fill text-success',
    error:   'bi-x-circle-fill text-danger',
    warning: 'bi-exclamation-triangle-fill text-warning',
    info:    'bi-info-circle-fill text-info',
  };

  const id   = 'toast_' + Date.now();
  const html = `
    <div id="${id}" class="toast align-items-center border-0" role="alert" data-bs-delay="3500">
      <div class="d-flex" style="background:var(--bg-card2);border-radius:10px;border:1px solid var(--border-col)">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi ${iconMap[type] || iconMap.info}"></i>
          <span style="color:#e0e0e8">${escHtml(message)}</span>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>`;

  container.insertAdjacentHTML('beforeend', html);
  const el   = document.getElementById(id);
  const bsT  = new bootstrap.Toast(el, { autohide: true });
  bsT.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

// ── API helper ────────────────────────────────────────────────
async function apiCall(url, options = {}) {
  const defaults = {
    headers: { 'Content-Type': 'application/json' },
  };
  const merged = { ...defaults, ...options };
  if (merged.body && typeof merged.body !== 'string') {
    merged.body = JSON.stringify(merged.body);
  }
  const res  = await fetch(url, merged);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.error || `HTTP ${res.status}`);
  }
  return data;
}

// ── Number formatting ─────────────────────────────────────────
function fmtKwh(val, dec = 2) {
  return Number(val).toLocaleString('de-DE', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + ' kWh';
}

function fmtEur(val) {
  return Number(val).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
}

function fmtNum(val, dec = 2) {
  return Number(val).toLocaleString('de-DE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

// ── Chart.js defaults ─────────────────────────────────────────
if (typeof Chart !== 'undefined') {
  Chart.defaults.color = 'rgba(255,255,255,.5)';
  Chart.defaults.borderColor = 'rgba(255,255,255,.07)';
  Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.plugins.tooltip.backgroundColor = '#1e2130';
  Chart.defaults.plugins.tooltip.borderColor     = 'rgba(255,255,255,.1)';
  Chart.defaults.plugins.tooltip.borderWidth     = 1;
  Chart.defaults.plugins.tooltip.titleColor      = '#fff';
  Chart.defaults.plugins.tooltip.bodyColor       = 'rgba(255,255,255,.7)';
  Chart.defaults.plugins.tooltip.padding         = 10;
  Chart.defaults.plugins.tooltip.cornerRadius    = 8;
  Chart.defaults.plugins.legend.labels.boxWidth  = 12;
  Chart.defaults.plugins.legend.labels.padding   = 16;
}
