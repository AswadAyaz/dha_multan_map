/**
 * Admin Interactive Leaflet Map for Plot Location & Polygon Drawing
 * Includes Master Plan Overlay + Zoom Controls
 */

let adminMap = null;
let currentMarker = null;
let currentPolygon = null;
let polygonPoints = [];
let drawMode = 'marker';
let adminOverlayLayer = null;

const SECTOR_COORDINATES = {
  '1': [30.2980, 71.5510],
  '2': [30.3040, 71.5540],
  '3': [30.3070, 71.5560],
  '4': [30.3090, 71.5580],
  '5': [30.3020, 71.5620],
  '6': [30.2950, 71.5660],
  '7': [30.2880, 71.5720],
  '8': [30.2920, 71.5590],
  '9': [30.2860, 71.5620],
  '10': [30.2810, 71.5670],
  '11': [30.3000, 71.5710],
  '12': [30.3030, 71.5770],
  '13': [30.3070, 71.5670],
  '14': [30.3120, 71.5750],
  '15': [30.3150, 71.5620],
  '16': [30.3180, 71.5540],
  '17': [30.3120, 71.5480],
  '18': [30.3160, 71.5420],
  '19': [30.3200, 71.5420],
  '20': [30.3060, 71.5440],
  '21': [30.3000, 71.5400],
  '22': [30.3015, 71.5570],
};

function initAdminMap(existingLat, existingLng, existingPolygonJson) {
  const defaultLat = existingLat || 30.2982;
  const defaultLng = existingLng || 71.5512;
  const defaultZoom = existingLat ? 17 : 14;

  adminMap = L.map('adminMapContainer', {
    center: [defaultLat, defaultLng],
    zoom: defaultZoom,
    maxZoom: 19,
    minZoom: 12,
    zoomControl: true
  });

  // Satellite layer (Google)
  const satelliteLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
    attribution: 'Google Satellite',
    maxZoom: 19
  });

  // Street layer (OSM)
  const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  });

  satelliteLayer.addTo(adminMap);

  // Layer control
  L.control.layers({
    '🛰️ Satellite (Google)': satelliteLayer,
    '🗺️ Street (OSM)': streetLayer
  }, null, { position: 'topright' }).addTo(adminMap);

  // Zoom control (default top-left)
  adminMap.zoomControl.setPosition('topleft');

  // Set existing Marker
  if (existingLat && existingLng) {
    setMarker(existingLat, existingLng);
  }

  // Set existing Polygon
  if (existingPolygonJson) {
    try {
      const coords = typeof existingPolygonJson === 'string' ? JSON.parse(existingPolygonJson) : existingPolygonJson;
      if (Array.isArray(coords) && coords.length >= 3) {
        polygonPoints = coords;
        renderPolygon();
        setMode('polygon');
      }
    } catch (e) {
      console.warn('Could not parse initial polygon:', e);
    }
  }

  // Map click handler
  adminMap.on('click', function(e) {
    const lat = Number(e.latlng.lat.toFixed(6));
    const lng = Number(e.latlng.lng.toFixed(6));

    if (drawMode === 'marker') {
      setMarker(lat, lng);
    } else if (drawMode === 'polygon') {
      polygonPoints.push([lat, lng]);
      renderPolygon();
      if (polygonPoints.length === 1) {
        setMarker(lat, lng);
      }
    }
  });

  // Block selection jump
  const blockSelect = document.getElementById('block_id');
  if (blockSelect) {
    blockSelect.addEventListener('change', function() {
      const blockId = this.value;
      if (SECTOR_COORDINATES[blockId]) {
        adminMap.flyTo(SECTOR_COORDINATES[blockId], 16);
      }
    });
  }

  // Load master plan overlay
  loadAdminOverlay();
}

async function loadAdminOverlay() {
  try {
    const res = await fetch('/api/master-plan-overlay.php');
    const data = await res.json();
    if (data.success && data.overlay && data.overlay.enabled !== false) {
      const b = data.overlay.bounds;
      adminOverlayLayer = L.imageOverlay(data.overlay.image_url.startsWith('/') ? data.overlay.image_url : '/' + data.overlay.image_url, [[b.south, b.west], [b.north, b.east]], {
        opacity: data.overlay.opacity || 0.85,
        interactive: false
      });
      adminOverlayLayer.addTo(adminMap);
    }
  } catch (err) {
    console.warn('Could not load master plan overlay:', err);
  }
}

function setMode(mode) {
  drawMode = mode;
  document.getElementById('modeMarkerBtn')?.classList.toggle('active', mode === 'marker');
  document.getElementById('modePolyBtn')?.classList.toggle('active', mode === 'polygon');
  const instructions = document.getElementById('mapInstructions');
  if (instructions) {
    if (mode === 'marker') {
      instructions.innerHTML = '👉 <strong>Marker Mode:</strong> Click anywhere on the map to place or move the plot location marker.';
    } else {
      instructions.innerHTML = '👉 <strong>Polygon Mode:</strong> Click 3 or more points to outline the plot boundary corners. Click "Clear Polygon" to restart.';
    }
  }
}

function setMarker(lat, lng) {
  if (currentMarker) {
    currentMarker.setLatLng([lat, lng]);
  } else {
    currentMarker = L.marker([lat, lng], { draggable: true }).addTo(adminMap);
    currentMarker.on('dragend', function(e) {
      const pos = e.target.getLatLng();
      updateCoordinatesFields(pos.lat, pos.lng);
    });
  }
  updateCoordinatesFields(lat, lng);
}

function updateCoordinatesFields(lat, lng) {
  const latField = document.getElementById('latitude');
  const lngField = document.getElementById('longitude');
  if (latField) latField.value = lat.toFixed(6);
  if (lngField) lngField.value = lng.toFixed(6);
}

function renderPolygon() {
  if (currentPolygon) {
    adminMap.removeLayer(currentPolygon);
    currentPolygon = null;
  }

  if (polygonPoints.length >= 2) {
    currentPolygon = L.polygon(polygonPoints, {
      color: '#10b981',
      fillColor: '#10b981',
      fillOpacity: 0.45,
      weight: 3
    }).addTo(adminMap);
  }

  const polyField = document.getElementById('polygon_coordinates');
  if (polyField) {
    polyField.value = polygonPoints.length >= 3 ? JSON.stringify(polygonPoints) : '';
  }

  const pointsCountEl = document.getElementById('polyPointsCount');
  if (pointsCountEl) {
    pointsCountEl.innerText = `${polygonPoints.length} points set`;
  }
}

function clearPolygon() {
  polygonPoints = [];
  if (currentPolygon) {
    adminMap.removeLayer(currentPolygon);
    currentPolygon = null;
  }
  const polyField = document.getElementById('polygon_coordinates');
  if (polyField) polyField.value = '';
  const pointsCountEl = document.getElementById('polyPointsCount');
  if (pointsCountEl) pointsCountEl.innerText = '0 points';
}

function resetToDHACenter() {
  if (adminMap) {
    adminMap.flyTo([30.2982, 71.5512], 14);
  }
}
