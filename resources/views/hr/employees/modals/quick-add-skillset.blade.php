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
                    <!-- Alert Container -->
                    <div id="skillset-alert" class="alert alert-dismissible fade" style="display: none;" role="alert">
                        <div id="skillset-alert-message"></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    
                    <!-- Skillset Name -->
                    <div class="mb-3">
                        <label class="form-label">Skillset Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="skillset-name" class="form-control"
                               placeholder="e.g., Sewing, Airbrushing" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    
                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="skillset-category" class="form-select">
                            <option value="">Select Category</option>
                            @isset($skillCategories)
                                @foreach ($skillCategories as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            @else
                                <option value="technical">Technical</option>
                                <option value="soft">Soft Skills</option>
                                <option value="production">Production</option>
                                <option value="administrative">Administrative</option>
                            @endisset
                        </select>
                    </div>
                    
                    <!-- Proficiency Required -->
                    <div class="mb-3">
                        <label class="form-label">Minimum Proficiency Required <span class="text-danger">*</span></label>
                        <select name="proficiency_required" id="skillset-proficiency" class="form-select" required>
                            <option value="basic" selected>Basic</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    
                    <!-- Description -->
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
    document.addEventListener('DOMContentLoaded', function() {
        // Open modal when clicking "Add New Skillset"
        document.addEventListener('click', function(e) {
            if (e.target.closest('#btn-add-skillset')) {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('quickAddSkillsetModal'));
                modal.show();
            }
        });
        
        // Handle form submission
        const skillsetForm = document.getElementById('quickAddSkillsetForm');
        if (skillsetForm) {
            skillsetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('btn-save-skillset');
                const spinner = document.getElementById('skillset-spinner');
                const alert = document.getElementById('skillset-alert');
                const alertMessage = document.getElementById('skillset-alert-message');
                
                // Show loading
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                alert.style.display = 'none';
                alert.className = 'alert alert-dismissible fade';
                
                // Get form data
                const formData = new FormData(this);
                
                // Send AJAX request
                fetch('{{ route("skillsets.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add new option to skillset select
                        const skillsetSelect = document.getElementById('skillsets-select');
                        if (skillsetSelect) {
                            const newOption = document.createElement('option');
                            newOption.value = data.skillset.id;
                            newOption.textContent = data.skillset.name;
                            newOption.setAttribute('data-category', data.skillset.category || '');
                            newOption.setAttribute('data-proficiency', data.skillset.proficiency_required || 'basic');
                            newOption.selected = true;
                            skillsetSelect.appendChild(newOption);
                            
                            // Trigger change event to update skillset details
                            if (typeof $ !== 'undefined' && $().select2) {
                                $(skillsetSelect).trigger('change');
                            } else {
                                // Fallback for non-select2
                                const event = new Event('change');
                                skillsetSelect.dispatchEvent(event);
                            }
                        }
                        
                        // Show success message
                        alertMessage.innerHTML = `<i class="bi bi-check-circle"></i> ${data.message || 'Skillset added successfully!'}`;
                        alert.classList.add('alert-success');
                        alert.style.display = 'block';
                        
                        // Reset form
                        this.reset();
                        
                        // Close modal after delay
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddSkillsetModal'));
                            modal.hide();
                            alert.style.display = 'none';
                        }, 1500);
                    } else {
                        throw new Error(data.message || 'Failed to add skillset');
                    }
                })
                .catch(error => {
                    alertMessage.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ${error.message}`;
                    alert.classList.add('alert-danger');
                    alert.style.display = 'block';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                });
            });
        }
        
        // Reset modal on close
        const quickAddModal = document.getElementById('quickAddSkillsetModal');
        if (quickAddModal) {
            quickAddModal.addEventListener('hidden.bs.modal', function() {
                const form = document.getElementById('quickAddSkillsetForm');
                if (form) form.reset();
                
                const alert = document.getElementById('skillset-alert');
                if (alert) {
                    alert.style.display = 'none';
                    alert.className = 'alert alert-dismissible fade';
                }
                
                // Clear validation errors
                document.querySelectorAll('#quickAddSkillsetForm .is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
                document.querySelectorAll('#quickAddSkillsetForm .invalid-feedback').forEach(el => {
                    el.textContent = '';
                });
            });
        }
    });
</script>
@endpush