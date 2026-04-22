@extends('layouts.app')
@section('title', 'Edit Timing — Approval')

@section('content')
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="mb-0 fw-semibold">Edit Timing</h5>
            <small class="text-muted">ID #{{ $timing->id }} &mdash; Status:
                <span class="badge {{ $timing->isPending() ? 'bg-warning text-dark' : ($timing->isApproved() ? 'bg-success' : 'bg-danger') }}">
                    {{ ucfirst($timing->approval_status) }}
                </span>
            </small>
        </div>
        <a href="{{ route('timing-approval.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- LEFT: Edit Form --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('timing-approval.update', $timing->id) }}" method="POST" id="editForm">
                        @csrf @method('PUT')

                        {{-- Row 1: Date + Employee --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal') is-invalid @enderror"
                                    name="tanggal" value="{{ old('tanggal', $timing->tanggal?->format('Y-m-d')) }}" required>
                                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-medium">Employee(s) <span class="text-danger">*</span></label>
                                <select class="form-select select2-multi @error('employee_ids') is-invalid @enderror"
                                    name="employee_ids[]" multiple required>
                                    @foreach ($employees as $emp)
                                        <option value="{{ $emp->id }}"
                                            {{ in_array($emp->id, old('employee_ids', [$timing->employee_id])) ? 'selected' : '' }}>
                                            {{ $emp->name }} — {{ $emp->department->name ?? '?' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Multiple = create copies for each additional employee.</small>
                                @error('employee_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Row 2: Project + Job Order --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Project <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('project_id') is-invalid @enderror" name="project_id" required>
                                    <option value="">Select Project</option>
                                    @foreach ($projects as $p)
                                        <option value="{{ $p->id }}" {{ old('project_id', $timing->project_id) == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Job Order</label>
                                <select class="form-select select2 @error('job_order_id') is-invalid @enderror" name="job_order_id">
                                    <option value="">Select Job Order</option>
                                    @foreach ($jobOrders as $jo)
                                        <option value="{{ $jo->id }}" {{ old('job_order_id', $timing->job_order_id) == $jo->id ? 'selected' : '' }}>
                                            {{ $jo->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('job_order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Row 3: Step + Parts --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Step / Process</label>
                                <input type="text" class="form-control @error('step') is-invalid @enderror"
                                    name="step" value="{{ old('step', $timing->step) }}" placeholder="e.g. Cutting, Sewing">
                                @error('step')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Parts / Components</label>
                                <input type="text" class="form-control @error('parts') is-invalid @enderror"
                                    name="parts" value="{{ old('parts', $timing->parts) }}" placeholder="e.g. Head, Body">
                                @error('parts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Row 4: Time + Break --}}
                        @php
                            // Pre-fill break: use previously saved break_deducted_minutes if set,
                            // otherwise fall back to total_paused_minutes from app pause cycles
                            $existingBreak = old('break_deducted_minutes',
                                ($timing->break_deducted_minutes > 0)
                                    ? $timing->break_deducted_minutes
                                    : ($timing->total_paused_minutes ?? 0)
                            );
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                                    id="start_time" name="start_time"
                                    value="{{ old('start_time', $timing->start_time ? \Carbon\Carbon::parse($timing->start_time)->format('H:i') : '') }}" required>
                                @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('end_time') is-invalid @enderror"
                                    id="end_time" name="end_time"
                                    value="{{ old('end_time', $timing->end_time ? \Carbon\Carbon::parse($timing->end_time)->format('H:i') : '') }}" required>
                                @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Break (menit)</label>
                                <div class="input-group">
                                    <input type="number" min="0" max="480"
                                        class="form-control @error('break_deducted_minutes') is-invalid @enderror"
                                        id="break_minutes" name="break_deducted_minutes"
                                        value="{{ $existingBreak }}"
                                        placeholder="0">
                                    <span class="input-group-text">min</span>
                                </div>
                                @if($timing->total_paused_minutes > 0 && $timing->break_deducted_minutes == 0)
                                    <small class="text-info">
                                        <i class="bi bi-info-circle me-1"></i>Pre-filled dari pause app ({{ $timing->total_paused_minutes }} min)
                                    </small>
                                @else
                                    <small class="text-muted">Dikurangi dari durasi total</small>
                                @endif
                                @error('break_deducted_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Duration Preview --}}
                        <div class="rounded-2 px-3 py-2 mb-3" style="background:#f0f9ff; border:1px solid #bae6fd;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-stopwatch text-primary"></i>
                                <div>
                                    <span class="text-muted small">Durasi bersih:</span>
                                    <strong id="duration-preview" class="ms-1 text-primary">—</strong>
                                </div>
                                <div class="text-muted small" id="duration-breakdown"></div>
                            </div>
                        </div>

                        {{-- Row 5: Output --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Output / Qty</label>
                                <input type="number" step="0.01" min="0" class="form-control @error('measurement_value') is-invalid @enderror"
                                    name="measurement_value" value="{{ old('measurement_value', $timing->measurement_value) }}"
                                    placeholder="e.g. 10">
                                @error('measurement_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Unit Type</label>
                                <input type="text" class="form-control @error('measurement_type') is-invalid @enderror"
                                    name="measurement_type" value="{{ old('measurement_type', $timing->measurement_type) }}"
                                    placeholder="e.g. pcs, kg">
                                @error('measurement_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div class="mb-4">
                            <label class="form-label fw-medium">Remarks</label>
                            <textarea class="form-control @error('remarks') is-invalid @enderror"
                                name="remarks" rows="2" placeholder="Additional notes...">{{ old('remarks', $timing->remarks) }}</textarea>
                            @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('timing-approval.index') }}" class="btn btn-outline-secondary rounded-2">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-4">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT: Sidebar --}}
        <div class="col-lg-4">

            {{-- Current Info Card --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-bottom fw-semibold" style="font-size:.85rem;">
                    <i class="bi bi-info-circle me-2 text-primary"></i>Current Record
                </div>
                <div class="card-body p-3">
                    <dl class="mb-0" style="font-size:.82rem;">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <dt class="text-muted fw-normal">Duration</dt>
                            <dd class="fw-semibold mb-0">{{ $timing->duration_formatted ?? 'N/A' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <dt class="text-muted fw-normal">Employee</dt>
                            <dd class="mb-0">{{ $timing->employee->name ?? '—' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <dt class="text-muted fw-normal">Department</dt>
                            <dd class="mb-0">{{ $timing->employee->department->name ?? '—' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <dt class="text-muted fw-normal">Project</dt>
                            <dd class="mb-0">{{ $timing->project->name ?? '—' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <dt class="text-muted fw-normal">Date</dt>
                            <dd class="mb-0">{{ $timing->tanggal?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <dt class="text-muted fw-normal">Created</dt>
                            <dd class="mb-0">{{ $timing->created_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Approval Info --}}
            @if($timing->approved_by)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-bottom fw-semibold" style="font-size:.85rem;">
                    <i class="bi bi-person-check me-2 text-success"></i>Approval Info
                </div>
                <div class="card-body p-3" style="font-size:.82rem;">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">By</span>
                        <span>{{ $timing->approver->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">At</span>
                        <span>{{ $timing->approved_at?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                    @if($timing->rejection_reason)
                    <div class="mt-2 p-2 rounded bg-danger bg-opacity-10 text-danger" style="font-size:.78rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $timing->rejection_reason }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Break Info --}}
            <div class="card border-0 shadow-sm" style="border-left:3px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <p class="mb-1 fw-semibold text-warning" style="font-size:.82rem;"><i class="bi bi-cup-hot me-1"></i>Break Deduction</p>
                    <p class="text-muted mb-0" style="font-size:.78rem;">
                        Masukkan durasi istirahat (menit) yang akan dikurangi dari total waktu kerja.
                        Durasi bersih = End − Start − Break.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(function () {
        $('.select2').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: 'Select...' });
        $('#employee_ids').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: 'Select employee(s)...' });

        function updateDuration() {
            const s = $('#start_time').val(), e = $('#end_time').val(), b = parseInt($('#break_minutes').val()) || 0;
            if (!s || !e) return;
            const start = new Date('2000-01-01T' + s), end = new Date('2000-01-01T' + e);
            const raw = (end - start) / 60000;
            if (raw <= 0) { $('#duration-preview').text('—'); $('#duration-breakdown').text(''); return; }
            const net = Math.max(0, raw - b);
            const h = Math.floor(net / 60), m = Math.round(net % 60);
            $('#duration-preview').text(h > 0 ? h + 'h ' + m + 'm' : m + 'm');
            $('#duration-breakdown').text(b > 0 ? '(' + Math.floor(raw/60) + 'h ' + Math.round(raw%60) + 'm gross − ' + b + 'm break)' : '');
        }

        $('#start_time, #end_time, #break_minutes').on('input change', updateDuration);
        updateDuration();
    });
    </script>
@endpush
