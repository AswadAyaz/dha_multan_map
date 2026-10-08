/**
 * DHA Multan Interactive Property Map Application Controller
 * Handles live map updates, dynamic filters, sector quick jumps,
 * plot search, slide-up drawers, and list view switcher.
 */

let allProperties = [];
let currentFilters = {
  search: "",
  property_type: "",
  status: "",
  phase_id: "",
  block_id: "",
  plot_size: "",
  min_price: "",
  max_price: "",
};

document.addEventListener("DOMContentLoaded", () => {
  // 1. Initialize Map
  initDHAMap();

  // 2. Load Sector options
  loadPhasesAndBlocks();

  // 3. Fetch initial properties
  fetchProperties();

  // 4. Setup listeners
  setupEventListeners();

  // 5. Force Leaflet invalidateSize after layout is painted
  setTimeout(() => {
    if (typeof dhaMap !== "undefined" && dhaMap) {
      dhaMap.invalidateSize();
    }
  }, 100);

  setTimeout(() => {
    if (typeof dhaMap !== "undefined" && dhaMap) {
      dhaMap.invalidateSize();
    }
  }, 400);

  // Check URL params for direct plot focus
  const urlParams = new URLSearchParams(window.location.search);
  const plotParam = urlParams.get("plot") || urlParams.get("id");
  if (plotParam) {
    currentFilters.search = plotParam;
    const searchInput = document.getElementById("headerSearchInput");
    if (searchInput) searchInput.value = plotParam;
  }
});

function setupEventListeners() {
  // Global Header Search
  const headerSearchInput = document.getElementById("headerSearchInput");
  if (headerSearchInput) {
    headerSearchInput.addEventListener(
      "input",
      debounce((e) => {
        currentFilters.search = e.target.value.trim();
        fetchProperties();
      }, 250),
    );
  }

  // Drawer Search
  const drawerSearchInput = document.getElementById("drawerSearchInput");
  if (drawerSearchInput) {
    drawerSearchInput.addEventListener(
      "input",
      debounce((e) => {
        currentFilters.search = e.target.value.trim();
        const hInput = document.getElementById("headerSearchInput");
        if (hInput) hInput.value = e.target.value;
        fetchProperties();
      }, 250),
    );
  }

  // Sector Quick Nav Pills (Horizontal Bar)
  document.querySelectorAll(".sector-nav-pill").forEach((pill) => {
    pill.addEventListener("click", function () {
      const sectorName = this.getAttribute("data-sector");
      document
        .querySelectorAll(".sector-nav-pill")
        .forEach((p) => p.classList.remove("active"));
      this.classList.add("active");

      if (!sectorName || sectorName.toLowerCase() === "all") {
        currentFilters.block_id = "";
        currentFilters.search = "";
        const blockSelect = document.getElementById("filter_block_id");
        if (blockSelect) blockSelect.value = "";
        resetDhaMapView();
        fetchProperties();
      } else {
        jumpToDhaSector(sectorName);
      }
    });
  });

  // Filter selects inside drawer
  [
    "property_type",
    "phase_id",
    "block_id",
    "plot_size",
    "min_price",
    "max_price",
  ].forEach((id) => {
    const el = document.getElementById("filter_" + id);
    if (el) {
      el.addEventListener("change", (e) => {
        currentFilters[id] = e.target.value;
        fetchProperties();
      });
    }
  });

  // Status Filter Pills inside drawer
  document.querySelectorAll(".status-pill").forEach((pill) => {
    pill.addEventListener("click", function () {
      const statusVal = this.getAttribute("data-status");
      if (this.classList.contains("active")) {
        this.classList.remove("active");
        currentFilters.status = "";
      } else {
        document
          .querySelectorAll(".status-pill")
          .forEach((p) => p.classList.remove("active"));
        this.classList.add("active");
        currentFilters.status = statusVal;
      }
      fetchProperties();
    });
  });
}

// Toggle Filter Drawer
function toggleFilterDrawer() {
  const drawer = document.getElementById("filterDrawer");
  if (!drawer) return;
  drawer.classList.toggle("open");
}

function closeFilterDrawer() {
  const drawer = document.getElementById("filterDrawer");
  if (drawer) drawer.classList.remove("open");
}

async function loadPhasesAndBlocks() {
  try {
    const res = await fetch("/api/phases.php?" + Date.now());
    const data = await res.json();
    if (data.success) {
      const blockSelect = document.getElementById("filter_block_id");
      if (blockSelect) {
        blockSelect.innerHTML =
          '<option value="">All Sectors / Blocks</option>';
        data.blocks.forEach((b) => {
          const opt = document.createElement("option");
          opt.value = b.id;
          opt.textContent = `${b.name} (${b.sector_type || "Residential"})`;
          blockSelect.appendChild(opt);
        });
      }
    }
  } catch (err) {
    console.warn("Could not load phases/blocks:", err);
  }
}

