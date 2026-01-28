{{-- resources/views/hr/employees/modals/quick-add-skillset.blade.php --}}
<div class="modal fade" id="quickAddSkillsetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="quickAddSkillsetForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle text-primary"></i> Add New Skillset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="skillset-alert" class="alert alert-dismissible fade" style="display: none;"></div>
                    
                    <div class="mb-3">
                        <label class="form-label">Skillset Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="skillset-name" class="form-control"
                               placeholder="e.g., Sewing, Airbrushing" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="skillset-category" class="form-select">
                            <option value="">Select Category</option>
                            @foreach ($skillCategories as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Minimum Proficiency Required <span class="text-danger">*</span></label>
                        <select name="proficiency_required" id="skillset-proficiency" class="form-select" required>
                            <option value="basic" selected>Basic</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="skillset-description" class="form-control" rows="3"
                                  placeholder="Brief description of this skill..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-skillset">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="skillset-spinner"></span>
                        <i class="bi bi-check-circle"></i> Add Skillset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('form-scripts')
<script>
    $(document).ready(function() {
        // Open modal when clicking "Add New Skillset"
        $(document).on('click', '#btn-add-skillset', function(e) {
            e.preventDefault();
            $('#quickAddSkillsetModal').modal('show');
        });
        
        // Handle form submission
        $('#quickAddSkillsetForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = $('#btn-save-skillset');
            const spinner = $('#skillset-spinner');
            const alert = $('#skillset-alert');
            
            // Show loading
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            alert.hide().removeClass('alert-success alert-danger');
            
            $.ajax({
                url: '{{ route("skillsets.store") }}',
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        // Add to select2
                        const newOption = new Option(
                            response.skillset.name,
                            response.skillset.id,
                            false,
                            true
                        );
                        
                        $(newOption).attr('data-category', response.skillset.category);
                        $(newOption).attr('data-proficiency', response.skillset.proficiency_required);
                        
                        $('#skillsets-select').append(newOption).trigger('change');
                        
                        // Show success
                        alert.html('<i class="bi bi-check-circle"></i> Skillset added successfully!')
                             .addClass('alert-success')
                             .show();
                        
                        // Reset form
                        form[0].reset();
                        
                        // Close modal after delay
                        setTimeout(() => {
                            $('#quickAddSkillsetModal').modal('hide');
                            alert.hide();
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Failed to add skillset';
                    
                    if (xhr.responseJSON?.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON?.errors) {
                        errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    
                    alert.html(`<i class="bi bi-exclamation-triangle"></i> ${errorMsg}`)
                         .addClass('alert-danger')
                         .show();
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });
        
        // Reset modal on close
        $('#quickAddSkillsetModal').on('hidden.bs.modal', function() {
            $('#quickAddSkillsetForm')[0].reset();
            $('#skillset-alert').hide();
        });
    });
</script>
@endpush