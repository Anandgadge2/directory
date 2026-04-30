{{-- Incident Details Modals --}}

{{-- Modal for Incident List by Type/Site --}}
<div class="modal fade" id="incidentTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" style="max-width: 850px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title fw-bold" id="incidentTypeModalTitle">📌 Incidents List</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="extra-small text-uppercase">
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th>Type</th>
                                <th>Forest Guard</th>
                                <th>Location</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-3">Date</th>
                            </tr>
                        </thead>
                        <tbody id="incidentTypeListBody">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal for Single Incident Detail --}}
<div class="modal fade" id="incidentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 650px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-exclamation me-2"></i>Incident Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="incidentDetailContent" class="modal-body bg-light-subtle p-0">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Fetching details...</p>
                </div>
            </div>
        </div>
    </div>
</div>
