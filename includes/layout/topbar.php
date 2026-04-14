<?php
/**
 * CivicScan – Top Bar Layout Partial
 */
$user = current_user();
?>
<header class="fixed top-0 left-0 right-0 lg:left-64 h-14 bg-surface-800/80 backdrop-blur-md border-b border-surface-600 z-20 flex items-center px-4 gap-4">

  <!-- Mobile menu toggle -->
  <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>

  <!-- Breadcrumbs / Page Title -->
  <div class="flex-1 min-w-0">
    <?php if (!empty($breadcrumbs)): ?>
    <nav class="flex items-center gap-1.5 text-sm text-slate-500">
      <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?><span class="text-slate-700">/</span><?php endif; ?>
        <?php if (isset($crumb['url']) && $i < count($breadcrumbs) - 1): ?>
          <a href="<?= h($crumb['url']) ?>" class="hover:text-slate-300 transition-colors"><?= h($crumb['label']) ?></a>
        <?php else: ?>
          <span class="text-slate-300 font-medium"><?= h($crumb['label']) ?></span>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php else: ?>
    <span class="text-slate-300 font-medium text-sm"><?= h($pageTitle ?? APP_NAME) ?></span>
    <?php endif; ?>
  </div>

  <!-- Global search -->
  <div class="hidden md:flex items-center">
    <a href="<?= APP_URL ?>/modules/voters/search.php" class="flex items-center gap-2 px-3 py-1.5 bg-surface-700 border border-surface-500 rounded-lg text-sm text-slate-500 hover:text-slate-300 hover:border-brand-500/50 transition-all w-52">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <span>Search voters…</span>
      <kbd class="ml-auto text-xs bg-surface-600 px-1.5 py-0.5 rounded border border-surface-500">⌘K</kbd>
    </a>
  </div>

  <!-- Theme toggle -->
  <button id="theme-toggle" onclick="toggleTheme()" title="Toggle theme" class="text-slate-400 hover:text-slate-200 transition-colors">
    <svg id="theme-sun" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    <svg id="theme-moon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
  </button>

  <!-- User avatar -->
  <div class="relative" x-data="{ open: false }">
    <button onclick="toggleUserMenu()" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
      <?= avatar($user, 'w-7 h-7') ?>
      <span class="hidden sm:block text-sm text-slate-300 font-medium"><?= h(explode(' ', $user['name'])[0]) ?></span>
      <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div id="user-menu" class="hidden absolute right-0 top-full mt-2 w-48 bg-surface-700 border border-surface-500 rounded-xl shadow-xl py-1.5 text-sm">
      <div class="px-3 py-2 border-b border-surface-600 mb-1">
        <div class="text-white font-medium truncate"><?= h($user['name']) ?></div>
        <div class="text-slate-500 text-xs truncate"><?= h($user['email']) ?></div>
      </div>
      <a href="<?= APP_URL ?>/modules/settings/index.php" class="flex items-center gap-2 px-3 py-2 text-slate-300 hover:bg-surface-600 hover:text-white transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        Settings
      </a>
      <a href="<?= APP_URL ?>/modules/auth/logout.php" class="flex items-center gap-2 px-3 py-2 text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign Out
      </a>
    </div>
  </div>
</header>
