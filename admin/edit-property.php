<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pdo = getDBConnection();
$error = '';
$success = '';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Fetch property
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Fetch Phases and Blocks
$phases = $pdo->query("SELECT * FROM phases ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$blocks = $pdo->query("SELECT * FROM blocks ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing images
$imgStmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
$imgStmt->execute([$id]);
$propertyImages = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

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
        $latitude = floatval($_POST['latitude'] ?? $property['latitude']);
        $longitude = floatval($_POST['longitude'] ?? $property['longitude']);
        $polygon_coordinates = !empty($_POST['polygon_coordinates']) ? $_POST['polygon_coordinates'] : null;

        if (empty($title) || empty($plot_number) || $block_id <= 0 || $price <= 0) {
            $error = 'Please fill in Title, Plot Number, Sector/Block, and a valid Price.';
        } else {
            // Handle uploaded images if any
            $featuredImage = $property['featured_image'];

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
                                $imgStmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_cover) VALUES (?, ?, 0)");
                                $imgStmt->execute([$id, $relPath]);
                                if (empty($featuredImage)) {
                                    $featuredImage = $relPath;
                                }
                            }
                        }
                    }
                }
            }

            // Update property record
            $sql = "UPDATE properties SET 
                title = ?, plot_number = ?, phase_id = ?, block_id = ?, street = ?,
                property_type = ?, plot_size = ?, price = ?, status = ?,
                description = ?, latitude = ?, longitude = ?, polygon_coordinates = ?,
                featured_image = ?, owner_dealer_name = ?, phone = ?, whatsapp = ?
                WHERE id = ?";

            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute([
                $title, $plot_number, $phase_id, $block_id, $street,
                $property_type, $plot_size, $price, $status,
                $description, $latitude, $longitude, $polygon_coordinates,
                $featuredImage, $dealer_name, $phone, $whatsapp,
                $id
            ]);

            header('Location: /admin/dashboard.php?msg=updated');
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
  <title>Edit Property: <?php echo e($property['plot_number']); ?> | DHA Multan Admin</title>
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
        <a href="/property.php?id=<?php echo $property['id']; ?>" target="_blank" class="nav-btn btn-secondary">👁 View Public Page</a>
        <a href="/admin/dashboard.php" class="nav-btn btn-secondary">← Back to Dashboard</a>
      </div>
    </div>
  </header>

  <main class="admin-container">
    <div class="page-title-section">
      <h1 class="page-title">Edit Property: <?php echo e($property['plot_number']); ?></h1>
      <p class="page-subtitle">Update pricing, sale status, contact info, or adjust boundary vertices on the Leaflet map.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/edit-property.php?id=<?php echo $property['id']; ?>" enctype="multipart/form-data" class="property-form-grid">
      <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">

      <!-- Left Column: Form Fields -->
      <div class="form-card">
        <h3>1. Property Details</h3>

        <div class="form-group">
          <label for="title">Listing Title *</label>
          <input type="text" id="title" name="title" required class="form-control" value="<?php echo e($property['title']); ?>">
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="plot_number">Plot / House # *</label>
            <input type="text" id="plot_number" name="plot_number" required class="form-control" value="<?php echo e($property['plot_number']); ?>">
          </div>
          <div class="form-group">
            <label for="property_type">Property Type *</label>
            <select id="property_type" name="property_type" class="form-control">
              <?php foreach (['Residential Plot', 'Commercial Plot', 'House', 'Villa', 'Other'] as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $property['property_type'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="phase_id">DHA Phase *</label>
            <select id="phase_id" name="phase_id" class="form-control">
              <?php foreach ($phases as $ph): ?>
                <option value="<?php echo $ph['id']; ?>" <?php echo $property['phase_id'] == $ph['id'] ? 'selected' : ''; ?>><?php echo e($ph['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="block_id">Sector / Block *</label>
            <select id="block_id" name="block_id" required class="form-control">
              <?php foreach ($blocks as $b): ?>
                <option value="<?php echo $b['id']; ?>" <?php echo $property['block_id'] == $b['id'] ? 'selected' : ''; ?>>
                  <?php echo e($b['name']); ?> (<?php echo e($b['sector_type']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="street">Street / Boulevard</label>
            <input type="text" id="street" name="street" class="form-control" value="<?php echo e($property['street']); ?>">
          </div>
          <div class="form-group">
            <label for="plot_size">Plot Size *</label>
            <select id="plot_size" name="plot_size" class="form-control">
              <?php foreach (['5 Marla', '8 Marla', '10 Marla', '1 Kanal', '2 Kanal', '4 Marla Commercial', '8 Marla Commercial'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $property['plot_size'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label for="price">Price in PKR (e.g. 25000000) *</label>
            <input type="number" step="10000" id="price" name="price" required class="form-control" value="<?php echo $property['price']; ?>">
            <small class="text-muted">Displays as: <?php echo e(formatPKR($property['price'])); ?></small>
          </div>
          <div class="form-group">
            <label for="status">Sale Status *</label>
            <select id="status" name="status" class="form-control">
              <option value="Available" <?php echo $property['status'] === 'Available' ? 'selected' : ''; ?>>🟢 Available / For Sale</option>
              <option value="Reserved" <?php echo $property['status'] === 'Reserved' ? 'selected' : ''; ?>>🟠 Reserved</option>
              <option value="Hold" <?php echo $property['status'] === 'Hold' ? 'selected' : ''; ?>>🔵 Hold</option>
              <option value="Sold" <?php echo $property['status'] === 'Sold' ? 'selected' : ''; ?>>🔴 Sold</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="description">Full Description</label>
          <textarea id="description" name="description" rows="4" class="form-control"><?php echo e($property['description']); ?></textarea>
        </div>

        <h3 class="mt-4">2. Realtor &amp; Contact Info</h3>
        <div class="form-group">
          <label for="owner_dealer_name">Dealer / Realtor Name</label>
          <input type="text" id="owner_dealer_name" name="owner_dealer_name" class="form-control" value="<?php echo e($property['owner_dealer_name']); ?>">
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="phone">Phone Number (Call Now) *</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo e($property['phone']); ?>">
          </div>
          <div class="form-group">
            <label for="whatsapp">WhatsApp Number *</label>
            <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?php echo e($property['whatsapp']); ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="images">Add More Photos</label>
          <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control">
        </div>
      </div>

      <!-- Right Column: Interactive Leaflet Map -->
      <div class="form-card">
        <div class="admin-map-header">
          <div>
            <h3>3. Plot Map Positioning</h3>
            <p class="text-muted text-sm">Adjust marker location or redraft polygon corners directly on the map.</p>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="resetToDHACenter()">Reset to DHA Center</button>
        </div>

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
          👉 Click to move marker, or switch to Polygon Mode to outline plot boundaries.
        </div>

        <div id="adminMapContainer" class="admin-map-frame"></div>

        <div class="coords-box mt-3">
          <div class="form-row-2">
            <div class="form-group">
              <label for="latitude">Latitude</label>
              <input type="text" id="latitude" name="latitude" readonly class="form-control bg-light" value="<?php echo e($property['latitude']); ?>">
            </div>
            <div class="form-group">
              <label for="longitude">Longitude</label>
              <input type="text" id="longitude" name="longitude" readonly class="form-control bg-light" value="<?php echo e($property['longitude']); ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="polygon_coordinates">Polygon Coordinates (JSON)</label>
            <input type="text" id="polygon_coordinates" name="polygon_coordinates" readonly class="form-control bg-light text-xs" value="<?php echo e($property['polygon_coordinates']); ?>">
          </div>
        </div>

        <div class="form-actions mt-4">
          <button type="submit" class="btn btn-primary btn-block btn-lg">💾 Update Property Details</button>
        </div>
      </div>
    </form>
  </main>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>window.L || document.write('<script src="/assets/vendor/leaflet/leaflet.js"><\/script>')</script>
  <script src="/admin/js/admin-map.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const initLat = <?php echo floatval($property['latitude']); ?>;
      const initLng = <?php echo floatval($property['longitude']); ?>;
      const initPoly = <?php echo !empty($property['polygon_coordinates']) ? json_encode($property['polygon_coordinates']) : 'null'; ?>;
      initAdminMap(initLat, initLng, initPoly);
    });
  </script>
</body>
</html>
