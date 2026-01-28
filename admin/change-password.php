<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'Please fill out all fields.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new) < 10) {
        $error = 'New password must be at least 10 characters.';
    } else {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute([':id' => (int)$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            $update = db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $update->execute([
                ':hash' => password_hash($new, PASSWORD_DEFAULT),
                ':id' => (int)$_SESSION['user_id'],
            ]);
            $message = 'Password updated successfully.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Change Password | Nellys Nails</title>
    <link rel="stylesheet" href="/admin/admin.css?v=2" />
  </head>
  <body class="admin-body">
    <header class="admin-header">
      <div>
        <h1>Change Password</h1>
        <p>Set a new password for your account.</p>
      </div>
      <div class="admin-actions">
        <a href="/admin/dashboard.php">Back to bookings</a>
      </div>
    </header>

    <main class="admin-main">
      <form class="edit-form" method="post">
        <?php if ($error): ?>
          <div class="alert error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
          <div class="alert"><?php echo escape($message); ?></div>
        <?php endif; ?>

        <label for="current-password">Current password</label>
        <input id="current-password" name="current_password" type="password" required />

        <label for="new-password">New password</label>
        <input id="new-password" name="new_password" type="password" required />

        <label for="confirm-password">Confirm new password</label>
        <input id="confirm-password" name="confirm_password" type="password" required />

        <button type="submit">Update password</button>
      </form>
    </main>
  </body>
</html>
