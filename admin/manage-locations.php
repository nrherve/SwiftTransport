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
    try {
        $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
        $stmt->execute(array($_GET['delete']));
        header("Location: manage-locations.php");
        exit;
    } catch (PDOException $e) {
        $error = 'Cannot delete this location — it is used in existing bookings or pricing.';
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id   = (int)$_POST['edit_id'];
    $name = trim($_POST['location_name']);

    if (!$name) {
        $error = 'Location name is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE locations SET location_name = ? WHERE id = ?");
        $stmt->execute(array($name, $id));
        header("Location: manage-locations.php");
        exit;
    }
}

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_location'])) {
    $name = trim($_POST['location_name']);

    if (!$name) {
        $error = 'Location name is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO locations (location_name) VALUES (?)");
        $stmt->execute(array($name));
        header("Location: manage-locations.php");
        exit;
    }
}

// Editing?
$edit_loc = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute(array($_GET['edit']));
    $edit_loc = $stmt->fetch();
}

// Fetch all locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY location_name")->fetchAll();

include '../includes/header.php';
?>

<main>
    <div class="page-title">
        <h1>Manage Locations</h1>
        <p>Add, edit or remove transport locations.</p>
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
                <?php echo $edit_loc ? 'Edit Location' : 'Add New Location'; ?>
            </h2>

            <form method="POST" action="">
                <?php if ($edit_loc): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_loc['id']; ?>">
                <?php else: ?>
                    <input type="hidden" name="new_location" value="1">
                <?php endif; ?>

                <div class="form-group">
                    <label>Location Name</label>
                    <input type="text" name="location_name"
                           placeholder="e.g. Remera"
                           value="<?php echo isset($edit_loc['location_name']) ? htmlspecialchars($edit_loc['location_name']) : ''; ?>"
                           required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <?php echo $edit_loc ? 'Update Location' : 'Add Location'; ?>
                </button>

                <?php if ($edit_loc): ?>
                    <a href="manage-locations.php" class="btn btn-primary btn-block mt-1">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Locations Table -->
        <div class="table-wrap" style="margin:0;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Location Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $loc): ?>
                    <tr>
                        <td><?php echo $loc['id']; ?></td>
                        <td><?php echo htmlspecialchars($loc['location_name']); ?></td>
                        <td>
                            <a href="manage-locations.php?edit=<?php echo $loc['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="manage-locations.php?delete=<?php echo $loc['id']; ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this location?')">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($locations)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted" style="padding:2rem;">
                            No locations yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<?php include '../includes/footer.php'; ?>