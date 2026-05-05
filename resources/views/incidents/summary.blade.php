@extends('layouts.app')

@section('content')

@php
    function severityBadge($s) {
        $map = [
            5 => 'danger',
            4 => 'warning',
            3 => 'primary',
            2 => 'success',
        ];
        return $map[$s] ?? 'secondary';
    }
@endphp

{{-- ================= HEADER ================= --}}
<div class="mb-3">
    <h5 class="fw-bold mb-0">Incident Summary</h5>
    <small class="text-muted">Consolidated incident analytics and detailed reports</small>
</div>

{{-- ================= TOP KPIs ================= --}}
<div class="row g-3 mb-4">
    @php
        $kpiItems = [
            ['total_incidents', 'Total Incidents', $kpis['total_incidents'], 'bi-exclamation-triangle-fill', 'text-danger', 'rgba(220, 53, 69, 0.1)'],
            ['animal_sighting', 'Animal Sightings', $kpis['animal_sightings'], 'bi-eye-fill', 'text-success', 'rgba(25, 135, 84, 0.1)'],
            ['water_source', 'Water Sources', $kpis['water_sources'], 'bi-droplet-fill', 'text-info', 'rgba(13, 202, 240, 0.1)'],
            ['fire', 'Fire Incidents', $kpis['fire'], 'bi-fire', 'text-danger', 'rgba(220, 53, 69, 0.12)'],
            ['birds', 'Bird Sightings', $kpis['birds'], 'bi-feather', 'text-primary', 'rgba(13, 110, 253, 0.1)'],
            ['insect_butterfly', 'Insect/Butterfly', $kpis['insects_butterflies'], 'bi-bug', 'text-warning', 'rgba(255, 193, 7, 0.14)']
        ];
    @endphp
    @foreach($kpiItems as $item)
    <div class="col-md">
        <div class="card border-0 shadow-sm h-100 kpi-card clickable" onclick="showIncidentsByType('{{ $item[0] }}', '{{ $item[1] }}')" style="cursor: pointer; background: white !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">{{ $item[1] }}</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ number_format($item[2]) }}</h3>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center kpi-icon-wrapper" style="width: 40px; height: 40px; background: {{ $item[5] }}; color: inherit;">
                        <i class="bi {{ $item[3] }} {{ $item[4] }} fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div id="incidentTableContainer">
    @include('incidents.partials.incident-table')
</div>

{{-- ================= CHARTS ROW ================= --}}
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-3 h-100 border-0 shadow-sm">
            <h6 class="fw-bold mb-3">Incident Distribution</h6>
            <div style="height:250px"><canvas id="typeChart"></canvas></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100 border-0 shadow-sm">
            <h6 class="fw-bold mb-3">By Patrol Mode</h6>
            <div style="height:250px"><canvas id="sessionChart"></canvas></div>
        </div>
    </div>
</div>

@endsection

@push('modals')
{{-- ================= INCIDENT DETAILS MODAL (Premium Style) ================= --}}
<div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 650px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold m-0"><i class="bi bi-shield-exclamation me-2"></i>Incident Evidence Detail</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="modalLoading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Fetching details...</p>
            </div>
            <div id="incidentDetails" class="modal-body bg-light-subtle p-0">
                <!-- Dynamic Content -->
            </div>
        </div>
    </div>
</div>

{{-- ================= TYPE MODAL (Premium Style) ================= --}}
<div class="modal fade" id="typeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 600px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title fw-bold m-0" id="typeModalTitle">📌 Incidents List</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th>Type</th>
                            <th>Forest Guard</th>
                            <th>Location</th>
                            <th class="text-end pe-3">Date</th>
                        </tr>
                    </thead>
                    <tbody id="typeListBody">
                        <!-- Dynamic -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
