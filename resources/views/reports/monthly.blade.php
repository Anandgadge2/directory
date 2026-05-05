@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Reports Hub</h3>
            <p class="text-muted mb-0">Advanced analytical reporting and performance tracking</p>
        </div>
        @if($reportType && $data && count($data) > 0)
        <div>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank" class="btn btn-danger d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-file-pdf fs-5"></i>
                <span class="fw-bold">Export Report</span>
            </a>
        </div>
        @endif
    </div>

    {{-- Report Selection Tabs --}}
    <div class="row g-3 mb-5">
        @php
            $types = [
                'attendance' => ['title' => 'Attendance', 'icon' => 'bi-calendar-check', 'color' => 'primary', 'desc' => 'Daily cycles & shift compliance'],
                'patrol' => ['title' => 'Day Patrol', 'icon' => 'bi-shield-shaded', 'color' => 'success', 'desc' => 'Route efficiency & coverage'],
                'night_patrol' => ['title' => 'Night Ops', 'icon' => 'bi-moon-stars-fill', 'color' => 'indigo', 'desc' => 'Night shift vigilance tracking'],
                'incident' => ['title' => 'Incidents', 'icon' => 'bi-exclamation-octagon-fill', 'color' => 'danger', 'desc' => 'Security & wildlife sightings']
            ];
        @endphp

        @foreach($types as $key => $info)
        <div class="col-md-3">
            <a href="{{ url()->current() }}?report_type={{ $key }}&start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&range={{ request('range') }}&beat={{ request('beat') }}" 
               class="text-decoration-none h-100 d-block">
                <div class="card h-100 border-0 shadow-sm transition-hover {{ $reportType == $key ? 'bg-'.$info['color'].' text-white' : 'bg-white' }}">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="icon-shape rounded-3 {{ $reportType == $key ? 'bg-white text-'.$info['color'] : 'bg-light text-'.$info['color'] }}">
                                <i class="bi {{ $info['icon'] }} fs-4"></i>
                            </div>
                            <h5 class="fw-bold mb-0">{{ $info['title'] }}</h5>
                        </div>
                        <p class="small mb-0 {{ $reportType == $key ? 'text-white-50' : 'text-muted' }}">{{ $info['desc'] }}</p>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>


    @if($reportType)
        {{-- Analytical Summaries --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart text-primary"></i> Performance Summary
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-report-table sortable-table">
                        <thead class="bg-light sticky-top">
                            @if($reportType == 'attendance')
                                <tr>
                                    <th class="ps-4" data-sortable data-type="number">Rank</th>
                                    <th class="text-start" data-sortable>Forest Guard Name</th>
                                    <th data-sortable>Days Active</th>
                                    <th data-sortable data-type="number">Late Marks</th>
                                    <th data-sortable data-type="number">Absents</th>
                                    <th class="pe-4" data-sortable data-type="number">Compliance</th>
                                </tr>
                            @elseif($reportType == 'patrol' || $reportType == 'night_patrol')
                                <tr>
                                    <th class="ps-4" data-sortable>Forest Guard Name</th>
                                    <th data-sortable data-type="number">Total Sessions</th>
                                    <th data-sortable data-type="number">Distance covered</th>
                                    <th data-sortable data-type="number">Avg Speed</th>
                                    <th class="pe-4" data-sortable data-type="number">Total Vigilance Time</th>
                                </tr>
                            @elseif($reportType == 'incident')
                                <tr>
                                    <th class="ps-4" data-sortable>Date & Time</th>
                                    <th data-sortable>Forest Guard</th>
                                    <th data-sortable>Incident Type</th>
                                    <th data-sortable>Location</th>
                                    <th class="pe-4" data-sortable>Observation Notes</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @forelse($summary as $s)
                                @if($reportType == 'attendance')
                                    <tr>
                                        <td class="ps-4 text-muted">#{{ $loop->iteration }}</td>
                                        <td class="fw-bold text-start">{{ $s->guard_name }}</td>
                                        <td>{{ $s->present_days }} / {{ $s->total_days }}</td>
                                        <td><span class="badge {{ $s->late_count > 0 ? 'bg-soft-warning text-warning' : 'bg-soft-success text-success' }}">{{ $s->late_count }} times</span></td>
                                        <td>{{ $s->absent_days }} days</td>
                                        <td class="pe-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress grow" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $s->attendance_rate }}%"></div>
                                                </div>
                                                <span class="fw-bold">{{ $s->attendance_rate }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @elseif($reportType == 'patrol' || $reportType == 'night_patrol')
                                    <tr>
                                        <td class="ps-4 fw-bold text-start">{{ $s->guard_name }}</td>
                                        <td>{{ $s->total_sessions }}</td>
                                        <td><span class="badge bg-soft-success text-success">{{ $s->total_dist }} km</span></td>
                                        <td>{{ $s->avg_speed }} km/h</td>
                                        <td class="pe-4 text-muted">{{ $s->total_time }} hours</td>
                                    </tr>
                                @elseif($reportType == 'incident')
                                    <tr>
                                        <td class="ps-4 text-muted small">{{ \Carbon\Carbon::parse($s->created_at)->format('d M y, H:i') }}</td>
                                        <td class="fw-bold">{{ $s->guard_name }}</td>
                                        <td><span class="badge bg-soft-danger text-danger">{{ ucwords(str_replace('_', ' ', $s->type)) }}</span></td>
                                        <td>{{ $s->site_name ?? 'N/A' }}</td>
                                        <td class="pe-4 small text-muted text-wrap" style="max-width: 250px;">{{ $s->notes }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="6" class="text-center py-5 text-muted">No analysis data available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Detailed Activity Logs --}}
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="mb-0 fw-bold text-dark">Activity Audit Logs</h5>
                    @if($reportType == 'attendance' && isset($daysInRange) && $daysInRange == 1 && count($data) > 0)
                        <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#attendanceMapModal">
                            <i class="bi bi-map-fill"></i>
                            <span>View Map</span>
                        </button>
                    @endif
                </div>
                <span class="badge bg-light text-dark">Showing latest {{ count($data) }} entries</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-report-table sortable-table">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4" data-sortable>Date & Time</th>
                                <th class="text-start" data-sortable>Forest Guard</th>
                                <th data-sortable>Location (Beat)</th>
                                @if($reportType == 'attendance')
                                    <th data-sortable>Status</th>
                                    <th data-sortable>Location</th>
                                    <th data-sortable>Punch In</th>
                                    <th data-sortable>Punch Out</th>
                                    <th class="pe-4" data-sortable data-type="number">Late Mins</th>
                                @elseif($reportType == 'incident')
                                    <th data-sortable>Incident Type</th>
                                    <th class="pe-4" data-sortable>Observation Notes</th>
                                @else
                                    <th data-sortable>Mode</th>
                                    <th data-sortable data-type="number">Distance</th>
                                    <th data-sortable>Duration</th>
                                    <th class="pe-4" data-sortable data-type="number">Avg Speed</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                                <tr>
                                    <td class="ps-4 text-muted small">
                                        @if($reportType == 'attendance')
                                            {{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}
                                        @elseif($reportType == 'incident')
                                            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($row->started_at)->format('d M Y, h:i A') }}
                                        @endif
                                    </td>
                                    <td class="fw-bold text-start">{{ $row->guard_name }}</td>
                                    <td>{{ $row->site_name ?? 'N/A' }}</td>
                                    
                                    @if($reportType == 'attendance')
                                        <td>
                                            <span class="badge rounded-pill {{ $row->status == 1 ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                                                {{ $row->status == 1 ? 'Present' : 'Absent' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($row->inOutStatus == 'Inside')
                                                <span class="text-success fw-bold"><i class="bi bi-geo-alt-fill"></i> ON SITE</span>
                                            @else
                                                <span class="text-muted small">Not Marked</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row->entry_time)
                                                <span class="text-primary fw-semibold"><i class="bi bi-box-arrow-in-right"></i> {{ $row->entry_time }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row->exit_time)
                                                <span class="text-warning fw-semibold"><i class="bi bi-box-arrow-right"></i> {{ $row->exit_time }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="pe-4">
                                            @if($row->late_minutes > 0)
                                                <span class="text-danger fw-bold"><i class="bi bi-clock-history"></i> {{ $row->late_minutes }}m</span>
                                            @else
                                                <span class="text-success small">On Time</span>
                                            @endif
                                        </td>
                                    @elseif($reportType == 'incident')
                                        <td><span class="badge bg-soft-dark text-dark border">{{ ucwords(str_replace('_', ' ', $row->type)) }}</span></td>
                                        <td class="pe-4 small text-muted text-wrap" style="max-width: 300px;">{{ $row->notes ?: 'No details provided' }}</td>
                                    @else
                                        <td><span class="badge bg-light text-dark">{{ $row->mode }}</span></td>
                                        <td><span class="fw-bold text-primary">{{ $row->distance_km }} km</span></td>
                                        <td class="small">{{ $row->duration_formatted }}</td>
                                        <td class="pe-4 fw-bold">{{ $row->avg_speed }} <small class="text-muted">km/h</small></td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-5 text-muted">No transactional records found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-5">
          
            <h4 class="fw-bold text-dark">Selection Required</h4>
            <p class="text-muted">Please select a report category above to drill down into the analytics.</p>
        </div>
    @endif
</div>

<style>
/* Modern Report Hub Styles */
:root {
    --bg-indigo: #6610f2;
    --indigo: #6610f2;
}
.bg-indigo { background-color: var(--indigo) !important; }
.text-indigo { color: var(--indigo) !important; }

.transition-hover {
    transition: all 0.3s cubic-bezier(.25,.8,.25,1);
    cursor: pointer;
}
.transition-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.icon-shape {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.custom-report-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    color: #6c757d;
    border-bottom-width: 0;
}
.custom-report-table td {
    padding-top: 1rem;
    padding-bottom: 1rem;
    font-size: 0.875rem;
}

.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1) !important; }
.bg-soft-success { background-color: rgba(25, 135, 84, 0.1) !important; }
.bg-soft-danger { background-color: rgba(220, 53, 69, 0.1) !important; }
.bg-soft-warning { background-color: rgba(255, 193, 7, 0.1) !important; }
.bg-soft-dark { background-color: rgba(33, 37, 41, 0.1) !important; }

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
}
</style>

