@php
    if (!function_exists('severityBadge')) {
        function severityBadge($s) {
            $map = [
                5 => 'danger',
                4 => 'warning',
                3 => 'primary',
                2 => 'success',
            ];
            return $map[$s] ?? 'secondary';
        }
    }
@endphp

<div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
    <table class="table table-hover align-middle mb-0 sortable-table sticky-header">
        <thead class="table-light sticky-top">
        <tr>
            <th style="background: #f8f9fa;">Sr.No</th>
            <th data-sortable>Type</th>
            <th data-sortable data-type="number" class="text-center">Severity</th>
            <th data-sortable>Forest Guard</th>
            <th data-sortable>Range</th>
            <th data-sortable>Beat</th>
            <th data-sortable>Session</th>
            <th data-sortable class="text-center" style="min-width: 140px;">Date</th>
        </tr>
        </thead>
        <tbody>
        @if(isset($incidents) && count($incidents) > 0)
            @foreach($incidents as $i)
                <tr onclick="if(!event.target.closest('.guard-name-link')) openIncidentDetail({{ $i->id }})" style="cursor:pointer">
                    <td class="text-center" style="background: #fff;">{{ $loop->iteration + ($incidents->currentPage() - 1) * $incidents->perPage() }}</td>
                    <td><span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $i->type)) }}</span></td>
                    <td class="text-center">
                        <span class="badge bg-{{ severityBadge($i->severity) }}">
                            {{ $i->severity }}
                        </span>
                    </td>
                    <td>
                        @if(!empty($i->guard_id))
                            <a href="#" class="guard-name-link user-name text-decoration-none" data-guard-id="{{ $i->guard_id }}">
                                {{ \App\Helpers\FormatHelper::formatName($i->guard) }}
                            </a>
                        @else
                            {{ $i->guard ?? '—' }}
                        @endif
                    </td>
                    <td>{{ $i->range_name ?? $i->range_id ?? 'NA' }}</td>
                    <td>{{ $i->beat_name ?? $i->beat_id ?? 'NA' }}</td>
                    <td>{{ $i->session ?? 'NA' }}</td>
                    <td class="text-center" data-value="{{ $i->created_at }}">{{ \Carbon\Carbon::parse($i->created_at)->format('d M Y') }}<br><small class="text-muted">{{ \Carbon\Carbon::parse($i->created_at)->format('h:i A') }}</small></td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8" class="text-center text-muted py-4">No incidents found for the selected criteria</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

<div class="p-3 d-flex justify-content-end border-top">
    {{ $incidents->links('pagination::bootstrap-4') }}
</div>
