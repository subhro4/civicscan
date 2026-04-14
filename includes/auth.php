<?php
/**
 * CivicScan – Auth & CSRF Helpers
 */

require_once __DIR__ . '/config.php';

// ─── Session Bootstrap ───────────────────────────────────────────────────────
function session_boot(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,  // set true on HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

session_boot();

// ─── Auth Checks ─────────────────────────────────────────────────────────────
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    return $_SESSION['auth_user'] ?? null;
}

function current_role(): string {
    return $_SESSION['auth_user']['role'] ?? '';
}

function is_admin(): bool {
    return current_role() === 'administrator';
}

function require_login(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        flash('error', 'Access denied. Administrator only.');
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['auth_user'] = [
        'id'               => $user['id'],
        'name'             => $user['name'],
        'email'            => $user['email'],
        'role'             => $user['role'],
        'theme_preference' => $user['theme_preference'],
        'profile_image_path' => $user['profile_image_path'] ?? null,
    ];
    // Update last login
    require_once __DIR__ . '/db.php';
    db_query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
}

function logout_user(): void {
    session_unset();
    session_destroy();
    session_boot();
    session_regenerate_id(true);
}

// ─── Flash Messages ──────────────────────────────────────────────────────────
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

function csrf_fail(): void {
    http_response_code(403);
    die('CSRF token mismatch.');
}
