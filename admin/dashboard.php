<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Stats
$total_users     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_bookings  = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_pending   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$total_confirmed = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$total_transit   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'in-transit'")->fetchColumn();
$total_delivered = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'delivered'")->fetchColumn();
$total_feedback  = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avg_rating      = $pdo->query("SELECT ROUND(AVG(rating), 1) FROM feedback")->fetchColumn();

// Recent Bookings - Matches your new table columns from image_e0bd17.png
$recent_stmt = $pdo->query("
    SELECT 
        b.id, b.item_name, b.vehicle_type, b.pickup_location_id, 
        b.dropoff_location_id, b.price, b.status, b.created_at,
        u.full_name AS client_name
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 8
");
$recent = $recent_stmt->fetchAll();

// Status badge map
$badge = array(
    'pending'    => 'badge-pending',
    'confirmed'  => 'badge-confirmed',
    'in-transit' => 'badge-transit',
    'delivered'  => 'badge-delivered',
    'cancelled'  => 'badge-cancelled',
);

include '../includes/header.php';
?>

<main>
    <div class="flex-between mb-3">
        <div class="page-title" style="margin-bottom:0;">
            <h1>Admin Dashboard</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong></p>
        </div>
        <span style="font-size:0.85rem;color:var(--text-muted);"><?php echo date('l, d F Y'); ?></span>
    </div>

    <div class="stat-grid">
        <div class="stat-box"><div class="stat-num"><?php echo (int)$total_users; ?></div><div class="stat-label">Total Users</div></div>
        <div class="stat-box" style="border-left-color:#0284c7;"><div class="stat-num"><?php echo (int)$total_bookings; ?></div><div class="stat-label">Total Bookings</div></div>
        <div class="stat-box" style="border-left-color:var(--warning);"><div class="stat-num"><?php echo (int)$total_pending; ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-box" style="border-left-color:var(--success);"><div class="stat-num"><?php echo (int)$total_delivered; ?></div><div class="stat-label">Delivered</div></div>
    </div>

    <div style="display:flex;gap:0.8rem;flex-wrap:wrap;margin: 2rem 0;">
        <a href="manage-users.php" class="btn btn-primary btn-sm">Manage Users</a>
        <a href="manage-bookings.php" class="btn btn-primary btn-sm">Manage Bookings</a>
        <a href="manage-pricing.php" class="btn btn-primary btn-sm">Global Rates</a>
    </div>

    <div class="page-title"><h1 style="font-size:1.3rem;">Recent Bookings</h1></div>
    <div class="table-wrap mb-3">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Vehicle</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="7">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $b): 
                        // Fixed the PHP error here
                        $status_key = $b['status'];
                        $cls = isset($badge[$status_key]) ? $badge[$status_key] : 'badge-pending'; 
                    ?>
                        <tr>
                            <td><?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['client_name']); ?></td>
                            <td><small><?php echo ucfirst($b['vehicle_type']); ?></small></td>
                            <td><?php echo htmlspecialchars($b['pickup_location_id']); ?></td>
                            <td><?php echo htmlspecialchars($b['dropoff_location_id']); ?></td>
                            <td><?php echo number_format($b['price']); ?> RWF</td>
                            <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include '../includes/footer.php'; ?>