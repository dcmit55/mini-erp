@extends('layouts.app')

@section('title', 'Create Work Policy')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('employee-work-policies.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Create Work Policy</h5>
                    <p class="text-muted small mb-0">Set standard working hours for an employee</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
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

                    <form action="{{ route('employee-work-policies.store') }}" method="POST" id="workPolicyForm">
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
                                    <small class="text-muted">Only employees without existing work policy are shown</small>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="employee_no_display" class="form-label small text-dark">Employee Number</label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3 bg-light" 
                                           id="employee_no_display" 
                                           name="employee_no_display" 
                                           value="{{ old('employee_no') }}"
                                           readonly
                                           placeholder="Auto filled">
                                </div>
                            </div>
                        </div>

                        <!-- Working Hours - Weekday -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-calendar-week me-2 text-primary"></i>Weekday (Mon-Fri)
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label for="weekday_start" class="form-label small text-dark">Start Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('weekday_start') is-invalid @enderror" 
                                           id="weekday_start" 
                                           name="weekday_start" 
                                           value="{{ old('weekday_start', '08:00') }}">
                                    @error('weekday_start')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="weekday_end" class="form-label small text-dark">End Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('weekday_end') is-invalid @enderror" 
                                           id="weekday_end" 
                                           name="weekday_end" 
                                           value="{{ old('weekday_end', '17:00') }}">
                                    @error('weekday_end')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <small class="text-muted">* 1 hour break will be automatically deducted.</small>
                        </div>

                        <!-- Default info for Saturday & Sunday -->
                        <div class="mb-4">
                            <div class="alert alert-info py-2 small">
                                <i class="fas fa-info-circle me-1"></i> 
                                <strong>Default hours:</strong> Saturday will be set to 08:00 – 13:00 (5 hours), Sunday off (0 hours). You can change these later in the edit page.
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('employee-work-policies.index') }}" 
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
    // Auto-fill employee number when employee is selected
    const employeeSelect = document.getElementById('employee_id');
    const employeeNoDisplay = document.getElementById('employee_no_display');

    function updateEmployeeNo() {
        const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const empNo = selectedOption.getAttribute('data-employee-no');
            employeeNoDisplay.value = empNo || '';
        } else {
            employeeNoDisplay.value = '';
        }
    }

    updateEmployeeNo();
    employeeSelect.addEventListener('change', updateEmployeeNo);

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        });
    }, 5000);
});
</script>
@endpush
@endsection