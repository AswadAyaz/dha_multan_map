/**
 * DHA Multan Interactive Property Map Engine (emap.pk concept)
 * Displays verified sample plots on high-resolution map with emap.pk UI controls:
 * - Floating top search pill, Islamic Durood calligraphy & emap.pk brand logo
 * - Floating "+ Post Free Property" & profile avatar
 * - Left vertical toolbar: Share, Refresh, Location Target, Info
 * - Bottom-left layer thumbnail switcher (Satellite <-> Streets)
 * - Right vertical zoom slider controller with + / - buttons
 * - Bottom legal disclaimer (No "Install App" button)
 */

let dhaMap = null;
let plotPolygonsLayerGroup = null;
let plotGpsMarkersLayerGroup = null;

let masterPlanOverlayLayers = [];
let masterPlanOverlayData = null;

let googleSatelliteLayer = null;
let googleStreetLayer = null;
let googleRoadsOverlay = null;
let currentBaseLayer = "satellite";

// DHA Multan Geographical Center & Default View
const DHA_MULTAN_CENTER = [30.2985, 71.5585];
const DHA_DEFAULT_ZOOM = 14;

// 5 Active Sample Sectors corresponding to the 5 sample plots
const DHA_SECTORS_DATA = [
  {
    id: "A",
    name: "Sector A",
    plot: "Plot 102",
    type: "1 Kanal Residential",
    center: [30.2985, 71.5512],
  },
  {
    id: "B1",
    name: "Sector B1",
    plot: "Plot 45",
    type: "1 Kanal Residential",
    center: [30.3045, 71.5535],
  },
  {
    id: "F",
    name: "Sector F (Rumanza)",
    plot: "Rumanza Villa 14",
    type: "2 Kanal Luxury Villa",
    center: [30.288, 71.573],
  },
  {
    id: "M",
    name: "Sector M",
    plot: "Plot 325",
    type: "10 Marla Residential",
    center: [30.3075, 71.5675],
  },
  {
    id: "MB",
    name: "Main Boulevard",
    plot: "MB-07",
    type: "8 Marla Commercial",
    center: [30.3005, 71.553],
  },
];

function initDHAMap() {
  if (dhaMap) {
    dhaMap.invalidateSize();
    return;
  }

  const mapContainer = document.getElementById("leafletMap");
  if (!mapContainer) return;

  // Initialize Leaflet Map (disable default zoom control to use our custom emap slider)
  dhaMap = L.map("leafletMap", {
    center: DHA_MULTAN_CENTER,
    zoom: DHA_DEFAULT_ZOOM,
    minZoom: 11,
    maxZoom: 18,
    zoomControl: false,
    attributionControl: false,
  });

  // CREATE CUSTOM LEAFLET PANES FOR LAYER ORDERING
  // 1. Master Plan Image Overlay Pane (zIndex 250) - Sits on top of satellite imagery
  const masterPlanPane = dhaMap.createPane("masterPlanPane");
  masterPlanPane.style.zIndex = "250";

  // 2. Plots Pane (zIndex 350) - Sits above master plan image & satellite imagery but BELOW roads
  const plotsPane = dhaMap.createPane("plotsPane");
  plotsPane.style.zIndex = "350";

  // 3. Roads & Labels Pane (zIndex 450) - Renders on top of plot fills so green never obscures roads!
  const roadsPane = dhaMap.createPane("roadsPane");
  roadsPane.style.zIndex = "450";
  roadsPane.style.pointerEvents = "none";

  // BASE TILE LAYERS
  // 1. Google Satellite (Base Imagery)
  googleSatelliteLayer = L.tileLayer(
    "https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",
    {
      attribution: "Google Satellite",
      maxZoom: 20,
    },
  );

  // 2. Google Street Map
  googleStreetLayer = L.tileLayer(
    "https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
    {
      attribution: "Google Maps",
      maxZoom: 20,
    },
  );

  // 3. Top Roads & Labels Overlay (Transparent PNG)
  googleRoadsOverlay = L.tileLayer(
    "https://mt1.google.com/vt/lyrs=h&x={x}&y={y}&z={z}",
    {
      pane: "roadsPane",
      attribution: "Google Roads & Labels",
      maxZoom: 20,
    },
  );

  // Default base layer is Google Satellite + Roads Overlay
  googleSatelliteLayer.addTo(dhaMap);
  googleRoadsOverlay.addTo(dhaMap);

  // Load and render Master Plan image overlay
  loadMasterPlanOverlay();

  // Dedicated Layer Groups for Plots & GPS Markers (NO sector polygons on map)
  plotPolygonsLayerGroup = L.layerGroup().addTo(dhaMap);
  plotGpsMarkersLayerGroup = L.layerGroup().addTo(dhaMap);

  // Initialize Right-Side Zoom Controller
  initZoomController();

  // Invalidate sizing at intervals to guarantee smooth rendering
  setTimeout(() => {
    if (dhaMap) dhaMap.invalidateSize();
  }, 60);
  setTimeout(() => {
    if (dhaMap) dhaMap.invalidateSize();
  }, 250);
  setTimeout(() => {
    if (dhaMap) dhaMap.invalidateSize();
  }, 600);

  window.addEventListener("resize", () => {
    if (dhaMap) dhaMap.invalidateSize();
  });
}

