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

// Fetch Vehicle Rates
$rates_stmt = $pdo->query("SELECT vehicle_name, base_fare, rate_per_km FROM pricing");
$rates = array();
while ($r = $rates_stmt->fetch()) {
    $rates[$r['vehicle_name']] = array(
        'base' => $r['base_fare'],
        'rate' => $r['rate_per_km']
    );
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute(array($_GET['edit'], $user_id));
    $booking = $stmt->fetch();
    if ($booking) $editing = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name    = trim($_POST['item_name']);
    $qty_weight   = trim($_POST['quantity_weight']);
    $v_type       = $_POST['vehicle_type']; 
    $pickup       = trim($_POST['pickup_location']); 
    $dropoff      = trim($_POST['dropoff_location']); 
    $dist         = (float)$_POST['distance_km']; 
    $edit_id      = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if (!$item_name || !$qty_weight || !$pickup || !$dropoff || $dist <= 0) {
        $error = 'Please select locations from the suggestions to calculate price.';
    } else {
        $price = $rates[$v_type]['base'] + ($dist * $rates[$v_type]['rate']);

        if ($edit_id) {
            $sql = "UPDATE bookings SET item_name=?, quantity_weight=?, vehicle_type=?, distance_km=?, pickup_location_id=?, dropoff_location_id=?, price=? WHERE id=? AND user_id=? AND status='pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($item_name, $qty_weight, $v_type, $dist, $pickup, $dropoff, $price, $edit_id, $user_id));
            $success = 'Booking updated successfully!';
        } else {
            $sql = "INSERT INTO bookings (user_id, item_name, quantity_weight, vehicle_type, distance_km, pickup_location_id, dropoff_location_id, price) VALUES (?,?,?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($user_id, $item_name, $qty_weight, $v_type, $dist, $pickup, $dropoff, $price));
            $success = 'Booking submitted successfully!';
        }
        $editing = false;
        $booking = null;
    }
}

include '../includes/header.php';
?>

<style>
    .autocomplete-container { position: relative; }
    .autocomplete-items {
        position: absolute;
        border: 1px solid #d4d4d4;
        border-top: none;
        z-index: 99;
        top: 100%;
        left: 0;
        right: 0;
        background-color: #fff;
        max-height: 220px;
        overflow-y: auto;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .autocomplete-item {
        padding: 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f1f1;
        font-size: 0.9rem;
    }
    .autocomplete-item:hover { background-color: #f8fafc; }
    .autocomplete-item strong { color: #2563eb; }
</style>

<main>
    <div class="form-wrap" style="max-width:580px;">
        <h2><?php echo $editing ? 'Edit Booking' : 'Book a Transport'; ?></h2>
        
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="my-bookings.php">View my bookings →</a></div><?php endif; ?>

        <form method="POST" action="" id="bookingForm">
            <?php if ($editing): ?><input type="hidden" name="edit_id" value="<?php echo $booking['id']; ?>"><?php endif; ?>

            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" value="<?php echo $editing ? htmlspecialchars($booking['item_name']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Quantity / Weight</label>
                <input type="text" name="quantity_weight" value="<?php echo $editing ? htmlspecialchars($booking['quantity_weight']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Vehicle Type</label>
                <select name="vehicle_type" id="vehicle_type" required>
                    <?php foreach($rates as $name => $vals): ?>
                        <option value="<?php echo $name; ?>" <?php if($editing && $booking['vehicle_type'] == $name) echo 'selected'; ?>>
                            <?php echo ucfirst($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group autocomplete-container">
                    <label>Pickup Location</label>
                    <input type="text" name="pickup_location" id="pickup_input" placeholder="e.g. Kimironko Market" autocomplete="off" value="<?php echo $editing ? htmlspecialchars($booking['pickup_location_id']) : ''; ?>" required>
                    <div id="pickup-list" class="autocomplete-items"></div>
                </div>

                <div class="form-group autocomplete-container">
                    <label>Drop-off Location</label>
                    <input type="text" name="dropoff_location" id="dropoff_input" placeholder="e.g. Kabuga" autocomplete="off" value="<?php echo $editing ? htmlspecialchars($booking['dropoff_location_id']) : ''; ?>" required>
                    <div id="dropoff-list" class="autocomplete-items"></div>
                </div>
            </div>

            <input type="hidden" name="distance_km" id="distance_km" value="<?php echo $editing ? $booking['distance_km'] : ''; ?>">

            <div id="price_display" class="alert alert-info" style="font-weight:bold; text-align:center; background:#f0f9ff; border:1px solid #bae6fd;">
                Distance: <span id="dist_val"><?php echo $editing ? $booking['distance_km'] : '0'; ?></span> km | 
                Price: <span id="price_val"><?php echo $editing ? number_format($booking['price']) : '0'; ?></span> RWF
            </div>

            <button type="submit" class="btn btn-primary btn-block">Confirm Booking</button>
        </form>
    </div>
</main>

<script>
const pricingData = <?php echo json_encode($rates); ?>;
let debounceTimer;

async function setupAutocomplete(inputId, listId) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const val = this.value;
        
        if (val.length < 3) {
            list.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const resp = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(val)}&limit=5&lat=-1.94&lon=30.06`);
                const data = await resp.json();

                list.innerHTML = '';
                data.features.forEach(feature => {
                    const p = feature.properties;
                    const label = `${p.name || ''}, ${p.city || p.state || 'Rwanda'}`;
                    
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.innerHTML = `<strong>${p.name || ''}</strong><br><small>${p.city || p.state || 'Rwanda'}</small>`;
                    
                    item.onclick = () => {
                        input.value = label;
                        list.innerHTML = '';
                        calculateRoute(); 
                    };
                    list.appendChild(item);
                });
            } catch (e) { console.error("Search error", e); }
        }, 300);
    });

    document.addEventListener('click', (e) => { if (e.target !== input) list.innerHTML = ''; });
}

async function calculateRoute() {
    const origin = document.getElementById('pickup_input').value;
    const dest = document.getElementById('dropoff_input').value;
    const vehicle = document.getElementById('vehicle_type').value;

    if (origin.length > 3 && dest.length > 3) {
        document.getElementById('dist_val').innerText = "Calculating...";
        
        const [start, end] = await Promise.all([getCoords(origin), getCoords(dest)]);

        if (start && end) {
            try {
                const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${start.lon},${start.lat};${end.lon},${end.lat}?overview=false`;
                const response = await fetch(osrmUrl);
                const data = await response.json();

                if (data.routes && data.routes.length > 0) {
                    const km = data.routes[0].distance / 1000;
                    document.getElementById('distance_km').value = km.toFixed(2);
                    document.getElementById('dist_val').innerText = km.toFixed(2);
                    
                    const base = parseFloat(pricingData[vehicle].base);
                    const rate = parseFloat(pricingData[vehicle].rate);
                    const total = base + (km * rate);
                    document.getElementById('price_val').innerText = Math.round(total).toLocaleString();
                }
            } catch (e) { console.error("Route error", e); }
        }
    }
}

async function getCoords(addr) {
    try {
        const resp = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(addr)}`);
        const data = await resp.json();
        return data.length > 0 ? { lat: data[0].lat, lon: data[0].lon } : null;
    } catch (e) { return null; }
}

setupAutocomplete('pickup_input', 'pickup-list');
setupAutocomplete('dropoff_input', 'dropoff-list');
document.getElementById('vehicle_type').addEventListener('change', calculateRoute);
</script>

<?php include '../includes/footer.php'; ?>