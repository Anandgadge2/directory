@extends('layouts.app')

@section('content')

<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">☀️ Foot Patrol Report (Daytime)</h2>
            <p class="text-muted mb-0">
                Patrols conducted during daytime hours (6:00 AM - 6:00 PM)
            </p>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-1">Total Sessions</h6>
                    <h3 class="mb-0 fw-bold text-primary">{{ number_format($totalSessions) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-1">Completed</h6>
                    <h3 class="mb-0 fw-bold text-success">{{ number_format($completed) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-1">Ongoing</h6>
                    <h3 class="mb-0 fw-bold text-warning">{{ number_format($ongoing) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-1">Total Distance</h6>
                    <h3 class="mb-0 fw-bold text-info">{{ number_format($totalDistance, 2) }} km</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Guard Statistics Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Guard Performance - Daytime Patrols</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sr.No</th>
                            <th>Guard Name</th>
                            <th class="text-center">Total Sessions</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">Ongoing</th>
                            <th class="text-center">Total Distance (km)</th>
                            <th class="text-center">Avg Speed (km/h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guardStats as $stat)
                            <tr>
                                <td>{{ $loop->iteration + ($guardStats->currentPage() - 1) * $guardStats->perPage() }}</td>
                                <td class="fw-bold">{{ $stat->guard }}</td>
                                <td class="text-center">{{ number_format($stat->total_sessions) }}</td>
                                <td class="text-center"><span class="badge bg-success">{{ number_format($stat->completed) }}</span></td>
                                <td class="text-center"><span class="badge bg-warning">{{ number_format($stat->ongoing) }}</span></td>
                                <td class="text-center">{{ number_format($stat->total_distance, 2) }}</td>
                                <td class="text-center">{{ number_format($stat->km_per_hour ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No daytime patrol records found for the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($guardStats->hasPages())
            <div class="card-footer bg-white">
                {{ $guardStats->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
