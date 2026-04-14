<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$pageTitle   = 'Search Voters';
$breadcrumbs = [
    ['label'=>'Voter Directory','url'=>APP_URL.'/modules/voters/index.php'],
    ['label'=>'Search'],
];

$q              = sanitize($_GET['q']              ?? '');
$stateFilter    = sanitize($_GET['state']          ?? '');
$constFilter    = sanitize($_GET['constituency']   ?? '');
$partFilter     = sanitize($_GET['part_number']    ?? '');
$genderFilter   = sanitize($_GET['gender']         ?? '');
$page           = max(1, (int)($_GET['page']       ?? 1));

$where  = ['v.deleted_at IS NULL'];
$params = [];

if ($q) {
    $where[] = '(v.elector_name LIKE ? OR v.voter_card_number LIKE ? OR v.relation_name LIKE ? OR v.house_number LIKE ? OR v.locality LIKE ? OR v.serial_number LIKE ? OR v.address_line_1 LIKE ?)';
    $params  = array_merge($params, array_fill(0,7,"%$q%"));
}
if ($stateFilter)  { $where[] = 's.name LIKE ?';  $params[] = "%$stateFilter%"; }
if ($constFilter)  { $where[] = 'c.name LIKE ?';  $params[] = "%$constFilter%"; }
if ($partFilter)   { $where[] = 'cp.part_number = ?'; $params[] = $partFilter; }
if ($genderFilter) { $where[] = 'v.gender = ?';   $params[] = $genderFilter; }

$whereSql  = 'WHERE ' . implode(' AND ', $where);
$searched  = ($q || $stateFilter || $constFilter || $partFilter || $genderFilter);

$total = $searched ? (db_row(
    "SELECT COUNT(*) AS c FROM voters v
     LEFT JOIN states s ON s.id=v.state_id
     LEFT JOIN constituencies c ON c.id=v.constituency_id
     LEFT JOIN constituency_parts cp ON cp.id=v.part_id
     $whereSql", $params
)['c'] ?? 0) : 0;

$pager  = paginate($total, $page, 30);
$voters = [];

if ($searched && $total > 0) {
    $voters = db_rows(
        "SELECT v.*, s.name AS state_name, d.name AS district_name, c.name AS constituency_name, cp.part_number
         FROM voters v
         LEFT JOIN states s ON s.id=v.state_id
         LEFT JOIN districts d ON d.id=v.district_id
         LEFT JOIN constituencies c ON c.id=v.constituency_id
         LEFT JOIN constituency_parts cp ON cp.id=v.part_id
         $whereSql
         ORDER BY v.elector_name ASC
         LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
        $params
    );
}
$states = db_rows('SELECT id, name FROM states WHERE status="active" ORDER BY name');
$qArgs  = array_filter(compact('q','stateFilter','constFilter','partFilter','genderFilter'),fn($v)=>$v!=='');
?>
<?php include __DIR__ . '/../../includes/layout/head.php'; ?>
<div class="flex">
<?php include __DIR__ . '/../../includes/layout/sidebar.php'; ?>
<div class="page-main flex-1">
<?php include __DIR__ . '/../../includes/layout/topbar.php'; ?>
<main class="page-content">

<div class="mb-6">
  <h1 class="font-display font-bold text-xl text-white">Search Voters</h1>
  <p class="text-slate-500 text-sm mt-0.5">Search across all voter records by any field.</p>
</div>