/* ================= AJAX PAGINATION ================= */
document.addEventListener('click', function(e) {
    if(e.target.closest('#incidentTableContainer .pagination a')) {
        e.preventDefault();
        let url = e.target.closest('a').href;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            document.getElementById('incidentTableContainer').innerHTML = html;
            if (window.initTableSort) window.initTableSort();
        })
        .catch(e => console.error(e));
    }
});

/* ================= MODAL JS ================= */
async function openIncidentDetail(id) {
    const modalEl = document.getElementById('incidentModal');
    const detailsContainer = document.getElementById('incidentDetails');
    const loading = document.getElementById('modalLoading');
    
    // Close type modal if open to prevent stacking issues
    const typeModalEl = document.getElementById('typeModal');
    const typeModalInstance = bootstrap.Modal.getInstance(typeModalEl);
    if(typeModalInstance) typeModalInstance.hide();

    detailsContainer.innerHTML = '';
    detailsContainer.classList.add('d-none');
    loading.classList.remove('d-none');
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    try {
        const response = await fetch(`/incidents/${id}/details`);
        const data = await response.json();
        
        if (data.error) throw new Error(data.error);

        const inc = data.incident || {};
        const comments = data.comments || [];
        const typeText = (inc.type || 'Incident').replace(/_/g, ' ').toUpperCase();

        let formattedDate = 'N/A';
        if (inc.created_at) {
            const dateObj = new Date(inc.created_at);
            formattedDate = dateObj.toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' });
        }

        loading.classList.add('d-none');
        detailsContainer.classList.remove('d-none');
        
        detailsContainer.innerHTML = `
            <div class="p-4">
                <!-- Status Row -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex gap-2">
                         <span class="badge rounded-pill bg-soft-warning text-warning border px-3 py-2">
                            <i class="bi bi-hourglass-split me-1"></i> PENDING
                        </span>
                        <span class="badge rounded-pill bg-soft-primary text-primary border px-3 py-2">
                            <i class="bi ${inc.session === 'Night' ? 'bi-moon-stars' : 'bi-sun'} me-1"></i> ${inc.session || 'Day'} Mode
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">PRIORITY</small>
                        <div class="fw-bold text-${inc.priority === 'High' ? 'danger' : (inc.priority === 'Medium' ? 'warning' : 'success')}">${inc.priority || 'Low'}</div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="p-3 bg-light d-flex justify-content-between align-items-center border-bottom text-primary">
                        <h5 class="fw-bold mb-0 d-flex align-items-center">
                            <i class="bi bi-shield-fill-exclamation me-2"></i> ${typeText}
                        </h5>
                        <div class="text-end small">
                            <div class="fw-bold">${formattedDate}</div>
                        </div>
                    </div>
                    
                    <div class="row g-0">
                        <div class="col-md-5 bg-light-subtle border-end p-3">
                            <h6 class="extra-small text-muted text-uppercase fw-bold mb-3"><i class="bi bi-person-fill me-1"></i> Reporter Details</h6>
                            <div class="d-flex align-items-center mb-3 p-2 bg-white rounded-3 shadow-sm">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-weight: bold;">
                                    ${(inc.guard_name || 'A').charAt(0)}
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark text-truncate">${inc.guard_name || 'Anonymous'}</div>
                                    <div class="extra-small text-primary"><i class="bi bi-telephone-fill"></i> ${inc.guard_contact || 'N/A'}</div>
                                </div>
                            </div>
                            
                            <h6 class="extra-small text-muted text-uppercase fw-bold mb-2 mt-4"><i class="bi bi-geo-alt-fill me-1"></i> Location</h6>
                            <div class="small fw-semibold text-dark">${inc.beat_name || 'Site NA'}</div>
                            <div class="extra-small text-muted mb-2">${inc.range_name || 'Range NA'}</div>
                            <div class="bg-white p-2 rounded-2 border extra-small d-flex justify-content-between align-items-center">
                                <code>${parseFloat(inc.lat).toFixed(4)}, ${parseFloat(inc.lng).toFixed(4)}</code>
                                <a href="https://www.google.com/maps?q=${inc.lat},${inc.lng}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:0.6rem">MAP</a>
                            </div>
                        </div>
                        <div class="col-md-7 p-3">
                            <h6 class="extra-small text-muted text-uppercase fw-bold mb-3"><i class="bi bi-camera-fill me-1"></i> Incident Evidence</h6>
                            <div class="rounded-3 overflow-hidden border bg-light shadow-sm" style="height: 160px;">
                                ${inc.photo ? 
                                    `<img src="${inc.photo.startsWith('data:') ? inc.photo : '/storage/'+inc.photo}" class="w-100 h-100 object-fit-cover cursor-zoom-in" onclick="window.open(this.src, '_blank')">` : 
                                    `<div class="d-flex flex-column align-items-center justify-content-center h-100 opacity-50">
                                        <i class="bi bi-image fs-1"></i>
                                        <div class="extra-small">No photo uploaded</div>
                                    </div>`
                                }
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="extra-small text-muted text-uppercase fw-bold mb-2"><i class="bi bi-chat-right-text-fill me-1"></i> Observations</h6>
                    <div class="p-3 bg-white border-start border-4 border-primary rounded shadow-sm fs-6">
                        "${inc.notes || 'No specific observations recorded.'}"
                    </div>
                </div>
                
                <div class="border-top pt-3">
                    <h6 class="extra-small text-muted text-uppercase fw-bold mb-3">Timeline & Comments (${comments.length})</h6>
                    <div class="ps-2">
                        ${comments.length > 0 ? comments.map(c => `
                            <div class="d-flex mb-2">
                                <div class="bg-light p-2 rounded shadow-sm w-100">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold small">${c.user_name}</span>
                                        <span class="text-muted extra-small">${new Date(c.created_at).toLocaleDateString()}</span>
                                    </div>
                                    <div class="small text-muted">${c.comment}</div>
                                </div>
                            </div>
                        `).join('') : '<div class="text-muted extra-small italic">No comments available.</div>'}
                    </div>
                </div>
            </div>
        `;
    } catch (err) {
        loading.classList.add('d-none');
        detailsContainer.classList.remove('d-none');
        detailsContainer.innerHTML = `<div class="p-4 text-center"><div class="alert alert-danger">Error: ${err.message}</div></div>`;
    }
}

