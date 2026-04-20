<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=booking-form.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error   = '';
$success = '';
$editing = false;
$booking = null;

// Fetch locations
$locations = $pdo->query("SELECT * FROM locations ORDER BY location_name")->fetchAll();

// Fetch all pricing into a lookup array
$prices_raw = $pdo->query("SELECT pickup_location_id, dropoff_location_id, price FROM pricing")->fetchAll();
$price_map  = array();
foreach ($prices_raw as $pr) {
    $price_map[$pr['pickup_location_id']][$pr['dropoff_location_id']] = $pr['price'];
}

// Edit mode
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute(array($_GET['edit'], $user_id));
    $booking = $stmt->fetch();
    if ($booking) $editing = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name    = trim($_POST['item_name']);
    $qty_weight   = trim($_POST['quantity_weight']);
    $pickup_id    = (int)$_POST['pickup_location_id'];
    $dropoff_id   = (int)$_POST['dropoff_location_id'];
    $edit_id      = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if (!$item_name || !$qty_weight || !$pickup_id || !$dropoff_id) {
        $error = 'All fields are required.';
    } elseif ($pickup_id === $dropoff_id) {
        $error = 'Pickup and drop-off locations cannot be the same.';
    } elseif (!isset($price_map[$pickup_id][$dropoff_id])) {
        $error = 'No pricing available for this route. Please contact us.';
    } else {
        $price = $price_map[$pickup_id][$dropoff_id];

        if ($edit_id) {
            // Update existing booking
            $stmt = $pdo->prepare("UPDATE bookings SET item_name=?, quantity_weight=?, pickup_location_id=?, dropoff_location_id=?, price=? WHERE id=? AND user_id=? AND status='pending'");
            $stmt->execute(array($item_name, $qty_weight, $pickup_id, $dropoff_id, $price, $edit_id, $user_id));
            $success = 'Booking updated successfully!';
        } else {
            // New booking
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, item_name, quantity_weight, pickup_location_id, dropoff_location_id, price) VALUES (?,?,?,?,?,?)");
            $stmt->execute(array($user_id, $item_name, $qty_weight, $pickup_id, $dropoff_id, $price));
            $success = 'Booking submitted successfully! We will confirm shortly.';
        }
        $editing = false;
        $booking = null;
    }
}

include '../includes/header.php';
?>

<main>
    <div class="form-wrap" style="max-width:580px;">
        <h2><?php echo $editing ? 'Edit Booking' : 'Book a Transport'; ?></h2>
        <p class="form-sub"><?php echo $editing ? 'Update your pending booking details below.' : 'Fill in your item details and choose your route.'; ?></p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="my-bookings.php">View my bookings →</a></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php if ($editing): ?>
                <input type="hidden" name="edit_id" value="<?php echo $booking['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" placeholder="e.g. Furniture, Electronics, Bags..."
                       value="<?php
                       if ($editing) {
                           echo htmlspecialchars($booking['item_name']);
                       } elseif (isset($_POST['item_name'])) {
                           echo htmlspecialchars($_POST['item_name']);
                       } else {
                           echo '';
                       }
                       ?>" required>
            </div>

            <div class="form-group">
                <label>Quantity / Weight</label>
                <input type="text" name="quantity_weight" placeholder="e.g. 5 boxes, 200kg, 3 units"
                       value="<?php
                       if ($editing) {
                           echo htmlspecialchars($booking['quantity_weight']);
                       } elseif (isset($_POST['quantity_weight'])) {
                           echo htmlspecialchars($_POST['quantity_weight']);
                       } else {
                           echo '';
                       }
                       ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Pickup Location</label>
                    <select name="pickup_location_id" id="pickup" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"
                                <?php
                                $selected = false;
                                if ($editing && $booking['pickup_location_id'] == $loc['id']) $selected = true;
                                elseif (isset($_POST['pickup_location_id']) && $_POST['pickup_location_id'] == $loc['id']) $selected = true;
                                echo $selected ? 'selected' : '';
                                ?>>
                                <?php echo htmlspecialchars($loc['location_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Drop-off Location</label>
                    <select name="dropoff_location_id" id="dropoff" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"
                                <?php
                                $selected = false;
                                if ($editing && $booking['dropoff_location_id'] == $loc['id']) $selected = true;
                                elseif (isset($_POST['dropoff_location_id']) && $_POST['dropoff_location_id'] == $loc['id']) $selected = true;
                                echo $selected ? 'selected' : '';
                                ?>>
                                <?php echo htmlspecialchars($loc['location_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="alert alert-info" style="font-size:0.88rem;">
                After selecting your route, the price will be shown upon submission based on our fixed pricing table.
                <br><a href="../index.php#pricing" style="font-size:0.85rem;">View pricing table →</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <?php echo $editing ? ' Update Booking' : 'Submit Booking Request'; ?>
            </button>
        </form>

        <?php if ($editing): ?>
            <hr class="divider">
            <a href="my-bookings.php?delete=<?php echo $booking['id']; ?>" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to remove this booking?')">Remove This Booking</a>
        <?php endif; ?>

        <hr class="divider">
        <p class="text-center text-muted" style="font-size:0.88rem;"><a href="my-bookings.php">← Back to My Bookings</a></p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>