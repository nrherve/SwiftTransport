<?php
session_start();
require_once '../config/db.php';

$error = '';
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
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(array($email));

        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // OLD PHP COMPATIBLE HASH
            $hashed = md5($password);

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($full_name, $email, $hashed, $phone));

            $success = 'Account created successfully! You can now log in.';
        }
    }
}

include '../includes/header.php';
?>

<main>
    <div class="form-wrap">
        <h2>Create Account</h2>
        <p class="form-sub">Join Swift Transport and book your first delivery today.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?> <a href="login.php">Login here</a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Your full name"
                    value="<?php echo htmlspecialchars(isset($_POST['full_name']) ? $_POST['full_name'] : ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com"
                    value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="+250 7XX XXX XXX"
                    value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>" required>
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

            <button type="submit" class="btn btn-primary btn-block mt-2">Create Account</button>
        </form>
        <?php endif; ?>

        <hr class="divider">
        <p class="text-center text-muted" style="font-size:0.88rem;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>