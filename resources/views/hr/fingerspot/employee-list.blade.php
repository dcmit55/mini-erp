@extends('layouts.app')

@section('title', 'Device Employee List - Fingerspot')

@section('content')
<div class="container-fluid py-4">

    {{-- Header with Back Button --}}
    <div class="mb-4">
        <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    {{-- Title Section --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-primary rounded-3">
                    <i class="fas fa-users text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">Device Employee List</h4>
                    <p class="text-muted mb-0">Employees who have been registered and scanned on the fingerprint device</p>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('fingerspot.register-employee.form') }}" class="btn btn-primary btn-sm rounded-2 px-4">
                <i class="fas fa-user-plus me-2"></i>Add Employee to Device
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-soft-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-soft-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
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
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small">Token API:</span>
                            <span class="badge {{ config('fingerspot.api_token') ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }} fw-normal">
                                {{ config('fingerspot.api_token') ? 'Configured' : 'Not set' }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-auto">
                            <span class="text-secondary small">Total registered:</span>
                            <span class="badge bg-soft-primary text-primary fw-normal">
                                {{ $employees->total() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="card border-0 shadow-xs mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('fingerspot.employee-list.form') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0"
                                   placeholder="Search by Employee ID or Name..."
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">Search</button>
                            @if(request()->filled('search'))
                                <a href="{{ route('fingerspot.employee-list.form') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-xs">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="employeeListTable">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 ps-4" style="width:60px;">#</th>
                            <th class="border-0">Employee ID</th>
                            <th class="border-0">Name</th>
                            <th class="border-0">Device PIN</th>
                            <th class="border-0">Last Scan</th>
                            <th class="border-0 text-center">Total Scans</th>
                            <th class="border-0 text-center" style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr class="align-middle">
                                <td class="ps-4">
                                    <span class="row-number">
                                        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark">{{ $employee->employee_no }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle bg-soft-primary text-primary fw-semibold">
                                            {{ strtoupper(substr($employee->name, 0, 1)) }}
                                        </div>
                                        <span>{{ $employee->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-3 py-1 font-monospace">
                                        {{ $employee->device_pin }}
                                    </span>
                                </td>
                                <td>
                                    @if($employee->last_scan)
                                        <span class="text-dark">
                                            {{ \Carbon\Carbon::parse($employee->last_scan)->format('d M Y') }}
                                        </span>
                                        <br>
                                        <span class="text-muted small">
                                            {{ \Carbon\Carbon::parse($employee->last_scan)->format('H:i') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-soft-success text-success rounded-pill px-3 py-1 fw-semibold">
                                        {{ number_format($employee->total_scans) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        {{-- Register Biometric --}}
                                        <a href="{{ route('fingerspot.register-biometric.form') }}"
                                           class="btn btn-sm btn-outline-primary border-0 px-2 py-1 action-btn"
                                           data-bs-toggle="tooltip" title="Register Biometric">
                                            <i class="fas fa-fingerprint"></i>
                                        </a>
                                        {{-- Delete from Device --}}
                                        <form action="{{ route('fingerspot.delete-employee') }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Remove {{ addslashes($employee->name) }} (PIN: {{ $employee->device_pin }}) from the device?')">
                                            @csrf
                                            <input type="hidden" name="cloud_id" value="{{ $defaultDeviceId }}">
                                            <input type="hidden" name="pin" value="{{ $employee->device_pin }}">
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger border-0 px-2 py-1 action-btn"
                                                    data-bs-toggle="tooltip" title="Remove from Device">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-fingerprint fa-3x mb-3 d-block"></i>
                                        @if(request()->filled('search'))
                                            <h5>No results for "{{ request('search') }}"</h5>
                                            <p class="mb-0 small">Try a different Employee ID or name.</p>
                                            <a href="{{ route('fingerspot.employee-list.form') }}"
                                               class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                <i class="fas fa-times me-1"></i>Clear Filter
                                            </a>
                                        @else
                                            <h5>No employees registered on the device yet</h5>
                                            <p class="mb-0 small">Employees will appear here once they scan for the first time.</p>
                                            <a href="{{ route('fingerspot.register-employee.form') }}"
                                               class="btn btn-primary btn-sm rounded-pill px-4 mt-3">
                                                <i class="fas fa-user-plus me-1"></i>Add Employee to Device
                                            </a>
                                        @endif
                                    </div>
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
                    Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }}
                    of {{ $employees->total() }} registered employees
                </div>
                <div>
                    {{ $employees->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    /* Custom Styles from the reference */
    .icon-shape {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
    }
    
    .icon-shape.icon-lg {
        width: 56px;
        height: 56px;
    }
    
    .bg-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .bg-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    .bg-soft-dark {
        background-color: rgba(33, 37, 41, 0.1);
    }
    
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
    
    .shadow-xs {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    /* Original styles */
    .row-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background-color: #eef2ff;
        color: #4f46e5;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
    }

    tr:hover .row-number {
        background-color: #4f46e5;
        color: #fff;
        transform: scale(1.05);
    }

    .avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 0.9rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .table td {
        padding: 0.85rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }

    .table tbody tr { transition: background 0.15s; }
    .table tbody tr:hover { background-color: #f8fafc; }

    .action-btn {
        border-radius: 6px;
        transition: all 0.2s;
        font-size: 0.85rem;
    }

    .action-btn:hover { transform: translateY(-1px); }

    .badge.bg-light {
        background-color: #f8fafc !important;
        border-color: #e2e8f0 !important;
        font-weight: 500;
    }

    .font-monospace { font-family: monospace; }

    @media (max-width: 768px) {
        .table thead { display: none; }
        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }
        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
        }
        .table tbody td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.7rem;
            min-width: 110px;
        }
        .action-btn { width: 100%; text-align: center; margin-top: 0.25rem; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // Responsive data-label
    const headers = document.querySelectorAll('#employeeListTable thead th');
    headers.forEach((th, i) => {
        const label = th.textContent.trim();
        if (label) {
            document.querySelectorAll(`#employeeListTable tbody td:nth-child(${i + 1})`).forEach(td => {
                td.setAttribute('data-label', label);
            });
        }
    });

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert .btn-close').forEach(btn => btn.click());
    }, 5000);
});
</script>
@endpush