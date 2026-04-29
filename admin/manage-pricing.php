<?php
session_start();
require_once '../config/db.php'; // Corrected path for admin folder

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error   = '';

// 1. Logic: Handle Deleting a Vehicle Rate
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM pricing WHERE id = ?");
    $stmt->execute(array($_GET['delete']));
    header("Location: manage-pricing.php");
    exit;
}

// 2. Logic: Handle Editing an existing Vehicle Rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id     = (int)$_POST['edit_id'];
    $base   = (float)$_POST['base_fare'];
    $per_km = (float)$_POST['rate_per_km'];

    if ($base < 0 || $per_km < 0) {
        $error = 'Rates cannot be negative.';
    } else {
        $stmt = $pdo->prepare("UPDATE pricing SET base_fare = ?, rate_per_km = ? WHERE id = ?");
        $stmt->execute(array($base, $per_km, $id));
        header("Location: manage-pricing.php");
        exit;
    }
}

// 3. Logic: Handle Adding a New Vehicle Rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_pricing'])) {
    $v_name = $_POST['vehicle_name'];
    $base   = (float)$_POST['base_fare'];
    $per_km = (float)$_POST['rate_per_km'];

    if (empty($v_name) || $base < 0 || $per_km < 0) {
        $error = 'All fields are required.';
    } else {
        // Check if vehicle already exists
        $check = $pdo->prepare("SELECT id FROM pricing WHERE vehicle_name = ?");
        $check->execute(array($v_name));
        if ($check->fetch()) {
            $error = 'A pricing rule for this vehicle already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO pricing (vehicle_name, base_fare, rate_per_km) VALUES (?,?,?)");
            $stmt->execute(array($v_name, $base, $per_km));
            header("Location: manage-pricing.php");
            exit;
        }
    }
}

// 4. Logic: Fetch data for the Edit form
$edit_price = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pricing WHERE id = ?");
    $stmt->execute(array($_GET['edit']));
    $edit_price = $stmt->fetch();
}

// 5. Logic: Fetch all pricing rules to show in the table
$pricing = $pdo->query("SELECT * FROM pricing ORDER BY vehicle_name ASC")->fetchAll();

include '../includes/header.php';
?>

<main>
    <div class="page-title">
        <h1>Manage Pricing</h1>
        <p>Set or update transport prices based on vehicle type and distance.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem;align-items:start;">

        <div class="form-wrap" style="margin:0;">
            <h2 style="font-size:1.2rem;">
                <?php echo $edit_price ? 'Edit Vehicle Rate' : 'Add New Vehicle Rate'; ?>
            </h2>
            <form method="POST" action="">
                <?php if ($edit_price): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_price['id']; ?>">
                    <div class="form-group">
                        <label>Vehicle Type</label>
                        <input type="text" name="vehicle_name" value="<?php echo htmlspecialchars($edit_price['vehicle_name']); ?>" readonly style="background:#f4f6f9;">
                    </div>
                <?php else: ?>
                    <input type="hidden" name="new_pricing" value="1">
                    <div class="form-group">
                        <label>Vehicle Name</label>
                        <select name="vehicle_name" required>
                            <option value="">-- Select --</option>
                            <option value="Rifan">Rifan</option>
                            <option value="truck">Truck</option>
                            <option value="van">Delivery Van</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Base Fare (RWF)</label>
                    <input type="number" name="base_fare" min="0" step="100" placeholder="e.g. 500" value="<?php echo $edit_price ? $edit_price['base_fare'] : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Rate Per KM (RWF)</label>
                    <input type="number" name="rate_per_km" min="0" step="10" placeholder="e.g. 300" value="<?php echo $edit_price ? $edit_price['rate_per_km'] : ''; ?>" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <?php echo $edit_price ? 'Update Rates' : 'Save Vehicle Rate'; ?>
                </button>
                
                <?php if ($edit_price): ?>
                    <a href="manage-pricing.php" class="btn btn-primary btn-block mt-1" style="background:#6b7280;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-wrap" style="margin:0;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vehicle</th>
                        <th>Base Fare</th>
                        <th>Rate / KM</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pricing as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><strong><?php echo ucfirst(htmlspecialchars($p['vehicle_name'])); ?></strong></td>
                        <td><?php echo number_format($p['base_fare']); ?> RWF</td>
                        <td><?php echo number_format($p['rate_per_km']); ?> RWF</td>
                        <td>
                            <a href="manage-pricing.php?edit=<?php echo $p['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="manage-pricing.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this rate?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pricing)): ?>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:2rem;">No rates set. Add one on the left.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<?php include '../includes/footer.php'; ?>