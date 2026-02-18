<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();

function is_valid_date_value(string $value): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
}

$filter = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'accepted', 'rejected'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'all';
}
$notice = $_GET['notice'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $redirectNotice = '';

    if ($action === 'status' && $id > 0) {
        $status = $_POST['status'] ?? 'pending';
        if (in_array($status, ['pending', 'accepted', 'rejected'], true)) {
            $stmt = db()->prepare('UPDATE bookings SET status = :status, updated_at = :updated_at WHERE id = :id');
            $stmt->execute([
                ':status' => $status,
                ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                ':id' => $id,
            ]);

            $config = require __DIR__ . '/../app/config.php';
            if ($config['send_status_emails']) {
                $bookingStmt = db()->prepare('SELECT name, email, service, date, time FROM bookings WHERE id = :id');
                $bookingStmt->execute([':id' => $id]);
                $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
                if ($booking) {
                    $subject = "Booking " . ucfirst($status) . " - Nellys Nails";
                    $body = "Hi {$booking['name']},\n\nYour booking has been {$status}.\n\nService: {$booking['service']}\nDate/time: {$booking['date']} at {$booking['time']}\n\nReply to this email if you need changes.\n\nNellys Nails";
                    send_email($booking['email'], $subject, $body);
                }
            }
        }
    } elseif ($action === 'block_dates') {
        $start = trim($_POST['block_start'] ?? '');
        $end = trim($_POST['block_end'] ?? '');
        $note = trim($_POST['block_note'] ?? '');
        if ($end === '') {
            $end = $start;
        }

        if (!is_valid_date_value($start) || !is_valid_date_value($end)) {
            $redirectNotice = 'block-invalid';
        } else {
            $startDate = new DateTimeImmutable($start);
            $endDate = new DateTimeImmutable($end);
            if ($endDate < $startDate) {
                $redirectNotice = 'block-invalid';
            } else {
                $diffDays = (int)$startDate->diff($endDate)->days;
                if ($diffDays > 365) {
                    $redirectNotice = 'block-too-large';
                } else {
                    $insertStmt = db()->prepare(
                        'INSERT OR REPLACE INTO blocked_dates (date, note, created_at) VALUES (:date, :note, :created_at)'
                    );
                    $createdAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');
                    $addedCount = 0;
                    for ($cursor = $startDate; $cursor <= $endDate; $cursor = $cursor->modify('+1 day')) {
                        $insertStmt->execute([
                            ':date' => $cursor->format('Y-m-d'),
                            ':note' => $note,
                            ':created_at' => $createdAt,
                        ]);
                        $addedCount++;
                    }
                    $redirectNotice = 'block-added-' . $addedCount;
                }
            }
        }
    } elseif ($action === 'unblock_date') {
        $blockedDate = trim($_POST['blocked_date'] ?? '');
        if (is_valid_date_value($blockedDate)) {
            $deleteStmt = db()->prepare('DELETE FROM blocked_dates WHERE date = :date');
            $deleteStmt->execute([':date' => $blockedDate]);
            $redirectNotice = 'block-removed';
        } else {
            $redirectNotice = 'block-invalid';
        }
    }

    $redirect = '/admin/dashboard.php?status=' . urlencode($filter);
    if ($redirectNotice !== '') {
        $redirect .= '&notice=' . urlencode($redirectNotice);
    }
    header('Location: ' . $redirect);
    exit;
}

