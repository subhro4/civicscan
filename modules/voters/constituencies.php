<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$districtId = (int)($_GET['district_id'] ?? 0);
$stateId    = (int)($_GET['state_id']    ?? 0);
$district   = db_row('SELECT d.*, s.name AS state_name FROM districts d LEFT JOIN states s ON s.id=d.state_id WHERE d.id=?', [$districtId]);
if (!$district) { flash('error','District not found.'); redirect('modules/voters'); }

$pageTitle   = $district['name'];
$breadcrumbs = [
    ['label'=>'Voter Directory','url'=> APP_URL.'/modules/voters'],
    ['label'=> h($district['state_name']),'url'=> APP_URL.'/modules/voters/districts?state_id='.$stateId],
    ['label'=> h($district['name'])],
];

$constituencies = db_rows(
    'SELECT c.*,
       COUNT(DISTINCT v.id) AS voter_count,
       COUNT(DISTINCT cp.id) AS part_count
     FROM constituencies c
     LEFT JOIN voters v ON v.constituency_id=c.id AND v.deleted_at IS NULL
     LEFT JOIN constituency_parts cp ON cp.constituency_id=c.id AND cp.deleted_at IS NULL
     WHERE c.district_id=? AND c.state_id=? AND c.deleted_at IS NULL
     GROUP BY c.id ORDER BY c.name',
    [$districtId, $stateId]
);
?>
<?php include __DIR__ . '/../../includes/layout/head.php'; ?>
<div class="flex">
<?php include __DIR__ . '/../../includes/layout/sidebar.php'; ?>
<div class="page-main flex-1">
<?php include __DIR__ . '/../../includes/layout/topbar.php'; ?>
<main class="page-content">

<div class="flex items-center gap-3 mb-6">
  <a href="<?= APP_URL ?>/modules/voters/districts?state_id=<?= $stateId ?>" class="btn btn-ghost btn-icon btn-sm">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
  </a>
  <div>
    <h1 class="font-display font-bold text-xl text-white"><?= h($district['name']) ?></h1>
    <p class="text-slate-500 text-sm"><?= count($constituencies) ?> constituencies</p>
  </div>
</div>

<?php if (empty($constituencies)): ?>
<div class="empty-state card py-16"><div class="empty-state-title">No constituencies in this district</div></div>
<?php else: ?>
<div class="card overflow-hidden">
  <table class="data-table">
    <thead><tr>
      <th>Constituency</th><th>Type</th><th>Parts</th><th>Voters</th><th>Status</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($constituencies as $c): ?>
    <tr>
      <td>
        <div class="text-white font-medium text-sm"><?= h($c['name']) ?></div>
        <?php if ($c['code']): ?><div class="text-slate-600 text-xs font-mono"><?= h($c['code']) ?></div><?php endif; ?>
      </td>
      <td><span class="text-xs text-slate-400"><?= ucfirst($c['constituency_type']) ?></span></td>
      <td class="text-slate-400 text-sm"><?= $c['part_count'] ?></td>
      <td class="text-slate-300 text-sm font-medium"><?= fmt_num($c['voter_count']) ?></td>
      <td><?= status_badge($c['status']) ?></td>
      <td>
        <a href="<?= APP_URL ?>/modules/voters/parts?constituency_id=<?= $c['id'] ?>&district_id=<?= $districtId ?>&state_id=<?= $stateId ?>"
           class="btn btn-ghost btn-sm text-xs text-blue-400 hover:text-blue-300">
          View Parts <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

</main></div></div>
<script>const CIVICSCAN={url:'<?= APP_URL ?>',csrf:'<?= csrf_token() ?>'};</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
