<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /admin/dashboard.php');
    exit;
}

$stmt = db()->prepare('SELECT * FROM bookings WHERE id = :id');
$stmt->execute([':id' => $id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: /admin/dashboard.php');
    exit;
}

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
        $stmt = db()->prepare(
            'UPDATE bookings
             SET name = :name, email = :email, phone = :phone, service = :service,
                 date = :date, time = :time, notes = :notes, status = :status, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':service' => $service,
            ':date' => $date,
            ':time' => $time,
            ':notes' => $notes,
            ':status' => $status,
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ':id' => $id,
        ]);

        header('Location: /admin/dashboard.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Booking | Nellys Nails</title>
    <link rel="stylesheet" href="/admin/admin.css?v=2" />
  </head>
  <body class="admin-body">
    <header class="admin-header">
      <div>
        <h1>Edit Booking</h1>
        <p>Update appointment details and status.</p>
      </div>
      <div class="admin-actions">
        <a href="/admin/dashboard.php">Back to bookings</a>
      </div>
    </header>

    <main class="admin-main">
      <form class="edit-form" method="post">
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="<?php echo escape($booking['name']); ?>" required />

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?php echo escape($booking['email']); ?>" required />

        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="text" value="<?php echo escape($booking['phone']); ?>" required />

        <label for="service">Service</label>
        <input id="service" name="service" type="text" value="<?php echo escape($booking['service']); ?>" required />

        <label for="date">Date</label>
        <input id="date" name="date" type="date" value="<?php echo escape($booking['date']); ?>" required />

        <label for="time">Time</label>
        <input id="time" name="time" type="time" value="<?php echo escape($booking['time']); ?>" required />

        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="4"><?php echo escape($booking['notes']); ?></textarea>

        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="accepted" <?php echo $booking['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
          <option value="rejected" <?php echo $booking['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>

        <button type="submit">Save changes</button>
      </form>
    </main>
  </body>
</html>

