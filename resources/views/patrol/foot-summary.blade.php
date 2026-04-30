@extends('layouts.app')

@section('content')

{{-- Show skeleton loader on page load if data is loading --}}
@if(request()->has('_loading'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.skeletonLoader) window.skeletonLoader.show();
        });
    </script>
@endif

{{-- KPIs --}}
<div class="row g-3 mb-4" id="kpi-cards">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Sessions</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalSessions }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="bi bi-play-circle-fill fs-5"></i>
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
                        <i class="bi bi-check-circle-fill fs-5"></i>
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
                        <i class="bi bi-clock-history fs-5"></i>
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

<hr>
{{-- ROW 2: Distance Coverage by Guard (FULL WIDTH) --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card p-3">
            <h6 class="fw-bold">Distance Coverage by Guard</h6>
            <div style="overflow-x:auto;">
                 <div id="guardChartWrapper" style="height:300px;">
                    {{-- Canvas width set dynamically via JS --}}
                    <canvas id="guardDistanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Single Consolidated Patrol Table with ALL Columns --}}
<h5 class="mt-4 mb-2 fw-bold">Patrol Overview</h5>

<div id="patrolTableContainer">
    @include('patrol.partials.foot-table')
</div>

{{-- Charts --}}
{{-- ================= CHARTS ================= --}}



{{-- ROW 3: Range-wise + Daily Trend --}}
<div class="row mt-4 g-3 align-items-stretch">
    <div class="col-md-6 d-flex">
        <div class="card p-3 w-100">
            <h6 class="fw-bold">Range-wise Distance Distribution</h6>
            <div style="height:240px;">
                <canvas id="rangeStack"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6 d-flex">
        <div class="card p-3 w-100">
            <h6 class="fw-bold">Daily Distance Trend (Last 30 Days)</h6>
            <div style="height:240px;">
                <canvas id="dailyTrend"></canvas>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Hide skeleton loader when page is fully loaded
    if (window.skeletonLoader) {
        window.skeletonLoader.hide();
    }
    
    // Hide filter loader
    const filterLoader = document.querySelector('.filter-loading');
    if (filterLoader) filterLoader.style.display = 'none';

    /* DISTANCE BY GUARD (Horizontal Scrollable) */
    const guardData = {!! json_encode($guardStats) !!}; // Use data from controller directly
    
    if (guardData.length) {
        const labels = guardData.map(d => d.guard);
        const values = guardData.map(d => d.total_distance);

        const canvas = document.getElementById('guardDistanceChart');
        // Dynamic width: 60px per guard
        canvas.width = Math.max(labels.length * 60, canvas.parentElement.clientWidth);
        canvas.height = 300;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Distance Covered (KM)',
                    data: values,
                    backgroundColor: '#2f6b4f'
                }]
            },
            options: {
                responsive: false, 
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => value.toLocaleString()
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.parsed.y.toLocaleString()} KM`
                        }
                    }
                }
            }
        });
    } else {
        // Fallback for no data
        const wrapper = document.getElementById('guardChartWrapper');
        if (wrapper) {
            wrapper.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                    <i class="bi bi-person-x fs-1 opacity-25"></i>
                    <p class="mt-2 mb-0 fw-medium">No distance data available per guard</p>
                    <small>Try selecting a different date range or beat</small>
                </div>
            `;
        }
    }

    /* RANGE STACK */
    const rangeData = {!! json_encode($rangeStats) !!};
    const rangeCanvas = document.getElementById('rangeStack');
    
    if (rangeCanvas && rangeData && rangeData.length > 0) {
        new Chart(rangeCanvas, {
            type: 'bar',
            data: {
                labels: rangeData.map(d => d.range_name),
                datasets: [{
                    label: 'Distance',
                    data: rangeData.map(d => d.distance),
                    backgroundColor: '#33691e'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    } else if (rangeCanvas) {
        rangeCanvas.parentElement.innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                <i class="bi bi-geo fs-2 opacity-25"></i>
                <p class="mt-2 mb-0 fw-medium">No range-wise data available</p>
            </div>
        `;
    }

    /* DAILY TREND */
    const trendRaw = {!! json_encode($dailyTrend ?? []) !!};
    const dailyTrendCanvas = document.getElementById('dailyTrend');
    
    if (dailyTrendCanvas && trendRaw && trendRaw.length > 0) {
        // Format dates properly
        const labels = trendRaw.map(d => {
            if (d.day) {
                const date = new Date(d.day);
                return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            }
            return d.day || '';
        });
        const values = trendRaw.map(d => Number(d.distance || 0));

        new Chart(dailyTrendCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Distance (km)',
                    data: values,
                    borderColor: '#0d47a1',
                    backgroundColor: 'rgba(13, 71, 161, 0.1)',
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Distance (km)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    } else if (dailyTrendCanvas) {
        // Show message if no data
        dailyTrendCanvas.parentElement.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-graph-down" style="font-size: 2rem;"></i>
                <p class="mt-2">No distance data available for the selected period</p>
            </div>
        `;
    }
});
</script>
@endpush
