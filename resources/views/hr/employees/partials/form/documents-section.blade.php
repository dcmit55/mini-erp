{{-- resources/views/hr/employees/partials/form/documents-section.blade.php --}}
<div class="form-section mb-4">
    <div class="section-header">
        <h5 class="section-title">
            <i class="bi bi-file-earmark-plus me-2"></i>Add New Documents
        </h5>
        <p class="section-subtitle">Upload additional documents (optional)</p>
    </div>
    <div class="section-body">
        <div id="document-container">
            <div class="document-item mb-3">
                <button type="button" class="btn btn-outline-danger btn-sm remove-document">
                    <i class="bi bi-x-lg"></i>
                </button>
                
                <div class="row">
                    <!-- Document Type -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Document Type</label>
                        <select name="document_types[]" class="form-select">
                            <option value="">Select Type</option>
                            @foreach ($documentTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Document Name -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Document Name</label>
                        <input type="text" name="document_names[]" class="form-control"
                               placeholder="e.g., ID Card">
                    </div>
                    
                    <!-- File -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="documents[]" class="form-control document-file"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                               title="Drag & drop files here">
                    </div>
                    
                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="document_descriptions[]" class="form-control" rows="2" 
                                  placeholder="Brief description..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Document Button -->
        <button type="button" id="add-document" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Add Another Document
        </button>
        
        <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle"></i> Max 5MB per file. Supported: PDF, DOC, DOCX, JPG, PNG
        </small>
    </div>
</div>

@push('form-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add document functionality
        document.getElementById('add-document').addEventListener('click', function() {
            const container = document.getElementById('document-container');
            const template = container.querySelector('.document-item');
            const newItem = template.cloneNode(true);
            
            // Clear values
            newItem.querySelectorAll('input, select, textarea').forEach(input => {
                input.value = '';
                input.classList.remove('is-valid', 'is-invalid');
            });
            
            // Add to container
            container.appendChild(newItem);
            
            // Add event listeners to new document item
            addDocumentEventListeners(newItem);
        });
        
        // Remove document functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-document')) {
                const container = document.getElementById('document-container');
                const item = e.target.closest('.document-item');
                
                if (container.children.length > 1) {
                    item.remove();
                } else {
                    // Reset the only remaining item
                    item.querySelectorAll('input, select, textarea').forEach(input => {
                        input.value = '';
                        input.classList.remove('is-valid', 'is-invalid');
                    });
                }
            }
        });
        
        // Initialize event listeners for existing document items
        document.querySelectorAll('.document-item').forEach(item => {
            addDocumentEventListeners(item);
        });
        
        function addDocumentEventListeners(item) {
            const fileInput = item.querySelector('.document-file');
            const nameInput = item.querySelector('input[name="document_names[]"]');
            
            // File validation
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    validateDocumentFile(this);
                    updateDocumentItemStatus(item);
                });
            }
            
            // Name validation
            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    validateDocumentName(this);
                    updateDocumentItemStatus(item);
                });
            }
        }
        
        function validateDocumentFile(input) {
            const file = input.files[0];
            
            if (!file) {
                input.classList.remove('is-valid', 'is-invalid');
                return false;
            }
            
            // File type validation
            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/jpg'
            ];
            
            // File size validation (5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!allowedTypes.includes(file.type)) {
                showValidation(input, false, 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG');
                return false;
            }
            
            if (file.size > maxSize) {
                showValidation(input, false, 'File size too large. Maximum 5MB allowed.');
                return false;
            }
            
            showValidation(input, true, `File "${file.name}" is valid`);
            return true;
        }
        
        function validateDocumentName(input) {
            const value = input.value.trim();
            
            if (!value) {
                input.classList.remove('is-valid', 'is-invalid');
                return false;
            }
            
            if (value.length < 3) {
                showValidation(input, false, 'Document name must be at least 3 characters');
                return false;
            }
            
            if (value.length > 255) {
                showValidation(input, false, 'Document name must not exceed 255 characters');
                return false;
            }
            
            showValidation(input, true, 'Document name is valid');
            return true;
        }
        
        function showValidation(input, isValid, message) {
            input.classList.remove('is-valid', 'is-invalid');
            
            // Remove existing feedback
            const existingFeedback = input.parentNode.querySelector('.validation-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }
            
            if (isValid) {
                input.classList.add('is-valid');
            } else {
                input.classList.add('is-invalid');
            }
            
            // Add feedback message
            const feedback = document.createElement('div');
            feedback.className = `validation-feedback ${isValid ? 'text-success' : 'text-danger'}`;
            feedback.textContent = message;
            feedback.style.fontSize = '0.75rem';
            input.parentNode.appendChild(feedback);
        }
        
        function updateDocumentItemStatus(item) {
            const fileInput = item.querySelector('.document-file');
            const typeSelect = item.querySelector('select[name="document_types[]"]');
            const nameInput = item.querySelector('input[name="document_names[]"]');
            
            const hasValidFile = fileInput.classList.contains('is-valid');
            const hasType = typeSelect.value !== '';
            const hasValidName = nameInput.classList.contains('is-valid');
            
            item.classList.remove('complete', 'partial');
            
            if (hasType && hasValidName && hasValidFile) {
                item.classList.add('complete');
            } else if (hasType || hasValidName || hasValidFile) {
                item.classList.add('partial');
            }
        }
    });
</script>
@endpush