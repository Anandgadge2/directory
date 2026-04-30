@extends('layouts.app')

@section('content')

{{-- ================= KPIs ================= --}}
<div class="row g-3 mb-4">
@foreach([
    ['Total Sessions',$kpis['total_sessions']],
    ['Completed',$kpis['completed']],
    ['Ongoing',$kpis['ongoing']],
    ['Active Guards',$kpis['active_guards']],
    ['Distance (KM)',number_format($kpis['total_distance'],2)]
] as [$label,$value])
<div class="col-md">
    <div class="card p-3 text-center shadow-sm h-100">
        <small class="text-muted">{{ $label }}</small>
        <h4 class="fw-bold mt-1">{{ $value }}</h4>
    </div>
</div>
@endforeach
</div>

{{-- ================= TABLE ================= --}}
<div class="card p-3 mb-4">
    <h6 class="fw-bold mb-2">Night Patrol Sessions</h6>

    <div class="table-responsive">
        <table class="table table-striped align-middle sortable-table">
            <thead class="table-light">
            <tr>
                <th data-sortable>Guard</th>
                <th data-sortable>Type</th>
                <th data-sortable data-type="number">Start Time</th>
                <th data-sortable data-type="number">End Time</th>
                <th data-sortable data-type="number">Distance (KM)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($patrols as $p)
                <tr data-session-id="{{ $p->session_id }}" data-guard-id="{{ $p->user_id }}" class="patrol-row">
                    <td>
                        @if(isset($p->user_id))
                            <x-guard-name :guard-id="$p->user_id" :name="$p->guard" />
                        @else
                            {{ \App\Helpers\FormatHelper::formatName($p->guard) }}
                        @endif
                    </td>
                    <td>{{ $p->type }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->started_at)->format('d M h:i A') }}</td>
                    <td>
                        {{ $p->ended_at
                            ? \Carbon\Carbon::parse($p->ended_at)->format('h:i A')
                            : '—'
                        }}
                    </td>
                    <td>{{ number_format($p->distance,2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No night patrol data</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $patrols->links('pagination::bootstrap-4') }}
</div>

{{-- ================= SPEED GRAPH ================= --}}
<div class="card p-3 mb-4">
    <h6 class="fw-bold">Guard Patrol Speed (KM/H)</h6>

    <div class="chart-scroll height:240px;">
        <canvas id="speedChart"></canvas>
    </div>
</div>

{{-- ================= DISTANCE GRAPH ================= --}}
<div class="card p-3 mb-4">
    <h6 class="fw-bold">Total Night Patrolling by Guard (KM)</h6>

    <div class="chart-scroll height:360px;">
        <canvas id="nightDistanceChart"></canvas>
    </div>
</div>

{{-- ================= HEATMAP ================= --}}
<!-- <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Night Patrol Heatmap (6 PM - 6 AM)</h6>
        <div>
            <small class="text-muted">Showing patrol sessions from 18:00 to 06:00</small>
            <button class="btn btn-sm btn-outline-primary ms-2" onclick="testHeatmap()">Test Heatmap</button>
        </div>
    </div>
    <div id="nightHeatMap" style="height:450px;border-radius:8px;"></div>
    <div class="mt-2 text-muted small">
        <i class="bi bi-info-circle"></i> Night patrol sessions: {{ $nightHeatmap->count() }} found | Hold Ctrl + Scroll to zoom
    </div>
</div> -->

<style>
.chart-scroll {
    overflow-x: auto;
}
#speedChart,
#nightDistanceChart {
    min-width: 1600px;
    height: 360px !important;
}
/* Hint for Ctrl+scroll zoom */
#nightHeatMap::after {
    content: 'Hold Ctrl + Scroll to zoom';
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255,255,255,0.85);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: #333;
    pointer-events: none;
    z-index: 1000;
}
</style>

@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- ================= SPEED CHART ================= --}}
<script>
new Chart(document.getElementById('speedChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($speedStats->pluck('guard')) !!},
        datasets: [{
            data: {!! json_encode($speedStats->pluck('speed')) !!},
            backgroundColor: '#1565c0',
            barThickness: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
            },
            y: { beginAtZero: true }
        },
        plugins: { legend: { display: false } }
    }
});
</script>