async function showIncidentsByType(key, label, extraParams = {}) {
    const modalEl = document.getElementById('typeModal');
    const title = document.getElementById('typeModalTitle');
    const body = document.getElementById('typeListBody');
    
    title.innerText = label;
    body.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</td></tr>';
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    try {
        let url = `/incidents/type/${key}?source=forest_reports&`;
        Object.keys(extraParams).forEach(k => {
            url += `${k}=${encodeURIComponent(extraParams[k])}&`;
        });
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (!data.incidents || data.incidents.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No incidents found for this selection.</td></tr>';
            return;
        }

        body.innerHTML = data.incidents.map((inc, index) => `
            <tr onclick="openIncidentDetail(${inc.id})" style="cursor:pointer">
                <td class="ps-3 text-muted small">${index + 1}</td>
                <td><span class="badge bg-light text-dark border">${inc.type.replace(/_/g, ' ')}</span></td>
                <td class="text-start ps-3"><small>${inc.guard || '—'}</small></td>
                <td><small>${inc.beat_name || '—'}<br><span class="text-muted" style="font-size:10px">${inc.range_name || ''}</span></small></td>
                <td class="text-end pe-3 font-monospace" style="font-size:11px">${new Date(inc.created_at).toLocaleDateString()}</td>
            </tr>
        `).join('');
    } catch (err) {
        body.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error: ${err.message}</td></tr>`;
    }
}

function closeIncident() {
    const modalEl = document.getElementById('incidentModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if(modal) modal.hide();
}

function closeTypeModal() {
    const modalEl = document.getElementById('typeModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if(modal) modal.hide();
}

/* ================= CHARTS ================= */
document.addEventListener('DOMContentLoaded', () => {

    const noDataPlugin = {
        id: 'noDataPlugin',
        afterDraw: (chart) => {
            const datasets = chart.data.datasets;
            let hasData = false;
            if (datasets.length > 0) {
                hasData = datasets[0].data.some(v => v > 0);
            }
            
            if (!hasData) {
                const { ctx, width, height } = chart;
                chart.clear();
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = '14px Inter, system-ui';
                ctx.fillStyle = '#6c757d';
                ctx.fillText('No data available', width / 2, height / 2);
                ctx.restore();
            }
        }
    };

    /* --- CHART 1: TYPE (Doughnut) --- */
    const typeLabelsRaw = {!! json_encode($typeStats->pluck('type')) !!};
    const typeLabelsReadable = {!! json_encode($typeStats->pluck('type')->map(fn($t)=>ucwords(str_replace('_',' ',$t)))) !!};
    const typeData = {!! json_encode($typeStats->pluck('total')) !!};
    
    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        plugins: [noDataPlugin],
        data: {
            labels: typeLabelsReadable.length > 0 ? typeLabelsReadable : ['No data'],
            datasets: [{
                data: typeData.length > 0 ? typeData : [0],
                backgroundColor: typeData.length > 0 ? ['#2e7d32','#1e88e5','#f9a825','#c62828','#555'] : ['#f8f9fa'],
                borderWidth:0
            }]
        },
        options:{ 
            maintainAspectRatio:false,
            cutout:'60%',
            onClick: (evt, elements, chart) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const type = typeLabelsRaw[index];
                    const label = typeLabelsReadable[index];
                    showIncidentsByType(type, label);
                }
            },
            plugins: { 
                legend: { 
                    display: typeData.length > 0,
                    position: 'bottom', 
                    labels:{boxWidth:12, cursor: 'pointer'} 
                },
                tooltip: { enabled: typeData.length > 0 }
            }
        }
    });

    /* --- CHART 2: SESSION (Bar) --- */
    const sessionLabels = {!! json_encode($sessionStats->pluck('session')) !!};
    const sessionData = {!! json_encode($sessionStats->pluck('total')) !!};

    new Chart(document.getElementById('sessionChart'), {
        type: 'bar',
        plugins: [noDataPlugin],
        data: {
            labels: sessionLabels.length > 0 ? sessionLabels : ['No data'],
            datasets: [{
                data: sessionData.length > 0 ? sessionData : [0],
                backgroundColor: sessionData.length > 0 ? '#1565c0' : '#f8f9fa',
                borderRadius:4,
                barThickness:20
            }]
        },
        options:{
            maintainAspectRatio:false,
            onClick: (evt, elements, chart) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const sessionName = sessionLabels[index];
                    showIncidentsByType('all', `Patrol Mode: ${sessionName}`, { session: sessionName });
                }
            },
            plugins:{ 
                legend:{ display:false },
                tooltip: { enabled: sessionData.length > 0 }
            },
            scales:{ 
                y:{ 
                    beginAtZero:true, 
                    display: sessionData.length > 0,
                    grid:{display:true} 
                }, 
                x:{ 
                    display: sessionData.length > 0,
                    grid:{display:false} 
                } 
            }
        }
    });


});
</script>

<style>
/* KPI Styles */
.kpi-card.clickable { cursor: pointer; }
.kpi-card.clickable:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

/* Comment Timeline */
.comment-item { border-left: 2px solid #eef2f7; }
.incident-photo-container img { width: 100%; object-fit: cover; max-height: 300px; border-radius: 8px; transition: 0.3s; }
.incident-photo-container img:hover { transform: scale(1.02); cursor: zoom-in; }

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

/* Sticky Header */
.sticky-header th {
    position: sticky; top: 0; z-index: 5; background-color: #f8f9fa;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
</style>
@endpush