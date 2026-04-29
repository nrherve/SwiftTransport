<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Stats Logic
$total    = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$total->execute(array($user_id));
$total_bookings = $total->fetchColumn();

$pending = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending'");
$pending->execute(array($user_id));
$pending_count = $pending->fetchColumn();

$delivered = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'delivered'");
$delivered->execute(array($user_id));
$delivered_count = $delivered->fetchColumn();

// Recent bookings logic - Updated to fetch location names directly from bookings table
$stmt = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE user_id = ? 
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute(array($user_id));
$recent = $stmt->fetchAll();

include '../includes/header.php';
?>

<main>
    <div class="flex-between mb-3">
        <div class="page-title" style="margin-bottom:0;">
            <h1>My Dashboard</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!</p>
        </div>
        <a href="booking-form.php" class="btn btn-accent">+ New Booking</a>
    </div>

    <div class="stat-grid">
        <div class="stat-box">
            <div class="stat-num"><?php echo $total_bookings; ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--warning);">
            <div class="stat-num"><?php echo $pending_count; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--success);">
            <div class="stat-num"><?php echo $delivered_count; ?></div>
            <div class="stat-label">Delivered</div>
        </div>
    </div>

    <div class="page-title">
        <h1 style="font-size:1.3rem;">Recent Bookings</h1>
    </div>

    <?php if (empty($recent)): ?>
        <div class="alert alert-info">You have no bookings yet. <a href="booking-form.php">Book your first transport!</a></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Design-safe badge mapping
                $badges = array(
                    'pending'    => 'badge-pending',
                    'confirmed'  => 'badge-confirmed',
                    'in-transit' => 'badge-transit',
                    'delivered'  => 'badge-delivered',
                    'cancelled'  => 'badge-cancelled',
                );

                foreach ($recent as $b):
                    $cls = isset($badges[$b['status']]) ? $badges[$b['status']] : 'badge-pending';
                ?>
                <tr>
                    <td><?php echo $b['id']; ?></td>
                    <td><?php echo htmlspecialchars($b['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['pickup_location_id']); ?></td>
                    <td><?php echo htmlspecialchars($b['dropoff_location_id']); ?></td>
                    <td><strong><?php echo number_format($b['price']); ?> RWF</strong></td>
                    <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                    <td>
                        <?php if ($b['status'] === 'pending'): ?>
                            <a href="booking-form.php?edit=<?php echo $b['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="my-bookings.php?delete=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this booking?')">Remove</a>
                        <?php elseif ($b['status'] === 'delivered'): ?>
                            <a href="feedback.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-success btn-sm">Feedback</a>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="my-bookings.php" class="btn btn-primary btn-sm">View All Bookings</a>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>