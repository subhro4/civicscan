<?php
/**
 * CivicScan – Landing Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CivicScan — Empowering Your Vote</title>
<meta name="description" content="A secure, role-based voter data management platform. Upload voter lists, search records, and navigate constituencies with ease.">
<link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: { fontFamily: { display: ['Syne','sans-serif'], body: ['DM Sans','sans-serif'] } } }
}
</script>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
<style>
  html { scroll-behavior: smooth; }
  body { font-family: 'DM Sans', sans-serif; background: #0d1117; color: #e6edf3; overflow-x: hidden; }

  .hero-grid {
    background-image:
      linear-gradient(rgba(37,99,235,0.06) 1px, transparent 1px),
      linear-gradient(90deg, rgba(37,99,235,0.06) 1px, transparent 1px);
    background-size: 48px 48px;
  }

  .bg-mesh-blue {
    background-image:
      radial-gradient(ellipse 80% 60% at 30% 20%, rgba(37,99,235,0.12) 0%, transparent 60%),
      radial-gradient(ellipse 60% 80% at 70% 80%, rgba(124,58,237,0.08) 0%, transparent 60%);
  }

  .bg-mesh-emerald {
    background-image:
      radial-gradient(ellipse 80% 60% at 70% 30%, rgba(16,185,129,0.1) 0%, transparent 60%),
      radial-gradient(ellipse 60% 80% at 20% 70%, rgba(59,130,246,0.06) 0%, transparent 60%);
  }

  .bg-mesh-red {
    background-image:
      radial-gradient(ellipse 80% 60% at 20% 30%, rgba(239,68,68,0.1) 0%, transparent 60%),
      radial-gradient(ellipse 60% 80% at 80% 70%, rgba(124,58,237,0.06) 0%, transparent 60%);
  }

  .glow-blue { box-shadow: 0 0 60px rgba(37,99,235,0.25), 0 0 120px rgba(37,99,235,0.1); }
  .text-gradient { background: linear-gradient(135deg, #fff 0%, #93c5fd 50%, #3b82f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

  .feature-card {
    background: #161b22;
    border: 1px solid #30363d;
    border-radius: 1rem;
    padding: 1.75rem;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .feature-card:hover {
    border-color: #3b82f6;
    transform: translateY(-6px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.4), 0 0 40px rgba(37,99,235,0.1);
  }

  .step-card {
    background: #161b22;
    border: 1px solid #30363d;
    border-radius: 1rem;
    padding: 1.75rem;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
  }
  .step-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--step-color), transparent);
    opacity: 0;
    transition: opacity 0.35s;
  }
  .step-card:hover::before { opacity: 1; }
  .step-card:hover {
    border-color: #484f58;
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
  }

  .security-card {
    background: #161b22;
    border: 1px solid #30363d;
    border-radius: 1.25rem;
    padding: 2rem;
    transition: all 0.35s;
  }
  .security-card:hover {
    border-color: rgba(239,68,68,0.4);
    box-shadow: 0 0 80px rgba(239,68,68,0.08);
  }

  .scan-line { position: absolute; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, rgba(59,130,246,0.8), transparent); animation: scan-anim 3s ease-in-out infinite; }
  @keyframes scan-anim { 0%,100% { top: 10%; opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 0% { top: 10%; } 100% { top: 90%; } }
  @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
  .float { animation: float 4s ease-in-out infinite; }

  nav a { transition: color 0.2s, opacity 0.2s; }
  nav a:hover { color: #fff; opacity: 1; }

  .hero-stat {
    border: 1px solid rgba(59,130,246,0.25);
    background: rgba(59,130,246,0.08);
    border-radius: 0.75rem;
    padding: 1.25rem 1.5rem;
    transition: all 0.3s;
  }
  .hero-stat:hover {
    border-color: rgba(59,130,246,0.5);
    background: rgba(59,130,246,0.12);
    transform: translateY(-2px);
  }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    border-radius: 0.6rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
    white-space: nowrap;
    background: #2563eb;
    color: #fff;
  }
  .btn-primary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37,99,235,0.35);
  }

  .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    border-radius: 0.6rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #30363d;
    white-space: nowrap;
    background: #21262d;
    color: #e6edf3;
  }
  .btn-secondary:hover {
    background: #30363d;
    border-color: #484f58;
    transform: translateY(-2px);
  }

  .nav-link { position: relative; }
  .nav-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 1px;
    background: #3b82f6;
    transition: width 0.3s ease;
  }
  .nav-link:hover::after { width: 100%; }

  .hero-badge {
    animation: fade-in-up 0.6s ease-out forwards;
    opacity: 0;
  }
  @keyframes fade-in-up {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .hero-title { animation: fade-in-up 0.7s 0.1s ease-out forwards; opacity: 0; }
  .hero-desc { animation: fade-in-up 0.7s 0.2s ease-out forwards; opacity: 0; }
  .hero-actions { animation: fade-in-up 0.7s 0.3s ease-out forwards; opacity: 0; }
  .hero-stats { animation: fade-in-up 0.7s 0.4s ease-out forwards; opacity: 0; }
  .hero-visual { animation: fade-in-up 0.8s 0.2s ease-out forwards; opacity: 0; }

  .section-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.3s;
  }

  .cta-section {
    background: linear-gradient(135deg, rgba(37,99,235,0.08) 0%, rgba(124,58,237,0.06) 100%);
    border-top: 1px solid #30363d;
    border-bottom: 1px solid #30363d;
  }

  .feature-icon-wrap {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
  }
  .feature-card:hover .feature-icon-wrap {
    transform: scale(1.1);
    box-shadow: 0 0 20px rgba(59,130,246,0.3);
  }
</style>
</head>
<body>

<!-- ── Navigation ──────────────────────────────────────────────────────────── -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-surface-900/80 backdrop-blur-xl border-b border-white/5">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="#" class="flex items-center gap-3">
      <img src="<?= APP_URL ?>/assets/images/logo-icon.svg" class="w-8 h-8" alt="">
      <span class="font-display font-bold text-white text-lg">Civic<span class="text-blue-400">Scan</span></span>
    </a>
    <div class="hidden md:flex items-center gap-8 text-sm text-slate-400">
      <a href="#features" class="nav-link hover:text-white opacity-90">Features</a>
      <a href="#how-it-works" class="nav-link hover:text-white opacity-90">How It Works</a>
      <a href="#security" class="nav-link hover:text-white opacity-90">Security</a>
    </div>
    <a href="<?= APP_URL ?>/login" class="btn btn-primary text-sm px-5 py-2">
      Sign In
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
  </div>
</nav>

<!-- ── Hero ────────────────────────────────────────────────────────────────── -->
<section class="relative min-h-screen flex items-center hero-grid pt-16">
  <!-- Radial gradient overlay -->
  <div class="absolute inset-0 bg-gradient-radial" style="background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(37,99,235,0.2) 0%, transparent 70%);"></div>

  <div class="relative max-w-6xl mx-auto px-6 py-24 w-full">
    <div class="grid lg:grid-cols-2 gap-16 items-center">
      <!-- Text -->
      <div>
        <div class="hero-badge inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/25 text-blue-400 text-xs font-medium mb-8">
          <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
          Secure · Role-Based · Internal Platform
        </div>
        <h1 class="hero-title font-display font-bold text-5xl lg:text-6xl leading-tight mb-6">
          <span class="text-gradient">Empowering</span><br>
          <span class="text-white">Your Vote.</span>
        </h1>
        <p class="hero-desc text-slate-400 text-lg leading-relaxed mb-10 max-w-lg">
          A powerful internal platform to import voter-list PDFs, search records instantly, and navigate the complete electoral hierarchy — State to Voter, in seconds.
        </p>
        <div class="hero-actions flex flex-wrap items-center gap-4">
          <a href="<?= APP_URL ?>/login" class="btn btn-primary px-7 py-3 text-base glow-blue">
            Access Platform
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
          <a href="#features" class="btn btn-secondary px-7 py-3 text-base">
            Explore Features
          </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mt-12">
          <div class="hero-stat">
            <div class="font-display font-bold text-2xl text-white mb-0.5">5+</div>
            <div class="text-xs text-slate-500">Module Areas</div>
          </div>
          <div class="hero-stat">
            <div class="font-display font-bold text-2xl text-white mb-0.5">2</div>
            <div class="text-xs text-slate-500">User Roles</div>
          </div>
          <div class="hero-stat">
            <div class="font-display font-bold text-2xl text-white mb-0.5">PDF</div>
            <div class="text-xs text-slate-500">Import Engine</div>
          </div>
        </div>
      </div>

      <!-- Visual Mock -->
      <div class="relative lg:block hidden">
        <div class="relative float" style="animation-delay: 0.5s;">
          <!-- Mock dashboard card -->
          <div class="bg-surface-800 border border-surface-600 rounded-2xl p-6 shadow-2xl overflow-hidden" style="box-shadow: 0 0 80px rgba(37,99,235,0.2);">
            <!-- Scanning line -->
            <div class="scan-line"></div>
            <!-- Top bar -->
            <div class="flex items-center justify-between mb-5">
              <div class="flex items-center gap-2.5">
                <img src="<?= APP_URL ?>/assets/images/logo-icon.svg" class="w-6 h-6" alt="">
                <span class="font-display font-bold text-sm text-white">CivicScan</span>
              </div>
              <div class="flex gap-1.5">
                <div class="w-2.5 h-2.5 rounded-full bg-red-500/60"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-amber-500/60"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/60"></div>
              </div>
            </div>
            <!-- Voter search bar -->
            <div class="bg-surface-900 border border-surface-500 rounded-lg px-3 py-2 flex items-center gap-2 mb-4">
              <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <span class="text-slate-500 text-xs">Search voter name, ID, constituency…</span>
            </div>
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1.5 text-xs text-slate-600 mb-4">
              <span class="text-blue-400">West Bengal</span>
              <span>/</span><span>Howrah District</span>
              <span>/</span><span>Shibpur AC</span>
            </div>
            <!-- Mock table rows -->
            <?php
            $rows = [
              ['RTC2910001','Ramesh Kumar Sharma','M','42','Part 12'],
              ['RTC2910002','Priya Devi','F','35','Part 12'],
              ['RTC2910003','Suresh Babu Patel','M','58','Part 13'],
            ];
            foreach ($rows as $r): ?>
            <div class="flex items-center gap-3 py-2 border-b border-surface-700 last:border-0">
              <div class="w-7 h-7 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-xs font-bold flex-shrink-0">
                <?= $r[2] === 'F' ? '♀' : '♂' ?>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-white text-xs font-medium truncate"><?= $r[1] ?></div>
                <div class="text-slate-600 text-xs"><?= $r[0] ?> · Age <?= $r[3] ?></div>
              </div>
              <div class="text-xs text-slate-600 flex-shrink-0"><?= $r[4] ?></div>
            </div>
            <?php endforeach; ?>
            <!-- Bottom -->
            <div class="mt-4 flex items-center justify-between text-xs text-slate-600">
              <span>Showing 3 of 1,248 voters</span>
              <span class="flex items-center gap-1 text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Live</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Features ────────────────────────────────────────────────────────────── -->
<section id="features" class="py-24 bg-mesh-blue" style="border-top: 1px solid #30363d; border-bottom: 1px solid #30363d;">
  <div class="max-w-6xl mx-auto px-6">
  <div class="text-center mb-16">
    <div class="section-label bg-blue-500/10 border border-blue-500/25 text-blue-400 mb-4">Platform Capabilities</div>
    <h2 class="font-display font-bold text-4xl text-white mb-4">Everything you need to<br>manage voter data.</h2>
    <p class="text-slate-400 max-w-xl mx-auto">From PDF ingestion to hierarchical drill-down navigation — built for speed, accuracy, and trust.</p>
  </div>

  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php
    $features = [
      ['icon'=>'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
       'title'=>'PDF Import Engine','color'=>'text-blue-400','bg'=>'bg-blue-500/10',
       'desc'=>'Upload voter-list PDFs. The system extracts, normalizes, deduplicates, and stores all voter records automatically with full import logs.'],
      ['icon'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
       'title'=>'Smart Voter Search','color'=>'text-violet-400','bg'=>'bg-violet-500/10',
       'desc'=>'Search by name, voter ID, house number, relation name, constituency, part number, serial number, or location — instantly.'],
      ['icon'=>'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
       'title'=>'Geo Hierarchy Navigation','color'=>'text-emerald-400','bg'=>'bg-emerald-500/10',
       'desc'=>'Drill down from State → District → Constituency → Part Number → Voter Records with breadcrumb navigation throughout.'],
      ['icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
       'title'=>'Role-Based Access','color'=>'text-amber-400','bg'=>'bg-amber-500/10',
       'desc'=>'Two-tier role system: Administrators control everything; Moderators handle day-to-day search, import, and constituency management.'],
      ['icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
       'title'=>'Security-First Design','color'=>'text-red-400','bg'=>'bg-red-500/10',
       'desc'=>'Password hashing, prepared statements, CSRF protection, route guards, session management, and full audit logging built-in.'],
      ['icon'=>'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
       'title'=>'Dark & Light Themes','color'=>'text-sky-400','bg'=>'bg-sky-500/10',
       'desc'=>'Beautiful dark-first design with full light-mode support. Theme preference is saved per user and applied across sessions.'],
    ];
    foreach ($features as $f): ?>
    <div class="feature-card">
      <div class="feature-icon-wrap <?= $f['bg'] ?>">
        <svg class="w-5 h-5 <?= $f['color'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?= $f['icon'] ?>"/></svg>
      </div>
      <h3 class="font-display font-semibold text-white text-base mb-2"><?= $f['title'] ?></h3>
      <p class="text-slate-400 text-sm leading-relaxed"><?= $f['desc'] ?></p>
    </div>
    <?php endforeach; ?>
  </div>
  </div>
</section>

<!-- ── How It Works ─────────────────────────────────────────────────────────── -->
<section id="how-it-works" class="py-24 bg-mesh-emerald" style="border-top: 1px solid #30363d; border-bottom: 1px solid #30363d;">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-16">
      <div class="section-label bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 mb-4">Workflow</div>
      <h2 class="font-display font-bold text-4xl text-white mb-4">How CivicScan works</h2>
    </div>
    <div class="grid md:grid-cols-4 gap-6">
      <?php
      $steps = [
        ['num'=>'01','title'=>'Upload PDF','desc'=>'Authorized users upload voter-list PDFs through the secure import module.','color'=>'#3b82f6'],
        ['num'=>'02','title'=>'Extract & Normalize','desc'=>'The system parses, maps, and deduplicates all voter records from the PDF.','color'=>'#8b5cf6'],
        ['num'=>'03','title'=>'Stored & Indexed','desc'=>'Records are stored in MySQL with full geographic hierarchy and indexed for fast search.','color'=>'#10b981'],
        ['num'=>'04','title'=>'Search & Navigate','desc'=>'Users search by any field or drill down State → District → Constituency → Voter.','color'=>'#f59e0b'],
      ];
      foreach ($steps as $s): ?>
      <div class="step-card" style="--step-color: <?= $s['color'] ?>; border-top: 3px solid <?= $s['color'] ?>;">
        <div class="font-display font-bold text-4xl mb-4" style="color: rgba(255,255,255,0.06);"><?= $s['num'] ?></div>
        <h3 class="font-display font-semibold text-white text-base mb-2"><?= $s['title'] ?></h3>
        <p class="text-slate-400 text-sm"><?= $s['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Security ────────────────────────────────────────────────────────────── -->
<section id="security" class="py-24 bg-mesh-red" style="border-top: 1px solid #30363d; border-bottom: 1px solid #30363d;">
  <div class="max-w-6xl mx-auto px-6">
  <div class="grid lg:grid-cols-2 gap-16 items-center">
    <div>
      <div class="section-label bg-red-500/10 border border-red-500/25 text-red-400 mb-6">Enterprise Security</div>
      <h2 class="font-display font-bold text-4xl text-white mb-6">Built secure,<br>from the ground up.</h2>
      <p class="text-slate-400 mb-8 leading-relaxed">Every layer of CivicScan is designed with security in mind — protecting voter data and administrative access at every point.</p>
      <div class="space-y-4">
        <?php
        $items = [
          'PHP password_hash() with bcrypt — no plain text passwords ever stored',
          'Prepared statements (PDO) — SQL injection impossible',
          'CSRF tokens on every form — request forgery blocked',
          'Session-based auth with route protection — unauthenticated access denied',
          'Role enforcement server-side — client bypassing ineffective',
          'File upload validation — type, extension, and size checked',
          'Full audit log — every sensitive action is tracked with user and timestamp',
        ];
        foreach ($items as $item): ?>
        <div class="flex items-start gap-3 text-sm text-slate-400">
          <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?= $item ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="relative">
      <div class="security-card" style="border-color: rgba(239,68,68,0.25); box-shadow: 0 0 80px rgba(239,68,68,0.08);">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div>
            <div class="text-white font-semibold text-sm">Security Layers</div>
            <div class="text-slate-500 text-xs">Active on all requests</div>
          </div>
        </div>
        <?php
        $layers = [
          ['Authentication', 'Session-based + Route guard', 'emerald'],
          ['Authorization', 'Role checks server-side', 'emerald'],
          ['Input Safety', 'Prepared statements + validation', 'emerald'],
          ['CSRF Shield', 'Token on every state-change', 'emerald'],
          ['File Security', 'Type, size, extension validation', 'emerald'],
          ['Audit Trail', 'All sensitive actions logged', 'emerald'],
        ];
        foreach ($layers as $l): ?>
        <div class="flex items-center justify-between py-2.5 border-b border-surface-600 last:border-0">
          <div>
            <div class="text-white text-xs font-medium"><?= $l[0] ?></div>
            <div class="text-slate-500 text-xs"><?= $l[1] ?></div>
          </div>
          <span class="inline-flex items-center gap-1 text-xs text-<?= $l[2] ?>-400 bg-<?= $l[2] ?>-500/10 px-2 py-0.5 rounded-full border border-<?= $l[2] ?>-500/20">
            <span class="w-1.5 h-1.5 rounded-full bg-<?= $l[2] ?>-400"></span> Active
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  </div>
</section>

<!-- ── CTA ──────────────────────────────────────────────────────────────────── -->
<section class="cta-section py-24">
  <div class="max-w-2xl mx-auto px-6 text-center">
    <img src="<?= APP_URL ?>/assets/images/logo-icon.svg" class="w-16 h-16 mx-auto mb-6 float" alt="">
    <h2 class="font-display font-bold text-4xl text-white mb-4">Ready to get started?</h2>
    <p class="text-slate-400 mb-8">Sign in with your administrator credentials to access the full platform.</p>
    <a href="<?= APP_URL ?>/login" class="btn btn-primary px-10 py-3.5 text-base glow-blue inline-flex items-center gap-2">
      Sign In to CivicScan
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
  </div>
</section>

<!-- ── Footer ───────────────────────────────────────────────────────────────── -->
<footer style="border-top: 1px solid #30363d;" class="py-8">
  <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <img src="<?= APP_URL ?>/assets/images/logo-icon.svg" class="w-6 h-6" alt="">
      <span class="font-display font-bold text-white">Civic<span class="text-blue-400">Scan</span></span>
      <span class="text-slate-600 text-sm">· Empowering Your Vote</span>
    </div>
    <div class="text-slate-600 text-sm"><?= date('Y') ?> · &copy; CivicScan. All rights reserved.</div>
  </div>
</footer>

</body>
</html>
