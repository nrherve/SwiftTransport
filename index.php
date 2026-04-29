<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// 1. Logic Change: Updated stats queries using array() syntax for WAMP
$total_bookings  = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_users     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_delivered = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='delivered'")->fetchColumn();

// Fetch feedback for testimonials
$feedbacks = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 3")->fetchAll();

// 2. Logic Change: Simplified Pricing. No more JOINs because locations are now text
$pricing_list = $pdo->query("SELECT vehicle_name, base_fare, rate_per_km FROM pricing ORDER BY vehicle_name ASC")->fetchAll();
?>

<div class="hero">
    <div class="hero-inner">
        <h1>Move Your Goods<br><span>Fast & Safe</span> Across Kigali</h1>
        <p>Reliable, affordable transportation services connecting all major locations in Kigali. Book in minutes, track in real time.</p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="client/register.php" class="btn btn-accent">Get Started</a>
            <a href="client/booking-form.php" class="btn btn-primary" style="border:2px solid rgba(255,255,255,0.4);background:transparent;">Book a Trip</a>
        </div>
    </div>
</div>

<div class="section" style="background:var(--white);padding:2.5rem 2rem;">
    <div style="max-width:1200px;margin:0 auto;">
        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);max-width:700px;margin:0 auto;">
            <div class="stat-box">
                <div class="stat-num"><?php echo $total_users; ?>+</div>
                <div class="stat-label">Happy Clients</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo $total_bookings; ?>+</div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo $total_delivered; ?>+</div>
                <div class="stat-label">Deliveries Done</div>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <h2 class="section-title">Our Services</h2>
    <p class="section-sub">Everything you need for smooth, reliable goods transport in Kigali</p>
    <div class="card-grid">
        <div class="card">
            <h3>Goods Transport</h3>
            <p>From small parcels to large quantities — we handle all types of cargo with care.</p>
        </div>
        <div class="card">
            <h3>Fast Delivery</h3>
            <p>Same-day delivery across Kigali with our dedicated fleet of vehicles.</p>
        </div>
        <div class="card">
            <h3>Live Tracking</h3>
            <p>Stay updated on your delivery status from booking to doorstep arrival.</p>
        </div>
        <div class="card">
            <h3>Affordable Rates</h3>
            <p>Transparent pricing based on distance. No hidden fees — ever.</p>
        </div>
    </div>
</div>

<div class="section" style="background:var(--white);">
    <h2 class="section-title">Our Pricing</h2>
    <p class="section-sub">Transparent rates based on vehicle type and distance</p>
    <div class="price-table">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle Type</th>
                        <th>Base Fare</th>
                        <th>Rate per KM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($pricing_list)) {
                        foreach ($pricing_list as $p) {
                            echo '<tr>';
                            echo '<td><strong>' . ucfirst(htmlspecialchars($p['vehicle_name'])) . '</strong></td>';
                            echo '<td>' . number_format($p['base_fare']) . ' RWF</td>';
                            echo '<td><strong style="color:var(--primary);">' . number_format($p['rate_per_km']) . ' RWF</strong></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3" class="text-center text-muted">No pricing available yet.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <p class="text-center text-muted mt-2" style="font-size:0.85rem;">💡 Total Price = Base Fare + (Distance in KM × Rate per KM)</p>
    </div>
</div>

<?php if (!empty($feedbacks)): ?>
<div class="section">
    <h2 class="section-title">What Our Clients Say</h2>
    <p class="section-sub">Real feedback from real customers</p>
    <div class="card-grid">
        <?php foreach ($feedbacks as $f): ?>
        <div class="card" style="text-align:left;">
            <div class="stars"><?= str_repeat('★', $f['rating']) . str_repeat('☆', 5 - $f['rating']) ?></div>
            <p style="margin:0.8rem 0;font-size:0.92rem;color:var(--text-muted);">"<?= htmlspecialchars($f['message']) ?>"</p>
            <strong style="font-size:0.88rem;color:var(--primary);">— <?= htmlspecialchars($f['full_name']) ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div style="background:var(--primary);padding:4rem 2rem;text-align:center;">
    <h2 style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--white);margin-bottom:0.8rem;">Ready to Ship Your Goods?</h2>
    <p style="color:rgba(255,255,255,0.7);margin-bottom:1.8rem;">Create your account and book your first transport in under 2 minutes.</p>
    <a href="client/register.php" class="btn btn-accent">Create Free Account</a>
</div>

<?php include 'includes/footer.php'; ?>