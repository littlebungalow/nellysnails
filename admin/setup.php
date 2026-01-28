<?php
require __DIR__ . '/../app/bootstrap.php';

// Run this once, then delete this file for security.

$users = [
    ['username' => 'nellys.nailssss', 'password' => 'r8G!vK2#M7pQx9$Zs4L!tN6@'],
];

$created = 0;
foreach ($users as $user) {
    $stmt = db()->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute([':username' => $user['username']]);
    if ($stmt->fetch()) {
        continue;
    }

    $stmt = db()->prepare(
        'INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, :created_at)'
    );
    $stmt->execute([
        ':username' => $user['username'],
        ':password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
        ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
    $created++;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Setup</title>
    <link rel="stylesheet" href="/admin/admin.css?v=2" />
  </head>
  <body class="admin-body">
    <div class="admin-card">
      <h1>Admin setup complete</h1>
      <p>Created <?php echo (int)$created; ?> new users.</p>
      <p><strong>Temporary password:</strong> <?php echo escape($users[0]['password']); ?></p>
      <p>Delete this file after login: <code>/admin/setup.php</code></p>
      <a class="button-link" href="/admin/index.php">Go to login</a>
    </div>
  </body>
</html>
