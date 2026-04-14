<?php
/**
 * CivicScan – Users Module: Index
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$pageTitle   = 'User Management';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Users'],
];

// ── Filters ──────────────────────────────────────────────────────────────────
$q      = sanitize($_GET['q']      ?? '');
$status = sanitize($_GET['status'] ?? '');
$role   = sanitize($_GET['role']   ?? '');
$sort   = in_array($_GET['sort'] ?? '', ['name','email','role','status','created_at']) ? $_GET['sort'] : 'created_at';
$dir    = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page   = max(1, (int)($_GET['page'] ?? 1));

// ── Query ─────────────────────────────────────────────────────────────────────
$where  = ['deleted_at IS NULL'];
$params = [];

if ($q) {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)';
    $params  = array_merge($params, ["%$q%","%$q%","%$q%","%$q%"]);
}
if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($role)   { $where[] = 'role = ?';   $params[] = $role;   }

$whereSql = 'WHERE ' . implode(' AND ', $where);

$total  = db_row("SELECT COUNT(*) AS c FROM users $whereSql", $params)['c'];
$pager  = paginate($total, $page);
$users  = db_rows(
    "SELECT * FROM users $whereSql ORDER BY $sort $dir LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
    $params
);
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
    <h1 class="font-display font-bold text-xl text-white">User Management</h1>
    <p class="text-slate-500 text-sm mt-0.5">Manage administrator and moderator accounts.</p>
  </div>
  <a href="<?= APP_URL ?>/modules/users/create" class="btn btn-primary btn-sm">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Add User
  </a>
</div>

<!-- Filters -->
<div class="card mb-5">
  <form method="GET" class="p-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
      <label class="form-label">Search</label>
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Name, email, phone…" class="form-input">
    </div>
    <div class="w-36">
      <label class="form-label">Role</label>
      <select name="role" class="form-select">
        <option value="">All Roles</option>
        <option value="administrator" <?= $role==='administrator'?'selected':'' ?>>Administrator</option>
        <option value="moderator"     <?= $role==='moderator'    ?'selected':'' ?>>Moderator</option>
      </select>
    </div>
    <div class="w-36">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">All Status</option>
        <option value="active"   <?= $status==='active'  ?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($q || $status || $role): ?>
    <a href="<?= APP_URL ?>/modules/users" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="card-header">
    <span class="text-slate-400 text-sm"><?= fmt_num($total) ?> user<?= $total !== 1 ? 's' : '' ?> found</span>
  </div>
  <?php if (empty($users)): ?>
  <div class="empty-state">
    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <div class="empty-state-title">No users found</div>
    <div class="empty-state-desc">Try adjusting your filters or add a new user.</div>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead>
        <tr>
          <th data-sort="name">Name</th>
          <th data-sort="email">Email / Phone</th>
          <th data-sort="role">Role</th>
          <th data-sort="status">Status</th>
          <th data-sort="created_at">Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="flex items-center gap-3">
              <?= avatar($u, 'w-8 h-8 flex-shrink-0') ?>
              <div>
                <div class="text-white text-sm font-medium"><?= h($u['name']) ?></div>
                <?php if ($u['city']): ?><div class="text-slate-600 text-xs"><?= h($u['city']) ?><?= $u['state'] ? ', ' . h($u['state']) : '' ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <div class="text-sm text-slate-300"><?= h($u['email']) ?></div>
            <div class="text-xs text-slate-600"><?= h($u['phone']) ?></div>
          </td>
          <td><?= role_badge($u['role']) ?></td>
          <td><span class="status-badge"><?= status_badge($u['status']) ?></span></td>
          <td class="text-slate-500 text-xs"><?= format_dt($u['created_at'], 'd M Y') ?></td>
          <td>
            <div class="flex items-center gap-1">
              <a href="<?= APP_URL ?>/modules/users/edit?id=<?= $u['id'] ?>" class="btn btn-ghost btn-icon btn-sm" data-tooltip="Edit">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </a>
              <?php if ($u['id'] != $user['id']): ?>
              <button onclick="toggleStatus(this,'<?= APP_URL ?>/modules/users/ajax?action=toggle_status&id=<?= $u['id'] ?>')"
                      class="btn btn-ghost btn-icon btn-sm" data-tooltip="Toggle Status">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
              </button>
              <button onclick="confirmDelete('<?= APP_URL ?>/modules/users/ajax?action=delete&id=<?= $u['id'] ?>','<?= h($u['name']) ?>')"
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

  <!-- Pagination -->
  <?php if ($pager['total_pages'] > 1): ?>
  <div class="px-4 py-3 border-t border-surface-600 flex items-center justify-between">
    <span class="text-slate-600 text-xs">Page <?= $pager['current'] ?> of <?= $pager['total_pages'] ?></span>
    <div class="pagination">
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pager['current'] - 1])) ?>"
         class="page-btn <?= !$pager['has_prev'] ? 'disabled' : '' ?>">‹</a>
      <?php for ($i = max(1, $pager['current']-2); $i <= min($pager['total_pages'], $pager['current']+2); $i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
         class="page-btn <?= $i === $pager['current'] ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pager['current'] + 1])) ?>"
         class="page-btn <?= !$pager['has_next'] ? 'disabled' : '' ?>">›</a>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

</main></div></div>

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
function confirmDelete(url, name){ document.getElementById('confirm-msg').textContent='Delete "'+name+'"? This cannot be undone.'; document.getElementById('confirm-btn').href=url; openModal('confirm-modal'); }
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
