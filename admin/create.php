<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $status = $_POST['status'] ?? 'pending';

    if ($name !== '' && $email !== '' && $phone !== '' && $service !== '' && $date !== '' && $time !== '') {
        $reference = strtoupper(bin2hex(random_bytes(4)));
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = db()->prepare(
            'INSERT INTO bookings (reference, name, email, phone, service, date, time, notes, status, reminder_sent, created_at, updated_at)
             VALUES (:reference, :name, :email, :phone, :service, :date, :time, :notes, :status, 0, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':reference' => $reference,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':service' => $service,
            ':date' => $date,
            ':time' => $time,
            ':notes' => $notes,
            ':status' => in_array($status, ['pending', 'accepted', 'rejected'], true) ? $status : 'pending',
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        header('Location: /admin/dashboard.php');
        exit;
    }

    $error = 'Please fill in all required fields.';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Booking | Nellys Nails</title>
    <link rel="stylesheet" href="/admin/admin.css?v=5" />
  </head>
  <body class="admin-body">
    <header class="admin-header">
      <div class="header-spacer" aria-hidden="true"></div>
      <div class="header-title">
        <h1>Add Booking</h1>
        <p>Create a manual booking entry.</p>
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

        <label for="name">Name</label>
        <input id="name" name="name" type="text" required />

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required />

        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="text" required />

        <label for="service">Service</label>
        <input id="service" name="service" type="text" required />

        <label for="date">Date</label>
        <input id="date" name="date" type="date" required />

        <label for="time">Time</label>
        <input id="time" name="time" type="time" required />

        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="4"></textarea>

        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="pending">Pending</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
        </select>

        <button type="submit">Add booking</button>
      </form>
    </main>
  </body>
</html>
