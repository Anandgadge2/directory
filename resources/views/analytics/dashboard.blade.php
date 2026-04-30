@extends('layouts.app')

@section('content')
@php $hideGlobalFilters = true; @endphp

<div class="container py-4 dashboard-container">

    {{-- Header & Logo Wrapper --}}
    <div class="dashboard-top-wrapper d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-start mb-4">
        
        {{-- Header --}}
        <div class="text-center text-md-start mb-3 mb-md-0">
            <h2 class="fw-bold dashboard-title">Analytics Dashboard</h2>
            <p class="dashboard-subtitle mb-0">
                Unified view of patrolling, attendance, and reports
            </p>
        </div>

        {{-- Logo --}}
        <div class="dashboard-logo">
            <img src="{{ asset('images/logo.png') }}" alt="AI Patrolling Logo" class="img-fluid">
        </div>
    </div>

    {{-- Dashboard Tiles --}}
    <div class="row justify-content-center g-3 g-md-4 dashboard-grid">
        @php
            $tiles = [
                ['/analytics/executive', 'Analytics'],
                ['/patrol/maps', 'KML / Patrol<br>Map'],
                ['/patrol/foot-summary', 'Foot Patrolling'],
                ['/patrol/night-summary', 'Night Patrolling'],
                ['/attendance/summary', 'Attendance<br>Summary'],
                ['/reports/monthly', 'Reports'],
                ['/incidents/summary', 'Incidents<br>Summary'],
            ];
        @endphp

        @foreach($tiles as $i => [$url, $label])
            @php
                $colorClass = $i % 2 === 0 ? 'tile-green' : 'tile-teal';
            @endphp
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ $url }}" class="dash-tile {{ $colorClass }}">
                    {!! $label !!}
                </a>
            </div>
        @endforeach
    </div>
</div>

<style>
/* Ensure content stays above backgrounds */
.dashboard-container {
    position: relative;
    z-index: 5;
}

/* ===============================
   TOP LOGO & WRAPPER
================================ */
.dashboard-top-wrapper {
    position: relative;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding-bottom: 20px;
}

.dashboard-logo img {
    height: 45px; 
    width: auto;
    object-fit: contain;
}

@media (min-width: 768px) {
    .dashboard-logo img { height: 68px; }
}

/* ===============================
   DASHBOARD TILES
================================ */
.dash-tile {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    height: 90px;
    border-radius: 16px;
    padding: 10px;
    font-weight: 600;
    font-size: 11px;
    line-height: 1.2;
    text-decoration: none;
    color: #1f2f1f !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.25s ease;
    position: relative;
    background-color: #fff; /* Fallback */
}

@media (min-width: 768px) {
    .dash-tile {
        height: 110px;
        font-size: 14px;
    }
}

.dash-tile:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.tile-green { background: linear-gradient(135deg, #a8ffaf, #d5fcd9); }
.tile-teal { background: linear-gradient(135deg, #a2f8f2, #d6fdfa); }

.dash-tile::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 16px;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.4);
}
</style>
@endsection