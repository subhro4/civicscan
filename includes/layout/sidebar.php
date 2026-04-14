<?php
/**
 * CivicScan – Sidebar Navigation
 */
$user    = current_user();
$curPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function nav_link(string $href, string $icon, string $label, string $curPath): string {
    $active = str_contains($curPath, $href);
    $cls    = $active
        ? 'bg-brand-600/20 text-brand-400 border-r-2 border-brand-500'
        : 'text-slate-400 hover:bg-surface-700 hover:text-slate-200';
    return "<a href=\"" . APP_URL . "/{$href}\" class=\"flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-all {$cls} rounded-l-md\">{$icon}<span>{$label}</span></a>";
}
?>
<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-surface-800 border-r border-surface-600 flex flex-col z-30 transition-transform duration-300 lg:translate-x-0 -translate-x-full">

  <!-- Logo -->
  <div class="flex items-center gap-3 px-5 py-5 border-b border-surface-600">
    <img src="<?= APP_URL ?>/assets/images/logo-icon.svg" alt="CivicScan" class="w-8 h-8">
    <div>
      <div class="font-display font-bold text-white text-base leading-tight">CivicScan</div>
      <div class="text-xs text-slate-500 leading-tight">Empowering Your Vote</div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 overflow-y-auto py-4 space-y-0.5 px-2">

    <div class="px-3 py-1.5 text-xs font-semibold text-slate-600 uppercase tracking-widest mb-1">Main</div>

    <?= nav_link('dashboard', '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>', 'Dashboard', $curPath) ?>

    <?= nav_link('modules/voters', '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', 'Voters', $curPath) ?>

    <?= nav_link('modules/constituencies', '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>', 'Constituencies', $curPath) ?>

    <?= nav_link('modules/import', '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>', 'PDF Import', $curPath) ?>

    <?php if (is_admin()): ?>
    <div class="px-3 py-1.5 mt-4 text-xs font-semibold text-slate-600 uppercase tracking-widest mb-1">Admin</div>
    <?= nav_link('modules/users', '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>', 'User Management', $curPath) ?>
    <?php endif; ?>

    <div class="px-3 py-1.5 mt-4 text-xs font-semibold text-slate-600 uppercase tracking-widest mb-1">Account</div>
    <?= nav_link('modules/settings', '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>', 'Settings', $curPath) ?>
  </nav>

  <!-- User profile bottom -->
  <div class="border-t border-surface-600 p-4">
    <div class="flex items-center gap-3">
      <?= avatar($user, 'w-8 h-8 flex-shrink-0') ?>
      <div class="min-w-0 flex-1">
        <div class="text-sm font-medium text-white truncate"><?= h($user['name']) ?></div>
        <div class="text-xs text-slate-500 truncate"><?= role_badge($user['role']) ?></div>
      </div>
      <a href="<?= APP_URL ?>/modules/auth/logout" title="Logout" class="text-slate-500 hover:text-red-400 transition-colors ml-auto">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      </a>
    </div>
  </div>
</aside>

<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>
