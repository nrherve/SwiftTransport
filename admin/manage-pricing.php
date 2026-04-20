<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error   = '';

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM pricing WHERE id = ?");
    $stmt->execute(array($_GET['delete']));
    header("Location: manage-pricing.php");
    exit;
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id    = (int)$_POST['edit_id'];
    $price = (float)$_POST['price'];

    if ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } else {
        $stmt = $pdo->prepare("UPDATE pricing SET price = ? WHERE id = ?");
        $stmt->execute(array($price, $id));
        header("Location: manage-pricing.php");
        exit;
    }
}

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_pricing'])) {
    $pickup_id  = (int)$_POST['pickup_location_id'];
    $dropoff_id = (int)$_POST['dropoff_location_id'];
    $price      = (float)$_POST['price'];

    if (!$pickup_id || !$dropoff_id || $price <= 0) {
        $error = 'All fields are required and price must be greater than 0.';
    } elseif ($pickup_id === $dropoff_id) {
        $error = 'Pickup and drop-off cannot be the same.';
    } else {
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM pricing WHERE pickup_location_id=? AND dropoff_location_id=?");
        $check->execute(array($pickup_id, $dropoff_id));
        if ($check->fetch()) {
            $error = 'A pricing rule for this route already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO pricing (pickup_location_id, dropoff_location_id, price) VALUES (?,?,?)");
            $stmt->execute(array($pickup_id, $dropoff_id, $price));
            header("Location: manage-pricing.php");
            exit;
        }
    }
}

// Editing?
$edit_price = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pricing WHERE id = ?");
    $stmt->execute(array($_GET['edit']));
    $edit_price = $stmt->fetch();
}

// Fetch locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY location_name")->fetchAll();

// Fetch pricing rules
$pricing = $pdo->query("
    SELECT p.*, l1.location_name AS pickup, l2.location_name AS dropoff
    FROM pricing p
    JOIN locations l1 ON p.pickup_location_id = l1.id
    JOIN locations l2 ON p.dropoff_location_id = l2.id
    ORDER BY l1.location_name, l2.location_name
")->fetchAll();

include '../includes/header.php';
?>

<main>
    <div class="page-title">
        <h1>Manage Pricing</h1>
        <p>Set or update transport prices for each route.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem;align-items:start;">

        <!-- Add / Edit Form -->
        <div class="form-wrap" style="margin:0;">
            <h2 style="font-size:1.2rem;">
                <?php echo $edit_price ? 'Edit Price' : 'Add Pricing Rule'; ?>
            </h2>
            <form method="POST" action="">
                <?php if ($edit_price): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_price['id']; ?>">
                    <div class="form-group">
                        <label>Route</label>
                        <?php
                        $pname = '';
                        $dname = '';
                        foreach ($locations as $l) {
                            if ($l['id'] == $edit_price['pickup_location_id'])  $pname = $l['location_name'];
                            if ($l['id'] == $edit_price['dropoff_location_id']) $dname = $l['location_name'];
                        }
                        ?>
                        <input type="text" value="<?php echo htmlspecialchars($pname); ?> → <?php echo htmlspecialchars($dname); ?>" readonly style="background:#f4f6f9;">
                    </div>
                <?php else: ?>
                    <input type="hidden" name="new_pricing" value="1">
                    <div class="form-group">
                        <label>Pickup Location</label>
                        <select name="pickup_location_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['location_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Drop-off Location</label>
                        <select name="dropoff_location_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['location_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Price (RWF)</label>
                    <input type="number" name="price" min="1" step="100" placeholder="e.g. 5000" value="<?php echo isset($edit_price['price']) ? $edit_price['price'] : ''; ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?php echo $edit_price ? 'Update Price' : 'Add Pricing Rule'; ?></button>
                <?php if ($edit_price): ?>
                    <a href="manage-pricing.php" class="btn btn-primary btn-block mt-1">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Pricing Table -->
        <div class="table-wrap" style="margin:0;">
            <table>
                <thead>
                    <tr><th>#</th><th>From</th><th>To</th><th>Price (RWF)</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pricing as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['pickup']); ?></td>
                        <td><?php echo htmlspecialchars($p['dropoff']); ?></td>
                        <td><strong><?php echo number_format($p['price']); ?></strong></td>
                        <td>
                            <a href="manage-pricing.php?edit=<?php echo $p['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="manage-pricing.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this pricing rule?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pricing)): ?>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:2rem;">No pricing rules yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<?php include '../includes/footer.php'; ?>