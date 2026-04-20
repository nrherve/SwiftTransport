<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$error     = '';
$success   = '';

// Pre-select booking if coming from bookings page
$selected_booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Fetch delivered bookings for this user (that don't already have feedback)
$stmt = $pdo->prepare("
    SELECT b.id, l1.location_name AS pickup, l2.location_name AS dropoff, b.item_name
    FROM bookings b
    JOIN locations l1 ON b.pickup_location_id = l1.id
    JOIN locations l2 ON b.dropoff_location_id = l2.id
    LEFT JOIN feedback f ON f.booking_id = b.id
    WHERE b.user_id = ? AND b.status = 'delivered' AND f.id IS NULL
");
$stmt->execute(array($user_id));
$delivered = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)$_POST['booking_id'];
    $message    = trim($_POST['message']);
    $rating     = (int)$_POST['rating'];

    if (!$booking_id || !$message || !$rating) {
        $error = 'All fields are required.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5.';
    } else {
        // Verify booking belongs to user and is delivered
        $check = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'delivered'");
        $check->execute(array($booking_id, $user_id));
        if (!$check->fetch()) {
            $error = 'Invalid booking selected.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO feedback (full_name, booking_id, message, rating) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($user_name, $booking_id, $message, $rating));
            $success = 'Thank you for your feedback! We appreciate it.';

            // Refresh delivered list
            $stmt2 = $pdo->prepare("
                SELECT b.id, l1.location_name AS pickup, l2.location_name AS dropoff, b.item_name
                FROM bookings b
                JOIN locations l1 ON b.pickup_location_id = l1.id
                JOIN locations l2 ON b.dropoff_location_id = l2.id
                LEFT JOIN feedback f ON f.booking_id = b.id
                WHERE b.user_id = ? AND b.status = 'delivered' AND f.id IS NULL
            ");
            $stmt2->execute(array($user_id));
            $delivered = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

include '../includes/header.php';
?>

<main>
    <div class="form-wrap" style="max-width:540px;">
        <h2>Leave Feedback</h2>
        <p class="form-sub">Share your experience with Swift Transport. Your feedback helps us improve.</p>

        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <?php if (empty($delivered)): ?>
            <div class="alert alert-info">No delivered bookings available for feedback yet. Feedback can only be submitted for completed deliveries.</div>
        <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" value="<?php echo htmlspecialchars($user_name); ?>" readonly style="background:#f4f6f9;color:var(--text-muted);">
            </div>

            <div class="form-group">
                <label>Select Completed Booking</label>
                <select name="booking_id" required>
                    <option value="">-- Choose a booking --</option>
                    <?php foreach ($delivered as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($selected_booking_id == $d['id']) ? 'selected' : ''; ?>>
                            #<?php echo $d['id']; ?> — <?php echo htmlspecialchars($d['item_name']); ?> (<?php echo htmlspecialchars($d['pickup']); ?> → <?php echo htmlspecialchars($d['dropoff']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Rating (1 = Poor, 5 = Excellent)</label>
                <select name="rating" required>
                    <option value="">-- Select rating --</option>
                    <option value="5">⭐⭐⭐⭐⭐ — Excellent</option>
                    <option value="4">⭐⭐⭐⭐ — Good</option>
                    <option value="3">⭐⭐⭐ — Average</option>
                    <option value="2">⭐⭐ — Poor</option>
                    <option value="1">⭐ — Very Poor</option>
                </select>
            </div>

            <div class="form-group">
                <label>Your Message</label>
                <textarea name="message" placeholder="Tell us about your experience..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Submit Feedback</button>
        </form>
        <?php endif; ?>

        <hr class="divider">
        <p class="text-center text-muted" style="font-size:0.88rem;"><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>