async function fetchProperties() {
  try {
    const params = new URLSearchParams();
    for (const key in currentFilters) {
      if (currentFilters[key]) {
        params.append(key, currentFilters[key]);
      }
    }

    const res = await fetch(
      "/api/properties.php?" + params.toString() + "&t=" + Date.now(),
    );
    const data = await res.json();

    if (data.success && Array.isArray(data.data)) {
      allProperties = data.data;
    } else {
      allProperties = [];
    }

    // Update Map View
    renderMapProperties(allProperties);

    // Update List View
    renderListView(allProperties);

    // Update Count in status badges
    const countEl = document.getElementById("resultsCount");
    if (countEl) {
      countEl.textContent = `${allProperties.length} Verified Plots`;
    }

    const mapCountBadge = document.getElementById("mapCountBadge");
    if (mapCountBadge) {
      mapCountBadge.textContent = `${allProperties.length} Plots On Map`;
    }

    // If user searched for an exact plot, auto-focus it
    if (currentFilters.search && allProperties.length > 0) {
      const exactMatch = allProperties.find(
        (p) =>
          p.plot_number.toLowerCase() === currentFilters.search.toLowerCase() ||
          p.plot_number
            .toLowerCase()
            .includes(currentFilters.search.toLowerCase()),
      );
      if (exactMatch) {
        focusPropertyOnMap(exactMatch.id);
      }
    }
  } catch (err) {
    console.error("Error fetching properties:", err);
    allProperties = [];
    renderMapProperties(allProperties);
    renderListView(allProperties);
  }
}

