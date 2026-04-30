{{-- Patrol Details Modals --}}

{{-- Modal for Patrol List by Type --}}
<div class="modal fade" id="patrolTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-success text-white" style="border-radius: 12px 12px 0 0;">
                <h5 class="modal-title" id="patrolTypeModalTitle">🚶 Patrol Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="extra-small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <th class="ps-3 py-3" style="width: 50px;">#</th>
                                <th>Forest Guard</th>
                                <th>Location / Range</th>
                                <th class="text-center">Distance</th>
                                <th class="text-end pe-3">Started At</th>
                            </tr>
                        </thead>
                        <tbody id="patrolTypeListBody">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
