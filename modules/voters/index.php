<?php
/**
 * CivicScan – Voters: State Overview
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$pageTitle   = 'Voter Directory';
$breadcrumbs = [['label' => 'Voter Directory']];

$states = db_rows(
    'SELECT s.*,
       COUNT(DISTINCT v.id)      AS total_voters,
       COUNT(DISTINCT d.id)      AS total_districts,
       COUNT(DISTINCT c.id)      AS total_constituencies
     FROM states s
     LEFT JOIN voters v ON v.state_id = s.id AND v.deleted_at IS NULL
     LEFT JOIN districts d ON d.state_id = s.id AND d.status = "active"
     LEFT JOIN constituencies c ON c.state_id = s.id AND c.deleted_at IS NULL
     WHERE s.status = "active"
     GROUP BY s.id ORDER BY s.sort_order, s.name'
);

$totalVoters = array_sum(array_column($states, 'total_voters'));
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
    <h1 class="font-display font-bold text-xl text-white">Voter Directory</h1>
    <p class="text-slate-500 text-sm mt-0.5">Browse <?= fmt_num($totalVoters) ?> voters across <?= count($states) ?> state<?= count($states)!==1?'s':'' ?>.</p>
  </div>
  <a href="<?= APP_URL ?>/modules/voters/search" class="btn btn-primary btn-sm">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    Search Voters
  </a>
</div>

<?php if (empty($states)): ?>
<div class="empty-state card py-20">
  <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
  <div class="empty-state-title">No voter data yet</div>
  <div class="empty-state-desc mb-4">Import voter-list PDFs to start browsing states.</div>
  <a href="<?= APP_URL ?>/modules/import" class="btn btn-primary btn-sm">Import PDF</a>
</div>
<?php else: ?>

<!-- State Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
  <?php
  $colors = ['blue','violet','emerald','amber','rose','sky','indigo','teal'];
  foreach ($states as $i => $st):
    $color = $colors[$i % count($colors)];
    $initials = strtoupper(substr(preg_replace('/[aeiou\s]/i', '', $st['name']), 0, 2)) ?: strtoupper(substr($st['name'], 0, 2));
  ?>
  <a href="<?= APP_URL ?>/modules/voters/districts?state_id=<?= $st['id'] ?>" class="geo-card group">
    <div class="flex items-start justify-between mb-4">
      <div class="w-12 h-12 bg-<?= $color ?>-500/15 border border-<?= $color ?>-500/25 rounded-xl flex items-center justify-center">
        <span class="font-display font-bold text-<?= $color ?>-400 text-sm"><?= $initials ?></span>
      </div>
      <svg class="w-4 h-4 text-slate-700 group-hover:text-<?= $color ?>-400 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </div>
    <h3 class="font-display font-semibold text-white text-base mb-3 leading-tight"><?= h($st['name']) ?></h3>
    <div class="grid grid-cols-3 gap-2">
      <div class="text-center">
        <div class="font-display font-bold text-lg text-<?= $color ?>-400"><?= $st['total_voters'] >= 1000 ? number_format($st['total_voters']/1000, 1).'K' : fmt_num($st['total_voters']) ?></div>
        <div class="text-slate-600 text-xs">Voters</div>
      </div>
      <div class="text-center">
        <div class="font-display font-bold text-lg text-slate-300"><?= $st['total_districts'] ?></div>
        <div class="text-slate-600 text-xs">Districts</div>
      </div>
      <div class="text-center">
        <div class="font-display font-bold text-lg text-slate-300"><?= $st['total_constituencies'] ?></div>
        <div class="text-slate-600 text-xs">Consts.</div>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</main></div></div>
<script>const CIVICSCAN = { url: '<?= APP_URL ?>', csrf: '<?= csrf_token() ?>' };</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
