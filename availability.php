<?php
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$date = trim($_GET['date'] ?? '');
if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['bookings' => []]);
    exit;
}

$stmt = db()->prepare(
    "SELECT time, service
     FROM bookings
     WHERE date = :date
       AND status != 'rejected'
     ORDER BY time ASC"
);
$stmt->execute([':date' => $date]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['bookings' => $bookings], JSON_UNESCAPED_UNICODE);
