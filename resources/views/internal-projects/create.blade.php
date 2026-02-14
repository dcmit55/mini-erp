{{-- resources/views/internal-projects/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Create Internal Project')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('internal-projects.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Create Internal Project</h5>
                    <p class="text-muted small mb-0">Complete new internal project information</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    @if(session('success'))
                        <div class="alert alert-success border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger border-0 mb-3 p-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span class="fw-medium">Error:</span>
                            </div>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('internal-projects.store') }}" method="POST" id="internalProjectForm">
                        @csrf
                        
                        <!-- Project Information -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Project Information
                            </h6>
                            
                            <div class="row g-2">
                                <!-- Project Type -->
                                <div class="col-md-6 mb-2">
                                    <label for="project" class="form-label small text-dark">Project Type <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 @error('project') is-invalid @enderror" 
                                            id="project" 
                                            name="project"
                                            required>
                                        <option value="">Select Project Type</option>
                                        <option value="Office" {{ old('project') == 'Office' ? 'selected' : '' }}>Office</option>
                                        <option value="Machine" {{ old('project') == 'Machine' ? 'selected' : '' }}>Machine</option>
                                        <option value="Testing" {{ old('project') == 'Testing' ? 'selected' : '' }}>Testing</option>
                                        <option value="Facilities" {{ old('project') == 'Facilities' ? 'selected' : '' }}>Facilities</option>
                                    </select>
                                    @error('project')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Department Section (Hanya SATU hidden field) -->
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Department <span class="text-danger">*</span></label>
                                    
                                    <!-- Tampilan untuk non-Testing: teks readonly PT DCM -->
                                    <div id="dept-static-display" class="form-control bg-light">
                                        PT DCM
                                    </div>
                                    
                                    <!-- Tampilan untuk Testing: dropdown select (awalnya hidden) -->
                                    <div id="dept-dropdown-wrapper" style="display: none;">
                                        <select id="dept-dropdown-select" class="form-select select2">
                                            <option value="">Select Department</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Hidden field yang akan dikirim sebagai department_id -->
                                    <input type="hidden" name="department_id" id="final_department_id" value="{{ old('department_id', $defaultPtDcmDepartmentId ?? '') }}">
                                    
                                    @error('department_id')
                                        <div class="invalid-feedback d-block small">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Job (Singkat) -->
                                <div class="col-md-12 mb-2">
                                    <label for="job" class="form-label small text-dark">Job <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('job') is-invalid @enderror" 
                                           id="job" 
                                           name="job" 
                                           value="{{ old('job') }}"
                                           placeholder="Enter job title or brief description"
                                           maxlength="200"
                                           required>
                                    @error('job')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Description (Lengkap) -->
                                <div class="col-md-12 mb-2">
                                    <label for="description" class="form-label small text-dark">Description</label>
                                    <textarea class="form-control border-1 rounded-2 py-2 px-3 @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4"
                                              placeholder="Enter detailed description (optional)">{{ old('description') }}</textarea>
                                    <small class="text-muted">Detailed project description (optional)</small>
                                    @error('description')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('internal-projects.index') }}" 
                               class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-save me-1"></i>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
        height: 42px;
    }

    textarea.form-control {
        min-height: 120px;
        resize: vertical;
        height: auto;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc2626;
    }

    .form-label.small {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
        font-weight: 500;
        color: #374151;
    }

    .bg-light {
        background-color: #f8fafc !important;
        color: #374151;
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    .btn {
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
    }

    .card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }

    h6.fw-medium {
        color: #334155;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    h6.fw-medium i {
        color: #4f46e5;
    }

    .text-danger {
        color: #dc2626 !important;
    }

    .row.g-2 {
        margin-bottom: -0.5rem;
    }

    .row.g-2 > [class^="col-"] {
        margin-bottom: 0.5rem;
    }

    small.text-muted {
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: block;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const defaultDeptId = '{{ $defaultPtDcmDepartmentId ?? '' }}';
    console.log('Default PT DCM ID:', defaultDeptId);

    // Inisialisasi Select2 untuk dropdown department
    if (typeof $.fn.select2 !== 'undefined') {
        $('#dept-dropdown-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Department',
            allowClear: true,
            width: '100%'
        });
    }

    // Elemen-elemen yang diperlukan
    const projectSelect = document.getElementById('project');
    const staticDisplay = document.getElementById('dept-static-display');
    const dropdownWrapper = document.getElementById('dept-dropdown-wrapper');
    const finalHidden = document.getElementById('final_department_id');
    const dropdownSelect = document.getElementById('dept-dropdown-select');

    // Fungsi untuk memperbarui tampilan dan hidden field
    function updateDepartmentField() {
        const projectType = projectSelect.value;
        const isTesting = projectType === 'Testing';

        if (isTesting) {
            // Testing: tampilkan dropdown, sembunyikan static
            staticDisplay.style.display = 'none';
            dropdownWrapper.style.display = 'block';
            // Hidden field mengikuti pilihan dropdown (atau kosong)
            finalHidden.value = dropdownSelect.value || '';
        } else {
            // Non-Testing: tampilkan static, sembunyikan dropdown
            staticDisplay.style.display = 'block';
            dropdownWrapper.style.display = 'none';
            // Hidden field diisi dengan default PT DCM ID
            finalHidden.value = defaultDeptId;
            // Reset dropdown (opsional)
            dropdownSelect.value = '';
            if (typeof $(dropdownSelect).val !== 'undefined') {
                $(dropdownSelect).val('').trigger('change');
            }
        }
    }

    // Event ketika project type berubah
    projectSelect.addEventListener('change', updateDepartmentField);

    // Event ketika dropdown berubah (hanya untuk Testing)
    dropdownSelect.addEventListener('change', function() {
        if (projectSelect.value === 'Testing') {
            finalHidden.value = this.value;
        }
    });

    // Panggil saat halaman dimuat untuk menyesuaikan dengan old value
    updateDepartmentField();

    // Character counter untuk job
    const jobInput = document.getElementById('job');
    if (jobInput) {
        const jobCounter = document.createElement('small');
        jobCounter.className = 'text-muted float-end mt-1';
        jobCounter.innerHTML = '0/200';
        jobInput.parentNode.appendChild(jobCounter);
        
        jobInput.addEventListener('input', function() {
            jobCounter.textContent = this.value.length + '/200';
            if (this.value.length > 200) {
                jobCounter.className = 'text-danger float-end mt-1';
            } else {
                jobCounter.className = 'text-muted float-end mt-1';
            }
        });
        
        // Trigger initial count
        if (jobInput.value) {
            jobCounter.textContent = jobInput.value.length + '/200';
        }
    }

    // Validasi form sebelum submit (pastikan department_id terisi)
    document.getElementById('internalProjectForm').addEventListener('submit', function(e) {
        if (!finalHidden.value) {
            e.preventDefault();
            alert('Department is required.');
        }
    });
});
</script>
@endpush
@endsection