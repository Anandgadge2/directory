@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.2);
            --primary-accent: #10b981;
            /* Forest Green */
        }

        .map-container-wrapper {
            position: relative;
            height: calc(100vh - 120px);
            margin: 0 -1.5rem;
            overflow: hidden;
            background: #f8fafc;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        #map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .filter-sidebar {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 360px;
            max-height: calc(100% - 40px);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(calc(100% + 40px));
        }

        .filter-sidebar.open {
            transform: translateX(0);
        }

        .drawer-toggle {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            z-index: 1001;
            width: 44px;
            height: 52px;
            background: var(--primary-accent);
            color: white;
            border: none;
            border-radius: 12px 0 0 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: -4px 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .drawer-toggle span {
            font-size: 0.55rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .drawer-toggle.active {
            right: 380px;
            background: #ef4444;
            border-radius: 12px;
        }

        .sidebar-header {
            padding: 20px 20px 10px;
        }

        .sidebar-header h5 {
            color: #111827;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .sidebar-header .sub-title {
            color: var(--primary-accent);
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-content {
            padding: 0 15px 20px;
            overflow-y: auto;
            flex: 1;
        }

        .layer-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 8px;
            padding: 10px 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .layer-item:hover {
            border-color: rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }

        .layer-item.active {
            background: #f0fdf4;
            border-color: rgba(16, 185, 129, 0.2);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 12px;
        }

        .layer-icon-box {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-right: 12px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .layer-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #374151;
            flex: 1;
        }

        .count-pill {
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            min-width: 32px;
            text-align: center;
            margin: 0 10px;
        }

        .eye-toggle {
            font-size: 1.2rem;
            color: #d1d5db;
        }

        .eye-toggle.active {
            color: var(--primary-accent);
        }

        .custom-loader {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            border-radius: 12px;
        }

        /* Leaflet Popup Styling */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
        }

        .leaflet-popup-content {
            margin: 0;
            width: 280px !important;
        }

        .popup-header {
            padding: 12px 15px;
            color: white;
            font-weight: 700;
        }

        .popup-body {
            padding: 12px 15px;
            font-size: 0.8rem;
        }

        .popup-row {
            display: flex;
            margin-bottom: 5px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 5px;
        }

        .popup-row:last-child {
            border-bottom: none;
        }

        .popup-label {
            color: #64748b;
            font-weight: 600;
            width: 40%;
            text-transform: uppercase;
            font-size: 0.65rem;
        }

        .popup-value {
            color: #1e293b;
            font-weight: 500;
            width: 60%;
        }
    </style>

    <div class="map-container-wrapper">
        <div id="map"></div>

        <button class="drawer-toggle" id="drawerToggle">
            <i class="bi bi-layers-half"></i>
            <span>Layers</span>
        </button>

        <div class="filter-sidebar glass-panel">
            <div class="sidebar-header">
                <h5>Map Layers</h5>
                <div class="sub-title">Geospatial Explorer</div>
            </div>

            <div class="sidebar-content">
                <form id="filterForm" class="mb-4">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted mb-1">Region</label>
                            <select id="rangeSelect" name="range_id" class="form-select form-select-sm">
                                <option value="">All Ranges</option>
                                @foreach($availableRanges as $range)
                                    <option value="{{ $range->id }}" {{ $selectedRange == $range->id ? 'selected' : '' }}>
                                        {{ $range->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted mb-1">Beat/Site</label>
                            <select id="beatSelect" name="site_id" class="form-select form-select-sm">
                                <option value="">All Beats</option>
                                @foreach($availableBeats as $beat)
                                    <option value="{{ $beat->id }}" {{ $selectedBeat == $beat->id ? 'selected' : '' }}>
                                        {{ $beat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted mb-1">Year</label>
                            <select id="yearSelect" name="year" class="form-select form-select-sm">
                                <option value="">All Years</option>
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-emerald btn-sm w-100 fw-bold">
                        <i class="bi bi-funnel-fill me-2"></i>Apply Filters
                    </button>
                </form>

                <div id="layerControls">
                    <div class="layer-item active" id="item_geofences" onclick="toggleLayerUI('geofences')">
                        <div class="status-dot" style="background-color: #10b981"></div>
                        <div class="layer-icon-box" style="color: #10b981">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="layer-label">Compartments</div>
                        <div id="count_geofences" class="count-pill">0</div>
                        <div class="eye-toggle active" id="eye_geofences">
                            <i class="bi bi-eye-fill"></i>
                        </div>
                        <input type="checkbox" class="layer-toggle d-none" value="geofences" id="check_geofences" checked>
                    </div>

                    @php
                        $layers = [
                            ['id' => 'drainage', 'label' => 'Drainage', 'color' => '#3b82f6', 'icon' => 'bi-droplet-half'],
                            ['id' => 'elephant_movement', 'label' => 'Elephant Movements', 'color' => '#f59e0b', 'icon' => 'bi-paw'],
                            ['id' => 'fire_point', 'label' => 'Fire Points', 'color' => '#ef4444', 'icon' => 'bi-fire'],
                            ['id' => 'forest_boundary', 'label' => 'Forest Boundary', 'color' => '#10b981', 'icon' => 'bi-tree-fill'],
                            ['id' => 'plantation_site', 'label' => 'Plantation Sites', 'color' => '#0ea5e9', 'icon' => 'bi-flower1'],
                            ['id' => 'revenue_forest_land', 'label' => 'Revenue Forest Land', 'color' => '#a855f7', 'icon' => 'bi-globe'],
                            ['id' => 'water_body', 'label' => 'Water Bodies', 'color' => '#6366f1', 'icon' => 'bi-water'],
                            ['id' => 'beat_boundary', 'label' => 'Beat Boundary', 'color' => '#eab308', 'icon' => 'bi-pentagon-fill'],
                        ];
                    @endphp

                    @foreach($layers as $layer)
                        @if(in_array($layer['id'], $availableLayers))
                            <div class="layer-item" id="item_{{ $layer['id'] }}" onclick="toggleLayerUI('{{ $layer['id'] }}')">
                                <div class="status-dot" style="background-color: {{ $layer['color'] }}"></div>
                                <div class="layer-icon-box" style="color: {{ $layer['color'] }}">
                                    <i class="bi {{ $layer['icon'] }}"></i>
                                </div>
                                <div class="layer-label">{{ $layer['label'] }}</div>
                                <div id="count_{{ $layer['id'] }}" class="count-pill">0</div>
                                <div class="eye-toggle" id="eye_{{ $layer['id'] }}">
                                    <i class="bi bi-eye-fill"></i>
                                </div>
                                <div id="spinner_{{ $layer['id'] }}" class="spinner-border spinner-border-sm text-emerald ms-2"
                                    role="status" style="display: none; width: 0.7rem; height: 0.7rem;"></div>
                                <input type="checkbox" class="layer-toggle d-none" value="{{ $layer['id'] }}"
                                    id="check_{{ $layer['id'] }}">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div id="customLoader" class="custom-loader">
            <div class="spinner-border text-emerald mb-2" role="status"></div>
            <span class="small fw-bold">Synchronizing Data...</span>
        </div>
    </div>

    @push('scripts')
        <script>
            let map;
            let baseLayers = {};
            let layerGroups = {};
            let loadedLayers = {};
            let geofenceLayers = L.featureGroup();
            let lastSite = '{{ $selectedBeat }}';
            let lastRange = '{{ $selectedRange }}';
            let lastYear = '{{ $selectedYear }}';

            const LAYER_STYLES = {
                'drainage': { color: '#3b82f6', weight: 3, opacity: 0.8 },
                'elephant_movement': { color: '#f59e0b', weight: 4, opacity: 0.8 },
                'fire_point': { color: '#ef4444' },
                'forest_boundary': { color: '#10b981', weight: 3, fillOpacity: 0.1 },
                'plantation_site': { color: '#0ea5e9', weight: 2, fillOpacity: 0.3 },
                'revenue_forest_land': { color: '#a855f7', weight: 2, fillOpacity: 0.3 },
                'water_body': { color: '#6366f1', weight: 2, fillOpacity: 0.5 },
                'beat_boundary': { color: '#eab308', weight: 4, fillOpacity: 0 },
                'geofences': { color: '#10b981', weight: 2, fillOpacity: 0.1 }
            };

            const LAYER_ICONS = {
                'elephant_movement': '🐘',
                'fire_point': '🔥',
                'plantation_site': '🌱',
                'drainage': '🌊',
                'water_body': '💧',
                'forest_boundary': '🌳',
                'revenue_forest_land': '📜',
                'beat_boundary': '🟡'
            };

            function toggleLayerUI(layerType) {
                const cb = document.getElementById('check_' + layerType);
                if (cb) {
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                    updateLayerUIState(layerType, cb.checked);
                }
            }

            function updateLayerUIState(layerType, active) {
                const item = document.getElementById('item_' + layerType);
                const eye = document.getElementById('eye_' + layerType);
                if (item) item.classList.toggle('active', active);
                if (eye) eye.classList.toggle('active', active);
            }

            document.addEventListener('DOMContentLoaded', function () {
                initMap();
                loadLayerCounts();

                const sidebar = document.querySelector('.filter-sidebar');
                const toggleBtn = document.getElementById('drawerToggle');

                toggleBtn.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                    this.classList.toggle('active');
                    const icon = this.querySelector('i');
                    icon.className = sidebar.classList.contains('open') ? 'bi bi-x-lg' : 'bi bi-layers-half';
                });

                document.getElementById('filterForm').addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const currentSite = formData.get('site_id');
                    const currentRange = formData.get('range_id');
                    const currentYear = formData.get('year');

                    // Only full reset if Site/Range changes. Year change only resets features, not boundaries.
                    const spatialChanged = (currentSite !== lastSite || currentRange !== lastRange);
                    resetMapScope(spatialChanged);

                    lastSite = currentSite;
                    lastRange = currentRange;
                    lastYear = currentYear;

                    loadLayerCounts();
                });

                document.body.addEventListener('change', function (e) {
                    if (e.target.classList.contains('layer-toggle')) {
                        handleLayerToggle(e.target.value, e.target.checked);
                    }
                });

                // Cascading Cascades
                const rangeSelect = document.getElementById('rangeSelect');
                if (rangeSelect) {
                    rangeSelect.addEventListener('change', function () {
                        const rangeId = this.value;
                        const beatSelect = document.getElementById('beatSelect');
                        beatSelect.innerHTML = '<option value="">Loading...</option>';
                        if (rangeId) {
                            fetch(`{{ url('/filters/beats') }}/${rangeId}`)
                                .then(res => res.json())
                                .then(data => {
                                    beatSelect.innerHTML = '<option value="">All Beats</option>';

                                    // Robust data handling (support flat array or object with 'beats' key)
                                    let beats = Array.isArray(data) ? data : (data.beats ? data.beats : []);

                                    if (Array.isArray(beats)) {
                                        beats.forEach(beat => {
                                            beatSelect.innerHTML += `<option value="${beat.id}">${beat.name}</option>`;
                                        });
                                    } else if (typeof beats === 'object') {
                                        Object.keys(beats).forEach(id => {
                                            beatSelect.innerHTML += `<option value="${id}">${beats[id]}</option>`;
                                        });
                                    }
                                });
                        } else {
                            beatSelect.innerHTML = '<option value="">All Beats</option>';
                        }
                    });
                }

                const beatSelect = document.getElementById('beatSelect');
                if (beatSelect) {
                    beatSelect.addEventListener('change', function () {
                        // Site filter only now
                    });
                }
            });

            function initMap() {
                map = L.map('map', {
                    center: [20.5937, 78.9629],
                    zoom: 5,
                    zoomControl: false,
                    renderer: L.canvas()
                });

                L.control.zoom({ position: 'bottomleft' }).addTo(map);

                const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                const googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
                    maxZoom: 20,
                    subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
                });

                L.control.layers({ "Street": osm, "Satellite": googleSat }, {}, { position: 'topleft' }).addTo(map);

                geofenceLayers.addTo(map);
            }

            function resetMapScope(spatialChanged = true) {
                Object.keys(layerGroups).forEach(id => {
                    map.removeLayer(layerGroups[id]);
                });
                layerGroups = {};
                loadedLayers = {};

                if (spatialChanged) {
                    geofenceLayers.clearLayers();
                }

                document.querySelectorAll('[id^="count_"]').forEach(el => el.textContent = '0');
                document.querySelectorAll('.layer-toggle').forEach(cb => {
                    const isGeo = cb.value === 'geofences';
                    cb.checked = isGeo;
                    updateLayerUIState(cb.value, isGeo);
                });
            }

            function loadLayerCounts() {
                document.getElementById('customLoader').style.display = 'flex';
                const formData = new FormData(document.getElementById('filterForm'));
                const params = new URLSearchParams(formData);
                params.append('only_counts', '1');

                fetch(`{{ route('know-your-area.data') }}?${params.toString()}`)
                    .then(res => res.json())
                    .then(response => {
                        document.getElementById('customLoader').style.display = 'none';
                        if (response.status === 'SUCCESS') {
                            const counts = response.counts || {};
                            Object.keys(counts).forEach(id => {
                                const el = document.getElementById('count_' + id);
                                if (el) el.textContent = counts[id];
                            });

                            if (response.geofences) {
                                const elGeo = document.getElementById('count_geofences');
                                if (elGeo) elGeo.textContent = response.geofences.length;
                                processGeofences(response.geofences);
                            }
                        }
                    });
            }

            function handleLayerToggle(layerType, show) {
                updateLayerUIState(layerType, show);

                if (layerType === 'geofences') {
                    if (show) map.addLayer(geofenceLayers);
                    else map.removeLayer(geofenceLayers);
                    return;
                }

                if (show) {
                    if (loadedLayers[layerType]) {
                        map.addLayer(layerGroups[layerType]);
                    } else {
                        fetchLayerData(layerType);
                    }
                } else if (layerGroups[layerType]) {
                    map.removeLayer(layerGroups[layerType]);
                }
            }

            function fetchLayerData(layerType) {
                const spinner = document.getElementById('spinner_' + layerType);
                if (spinner) spinner.style.display = 'inline-block';

                const formData = new FormData(document.getElementById('filterForm'));
                const params = new URLSearchParams(formData);
                params.append('layer_types[]', layerType);

                fetch(`{{ route('know-your-area.data') }}?${params.toString()}`)
                    .then(res => res.json())
                    .then(response => {
                        if (spinner) spinner.style.display = 'none';
                        if (response.status === 'SUCCESS' && response.data[layerType]) {
                            const group = processFeatures(layerType, response.data[layerType]);
                            layerGroups[layerType] = group;
                            loadedLayers[layerType] = true;
                            group.addTo(map);
                            fitMapToVisibleLayers();
                        }
                    });
            }

            function processFeatures(layerType, features) {
                const group = L.featureGroup();
                const style = LAYER_STYLES[layerType] || { color: '#333' };
                const iconEmoji = LAYER_ICONS[layerType] || '📍';

                L.geoJSON({ type: 'FeatureCollection', features: features }, {
                    style: function (f) { return style; },
                    pointToLayer: function (f, latlng) {
                        return L.marker(latlng, {
                            icon: L.divIcon({
                                className: 'custom-emoji-marker',
                                html: `<div style="font-size:24px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">${iconEmoji}</div>`,
                                iconSize: [30, 30],
                                iconAnchor: [15, 30]
                            })
                        });
                    },
                    onEachFeature: function (f, layer) {
                        layer.bindPopup(createPopup(layerType, f.properties));
                    }
                }).addTo(group);

                return group;
            }

            function processGeofences(geofences) {
                geofences.forEach(geo => {
                    let layer;
                    const lat = parseFloat(geo.latitude || geo.lat);
                    const lng = parseFloat(geo.longitude || geo.lng);

                    if (geo.type === 'Circle' && lat && lng) {
                        layer = L.circle([lat, lng], {
                            ...LAYER_STYLES.geofences,
                            radius: parseFloat(geo.radius)
                        });
                    } else if (geo.poly_lat_lng) {
                        const coords = typeof geo.poly_lat_lng === 'string' ? JSON.parse(geo.poly_lat_lng) : geo.poly_lat_lng;
                        const path = coords.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
                        layer = L.polygon(path, LAYER_STYLES.geofences);
                    }

                    if (layer) {
                        layer.bindPopup(createPopup('COMPARTMENT', { name: geo.name, type: geo.type, beat: geo.site_name }));
                        geofenceLayers.addLayer(layer);
                    }
                });

                if (geofences.length > 0) fitMapToVisibleLayers();
            }

            function createPopup(type, props) {
                const color = LAYER_STYLES[type.toLowerCase()]?.color || '#10b981';
                let content = `<div class="popup-header" style="background:${color}">${type.toUpperCase()}</div><div class="popup-body">`;

                Object.keys(props).forEach(k => {
                    if (['id', 'layer_type', 'coordinates', 'geometry_type'].includes(k)) return;
                    const val = props[k];
                    if (val === null || val === undefined || val === '') return;

                    content += `<div class="popup-row"><div class="popup-label">${k.replace(/_/g, ' ')}</div><div class="popup-value">${val}</div></div>`;
                });

                content += '</div>';
                return content;
            }

            function fitMapToVisibleLayers() {
                const bounds = L.latLngBounds();
                let hasVisible = false;

                if (geofenceLayers.getLayers().length > 0 && map.hasLayer(geofenceLayers)) {
                    bounds.extend(geofenceLayers.getBounds());
                    hasVisible = true;
                }

                Object.keys(layerGroups).forEach(id => {
                    if (map.hasLayer(layerGroups[id])) {
                        bounds.extend(layerGroups[id].getBounds());
                        hasVisible = true;
                    }
                });

                if (hasVisible) map.fitBounds(bounds, { padding: [50, 50] });
            }
        </script>
    @endpush
@endsection