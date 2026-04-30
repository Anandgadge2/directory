@extends('layouts.app')

@section('content')
<!-- Global Filters -->
@include('partials.global-filters')

<!-- Guard Header -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        @if($guard->profile_pic)
                        <img src="{{ asset('storage/profiles/' . $guard->profile_pic) }}" 
                             class="rounded-circle border-primary" 
                             style="width: 120px; height: 120px; object-fit: cover; border: 3px solid;" 
                             alt="{{ $guard->name }}">
                        @else
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                             style="width: 120px; height: 120px;">
                            <i class="fas fa-user-tie text-white fa-3x"></i>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-7">
                        <h3 class="mb-1">{{ \App\Helpers\FormatHelper::formatName($guard->name) }}</h3>
                        <p class="text-muted mb-2">
                            <i class="fas fa-id-badge"></i> ID: {{ $guard->gen_id }}
                            @if($guard->designation) • <i class="fas fa-briefcase"></i> {{ $guard->designation }} @endif
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-phone"></i> {{ $guard->contact }}
                            @if($guard->email) • <i class="fas fa-envelope"></i> {{ $guard->email }} @endif
                        </p>
                        @if($guard->address)
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt"></i> {{ $guard->address }}
                        </p>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="bg-primary bg-opacity-10 rounded p-2">
                                    <h5 class="mb-0 text-primary">{{ $stats['total_sessions'] }}</h5>
                                    <small class="text-muted">Total Sessions</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-success bg-opacity-10 rounded p-2">
                                    <h5 class="mb-0 text-success">{{ $stats['completed_sessions'] }}</h5>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-info bg-opacity-10 rounded p-2">
                                    <h5 class="mb-0 text-info">{{ $stats['total_distance_km'] }} km</h5>
                                    <small class="text-muted">Total Distance</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-warning bg-opacity-10 rounded p-2">
                                    <h5 class="mb-0 text-warning">{{ $stats['sites_covered'] }}</h5>
                                    <small class="text-muted">Sites Covered</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Performance Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-block">
                                <i class="fas fa-route text-primary fa-2x"></i>
                            </div>
                            <h6 class="mt-2">Total Sessions</h6>
                            <h4 class="mb-0">{{ $stats['total_sessions'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-block">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                            <h6 class="mt-2">Completed</h6>
                            <h4 class="mb-0">{{ $stats['completed_sessions'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3 d-inline-block">
                                <i class="fas fa-clock text-warning fa-2x"></i>
                            </div>
                            <h6 class="mt-2">In Progress</h6>
                            <h4 class="mb-0">{{ $stats['active_sessions'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="bg-info bg-opacity-10 rounded-circle p-3 d-inline-block">
                                <i class="fas fa-road text-info fa-2x"></i>
                            </div>
                            <h6 class="mt-2">Distance (KM)</h6>
                            <h4 class="mb-0">{{ $stats['total_distance_km'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="bg-purple bg-opacity-10 rounded-circle p-3 d-inline-block">
                                <i class="fas fa-hourglass-half text-purple fa-2x"></i>
                            </div>
                            <h6 class="mt-2">Patrol Hours</h6>
                            <h4 class="mb-0">{{ $stats['total_patrol_hours'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="bg-secondary bg-opacity-10 rounded-circle p-3 d-inline-block">
                                <i class="fas fa-chart-line text-secondary fa-2x"></i>
                            </div>
                            <h6 class="mt-2">Avg Duration</h6>
                            <h4 class="mb-0">{{ $stats['avg_session_duration'] }}h</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assigned Sites and Regions -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Assigned Sites</h5>
            </div>
            <div class="card-body">
                @if($assignedSites->count() > 0)
                    @foreach($assignedSites as $site)
                    <div class="border-bottom pb-2 mb-2">
                        <h6 class="mb-1">{{ $site->site_name }}</h6>
                        <p class="text-muted mb-1 small">
                            <i class="fas fa-building"></i> {{ $site->client_name }}
                            @if($site->shift_name) • <i class="fas fa-clock"></i> {{ $site->shift_name }} @endif
                        </p>
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($site->start_date)->format('M j, Y') }} - 
                            {{ \Carbon\Carbon::parse($site->end_date)->format('M j, Y') }}
                        </p>
                        @if($site->site_address)
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-map-marker-alt"></i> {{ $site->site_address }}
                        </p>
                        @endif
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">No assigned sites found</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Guard Regions</h5>
            </div>
            <div class="card-body">
                @if($guardRegions->count() > 0)
                    @foreach($guardRegions as $region)
                    <div class="border-bottom pb-2 mb-2">
                        <h6 class="mb-1">{{ $region->name ?? 'Unnamed Region' }}</h6>
                        <p class="text-muted mb-1 small">
                            <i class="fas fa-map"></i> {{ $region->type }}
                            @if($region->radius) • <i class="fas fa-ruler"></i> {{ $region->radius }}m radius @endif
                        </p>
                        @if($region->site_name)
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-location-dot"></i> {{ $region->site_name }}
                        </p>
                        @endif
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">No guard regions found</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Patrol Sessions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Patrol Sessions</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="searchSessions" placeholder="Search sessions..." style="width: 200px;">
                    <select class="form-select form-select-sm" id="filterStatus" style="width: 150px;">
                        <option value="">All Status</option>
                        <option value="Completed">Completed</option>
                        <option value="In Progress">In Progress</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Type</th>
                                <th>Session</th>
                                <th>Site</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Duration</th>
                                <th>Distance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sessionsTableBody">
                            @foreach($sessions as $session)
                            <tr class="session-row" data-status="{{ $session->status }}">
                                <td><strong>#{{ $session->session_id }}</strong></td>
                                <td><span class="badge bg-info">{{ $session->type }}</span></td>
                                <td><span class="badge bg-primary">{{ $session->session }}</span></td>
                                <td>{{ $session->site_name ?? 'Unknown' }}</td>
                                <td>{{ \Carbon\Carbon::parse($session->started_at)->format('M j, Y H:i') }}</td>
                                <td>{{ $session->ended_at ? \Carbon\Carbon::parse($session->ended_at)->format('M j, Y H:i') : 'In Progress' }}</td>
                                <td>{{ floor($session->duration_minutes / 60) }}h {{ $session->duration_minutes % 60 }}m</td>
                                <td>{{ $session->distance_km }} km</td>
                                <td>
                                    <span class="badge bg-{{ $session->status == 'Completed' ? 'success' : 'warning' }}">
                                        {{ $session->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($session->path_geojson)
                                        <button type="button" class="btn btn-outline-primary view-path-btn" 
                                                data-session-id="{{ $session->session_id }}"
                                                data-path-geojson="{{ $session->path_geojson }}"
                                                data-start-lat="{{ $session->start_lat }}"
                                                data-start-lng="{{ $session->start_lng }}">
                                            <i class="fas fa-map-marked-alt"></i> View Path
                                        </button>
                                        @endif
                                        <button type="button" class="btn btn-outline-info session-details-btn" 
                                                data-session-id="{{ $session->session_id }}">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $sessions->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Patrol Logs -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Patrol Logs (Last 50)</h5>
            </div>
            <div class="card-body">
                @if($patrolLogs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Type</th>
                                    <th>Notes</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($patrolLogs as $log)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y H:i:s') }}</td>
                                    <td><span class="badge bg-secondary">{{ $log->type }}</span></td>
                                    <td>{{ $log->notes ?? 'No notes' }}</td>
                                    <td>
                                        @if($log->lat && $log->lng)
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> {{ $log->lat }}, {{ $log->lng }}
                                        </small>
                                        @else
                                        <span class="text-muted">No location</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No patrol logs found</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Path View Modal -->
<div class="modal fade" id="pathModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Patrol Path View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="pathMap" style="height: 500px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Session Details Modal -->
<div class="modal fade" id="sessionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Session Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sessionModalBody">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
// Search and filter functionality
document.getElementById('searchSessions').addEventListener('input', filterSessions);
document.getElementById('filterStatus').addEventListener('change', filterSessions);

function filterSessions() {
    const searchTerm = document.getElementById('searchSessions').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;

    document.querySelectorAll('.session-row').forEach(row => {
        const status = row.dataset.status;
        const text = row.textContent.toLowerCase();

        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Path view functionality
document.querySelectorAll('.view-path-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const sessionId = this.dataset.sessionId;
        const pathGeoJson = this.dataset.pathGeojson;
        const startLat = this.dataset.startLat;
        const startLng = this.dataset.startLng;
        
        showPathModal(sessionId, pathGeoJson, startLat, startLng);
    });
});

function showPathModal(sessionId, pathGeoJson, startLat, startLng) {
    const modal = new bootstrap.Modal(document.getElementById('pathModal'));
    
    // Initialize map when modal is shown
    document.getElementById('pathModal').addEventListener('shown.bs.modal', function() {
        if (!window.pathMap) {
            window.pathMap = L.map('pathMap').setView([startLat || 22.5, startLng || 78.5], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(window.pathMap);
        } else {
            window.pathMap.setView([startLat || 22.5, startLng || 78.5], 13);
        }
        
        // Clear existing layers
        window.pathMap.eachLayer(function(layer) {
            if (layer instanceof L.GeoJSON || layer instanceof L.Marker) {
                window.pathMap.removeLayer(layer);
            }
        });
        
        // Add path
        try {
            const geoJson = JSON.parse(pathGeoJson);
            const pathLayer = L.geoJSON(geoJson, {
                color: '#2e7d32',
                weight: 4,
                opacity: 0.8
            }).addTo(window.pathMap);
            
            // Add start marker
            if (startLat && startLng) {
                L.marker([startLat, startLng])
                    .addTo(window.pathMap)
                    .bindPopup('Start Point')
                    .openPopup();
            }
            
            // Fit bounds to show the entire path
            if (pathLayer.getBounds().isValid()) {
                window.pathMap.fitBounds(pathLayer.getBounds(), { padding: [20, 20] });
            }
        } catch(e) {
            console.error('Error parsing GeoJSON:', e);
        }
    }, { once: true });
    
    modal.show();
}

// Session details functionality
document.querySelectorAll('.session-details-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const sessionId = this.dataset.sessionId;
        showSessionDetails(sessionId);
    });
});

function showSessionDetails(sessionId) {
    fetch(`/api/patrol-session/${sessionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Session not found');
                return;
            }
            
            const session = data.session;
            const logs = data.logs || [];
            
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

            const modalBody = document.getElementById('sessionModalBody');
            modalBody.innerHTML = `
                <div class="session-details-container px-1">
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
                                <h4 class="metric-value mb-0 small-on-mobile">${distanceFormatted}</h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="metric-card h-100">
                                <div class="metric-icon bg-soft-success text-success">
                                    <i class="bi bi-clock-fill"></i>
                                </div>
                                <span class="metric-label">Duration</span>
                                <h4 class="metric-value mb-0 small-on-mobile">${durationHours}h ${durationMinutes}m</h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="metric-card h-100">
                                <div class="metric-icon bg-soft-info text-info">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <span class="metric-label">Avg Speed</span>
                                <h4 class="metric-value mb-0 small-on-mobile">${avgSpeed} <small class="fw-normal">km/h</small></h4>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-3">
                        <!-- Guard & Location Info -->
                        <div class="col-md-6 border-end-md">
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
                                        <i class="bi bi-info-circle text-muted"></i>
                                        <span class="text-muted small">Type:</span>
                                        <span class="fw-medium">${session.type || 'N/A'} • ${session.session || 'N/A'}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Timeline & Logs -->
                        <div class="col-md-6">
                            <div class="info-group mb-4">
                                <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Timeline</label>
                                <div class="timeline-visual d-flex align-items-center mb-2">
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
                                    <label class="text-muted small text-uppercase fw-bold mb-0">Recent Logs (${logs.length})</label>
                                    <button class="btn btn-sm p-0 text-primary extra-small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#sessionLogsCollapse">Toggle</button>
                                </div>
                                <div class="collapse show" id="sessionLogsCollapse">
                                    <div class="logs-minimal-list overflow-auto px-1" style="max-height: 100px;">
                                        ${logs.slice(0, 5).map(log => `
                                            <div class="log-item py-1 border-bottom border-light d-flex justify-content-between">
                                                <span class="fw-bold extra-small text-truncate" style="max-width: 70%">${log.type}</span>
                                                <span class="text-muted extra-small">${new Date(log.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                <style>
                    .small-on-mobile { font-size: 1.1rem; }
                    @media (max-width: 576px) { .small-on-mobile { font-size: 0.9rem; } }
                </style>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('sessionModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching session details:', error);
            alert('Error loading session details');
        });
}
</script>

@endsection
