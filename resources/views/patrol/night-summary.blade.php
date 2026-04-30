@extends('layouts.app')

@section('content')

{{-- KPI ROW --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Sessions</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalSessions }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="bi bi-moon-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Completed</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $completed }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-shield-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Ongoing</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $ongoing }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="bi bi-broadcast-pin fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Distance (km)</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ number_format($totalDistance,2) }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="bi bi-geo-alt-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Single Consolidated Patrol Table --}}
<h5 class="mt-4 mb-2 fw-bold">Night Patrol Overview</h5>

<div id="nightTableContainer">
    @include('patrol.partials.night-table')
</div>

{{-- CHARTS ROW --}}
<div class="row mt-4 g-3 align-items-stretch">
   
    {{-- Total Night Patrolling by Guard (KM) --}}
    <div class="col-12">
        <div class="card p-3">
            <h6 class="fw-bold">Total Night Patrolling by Guard (KM)</h6>
            <div style="overflow-x:auto;">
                 <div style="height:350px;">
                    <canvas id="nightDistanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

     {{-- Speed Chart --}}
     <div class="col-12 mt-4">
        <div class="card p-3">
            <h6 class="fw-bold">Guard Patrol Speed (KM/H)</h6>
            <div style="overflow-x:auto;">
                <div style="height:350px;">
                    <canvas id="speedChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://google.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* AJAX Pagination */
    document.addEventListener('click', function(e) {
        if(e.target.closest('#nightTableContainer .pagination a')) {
            e.preventDefault();
            let url = e.target.closest('a').href;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                document.getElementById('nightTableContainer').innerHTML = html;
            })
            .catch(e => console.error(e));
        }
    });

    /* Data from Controller */
    const guardData = {!! json_encode($guardStats) !!};
    const speedData = {!! json_encode($speedStats) !!};

    /* 1. Distance Chart */
    if (guardData.length) {
        const dCtx = document.getElementById('nightDistanceChart');
        const dLabels = guardData.map(d => d.guard);
        const dValues = guardData.map(d => d.total_distance);
        
        // Ensure scrollable width if many guards
        dCtx.width = Math.max(dLabels.length * 50, dCtx.parentElement.clientWidth);
        dCtx.height = 350;

        new Chart(dCtx, {
            type: 'bar',
            data: {
                labels: dLabels,
                datasets: [{
                    label: 'Distance (KM)',
                    data: dValues,
                    backgroundColor: '#198754'
                }]
            },
            options: {
                responsive: false, // Important for scrolling canvas
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
                    y: { beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });
    } else {
        const dCtx = document.getElementById('nightDistanceChart');
        if (dCtx) {
            dCtx.parentElement.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted py-5">
                    <i class="bi bi-person-x fs-1 opacity-25"></i>
                    <p class="mt-2 mb-0 fw-medium">No distance data available per guard</p>
                    <small>Try selecting a different date range or beat</small>
                </div>
            `;
        }
    }

    /* 2. Speed Chart */
    if (speedData.length) {
        const sCtx = document.getElementById('speedChart');
        const sLabels = speedData.map(d => d.guard);
        const sValues = speedData.map(d => d.speed);
        
        sCtx.width = Math.max(sLabels.length * 50, sCtx.parentElement.clientWidth);
        sCtx.height = 350;

        new Chart(sCtx, {
            type: 'bar', // Using bar for speed as well to match design
            data: {
                labels: sLabels,
                datasets: [{
                    label: 'Speed (KM/H)',
                    data: sValues,
                    backgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
                    y: { beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });
    } else {
        const sCtx = document.getElementById('speedChart');
        if (sCtx) {
            sCtx.parentElement.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted py-5">
                    <i class="bi bi-speedometer2 fs-1 opacity-25"></i>
                    <p class="mt-2 mb-0 fw-medium">No speed data available for this period</p>
                </div>
            `;
        }
    }
});
</script>
@endpush
