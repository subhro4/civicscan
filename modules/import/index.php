<?php
/**
 * CivicScan – PDF Import Module
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$pageTitle   = 'PDF Import';
$breadcrumbs = [
    ['label'=>'Dashboard','url'=>APP_URL.'/dashboard'],
    ['label'=>'PDF Import'],
];

$errors = [];

// ── Handle Upload ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'upload') {
    if (!csrf_verify()) csrf_fail();

    $stateId    = (int)($_POST['state_id']        ?? 0);
    $districtId = (int)($_POST['district_id']      ?? 0);
    $constId    = (int)($_POST['constituency_id']  ?? 0) ?: null;
    $partNum    = sanitize($_POST['part_number']   ?? '') ?: null;
    $srcYear    = (int)($_POST['source_year']      ?? 0) ?: null;
    $srcLang    = sanitize($_POST['source_language'] ?? 'english') ?: 'english';
    $engine     = in_array($_POST['extraction_engine']??'', ['text','ocr','hybrid','manual']) ? $_POST['extraction_engine'] : 'text';
    $me         = current_user();

    // File validation
    if (empty($_FILES['pdf_file']['name'])) {
        $errors['pdf_file'] = 'Please select a PDF file to upload.';
    } elseif ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $errors['pdf_file'] = 'Upload error code: ' . $_FILES['pdf_file']['error'];
    } elseif ($_FILES['pdf_file']['size'] > UPLOAD_MAX_SIZE) {
        $errors['pdf_file'] = 'File size exceeds maximum allowed (' . fmt_bytes(UPLOAD_MAX_SIZE) . ').';
    } else {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['pdf_file']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, UPLOAD_ALLOWED)) {
            $errors['pdf_file'] = 'Only PDF files are accepted. Detected: ' . $mimeType;
        }
    }

    if (!$stateId)    $errors['state_id']    = 'Please select a state.';
    if (!$districtId) $errors['district_id'] = 'Please select a district.';

    if (empty($errors)) {
        $uuid        = bin2hex(random_bytes(16));
        $uuid        = substr($uuid,0,8).'-'.substr($uuid,8,4).'-'.substr($uuid,12,4).'-'.substr($uuid,16,4).'-'.substr($uuid,20,12);
        $origName    = basename($_FILES['pdf_file']['name']);
        $ext         = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $storedName  = $uuid . '.' . $ext;
        $destPath    = UPLOAD_DIR . $storedName;
        $checksum    = hash_file('sha256', $_FILES['pdf_file']['tmp_name']);

        // Check duplicate by checksum
        $dup = db_row('SELECT id FROM voter_import_batches WHERE file_checksum_sha256 = ?', [$checksum]);
        if ($dup) {
            flash('error','This file has already been imported (duplicate checksum detected).');
            redirect('modules/import');
        }

        if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $destPath)) {
            flash('error','Failed to save uploaded file. Check folder permissions.');
            redirect('modules/import');
        }

        db_query(
            'INSERT INTO voter_import_batches
             (file_uuid, uploaded_by, state_id, district_id, constituency_id, part_id, original_file_name, stored_file_name, file_path, file_size_bytes, mime_type, file_checksum_sha256, source_year, source_language, extraction_engine, import_status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"queued")',
            [$uuid, $me['id'], $stateId, $districtId, $constId, null, $origName, $storedName,
             'uploads/pdfs/' . $storedName, $_FILES['pdf_file']['size'], 'application/pdf',
             $checksum, $srcYear, $srcLang, $engine]
        );
        $batchId = db_last_id();
        audit('import','upload','voter_import_batches',$batchId);
        flash('success', 'PDF "' . h($origName) . '" queued for processing (Batch #' . $batchId . ').');
        redirect('modules/import');
    }
}

// ── Fetch data for display ────────────────────────────────────────────────────
$page   = max(1,(int)($_GET['page']??1));
$status = sanitize($_GET['status']??'');
$where  = $status ? 'WHERE import_status = ?' : '';
$params = $status ? [$status] : [];

$total   = db_row("SELECT COUNT(*) AS c FROM voter_import_batches $where", $params)['c'];
$pager   = paginate($total, $page, 15);
$batches = db_rows(
    "SELECT vib.*, u.name AS uploader_name, s.name AS state_name, d.name AS district_name
     FROM voter_import_batches vib
     LEFT JOIN users u ON u.id=vib.uploaded_by
     LEFT JOIN states s ON s.id=vib.state_id
     LEFT JOIN districts d ON d.id=vib.district_id
     $where
     ORDER BY vib.created_at DESC
     LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
    $params
);

$states = db_rows('SELECT id, name FROM states WHERE status="active" ORDER BY name');
$me     = current_user();
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
    <h1 class="font-display font-bold text-xl text-white">PDF Import</h1>
    <p class="text-slate-500 text-sm mt-0.5">Upload voter-list PDFs and track import batches.</p>
  </div>
  <button onclick="openModal('upload-modal')" class="btn btn-primary btn-sm">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
    Upload PDF
  </button>
</div>

<!-- Status filter tabs -->
<div class="flex gap-1 mb-5 flex-wrap">
  <?php
  $tabs = [''=> 'All', 'queued'=>'Queued', 'processing'=>'Processing', 'completed'=>'Completed', 'completed_with_errors'=>'With Errors', 'failed'=>'Failed'];
  foreach ($tabs as $val => $lbl): ?>
  <a href="?status=<?= $val ?>" class="px-3 py-1.5 text-xs rounded-lg border transition-all <?= $status===$val?'bg-brand-600/20 border-brand-500/50 text-brand-400':'border-surface-600 text-slate-500 hover:border-surface-500 hover:text-slate-300' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</div>

<!-- Batches Table -->
<div class="card">
  <div class="card-header"><span class="text-slate-400 text-sm"><?= fmt_num($total) ?> import batch<?= $total!==1?'es':'' ?></span></div>
  <?php if (empty($batches)): ?>
  <div class="empty-state py-16">
    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
    <div class="empty-state-title">No imports yet</div>
    <div class="empty-state-desc">Upload a voter-list PDF to create your first import batch.</div>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead><tr>
        <th>#</th><th>File</th><th>Location</th><th>Records</th><th>Engine</th><th>Status</th><th>Uploaded</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($batches as $b): ?>
      <tr>
        <td class="text-slate-600 text-xs font-mono">#<?= $b['id'] ?></td>
        <td>
          <div class="text-white text-xs font-medium"><?= h(truncate($b['original_file_name'],35)) ?></div>
          <div class="text-slate-600 text-xs"><?= fmt_bytes($b['file_size_bytes']) ?></div>
        </td>
        <td>
          <div class="text-slate-300 text-xs"><?= h($b['state_name']??'—') ?></div>
          <div class="text-slate-600 text-xs"><?= h($b['district_name']??'—') ?></div>
        </td>
        <td>
          <?php if ($b['total_records_detected'] > 0): ?>
          <div class="text-white text-xs"><?= fmt_num($b['inserted_records']) ?> / <?= fmt_num($b['total_records_detected']) ?></div>
          <div class="progress-bar mt-1 w-20">
            <div class="progress-fill" style="width:<?= min(100,round($b['inserted_records']/$b['total_records_detected']*100)) ?>%"></div>
          </div>
          <?php else: ?><span class="text-slate-600 text-xs">—</span><?php endif; ?>
        </td>
        <td>
          <span class="text-xs text-slate-400"><?= ucfirst($b['extraction_engine']) ?></span>
        </td>
        <td><?= status_badge($b['import_status']) ?></td>
        <td>
          <div class="text-slate-400 text-xs"><?= time_ago($b['created_at']) ?></div>
          <div class="text-slate-600 text-xs"><?= h($b['uploader_name']??'—') ?></div>
        </td>
        <td>
          <button onclick="showBatchDetail(<?= $b['id'] ?>)" class="btn btn-ghost btn-icon btn-sm" data-tooltip="Details">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </button>
          <script>window['batch_<?= $b['id'] ?>']= <?= json_encode($b) ?>;</script>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pager['total_pages']>1): ?>
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

<!-- Upload Modal -->
<div id="upload-modal" class="modal-backdrop hidden">
  <div class="modal-box" style="max-width:540px">
    <div class="modal-header">
      <h3 class="font-display font-semibold text-white text-base">Upload Voter List PDF</h3>
      <button onclick="closeModal('upload-modal')" class="text-slate-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="_action" value="upload">
      <div class="modal-body space-y-4">

        <!-- Drop zone -->
        <div id="drop-zone" class="drop-zone" onclick="document.getElementById('pdf_file').click()">
          <svg class="w-10 h-10 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <div class="drop-label text-sm text-slate-500">Click to select a PDF, or drag & drop here</div>
          <div class="text-xs text-slate-700 mt-1">PDF only · Max <?= fmt_bytes(UPLOAD_MAX_SIZE) ?></div>
          <input type="file" id="pdf_file" name="pdf_file" accept=".pdf,application/pdf" class="hidden">
        </div>
        <?php if (isset($errors['pdf_file'])): ?><div class="form-error"><?= h($errors['pdf_file']) ?></div><?php endif; ?>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="form-label">State <span class="req">*</span></label>
            <select name="state_id" id="imp_state" class="form-select <?= isset($errors['state_id'])?'error':'' ?>" onchange="loadImpDistricts(this.value)">
              <option value="">Select state</option>
              <?php foreach ($states as $s): ?><option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option><?php endforeach; ?>
            </select>
            <?php if (isset($errors['state_id'])): ?><div class="form-error"><?= h($errors['state_id']) ?></div><?php endif; ?>
          </div>
          <div>
            <label class="form-label">District <span class="req">*</span></label>
            <select name="district_id" id="imp_district" class="form-select <?= isset($errors['district_id'])?'error':'' ?>">
              <option value="">Select district</option>
            </select>
            <?php if (isset($errors['district_id'])): ?><div class="form-error"><?= h($errors['district_id']) ?></div><?php endif; ?>
          </div>
          <div>
            <label class="form-label">Extraction Engine</label>
            <select name="extraction_engine" class="form-select">
              <option value="text">Text (direct parse)</option>
              <option value="ocr">OCR (scanned)</option>
              <option value="hybrid">Hybrid</option>
              <option value="manual">Manual</option>
            </select>
          </div>
          <div>
            <label class="form-label">Source Year</label>
            <input type="number" name="source_year" class="form-input" placeholder="<?= date('Y') ?>" min="2000" max="<?= date('Y') ?>">
          </div>
          <div>
            <label class="form-label">Part Number <span class="text-slate-700">(optional)</span></label>
            <input type="text" name="part_number" class="form-input" placeholder="e.g. 12">
          </div>
          <div>
            <label class="form-label">Language</label>
            <select name="source_language" class="form-select">
              <option value="english">English</option>
              <option value="hindi">Hindi</option>
              <option value="bengali">Bengali</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <div class="bg-amber-500/5 border border-amber-500/20 rounded-lg px-4 py-3 text-xs text-amber-400">
          <strong>Note:</strong> After upload, the file will be queued. Your application's processing script (cron/queue worker) must run <code>process.php</code> to extract voter records from the PDF.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('upload-modal')" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          Upload & Queue
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Batch Detail Modal -->
<div id="batch-detail-modal" class="modal-backdrop hidden">
  <div class="modal-box" style="max-width:540px">
    <div class="modal-header">
      <h3 class="font-display font-semibold text-white text-base">Import Batch Details</h3>
      <button onclick="closeModal('batch-detail-modal')" class="text-slate-500 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body" id="batch-detail-body"></div>
  </div>
</div>

<script>
const CIVICSCAN={url:'<?= APP_URL ?>',csrf:'<?= csrf_token() ?>'};

function loadImpDistricts(stateId) {
  const sel = document.getElementById('imp_district');
  sel.innerHTML = '<option value="">Loading…</option>';
  if (!stateId) { sel.innerHTML = '<option value="">Select district</option>'; return; }
  fetch(CIVICSCAN.url+'/modules/constituencies/save?action=districts&state_id='+stateId)
    .then(r=>r.json()).then(d=>{
      sel.innerHTML='<option value="">Select district</option>';
      d.forEach(dist=>{ sel.innerHTML+=`<option value="${dist.id}">${dist.name}</option>`; });
    });
}

function showBatchDetail(id) {
  const b = window['batch_'+id];
  if (!b) return;
  const statusClass = {'queued':'text-amber-400','processing':'text-blue-400','completed':'text-emerald-400','completed_with_errors':'text-orange-400','failed':'text-red-400'}[b.import_status]||'text-slate-400';
  document.getElementById('batch-detail-body').innerHTML = `
    <div class="space-y-4">
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div><div class="text-slate-600 text-xs mb-0.5">File</div><div class="text-white break-all">${b.original_file_name}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Size</div><div class="text-white">${formatBytes(b.file_size_bytes)}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Status</div><div class="${statusClass} font-medium capitalize">${b.import_status.replace(/_/g,' ')}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Engine</div><div class="text-white capitalize">${b.extraction_engine}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Total Detected</div><div class="text-white">${b.total_records_detected||'—'}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Inserted</div><div class="text-emerald-400">${b.inserted_records||0}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Skipped</div><div class="text-amber-400">${b.skipped_records||0}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Failed</div><div class="text-red-400">${b.failed_records||0}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Uploaded By</div><div class="text-white">${b.uploader_name||'—'}</div></div>
        <div><div class="text-slate-600 text-xs mb-0.5">Uploaded At</div><div class="text-white">${b.created_at||'—'}</div></div>
        <div class="col-span-2"><div class="text-slate-600 text-xs mb-0.5">Checksum (SHA256)</div><div class="text-slate-500 font-mono text-xs break-all">${b.file_checksum_sha256||'—'}</div></div>
        ${b.remarks?`<div class="col-span-2"><div class="text-slate-600 text-xs mb-0.5">Remarks</div><div class="text-slate-400 text-sm">${b.remarks}</div></div>`:''}
      </div>
    </div>`;
  openModal('batch-detail-modal');
}

function formatBytes(n){if(!n)return'—';if(n>=1048576)return(n/1048576).toFixed(1)+' MB';if(n>=1024)return(n/1024).toFixed(1)+' KB';return n+' B';}

document.getElementById('pdf_file').addEventListener('change',function(){
  const lbl = document.querySelector('.drop-label');
  if(this.files.length){lbl.textContent='Selected: '+this.files[0].name+' ('+formatBytes(this.files[0].size)+')';}
});
['dragenter','dragover'].forEach(ev=>document.getElementById('drop-zone').addEventListener(ev,e=>{e.preventDefault();document.getElementById('drop-zone').classList.add('dragover');}));
['dragleave','drop'].forEach(ev=>document.getElementById('drop-zone').addEventListener(ev,()=>document.getElementById('drop-zone').classList.remove('dragover')));
document.getElementById('drop-zone').addEventListener('drop',e=>{e.preventDefault();const f=e.dataTransfer.files[0];if(f){const dt=new DataTransfer();dt.items.add(f);document.getElementById('pdf_file').files=dt.files;document.querySelector('.drop-label').textContent='Selected: '+f.name;}});

<?php if (!empty($errors)): ?>openModal('upload-modal');<?php endif; ?>
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
