<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$pdo = getDBConnection();
$phases = $pdo->query("SELECT * FROM phases ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$blocks = $pdo->query("SELECT * FROM blocks ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>DHA Multan Property Map | Interactive Master Plan &amp; Plot Explorer</title>
  <meta name="description" content="Explore verified residential and commercial plots, houses, and villas with live possession and sales status across DHA Multan.">
  <meta property="og:title" content="DHA Multan Property Map | Interactive Master Plan &amp; Plot Explorer">
  <meta property="og:description" content="Explore verified residential and commercial plots, houses, and villas with live possession and sales status across DHA Multan.">
  <meta property="og:type" content="website">

  <!-- Local Leaflet CSS -->
  <link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
  <!-- Fallback Leaflet CDN if needed -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <!-- Custom eMap Stylesheet -->
  <link rel="stylesheet" href="/assets/css/style.css?v=3.2">
</head>
<body>

  <!-- Fullscreen Map Architecture (emap.pk Interface) -->
  <div class="emap-app-container">

    <!-- 1. 100% Fullscreen Leaflet Map -->
    <div id="leafletMap" class="emap-leaflet-fullscreen"></div>

    <!-- 2. Floating Top Header Bar -->
    <header class="emap-top-bar">
      
      <!-- Top Left: Floating Search Pill -->
      <div class="emap-search-pill" id="emapSearchPill">
        <button type="button" class="emap-hamburger-btn" onclick="toggleFilterDrawer()" title="Sectors &amp; Filters Menu">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <input 
          type="text" 
          id="headerSearchInput" 
          class="emap-search-input" 
          placeholder="Search map by society name ..." 
          autocomplete="off"
        >
        <button type="button" class="emap-search-icon-btn" onclick="toggleFilterDrawer()" title="Search &amp; Filter">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </button>
      </div>


      <!-- Top Right: Profile Avatar -->
      <div class="emap-top-right">
        <div class="emap-profile-wrapper">
          <button type="button" class="emap-avatar-btn" id="emapProfileToggleBtn" onclick="toggleProfileDropdown()" title="User Menu &amp; Admin">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#1e293b" stroke-width="1.8">
              <circle cx="12" cy="12" r="10" fill="#ffffff"/>
              <circle cx="12" cy="10" r="3.2"/>
              <path d="M6.168 18.849A4 4 0 0 1 10 16h4a4 4 0 0 1 3.834 2.85"/>
            </svg>
          </button>
          <div class="emap-profile-dropdown" id="emapProfileDropdown">
            <div class="profile-dropdown-header">
              <strong>DHA Multan Portal</strong>
              <small>Authorized Master Map Portal</small>
            </div>
            <a href="/admin/login.php" class="profile-dropdown-item">🔐 Realtor / Admin Login</a>
            <a href="#" onclick="switchView('list'); return false;" class="profile-dropdown-item">📋 List View (All Plots)</a>
            <a href="#" onclick="toggleFilterDrawer(); return false;" class="profile-dropdown-item">🔍 Sectors &amp; Price Filters</a>
            <a href="#" onclick="toggleEmapInfoModal(); return false;" class="profile-dropdown-item">ℹ️ Map Legend &amp; Info</a>
          </div>
        </div>
      </div>

    </header>

    <!-- 3. Left Side Floating Toolbar (Share, Refresh, Location, Info) -->
    <div class="emap-left-toolbar">
      <button type="button" class="emap-tool-btn" id="emapPlanBtn" onclick="toggleMasterPlanOverlay()" title="Toggle DHA Master Plan Image Overlay">
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2">
          <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/>
          <line x1="8" y1="2" x2="8" y2="18"/>
          <line x1="16" y1="6" x2="16" y2="22"/>
        </svg>
      </button>
      <button type="button" class="emap-tool-btn" id="emapShareBtn" onclick="handleEmapShare()" title="Share Map">
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2">
          <circle cx="18" cy="5" r="3"/>
          <circle cx="6" cy="12" r="3"/>
          <circle cx="18" cy="19" r="3"/>
          <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
          <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
        </svg>
      </button>
      <button type="button" class="emap-tool-btn" id="emapRefreshBtn" onclick="resetDhaMapView()" title="Reset Camera View">
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2">
          <polyline points="23 4 23 10 17 10"/>
          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
      </button>
      <button type="button" class="emap-tool-btn" id="emapLocationBtn" onclick="locateDhaCenter()" title="Center DHA Multan">
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2">
          <circle cx="12" cy="12" r="3"/>
          <path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
          <circle cx="12" cy="12" r="8"/>
        </svg>
      </button>
      <button type="button" class="emap-tool-btn" id="emapInfoBtn" onclick="toggleEmapInfoModal()" title="Map Information &amp; Status Legend">
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="16" x2="12" y2="12"/>
          <line x1="12" y1="8" x2="12.01" y2="8"/>
        </svg>
      </button>
    </div>

    <!-- 4. Bottom-Left Layer Switcher Thumbnail (Satellite / Streets) -->
    <div class="emap-layer-box" onclick="toggleMapTileLayer()" title="Click to Switch Satellite / Streets Map">
      <div class="emap-layer-thumb" id="emapLayerThumb">
        <span class="emap-layer-tag" id="emapLayerTag">Streets</span>
      </div>
    </div>

    <!-- 5. Right Side Floating Zoom Slider Controller -->
    <div class="emap-right-zoom-panel">
      <div class="emap-slider-container">
        <div class="emap-slider-track">
          <div class="emap-slider-fill" id="emapZoomFill"></div>
          <input 
            type="range" 
            id="emapZoomSlider" 
            class="emap-vertical-slider" 
            min="11" 
            max="18" 
            step="0.2" 
            value="14" 
            orient="vertical"
          >
        </div>
      </div>
      <div class="emap-zoom-actions">
        <button type="button" class="emap-zoom-step-btn" id="emapZoomInBtn" onclick="zoomInMap()" title="Zoom In">+</button>
        <button type="button" class="emap-zoom-step-btn" id="emapZoomOutBtn" onclick="zoomOutMap()" title="Zoom Out">&minus;</button>
      </div>
    </div>

    <!-- 6. Bottom Disclaimer -->
    <div class="emap-bottom-disclaimer">
      Disclaimer: Map data is for reference only. Verify with authorities. No liability assumed.
    </div>

    <!-- Map Count Badge -->
    <div class="emap-count-badge" id="mapCountBadge"></div>

    <!-- 7. Slide-Up Bottom Plot Details Drawer -->
    <div class="bottom-plot-drawer" id="bottomPlotDrawer"></div>

    <!-- 8. Floating Toast Notification -->
    <div class="emap-toast" id="emapToast"></div>

    <!-- 9. Collapsible Side Drawer (Menu & Filters) -->
    <aside class="filter-drawer" id="filterDrawer">
      <div class="drawer-header">
        <div class="drawer-header-title">
          <span>☰</span>
          <span>DHA Multan Master Map</span>
        </div>
        <button type="button" class="drawer-close" onclick="closeFilterDrawer()">&times;</button>
      </div>

      <div class="drawer-body">
        
        <!-- Search Keyword -->
        <div class="filter-group">
          <label class="filter-label" for="drawerSearchInput">Search Plot or Society</label>
          <input type="text" id="drawerSearchInput" class="form-control" placeholder="e.g. Plot 102, Sector A, Rumanza...">
        </div>

        <!-- Availability Status -->
        <div class="filter-group">
          <label class="filter-label">Availability Status</label>
          <div class="status-pill-group">
            <button type="button" class="status-pill" data-status="Available">
              <span class="status-indicator-dot dot-available"></span>
              <span>Available</span>
            </button>
            <button type="button" class="status-pill" data-status="Sold">
              <span class="status-indicator-dot dot-sold"></span>
              <span>Sold</span>
            </button>
            <button type="button" class="status-pill" data-status="Reserved">
              <span class="status-indicator-dot dot-reserved"></span>
              <span>Reserved</span>
            </button>
            <button type="button" class="status-pill" data-status="Hold">
              <span class="status-indicator-dot dot-hold"></span>
              <span>On Hold</span>
            </button>
          </div>
        </div>

        <!-- DHA Phase & Sector / Block -->
        <div class="filter-group">
          <label class="filter-label" for="filter_phase_id">DHA Phase</label>
          <select id="filter_phase_id" class="form-control">
            <option value="">All Phases</option>
            <?php foreach ($phases as $ph): ?>
              <option value="<?php echo $ph['id']; ?>"><?php echo htmlspecialchars($ph['name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label class="filter-label" for="filter_block_id">Sector / Block</label>
          <select id="filter_block_id" class="form-control">
            <option value="">All Sectors / Blocks</option>
            <?php foreach ($blocks as $b): ?>
              <option value="<?php echo $b['id']; ?>">
                <?php echo htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($b['sector_type'], ENT_QUOTES, 'UTF-8'); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Property Type Filter -->
        <div class="filter-group">
          <label class="filter-label" for="filter_property_type">Property Type</label>
          <select id="filter_property_type" class="form-control">
            <option value="">All Types</option>
            <option value="Residential Plot">Residential Plot</option>
            <option value="Commercial Plot">Commercial Plot</option>
            <option value="House">House</option>
            <option value="Villa">Villa</option>
            <option value="Plot">Plot</option>
          </select>
        </div>

        <!-- Plot Size Filter -->
        <div class="filter-group">
          <label class="filter-label" for="filter_plot_size">Plot Size</label>
          <select id="filter_plot_size" class="form-control">
            <option value="">All Sizes</option>
            <option value="5 Marla">5 Marla</option>
            <option value="10 Marla">10 Marla</option>
            <option value="1 Kanal">1 Kanal</option>
            <option value="2 Kanal">2 Kanal</option>
            <option value="4 Marla">4 Marla</option>
            <option value="8 Marla">8 Marla</option>
          </select>
        </div>

        <!-- Price Range Filter -->
        <div class="filter-group">
          <label class="filter-label">Price Range (PKR)</label>
          <div style="display: flex; gap: 8px;">
            <input type="number" id="filter_min_price" class="form-control" placeholder="Min Price" step="100000">
            <input type="number" id="filter_max_price" class="form-control" placeholder="Max Price" step="100000">
          </div>
        </div>

        <!-- View Mode Switcher -->
        <div class="filter-group">
          <label class="filter-label">View Mode</label>
          <div class="view-mode-pill" style="width: 100%; display: flex;">
            <button type="button" id="viewMapBtn" class="view-btn active" style="flex: 1;" onclick="switchView('map')">
              🗺️ Master Map
            </button>
            <button type="button" id="viewListBtn" class="view-btn" style="flex: 1;" onclick="switchView('list')">
              📋 List View
            </button>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="filter-group" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
          <a href="/admin/login.php" class="btn btn-outline" style="width: 100%;">
            🔐 Realtor / Admin Portal Login
          </a>
        </div>

      </div>

      <div class="drawer-footer">
        <span id="resultsCount" style="font-size: 0.85rem; font-weight: 700; color: var(--primary);">
          Loading plots...
        </span>
        <button type="button" class="btn btn-secondary btn-sm" onclick="resetFilters()">
          Clear All
        </button>
      </div>
    </aside>

    <!-- 10. List View Container (Toggled via view switcher) -->
    <section class="list-view-container" id="listViewContainer">
      <div style="max-width: 1400px; margin: 0 auto 1.25rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Verified DHA Multan Plots &amp; Properties</h2>
          <p style="color: var(--text-muted); font-size: 0.875rem;">Interactive listings with price, size, and exact map mapping</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" onclick="switchView('map')">
          🗺️ Return to Master Map
        </button>
      </div>

      <div class="properties-grid" id="propertiesGrid">
        <!-- Rendered dynamically by app.js -->
      </div>
    </section>

  </div>

  <!-- 11. Info Modal (Legend & Details) -->
  <div class="emap-modal" id="emapInfoModal" onclick="if(event.target === this) closeEmapInfoModal()">
    <div class="emap-modal-content">
      <div class="emap-modal-header">
        <h3>
          <span>ℹ️</span>
          <span>DHA Multan Master Map Info</span>
        </h3>
        <button type="button" class="emap-modal-close" onclick="closeEmapInfoModal()">&times;</button>
      </div>
      <div class="emap-modal-body">
        <p style="color: #475569; font-size: 0.9rem; line-height: 1.6;">
          Welcome to the official interactive property map for <strong>DHA Multan</strong>. This portal enables investors and buyers to view verified residential and commercial plots, Rumanza Golf villas, and possession status with accurate coordinates.
        </p>

        <h4 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 1rem;">Plot Status Legend:</h4>
        <div class="emap-info-legend">
          <div class="emap-legend-item">
            <span class="emap-legend-dot" style="background-color: #10b981;"></span>
            <span>Available (Green)</span>
          </div>
          <div class="emap-legend-item">
            <span class="emap-legend-dot" style="background-color: #ef4444;"></span>
            <span>Sold (Red)</span>
          </div>
          <div class="emap-legend-item">
            <span class="emap-legend-dot" style="background-color: #f59e0b;"></span>
            <span>Reserved (Yellow)</span>
          </div>
          <div class="emap-legend-item">
            <span class="emap-legend-dot" style="background-color: #3b82f6;"></span>
            <span>On Hold (Blue)</span>
          </div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 0.85rem; margin-top: 1rem;">
          <div style="font-weight: 700; color: #166534; font-size: 0.875rem;">Official Dealer Desk</div>
          <div style="color: #15803d; font-size: 0.8rem; margin-top: 0.2rem;">For booking, transfers, or map inquiries:</div>
          <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
            <a href="https://wa.me/923008632551" target="_blank" class="btn btn-whatsapp btn-sm">💬 WhatsApp Desk</a>
            <a href="tel:+923008632551" class="btn btn-call btn-sm">📞 Call Desk</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 13. Full Property Detail Modal -->
  <div class="modal-overlay" id="propertyDetailModal" onclick="if(event.target === this) closePropertyModal()">
    <div class="modal-dialog">
      <button type="button" class="modal-close-btn" onclick="closePropertyModal()">&times;</button>
      <div id="propertyModalContent">
        <!-- Injected dynamically by openPropertyModal(id) -->
      </div>
    </div>
  </div>

  <!-- Leaflet Local Vendor JS & Fallback -->
  <script src="/assets/vendor/leaflet/leaflet.js"></script>
  <script>
    if (typeof L === 'undefined') {
      document.write('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"><\/script>');
    }
  </script>
  <!-- Map & Application Controllers -->
  <script src="/assets/js/map.js?v=3.2"></script>
  <script src="/assets/js/app.js?v=3.2"></script>

</body>
</html>
