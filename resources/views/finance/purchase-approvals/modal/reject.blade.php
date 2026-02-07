<!-- Reject Modal -->
<div class="modal fade" id="rejectModal{{ $purchase->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('purchase-approvals.reject', $purchase->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject purchase <strong>{{ $purchase->po_number }}</strong>?</p>
                    <p>Please provide reason for rejection:</p>
                    
                    <div class="mb-3">
                        <label for="reject_notes_{{ $purchase->id }}" class="form-label small text-dark">
                            Rejection Reason <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control border-1 rounded-2 py-2 px-3" 
                                  id="reject_notes_{{ $purchase->id }}" 
                                  name="finance_notes" 
                                  rows="3" 
                                  required
                                  placeholder="Please provide reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-2 px-3">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>