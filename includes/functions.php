<?php
/**
 * CivicScan – Global Helper Functions
 */

// ─── Security ─────────────────────────────────────────────────────────────────
function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize(string $v): string {
    return trim(strip_tags($v));
}

// ─── Redirect ────────────────────────────────────────────────────────────────
function redirect(string $path): void {
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

// ─── Pagination ──────────────────────────────────────────────────────────────
function paginate(int $total, int $page, int $perPage = PER_PAGE): array {
    $totalPages = (int)ceil($total / $perPage);
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $page,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'has_prev'    => $page > 1,
        'has_next'    => $page < $totalPages,
    ];
}

// ─── Password ─────────────────────────────────────────────────────────────────
function hash_password(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_password(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

function generate_password(int $length = 12): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
    $pass  = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

// ─── Date / Time ─────────────────────────────────────────────────────────────
function format_dt(?string $dt, string $format = 'd M Y, h:i A'): string {
    if (!$dt) return '—';
    return (new DateTime($dt))->format($format);
}

function time_ago(?string $dt): string {
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return format_dt($dt, 'd M Y');
}

// ─── Numbers ─────────────────────────────────────────────────────────────────
function fmt_num(int|float $n): string {
    return number_format($n);
}

// ─── String ──────────────────────────────────────────────────────────────────
function truncate(string $s, int $len = 40): string {
    return mb_strlen($s) > $len ? mb_substr($s, 0, $len) . '…' : $s;
}

function slug(string $s): string {
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($s)));
}

function initials(string $name): string {
    $parts = array_filter(explode(' ', $name));
    $i = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $i .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $i;
}

// ─── File ────────────────────────────────────────────────────────────────────
function fmt_bytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// ─── Status Badge HTML ────────────────────────────────────────────────────────
function status_badge(string $status): string {
    $map = [
        'active'               => 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30',
        'inactive'             => 'bg-slate-500/15 text-slate-400 border border-slate-500/30',
        'queued'               => 'bg-amber-500/15 text-amber-400 border border-amber-500/30',
        'processing'           => 'bg-blue-500/15 text-blue-400 border border-blue-500/30',
        'completed'            => 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30',
        'completed_with_errors'=> 'bg-orange-500/15 text-orange-400 border border-orange-500/30',
        'failed'               => 'bg-red-500/15 text-red-400 border border-red-500/30',
    ];
    $cls = $map[$status] ?? 'bg-slate-500/15 text-slate-400';
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {$cls}\">" . h(ucfirst(str_replace('_', ' ', $status))) . "</span>";
}

// ─── Role Badge HTML ──────────────────────────────────────────────────────────
function role_badge(string $role): string {
    $cls = $role === 'administrator'
        ? 'bg-violet-500/15 text-violet-400 border border-violet-500/30'
        : 'bg-sky-500/15 text-sky-400 border border-sky-500/30';
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {$cls}\">" . h(ucfirst($role)) . "</span>";
}

// ─── Avatar HTML ─────────────────────────────────────────────────────────────
function avatar(array $user, string $size = 'w-9 h-9'): string {
    if (!empty($user['profile_image_path'])) {
        return "<img src=\"" . APP_URL . "/uploads/" . h($user['profile_image_path']) . "\" class=\"{$size} rounded-full object-cover\" alt=\"\">";
    }
    $bg = ['bg-blue-600','bg-violet-600','bg-emerald-600','bg-rose-600','bg-amber-600'];
    $c  = $bg[crc32($user['name']) % count($bg)];
    return "<div class=\"{$size} {$c} rounded-full flex items-center justify-center text-white font-semibold text-sm\">" . h(initials($user['name'])) . "</div>";
}

// ─── JSON Response ────────────────────────────────────────────────────────────
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ─── Audit Log Helper ─────────────────────────────────────────────────────────
function audit(string $module, string $action, string $table, string $recordId, ?array $old = null, ?array $new = null): void {
    $user = current_user();
    db_query(
        'INSERT INTO audit_logs (actor_user_id, module, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
         VALUES (?,?,?,?,?,?,?,?,?)',
        [
            $user['id'] ?? null,
            $module,
            $action,
            $table,
            $recordId,
            $old  ? json_encode($old)  : null,
            $new  ? json_encode($new)  : null,
            $_SERVER['REMOTE_ADDR']    ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]
    );
}
