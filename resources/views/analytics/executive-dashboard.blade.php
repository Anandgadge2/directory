@extends('layouts.app')

@section('content')

    {{-- 
        IMPORTANT ARCHITECTURE NOTE:
        - #dashboardContent is updated via AJAX when filters change.
        - Modals and scripts are loaded ONCE below, OUTSIDE dashboardContent, 
          so they persist across AJAX updates without being duplicated or broken.
        - The executive-dashboard-content partial renders only the visible cards/charts.
    --}}

    {{-- Persistent Modals & Scripts (loaded once, never replaced by AJAX) --}}
    @include('analytics.partials.dashboard-modals')

    <div class="container-fluid" id="dashboardContent">
        @include('analytics.partials.executive-dashboard-content')
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="{{ asset('js/executive-dashboard-charts.js') }}"></script>
    @include('analytics.partials.dashboard-scripts')
@endpush