<?php
/**
 * CivicScan – Constituencies Save & AJAX
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$action = sanitize($_GET['action'] ?? '');
$me     = current_user();

// ── AJAX: Get districts by state ──────────────────────────────────────────────
if ($action === 'districts') {
    $stateId = (int)($_GET['state_id'] ?? 0);
    $rows    = db_rows('SELECT id, name FROM districts WHERE state_id = ? AND status = "active" ORDER BY name', [$stateId]);
    json_response($rows);
}

// ── AJAX: Get constituency data ───────────────────────────────────────────────
if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $c  = db_row('SELECT * FROM constituencies WHERE id = ? AND deleted_at IS NULL', [$id]);
    if (!$c) json_response(['error' => 'Not found'], 404);
    json_response($c);
}

// ── AJAX: Delete ──────────────────────────────────────────────────────────────
if ($action === 'delete') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $c  = db_row('SELECT * FROM constituencies WHERE id = ? AND deleted_at IS NULL', [$id]);
    if (!$c) { flash('error','Constituency not found.'); redirect('modules/constituencies'); }
    db_query('UPDATE constituencies SET deleted_at=NOW(), deleted_by=? WHERE id=?', [$me['id'], $id]);
    audit('constituencies','delete','constituencies',$id,$c,null);
    flash('success', '"'.$c['name'].'" deleted.');
    redirect('modules/constituencies');
}

// ── POST: Save (create or update) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) csrf_fail();

    $id       = (int)($_POST['id'] ?? 0);
    $stateId  = (int)($_POST['state_id']  ?? 0);
    $distId   = (int)($_POST['district_id'] ?? 0);
    $name     = sanitize($_POST['name']              ?? '');
    $type     = sanitize($_POST['constituency_type'] ?? 'assembly');
    $code     = sanitize($_POST['code']              ?? '') ?: null;
    $desc     = sanitize($_POST['description']       ?? '') ?: null;
    $status   = sanitize($_POST['status']            ?? 'active');

    if (!$stateId || !$distId || !$name) {
        flash('error','State, District and Name are required.'); redirect('modules/constituencies');
    }

    if ($id) {
        $old = db_row('SELECT * FROM constituencies WHERE id=?', [$id]);
        db_query('UPDATE constituencies SET state_id=?,district_id=?,name=?,constituency_type=?,code=?,description=?,status=?,updated_by=? WHERE id=?',
            [$stateId,$distId,$name,$type,$code,$desc,$status,$me['id'],$id]);
        audit('constituencies','update','constituencies',$id,$old,compact('stateId','distId','name','type','code','status'));
        flash('success','Constituency updated.');
    } else {
        db_query('INSERT INTO constituencies (state_id,district_id,name,constituency_type,code,description,status,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?)',
            [$stateId,$distId,$name,$type,$code,$desc,$status,$me['id'],$me['id']]);
        audit('constituencies','create','constituencies',db_last_id(),null,compact('name','stateId'));
        flash('success','Constituency "'.$name.'" added.');
    }
    redirect('modules/constituencies');
}
