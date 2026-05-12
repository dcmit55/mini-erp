@extends('layouts.app')

@section('title', 'Data Export')

@section('content')
<div class="container-fluid py-4">

    {{-- Header Section --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-success rounded-3">
                    <i class="fas fa-file-export text-success fs-4"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-semibold">Data Export</h6>
                    <p class="text-muted mb-0 small">Export employee data to Excel format for import into the Symcore system</p>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('hr.record') }}" class="btn btn-sm btn-outline-secondary px-3">
                <i class="fas fa-arrow-left me-1"></i><span class="d-none d-sm-inline">Back</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-soft-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-soft-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Filter Section --}}
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <span class="text-uppercase fw-semibold small tracking-wide">Export Settings</span>
        </div>

        <div class="card border-0 shadow-xs">
            <div class="card-body p-3" style="font-size:0.8125rem;">
                <form action="{{ route('symcore-export.export') }}" method="GET">

                    {{-- Mode toggle --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase text-secondary">Filter Mode</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="filter_mode" id="mode_month" value="month" checked>
                                <label class="form-check-label" for="mode_month">By Month</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="filter_mode" id="mode_range" value="range">
                                <label class="form-check-label" for="mode_range">Date Range</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Per Month --}}
                        <div id="section_month" class="col-12">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-uppercase text-secondary">Month</label>
                                    <select name="month" class="form-select form-select-sm rounded-2">
                                        @foreach(range(1,12) as $m)
                                            <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::create(null, $m)->format('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-uppercase text-secondary">Year</label>
                                    <select name="year" class="form-select form-select-sm rounded-2">
                                        @foreach(range(now()->year, 2020) as $y)
                                            <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Date Range --}}
                        <div id="section_range" class="col-12" style="display:none;">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-uppercase text-secondary">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm rounded-2"
                                           value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-uppercase text-secondary">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm rounded-2"
                                           value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Department --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-uppercase text-secondary">Department</label>
                            <select name="department_id" class="form-select form-select-sm rounded-2">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success btn-sm px-4 py-2 fw-semibold rounded-2">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Columns Info --}}
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <span class="text-uppercase fw-semibold small tracking-wide">Export Columns</span>
        </div>

        <div class="row g-3">
            @php
                $columns = [
                    ['OT Hours',        'Total net hours from HR-approved overtime requests',          'fas fa-hourglass-half', 'primary'],
                    ['OT Type',         'OT code: Weekday / Sunday / Public Holiday',                  'fas fa-tag',           'info'],
                    ['Late Days',       'Days late between 1–60 minutes from daily attendance',        'fas fa-clock',         'warning'],
                    ['Leave Hours',     'Hours from late >60 minutes + early clock-out',               'fas fa-door-open',     'warning'],
                    ['Leave Days',      'Total days from approved leave requests',                      'fas fa-calendar-minus','secondary'],
                    ['Absence Days',    'Days with absent status from daily attendance',               'fas fa-user-slash',    'danger'],
                    ['Leave Balance',   'Employee current leave balance',                              'fas fa-wallet',        'success'],
                    ['Kasbon',          'Not available yet — column left empty',                       'fas fa-money-bill',    'secondary'],
                    ['Uniform Deposit', 'Not available yet — column left empty',                       'fas fa-tshirt',        'secondary'],
                ];
            @endphp

            @foreach($columns as $col)
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-xs h-100">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-shape icon-sm bg-soft-{{ $col[3] }} rounded-2">
                                <i class="{{ $col[2] }} text-{{ $col[3] }} small"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark" style="font-size:0.8rem;">{{ $col[0] }}</div>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $col[1] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

<script>
document.querySelectorAll('input[name="filter_mode"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.getElementById('section_month').style.display = this.value === 'month' ? '' : 'none';
        document.getElementById('section_range').style.display = this.value === 'range' ? '' : 'none';
    });
});
</script>
@endsection

@push('styles')
<style>
    .icon-shape {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
    }
    .icon-shape.icon-lg { width: 44px; height: 44px; }
    .icon-shape.icon-md { width: 36px; height: 36px; }
    .icon-shape.icon-sm { width: 28px; height: 28px; }

    .bg-soft-primary   { background-color: rgba(13, 110, 253, 0.1); }
    .bg-soft-success   { background-color: rgba(25, 135, 84, 0.1); }
    .bg-soft-danger    { background-color: rgba(220, 53, 69, 0.1); }
    .bg-soft-warning   { background-color: rgba(255, 193, 7, 0.1); }
    .bg-soft-info      { background-color: rgba(13, 202, 240, 0.1); }
    .bg-soft-secondary { background-color: rgba(108, 117, 125, 0.1); }

    .alert-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #0f5132;
        border: none;
    }
    .alert-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #842029;
        border: none;
    }

    .shadow-xs { box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }

    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }

    .tracking-wide { letter-spacing: 0.5px; }

    .line {
        width: 4px;
        height: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
    }
</style>
@endpush
