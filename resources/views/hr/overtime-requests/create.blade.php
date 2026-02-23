@extends('layouts.app')

@section('title', 'Create Overtime Request')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Create Overtime Request</h5>
                    <p class="text-muted small mb-0">Submit a new overtime request for an employee</p>
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

                    <form action="{{ route('overtime-requests.store') }}" method="POST" id="overtimeRequestForm">
                        @csrf
                        
                        <!-- Employee Selection -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-user me-2 text-primary"></i>Employee Information
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-8 mb-2">
                                    <label for="employee_id" class="form-label small text-dark">Employee <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 @error('employee_id') is-invalid @enderror" 
                                            id="employee_id" 
                                            name="employee_id"
                                            required>
                                        <option value="">Select Employee</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" data-employee-no="{{ $employee->employee_no }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                                {{ $employee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('employee_id')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="employee_no_display" class="form-label small text-dark">Employee Number</label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3 bg-light" 
                                           id="employee_no_display" 
                                           readonly
                                           placeholder="Auto filled">
                                </div>
                            </div>
                        </div>

                        <!-- Job Order Selection (dropdown menampilkan nama proyek, project field menampilkan departemen) -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-clipboard-list me-2 text-primary"></i>Job Order Information
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-8 mb-2">
                                    <label for="job_order_id" class="form-label small text-dark">Job Order <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 @error('job_order_id') is-invalid @enderror" 
                                            id="job_order_id" 
                                            name="job_order_id"
                                            required>
                                        <option value="">Select Job Order</option>
                                        @foreach($jobOrders as $jobOrder)
                                            <option value="{{ $jobOrder->id }}" 
                                                data-project="{{ $jobOrder->name }}" 
                                                data-department="{{ $jobOrder->department->name ?? '' }}"
                                                {{ old('job_order_id') == $jobOrder->id ? 'selected' : '' }}>
                                                {{ $jobOrder->name }} <!-- hanya nama proyek -->
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('job_order_id')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="project_display" class="form-label small text-dark">Department</label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3 bg-light" 
                                           id="project_display" 
                                           readonly
                                           placeholder="Auto filled">
                                </div>
                            </div>
                        </div>

                        <!-- Overtime Details -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-hourglass-half me-2 text-primary"></i>Overtime Details
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-12 mb-2">
                                    <label for="reason" class="form-label small text-dark">Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control border-1 rounded-2 py-2 px-3 @error('reason') is-invalid @enderror" 
                                              id="reason" 
                                              name="reason" 
                                              rows="2"
                                              required>{{ old('reason') }}</textarea>
                                    @error('reason')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label for="ot_code" class="form-label small text-dark">OT Code <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 @error('ot_code') is-invalid @enderror" 
                                            id="ot_code" 
                                            name="ot_code"
                                            required>
                                        <option value="">Select</option>
                                        <option value="Normal Day" {{ old('ot_code') == 'Normal Day' ? 'selected' : '' }}>Normal Day</option>
                                        <option value="Sunday" {{ old('ot_code') == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                                        <option value="Public Holiday" {{ old('ot_code') == 'Public Holiday' ? 'selected' : '' }}>Public Holiday</option>
                                    </select>
                                    @error('ot_code')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label for="start_time" class="form-label small text-dark">Start Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('start_time') is-invalid @enderror" 
                                           id="start_time" 
                                           name="start_time" 
                                           value="{{ old('start_time') }}"
                                           required>
                                    @error('start_time')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label for="end_time" class="form-label small text-dark">End Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('end_time') is-invalid @enderror" 
                                           id="end_time" 
                                           name="end_time" 
                                           value="{{ old('end_time') }}"
                                           required>
                                    @error('end_time')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('overtime-requests.index') }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">Cancel</a>
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
    .form-control, .form-select { border-color: #e2e8f0; font-size: 0.9rem; height: 42px; }
    .form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1); }
    .form-control.is-invalid, .form-select.is-invalid { border-color: #dc2626; }
    .form-label.small { font-size: 0.85rem; margin-bottom: 0.25rem; font-weight: 500; color: #374151; }
    .bg-light { background-color: #f8fafc !important; color: #374151; font-weight: 500; }
    .btn { font-size: 0.9rem; font-weight: 500; }
    .btn-primary { background-color: #4f46e5; border-color: #4f46e5; }
    .btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
    .card { background: #ffffff; border: 1px solid #e2e8f0; }
    h6.fw-medium { color: #334155; font-size: 1rem; font-weight: 600; margin-bottom: 1rem; }
    h6.fw-medium i { color: #4f46e5; }
    .text-danger { color: #dc2626 !important; }
    .row.g-2 { margin-bottom: -0.5rem; }
    .row.g-2 > [class^="col-"] { margin-bottom: 0.5rem; }
    small.text-muted { font-size: 0.8rem; margin-top: 0.25rem; display: block; }
    textarea.form-control { height: auto; min-height: 80px; }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employee_id');
    const employeeNoDisplay = document.getElementById('employee_no_display');
    const jobOrderSelect = document.getElementById('job_order_id');
    const projectDisplay = document.getElementById('project_display');

    function updateEmployeeNo() {
        const selected = employeeSelect.options[employeeSelect.selectedIndex];
        employeeNoDisplay.value = selected?.getAttribute('data-employee-no') || '';
    }

    function updateProject() {
        const selected = jobOrderSelect.options[jobOrderSelect.selectedIndex];
        if (selected?.value) {
            const department = selected.getAttribute('data-department');
            projectDisplay.value = department || '-';
        } else {
            projectDisplay.value = '';
        }
    }

    employeeSelect.addEventListener('change', updateEmployeeNo);
    jobOrderSelect.addEventListener('change', updateProject);

    updateEmployeeNo();
    updateProject();

    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        });
    }, 5000);
});
</script>
@endpush
@endsection