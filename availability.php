<?php
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$date = trim($_GET['date'] ?? '');
$month = trim($_GET['month'] ?? '');

if ($date === '' && $month === '') {
    http_response_code(400);
    echo json_encode(['bookings' => [], 'blocked' => false, 'blockedDates' => []]);
    exit;
}

$payload = [
    'bookings' => [],
    'blocked' => false,
    'blockedDates' => [],
];

if ($month !== '') {
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        http_response_code(400);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = db()->prepare(
        "SELECT date
         FROM blocked_dates
         WHERE substr(date, 1, 7) = :month
         ORDER BY date ASC"
    );
    $stmt->execute([':month' => $month]);
    $payload['blockedDates'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'date');
}

if ($date !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $blockedStmt = db()->prepare('SELECT 1 FROM blocked_dates WHERE date = :date LIMIT 1');
    $blockedStmt->execute([':date' => $date]);
    $payload['blocked'] = (bool)$blockedStmt->fetchColumn();

    if (!$payload['blocked']) {
        $stmt = db()->prepare(
            "SELECT time, service
             FROM bookings
             WHERE date = :date
               AND status != 'rejected'
             ORDER BY time ASC"
        );
        $stmt->execute([':date' => $date]);
        $payload['bookings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
