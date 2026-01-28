<?php
require __DIR__ . '/app/bootstrap.php';

$config = require __DIR__ . '/app/config.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $service === '' || $date === '' || $time === '') {
    header('Location: /index.html?status=error');
    exit;
}

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
    ':status' => 'pending',
    ':created_at' => $now,
    ':updated_at' => $now,
]);

$clientSubject = "We received your booking request";
$clientBody = "Hi $name,\n\nThanks for booking with Nellys Nails.\n\nBooking reference: $reference\nService: $service\nPreferred date/time: $date at $time\n\nWe'll confirm soon. If you need changes, reply to this email.\n\nNellys Nails";

send_email($email, $clientSubject, $clientBody);

$studioSubject = "New booking request - $reference";
$studioBody = "New booking request:\n\nName: $name\nEmail: $email\nPhone: $phone\nService: $service\nPreferred date/time: $date at $time\nNotes: $notes\nReference: $reference";

send_email($config['studio_email'], $studioSubject, $studioBody);

header('Location: /index.html?status=success');
exit;

