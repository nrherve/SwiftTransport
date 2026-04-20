<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
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
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(array($email));
        $user = $stmt->fetch();

        // Use md5 for old PHP compatibility (matches registration)
        if ($user && md5($password) === $user['password']) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

include '../includes/header.php';
?>

<main>
    <div class="form-wrap">
        <h2>Client Login</h2>
        <p class="form-sub">Welcome back! Log in to manage your bookings.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-2">Login</button>
        </form>

        <hr class="divider">
        <p class="text-center text-muted" style="font-size:0.88rem;">Don't have an account? <a href="register.php">Register here</a></p>
        <p class="text-center text-muted mt-1" style="font-size:0.88rem;">Are you an admin? <a href="../admin/login.php">Admin Login</a></p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>