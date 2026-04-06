@extends('layouts.app')

@section('title', 'Leave Request List')

@section('content')
<div class="container-fluid py-3">
    <div class="col-12">

        {{-- Header --}}
        <div class="position-relative d-flex align-items-center mb-3" style="min-height:44px;">
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="fw-semibold text-dark">Leave Requests</span>
            </div>
            <div class="position-absolute start-50 translate-middle-x text-center d-none d-md-block" style="pointer-events:none;">
                <h5 class="text-dark fw-semibold mb-0">Leave Request List</h5>
            </div>
            <div class="ms-auto flex-shrink-0">
                <a href="{{ route('leave_requests.create') }}" class="btn btn-primary btn-sm rounded-2 px-3">
                    Add Leave Request
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
            {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Filters --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('leave_requests.index') }}" class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Employee</label>
                        <select name="employee_id" class="form-select form-select-sm border-1 rounded-2">
                            <option value="">All</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Department</label>
                        <select name="department_id" class="form-select form-select-sm border-1 rounded-2">
                            <option value="">All</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Leave Type</label>
                        <select name="type" class="form-select form-select-sm border-1 rounded-2">
                            <option value="">All</option>
                            @foreach($leaveTypeLabels as $val => $label)
                                <option value="{{ $val }}" {{ request('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Status</label>
                        <select name="approval_status" class="form-select form-select-sm border-1 rounded-2">
                            <option value="">All</option>
                            <option value="both_approved" {{ request('approval_status') == 'both_approved' ? 'selected' : '' }}>Both Approved</option>
                            <option value="pending"       {{ request('approval_status') == 'pending'       ? 'selected' : '' }}>Pending</option>
                            <option value="rejected"      {{ request('approval_status') == 'rejected'      ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-dark mb-1">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm border-1 rounded-2"
                               placeholder="Name, reason..." value="{{ request('search') }}">
                    </div>
                    <div class="col-6 col-md-1 d-flex align-items-end">
                        <div class="d-flex gap-1 w-100">
                            <button type="submit" class="btn btn-primary btn-sm rounded-2 flex-fill">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="{{ route('leave_requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table (desktop) --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden d-none d-md-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.875rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center" style="width:44px;">No</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Employee</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Department</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Leave Type</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Period</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Duration</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">HR</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Director</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Submitted</th>
                                @if($isAuthenticated && in_array($userRole, ['super_admin', 'admin_hr']))
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-end" style="width:100px;">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @if($leaves->isEmpty())
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="fas fa-folder-open fa-2x text-muted mb-2 d-block"></i>
                                    <span class="text-muted small">No leave requests found.</span>
                                </td>
                            </tr>
                            @else
                            @php $startNumber = ($leaves->currentPage() - 1) * $leaves->perPage() + 1; @endphp
                            @foreach($leaves as $i => $leave)
                            <tr>
                                <td class="px-3 py-2 text-center text-muted">{{ $startNumber + $i }}</td>
                                <td class="px-3 py-2">
                                    <div class="fw-medium">{{ $leave->employee->name ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-2 text-muted small">{{ $leave->employee->department->name ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <x-leave-type-badge :type="$leave->type" :labels="$leaveTypeLabels" />
                                </td>
                                <td class="px-3 py-2 small" style="white-space:nowrap;">
                                    <span>{{ $leave->start_date?->format('d/m/Y') ?? '-' }}</span>
                                    @if($leave->end_date && $leave->end_date != $leave->start_date)
                                        <span class="text-muted"> – {{ $leave->end_date->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ rtrim(rtrim(number_format($leave->duration, 2, '.', ''), '0'), '.') }} hari
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @php $c1 = $leave->approval_1 === 'approved' ? 'success' : ($leave->approval_1 === 'rejected' ? 'danger' : 'warning'); @endphp
                                    <span class="badge bg-{{ $c1 }} {{ $c1 === 'warning' ? 'text-dark' : '' }}">{{ ucfirst($leave->approval_1) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @php $c2 = $leave->approval_2 === 'approved' ? 'success' : ($leave->approval_2 === 'rejected' ? 'danger' : 'warning'); @endphp
                                    <span class="badge bg-{{ $c2 }} {{ $c2 === 'warning' ? 'text-dark' : '' }}">{{ ucfirst($leave->approval_2) }}</span>
                                </td>
                                <td class="px-3 py-2 text-muted small" style="white-space:nowrap;">
                                    {{ $leave->created_at->format('d/m/Y H:i') }}
                                </td>
                                @if($isAuthenticated && in_array($userRole, ['super_admin', 'admin_hr']))
                                <td class="px-3 py-2 text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('leave_requests.show', $leave->id) }}" class="btn btn-outline-info btn-sm rounded-2 px-2 py-1" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('leave_requests.edit', $leave->id) }}" class="btn btn-outline-warning btn-sm rounded-2 px-2 py-1" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <form action="{{ route('leave_requests.destroy', $leave->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete leave request for {{ addslashes($leave->employee->name ?? '') }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                @if(!$leaves->isEmpty() && $leaves->hasPages())
                <div class="card-footer border-0 bg-light px-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $leaves->firstItem() }}–{{ $leaves->lastItem() }} of {{ $leaves->total() }}
                        </div>
                        {{ $leaves->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Cards (mobile) --}}
        <div class="d-md-none">
            @if($leaves->isEmpty())
            <div class="card border-0 shadow-sm rounded-3 text-center py-5">
                <i class="fas fa-folder-open fa-2x text-muted mb-2 d-block"></i>
                <span class="text-muted small">No leave requests found.</span>
            </div>
            @else
            @foreach($leaves as $leave)
            <div class="card border-0 shadow-sm rounded-3 mb-2">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-medium">{{ $leave->employee->name ?? '-' }}</div>
                            <div class="text-muted small">{{ $leave->employee->department->name ?? '' }}</div>
                        </div>
                        <x-leave-type-badge :type="$leave->type" :labels="$leaveTypeLabels" />
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center" style="font-size:0.82rem;">
                        <span class="text-muted">{{ $leave->start_date?->format('d/m/Y') }}@if($leave->end_date && $leave->end_date != $leave->start_date) – {{ $leave->end_date->format('d/m/Y') }}@endif</span>
                        <span>{{ rtrim(rtrim(number_format($leave->duration, 2, '.', ''), '0'), '.') }} hari</span>
                        @php $c1 = $leave->approval_1 === 'approved' ? 'success' : ($leave->approval_1 === 'rejected' ? 'danger' : 'warning'); @endphp
                        @php $c2 = $leave->approval_2 === 'approved' ? 'success' : ($leave->approval_2 === 'rejected' ? 'danger' : 'warning'); @endphp
                        <span class="badge bg-{{ $c1 }} {{ $c1 === 'warning' ? 'text-dark' : '' }}">HR: {{ ucfirst($leave->approval_1) }}</span>
                        <span class="badge bg-{{ $c2 }} {{ $c2 === 'warning' ? 'text-dark' : '' }}">Dir: {{ ucfirst($leave->approval_2) }}</span>
                    </div>
                    @if($isAuthenticated && in_array($userRole, ['super_admin', 'admin_hr']))
                    <div class="d-flex gap-2">
                        <a href="{{ route('leave_requests.show', $leave->id) }}" class="btn btn-outline-info btn-sm rounded-2 px-2">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('leave_requests.edit', $leave->id) }}" class="btn btn-outline-warning btn-sm rounded-2 px-2">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <form action="{{ route('leave_requests.destroy', $leave->id) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-2 px-2">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            @if($leaves->hasPages())
            <div class="py-2">{{ $leaves->appends(request()->query())->links() }}</div>
            @endif
            @endif
        </div>

    </div>
</div>

<style>
    .table td { vertical-align: middle; }
    .badge { font-weight: 500; }
</style>
@endsection
