<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Patrol Analytics</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/patrol-map.css') }}">

    <link rel="stylesheet" href="{{ asset('css/global-filters.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table-sort.css') }}">
    <link rel="stylesheet" href="{{ asset('css/enhanced-dashboard.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/global-filters.js') }}" defer></script>
    <script src="{{ asset('js/enhanced-table-sort.js') }}" defer></script>
    <script src="{{ asset('js/global-handlers.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        /* Mobile Overlay Backdrop */
        #sidebarBackdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 1040;
            /* Just below sidebar (1045) but above content */
        }

        body.mobile-sidebar-open #sidebarBackdrop {
            display: block;
        }

        @media (min-width: 992px) {
            #sidebarBackdrop {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    {{-- Mobile Overlay Backdrop --}}
    <div id="sidebarBackdrop"></div>

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Decorative Backgrounds --}}
    <div class="nature-bg left-bg"></div>
    <div class="nature-bg right-bg"></div>

    {{-- Main Content Area --}}
    <div class="content d-flex flex-column" id="mainContent">

        {{-- 1. Navbar --}}
        <div class="w-100">
            @include('layouts.navbar')
        </div>

        {{-- 2. Global Filters --}}
        @if(!isset($hideGlobalFilters) || !$hideGlobalFilters)
            <div class="container-fluid px-2 px-md-4">
                @include('partials.global-filters')
            </div>
        @endif

        {{-- 3. Main Page Content --}}
        <div class="container-fluid px-2 px-md-4 flex-grow-1">
            @yield('content')
        </div>
    </div>

    {{-- Guard Detail Modal --}}
    @include('partials.guard-detail-modal')

    {{-- Toast Notifications --}}
    @include('components.toast-container')

    {{-- Skeleton Loader --}}
    @include('components.skeleton-loader')

    {{-- Sidebar Logic Script --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            const body = document.body;

            // 1. Initial State Restoration
            const isCollapsed = localStorage.getItem('sidebarState') === 'collapsed';
            if (isCollapsed && window.innerWidth > 991) {
                sidebar.classList.add('collapsed');
                body.classList.add('sidebar-mini');
            }

            // 2. Toggle Click Handler
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (window.innerWidth < 992) {
                        // MOBILE: Toggle Drawer & Backdrop
                        sidebar.classList.toggle('mobile-show');
                        body.classList.toggle('mobile-sidebar-open');
                    } else {
                        // DESKTOP: Toggle Mini Sidebar
                        sidebar.classList.toggle('collapsed');
                        body.classList.toggle('sidebar-mini');

                        const state = sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded';
                        localStorage.setItem('sidebarState', state);
                    }
                });
            }

            // 3. Close sidebar when clicking backdrop (Mobile)
            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    sidebar.classList.remove('mobile-show');
                    body.classList.remove('mobile-sidebar-open');
                });
            }

            // 4. Scroll Position Persistence
            const savedPos = localStorage.getItem('sidebarScrollPos');
            if (savedPos && sidebar) {
                sidebar.scrollTop = savedPos;
            }
            if (sidebar) {
                sidebar.addEventListener('scroll', function () {
                    localStorage.setItem('sidebarScrollPos', sidebar.scrollTop);
                });
            }
        });
    </script>

    @stack('modals')
    @stack('scripts')

</body>

</html>