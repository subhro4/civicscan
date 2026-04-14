<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$partId         = (int)($_GET['part_id']         ?? 0);
$constituencyId = (int)($_GET['constituency_id'] ?? 0);
$districtId     = (int)($_GET['district_id']     ?? 0);
$stateId        = (int)($_GET['state_id']        ?? 0);

// Build back breadcrumb context
$crumbConstituency = $constituencyId ? db_row('SELECT id,name FROM constituencies WHERE id=?',[$constituencyId]) : null;
$crumbDistrict     = $districtId ? db_row('SELECT id,name FROM districts WHERE id=?',[$districtId]) : null;
$crumbState        = $stateId ? db_row('SELECT id,name FROM states WHERE id=?',[$stateId]) : null;

$pageTitle = 'Voter Records';
$breadcrumbs = [['label'=>'Voter Directory','url'=>APP_URL.'/modules/voters/index.php']];
if ($crumbState) $breadcrumbs[] = ['label'=>h($crumbState['name']),'url'=>APP_URL.'/modules/voters/districts.php?state_id='.$stateId];
if ($crumbDistrict) $breadcrumbs[] = ['label'=>h($crumbDistrict['name']),'url'=>APP_URL.'/modules/voters/constituencies.php?district_id='.$districtId.'&state_id='.$stateId];
if ($crumbConstituency) $breadcrumbs[] = ['label'=>h($crumbConstituency['name']),'url'=>APP_URL.'/modules/voters/parts.php?constituency_id='.$constituencyId.'&district_id='.$districtId.'&state_id='.$stateId];
$breadcrumbs[] = ['label'=>'Voters'];

// Search / filter params
$q       = sanitize($_GET['q']       ?? '');
$gender  = sanitize($_GET['gender']  ?? '');
$ageMin  = (int)($_GET['age_min']    ?? 0);
$ageMax  = (int)($_GET['age_max']    ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));

// Build WHERE
$where  = ['v.deleted_at IS NULL'];
$params = [];

if ($partId)         { $where[] = 'v.part_id = ?';          $params[] = $partId; }
if ($constituencyId) { $where[] = 'v.constituency_id = ?';  $params[] = $constituencyId; }
if ($districtId)     { $where[] = 'v.district_id = ?';      $params[] = $districtId; }
if ($stateId)        { $where[] = 'v.state_id = ?';         $params[] = $stateId; }
if ($gender)         { $where[] = 'v.gender = ?';           $params[] = $gender; }
if ($ageMin > 0)     { $where[] = 'v.age >= ?';             $params[] = $ageMin; }
if ($ageMax > 0)     { $where[] = 'v.age <= ?';             $params[] = $ageMax; }

