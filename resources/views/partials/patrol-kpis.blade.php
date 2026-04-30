<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card kpi-card text-center p-3">
            <small class="text-muted">Total Sessions</small>
            <h4 class="fw-bold mb-0">{{ $stats['total_sessions'] ?? 0 }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card kpi-card text-center p-3">
            <small class="text-muted">Completed</small>
            <h4 class="fw-bold mb-0 text-success">{{ $stats['completed_sessions'] ?? 0 }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card kpi-card text-center p-3">
            <small class="text-muted">Active</small>
            <h4 class="fw-bold mb-0 text-warning">{{ $stats['active_sessions'] ?? 0 }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card kpi-card text-center p-3">
            <small class="text-muted">Distance (KM)</small>
            <h4 class="fw-bold mb-0 text-info">{{ $stats['total_distance_km'] ?? 0 }}</h4>
        </div>
    </div>
</div>
