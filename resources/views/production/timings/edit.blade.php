@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-3">Edit Timing</h4>

                {{-- Old Data Panel --}}
                <div class="alert alert-light border mb-4" style="font-size:.85rem;">
                    <div class="fw-semibold mb-2 text-muted"
                        style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">Current Record (Before Edit)
                    </div>
                    <div class="row g-2">
                        <div class="col-md-2">
                            <span class="text-muted">Date:</span><br>
                            <strong>{{ $timing->tanggal ?? '—' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Project:</span><br>
                            <strong>{{ $timing->project?->name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Job Order:</span><br>
                            <strong>{{ $timing->jobOrder?->name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted">Employee:</span><br>
                            <strong>{{ $timing->employee?->name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted">Time:</span><br>
                            <strong>{{ $timing->start_time }} – {{ $timing->end_time }}</strong>
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif
                <form action="{{ route('timings.update', $timing->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="tanggal" class="form-control" required
                                value="{{ old('tanggal', $timing->tanggal) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select select2" required>
                                <option value="">Select Project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        data-department="{{ $project->departments->pluck('name')->implode(', ') }}"
                                        data-parts='@json($project->parts->pluck('part_name'))'
                                        {{ old('project_id', $timing->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Job Order</label>
                            <select name="job_order_id" class="form-select select2" id="job_order_select">
                                <option value="">— No Job Order —</option>
                                @foreach ($jobOrders as $jo)
                                    <option value="{{ $jo->id }}" data-project="{{ $jo->project_id }}"
                                        {{ old('job_order_id', $timing->job_order_id) == $jo->id ? 'selected' : '' }}>
                                        {{ $jo->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Step</label>
                            <input type="text" name="step" class="form-control" required
                                value="{{ old('step', $timing->step) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Part</label>
                            @php
                                $selectedProject = $projects->firstWhere('id', old('project_id', $timing->project_id));
                                $parts = $selectedProject ? $selectedProject->parts->pluck('part_name')->toArray() : [];
                                $hasParts = count($parts) > 0;
                            @endphp
                            <select name="parts" class="form-select part-select"
                                {{ !$hasParts ? 'readonly disabled' : '' }}>
                                @if (!$hasParts)
                                    <option value="No Part" selected>No Part</option>
                                @else
                                    <option value="">Select Project Part</option>
                                    @foreach ($parts as $part)
                                        <option value="{{ $part }}"
                                            {{ old('parts', $timing->parts) == $part ? 'selected' : '' }}>
                                            {{ $part }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select select2" required>
                                <option value="">Select Employee</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}"
                                        {{ old('employee_id', $timing->employee_id) == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required
                                value="{{ old('start_time', $timing->start_time) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" required
                                value="{{ old('end_time', $timing->end_time) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Duration (min)</label>
                            <input type="number" name="duration_minutes" class="form-control" required
                                value="{{ old('duration_minutes', $timing->duration_minutes) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Measurement Type</label>
                            <select name="measurement_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="progress"
                                    {{ old('measurement_type', $timing->measurement_type) == 'progress' ? 'selected' : '' }}>
                                    Progress (%)</option>
                                <option value="qty"
                                    {{ old('measurement_type', $timing->measurement_type) == 'qty' ? 'selected' : '' }}>Qty
                                </option>
                                <option value="pcs"
                                    {{ old('measurement_type', $timing->measurement_type) == 'pcs' ? 'selected' : '' }}>Pcs
                                </option>
                                <option value="unit"
                                    {{ old('measurement_type', $timing->measurement_type) == 'unit' ? 'selected' : '' }}>
                                    Unit</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Measurement Value</label>
                            <input type="number" step="0.01" name="measurement_value" class="form-control" required
                                value="{{ old('measurement_value', $timing->measurement_value) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending"
                                    {{ old('status', $timing->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="on progress"
                                    {{ old('status', $timing->status) == 'on progress' ? 'selected' : '' }}>On Progress
                                </option>
                                <option value="complete"
                                    {{ old('status', $timing->status) == 'complete' ? 'selected' : '' }}>Complete</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control"
                                value="{{ old('remarks', $timing->remarks) }}">
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('timings.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                aria-hidden="true"></span>
                            Update Timing
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true
            });

            // Filter job orders by selected project
            function filterJobOrders(projectId) {
                $('#job_order_select option').each(function() {
                    var $opt = $(this);
                    if (!$opt.val() || !projectId || $opt.data('project') == projectId) {
                        $opt.show();
                    } else {
                        $opt.hide();
                    }
                });
            }

            // Run on load with initial project value
            var initialProject = $('select[name="project_id"]').val();
            filterJobOrders(initialProject);

            // Project change: update parts select and filter job orders
            $('select[name="project_id"]').on('change', function() {
                let projectId = $(this).val();
                filterJobOrders(projectId);
                $('#job_order_select').val('').trigger('change');

                let selected = $(this).find(':selected')[0];
                let parts = selected.getAttribute('data-parts');
                let $partSelect = $('select[name="parts"]');
                if (parts && JSON.parse(parts).length > 0) {
                    $partSelect.prop('disabled', false).prop('readonly', false);
                    $partSelect.html('<option value="">Select Project Part</option>');
                    JSON.parse(parts).forEach(function(part) {
                        $partSelect.append(`<option value="${part}">${part}</option>`);
                    });
                    $partSelect.val('');
                } else {
                    $partSelect.prop('disabled', true).prop('readonly', true);
                    $partSelect.html('<option value="No Part" selected>No Part</option>');
                    $partSelect.val('No Part');
                }
            });
        });
    </script>
@endpush
