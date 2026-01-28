{{-- resources/views/hr/employees/create.blade.php --}}
@extends('hr.employees.layouts.employee-form-layout', [
    'title' => 'Add New Employee',
    'icon' => 'bi bi-person-plus-fill',
    'formAction' => route('employees.store'),
    'cancelRoute' => route('employees.index'),
    'submitText' => 'Save Employee'
])

@section('form-content')
    <!-- Photo Section -->
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
                        <img id="photo-preview" src="{{ asset('images/default-avatar.png') }}" 
                             alt="Employee Photo" class="photo-preview"
                             style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    
                    <div class="mb-3">
                        <input type="file" class="form-control" id="photo" name="photo" 
                               accept="image/jpeg,image/png,image/jpg" 
                               onchange="previewPhoto(this)"
                               required
                               title="Drag & drop photo here or click to browse">
                        
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle"></i> Max 2MB. Supported: JPG, PNG, JPEG
                        </small>
                        
                        @error('photo')
                            <div class="text-danger small mt-1">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Basic Information -->
    <div class="form-section mb-4">
        <div class="section-header">
            <h5 class="section-title">
                <i class="bi bi-person-badge-fill me-2"></i>Basic Information
            </h5>
            <p class="section-subtitle">Required employee identification details</p>
        </div>
        <div class="section-body">
            <div class="row">
                <!-- Employee Number -->
                <div class="col-md-6 mb-3">
                    <label for="employee_no" class="form-label">
                        Employee Number <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">DCM-</span>
                        <input type="text" class="form-control" id="employee_no" name="employee_no"
                               value="{{ old('employee_no') }}"
                               placeholder="0001" maxlength="10" required
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <small class="text-muted">Enter 4-digit number (e.g., 0001)</small>
                    @error('employee_no')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Full Name -->
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">
                        Full Name <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="name" name="name"
                               value="{{ old('name') }}" 
                               placeholder="Enter full name" required>
                    </div>
                    @error('name')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Employment Type -->
                <div class="col-md-4 mb-3">
                    <label for="employment_type" class="form-label">
                        Employment Type <span class="text-danger">*</span>
                    </label>
                    <select name="employment_type" id="employment_type" class="form-select" required>
                        <option value="">Select Type</option>
                        @foreach ($employmentTypes as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('employment_type') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('employment_type')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Employment Status -->
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">
                        Employment Status <span class="text-danger">*</span>
                    </label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="active"
                            {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" 
                            {{ old('status') == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                        <option value="terminated" 
                            {{ old('status') == 'terminated' ? 'selected' : '' }}>
                            Terminated
                        </option>
                    </select>
                    @error('status')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Gender -->
                <div class="col-md-4 mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select name="gender" id="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male" 
                            {{ old('gender') == 'male' ? 'selected' : '' }}>
                            Male
                        </option>
                        <option value="female" 
                            {{ old('gender') == 'female' ? 'selected' : '' }}>
                            Female
                        </option>
                    </select>
                    @error('gender')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    
    <!-- Personal Information -->
    <div class="form-section mb-4">
        <div class="section-header">
            <h5 class="section-title">
                <i class="bi bi-card-text me-2"></i>Personal Information
            </h5>
            <p class="section-subtitle">Identity and birth details</p>
        </div>
        <div class="section-body">
            <div class="row">
                <!-- KTP ID -->
                <div class="col-md-6 mb-3">
                    <label for="ktp_id" class="form-label">National ID Number (KTP)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                        <input type="text" class="form-control" id="ktp_id" name="ktp_id"
                               value="{{ old('ktp_id') }}" 
                               placeholder="1234567890123456" maxlength="16"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <small class="text-muted">16-digit national ID number</small>
                    @error('ktp_id')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror
                </div>
                
                <!-- Date of Birth -->
                <div class="col-md-6 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                           value="{{ old('date_of_birth') }}" 
                           max="{{ date('Y-m-d') }}">
                    @error('date_of_birth')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror
                </div>
                
                <!-- Place of Birth -->
                <div class="col-md-12 mb-3">
                    <label for="place_of_birth" class="form-label">Place of Birth</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" class="form-control" id="place_of_birth" name="place_of_birth"
                               value="{{ old('place_of_birth') }}" placeholder="e.g., Batam">
                    </div>
                    @error('place_of_birth')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Information -->
    <div class="form-section mb-4">
        <div class="section-header">
            <h5 class="section-title">
                <i class="bi bi-telephone-fill me-2"></i>Contact Information
            </h5>
            <p class="section-subtitle">How to reach this employee</p>
        </div>
        <div class="section-body">
            <div class="row">
                <!-- Email -->
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email') }}" 
                               placeholder="employee@company.com">
                    </div>
                    @error('email')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Phone -->
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="{{ old('phone') }}" 
                               placeholder="+62 xxx xxxx xxxx"
                               oninput="this.value = this.value.replace(/[^0-9+]/g, '')">
                    </div>
                    @error('phone')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Address -->
                <div class="col-md-12 mb-3">
                    <label for="address" class="form-label">Full Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"
                              placeholder="Street address, city, postal code...">{{ old('address') }}</textarea>
                    @error('address')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    
    <!-- Employment Details -->
    <div class="form-section mb-4">
        <div class="section-header">
            <h5 class="section-title">
                <i class="bi bi-briefcase-fill me-2"></i>Employment Details
            </h5>
            <p class="section-subtitle">Position and department information</p>
        </div>
        <div class="section-body">
            <div class="row">
                <!-- Department -->
                <div class="col-md-6 mb-3">
                    <label for="department_id" class="form-label">
                        Department <span class="text-danger">*</span>
                    </label>
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
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Position -->
                <div class="col-md-6 mb-3">
                    <label for="position" class="form-label">
                        Position <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                        <input type="text" class="form-control" id="position" name="position"
                               value="{{ old('position') }}" required>
                    </div>
                    @error('position')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Hire Date -->
                <div class="col-md-6 mb-3">
                    <label for="hire_date" class="form-label">Hire Date</label>
                    <input type="date" class="form-control" id="hire_date" name="hire_date"
                           value="{{ old('hire_date') }}"
                           max="{{ date('Y-m-d') }}">
                    @error('hire_date')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Contract End Date -->
                <div class="col-md-6 mb-3">
                    <label for="contract_end_date" class="form-label">Contract End Date</label>
                    <input type="date" class="form-control" id="contract_end_date" name="contract_end_date"
                           value="{{ old('contract_end_date') }}">
                    @error('contract_end_date')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Leave Balance -->
                <div class="col-md-6 mb-3">
                    <label for="saldo_cuti" class="form-label">
                        Leave Balance <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="saldo_cuti" name="saldo_cuti"
                               min="0" max="999.99" step="0.5"
                               value="{{ old('saldo_cuti', 12) }}"
                               placeholder="12 or 11.5" required>
                        <span class="input-group-text">days</span>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Annual leave balance in days (can use 0.5 for half day)
                    </small>
                    @error('saldo_cuti')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    
    <!-- Skillsets Section -->
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
                    <select name="skillsets[]" class="form-select" multiple>
                        <option value="">Select skillsets...</option>
                        @foreach ($skillsets as $skillset)
                            <option value="{{ $skillset->id }}"
                                {{ old('skillsets') && in_array($skillset->id, old('skillsets')) ? 'selected' : '' }}>
                                {{ $skillset->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted mt-1 d-block">
                        <i class="bi bi-info-circle"></i> Hold Ctrl/Cmd to select multiple skills
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Financial Information -->
    <div class="form-section mb-4">
        <div class="section-header">
            <h5 class="section-title">
                <i class="bi bi-credit-card-fill me-2"></i>Financial Information
            </h5>
            <p class="section-subtitle">Salary and banking details</p>
        </div>
        <div class="section-body">
            <div class="row">
                <!-- Salary -->
                <div class="col-md-6 mb-3">
                    <label for="salary" class="form-label">Monthly Salary</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="salary" name="salary"
                               value="{{ old('salary') }}" 
                               placeholder="0" min="0"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    @error('salary')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>

                <!-- Bank Account -->
                <div class="col-md-6 mb-3">
                    <label for="rekening" class="form-label">Bank Account Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                        <input type="text" class="form-control" id="rekening" name="rekening"
                               value="{{ old('rekening') }}" 
                               placeholder="1234-5678-9012-3456"
                               oninput="this.value = this.value.replace(/[^0-9-]/g, '')">
                    </div>
                    @error('rekening')
                        <small class="text-danger d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    
    <!-- Documents Section -->
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
                                   placeholder="e.g., ID Card">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">File</label>
                            <input type="file" name="documents[]" class="form-control document-file"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                                   title="Drag & drop files here">
                        </div>
                        
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
    
    <!-- Additional Notes -->
    <div class="form-section mb-4">
        <div class="section-header">
            <h5 class="section-title">
                <i class="bi bi-journal-text me-2"></i>Additional Notes
            </h5>
        </div>
        <div class="section-body">
            <textarea class="form-control" id="notes" name="notes" rows="4"
                      placeholder="Any additional information about the employee...">{{ old('notes') }}</textarea>
            @error('notes')
                <small class="text-danger d-block">{{ $message }}</small>
            @enderror
        </div>
    </div>
@endsection

{{-- Script untuk multiple documents --}}
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
                });
                
                // Add to container
                container.appendChild(newItem);
            });
            
            // Remove document functionality (optional - add remove button if needed)
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-document')) {
                    const container = document.getElementById('document-container');
                    if (container.children.length > 1) {
                        e.target.closest('.document-item').remove();
                    }
                }
            });
            
            // Add remove button to document items (except first)
            function addRemoveButtons() {
                const documentItems = document.querySelectorAll('.document-item');
                documentItems.forEach((item, index) => {
                    if (index > 0 && !item.querySelector('.remove-document')) {
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-outline-danger btn-sm remove-document';
                        removeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
                        removeBtn.style.position = 'absolute';
                        removeBtn.style.top = '10px';
                        removeBtn.style.right = '10px';
                        removeBtn.style.zIndex = '10';
                        
                        item.style.position = 'relative';
                        item.appendChild(removeBtn);
                    }
                });
            }
            
            // Initialize remove buttons
            addRemoveButtons();
        });
    </script>
@endpush