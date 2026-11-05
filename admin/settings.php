<?php
require_once __DIR__ . '/layout-start.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$success = '';
$error = '';

if (empty($_SESSION['settings_csrf'])) {
    $_SESSION['settings_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['settings_csrf'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($name === '' || $email === '') {
                $error = 'Full name and email address are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $check = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1');
                $check->bind_param('si', $email, $userId);
                $check->execute();
                $duplicate = $check->get_result()->num_rows > 0;
                $check->close();

                if ($duplicate) {
                    $error = 'This email address is already used by another account.';
                } else {
                    $update = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ? AND role = 'admin'");
                    $update->bind_param('sssi', $name, $email, $phone, $userId);
                    if ($update->execute()) {
                        $_SESSION['user_name'] = $name;
                        $success = 'Profile information updated successfully.';
                    } else {
                        $error = 'Could not update profile. Please try again.';
                    }
                    $update->close();
                }
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ? AND role = 'admin' LIMIT 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $account = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
                $error = 'Your current password is incorrect.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'New password must contain at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New password and confirm password do not match.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ? AND role = 'admin'");
                $update->bind_param('si', $newHash, $userId);
                if ($update->execute()) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Could not change password. Please try again.';
                }
                $update->close();
            }
        }
    }
}

$stmt = $conn->prepare("SELECT user_id, full_name, email, phone, status FROM users WHERE user_id = ? AND role = 'admin' LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<style>
.settings-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:18px;align-items:start}
.settings-card{background:var(--panel);border:1px solid var(--border);border-radius:11px;padding:22px;margin-bottom:18px}
.settings-card h3{font-size:15px;margin:0}.settings-description{color:var(--muted);font-size:11px;line-height:1.6;margin:7px 0 20px}
.settings-form label{display:block;color:#d9cce0;font-size:11px;margin:12px 0 5px}.settings-form .form-control{margin:0;padding:11px 12px;font-size:12px}
.settings-form .btn{margin-top:17px;padding:10px 16px;font-size:12px}.settings-info{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);font-size:11px}.settings-info:last-child{border-bottom:0}.settings-info span:first-child{color:var(--muted)}.settings-info strong{color:var(--text);text-align:right;overflow-wrap:anywhere}.settings-icon{width:38px;height:38px;border-radius:9px;display:grid;place-items:center;background:#d01fe626;color:#e84bf2;font-size:18px;margin-bottom:13px}
@media(max-width:850px){.settings-grid{grid-template-columns:1fr}}
</style>

<section class="page-heading">
  <div><h1>Settings</h1><p>Manage your administrator profile and account security.</p></div>
  <span class="date-label">⚙ Account Settings</span>
</section>

<?php if ($success): ?><div class="notice"><?=h($success)?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?=h($error)?></div><?php endif; ?>

<?php if ($admin): ?>
<div class="settings-grid">
  <div>
    <section class="settings-card">
      <div class="settings-icon">♙</div>
      <h3>Profile Settings</h3>
      <p class="settings-description">Update the personal information associated with your administrator account.</p>
      <form method="post" class="settings-form">
        <input type="hidden" name="csrf_token" value="<?=h($_SESSION['settings_csrf'])?>">
        <input type="hidden" name="action" value="update_profile">
        <label for="full_name">Full Name</label>
        <input class="form-control" id="full_name" name="full_name" type="text" value="<?=h($admin['full_name'])?>" required>
        <label for="email">Email Address</label>
        <input class="form-control" id="email" name="email" type="email" value="<?=h($admin['email'])?>" required>
        <label for="phone">Phone Number</label>
        <input class="form-control" id="phone" name="phone" type="text" value="<?=h($admin['phone'])?>" placeholder="Enter phone number">
        <button class="btn" type="submit">Save Changes</button>
      </form>
    </section>

    <section class="settings-card">
      <div class="settings-icon">🔒</div>
      <h3>Change Password</h3>
      <p class="settings-description">For your account's safety, enter your current password before choosing a new one.</p>
      <form method="post" class="settings-form">
        <input type="hidden" name="csrf_token" value="<?=h($_SESSION['settings_csrf'])?>">
        <input type="hidden" name="action" value="change_password">
        <label for="current_password">Current Password</label>
        <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        <label for="new_password">New Password</label>
        <input class="form-control" id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password" required>
        <label for="confirm_password">Confirm New Password</label>
        <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
        <button class="btn" type="submit">Update Password</button>
      </form>
    </section>
  </div>

  <div>
    <section class="settings-card">
      <div class="settings-icon">ℹ</div>
      <h3>Account Information</h3>
      <p class="settings-description">A quick overview of your administrator account.</p>
      <div class="settings-info"><span>Account ID</span><strong>#<?=h($admin['user_id'])?></strong></div>
      <div class="settings-info"><span>Account Type</span><strong>Administrator</strong></div>
      <div class="settings-info"><span>Account Status</span><strong><span class="status <?=h($admin['status'])?>"><?=h(ucfirst($admin['status']))?></span></strong></div>
      <div class="settings-info"><span>Database</span><strong>event_decoration_db</strong></div>
      <div class="settings-info"><span>Database Engine</span><strong>MySQL / InnoDB</strong></div>
    </section>

    <section class="settings-card">
      <div class="settings-icon">🛡</div>
      <h3>Security Tips</h3>
      <p class="settings-description">Keep your administrator account secure.</p>
      <ul style="color:#c9bacf;font-size:11px;line-height:2;padding-left:18px;margin-bottom:0">
        <li>Use a strong password with at least 8 characters.</li>
        <li>Do not share your administrator password.</li>
        <li>Always log out when using a shared computer.</li>
      </ul>
    </section>
  </div>
</div>
<?php else: ?>
  <div class="notice error">Admin profile could not be loaded. Please log in again.</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
