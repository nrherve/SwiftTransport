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
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute(array($_GET['delete']));
    $success = 'User deleted successfully.';
}

// Handle edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $id        = (int)$_POST['edit_user_id'];
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);

    if (!$full_name || !$email || !$phone) {
        $error = 'All fields are required.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->execute(array($full_name, $email, $phone, $id));
        $success = 'User updated successfully.';
    }
}

// Fetch editing user
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute(array($_GET['edit']));
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all users
$users = $pdo->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS total_bookings 
    FROM users u 
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<main>
    <div class="page-title">
        <h1>Manage Users</h1>
        <p>View, edit, or delete client accounts.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Edit Form -->
    <?php if ($edit_user): ?>
    <div class="form-wrap" style="max-width:600px;margin-bottom:2rem;">
        <h2 style="font-size:1.2rem;">Edit User #<?php echo $edit_user['id']; ?></h2>
        <form method="POST" action="">
            <input type="hidden" name="edit_user_id" value="<?php echo $edit_user['id']; ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($edit_user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_user['phone']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
            </div>

            <div style="display:flex;gap:0.8rem;">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="manage-users.php" class="btn btn-primary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Bookings</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['phone']); ?></td>
                    <td><span class="badge badge-confirmed"><?php echo $u['total_bookings']; ?></span></td>
                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <a href="manage-users.php?edit=<?php echo $u['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="manage-users.php?delete=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user and all their data?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding:2rem;">
                        No users registered yet.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>