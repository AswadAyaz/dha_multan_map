<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$pdo = getDBConnection();

// Fetch statistics
$totalProps = (int)$pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$availableProps = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'Available'")->fetchColumn();
$soldProps = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'Sold'")->fetchColumn();
$reservedProps = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'Reserved'")->fetchColumn();
$totalPhases = (int)$pdo->query("SELECT COUNT(*) FROM phases")->fetchColumn();
$totalBlocks = (int)$pdo->query("SELECT COUNT(*) FROM blocks")->fetchColumn();

// Fetch properties
$stmt = $pdo->query("SELECT 
    p.*, 
    ph.name as phase_name, 
    b.name as block_name
FROM properties p
JOIN phases ph ON p.phase_id = ph.id
JOIN blocks b ON p.block_id = b.id
ORDER BY p.id DESC");
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | DHA Multan Property Map</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">

  <!-- Admin Navigation -->
  <header class="admin-header">
    <div class="admin-header-container">
      <a href="/admin/dashboard.php" class="admin-brand">
        <span class="logo-shield">DHA</span>
        <span class="logo-text">MULTAN <em>ADMIN</em></span>
      </a>

      <div class="admin-nav-links">
        <a href="/" target="_blank" class="nav-btn btn-secondary">🌐 View Public Map</a>
        <a href="/admin/add-property.php" class="nav-btn btn-primary">+ Add New Property</a>
        <div class="admin-user-menu">
          <span class="admin-user-name">👤 <?php echo e($_SESSION['admin_name']); ?></span>
          <a href="/admin/logout.php" class="logout-link">Sign Out</a>
        </div>
      </div>
    </div>
  </header>

  <main class="admin-container">
    <div class="dashboard-header-row">
      <div>
        <h1 class="page-title">DHA Multan Inventory &amp; Map Management</h1>
        <p class="page-subtitle">Configure plot polygons, pins, pricing, and live inventory statuses.</p>
      </div>
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="/admin/upload-images.php" class="btn btn-secondary btn-lg">📷 Upload Images</a>
        <a href="/admin/add-property.php" class="btn btn-primary btn-lg">+ Add Property</a>
      </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-label">Total Properties</div>
        <div class="metric-value"><?php echo $totalProps; ?></div>
        <div class="metric-sub">Registered on Map</div>
      </div>

      <div class="metric-card stat-available">
        <div class="metric-label">Available for Sale</div>
        <div class="metric-value"><?php echo $availableProps; ?></div>
        <div class="metric-sub">🟢 Active listings</div>
      </div>

      <div class="metric-card stat-sold">
        <div class="metric-label">Sold Properties</div>
        <div class="metric-value"><?php echo $soldProps; ?></div>
        <div class="metric-sub">🔴 Completed deals</div>
      </div>

      <div class="metric-card stat-reserved">
        <div class="metric-label">Reserved / On Hold</div>
        <div class="metric-value"><?php echo $reservedProps; ?></div>
        <div class="metric-sub">🟠 Token under process</div>
      </div>

      <div class="metric-card">
        <div class="metric-label">DHA Phases</div>
        <div class="metric-value"><?php echo $totalPhases; ?></div>
        <div class="metric-sub">Active Phases</div>
      </div>

      <div class="metric-card">
        <div class="metric-label">Sectors / Blocks</div>
        <div class="metric-value"><?php echo $totalBlocks; ?></div>
        <div class="metric-sub">Sectors A to V + Comm</div>
      </div>
    </div>

    <!-- Properties Management Table Section -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div>
          <h2>All DHA Multan Properties</h2>
          <span class="text-muted"><?php echo count($properties); ?> items total</span>
        </div>
        <div class="table-search-box">
          <input type="text" id="adminTableSearch" placeholder="Filter by plot #, sector, type..." class="form-control" onkeyup="filterAdminTable()">
        </div>
      </div>

      <div class="table-responsive">
        <table class="admin-table" id="propertiesTable">
          <thead>
            <tr>
              <th>Image</th>
              <th>Plot #</th>
              <th>Sector / Block</th>
              <th>Type &amp; Size</th>
              <th>Price</th>
              <th>Map Representation</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($properties)): ?>
              <tr>
                <td colspan="8" class="text-center py-4">No properties added yet. Click "+ Add New Property" to get started.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($properties as $p): ?>
                <?php 
                  $hasPoly = !empty($p['polygon_coordinates']) && $p['polygon_coordinates'] !== 'null';
                  $badgeClass = 'badge-' . strtolower($p['status']);
                ?>
                <tr>
                  <td>
                    <img src="/<?php echo e($p['featured_image'] ?: 'uploads/properties/default-plot.svg'); ?>" alt="Plot" class="table-thumb">
                  </td>
                  <td>
                    <strong><?php echo e($p['plot_number']); ?></strong>
                    <div class="text-xs text-muted"><?php echo e($p['street'] ?: 'Standard Street'); ?></div>
                  </td>
                  <td>
                    <div><?php echo e($p['block_name']); ?></div>
                    <div class="text-xs text-muted"><?php echo e($p['phase_name']); ?></div>
                  </td>
                  <td>
                    <div><?php echo e($p['property_type']); ?></div>
                    <span class="tag-pill"><?php echo e($p['plot_size']); ?></span>
                  </td>
                  <td class="font-semibold text-emerald">
                    <?php echo e(formatPKR($p['price'])); ?>
                  </td>
                  <td>
                    <?php if ($hasPoly): ?>
                      <span class="badge badge-poly" title="Exact boundary coordinates stored">📐 Polygon Boundary</span>
                    <?php else: ?>
                      <span class="badge badge-marker" title="Point coordinates stored">📍 Point Marker</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="status-badge <?php echo $badgeClass; ?>">
                      <?php echo e($p['status']); ?>
                    </span>
                  </td>
                  <td class="table-actions">
                    <a href="/property.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn-action btn-view" title="Preview Public Page">👁</a>
                    <a href="/admin/edit-property.php?id=<?php echo $p['id']; ?>" class="btn-action btn-edit" title="Edit Property">✏️</a>
                    <form method="POST" action="/admin/delete-property.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete <?php echo e($p['plot_number']); ?>?');">
                      <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                      <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                      <button type="submit" class="btn-action btn-delete" title="Delete Property">🗑</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Master Plan Overlay Calibration -->
    <div class="admin-card" style="margin-top: 1.5rem;">
      <div class="admin-card-header" style="cursor: pointer;" onclick="toggleOverlaySection()">
        <h3>🗺️ Master Plan Overlay Calibration <span class="toggle-arrow" style="font-size:0.7rem; color:#64748b;">▼</span></h3>
        <small class="text-muted">Position the master plan image overlay parts on the satellite map</small>
      </div>
      <div id="overlaySection" style="display: none; padding: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
          <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Select Part to Edit:</label>
            <select id="partSelector" class="form-control" onchange="switchPart()" style="width: 100%; max-width: 300px;"></select>
          </div>
          <div id="overlayStatus" class="overlay-status locked" style="padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; margin-bottom: 0; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; white-space: nowrap;">🔒 Overlay LOCKED - Click Unlock to edit</div>
        </div>
        <div id="calibrationMap" style="width: 100%; height: 400px; border-radius: 8px; border: 2px solid #e2e8f0; margin-bottom: 1rem;"></div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem;">
          <div>
            <label style="font-size: 0.82rem; font-weight: 600;">Opacity:</label>
            <input type="range" id="opacitySlider" min="0" max="1" step="0.05" value="0.85" style="width: 150px;">
            <span id="opacityValue" style="font-size: 0.82rem;">85%</span>
          </div>
          <button type="button" id="lockToggleBtn" class="btn btn-primary" onclick="toggleOverlayLock()">🔓 Unlock</button>
          <button type="button" class="btn btn-primary" onclick="saveOverlayConfig()">💾 Save All Parts</button>
          <button type="button" class="btn btn-secondary" onclick="resetOverlayBounds()">↺ Reset Current Part</button>
        </div>
        <div style="font-size: 0.75rem; color: #64748b;">
          <strong>Bounds:</strong> <span id="boundsInfo">N: -, S: -, E: -, W: -</span>
        </div>
      </div>
    </div>

  </main>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>window.L || document.write('<script src="/assets/vendor/leaflet/leaflet.js"><\/script>')</script>

  <script>
    function filterAdminTable() {
      const q = document.getElementById('adminTableSearch').value.toLowerCase();
      const rows = document.querySelectorAll('#propertiesTable tbody tr');
      rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
      });
    }

    // Overlay Calibration - Multi-Part Corner Handles System
    let calMap = null, overlayLayers = [], isLocked = true;
    let calMarkers = [], moveMarker = null, moveStart = null, moveBoundsStart = null;
    let overlayConfig = { parts: [], opacity: 0.85, locked: true, enabled: true, visible_public: true };
    let currentPartIndex = 0;

    const cornerIcon = L.divIcon({ className: '', html: '<div style="width:16px;height:16px;background:#3b82f6;border:2px solid #fff;border-radius:50%;box-shadow:0 0 6px rgba(0,0,0,.5);cursor:pointer;"></div>', iconSize: [16, 16], iconAnchor: [8, 8] });
    const edgeIcon = L.divIcon({ className: '', html: '<div style="width:12px;height:12px;background:#10b981;border:2px solid #fff;border-radius:50%;box-shadow:0 0 4px rgba(0,0,0,.4);cursor:pointer;"></div>', iconSize: [12, 12], iconAnchor: [6, 6] });
    const moveIcon = L.divIcon({ className: '', html: '<div style="width:22px;height:22px;background:#f59e0b;border:3px solid #fff;border-radius:50%;box-shadow:0 0 8px rgba(0,0,0,.6);cursor:move;display:flex;align-items:center;justify-content:center;"><span style="font-size:11px;color:#fff;font-weight:bold;">+</span></div>', iconSize: [22, 22], iconAnchor: [11, 11] });

    function toggleOverlaySection() {
      const section = document.getElementById('overlaySection');
      const arrow = document.querySelector('.toggle-arrow');
      if (section.style.display === 'none') {
        section.style.display = 'block';
        arrow.textContent = '▲';
        setTimeout(() => initCalibrationMap(), 150);
      } else {
        section.style.display = 'none';
        arrow.textContent = '▼';
      }
    }

    function initCalibrationMap() {
      if (calMap) { calMap.invalidateSize(); return; }
      calMap = L.map('calibrationMap', { center: [30.305, 71.560], zoom: 13, maxZoom: 20, minZoom: 10 });
      L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { attribution: 'Google Satellite' }).addTo(calMap);
      loadOverlayConfig();
    }

    async function loadOverlayConfig() {
      try {
        const res = await fetch('/api/master-plan-overlay.php');
        const data = await res.json();
        if (data.success && data.overlay) {
          overlayConfig = data.overlay;
          isLocked = !!overlayConfig.locked;
          document.getElementById('opacitySlider').value = overlayConfig.opacity || 0.85;
          document.getElementById('opacityValue').textContent = Math.round((overlayConfig.opacity || 0.85) * 100) + '%';
          
          // Populate part selector
          const selector = document.getElementById('partSelector');
          selector.innerHTML = '';
          (overlayConfig.parts || []).forEach((part, idx) => {
            const opt = document.createElement('option');
            opt.value = idx;
            opt.textContent = part.name || `Part ${idx + 1}`;
            selector.appendChild(opt);
          });
          
          updateLockUI();
        }
      } catch (err) {}
      renderAllOverlays();
    }

    function getCurrentPart() {
      return (overlayConfig.parts || [])[currentPartIndex];
    }

    function renderAllOverlays() {
      if (!calMap) return;
      // Remove existing overlay layers
      overlayLayers.forEach(layer => { if (layer) calMap.removeLayer(layer); });
      overlayLayers = [];
      clearCalMarkers();

      (overlayConfig.parts || []).forEach((part, idx) => {
        const b = part.bounds;
        if (!b || !b.north || !b.south || !b.east || !b.west) return;

        const layer = L.imageOverlay(part.image_url, [[b.south, b.west], [b.north, b.east]], {
          opacity: part.opacity !== undefined ? part.opacity : (overlayConfig.opacity || 0.85),
          interactive: false
        });
        layer.addTo(calMap);
        overlayLayers[idx] = layer;
      });

      // Add handles only for current part if unlocked
      if (!isLocked) addCalHandles();
      updateBoundsInfo();
    }

    function switchPart() {
      currentPartIndex = parseInt(document.getElementById('partSelector').value) || 0;
      const part = getCurrentPart();
      if (part) {
        document.getElementById('opacitySlider').value = part.opacity !== undefined ? part.opacity : (overlayConfig.opacity || 0.85);
        document.getElementById('opacityValue').textContent = Math.round((part.opacity || overlayConfig.opacity || 0.85) * 100) + '%';
      }
      renderAllOverlays();
    }

    function getCornerPositions() {
      const part = getCurrentPart();
      if (!part) return { nw: [0,0], ne: [0,0], sw: [0,0], se: [0,0] };
      const b = part.bounds;
      return {
        nw: [b.north, b.west],
        ne: [b.north, b.east],
        sw: [b.south, b.west],
        se: [b.south, b.east]
      };
    }

    function addCalHandles() {
      clearCalMarkers();
      const c = getCornerPositions();
      const corners = [
        { pos: c.nw, key: 'nw' },
        { pos: c.ne, key: 'ne' },
        { pos: c.sw, key: 'sw' },
        { pos: c.se, key: 'se' }
      ];
      const edges = [
        { pos: [(c.nw[0]+c.ne[0])/2, (c.nw[1]+c.ne[1])/2], key: 'n' },
        { pos: [(c.sw[0]+c.se[0])/2, (c.sw[1]+c.se[1])/2], key: 's' },
        { pos: [(c.nw[0]+c.sw[0])/2, c.nw[1]], key: 'w' },
        { pos: [(c.ne[0]+c.se[0])/2, c.ne[1]], key: 'e' }
      ];
      const center = [(c.nw[0]+c.se[0])/2, (c.nw[1]+c.se[1])/2];

      corners.forEach(({ pos, key }) => {
        const m = L.marker(pos, { icon: cornerIcon, draggable: true }).addTo(calMap);
        m.on('drag', (e) => onCornerDrag(e.latlng, key));
        calMarkers.push(m);
      });

      edges.forEach(({ pos, key }) => {
        const m = L.marker(pos, { icon: edgeIcon, draggable: true }).addTo(calMap);
        m.on('drag', (e) => onEdgeDrag(e.latlng, key));
        calMarkers.push(m);
      });

      moveMarker = L.marker(center, { icon: moveIcon, draggable: true }).addTo(calMap);
      moveMarker.on('dragstart', (e) => {
        moveStart = e.latlng;
        const part = getCurrentPart();
        moveBoundsStart = part ? { ...part.bounds } : null;
      });
      moveMarker.on('drag', (e) => {
        if (!moveStart || !moveBoundsStart) return;
        const dlat = e.latlng.lat - moveStart.lat;
        const dlng = e.latlng.lng - moveStart.lng;
        const part = getCurrentPart();
        if (part) {
          part.bounds.north = moveBoundsStart.north + dlat;
          part.bounds.south = moveBoundsStart.south + dlat;
          part.bounds.east = moveBoundsStart.east + dlng;
          part.bounds.west = moveBoundsStart.west + dlng;
          updateOverlayAndHandles();
        }
      });
      moveMarker.on('dragend', () => { moveStart = null; moveBoundsStart = null; });
      calMarkers.push(moveMarker);
    }

    function clearCalMarkers() {
      calMarkers.forEach(m => { if (calMap) calMap.removeLayer(m); });
      calMarkers = [];
      moveMarker = null;
      moveStart = null;
      moveBoundsStart = null;
    }

    function onCornerDrag(latlng, corner) {
      const part = getCurrentPart();
      if (!part) return;
      const b = part.bounds;
      if (corner === 'nw') { b.north = latlng.lat; b.west = latlng.lng; }
      else if (corner === 'ne') { b.north = latlng.lat; b.east = latlng.lng; }
      else if (corner === 'sw') { b.south = latlng.lat; b.west = latlng.lng; }
      else if (corner === 'se') { b.south = latlng.lat; b.east = latlng.lng; }
      updateOverlayAndHandles();
    }

    function onEdgeDrag(latlng, edge) {
      const part = getCurrentPart();
      if (!part) return;
      const b = part.bounds;
      if (edge === 'n') { b.north = latlng.lat; }
      else if (edge === 's') { b.south = latlng.lat; }
      else if (edge === 'w') { b.west = latlng.lng; }
      else if (edge === 'e') { b.east = latlng.lng; }
      updateOverlayAndHandles();
    }

    function updateOverlayAndHandles() {
      const part = getCurrentPart();
      if (!part) return;
      const b = part.bounds;
      const layer = overlayLayers[currentPartIndex];
      if (layer) layer.setBounds([[b.south, b.west], [b.north, b.east]]);
      refreshHandlePositions();
      updateBoundsInfo();
    }

    function refreshHandlePositions() {
      const c = getCornerPositions();
      if (calMarkers.length >= 8) {
        calMarkers[0].setLatLng(c.nw);
        calMarkers[1].setLatLng(c.ne);
        calMarkers[2].setLatLng(c.sw);
        calMarkers[3].setLatLng(c.se);
        calMarkers[4].setLatLng([(c.nw[0]+c.ne[0])/2, (c.nw[1]+c.ne[1])/2]);
        calMarkers[5].setLatLng([(c.sw[0]+c.se[0])/2, (c.sw[1]+c.se[1])/2]);
        calMarkers[6].setLatLng([(c.nw[0]+c.sw[0])/2, c.nw[1]]);
        calMarkers[7].setLatLng([(c.ne[0]+c.se[0])/2, c.ne[1]]);
      }
      if (moveMarker) moveMarker.setLatLng([(c.nw[0]+c.se[0])/2, (c.nw[1]+c.se[1])/2]);
    }

    function updateBoundsInfo() {
      const part = getCurrentPart();
      if (!part) {
        document.getElementById('boundsInfo').textContent = 'N: -, S: -, E: -, W: -';
        return;
      }
      const b = part.bounds;
      document.getElementById('boundsInfo').textContent = `N: ${b.north.toFixed(4)}, S: ${b.south.toFixed(4)}, E: ${b.east.toFixed(4)}, W: ${b.west.toFixed(4)}`;
    }

    function toggleOverlayLock() {
      isLocked = !isLocked;
      overlayConfig.locked = isLocked;
      updateLockUI();
      renderAllOverlays();
    }

    function updateLockUI() {
      const btn = document.getElementById('lockToggleBtn');
      const status = document.getElementById('overlayStatus');
      if (isLocked) {
        btn.textContent = '🔓 Unlock';
        status.className = 'overlay-status locked';
        status.style.cssText = 'padding:0.5rem 1rem;border-radius:8px;font-size:0.82rem;font-weight:600;margin-bottom:0;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;white-space:nowrap;';
        status.textContent = '🔒 Overlay LOCKED - Click Unlock to edit';
      } else {
        btn.textContent = '🔒 Lock';
        status.className = 'overlay-status unlocked';
        status.style.cssText = 'padding:0.5rem 1rem;border-radius:8px;font-size:0.82rem;font-weight:600;margin-bottom:0;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;white-space:nowrap;';
        status.textContent = '🔓 Overlay UNLOCKED - Drag handles to move/resize';
      }
    }

    document.getElementById('opacitySlider').addEventListener('input', (e) => {
      const val = parseFloat(e.target.value);
      document.getElementById('opacityValue').textContent = Math.round(val * 100) + '%';
      const part = getCurrentPart();
      if (part) {
        part.opacity = val;
        const layer = overlayLayers[currentPartIndex];
        if (layer) layer.setOpacity(val);
      }
    });

    function resetOverlayBounds() {
      const part = getCurrentPart();
      if (part) {
        // Default bounds for each part (approximate)
        const defaults = [
          { north: 30.335, south: 30.305, east: 71.575, west: 71.525 },
          { north: 30.335, south: 30.305, east: 71.625, west: 71.575 },
          { north: 30.305, south: 30.275, east: 71.575, west: 71.525 },
          { north: 30.305, south: 30.275, east: 71.625, west: 71.575 },
          { north: 30.275, south: 30.245, east: 71.600, west: 71.550 }
        ];
        part.bounds = defaults[currentPartIndex] || defaults[0];
        renderAllOverlays();
      }
    }

    async function saveOverlayConfig() {
      try {
        const res = await fetch('/api/admin/master-plan-overlay.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(overlayConfig)
        });
        const data = await res.json();
        if (data.success) { alert('All overlay parts saved successfully!'); }
        else { alert('Failed: ' + (data.error || 'Unknown')); }
      } catch (err) { alert('Network error.'); }
    }
  </script>
</body>
</html>
