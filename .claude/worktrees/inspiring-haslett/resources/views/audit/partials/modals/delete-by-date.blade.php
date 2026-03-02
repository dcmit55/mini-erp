<div class="modal fade" id="deleteByDateModal" tabindex="-1" aria-labelledby="deleteByDateLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="deleteByDateLabel">Delete Audit Logs by Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="deleteByDateForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="deleteDateFrom" name="date_from" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="deleteDateTo" name="date_to" required>
                    </div>
                    <div class="alert alert-warning alert-audit-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        All audit logs within the specified date range will be permanently deleted and cannot be
                        recovered.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-audit-danger" id="confirmDeleteByDateBtn">Delete</button>
            </div>
        </div>
    </div>
</div>