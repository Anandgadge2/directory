{{-- ================= GUARD DETAIL MODAL ================= --}}
<div class="modal fade guard-intelligence-modal" id="guardDetailModal" tabindex="-1" style="z-index: 1000002 !important;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">

            {{-- HEADER --}}
            <div class="modal-header bg-primary text-white py-3 border-0">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="bi bi-shield-check fs-4 me-2"></i>
                    Forest Guard Intelligence Profile
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body p-0">
                <div id="guardDetailContent">
                    <div class="py-5 text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3 text-muted fw-semibold">Gleaning insights from the field...</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- INTERNAL INCIDENT DETAIL MODAL --}}
<div class="modal fade" id="guardIncidentInternalModal" tabindex="-1" style="z-index: 1000005 !important;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 15px;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Incident Evidence</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="guardIncidentInternalContent">
                <div class="text-center py-4"><div class="spinner-border text-danger"></div></div>
            </div>
        </div>
    </div>
</div>

{{-- ================= LEAFLET ================= --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let guardMapInstance      = null;
let guardGeofenceGroup    = null;
let guardPathGroup        = null;
let guardCurrentTileLayer = null;
let guardSatelliteLayer   = null;

/* ================= GLOBAL CLICK HANDLER ================= */
document.addEventListener('click', function (e) {
    let link = e.target.closest('.guard-name-link, [data-guard-id], .guard-profile-trigger');

    if (!link && e.target.tagName === 'A') {
        const href       = e.target.getAttribute('href');
        const guardMatch = href?.match(/guard[_-]?details?\/(\d+)/i);
        if (guardMatch) link = e.target;
    }

    if (!link) return;

    const guardId = link.dataset.guardId
        || link.getAttribute('data-guard-id')
        || link.href?.match(/guard[_-]?details?\/(\d+)/i)?.[1];

    if (!guardId) return;

    e.preventDefault();
    e.stopPropagation();

    const modal   = new bootstrap.Modal(document.getElementById('guardDetailModal'));
    const content = document.getElementById('guardDetailContent');

    modal.show();

    content.innerHTML = `
        <div class="py-5 text-center">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3 text-muted">Retrieving intelligence for Forest Guard</p>
        </div>
    `;

    // Collect global filters
    const params    = new URLSearchParams();
    const startDate = document.getElementById('startDateInput')?.value
                   || document.querySelector('[name="start_date"]')?.value;
    const endDate   = document.getElementById('endDateInput')?.value
                   || document.querySelector('[name="end_date"]')?.value;
    const range     = document.querySelector('[name="range"]')?.value;
    const beat      = document.querySelector('[name="beat"]')?.value;

    if (startDate) params.append('start_date', startDate);
    if (endDate)   params.append('end_date',   endDate);
    if (range)     params.append('range',      range);
    if (beat)      params.append('beat',       beat);

    fetch(`/api/guard-details/${guardId}?${params}`)
        .then(r => {
            if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
            return r.json();
        })
        .then(res => {
            if (!res.success) throw new Error('API returned success: false');
            content.innerHTML = renderGuardProfile(res.guard, startDate, endDate);

            setTimeout(() => {
                const paths     = res.guard.patrol_paths || [];
                const geofences = res.geofences          || [];
                initGuardMapSystem(paths, geofences);
            }, 150);
        })
        .catch(err => {
            console.error('Guard profile error:', err);
            content.innerHTML = `
                <div class="p-5 text-center">
                    <div class="alert alert-danger">Unable to load guard profile.</div>
                </div>`;
        });
});

/* ================= RENDERER ================= */
function renderGuardProfile(g, start, end) {
    const a = g.attendance_stats || {};
    const p = g.patrol_stats     || {};
    const i = g.incident_stats   || {};

    // Data period label — mirrors the blue pill in the screenshot
    const periodLabel = a.month || ((start && end) ? `${start} - ${end}` : 'Last 30 Days');

    return `
    <div class="container-fluid p-0 bg-white">

        {{-- DATA PERIOD BANNER (matches screenshot top bar) --}}
        <div class="d-flex align-items-center justify-content-between px-3 py-2"
             style="background:#eef4ff; border-bottom:1px solid #d6e4ff;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar3 text-primary"></i>
                <span class="fw-semibold text-dark" style="font-size:13px;">Data Period:</span>
                <span class="badge rounded-pill px-3 py-1 fw-semibold"
                      style="background:#0d6efd; color:#fff; font-size:12px;">
                    ${periodLabel}
                </span>
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size:12px;">
                <i class="bi bi-info-circle"></i>
                <span>Data filtered according to global filters</span>
            </div>
        </div>

        {{-- FOUR COLUMN STATS ROW — exactly matching screenshot --}}
        <div class="d-flex" style="min-height:200px; border-bottom:1px solid #e9ecef;">

            {{-- 1. PROFILE (black header, white body) --}}
            <div class="d-flex flex-column" style="width:22%; border-right:1px solid #e9ecef;">
                <div class="text-center fw-bold text-white py-2"
                     style="background:#1a1a1a; font-size:14px; letter-spacing:.3px;">
                    Profile
                </div>
                <div class="p-3 small flex-grow-1" style="line-height:1.9; color:#333;">
                    <div><strong>Name:</strong> ${g.name || 'N/A'}</div>
                    <div><strong>Designation:</strong> ${g.designation || 'NA'}</div>
                    <div><strong>Contact:</strong> ${g.contact || 'NA'}</div>
                    <div><strong>Email:</strong> ${g.email || 'NA'}</div>
                    <div><strong>Department:</strong> ${g.company_name || 'N/A'}</div>
                    <div><strong>Range:</strong> ${g.range || 'N/A'}</div>
                    <div><strong>Site:</strong> ${g.site || 'N/A'}</div>
                    <div><strong>Compartment:</strong> ${g.compartment || 'N/A'}</div>
                </div>
            </div>

            {{-- 2. ATTENDANCE (green header) --}}
            <div class="d-flex flex-column" style="width:26%; border-right:1px solid #e9ecef;">
                <div class="text-center fw-bold text-white py-2"
                     style="background:#1a8a44; font-size:14px;">
                    Attendance (${periodLabel})
                </div>
                <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center" style="color:#333;">
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Total Days:</span>
                        <span class="fw-bold" style="font-size:13px;">${a.total_days || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Present:</span>
                        <span class="fw-bold" style="font-size:13px;">${a.present_days || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Absent:</span>
                        <span class="fw-bold" style="font-size:13px;">${a.absent_days || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Late:</span>
                        <span class="fw-bold" style="font-size:13px;">${a.late_days || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="fw-semibold" style="font-size:13px;">Attendance %:</span>
                        <span class="badge fw-bold px-2 py-1"
                              style="background:#1a8a44; color:#fff; font-size:12px;">
                            ${a.attendance_rate || 0}%
                        </span>
                    </div>
                </div>
            </div>

            {{-- 3. PATROL PERFORMANCE (blue header) --}}
            <div class="d-flex flex-column" style="width:26%; border-right:1px solid #e9ecef;">
                <div class="text-center fw-bold text-white py-2"
                     style="background:#0d6efd; font-size:14px;">
                    Patrol Performance
                </div>
                <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center" style="color:#333;">
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Total Sessions:</span>
                        <span class="fw-bold" style="font-size:13px;">${p.total_sessions || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Completed:</span>
                        <span class="fw-bold" style="font-size:13px;">${p.completed_sessions || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Ongoing:</span>
                        <span class="fw-bold" style="font-size:13px;">${p.ongoing_sessions || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="fw-semibold" style="font-size:13px;">Total Distance:</span>
                        <span class="fw-bold" style="font-size:13px;">${p.total_distance_km || 0} km</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1">
                        <span class="fw-semibold" style="font-size:13px;">Avg Distance:</span>
                        <span class="fw-bold" style="font-size:13px;">${p.avg_distance_km || 0} km</span>
                    </div>
                </div>
            </div>

            {{-- 4. INCIDENTS (red header) --}}
            <div class="d-flex flex-column" style="width:26%;">
                <div class="text-center fw-bold text-white py-2"
                     style="background:#dc3545; font-size:14px;">
                    Incidents
                </div>
                <div class="p-3 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                    <div class="fw-bold text-danger" style="font-size:52px; line-height:1;">
                        ${i.total_incidents || 0}
                    </div>
                    <div class="text-muted mt-1" style="font-size:13px;">Total Incidents</div>
                </div>
            </div>

        </div>
        {{-- END OF STATS ROW --}}

        {{-- MAP + TABLE WRAPPER --}}
        <div class="p-3">

        {{-- MAP SECTION --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px; overflow:hidden;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-map-fill me-2 text-primary"></i>
                    Patrol Paths &amp; Compartments
                </h6>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-secondary"   id="guardToggleGeofenceBtn">Hide Compartments</button>
                    <button class="btn btn-sm btn-outline-secondary"   id="guardTogglePathBtn">Hide Paths</button>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-dark active" id="guardMapBtn">Map</button>
                        <button class="btn btn-sm btn-outline-dark"        id="guardSatelliteBtn">Satellite</button>
                    </div>
                    <button class="btn btn-sm btn-outline-success" id="guardFullscreenBtn">
                        <i class="bi bi-fullscreen"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0 position-relative">
                <div id="guardDetailMap" style="height:550px; background:#f0f0f0;"></div>

                {{-- Legend --}}
                <div class="position-absolute bottom-0 start-0 m-3 p-2 bg-white shadow-sm rounded-3 border"
                     style="z-index:1000; opacity:0.95; font-size:11px; min-width:160px;">
                    <div class="fw-bold mb-2 text-uppercase" style="font-size:10px; letter-spacing:.5px;">Legend</div>
                    
                    <div class="d-flex align-items-center mb-1">
                        <span style="width:14px; height:14px; background:rgba(40,167,69,0.15); border:2px dashed #6a1b9a; display:inline-block; margin-right:8px;"></span>
                        Compartment
                    </div>
                    <div class="d-flex align-items-center mb-1">
                        <span style="width:14px; height:2px; background:linear-gradient(to right, #2196f3, #e91e63, #ff9800); display:inline-block; margin-right:8px;"></span>
                        Patrol Paths (Session Colors)
                    </div>
                    <div class="d-flex align-items-center">
                        <span style="width:10px; height:10px; background:#28a745; border-radius:50%; border:2px solid #fff; display:inline-block; margin-right:8px;"></span>
                        Session Start
                    </div>
                </div>
            </div>
        </div>

        {{-- INCIDENT TABLE --}}
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm" style="border-radius:15px;">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold mb-0">Latest Evidence &amp; Incident Logs</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-light">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th>Date &amp; Time</th>
                                        <th>Incident Type</th>
                                        <th>Beat / Compartment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${(i.latest || []).length
                                        ? (i.latest || []).map(item => `
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark">${item.date}</div>
                                                    <small class="text-muted">${item.time}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="bg-danger rounded-circle me-2" style="width:8px; height:8px; display:inline-block;"></span>
                                                        <span class="fw-semibold">${item.type}</span>
                                                    </div>
                                                </td>
                                                <td><small class="text-muted">${item.site_name}</small></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        ${item.priority || 'Normal'}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary rounded-pill px-3"
                                                            onclick="openInternalIncidentDetail('${item.id}')">
                                                        View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')
                                        : `<tr><td colspan="5" class="text-center py-4 text-muted">
                                               No incidents found in this period
                                           </td></tr>`
                                    }
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>{{-- /MAP+TABLE WRAPPER --}}
    </div>{{-- /container-fluid --}}

    <style>
        #guardDetailMap:fullscreen { height: 100vh !important; }
        .extra-small { font-size: 10px; }
    </style>
    `;
}

/* ================= MAP SYSTEM ================= */
function initGuardMapSystem(paths, geofences) {
    const el = document.getElementById('guardDetailMap');
    if (!el) return;

    // Destroy previous instance
    if (guardMapInstance) {
        guardMapInstance.remove();
        guardMapInstance = null;
    }

    guardMapInstance = L.map(el, {
        zoomControl:       true,
        scrollWheelZoom:   false,
        preferCanvas:      true,   // better performance for many paths
    });

    guardCurrentTileLayer = L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        { attribution: '© OpenStreetMap contributors' }
    ).addTo(guardMapInstance);

    guardSatelliteLayer = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { attribution: '© Esri' }
    );

    guardGeofenceGroup = L.featureGroup().addTo(guardMapInstance);
    guardPathGroup     = L.featureGroup().addTo(guardMapInstance);

    /* ---- GEOFENCES ---- */
    if (geofences && geofences.length > 0) {
        geofences.forEach(g => {
            let layer = null;

            const style = {
                color:       '#6a1b9a',
                fillColor:   '#28a745',
                fillOpacity: 0.12,
                weight:      2,
                dashArray:   '6, 4',
            };

            try {
                if (g.type === 'Circle' && g.lat && g.lng) {
                    layer = L.circle([parseFloat(g.lat), parseFloat(g.lng)], {
                        radius: parseFloat(g.radius) || 500,
                        ...style,
                    });

                } else if (g.poly_lat_lng) {
                    // poly_lat_lng can arrive as a JSON string or already an array
                    let coords = g.poly_lat_lng;
                    if (typeof coords === 'string') {
                        coords = JSON.parse(coords);
                    }

                    if (Array.isArray(coords) && coords.length >= 3) {
                        // Support both [{lat,lng}] and [[lat,lng]] formats
                        const latLngs = coords.map(c => {
                            if (Array.isArray(c)) {
                                // GeoJSON order is [lng, lat]
                                return [parseFloat(c[1]), parseFloat(c[0])];
                            }
                            return [parseFloat(c.lat), parseFloat(c.lng)];
                        });
                        layer = L.polygon(latLngs, style);
                    }

                } else if (g.lat && g.lng) {
                    // Fallback: just a point marker for the compartment centre
                    layer = L.circleMarker([parseFloat(g.lat), parseFloat(g.lng)], {
                        radius:      10,
                        color:       '#6a1b9a',
                        fillColor:   '#28a745',
                        fillOpacity: 0.4,
                        weight:      2,
                    });
                }
            } catch (e) {
                console.warn('Geofence render error for', g.name, e);
            }

            if (layer) {
                const label = `
                    <div class="fw-bold small">${g.name}</div>
                    ${g.site_name ? `<div class="text-muted" style="font-size:11px;">${g.site_name}</div>` : ''}
                `;
                layer.bindTooltip(label, { sticky: true, opacity: 0.92 });
                layer.addTo(guardGeofenceGroup);
            }
        });
    }

    /* ---- PATROL PATHS ---- */
    const palette = ['#28a745', '#e91e63', '#9c27b0', '#2196f3', '#00bcd4', '#4caf50', '#ff9800', '#f44336'];
    
    if (paths && paths.length > 0) {
        paths.forEach(p => {
            const geo = normalizeGuardPath(p.path_geojson);
            if (!geo) return;

            // Use session_id to pick a consistent color from the palette, same as KML blade
            const color = palette[p.id % palette.length];
            const distKm = p.distance ? (p.distance / 1000).toFixed(2) : '0.00';

            const pathLayer = L.geoJSON(geo, {
                style: {
                    color:   color,
                    weight:  5,
                    opacity: 0.85,
                    lineCap: 'round',
                    lineJoin: 'round',
                },
            });

            const startTime = p.started_at
                ? new Date(p.started_at).toLocaleString()
                : 'Unknown';
            const endTime = p.ended_at
                ? new Date(p.ended_at).toLocaleString()
                : 'Ongoing';

            pathLayer.bindTooltip(`
                <div class="small p-1" style="min-width:160px;">
                    <strong>${p.session || ('Session #' + p.id)}</strong><br>
                    <span style="color:${color};">■</span> ${p.type || 'Patrol'}<br>
                    🕐 ${startTime}<br>
                    🏁 ${endTime}<br>
                    📏 ${distKm} km
                </div>
            `, { sticky: true, opacity: 0.95 });

            pathLayer.addTo(guardPathGroup);

            // Start marker (green dot)
            if (p.start_lat && p.start_lng) {
                L.circleMarker([parseFloat(p.start_lat), parseFloat(p.start_lng)], {
                    radius:      5,
                    color:       '#fff',
                    weight:      2,
                    fillColor:   '#28a745',
                    fillOpacity: 1,
                })
                .bindTooltip('Session Start', { direction: 'top' })
                .addTo(guardPathGroup);
            }

            // End marker (red dot) if session is completed
            if (p.end_lat && p.end_lng && p.ended_at) {
                L.circleMarker([parseFloat(p.end_lat), parseFloat(p.end_lng)], {
                    radius:      5,
                    color:       '#fff',
                    weight:      2,
                    fillColor:   '#dc3545',
                    fillOpacity: 1,
                })
                .bindTooltip('Session End', { direction: 'top' })
                .addTo(guardPathGroup);
            }
        });
    }

    // Fit all bounds
    guardFitAll();

    // Wire up controls
    initMapControls();
}

/* ================= MAP CONTROLS ================= */
function initMapControls() {
    const mapBtn          = document.getElementById('guardMapBtn');
    const satBtn          = document.getElementById('guardSatelliteBtn');
    const toggleGeoBtn    = document.getElementById('guardToggleGeofenceBtn');
    const togglePathBtn   = document.getElementById('guardTogglePathBtn');
    const fullBtn         = document.getElementById('guardFullscreenBtn');

    if (mapBtn) mapBtn.onclick = () => {
        if (guardMapInstance.hasLayer(guardSatelliteLayer)) {
            guardMapInstance.removeLayer(guardSatelliteLayer);
        }
        if (!guardMapInstance.hasLayer(guardCurrentTileLayer)) {
            guardCurrentTileLayer.addTo(guardMapInstance);
        }
        mapBtn.classList.add('active');
        satBtn.classList.remove('active');
    };

    if (satBtn) satBtn.onclick = () => {
        if (guardMapInstance.hasLayer(guardCurrentTileLayer)) {
            guardMapInstance.removeLayer(guardCurrentTileLayer);
        }
        if (!guardMapInstance.hasLayer(guardSatelliteLayer)) {
            guardSatelliteLayer.addTo(guardMapInstance);
        }
        satBtn.classList.add('active');
        mapBtn.classList.remove('active');
    };

    let geofencesVisible = true;
    if (toggleGeoBtn) toggleGeoBtn.onclick = () => {
        if (geofencesVisible) {
            guardMapInstance.removeLayer(guardGeofenceGroup);
            toggleGeoBtn.innerText = 'Show Compartments';
        } else {
            guardMapInstance.addLayer(guardGeofenceGroup);
            toggleGeoBtn.innerText = 'Hide Compartments';
        }
        geofencesVisible = !geofencesVisible;
    };

    let pathsVisible = true;
    if (togglePathBtn) togglePathBtn.onclick = () => {
        if (pathsVisible) {
            guardMapInstance.removeLayer(guardPathGroup);
            togglePathBtn.innerText = 'Show Paths';
        } else {
            guardMapInstance.addLayer(guardPathGroup);
            togglePathBtn.innerText = 'Hide Paths';
        }
        pathsVisible = !pathsVisible;
    };

    if (fullBtn) fullBtn.onclick = () => {
        const mapEl = document.getElementById('guardDetailMap');
        if (!document.fullscreenElement) {
            mapEl.requestFullscreen().catch(err => console.error(err));
        } else {
            document.exitFullscreen();
        }
    };

    // Ctrl + scroll to zoom
    const mapEl = document.getElementById('guardDetailMap');
    mapEl.addEventListener('wheel', e => {
        if (e.ctrlKey) {
            e.preventDefault();
            guardMapInstance.scrollWheelZoom.enable();
        } else {
            guardMapInstance.scrollWheelZoom.disable();
        }
    }, { passive: false });

    // Invalidate size after modal is fully shown (fixes blank map bug)
    const modalEl = document.getElementById('guardDetailModal');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', () => {
            guardMapInstance && guardMapInstance.invalidateSize();
            guardFitAll();
        }, { once: true });
    }
}

function guardFitAll() {
    if (!guardMapInstance) return;
    try {
        const bounds = L.featureGroup([guardGeofenceGroup, guardPathGroup]).getBounds();
        if (bounds.isValid()) {
            guardMapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
        }
    } catch (e) {
        // No layers yet
    }
}

/* ================= PATH NORMALIZER ================= */
function normalizeGuardPath(raw) {
    let data = raw;
    if (typeof data === 'string') {
        try { data = JSON.parse(data); } catch (e) { return null; }
    }
    if (!data) return null;

    // Already a GeoJSON LineString
    if (data.type === 'LineString' && Array.isArray(data.coordinates)) {
        return data;
    }

    // Array of {lat, lng} or [lng, lat]
    if (Array.isArray(data)) {
        const coords = data
            .map(p => {
                if (Array.isArray(p))  return [Number(p[0]), Number(p[1])];  // [lng, lat]
                if (p.lng && p.lat)    return [Number(p.lng), Number(p.lat)];
                return null;
            })
            .filter(Boolean);
        if (coords.length >= 2) {
            return { type: 'LineString', coordinates: coords };
        }
    }

    return null;
}

/* ================= INCIDENT DETAILS ================= */
function openInternalIncidentDetail(id) {
    const modal   = new bootstrap.Modal(document.getElementById('guardIncidentInternalModal'));
    const content = document.getElementById('guardIncidentInternalContent');

    modal.show();
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-danger"></div>
            <p class="mt-2">Pulling case file...</p>
        </div>`;

    fetch(`/incidents/${id}/details`)
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const inc      = res.incident;
            const comments = res.comments || [];
            if (!inc) throw new Error('Data missing');

            content.innerHTML = `
                <div class="row g-4">
                    <div class="col-md-7">
                        <div class="badge bg-danger mb-2 text-uppercase fw-bold p-2">${inc.type}</div>
                        <h4 class="fw-bold text-dark mb-1">${inc.beat_name || 'N/A'}</h4>
                        <div class="text-muted small mb-3">
                            <i class="bi bi-calendar-event me-1"></i>
                            ${new Date(inc.created_at).toLocaleString()}
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-4" style="border-left:4px solid #dc3545;">
                            <h6 class="fw-bold mb-2">Observation Remarks</h6>
                            <p class="mb-0 text-dark">${inc.notes || inc.details_remark || 'No description provided.'}</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="p-2 border rounded bg-white">
                                    <small class="text-muted d-block text-uppercase extra-small fw-bold">Forest Guard On Duty</small>
                                    <div class="fw-semibold">${inc.guard_name || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-white">
                                    <small class="text-muted d-block text-uppercase extra-small fw-bold">Priority Level</small>
                                    <div class="fw-semibold text-danger">${inc.priority || 'Normal'}</div>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Supervisor Review &amp; Comments</h6>
                        <div class="pe-2" style="max-height:200px; overflow-y:auto;">
                            ${comments.length
                                ? comments.map(c => `
                                    <div class="mb-3 p-2 rounded border-start border-warning border-3" style="background:#fffef0;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="fw-bold text-dark">${c.user_name || 'System'}</small>
                                            <small class="text-muted">${new Date(c.created_at).toLocaleDateString()}</small>
                                        </div>
                                        <div class="small text-secondary">${c.comment}</div>
                                    </div>
                                `).join('')
                                : '<p class="text-muted small fst-italic">No supervisor comments yet.</p>'
                            }
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card border-0 shadow-sm bg-dark text-white mb-3"
                             style="border-radius:12px; min-height:220px; overflow:hidden;">
                            ${inc.photo
                                ? `<img src="${inc.photo}" class="card-img-top"
                                        style="height:100%; width:100%; object-fit:cover;"
                                        onerror="this.src='/assets/img/placeholder-incident.jpg'">`
                                : `<div class="d-flex flex-column align-items-center justify-content-center h-100 p-5">
                                       <i class="bi bi-camera-video-off fs-1 mb-2"></i>
                                       <div class="small text-muted">No visual evidence attached</div>
                                   </div>`
                            }
                        </div>

                        <div class="card border-0 shadow-sm p-3 bg-light" style="border-radius:12px;">
                            <h6 class="fw-bold mb-2 small">
                                <i class="bi bi-geo-alt-fill me-1 text-danger"></i>Coordinates
                            </h6>
                            <div class="d-flex align-items-center justify-content-between p-2 bg-white border rounded">
                                <code class="text-dark">
                                    ${inc.lat ? Number(inc.lat).toFixed(6) : '--'},
                                    ${inc.lng ? Number(inc.lng).toFixed(6) : '--'}
                                </code>
                                <a href="https://www.google.com/maps?q=${inc.lat},${inc.lng}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary py-0 px-2">
                                    <i class="bi bi-map"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            console.error('Incident detail error:', err);
            content.innerHTML = `
                <div class="alert alert-danger p-4 text-center">
                    <i class="bi bi-exclamation-octagon fs-2 d-block mb-3"></i>
                    Error loading incident details. The record might be restricted or missing.
                </div>`;
        });
}
</script>

<style>
.guard-intelligence-modal .modal-content {
    background: #f8f9fa;
}
.guard-intelligence-modal .modal-header.bg-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #004fb0 100%) !important;
}
.guard-intelligence-modal .table thead th {
    font-weight: 700;
    border-bottom: 2px solid #dee2e6;
}
.guard-intelligence-modal .progress-bar {
    border-radius: 10px;
}
#guardDetailMap:fullscreen {
    height: 100vh !important;
}
.extra-small {
    font-size: 10px;
}
</style>