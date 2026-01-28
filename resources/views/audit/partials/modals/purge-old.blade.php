<div class="modal fade" id="purgeOldModal" tabindex="-1" aria-labelledby="purgeOldLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title" id="purgeOldLabel">Purge Old Audit Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="purgeOldForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Delete logs older than (days)</label>
                        <input type="number" class="form-control" id="purgeDays" name="days" min="1"
                            max="365" value="30" required>
                        <small class="text-muted">Enter number of days. Logs older than this will be deleted.</small>
                    </div>
                    <div class="alert alert-danger alert-audit-danger">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <strong>Warning:</strong> All audit logs older than the specified days will be permanently
                        deleted and cannot be recovered.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-audit-danger" id="confirmPurgeBtn">Purge</button>
            </div>
        </div>
    </div>
</div>