// Initialize the vertical zoom slider on the right side
function initZoomController() {
  const slider = document.getElementById("emapZoomSlider");
  const fill = document.getElementById("emapZoomFill");

  function updateZoomUI(zoom) {
    if (slider) slider.value = zoom;
    if (fill) {
      const min = 11;
      const max = 18;
      const pct = Math.min(
        100,
        Math.max(0, ((zoom - min) / (max - min)) * 100),
      );
      fill.style.height = pct + "%";
    }
  }

  if (dhaMap) {
    updateZoomUI(dhaMap.getZoom());

    dhaMap.on("zoom", () => {
      updateZoomUI(dhaMap.getZoom());
    });

    if (slider) {
      slider.addEventListener("input", (e) => {
        const val = parseFloat(e.target.value);
        dhaMap.setZoom(val);
      });
    }
  }
}

function zoomInMap() {
  if (dhaMap) dhaMap.zoomIn();
}

function zoomOutMap() {
  if (dhaMap) dhaMap.zoomOut();
}

// Toggle between Satellite and Street Map via bottom-left thumbnail
function toggleMapTileLayer() {
  if (!dhaMap || !googleSatelliteLayer || !googleStreetLayer) return;

  const tag = document.getElementById("emapLayerTag");
  const thumb = document.getElementById("emapLayerThumb");

  if (currentBaseLayer === "satellite") {
    dhaMap.removeLayer(googleSatelliteLayer);

    if (googleRoadsOverlay && dhaMap.hasLayer(googleRoadsOverlay)) {
      dhaMap.removeLayer(googleRoadsOverlay);
    }

    googleStreetLayer.addTo(dhaMap);
    currentBaseLayer = "streets";

    if (tag) tag.textContent = "Satellite";
    if (thumb) thumb.classList.add("is-streets");

    showEmapToast("Switched to Google Streets Map");
  } else {
    dhaMap.removeLayer(googleStreetLayer);
    googleSatelliteLayer.addTo(dhaMap);
    currentBaseLayer = "satellite";

    const isMasterPlanActive = masterPlanOverlayLayers.some(
      (layer) => layer && dhaMap.hasLayer(layer),
    );

    if (
      googleRoadsOverlay &&
      !isMasterPlanActive &&
      !dhaMap.hasLayer(googleRoadsOverlay)
    ) {
      googleRoadsOverlay.addTo(dhaMap);
    }

    if (tag) tag.textContent = "Streets";
    if (thumb) thumb.classList.remove("is-streets");

    showEmapToast("Switched to Google Satellite Map");
  }
}

// Master Plan Image Overlay Functions (Public & Interactive)
async function loadMasterPlanOverlay() {
  try {
    const res = await fetch("/api/master-plan-overlay.php?" + Date.now());
    const data = await res.json();
    if (data.success && data.overlay) {
      masterPlanOverlayData = data.overlay;
      renderMasterPlanOverlay();
    }
  } catch (err) {
    console.warn("Could not load master plan overlay config:", err);
  }
}

