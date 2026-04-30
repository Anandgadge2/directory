@extends('layouts.app')

@section('content')

<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">🌙 Night Patrol Report</h2>
            <p class="text-muted mb-0">
                Patrols conducted during night hours (6:00 PM - 6:00 AM)
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

    {{-- Top Guards Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Top Performing Guards - Night Patrols</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Rank</th>
                            <th>Guard Name</th>
                            <th class="text-center">Total Distance (km)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topGuards as $guard)
                            <tr>
                                <td>
                                    @if($loop->iteration == 1)
                                        <span class="badge bg-warning text-dark">🥇 1st</span>
                                    @elseif($loop->iteration == 2)
                                        <span class="badge bg-secondary">🥈 2nd</span>
                                    @elseif($loop->iteration == 3)
                                        <span class="badge bg-danger">🥉 3rd</span>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $guard->guard }}</td>
                                <td class="text-center">{{ number_format($guard->distance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    No night patrol records found for the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
