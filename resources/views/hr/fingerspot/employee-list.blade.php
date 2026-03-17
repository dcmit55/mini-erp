@extends('layouts.app')

@section('title', 'Device Employee List - Fingerspot')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-primary rounded-3">
                    <i class="fas fa-users text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">Device Employee List</h4>
                    <p class="text-muted mb-0">All employees — device registration and biometric status</p>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('fingerspot.register-employee.form') }}" class="btn btn-primary btn-sm rounded-2 px-4">
                <i class="fas fa-user-plus me-2"></i>Register Employee to Device
            </a>
        </div>
    </div>

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
    @if(session('info'))
    <div class="alert alert-soft-info alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs h-100">
                <div class="card-body py-3 px-4">
                    <div class="text-secondary small mb-1">Active Employees</div>
                    <div class="fw-bold fs-4">{{ $totalActive }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs h-100">
                <div class="card-body py-3 px-4">
                    <div class="text-secondary small mb-1">On Device</div>
                    <div class="fw-bold fs-4 text-success">{{ $totalOnDevice }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs h-100">
                <div class="card-body py-3 px-4">
                    <div class="text-secondary small mb-1">Not Registered</div>
                    <div class="fw-bold fs-4 text-secondary">{{ $totalActive - $totalOnDevice }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs h-100">
                <div class="card-body py-3 px-4">
                    <div class="text-secondary small mb-1">No Biometric</div>
                    <div class="fw-bold fs-4 text-warning">{{ $totalNoBiometric }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter + Search --}}
    <div class="card border-0 shadow-xs mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <form method="GET" action="{{ route('fingerspot.employee-list.form') }}" class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="input-group input-group-sm" style="max-width:300px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0"
                                   placeholder="Search by Employee ID or Name..."
                                   value="{{ request('search') }}">
                        </div>
                        <select name="filter" class="form-select form-select-sm" style="width:auto;">
                            <option value="" {{ request('filter') == '' ? 'selected' : '' }}>All Status</option>
                            <option value="on_device" {{ request('filter') == 'on_device' ? 'selected' : '' }}>On Device</option>
                            <option value="not_registered" {{ request('filter') == 'not_registered' ? 'selected' : '' }}>Not Registered</option>
                            <option value="no_biometric" {{ request('filter') == 'no_biometric' ? 'selected' : '' }}>No Biometric</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        @if(request()->filled('search') || request()->filled('filter'))
                            <a href="{{ route('fingerspot.employee-list.form') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-xs">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="employeeListTable">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 ps-4" style="width:55px;">#</th>
                            <th class="border-0">Employee ID</th>
                            <th class="border-0">Name</th>
                            <th class="border-0 text-center">Device PIN</th>
                            <th class="border-0 text-center">Device Status</th>
                            <th class="border-0 text-center">Biometric</th>
                            <th class="border-0">Last Scan</th>
                            <th class="border-0 text-center">Scans</th>
                            <th class="border-0 text-center" style="width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr class="align-middle {{ !$employee->on_device ? 'row-not-registered' : '' }}">
                                <td class="ps-4">
                                    <span class="row-number">
                                        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                                    </span>
                                </td>
                                <td><span class="fw-medium text-dark">{{ $employee->employee_no }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle {{ $employee->on_device ? 'bg-soft-primary text-primary' : 'bg-soft-secondary text-secondary' }} fw-semibold">
                                            {{ strtoupper(substr($employee->name, 0, 1)) }}
                                        </div>
                                        <span>{{ $employee->name }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($employee->on_device)
                                        <span class="badge bg-light text-dark border px-3 py-1 font-monospace">
                                            {{ $employee->device_pin }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($employee->on_device)
                                        <span class="badge bg-soft-success text-success px-2 py-1">
                                            <i class="fas fa-check-circle me-1"></i>On Device
                                        </span>
                                    @else
                                        <span class="badge bg-soft-danger text-danger px-2 py-1">
                                            <i class="fas fa-times-circle me-1"></i>Not Registered
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!$employee->on_device)
                                        <span class="text-muted small">—</span>
                                    @elseif($employee->biometric_registered)
                                        <span class="badge bg-soft-success text-success px-2 py-1">
                                            <i class="fas fa-fingerprint me-1"></i>Registered
                                        </span>
                                    @else
                                        <span class="badge bg-soft-warning text-warning px-2 py-1">
                                            <i class="fas fa-exclamation-circle me-1"></i>Belum daftar biometric
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($employee->last_scan)
                                        <span class="text-dark">{{ \Carbon\Carbon::parse($employee->last_scan)->format('d M Y') }}</span>
                                        <br><span class="text-muted small">{{ \Carbon\Carbon::parse($employee->last_scan)->format('H:i') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($employee->on_device)
                                        <span class="badge bg-soft-success text-success rounded-pill px-3">
                                            {{ number_format($employee->total_scans) }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        @if(!$employee->on_device)
                                            {{-- Register to Device --}}
                                            <a href="{{ route('fingerspot.register-employee.form') }}?pin={{ $employee->device_pin }}&name={{ urlencode($employee->name) }}"
                                               class="btn btn-sm btn-outline-primary border-0 px-2 py-1 action-btn"
                                               data-bs-toggle="tooltip" title="Register to Device">
                                                <i class="fas fa-plus-circle"></i>
                                            </a>
                                        @else
                                            {{-- Register Biometric --}}
                                            <a href="{{ route('fingerspot.register-biometric.form') }}"
                                               class="btn btn-sm btn-outline-primary border-0 px-2 py-1 action-btn"
                                               data-bs-toggle="tooltip" title="Register Biometric">
                                                <i class="fas fa-fingerprint"></i>
                                            </a>
                                            {{-- Remove from Device --}}
                                            <form action="{{ route('fingerspot.delete-employee') }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Remove {{ addslashes($employee->name) }} (PIN: {{ $employee->device_pin }}) from device?')">
                                                @csrf
                                                <input type="hidden" name="device_id" value="{{ $defaultDeviceId }}">
                                                <input type="hidden" name="pin" value="{{ $employee->device_pin }}">
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger border-0 px-2 py-1 action-btn"
                                                        data-bs-toggle="tooltip" title="Remove from Device">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                    <h5>No employees found</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($employees->hasPages())
        <div class="card-footer bg-white border-0 py-3 px-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }} employees
                </div>
                {{ $employees->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    .icon-shape { display:inline-flex; align-items:center; justify-content:center; }
    .icon-shape.icon-lg { width:56px; height:56px; }
    .bg-soft-primary   { background-color:rgba(13,110,253,0.1); }
    .bg-soft-success   { background-color:rgba(25,135,84,0.1); }
    .bg-soft-danger    { background-color:rgba(220,53,69,0.1); }
    .bg-soft-warning   { background-color:rgba(255,193,7,0.1); }
    .bg-soft-secondary { background-color:rgba(108,117,125,0.1); }
    .bg-soft-dark      { background-color:rgba(33,37,41,0.1); }
    .alert-soft-success { background-color:rgba(25,135,84,0.1); color:#0f5132; border:none; }
    .alert-soft-danger  { background-color:rgba(220,53,69,0.1); color:#842029; border:none; }
    .alert-soft-info    { background-color:rgba(13,202,240,0.1); color:#055160; border:none; }
    .shadow-xs { box-shadow:0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    .row-not-registered { background-color:#fafafa; }
    .row-number {
        display:inline-flex; align-items:center; justify-content:center;
        width:34px; height:34px; background-color:#eef2ff; color:#4f46e5;
        border-radius:8px; font-weight:600; font-size:0.82rem; transition:all 0.2s;
    }
    tr:hover .row-number { background-color:#4f46e5; color:#fff; transform:scale(1.05); }
    .avatar-circle {
        width:32px; height:32px; border-radius:50%;
        display:inline-flex; align-items:center; justify-content:center;
        font-size:0.8rem; flex-shrink:0;
    }
    .table th {
        font-weight:600; font-size:0.75rem; text-transform:uppercase;
        letter-spacing:0.05em; color:#64748b; padding:0.85rem 0.75rem;
        border-bottom:2px solid #e2e8f0; white-space:nowrap;
    }
    .table td { padding:0.75rem 0.75rem; border-bottom:1px solid #f1f5f9; font-size:0.88rem; }
    .table tbody tr { transition:background 0.15s; }
    .table tbody tr:hover { background-color:#f0f7ff; }
    .action-btn { border-radius:6px; transition:all 0.2s; font-size:0.85rem; }
    .action-btn:hover { transform:translateY(-1px); }
    .badge.bg-light { background-color:#f8fafc !important; border-color:#e2e8f0 !important; }
    .font-monospace { font-family:monospace; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    setTimeout(() => {
        document.querySelectorAll('.alert .btn-close').forEach(btn => btn.click());
    }, 5000);
});
</script>
@endpush
