{{-- Key Performance Indicators --}}
<div class="row g-3 mb-4">
    {{-- 1. Active Guards - Opens Modal --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#activeGuardsModal" 
             style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="text-uppercase extra-small fw-bold mb-1">Active Forest Guards</h6>
                        <h2 class="mb-0 fw-bold">{{ number_format($kpis['activeGuards'] ?? 0) }}</h2>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Total Patrols - Opens Modal --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#patrolAnalyticsModal" 
             style="cursor: pointer;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Patrols</h6>
                        <h3 class="mb-0 fw-bold text-dark kpi-value" data-kpi="totalPatrols">{{ number_format($kpis['totalPatrols'] ?? 0) }}</h3>
                        <div class="mt-2 small text-muted"><span class="fw-bold">{{ $kpis['completedPatrols'] ?? 0 }}</span> completed</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-person-walking"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Total Distance - Opens Modal --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#totalDistanceModal" 
             style="cursor: pointer;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Distance</h6>
                        <h3 class="mb-0 fw-bold text-dark kpi-value" data-kpi="totalDistance">{{ number_format($kpis['totalDistance'] ?? 0, 2) }} <small class="fs-6 fw-normal">km</small></h3>
                        <div class="mt-2 small text-muted">Avg: {{ number_format($kpis['avgDistancePerGuard'] ?? 0, 2) }} km/forest guard</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Attendance Rate - Modal Trigger --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#attendanceDetailsModal"
             style="cursor: pointer;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Attendance Rate</h6>
                        <h3 class="mb-0 fw-bold text-dark kpi-value" data-kpi="attendanceRate">{{ number_format($kpis['attendanceRate'] ?? 0, 1) }}%</h3>
                        <div class="mt-2 small text-muted"><span class="fw-bold" data-kpi="presentCountToday">{{ $kpis['presentCountToday'] ?? 0 }}</span> guards present today</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. Total Incidents - Modal trigger --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#totalIncidentsModal" 
             style="cursor: pointer;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Incidents</h6>
                        <h3 class="mb-0 fw-bold text-dark kpi-value" data-kpi="totalIncidents">{{ number_format($kpis['totalIncidents'] ?? 0) }}</h3>
                        <div class="mt-2 small text-muted"><span class="fw-bold text-danger" data-kpi="pendingIncidents">{{ $kpis['pendingIncidents'] ?? 0 }}</span> pending review</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 6. Resolution Rate - Opens Modal --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#resolutionRateModal" 
             style="cursor: pointer;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Resolution Rate</h6>
                        <h3 class="mb-0 fw-bold text-dark kpi-value" data-kpi="resolutionRate">{{ number_format($kpis['resolutionRate'] ?? 0, 1) }}%</h3>
                        <div class="mt-2 small text-muted"><span class="fw-bold text-success">{{ $kpis['resolvedIncidents'] ?? 0 }}</span> cases resolved</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-check-square-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 7. Beat Coverage - Opens Modal --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#beatCoverageModal" 
             style="cursor: pointer;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Beat Coverage</h6>
                        <h3 class="mb-0 fw-bold text-dark kpi-value" data-kpi="siteCoverage">{{ number_format(isset($coverageAnalysis) && isset($coverageAnalysis['coveragePercentage']) ? $coverageAnalysis['coveragePercentage'] : ($kpis['siteCoverage'] ?? 0), 1) }}%</h3>
                        <div class="mt-2 small text-muted"><span class="fw-bold">{{ isset($coverageAnalysis) && isset($coverageAnalysis['sitesWithPatrols']) ? $coverageAnalysis['sitesWithPatrols'] : 0 }}</span> / {{ isset($coverageAnalysis) && isset($coverageAnalysis['totalSites']) ? $coverageAnalysis['totalSites'] : ($kpis['totalSites'] ?? 0) }} active</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="bi bi-map-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 8. Total Beats - Opens Modal --}}
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card kpi-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#totalBeatsModal" 
             style="cursor: pointer;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Managed Beats</h6>
                        <h3 class="mb-0 fw-bold text-dark kpi-value" data-kpi="totalSites">{{ number_format($kpis['totalSites'] ?? 0) }}</h3>
                        <div class="mt-2 small text-muted">Across all ranges</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-tree-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>