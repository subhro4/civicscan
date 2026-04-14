<?php
/**
 * CivicScan – Constituencies: Index
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$pageTitle   = 'Constituencies';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Constituencies'],
];

// ── Filters ──────────────────────────────────────────────────────────────────
$q      = sanitize($_GET['q']      ?? '');
$stateF = sanitize($_GET['state']  ?? '');
$type   = sanitize($_GET['type']   ?? '');
$sort   = in_array($_GET['sort'] ?? '', ['name','constituency_type','created_at']) ? $_GET['sort'] : 'created_at';
$dir    = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['c.deleted_at IS NULL'];
$params = [];

if ($q) { $where[] = '(c.name LIKE ? OR c.code LIKE ?)'; $params = array_merge($params, ["%$q%","%$q%"]); }
if ($stateF) { $where[] = 's.name LIKE ?'; $params[] = "%$stateF%"; }
if ($type) { $where[] = 'c.constituency_type = ?'; $params[] = $type; }

$whereSql = 'WHERE ' . implode(' AND ', $where);
$total    = db_row("SELECT COUNT(*) AS cnt FROM constituencies c LEFT JOIN states s ON s.id=c.state_id LEFT JOIN districts d ON d.id=c.district_id $whereSql", $params)['cnt'];
$pager    = paginate($total, $page);
$items    = db_rows(
    "SELECT c.*, s.name AS state_name, d.name AS district_name,
            (SELECT COUNT(*) FROM voters v WHERE v.constituency_id=c.id AND v.deleted_at IS NULL) AS voter_count
     FROM constituencies c
     LEFT JOIN states s ON s.id=c.state_id
     LEFT JOIN districts d ON d.id=c.district_id
     $whereSql
     ORDER BY c.$sort $dir
     LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
    $params
);
$states   = db_rows('SELECT id, name FROM states WHERE status="active" ORDER BY name');
?>
<?php include __DIR__ . '/../../includes/layout/head.php'; ?>
<div class="flex">
<?php include __DIR__ . '/../../includes/layout/sidebar.php'; ?>
<div class="page-main flex-1">
<?php include __DIR__ . '/../../includes/layout/topbar.php'; ?>
<main class="page-content">
<?php include __DIR__ . '/../../includes/layout/flash.php'; ?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="font-display font-bold text-xl text-white">Constituencies</h1>
    <p class="text-slate-500 text-sm mt-0.5">Manage electoral constituencies across states and districts.</p>
  </div>
  <button onclick="openModal('add-modal')" class="btn btn-primary btn-sm">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Add Constituency
  </button>
</div>

<!-- Filters -->
<div class="card mb-5">
  <form method="GET" class="p-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-44">
      <label class="form-label">Search</label>
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Name or code…" class="form-input">
    </div>
    <div class="w-40">
      <label class="form-label">State</label>
      <select name="state" class="form-select">
        <option value="">All States</option>
        <?php foreach ($states as $s): ?>
        <option value="<?= h($s['name']) ?>" <?= $stateF===$s['name']?'selected':'' ?>><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="w-40">
      <label class="form-label">Type</label>
      <select name="type" class="form-select">
        <option value="">All Types</option>
        <option value="assembly"   <?= $type==='assembly'  ?'selected':'' ?>>Assembly</option>
        <option value="parliament" <?= $type==='parliament'?'selected':'' ?>>Parliament</option>
        <option value="local"      <?= $type==='local'     ?'selected':'' ?>>Local</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($q||$stateF||$type): ?><a href="<?= APP_URL ?>/modules/constituencies/index.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="card-header">
    <span class="text-slate-400 text-sm"><?= fmt_num($total) ?> constituency record<?= $total!==1?'s':'' ?></span>
  </div>
  <?php if (empty($items)): ?>
  <div class="empty-state">
    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
    <div class="empty-state-title">No constituencies found</div>
    <div class="empty-state-desc">Add your first constituency to get started.</div>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead>
        <tr>
          <th data-sort="name">Constituency</th>
          <th>State / District</th>
          <th data-sort="constituency_type">Type</th>
          <th>Voters</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $c): ?>
        <tr>
          <td>
            <div class="text-white font-medium text-sm"><?= h($c['name']) ?></div>
            <?php if ($c['code']): ?><div class="text-slate-600 text-xs font-mono"><?= h($c['code']) ?></div><?php endif; ?>
          </td>
          <td>
            <div class="text-slate-300 text-xs"><?= h($c['state_name'] ?? '—') ?></div>
            <div class="text-slate-600 text-xs"><?= h($c['district_name'] ?? '—') ?></div>
          </td>
          <td>
            <?php $tmap=['assembly'=>'bg-blue-500/10 text-blue-400','parliament'=>'bg-violet-500/10 text-violet-400','local'=>'bg-emerald-500/10 text-emerald-400']; ?>
            <span class="badge <?= $tmap[$c['constituency_type']] ?? '' ?>"><?= ucfirst($c['constituency_type']) ?></span>
          </td>
          <td class="text-slate-300 text-sm"><?= fmt_num($c['voter_count']) ?></td>
          <td><?= status_badge($c['status']) ?></td>
          <td>
            <div class="flex items-center gap-1">
              <button onclick="editConstituency(<?= $c['id'] ?>)" class="btn btn-ghost btn-icon btn-sm" data-tooltip="Edit">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <?php if (is_admin()): ?>
              <button onclick="confirmDelete('<?= APP_URL ?>/modules/constituencies/ajax.php?action=delete&id=<?= $c['id'] ?>','<?= h($c['name']) ?>')"
                      class="btn btn-ghost btn-icon btn-sm text-red-500 hover:text-red-400" data-tooltip="Delete">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pager['total_pages'] > 1): ?>
  <div class="px-4 py-3 border-t border-surface-600 flex items-center justify-between">
    <span class="text-slate-600 text-xs">Page <?= $pager['current'] ?> of <?= $pager['total_pages'] ?></span>
    <div class="pagination">
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pager['current']-1])) ?>" class="page-btn <?= !$pager['has_prev']?'disabled':'' ?>">‹</a>
      <?php for($i=max(1,$pager['current']-2);$i<=min($pager['total_pages'],$pager['current']+2);$i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="page-btn <?= $i===$pager['current']?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pager['current']+1])) ?>" class="page-btn <?= !$pager['has_next']?'disabled':'' ?>">›</a>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
</main></div></div>

<!-- Add / Edit Modal -->
<div id="add-modal" class="modal-backdrop hidden">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="modal-title" class="font-display font-semibold text-white text-base">Add Constituency</h3>
      <button onclick="closeModal('add-modal')" class="text-slate-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/modules/constituencies/save.php">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="c_id" value="">
      <div class="modal-body space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="form-label">State <span class="req">*</span></label>
            <select name="state_id" id="c_state_id" class="form-select" onchange="loadDistricts(this.value)">
              <option value="">Select state</option>
              <?php foreach ($states as $s): ?><option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">District <span class="req">*</span></label>
            <select name="district_id" id="c_district_id" class="form-select"><option value="">Select district</option></select>
          </div>
        </div>
        <div>
          <label class="form-label">Constituency Name <span class="req">*</span></label>
          <input type="text" name="name" id="c_name" class="form-input" placeholder="e.g. Shibpur Assembly Constituency">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="form-label">Type</label>
            <select name="constituency_type" id="c_type" class="form-select">
              <option value="assembly">Assembly</option>
              <option value="parliament">Parliament</option>
              <option value="local">Local</option>
            </select>
          </div>
          <div>
            <label class="form-label">Code / Number</label>
            <input type="text" name="code" id="c_code" class="form-input" placeholder="AC-123">
          </div>
        </div>
        <div>
          <label class="form-label">Status</label>
          <select name="status" id="c_status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="form-label">Description</label>
          <textarea name="description" id="c_desc" class="form-textarea" rows="2" placeholder="Optional notes…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('add-modal')" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Constituency</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm Modal -->
<div id="confirm-modal" class="modal-backdrop hidden">
  <div class="modal-box max-w-sm">
    <div class="modal-header">
      <h3 class="font-display font-semibold text-white text-base">Confirm Delete</h3>
      <button onclick="closeModal('confirm-modal')" class="text-slate-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body"><p id="confirm-msg" class="text-slate-400 text-sm"></p></div>
    <div class="modal-footer">
      <button onclick="closeModal('confirm-modal')" class="btn btn-secondary btn-sm">Cancel</button>
      <a id="confirm-btn" href="#" class="btn btn-danger btn-sm">Delete</a>
    </div>
  </div>
</div>

<script>
const CIVICSCAN = { url: '<?= APP_URL ?>', csrf: '<?= csrf_token() ?>' };
function confirmDelete(url, name){ document.getElementById('confirm-msg').textContent='Delete "'+name+'"? Cannot be undone.'; document.getElementById('confirm-btn').href=url; openModal('confirm-modal'); }

function loadDistricts(stateId, selectedId = '') {
  const sel = document.getElementById('c_district_id');
  sel.innerHTML = '<option value="">Loading…</option>';
  if (!stateId) { sel.innerHTML = '<option value="">Select district</option>'; return; }
  fetch(CIVICSCAN.url + '/modules/constituencies/ajax.php?action=districts&state_id=' + stateId)
    .then(r => r.json()).then(d => {
      sel.innerHTML = '<option value="">Select district</option>';
      d.forEach(dist => { sel.innerHTML += `<option value="${dist.id}" ${dist.id==selectedId?'selected':''}>${dist.name}</option>`; });
    });
}

function editConstituency(id) {
  fetch(CIVICSCAN.url + '/modules/constituencies/ajax.php?action=get&id=' + id)
    .then(r => r.json()).then(c => {
      document.getElementById('modal-title').textContent = 'Edit Constituency';
      document.getElementById('c_id').value       = c.id;
      document.getElementById('c_state_id').value = c.state_id;
      document.getElementById('c_name').value     = c.name;
      document.getElementById('c_type').value     = c.constituency_type;
      document.getElementById('c_code').value     = c.code || '';
      document.getElementById('c_status').value   = c.status;
      document.getElementById('c_desc').value     = c.description || '';
      loadDistricts(c.state_id, c.district_id);
      openModal('add-modal');
    });
}
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
