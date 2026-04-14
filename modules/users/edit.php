<?php
/**
 * CivicScan – Users: Edit
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$id   = (int)($_GET['id'] ?? 0);
$user = db_row('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
if (!$user) { flash('error', 'User not found.'); redirect('modules/users'); }

$me          = current_user();
$pageTitle   = 'Edit User';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Users',     'url' => APP_URL . '/modules/users'],
    ['label' => 'Edit: ' . $user['name']],
];
$errors = [];
$input  = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) csrf_fail();

    $tab = sanitize($_POST['_tab'] ?? 'profile');

    if ($tab === 'profile') {
        $input = array_merge($input, [
            'role'             => sanitize($_POST['role']             ?? ''),
            'name'             => sanitize($_POST['name']             ?? ''),
            'email'            => strtolower(sanitize($_POST['email'] ?? '')),
            'phone'            => sanitize($_POST['phone']            ?? ''),
            'address_line_1'   => sanitize($_POST['address_line_1']   ?? ''),
            'city'             => sanitize($_POST['city']             ?? ''),
            'state'            => sanitize($_POST['state']            ?? ''),
            'postal_code'      => sanitize($_POST['postal_code']      ?? ''),
            'status'           => sanitize($_POST['status']           ?? 'active'),
            'theme_preference' => sanitize($_POST['theme_preference'] ?? 'dark'),
        ]);

        if (empty($input['name']))  $errors['name']  = 'Full name is required.';
        if (empty($input['email'])) $errors['email'] = 'Email is required.';
        elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
        else {
            $dup = db_row('SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL', [$input['email'], $id]);
            if ($dup) $errors['email'] = 'Email already in use.';
        }
        if (empty($input['phone'])) $errors['phone'] = 'Phone is required.';
        else {
            $dup = db_row('SELECT id FROM users WHERE phone = ? AND id != ? AND deleted_at IS NULL', [$input['phone'], $id]);
            if ($dup) $errors['phone'] = 'Phone already in use.';
        }

        if (empty($errors)) {
            db_query(
                'UPDATE users SET role=?, name=?, email=?, phone=?, address_line_1=?, city=?, state=?, postal_code=?, status=?, theme_preference=?, updated_by=? WHERE id=?',
                [$input['role'], $input['name'], $input['email'], $input['phone'],
                 $input['address_line_1'] ?: null, $input['city'] ?: null, $input['state'] ?: null,
                 $input['postal_code'] ?: null, $input['status'], $input['theme_preference'], $me['id'], $id]
            );
            audit('users', 'update', 'users', $id, $user, $input);
            flash('success', 'User updated successfully.');
            redirect("modules/users/edit?id=$id");
        }
    }

    if ($tab === 'password') {
        $newPass    = $_POST['new_password']     ?? '';
        $confPass   = $_POST['confirm_password'] ?? '';
        if (empty($newPass))         $errors['new_password'] = 'New password is required.';
        elseif (strlen($newPass) < 8) $errors['new_password'] = 'Minimum 8 characters.';
        if ($newPass !== $confPass)  $errors['confirm_password'] = 'Passwords do not match.';

        if (empty($errors)) {
            db_query('UPDATE users SET password_hash=?, last_password_changed_at=NOW(), updated_by=? WHERE id=?',
                [hash_password($newPass), $me['id'], $id]);
            audit('users', 'password_reset', 'users', $id);
            flash('success', 'Password reset successfully.');
            redirect("modules/users/edit?id=$id");
        }
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

<div class="flex items-center gap-4 mb-6">
  <?= avatar($user, 'w-12 h-12') ?>
  <div>
    <h1 class="font-display font-bold text-xl text-white"><?= h($user['name']) ?></h1>
    <div class="flex items-center gap-2 mt-1"><?= role_badge($user['role']) ?> <?= status_badge($user['status']) ?></div>
  </div>
</div>

<!-- Tabs -->
<div class="flex border-b border-surface-600 mb-6 gap-1">
  <button onclick="showTab('profile')" id="tab-profile" class="tab-btn px-4 py-2 text-sm font-medium border-b-2 border-brand-500 text-brand-400 -mb-px">Profile</button>
  <button onclick="showTab('password')" id="tab-password" class="tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-300 -mb-px transition-colors">Reset Password</button>
</div>

<!-- Profile Tab -->
<div id="pane-profile">
<form method="POST" class="max-w-2xl">
  <?= csrf_field() ?>
  <input type="hidden" name="_tab" value="profile">
  <div class="card mb-5">
    <div class="card-header"><h2 class="font-display font-semibold text-white text-sm">Account Details</h2></div>
    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="form-group">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" <?= ($id == $me['id']) ? 'disabled' : '' ?>>
          <option value="moderator"     <?= $input['role']==='moderator'    ?'selected':'' ?>>Moderator</option>
          <option value="administrator" <?= $input['role']==='administrator'?'selected':'' ?>>Administrator</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" <?= ($id == $me['id']) ? 'disabled' : '' ?>>
          <option value="active"   <?= $input['status']==='active'  ?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $input['status']==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <div class="form-group sm:col-span-2">
        <label class="form-label">Full Name <span class="req">*</span></label>
        <input type="text" name="name" value="<?= h($input['name']) ?>" class="form-input <?= isset($errors['name'])?'error':'' ?>">
        <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Email <span class="req">*</span></label>
        <input type="email" name="email" value="<?= h($input['email']) ?>" class="form-input <?= isset($errors['email'])?'error':'' ?>">
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Phone <span class="req">*</span></label>
        <input type="tel" name="phone" value="<?= h($input['phone']) ?>" class="form-input <?= isset($errors['phone'])?'error':'' ?>">
        <?php if (isset($errors['phone'])): ?><div class="form-error"><?= h($errors['phone']) ?></div><?php endif; ?>
      </div>
      <div class="form-group sm:col-span-2">
        <label class="form-label">Address</label>
        <input type="text" name="address_line_1" value="<?= h($input['address_line_1']) ?>" class="form-input">
      </div>
      <div class="form-group"><label class="form-label">City</label><input type="text" name="city" value="<?= h($input['city']) ?>" class="form-input"></div>
      <div class="form-group"><label class="form-label">State</label><input type="text" name="state" value="<?= h($input['state']) ?>" class="form-input"></div>
      <div class="form-group"><label class="form-label">Postal Code</label><input type="text" name="postal_code" value="<?= h($input['postal_code']) ?>" class="form-input"></div>
      <div class="form-group">
        <label class="form-label">Theme</label>
        <select name="theme_preference" class="form-select">
          <option value="dark"  <?= $input['theme_preference']==='dark' ?'selected':'' ?>>Dark</option>
          <option value="light" <?= $input['theme_preference']==='light'?'selected':'' ?>>Light</option>
        </select>
      </div>
    </div>
  </div>
  <div class="flex items-center gap-3">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= APP_URL ?>/modules/users" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>

<!-- Password Tab -->
<div id="pane-password" class="hidden">
<form method="POST" class="max-w-md">
  <?= csrf_field() ?>
  <input type="hidden" name="_tab" value="password">
  <div class="card mb-5">
    <div class="card-header"><h2 class="font-display font-semibold text-white text-sm">Reset Password</h2></div>
    <div class="card-body space-y-4">
      <div class="form-group">
        <label class="form-label">New Password <span class="req">*</span></label>
        <input type="password" name="new_password" class="form-input <?= isset($errors['new_password'])?'error':'' ?>" placeholder="Min. 8 characters">
        <?php if (isset($errors['new_password'])): ?><div class="form-error"><?= h($errors['new_password']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password <span class="req">*</span></label>
        <input type="password" name="confirm_password" class="form-input <?= isset($errors['confirm_password'])?'error':'' ?>">
        <?php if (isset($errors['confirm_password'])): ?><div class="form-error"><?= h($errors['confirm_password']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Reset Password</button>
</form>
</div>

</main></div></div>
<script>
const CIVICSCAN = { url: '<?= APP_URL ?>', csrf: '<?= csrf_token() ?>' };
function showTab(name) {
  ['profile','password'].forEach(t => {
    document.getElementById('pane-'+t).classList.toggle('hidden', t !== name);
    const btn = document.getElementById('tab-'+t);
    btn.classList.toggle('border-brand-500', t === name);
    btn.classList.toggle('text-brand-400', t === name);
    btn.classList.toggle('border-transparent', t !== name);
    btn.classList.toggle('text-slate-500', t !== name);
  });
}
<?php if (!empty($errors) && isset($_POST['_tab'])): ?>
showTab('<?= h($_POST['_tab']) ?>');
<?php endif; ?>
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