{{-- ================= DISTANCE CHART ================= --}}
<script>
new Chart(document.getElementById('nightDistanceChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($nightDistanceByGuard->pluck('guard')) !!},
        datasets: [{
            data: {!! json_encode($nightDistanceByGuard->pluck('total_distance')) !!},
            backgroundColor: '#2e7d32',
            barThickness: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
            },
            y: { beginAtZero: true }
        },
        plugins: { legend: { display: false } }
    }
});
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

<script>
// Global variables
let heatMap;
let currentTileLayer;
let satelliteTileLayer;

// Function to normalize GeoJSON path data (from kml-view)
function normalizePathGeoJson(raw) {
    let data = raw;
    if (!data) return null;

    // Parse JSON string
    if (typeof data === 'string') {
        try {
            data = JSON.parse(data);
        } catch {
            return null;
        }
    }

    let coords = [];

    /* Case 1: Proper GeoJSON */
    if (data && typeof data === 'object' && data.type) {
        if (data.type === 'LineString' && Array.isArray(data.coordinates)) {
            coords = data.coordinates
                .map(c => [Number(c[0]), Number(c[1])])
                .filter(c => Number.isFinite(c[0]) && Number.isFinite(c[1]));
            if (coords.length > 0) {
                return { type: 'LineString', coordinates: coords };
            }
        }

        if (data.type === 'MultiLineString' && Array.isArray(data.coordinates)) {
            const allCoords = [];
            data.coordinates.forEach(line => {
                if (Array.isArray(line)) {
                    line.forEach(c => {
                        if (Array.isArray(c) && c.length >= 2) {
                            const coord = [Number(c[0]), Number(c[1])];
                            if (Number.isFinite(coord[0]) && Number.isFinite(coord[1])) {
                                allCoords.push(coord);
                            }
                        }
                    });
                }
            });
            if (allCoords.length > 0) {
                return { type: 'LineString', coordinates: allCoords };
            }
        }
    }

    return null;
}

// Initialize map (based on kml-view pattern)
function initNightMap() {
    console.log('Initializing night patrol map...');
    
    heatMap = L.map('nightHeatMap', {
        center: [22.5, 78.5],
        zoom: 7,
        zoomControl: true,
        scrollWheelZoom: false,
        dragging: true
    });
    
    // Enable zoom with Ctrl+scroll
    heatMap.on('wheel', function(e) {
        if (e.originalEvent.ctrlKey) {
            e.originalEvent.preventDefault();
            const delta = e.originalEvent.deltaY;
            if (delta > 0) {
                heatMap.setZoom(heatMap.getZoom() - 1);
            } else {
                heatMap.setZoom(heatMap.getZoom() + 1);
            }
        }
    });
    
    // Handle Ctrl+scroll via keyboard events
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            heatMap.scrollWheelZoom.enable();
        }
    });
    
    document.addEventListener('keyup', function(e) {
        if (!e.ctrlKey) {
            heatMap.scrollWheelZoom.disable();
        }
    });

    // Default tile layer
    currentTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(heatMap);

    console.log('Map initialized successfully');
}