function renderMasterPlanOverlay() {
  if (!dhaMap || !masterPlanOverlayData) return;

  masterPlanOverlayLayers.forEach((layer) => {
    if (layer && dhaMap.hasLayer(layer)) {
      dhaMap.removeLayer(layer);
    }
  });
  masterPlanOverlayLayers = [];

  if (
    masterPlanOverlayData.enabled === false ||
    masterPlanOverlayData.visible_public === false
  ) {
    updateMasterPlanButtonUI(false);

    if (
      currentBaseLayer === "satellite" &&
      googleRoadsOverlay &&
      !dhaMap.hasLayer(googleRoadsOverlay)
    ) {
      googleRoadsOverlay.addTo(dhaMap);
    }
    return;
  }

  const parts = masterPlanOverlayData.parts || [];
  let anyVisible = false;

  parts.forEach((part) => {
    if (part.enabled === false || part.visible_public === false) return;

    const b = part.bounds;
    if (
      !b ||
      !Number.isFinite(Number(b.north)) ||
      !Number.isFinite(Number(b.south)) ||
      !Number.isFinite(Number(b.east)) ||
      !Number.isFinite(Number(b.west))
    ) {
      console.warn("Skipping master plan part with invalid bounds:", part);
      return;
    }

    const partNumber = Number(part.id);
    if (!Number.isInteger(partNumber) || partNumber < 1 || partNumber > 5) {
      console.warn("Skipping master plan part with invalid ID:", part);
      return;
    }

    const layer = L.tileLayer(
      `/assets/tiles/part${partNumber}/{z}/{x}/{y}.png`,
      {
        pane: "masterPlanPane",
        opacity: Number(part.opacity ?? masterPlanOverlayData.opacity ?? 0.85),
        minZoom: 11,
        maxZoom: 20,
        minNativeZoom: 11,
        maxNativeZoom: 18,
        bounds: L.latLngBounds(
          [Number(b.south), Number(b.west)],
          [Number(b.north), Number(b.east)],
        ),
        noWrap: true,
        keepBuffer: 2,
        updateWhenIdle: true,
      },
    );

    layer.addTo(dhaMap);
    masterPlanOverlayLayers.push(layer);
    anyVisible = true;
  });

  if (anyVisible && googleRoadsOverlay && dhaMap.hasLayer(googleRoadsOverlay)) {
    dhaMap.removeLayer(googleRoadsOverlay);
  }

  updateMasterPlanButtonUI(anyVisible);
}

function toggleMasterPlanOverlay() {
  if (!masterPlanOverlayData) {
    loadMasterPlanOverlay();
    return;
  }

  const parts = masterPlanOverlayData.parts || [];
  const anyActive = masterPlanOverlayLayers.some(
    (layer) => layer && dhaMap.hasLayer(layer),
  );

  if (anyActive) {
    // Hide all parts
    masterPlanOverlayLayers.forEach((layer) => {
      if (layer && dhaMap.hasLayer(layer)) {
        dhaMap.removeLayer(layer);
      }
    });
    updateMasterPlanButtonUI(false);
    if (
      currentBaseLayer === "satellite" &&
      googleRoadsOverlay &&
      !dhaMap.hasLayer(googleRoadsOverlay)
    ) {
      googleRoadsOverlay.addTo(dhaMap);
    }
    showEmapToast("DHA Master Plan images hidden");
  } else if (masterPlanOverlayLayers.length > 0) {
    // Show all parts
    masterPlanOverlayLayers.forEach((layer) => {
      if (layer) layer.addTo(dhaMap);
    });
    if (googleRoadsOverlay && dhaMap.hasLayer(googleRoadsOverlay)) {
      dhaMap.removeLayer(googleRoadsOverlay);
    }
    updateMasterPlanButtonUI(true);
    showEmapToast("DHA Master Plan images shown with plot numbers");
  } else {
    // Load and show
    masterPlanOverlayData.enabled = true;
    masterPlanOverlayData.visible_public = true;
    parts.forEach((part) => {
      part.enabled = true;
      part.visible_public = true;
    });
    renderMasterPlanOverlay();
    showEmapToast("DHA Master Plan images enabled");
  }
}

