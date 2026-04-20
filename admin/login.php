<?php
session_start();
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        // Use old array() syntax
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute(array($email));
        $admin = $stmt->fetch();

        if ($admin) {
            // Check password using crypt() if password_hash is not available
            if (crypt($password, $admin['password']) === $admin['password']) {
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Swift Transport</title>
    <!-- Link your CSS file -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!--<style>
        /* Minimal fallback styles */
        body { font-family: Arial, sans-serif; background: #f7f7f7; }
        .form-wrap { max-width: 400px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 10px; width: 100%; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .divider { margin: 20px 0; border: 0; border-top: 1px solid #ccc; }
        .text-center { text-align: center; }
        .text-muted { color: #6c757d; }
    </style>-->
</head>
<body>
    <main>
        <div class="form-wrap">
            <h2>Admin Login</h2>
            <p class="form-sub">Access the Swift Transport admin dashboard.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Your password" required>
                </div>
                <button type="submit" class="btn">Login as Admin</button>
            </form>

            <hr class="divider">
            <p class="text-center text-muted" style="font-size:0.88rem;">Don't have an admin account? <a href="register.php">Register here</a></p>
            <p class="text-center text-muted mt-1" style="font-size:0.88rem;">Are you a client? <a href="../client/login.php">Client Login</a></p>
        </div>
    </main>
</body>
</html>