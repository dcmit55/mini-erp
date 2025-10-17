@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:1.3rem;">
                    <i class="bi bi-person-plus-fill me-2 gradient-icon"></i>Add New Employee
                </h2>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
                    @csrf

                    <!-- Employee Photo -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Employee Photo</h6>
                                    <div class="mb-3">
                                        <img id="photo-preview" src="{{ asset('images/default-avatar.png') }}"
                                            alt="Preview" class="rounded-circle border"
                                            style="width: 120px; height: 120px; object-fit: cover;">
                                    </div>
                                    <input type="file" class="form-control" id="photo" name="photo"
                                        accept="image/jpeg,image/png,image/jpg" onchange="previewPhoto(this)"
                                        title="Drag & drop photo here or click to browse">
                                    <small class="text-muted">Max 2MB. Supported: JPG, PNG, JPEG. Drag & drop
                                        supported.</small>
                                    @error('photo')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="row">
                        <div class="mb-3">
                            <label for="employee_no" class="form-label">Employee Number <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">DCM-</span>
                                <input type="text" class="form-control" id="employee_no" name="employee_no"
                                    value="{{ old('employee_no') }}" placeholder="0001" maxlength="10" required>
                            </div>
                            <small class="text-muted">Enter 4-digit number (e.g., 0001)</small>
                            @error('employee_no')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Employee Name <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ old('name') }}" required>
                            </div>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Employment Status <span
                                    class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                                <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated
                                </option>
                            </select>
                            @error('status')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Personal Information Section -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select name="gender" id="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                            @error('gender')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ktp_id" class="form-label">KTP ID Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" class="form-control" id="ktp_id" name="ktp_id"
                                    value="{{ old('ktp_id') }}" placeholder="1234567890123456" maxlength="20">
                            </div>
                            <small class="text-muted">Enter 16-digit KTP number</small>
                            @error('ktp_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="place_of_birth" class="form-label">Place of Birth</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" id="place_of_birth" name="place_of_birth"
                                    value="{{ old('place_of_birth') }}" placeholder="e.g., Jakarta">
                            </div>
                            @error('place_of_birth')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                value="{{ old('date_of_birth') }}" max="{{ date('Y-m-d') }}">
                            @error('date_of_birth')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select name="department_id" id="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                                <input type="text" class="form-control" id="position" name="position"
                                    value="{{ old('position') }}" required>
                            </div>
                            @error('position')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ old('email') }}" placeholder="employee@company.com">
                            </div>
                            @error('email')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="{{ old('phone') }}" placeholder="+62 xxx xxxx xxxx">
                            </div>
                            @error('phone')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" placeholder="Enter full address...">{{ old('address') }}</textarea>
                        @error('address')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Financial Information -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="rekening" class="form-label">Bank Account Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                <input type="text" class="form-control" id="rekening" name="rekening"
                                    value="{{ old('rekening') }}" placeholder="1234-5678-9012-3456">
                            </div>
                            @error('rekening')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="salary" class="form-label">Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="salary" name="salary"
                                    value="{{ old('salary') }}" placeholder="0" min="0">
                            </div>
                            @error('salary')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Employment Details -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="hire_date" class="form-label">Hire Date</label>
                            <input type="date" class="form-control" id="hire_date" name="hire_date"
                                value="{{ old('hire_date') }}">
                            @error('hire_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="saldo_cuti" class="form-label">Leave Balance (Days)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                <input type="number" class="form-control" id="saldo_cuti" name="saldo_cuti"
                                    value="{{ old('saldo_cuti', 12) }}" min="0" max="365">
                                <span class="input-group-text">days</span>
                            </div>
                            @error('saldo_cuti')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Documents Upload -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> Document Upload (Optional)</h6>
                            </div>
                            <div class="card-body">
                                <div id="document-container">
                                    <div class="document-item mb-3 border rounded position-relative"
                                        style="padding: 20px 50px 20px 20px;">
                                        <!-- Delete button positioned at top-right -->
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm remove-document position-absolute"
                                            style="top: 10px; right: 10px; z-index: 10;">
                                            <i class="bi bi-x-lg"></i>
                                        </button>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Document Type</label>
                                                <select name="document_types[]" class="form-select">
                                                    <option value="">Select Type</option>
                                                    @foreach ($documentTypes as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Document Name</label>
                                                <input type="text" name="document_names[]" class="form-control"
                                                    placeholder="e.g., ID Card">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">File</label>
                                                <input type="file" name="documents[]" class="form-control"
                                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                    title="Drag & drop files here or click to browse">
                                                <div class="upload-progress">
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: 0%">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <label class="form-label">Description (Optional)</label>
                                                <textarea name="document_descriptions[]" class="form-control" rows="2" placeholder="Brief description..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="add-document" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus"></i> Add Another Document
                                </button>
                                <small class="text-muted d-block mt-2">Max 5MB per file. Supported: PDF, DOC, DOCX, JPG,
                                    PNG</small>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                            placeholder="Additional notes about the employee...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="employee-submit-btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                aria-hidden="true"></span>
                            <i class="bi bi-save"></i> Save Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <style>
        /* Document item styling */
        .document-item {
            background-color: #f8f9fa;
            transition: all 0.2s ease;
            border: 2px dashed #dee2e6 !important;
        }

        .document-item:hover {
            border-color: #6c757d !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Remove button styling */
        .document-item .remove-document {
            opacity: 0.7;
            transition: all 0.2s ease;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .document-item:hover .remove-document {
            opacity: 1;
        }

        .document-item .remove-document:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            transform: scale(1.1);
        }

        /* Add document button */
        #add-document {
            border: 1px dashed #6c757d;
            background: transparent;
            padding: 5px 10px;
            transition: all 0.2s ease;
        }

        #add-document:hover {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
            transform: translateY(-1px);
        }

        /* Form spacing improvements */
        .document-item .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .document-item .form-control,
        .document-item .form-select {
            border-radius: 6px;
        }

        /* Validation feedback styling */
        .invalid-feedback {
            display: block !important;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #dc3545;
        }

        .valid-feedback {
            display: block !important;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #198754;
        }

        /* Input validation states */
        .form-control.is-valid {
            border-color: #198754;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23198754' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 8l3 3 5-5'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23dc3545' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 4l8 8M12 4l-8 8'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-select.is-valid {
            border-color: #198754;
        }

        .form-select.is-invalid {
            border-color: #dc3545;
        }

        /* Document item progress indicators */
        .document-item {
            position: relative;
        }

        .document-item::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #dee2e6;
            transition: all 0.3s ease;
            z-index: 5;
        }

        .document-item.complete::before {
            background-color: #198754;
        }

        .document-item.partial::before {
            background-color: #ffc107;
        }

        /* Animation for validation feedback */
        .validation-feedback {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Drag and drop styling */
        .form-control.drag-over {
            border-color: #0d6efd !important;
            background-color: #f0f8ff !important;
            border-style: solid !important;
        }

        /* File upload progress */
        .upload-progress {
            margin-top: 0.5rem;
            display: none;
        }

        .upload-progress.show {
            display: block;
        }

        .progress {
            height: 6px;
            border-radius: 3px;
        }

        /* Photo upload area enhancement */
        #photo {
            transition: all 0.3s ease;
        }

        #photo:hover {
            border-color: #0d6efd;
        }

        .photo-upload-area {
            position: relative;
            cursor: pointer;
        }

        .photo-upload-area:hover .card-body {
            background-color: #f0f8ff;
        }
    </style>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Photo preview function dengan validation
            window.previewPhoto = function(input) {
                const file = input.files[0];
                const preview = document.getElementById('photo-preview');
                const feedback = input.parentNode.querySelector('.validation-feedback');

                // Remove existing feedback
                if (feedback) feedback.remove();

                if (file) {
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    if (!allowedTypes.includes(file.type)) {
                        showValidationError(input, 'Invalid file type. Only JPG, PNG, JPEG are allowed.');
                        return;
                    }

                    // Validate file size (2MB = 2097152 bytes)
                    if (file.size > 2097152) {
                        showValidationError(input, 'File size too large. Maximum 2MB allowed.');
                        return;
                    }

                    // Preview image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        showValidationSuccess(input, 'Photo uploaded successfully!');
                    };
                    reader.readAsDataURL(file);
                }
            };

            // Document file validation
            function validateDocumentFile(input) {
                const file = input.files[0];
                const feedback = input.parentNode.querySelector('.validation-feedback');

                // Remove existing feedback
                if (feedback) feedback.remove();

                if (file) {
                    // Validate file type
                    const allowedTypes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg',
                        'image/png',
                        'image/jpg'
                    ];

                    if (!allowedTypes.includes(file.type)) {
                        showValidationError(input, 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG allowed.');
                        return false;
                    }

                    // Validate file size (5MB = 5242880 bytes)
                    if (file.size > 5242880) {
                        showValidationError(input, 'File size too large. Maximum 5MB allowed.');
                        return false;
                    }

                    showValidationSuccess(input, `File "${file.name}" is valid (${formatFileSize(file.size)})`);
                    return true;
                }
                return false;
            }

            // Format file size
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Show validation error
            function showValidationError(input, message) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback validation-feedback';
                feedback.textContent = message;
                input.parentNode.appendChild(feedback);
            }

            // Show validation success
            function showValidationSuccess(input, message) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');

                const feedback = document.createElement('div');
                feedback.className = 'valid-feedback validation-feedback';
                feedback.textContent = message;
                input.parentNode.appendChild(feedback);
            }

            // Document name validation
            function validateDocumentName(input) {
                const value = input.value.trim();
                const feedback = input.parentNode.querySelector('.validation-feedback');

                // Remove existing feedback
                if (feedback) feedback.remove();

                if (value) {
                    if (value.length < 3) {
                        showValidationError(input, 'Document name must be at least 3 characters.');
                    } else if (value.length > 255) {
                        showValidationError(input, 'Document name must not exceed 255 characters.');
                    } else {
                        showValidationSuccess(input, 'Document name is valid.');
                    }
                } else {
                    input.classList.remove('is-valid', 'is-invalid');
                }
            }

            // Add event listeners for document validation
            function addDocumentValidationListeners(container) {
                // File input validation
                const fileInputs = container.querySelectorAll('input[name="documents[]"]');
                fileInputs.forEach(input => {
                    input.addEventListener('change', function() {
                        validateDocumentFile(this);
                        updateDocumentItemStatus(this.closest('.document-item'));
                    });
                });

                // Document name validation
                const nameInputs = container.querySelectorAll('input[name="document_names[]"]');
                nameInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        validateDocumentName(this);
                        updateDocumentItemStatus(this.closest('.document-item'));
                    });
                });
            }

            // Update document item status
            function updateDocumentItemStatus(documentItem) {
                const fileInput = documentItem.querySelector('input[name="documents[]"]');
                const typeSelect = documentItem.querySelector('select[name="document_types[]"]');
                const nameInput = documentItem.querySelector('input[name="document_names[]"]');

                const hasFile = fileInput.files.length > 0 && fileInput.classList.contains('is-valid');
                const hasType = typeSelect.value !== '';
                const hasName = nameInput.value.trim() !== '' && !nameInput.classList.contains('is-invalid');

                // Update document item status
                documentItem.classList.remove('complete', 'partial');

                if (hasType && hasName && hasFile) {
                    documentItem.classList.add('complete');
                } else if (hasType || hasName || hasFile) {
                    documentItem.classList.add('partial');
                }
            }

            // Initialize validation for existing document items
            addDocumentValidationListeners(document.getElementById('document-container'));

            // Add drag and drop functionality for file inputs
            function addDragDropListeners(fileInput) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    fileInput.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                ['dragenter', 'dragover'].forEach(eventName => {
                    fileInput.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    fileInput.addEventListener(eventName, unhighlight, false);
                });

                function highlight(e) {
                    fileInput.classList.add('drag-over');
                }

                function unhighlight(e) {
                    fileInput.classList.remove('drag-over');
                }

                fileInput.addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;

                    if (files.length > 0) {
                        fileInput.files = files;
                        // Trigger change event for validation
                        const event = new Event('change', {
                            bubbles: true
                        });
                        fileInput.dispatchEvent(event);
                    }
                }
            }

            // Add drag & drop to photo input
            addDragDropListeners(document.getElementById('photo'));

            // Add drag & drop to existing document file inputs
            document.querySelectorAll('input[name="documents[]"]').forEach(input => {
                addDragDropListeners(input);
            });

            // File size formatter helper
            window.formatFileSize = formatFileSize;

            // Add document functionality with validation
            document.getElementById('add-document').addEventListener('click', function() {
                const container = document.getElementById('document-container');
                const newItem = container.querySelector('.document-item').cloneNode(true);

                // Clear values and validation classes
                newItem.querySelectorAll('input, select, textarea').forEach(input => {
                    input.value = '';
                    input.classList.remove('is-valid', 'is-invalid');
                });

                // Remove validation feedback
                newItem.querySelectorAll('.validation-feedback').forEach(feedback => {
                    feedback.remove();
                });

                // Reset document item styling
                newItem.classList.remove('complete', 'partial');
                newItem.style.borderColor = '#dee2e6';
                newItem.style.backgroundColor = '#f8f9fa';

                container.appendChild(newItem);

                // Add validation listeners to new item
                addDocumentValidationListeners(newItem);

                // Add drag & drop to new file input
                const newFileInput = newItem.querySelector('input[name="documents[]"]');
                addDragDropListeners(newFileInput);
            });

            // Remove document functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-document') || e.target.closest(
                        '.remove-document')) {
                    const container = document.getElementById('document-container');
                    if (container.children.length > 1) {
                        e.target.closest('.document-item').remove();
                    } else {
                        // Clear form if only one item remains
                        const item = e.target.closest('.document-item');
                        item.querySelectorAll('input, select, textarea').forEach(input => {
                            input.value = '';
                            input.classList.remove('is-valid', 'is-invalid');
                        });
                        item.querySelectorAll('.validation-feedback').forEach(feedback => {
                            feedback.remove();
                        });
                        // Reset styling
                        item.classList.remove('complete', 'partial');
                        item.style.borderColor = '#dee2e6';
                        item.style.backgroundColor = '#f8f9fa';
                    }
                }
            });

            // Format salary input
            const salaryInput = document.getElementById('salary');
            if (salaryInput) {
                salaryInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }

            // Format phone number input
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    let value = this.value.replace(/[^0-9+]/g, '');
                    this.value = value;
                });
            }

            // Format rekening input
            const rekeningInput = document.getElementById('rekening');
            if (rekeningInput) {
                rekeningInput.addEventListener('input', function() {
                    let value = this.value.replace(/[^0-9-]/g, '');
                    this.value = value;
                });
            }

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Real-time form validation summary
            function updateFormValidationSummary() {
                const submitBtn = document.getElementById('employee-submit-btn');
                const photoInput = document.getElementById('photo');
                const requiredFields = document.querySelectorAll('input[required], select[required]');

                let hasInvalidFiles = false;

                // Check photo validation
                if (photoInput.files.length > 0 && photoInput.classList.contains('is-invalid')) {
                    hasInvalidFiles = true;
                }

                // Check document validations
                const documentFiles = document.querySelectorAll('input[name="documents[]"]');
                documentFiles.forEach(fileInput => {
                    if (fileInput.files.length > 0 && fileInput.classList.contains('is-invalid')) {
                        hasInvalidFiles = true;
                    }
                });

                // Update submit button state based on file validation
                if (hasInvalidFiles) {
                    submitBtn.classList.remove('btn-primary');
                    submitBtn.classList.add('btn-secondary');
                    submitBtn.title = 'Please fix file validation errors before submitting';
                } else {
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('btn-primary');
                    submitBtn.title = '';
                }
            }

            // Add real-time validation to photo input
            document.getElementById('photo').addEventListener('change', function() {
                updateFormValidationSummary();
            });

            // Add real-time validation to all form inputs
            const allInputs = document.querySelectorAll('input, select, textarea');
            allInputs.forEach(input => {
                input.addEventListener('input', updateFormValidationSummary);
                input.addEventListener('change', updateFormValidationSummary);
            });

            // Initial validation summary
            updateFormValidationSummary();

            // Prevent multiple submit on create employee form
            const form = document.querySelector('form[action="{{ route('employees.store') }}"]');
            const submitBtn = document.getElementById('employee-submit-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                });
            }

            // Update bagian JavaScript, hapus generate employee number functionality

            // Format input as user types
            document.getElementById('employee_no').addEventListener('input', function() {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');

                // Limit to 4 digits
                if (this.value.length > 4) {
                    this.value = this.value.substring(0, 4);
                }
            });

            // Real-time validation
            document.getElementById('employee_no').addEventListener('blur', function() {
                const value = this.value;
                if (value) {
                    fetch('/employees/check-employee-no', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                employee_no: value,
                                @if (isset($employee))
                                    employee_id: {{ $employee->id }}
                                @endif
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            const input = document.getElementById('employee_no');
                            const feedback = input.parentNode.parentNode.querySelector(
                                '.validation-feedback');

                            // Remove existing feedback
                            if (feedback) {
                                feedback.remove();
                            }

                            if (!data.available) {
                                input.classList.add('is-invalid');
                                const feedbackDiv = document.createElement('div');
                                feedbackDiv.className = 'text-danger small validation-feedback';
                                feedbackDiv.textContent =
                                    `Employee number DCM-${value.padStart(4, '0')} already exists.`;
                                input.parentNode.parentNode.appendChild(feedbackDiv);
                            } else {
                                input.classList.remove('is-invalid');
                                input.classList.add('is-valid');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });
            
            // Format KTP ID input
            const ktpInput = document.getElementById('ktp_id');
            if (ktpInput) {
                ktpInput.addEventListener('input', function() {
                    // Only allow numbers
                    this.value = this.value.replace(/[^0-9]/g, '');

                    // Limit to 16 digits
                    if (this.value.length > 16) {
                        this.value = this.value.substring(0, 16);
                    }
                });

                // Real-time validation
                ktpInput.addEventListener('blur', function() {
                    const value = this.value;
                    const feedback = this.parentNode.parentNode.querySelector('.validation-feedback');

                    // Remove existing feedback
                    if (feedback) feedback.remove();

                    if (value) {
                        if (value.length !== 16) {
                            showValidationError(this, 'KTP ID must be exactly 16 digits');
                        } else {
                            // Check uniqueness via AJAX
                            fetch('/employees/check-ktp', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute('content'),
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        ktp_id: value,
                                        @if (isset($employee))
                                            employee_id: {{ $employee->id }}
                                        @endif
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data.available) {
                                        showValidationError(this, 'KTP ID already exists');
                                    } else {
                                        this.classList.remove('is-invalid');
                                        this.classList.add('is-valid');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                });
                        }
                    }
                });
            }

            // Date of birth age calculator
            const dobInput = document.getElementById('date_of_birth');
            if (dobInput) {
                dobInput.addEventListener('change', function() {
                    const dob = new Date(this.value);
                    const today = new Date();
                    const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));

                    const feedback = this.parentNode.querySelector('.validation-feedback');
                    if (feedback) feedback.remove();

                    if (age < 17) {
                        showValidationError(this, 'Employee must be at least 17 years old');
                    } else if (age > 100) {
                        showValidationError(this, 'Please enter a valid date of birth');
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');

                        const ageDisplay = document.createElement('small');
                        ageDisplay.className = 'text-success d-block mt-1 validation-feedback';
                        ageDisplay.textContent = `Age: ${age} years old`;
                        this.parentNode.appendChild(ageDisplay);
                    }
                });
            }
        });
    </script>
@endpush
