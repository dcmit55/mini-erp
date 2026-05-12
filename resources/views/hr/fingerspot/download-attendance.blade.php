@extends('layouts.app')

@section('title', 'Download Attendance Report')

@section('content')
<div class="container-fluid py-4">

    {{-- Back Button --}}
    <div class="mb-4">
        <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-success rounded-3">
                    <i class="fas fa-file-excel text-success fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">Download Attendance Report</h4>
                    <p class="text-muted mb-0">Export <code>daily_attendances</code> data to Excel (.xlsx) file</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('error'))
    <div class="alert alert-soft-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-soft-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Configuration Card --}}
    <div class="card border-0 shadow-xs mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <span class="badge bg-soft-info text-info px-3 py-2">
                        <i class="fas fa-info-circle me-1"></i>Configuration
                    </span>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap gap-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small">Device ID:</span>
                            <span class="badge bg-soft-dark fw-normal">
                                {!! $defaultDeviceId ?: '<span class="text-danger">Not set</span>' !!}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h6 class="fw-semibold mb-0">Report Filters</h6>
                    <p class="text-muted small">Select the date range and filters for your attendance report</p>
                </div>
                <div class="card-body p-4">

                    <form method="POST" action="{{ route('fingerspot.download-attendance') }}" id="downloadForm">
                        @csrf

                        {{-- Filter Mode --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Filter By</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode" id="modeRange" value="range" checked>
                                    <label class="form-check-label" for="modeRange">Date Range</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode" id="modeMonth" value="month">
                                    <label class="form-check-label" for="modeMonth">Specific Month</label>
                                </div>
                            </div>
                        </div>

                        {{-- Date Range Section --}}
                        <div id="rangeSection" class="mb-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date"
                                           class="form-control @error('start_date') is-invalid @enderror"
                                           value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                                    @error('start_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="end_date">End Date</label>
                                    <input type="date" id="end_date" name="end_date"
                                           class="form-control @error('end_date') is-invalid @enderror"
                                           value="{{ old('end_date', now()->format('Y-m-d')) }}">
                                    @error('end_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Month Section --}}
                        <div id="monthSection" style="display:none;" class="mb-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="month">Month</label>
                                    <select id="month" name="month" class="form-select @error('month') is-invalid @enderror">
                                        @foreach(range(1,12) as $m)
                                            <option value="{{ $m }}" {{ (old('month', now()->month) == $m) ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('month')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="year">Year</label>
                                    <select id="year" name="year" class="form-select @error('year') is-invalid @enderror">
                                        @foreach(range(now()->year, 2020) as $y)
                                            <option value="{{ $y }}" {{ (old('year', now()->year) == $y) ? 'selected' : '' }}>
                                                {{ $y }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('year')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Department Filter --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="department_id">
                                Department
                            </label>
                            <select id="department_id" name="department_id" class="form-select">
                                <option value="">-- All Departments --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Employee Filter --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="employee_id">
                                Employee
                            </label>
                            <select id="employee_id" name="employee_id" class="form-select">
                                <option value="">-- All Employees on Device --</option>
                                @foreach($deviceEmployees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employee_no }} - {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only employees registered on the fingerprint device are shown</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success px-4 py-2" id="btnDownload" style="width: auto;">
                                <i class="fas fa-download me-2"></i>Download
                            </button>
                        </div>
                    </form>

                </div>
                <div class="card-footer bg-white border-0 px-4 py-3">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="fas fa-clock"></i>
                        <span>Report generation may take a few moments depending on the date range</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    /* Icon Shape */
    .icon-shape {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .icon-shape.icon-lg {
        width: 56px;
        height: 56px;
    }
    
    /* Background Soft Colors */
    .bg-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    .bg-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .bg-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-soft-dark {
        background-color: rgba(33, 37, 41, 0.1);
    }
    
    /* Alert Styles */
    .alert-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #0f5132;
        border: none;
        border-radius: 10px;
    }
    
    .alert-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #842029;
        border: none;
        border-radius: 10px;
    }
    
    .alert-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
        color: #055160;
        border: none;
        border-radius: 10px;
    }
    
    /* Card Shadow */
    .shadow-xs {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    /* Form Controls */
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 0.5rem 0.75rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* Card */
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    
    /* Radio Buttons - Enhanced */
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .form-check-input:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* Download Button - Smaller */
    .btn-success {
        background-color: #28a745;
        border: none;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-success:hover {
        background-color: #218838;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
    }
    
    .btn-success:active {
        background-color: #1e7e34;
        transform: translateY(0);
    }
    
    .btn-success:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
    }
    
    .btn-outline-secondary {
        transition: all 0.2s ease;
    }
    
    .btn-outline-secondary:hover {
        transform: translateX(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .icon-shape.icon-lg {
            width: 48px;
            height: 48px;
        }
        
        .icon-shape.icon-lg i {
            font-size: 1.25rem !important;
        }
        
        h4 {
            font-size: 1.25rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ── Toggle Date Range / Month Section ─────────────────────────────────
    const rangeSection = document.getElementById('rangeSection');
    const monthSection = document.getElementById('monthSection');
    const modeRadios = document.querySelectorAll('input[name="mode"]');

    function toggleSections(mode) {
        if (mode === 'range') {
            rangeSection.style.display = '';
            monthSection.style.display = 'none';
        } else {
            rangeSection.style.display = 'none';
            monthSection.style.display = '';
        }
    }

    modeRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            toggleSections(this.value);
        });
    });

    // Restore mode on validation error
    const savedMode = '{{ old('mode', 'range') }}';
    if (savedMode === 'month') {
        document.getElementById('modeMonth').checked = true;
        toggleSections('month');
    }

    // ── Form Submit Feedback ─────────────────────────────────────────────
    const downloadForm = document.getElementById('downloadForm');
    const downloadBtn = document.getElementById('btnDownload');

    if (downloadForm) {
        downloadForm.addEventListener('submit', function () {
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-download me-2"></i>Download';
            }, 10000);
        });
    }

    // ── Auto-dismiss alerts ─────────────────────────────────────────────
    setTimeout(() => {
        document.querySelectorAll('.alert .btn-close').forEach(btn => {
            if (btn) btn.click();
        });
    }, 5000);

});
</script>
@endpush