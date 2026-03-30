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

                            @if($errors->has('employee_id') || $errors->has('employee_id.*'))
                                <div class="alert alert-danger border-0 py-2 px-3 small mb-2">
                                    @foreach($errors->get('employee_id') as $msg)<div>{{ $msg }}</div>@endforeach
                                    @foreach($errors->get('employee_id.*') as $msg)<div>{{ $msg }}</div>@endforeach
                                </div>
                            @endif

                            <!-- Header -->
                            <div class="row g-2 mb-1">
                                <div class="col-md-8">
                                    <span class="form-label small text-dark mb-0">Employee <span class="text-danger">*</span></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="form-label small text-dark mb-0">Employee No</span>
                                </div>
                            </div>

                            <!-- Rows -->
                            <div id="employee-rows">
                                @php $oldEmployees = old('employee_id', [null]); @endphp
                                @foreach($oldEmployees as $oldEmpId)
                                <div class="employee-row row g-2 mb-2 align-items-center">
                                    <div class="col-md-8">
                                        <select name="employee_id[]" class="employee-select form-select border-1 rounded-2" required>
                                            <option value="">Select employee...</option>
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}"
                                                    data-employee-no="{{ $employee->employee_no }}"
                                                    {{ $oldEmpId == $employee->id ? 'selected' : '' }}>
                                                    {{ $employee->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-center gap-2">
                                        <input type="text" class="employee-no-display form-control border-1 rounded-2 bg-light fw-medium flex-grow-1"
                                               readonly placeholder="—">
                                        <button type="button" class="btn btn-sm btn-light remove-row border text-danger flex-shrink-0" title="Remove" style="width:38px;height:38px;padding:0;">
                                            <i class="fas fa-times" style="font-size:0.75rem;"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-2">
                                <button type="button" id="add-employee-row" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus me-1"></i> Add Employee
                                </button>
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
    .form-control-sm, .form-select-sm { height: 36px !important; font-size: 0.875rem; }
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

    /* Employee rows */
    #employee-rows .employee-row:last-child { margin-bottom: 0 !important; }
    .employee-no-display { font-size: 0.85rem; letter-spacing: 0.03em; }
    .remove-row:hover { background-color: #fee2e2 !important; border-color: #fca5a5 !important; }
</style>

@push('scripts')
<script>
$(function() {
    // Employee data map for auto-fill
    const employeeData = @json($employees->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'employee_no' => $e->employee_no])->values());

    function buildEmployeeOptions() {
        let opts = '<option value="">Select Employee</option>';
        employeeData.forEach(function(emp) {
            opts += `<option value="${emp.id}" data-employee-no="${emp.employee_no}">${emp.name}</option>`;
        });
        return opts;
    }

    function initSelect2Row(row) {
        const $select = $(row).find('.employee-select');
        $select.select2({
            theme: 'bootstrap-5',
            placeholder: 'Search employee...',
            allowClear: true,
            width: '100%',
        });
        $select.on('change', function() {
            const empNo = $(this).find('option:selected').data('employee-no') || '';
            $(this).closest('.employee-row').find('.employee-no-display').val(empNo);
        });
        // Fill employee_no for pre-selected (old() values)
        const empNo = $select.find('option:selected').data('employee-no') || '';
        $(row).find('.employee-no-display').val(empNo);
    }

    function updateRemoveButtons() {
        const count = $('#employee-rows .employee-row').length;
        $('.remove-row').prop('disabled', count === 1);
    }

    // Init existing rows
    $('#employee-rows .employee-row').each(function() {
        initSelect2Row(this);
    });
    updateRemoveButtons();

    // Add row
    $('#add-employee-row').on('click', function() {
        const html = `
            <div class="employee-row row g-2 mb-2 align-items-center">
                <div class="col-md-8">
                    <select name="employee_id[]" class="employee-select form-select border-1 rounded-2" required>
                        ${buildEmployeeOptions()}
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-center gap-2">
                    <input type="text" class="employee-no-display form-control border-1 rounded-2 bg-light fw-medium flex-grow-1"
                           readonly placeholder="—">
                    <button type="button" class="btn btn-sm btn-light remove-row border text-danger flex-shrink-0" title="Remove" style="width:38px;height:38px;padding:0;">
                        <i class="fas fa-times" style="font-size:0.75rem;"></i>
                    </button>
                </div>
            </div>`;
        const $row = $(html);
        $('#employee-rows').append($row);
        initSelect2Row($row[0]);
        updateRemoveButtons();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('#employee-rows .employee-row').length > 1) {
            const $row = $(this).closest('.employee-row');
            $row.find('.employee-select').select2('destroy');
            $row.remove();
            updateRemoveButtons();
        }
    });

    // Job order department display
    function updateProject() {
        const selected = $('#job_order_id option:selected');
        $('#project_display').val(selected.val() ? (selected.data('department') || '-') : '');
    }
    $('#job_order_id').on('change', updateProject);
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