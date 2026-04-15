@extends('layouts.app')

@section('title', 'New Warning Letter')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('warning-letters.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-0 fw-bold">New Warning Letter</h4>
                    <small class="text-muted">SP level is determined automatically based on the employee's history</small>
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- SP Level Auto-Suggest Banner --}}
            @if($terminationFlag)
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <strong>This employee has an active SP4.</strong><br>
                        The next violation must be processed through a <strong>Termination of Employment</strong> procedure, not a new SP.
                    </div>
                </div>
            @elseif($suggestedSpLevel)
                @php
                    $spColors = [1=>'info', 2=>'warning', 3=>'orange', 4=>'danger'];
                    $spColor  = $spColors[$suggestedSpLevel] ?? 'secondary';
                @endphp
                <div class="alert alert-{{ $spColor === 'orange' ? 'warning' : $spColor }} d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle-fill fs-5 me-3"></i>
                    <div>
                        @if($activeSpLevel)
                            This employee has an <strong>active SP{{ $activeSpLevel }}</strong>. A new violation will result in
                        @else
                            This employee has no prior SP. This violation will result in
                        @endif
                        <strong>SP{{ $suggestedSpLevel }}</strong>.
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('warning-letters.store') }}" id="formCreateWL">
                        @csrf

                        {{-- Employee --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employeeSelect" class="form-select select2-employee @error('employee_id') is-invalid @enderror" required>
                                <option value="">-- Select Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}"
                                        {{ old('employee_id', request('employee_id')) == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employee_no }} — {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">After selecting an employee, the SP level will be suggested automatically.</small>
                        </div>

                        {{-- Violation Category --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Violation Category <span class="text-danger">*</span></label>
                            <select name="violation_cat_id" class="form-select @error('violation_cat_id') is-invalid @enderror" required>
                                <option value="">-- Select Category --</option>
                                @foreach($violationCategories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('violation_cat_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                        <span class="text-muted">({{ $cat->severity }})</span>
                                    </option>
                                @endforeach
                            </select>
                            @error('violation_cat_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Violation Date --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Violation Date <span class="text-danger">*</span></label>
                            <input type="date" name="violation_date"
                                class="form-control @error('violation_date') is-invalid @enderror"
                                value="{{ old('violation_date', date('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}" required>
                            @error('violation_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Reason --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Violation Description <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="4"
                                class="form-control @error('reason') is-invalid @enderror"
                                placeholder="Describe the violation in detail..."
                                required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Template --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Letter Template</label>
                            <select name="template_id" class="form-select @error('template_id') is-invalid @enderror">
                                <option value="">-- Use default template based on SP level --</option>
                                @foreach($templates as $tmpl)
                                    <option value="{{ $tmpl->id }}"
                                        {{ old('template_id') == $tmpl->id ? 'selected' : '' }}>
                                        SP{{ $tmpl->sp_level }} — {{ $tmpl->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('warning-letters.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            @if(!$terminationFlag)
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save as Draft
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    $('.select2-employee').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Search or select employee --',
        allowClear: true,
    });

    // Auto-reload page when employee is selected to update SP suggestion
    $('#employeeSelect').on('change', function () {
        const val = $(this).val();
        if (val) {
            window.location.href = '{{ route('warning-letters.create') }}?employee_id=' + val;
        }
    });
});
</script>
@endpush
