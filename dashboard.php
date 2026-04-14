<?php
/**
 * CivicScan – Dashboard
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user      = current_user();
$pageTitle = 'Dashboard';

// ── Stats ────────────────────────────────────────────────────────────────────
$totalVoters         = db_row('SELECT COUNT(*) AS c FROM voters WHERE deleted_at IS NULL')['c'] ?? 0;
$totalConstituencies = db_row('SELECT COUNT(*) AS c FROM constituencies WHERE deleted_at IS NULL')['c'] ?? 0;
$totalImports        = db_row('SELECT COUNT(*) AS c FROM voter_import_batches')['c'] ?? 0;
$totalUsers          = db_row('SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL')['c'] ?? 0;

// ── Recent Imports ───────────────────────────────────────────────────────────
$recentImports = db_rows(
    'SELECT vib.*, u.name AS uploader_name, s.name AS state_name
     FROM voter_import_batches vib
     LEFT JOIN users u ON u.id = vib.uploaded_by
     LEFT JOIN states s ON s.id = vib.state_id
     ORDER BY vib.created_at DESC LIMIT 6'
);

// ── Recent Voters ─────────────────────────────────────────────────────────────
$recentVoters = db_rows(
    'SELECT v.*, s.name AS state_name, c.name AS constituency_name
     FROM voters v
     LEFT JOIN states s ON s.id = v.state_id
     LEFT JOIN constituencies c ON c.id = v.constituency_id
     WHERE v.deleted_at IS NULL
     ORDER BY v.created_at DESC LIMIT 8'
);

// ── States for quick nav ──────────────────────────────────────────────────────
$states = db_rows(
    'SELECT s.*, COUNT(v.id) AS voter_count
     FROM states s
     LEFT JOIN voters v ON v.state_id = s.id AND v.deleted_at IS NULL
     WHERE s.status = "active"
     GROUP BY s.id ORDER BY s.sort_order, s.name LIMIT 8'
);
?>
<?php include __DIR__ . '/includes/layout/head.php'; ?>

<div class="flex">
  <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

  <div class="page-main flex-1">
    <?php include __DIR__ . '/includes/layout/topbar.php'; ?>

    <main class="page-content">
      <?php include __DIR__ . '/includes/layout/flash.php'; ?>

      <!-- Welcome header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="font-display font-bold text-2xl text-white mb-1">
            Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>,
            <?= h(explode(' ', $user['name'])[0]) ?> 👋
          </h1>
          <p class="text-slate-500 text-sm">Here's what's happening in CivicScan today.</p>
        </div>
        <div class="flex items-center gap-3">
          <a href="<?= APP_URL ?>/modules/voters/search" class="btn btn-secondary btn-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Search Voters
          </a>
          <a href="<?= APP_URL ?>/modules/import" class="btn btn-primary btn-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Import PDF
          </a>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <?php
        $stats = [
          ['label'=>'Total Voters',        'value'=>fmt_num($totalVoters),         'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color'=>'text-blue-400',    'bg'=>'bg-blue-500/10',    'accent'=>'#3b82f6', 'href'=>'modules/voters'],
          ['label'=>'Constituencies',      'value'=>fmt_num($totalConstituencies), 'icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color'=>'text-violet-400', 'bg'=>'bg-violet-500/10', 'accent'=>'#8b5cf6', 'href'=>'modules/constituencies'],
          ['label'=>'PDF Imports',         'value'=>fmt_num($totalImports),        'icon'=>'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12', 'color'=>'text-emerald-400', 'bg'=>'bg-emerald-500/10', 'accent'=>'#10b981', 'href'=>'modules/import'],
          ['label'=>'Platform Users',      'value'=>fmt_num($totalUsers),          'icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'color'=>'text-amber-400',   'bg'=>'bg-amber-500/10',   'accent'=>'#f59e0b', 'href'=>'modules/users'],
        ];
        foreach ($stats as $s): ?>
        <a href="<?= APP_URL ?>/<?= $s['href'] ?>" class="stat-card block" style="--accent: <?= $s['accent'] ?>">
          <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 <?= $s['bg'] ?> rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 <?= $s['color'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?= $s['icon'] ?>"/></svg>
            </div>
            <svg class="w-3.5 h-3.5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </div>
          <div class="font-display font-bold text-2xl text-white mb-1"><?= $s['value'] ?></div>
          <div class="text-slate-500 text-sm"><?= $s['label'] ?></div>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="grid lg:grid-cols-3 gap-6 mb-6">

        <!-- Recent Imports -->
        <div class="lg:col-span-2 card">
          <div class="card-header">
            <div>
              <h2 class="font-display font-semibold text-white text-sm">Recent PDF Imports</h2>
              <p class="text-slate-600 text-xs mt-0.5">Latest voter list upload batches</p>
            </div>
            <a href="<?= APP_URL ?>/modules/import" class="btn btn-ghost btn-sm text-xs">View All</a>
          </div>
          <?php if (empty($recentImports)): ?>
          <div class="empty-state py-12">
            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <div class="empty-state-title">No imports yet</div>
            <div class="empty-state-desc">Upload your first voter-list PDF to get started.</div>
          </div>
          <?php else: ?>
          <div class="overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th>File / State</th>
                  <th>Records</th>
                  <th>Status</th>
                  <th>Uploaded</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentImports as $imp): ?>
                <tr>
                  <td>
                    <div class="font-medium text-white text-xs"><?= h(truncate($imp['original_file_name'], 32)) ?></div>
                    <div class="text-slate-600 text-xs"><?= h($imp['state_name'] ?? 'Unknown State') ?> · By <?= h($imp['uploader_name'] ?? '—') ?></div>
                  </td>
                  <td>
                    <div class="text-white text-xs"><?= fmt_num($imp['inserted_records']) ?> / <?= fmt_num($imp['total_records_detected']) ?></div>
                    <?php if ($imp['total_records_detected'] > 0): ?>
                    <div class="progress-bar mt-1 w-20">
                      <div class="progress-fill" style="width:<?= min(100, round($imp['inserted_records']/$imp['total_records_detected']*100)) ?>%"></div>
                    </div>
                    <?php endif; ?>
                  </td>
                  <td><?= status_badge($imp['import_status']) ?></td>
                  <td class="text-slate-500 text-xs"><?= time_ago($imp['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

        <!-- Quick Geography Nav -->
        <div class="card">
          <div class="card-header">
            <div>
              <h2 class="font-display font-semibold text-white text-sm">States Overview</h2>
              <p class="text-slate-600 text-xs mt-0.5">Click to browse voters</p>
            </div>
          </div>
          <?php if (empty($states)): ?>
          <div class="empty-state py-10">
            <div class="empty-state-title">No states found</div>
            <div class="empty-state-desc">Import voter data to see states.</div>
          </div>
          <?php else: ?>
          <div class="p-2 space-y-1">
            <?php foreach ($states as $st): ?>
            <a href="<?= APP_URL ?>/modules/voters/districts?state_id=<?= $st['id'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-surface-700 transition-colors group">
              <div class="w-8 h-8 bg-blue-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-blue-400 text-xs font-bold font-display"><?= strtoupper(substr($st['name'], 0, 2)) ?></span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-white text-xs font-medium truncate"><?= h($st['name']) ?></div>
                <div class="text-slate-600 text-xs"><?= fmt_num($st['voter_count']) ?> voters</div>
              </div>
              <svg class="w-3.5 h-3.5 text-slate-700 group-hover:text-slate-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <?php endforeach; ?>
            <a href="<?= APP_URL ?>/modules/voters" class="flex items-center justify-center gap-1 py-2 text-xs text-blue-400 hover:text-blue-300 transition-colors mt-2">
              View all states <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Voters -->
      <?php if (!empty($recentVoters)): ?>
      <div class="card">
        <div class="card-header">
          <div>
            <h2 class="font-display font-semibold text-white text-sm">Recently Added Voters</h2>
            <p class="text-slate-600 text-xs mt-0.5">Latest records added to the database</p>
          </div>
          <a href="<?= APP_URL ?>/modules/voters/search" class="btn btn-ghost btn-sm text-xs">Search All</a>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Voter Name</th>
                <th>Voter Card No.</th>
                <th>Part / Serial</th>
                <th>Constituency</th>
                <th>State</th>
                <th>Added</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentVoters as $v): ?>
              <tr>
                <td>
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full <?= $v['gender'] === 'female' ? 'bg-pink-500/20 text-pink-400' : 'bg-blue-500/20 text-blue-400' ?> flex items-center justify-center text-xs font-bold flex-shrink-0">
                      <?= strtoupper(substr($v['elector_name'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="font-medium text-white text-xs"><?= h($v['elector_name']) ?></div>
                      <?php if ($v['relation_name']): ?>
                      <div class="text-slate-600 text-xs"><?= ucfirst($v['relation_type']) ?>: <?= h($v['relation_name']) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td class="text-slate-300 font-mono text-xs"><?= h($v['voter_card_number'] ?? '—') ?></td>
                <td class="text-slate-500 text-xs"><?= h($v['part_id']) ?> / <?= h($v['serial_number']) ?></td>
                <td class="text-slate-400 text-xs"><?= h(truncate($v['constituency_name'] ?? '—', 25)) ?></td>
                <td class="text-slate-500 text-xs"><?= h($v['state_name'] ?? '—') ?></td>
                <td class="text-slate-600 text-xs"><?= time_ago($v['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Confirm modal (global) -->
<div id="confirm-modal" class="modal-backdrop hidden">
  <div class="modal-box max-w-sm">
    <div class="modal-header">
      <h3 class="font-display font-semibold text-white text-base">Confirm Action</h3>
      <button onclick="closeModal('confirm-modal')" class="text-slate-500 hover:text-white transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p id="confirm-msg" class="text-slate-400 text-sm"></p>
    </div>
    <div class="modal-footer">
      <button onclick="closeModal('confirm-modal')" class="btn btn-secondary btn-sm">Cancel</button>
      <button id="confirm-btn" class="btn btn-danger btn-sm">Delete</button>
    </div>
  </div>
</div>

<script>
const CIVICSCAN = { url: '<?= APP_URL ?>', csrf: '<?= csrf_token() ?>' };
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