function updateMasterPlanButtonUI(isActive) {
  const btn = document.getElementById("emapPlanBtn");
  if (btn) {
    if (isActive) {
      btn.classList.add("active");
      btn.style.background = "#ecfdf5";
      btn.style.color = "#059669";
    } else {
      btn.classList.remove("active");
      btn.style.background = "#ffffff";
      btn.style.color = "#1e293b";
    }
  }
}

function setMasterPlanOpacity(val) {
  if (!masterPlanOverlayData) return;
  const opacity = parseFloat(val);
  masterPlanOverlayData.opacity = opacity;

  const parts = masterPlanOverlayData.parts || [];
  parts.forEach((part, index) => {
    part.opacity = opacity;
    if (masterPlanOverlayLayers[index]) {
      masterPlanOverlayLayers[index].setOpacity(opacity);
    }
  });
}
window.toggleMasterPlanOverlay = toggleMasterPlanOverlay;
window.setMasterPlanOpacity = setMasterPlanOpacity;

// Left Toolbar Action Handlers
function handleEmapShare() {
  const url = window.location.href;
  if (navigator.clipboard) {
    navigator.clipboard
      .writeText(url)
      .then(() => {
        showEmapToast("🔗 DHA Multan map link copied to clipboard!");
      })
      .catch(() => {
        prompt("Copy this map URL:", url);
      });
  } else {
    prompt("Copy this map URL:", url);
  }
}

function locateDhaCenter() {
  if (dhaMap) {
    dhaMap.flyTo(DHA_MULTAN_CENTER, 14, { duration: 1.2 });
    showEmapToast("📍 Centered on DHA Multan Master Plan");
  }
}

function resetDhaMapView() {
  if (dhaMap) {
    dhaMap.flyTo(DHA_MULTAN_CENTER, DHA_DEFAULT_ZOOM, { duration: 1 });
    showEmapToast("↺ Map view reset to DHA Multan");
  }
}

function toggleEmapInfoModal() {
  const modal = document.getElementById("emapInfoModal");
  if (modal) {
    modal.classList.toggle("active");
  }
}

function closeEmapInfoModal() {
  const modal = document.getElementById("emapInfoModal");
  if (modal) {
    modal.classList.remove("active");
  }
}

function toggleProfileDropdown(e) {
  if (e && e.stopPropagation) e.stopPropagation();
  const dropdown = document.getElementById("emapProfileDropdown");
  if (dropdown) {
    dropdown.classList.toggle("active");
  }
}
window.toggleProfileDropdown = toggleProfileDropdown;
window.toggleEmapProfileDropdown = toggleProfileDropdown;

function openPostPropertyModal() {
  const modal = document.getElementById("postPropertyModal");
  if (modal) {
    modal.classList.add("active");
  }
  const dropdown = document.getElementById("emapProfileDropdown");
  if (dropdown) dropdown.classList.remove("active");
}

function closePostPropertyModal() {
  const modal = document.getElementById("postPropertyModal");
  if (modal) {
    modal.classList.remove("active");
  }
}

function showEmapToast(msg) {
  const toast = document.getElementById("emapToast");
  if (!toast) return;
  toast.textContent = msg;
  toast.classList.add("show");
  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(() => {
    toast.classList.remove("show");
  }, 2800);
}

