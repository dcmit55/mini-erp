<div class="modal fade" id="lateModal" tabindex="-1" aria-labelledby="lateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="lateForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lateModalLabel">Set Late Time</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="late-employee-id" name="employee_id">
                    <input type="hidden" id="late-date" name="date" value="{{ $date }}">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Arrival Time (Late)</label>
                        <input type="time" class="form-control" id="late-time" name="late_time" required>
                        <small class="text-muted">Enter the actual arrival time of the employee</small>
                        <div class="invalid-feedback">Please select a valid time.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>