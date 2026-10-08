<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pdo = getDBConnection();
$error = '';
$success = '';

// Fetch Phases and Blocks
$phases = $pdo->query("SELECT * FROM phases ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$blocks = $pdo->query("SELECT * FROM blocks ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Session security token expired. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $plot_number = trim($_POST['plot_number'] ?? '');
        $phase_id = intval($_POST['phase_id'] ?? 1);
        $block_id = intval($_POST['block_id'] ?? 0);
        $street = trim($_POST['street'] ?? '');
        $property_type = $_POST['property_type'] ?? 'Residential Plot';
        $plot_size = trim($_POST['plot_size'] ?? '1 Kanal');
        $price = floatval($_POST['price'] ?? 0);
        $status = $_POST['status'] ?? 'Available';
        $description = trim($_POST['description'] ?? '');
        $dealer_name = trim($_POST['owner_dealer_name'] ?? 'Authorized DHA Multan Realtor');
        $phone = trim($_POST['phone'] ?? '+92 300 1234567');
        $whatsapp = trim($_POST['whatsapp'] ?? '923001234567');
        $latitude = floatval($_POST['latitude'] ?? 30.2982);
        $longitude = floatval($_POST['longitude'] ?? 71.5512);
        $polygon_coordinates = !empty($_POST['polygon_coordinates']) ? $_POST['polygon_coordinates'] : null;

        if (empty($title) || empty($plot_number) || $block_id <= 0 || $price <= 0) {
            $error = 'Please fill in Title, Plot Number, Sector/Block, and a valid Price.';
        } else {
            // Handle uploaded images
            $uploadedImages = [];
            $featuredImage = null;

            if (!empty($_FILES['images']['name'][0])) {
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $uploadDir = __DIR__ . '/../uploads/properties/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($_FILES['images']['name'] as $key => $name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['images']['tmp_name'][$key];
                        $size = $_FILES['images']['size'][$key];
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmpName);
                        finfo_close($finfo);

                        if (in_array($mime, $allowedMime) && $size <= 8 * 1024 * 1024) {
                            $ext = pathinfo($name, PATHINFO_EXTENSION);
                            $safeFilename = 'plot_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                            $destination = $uploadDir . $safeFilename;
                            if (move_uploaded_file($tmpName, $destination)) {
                                $relPath = 'uploads/properties/' . $safeFilename;
                                $uploadedImages[] = $relPath;
                                if ($featuredImage === null) {
                                    $featuredImage = $relPath;
                                }
                            }
                        }
                    }
                }
            }

            if ($featuredImage === null) {
                $featuredImage = 'uploads/properties/plot-102.svg';
            }

            // Insert property record
            $sql = "INSERT INTO properties (
                title, plot_number, phase_id, block_id, street,
                property_type, plot_size, price, status,
                description, latitude, longitude, polygon_coordinates,
                featured_image, owner_dealer_name, phone, whatsapp
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $title, $plot_number, $phase_id, $block_id, $street,
                $property_type, $plot_size, $price, $status,
                $description, $latitude, $longitude, $polygon_coordinates,
                $featuredImage, $dealer_name, $phone, $whatsapp
            ]);

            $propertyId = $pdo->lastInsertId();

            // Insert gallery images
            if (!empty($uploadedImages)) {
                $imgStmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_cover) VALUES (?, ?, ?)");
                foreach ($uploadedImages as $idx => $path) {
                    $imgStmt->execute([$propertyId, $path, $idx === 0 ? 1 : 0]);
                }
            }

            header('Location: /admin/dashboard.php?msg=added');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Property | DHA Multan Admin</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">

  <header class="admin-header">
    <div class="admin-header-container">
      <a href="/admin/dashboard.php" class="admin-brand">
        <span class="logo-shield">DHA</span>
        <span class="logo-text">MULTAN <em>ADMIN</em></span>
      </a>
      <div class="admin-nav-links">
        <a href="/admin/dashboard.php" class="nav-btn btn-secondary">← Back to Dashboard</a>
      </div>
    </div>
  </header>

  <main class="admin-container">
    <div class="page-title-section">
      <h1 class="page-title">Add Property to DHA Multan Map</h1>
      <p class="page-subtitle">Input property specifications and place a marker or draw exact plot polygon boundaries on the Leaflet map.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/add-property.php" enctype="multipart/form-data" class="property-form-grid">
      <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">

      <!-- Left Column: Form Fields -->
      <div class="form-card">
        <h3>1. Property Details</h3>

        <div class="form-group">
          <label for="title">Listing Title *</label>
          <input type="text" id="title" name="title" required class="form-control" placeholder="e.g. 1 Kanal Corner Plot on 80ft Road" value="<?php echo e($_POST['title'] ?? ''); ?>">
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="plot_number">Plot / House # *</label>
            <input type="text" id="plot_number" name="plot_number" required class="form-control" placeholder="e.g. Plot 102" value="<?php echo e($_POST['plot_number'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="property_type">Property Type *</label>
            <select id="property_type" name="property_type" class="form-control">
              <option value="Residential Plot">Residential Plot</option>
              <option value="Commercial Plot">Commercial Plot</option>
              <option value="House">House</option>
              <option value="Villa">Villa</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="phase_id">DHA Phase *</label>
            <select id="phase_id" name="phase_id" class="form-control">
              <?php foreach ($phases as $ph): ?>
                <option value="<?php echo $ph['id']; ?>"><?php echo e($ph['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="block_id">Sector / Block *</label>
            <select id="block_id" name="block_id" required class="form-control">
              <option value="">-- Select Sector / Block --</option>
              <?php foreach ($blocks as $b): ?>
                <option value="<?php echo $b['id']; ?>"><?php echo e($b['name']); ?> (<?php echo e($b['sector_type']); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="street">Street / Boulevard</label>
            <input type="text" id="street" name="street" class="form-control" placeholder="e.g. Street 4" value="<?php echo e($_POST['street'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="plot_size">Plot Size *</label>
            <select id="plot_size" name="plot_size" class="form-control">
              <option value="5 Marla">5 Marla</option>
              <option value="8 Marla">8 Marla</option>
              <option value="10 Marla">10 Marla</option>
              <option value="1 Kanal" selected>1 Kanal</option>
              <option value="2 Kanal">2 Kanal</option>
              <option value="4 Marla Commercial">4 Marla Commercial</option>
              <option value="8 Marla Commercial">8 Marla Commercial</option>
            </select>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="price">Price in PKR (e.g. 25000000) *</label>
            <input type="number" step="10000" id="price" name="price" required class="form-control" placeholder="25000000" value="<?php echo e($_POST['price'] ?? ''); ?>">
            <small class="text-muted">Will display formatted as PKR Crore / Lac automatically.</small>
          </div>
          <div class="form-group">
            <label for="status">Sale Status *</label>
            <select id="status" name="status" class="form-control">
              <option value="Available">🟢 Available / For Sale</option>
              <option value="Reserved">🟠 Reserved</option>
              <option value="Hold">🔵 Hold</option>
              <option value="Sold">🔴 Sold</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="description">Full Description</label>
          <textarea id="description" name="description" rows="4" class="form-control" placeholder="Describe key features, possession status, proximity to parks, mosques, and commercial hubs..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
        </div>

        <h3 class="mt-4">2. Realtor &amp; Contact Info</h3>
        <div class="form-group">
          <label for="owner_dealer_name">Dealer / Realtor Name</label>
          <input type="text" id="owner_dealer_name" name="owner_dealer_name" class="form-control" placeholder="e.g. DHA Multan Properties" value="<?php echo e($_POST['owner_dealer_name'] ?? 'Authorized DHA Multan Realtor'); ?>">
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="phone">Phone Number (Call Now) *</label>
            <input type="text" id="phone" name="phone" class="form-control" placeholder="+92 300 1234567" value="<?php echo e($_POST['phone'] ?? '+92 300 1234567'); ?>">
          </div>
          <div class="form-group">
            <label for="whatsapp">WhatsApp Number *</label>
            <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="923001234567" value="<?php echo e($_POST['whatsapp'] ?? '923001234567'); ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="images">Property Photos (Multiple Allowed)</label>
          <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control">
          <small class="text-muted">Supported formats: JPG, PNG, WEBP (Max 8MB each).</small>
        </div>
      </div>

      <!-- Right Column: Interactive Leaflet Map -->
      <div class="form-card">
        <div class="admin-map-header">
          <div>
            <h3>3. Plot Map Positioning</h3>
            <p class="text-muted text-sm">Select Sector above to fly directly to that block, then place a marker or draw boundary corners.</p>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="resetToDHACenter()">Reset to DHA Center</button>
        </div>

        <!-- Tool Mode Switcher -->
        <div class="map-tool-bar">
          <button type="button" id="modeMarkerBtn" class="btn btn-tool active" onclick="setMode('marker')">
            📍 Place Point Marker
          </button>
          <button type="button" id="modePolyBtn" class="btn btn-tool" onclick="setMode('polygon')">
            📐 Draw Plot Boundary (Polygon)
          </button>
          <button type="button" class="btn btn-tool-danger" onclick="clearPolygon()">
            🗑 Clear Polygon (<span id="polyPointsCount">0 points</span>)
          </button>
        </div>

        <div class="map-instructions" id="mapInstructions">
          👉 <strong>Marker Mode:</strong> Click anywhere on the map to place or drag the plot location marker.
        </div>

        <!-- Leaflet Map Container -->
        <div id="adminMapContainer" class="admin-map-frame"></div>

        <!-- Coordinates Hidden / Read-only Fields -->
        <div class="coords-box mt-3">
          <div class="form-row-2">
            <div class="form-group">
              <label for="latitude">Latitude</label>
              <input type="text" id="latitude" name="latitude" readonly class="form-control bg-light" value="30.298200">
            </div>
            <div class="form-group">
              <label for="longitude">Longitude</label>
              <input type="text" id="longitude" name="longitude" readonly class="form-control bg-light" value="71.551200">
            </div>
          </div>
          <div class="form-group">
            <label for="polygon_coordinates">Polygon Coordinates (JSON)</label>
            <input type="text" id="polygon_coordinates" name="polygon_coordinates" readonly class="form-control bg-light text-xs" placeholder="Auto-populated when drawing boundary corners">
          </div>
        </div>

        <div class="form-actions mt-4">
          <button type="submit" class="btn btn-primary btn-block btn-lg">💾 Save Property to DHA Multan Map</button>
        </div>
      </div>
    </form>
  </main>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>window.L || document.write('<script src="/assets/vendor/leaflet/leaflet.js"><\/script>')</script>
  <script src="/admin/js/admin-map.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      initAdminMap(30.2982, 71.5512, null);
    });
  </script>
</body>
</html>
