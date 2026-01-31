<?php
require __DIR__ . '/../app/bootstrap.php';
$config = require __DIR__ . '/../app/config.php';

$today = new DateTimeImmutable('now');
$tomorrow = $today->modify('+1 day')->format('Y-m-d');

$stmt = db()->prepare(
    "SELECT id, name, email, service, date, time
     FROM bookings
     WHERE date = :date AND status = 'accepted' AND reminder_sent = 0"
);
$stmt->execute([':date' => $tomorrow]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($bookings as $booking) {
    $clientSubject = "Reminder: your appointment is tomorrow";
    $clientBody = "Hi {$booking['name']},\n\nJust a reminder about your Nellys Nails appointment tomorrow.\n\nService: {$booking['service']}\nDate/time: {$booking['date']} at {$booking['time']}\n\nSee you soon!\n\nNellys Nails";
    $studioSubject = "Reminder: appointment tomorrow - {$booking['name']}";
    $studioBody = "Reminder: upcoming appointment tomorrow.\n\nName: {$booking['name']}\nEmail: {$booking['email']}\nService: {$booking['service']}\nDate/time: {$booking['date']} at {$booking['time']}\n\nNellys Nails";

    $clientSent = send_email($booking['email'], $clientSubject, $clientBody);
    $studioSent = send_email($config['studio_email'], $studioSubject, $studioBody);

    if ($clientSent && $studioSent) {
        $update = db()->prepare('UPDATE bookings SET reminder_sent = 1, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ':id' => $booking['id'],
        ]);
    }
}

echo "Reminders sent: " . count($bookings) . "\n";

