<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute(array($_GET['delete'], $user_id));
    if ($stmt->rowCount()) {
        $success = 'Booking removed successfully.';
    } else {
        $error = 'Cannot remove this booking (it may have already been confirmed or delivered).';
    }
}

// Fetch all bookings
$stmt = $pdo->prepare("
    SELECT b.*, l1.location_name AS pickup, l2.location_name AS dropoff
    FROM bookings b
    JOIN locations l1 ON b.pickup_location_id = l1.id
    JOIN locations l2 ON b.dropoff_location_id = l2.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute(array($user_id));
$bookings = $stmt->fetchAll();

include '../includes/header.php';
?>

<main>
    <div class="flex-between mb-3">
        <div class="page-title" style="margin-bottom:0;">
            <h1>My Bookings</h1>
            <p>All your transport requests in one place.</p>
        </div>
        <a href="booking-form.php" class="btn btn-accent">+ New Booking</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">You have no bookings yet. <a href="booking-form.php">Book your first transport!</a></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Qty/Weight</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Price (RWF)</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($bookings as $b) {
                    $badges = array(
                        'pending'    => 'badge-pending',
                        'confirmed'  => 'badge-confirmed',
                        'in-transit' => 'badge-transit',
                        'delivered'  => 'badge-delivered',
                        'cancelled'  => 'badge-cancelled'
                    );
                    $cls = isset($badges[$b['status']]) ? $badges[$b['status']] : 'badge-pending';
                ?>
                <tr>
                    <td><?php echo $b['id']; ?></td>
                    <td><?php echo htmlspecialchars($b['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['quantity_weight']); ?></td>
                    <td><?php echo htmlspecialchars($b['pickup']); ?></td>
                    <td><?php echo htmlspecialchars($b['dropoff']); ?></td>
                    <td><strong><?php echo number_format($b['price']); ?></strong></td>
                    <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                    <td>
                        <?php if ($b['status'] === 'pending'): ?>
                            <a href="booking-form.php?edit=<?php echo $b['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="my-bookings.php?delete=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this booking?')">Remove</a>
                        <?php elseif ($b['status'] === 'delivered'): ?>
                            <a href="feedback.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-success btn-sm">Feedback</a>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>