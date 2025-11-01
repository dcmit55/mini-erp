@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-4">Edit Timing</h4>
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
                                    <option value="{{ $project->id }}" data-department="{{ $project->department->name }}"
                                        data-parts='@json($project->parts->pluck('part_name'))'
                                        {{ old('project_id', $timing->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
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
                            <label class="form-label">Output Qty</label>
                            <input type="number" name="output_qty" class="form-control" required
                                value="{{ old('output_qty', $timing->output_qty) }}">
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

            // Project change: update parts select
            $('select[name="project_id"]').on('change', function() {
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