function renderListView(properties) {
  const container = document.getElementById("propertiesGrid");
  if (!container) return;

  if (properties.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: #64748b;">
        <h3 style="font-size: 1.25rem; font-weight: 700; color: #0f172a;">No matching plots found in DHA Multan</h3>
        <p style="margin-top: 0.5rem;">Try clearing your filters or selecting another sector.</p>
        <button type="button" class="btn btn-primary mt-3" onclick="resetFilters()" style="margin-top: 1rem;">
          Reset All Filters
        </button>
      </div>
    `;
    return;
  }

  container.innerHTML = properties
    .map((p) => {
      const imgSrc = p.featured_image
        ? "/" + p.featured_image
        : "/uploads/properties/plot-102.svg";
      return `
      <div class="property-card">
        <div class="card-media">
          <img src="${imgSrc}" alt="${p.plot_number}" onerror="this.src='/uploads/properties/plot-102.svg'">
          <span class="card-badge" style="background-color: ${p.status_color}">${p.status}</span>
          <span class="card-boundary-tag">${p.has_polygon ? "📐 Polygon Boundary" : "📍 Map Pin"}</span>
        </div>
        <div class="card-content">
          <div class="card-header-sector">DHA MULTAN &bull; ${p.block_name}</div>
          <h3 class="card-plot-title">${p.title} (${p.plot_number})</h3>
          <div class="card-specs-row">
            <span class="spec-item">📏 ${p.plot_size}</span>
            <span class="spec-item">🏷 ${p.property_type}</span>
            <span class="spec-item">📍 ${p.street || "Phase 1"}</span>
          </div>
          <div class="card-price">${p.formatted_price}</div>
          <div class="card-footer-buttons">
            <button type="button" class="btn btn-secondary btn-sm" onclick="locateOnMapFromList(${p.id})">
              🗺 Locate on Map
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="openPropertyModal(${p.id})">
              View Details
            </button>
          </div>
        </div>
      </div>
    `;
    })
    .join("");
}

function switchView(mode) {
  const leafletMap = document.getElementById("leafletMap");
  const listContainer = document.getElementById("listViewContainer");
  const mapBtn = document.getElementById("viewMapBtn");
  const listBtn = document.getElementById("viewListBtn");
  const leftToolbar = document.querySelector(".emap-left-toolbar");
  const zoomPanel = document.querySelector(".emap-right-zoom-panel");
  const layerBox = document.querySelector(".emap-layer-box");
  const bottomDrawer = document.getElementById("bottomPlotDrawer");

  if (mode === "map") {
    if (leafletMap) leafletMap.style.display = "block";
    if (listContainer) listContainer.classList.remove("active");
    if (leftToolbar) leftToolbar.style.display = "flex";
    if (zoomPanel) zoomPanel.style.display = "flex";
    if (layerBox) layerBox.style.display = "block";
    mapBtn?.classList.add("active");
    listBtn?.classList.remove("active");
    closeFilterDrawer();
    if (typeof dhaMap !== "undefined" && dhaMap) {
      setTimeout(() => dhaMap.invalidateSize(), 50);
    }
  } else {
    if (leafletMap) leafletMap.style.display = "none";
    if (listContainer) listContainer.classList.add("active");
    if (leftToolbar) leftToolbar.style.display = "none";
    if (zoomPanel) zoomPanel.style.display = "none";
    if (layerBox) layerBox.style.display = "none";
    if (bottomDrawer) bottomDrawer.classList.remove("open");
    mapBtn?.classList.remove("active");
    listBtn?.classList.add("active");
    closeFilterDrawer();
  }
}

function openPostPropertyModal() {
  const modal = document.getElementById("postPropertyModal");
  if (modal) modal.classList.add("active");
  const dropdown = document.getElementById("emapProfileDropdown");
  if (dropdown) dropdown.classList.remove("active");
}

function closePostPropertyModal() {
  const modal = document.getElementById("postPropertyModal");
  if (modal) modal.classList.remove("active");
}

function locateOnMapFromList(propertyId) {
  switchView("map");
  focusPropertyOnMap(propertyId);
}

function resetFilters() {
  currentFilters = {
    search: "",
    property_type: "",
    status: "",
    phase_id: "",
    block_id: "",
    plot_size: "",
    min_price: "",
    max_price: "",
  };

  const headerSearchInput = document.getElementById("headerSearchInput");
  if (headerSearchInput) headerSearchInput.value = "";
  const drawerSearchInput = document.getElementById("drawerSearchInput");
  if (drawerSearchInput) drawerSearchInput.value = "";

  [
    "property_type",
    "phase_id",
    "block_id",
    "plot_size",
    "min_price",
    "max_price",
  ].forEach((id) => {
    const el = document.getElementById("filter_" + id);
    if (el) el.value = "";
  });

  document
    .querySelectorAll(".status-pill")
    .forEach((p) => p.classList.remove("active"));
  document.querySelectorAll(".sector-nav-pill").forEach((p) => {
    if (p.getAttribute("data-sector") === "all") {
      p.classList.add("active");
    } else {
      p.classList.remove("active");
    }
  });

  fetchProperties();
  resetDhaMapView();
}

// Property Details Modal
async function openPropertyModal(id) {
  try {
    const res = await fetch("/api/property.php?id=" + id + "&t=" + Date.now());
    const result = await res.json();
    if (!result.success) return;

    const p = result.data;
    const modalContent = document.getElementById("propertyModalContent");
    const modal = document.getElementById("propertyDetailModal");

    const imgSrc = p.featured_image
      ? "/" + p.featured_image
      : "/uploads/properties/plot-102.svg";

    modalContent.innerHTML = `
      <div class="detail-gallery">
        <img src="${imgSrc}" alt="${p.title}" onerror="this.src='/uploads/properties/plot-102.svg'">
      </div>
      <div class="detail-body">
        <div class="detail-header-row">
          <div>
            <div class="popup-sector-tag">DHA MULTAN &bull; ${p.phase_name} &bull; ${p.block_name}</div>
            <h2 class="detail-title">${p.title} (${p.plot_number})</h2>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
              Street: ${p.street || "Main Avenue"}
            </div>
          </div>
          <div style="text-align: right;">
            <div class="detail-price">${p.formatted_price}</div>
            <span class="card-badge" style="position: static; background-color: ${p.status_color}; font-size: 0.8rem; margin-top: 0.35rem; display: inline-block;">
              ${p.status}
            </span>
          </div>
        </div>

        <div class="detail-specs-grid">
          <div>
            <div class="spec-box-label">Plot Number</div>
            <div class="spec-box-value">${p.plot_number}</div>
          </div>
          <div>
            <div class="spec-box-label">Property Size</div>
            <div class="spec-box-value">${p.plot_size}</div>
          </div>
          <div>
            <div class="spec-box-label">Property Type</div>
            <div class="spec-box-value">${p.property_type}</div>
          </div>
          <div>
            <div class="spec-box-label">Map Mapping</div>
            <div class="spec-box-value">${p.has_polygon ? "📐 Polygon" : "📍 Marker"}</div>
          </div>
        </div>

        <div class="detail-desc-box">
          <h4>Property Description</h4>
          <p>${p.description || "Prime verified property available in DHA Multan."}</p>
        </div>

        <div class="detail-realtor-card">
          <div class="realtor-info">
            <h5>${p.owner_dealer_name || "Authorized DHA Multan Realtor"}</h5>
            <p>Verified Property Dealer &bull; DHA Multan Registered</p>
          </div>
          <div class="realtor-contact-buttons">
            <a href="${p.tel_url}" class="btn btn-call btn-sm">
              📞 Call ${p.phone}
            </a>
            <a href="${p.whatsapp_url}" target="_blank" class="btn btn-whatsapp btn-sm">
              💬 WhatsApp
            </a>
            <a href="/property.php?id=${p.id}" target="_blank" class="btn btn-secondary btn-sm">
              🔗 Dedicated Page
            </a>
          </div>
        </div>
      </div>
    `;

    modal.classList.add("active");
  } catch (err) {
    console.error("Failed to open property details:", err);
  }
}

function closePropertyModal() {
  const modal = document.getElementById("propertyDetailModal");
  if (modal) modal.classList.remove("active");
}

function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}