$sql = 'SELECT * FROM bookings';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE status = :status';
    $params[':status'] = $filter;
}
$sql .= ' ORDER BY date ASC, time ASC, created_at ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$blockedStmt = db()->prepare('SELECT date, note FROM blocked_dates ORDER BY date ASC');
$blockedStmt->execute();
$blockedDates = $blockedStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bookings | Nellys Nails</title>
    <link rel="stylesheet" href="/admin/admin.css?v=2" />
  </head>
  <body class="admin-body">
    <header class="admin-header">
      <div class="header-spacer" aria-hidden="true"></div>
      <div class="header-title">
        <h1>Bookings</h1>
        <p>Manage booking requests and updates.</p>
      </div>
      <div class="admin-actions">
        <a href="/admin/create.php">Add booking</a>
        <a href="/admin/change-password.php">Change password</a>
        <a class="logout" href="/admin/logout.php">Logout</a>
      </div>
    </header>

    <nav class="admin-filters">
      <a href="/admin/dashboard.php?status=pending">Pending</a>
      <a href="/admin/dashboard.php?status=accepted">Accepted</a>
      <a href="/admin/dashboard.php?status=rejected">Rejected</a>
      <a href="/admin/dashboard.php?status=all">All</a>
    </nav>

    <main class="admin-main">
      <section class="admin-card block-card">
        <h2>Block dates</h2>
        <p>Use this for holidays or days off. Clients cannot book blocked dates.</p>
        <form method="post">
          <input type="hidden" name="action" value="block_dates" />
          <label for="block-start">Start date</label>
          <input id="block-start" name="block_start" type="date" required />
          <label for="block-end">End date</label>
          <input id="block-end" name="block_end" type="date" />
          <label for="block-note">Reason (optional)</label>
          <input id="block-note" name="block_note" type="text" maxlength="120" placeholder="Holiday" />
          <button type="submit">Block date range</button>
        </form>

        <?php if ($notice === 'block-invalid'): ?>
          <p class="alert error">Please use a valid start/end date.</p>
        <?php elseif ($notice === 'block-too-large'): ?>
          <p class="alert error">Date range is too large. Maximum is 365 days.</p>
        <?php elseif ($notice === 'block-removed'): ?>
          <p class="alert">Date removed from blocked list.</p>
        <?php elseif (strpos($notice, 'block-added-') === 0): ?>
          <p class="alert">
            Blocked
            <?php echo (int)substr($notice, strlen('block-added-')); ?>
            date(s).
          </p>
        <?php endif; ?>

        <?php if (empty($blockedDates)): ?>
          <p class="empty">No blocked dates yet.</p>
        <?php else: ?>
          <div class="blocked-list">
            <?php foreach ($blockedDates as $blocked): ?>
              <div class="blocked-item">
                <div>
                  <strong><?php echo escape($blocked['date']); ?></strong>
                  <?php if (!empty($blocked['note'])): ?>
                    <span><?php echo escape($blocked['note']); ?></span>
                  <?php endif; ?>
                </div>
                <form method="post">
                  <input type="hidden" name="action" value="unblock_date" />
                  <input type="hidden" name="blocked_date" value="<?php echo escape($blocked['date']); ?>" />
                  <button type="submit" class="secondary">Unblock</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <?php if (empty($bookings)): ?>
        <p class="empty">No bookings yet.</p>
      <?php endif; ?>

      <?php foreach ($bookings as $booking): ?>
        <article
          class="booking-card"
          data-id="<?php echo (int)$booking['id']; ?>"
          data-name="<?php echo escape($booking['name']); ?>"
          data-service="<?php echo escape($booking['service']); ?>"
          data-date="<?php echo escape($booking['date']); ?>"
          data-time="<?php echo escape($booking['time']); ?>"
          data-email="<?php echo escape($booking['email']); ?>"
          data-phone="<?php echo escape($booking['phone']); ?>"
          data-notes="<?php echo escape($booking['notes'] ?? ''); ?>"
          data-status="<?php echo escape($booking['status']); ?>"
        >
          <div class="booking-details">
            <h3><?php echo escape($booking['name']); ?></h3>
            <p><strong>Service:</strong> <?php echo escape($booking['service']); ?></p>
            <p><strong>Date/time:</strong> <?php echo escape($booking['date']); ?> at <?php echo escape($booking['time']); ?></p>
            <p><strong>Email:</strong> <?php echo escape($booking['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo escape($booking['phone']); ?></p>
            <?php if (!empty($booking['notes'])): ?>
              <p><strong>Notes:</strong> <?php echo escape($booking['notes']); ?></p>
            <?php endif; ?>
            <p class="status">Status: <span><?php echo escape($booking['status']); ?></span></p>
          </div>
          <div class="tap-hint" aria-hidden="true">
            <?php echo escape($booking['name']); ?>
            <span class="tap-date"><?php echo escape($booking['date']); ?></span>
          </div>
          <div class="booking-actions">
            <?php if ($booking['status'] === 'pending'): ?>
              <form method="post">
                <input type="hidden" name="action" value="status" />
                <input type="hidden" name="id" value="<?php echo (int)$booking['id']; ?>" />
                <input type="hidden" name="status" value="accepted" />
                <button type="submit">Accept</button>
              </form>
              <form method="post">
                <input type="hidden" name="action" value="status" />
                <input type="hidden" name="id" value="<?php echo (int)$booking['id']; ?>" />
                <input type="hidden" name="status" value="rejected" />
                <button type="submit" class="secondary">Reject</button>
              </form>
            <?php endif; ?>
            <a class="secondary" href="/admin/edit.php?id=<?php echo (int)$booking['id']; ?>">Edit</a>
          </div>
        </article>
      <?php endforeach; ?>
    </main>

    <div class="booking-modal" id="booking-modal" aria-hidden="true">
      <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <h2 id="modal-title">Booking details</h2>
        <div class="modal-details">
          <p><strong>Name:</strong> <span data-field="name"></span></p>
          <p><strong>Service:</strong> <span data-field="service"></span></p>
          <p><strong>Date/time:</strong> <span data-field="datetime"></span></p>
          <p><strong>Email:</strong> <span data-field="email"></span></p>
          <p><strong>Phone:</strong> <span data-field="phone"></span></p>
          <p><strong>Notes:</strong> <span data-field="notes"></span></p>
          <p><strong>Status:</strong> <span data-field="status"></span></p>
        </div>
        <div class="modal-actions" data-field="actions">
          <form method="post" data-action="accept">
            <input type="hidden" name="action" value="status" />
            <input type="hidden" name="id" value="" />
            <input type="hidden" name="status" value="accepted" />
            <button type="submit">Accept</button>
          </form>
          <form method="post" data-action="reject">
            <input type="hidden" name="action" value="status" />
            <input type="hidden" name="id" value="" />
            <input type="hidden" name="status" value="rejected" />
            <button type="submit" class="secondary">Reject</button>
          </form>
          <a class="secondary" href="#" data-field="edit-link">Edit</a>
          <button type="button" class="close" data-field="close">Close</button>
        </div>
      </div>
    </div>

    <script>
      const modal = document.getElementById("booking-modal");
      const cards = document.querySelectorAll(".booking-card");
      const isCompact = window.matchMedia("(max-width: 900px)");

      function setCompactState() {
        cards.forEach((card) => {
          card.classList.toggle("is-compact", isCompact.matches);
        });
      }

      function openModal(card) {
        if (!isCompact.matches) return;
        const fields = {
          name: card.dataset.name || "",
          service: card.dataset.service || "",
          datetime: `${card.dataset.date || ""} at ${card.dataset.time || ""}`,
          email: card.dataset.email || "",
          phone: card.dataset.phone || "",
          notes: card.dataset.notes || "—",
          status: card.dataset.status || "",
        };

        modal.querySelector('[data-field="name"]').textContent = fields.name;
        modal.querySelector('[data-field="service"]').textContent = fields.service;
        modal.querySelector('[data-field="datetime"]').textContent = fields.datetime;
        modal.querySelector('[data-field="email"]').textContent = fields.email;
        modal.querySelector('[data-field="phone"]').textContent = fields.phone;
        modal.querySelector('[data-field="notes"]').textContent = fields.notes || "—";
        modal.querySelector('[data-field="status"]').textContent = fields.status;

        const idInputs = modal.querySelectorAll('input[name="id"]');
        idInputs.forEach((input) => {
          input.value = card.dataset.id || "";
        });

        const editLink = modal.querySelector('[data-field="edit-link"]');
        editLink.href = `/admin/edit.php?id=${card.dataset.id || ""}`;

        const pending = (card.dataset.status || "").toLowerCase() === "pending";
        modal.querySelector('[data-action="accept"]').style.display = pending ? "block" : "none";
        modal.querySelector('[data-action="reject"]').style.display = pending ? "block" : "none";

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
      }

      function closeModal() {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
      }

      setCompactState();
      isCompact.addEventListener("change", setCompactState);

      cards.forEach((card) => {
        card.addEventListener("click", (event) => {
          if (!isCompact.matches) return;
          if (event.target.closest("a, button, form, input, select, textarea")) {
            return;
          }
          openModal(card);
        });
      });

      modal.addEventListener("click", (event) => {
        if (event.target === modal || event.target.matches('[data-field="close"]')) {
          closeModal();
        }
      });

      window.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          closeModal();
        }
      });
    </script>
  </body>
</html>

