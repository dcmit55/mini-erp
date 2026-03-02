<div class="modal fade" id="bulk-late-modal" tabindex="-1" aria-labelledby="bulkLateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="bulkLateModalLabel">
                    <i class="bi bi-clock"></i> Mark Employees as Late - Enter Individual Times
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulk-late-form">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        <strong><span id="bulk-late-employee-count">0</span> employee(s)</strong> will be marked as
                        late.
                        <br>
                        <small>Enter the time each employee arrived for their status to be recorded.</small>
                    </div>

                    <!-- Dynamic employee late time inputs -->
                    <div id="bulk-late-employee-inputs" class="row g-3">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle"></i> Mark All as Late
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>