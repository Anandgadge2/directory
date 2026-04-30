{{-- 
    Executive Dashboard Content Partial
    This partial is returned for AJAX calls when filters change.
    Modals and scripts are NOT included here - they are loaded once in executive-dashboard.blade.php
--}}

{{-- Key Performance Indicators --}}
@include('analytics.partials.kpi-cards', ['kpis' => $kpis, 'coverageAnalysis' => $coverageAnalysis])

{{-- Guard Performance Rankings --}}
<div class="mb-2">
    <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Performance
        Metrics</small>
</div>
@include('analytics.partials.guard-performance', ['guardPerformance' => $guardPerformance])


{{-- Patrol Analytics --}}
<div class="mt-4 mb-2">
    <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Patrol
        Operations</small>
</div>
@include('analytics.partials.patrol-analytics', ['patrolAnalytics' => $patrolAnalytics])


{{-- Incident Tracking --}}
<div class="mt-4 mb-2">
    <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Safety &
        Incidents</small>
</div>
@include('analytics.partials.incident-tracking', ['incidentTracking' => $incidentTracking])
