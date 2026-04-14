<?php
/**
 * CivicScan – Flash Messages Partial
 */
$flashes = get_flashes();
if (!empty($flashes)): ?>
<div class="space-y-2 mb-5" id="flash-container">
<?php foreach ($flashes as $f):
    $map = [
        'success' => ['bg-emerald-500/10 border-emerald-500/30 text-emerald-400', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        'error'   => ['bg-red-500/10 border-red-500/30 text-red-400', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        'warning' => ['bg-amber-500/10 border-amber-500/30 text-amber-400', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'],
        'info'    => ['bg-blue-500/10 border-blue-500/30 text-blue-400', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ];
    [$cls, $icon] = $map[$f['type']] ?? $map['info'];
?>
<div class="flex items-start gap-3 px-4 py-3 rounded-xl border <?= $cls ?> text-sm">
  <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $icon ?></svg>
  <span><?= h($f['message']) ?></span>
  <button onclick="this.closest('div').remove()" class="ml-auto flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
  </button>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
