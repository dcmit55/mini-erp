@extends('layouts.app')

@section('title', 'Bulk Warning Letter')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('warning-batches.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-0 fw-bold">Bulk Warning Letter</h4>
                    <small class="text-muted">Issue warning letters for multiple employees at once</small>
                </div>
            </div>

            <div class="alert alert-info d-flex align-items-start mb-4">
                <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                <div>
                    <strong>Note:</strong> SP level is determined <strong>individually per employee</strong> based on their active history.
                    Employees with an active SP4 will be automatically skipped and shown in the summary.
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('warning-batches.store') }}">
                @csrf

                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-transparent fw-semibold">Incident Information</div>
                    <div class="card-body p-4">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Batch Name <span class="text-danger">*</span></label>
                            <input type="text" name="batch_name" class="form-control @error('batch_name') is-invalid @enderror"
                                placeholder="e.g. SP Project Alpha Loss — March 2026"
                                value="{{ old('batch_name') }}" required>
                            @error('batch_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Violation Category <span class="text-danger">*</span></label>
                                <select name="violation_cat_id" class="form-select @error('violation_cat_id') is-invalid @enderror" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($violationCategories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('violation_cat_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('violation_cat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Incident Date <span class="text-danger">*</span></label>
                                <input type="date" name="incident_date"
                                    class="form-control @error('incident_date') is-invalid @enderror"
                                    value="{{ old('incident_date', date('Y-m-d')) }}"
                                    max="{{ date('Y-m-d') }}" required>
                                @error('incident_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold">Incident Description <span class="text-danger">*</span></label>
                            <textarea name="incident_description" rows="3"
                                class="form-control @error('incident_description') is-invalid @enderror"
                                placeholder="Describe the incident/violation involving all employees below..."
                                required>{{ old('incident_description') }}</textarea>
                            @error('incident_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Select Employees <span class="text-danger">*</span></span>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAll">Clear All</button>
                        </div>
                    </div>
                    <div class="card-body">
                        @error('employee_ids')
                            <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                        @enderror

                        <div style="max-height: 400px; overflow-y: auto;">
                            <div class="row g-2">
                                @foreach($employees as $emp)
                                <div class="col-md-4 col-lg-3">
                                    <div class="form-check border rounded-2 p-2 ps-4">
                                        <input class="form-check-input emp-check" type="checkbox"
                                            name="employee_ids[]" value="{{ $emp->id }}"
                                            id="emp{{ $emp->id }}"
                                            {{ in_array($emp->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emp{{ $emp->id }}">
                                            <div class="fw-semibold small">{{ $emp->name }}</div>
                                            <div class="text-muted" style="font-size:11px">{{ $emp->employee_no }}</div>
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-2 text-muted small">
                            <span id="selectedCount">0</span> employees selected (minimum 2)
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('warning-batches.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-lightning-fill me-1"></i> Generate Warning Letters
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('click', function() {
    document.querySelectorAll('.emp-check').forEach(c => c.checked = true);
    updateCount();
});
document.getElementById('clearAll').addEventListener('click', function() {
    document.querySelectorAll('.emp-check').forEach(c => c.checked = false);
    updateCount();
});
document.querySelectorAll('.emp-check').forEach(c => c.addEventListener('change', updateCount));
function updateCount() {
    document.getElementById('selectedCount').textContent = document.querySelectorAll('.emp-check:checked').length;
}
updateCount();
</script>
@endsection
