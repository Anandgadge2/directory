{{-- Guard Performance Rankings --}}
@php
    $fullPerf = $guardPerformance['fullPerformance'] ?? collect();
    $hasMeaningfulData = $fullPerf->contains(function ($guard) {
        return ($guard->patrol_sessions ?? 0) > 0
            || ($guard->total_distance_km ?? 0) > 0
            || ($guard->days_present ?? 0) > 0
            || ($guard->incidents_reported ?? 0) > 0;
    });
    $showNoData = $fullPerf->isEmpty() || !$hasMeaningfulData;
@endphp
        <div class="card border-0 shadow-sm h-auto">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">📊 Forest Guard Performance Overview</h5>
    </div>
            <div class="card-body p-0" style="min-height: 260px;">
        @if($showNoData)
                    <div class="guard-performance-no-data d-flex align-items-center justify-content-center py-5 px-3" style="background: #1a202c; min-height: 220px;">
                        <p class="mb-0 text-white fw-medium">No data available for this range</p>
            </div>
        @else
        @php
            $guardsWithData = $fullPerf->values();
        @endphp
                <div class="table-responsive" style="max-height: 610px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0 sortable-table">
                        <thead class="sticky-top bg-white" style="z-index: 10;">
                    <tr>
                                <th data-sortable class="text-center">Sr.No</th>
                                <th data-sortable class="text-start ps-3">Forest Guard</th>
                                <th data-sortable data-type="number" class="text-center">Patrols</th>
                                <th data-sortable data-type="number" class="text-center">Total Dist</th>
                                <th data-sortable data-type="number" class="text-center">Avg Dist</th>
                                <th data-sortable data-type="number" class="text-center">Avg Time</th>
                                <th data-sortable data-type="number" class="text-center">Present</th>
                                <th data-sortable data-type="number" class="text-center">Incidents</th>
                                <th data-sortable data-type="number" class="text-center">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guardsWithData as $index => $guard)
                        <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="text-start ps-3">
                                        <a href="#" class="guard-name-link" data-guard-id="{{ $guard->id }}">
                                            {{ $guard->name }}
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $guard->patrol_sessions ?? 0 }}</td>
                                    <td class="text-center">{{ number_format($guard->total_distance_km ?? 0, 2) }} km</td>
                                    <td class="text-center">{{ number_format($guard->avg_distance_per_session ?? 0, 2) }} km</td>
                                    <td class="text-center">{{ number_format($guard->avg_duration_hours ?? 0, 2) }} hrs</td>
                                    <td class="text-center">{{ $guard->days_present ?? 0 }} days</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border clickable" 
                                              onclick="showIncidentsByType('all', 'Incidents by {{ addslashes($guard->name) }}', {user: '{{ $guard->id }}'})"
                                              style="cursor:pointer">
                                            {{ $guard->incidents_reported ?? 0 }}
                                        </span>
                            </td>
                                    <td class="text-center"><span class="badge bg-primary">{{ number_format($guard->performance_score ?? 0, 1) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
 


