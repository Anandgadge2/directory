{{-- Incident Tracking Section --}}
<div class="row g-4 mb-4">
    {{-- Left Card: Status Tracking (8 Column) --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header text-white" style="background-color: #ff7675; border-radius: 12px 12px 0 0;">
                <h6 class="mb-0 py-1"><i class="bi bi-alarm-fill me-2"></i>Incident Status Tracking</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4 align-items-center">
                    {{-- Critical KPI --}}
                    <div class="col-md-4">
                        <div class="p-4 text-center rounded-4 shadow-sm"
                            style="background-color: #ff9999; border: 1px solid #ff7675;">
                            <div class="text-white small fw-bold text-uppercase mb-2">Critical Pending</div>
                            <div class="display-4 fw-bold text-white">
                                {{ isset($incidentTracking['criticalIncidents']) ? count($incidentTracking['criticalIncidents']) : 0 }}
                            </div>
                        </div>
                    </div>
                    {{-- Status Chart --}}
                    <div class="col-md-8">
                        <div style="height:150px;">
                            <canvas id="incidentStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Recent Critical Alert --}}
                @if(isset($incidentTracking['criticalIncidents']) && count($incidentTracking['criticalIncidents']) > 0)
                    <div class="alert mb-4 border-0"
                        style="background-color: #fef9e7; border-left: 4px solid #f1c40f !important;">
                        <div class="fw-bold text-dark mb-2">Recent Critical Incidents Requiring Attention:</div>
                        <ul class="mb-0 ps-3">
                            @foreach($incidentTracking['criticalIncidents']->take(3) as $incident)
                                <li class="mb-1" onclick="window.openIncidentDetail({{ $incident->id }})"
                                    style="cursor:pointer; font-size: 1.1rem; color: #b09115;">
                                    <span class="fw-bold"
                                        style="color: #b09115; font-style: italic;">{{ $incident->type }}</span>
                                    <span class="text-dark">at - </span>
                                    <span
                                        class="text-muted">{{ \Carbon\Carbon::parse($incident->dateFormat)->format('Y-m-d') }}</span>
                                    (<span class="fw-bold" style="color: #4A90E2;">Forest Guard: {{ $incident->guard_name }}</span>)
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Resolution Table --}}
                <div class="table-responsive mt-2">
                    <table class="table align-middle sortable-table">
                        <thead style="background-color: #f8f9fa;">
                            <tr class="small text-uppercase text-muted" style="font-size: 0.75rem;">
                                <th class="text-center py-3" width="70">SR.NO</th>
                                <th class="py-3">BEAT NAME <i class="bi bi-arrow-down-up ms-1"
                                        style="font-size: 0.6rem;"></i></th>
                                <th class="text-center py-3">TOTAL <i class="bi bi-arrow-down-up ms-1"
                                        style="font-size: 0.6rem;"></i></th>
                                <th class="text-center py-3">RESOLVED <i class="bi bi-arrow-down-up ms-1"
                                        style="font-size: 0.6rem;"></i></th>
                                <th class="text-center py-3">PENDING <i class="bi bi-arrow-down-up ms-1"
                                        style="font-size: 0.6rem;"></i></th>
                                <th class="text-center py-3 border-end-0">RESOLUTION % <i
                                        class="bi bi-arrow-down-up ms-1" style="font-size: 0.6rem;"></i></th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @php $incidentsSites = isset($incidentTracking['incidentsBySite']) ? $incidentTracking['incidentsBySite'] : []; @endphp
                            @if(count($incidentsSites) > 0)
                                @foreach($incidentsSites as $site)
                                    <tr onclick="window.showIncidentsByType('all', 'Incidents at {{ addslashes($site->site_name) }}', {site_name: '{{ addslashes($site->site_name) }}'})"
                                        style="cursor:pointer" class="hover-bg-light border-bottom">
                                        <td class="text-center py-3 text-muted">{{ $loop->iteration }}</td>
                                        <td class="fw-bold text-muted py-3">{{ $site->site_name }}</td>
                                        <td class="text-center py-3 fw-bold text-muted">{{ $site->incident_count }}</td>
                                        <td class="text-center py-3">
                                            <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow-sm"
                                                style="width: 24px; height: 24px; font-size: 0.75rem; font-weight: bold;">
                                                {{ $site->resolved_count }}
                                            </div>
                                        </td>
                                        <td class="text-center py-3">
                                            <div class="d-inline-flex align-items-center justify-content-center bg-warning text-white rounded-circle shadow-sm"
                                                style="width: 24px; height: 24px; font-size: 0.75rem; font-weight: bold;">
                                                {{ $site->pending_count }}
                                            </div>
                                        </td>
                                        <td class="text-center py-3 fw-bold text-muted">{{ $site->resolution_percentage }}%</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted small">No incident records found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Card: Incident Types (4 Column) --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-auto overflow-hidden" style="border-radius: 12px;">
            <div class="card-header text-white" style="background-color: #00cec9; border-bottom: none;">
                <h6 class="mb-0 py-1 fw-bold">Incident Types</h6>
            </div>
            <div class="card-body p-6">
                <div style="height:350px;">
                    <canvas id="incidentTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('modals')
    {{-- @php
    // Redundant modals and scripts removed - now in dashboard-assets.blade.php
    @endphp --}}
@endpush

@push('scripts')
    {{-- @php
    // Redundant modals and scripts removed - now in dashboard-assets.blade.php
    @endphp --}}
@endpush

@php
    $statusMap = [
        0 => 'Pending (Supervisor)',
        1 => 'Resolved',
        2 => 'Ignored',
        3 => 'Escalated (Admin)',
        4 => 'Pending (Admin)',
        5 => 'Escalated (Client)',
        6 => 'Reverted'
    ];

    $statusLabels = [];
    $statusData = [];
    foreach (isset($incidentTracking['statusDistribution']) ? $incidentTracking['statusDistribution'] : [] as $flag => $count) {
        if ($count > 0) {
            $statusLabels[] = $statusMap[$flag] ?? 'Unknown';
            $statusData[] = $count;
        }
    }

    $typeLabels = $incidentTracking['incidentTypes']->pluck('type')->map(fn($t) => ucwords(str_replace('_', ' ', $t)))->values()->toArray();
    $typeData = $incidentTracking['incidentTypes']->pluck('count')->values()->toArray();
@endphp

<script>
    window.incidentTrackingData = {
        statusLabels: {!! json_encode($statusLabels) !!},
        statusData: {!! json_encode($statusData) !!},
        typeLabels: {!! json_encode($typeLabels) !!},
        typeKeys: {!! json_encode($incidentTracking['incidentTypes']->pluck('type')->values()) !!},
        typeData: {!! json_encode($typeData) !!}
    };
</script>