// Create custom GPS Location Pin Icon for plot
function createGpsPinIcon(prop) {
  const color = prop.status_color || "#10b981";
  return L.divIcon({
    className: "gps-marker-wrapper",
    html: `
      <div class="gps-plot-marker" style="--pin-color: ${color};" title="Click to View ${prop.plot_number} Details">
        <div class="gps-pulse-beacon"></div>
        <div class="gps-pin-svg-wrap">
          <svg class="gps-pin-svg" viewBox="0 0 32 44" width="32" height="44">
            <defs>
              <filter id="gps-shadow-${prop.id}" x="-30%" y="-30%" width="160%" height="160%">
                <feDropShadow dx="0" dy="3" stdDeviation="2.5" flood-color="rgba(0,0,0,0.5)"/>
              </filter>
            </defs>
            <path d="M16 0C7.163 0 0 7.163 0 16c0 11.5 16 28 16 28s16-16.5 16-28c0-8.837-7.163-16-16-16z" fill="${color}" stroke="#ffffff" stroke-width="2" filter="url(#gps-shadow-${prop.id})"/>
            <circle cx="16" cy="15" r="7.5" fill="#ffffff"/>
            <circle cx="16" cy="15" r="4.5" fill="${color}"/>
          </svg>
        </div>
        <div class="gps-plot-badge">
          <span class="gps-badge-dot" style="background-color: ${color};"></span>
          <span>${prop.plot_number}</span>
        </div>
      </div>
    `,
    iconSize: [80, 52],
    iconAnchor: [40, 44],
    popupAnchor: [0, -44],
  });
}

// Render Plot Boundaries and GPS Location Pins on Map
function renderMapProperties(properties) {
  if (!plotPolygonsLayerGroup || !plotGpsMarkersLayerGroup) return;

  plotPolygonsLayerGroup.clearLayers();
  plotGpsMarkersLayerGroup.clearLayers();

  const allLatLngs = [];

  properties.forEach((prop) => {
    const lat = (prop.coordinates && prop.coordinates.lat) || prop.latitude;
    const lng = (prop.coordinates && prop.coordinates.lng) || prop.longitude;

    if (!lat || !lng) return;

    const latLng = [lat, lng];
    allLatLngs.push(latLng);

    // 1. Draw Plot Polygon (if bounds exist)
    const bounds = prop.bounds || prop.polygon_coordinates;
    if (bounds && bounds.length > 2) {
      const polygon = L.polygon(bounds, {
        color: prop.status_color || "#10b981",
        weight: 3,
        opacity: 0.9,
        fillColor: prop.status_color || "#10b981",
        fillOpacity: 0.35,
        className: "emap-plot-polygon",
      });
      polygon.on("click", () => {
        if (dhaMap) {
          dhaMap.flyTo(latLng, 18, { duration: 0.8 });
        }
        showBottomPlotDrawer(prop);
      });
      polygon.addTo(plotPolygonsLayerGroup);
    }

    // 2. Add Custom GPS Pin Marker
    const marker = L.marker(latLng, {
      icon: createGpsPinIcon(prop),
      zIndexOffset: 1000,
    });

    // Store property ID for jump targeting
    marker.propertyId = prop.id;

    // When clicking the GPS pin marker, immediately fly/zoom into the exact property location!
    marker.on("click", () => {
      if (dhaMap) {
        dhaMap.flyTo(latLng, 18, { duration: 0.8 });
      }
      showBottomPlotDrawer(prop);
    });

    marker.addTo(plotGpsMarkersLayerGroup);
  });

  // Fit bounds to show all markers if they exist
  if (allLatLngs.length > 0 && dhaMap) {
    const bounds = L.latLngBounds(allLatLngs);
    // Don't auto-zoom in too far if only one plot
    dhaMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
  }
}

function generatePropertyPopupHtml(prop) {
  const imgSrc = prop.featured_image
    ? "/" + prop.featured_image
    : "/uploads/properties/plot-102.svg";
  return `
    <div class="plot-popup-card">
      <div class="popup-img-wrapper">
        <img src="${imgSrc}" alt="${prop.plot_number}" class="popup-img" onerror="this.src='/uploads/properties/plot-102.svg'">
        <span class="popup-status-badge" style="background-color: ${prop.status_color}">${prop.status}</span>
      </div>
      <div class="popup-body">
        <div class="popup-sector-tag">📍 DHA MULTAN &bull; ${prop.block_name}</div>
        <div class="popup-plot-number">${prop.plot_number}</div>
        <div class="popup-meta">${prop.plot_size} &bull; ${prop.property_type}</div>
        <div class="popup-price">${prop.formatted_price}</div>
        <div class="popup-actions">
          <a href="${prop.whatsapp_url}" target="_blank" class="btn btn-whatsapp btn-sm">💬 WhatsApp</a>
          <a href="${prop.tel_url}" class="btn btn-call btn-sm">📞 Call</a>
          <button type="button" class="btn btn-primary btn-sm popup-btn-full" onclick="openPropertyModal(${prop.id})">
            🔍 Full Plot Details
          </button>
        </div>
      </div>
    </div>
  `;
}

