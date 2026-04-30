@extends('layouts.app')

@section('content')



    {{-- ================= TITLE & HEADER ================= --}}
    <!-- <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white p-4 shadow-sm border-0 rounded-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1" style="color: #1e293b; letter-spacing: -0.5px;">Patrol Analysis</h2>
                    <p class="text-muted mb-0 small">Real-time geospatial tracking and session analytics</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill">
                        <i class="bi bi-clock-history me-1"></i> Live Tracking
                    </span>
                </div>
            </div>
        </div>
    </div> -->

    {{-- ================= KPIs ================= --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 kpi-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Sessions</h6>
                            <h3 class="mb-0 fw-bold text-dark">{{ $stats['total_sessions'] }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                            <i class="bi bi-collection-play-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 kpi-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-2">Completed</h6>
                            <h3 class="mb-0 fw-bold text-dark text-success">{{ $stats['completed_sessions'] }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 kpi-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-2">Ongoing</h6>
                            <h3 class="mb-0 fw-bold text-dark text-warning">{{ $stats['active_sessions'] }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                            <i class="bi bi-play-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 kpi-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Distance</h6>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($stats['total_distance_km'], 1) }} <small class="fw-normal fs-6 text-muted">km</small></h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- <form method="GET" class="d-flex gap-2 mb-3 align-items-end">
            <div>
                <label class="small text-muted">Guard</label>
                <select name="user" class="form-select form-select-sm">
                    <option value="">All Guards</option>
                    @foreach($guardList as $u)
                        <option value="{{ $u->id }}" {{ request('user') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="sort" value="distance_desc">

            <button class="btn btn-sm btn-primary">
                Apply
            </button>
        </form> -->


    {{-- ================= MAP AND SIDEBAR ================= --}}
    <div class="row g-3">
        {{-- ================= MAP SECTION ================= --}}
        <div class="col-lg-9 position-relative">
            <div class="card p-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0">Patrol Analysis</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" id="showAllBtn">Show All</button>
                        <button class="btn btn-sm btn-outline-secondary" id="hideGeofencesBtn">Hide Compartments</button>
                        <button class="btn btn-sm btn-outline-success" id="fullscreenBtn" title="Toggle Fullscreen">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-secondary" id="resizeSmallBtn" title="Small Size">
                                <i class="bi bi-square"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary active" id="resizeMediumBtn"
                                title="Medium Size">
                                <i class="bi bi-square-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="resizeLargeBtn" title="Large Size">
                                <i class="bi bi-square-fill" style="font-size: 1.2em;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Map Tabs --}}
                <div class="btn-group mb-2" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary active" id="mapTab">Map</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="satelliteTab">Satellite</button>
                </div>

                <div id="patrol-map-container" class="position-relative">
                    <div id="patrol-map" style="height:650px;width:100%;border-radius:8px;"></div>
                </div>


            </div>
        </div>

        {{-- ================= SIDEBAR SESSIONS LIST ================= --}}
        <div class="col-lg-3">
            <div class="card p-2 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0">Patrol Sessions</h6>

                    <select id="sortSessions" class="form-select form-select-sm w-auto">
                        <option value="">Sort</option>
                        <option value="distance_desc">Distance ↓</option>
                        <option value="distance_asc">Distance ↑</option>
                    </select>
                </div>
                <div id="sessionsList" style="max-height:720px;overflow-y:auto;">
                    @forelse($sessions as $s)
                        @if($s->path_geojson)
                            @php
                                // Generate session indicator color based on session ID
                                $colors = ['#28a745', '#e91e63', '#9c27b0', '#2196f3', '#00bcd4', '#4caf50', '#ff9800', '#f44336'];
                                $indicatorColor = $colors[$s->session_id % count($colors)];
                            @endphp
                            <div class="session-card mb-3 p-3 border rounded shadow-sm" data-session-id="{{ $s->session_id }}"
                                data-user-id="{{ $s->user_id }}" data-status="{{ $s->status }}" data-color="{{ $indicatorColor }}"
                                style="cursor: pointer; transition: all 0.2s; background: white;">
                                {{-- Session Header with Indicator --}}
                                <div class="d-flex align-items-center mb-3">
                                    <div class="session-indicator me-2"
                                        style="width: 14px; height: 14px; border-radius: 50%; background: {{ $indicatorColor }}; flex-shrink: 0;">
                                    </div>
                                    <strong class="text-primary" style="font-size: 0.95rem;">Session #{{ $s->session_id }}</strong>
                                </div>

                                {{-- User Profile Picture and Name --}}
                                <div class="d-flex align-items-center mb-3">
                                    @if($s->user_profile)
                                        @php
                                            // Handle different profile picture path formats
                                            $profilePic = $s->user_profile;
                                            if (strpos($profilePic, 'http') === 0) {
                                                $profileUrl = $profilePic; // Full URL
                                            } else {
                                                $profileUrl = asset('storage/profiles/' . $profilePic);
                                            }
                                        @endphp
                                        <img src="{{ $profileUrl }}" class="rounded-circle me-3 border-2 user-profile-img"
                                            style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6 !important;"
                                            alt="{{ $s->user_name }}"
                                            onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                    @else
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3 border-2"
                                            style="width: 50px; height: 50px; border: 2px solid #dee2e6 !important;">
                                            <span class="text-white fw-bold"
                                                style="font-size: 1.2rem;">{{ strtoupper(substr($s->user_name, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                    <div class="grow">
                                        <a href="#" class="guard-name-link text-decoration-none fw-bold d-block mb-1"
                                            data-guard-id="{{ $s->user_id }}" style="color: #212529; font-size: 0.95rem;">
                                            {{ \App\Helpers\FormatHelper::formatName($s->user_name) }}
                                        </a>
                                        <div class="text-muted small" style="font-size: 0.8rem;">
                                            {{ $s->site_name ? $s->site_name . ($s->range_name ? ' (' . $s->range_name . ')' : '') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Status, Time, and Distance Buttons --}}
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-success" style="font-size: 0.75rem; padding: 0.4em 0.6em;">
                                        {{ $s->status }}
                                    </span>
                                    <span class="badge bg-success" style="font-size: 0.75rem; padding: 0.4em 0.6em;">
                                        {{ \Carbon\Carbon::parse($s->started_at)->format('d M H:i') }}
                                    </span>
                                    @if($s->ended_at)
                                        <span class="badge bg-danger" style="font-size: 0.75rem; padding: 0.4em 0.6em;">
                                            {{ \Carbon\Carbon::parse($s->ended_at)->format('d M H:i') }}
                                        </span>
                                    @endif
                                    <span class="badge bg-primary" style="font-size: 0.75rem; padding: 0.4em 0.6em;">
                                        {{ number_format($s->distance_km, 2) }} km
                                    </span>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-primary zoom-session-btn flex-fill"
                                        data-session-id="{{ $s->session_id }}" data-user-id="{{ $s->user_id }}"
                                        style="font-size: 0.85rem;">
                                        <i class="bi bi-zoom-in"></i> Zoom
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info text-white view-session-btn flex-fill"
                                        data-session-id="{{ $s->session_id }}" style="font-size: 0.85rem;">
                                        <i class="bi bi-eye"></i> View Details
                                    </button>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No sessions found
                        </div>
                    @endforelse

                    {{-- Pagination --}}
                    {{-- @if(method_exists($sessions, 'links'))
                    <div class="mt-3">
                        {{ $sessions->links('pagination::bootstrap-4') }}
                    </div>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>

    {{-- ================= SESSION DETAILS MODAL ================= --}}
    <div class="modal fade" id="sessionModal" tabindex="-1" aria-labelledby="sessionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sessionModalLabel">Session Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="sessionModalBody"></div>

            </div>
        </div>
    </div>

    {{-- ================= JAVASCRIPT ================= --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.animatedmarker@1.0.0/dist/leaflet.animatedmarker.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Define showSessionDetails function early and globally to prevent ReferenceError
        window.showSessionDetails = function (sessionId) {
            // Check if the enhanced function exists and use it, otherwise use simple fetch
            if (typeof showEnhancedSessionDetails === 'function') {
                // Use the enhanced async function
                fetch(`/api/patrol-session/${sessionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error || !data.session) {
                            if (typeof showErrorToast === 'function') {
                                showErrorToast('Session not found', sessionId);
                            } else {
                                alert('Session not found');
                            }
                            return;
                        }
                        showEnhancedSessionDetails(data.session, sessionId);
                        // Zoom to session on map
                        if (typeof zoomToSession === 'function') {
                            zoomToSession(sessionId);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching session details:', error);
                        if (typeof showErrorToast === 'function') {
                            showErrorToast('Error loading session details', sessionId);
                        } else {
                            alert('Error loading session details');
                        }
                    });
            } else {
                // Fallback: simple fetch and display
                fetch(`/api/patrol-session/${sessionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error || !data.session) {
                            alert('Session not found');
                            return;
                        }

                        const session = data.session;
                        const logs = data.logs || [];

                        const modalBody = document.getElementById('sessionModalBody');
                        if (!modalBody) {
                            alert('Modal not found');
                            return;
                        }

                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-8">
                                    <h6>Session Information</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>Session ID:</strong></td><td>#${session.session_id}</td></tr>
                                        <tr><td><strong>Type:</strong></td><td><span class="badge bg-info">${session.type || 'N/A'}</span></td></tr>
                                        <tr><td><strong>Session:</strong></td><td><span class="badge bg-primary">${session.session || 'N/A'}</span></td></tr>
                                        <tr><td><strong>Status:</strong></td><td><span class="badge bg-${session.status == 'Completed' ? 'success' : 'warning'}">${session.status || 'N/A'}</span></td></tr>
                                        <tr><td><strong>Site:</strong></td><td>${session.site_name || 'Unknown'}</td></tr>
                                        <tr><td><strong>Range:</strong></td><td>${session.range_name || 'Unknown'}</td></tr>
                                        <tr><td><strong>Start Time:</strong></td><td>${new Date(session.started_at).toLocaleString()}</td></tr>
                                        <tr><td><strong>End Time:</strong></td><td>${session.ended_at ? new Date(session.ended_at).toLocaleString() : 'In Progress'}</td></tr>
                                        <tr><td><strong>Duration:</strong></td><td>${session.duration_minutes ? Math.floor(session.duration_minutes / 60) + 'h ' + (session.duration_minutes % 60) + 'm' : 'N/A'}</td></tr>
                                        <tr><td><strong>Distance:</strong></td><td>${session.distance_km || 0} km</td></tr>
                                        ${session.method ? `<tr><td><strong>Method:</strong></td><td>${session.method}</td></tr>` : ''}
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <h6>Coordinates</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>Start:</strong></td><td>${session.start_lat ? `${session.start_lat}, ${session.start_lng}` : 'N/A'}</td></tr>
                                        <tr><td><strong>End:</strong></td><td>${session.end_lat ? `${session.end_lat}, ${session.end_lng}` : 'N/A'}</td></tr>
                                    </table>

                                    ${logs.length > 0 ? `
                                    <h6 class="mt-3">Patrol Logs (${logs.length})</h6>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        ${logs.map(log => `
                                            <div class="border-bottom pb-2 mb-2">
                                                <small class="text-muted">${new Date(log.created_at).toLocaleString()}</small><br>
                                                <strong>${log.type}</strong>
                                                ${log.notes ? `<br><small>${log.notes}</small>` : ''}
                                                ${log.lat && log.lng ? `<br><small class="text-muted">📍 ${log.lat}, ${log.lng}</small>` : ''}
                                            </div>
                                        `).join('')}
                                    </div>
                                    ` : '<p class="text-muted">No patrol logs available</p>'}
                                </div>
                            </div>
                        `;

                        const modal = new bootstrap.Modal(document.getElementById('sessionModal'));
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error fetching session details:', error);
                        alert('Error loading session details');
                    });
            }
        };

        // Also define as regular function for backward compatibility
        function showSessionDetails(sessionId) {
            return window.showSessionDetails(sessionId);
        }

        let map;
        let currentTileLayer;
        let satelliteTileLayer;
        let sessionLayers = {};
        let markers = {};
        let activeLayers = [];
        let activeUserId = null;
        let geofenceLayers = [];
        let isGeofencesVisible = true;
        let sessionDetailsModal = null;

        // Session colors by type - using brown/orange for patrol paths like in image
        const sessionColors = {
            'Foot': '#8B4513',      // Brown/Saddle Brown
            'Vehicle': '#D2691E',   // Chocolate/Orange Brown
            'Bicycle': '#CD853F',   // Peru Brown
            'Other': '#A0522D'      // Sienna Brown
        };

        function normalizePathGeoJson(raw) {
            let data = raw;
            if (!data) return null;

            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    return null;
                }
            }

            if (Array.isArray(data)) {
                if (data.length === 0) return null;

                if (data[0] && typeof data[0] === 'object' && !Array.isArray(data[0]) && ('lat' in data[0] || 'lng' in data[0])) {
                    const coords = data
                        .map(p => [Number(p.lng), Number(p.lat)])
                        .filter(c => Number.isFinite(c[0]) && Number.isFinite(c[1]));
                    if (coords.length === 0) return null;
                    return { type: 'LineString', coordinates: coords };
                }

                if (Array.isArray(data[0]) && data[0].length >= 2) {
                    let coords = data
                        .map(p => [Number(p[0]), Number(p[1])])
                        .filter(c => Number.isFinite(c[0]) && Number.isFinite(c[1]));
                    if (coords.length === 0) return null;

                    const first = coords[0];
                    const a = Math.abs(first[0]);
                    const b = Math.abs(first[1]);
                    if (a <= 90 && b <= 180 && a < b) {
                        coords = coords.map(([x, y]) => [y, x]);
                    }

                    return { type: 'LineString', coordinates: coords };
                }

                return null;
            }

            if (data && typeof data === 'object' && data.type) {
                return data;
            }

            return null;
        }

        // Initialize map with Ctrl+scroll zoom (disabled by default)
        function initMap() {
            map = L.map('patrol-map', {
                center: [22.5, 78.5],
                zoom: 7,
                zoomControl: true,
                scrollWheelZoom: false,  // Disable scroll zoom by default
                dragging: true
            });

            // Enable zoom with Ctrl+scroll
            map.on('wheel', function (e) {
                if (e.originalEvent.ctrlKey) {
                    e.originalEvent.preventDefault();
                    const delta = e.originalEvent.deltaY;
                    if (delta > 0) {
                        map.setZoom(map.getZoom() - 1);
                    } else {
                        map.setZoom(map.getZoom() + 1);
                    }
                }
            });

            // Also handle Ctrl+scroll via keyboard events
            document.addEventListener('keydown', function (e) {
                if (e.ctrlKey) {
                    map.scrollWheelZoom.enable();
                }
            });

            document.addEventListener('keyup', function (e) {
                if (!e.ctrlKey) {
                    map.scrollWheelZoom.disable();
                }
            });

            // Default tile layer (Map)
            currentTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Satellite tile layer
            satelliteTileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '© Esri'
            });

            // Load geofences
            loadGeofences();

            // Load all sessions
            loadSessions();

            // Show all paths by default and fit bounds
            setTimeout(() => {
                showAllPaths();
            }, 500);
        }

        // Load geofences
        function loadGeofences() {
            @foreach($geofences as $g)
                @if($g->type === 'Circle' && $g->lat && $g->lng)
                    const circle{{ $g->id }} = L.circle([{{ $g->lat }}, {{ $g->lng }}], {
                        radius: {{ $g->radius }},
                        color: sessionColor,
                        fillColor: '#6a1b9a',
                        fillOpacity: 0.07,
                        weight: 2
                    }).bindPopup('{{ $g->site_name ?? "Geofence" }}');
                    geofenceLayers.push(circle{{ $g->id }});
                    if (isGeofencesVisible) {
                        circle{{ $g->id }}.addTo(map);
                    }
                @elseif($g->poly_lat_lng)
                    try {
                        const polyCoords = JSON.parse(@json($g->poly_lat_lng));
                        const polygon{{ $g->id }} = L.polygon(
                            polyCoords.map(p => [p.lat, p.lng]),
                            {
                                color: '#6a1b9a',
                                fillColor: '#6a1b9a',
                                fillOpacity: 0.07,
                                weight: 2
                            }
                        ).bindPopup('{{ $g->site_name ?? "Compartment" }}');
                        geofenceLayers.push(polygon{{ $g->id }});
                        if (isGeofencesVisible) {
                            polygon{{ $g->id }}.addTo(map);
                        }
                    } catch (e) {
                        console.error('Error parsing compartment polygon:', e);
                    }
                @endif
            @endforeach
        }

        // Global function to reload map data (called by AJAX filter updates)
        function reloadMapData(newSessions) {
            // Clear existing layers
            clearActiveLayers();
            sessionLayers = {};

            // Load new sessions
            if (newSessions && newSessions.length > 0) {
                newSessions.forEach(function (s) {
                    // Generate color for session (same logic as sidebar for consistency)
                    const colors = ['#28a745', '#e91e63', '#9c27b0', '#2196f3', '#00bcd4', '#4caf50', '#ff9800', '#f44336'];
                    const sessionColor = colors[s.session_id % colors.length];

                    let pathLayer = null;
                    let startMarker = null;
                    let endMarker = null;
                    let directLine = null;

                    // 1. Create Path Layer if GeoJSON exists
                    if (s.path_geojson) {
                        try {
                            let rawPath = s.path_geojson;
                            if (typeof rawPath === 'string') {
                                rawPath = JSON.parse(rawPath);
                            }
                            const geoJson = normalizePathGeoJson(rawPath);
                            if (geoJson) {
                                pathLayer = L.geoJSON(geoJson, {
                                    style: {
                                        color: sessionColor,
                                        weight: 6,
                                        opacity: 0.9,
                                        lineCap: 'round',
                                        lineJoin: 'round'
                                    },
                                    onEachFeature: function (feature, layer) {
                                        layer.on('click', function () {
                                            if (typeof showSessionDetails === 'function') {
                                                showSessionDetails(s.session_id);
                                            }
                                        });
                                    }
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing path for session ' + s.session_id, e);
                        }
                    }

                    // 2. Create Markers if coords exist
                    if (s.start_lat && s.start_lng) {
                        startMarker = L.marker([s.start_lat, s.start_lng], {
                            icon: L.divIcon({
                                className: 'start-marker',
                                html: `<div style="background: #dc3545; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.5);"></div>`,
                                iconSize: [14, 14],
                                iconAnchor: [7, 7]
                            })
                        });
                    }

                    if (s.end_lat && s.end_lng) {
                        endMarker = L.marker([s.end_lat, s.end_lng], {
                            icon: L.divIcon({
                                className: 'end-marker',
                                html: `<div style="background: #dc3545; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.5);"></div>`,
                                iconSize: [14, 14],
                                iconAnchor: [7, 7]
                            })
                        });

                        if (s.start_lat && s.start_lng) {
                            directLine = L.polyline([[s.start_lat, s.start_lng], [s.end_lat, s.end_lng]], {
                                color: sessionColor,
                                weight: 2,
                                opacity: 0.4,
                                dashArray: '5, 5'
                            });
                        }
                    }

                    // Store session data (always store, even if no path, so Zoom button works)
                    sessionLayers[s.session_id] = {
                        layer: pathLayer,
                        start_marker: startMarker,
                        end_marker: endMarker,
                        direct_line: directLine,
                        user_id: s.user_id,
                        user_name: s.user_name,
                        session_id: s.session_id,
                        color: sessionColor,
                    };
                });

                // Show all paths
                showAllPaths();
            } else {
                // No sessions - clear map
                clearActiveLayers();
            }
        }

        // Function to update the sessions sidebar when filters are applied
        window.updateSessionsSidebar = function (sessions) {
            const sessionsList = document.getElementById('sessionsList');
            if (!sessionsList) return;

            sessionsList.innerHTML = '';

            if (!sessions || sessions.length === 0) {
                sessionsList.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No sessions found</div>';
                return;
            }

            sessions.forEach(s => {
                if (!s.path_geojson) return;

                const colors = ['#28a745', '#e91e63', '#9c27b0', '#2196f3', '#00bcd4', '#4caf50', '#ff9800', '#f44336'];
                const indicatorColor = colors[s.session_id % colors.length];

                const startedAt = new Date(s.started_at);
                const endedAt = s.ended_at ? new Date(s.ended_at) : null;

                const formatDate = (date) => {
                    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + ' ' +
                        date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
                };

                const profilePicUrl = s.user_profile ?
                    (s.user_profile.startsWith('http') ? s.user_profile : `/storage/profiles/${s.user_profile}`) :
                    null;

                const avatarHtml = profilePicUrl ?
                    `<img src="${profilePicUrl}" class="rounded-circle me-3 border-2 user-profile-img" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6 !important;" alt="${s.user_name}" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3 border-2\' style=\'width: 50px; height: 50px; border: 2px solid #dee2e6 !important;\'><span class=\'text-white fw-bold\'>${(s.user_name || '?')[0].toUpperCase()}</span></div>';">` :
                    `<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3 border-2" style="width: 50px; height: 50px; border: 2px solid #dee2e6 !important;"><span class="text-white fw-bold" style="font-size: 1.2rem;">${(s.user_name || '?')[0].toUpperCase()}</span></div>`;

                const cardHtml = `
                    <div class="session-card mb-3 p-3 border rounded shadow-sm"
                         data-session-id="${s.session_id}"
                         data-user-id="${s.user_id}"
                         data-status="${s.status}"
                         data-color="${indicatorColor}"
                         style="cursor: pointer; transition: all 0.2s; background: white;">

                        <div class="d-flex align-items-center mb-3">
                            <div class="session-indicator me-2" style="width: 14px; height: 14px; border-radius: 50%; background: ${indicatorColor}; flex-shrink: 0;"></div>
                            <strong class="text-primary" style="font-size: 0.95rem;">Session #${s.session_id}</strong>
                        </div>

                        <div class="d-flex align-items-center mb-3">
                            ${avatarHtml}
                            <div class="grow">
                                <a href="#" class="guard-name-link text-decoration-none fw-bold d-block mb-1" 
                                   data-guard-id="${s.user_id}"
                                   style="color: #212529; font-size: 0.95rem;">
                                    ${s.user_name}
                                </a>
                                <div class="text-muted small" style="font-size: 0.8rem;">
                                    ${s.site_name || 'N/A'}${s.range_name ? ' (' + s.range_name + ')' : ''}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-success" style="font-size: 0.75rem; padding: 0.4em 0.6em;">${s.status}</span>
                            <span class="badge bg-success" style="font-size: 0.75rem; padding: 0.4em 0.6em;">${formatDate(startedAt)}</span>
                            ${endedAt ? `<span class="badge bg-danger" style="font-size: 0.75rem; padding: 0.4em 0.6em;">${formatDate(endedAt)}</span>` : ''}
                            <span class="badge bg-primary" style="font-size: 0.75rem; padding: 0.4em 0.6em;">${parseFloat(s.distance_km || 0).toFixed(2)} km</span>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-primary zoom-session-btn flex-fill" data-session-id="${s.session_id}" style="font-size: 0.85rem;">
                                <i class="bi bi-zoom-in"></i> Zoom
                            </button>
                            <button type="button" class="btn btn-sm btn-info text-white view-session-btn flex-fill" data-session-id="${s.session_id}" style="font-size: 0.85rem;">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                `;

                sessionsList.insertAdjacentHTML('beforeend', cardHtml);
            });

            // Re-attach event listeners to new cards
            attachSessionCardListeners();
        };

        function attachSessionCardListeners() {
            // Zoom session buttons
            document.querySelectorAll('.zoom-session-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const sessionId = parseInt(this.dataset.sessionId);
                    zoomToSession(sessionId);
                });
            });

            // View session buttons
            document.querySelectorAll('.view-session-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const sessionId = parseInt(this.dataset.sessionId);
                    showSessionDetails(sessionId);
                });
            });

            // Session card clicks
            document.querySelectorAll('.session-card').forEach(card => {
                card.addEventListener('click', function (e) {
                    if (!e.target.closest('button') && !e.target.closest('a') && !e.target.closest('img') && !e.target.closest('.badge')) {
                        const sessionId = parseInt(this.dataset.sessionId);
                        zoomToSession(sessionId);
                    }
                });
            });
        }

        // Load patrol sessions
        function loadSessions() {
            @foreach($sessions as $s)
                try {
                    // Generate color for session
                    const sessionColor = document
                        .querySelector(`.session-card[data-session-id="{{ $s->session_id }}"]`)
                        ?.dataset.color || '#999';

                    let pathLayer = null;
                    let startMarker = null;
                    let endMarker = null;
                    let directLine = null;

                    // 1. Create Path Layer if GeoJSON exists
                    @if($s->path_geojson)
                        let rawPath = @json($s->path_geojson);
                        if (typeof rawPath === 'string') {
                            rawPath = JSON.parse(rawPath);
                        }
                        const geoJson = normalizePathGeoJson(rawPath);
                        if (geoJson) {
                            pathLayer = L.geoJSON(geoJson, {
                                style: {
                                    color: sessionColor,
                                    weight: 6,
                                    opacity: 0.9,
                                    lineCap: 'round',
                                    lineJoin: 'round'
                                },
                                onEachFeature: function (feature, layer) {
                                    layer.on('click', function () {
                                        showSessionDetails({{ $s->session_id }});
                                    });
                                }
                            });
                        }
                    @endif

                    // 2. Create Markers if coords exist
                    @if($s->start_lat && $s->start_lng)
                        startMarker = L.marker([{{ $s->start_lat }}, {{ $s->start_lng }}], {
                            icon: L.divIcon({
                                className: 'start-marker',
                                html: '<div style="background: #dc3545; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.5);"></div>',
                                iconSize: [14, 14],
                                iconAnchor: [7, 7]
                            })
                        });
                    @endif

                    @if($s->end_lat && $s->end_lng)
                        endMarker = L.marker([{{ $s->end_lat }}, {{ $s->end_lng }}], {
                            icon: L.divIcon({
                                className: 'end-marker',
                                html: '<div style="background: #dc3545; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.5);"></div>',
                                iconSize: [14, 14],
                                iconAnchor: [7, 7]
                            })
                        });

                        @if($s->start_lat && $s->start_lng)
                            directLine = L.polyline([
                                [{{ $s->start_lat }}, {{ $s->start_lng }}],
                                [{{ $s->end_lat }}, {{ $s->end_lng }}]
                            ], {
                                color: sessionColor,
                                weight: 0,
                                opacity: 0.0,
                                dashArray: '5, 5'
                            });
                        @endif
                    @endif

                    // Store session data
                    sessionLayers[{{ $s->session_id }}] = {
                        layer: pathLayer,
                        start_marker: startMarker,
                        end_marker: endMarker,
                        direct_line: directLine,
                        user_id: {{ $s->user_id }},
                        user_name: "{{ $s->user_name }}",
                        session_id: {{ $s->session_id }},
                        session_type: '{{ $s->session }}',
                        color: sessionColor,
                    };
                } catch (e) {
                    console.error('Error loading session {{ $s->session_id }}:', e);
                }
            @endforeach
        }

        // Show all paths
        function showAllPaths() {
            clearActiveLayers();
            Object.values(sessionLayers).forEach(session => {
                if (session.layer) {
                    session.layer.setStyle({
                        color: session.color,
                        weight: 6,
                        opacity: 0.9
                    });
                    session.layer.addTo(map);
                    activeLayers.push(session.layer);
                }

                // Add direct line if available
                if (session.direct_line) {
                    session.direct_line.addTo(map);
                    activeLayers.push(session.direct_line);
                }

                if (session.start_marker) {
                    session.start_marker.addTo(map);
                    activeLayers.push(session.start_marker);
                }
                if (session.end_marker) {
                    session.end_marker.addTo(map);
                    activeLayers.push(session.end_marker);
                }
            });

            // Reset all session card styles
            document.querySelectorAll('.session-card').forEach(card => {
                card.style.border = '1px solid #dee2e6';
                card.style.boxShadow = 'none';
            });

            fitAllPaths();
        }

        // Show paths for specific user
        function showUserPaths(userId) {
            clearActiveLayers();
            activeUserId = userId;

            const userSessions = Object.values(sessionLayers).filter(s => s.user_id == userId);

            if (userSessions.length === 0) {
                alert('No patrol paths found for this guard');
                return;
            }

            userSessions.forEach(session => {
                // Highlight paths with thicker, brighter lines
                if (session.layer) {
                    session.layer.setStyle({
                        color: session.color,
                        weight: 5,  // Thicker when highlighted
                        opacity: 1
                    });
                    session.layer.addTo(map);
                    activeLayers.push(session.layer);
                }

                // Add direct line if available
                if (session.direct_line) {
                    session.direct_line.setStyle({ opacity: 1 });
                    session.direct_line.addTo(map);
                    activeLayers.push(session.direct_line);
                }

                if (session.start_marker) {
                    session.start_marker.addTo(map);
                    activeLayers.push(session.start_marker);
                }
                if (session.end_marker) {
                    session.end_marker.addTo(map);
                    activeLayers.push(session.end_marker);
                }
            });

            // Fit bounds to user's paths
            if (userSessions.length > 0) {
                const features = [];
                userSessions.forEach(s => {
                    if (s.layer) features.push(s.layer);
                    if (s.start_marker) features.push(s.start_marker);
                    if (s.end_marker) features.push(s.end_marker);
                    if (s.direct_line) features.push(s.direct_line);
                });

                if (features.length > 0) {
                    const group = L.featureGroup(features);
                    const bounds = group.getBounds();
                    if (bounds.isValid()) {
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }
                }
            }

            // Highlight session cards for this user
            document.querySelectorAll('.session-card').forEach(card => {
                if (parseInt(card.dataset.userId) === userId) {
                    card.style.border = '2px solid #28a745';
                    card.style.boxShadow = '0 4px 8px rgba(40, 167, 69, 0.3)';
                } else {
                    card.style.border = '1px solid #dee2e6';
                    card.style.boxShadow = 'none';
                }
            });
        }


        function zoomToSession(sessionId) {
            const selected = sessionLayers[sessionId];
            if (!selected) {
                console.warn('Session Layer not found for ID:', sessionId);
                return;
            }

            clearActiveLayers();

            // Re-draw all layers with faded opacity, except the selected one
            Object.values(sessionLayers).forEach(s => {
                if (s.session_id === sessionId) {
                    if (s.layer) {
                        s.layer.setStyle({
                            color: s.color,
                            weight: 6,
                            opacity: 1
                        });
                    }
                } else {
                    if (s.layer) {
                        s.layer.setStyle({
                            color: '#cccccc',
                            weight: 0,
                            opacity: 0.0    
                        });
                    }
                }

                if (s.layer) {
                    s.layer.addTo(map);
                    activeLayers.push(s.layer);
                }

                // Add markers and lines for current session (full opacity) or others (low opacity)
                const opacity = s.session_id === sessionId ? 1 : 0.2;

                if (s.direct_line) {
                    s.direct_line.setStyle({ opacity: opacity });
                    s.direct_line.addTo(map);
                    activeLayers.push(s.direct_line);
                }

                if (s.start_marker) {
                    s.start_marker.addTo(map);
                    activeLayers.push(s.start_marker);
                }

                if (s.end_marker) {
                    s.end_marker.addTo(map);
                    activeLayers.push(s.end_marker);
                }
            });

            // Fit bounds to all elements of the SELECTED session
            const features = [];
            if (selected.layer) features.push(selected.layer);
            if (selected.start_marker) features.push(selected.start_marker);
            if (selected.end_marker) features.push(selected.end_marker);
            if (selected.direct_line) features.push(selected.direct_line);

            if (features.length > 0) {
                const group = L.featureGroup(features);
                const bounds = group.getBounds();

                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [80, 80], maxZoom: 17 });
                } else {
                    // Fallback: zoom to start marker if bounds invalid (e.g. single point)
                    if (selected.start_marker) {
                        map.setView(selected.start_marker.getLatLng(), 17);
                    }
                }
            }

            // Highlight the session card in sidebar
            document.querySelectorAll('.session-card').forEach(card => {
                card.style.border = '1px solid #dee2e6';
                card.style.boxShadow = 'none';

                if (parseInt(card.dataset.sessionId) === sessionId) {
                    card.style.border = `3px solid ${selected.color}`;
                    card.style.boxShadow = `0 0 12px ${selected.color}44`;
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        }


        // Clear active layers
        function clearActiveLayers() {
            activeLayers.forEach(layer => {
                if (map.hasLayer(layer)) {
                    map.removeLayer(layer);
                }
            });
            activeLayers = [];
            activeUserId = null;
        }

        // Fit bounds to all paths
        function fitAllPaths() {
            const features = [];
            Object.values(sessionLayers).forEach(s => {
                if (s.layer) features.push(s.layer);
                if (s.start_marker) features.push(s.start_marker);
                if (s.end_marker) features.push(s.end_marker);
                if (s.direct_line) features.push(s.direct_line);
            });

            if (features.length > 0) {
                const group = L.featureGroup(features);
                const bounds = group.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            }
        }

        // Enhanced session details with more information and better formatting
        function showEnhancedSessionDetails(session, sessionId, logs = []) {
            const modalBody = document.getElementById('sessionModalBody');

            // Calculate additional metrics
            const startTime = new Date(session.started_at);
            const endTime = session.ended_at ? new Date(session.ended_at) : null;
            const now = new Date();
            const duration = endTime ? endTime - startTime : now - startTime;
            
            const durationHours = Math.floor(duration / (1000 * 60 * 60));
            const durationMinutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));

            // Format distance
            const distance = parseFloat(session.distance_km || 0);
            const distanceFormatted = distance > 0 ? distance.toFixed(2) + ' km' : '0.00 km';

            // Calculate average speed
            const durationInHours = duration / (1000 * 60 * 60);
            const avgSpeed = (distance > 0 && durationInHours > 0) ? (distance / durationInHours).toFixed(2) : '0.00';

            modalBody.innerHTML = `
                <div class="session-details-container px-2">
                    <!-- Session Header Info -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small mb-1 d-block tracking-wider">Patrol Session</span>
                            <h3 class="fw-bold mb-0 text-dark">#${session.session_id}</h3>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0 w-100 w-md-auto">
                             <div class="badge-status rounded-pill px-3 py-2 bg-soft-${session.status === 'Completed' ? 'success' : 'warning'} text-${session.status === 'Completed' ? 'success' : 'warning'} fw-bold flex-grow-1 text-center">
                                <i class="bi bi-circle-fill small me-1"></i> ${session.status}
                             </div>
                        </div>
                    </div>

                    <!-- Quick Metrics Grid -->
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="metric-card h-100">
                                <div class="metric-icon bg-soft-primary text-primary">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <span class="metric-label">Distance</span>
                                <h4 class="metric-value mb-0">${distanceFormatted}</h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="metric-card h-100">
                                <div class="metric-icon bg-soft-success text-success">
                                    <i class="bi bi-clock-fill"></i>
                                </div>
                                <span class="metric-label">Duration</span>
                                <h4 class="metric-value mb-0">${durationHours}h ${durationMinutes}m</h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="metric-card h-100">
                                <div class="metric-icon bg-soft-info text-info">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <span class="metric-label">Avg Speed</span>
                                <h4 class="metric-value mb-0">${avgSpeed} <small class="fw-normal">km/h</small></h4>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- Guard & Location Info -->
                        <div class="col-md-6 border-end-md">
                            <div class="info-group mb-3">
                                <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Guard Information</label>
                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 transition-hover">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; min-width: 40px;">
                                        ${(session.user_name || 'G')[0]}
                                    </div>
                                    <div class="overflow-hidden">
                                        <h6 class="mb-0 fw-bold text-truncate">${session.user_name || 'N/A'}</h6>
                                        <span class="text-muted small">${session.type || 'Routine'} • ${session.session || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-group">
                                <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Location Details</label>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-geo-fill text-muted"></i>
                                        <span class="text-muted small">Site:</span>
                                        <span class="fw-medium">${session.site_name || 'N/A'}</span>
                                    </li>
                                    <li class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-pin-map text-muted"></i>
                                        <span class="text-muted small">Range:</span>
                                        <span class="fw-medium">${session.range_name || 'N/A'}</span>
                                    </li>
                                    <li class="d-flex align-items-center gap-2">
                                        <i class="bi bi-building text-muted"></i>
                                        <span class="text-muted small">Client:</span>
                                        <span class="fw-medium">${session.client_name || 'N/A'}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Timeline & Logs -->
                        <div class="col-md-6">
                            <div class="info-group mb-4">
                                <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Timeline</label>
                                <div class="timeline-visual d-flex align-items-center mb-3">
                                    <div class="dot active"></div>
                                    <div class="line ${session.ended_at ? 'active' : ''}"></div>
                                    <div class="dot ${session.ended_at ? 'active' : ''}"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div class="text-start">
                                        <small class="d-block text-muted">Started</small>
                                        <span class="fw-bold small">${startTime.toLocaleString([], {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'})}</span>
                                    </div>
                                    <div class="text-end">
                                        <small class="d-block text-muted">Ended</small>
                                        <span class="fw-bold small">${endTime ? endTime.toLocaleString([], {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'}) : '<span class="text-warning">In Progress</span>'}</span>
                                    </div>
                                </div>
                            </div>
                            
                            ${logs.length > 0 ? `
                            <div class="info-group mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="text-muted small text-uppercase fw-bold mb-0">Activity Logs (${logs.length})</label>
                                    <button class="btn btn-sm p-0 text-primary small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#sessionLogsCollapse">
                                        Toggle Logs
                                    </button>
                                </div>
                                <div class="collapse show" id="sessionLogsCollapse">
                                    <div class="logs-minimal-list overflow-auto px-1" style="max-height: 120px;">
                                        ${logs.slice(0, 5).map(log => `
                                            <div class="log-item py-1 border-bottom border-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold extra-small">${log.type}</span>
                                                    <span class="text-muted extra-small">${new Date(log.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                                                </div>
                                            </div>
                                        `).join('')}
                                        ${logs.length > 5 ? `<p class="text-center mt-1 mb-0 small text-primary">+ ${logs.length - 5} more logs</p>` : ''}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            // Clean up any existing modal and backdrop first
            cleanupModal();

            // Get modal element
            const modalEl = document.getElementById('sessionModal');

            // Create new modal instance
            sessionDetailsModal = new bootstrap.Modal(modalEl, {
                backdrop: 'true',  // Use static backdrop to prevent issues
                keyboard: true,
                focus: true
            });

            // Show modal
            sessionDetailsModal.show();

            // Add cleanup event listener
            modalEl.addEventListener('hidden.bs.modal', cleanupModal, { once: true });
        }

        // Global cleanup function for modal
        function cleanupModal() {
            // Remove any existing backdrops
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });

            // Reset body styles
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');

            // Remove any remaining modal instances
            const modalEl = document.getElementById('sessionModal');
            const existingModal = bootstrap.Modal.getInstance(modalEl);
            if (existingModal) {
                existingModal.dispose();
            }

            // Remove highlight from session cards
            document.querySelectorAll('.session-card').forEach(card => {
                card.style.border = '';
                card.style.boxShadow = '';
            });
        }

        // Error toast function for better user feedback
        function showErrorToast(message, sessionId = null) {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast" role="alert">
                    <div class="toast-header bg-danger text-white">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong class="me-auto">Error</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                        ${sessionId ? `<br><small class="text-muted">Session ID: ${sessionId}</small>` : ''}
                    </div>
                </div>
            `;

            toastContainer.insertAdjacentHTML('beforeend', toastHtml);

            // Initialize and show toast
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 5000
            });
            toast.show();

            // Remove toast element after hiding
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }

        // Additional helper functions for the enhanced view buttons
        function playbackSession(sessionId) {
            console.log('Starting playback for session:', sessionId);
            // Hide modal first
            const modalEl = document.getElementById('sessionModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Start playback animation
            setTimeout(() => {
                playAnimation();
            }, 500);
        }

        function exportSessionData(sessionId) {
            console.log('Exporting data for session:', sessionId);
            // Implement export functionality
            alert('Export functionality coming soon! Session ID: ' + sessionId);
        }

        // Playback animation
        function playAnimation() {
            if (activeLayers.length === 0) {
                alert('Please select a guard or show all paths first');
                return;
            }

            // Simple animation: pulse effect
            activeLayers.forEach(layer => {
                if (layer.setStyle) {
                    let opacity = 0.3;
                    const interval = setInterval(() => {
                        opacity = opacity === 0.3 ? 1 : 0.3;
                        layer.setStyle({ opacity: opacity });
                    }, 500);

                    setTimeout(() => {
                        clearInterval(interval);
                        layer.setStyle({ opacity: 0.8 });
                    }, 5000);
                }
            });
        }

        // Toggle geofences
        function toggleGeofences() {
            isGeofencesVisible = !isGeofencesVisible;
            const btn = document.getElementById('hideGeofencesBtn');

            if (isGeofencesVisible) {
                geofenceLayers.forEach(layer => layer.addTo(map));
                btn.textContent = 'Hide Compartments';
            } else {
                geofenceLayers.forEach(layer => map.removeLayer(layer));
                btn.textContent = 'Show Compartments';
            }
        }

        // Switch map type
        function switchMapType(type) {
            map.removeLayer(currentTileLayer);

            if (type === 'satellite') {
                currentTileLayer = satelliteTileLayer;
                document.getElementById('mapTab').classList.remove('active');
                document.getElementById('satelliteTab').classList.add('active');
            } else {
                currentTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                });
                document.getElementById('satelliteTab').classList.remove('active');
                document.getElementById('mapTab').classList.add('active');
            }

            currentTileLayer.addTo(map);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function () {
            initMap();

            // ================= FULLSCREEN FUNCTIONALITY =================
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const mapContainer = document.getElementById('patrol-map-container');
            const patrolMap = document.getElementById('patrol-map');
            let isFullscreen = false;

            if (fullscreenBtn && mapContainer) {
                fullscreenBtn.addEventListener('click', function () {
                    if (!isFullscreen) {
                        // Enter fullscreen
                        if (mapContainer.requestFullscreen) {
                            mapContainer.requestFullscreen();
                        } else if (mapContainer.webkitRequestFullscreen) {
                            mapContainer.webkitRequestFullscreen();
                        } else if (mapContainer.msRequestFullscreen) {
                            mapContainer.msRequestFullscreen();
                        }
                        isFullscreen = true;
                        fullscreenBtn.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
                        fullscreenBtn.title = 'Exit Fullscreen';
                    } else {
                        // Exit fullscreen
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        } else if (document.msExitFullscreen) {
                            document.msExitFullscreen();
                        }
                        isFullscreen = false;
                        fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
                        fullscreenBtn.title = 'Toggle Fullscreen';
                    }
                });

                // Listen for fullscreen changes
                document.addEventListener('fullscreenchange', function () {
                    if (!document.fullscreenElement) {
                        isFullscreen = false;
                        fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
                        fullscreenBtn.title = 'Toggle Fullscreen';
                    }
                    // Resize map when fullscreen changes
                    setTimeout(() => {
                        if (map) map.invalidateSize();
                    }, 100);
                });
            }

            // ================= RESIZE FUNCTIONALITY =================
            const resizeSmallBtn = document.getElementById('resizeSmallBtn');
            const resizeMediumBtn = document.getElementById('resizeMediumBtn');
            const resizeLargeBtn = document.getElementById('resizeLargeBtn');

            const mapSizes = {
                small: '400px',
                medium: '650px',
                large: '900px'
            };

            function setMapSize(size) {
                if (patrolMap) {
                    patrolMap.style.height = mapSizes[size];
                    setTimeout(() => {
                        if (map) map.invalidateSize();
                    }, 100);
                }

                // Update button states
                [resizeSmallBtn, resizeMediumBtn, resizeLargeBtn].forEach(btn => {
                    if (btn) btn.classList.remove('active');
                });

                if (size === 'small' && resizeSmallBtn) resizeSmallBtn.classList.add('active');
                if (size === 'medium' && resizeMediumBtn) resizeMediumBtn.classList.add('active');
                if (size === 'large' && resizeLargeBtn) resizeLargeBtn.classList.add('active');
            }

            if (resizeSmallBtn) {
                resizeSmallBtn.addEventListener('click', () => setMapSize('small'));
            }
            if (resizeMediumBtn) {
                resizeMediumBtn.addEventListener('click', () => setMapSize('medium'));
            }
            if (resizeLargeBtn) {
                resizeLargeBtn.addEventListener('click', () => setMapSize('large'));
            }

            // Ensure modal never leaves a stuck backdrop/body lock
            const sessionModalEl = document.getElementById('sessionModal');
            if (sessionModalEl) {
                sessionModalEl.addEventListener('hidden.bs.modal', function () {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                });
            }

            // User name clicks - highlight guard's paths
            document.querySelectorAll('.user-name-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const userId = parseInt(this.dataset.userId);
                    showUserPaths(userId);
                });
            });

            // Zoom session buttons
            document.querySelectorAll('.zoom-session-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const sessionId = parseInt(this.dataset.sessionId);
                    zoomToSession(sessionId);
                });
            });

            // Enhanced View session buttons with loading states and better error handling
            document.querySelectorAll('.view-session-btn').forEach(btn => {
                btn.addEventListener('click', async function (e) {
                    e.stopPropagation();

                    const sessionId = parseInt(this.dataset.sessionId);
                    const originalContent = this.innerHTML;

                    // Show loading state
                    this.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';
                    this.disabled = true;

                    try {
                        // Highlight the session card while loading
                        const sessionCard = document.querySelector(`.session-card[data-session-id="${sessionId}"]`);
                        if (sessionCard) {
                            sessionCard.style.border = '2px solid #007bff';
                            sessionCard.style.boxShadow = '0 0 8px rgba(0,123,255,0.3)';
                        }

                        // Create AbortController for timeout
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 10000);

                        // Fetch session details with timeout
                        const response = await fetch(`/api/patrol-session/${sessionId}`, {
                            signal: controller.signal
                        });

                        clearTimeout(timeoutId);

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const data = await response.json();

                        if (!data.session) {
                            throw new Error('No session data received');
                        }

                        // Show session details (pass session and logs)
                        showEnhancedSessionDetails(data.session, sessionId, data.logs || []);

                        // Zoom to session on map
                        zoomToSession(sessionId);

                        // Update button to success state briefly
                        this.innerHTML = '<i class="bi bi-check-circle"></i> Viewed';
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }, 1500);

                    } catch (error) {
                        console.error('Error loading session details:', error);

                        // Show user-friendly error message
                        let errorMessage = 'Failed to load session details';
                        if (error.name === 'AbortError') {
                            errorMessage = 'Request timed out - please check connection';
                        } else if (error.message.includes('404')) {
                            errorMessage = 'Session not found';
                        } else if (error.message.includes('500')) {
                            errorMessage = 'Server error - please try again';
                        }

                        // Show error toast or alert
                        showErrorToast(errorMessage, sessionId);

                        // Reset button
                        this.innerHTML = originalContent;
                        this.disabled = false;

                        // Remove highlight from session card
                        const sessionCard = document.querySelector(`.session-card[data-session-id="${sessionId}"]`);
                        if (sessionCard) {
                            sessionCard.style.border = '';
                            sessionCard.style.boxShadow = '';
                        }
                    }
                });
            });

            // Map controls
            document.getElementById('mapTab').addEventListener('click', () => switchMapType('map'));
            document.getElementById('satelliteTab').addEventListener('click', () => switchMapType('satellite'));
            document.getElementById('hideGeofencesBtn').addEventListener('click', toggleGeofences);
            document.getElementById('showAllBtn').addEventListener('click', showAllPaths);

            // Session card clicks - zoom to session when clicking on card (but not on buttons/links)
            document.querySelectorAll('.session-card').forEach(card => {
                card.addEventListener('click', function (e) {
                    // Don't trigger if clicking on buttons, links, or images
                    if (!e.target.closest('button') && !e.target.closest('a') && !e.target.closest('img') && !e.target.closest('.badge')) {
                        const sessionId = parseInt(this.dataset.sessionId);
                        zoomToSession(sessionId);
                    }
                });
            });
        });

        // Calculate total distance
        function calculateTotalDistance() {
            let totalDistance = 0;
            Object.values(sessionLayers).forEach(session => {
                // Extract distance from session data if available
                // This is a placeholder - you may need to pass distance from backend
            });
            document.getElementById('mapDistance').textContent = `Map distance (client): ${totalDistance.toFixed(2)} km`;
        }


        const sortSelect = document.getElementById('sortSessions');
        if (sortSelect) {
            sortSelect.addEventListener('change', function () {
                const cards = Array.from(document.querySelectorAll('.session-card'));

                const sorted = cards.sort((a, b) => {
                    const da = parseFloat(a.querySelector('.badge.bg-primary').innerText);
                    const db = parseFloat(b.querySelector('.badge.bg-primary').innerText);
                    return this.value === 'distance_desc' ? db - da : da - db;
                });

                const container = document.getElementById('sessionsList');
                sorted.forEach(card => container.appendChild(card));
            });
        }


    </script>

    <style>
        #patrol-map {
            border-radius: 8px;
            z-index: 1;
            cursor: grab;
            height: 650px;
            width: 100%;
        }

        /* Fullscreen Fix: Force container and map to fill screen */
        :fullscreen #patrol-map-container,
        :-webkit-full-screen #patrol-map-container,
        :-moz-full-screen #patrol-map-container,
        :-ms-fullscreen #patrol-map-container {
            width: 100vw !important;
            height: 100vh !important;
            background: #fff;
            display: block !important;
        }

        #patrol-map-container:fullscreen #patrol-map,
        #patrol-map-container:-webkit-full-screen #patrol-map,
        #patrol-map-container:-moz-full-screen #patrol-map,
        #patrol-map-container:-ms-fullscreen #patrol-map {
            height: 100% !important;
            width: 100% !important;
            border-radius: 0 !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
        }

        #patrol-map:active {
            cursor: grabbing;
        }

        /* Hint for Ctrl+scroll zoom */
        #patrol-map::after {
            content: 'Hold Ctrl + Scroll to zoom';
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }

        #patrol-map:hover::after {
            opacity: 1;
        }

        .btn-group .btn.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .session-indicator {
            flex-shrink: 0;
        }

        /* Custom tooltip styling for patrol paths */
        .patrol-path-tooltip {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid #0d6efd !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            padding: 10px !important;
            font-size: 13px !important;
            max-width: 250px !important;
            white-space: normal !important;
        }

        .patrol-path-tooltip::before {
            border-top-color: #0d6efd !important;
        }
    </style>

@endsection