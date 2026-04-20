<?php
$current = basename($_SERVER['PHP_SELF']);
$folder  = basename(dirname($_SERVER['PHP_SELF']));

$is_client = isset($_SESSION['user_id']);
$is_admin  = isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnPoint Transport Rwanda</title>

    <!-- FIXED CSS PATH -->
    <link rel="stylesheet" href="/onpoint/assets/css/style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <a href="/onpoint/index.php" class="logo">
            <div class="logo-icon"></div>
            <div class="logo-text">Swift<span>Transport</span></div>
        </a>

        <nav>
            <?php if ($is_admin): ?>
                <a href="/onpoint/admin/dashboard.php">Dashboard</a>
                <a href="/onpoint/admin/manage-users.php">Users</a>
                <a href="/onpoint/admin/manage-bookings.php">Bookings</a>
                <a href="/onpoint/admin/manage-locations.php">Locations</a>
                <a href="/onpoint/admin/manage-pricing.php">Pricing</a>
                <a href="/onpoint/admin/manage-feedback.php">Feedback</a>
                <a href="/onpoint/admin/logout.php" class="btn-nav">Logout</a>

            <?php elseif ($is_client): ?>
                <a href="/onpoint/index.php">Home</a>
                <a href="/onpoint/client/dashboard.php">Dashboard</a>
                <a href="/onpoint/client/booking-form.php">Book Now</a>
                <a href="/onpoint/client/my-bookings.php">My Bookings</a>
                <a href="/onpoint/client/feedback.php">Feedback</a>
                <a href="/onpoint/client/logout.php" class="btn-nav">Logout</a>

            <?php else: ?>
                <a href="/onpoint/index.php" <?= $current == 'index.php' ? 'class="active"' : '' ?>Home</a>
                <a href="/onpoint/client/login.php">Login</a>
                <a href="/onpoint/client/register.php">Register</a>
             
                <a href="/onpoint/client/booking-form.php" class="btn-nav">Book Now</a>
            <?php endif; ?>
        </nav>
    </div>
</header>