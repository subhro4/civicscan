<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$stateId = (int)($_GET['state_id'] ?? 0);
$state   = db_row('SELECT * FROM states WHERE id = ? AND status="active"', [$stateId]);
if (!$state) { flash('error','State not found.'); redirect('modules/voters'); }

$pageTitle   = $state['name'];
$breadcrumbs = [
    ['label'=>'Voter Directory','url'=> APP_URL.'/modules/voters'],
    ['label'=> h($state['name'])],
];

$districts = db_rows(
    'SELECT d.*,
       COUNT(DISTINCT v.id) AS voter_count,
       COUNT(DISTINCT c.id) AS constituency_count
     FROM districts d
     LEFT JOIN voters v ON v.district_id=d.id AND v.state_id=? AND v.deleted_at IS NULL
     LEFT JOIN constituencies c ON c.district_id=d.id AND c.state_id=? AND c.deleted_at IS NULL
     WHERE d.state_id=? AND d.status="active"
     GROUP BY d.id ORDER BY d.sort_order, d.name',
    [$stateId, $stateId, $stateId]
);
?>
<?php include __DIR__ . '/../../includes/layout/head.php'; ?>
<div class="flex">
<?php include __DIR__ . '/../../includes/layout/sidebar.php'; ?>
<div class="page-main flex-1">
<?php include __DIR__ . '/../../includes/layout/topbar.php'; ?>
<main class="page-content">

<div class="flex items-center gap-3 mb-6">
  <a href="<?= APP_URL ?>/modules/voters" class="btn btn-ghost btn-icon btn-sm">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
  </a>
  <div>
    <h1 class="font-display font-bold text-xl text-white"><?= h($state['name']) ?></h1>
    <p class="text-slate-500 text-sm"><?= count($districts) ?> district<?= count($districts)!==1?'s':'' ?></p>
  </div>
</div>

<?php if (empty($districts)): ?>
<div class="empty-state card py-16"><div class="empty-state-title">No districts found</div></div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
  <?php foreach ($districts as $d): ?>
  <a href="<?= APP_URL ?>/modules/voters/constituencies?district_id=<?= $d['id'] ?>&state_id=<?= $stateId ?>" class="geo-card group">
    <div class="flex items-start justify-between mb-3">
      <div class="w-10 h-10 bg-sky-500/15 border border-sky-500/20 rounded-lg flex items-center justify-center">
        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <svg class="w-3.5 h-3.5 text-slate-700 group-hover:text-sky-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </div>
    <h3 class="font-display font-semibold text-white text-base mb-3"><?= h($d['name']) ?></h3>
    <div class="flex gap-4">
      <div>
        <div class="font-bold text-sky-400 text-base"><?= $d['voter_count']>=1000?number_format($d['voter_count']/1000,1).'K':fmt_num($d['voter_count']) ?></div>
        <div class="text-slate-600 text-xs">Voters</div>
      </div>
      <div>
        <div class="font-bold text-slate-300 text-base"><?= $d['constituency_count'] ?></div>
        <div class="text-slate-600 text-xs">Constituencies</div>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</main></div></div>
<script>const CIVICSCAN={url:'<?= APP_URL ?>',csrf:'<?= csrf_token() ?>'};</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