// Load and process night patrol heatmap data
function loadNightHeatmap() {
    console.log('Loading night patrol heatmap data...');
    console.log('Total sessions to process: {{ $nightHeatmap->count() }}');
    
    let heatPoints = [];
    let processedSessions = 0;
    let sessionIndex = 0;

    @php($index = 0)
    @foreach($nightHeatmap as $h)
    try {
        sessionIndex++;
        console.log('Processing session ' + sessionIndex + '...');
        
        let rawPath = @json($h->path_geojson);
        if (typeof rawPath === 'string') {
            rawPath = JSON.parse(rawPath);
        }
        
        const geoJson = normalizePathGeoJson(rawPath);
        if (!geoJson) {
            console.warn('Failed to normalize GeoJSON for session ' + sessionIndex);
            continue;
        }
        
        // Convert coordinates to heat points
        if (geoJson.coordinates && Array.isArray(geoJson.coordinates)) {
            geoJson.coordinates.forEach(coord => {
                if (Array.isArray(coord) && coord.length >= 2) {
                    // Convert [lng, lat] to [lat, lng] for heatmap
                    heatPoints.push([coord[1], coord[0], 0.8]);
                }
            });
            processedSessions++;
        }
        
    } catch(e) {
        console.error('Error processing session ' + sessionIndex + ':', e);
    }
    @php($index++)
    @endforeach

    console.log('Processed ' + processedSessions + ' sessions');
    console.log('Generated ' + heatPoints.length + ' heat points');

    // Add heatmap to map
    if (heatPoints.length > 0) {
        try {
            const heatLayer = L.heatLayer(heatPoints, {
                radius: 32,
                blur: 25,
                maxZoom: 10,
                minOpacity: 0.5
            });
            
            heatLayer.addTo(heatMap);
            console.log('Night patrol heatmap loaded successfully!');
            
            // Fit map to show all heat points
            if (heatPoints.length > 0) {
                const bounds = L.latLngBounds(heatPoints.map(p => [p[0], p[1]]));
                heatMap.fitBounds(bounds, { padding: [20, 20] });
            }
            
        } catch(heatError) {
            console.error('Error adding heatmap layer:', heatError);
            alert('Error adding heatmap: ' + heatError.message);
        }
    } else {
        console.warn('No heat points generated - showing demo data');
        
        // Add demo data
        const demoPoints = [
            [22.5, 78.5, 0.3],
            [22.51, 78.51, 0.3],
            [22.49, 78.49, 0.3]
        ];
        
        L.heatLayer(demoPoints, {
            radius: 25,
            blur: 20,
            maxZoom: 10,
            minOpacity: 0.2
        }).addTo(heatMap);
        
        L.control.attribution({prefix: false}).addAttribution('No night patrol data available (6 PM - 6 AM)').addTo(heatMap);
    }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing night patrol explorer...');
    
    // Check if heat plugin is available
    if (typeof L.heatLayer === 'undefined') {
        console.error('Leaflet heat plugin not loaded!');
        alert('Heatmap plugin not loaded. Please refresh the page.');
        return;
    }
    
    // Initialize map
    initNightMap();
    
    // Load heatmap data
    setTimeout(() => {
        loadNightHeatmap();
    }, 500);
});

// Test function
function testHeatmap() {
    console.log('Testing heatmap functionality...');
    
    if (typeof heatMap === 'undefined' || !heatMap) {
        alert('Map not initialized!');
        return;
    }
    
    // Clear existing heat layers
    heatMap.eachLayer(function(layer) {
        if (layer instanceof L.HeatLayer) {
            heatMap.removeLayer(layer);
        }
    });
    
    // Add test points
    const testPoints = [
        [22.5, 78.5, 0.8],
        [22.51, 78.51, 0.8],
        [22.49, 78.49, 0.8],
        [22.52, 78.52, 0.8],
        [22.48, 78.48, 0.8]
    ];
    
    try {
        L.heatLayer(testPoints, {
            radius: 32,
            blur: 25,
            maxZoom: 10,
            minOpacity: 0.5
        }).addTo(heatMap);
        
        alert('Test heatmap added! You should see 5 red heat points.');
    } catch(e) {
        alert('Error adding test heatmap: ' + e.message);
    }
}
</script>

{{-- ================= STYLE ================= --}}
<style>
.chart-scroll {
    overflow-x: auto;
}
#speedChart,
#nightDistanceChart {
    min-width: 1600px;
    height: 360px !important;
}
/* Hint for Ctrl+scroll zoom */
#nightHeatMap::after {
    content: 'Hold Ctrl + Scroll to zoom';
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255,255,255,0.85);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: #333;
    pointer-events: none;
    z-index: 1000;
}
</style>



@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- ================= SPEED CHART ================= --}}
<script>
new Chart(document.getElementById('speedChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($speedStats->pluck('guard')) !!},
        datasets: [{
            data: {!! json_encode($speedStats->pluck('speed')) !!},
            backgroundColor: '#1565c0',
            barThickness: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
            },
            y: { beginAtZero: true }
        },
        plugins: { legend: { display: false } }
    }
});
</script>

{{-- ================= DISTANCE CHART ================= --}}
<script>
new Chart(document.getElementById('nightDistanceChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($nightDistanceByGuard->pluck('guard')) !!},
        datasets: [{
            data: {!! json_encode($nightDistanceByGuard->pluck('total_distance')) !!},
            backgroundColor: '#2e7d32',
            barThickness: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
            },
            y: { beginAtZero: true }
        },
        plugins: { legend: { display: false } }
    }
});
</script>

@endpush
