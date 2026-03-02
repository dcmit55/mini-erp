@extends('layouts.app')

@section('title', 'Edit Overtime Request')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('overtime-requests.show', $overtimeRequest) }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Edit Overtime Request #{{ $overtimeRequest->id }}</h5>
                    <p class="text-muted small mb-0">Update overtime request details</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    @if(session('error'))
                        <div class="alert alert-danger border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
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

                    <form action="{{ route('overtime-requests.update', $overtimeRequest) }}" method="POST" id="overtimeRequestForm">
                        @csrf
                        @method('PUT')
                        
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
                                            <option value="{{ $employee->id }}" 
                                                data-employee-no="{{ $employee->employee_no }}" 
                                                {{ old('employee_id', $overtimeRequest->employee_id) == $employee->id ? 'selected' : '' }}>
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

                        <!-- Job Order Selection -->
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
                                                {{ old('job_order_id', $overtimeRequest->job_order_id) == $jobOrder->id ? 'selected' : '' }}>
                                                {{ $jobOrder->name }}
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
                                              required>{{ old('reason', $overtimeRequest->reason) }}</textarea>
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
                                        <option value="Normal Day" {{ old('ot_code', $overtimeRequest->ot_code) == 'Normal Day' ? 'selected' : '' }}>Normal Day</option>
                                        <option value="Sunday" {{ old('ot_code', $overtimeRequest->ot_code) == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                                        <option value="Public Holiday" {{ old('ot_code', $overtimeRequest->ot_code) == 'Public Holiday' ? 'selected' : '' }}>Public Holiday</option>
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
                                           value="{{ old('start_time', \Carbon\Carbon::parse($overtimeRequest->start_time)->format('Y-m-d\TH:i')) }}"
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
                                           value="{{ old('end_time', \Carbon\Carbon::parse($overtimeRequest->end_time)->format('Y-m-d\TH:i')) }}"
                                           required>
                                    @error('end_time')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('overtime-requests.show', $overtimeRequest) }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-save me-1"></i>Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style> /* CSS sama seperti create */ </style>

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
});
</script>
@endpush
@endsection