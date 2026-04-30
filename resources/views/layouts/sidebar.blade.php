<div class="sidebar" id="sidebar">

    {{-- Sidebar Logo Area --}}
    <div class="sidebar-logo d-flex align-items-center justify-content-center py-3 border-bottom border-secondary">
        <div class="logo-full text-center">
            <img src="{{ asset('images/logo1.png') }}" alt="Logo" style="max-height: 40px;">
            <h6 class="mt-2 mb-0 text-white fw-bold">AI Patrolling</h6>
        </div>
        <div class="logo-mini text-center d-none">
            <img src="{{ asset('images/logo1.png') }}" alt="Logo" style="max-height: 30px;">
        </div>
    </div>

    <div class="sidebar-content py-2">

        {{-- MAIN DASHBOARD --}}
        {{-- <a href="/forest-dashboard" 
           class="sidebar-link {{ request()->routeIs('/') || request()->is('/') ? 'active' : '' }}"
           title="Main Dashboard">
            <i class="bi bi-speedometer2 fs-5 me-2"></i>
            <span class="link-text">Main Dashboard</span>
        </a> --}}

        <a href="/home" 
           class="sidebar-link {{ request()->is('home') ? 'active' : '' }}"
           title="Old Dashboard">
            <i class="bi bi-archive fs-5 me-2"></i>
            <span class="link-text">Dashboard</span>
        </a>

        <a href="/analytics/executive" 
           class="sidebar-link {{ request()->is('analytics/executive') ? 'active' : '' }}"
           title="Analytics">
            <i class="bi bi-graph-up-arrow fs-5 me-2"></i>
            <span class="link-text">Analytics</span>
        </a>

        <hr class="sidebar-divider my-2 text-white">

        {{-- 1. Foot Patrolling --}}
        <a href="/patrol/foot-summary" 
           class="sidebar-link {{ request()->is('patrol/foot-summary') ? 'active' : '' }}"
           title="Foot Patrolling">
            <i class="bi bi-person-walking fs-5 me-2"></i>
            <span class="link-text">Foot Patrolling</span>
        </a>

        {{-- 2. Night Patrolling --}}
        <a href="/patrol/night-summary" 
           class="sidebar-link {{ request()->is('patrol/night-summary') ? 'active' : '' }}"
           title="Night Patrolling">
            <i class="bi bi-moon-stars fs-5 me-2"></i>
            <span class="link-text">Night Patrolling</span>
        </a>

        {{-- 3. KML/Patrol Map --}}
        <a href="/patrol/maps" 
           class="sidebar-link {{ request()->is('patrol/maps') ? 'active' : '' }}"
           title="KML/Patrol Map">
            <i class="bi bi-map fs-5 me-2"></i>
            <span class="link-text">KML/Patrol Map</span>
        </a>

        {{-- 3.1 Know Your Area --}}
        <a href="/know-your-area" 
           class="sidebar-link {{ request()->is('know-your-area') ? 'active' : '' }}"
           title="Know Your Area">
            <i class="bi bi-geo-alt fs-5 me-2"></i>
            <span class="link-text">Know Your Area</span>
        </a>

        {{-- 4. Attendance Summary --}}
        <a href="/attendance/summary" 
           class="sidebar-link {{ request()->is('attendance/summary') ? 'active' : '' }}"
           title="Attendance Summary">
            <i class="bi bi-calendar-check fs-5 me-2"></i>
            <span class="link-text">Attendance Summary</span>
        </a>

        {{-- 4.1 Attendance Request --}}
        <a href="{{ route('attendance.requests') }}" 
           class="sidebar-link {{ request()->routeIs('attendance.requests') ? 'active' : '' }}"
           title="Attendance Request">
            <i class="bi bi-calendar-range fs-5 me-2"></i>
            <span class="link-text">Attendance Request</span>
        </a>

        {{-- 5. Incident Summary --}}
        <a href="/incidents/summary" 
           class="sidebar-link {{ request()->is('incidents/summary') ? 'active' : '' }}"
           title="Incident Summary">
            <i class="bi bi-exclamation-triangle fs-5 me-2"></i>
            <span class="link-text">Incident Summary</span>
        </a>

        {{-- 6. Reports --}}
        <a href="/reports/monthly" 
           class="sidebar-link {{ request()->is('reports/monthly') ? 'active' : '' }}"
           title="Reports">
            <i class="bi bi-file-earmark-text fs-5 me-2"></i>
            <span class="link-text">Reports</span>
        </a>

        {{-- 7. Camera & Tracking --}}
        <a href="/reports/camera-tracking" 
           class="sidebar-link {{ request()->is('reports/camera-tracking') ? 'active' : '' }}"
           title="Camera & Tracking">
            <i class="bi bi-camera-video fs-5 me-2"></i>
            <span class="link-text">Camera & Tracking</span>
        </a>

        <hr class="sidebar-divider my-2 text-white">

        {{-- 8. User Settings --}}
        <a href="{{ route('profile') }}" 
           class="sidebar-link {{ request()->routeIs('profile') ? 'active' : '' }}"
           title="My Profile">
            <i class="bi bi-person-gear fs-5 me-2"></i>
            <span class="link-text">My Profile</span>
        </a>

    </div>
</div>