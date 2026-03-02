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
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

                <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- ===== SECTION 1: PHOTO ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-camera-fill me-2"></i>Employee Photo
                            </h5>
                        </div>
                        <div class="section-body">
                            <div class="row justify-content-center">
                                <div class="col-md-4 text-center">
                                    <div class="photo-preview-container mb-3">
                                        <img id="photo-preview" src="{{ $employee->photo_url }}" alt="Preview"
                                            class="rounded-circle border shadow-sm"
                                            style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <input type="file" class="form-control" id="photo" name="photo"
                                        accept="image/jpeg,image/png,image/jpg" onchange="previewPhoto(this)"
                                        title="Drag & drop photo here or click to browse">
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle"></i> Max 2MB. Leave empty to keep current photo
                                    </small>
                                    @error('photo')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION 2: BASIC INFORMATION ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-person-badge-fill me-2"></i>Basic Information
                            </h5>
                            <p class="section-subtitle">Required employee identification details</p>
                        </div>
                        <div class="section-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="employee_no" class="form-label">
                                        Employee Number <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">DCM-</span>
                                        <input type="text" class="form-control" id="employee_no" name="employee_no"
                                            value="{{ old('employee_no', $employee->employee_number_only) }}"
                                            placeholder="0001" maxlength="10" required>
                                    </div>
                                    <small class="text-muted">Enter 4-digit number (e.g., 0001)</small>
                                    @error('employee_no')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name', $employee->name) }}" placeholder="Enter full name"
                                            required>
                                    </div>
                                    @error('name')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="employment_type" class="form-label">
                                        Employment Type <span class="text-danger">*</span>
                                    </label>
                                    <select name="employment_type" id="employment_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        @foreach ($employmentTypes as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('employment_type', $employee->employment_type) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('employment_type')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">
                                        Employment Status <span class="text-danger">*</span>
                                    </label>
                                    <select name="status" id="status" class="form-select" required>
                                        <option value="active"
                                            {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="inactive"
                                            {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>
                                            Inactive
                                        </option>
                                        <option value="terminated"
                                            {{ old('status', $employee->status) == 'terminated' ? 'selected' : '' }}>
                                            Terminated</option>
                                    </select>
                                    @error('status')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select name="gender" id="gender" class="form-select">
                                        <option value="">Select Gender</option>
                                        <option value="male"
                                            {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Male
                                        </option>
                                        <option value="female"
                                            {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Female
                                        </option>
                                    </select>
                                    @error('gender')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION 3: PERSONAL INFORMATION ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-card-text me-2"></i>Personal Information
                            </h5>
                            <p class="section-subtitle">Identity and birth details</p>
                        </div>
                        <div class="section-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ktp_id" class="form-label">National ID Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                        <input type="text" class="form-control" id="ktp_id" name="ktp_id"
                                            value="{{ old('ktp_id', $employee->ktp_id) }}" placeholder="1234567890123456"
                                            maxlength="20">
                                    </div>
                                    <small class="text-muted">16-digit national ID number</small>
                                    @error('ktp_id')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                        value="{{ old('date_of_birth', $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : '') }}"
                                        max="{{ date('Y-m-d') }}">
                                    @error('date_of_birth')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="place_of_birth" class="form-label">Place of Birth</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                        <input type="text" class="form-control" id="place_of_birth"
                                            name="place_of_birth"
                                            value="{{ old('place_of_birth', $employee->place_of_birth) }}"
                                            placeholder="e.g., Batam">
                                    </div>
                                    @error('place_of_birth')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION 4: CONTACT INFORMATION ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-telephone-fill me-2"></i>Contact Information
                            </h5>
                            <p class="section-subtitle">How to reach this employee</p>
                        </div>
                        <div class="section-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="{{ old('email', $employee->email) }}"
                                            placeholder="employee@company.com">
                                    </div>
                                    @error('email')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            value="{{ old('phone', $employee->phone) }}" placeholder="+62 xxx xxxx xxxx">
                                    </div>
                                    @error('phone')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="address" class="form-label">Full Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"
                                        placeholder="Street address, city, postal code...">{{ old('address', $employee->address) }}</textarea>
                                    @error('address')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION 5: EMPLOYMENT DETAILS ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-briefcase-fill me-2"></i>Employment Details
                            </h5>
                            <p class="section-subtitle">Position and department information</p>
                        </div>
                        <div class="section-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label">
                                        Department <span class="text-danger">*</span>
                                    </label>
                                    <select name="department_id" id="department_id" class="form-select" required>
                                        <option value="">Select Department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label">
                                        Position <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                                        <input type="text" class="form-control" id="position" name="position"
                                            value="{{ old('position', $employee->position) }}" required>
                                    </div>
                                    @error('position')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="hire_date" class="form-label">Hire Date</label>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date"
                                        value="{{ old('hire_date', $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '') }}">
                                    @error('hire_date')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="contract_end_date" class="form-label">Contract End Date</label>
                                    <input type="date" class="form-control" id="contract_end_date"
                                        name="contract_end_date"
                                        value="{{ old('contract_end_date', $employee->contract_end_date ? $employee->contract_end_date->format('Y-m-d') : '') }}">
                                    @error('contract_end_date')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="saldo_cuti" class="form-label">
                                        Leave Balance <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                            class="form-control @error('saldo_cuti') is-invalid @enderror" id="saldo_cuti"
                                            name="saldo_cuti" min="0" max="999.99" step="0.5"
                                            value="{{ old('saldo_cuti', isset($employee) ? number_format($employee->saldo_cuti, 2, '.', '') : 12) }}"
                                            placeholder="12 or 11.5">
                                        <span class="input-group-text">days</span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i> Annual leave balance in days (can use 0.5 for
                                        half day)
                                    </small>
                                    @error('saldo_cuti')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 6: SKILLSETS ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-stars me-2"></i>Skillsets & Competencies
                            </h5>
                            <p class="section-subtitle">Employee skills and proficiency levels</p>
                        </div>
                        <div class="section-body">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Select Skillsets</label>
                                    <select id="skillsets-select" name="skillsets[]"
                                        class="form-select select2-skillsets" multiple>
                                        @foreach ($skillsets as $skillset)
                                            <option value="{{ $skillset->id }}"
                                                data-category="{{ $skillset->category }}"
                                                data-proficiency="{{ $skillset->proficiency_required }}"
                                                {{ $employee->skillsets->contains($skillset->id) ? 'selected' : '' }}>
                                                {{ $skillset->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="bi bi-info-circle"></i> Select multiple skills. Can't find a skill?
                                        <a href="#" id="btn-add-skillset" class="text-primary">
                                            <i class="bi bi-plus-circle"></i> Add New Skillset
                                        </a>
                                    </small>
                                </div>
                            </div>

                            <!-- Dynamic Skillset Details -->
                            <div id="skillset-details-container" class="mt-3">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION 7: FINANCIAL INFORMATION ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-credit-card-fill me-2"></i>Financial Information
                            </h5>
                            <p class="section-subtitle">Salary and banking details</p>
                        </div>
                        <div class="section-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="salary" class="form-label">Monthly Salary</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="salary" name="salary"
                                            value="{{ old('salary', $employee->salary) }}" placeholder="0"
                                            min="0">
                                    </div>
                                    @error('salary')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="rekening" class="form-label">Bank Account Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                        <input type="text" class="form-control" id="rekening" name="rekening"
                                            value="{{ old('rekening', $employee->rekening) }}"
                                            placeholder="1234-5678-9012-3456">
                                    </div>
                                    @error('rekening')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION 8: EXISTING DOCUMENTS ===== -->
                    @if ($employee->documents->count() > 0)
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <h5 class="section-title">
                                    <i class="bi bi-file-earmark-check me-2"></i>Existing Documents
                                </h5>
                                <p class="section-subtitle">{{ $employee->documents->count() }} document(s) uploaded</p>
                            </div>
                            <div class="section-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle">
                                        <thead class="table-light">
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
                                                            <small class="text-muted">{{ $document->description }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $document->formatted_file_size }}</td>
                                                    <td>{{ $document->created_at->format('d M Y') }}</td>
                                                    <td class="text-nowrap">
                                                        <a href="{{ $document->file_url }}" target="_blank"
                                                            class="btn btn-outline-primary btn-sm" title="View Document">
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
                    @endif

                    <!-- ===== SECTION 8: ADD NEW DOCUMENTS ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-file-earmark-plus me-2"></i>Add New Documents
                            </h5>
                            <p class="section-subtitle">Upload additional documents (optional)</p>
                        </div>
                        <div class="section-body">
                            <div id="document-container">
                                <div class="document-item mb-3 border rounded position-relative p-3">
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm remove-document position-absolute"
                                        style="top: 10px; right: 10px; z-index: 10;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Document Type</label>
                                            <select name="document_types[]" class="form-select">
                                                <option value="">Select Type</option>
                                                @foreach ($documentTypes as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Document Name</label>
                                            <input type="text" name="document_names[]" class="form-control"
                                                placeholder="e.g., Updated ID Card">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">File</label>
                                            <input type="file" name="documents[]" class="form-control"
                                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" title="Drag & drop files here">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Description (Optional)</label>
                                            <textarea name="document_descriptions[]" class="form-control" rows="2" placeholder="Brief description..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add-document" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Add Another Document
                            </button>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle"></i> Max 5MB per file. Supported: PDF, DOC, DOCX, JPG, PNG
                            </small>
                        </div>
                    </div>

                    <!-- ===== SECTION 9: ADDITIONAL NOTES ===== -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="bi bi-journal-text me-2"></i>Additional Notes
                            </h5>
                        </div>
                        <div class="section-body">
                            <textarea class="form-control" id="notes" name="notes" rows="4"
                                placeholder="Any additional information about the employee...">{{ old('notes', $employee->notes) }}</textarea>
                            @error('notes')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="employee-update-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                                Update Employee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add Skillset Modal -->
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
                        <div id="skillset-alert" class="alert alert-dismissible fade" style="display: none;"
                            role="alert">
                            <div id="skillset-alert-message"></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>

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
                            <label class="form-label">Minimum Proficiency Required <span
                                    class="text-danger">*</span></label>
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
@endsection

@push('styles')
    <style>
        /* Form Section Styling */
        .form-section {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .form-section:hover {
            border-color: #dee2e6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .section-title i {
            color: #6c757d;
        }

        .section-subtitle {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0;
            margin-top: 0;
            text-align: right;
        }

        .section-body {
            padding: 1.5rem;
        }

        /* Photo Preview */
        .photo-preview-container {
            position: relative;
            display: inline-block;
        }

        #photo-preview {
            transition: all 0.3s ease;
        }

        #photo-preview:hover {
            transform: scale(1.05);
        }

        /* Form Labels */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        /* Input Groups */
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        /* Document Item */
        .document-item {
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            border: 2px dashed #dee2e6 !important;
        }

        .document-item:hover {
            border-color: #adb5bd !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .document-item .remove-document {
            transition: all 0.2s ease;
        }

        .document-item .remove-document:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            transform: scale(1.1);
        }

        /* Validation States */
        .form-control.is-valid,
        .form-select.is-valid {
            border-color: #198754;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23198754' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 8l3 3 5-5'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23dc3545' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 4l8 8M12 4l-8 8'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        /* Drag & Drop */
        .form-control[type="file"].drag-over {
            border-color: #0d6efd !important;
            background-color: #e7f1ff !important;
            border-style: solid !important;
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

        /* Responsive */
        @media (max-width: 768px) {
            .section-header {
                padding: 0.75rem 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }

            .section-subtitle {
                text-align: left;
            }

            .section-body {
                padding: 1rem;
            }

            .section-title {
                font-size: 0.95rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Copy all JavaScript from create.blade.php here
            // (Semua JavaScript yang sama persis seperti di create, tidak ada perubahan)

            // Photo preview function dengan validation
            window.previewPhoto = function(input) {
                const file = input.files[0];
                const preview = document.getElementById('photo-preview');
                const feedback = input.parentNode.querySelector('.validation-feedback');

                if (feedback) feedback.remove();

                if (file) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    if (!allowedTypes.includes(file.type)) {
                        showValidationError(input, 'Invalid file type. Only JPG, PNG, JPEG are allowed.');
                        return;
                    }

                    if (file.size > 2097152) {
                        showValidationError(input, 'File size too large. Maximum 2MB allowed.');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        showValidationSuccess(input, 'Photo uploaded successfully!');
                    };
                    reader.readAsDataURL(file);
                }
            };

            // ... (Copy semua function lainnya dari create.blade.php)
            // Document file validation
            function validateDocumentFile(input) {
                const file = input.files[0];
                const feedback = input.parentNode.querySelector('.validation-feedback');

                if (feedback) feedback.remove();

                if (file) {
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

                    if (file.size > 5242880) {
                        showValidationError(input, 'File size too large. Maximum 5MB allowed.');
                        return false;
                    }

                    showValidationSuccess(input, `File "${file.name}" is valid (${formatFileSize(file.size)})`);
                    return true;
                }
                return false;
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function showValidationError(input, message) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback validation-feedback';
                feedback.textContent = message;
                input.parentNode.appendChild(feedback);
            }

            function showValidationSuccess(input, message) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');

                const feedback = document.createElement('div');
                feedback.className = 'valid-feedback validation-feedback';
                feedback.textContent = message;
                input.parentNode.appendChild(feedback);
            }

            function validateDocumentName(input) {
                const value = input.value.trim();
                const feedback = input.parentNode.querySelector('.validation-feedback');

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

            function addDocumentValidationListeners(container) {
                const fileInputs = container.querySelectorAll('input[name="documents[]"]');
                fileInputs.forEach(input => {
                    input.addEventListener('change', function() {
                        validateDocumentFile(this);
                        updateDocumentItemStatus(this.closest('.document-item'));
                    });
                });

                const nameInputs = container.querySelectorAll('input[name="document_names[]"]');
                nameInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        validateDocumentName(this);
                        updateDocumentItemStatus(this.closest('.document-item'));
                    });
                });
            }

            function updateDocumentItemStatus(documentItem) {
                const fileInput = documentItem.querySelector('input[name="documents[]"]');
                const typeSelect = documentItem.querySelector('select[name="document_types[]"]');
                const nameInput = documentItem.querySelector('input[name="document_names[]"]');

                const hasFile = fileInput.files.length > 0 && fileInput.classList.contains('is-valid');
                const hasType = typeSelect.value !== '';
                const hasName = nameInput.value.trim() !== '' && !nameInput.classList.contains('is-invalid');

                documentItem.classList.remove('complete', 'partial');

                if (hasType && hasName && hasFile) {
                    documentItem.classList.add('complete');
                } else if (hasType || hasName || hasFile) {
                    documentItem.classList.add('partial');
                }
            }

            addDocumentValidationListeners(document.getElementById('document-container'));

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
                        const event = new Event('change', {
                            bubbles: true
                        });
                        fileInput.dispatchEvent(event);
                    }
                }
            }

            addDragDropListeners(document.getElementById('photo'));

            document.querySelectorAll('input[name="documents[]"]').forEach(input => {
                addDragDropListeners(input);
            });

            window.formatFileSize = formatFileSize;

            document.getElementById('add-document').addEventListener('click', function() {
                const container = document.getElementById('document-container');
                const newItem = container.querySelector('.document-item').cloneNode(true);

                newItem.querySelectorAll('input, select, textarea').forEach(input => {
                    input.value = '';
                    input.classList.remove('is-valid', 'is-invalid');
                });

                newItem.querySelectorAll('.validation-feedback').forEach(feedback => {
                    feedback.remove();
                });

                newItem.classList.remove('complete', 'partial');

                container.appendChild(newItem);

                addDocumentValidationListeners(newItem);

                const newFileInput = newItem.querySelector('input[name="documents[]"]');
                addDragDropListeners(newFileInput);
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-document') || e.target.closest(
                        '.remove-document')) {
                    const container = document.getElementById('document-container');
                    if (container.children.length > 1) {
                        e.target.closest('.document-item').remove();
                    } else {
                        const item = e.target.closest('.document-item');
                        item.querySelectorAll('input, select, textarea').forEach(input => {
                            input.value = '';
                            input.classList.remove('is-valid', 'is-invalid');
                        });
                        item.querySelectorAll('.validation-feedback').forEach(feedback => {
                            feedback.remove();
                        });
                        item.classList.remove('complete', 'partial');
                    }
                }
            });

            // Employee number validation
            document.getElementById('employee_no').addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 4) {
                    this.value = this.value.substring(0, 4);
                }
            });

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
                                employee_id: {{ $employee->id }}
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            const input = document.getElementById('employee_no');
                            const feedback = input.parentNode.parentNode.querySelector(
                                '.validation-feedback');

                            if (feedback) feedback.remove();

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

            // Handle document deletion
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

            function deleteDocument(documentId, btn) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';
                btn.disabled = true;

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
                            const row = document.getElementById(`document-row-${documentId}`);
                            if (row) {
                                row.style.transition = 'all 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-100px)';

                                setTimeout(() => {
                                    row.remove();

                                    const tbody = document.querySelector('.table tbody');
                                    if (tbody && tbody.children.length === 0) {
                                        const documentsCard = document.querySelector('.form-section');
                                        if (documentsCard && documentsCard.querySelector('.table')) {
                                            documentsCard.style.display = 'none';
                                        }
                                    }
                                }, 300);
                            }

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

                        btn.innerHTML = originalHtml;
                        btn.disabled = false;

                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to delete document. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    });
            }

            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Prevent multiple submit
            const form = document.querySelector('form[action="{{ route('employees.update', $employee) }}"]');
            const submitBtn = document.getElementById('employee-update-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                });
            }

            // SKILLSETS FUNCTIONALITY
            $(document).ready(function() {
                // Initialize Select2 for skillsets
                $('.select2-skillsets').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select skillsets...',
                    allowClear: true,
                    width: '100%',
                    templateResult: formatSkillsetOption,
                    templateSelection: formatSkillsetSelection
                });

                // Format skillset option dengan category badge
                function formatSkillsetOption(skillset) {
                    if (!skillset.id) return skillset.text;

                    const category = $(skillset.element).data('category');
                    if (!category) return skillset.text;

                    const categoryColors = {
                        'Production': 'primary',
                        'Technical': 'info',
                        'Quality Control': 'success',
                        'Maintenance': 'warning',
                        'Administrative': 'secondary'
                    };

                    const color = categoryColors[category] || 'secondary';

                    return $(`
                    <div>
                        ${skillset.text}
                        <span class="badge bg-${color} ms-2" style="font-size: 0.7rem;">${category}</span>
                    </div>
                `);
                }

                function formatSkillsetSelection(skillset) {
                    return skillset.text;
                }

                // Load existing skillsets on page load
                updateSkillsetDetails();

                // Handle skillset selection change
                $('#skillsets-select').on('change', function() {
                    updateSkillsetDetails();
                });

                function updateSkillsetDetails() {
                    const selectedSkillsets = $('#skillsets-select').select2('data');
                    const container = $('#skillset-details-container');

                    if (selectedSkillsets.length === 0) {
                        container.html(
                            '<div class="alert alert-info"><i class="bi bi-info-circle"></i> No skillsets selected</div>'
                            );
                        return;
                    }

                    let html = '<div class="row g-3">';

                    selectedSkillsets.forEach((skillset, index) => {
                        const skillsetId = skillset.id;
                        const proficiency = $(skillset.element).data('proficiency');
                        const category = $(skillset.element).data('category');

                        // Get existing data from employee (if editing)
                        const existingSkillset = @json($employee->skillsets->keyBy('id'));
                        const existingData = existingSkillset[skillsetId];
                        const currentProficiency = existingData ? existingData.pivot
                            .proficiency_level : proficiency;
                        const acquiredDate = existingData ? existingData.pivot.acquired_date :
                            new Date().toISOString().split('T')[0];

                        html += `
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">${skillset.text}</h6>
                                        ${category ? `<span class="badge bg-primary">${category}</span>` : ''}
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Proficiency Level</label>
                                        <select name="skillset_proficiency[${index}]" class="form-select form-select-sm">
                                            <option value="basic" ${currentProficiency === 'basic' ? 'selected' : ''}>Basic</option>
                                            <option value="intermediate" ${currentProficiency === 'intermediate' ? 'selected' : ''}>Intermediate</option>
                                            <option value="advanced" ${currentProficiency === 'advanced' ? 'selected' : ''}>Advanced</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="form-label small mb-1">Date Acquired</label>
                                        <input type="date" name="skillset_acquired_date[${index}]"
                                            class="form-control form-control-sm"
                                            value="${acquiredDate}"
                                            max="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    });

                    html += '</div>';
                    container.html(html);
                }

                // Open Quick Add Skillset Modal
                $('#btn-add-skillset').on('click', function(e) {
                    e.preventDefault();
                    $('#quickAddSkillsetModal').modal('show');
                });

                // Handle Quick Add Skillset Form Submit
                $('#quickAddSkillsetForm').on('submit', function(e) {
                    e.preventDefault();

                    const form = $(this);
                    const submitBtn = $('#btn-save-skillset');
                    const spinner = $('#skillset-spinner');
                    const alert = $('#skillset-alert');
                    const alertMessage = $('#skillset-alert-message');

                    // Clear previous alerts
                    alert.hide().removeClass('alert-success alert-danger');

                    // Show loading
                    submitBtn.prop('disabled', true);
                    spinner.removeClass('d-none');

                    $.ajax({
                        url: '{{ route('skillsets.store') }}',
                        method: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                // Add new option to select2
                                const newOption = new Option(
                                    response.skillset.name,
                                    response.skillset.id,
                                    false,
                                    true
                                );

                                $(newOption).attr('data-category', response.skillset
                                    .category);
                                $(newOption).attr('data-proficiency', response.skillset
                                    .proficiency_required);

                                $('#skillsets-select').append(newOption).trigger(
                                    'change');

                                // Show success message
                                alertMessage.html(
                                    `<i class="bi bi-check-circle"></i> ${response.message}`
                                    );
                                alert.addClass('alert-success').fadeIn();

                                // Reset form
                                form[0].reset();

                                // Close modal after delay
                                setTimeout(() => {
                                    $('#quickAddSkillsetModal').modal('hide');
                                    alert.fadeOut();
                                }, 1500);
                            }
                        },
                        error: function(xhr) {
                            let errorMsg = 'Failed to add skillset';

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                const errors = Object.values(xhr.responseJSON.errors)
                                    .flat();
                                errorMsg = errors.join('<br>');
                            }

                            alertMessage.html(
                                `<i class="bi bi-exclamation-triangle"></i> ${errorMsg}`
                                );
                            alert.addClass('alert-danger').fadeIn();
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
        });
    </script>
@endpush
