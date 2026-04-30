{{-- Patrol Analytics --}}
<div class="row mb-4">
   

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📈 Daily Patrol Trend</h5>
            </div>
            <div class="card-body">
                <div style="height: 310px;">
                    <canvas id="dailyPatrolTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
window.patrolAnalyticsData = {
    typeLabels: {!! json_encode($patrolAnalytics['patrolByType']->pluck('type')->toArray()) !!},
    typeCounts: {!! json_encode($patrolAnalytics['patrolByType']->pluck('count')->toArray()) !!},
    typeDistances: {!! json_encode($patrolAnalytics['patrolByType']->pluck('total_distance_km')->toArray()) !!},
    dailyLabels: {!! json_encode($patrolAnalytics['dailyTrend']->pluck('date')->toArray()) !!},
    dailyCounts: {!! json_encode($patrolAnalytics['dailyTrend']->pluck('patrol_count')->toArray()) !!},
    dailyDistances: {!! json_encode($patrolAnalytics['dailyTrend']->pluck('distance_km')->toArray()) !!}
};

/**
 * Shows a list of patrols filtered by type from chart click
 */
window.showPatrolsByType = async function(type, titleLabel) {
    const modalEl = document.getElementById('patrolTypeModal');
    const title = document.getElementById('patrolTypeModalTitle');
    const body = document.getElementById('patrolTypeListBody');
    
    title.innerText = titleLabel || `Patrols: ${type}`;
    body.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-success"></div><p class="text-muted mt-2 small">Loading patrol data...</p></td></tr>';
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    try {
        const globalFilters = window.getCurrentFilters ? window.getCurrentFilters() : '';
        const response = await fetch(`/api/patrols-by-type?type=${encodeURIComponent(type)}&${globalFilters}`);
        const data = await response.json();
        
        if (!data.patrols || data.patrols.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted small"><i class="bi bi-info-circle me-1"></i>No patrols found for this selection</td></tr>';
            return;
        }

        body.innerHTML = data.patrols.map((patrol, index) => {
            const initial = (patrol.guard_name || 'G').charAt(0).toUpperCase();
            return `
                <tr>
                    <td class="ps-3 text-muted small">${index + 1}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem; flex-shrink: 0;">
                                ${initial}
                            </div>
                            <div>
                                <div class="fw-bold text-dark small">Forest Guard: ${patrol.guard_name || 'Unknown'}</div>
                                <div class="text-muted extra-small" style="font-size: 0.65rem;">${patrol.phone || 'No contact'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small fw-bold text-dark">${patrol.site_name || 'N/A'}</div>
                        <div class="text-muted extra-small" style="font-size: 0.65rem;">${patrol.range_name || ''}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                            ${patrol.distance_km} km
                        </span>
                    </td>
                    <td class="text-end pe-3">
                        <div class="small text-dark fw-bold">${patrol.formatted_start}</div>
                        <div class="text-muted extra-small">${patrol.duration}</div>
                    </td>
                </tr>
            `;
        }).join('');
    } catch (error) {
        console.error('Error fetching patrols:', error);
        body.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Error loading data.</td></tr>';
    }
};
</script>
