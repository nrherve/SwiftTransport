<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// --- NOTIFICATION LOGIC (Updated table name to 'admin') ---
try {
    $notif_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    
    // Changing 'admins' to 'admin' here to fix the Fatal Error
    $notif_admins   = $pdo->query("SELECT COUNT(*) FROM admin WHERE is_approved = 0")->fetchColumn();
    
    $notif_feedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
} catch (PDOException $e) {
    // If it still fails, we set them to 0 so the page doesn't crash
    $notif_bookings = 0;
    $notif_admins   = 0;
    $notif_feedback = 0;
}

$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute(array($_GET['delete']));
    $success = 'Feedback deleted.';
}

// Stats
$avg_rating  = $pdo->query("SELECT ROUND(AVG(rating),1) FROM feedback")->fetchColumn();
$total_fb    = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$five_star   = $pdo->query("SELECT COUNT(*) FROM feedback WHERE rating = 5")->fetchColumn();

// All feedback
$feedbacks = $pdo->query("
    SELECT f.*, u.full_name,
           b.item_name,
           b.pickup_location_id AS pickup, 
           b.dropoff_location_id AS dropoff
    FROM feedback f
    JOIN bookings b ON f.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    ORDER BY f.created_at DESC
")->fetchAll();

include '../includes/header.php';
?>

<main>
    <div class="page-title">
        <h1>Client Feedback</h1>
        <p>All ratings and reviews from your customers.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);max-width:500px;margin-bottom:2rem;">
        <div class="stat-box">
            <div class="stat-num"><?php echo $total_fb; ?></div>
            <div class="stat-label">Total Reviews</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--accent);">
            <div class="stat-num"><?php echo $avg_rating ? $avg_rating . ' ⭐' : 'N/A'; ?></div>
            <div class="stat-label">Avg Rating</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--success);">
            <div class="stat-num"><?php echo $five_star; ?></div>
            <div class="stat-label">5-Star Reviews</div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client Name</th>
                    <th>Booking</th>
                    <th>Route</th>
                    <th>Rating</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($feedbacks)): ?>
                    <?php foreach ($feedbacks as $f): ?>
                    <tr>
                        <td><?php echo $f['id']; ?></td>
                        <td><?php echo htmlspecialchars($f['full_name']); ?></td>
                        <td>#<?php echo $f['booking_id']; ?> — <?php echo htmlspecialchars($f['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($f['pickup']); ?> → <?php echo htmlspecialchars($f['dropoff']); ?></td>
                        <td>
                            <span class="stars"><?php echo str_repeat('★', $f['rating']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($f['message']); ?></td>
                        <td><?php echo date('d M Y', strtotime($f['created_at'])); ?></td>
                        <td>
                            <a href="manage-feedback.php?delete=<?php echo $f['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No feedback yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include '../includes/footer.php'; ?>