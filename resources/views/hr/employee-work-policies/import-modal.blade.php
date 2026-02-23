<!-- Modal Import Work Policies -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('employee-work-policies.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Work Policies</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">Choose Excel File</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <strong>File Format:</strong> Required columns: employee_no, name, weekday_start, weekday_end, saturday_start, saturday_end, sunday_start, sunday_end.
                            <br>Please ensure the file uses a header row in the first line.                        </small>
                    </div>
                    <div id="importProgress" class="progress d-none">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%">Processing...</div>
                    </div>
                    <div id="importResult" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="importBtn">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var $btn = $('#importBtn');
        var $progress = $('#importProgress');
        var $result = $('#importResult');

        $btn.prop('disabled', true);
        $progress.removeClass('d-none');
        $result.html('');

        $.ajax({
            url: "{{ route('employee-work-policies.import') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $progress.addClass('d-none');
                $result.html('<div class="alert alert-success">' + response.message + '</div>');
                setTimeout(() => {
                    $('#importModal').modal('hide');
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                $progress.addClass('d-none');
                var errorMsg = xhr.responseJSON?.message || 'Import failed.';
                $result.html('<div class="alert alert-danger">' + errorMsg + '</div>');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush