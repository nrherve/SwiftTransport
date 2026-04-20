<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error   = '';

// ── Update Status ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $id     = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    $allowed = array('pending','confirmed','in-transit','delivered','cancelled');

    if (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute(array($status, $id));
        $success = 'Booking #' . $id . ' status updated to "' . $status . '".';
    } else {
        $error = 'Invalid status value.';
    }
}

// ── Delete ───────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt   = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute(array($del_id));
    $success = 'Booking #' . $del_id . ' deleted.';
}

// ── Filter ───────────────────────────────────────────────────────────────────
$allowed_filters = array('all','pending','confirmed','in-transit','delivered','cancelled');
$filter = (isset($_GET['filter']) && in_array($_GET['filter'], $allowed_filters))
          ? $_GET['filter'] : 'all';

if ($filter === 'all') {
    $stmt = $pdo->query("
        SELECT
            b.id, b.item_name, b.quantity_weight, b.price, b.status, b.created_at,
            u.full_name  AS client_name,
            l1.location_name AS pickup,
            l2.location_name AS dropoff
        FROM bookings b
        JOIN users     u  ON b.user_id             = u.id
        JOIN locations l1 ON b.pickup_location_id  = l1.id
        JOIN locations l2 ON b.dropoff_location_id = l2.id
        ORDER BY b.created_at DESC
    ");
    $bookings = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT
            b.id, b.item_name, b.quantity_weight, b.price, b.status, b.created_at,
            u.full_name  AS client_name,
            l1.location_name AS pickup,
            l2.location_name AS dropoff
        FROM bookings b
        JOIN users     u  ON b.user_id             = u.id
        JOIN locations l1 ON b.pickup_location_id  = l1.id
        JOIN locations l2 ON b.dropoff_location_id = l2.id
        WHERE b.status = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute(array($filter));
    $bookings = $stmt->fetchAll();
}

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
    <div class="page-title">
        <h1>Manage Bookings</h1>
        <p>Confirm, update status, or delete bookings.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Filter Tabs -->
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
           style="<?php echo !$active ? 'background:var(--white);border:1px solid var(--border);color:var(--text-dark);' : ''; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Item</th>
                    <th>Qty/Wt</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Price (RWF)</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted" style="padding:2rem;">No bookings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b):
                        $cls = isset($badge[$b['status']]) ? $badge[$b['status']] : 'badge-pending';
                    ?>
                    <tr>
                        <td><?php echo (int)$b['id']; ?></td>
                        <td><?php echo htmlspecialchars($b['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['quantity_weight']); ?></td>
                        <td><?php echo htmlspecialchars($b['pickup']); ?></td>
                        <td><?php echo htmlspecialchars($b['dropoff']); ?></td>
                        <td><strong><?php echo number_format((float)$b['price']); ?></strong></td>
                        <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                        <td>
                            <!-- Update status form -->
                            <form method="POST" action="manage-bookings.php?filter=<?php echo $filter; ?>"
                                  style="display:inline-flex;gap:0.3rem;align-items:center;">
                                <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                                <select name="status" style="padding:0.3rem 0.5rem;border-radius:6px;border:1px solid var(--border);font-size:0.8rem;">
                                    <?php foreach ($filter_labels as $v => $lbl):
                                        if ($v === 'all') continue;
                                    ?>
                                    <option value="<?php echo $v; ?>" <?php echo ($b['status'] === $v ? 'selected' : ''); ?>>
                                        <?php echo $lbl; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-success btn-sm">✓</button>
                            </form>
                            <!-- Delete -->
                            <a href="manage-bookings.php?delete=<?php echo (int)$b['id']; ?>&filter=<?php echo $filter; ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete booking #<?php echo (int)$b['id']; ?>?');">Del</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>