<?php
/**
 * CivicScan – Settings Module
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$me          = current_user();
$userRecord  = db_row('SELECT * FROM users WHERE id = ?', [$me['id']]);
$pageTitle   = 'Settings';
$breadcrumbs = [['label'=>'Settings']];
$errors      = [];
$activeTab   = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) csrf_fail();

    $tab = sanitize($_POST['_tab'] ?? 'profile');
    $activeTab = $tab;

    // ── Profile Tab ────────────────────────────────────────────────────────────
    if ($tab === 'profile') {
        $name     = sanitize($_POST['name']          ?? '');
        $email    = strtolower(sanitize($_POST['email'] ?? ''));
        $phone    = sanitize($_POST['phone']          ?? '');
        $addr     = sanitize($_POST['address_line_1'] ?? '');
        $city     = sanitize($_POST['city']           ?? '');
        $state    = sanitize($_POST['state']          ?? '');
        $postal   = sanitize($_POST['postal_code']    ?? '');

        if (empty($name))  $errors['name']  = 'Name is required.';
        if (empty($email)) $errors['email'] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
        else {
            $dup = db_row('SELECT id FROM users WHERE email=? AND id!=? AND deleted_at IS NULL',[$email,$me['id']]);
            if ($dup) $errors['email'] = 'Email already in use.';
        }
        if (empty($phone)) $errors['phone'] = 'Phone is required.';
        else {
            $dup = db_row('SELECT id FROM users WHERE phone=? AND id!=? AND deleted_at IS NULL',[$phone,$me['id']]);
            if ($dup) $errors['phone'] = 'Phone already in use.';
        }

        if (empty($errors)) {
            db_query('UPDATE users SET name=?,email=?,phone=?,address_line_1=?,city=?,state=?,postal_code=?,updated_by=? WHERE id=?',
                [$name,$email,$phone,$addr?:null,$city?:null,$state?:null,$postal?:null,$me['id'],$me['id']]);
            // Refresh session
            $_SESSION['auth_user']['name']  = $name;
            $_SESSION['auth_user']['email'] = $email;
            audit('settings','profile_update','users',$me['id']);
            flash('success','Profile updated successfully.');
            redirect('modules/settings/index.php');
        }
    }

    // ── Password Tab ───────────────────────────────────────────────────────────
    if ($tab === 'password') {
        $current  = $_POST['current_password']  ?? '';
        $newPass  = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (empty($current)) $errors['current_password'] = 'Current password is required.';
        elseif (!verify_password($current, $userRecord['password_hash'])) $errors['current_password'] = 'Incorrect current password.';
        if (empty($newPass)) $errors['new_password'] = 'New password is required.';
        elseif (strlen($newPass) < 8) $errors['new_password'] = 'Minimum 8 characters.';
        if ($newPass !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

        if (empty($errors)) {
            db_query('UPDATE users SET password_hash=?,last_password_changed_at=NOW(),updated_by=? WHERE id=?',
                [hash_password($newPass),$me['id'],$me['id']]);
            audit('settings','password_change','users',$me['id']);
            flash('success','Password changed successfully.');
            redirect('modules/settings/index.php');
        }
    }

    // ── Theme Tab ──────────────────────────────────────────────────────────────
    if ($tab === 'theme') {
        $theme = in_array($_POST['theme']??'', ['dark','light','system']) ? $_POST['theme'] : 'dark';
        db_query('UPDATE users SET theme_preference=? WHERE id=?',[$theme,$me['id']]);
        $_SESSION['auth_user']['theme_preference'] = $theme;
        flash('success','Theme preference saved.');
        redirect('modules/settings/index.php');
    }
}
?>
<?php include __DIR__ . '/../../includes/layout/head.php'; ?>
<div class="flex">
<?php include __DIR__ . '/../../includes/layout/sidebar.php'; ?>
<div class="page-main flex-1">
<?php include __DIR__ . '/../../includes/layout/topbar.php'; ?>
<main class="page-content">
<?php include __DIR__ . '/../../includes/layout/flash.php'; ?>

<div class="mb-6">
  <h1 class="font-display font-bold text-xl text-white">Settings</h1>
  <p class="text-slate-500 text-sm mt-0.5">Manage your account profile, security, and preferences.</p>
</div>

<!-- Profile Card -->
<div class="flex items-center gap-4 card p-5 mb-6">
  <?= avatar($userRecord, 'w-14 h-14') ?>
  <div>
    <div class="font-display font-bold text-white text-lg"><?= h($userRecord['name']) ?></div>
    <div class="flex items-center gap-2 mt-1">
      <?= role_badge($userRecord['role']) ?>
      <?= status_badge($userRecord['status']) ?>
      <?php if ($userRecord['last_login_at']): ?>
      <span class="text-slate-600 text-xs">Last login: <?= format_dt($userRecord['last_login_at']) ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Tabs -->
<div class="flex border-b border-surface-600 mb-6 gap-1">
  <button onclick="showTab('profile')"  id="tab-profile"  class="tab-btn px-4 py-2 text-sm font-medium border-b-2 <?= $activeTab==='profile' ?'border-brand-500 text-brand-400':'border-transparent text-slate-500 hover:text-slate-300' ?> -mb-px transition-colors">Profile</button>
  <button onclick="showTab('password')" id="tab-password" class="tab-btn px-4 py-2 text-sm font-medium border-b-2 <?= $activeTab==='password'?'border-brand-500 text-brand-400':'border-transparent text-slate-500 hover:text-slate-300' ?> -mb-px transition-colors">Password</button>
  <button onclick="showTab('theme')"    id="tab-theme"    class="tab-btn px-4 py-2 text-sm font-medium border-b-2 <?= $activeTab==='theme'   ?'border-brand-500 text-brand-400':'border-transparent text-slate-500 hover:text-slate-300' ?> -mb-px transition-colors">Theme</button>
</div>

<!-- Profile Tab -->
<div id="pane-profile" class="<?= $activeTab!=='profile'?'hidden':'' ?>">
<form method="POST" class="max-w-2xl">
  <?= csrf_field() ?><input type="hidden" name="_tab" value="profile">
  <div class="card mb-5">
    <div class="card-header"><h2 class="font-display font-semibold text-white text-sm">Profile Information</h2></div>
    <div class="card-body grid sm:grid-cols-2 gap-4">
      <div class="form-group sm:col-span-2">
        <label class="form-label">Full Name <span class="req">*</span></label>
        <input type="text" name="name" value="<?= h($userRecord['name']) ?>" class="form-input <?= isset($errors['name'])?'error':'' ?>">
        <?php if(isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Email <span class="req">*</span></label>
        <input type="email" name="email" value="<?= h($userRecord['email']) ?>" class="form-input <?= isset($errors['email'])?'error':'' ?>">
        <?php if(isset($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Phone <span class="req">*</span></label>
        <input type="tel" name="phone" value="<?= h($userRecord['phone']) ?>" class="form-input <?= isset($errors['phone'])?'error':'' ?>">
        <?php if(isset($errors['phone'])): ?><div class="form-error"><?= h($errors['phone']) ?></div><?php endif; ?>
      </div>
      <div class="form-group sm:col-span-2">
        <label class="form-label">Address</label>
        <input type="text" name="address_line_1" value="<?= h($userRecord['address_line_1']) ?>" class="form-input" placeholder="Street address">
      </div>
      <div class="form-group"><label class="form-label">City</label><input type="text" name="city" value="<?= h($userRecord['city']) ?>" class="form-input"></div>
      <div class="form-group"><label class="form-label">State</label><input type="text" name="state" value="<?= h($userRecord['state']) ?>" class="form-input"></div>
      <div class="form-group"><label class="form-label">Postal Code</label><input type="text" name="postal_code" value="<?= h($userRecord['postal_code']) ?>" class="form-input"></div>
    </div>
  </div>
  <div class="flex gap-3"><button type="submit" class="btn btn-primary">Save Profile</button></div>
</form>
</div>

<!-- Password Tab -->
<div id="pane-password" class="<?= $activeTab!=='password'?'hidden':'' ?>">
<form method="POST" class="max-w-md">
  <?= csrf_field() ?><input type="hidden" name="_tab" value="password">
  <div class="card mb-5">
    <div class="card-header"><h2 class="font-display font-semibold text-white text-sm">Change Password</h2></div>
    <div class="card-body space-y-4">
      <div class="form-group">
        <label class="form-label">Current Password <span class="req">*</span></label>
        <input type="password" name="current_password" class="form-input <?= isset($errors['current_password'])?'error':'' ?>">
        <?php if(isset($errors['current_password'])): ?><div class="form-error"><?= h($errors['current_password']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">New Password <span class="req">*</span></label>
        <input type="password" name="new_password" class="form-input <?= isset($errors['new_password'])?'error':'' ?>" placeholder="Min. 8 characters">
        <?php if(isset($errors['new_password'])): ?><div class="form-error"><?= h($errors['new_password']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password <span class="req">*</span></label>
        <input type="password" name="confirm_password" class="form-input <?= isset($errors['confirm_password'])?'error':'' ?>">
        <?php if(isset($errors['confirm_password'])): ?><div class="form-error"><?= h($errors['confirm_password']) ?></div><?php endif; ?>
      </div>
      <?php if ($userRecord['last_password_changed_at']): ?>
      <div class="text-xs text-slate-600">Last changed: <?= format_dt($userRecord['last_password_changed_at']) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Change Password</button>
</form>
</div>

<!-- Theme Tab -->
<div id="pane-theme" class="<?= $activeTab!=='theme'?'hidden':'' ?>">
<form method="POST" class="max-w-sm">
  <?= csrf_field() ?><input type="hidden" name="_tab" value="theme">
  <div class="card mb-5">
    <div class="card-header"><h2 class="font-display font-semibold text-white text-sm">Theme Preference</h2></div>
    <div class="card-body space-y-3">
      <?php foreach (['dark'=>['Dark Mode','Dark interface (default)','#0d1117','text-white'],'light'=>['Light Mode','Bright interface','#f8fafc','text-slate-900'],'system'=>['System Default','Follow OS setting','','text-slate-500']] as $val=>[$label,$desc,$bg,$tc]): ?>
      <label class="flex items-center gap-4 p-3 rounded-xl border border-surface-600 cursor-pointer hover:border-brand-500/40 transition-colors <?= $userRecord['theme_preference']===$val?'border-brand-500/50 bg-brand-500/5':'' ?>">
        <input type="radio" name="theme" value="<?= $val ?>" <?= $userRecord['theme_preference']===$val?'checked':'' ?> class="w-4 h-4 text-brand-500">
        <?php if ($bg): ?>
        <div class="w-10 h-7 rounded-md border border-surface-500 flex-shrink-0" style="background:<?= $bg ?>"></div>
        <?php else: ?>
        <div class="w-10 h-7 rounded-md border border-surface-500 flex-shrink-0 overflow-hidden"><div class="w-full h-1/2" style="background:#0d1117"></div><div class="w-full h-1/2" style="background:#f8fafc"></div></div>
        <?php endif; ?>
        <div><div class="text-white text-sm font-medium"><?= $label ?></div><div class="text-slate-500 text-xs"><?= $desc ?></div></div>
      </label>
      <?php endforeach; ?>
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Save Theme</button>
</form>
</div>

</main></div></div>
<script>
const CIVICSCAN={url:'<?= APP_URL ?>',csrf:'<?= csrf_token() ?>'};
function showTab(name){
  ['profile','password','theme'].forEach(t=>{
    document.getElementById('pane-'+t).classList.toggle('hidden',t!==name);
    const btn=document.getElementById('tab-'+t);
    btn.classList.toggle('border-brand-500',t===name); btn.classList.toggle('text-brand-400',t===name);
    btn.classList.toggle('border-transparent',t!==name); btn.classList.toggle('text-slate-500',t!==name);
  });
}
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
