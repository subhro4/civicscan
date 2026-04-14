<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'POST only'], 405);
}

$theme = in_array($_POST['theme'] ?? '', ['dark','light','system']) ? $_POST['theme'] : 'dark';
db_query('UPDATE users SET theme_preference=? WHERE id=?', [$theme, current_user()['id']]);
$_SESSION['auth_user']['theme_preference'] = $theme;
json_response(['ok' => true, 'theme' => $theme]);
