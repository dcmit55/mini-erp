@extends('layouts.app')

@section('title', 'Edit Draft — ' . $warningLetter->letter_number)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('warning-letters.show', $warningLetter) }}" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-0 fw-bold">Edit Draft</h4>
                    <small class="text-muted">{{ $warningLetter->letter_number }} — {{ $warningLetter->spLabel }}</small>
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('warning-letters.update', $warningLetter) }}">
                        @csrf @method('PUT')

                        {{-- Employee (read-only) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Employee</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $warningLetter->employee->employee_no }} — {{ $warningLetter->employee->name }}" readonly>
                            <small class="text-muted">Employee cannot be changed after the draft is created.</small>
                        </div>

                        {{-- SP Level (read-only) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">SP Level</label>
                            <input type="text" class="form-control bg-light" value="{{ $warningLetter->spLabel }}" readonly>
                        </div>

                        {{-- Violation Category --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Violation Category <span class="text-danger">*</span></label>
                            <select name="violation_cat_id" class="form-select @error('violation_cat_id') is-invalid @enderror" required>
                                @foreach($violationCategories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('violation_cat_id', $warningLetter->violation_cat_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('violation_cat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Violation Date --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Violation Date <span class="text-danger">*</span></label>
                            <input type="date" name="violation_date"
                                class="form-control @error('violation_date') is-invalid @enderror"
                                value="{{ old('violation_date', $warningLetter->violation_date->format('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}" required>
                            @error('violation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Reason --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Violation Description <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="4"
                                class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason', $warningLetter->reason) }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Template --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Letter Template</label>
                            <select name="template_id" class="form-select">
                                <option value="">-- Default template --</option>
                                @foreach($templates as $tmpl)
                                    <option value="{{ $tmpl->id }}"
                                        {{ old('template_id', $warningLetter->template_id) == $tmpl->id ? 'selected' : '' }}>
                                        SP{{ $tmpl->sp_level }} — {{ $tmpl->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('warning-letters.show', $warningLetter) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
