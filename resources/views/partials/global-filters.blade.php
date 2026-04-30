@if ((!isset($hideFilters) || !$hideFilters) && isset($ranges))

    <div class="global-filter-card">
        <form method="GET" class="global-filter-grid" @if(isset($manualFilterApply) && $manualFilterApply) data-manual-apply="true" @endif>

            {{-- Keep other query params (pagination, sorting, etc.) --}}
            @foreach(request()->except(['range', 'beat', 'user', 'start_date', 'end_date', 'guard_search', 'page']) as $k => $v)
                @if(is_array($v))
                    @foreach($v as $subKey => $subValue)
                        <input type="hidden" name="{{ $k }}[{{ $subKey }}]" value="{{ $subValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach

            {{-- RANGE --}}
            <div class="filter-block">
                <label>Range</label>
                <select name="range" id="rangeSelect">
                    <option value="">All Ranges</option>
                    @foreach($ranges as $id => $name)
                        <option value="{{ $id }}" {{ request('range') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- BEAT (Dependent on Range) --}}
            <div class="filter-block">
                <label>Beat</label>
                <select name="beat" id="beatSelect" {{ (request('range') || $beats->isNotEmpty()) ? '' : 'disabled' }}>
                    <option value="">All Beats</option>
                    @foreach($beats as $id => $name)
                        <option value="{{ $id }}" {{ request('beat') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- USER (Dependent on Beat/Range) --}}
            <div class="filter-block">
                <label>Forest Guard / User</label>
                <select name="user" id="userSelect">
                    <option value="">All Forest Guards</option>
                    @foreach($users as $id => $name)
                        <option value="{{ $id }}" {{ request('user') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- DATE --}}
            <div class="filter-block">
                <label>Start Date</label>
                <input type="date" name="start_date" id="startDateInput" value="{{ request('start_date') }}">
            </div>

            <div class="filter-block">
                <label>End Date</label>
                <input type="date" name="end_date" id="endDateInput" value="{{ request('end_date') }}">
            </div>

            {{-- SEARCH GUARD --}}
            <!-- <div class="filter-block d-flex align-items-end justify-content-end">
                <div class="position-relative w-100" style="max-width: 250px;">
                    <input type="text" 
                           name="guard_search" 
                           id="guardSearchInput"
                           class="form-control form-control-sm pe-5" 
                           placeholder="Search Forest Guard..." 
                           value="{{ request('guard_search') }}"
                           autocomplete="off">

                    <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted search-icon" 
                       id="searchIcon"
                       style="cursor: pointer; pointer-events: auto;"></i>

                    <div id="guardAutocompleteDropdown" class="autocomplete-dropdown" style="display: none;"></div>
                </div>
            </div> -->

            {{-- Loader --}}
            <div class="filter-loading">
                <div class="spinner"></div> Loading...
            </div>

            {{-- Apply Button (only for manual-apply pages like Reports) --}}
            @if(isset($manualFilterApply) && $manualFilterApply)
                <div class="filter-block d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm" id="applyFiltersBtn" style="height: 38px; border-radius: 8px;">
                        <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                    </button>
                </div>
            @endif

        </form>

        {{-- Global Date Range Display --}}
        <div id="globalPeriodBadge" class="mt-2 text-end">
            <span class="badge bg-white shadow-sm text-dark p-2 border">
                <i class="bi bi-calendar-range me-1"></i>
                Period:
                <strong
                    id="displayStartDate">{{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : \Carbon\Carbon::now()->subDays(30)->format('d M Y') }}</strong>
                -
                <strong
                    id="displayEndDate">{{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : \Carbon\Carbon::now()->format('d M Y') }}</strong>
            </span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle filter persistence - REMOVED aggressive reset on reload
            // This was causing users to lose filters when manually refreshing
            
            // Initialize skeleton and loader visibility
            const loader = document.querySelector('.filter-loading');
            if (loader) {
                loader.style.display = 'none';
            }

            // Helper to format date as 'd M Y'
            function formatDate(dateStr) {
                if (!dateStr) return '';
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const d = new Date(dateStr);
                if (isNaN(d)) return dateStr;
                return `${String(d.getDate()).padStart(2, '0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
            }

            // Real-time Update for Date Display
            const startInput = document.getElementById('startDateInput');
            const endInput = document.getElementById('endDateInput');
            const displayStart = document.getElementById('displayStartDate');
            const displayEnd = document.getElementById('displayEndDate');

            function updateDisplayDates() {
                if (startInput && displayStart && startInput.value) {
                    displayStart.innerText = formatDate(startInput.value);
                }
                if (endInput && displayEnd && endInput.value) {
                    displayEnd.innerText = formatDate(endInput.value);
                }
            }

            if (startInput) startInput.addEventListener('change', updateDisplayDates);
            if (endInput) endInput.addEventListener('change', updateDisplayDates);

            // Fallback: Hide loader after 5 seconds if still visible (in case of errors)
            setTimeout(function () {
                if (loader && loader.style.display !== 'none') {
                    loader.style.display = 'none';
                }
            }, 5000);

            // Function to reset search box
            function resetSearchBox() {
                const searchInput = document.getElementById('guardSearchInput');
                if (searchInput) {
                    searchInput.value = '';
                }
            }

            // Detect manual-apply mode (Reports page)
            const filterForm = document.querySelector('.global-filter-grid');
            const isManualApply = filterForm && filterForm.dataset.manualApply === 'true';

            // Function to submit filters (AJAX for supported pages, form submit for others)
            function submitFilters() {
                // If manual-apply mode, do NOT auto-submit
                if (isManualApply) return;

                // Check if we're on pages that support AJAX updates
                const isMapsPage = window.location.pathname.includes('/patrol/maps') ||
                    window.location.pathname.includes('/patrol/kml');
                const isExecutivePage = window.location.pathname.includes('/analytics/executive');

                if (isMapsPage) {
                    // Show loader for AJAX requests
                    const loader = document.querySelector('.filter-loading');
                    if (loader) loader.style.display = 'flex';
                    if (window.skeletonLoader) window.skeletonLoader.show();

                    // Use AJAX for maps page
                    updateMapsData();
                } else if (isExecutivePage) {
                    // Use AJAX for Executive Dashboard
                    updateExecutiveDashboard();
                } else {
                    // Use form submit for all other pages
                    const form = document.querySelector('.global-filter-grid');
                    if (form) {
                        form.submit();
                    }
                }
            }

            // Global abort controller for executive dashboard updates
            let dashboardAbortController = null;

            // AJAX function to update Executive Dashboard without page reload
            function updateExecutiveDashboard() {
                const form = document.querySelector('.global-filter-grid');
                if (!form) return;

                // Cancel any pending request
                if (dashboardAbortController) {
                    dashboardAbortController.abort();
                }
                dashboardAbortController = new AbortController();

                // Show loader
                const loader = document.querySelector('.filter-loading');
                if (loader) loader.style.display = 'flex';
                
                // Add a subtle overlay to indicate loading on the dashboard content
                const dashboardContent = document.getElementById('dashboardContent');
                if (dashboardContent) {
                    dashboardContent.style.opacity = '0.5';
                    dashboardContent.style.pointerEvents = 'none';
                }

                // Build query string from form data
                const formData = new FormData(form);
                const params = new URLSearchParams();

                // Add all filter values
                for (const [key, value] of formData.entries()) {
                    if (value && value.trim() !== '') {
                        params.append(key, value);
                    }
                }

                // Update URL without reload
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.pushState({}, '', newUrl);

                // Fetch new content
                console.log('Fetching update from:', newUrl);
                fetch(newUrl, {
                    method: 'GET',
                    signal: dashboardAbortController.signal,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html' // We want the partial HTML back
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    
                    // Specific check: if redirected to login, force full page reload
                    if (response.redirected && response.url.includes('/login')) {
                        window.location.href = response.url;
                        return Promise.reject(new Error('Redirected to login'));
                    }
                    if (response.status === 401 || response.status === 403) {
                        window.location.reload();
                        return Promise.reject(new Error('Unauthenticated'));
                    }
                    
                    return response.text();
                })
                .then(html => {
                    console.log('Received HTML length:', html.length);
                    // console.log('HTML Preview:', html.substring(0, 500));
                    
                    // Safety check to prevent injecting the login or error page into the dashboard
                    if (html.includes('<html') && html.includes('<body') && html.includes('login')) {
                        console.warn('Received full login page HTML. Forcing reload.');
                        window.location.reload();
                        return;
                    }

                    if (dashboardContent) {
                        // Reset content
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.getElementById('dashboardContent');
                        
                        if (newContent) {
                            console.log('Found #dashboardContent in response, updating innerHTML');
                            dashboardContent.innerHTML = newContent.innerHTML;
                        } else {
                            // Only fallback if the HTML looks like a partial (does not contain a full document structure)
                            if (html.includes('<html') && html.includes('<body')) {
                                console.error('Received a full HTML document without #dashboardContent. Falling back to full reload.');
                                form.submit();
                                return;
                            }
                            // Fallback if ID not found - the controller likely returned the partial directly
                            console.log('#dashboardContent not found in response, assuming partial and updating innerHTML directly');
                            dashboardContent.innerHTML = html;
                        }

                        console.log('Dashboard innerHTML updated. Items count:', dashboardContent.children.length);

                        // Execute script tags manually since innerHTML doesn't do it automatically
                        const scripts = dashboardContent.querySelectorAll('script');
                        console.log('Found scripts to re-execute:', scripts.length);
                        scripts.forEach(oldScript => {
                            const newScript = document.createElement('script');
                            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                            oldScript.parentNode.replaceChild(newScript, oldScript);
                        });
                        
                        // Re-initialize charts and plugins after DOM update and script execution
                        setTimeout(() => {
                            console.log('Triggering chart and plugin re-initialization');
                            if (typeof window.initializeCharts === 'function') {
                                window.initializeCharts();
                            } else {
                                // Fallback: trigger DOMContentLoaded if initializeCharts is not explicitly available
                                console.log('window.initializeCharts not found, triggering DOMContentLoaded fallback');
                                const event = new Event('DOMContentLoaded');
                                document.dispatchEvent(event);
                            }
                            
                            // Re-initialize sortable tables styling and interactivity
                            if (typeof window.initTableSort === 'function') {
                                window.initTableSort();
                            }
                        }, 100);
                    } else {
                        console.error('dashboardContent element not found on page!');
                        // No dashboard content div found, fallback to reload
                        form.submit();
                    }
                })
                .catch(error => {
                    if (error.name === 'AbortError') {
                        console.log('Request aborted');
                        return;
                    }
                    console.error('Error updating dashboard:', error);
                    // Fallback to full reload on error
                    form.submit();
                })
                .finally(() => {
                    // Restore dashboard visibility and interactivity
                    if (dashboardContent) {
                        dashboardContent.style.opacity = '1';
                        dashboardContent.style.pointerEvents = 'auto';
                    }

                    // Only hide loader if this was the latest request
                    if (!dashboardAbortController || dashboardAbortController.signal.aborted) return;
                    
                    if (loader) loader.style.display = 'none';
                    
                    console.log('Update cycle completed');
                });
            }

            // AJAX function to update maps data without page reload
            function updateMapsData() {
                const form = document.querySelector('.global-filter-grid');
                if (!form) return;

                // Build query string from form data
                const formData = new FormData(form);
                const params = new URLSearchParams();

                // Add all filter values
                for (const [key, value] of formData.entries()) {
                    if (value && value.trim() !== '') {
                        params.append(key, value);
                    }
                }

                // Update URL without reload
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.pushState({}, '', newUrl);

                // Fetch filtered data
                fetch('/patrol/api/filtered-data?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update KPI cards
                            updateKPICards(data.stats);

                            // Update sessions list
                            updateSessionsList(data.sessions);

                            // Reload map data if function exists
                            if (typeof reloadMapData === 'function') {
                                reloadMapData(data.sessions);
                            }
                        } else {
                            console.error('Error fetching filtered data:', data.error);
                            if (window.toast) window.toast.error('Failed to update data');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (window.toast) window.toast.error('Failed to update data');
                    })
                    .finally(() => {
                        // Hide loaders
                        const loader = document.querySelector('.filter-loading');
                        if (loader) loader.style.display = 'none';

                        if (window.skeletonLoader) window.skeletonLoader.hide();
                    });
            }

            // Update KPI cards with new stats
            function updateKPICards(stats) {
                const kpiCards = document.querySelectorAll('.kpi-card h3');
                if (kpiCards.length >= 4) {
                    kpiCards[0].textContent = stats.total_sessions || 0;
                    kpiCards[1].textContent = stats.completed_sessions || 0;
                    kpiCards[2].textContent = stats.active_sessions || 0;
                    kpiCards[3].textContent = (stats.total_distance_km || 0).toFixed(2);
                }
            }

            // Update sessions list (placeholder - will be implemented based on actual structure)
            function updateSessionsList(sessions) {
                // This will be handled by the map reload function
                if (typeof updateSessionsSidebar === 'function') {
                    updateSessionsSidebar(sessions);
                }
            }

            // AJAX function to update executive analytics KPI cards
            function updateExecutiveAnalytics() {
                const form = document.querySelector('.global-filter-grid');
                if (!form) return;

                // Build query string from form data
                const formData = new FormData(form);
                const params = new URLSearchParams();

                // Add all filter values
                for (const [key, value] of formData.entries()) {
                    if (value && value.trim() !== '') {
                        params.append(key, value);
                    }
                }

                // Update URL without reload
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.pushState({}, '', newUrl);

                // Fetch filtered KPI data
                fetch('/analytics/executive/api/kpis?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update KPI cards
                            updateExecutiveKPICards(data.kpis);
                        } else {
                            console.error('Error fetching KPI data:', data.error);
                            if (window.toast) window.toast.error('Failed to update KPI data');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Fallback to form submit if AJAX fails
                        const form = document.querySelector('.global-filter-grid');
                        if (form) form.submit();
                    })
                    .finally(() => {
                        // Hide loaders
                        const loader = document.querySelector('.filter-loading');
                        if (loader) loader.style.display = 'none';

                        if (window.skeletonLoader) window.skeletonLoader.hide();
                    });
            }

            // Update Executive Analytics KPI cards
            function updateExecutiveKPICards(kpis) {
                // Update Active Guards
                const activeGuardsEl = document.querySelector('[data-kpi="activeGuards"]');
                if (activeGuardsEl) {
                    activeGuardsEl.textContent = number_format(kpis.activeGuards || 0);
                }

                // Update Total Patrols
                const totalPatrolsEl = document.querySelector('[data-kpi="totalPatrols"]');
                if (totalPatrolsEl) {
                    totalPatrolsEl.textContent = number_format(kpis.totalPatrols || 0);
                }

                // Update Total Distance
                const totalDistanceEl = document.querySelector('[data-kpi="totalDistance"]');
                if (totalDistanceEl) {
                    totalDistanceEl.textContent = number_format(kpis.totalDistance || 0, 2) + ' km';
                }

                // Update Attendance Rate
                const attendanceRateEl = document.querySelector('[data-kpi="attendanceRate"]');
                if (attendanceRateEl) {
                    attendanceRateEl.textContent = number_format(kpis.attendanceRate || 0, 1) + '%';
                }

                // Update Total Incidents
                const totalIncidentsEl = document.querySelector('[data-kpi="totalIncidents"]');
                const pendingIncidentsEl = document.querySelector('[data-kpi="pendingIncidents"]');
                if (totalIncidentsEl) {
                    totalIncidentsEl.textContent = number_format(kpis.totalIncidents || 0);
                }
                if (pendingIncidentsEl) {
                    pendingIncidentsEl.textContent = number_format(kpis.pendingIncidents || 0);
                }

                // Update Resolution Rate
                const resolutionRateEl = document.querySelector('[data-kpi="resolutionRate"]');
                if (resolutionRateEl) {
                    const rate = number_format(kpis.resolutionRate || 0, 1) + '%';
                    resolutionRateEl.textContent = rate;
                    
                    // Update Modal elements if they exist
                    const modalTitle = document.getElementById('modalResRateTitle');
                    const modalBar = document.getElementById('modalResRateProgressBar');
                    const modalTotal = document.getElementById('modalResRateTotal');
                    const modalResolved = document.getElementById('modalResRateResolved');
                    const modalPending = document.getElementById('modalResRatePending');
                    const modalCalc = document.getElementById('modalResRateCalculation');

                    if (modalTitle) modalTitle.textContent = rate;
                    if (modalBar) {
                        modalBar.style.width = rate;
                        modalBar.textContent = rate;
                    }
                    if (modalTotal) modalTotal.textContent = number_format(kpis.totalIncidents || 0);
                    if (modalResolved) modalResolved.textContent = number_format(kpis.resolvedIncidents || 0);
                    if (modalPending) modalPending.textContent = number_format((kpis.totalIncidents || 0) - (kpis.resolvedIncidents || 0));
                    if (modalCalc) {
                        modalCalc.innerHTML = `${number_format(kpis.resolvedIncidents || 0)} ÷ ${number_format(kpis.totalIncidents || 0)} × 100 = <strong>${rate}</strong>`;
                    }
                }

                // Update Site Coverage
                const siteCoverageEl = document.querySelector('[data-kpi="siteCoverage"]');
                if (siteCoverageEl) {
                    siteCoverageEl.textContent = number_format(kpis.siteCoverage || 0, 1) + '%';
                }

                // Update Total Sites
                const totalSitesEl = document.querySelector('[data-kpi="totalSites"]');
                if (totalSitesEl) {
                    totalSitesEl.textContent = number_format(kpis.totalSites || 0);
                }
            }

            // Helper function to format numbers
            function number_format(num, decimals = 0) {
                return parseFloat(num || 0).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            // Function to load beats for a selected range
            function loadBeats(rangeId) {
                const beatSelect = document.getElementById('beatSelect');
                if (!beatSelect) return;

                console.log('loadBeats called with rangeId:', rangeId);

                // Clear existing options except "All Beats"
                beatSelect.innerHTML = '<option value="">All Beats</option>';

                if (!rangeId) {
                    // If no range selected, DISABLE beat dropdown and clear users
                    beatSelect.disabled = true;
                    loadUsers(null, null);
                    return;
                }

                // Show loading state
                beatSelect.disabled = true;
                beatSelect.innerHTML = '<option value="">Loading beats...</option>';

                // Fetch beats for the selected range
                fetch(`/filters/beats/${rangeId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        console.log('Beats response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Beats data received:', data);
                        beatSelect.innerHTML = '<option value="">All Beats</option>';

                        if (data.beats && Object.keys(data.beats).length > 0) {
                            for (const [id, name] of Object.entries(data.beats)) {
                                const option = document.createElement('option');
                                option.value = id;
                                option.textContent = name;
                                // Preserve selected beat if it's still valid
                                if (beatSelect.dataset.selectedBeat === id) {
                                    option.selected = true;
                                }
                                beatSelect.appendChild(option);
                            }
                            // ENABLE beat dropdown when beats are loaded
                            beatSelect.disabled = false;
                            console.log('Beat dropdown enabled with', Object.keys(data.beats).length, 'beats');
                        } else {
                            // No beats found, keep disabled
                            beatSelect.disabled = true;
                            console.log('No beats found for range', rangeId);
                        }

                        // Load users for the selected range (and beat if any)
                        const selectedBeat = beatSelect.value;
                        loadUsers(rangeId, selectedBeat);
                    })
                    .catch(error => {
                        console.error('Error loading beats:', error);
                        beatSelect.innerHTML = '<option value="">All Beats</option>';
                        beatSelect.disabled = true;
                        loadUsers(rangeId, null);
                    });
            }

            // Function to load users for selected range/beat
            function loadUsers(rangeId, beatId) {
                const userSelect = document.getElementById('userSelect');
                if (!userSelect) return;

                // Clear existing options except "All Forest Guards"
                userSelect.innerHTML = '<option value="">All Forest Guards</option>';

                // Show loading state
                userSelect.disabled = true;
                userSelect.innerHTML = '<option value="">Loading forest guards...</option>';

                // Build query params
                const params = new URLSearchParams();
                if (rangeId) params.append('range', rangeId);
                if (beatId) params.append('beat', beatId);

                // Fetch users
                fetch(`/filters/users?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        userSelect.innerHTML = '<option value="">All Forest Guards</option>';

                        if (data.users && Object.keys(data.users).length > 0) {
                            for (const [id, name] of Object.entries(data.users)) {
                                const option = document.createElement('option');
                                option.value = id;
                                option.textContent = name;
                                // Preserve selected user if it's still valid
                                if (userSelect.dataset.selectedUser === id) {
                                    option.selected = true;
                                }
                                userSelect.appendChild(option);
                            }
                        }

                        userSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading users:', error);
                        userSelect.innerHTML = '<option value="">All Forest Guards</option>';
                        userSelect.disabled = false;
                    });
            }

            // Guard autocomplete functionality
            let autocompleteTimeout = null;
            const autocompleteDropdown = document.getElementById('guardAutocompleteDropdown');
            const guardSearchInput = document.getElementById('guardSearchInput');

            function showAutocomplete(suggestions) {
                if (!autocompleteDropdown || !guardSearchInput) return;

                if (suggestions.length === 0) {
                    autocompleteDropdown.style.display = 'none';
                    return;
                }

                autocompleteDropdown.innerHTML = '';
                suggestions.forEach(suggestion => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = suggestion.label;
                    item.dataset.name = suggestion.name;
                    item.addEventListener('click', function () {
                        guardSearchInput.value = suggestion.name;
                        autocompleteDropdown.style.display = 'none';
                        submitFilters();
                    });
                    autocompleteDropdown.appendChild(item);
                });

                autocompleteDropdown.style.display = 'block';
            }

            function fetchAutocompleteSuggestions(searchTerm) {
                if (searchTerm.length < 2) {
                    autocompleteDropdown.style.display = 'none';
                    return;
                }

                const rangeSelect = document.getElementById('rangeSelect');
                const beatSelect = document.getElementById('beatSelect');
                const params = new URLSearchParams();
                params.append('q', searchTerm);
                if (rangeSelect && rangeSelect.value) params.append('range', rangeSelect.value);
                if (beatSelect && beatSelect.value) params.append('beat', beatSelect.value);

                fetch(`/filters/guards/autocomplete?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        showAutocomplete(data.suggestions || []);
                    })
                    .catch(error => {
                        console.error('Error fetching autocomplete:', error);
                    });
            }

            if (guardSearchInput) {
                guardSearchInput.addEventListener('input', function () {
                    clearTimeout(autocompleteTimeout);
                    const searchTerm = this.value.trim();

                    autocompleteTimeout = setTimeout(() => {
                        fetchAutocompleteSuggestions(searchTerm);
                    }, 300); // 300ms debounce
                });

                // Close autocomplete when clicking outside
                document.addEventListener('click', function (e) {
                    if (!guardSearchInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
                        autocompleteDropdown.style.display = 'none';
                    }
                });
            }

            // 1. Handle Range changes
            const rangeSelect = document.getElementById('rangeSelect');
            if (rangeSelect) {
                // Store initially selected beat and user to preserve if still valid
                const beatSelect = document.getElementById('beatSelect');
                const userSelect = document.getElementById('userSelect');
                if (beatSelect && beatSelect.value) {
                    beatSelect.dataset.selectedBeat = beatSelect.value;
                }
                if (userSelect && userSelect.value) {
                    userSelect.dataset.selectedUser = userSelect.value;
                }

                rangeSelect.addEventListener('change', function () {
                    const selectedRange = this.value;
                    const beatSelect = document.getElementById('beatSelect');
                    const userSelect = document.getElementById('userSelect');

                    // If "All Ranges" is selected, reset and DISABLE beat dropdown
                    if (selectedRange === '') {
                        if (beatSelect) {
                            beatSelect.innerHTML = '<option value="">All Beats</option>';
                            beatSelect.dataset.selectedBeat = '';
                            beatSelect.disabled = true; // DISABLE beat dropdown
                        }

                        if (userSelect) {
                            userSelect.innerHTML = '<option value="">All Guards</option>';
                            userSelect.dataset.selectedUser = '';
                        }

                        // Reset search box
                        resetSearchBox();

                        // Load all users
                        loadUsers(null, null);
                        
                        // Submit immediately when clearing range
                        submitFilters();
                    } else {
                        // Load beats for selected range (will enable beat dropdown if beats exist)
                        loadBeats(selectedRange);
                        
                        // Auto-submit immediately when range changes
                        submitFilters();
                    }
                });
            }

            // 2. Handle Beat changes
            const beatSelect = document.getElementById('beatSelect');
            if (beatSelect) {
                beatSelect.addEventListener('change', function () {
                    const selectedBeat = this.value;
                    const rangeSelect = document.getElementById('rangeSelect');
                    const selectedRange = rangeSelect ? rangeSelect.value : null;

                    // If "All Beats" is selected, reset dependent filters
                    if (selectedBeat === '') {
                        const userSelect = document.getElementById('userSelect');
                        if (userSelect) {
                            userSelect.innerHTML = '<option value="">All Guards</option>';
                            userSelect.dataset.selectedUser = '';
                        }

                        // Reset search box
                        resetSearchBox();

                        // Load users for range only
                        loadUsers(selectedRange, null);
                    } else {
                        // Load users for selected range and beat
                        loadUsers(selectedRange, selectedBeat);
                    }

                    // Submit immediately when beat changes
                    submitFilters();
                });
            }

            // 3. Handle User/Guard changes
            const userSelect = document.getElementById('userSelect');
            if (userSelect) {
                userSelect.addEventListener('change', function () {
                    // If "All Guards" is selected, reset search box
                    if (this.value === '') {
                        resetSearchBox();
                    }

                    // Auto-submit when user is selected
                    submitFilters();
                });
            }

            // 4. Handle Date changes
            const startDateInput = document.getElementById('startDateInput');
            const endDateInput = document.getElementById('endDateInput');

            if (startDateInput) {
                startDateInput.addEventListener('change', function () {
                    // Date auto-fill logic
                    if (this.value && endDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        const selectedStart = new Date(this.value);

                        if (selectedStart <= new Date()) {
                            endDateInput.value = today;
                        } else {
                            endDateInput.value = this.value;
                        }
                    }

                    // Auto-submit when start date changes
                    submitFilters();
                });
            }

            if (endDateInput) {
                endDateInput.addEventListener('change', function () {
                    // Date auto-fill logic
                    if (this.value && startDateInput) {
                        const endDate = new Date(this.value);
                        const startDate = new Date(endDate.getFullYear(), endDate.getMonth(), 1);
                        const startDateStr = startDate.toISOString().split('T')[0];

                        if (!startDateInput.value || new Date(startDateInput.value) > endDate) {
                            startDateInput.value = startDateStr;
                        }
                    }

                    // Auto-submit when end date changes
                    submitFilters();
                });
            }

            // 2. Search Guard Input - Only trigger on Enter key or magnifying glass click
            const searchInput = document.getElementById('guardSearchInput');
            const searchIcon = document.getElementById('searchIcon');
            const form = searchInput ? searchInput.form : null;

            function submitSearch() {
                if (!form) {
                    console.error('Form not found');
                    if (window.toast) window.toast.error('Form submission failed');
                    return;
                }

                // Validate search input
                const searchValue = searchInput.value.trim();
                if (!searchValue) {
                    // If search is empty, remove guard_search parameter and submit
                    const url = new URL(window.location.href);
                    url.searchParams.delete('guard_search');
                    window.location.href = url.toString();
                    return;
                }

                submitFilters();
            }

            if (searchInput) {
                // Trigger search on Enter key
                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        submitSearch();
                    }
                });
            }

            if (searchIcon) {
                // Trigger search on magnifying glass icon click
                searchIcon.addEventListener('click', function () {
                    submitSearch();
                });
            }

            // Initialize dependent dropdowns on page load if filters are already selected
            // This runs after all functions are defined
            const rangeSelectInit = document.getElementById('rangeSelect');
            const beatSelectInit = document.getElementById('beatSelect');

            if (rangeSelectInit && rangeSelectInit.value) {
                // Store selected beat/user to preserve if still valid
                if (beatSelectInit && beatSelectInit.value) {
                    beatSelectInit.dataset.selectedBeat = beatSelectInit.value;
                }
                const userSelectInit = document.getElementById('userSelect');
                if (userSelectInit && userSelectInit.value) {
                    userSelectInit.dataset.selectedUser = userSelectInit.value;
                }

                // Load beats and users for the selected range
                loadBeats(rangeSelectInit.value);
            } else if (beatSelectInit && beatSelectInit.value) {
                // If only beat is selected (shouldn't happen, but handle it)
                const userSelectInit = document.getElementById('userSelect');
                if (userSelectInit && userSelectInit.value) {
                    userSelectInit.dataset.selectedUser = userSelectInit.value;
                }
                loadUsers(null, beatSelectInit.value);
            }
        });
    </script>

@endif