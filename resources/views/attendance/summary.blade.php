@extends('layouts.app')

@section('content')

{{-- ================= KPIs (Top Summary) ================= --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 kpi-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Forest Guards</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalGuards }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="bi bi-people-fill fs-5"></i>
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
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Man-Days (P)</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalPresentManDays }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-calendar-check-fill fs-5"></i>
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
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Man-Days (A)</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalAbsentManDays }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="bi bi-calendar-x-fill fs-5"></i>
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
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Attendance %</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $presentPct }}%</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="bi bi-percent fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================= DETAILED EXPLORER TABLE ================= --}}
<div class="card p-3 mb-4 border-0 shadow-sm" style="background:var(--card-bg, #fff);">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Detailed Attendance Explorer</h5>
        @if(isset($startDate) && isset($endDate))
             <span class="badge bg-light text-dark border">
                {{ $startDate->format('d M') }} - {{ $endDate->format('d M') }}
            </span>
        @endif
    </div>

    <div class="attendance-explorer-wrapper">
        <div class="explorer-layout">

            {{-- Table Wrapper --}}
            <div class="dot-table-wrapper">
                <div class="dot-table-scroll">
                    <table class="dot-table sortable-table">
                        <thead>
                            <tr>
                                <th style="background: #f8f9fa;">Sr.No</th>
                                <th data-sortable style="background: #f8f9fa;">User</th>
                                <th data-sortable>Range</th>
                                <th data-sortable>Beat</th>
                                <th data-sortable>Total</th>
                                @foreach($dates as $dt)
                                    <th title="{{ $dt->format('Y-m-d') }}">
                                        {{ $dt->format('d') }}<br>
                                        <small style="font-size:9px; font-weight:400; color:#888;">{{ $dt->format('M') }}</small>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                        @forelse($grid as $data)
                            @php $user = $data['user']; @endphp
                            <tr>
                                <td style="background: #fff;" class="text-center">{{ $loop->iteration }}</td>
                                <td class="user-cell" style="background: #fff;">
                                    <img src="{{ asset($user->profile_pic ?? 'images/user-placeholder.png') }}" class="user-avatar">
                                    <a href="#" class="guard-name-link user-name text-decoration-none" data-guard-id="{{ $user->id }}">
                                        {{ \App\Helpers\FormatHelper::formatName($user->name) }}
                                    </a>
                                </td>

                               <td>{{ $data['meta']['range'] ?? 'NA' }}</td>
                               <td>{{ $data['meta']['beat'] ?? 'NA' }}</td>
                              
                                <td class="fw-semibold">
                                    {{ $data['summary']['present'] }} / {{ $data['summary']['total'] }}
                                </td>

                                {{-- Iterate over dates --}}
                                @foreach($dates as $dt)
                                    @php 
                                        $dStr = $dt->toDateString();
                                        $isPresent = $data['days'][$dStr]['present'] ?? false;
                                    @endphp
                                    <td>
                                        <span class="dot {{ $isPresent ? 'present' : 'absent' }}" 
                                              title="{{ $dStr }}: {{ $isPresent ? 'Present' : 'Absent' }}">
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($dates) + 5 }}" class="text-center py-5 text-muted">No attendance records found for this period.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ================= BOTTOM SECTION: CHARTS ================= --}}
<div class="row g-3">
    
    {{-- Daily Attendance Trend --}}
    <div class="col-lg-8">
        <div class="card chart-box h-100">
            <h6 class="fw-bold mb-3">
                Daily Attendance 
                <small class="text-muted fw-normal ms-2" style="font-size: 0.75rem;">(Click bars to view guard list)</small>
            </h6>

            <div class="chart-scroll-x">
                <div class="chart-wide">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top 10 Defaulters --}}
    <div class="col-lg-4">
        <div class="card p-3 h-100 border-0 shadow-sm">
            <h6 class="fw-bold mb-3" style="color:rgb(29, 26, 26);">Top 10 Defaulters</h6>
            <div class="table-responsive" style="max-height: 300px; overflow-y:auto;">
                <table class="table table-sm table-hover mb-0 small">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Sr.No</th>
                            <th>Forest Guard</th>
                            <th class="text-end">Absent Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($defaulters as $d)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                
                                <td><a href="#" class="guard-name-link user-name text-decoration-none" data-guard-id="{{ $d['user_id'] }}">
                                        {{ \App\Helpers\FormatHelper::formatName($d['name']) }}
                                    </a></td>
                                <td class="text-end fw-bold" style="color:rgb(250, 123, 129);">{{ $d['days_absent'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- STYLES from Explorer --}}
<style>
/* ================= CHART LAYOUT ================= */
.chart-box {
    padding: 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    background: #fff;
    border-radius: 12px;
    border: none;
}

.chart-scroll-x {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.chart-wide {
    width: max-content;
    min-width: 1200px;
    height: 320px;
}

/* Attendance Table Layout */
.attendance-explorer-wrapper { padding: 0; }
.explorer-layout { display: flex; flex-direction: column; gap: 15px; }

.dot-table-wrapper {
    width: 100%;
    overflow: hidden;
    position: relative;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #eee;
}

.dot-table-scroll {
    height: 60vh; 
    overflow: auto;
    position: relative;
    background: #ffffff;
    -webkit-overflow-scrolling: touch; /* Vital for mobile momentum */
}

/* Custom scrollbar for better appearance */
.dot-table-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
.dot-table-scroll::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }

.dot-table {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    font-size: 13px;
    table-layout: fixed;
}

.dot-table th, .dot-table td { 
    white-space: nowrap; 
    padding: 12px 15px; 
    border-bottom: 1px solid #f1f5f9;
}

/* Sticky Header - Base z-index for ALL headers */
.dot-table thead th {
    position: sticky;
    top: 0;
    z-index: 30; /* Higher than table body cells */
    background: #f8fafc !important; 
    font-weight: 700;
    color: #475569;
    box-shadow: 0 1px 0 #e2e8f0;
}

/* DESKTOP STICKY COLUMNS - Higher z-index to stay on TOP of non-sticky content */
.dot-table th:first-child,
.dot-table td:first-child {
    position: sticky;
    left: 0;
    background: #ffffff !important;
    z-index: 20; /* Keep it above normal scrolling content */
    width: 60px;
    min-width: 60px;
    text-align: center;
    border-right: 1px solid #f1f5f9;
}

.dot-table th:nth-child(2),
.dot-table td:nth-child(2) {
    position: sticky;
    left: 60px; 
    background: #ffffff !important;
    z-index: 20; /* Keep it above normal scrolling content */
    width: 240px;
    min-width: 240px;
    border-right: 1px solid #f1f5f9;
    box-shadow: 4px 0 8px rgba(0,0,0,0.02);
    white-space: normal !important; /* Fix intersection issue */
    vertical-align: middle;
}

/* MOBILE RESPONSIVE TWEAKS */
@media (max-width: 768px) {
    .dot-table { 
        font-size: 11px; 
    }
    .dot-table th, .dot-table td { 
        padding: 8px 6px; 
    }
    
    /* Sr.No - Static on mobile */
    .dot-table th:first-child,
    .dot-table td:first-child {
        position: static !important;
        width: 40px !important;
        min-width: 40px !important;
    }

    /* User Column - Scrollable on mobile (Disabled Stickiness) */
    .dot-table th:nth-child(2),
    .dot-table td:nth-child(2) {
        position: static !important;
        left: auto !important;
        z-index: 1 !important;
        background: #ffffff !important;
        width: auto !important;
        min-width: 120px !important;
        box-shadow: none !important;
    }
    
    /* Ensure Header remains static too */
    .dot-table thead th:nth-child(2) {
        z-index: 10 !important;
        top: 0 !important;
    }

    /* Hide non-vital columns on very narrow screens */
    .dot-table th:nth-child(4), .dot-table td:nth-child(4), /* Range/Beat */
    .dot-table th:nth-child(5), .dot-table td:nth-child(5) {
        display: none;
    }

    .user-name {
        font-size: 11px;
        white-space: normal;
        max-width: 100px;
    }
}

/* Chart container z-index fix */
.chart-box { position: relative; z-index: 1 !important; }
.chart-box canvas { position: relative; z-index: 1 !important; }

.user-cell { display: flex; align-items: center; gap: 8px; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
.user-name { font-weight: 600; color: #1e293b; font-size: 13px; }

.dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; vertical-align: middle; }
.dot.present { background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
.dot.absent  { background: #fda4af; }

/* Side KPI styles removed */

/* KPI (Side Panel) */
/* The main top KPI cards are now handled by enhanced-dashboard.css global styles */
</style>


{{-- ================= MODAL: ATTENDANCE LIST (User Requested) ================= --}}
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="attnModalLabel">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group list-group-flush" id="attnList"></ul>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* ================= DAILY BAR ================= */
    const dailyData = {!! json_encode($dailyTrend) !!};
    const barChartCanvas = document.getElementById('barChart');

    if (barChartCanvas && dailyData.length) {
        new Chart(barChartCanvas, {
            type: 'bar',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [
                    {
                        label: 'Present',
                        data: dailyData.map(d => d.present),
                        backgroundColor: '#43a047',
                        hoverBackgroundColor: '#2e7d32',
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        borderRadius: 4
                    },
                    {
                        label: 'Absent',
                        data: dailyData.map(d => d.absent),
                        backgroundColor: '#ffb1af',
                        hoverBackgroundColor: '#ff9a96',
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (chartEvent, elements, chart) => {
                    // Chart.js passes the native event, but we need to check if methods exist
                    if (chartEvent && typeof chartEvent.stopPropagation === 'function') {
                        chartEvent.stopPropagation();
                    }
                    if (chartEvent && typeof chartEvent.preventDefault === 'function') {
                        chartEvent.preventDefault();
                    }
                    
                    if (!elements || !elements.length) return;

                    const i = elements[0].index;
                    const dsIndex = elements[0].datasetIndex; // 0 = Present, 1 = Absent
                    const d = dailyData[i];

                    if (!d) return;

                    const isPresent = dsIndex === 0;
                    const list = isPresent ? (d.present_list || []) : (d.absent_list || []);
                    const title = isPresent 
                        ? `Present on ${d.date} (${d.present || 0})` 
                        : `Absent on ${d.date} (${d.absent || 0})`;
                    
                    const listEl = document.getElementById('attnList');
                    const titleEl = document.getElementById('attnModalLabel');
                    const modalEl = document.getElementById('attendanceModal');
                    
                    if (!listEl || !titleEl || !modalEl) {
                        console.error('Modal elements not found');
                        return;
                    }
                    
                    titleEl.textContent = title;
                    titleEl.className = 'modal-title fw-bold ' + (isPresent ? 'text-success' : '');
                    if (!isPresent) {
                        titleEl.style.color = '#d32f2f';
                    }
                    
                    listEl.innerHTML = list.length 
                        ? list.map(u => `
                            <li class="list-group-item py-2">
                                <a href="#" class="guard-name-link text-decoration-underline text-primary" 
                                      style="cursor:pointer"                                        data-guard-id="${u.id || ''}">
                                    ${u.name || 'Unknown'}
                                </a>
                            </li>
                        `).join('') 
                        : `<li class="list-group-item text-muted text-center">No forest guards list available</li>`;

                    // Close any existing modal first
                    try {
                        const existingModal = bootstrap.Modal.getInstance(modalEl);
                        if (existingModal) {
                            existingModal.hide();
                        }
                    } catch (e) {
                        console.warn('Error closing existing modal:', e);
                    }
                    
                    // Show new modal
                    try {
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    } catch (e) {
                        console.error('Error showing modal:', e);
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 12,
                            maxRotation: 0,
                            font: { size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4], color: '#f0f0f0' },
                        title: { display: true, text: 'Guards' }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    },
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { usePointStyle: true, boxWidth: 8 }
                    }
                }
            }
        });
    }

});



</script>
@endpush
