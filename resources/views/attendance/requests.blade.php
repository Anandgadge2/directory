@extends('layouts.app')

@section('content')
<div class="card p-4 border-0 shadow-sm" style="background: #fff; border-radius: 15px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Off Premises Attendance</h4>
        <button class="btn btn-warning text-white fw-bold px-4 rounded-pill shadow-sm" id="approveAllBtn">
            <i class="bi bi-check2-circle me-1"></i> Approve All
        </button>
    </div>

    {{-- Filters/Search row --}}
    <div class="row mb-3 align-items-center">
        <div class="col-md-6 d-flex align-items-center gap-2">
            <span class="text-muted">Show</span>
            <select class="form-select form-select-sm w-auto" id="entryLimit">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $limit == 100 ? 'selected' : '' }}>100</option>
            </select>
            <span class="text-muted">entries</span>
        </div>
        <div class="col-md-6 text-end">
            <div class="d-inline-flex align-items-center gap-2">
                <span class="text-muted">Search:</span>
                <input type="text" class="form-control form-control-sm" id="searchTable" style="width: 200px;">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle custom-table" id="attendanceTable">
            <thead class="bg-light">
                <tr>
                    <th class="text-secondary small fw-bold">SR. NO.</th>
                    <th class="text-secondary small fw-bold">EMPLOYEE NAME</th>
                    <th class="text-secondary small fw-bold">GEOFENCE</th>
                    <th class="text-secondary small fw-bold">TYPE</th>
                    <th class="text-secondary small fw-bold">REMARK</th>
                    <th class="text-secondary small fw-bold">DATE & TIME</th>
                    <th class="text-secondary small fw-bold">STATUS</th>
                    <th class="text-secondary small fw-bold text-center">MAP</th>
                    <th class="text-secondary small fw-bold">ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td class="small">{{ $loop->iteration + ($requests->currentPage() - 1) * $requests->perPage() }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-dark">{{ \App\Helpers\FormatHelper::formatName($request->name) }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border fw-normal">{{ $request->geo_name ?: 'Current Location' }}</span>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-light text-primary border px-3">{{ $request->inOutStatus ?: ($request->entry_time ? 'Entry' : 'Exit') }}</span>
                        </td>
                        <td class="text-muted small">{{ $request->duration_for_calc ?: '-' }}</td>
                        <td>
                            <div class="small fw-bold">{{ Carbon\Carbon::parse($request->dateFormat)->format('d-m-Y') }}</div>
                            <div class="text-muted" style="font-size: 0.8rem;">{{ $request->entry_time ?: $request->exit_time }}</div>
                        </td>
                        <td>
                            @if($request->status == 1)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3">Approved</span>
                            @elseif($request->status == 0)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3">Pending</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3">Rejected</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($request->location)
                                <button class="btn btn-sm btn-outline-primary border-0 view-map-btn" 
                                        data-location="{{ $request->location }}"
                                        data-name="{{ $request->name }}"
                                        data-date="{{ $request->dateFormat }} {{ $request->entry_time }}">
                                    <i class="bi bi-geo-alt fs-5"></i>
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($request->status == 0)
                                <button class="btn btn-sm btn-outline-success border-0 approve-btn" data-id="{{ $request->id }}">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            @else
                                <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Showing {{ $requests->firstItem() }} to {{ $requests->lastItem() }} of {{ $requests->total() }} entries
        </div>
        <div class="pagination-container">
            {{ $requests->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Map Modal --}}
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="mapModalTitle">Check-In Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalMap" style="height: 450px; width: 100%; border-radius: 12px; border: 1px solid #eee;"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-table thead th {
        padding: 15px 12px;
        border-top: none;
        border-bottom: 2px solid #f8f9fa;
    }
    .custom-table tbody td {
        padding: 15px 12px;
        border-bottom: 1px solid #f8f9fa;
    }
    .custom-table tbody tr:hover {
        background-color: #fcfcfc;
    }
    .bg-success-subtle { background-color: #e6fffa !important; }
    .bg-warning-subtle { background-color: #fffaf0 !important; }
    .bg-danger-subtle { background-color: #fff5f5 !important; }
    
    .view-map-btn:hover {
        background: #f0f7ff;
        color: #0d6efd;
    }

    /* Custom Pagination Styling */
    .pagination {
        margin-bottom: 0;
        gap: 5px;
    }
    .page-item .page-link {
        border-radius: 8px !important;
        padding: 8px 16px;
        color: #4b5563;
        border: 1px solid #e5e7eb;
        font-weight: 500;
        font-size: 0.875rem;
    }
    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    .page-link:hover {
        background-color: #f3f4f6;
        color: #0d6efd;
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let map = null;
    let marker = null;
    let tileLayer = null;

    const mapModalEl = document.getElementById('mapModal');
    const mapModal = new bootstrap.Modal(mapModalEl);

    document.querySelectorAll('.view-map-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const locationStr = this.getAttribute('data-location');
            const name = this.getAttribute('data-name');
            const date = this.getAttribute('data-date');

            if (!locationStr) return;

            // Handle potential JSON or string format
            let lat, lng;
            try {
                if (locationStr.includes('{')) {
                    const loc = JSON.parse(locationStr);
                    lat = loc.lat;
                    lng = loc.lng;
                } else if (locationStr.includes(',')) {
                    const parts = locationStr.split(',');
                    lat = parseFloat(parts[0]);
                    lng = parseFloat(parts[1]);
                }
            } catch (e) {
                console.error("Error parsing location", e);
                return;
            }

            if (isNaN(lat) || isNaN(lng)) return;

            document.getElementById('mapModalTitle').innerText = `Check-In Location: ${name} (${date})`;
            mapModal.show();

            // Initialize or update map after modal is shown
            setTimeout(() => {
                if (!map) {
                    map = L.map('modalMap').setView([lat, lng], 15);
                    tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                } else {
                    map.setView([lat, lng], 15);
                    map.invalidateSize();
                }

                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng]).addTo(map);
                }
                
                marker.bindPopup(`<b>${name}</b><br>${date}`).openPopup();
            }, 300);
        });
    });

    // Reset map size on modal show (fix for leaflet grey tiles)
    mapModalEl.addEventListener('shown.bs.modal', function() {
        if (map) {
            map.invalidateSize();
        }
    });

    // Entry limit functionality
    document.getElementById('entryLimit').addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('limit', this.value);
        url.searchParams.set('page', 1); // Reset to page 1
        window.location.href = url.toString();
    });

    // Search functionality
    document.getElementById('searchTable').addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('#attendanceTable tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });

    // Individual Approve
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const row = this.closest('tr');
            if(confirm('Approve this request?')) {
                fetch(`/attendance/approve/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                   if(data.status === 'success') {
                       row.cells[6].innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3">Approved</span>';
                       this.parentElement.innerHTML = '<span class="text-muted small">N/A</span>';
                   } else {
                       alert('Error: ' + data.message);
                   }
                });
            }
        });
    });

    // Approve All
    document.getElementById('approveAllBtn').addEventListener('click', function() {
        // Simple implementation: click all visible approve buttons
        const visibleBtns = Array.from(document.querySelectorAll('.approve-btn')).filter(b => b.offsetParent !== null);
        if(visibleBtns.length === 0) {
            alert('No pending requests to approve.');
            return;
        }
        if(confirm(`Approve all ${visibleBtns.length} visible pending requests?`)) {
            let processed = 0;
            visibleBtns.forEach(btn => {
                const id = btn.getAttribute('data-id');
                const row = btn.closest('tr');
                fetch(`/attendance/approve/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        row.cells[6].innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3">Approved</span>';
                        btn.parentElement.innerHTML = '<span class="text-muted small">N/A</span>';
                    }
                    processed++;
                    if(processed === visibleBtns.length) {
                        alert('Approval process completed.');
                    }
                });
            });
        }
    });
});
</script>
@endpush
