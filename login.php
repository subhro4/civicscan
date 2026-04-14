<?php
/**
 * CivicScan – Login Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$input  = ['login' => '', 'remember' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) csrf_fail();

    $login    = sanitize($_POST['login']    ?? '');
    $password = $_POST['password'] ?? '';
    $input['login'] = $login;

    if (empty($login))    $errors['login']    = 'Email or phone is required.';
    if (empty($password)) $errors['password'] = 'Password is required.';

    if (empty($errors)) {
        $user = db_row(
            'SELECT * FROM users WHERE (email = ? OR phone = ?) AND deleted_at IS NULL LIMIT 1',
            [$login, $login]
        );

        if (!$user) {
            $errors['login'] = 'No account found with these credentials.';
        } elseif ($user['status'] === 'inactive') {
            $errors['login'] = 'Your account has been deactivated. Contact administrator.';
        } elseif (!verify_password($password, $user['password_hash'])) {
            $errors['password'] = 'Incorrect password. Please try again.';
        } else {
            login_user($user);
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('dashboard.php');
        }
    }
}

$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — CivicScan</title>
<link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
<style>
  .login-bg {
    background: #0d1117;
    background-image:
      radial-gradient(ellipse 60% 50% at 20% 50%, rgba(37,99,235,0.12) 0%, transparent 70%),
      radial-gradient(ellipse 50% 60% at 80% 50%, rgba(124,58,237,0.08) 0%, transparent 70%),
      linear-gradient(rgba(37,99,235,0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(37,99,235,0.04) 1px, transparent 1px);
    background-size: auto, auto, 40px 40px, 40px 40px;
  }
</style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center px-4 py-12">

<div class="w-full max-w-sm">
  <!-- Logo -->
  <div class="text-center mb-8">
    <a href="<?= APP_URL ?>" class="inline-flex flex-col items-center gap-3">
      <img src="<?= APP_URL ?>/assets/images/logo-icon.svg" class="w-12 h-12" alt="CivicScan">
      <div>
        <div class="font-display font-bold text-2xl text-white">Civic<span class="text-blue-400">Scan</span></div>
        <div class="text-slate-500 text-xs mt-0.5">Empowering Your Vote</div>
      </div>
    </a>
  </div>

  <!-- Flash messages -->
  <?php foreach ($flashes as $f): ?>
  <div class="mb-4 px-4 py-3 rounded-xl border text-sm
    <?= $f['type'] === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border-red-500/30 text-red-400' ?>">
    <?= h($f['message']) ?>
  </div>
  <?php endforeach; ?>

  <!-- Card -->
  <div class="card p-8">
    <h1 class="font-display font-bold text-xl text-white mb-1.5">Sign in to your account</h1>
    <p class="text-slate-500 text-sm mb-7">Enter your credentials to access the platform.</p>

    <form method="POST" action="" novalidate>
      <?= csrf_field() ?>

      <!-- Login field -->
      <div class="form-group">
        <label class="form-label" for="login">Email or Phone <span class="req">*</span></label>
        <input
          type="text"
          id="login"
          name="login"
          value="<?= h($input['login']) ?>"
          placeholder="admin@example.com or 9999999999"
          class="form-input <?= isset($errors['login']) ? 'error' : '' ?>"
          autocomplete="username"
          autofocus
        >
        <?php if (isset($errors['login'])): ?>
          <div class="form-error"><?= h($errors['login']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Password field -->
      <div class="form-group">
        <div class="flex items-center justify-between mb-1.5">
          <label class="form-label mb-0" for="password">Password <span class="req">*</span></label>
        </div>
        <div class="relative">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            class="form-input pr-10 <?= isset($errors['password']) ? 'error' : '' ?>"
            autocomplete="current-password"
          >
          <button type="button" onclick="togglePwd()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
            <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
        <?php if (isset($errors['password'])): ?>
          <div class="form-error"><?= h($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary w-full py-2.5 justify-center mt-2">
        Sign In
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </button>
    </form>
  </div>

  <!-- Footer note -->
  <p class="text-center text-slate-600 text-xs mt-6">
    <a href="<?= APP_URL ?>" class="hover:text-slate-400 transition-colors">← Back to homepage</a>
    &nbsp;·&nbsp;
    <?= APP_NAME ?> v<?= APP_VERSION ?>
  </p>
</div>

<script>
function togglePwd() {
  const input = document.getElementById('password');
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
