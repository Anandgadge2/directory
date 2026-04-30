<style>
    .patrol-table-wrapper {
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: 1px solid #edf2f7;
    }
    .patrol-scroll {
        max-height: 550px;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .patrol-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.875rem;
    }
    .patrol-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        color: #64748b;
        padding: 12px 15px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .patrol-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
        background: #fff;
    }
    
    /* Sticky Columns for Desktop */
    .patrol-table .sticky-col-1 {
        position: sticky;
        left: 0;
        z-index: 5;
        background: #fff !important;
        border-right: 1px solid #f1f5f9;
        width: 60px;
        text-align: center;
    }
    .patrol-table .sticky-col-2 {
        position: sticky;
        left: 60px;
        z-index: 5;
        background: #fff !important;
        border-right: 1px solid #f1f5f9;
        min-width: 180px;
    }

    /* Mobile Overrides */
    @media (max-width: 768px) {
        .patrol-table { font-size: 0.75rem; }
        .patrol-table .sticky-col-1 {
            position: static !important;
            width: 40px;
        }
        .patrol-table .sticky-col-2 {
            position: static !important;
            min-width: 140px;
            box-shadow: none;
        }
        /* Top Left Header Intersection */
        .patrol-table thead th.sticky-col-2 {
            z-index: 10 !important;
        }
        /* Hide less vital info on mobile list */
        .patrol-hide-mobile { display: none; }
    }
</style>

<div class="patrol-table-wrapper">
    <div class="patrol-scroll">
        <table class="patrol-table sortable-table">
            <thead>
                <tr>
                    <th class="sticky-col-1">Sr.No</th>
                    <th class="sticky-col-2" data-sortable>Guard</th>
                    <th class="text-center" data-sortable data-type="number" title="Total Sections">Total<br>Sessions</th>
                    <th class="text-center patrol-hide-mobile" data-sortable data-type="number">Comp.</th>
                    <th class="text-center patrol-hide-mobile" data-sortable data-type="number">Ong.</th>
                    <th data-sortable class="patrol-hide-mobile">Range</th>
                    <th data-sortable>Beat</th>
                    <th data-sortable>Start Time</th>
                    <th data-sortable class="patrol-hide-mobile">End Time</th>
                    <th data-sortable>Status</th>
                    <th data-sortable data-type="number">Dist.<br>(KM)</th>
                    <th data-sortable data-type="number" class="patrol-hide-mobile">Speed<br>(KM/H)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patrols as $index => $p)
                    @php
                        // Map aggregate stats to this row based on user_id
                        $stats = $guardStats->firstWhere('user_id', $p->user_id);
                    @endphp
                    <tr>
                        <td class="sticky-col-1">{{ ($patrols->currentPage() - 1) * $patrols->perPage() + $loop->iteration }}</td>
                        <td class="sticky-col-2">
                            @if($p->user_id)
                                <a href="#" class="guard-name-link fw-bold text-decoration-none" data-guard-id="{{ $p->user_id }}">
                                    {{ \App\Helpers\FormatHelper::formatName($p->user_name) }}
                                </a>
                            @else
                                <span class="fw-bold">{{ \App\Helpers\FormatHelper::formatName($p->user_name) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $stats->total_sessions ?? '-' }}</td>
                        <td class="text-center text-success patrol-hide-mobile">{{ $stats->completed ?? '-' }}</td>
                        <td class="text-center text-warning patrol-hide-mobile">{{ $stats->ongoing ?? '-' }}</td>
                        <td class="patrol-hide-mobile">{{ $p->range ?? 'NA' }}</td>
                        <td>{{ $p->beat ?? 'NA' }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->started_at)->format('d M, H:i') }}</td>
                        <td class="patrol-hide-mobile">
                             {{ $p->ended_at ? \Carbon\Carbon::parse($p->ended_at)->format('d M, H:i') : '-' }}
                        </td>
                        <td>
                            @if($p->ended_at)
                                <span class="badge bg-soft-success text-success extra-small">Comp.</span>
                            @else
                                <span class="badge bg-soft-warning text-warning extra-small">Active</span>
                            @endif
                        </td>
                        <td class="text-center fw-medium">{{ number_format($p->distance ?? 0, 1) }}</td>
                        <td class="patrol-hide-mobile text-center">{{ number_format($p->speed ?? 0, 1) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
                            <i class="bi bi-moon-stars fs-3 d-block mb-3"></i>
                            <span class="fw-medium">No night patrol records found</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $patrols->links('pagination::bootstrap-4') }}
</div>