if ($q) {
    $where[]  = '(v.elector_name LIKE ? OR v.voter_card_number LIKE ? OR v.relation_name LIKE ? OR v.house_number LIKE ? OR v.serial_number LIKE ? OR v.locality LIKE ?)';
    $params   = array_merge($params, array_fill(0, 6, "%$q%"));
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$total    = db_row("SELECT COUNT(*) AS c FROM voters v $whereSql", $params)['c'];
$pager    = paginate($total, $page, 30);

$voters = db_rows(
    "SELECT v.*, cp.part_number, c.name AS constituency_name, s.name AS state_name
     FROM voters v
     LEFT JOIN constituency_parts cp ON cp.id=v.part_id
     LEFT JOIN constituencies c ON c.id=v.constituency_id
     LEFT JOIN states s ON s.id=v.state_id
     $whereSql
     ORDER BY v.serial_number ASC
     LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
    $params
);

// Build query string for pagination
$qArgs = array_filter(['part_id'=>$partId,'constituency_id'=>$constituencyId,'district_id'=>$districtId,'state_id'=>$stateId,'q'=>$q,'gender'=>$gender,'age_min'=>$ageMin,'age_max'=>$ageMax]);
?>
<?php include __DIR__ . '/../../includes/layout/head.php'; ?>
<div class="flex">
<?php include __DIR__ . '/../../includes/layout/sidebar.php'; ?>
<div class="page-main flex-1">
<?php include __DIR__ . '/../../includes/layout/topbar.php'; ?>
<main class="page-content">

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
  <div>
    <h1 class="font-display font-bold text-xl text-white">Voter Records</h1>
    <p class="text-slate-500 text-sm"><?= fmt_num($total) ?> voter<?= $total!==1?'s':'' ?> found</p>
  </div>
</div>

<!-- Search + Filters -->
<div class="card mb-5">
  <form method="GET" class="p-4">
    <!-- Preserve drill-down context -->
    <?php foreach (['part_id'=>$partId,'constituency_id'=>$constituencyId,'district_id'=>$districtId,'state_id'=>$stateId] as $k=>$v): ?>
    <?php if ($v): ?><input type="hidden" name="<?= $k ?>" value="<?= $v ?>"><?php endif; ?>
    <?php endforeach; ?>
    <div class="flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-60">
        <label class="form-label">Search Voters</label>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Name, Voter ID, house no., serial…" class="form-input" autofocus>
      </div>
      <div class="w-28">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">All</option>
          <option value="male"   <?= $gender==='male'  ?'selected':'' ?>>Male</option>
          <option value="female" <?= $gender==='female'?'selected':'' ?>>Female</option>
          <option value="other"  <?= $gender==='other' ?'selected':'' ?>>Other</option>
        </select>
      </div>
      <div class="w-24">
        <label class="form-label">Age From</label>
        <input type="number" name="age_min" value="<?= $ageMin ?: '' ?>" placeholder="18" class="form-input" min="1" max="120">
      </div>
      <div class="w-24">
        <label class="form-label">Age To</label>
        <input type="number" name="age_max" value="<?= $ageMax ?: '' ?>" placeholder="100" class="form-input" min="1" max="120">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if ($q||$gender||$ageMin||$ageMax): ?><a href="?<?= http_build_query(array_filter(['part_id'=>$partId,'constituency_id'=>$constituencyId,'district_id'=>$districtId,'state_id'=>$stateId])) ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- Voter Table -->
<div class="card">
  <?php if (empty($voters)): ?>
  <div class="empty-state py-16">
    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <div class="empty-state-title">No voters found</div>
    <div class="empty-state-desc">Try adjusting your search or filters.</div>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Sr.</th>
          <th>Voter Name</th>
          <th>Relation</th>
          <th>Voter ID</th>
          <th>House No.</th>
          <th>Age/Gender</th>
          <th>Part</th>
          <th>Locality</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($voters as $v): ?>
        <tr class="cursor-pointer hover:bg-surface-700" onclick="openModal('voter-<?= $v['id'] ?>')">
          <td class="text-slate-600 font-mono text-xs w-12"><?= h($v['serial_number']) ?></td>
          <td>
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full <?= $v['gender']==='female'?'bg-pink-500/20 text-pink-400':($v['gender']==='other'?'bg-purple-500/20 text-purple-400':'bg-blue-500/20 text-blue-400') ?> flex-shrink-0 flex items-center justify-center text-xs font-bold">
                <?= strtoupper(substr($v['elector_name'],0,1)) ?>
              </div>
              <span class="text-white text-sm font-medium"><?= h($v['elector_name']) ?></span>
            </div>
          </td>
          <td>
            <div class="text-xs text-slate-500"><?= h(ucfirst($v['relation_type'])) ?></div>
            <div class="text-xs text-slate-400"><?= h($v['relation_name'] ?? '—') ?></div>
          </td>
          <td class="font-mono text-xs text-slate-300"><?= h($v['voter_card_number'] ?? '—') ?></td>
          <td class="text-slate-400 text-xs"><?= h($v['house_number'] ?? '—') ?></td>
          <td class="text-slate-400 text-xs whitespace-nowrap">
            <?= $v['age'] ? $v['age'].'y' : '—' ?>
            <?php $gmap=['male'=>'♂','female'=>'♀','other'=>'⚧','unknown'=>'?']; ?>
            <span class="ml-1"><?= $gmap[$v['gender']] ?? '?' ?></span>
          </td>
          <td class="text-slate-500 text-xs"><?= h($v['part_number'] ?? '—') ?></td>
          <td class="text-slate-500 text-xs"><?= h(truncate($v['locality'] ?? '—', 22)) ?></td>
        </tr>
        <!-- Voter detail modal (inline, lightweight) -->
        <script>
        window['voter_<?= $v['id'] ?>'] = <?= json_encode($v) ?>;
        </script>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pager['total_pages'] > 1): ?>
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
  <?php endif; ?>
</div>

</main></div></div>

<!-- Voter Detail Modal (generic, populated by JS) -->
<div id="voter-detail-modal" class="modal-backdrop hidden">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="font-display font-semibold text-white text-base" id="vd-name">Voter Detail</h3>
      <button onclick="closeModal('voter-detail-modal')" class="text-slate-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <div class="grid grid-cols-2 gap-4 text-sm" id="vd-body"></div>
    </div>
  </div>
</div>

<script>
const CIVICSCAN={url:'<?= APP_URL ?>',csrf:'<?= csrf_token() ?>'};
function openModal(id){
  // Check if it's a voter modal
  if(id.startsWith('voter-')){
    const vId = id.replace('voter-','');
    const v   = window['voter_'+vId];
    if(!v) return;
    document.getElementById('vd-name').textContent = v.elector_name;
    const fields = [
      ['Voter Card No.', v.voter_card_number||'—'],
      ['Serial Number',  v.serial_number||'—'],
      ['Relation Type',  (v.relation_type||'').charAt(0).toUpperCase()+(v.relation_type||'').slice(1)],
      ['Relation Name',  v.relation_name||'—'],
      ['House Number',   v.house_number||'—'],
      ['Age',            v.age ? v.age+' years' : '—'],
      ['Gender',         (v.gender||'').charAt(0).toUpperCase()+(v.gender||'').slice(1)],
      ['Locality',       v.locality||'—'],
      ['Section',        v.section_name||'—'],
      ['Polling Station',v.polling_station_name||'—'],
      ['Constituency',   v.constituency_name||'—'],
      ['Part No.',       v.part_number||'—'],
    ];
    document.getElementById('vd-body').innerHTML = fields.map(([l,val])=>
      `<div><div class="text-slate-600 text-xs mb-0.5">${l}</div><div class="text-white text-sm">${val}</div></div>`
    ).join('');
    const m = document.getElementById('voter-detail-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow='hidden';
    return;
  }
  const m = document.getElementById(id);
  if(m){m.classList.remove('hidden');m.classList.add('flex');document.body.style.overflow='hidden';}
}
function closeModal(id){
  const m=document.getElementById(id);
  if(m){m.classList.add('hidden');m.classList.remove('flex');document.body.style.overflow='';}
}
document.addEventListener('click',e=>{if(e.target.classList.contains('modal-backdrop')){e.target.classList.add('hidden');e.target.classList.remove('flex');document.body.style.overflow='';}});
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
