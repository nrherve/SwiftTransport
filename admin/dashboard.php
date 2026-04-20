<?php
session_start();
require_once '../config/db.php';   // adjust path if db.php is elsewhere

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// ── Stats ────────────────────────────────────────────────────────────────────
$total_users     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_bookings  = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_pending   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$total_confirmed = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$total_transit   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'in-transit'")->fetchColumn();
$total_delivered = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'delivered'")->fetchColumn();
$total_feedback  = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avg_rating      = $pdo->query("SELECT ROUND(AVG(rating), 1) FROM feedback")->fetchColumn();

// ── Recent Bookings ──────────────────────────────────────────────────────────
$recent_stmt = $pdo->query("
    SELECT
        b.id,
        b.item_name,
        b.quantity_weight,
        b.price,
        b.status,
        b.created_at,
        u.full_name  AS client_name,
        l1.location_name AS pickup,
        l2.location_name AS dropoff
    FROM bookings b
    LEFT JOIN users     u  ON b.user_id             = u.id
    LEFT JOIN locations l1 ON b.pickup_location_id  = l1.id
    LEFT JOIN locations l2 ON b.dropoff_location_id = l2.id
    ORDER BY b.created_at DESC
    LIMIT 8
");
$recent = $recent_stmt->fetchAll();

// ── Newest Users ─────────────────────────────────────────────────────────────
$new_users = $pdo->query("
    SELECT id, full_name, email, phone, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

// ── Status badge map ─────────────────────────────────────────────────────────
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

    <!-- Page Header -->
    <div class="flex-between mb-3">
        <div class="page-title" style="margin-bottom:0;">
            <h1>Admin Dashboard</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong> — here's the system overview.</p>
        </div>
        <span style="font-size:0.85rem;color:var(--text-muted);"><?php echo date('l, d F Y'); ?></span>
    </div>

    <!-- ── Stat Cards ─────────────────────────────────────────────────────── -->
    <div class="stat-grid">
        <div class="stat-box">
            <div class="stat-num"><?php echo (int)$total_users; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-box" style="border-left-color:#0284c7;">
            <div class="stat-num"><?php echo (int)$total_bookings; ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--warning);">
            <div class="stat-num"><?php echo (int)$total_pending; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-box" style="border-left-color:#1d4ed8;">
            <div class="stat-num"><?php echo (int)$total_confirmed; ?></div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-box" style="border-left-color:#7c3aed;">
            <div class="stat-num"><?php echo (int)$total_transit; ?></div>
            <div class="stat-label">In Transit</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--success);">
            <div class="stat-num"><?php echo (int)$total_delivered; ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--accent);">
            <div class="stat-num"><?php echo (int)$total_feedback; ?></div>
            <div class="stat-label">Feedbacks</div>
        </div>
        <div class="stat-box" style="border-left-color:var(--accent);">
            <div class="stat-num"><?php echo $avg_rating ? $avg_rating . ' ⭐' : 'N/A'; ?></div>
            <div class="stat-label">Avg Rating</div>
        </div>
    </div>

    <!-- ── Quick Nav ──────────────────────────────────────────────────────── -->
    <div style="display:flex;gap:0.8rem;flex-wrap:wrap;margin-bottom:2rem;">
        <a href="manage-users.php"     class="btn btn-primary btn-sm">Manage Users</a>
        <a href="manage-bookings.php"  class="btn btn-primary btn-sm">Manage Bookings</a>
        <a href="manage-locations.php" class="btn btn-primary btn-sm">Manage Locations</a>
        <a href="manage-pricing.php"   class="btn btn-primary btn-sm">Manage Pricing</a>
        <a href="manage-feedback.php"  class="btn btn-primary btn-sm">View Feedback</a>
    </div>

    <!-- ── Recent Bookings ────────────────────────────────────────────────── -->
    <div class="page-title"><h1 style="font-size:1.3rem;">Recent Bookings</h1></div>
    <div class="table-wrap mb-3">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Item</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding:2rem;">No bookings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent as $b): ?>
                        <?php $cls = isset($badge[$b['status']]) ? $badge[$b['status']] : 'badge-pending'; ?>
                        <tr>
                            <td><?php echo (int)$b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['pickup']); ?></td>
                            <td><?php echo htmlspecialchars($b['dropoff']); ?></td>
                            <td><?php echo number_format((float)$b['price']); ?> RWF</td>
                            <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="manage-bookings.php" class="btn btn-primary btn-sm">View All Bookings →</a>

    <!-- ── Newest Users ───────────────────────────────────────────────────── -->
    <div class="page-title mt-3"><h1 style="font-size:1.3rem;">Newest Users</h1></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($new_users)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding:2rem;">No users yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($new_users as $u): ?>
                        <tr>
                            <td><?php echo (int)$u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone']); ?></td>
                            <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="manage-users.php" class="btn btn-primary btn-sm mt-2">View All Users →</a>

</main>

<?php include '../includes/footer.php'; ?>