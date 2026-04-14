<?php
/**
 * CivicScan – Users: Create
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$pageTitle   = 'Add User';
$breadcrumbs = [
    ['label' => 'Dashboard',       'url' => APP_URL . '/dashboard'],
    ['label' => 'Users',           'url' => APP_URL . '/modules/users'],
    ['label' => 'Add User'],
];

$errors = [];
$input  = ['role' => 'moderator', 'status' => 'active', 'theme_preference' => 'dark'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) csrf_fail();

    $input = [
        'role'             => sanitize($_POST['role']             ?? ''),
        'name'             => sanitize($_POST['name']             ?? ''),
        'email'            => strtolower(sanitize($_POST['email'] ?? '')),
        'phone'            => sanitize($_POST['phone']            ?? ''),
        'password'         => $_POST['password']                  ?? '',
        'password_confirm' => $_POST['password_confirm']          ?? '',
        'address_line_1'   => sanitize($_POST['address_line_1']   ?? ''),
        'city'             => sanitize($_POST['city']             ?? ''),
        'state'            => sanitize($_POST['state']            ?? ''),
        'postal_code'      => sanitize($_POST['postal_code']      ?? ''),
        'status'           => sanitize($_POST['status']           ?? 'active'),
        'theme_preference' => sanitize($_POST['theme_preference'] ?? 'dark'),
    ];

    // Validation
    if (!in_array($input['role'], ['administrator','moderator'])) $errors['role'] = 'Invalid role.';
    if (empty($input['name']))  $errors['name']  = 'Full name is required.';
    if (empty($input['email'])) $errors['email'] = 'Email is required.';
    elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
    else {
        $exists = db_row('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL', [$input['email']]);
        if ($exists) $errors['email'] = 'This email is already registered.';
    }
    if (empty($input['phone'])) $errors['phone'] = 'Phone is required.';
    else {
        $exists = db_row('SELECT id FROM users WHERE phone = ? AND deleted_at IS NULL', [$input['phone']]);
        if ($exists) $errors['phone'] = 'This phone number is already registered.';
    }
    if (empty($input['password'])) $errors['password'] = 'Password is required.';
    elseif (strlen($input['password']) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($input['password'] !== $input['password_confirm']) $errors['password_confirm'] = 'Passwords do not match.';

    if (empty($errors)) {
        $userId = current_user()['id'];
        db_query(
            'INSERT INTO users (role, name, email, phone, password_hash, address_line_1, city, state, postal_code, status, theme_preference, created_by, updated_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $input['role'], $input['name'], $input['email'], $input['phone'],
                hash_password($input['password']),
                $input['address_line_1'] ?: null, $input['city'] ?: null,
                $input['state'] ?: null, $input['postal_code'] ?: null,
                $input['status'], $input['theme_preference'],
                $userId, $userId,
            ]
        );
        audit('users', 'create', 'users', db_last_id(), null, $input);
        flash('success', 'User "' . $input['name'] . '" created successfully.');
        redirect('modules/users');
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
  <h1 class="font-display font-bold text-xl text-white">Add New User</h1>
  <p class="text-slate-500 text-sm mt-0.5">Create a new administrator or moderator account.</p>
</div>

<form method="POST" class="max-w-2xl">
  <?= csrf_field() ?>

  <div class="card mb-5">
    <div class="card-header"><h2 class="font-display font-semibold text-white text-sm">Account Details</h2></div>
    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">

      <div class="form-group">
        <label class="form-label">Role <span class="req">*</span></label>
        <select name="role" class="form-select <?= isset($errors['role'])?'error':'' ?>">
          <option value="moderator"     <?= $input['role']==='moderator'    ?'selected':'' ?>>Moderator</option>
          <option value="administrator" <?= $input['role']==='administrator'?'selected':'' ?>>Administrator</option>
        </select>
        <?php if (isset($errors['role'])): ?><div class="form-error"><?= h($errors['role']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Status <span class="req">*</span></label>
        <select name="status" class="form-select">
          <option value="active"   <?= $input['status']==='active'  ?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $input['status']==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>

      <div class="form-group sm:col-span-2">
        <label class="form-label">Full Name <span class="req">*</span></label>
        <input type="text" name="name" value="<?= h($input['name']) ?>" class="form-input <?= isset($errors['name'])?'error':'' ?>" placeholder="John Doe">
        <?php if (isset($errors['name'])): ?><div class="form-error"><?= h($errors['name']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address <span class="req">*</span></label>
        <input type="email" name="email" value="<?= h($input['email']) ?>" class="form-input <?= isset($errors['email'])?'error':'' ?>" placeholder="user@example.com">
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Phone Number <span class="req">*</span></label>
        <input type="tel" name="phone" value="<?= h($input['phone']) ?>" class="form-input <?= isset($errors['phone'])?'error':'' ?>" placeholder="9876543210">
        <?php if (isset($errors['phone'])): ?><div class="form-error"><?= h($errors['phone']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Password <span class="req">*</span></label>
        <input type="password" name="password" class="form-input <?= isset($errors['password'])?'error':'' ?>" placeholder="Min. 8 characters">
        <?php if (isset($errors['password'])): ?><div class="form-error"><?= h($errors['password']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password <span class="req">*</span></label>
        <input type="password" name="password_confirm" class="form-input <?= isset($errors['password_confirm'])?'error':'' ?>" placeholder="Re-enter password">
        <?php if (isset($errors['password_confirm'])): ?><div class="form-error"><?= h($errors['password_confirm']) ?></div><?php endif; ?>
      </div>

      <div class="sm:col-span-2 flex items-center justify-between py-2 border-t border-surface-600">
        <button type="button" onclick="generatePwd()" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Generate password</button>
        <select name="theme_preference" class="form-select w-36">
          <option value="dark"  <?= $input['theme_preference']==='dark' ?'selected':'' ?>>Dark Theme</option>
          <option value="light" <?= $input['theme_preference']==='light'?'selected':'' ?>>Light Theme</option>
        </select>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-header"><h2 class="font-display font-semibold text-white text-sm">Profile Info <span class="text-slate-600 font-normal text-xs">(optional)</span></h2></div>
    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="form-group sm:col-span-2">
        <label class="form-label">Address</label>
        <input type="text" name="address_line_1" value="<?= h($input['address_line_1']) ?>" class="form-input" placeholder="Street address">
      </div>
      <div class="form-group">
        <label class="form-label">City</label>
        <input type="text" name="city" value="<?= h($input['city']) ?>" class="form-input" placeholder="City">
      </div>
      <div class="form-group">
        <label class="form-label">State</label>
        <input type="text" name="state" value="<?= h($input['state']) ?>" class="form-input" placeholder="State">
      </div>
      <div class="form-group">
        <label class="form-label">Postal Code</label>
        <input type="text" name="postal_code" value="<?= h($input['postal_code']) ?>" class="form-input" placeholder="700001">
      </div>
    </div>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="btn btn-primary">Create User</button>
    <a href="<?= APP_URL ?>/modules/users" class="btn btn-secondary">Cancel</a>
  </div>
</form>

</main></div></div>
<script>
const CIVICSCAN = { url: '<?= APP_URL ?>', csrf: '<?= csrf_token() ?>' };
function generatePwd() {
  const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
  let p = '';
  for (let i = 0; i < 12; i++) p += chars[Math.floor(Math.random() * chars.length)];
  document.querySelector('[name="password"]').value = p;
  document.querySelector('[name="password_confirm"]').value = p;
  alert('Generated password: ' + p + '\n\nMake sure to note this down before saving.');
}
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
