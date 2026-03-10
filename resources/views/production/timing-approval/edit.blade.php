@extends('layouts.app')

@section('title', 'Edit Timing - Approval')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-pencil-square"></i> Edit Timing Data
            </h1>
            <a href="{{ route('timing-approval.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Approval
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Timing Details - ID: {{ $timing->id }}
                    <span
                        class="badge {{ $timing->isPending() ? 'bg-warning' : ($timing->isApproved() ? 'bg-success' : 'bg-danger') }}">
                        {{ ucfirst($timing->approval_status) }}
                    </span>
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('timing-approval.update', $timing->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tanggal" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal"
                                name="tanggal" value="{{ old('tanggal', $timing->tanggal) }}" required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="employee_ids" class="form-label">Employee(s) <span
                                    class="text-danger">*</span></label>
                            <select class="form-select select2-multi @error('employee_ids') is-invalid @enderror"
                                id="employee_ids" name="employee_ids[]" multiple required>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}"
                                        {{ in_array($employee->id, old('employee_ids', [$timing->employee_id])) ? 'selected' : '' }}>
                                        {{ $employee->name }} - {{ $employee->department->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Select one employee to update this record, or multiple to create additional records with the
                                same timing data.
                            </small>
                            @error('employee_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select class="form-select select2 @error('project_id') is-invalid @enderror" id="project_id"
                                name="project_id" required>
                                <option value="">Select Project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        {{ old('project_id', $timing->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="job_order_id" class="form-label">Job Order</label>
                            <select class="form-select select2 @error('job_order_id') is-invalid @enderror"
                                id="job_order_id" name="job_order_id">
                                <option value="">Select Job Order</option>
                                @foreach ($jobOrders as $jobOrder)
                                    <option value="{{ $jobOrder->id }}"
                                        {{ old('job_order_id', $timing->job_order_id) == $jobOrder->id ? 'selected' : '' }}>
                                        {{ $jobOrder->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('job_order_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="step" class="form-label">Step / Process</label>
                            <input type="text" class="form-control @error('step') is-invalid @enderror" id="step"
                                name="step" value="{{ old('step', $timing->step) }}"
                                placeholder="e.g., Cutting, Sewing, Assembly">
                            @error('step')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="parts" class="form-label">Parts / Components</label>
                            <input type="text" class="form-control @error('parts') is-invalid @enderror" id="parts"
                                name="parts" value="{{ old('parts', $timing->parts) }}"
                                placeholder="e.g., Head, Body, Arms">
                            @error('parts')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                                id="start_time" name="start_time" value="{{ old('start_time', $timing->start_time) }}"
                                required>
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('end_time') is-invalid @enderror"
                                id="end_time" name="end_time" value="{{ old('end_time', $timing->end_time) }}"
                                required>
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Duration will be calculated automatically</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="measurement_value" class="form-label">Output / Quantity</label>
                            <input type="number" step="0.01" min="0"
                                class="form-control @error('measurement_value') is-invalid @enderror"
                                id="measurement_value" name="measurement_value"
                                value="{{ old('measurement_value', $timing->measurement_value) }}"
                                placeholder="e.g., 10">
                            @error('measurement_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="measurement_type" class="form-label">Unit Type</label>
                            <input type="text" class="form-control @error('measurement_type') is-invalid @enderror"
                                id="measurement_type" name="measurement_type"
                                value="{{ old('measurement_type', $timing->measurement_type) }}"
                                placeholder="e.g., pcs, units, kg">
                            @error('measurement_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks / Notes</label>
                        <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3"
                            placeholder="Additional notes...">{{ old('remarks', $timing->remarks) }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="border-top pt-3 mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                <i class="bi bi-info-circle"></i>
                                Changes will be saved but approval status remains:
                                <strong>{{ ucfirst($timing->approval_status) }}</strong>
                            </div>
                            <div>
                                <a href="{{ route('timing-approval.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Current Values Info --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-secondary">Current Timing Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <dl>
                            <dt>Current Duration</dt>
                            <dd>{{ $timing->duration_formatted ?? 'N/A' }}</dd>

                            <dt>Approval Status</dt>
                            <dd>
                                <span
                                    class="badge {{ $timing->isPending() ? 'bg-warning' : ($timing->isApproved() ? 'bg-success' : 'bg-danger') }}">
                                    {{ ucfirst($timing->approval_status) }}
                                </span>
                            </dd>
                        </dl>
                    </div>

                    <div class="col-md-4">
                        <dl>
                            @if ($timing->approved_by)
                                <dt>Approved/Rejected By</dt>
                                <dd>{{ $timing->approver->name ?? 'N/A' }}</dd>

                                <dt>Approved/Rejected At</dt>
                                <dd>{{ $timing->approved_at ? $timing->approved_at->format('d M Y H:i') : 'N/A' }}</dd>
                            @endif
                        </dl>
                    </div>

                    <div class="col-md-4">
                        <dl>
                            @if ($timing->rejection_reason)
                                <dt>Rejection Reason</dt>
                                <dd class="text-danger">{{ $timing->rejection_reason }}</dd>
                            @endif

                            <dt>Created At</dt>
                            <dd>{{ $timing->created_at->format('d M Y H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 (single selects)
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'Select an option'
            });

            // Initialize Select2 (multi-select for employees)
            $('#employee_ids').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'Select employee(s)...'
            });

            // Calculate duration preview
            function updateDurationPreview() {
                const startTime = $('#start_time').val();
                const endTime = $('#end_time').val();

                if (startTime && endTime) {
                    const start = new Date('2000-01-01 ' + startTime);
                    const end = new Date('2000-01-01 ' + endTime);
                    const diff = (end - start) / 1000 / 60; // minutes

                    if (diff > 0) {
                        const hours = Math.floor(diff / 60);
                        const minutes = Math.floor(diff % 60);
                        const preview = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;

                        $('#end_time').next('small').html(`Duration: <strong>${preview}</strong>`);
                    } else {
                        $('#end_time').next('small').html('Duration will be calculated automatically');
                    }
                }
            }

            $('#start_time, #end_time').on('change', updateDurationPreview);
            updateDurationPreview(); // Initial calculation
        });
    </script>
@endpush
