/**
 * CivicScan – App JavaScript
 */

// ─── Theme Toggle ─────────────────────────────────────────────────────────────
function toggleTheme() {
  const html  = document.documentElement;
  const isDark = html.classList.contains('dark');
  html.classList.toggle('dark', !isDark);
  html.setAttribute('data-theme', isDark ? 'light' : 'dark');

  // Update icons
  document.getElementById('theme-sun')?.classList.toggle('hidden', !isDark);
  document.getElementById('theme-moon')?.classList.toggle('hidden', isDark);

  // Persist via AJAX
  fetch(CIVICSCAN.url + '/modules/settings/theme_ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CIVICSCAN.csrf},
    body: 'theme=' + (isDark ? 'light' : 'dark')
  });
}

// ─── Sidebar Toggle ────────────────────────────────────────────────────────────
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  const isOpen  = !sidebar.classList.contains('-translate-x-full');
  sidebar.classList.toggle('-translate-x-full', isOpen);
  overlay.classList.toggle('hidden', isOpen);
}

// ─── User Menu ─────────────────────────────────────────────────────────────────
function toggleUserMenu() {
  const menu = document.getElementById('user-menu');
  menu?.classList.toggle('hidden');
}
document.addEventListener('click', (e) => {
  const menu = document.getElementById('user-menu');
  if (menu && !e.target.closest('[onclick="toggleUserMenu()"]')) {
    menu.classList.add('hidden');
  }
});

// ─── Modal Helpers ─────────────────────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.style.overflow = '';
  }
}

// Close modal on backdrop click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.add('hidden');
    e.target.classList.remove('flex');
    document.body.style.overflow = '';
  }
});

// ─── Confirm Delete ────────────────────────────────────────────────────────────
function confirmDelete(url, label = 'this record') {
  const modal = document.getElementById('confirm-modal');
  const msg   = document.getElementById('confirm-msg');
  const btn   = document.getElementById('confirm-btn');
  if (msg)  msg.textContent = `Are you sure you want to delete ${label}? This action cannot be undone.`;
  if (btn)  btn.onclick = () => { window.location.href = url; };
  openModal('confirm-modal');
}

// ─── File Drop Zone ─────────────────────────────────────────────────────────────
function initDropZone(zoneId, inputId) {
  const zone  = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  if (!zone || !input) return;

  zone.addEventListener('click', () => input.click());

  ['dragenter','dragover'].forEach(ev => {
    zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('dragover'); });
  });
  ['dragleave','drop'].forEach(ev => {
    zone.addEventListener(ev, () => zone.classList.remove('dragover'));
  });

  zone.addEventListener('drop', e => {
    e.preventDefault();
    const files = e.dataTransfer.files;
    if (files.length) {
      input.files = files;
      updateDropZoneLabel(zoneId, files[0]);
    }
  });

  input.addEventListener('change', () => {
    if (input.files.length) updateDropZoneLabel(zoneId, input.files[0]);
  });
}

function updateDropZoneLabel(zoneId, file) {
  const zone = document.getElementById(zoneId);
  const lbl  = zone?.querySelector('.drop-label');
  if (lbl) lbl.textContent = `Selected: ${file.name} (${(file.size/1048576).toFixed(1)} MB)`;
}

// ─── Form Validation Helper ────────────────────────────────────────────────────
function showFieldError(inputId, msg) {
  const input = document.getElementById(inputId);
  const err   = document.getElementById(inputId + '_error');
  input?.classList.add('error');
  if (err) err.textContent = msg;
}
function clearFieldError(inputId) {
  const input = document.getElementById(inputId);
  const err   = document.getElementById(inputId + '_error');
  input?.classList.remove('error');
  if (err) err.textContent = '';
}

// ─── Toast Notification ────────────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const map = {
    success: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
    error:   'bg-red-500/10 border-red-500/30 text-red-400',
    info:    'bg-blue-500/10 border-blue-500/30 text-blue-400',
  };
  const div = document.createElement('div');
  div.className = `fixed bottom-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-xl border text-sm animate-fade-in ${map[type] || map.info}`;
  div.innerHTML = `<span>${msg}</span><button onclick="this.closest('div').remove()" class="ml-2 opacity-60 hover:opacity-100">✕</button>`;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 5000);
}

// ─── Table Sort ───────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('th[data-sort]').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
      const url  = new URL(window.location);
      const col  = th.dataset.sort;
      const cur  = url.searchParams.get('sort');
      const dir  = (cur === col && url.searchParams.get('dir') === 'asc') ? 'desc' : 'asc';
      url.searchParams.set('sort', col);
      url.searchParams.set('dir', dir);
      url.searchParams.set('page', 1);
      window.location.href = url.toString();
    });
  });

  // Init theme icons
  const isDark = document.documentElement.classList.contains('dark');
  document.getElementById('theme-sun')?.classList.toggle('hidden', isDark);
  document.getElementById('theme-moon')?.classList.toggle('hidden', !isDark);

  // Auto-dismiss flashes
  setTimeout(() => {
    document.getElementById('flash-container')?.remove();
  }, 6000);
});

// ─── AJAX Status Toggle ────────────────────────────────────────────────────────
function toggleStatus(btn, url) {
  fetch(url, {
    method: 'POST',
    headers: {'X-CSRF-Token': CIVICSCAN.csrf}
  })
  .then(r => r.json())
  .then(d => {
    if (d.status) {
      btn.dataset.status = d.status;
      const badge = btn.closest('tr')?.querySelector('.status-badge');
      if (badge) badge.outerHTML = d.badge;
      toast('Status updated', 'success');
    }
  })
  .catch(() => toast('Failed to update status', 'error'));
}

// ─── Search with debounce ──────────────────────────────────────────────────────
function debounce(fn, ms = 350) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

const liveSearch = debounce((input) => {
  const url = new URL(window.location);
  url.searchParams.set('q', input.value);
  url.searchParams.set('page', 1);
  window.location.href = url.toString();
});

// Keyboard shortcut: Cmd+K / Ctrl+K → focus voter search
document.addEventListener('keydown', (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault();
    const input = document.querySelector('[name="q"]');
    input ? input.focus() : (window.location.href = CIVICSCAN.url + '/modules/voters/search.php');
  }
});