@if($reportType == 'attendance' && isset($daysInRange) && $daysInRange == 1)
@push('modals')
<div class="modal fade" id="attendanceMapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-primary"></i>
                    Attendance Geospatial View - {{ request('start_date') ?: date('d M Y') }}
                </h6>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light active" id="mapDefaultBtn">Default</button>
                        <button type="button" class="btn btn-outline-light" id="mapSatelliteBtn">Satellite</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-light" id="toggleGeofencesBtn">Hide Compartments</button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 position-relative">
                <div id="attendance-map" style="width: 100%; height: 100%; min-height: 500px;"></div>
                
                {{-- Legend Overlay --}}
                <div class="position-absolute bottom-0 start-0 m-3 p-3 bg-white shadow-sm rounded-3 border z-index-1000" style="z-index: 1000; min-width: 180px;">
                    <h6 class="fw-bold small mb-2 border-bottom pb-1">Map Legend</h6>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div style="width: 12px; height: 12px; background: #28a745; border-radius: 50%;"></div>
                        <span class="small">On Site (Geofence)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div style="width: 12px; height: 12px; background: #dc3545; border-radius: 50%;"></div>
                        <span class="small">Outside Geofence</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div style="width: 12px; height: 12px; border: 2px solid #6a1b9a; background: rgba(106, 27, 154, 0.1);"></div>
                        <span class="small">Compartment (Geofence)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let map = null;
    let defaultLayer, satelliteLayer;
    let geofenceLayerGroup = L.layerGroup();
    let markerLayerGroup = L.layerGroup();
    let isGeofenceVisible = true;

    const modal = document.getElementById('attendanceMapModal');
    if (!modal) return;

    modal.addEventListener('shown.bs.modal', function() {
        if (!map) {
            initMap();
        } else {
            map.invalidateSize();
        }
    });

    function initMap() {
        map = L.map('attendance-map', {
            center: [22.5, 78.5],
            zoom: 7,
            zoomControl: false
        });

        L.control.zoom({ position: 'topright' }).addTo(map);

        defaultLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri'
        });

        markerLayerGroup.addTo(map);
        geofenceLayerGroup.addTo(map);

        loadMapData();
        
        // Event Listeners for controls
        document.getElementById('mapDefaultBtn').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('mapSatelliteBtn').classList.remove('active');
            map.removeLayer(satelliteLayer);
            map.addLayer(defaultLayer);
        });

        document.getElementById('mapSatelliteBtn').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('mapDefaultBtn').classList.remove('active');
            map.removeLayer(defaultLayer);
            map.addLayer(satelliteLayer);
        });

        document.getElementById('toggleGeofencesBtn').addEventListener('click', function() {
            if (isGeofenceVisible) {
                map.removeLayer(geofenceLayerGroup);
                this.innerText = 'Show Compartments';
                this.classList.replace('btn-outline-light', 'btn-light');
            } else {
                map.addLayer(geofenceLayerGroup);
                this.innerText = 'Hide Compartments';
                this.classList.replace('btn-light', 'btn-outline-light');
            }
            isGeofenceVisible = !isGeofenceVisible;
        });
    }

    function loadMapData() {
        const bounds = L.latLngBounds();
        let hasData = false;

        // 1. Add Geofences
        @foreach($geofences as $g)
            @if($g->type === 'Circle' && $g->lat && $g->lng)
                (function() {
                    const circle = L.circle([{{ $g->lat }}, {{ $g->lng }}], {
                        radius: {{ $g->radius }},
                        color: '#6a1b9a',
                        fillColor: '#6a1b9a',
                        fillOpacity: 0.1,
                        weight: 2
                    }).bindPopup('<b>{{ $g->name ?: $g->site_name }}</b><br>Site: {{ $g->site_name }}<br>Range: {{ $g->range_name ?: "N/A" }}<br>Type: Geofence');
                    geofenceLayerGroup.addLayer(circle);
                    bounds.extend(circle.getBounds());
                    hasData = true;
                })();
            @elseif($g->poly_lat_lng)
                (function() {
                    try {
                        const coords = JSON.parse(@json($g->poly_lat_lng));
                        if (coords && coords.length > 0) {
                            const polygon = L.polygon(coords.map(p => [p.lat, p.lng]), {
                                color: '#6a1b9a',
                                fillColor: '#6a1b9a',
                                fillOpacity: 0.1,
                                weight: 2
                            }).bindPopup('<b>{{ $g->name ?: $g->site_name }}</b><br>Site: {{ $g->site_name }}<br>Range: {{ $g->range_name ?: "N/A" }}<br>Type: Compartment');
                            geofenceLayerGroup.addLayer(polygon);
                            bounds.extend(polygon.getBounds());
                            hasData = true;
                        }
                    } catch (e) {
                        console.error('Error parsing geofence', e);
                    }
                })();
            @endif
        @endforeach

        // 2. Add Attendance Markers
        @foreach($data as $row)
            @if($row->location)
                (function() {
                    try {
                        let loc = @json($row->location);
                        if (typeof loc === 'string') loc = JSON.parse(loc);
                        
                        if (loc && loc.lat && loc.lng) {
                            const isInside = "{{ $row->inOutStatus }}" === 'Inside';
                            const color = isInside ? '#28a745' : '#dc3545';
                            
                            const marker = L.circleMarker([loc.lat, loc.lng], {
                                radius: 8,
                                fillColor: color,
                                color: '#fff',
                                weight: 2,
                                opacity: 1,
                                fillOpacity: 0.9
                            }).bindPopup(`
                                <div style="min-width: 200px; padding: 4px;">
                                    <p style="margin: 0 0 3px; font-weight: bold; font-size: 13px;">Name: {{ $row->guard_name }}</p>
                                    <p style="margin: 0 0 3px; font-size: 12px; color: #555;">Site: {{ $row->site_name ?: 'Current Location' }}</p>
                                    <p style="margin: 0 0 3px; font-size: 12px; color: #555;">Date: {{ $row->date_display ?? \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</p>
                                    <p style="margin: 0 0 3px; font-size: 12px; color: #555;">Punch-In: {{ $row->entry_time ?: 'Not Marked' }}</p>
                                    <p style="margin: 0; font-size: 12px; color: #555;">Punch-Out: {{ $row->exit_time ?: 'Not Marked' }}</p>
                                </div>
                            `);
                            markerLayerGroup.addLayer(marker);
                            bounds.extend([loc.lat, loc.lng]);
                            hasData = true;
                        }
                    } catch (e) {
                        console.error('Error parsing location for guard {{ $row->guard_name }}', e);
                    }
                })();
            @endif
        @endforeach

        if (hasData) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }
});

// Auto-open map modal if ?open_map=1 is in the URL (from PDF link)
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('open_map') === '1') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const mapModal = document.getElementById('attendanceMapModal');
            if (mapModal) {
                const bsModal = new bootstrap.Modal(mapModal);
                bsModal.show();
            }
        }, 500);
    });
}
</script>
@endpush
@endif
@endsection