<!-- Search Form -->
<div class="card mb-5">
  <form method="GET" class="p-5">
    <!-- Main search bar -->
    <div class="relative mb-4">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search by name, voter card no., house number, serial number, locality…"
             class="form-input pl-10 py-3 text-base" autofocus>
    </div>
    <!-- Advanced filters -->
    <div class="flex flex-wrap gap-3 items-end">
      <div class="w-44">
        <label class="form-label">State</label>
        <select name="state" class="form-select">
          <option value="">All States</option>
          <?php foreach ($states as $s): ?>
          <option value="<?= h($s['name']) ?>" <?= $stateFilter===$s['name']?'selected':'' ?>><?= h($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="w-44">
        <label class="form-label">Constituency</label>
        <input type="text" name="constituency" value="<?= h($constFilter) ?>" class="form-input" placeholder="Constituency name">
      </div>
      <div class="w-28">
        <label class="form-label">Part No.</label>
        <input type="text" name="part_number" value="<?= h($partFilter) ?>" class="form-input" placeholder="e.g. 12">
      </div>
      <div class="w-28">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">All</option>
          <option value="male"   <?= $genderFilter==='male'  ?'selected':'' ?>>Male</option>
          <option value="female" <?= $genderFilter==='female'?'selected':'' ?>>Female</option>
          <option value="other"  <?= $genderFilter==='other' ?'selected':'' ?>>Other</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        Search
      </button>
      <?php if ($searched): ?><a href="<?= APP_URL ?>/modules/voters/search.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- Results -->
<?php if (!$searched): ?>
<div class="empty-state card py-20">
  <svg class="w-16 h-16 text-surface-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
  <div class="empty-state-title text-base">Enter a search term above</div>
  <div class="empty-state-desc">You can search by name, voter card no., house number, constituency, part number, locality, or serial number.</div>
</div>
<?php elseif (empty($voters)): ?>
<div class="empty-state card py-16">
  <div class="empty-state-title">No voters match your search</div>
  <div class="empty-state-desc">Try broadening your search terms.</div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-header">
    <span class="text-slate-400 text-sm"><?= fmt_num($total) ?> result<?= $total!==1?'s':'' ?> found</span>
    <?php if ($q): ?><span class="text-slate-600 text-xs">for "<span class="text-slate-400"><?= h($q) ?></span>"</span><?php endif; ?>
  </div>
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead><tr>
        <th>Voter Name</th><th>Voter Card No.</th><th>Relation</th>
        <th>House No.</th><th>Age/Gender</th><th>Part</th><th>Constituency</th><th>State</th>
      </tr></thead>
      <tbody>
      <?php foreach ($voters as $v): ?>
      <tr>
        <td>
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full <?= $v['gender']==='female'?'bg-pink-500/20 text-pink-400':'bg-blue-500/20 text-blue-400' ?> flex-shrink-0 flex items-center justify-center text-xs font-bold">
              <?= strtoupper(substr($v['elector_name'],0,1)) ?>
            </div>
            <div>
              <div class="text-white text-sm font-medium"><?= h($v['elector_name']) ?></div>
              <div class="text-slate-600 text-xs">Sr. <?= h($v['serial_number']) ?></div>
            </div>
          </div>
        </td>
        <td class="font-mono text-xs text-slate-300"><?= h($v['voter_card_number']??'—') ?></td>
        <td><div class="text-slate-500 text-xs"><?= h(ucfirst($v['relation_type'])) ?></div><div class="text-slate-400 text-xs"><?= h($v['relation_name']??'—') ?></div></td>
        <td class="text-slate-400 text-xs"><?= h($v['house_number']??'—') ?></td>
        <td class="text-slate-400 text-xs whitespace-nowrap"><?= $v['age']?$v['age'].'y':'—' ?> <?= ['male'=>'♂','female'=>'♀','other'=>'⚧','unknown'=>'?'][$v['gender']]??'?' ?></td>
        <td class="text-slate-500 text-xs"><?= h($v['part_number']??'—') ?></td>
        <td class="text-slate-400 text-xs"><?= h(truncate($v['constituency_name']??'—',22)) ?></td>
        <td class="text-slate-500 text-xs"><?= h($v['state_name']??'—') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pager['total_pages']>1): ?>
  <div class="px-4 py-3 border-t border-surface-600 flex items-center justify-between flex-wrap gap-3">
    <span class="text-slate-600 text-xs">Showing <?= fmt_num($pager['offset']+1) ?>–<?= fmt_num(min($pager['offset']+$pager['per_page'],$total)) ?> of <?= fmt_num($total) ?></span>
    <div class="pagination">
      <a href="?<?= http_build_query(array_merge($qArgs,['page'=>$pager['current']-1])) ?>" class="page-btn <?= !$pager['has_prev']?'disabled':'' ?>">‹</a>
      <?php for($i=max(1,$pager['current']-2);$i<=min($pager['total_pages'],$pager['current']+2);$i++): ?>
      <a href="?<?= http_build_query(array_merge($qArgs,['page'=>$i])) ?>" class="page-btn <?= $i===$pager['current']?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <a href="?<?= http_build_query(array_merge($qArgs,['page'=>$pager['current']+1])) ?>" class="page-btn <?= !$pager['has_next']?'disabled':'' ?>">›</a>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

</main></div></div>
<script>const CIVICSCAN={url:'<?= APP_URL ?>',csrf:'<?= csrf_token() ?>'};</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
