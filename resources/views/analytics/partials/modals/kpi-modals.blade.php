{{-- KPI Details Modals --}}
<style>
    /* Premium Fixed Height/Width Modals for Executive Dashboard */
    .dashboard-modal-fixed .modal-dialog {
        max-width: 950px !important;
        width: 950px !important;
        margin: 1.75rem auto;
    }
    .dashboard-modal-fixed .modal-content {
        height: 85vh !important;
        display: flex;
        flex-direction: column;
        border-radius: 12px;
        overflow: hidden;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .dashboard-modal-fixed .modal-header {
        flex-shrink: 0;
        padding: 1.25rem 1.5rem;
    }
    .dashboard-modal-fixed .modal-body {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1.5rem;
        background-color: #f8fafc;
    }
    .dashboard-modal-fixed .modal-footer {
        flex-shrink: 0;
        padding: 1rem 1.5rem;
        background-color: #fff;
        border-top: 1px solid #e2e8f0;
    }
    
    /* Smaller centered variant for Resolution Rate */
    .dashboard-modal-centered-sm .modal-dialog {
        max-width: 600px !important;
        width: 600px !important;
    }
    .dashboard-modal-centered-sm .modal-content {
        height: auto !important;
        min-height: 400px;
    }

    @media (max-width: 992px) {
        .dashboard-modal-fixed .modal-dialog {
            max-width: 95% !important;
            width: 95% !important;
        }
    }
</style>

{{-- Modal 1: Active Guards Details --}}
<div class="modal fade dashboard-modal-fixed" id="activeGuardsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">👥 Active Forest Guard Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Total Active Guards:</strong> {{ number_format($kpis['activeGuards'] ?? 0) }}
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">SR.NO</th>
                                <th>FOREST GUARD NAME</th>
                                <th>CONTACT</th>
                                <th>EMAIL</th>
                                <th class="text-center">STATUS</th>
                            </tr>
                        </thead>
                        <tbody id="activeGuardsList">
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2">Fetching active forest guards...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal 2: Patrol Analytics --}}
<div class="modal fade dashboard-modal-fixed" id="patrolAnalyticsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">🚶 Patrol Analytics Overview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Top Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body text-center py-3">
                                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Total Patrols</h6>
                                <h2 class="mb-0 fw-bold" id="modalTotalPatrols">{{ number_format($kpis['totalPatrols']) }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body text-center py-3">
                                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Completed</h6>
                                <h3 class="mb-0 fw-bold" id="modalCompletedPatrols">{{ number_format($kpis['completedPatrols']) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-warning text-dark">
                            <div class="card-body text-center py-3">
                                <h6 class="text-dark-50 text-uppercase small fw-bold mb-2">Ongoing</h6>
                                <h3 class="mb-0 fw-bold" id="modalOngoingPatrols">{{ number_format($kpis['totalPatrols'] - $kpis['completedPatrols']) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body text-center py-3">
                                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Total Distance</h6>
                                <h3 class="mb-0 fw-bold" id="modalTotalDistance">{{ number_format($kpis['totalDistance'], 2) }} km</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completion Progress Bar -->
                <div class="card border shadow-sm mb-4 bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">Patrol Completion Rate</h6>
                            <span class="badge bg-success" id="modalCompletionRateBadge">{{ $kpis['totalPatrols'] > 0 ? number_format(($kpis['completedPatrols'] / $kpis['totalPatrols']) * 100, 1) : 0 }}%</span>
                        </div>
                        <div class="progress" style="height: 15px;">
                            <div id="modalCompletionProgressBar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $kpis['totalPatrols'] > 0 ? ($kpis['completedPatrols'] / $kpis['totalPatrols']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Day vs Night Breakdown -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-brightness-high text-warning me-2"></i>Day vs Night Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mt-2">
                                    <div class="col-6 border-end">
                                        <div class="display-6 text-warning mb-1"><i class="bi bi-sun-fill"></i></div>
                                        <h4 class="fw-bold mb-0" id="modalDayPatrols">0</h4>
                                        <small class="text-muted text-uppercase small fw-bold">Day Patrols</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="display-6 text-primary mb-1"><i class="bi bi-moon-stars-fill"></i></div>
                                        <h4 class="fw-bold mb-0" id="modalNightPatrols">0</h4>
                                        <small class="text-muted text-uppercase small fw-bold">Night Patrols</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patrol Type Breakdown -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-stars text-primary me-2"></i>Patrol Type Breakdown</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light small">
                                            <tr>
                                                <th class="ps-3 py-2">Type</th>
                                                <th class="text-center py-2">Count</th>
                                                <th class="text-end pe-3 py-2">Distance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modalPatrolTypeTable" class="small">
                                            <tr><td colspan="3" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading breakdown...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Sites by Distance -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt text-success me-2"></i>Top Sites by Patrol Distance</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light sticky-top small">
                                            <tr>
                                                <th class="ps-3 py-2" style="width: 60px;">#</th>
                                                <th class="py-2">Site Name</th>
                                                <th class="text-end pe-3 py-2">Total Distance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modalTopSitesTable" class="small">
                                            <tr><td colspan="3" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-success me-2"></div>Loading site rankings...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal 3: Resolution Rate Details --}}
<div class="modal fade dashboard-modal-fixed dashboard-modal-centered-sm" id="resolutionRateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">✅ Resolution Rate Calculation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <h4 class="text-center mb-3">Resolution Rate: <span id="modalResRateTitle" class="text-success">{{ number_format($kpis['resolutionRate'], 1) }}%</span></h4>
                        <div class="text-center mb-3">
                            <div class="progress" style="height: 30px;">
                                <div id="modalResRateProgressBar" class="progress-bar bg-success" role="progressbar" style="width: {{ $kpis['resolutionRate'] }}%">
                                    {{ number_format($kpis['resolutionRate'], 1) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">Calculation Formula:</h6>
                <div class="alert alert-info">
                    <code>Resolution Rate = (Resolved Incidents / Total Incidents) × 100</code>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Incidents</h6>
                                <h3 id="modalResRateTotal" class="text-danger">{{ number_format($kpis['totalIncidents']) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Resolved</h6>
                                <h3 id="modalResRateResolved" class="text-success">{{ number_format($kpis['resolvedIncidents']) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Pending</h6>
                                <h3 id="modalResRatePending" class="text-warning">{{ number_format($kpis['pendingIncidents']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6 class="fw-bold">Calculation:</h6>
                    <p id="modalResRateCalculation" class="mb-1">{{ number_format($kpis['resolvedIncidents']) }} ÷ {{ number_format($kpis['totalIncidents']) }} × 100 = <strong>{{ number_format($kpis['resolutionRate'], 1) }}%</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal 4: Beat Coverage Details --}}
<div class="modal fade dashboard-modal-fixed" id="beatCoverageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">🗺️ Beat Coverage Analysis</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Data Summary Cards -->
                <div class="row g-3 mb-4 text-center">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body py-2">
                                <h6 class="text-white-50 small mb-1">Total Beats</h6>
                                <h3 class="mb-0 fw-bold" id="modalCoverageTotalBeats">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body py-2">
                                <h6 class="text-white-50 small mb-1">Covered</h6>
                                <h3 class="mb-0 fw-bold" id="modalCoverageCoveredBeats">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-danger text-white">
                            <div class="card-body py-2">
                                <h6 class="text-white-50 small mb-1">Unpatrolled</h6>
                                <h3 class="mb-0 fw-bold" id="modalCoverageUnpatrolledBeats">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4 bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">Site Coverage Percentage</h6>
                            <span id="modalCoveragePercentageText" class="badge bg-info text-dark">0.0%</span>
                        </div>
                        <div class="progress" style="height: 15px;">
                            <div id="modalCoverageProgressBar" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3">
                    <!-- Column 1: Gaps -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-danger text-white py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 small fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>0 Patrol Gaps</h6>
                                <span class="badge bg-white text-danger" id="modalCoverageGapsCount">0</span>
                            </div>
                            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                                <div id="coverageGapsList" class="list-group list-group-flush">
                                    <!-- Dynamic -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Most Visited -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-success text-white py-2">
                                <h6 class="mb-0 small fw-bold"><i class="bi bi-graph-up-arrow me-1"></i>Most Visited</h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                                <div id="mostVisitedBeatsList" class="list-group list-group-flush">
                                    <!-- Dynamic -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Least Visited -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-warning text-dark py-2">
                                <h6 class="mb-0 small fw-bold"><i class="bi bi-graph-down-arrow me-1"></i>Least Visited</h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                                <div id="leastVisitedBeatsList" class="list-group list-group-flush">
                                    <div class="text-center py-4 text-muted small">
                                        <div class="spinner-border spinner-border-sm text-warning mb-2"></div>
                                        <p class="mb-0">Loading...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4 bg-light">
                    <div class="card-body py-3">
                        <h6 class="fw-bold mb-2 small text-muted text-uppercase">Calculation Logic</h6>
                        <div class="alert alert-info py-2 mb-2 small border-0 shadow-sm">
                            <code class="fw-bold">Coverage Rate = (Covered Sites / Total Sites) × 100</code>
                        </div>
                        <div class="fw-bold text-dark font-monospace" id="modalCoverageCalculationText">
                            0 ÷ 0 × 100 = 0.0%
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <a href="{{ route('patrol.kml.view') }}" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-map"></i> View Detailed KML Map
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal 4b: Total Incidents Breakdown --}}
<div class="modal fade dashboard-modal-fixed" id="totalIncidentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-shield-exclamation me-2"></i>Total Incidents Analytics</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Top Summary Row -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm text-center h-100">
                            <div class="card-body py-3 d-flex flex-column justify-content-center align-items-center">
                                <div class="mb-2 text-danger opacity-25">
                                    <i class="bi bi-shield-exclamation fs-4"></i>
                                </div>
                                <h2 class="fw-bold text-dark mb-0" id="modalIncidentsTotal">0</h2>
                                <p class="text-muted extra-small text-uppercase fw-bold mb-0">Total Incidents</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm text-center border-start border-danger border-4 h-100">
                            <div class="card-body py-3 d-flex flex-column justify-content-center align-items-center">
                                <div class="mb-2 text-warning opacity-75">
                                    <i class="bi bi-hourglass-split fs-4"></i>
                                </div>
                                <h2 class="fw-bold text-danger mb-0" id="modalIncidentsPending">0</h2>
                                <p class="text-muted extra-small text-uppercase fw-bold mb-0">Pending Review</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm text-center border-start border-success border-4 h-100">
                            <div class="card-body py-3 d-flex flex-column justify-content-center align-items-center">
                                <div class="mb-2 text-success opacity-75">
                                    <i class="bi bi-check-circle-fill fs-4"></i>
                                </div>
                                <h2 class="fw-bold text-success mb-0" id="modalIncidentsResolved">0</h2>
                                <p class="text-muted extra-small text-uppercase fw-bold mb-0">Resolved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm text-center border-start border-info border-4 h-100">
                            <div class="card-body py-3 d-flex flex-column justify-content-center align-items-center">
                                <div class="mb-2 text-info opacity-75">
                                    <i class="bi bi-graph-up-arrow fs-4"></i>
                                </div>
                                <h2 class="fw-bold text-info mb-0" id="modalIncidentsRate">0.0%</h2>
                                <p class="text-muted extra-small text-uppercase fw-bold mb-0">Resolution Rate</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Incidents Table -->
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-uppercase small text-muted"><i class="bi bi-list-ul me-2"></i>Recent Critical Incidents</h6>
                               
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light sticky-top small">
                                            <tr>
                                                <th class="ps-3 py-2 text-nowrap" style="min-width: 90px;">Date / Time</th>
                                                 <th class="py-2">Details</th>
                                                <th class="py-2 d-none d-md-table-cell">Site</th>
                                                <th class="py-2 d-none d-md-table-cell">Forest Guard</th>
                                                <th class="text-center py-2" style="width: 100px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modalIncidentsRecentTable" class="small">
                                            <tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-danger me-2"></div>Loading recent records...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Site Performance -->
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 border-0">
                                <h6 class="fw-bold mb-0 text-uppercase small text-muted">Site-Level Incident Distribution</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light small">
                                            <tr>
                                                <th class="ps-3 py-2">Site Name</th>
                                                <th class="text-center py-2">Total</th>
                                                <th class="text-center py-2">Resolved</th>
                                                <th class="text-center py-2">Pending</th>
                                                <th class="text-end pe-3 py-2">Performance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modalIncidentsSiteTable" class="small">
                                            <tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-danger me-2"></div>Loading site data...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <a href="{{ url('/incidents/summary') }}" class="btn btn-danger btn-lg shadow-sm">
                        <i class="bi bi-journal-text me-2"></i>Open Full Incident Management
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal 8: Total Beats Details --}}
<div class="modal fade dashboard-modal-fixed" id="totalBeatsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">🌲 Total Beats Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary">
                    <strong>Total Beats:</strong> <span id="modalTotalBeatsCount">{{ number_format($kpis['totalSites']) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Sr.No</th>
                                <th>Beat Name</th>
                                <th>Range</th>
                            </tr>
                        </thead>
                        <tbody id="totalBeatsList">
                            <tr>
                                <td colspan="3" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal 9: Total Distance Details --}}
<div class="modal fade dashboard-modal-fixed" id="totalDistanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">📏 Total Patrolling Distance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Summary Overview --}}
                <div class="row g-3 mb-4 text-center">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body py-3">
                                <h6 class="text-white-50 text-uppercase extra-small fw-bold mb-1">Total Distance</h6>
                                <h3 class="mb-0 fw-bold" id="modalDistanceTotal">{{ number_format($kpis['totalDistance'] ?? 0, 1) }} km</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body py-3">
                                <h6 class="text-white-50 text-uppercase extra-small fw-bold mb-1">Active Guards</h6>
                                <h3 class="mb-0 fw-bold" id="modalDistanceGuards">{{ number_format($kpis['activeGuards'] ?? 0) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body py-3">
                                <h6 class="text-white-50 text-uppercase extra-small fw-bold mb-1">Avg Efficiency</h6>
                                <h3 class="mb-0 fw-bold" id="modalDistanceAvg">{{ number_format($kpis['avgDistancePerGuard'] ?? 0, 1) }} <small class="fs-6 fw-normal">km</small></h3>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Distance Breakdown Table --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0 text-dark text-uppercase small" style="letter-spacing: 0.5px;"><i class="bi bi-bar-chart-fill text-info me-2"></i>Distance Breakdown by Forest Guard</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 60px;">#</th>
                                <th>FOREST GUARD NAME</th>
                                <th>SITE / BEAT</th>
                                <th class="text-end pe-3">DISTANCE (KM)</th>
                            </tr>
                        </thead>
                        <tbody id="totalDistanceList">
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="spinner-border text-info" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2 small">Aggregating distance data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a href="{{ route('patrol.foot.summary') }}" class="btn btn-info text-white shadow-sm">View Full Foot Summary</a>
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Attendance Rate Modal --}}
<div class="modal fade dashboard-modal-fixed" id="attendanceDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-check-fill me-2"></i>Attendance Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                {{-- Summary Overview --}}
                <div class="row g-3 mb-4 text-center">
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-warning-subtle text-warning-emphasis">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase extra-small fw-bold mb-1">Attendance Rate</h6>
                                <h3 class="mb-0 fw-bold" id="modalAttendanceRate">{{ number_format($kpis['attendanceRate'] ?? 0, 1) }}%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-primary-subtle text-primary-emphasis">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase extra-small fw-bold mb-1">Guards Present Today</h6>
                                <h3 class="mb-0 fw-bold" id="modalAttendancePresentToday">{{ number_format($kpis['presentCountToday'] ?? 0) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-success-subtle text-success-emphasis">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase extra-small fw-bold mb-1">Total Man Days</h6>
                                <h3 class="mb-0 fw-bold" id="modalAttendancePresent">{{ number_format($kpis['presentCount'] ?? 0) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm bg-info-subtle text-info-emphasis">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase extra-small fw-bold mb-1">Active Forest Guards</h6>
                                <h3 class="mb-0 fw-bold" id="modalAttendanceStaff">{{ number_format($kpis['activeGuards'] ?? 0) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Detailed List --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold m-0 text-dark text-uppercase small" style="letter-spacing: 0.5px;">Attendance Consistency by Forest Guard</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light extra-small text-uppercase text-muted">
                                    <tr>
                                        <th class="ps-3 py-2">Forest Guard Details</th>
                                        <th class="py-2">Site Context</th>
                                        <th class="text-center py-2">Days Present</th>
                                        <th class="text-end pe-3 py-2">Late Freq.</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceDetailsList">
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="spinner-border text-warning"></div>
                                            <p class="text-muted mt-2 small">Analyzing attendance patterns...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a href="{{ url('/attendance/summary') }}" id="fullAttendanceLedgerBtn" class="btn btn-warning shadow-sm fw-bold">Open Full Attendance Ledger</a>
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
