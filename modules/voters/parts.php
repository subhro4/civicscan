<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$constituencyId = (int)($_GET['constituency_id'] ?? 0);
$districtId     = (int)($_GET['district_id']     ?? 0);
$stateId        = (int)($_GET['state_id']        ?? 0);

$constituency = db_row(
    'SELECT c.*, s.name AS state_name, d.name AS district_name
     FROM constituencies c
     LEFT JOIN states s ON s.id=c.state_id
     LEFT JOIN districts d ON d.id=c.district_id
     WHERE c.id=? AND c.deleted_at IS NULL', [$constituencyId]
);
if (!$constituency) { flash('error','Constituency not found.'); redirect('modules/voters'); }

$pageTitle   = $constituency['name'];
$breadcrumbs = [
    ['label'=>'Voter Directory','url'=>APP_URL.'/modules/voters'],
    ['label'=>h($constituency['state_name']),'url'=>APP_URL.'/modules/voters/districts?state_id='.$stateId],
    ['label'=>h($constituency['district_name']),'url'=>APP_URL.'/modules/voters/constituencies?district_id='.$districtId.'&state_id='.$stateId],
    ['label'=>h($constituency['name'])],
];

$parts = db_rows(
    'SELECT cp.*, COUNT(v.id) AS voter_count
     FROM constituency_parts cp
     LEFT JOIN voters v ON v.part_id=cp.id AND v.deleted_at IS NULL
     WHERE cp.constituency_id=? AND cp.deleted_at IS NULL
     GROUP BY cp.id ORDER BY cp.part_number',
    [$constituencyId]
);
?>
<?php include __DIR__ . '/../../includes/layout/head.php'; ?>
<div class="flex">
<?php include __DIR__ . '/../../includes/layout/sidebar.php'; ?>
<div class="page-main flex-1">
<?php include __DIR__ . '/../../includes/layout/topbar.php'; ?>
<main class="page-content">

<div class="flex items-center gap-3 mb-6">
  <a href="<?= APP_URL ?>/modules/voters/constituencies?district_id=<?= $districtId ?>&state_id=<?= $stateId ?>" class="btn btn-ghost btn-icon btn-sm">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
  </a>
  <div>
    <h1 class="font-display font-bold text-xl text-white"><?= h($constituency['name']) ?></h1>
    <p class="text-slate-500 text-sm"><?= count($parts) ?> part<?= count($parts)!==1?'s':'' ?></p>
  </div>
  <div class="ml-auto">
    <a href="<?= APP_URL ?>/modules/voters/records?constituency_id=<?= $constituencyId ?>&district_id=<?= $districtId ?>&state_id=<?= $stateId ?>" class="btn btn-primary btn-sm">
      View All Voters
    </a>
  </div>
</div>

<?php if (empty($parts)): ?>
<div class="empty-state card py-16"><div class="empty-state-title">No parts found for this constituency</div></div>
<?php else: ?>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
  <?php foreach ($parts as $p): ?>
  <a href="<?= APP_URL ?>/modules/voters/records?part_id=<?= $p['id'] ?>&constituency_id=<?= $constituencyId ?>&district_id=<?= $districtId ?>&state_id=<?= $stateId ?>"
     class="geo-card group">
    <div class="flex items-start justify-between mb-3">
      <div class="w-10 h-10 bg-violet-500/15 border border-violet-500/20 rounded-lg flex items-center justify-center">
        <span class="font-display font-bold text-violet-400 text-xs">P<?= h($p['part_number']) ?></span>
      </div>
      <svg class="w-3.5 h-3.5 text-slate-700 group-hover:text-violet-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </div>
    <?php if ($p['part_name']): ?>
    <h3 class="font-medium text-white text-sm mb-1"><?= h($p['part_name']) ?></h3>
    <?php endif; ?>
    <?php if ($p['polling_station_name']): ?>
    <div class="text-slate-500 text-xs mb-3 truncate"><?= h($p['polling_station_name']) ?></div>
    <?php endif; ?>
    <div class="grid grid-cols-2 gap-2 text-center">
      <div>
        <div class="font-bold text-violet-400 text-base"><?= fmt_num($p['voter_count']) ?></div>
        <div class="text-slate-600 text-xs">Voters</div>
      </div>
      <div>
        <div class="font-bold text-slate-300 text-base"><?= $p['total_electors'] > 0 ? fmt_num($p['total_electors']) : '—' ?></div>
        <div class="text-slate-600 text-xs">Registered</div>
      </div>
    </div>
    <?php if ($p['total_male'] || $p['total_female']): ?>
    <div class="mt-3 pt-3 border-t border-surface-600 flex gap-3 text-xs text-slate-600">
      <span>♂ <?= fmt_num($p['total_male']) ?></span>
      <span>♀ <?= fmt_num($p['total_female']) ?></span>
      <?php if ($p['total_other']): ?><span>⚧ <?= fmt_num($p['total_other']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</main></div></div>
<script>const CIVICSCAN={url:'<?= APP_URL ?>',csrf:'<?= csrf_token() ?>'};</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