// Focus on a specific property (flyTo + highlight + open drawer)
function focusPropertyOnMap(propertyId) {
  if (!dhaMap) return;

  // Check in GPS markers
  if (plotGpsMarkersLayerGroup) {
    plotGpsMarkersLayerGroup.eachLayer((layer) => {
      if (layer.propertyId === propertyId) {
        dhaMap.flyTo(layer.getLatLng(), 18, { duration: 1.0 });
      }
    });
  }

  // Open drawer
  const propObj = allProperties.find((p) => p.id === propertyId);
  if (propObj) {
    showBottomPlotDrawer(propObj);
  }
}

// Quick jump to a DHA Sector / Sample Plot
function jumpToDhaSector(sectorName) {
  const match = DHA_SECTORS_DATA.find(
    (s) =>
      s.name.toLowerCase() === sectorName.toLowerCase() ||
      s.name.toLowerCase().includes(sectorName.toLowerCase()) ||
      s.id.toLowerCase() === sectorName.toLowerCase() ||
      (s.plot && s.plot.toLowerCase() === sectorName.toLowerCase()),
  );

  if (match && dhaMap) {
    dhaMap.flyTo(match.center, 18, { duration: 1.0 });

    // Find property by plot number or sector name
    const propMatch = allProperties.find(
      (p) =>
        p.plot_number.toLowerCase() === (match.plot || "").toLowerCase() ||
        p.block_name.toLowerCase().includes(match.id.toLowerCase()),
    );

    if (propMatch) {
      setTimeout(() => {
        focusPropertyOnMap(propMatch.id);
      }, 1100);
    }

    // Close filter drawer if open
    closeFilterDrawer();
  }
}

// Slide-up bottom card on plot selection
function showBottomPlotDrawer(prop) {
  const drawer = document.getElementById("bottomPlotDrawer");
  if (!drawer) return;

  const imgSrc = prop.featured_image
    ? "/" + prop.featured_image
    : "/uploads/properties/plot-102.svg";

  drawer.innerHTML = `
    <div class="bottom-drawer-inner">
      <button type="button" class="drawer-close-btn" onclick="closeBottomPlotDrawer()">&times;</button>
      <div class="drawer-media">
        <img src="${imgSrc}" alt="${prop.plot_number}" onerror="this.src='/uploads/properties/plot-102.svg'">
        <span class="drawer-status-badge" style="background-color: ${prop.status_color}">${prop.status}</span>
      </div>
      <div class="drawer-info">
        <div class="drawer-sector">📍 DHA MULTAN &bull; ${prop.block_name}</div>
        <h3 class="drawer-title">${prop.plot_number} &mdash; ${prop.plot_size}</h3>
        <p class="drawer-street">📍 ${prop.street ? prop.street + ", " : ""}${prop.block_name}</p>
        <div class="drawer-price">${prop.formatted_price}</div>
      </div>
      <div class="drawer-actions">
        <a href="${prop.whatsapp_url}" target="_blank" class="btn btn-whatsapp">
          💬 WhatsApp Dealer
        </a>
        <a href="${prop.tel_url}" class="btn btn-secondary">
          📞 Call Dealer
        </a>
        <button type="button" class="btn btn-primary" onclick="openPropertyModal(${prop.id})">
          🔍 Full Details
        </button>
      </div>
    </div>
  `;

  drawer.classList.add("active");
}

function closeBottomPlotDrawer() {
  const drawer = document.getElementById("bottomPlotDrawer");
  if (drawer) drawer.classList.remove("active");
}

// Close profile dropdown when clicking outside
document.addEventListener("click", (e) => {
  const dropdown = document.getElementById("emapProfileDropdown");
  const btn = document.getElementById("emapProfileToggleBtn");
  if (
    dropdown &&
    btn &&
    !btn.contains(e.target) &&
    !dropdown.contains(e.target)
  ) {
    dropdown.classList.remove("active");
  }
});
