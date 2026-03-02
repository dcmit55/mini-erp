<div class="card-header bg-transparent border-0 py-3">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-0">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-check gradient-icon me-2" style="font-size: 1.5rem;"></i>
            <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Audit Log</h2>
        </div>

        <div class="ms-lg-auto">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-lg-end">
                <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                    <i class="bi bi-trash3 me-1"></i> Bulk Delete
                </button>
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                    data-bs-target="#deleteByDateModal">
                    <i class="bi bi-calendar-x me-1"></i> Delete by Date
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#purgeOldModal">
                    <i class="bi bi-hourglass-split me-1"></i> Purge Old Logs
                </button>
            </div>
        </div>
    </div>
</div>