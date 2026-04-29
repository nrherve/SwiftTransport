<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Notification Logic: Count pending items for badges
$notif_pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();

$success = ''; $error = '';

// Update Status Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $id = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute(array($status, $id));
    $success = "Booking #$id updated to $status.";
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql = "SELECT b.*, u.full_name AS client_name FROM bookings b JOIN users u ON b.user_id = u.id";

if ($filter !== 'all') { 
    $sql .= " WHERE b.status = " . $pdo->quote($filter); 
}
$sql .= " ORDER BY b.created_at DESC";

$bookings = $pdo->query($sql)->fetchAll();

include '../includes/header.php';
?>

<style>
    /* Minimal CSS for the notification badge on the tab */
    .badge-count {
        background: #ef4444;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
        font-weight: bold;
    }
</style>

<main>
    <div class="page-title">
        <h1>Manage Bookings</h1>
        <p>Review details and update delivery status.</p>
    </div>

    <?php if ($success): ?>
        <div style="padding:10px; background:#dcfce7; color:#166534; margin-bottom:15px;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <?php
        $filter_labels = array(
            'all'        => 'All',
            'pending'    => 'Pending',
            'confirmed'  => 'Confirmed',
            'in-transit' => 'In Transit',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
        );

        foreach ($filter_labels as $val => $label):
            $active = ($filter === $val);
        ?>
        <a href="manage-bookings.php?filter=<?php echo $val; ?>"
           class="btn btn-sm <?php echo $active ? 'btn-primary' : ''; ?>"
           style="<?php echo !$active ? 'background:var(--white);border:1px solid var(--border);color:var(--text-dark);' : ''; ?> display: flex; align-items: center;">
            
            <?php echo $label; ?>
            
            <?php if ($val === 'pending' && $notif_pending > 0): ?>
                <span class="badge-count"><?php echo $notif_pending; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr style="background:#1a3c5e;">
                    <th>#</th>
                    <th>Client</th>
                    <th>Vehicle</th>
                    <th>KM</th>
                    <th>Pickup Address</th>
                    <th>Dropoff Address</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?php echo $b['id']; ?></td>
                    <td><?php echo htmlspecialchars($b['client_name']); ?></td>
                    <td><?php echo ucfirst($b['vehicle_type']); ?></td>
                    <td><?php echo $b['distance_km']; ?></td>
                    <td><?php echo htmlspecialchars($b['pickup_location_id']); ?></td>
                    <td><?php echo htmlspecialchars($b['dropoff_location_id']); ?></td>
                    <td><?php echo number_format($b['price']); ?> RWF</td>
                    <td><?php echo ucfirst($b['status']); ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                            <select name="status">
                                <option value="pending" <?php if($b['status']=='pending') echo 'selected';?>>Pending</option>
                                <option value="confirmed" <?php if($b['status']=='confirmed') echo 'selected';?>>Confirmed</option>
                                <option value="in-transit" <?php if($b['status']=='in-transit') echo 'selected';?>>In-Transit</option>
                                <option value="delivered" <?php if($b['status']=='delivered') echo 'selected';?>>Delivered</option>
                                <option value="cancelled" <?php if($b['status']=='cancelled') echo 'selected';?>>Cancelled</option>
                            </select>
                            <button type="submit" class="btn btn-success btn-sm">✓</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include '../includes/footer.php'; ?>