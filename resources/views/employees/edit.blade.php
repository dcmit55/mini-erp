@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-header">
                <h2 class="mb-0" style="font-size:1.3rem;">
                    <i class="bi bi-person-lines-fill me-2 gradient-icon"></i>Edit Employee: {{ $employee->name }}
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

                <form method="POST" action="{{ route('employees.update', $employee->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Employee Photo -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Employee Photo</h6>
                                    <div class="mb-3">
                                        <img id="photo-preview" src="{{ $employee->photo_url }}" alt="Preview"
                                            class="rounded-circle border"
                                            style="width: 120px; height: 120px; object-fit: cover;">
                                    </div>
                                    <input type="file" class="form-control" id="photo" name="photo"
                                        accept="image/jpeg,image/png,image/jpg" onchange="previewPhoto(this)"
                                        title="Drag & drop photo here or click to browse (Max 2MB)">
                                    <!-- File feedback will be inserted here dynamically -->
                                    <small class="text-muted">Max 2MB. Supported: JPG, PNG, JPEG. Leave empty to keep
                                        current photo. Drag & drop supported.</small>
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
                                    value="{{ old('employee_no', $employee->employee_number_only) }}" placeholder="0001"
                                    maxlength="10" required>
                            </div>
                            <small class="text-muted">Enter 4-digit number (e.g., 0001)</small>
                            @error('employee_no')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="name" class="form-label">Employee Name <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ old('name', $employee->name) }}" required>
                            </div>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="employment_type" class="form-label">Employment Type <span
                                    class="text-danger">*</span></label>
                            <select name="employment_type" id="employment_type" class="form-select" required>
                                <option value="">Select Employment Type</option>
                                @foreach ($employmentTypes as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('employment_type', $employee->employment_type) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employment_type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Employment Status <span
                                    class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="active"
                                    {{ old('status', $employee->status ?? 'active') == 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive"
                                    {{ old('status', $employee->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                                <option value="terminated"
                                    {{ old('status', $employee->status ?? '') == 'terminated' ? 'selected' : '' }}>
                                    Terminated</option>
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
                                <option value="male" {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>
                                    Male</option>
                                <option value="female"
                                    {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Female</option>
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
                                    value="{{ old('ktp_id', $employee->ktp_id) }}" placeholder="1234567890123456"
                                    maxlength="20">
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
                                    value="{{ old('place_of_birth', $employee->place_of_birth) }}"
                                    placeholder="e.g., Jakarta">
                            </div>
                            @error('place_of_birth')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                value="{{ old('date_of_birth', $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : '') }}"
                                max="{{ date('Y-m-d') }}">
                            @error('date_of_birth')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select name="department_id" id="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id', $employee->department_id ?? '') == $dept->id ? 'selected' : '' }}>
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
                                    value="{{ old('position', $employee->position) }}" required>
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
                                    value="{{ old('email', $employee->email ?? '') }}"
                                    placeholder="employee@company.com">
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
                                    value="{{ old('phone', $employee->phone ?? '') }}" placeholder="+62 xxx xxxx xxxx">
                            </div>
                            @error('phone')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" placeholder="Enter full address...">{{ old('address', $employee->address) }}</textarea>
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
                                    value="{{ old('rekening', $employee->rekening ?? '') }}"
                                    placeholder="1234-5678-9012-3456">
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
                                    value="{{ old('salary', $employee->salary ?? '') }}" placeholder="0" min="0">
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
                                value="{{ old('hire_date', $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '') }}">
                            @error('hire_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="saldo_cuti" class="form-label">Leave Balance (Days)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                <input type="number" class="form-control" id="saldo_cuti" name="saldo_cuti"
                                    value="{{ old('saldo_cuti', $employee->saldo_cuti ?? 12) }}" min="0"
                                    max="365">
                                <span class="input-group-text">days</span>
                            </div>
                            @error('saldo_cuti')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Existing Documents -->
                    @if ($employee->documents->count() > 0)
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> Existing Documents</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Name</th>
                                                    <th>Size</th>
                                                    <th>Uploaded</th>
                                                    <th width="120">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($employee->documents as $document)
                                                    <tr id="document-row-{{ $document->id }}">
                                                        <td>
                                                            <span class="badge bg-info">
                                                                {{ strtoupper(str_replace('_', ' ', $document->document_type)) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div>{{ $document->document_name }}</div>
                                                            @if ($document->description)
                                                                <small
                                                                    class="text-muted">{{ $document->description }}</small>
                                                            @endif
                                                        </td>
                                                        <td>{{ $document->formatted_file_size }}</td>
                                                        <td>{{ $document->created_at->format('d M Y') }}</td>
                                                        <td class="text-nowrap">
                                                            <a href="{{ $document->file_url }}" target="_blank"
                                                                class="btn btn-outline-primary btn-sm"
                                                                title="View Document">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <button type="button"
                                                                class="btn btn-outline-danger btn-sm delete-document-btn"
                                                                data-document-id="{{ $document->id }}"
                                                                data-document-name="{{ $document->document_name }}"
                                                                title="Delete Document">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Add New Documents -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Documents (Optional)</h6>
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
                                                    placeholder="e.g., Updated ID Card">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">File</label>
                                                <input type="file" name="documents[]" class="form-control"
                                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                    title="Drag & drop files here or click to browse (Max 5MB)">
                                                <!-- File feedback will be inserted here dynamically -->
                                                <div class="upload-progress">
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: 0%">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">Uploading...</small>
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
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle"></i> Max 5MB per file. Supported formats: PDF, DOC, DOCX,
                                    JPG, PNG.
                                    Drag & drop files directly into the file input boxes for faster uploads.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                            placeholder="Additional notes about the employee...">{{ old('notes', $employee->notes ?? '') }}</textarea>
                        @error('notes')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="employee-update-btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                aria-hidden="true"></span>
                            <i class="bi bi-save"></i> Update Employee
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
            border-style: dashed !important;
            border-width: 2px !important;
        }

        /* Photo upload area enhancement */
        #photo {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        #photo:hover {
            border-color: #0d6efd;
        }

        /* File input validation states */
        input[type="file"].is-valid {
            border-color: #198754;
            background-color: #f8fff9;
        }

        input[type="file"].is-invalid {
            border-color: #dc3545;
            background-color: #fff5f5;
        }

        /* File feedback styling */
        .file-feedback {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .file-feedback i {
            margin-right: 0.25rem;
        }

        /* Upload progress styling */
        .upload-progress {
            margin-top: 0.5rem;
            display: none;
        }

        .upload-progress.show {
            display: block;
        }

        .upload-progress .progress {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .upload-progress .progress-bar {
            background-color: #0d6efd;
            transition: width 0.3s ease;
        }

        /* Document type selection styling */
        select[name="document_types[]"].is-valid {
            border-color: #198754;
        }

        select[name="document_types[]"].is-invalid {
            border-color: #dc3545;
        }

        /* Enhanced drag & drop visual feedback */
        input[type="file"]:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Loading animation for delete button */
        .delete-document-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Smooth row deletion animation */
        .table tbody tr {
            transition: all 0.3s ease;
        }

        /* Document table hover effects */
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Button hover effects */
        .btn-outline-danger:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }

        .btn-outline-primary:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }
    </style>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Photo preview and validation function
            window.previewPhoto = function(input) {
                const file = input.files[0];
                const feedbackElement = input.parentElement.querySelector('.file-feedback') ||
                    createFeedbackElement(input);

                if (!file) {
                    clearValidation(input);
                    return;
                }

                // Validate photo
                const validation = validatePhoto(file);
                if (!validation.valid) {
                    showValidationError(input, validation.message, feedbackElement);
                    input.value = '';
                    return;
                }

                // Show preview if valid
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photo-preview').src = e.target.result;
                    showValidationSuccess(input, 'Photo looks good!', feedbackElement);
                };
                reader.readAsDataURL(file);
            };

            // Photo validation function
            function validatePhoto(file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    return {
                        valid: false,
                        message: 'Please select a valid image file (JPG, PNG, JPEG)'
                    };
                }

                if (file.size > maxSize) {
                    return {
                        valid: false,
                        message: 'Photo size must be less than 2MB'
                    };
                }

                return {
                    valid: true
                };
            }

            // Document validation function
            function validateDocument(file) {
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png',
                    'image/jpg'
                ];
                const maxSize = 5 * 1024 * 1024; // 5MB

                if (!allowedTypes.includes(file.type)) {
                    return {
                        valid: false,
                        message: 'Please select a valid file type (PDF, DOC, DOCX, JPG, PNG)'
                    };
                }

                if (file.size > maxSize) {
                    return {
                        valid: false,
                        message: 'File size must be less than 5MB'
                    };
                }

                return {
                    valid: true
                };
            }

            // Create feedback element
            function createFeedbackElement(input) {
                const feedback = document.createElement('div');
                feedback.className = 'file-feedback small mt-1';
                input.parentElement.appendChild(feedback);
                return feedback;
            }

            // Show validation error
            function showValidationError(input, message, feedbackElement) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
                feedbackElement.className = 'file-feedback small mt-1 text-danger';
                feedbackElement.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${message}`;
            }

            // Show validation success
            function showValidationSuccess(input, message, feedbackElement) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                feedbackElement.className = 'file-feedback small mt-1 text-success';
                feedbackElement.innerHTML = `<i class="bi bi-check-circle"></i> ${message}`;
            }

            // Clear validation
            function clearValidation(input) {
                input.classList.remove('is-valid', 'is-invalid');
                const feedback = input.parentElement.querySelector('.file-feedback');
                if (feedback) {
                    feedback.remove();
                }
            }

            // Add real-time validation for document uploads
            document.addEventListener('change', function(e) {
                if (e.target.type === 'file' && e.target.name === 'documents[]') {
                    const file = e.target.files[0];
                    const feedbackElement = e.target.parentElement.querySelector('.file-feedback') ||
                        createFeedbackElement(e.target);

                    if (!file) {
                        clearValidation(e.target);
                        updateDocumentItemStatus(e.target.closest('.document-item'));
                        return;
                    }

                    const validation = validateDocument(file);
                    if (!validation.valid) {
                        showValidationError(e.target, validation.message, feedbackElement);
                        e.target.value = '';
                    } else {
                        const sizeText = (file.size / 1024 / 1024).toFixed(2);
                        showValidationSuccess(e.target, `${file.name} (${sizeText}MB) ready to upload`,
                            feedbackElement);
                    }

                    updateDocumentItemStatus(e.target.closest('.document-item'));
                }
            });

            // Update document item completion status
            function updateDocumentItemStatus(documentItem) {
                const typeSelect = documentItem.querySelector('select[name="document_types[]"]');
                const nameInput = documentItem.querySelector('input[name="document_names[]"]');
                const fileInput = documentItem.querySelector('input[name="documents[]"]');

                const hasType = typeSelect.value !== '';
                const hasName = nameInput.value.trim() !== '';
                const hasFile = fileInput.files.length > 0 && fileInput.classList.contains('is-valid');

                documentItem.classList.remove('complete', 'partial');

                if (hasType && hasName && hasFile) {
                    documentItem.classList.add('complete');
                } else if (hasType || hasName || hasFile) {
                    documentItem.classList.add('partial');
                }
            }

            // Add event listeners for document item inputs to update status
            document.addEventListener('input', function(e) {
                if (e.target.matches('select[name="document_types[]"], input[name="document_names[]"]')) {
                    updateDocumentItemStatus(e.target.closest('.document-item'));
                }
            });

            // Drag and drop functionality for photo
            const photoInput = document.getElementById('photo');
            if (photoInput) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    photoInput.addEventListener(eventName, preventDefaults, false);
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    photoInput.addEventListener(eventName, () => photoInput.classList.add('drag-over'),
                        false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    photoInput.addEventListener(eventName, () => photoInput.classList.remove('drag-over'),
                        false);
                });

                photoInput.addEventListener('drop', function(e) {
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        photoInput.files = files;
                        previewPhoto(photoInput);
                    }
                });
            }

            // Drag and drop functionality for documents
            document.addEventListener('dragenter', function(e) {
                if (e.target.type === 'file' && e.target.name === 'documents[]') {
                    preventDefaults(e);
                }
            });

            document.addEventListener('dragover', function(e) {
                if (e.target.type === 'file' && e.target.name === 'documents[]') {
                    preventDefaults(e);
                    e.target.classList.add('drag-over');
                }
            });

            document.addEventListener('dragleave', function(e) {
                if (e.target.type === 'file' && e.target.name === 'documents[]') {
                    e.target.classList.remove('drag-over');
                }
            });

            document.addEventListener('drop', function(e) {
                if (e.target.type === 'file' && e.target.name === 'documents[]') {
                    preventDefaults(e);
                    e.target.classList.remove('drag-over');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        e.target.files = files;
                        e.target.dispatchEvent(new Event('change'));
                    }
                }
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Add document functionality
            document.getElementById('add-document').addEventListener('click', function() {
                const container = document.getElementById('document-container');
                const newItem = container.querySelector('.document-item').cloneNode(true);

                // Clear values
                newItem.querySelectorAll('input, select, textarea').forEach(input => {
                    input.value = '';
                });

                container.appendChild(newItem);
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
                        });
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

            // Prevent multiple submit on edit employee form
            const form = document.querySelector('form[action="{{ route('employees.update', $employee->id) }}"]');
            const submitBtn = document.getElementById('employee-update-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                });
            }

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

            // Handle document deletion with SweetAlert
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-document-btn')) {
                    e.preventDefault();

                    const btn = e.target.closest('.delete-document-btn');
                    const documentId = btn.getAttribute('data-document-id');
                    const documentName = btn.getAttribute('data-document-name');

                    Swal.fire({
                        title: 'Delete Document?',
                        html: `Are you sure you want to delete "<strong>${documentName}</strong>"?<br><small class="text-muted">This action cannot be undone.</small>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Delete it!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        focusCancel: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteDocument(documentId, btn);
                        }
                    });
                }
            });

            // Function to delete document via AJAX
            function deleteDocument(documentId, btn) {
                // Show loading state
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';
                btn.disabled = true;

                // Show loading toast
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the document.',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/employee-documents/${documentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from table
                            const row = document.getElementById(`document-row-${documentId}`);
                            if (row) {
                                row.style.transition = 'all 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-100px)';

                                setTimeout(() => {
                                    row.remove();

                                    // Check if table is empty
                                    const tbody = document.querySelector('.table tbody');
                                    if (tbody && tbody.children.length === 0) {
                                        // Hide the entire documents section or show empty state
                                        const documentsCard = document.querySelector('.card.bg-light');
                                        if (documentsCard) {
                                            documentsCard.style.transition = 'all 0.3s ease';
                                            documentsCard.style.opacity = '0';
                                            setTimeout(() => {
                                                documentsCard.style.display = 'none';
                                            }, 300);
                                        }
                                    }
                                }, 300);
                            }

                            // Show success message
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Document has been deleted successfully.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        } else {
                            throw new Error(data.message || 'Failed to delete document');
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);

                        // Restore button state
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;

                        // Show error message
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to delete document. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    });
            }
        });
    </script>
@endpush
