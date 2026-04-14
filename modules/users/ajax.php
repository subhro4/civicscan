<?php
/**
 * CivicScan – Users AJAX Handler
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$action = sanitize($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);
$me     = current_user();

if (!$id || $id === (int)$me['id']) {
    json_response(['error' => 'Invalid request.'], 400);
}

$target = db_row('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
if (!$target) json_response(['error' => 'User not found.'], 404);

switch ($action) {
    case 'toggle_status':
        $newStatus = $target['status'] === 'active' ? 'inactive' : 'active';
        db_query('UPDATE users SET status = ?, updated_by = ? WHERE id = ?', [$newStatus, $me['id'], $id]);
        audit('users', 'toggle_status', 'users', $id, ['status' => $target['status']], ['status' => $newStatus]);
        json_response(['status' => $newStatus, 'badge' => status_badge($newStatus)]);
        break;

    case 'delete':
        db_query('UPDATE users SET deleted_at = NOW(), deleted_by = ? WHERE id = ?', [$me['id'], $id]);
        audit('users', 'delete', 'users', $id, $target, null);
        flash('success', 'User "' . $target['name'] . '" deleted.');
        redirect('modules/users/index.php');
        break;

    default:
        json_response(['error' => 'Unknown action.'], 400);
}
