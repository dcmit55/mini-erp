<!-- Approve Modal -->
<div class="modal fade" id="approveModal{{ $purchase->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('purchase-approvals.approve', $purchase->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve purchase <strong>{{ $purchase->po_number }}</strong>?</p>
                    <p>This will create DCM costing entry.</p>
                    
                    <div class="mb-3">
                        <label for="finance_notes_{{ $purchase->id }}" class="form-label small text-dark">
                            Finance Notes (Optional)
                        </label>
                        <textarea class="form-control border-1 rounded-2 py-2 px-3" 
                                  id="finance_notes_{{ $purchase->id }}" 
                                  name="finance_notes" 
                                  rows="3"
                                  placeholder="Add any notes for finance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-3">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>