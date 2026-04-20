<?php
session_start();
require_once '../config/db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if (!$full_name || !$email || !$phone || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
        $stmt->execute(array($email));
        if ($stmt->fetch()) {
            $error = 'An admin account with this email already exists.';
        } else {
            // Generate a salt for crypt() in a PHP 5-compatible way
            $salt_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./';
            $salt = '';
            for ($i = 0; $i < 22; $i++) {
                $salt .= $salt_chars[mt_rand(0, 63)];
            }
            $hashed = crypt($password, '$2y$10$' . $salt);

            $stmt = $pdo->prepare("INSERT INTO admin (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($full_name, $email, $hashed, $phone));
            $success = 'Admin account created! You can now log in.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration - Swift Transport</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main>
        <div class="form-wrap">
            <h2>Admin Registration</h2>
            <p class="form-sub">Create an admin account to manage the Swift Transport system.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?> <a href="login.php">Login here</a></div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="Admin full name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="+250 7XX XXX XXX" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                </div>
                <button type="submit" class="btn">Create Admin Account</button>
            </form>
            <?php endif; ?>

            <hr class="divider">
            <p class="text-center text-muted" style="font-size:0.88rem;">Already an admin? <a href="login.php">Login here</a></p>
        </div>
    </main>
</body>
</html>