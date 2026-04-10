@extends('layouts.app')
@section('title', isset($shift) ? 'Edit Shift' : 'Add Shift')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-7">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('session-shifts.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">{{ isset($shift) ? 'Edit Shift: ' . $shift->type_of_shift : 'Add New Shift' }}</h5>
                    <p class="text-muted small mb-0">{{ isset($shift) ? 'Update shift schedule and detection settings' : 'Define a new shift with work hours and clock-in detection window' }}</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-4">

                    @if($errors->any())
                        <div class="alert alert-danger border-0 mb-3 p-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span class="fw-medium">Please fix the following errors:</span>
                            </div>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ isset($shift) ? route('session-shifts.update', $shift) : route('session-shifts.store') }}"
                          method="POST">
                        @csrf
                        @if(isset($shift)) @method('PUT') @endif

                        {{-- Basic Info --}}
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-3">
                                <i class="fas fa-layer-group me-2 text-primary"></i>Shift Identity
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label small text-dark">Shift Code <span class="text-danger">*</span></label>
                                    <input type="text" name="type_of_shift"
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('type_of_shift') is-invalid @enderror"
                                           value="{{ old('type_of_shift', $shift->type_of_shift ?? '') }}"
                                           placeholder="e.g. A9, B9, C8" maxlength="10">
                                    @error('type_of_shift')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-5 mb-2">
                                    <label class="form-label small text-dark">Department</label>
                                    <select name="department_id" class="form-select border-1 rounded-2 py-2 px-3">
                                        <option value="">Default (all departments)</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                {{ old('department_id', $shift->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Leave empty to apply as fallback for all departments</small>
                                </div>
                                <div class="col-md-3 mb-2 d-flex align-items-center pt-3">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="for_wna" value="1"
                                               id="for_wna" {{ old('for_wna', $shift->for_wna ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="for_wna">WNA only</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Work Hours --}}
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-3">
                                <i class="fas fa-clock me-2 text-primary"></i>Work Hours
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time"
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('start_time') is-invalid @enderror"
                                           value="{{ old('start_time', isset($shift) ? substr($shift->start_time, 0, 5) : '') }}">
                                    @error('start_time')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">End Time <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time"
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('end_time') is-invalid @enderror"
                                           value="{{ old('end_time', isset($shift) ? substr($shift->end_time, 0, 5) : '') }}">
                                    @error('end_time')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Breaks --}}
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-3">
                                <i class="fas fa-coffee me-2 text-primary"></i>Break Schedule
                                <span class="text-muted fw-normal small ms-1">(optional)</span>
                            </h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-dark">Break 1 — Start</label>
                                    <input type="time" name="break_start" class="form-control border-1 rounded-2 py-2 px-3"
                                           value="{{ old('break_start', isset($shift) ? substr($shift->break_start ?? '', 0, 5) : '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-dark">Break 1 — End</label>
                                    <input type="time" name="break_end" class="form-control border-1 rounded-2 py-2 px-3"
                                           value="{{ old('break_end', isset($shift) ? substr($shift->break_end ?? '', 0, 5) : '') }}">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-dark">Break 2 — Start <span class="text-muted">(for long shifts)</span></label>
                                    <input type="time" name="break2_start" class="form-control border-1 rounded-2 py-2 px-3"
                                           value="{{ old('break2_start', isset($shift) ? substr($shift->break2_start ?? '', 0, 5) : '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-dark">Break 2 — End</label>
                                    <input type="time" name="break2_end" class="form-control border-1 rounded-2 py-2 px-3"
                                           value="{{ old('break2_end', isset($shift) ? substr($shift->break2_end ?? '', 0, 5) : '') }}">
                                </div>
                            </div>
                            @if(isset($shift) && $shift->break2_start)
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-3" id="clearBreak2Btn">
                                        <i class="fas fa-times me-1"></i>Clear Break 2
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Detection Window --}}
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-1">
                                <i class="fas fa-crosshairs me-2 text-primary"></i>Clock-in Detection Window
                            </h6>
                            <p class="text-muted small mb-3">
                                Employees who clock in within this time range are automatically assigned to this shift.
                            </p>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-dark">From <span class="text-danger">*</span></label>
                                    <input type="time" name="detect_from"
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('detect_from') is-invalid @enderror"
                                           value="{{ old('detect_from', isset($shift) ? substr($shift->detect_from, 0, 5) : '') }}">
                                    @error('detect_from')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-dark">Until (exclusive) <span class="text-danger">*</span></label>
                                    <input type="time" name="detect_until"
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('detect_until') is-invalid @enderror"
                                           value="{{ old('detect_until', isset($shift) ? substr($shift->detect_until, 0, 5) : '') }}">
                                    @error('detect_until')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Applicable Days --}}
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-1">
                                <i class="fas fa-calendar-week me-2 text-primary"></i>Applicable Days
                            </h6>
                            <p class="text-muted small mb-3">Biarkan kosong jika berlaku setiap hari.</p>
                            @php
                                $savedDays = old('applicable_days', isset($shift) ? ($shift->applicable_days ?? []) : []);
                                $dayLabels = [1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'];
                            @endphp
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach($dayLabels as $num => $label)
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="applicable_days[]" value="{{ $num }}"
                                           id="day_{{ $num }}"
                                           {{ in_array($num, array_map('intval', $savedDays)) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-medium" for="day_{{ $num }}">{{ $label }}</label>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 px-2 py-1" onclick="setDays([1,2,3,4,5])">
                                    <i class="fas fa-check-double me-1"></i>Sen–Jum
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 px-2 py-1" onclick="setDays([6])">
                                    Sabtu saja
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 px-2 py-1" onclick="setDays([])">
                                    Hapus semua
                                </button>
                            </div>
                        </div>

                        {{-- Position Keywords --}}
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-1">
                                <i class="fas fa-user-tag me-2 text-primary"></i>Position Keywords
                            </h6>
                            <p class="text-muted small mb-2">
                                Filter berdasarkan posisi karyawan. Pisahkan dengan koma. Kosongkan jika berlaku untuk semua posisi.
                            </p>
                            @php
                                $savedKeywords = old('position_keywords',
                                    isset($shift) && $shift->position_keywords
                                        ? implode(', ', $shift->position_keywords)
                                        : ''
                                );
                            @endphp
                            <input type="text" name="position_keywords"
                                   class="form-control border-1 rounded-2 py-2 px-3 @error('position_keywords') is-invalid @enderror"
                                   value="{{ $savedKeywords }}"
                                   placeholder="contoh: operator, sewing">
                            <small class="text-muted">Contoh: <code>operator, sewing</code> — cocok jika posisi karyawan mengandung salah satu kata ini.</small>
                            @error('position_keywords')<div class="invalid-feedback small">{{ $message }}</div>@enderror
                        </div>

                        {{-- Employee Override --}}
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-1">
                                <i class="fas fa-user-lock me-2 text-primary"></i>Shift Khusus per Karyawan
                            </h6>
                            <p class="text-muted small mb-2">
                                Opsional. Jika diisi, shift ini hanya berlaku untuk karyawan tersebut (prioritas tertinggi).
                            </p>
                            <select name="employee_id" class="form-select border-1 rounded-2 py-2 px-3">
                                <option value="">— Tidak spesifik (berlaku umum) —</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}"
                                        {{ old('employee_id', $shift->employee_id ?? '') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                        @if($emp->department) ({{ $emp->department->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Active toggle --}}
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="is_active" {{ old('is_active', $shift->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="is_active">Active (will be used for shift detection)</label>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('session-shifts.index') }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-save me-1"></i>{{ isset($shift) ? 'Update Shift' : 'Save Shift' }}
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
    .form-control.is-invalid, .form-select.is-invalid { border-color: #dc2626; }
    .form-label.small {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
        font-weight: 500;
        color: #374151;
    }
    .btn-primary { background-color: #4f46e5; border-color: #4f46e5; }
    .btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
    h6.fw-medium { color: #334155; font-size: 0.95rem; font-weight: 600; }
    h6.fw-medium i { color: #4f46e5; }
    small.text-muted { font-size: 0.8rem; margin-top: 0.25rem; display: block; }
</style>

<script>
function setDays(days) {
    [1,2,3,4,5,6,7].forEach(function(d) {
        var cb = document.getElementById('day_' + d);
        if (cb) cb.checked = days.includes(d);
    });
}
</script>

@if(isset($shift) && $shift->break2_start)
{{-- Separate form OUTSIDE the main form to avoid nested forms --}}
<form id="clearBreak2Form" action="{{ route('session-shifts.clear-break2', $shift) }}" method="POST" style="display:none;">
    @csrf @method('PATCH')
</form>
<script>
document.getElementById('clearBreak2Btn')?.addEventListener('click', function () {
    if (confirm('Clear Break 2 for shift {{ $shift->type_of_shift }}?')) {
        document.getElementById('clearBreak2Form').submit();
    }
});
</script>
@endif
@endsection
