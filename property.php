<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$pdo = getDBConnection();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /');
    exit;
}

// Fetch property
$stmt = $pdo->prepare("SELECT 
    p.*,
    ph.name as phase_name,
    b.name as block_name,
    b.sector_type
FROM properties p
JOIN phases ph ON p.phase_id = ph.id
JOIN blocks b ON p.block_id = b.id
WHERE p.id = ?");
$stmt->execute([$id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header('Location: /');
    exit;
}

// Fetch gallery images
$imgStmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch similar properties in same block/sector
$simStmt = $pdo->prepare("SELECT * FROM properties WHERE block_id = ? AND id != ? LIMIT 3");
$simStmt->execute([$property['block_id'], $id]);
$similar = $simStmt->fetchAll(PDO::FETCH_ASSOC);

$statusColors = [
    'Available' => '#10b981',
    'Sold' => '#ef4444',
    'Reserved' => '#f59e0b',
    'Hold' => '#3b82f6'
];
$statusColor = $statusColors[$property['status']] ?? '#10b981';

$phoneClean = preg_replace('/[^0-9]/', '', $property['whatsapp']);
$waMessage = urlencode("Hi, I am interested in DHA Multan {$property['block_name']} {$property['plot_number']} ({$property['title']}) listed on DHA Multan Property Map.");
$waUrl = "https://wa.me/{$phoneClean}?text={$waMessage}";
$telUrl = "tel:" . preg_replace('/[^0-9+]/', '', $property['phone']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($property['plot_number']); ?> - <?php echo e($property['block_name']); ?> | DHA Multan Property Map</title>
  <meta name="description" content="<?php echo e($property['title']); ?> in <?php echo e($property['block_name']); ?>, DHA Multan. Price: <?php echo e(formatPKR($property['price'])); ?>. Status: <?php echo e($property['status']); ?>.">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

  <!-- Site Navigation -->
  <header class="site-header">
    <div class="header-container">
      <a href="/" class="brand-logo">
        <span class="logo-shield">DHA</span>
        <span class="logo-text">MULTAN <em>MAP</em></span>
      </a>
      <div class="header-actions">
        <a href="/?plot=<?php echo urlencode($property['plot_number']); ?>" class="btn btn-secondary btn-sm">🗺 View on DHA Master Map</a>
        <a href="/admin/login.php" class="btn btn-outline btn-sm">Admin Portal</a>
      </div>
    </div>
  </header>

  <main style="max-width: 1200px; margin: 2rem auto; padding: 0 1.25rem; flex: 1;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
      <a href="/" style="color: var(--primary);">Home</a> &rsaquo;
      <span><?php echo e($property['phase_name']); ?></span> &rsaquo;
      <span><?php echo e($property['block_name']); ?></span> &rsaquo;
      <span style="font-weight: 700; color: var(--text-main);"><?php echo e($property['plot_number']); ?></span>
    </div>

    <div class="property-form-grid" style="grid-template-columns: 1.3fr 0.9fr;">
      <!-- Left Column: Media & Specs -->
      <div>
        <div class="admin-card" style="margin-bottom: 1.5rem;">
          <div style="position: relative; height: 380px; background: #062b20;">
            <img src="/<?php echo e($property['featured_image'] ?: 'uploads/properties/plot-102.svg'); ?>" alt="<?php echo e($property['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='/uploads/properties/plot-102.svg'">
            <span class="card-badge" style="background-color: <?php echo $statusColor; ?>; font-size: 0.85rem; padding: 0.4rem 0.9rem;">
              <?php echo e($property['status']); ?>
            </span>
          </div>
          
          <div style="padding: 1.75rem;">
            <div class="popup-sector-tag" style="font-size: 0.85rem;"><?php echo e($property['phase_name']); ?> &bull; <?php echo e($property['block_name']); ?></div>
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;"><?php echo e($property['title']); ?></h1>
            <div style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1.25rem;">
              Plot # <strong><?php echo e($property['plot_number']); ?></strong> &bull; <?php echo e($property['street'] ?: 'Standard Street'); ?>
            </div>

            <div style="font-size: 2.25rem; font-weight: 800; color: #065f46; margin-bottom: 1.5rem;">
              <?php echo e(formatPKR($property['price'])); ?>
            </div>

            <!-- Specs Grid -->
            <div class="detail-specs-grid" style="margin-bottom: 2rem;">
              <div>
                <div class="spec-box-label">Property Type</div>
                <div class="spec-box-value"><?php echo e($property['property_type']); ?></div>
              </div>
              <div>
                <div class="spec-box-label">Plot Size</div>
                <div class="spec-box-value"><?php echo e($property['plot_size']); ?></div>
              </div>
              <div>
                <div class="spec-box-label">Sector / Block</div>
                <div class="spec-box-value"><?php echo e($property['block_name']); ?></div>
              </div>
              <div>
                <div class="spec-box-label">Map Representation</div>
                <div class="spec-box-value"><?php echo !empty($property['polygon_coordinates']) ? '📐 Exact Polygon' : '📍 Point Pin'; ?></div>
              </div>
            </div>

            <!-- Description -->
            <div class="detail-desc-box">
              <h4>Property Details &amp; Highlights</h4>
              <p style="font-size: 1rem; color: #334155; line-height: 1.7;"><?php echo nl2br(e($property['description'])); ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Interactive Plot Map & Realtor Card -->
      <div>
        <!-- Plot Map Frame -->
        <div class="form-card" style="margin-bottom: 1.5rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <h3 style="margin: 0; border: none; padding: 0;">Plot Location Map</h3>
            <a href="/?plot=<?php echo urlencode($property['plot_number']); ?>" class="btn btn-secondary btn-sm">Full Map View ↗</a>
          </div>
          <div id="singlePropertyMap" style="height: 280px; border-radius: var(--radius-md); border: 1px solid var(--border-color);"></div>
          <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted); display: flex; justify-content: space-between;">
            <span>Lat: <?php echo e($property['latitude']); ?></span>
            <span>Lng: <?php echo e($property['longitude']); ?></span>
          </div>
        </div>

        <!-- Realtor Contact Card -->
        <div class="form-card" style="border: 2px solid #bbf7d0; background: #f0fdf4;">
          <h3 style="border-bottom-color: #bbf7d0; color: var(--primary);">Contact Authorized Realtor</h3>
          <div style="margin-bottom: 1.25rem;">
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);"><?php echo e($property['owner_dealer_name']); ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Registered Real Estate Dealer &bull; DHA Multan</div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <a href="<?php echo $waUrl; ?>" target="_blank" class="btn btn-whatsapp btn-block btn-lg">
              💬 Inquire via WhatsApp
            </a>
            <a href="<?php echo $telUrl; ?>" class="btn btn-call btn-block btn-lg">
              📞 Call <?php echo e($property['phone']); ?>
            </a>
          </div>
          <div style="margin-top: 1rem; font-size: 0.75rem; color: #15803d; text-align: center;">
            ✓ Direct dealer communication &bull; Fast token verification
          </div>
        </div>

        <!-- Similar Properties -->
        <?php if (!empty($similar)): ?>
          <div class="form-card" style="margin-top: 1.5rem;">
            <h3>More in <?php echo e($property['block_name']); ?></h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              <?php foreach ($similar as $sim): ?>
                <a href="/property.php?id=<?php echo $sim['id']; ?>" style="display: flex; gap: 0.75rem; align-items: center; padding: 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: #ffffff;">
                  <img src="/<?php echo e($sim['featured_image'] ?: 'uploads/properties/plot-102.svg'); ?>" alt="Plot" style="width: 50px; height: 40px; object-fit: cover; border-radius: 4px;" onerror="this.src='/uploads/properties/plot-102.svg'">
                  <div style="flex: 1;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);"><?php echo e($sim['plot_number']); ?></div>
                    <div style="font-size: 0.75rem; color: #065f46; font-weight: 700;"><?php echo e(formatPKR($sim['price'])); ?></div>
                  </div>
                  <span style="font-size: 0.75rem; color: var(--primary);">View &rsaquo;</span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>window.L || document.write('<script src="/assets/vendor/leaflet/leaflet.js"><\/script>')</script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const lat = <?php echo floatval($property['latitude']); ?>;
      const lng = <?php echo floatval($property['longitude']); ?>;
      const polyJson = <?php echo !empty($property['polygon_coordinates']) ? json_encode($property['polygon_coordinates']) : 'null'; ?>;
      const statusColor = '<?php echo $statusColor; ?>';

      const map = L.map('singlePropertyMap', {
        center: [lat, lng],
        zoom: 17,
        zoomControl: false
      });

      L.control.zoom({ position: 'topright' }).addTo(map);

      L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Esri Satellite',
        maxZoom: 19
      }).addTo(map);

      if (polyJson) {
        try {
          const coords = typeof polyJson === 'string' ? JSON.parse(polyJson) : polyJson;
          if (Array.isArray(coords) && coords.length >= 3) {
            L.polygon(coords, {
              color: statusColor,
              weight: 3,
              fillColor: statusColor,
              fillOpacity: 0.6
            }).addTo(map);
          }
        } catch (e) {}
      } else {
        L.marker([lat, lng]).addTo(map);
      }
    });
  </script>
</body>
</html>
