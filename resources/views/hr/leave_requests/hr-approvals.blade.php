@extends('layouts.app')

@section('title', 'HR Leave Approvals')

@section('content')
<div class="container-fluid py-3">
    <div class="col-12">

        <!-- Header -->
        <div class="position-relative d-flex align-items-center mb-3" style="min-height:44px;">
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @php
                    $level3Matrix  = \App\Models\Hr\ApprovalMatrix::where('module', 'leave')->where('level', 3)->first();
                    $directorRoles = $level3Matrix ? array_merge($level3Matrix->getAllowedRoles(), ['super_admin']) : ['director', 'admin_hr', 'super_admin'];
                @endphp
                <a href="{{ route('leave_requests.hr-approvals') }}" class="btn btn-primary btn-sm rounded-2 px-3 position-relative">
                    <i class="fas fa-user-check me-1"></i> HR
                    @if($stats['total_pending'] > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;min-width:1.4em;padding:.25em .45em;">
                        {{ $stats['total_pending'] > 99 ? '99+' : $stats['total_pending'] }}
                    </span>
                    @endif
                </a>
                @if(in_array(auth()->user()->role, $directorRoles))
                <a href="{{ route('leave_requests.director-approvals') }}" class="btn btn-outline-primary btn-sm rounded-2 px-3 position-relative">
                    <i class="fas fa-user-tie me-1"></i> Director
                    @if($directorPendingCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;min-width:1.4em;padding:.25em .45em;">
                        {{ $directorPendingCount > 99 ? '99+' : $directorPendingCount }}
                    </span>
                    @endif
                </a>
                @endif
            </div>
            <div class="position-absolute start-50 translate-middle-x text-center d-none d-md-block" style="pointer-events:none;">
                <h5 class="text-dark fw-semibold mb-0">Leave Approvals</h5>
            </div>
            <div class="ms-auto flex-shrink-0">
                <a href="{{ route('leave_requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-list me-1"></i><span class="d-none d-sm-inline"> All Leave Requests</span>
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

        <!-- Filters -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('leave_requests.hr-approvals') }}" class="row g-2">
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
                        <label class="form-label small text-dark mb-1">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm border-1 rounded-2" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm border-1 rounded-2" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-6 col-md-2 d-flex align-items-end">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm rounded-2 px-2">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="{{ route('leave_requests.hr-approvals') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table (desktop) -->
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
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Doc</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Days</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-end" style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($leaves->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                                    <span class="text-muted small">No pending HR approvals.</span>
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
                                <td class="px-3 py-2">
                                    <div>{{ $leave->start_date?->format('d/m/Y') ?? '-' }}</div>
                                    @if($leave->end_date && $leave->end_date != $leave->start_date)
                                        <div class="text-muted small">s/d {{ $leave->end_date->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ rtrim(rtrim(number_format($leave->duration, 2, '.', ''), '0'), '.') }} hari
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if($leave->mc_document || $leave->doctor_letter)
                                        <span class="badge rounded-2 px-2 py-1 bg-success bg-opacity-10 text-success border border-success border-opacity-25">Uploaded</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @php $daysPending = $leave->created_at->diffInDays(now()); @endphp
                                    <span class="badge rounded-2 px-2 py-1
                                        bg-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} bg-opacity-10
                                        text-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }}
                                        border border-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} border-opacity-25">
                                        {{ $daysPending }}d
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('leave_requests.show', $leave->id) }}" class="btn btn-outline-info btn-sm rounded-2 px-2 py-1" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('leave_requests.updateApproval', $leave->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve?')">
                                            @csrf
                                            <input type="hidden" name="approval_1" value="approved">
                                            <button type="submit" class="btn btn-outline-success btn-sm rounded-2 px-2 py-1" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1 reject-btn"
                                                data-id="{{ $leave->id }}"
                                                data-employee="{{ $leave->employee->name ?? '' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rejectModal"
                                                title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
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

        <!-- Cards (mobile) -->
        <div class="d-md-none">
            @if($leaves->isEmpty())
            <div class="card border-0 shadow-sm rounded-3 text-center py-5">
                <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                <span class="text-muted small">No pending HR approvals.</span>
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
                        @php $daysPending = $leave->created_at->diffInDays(now()); @endphp
                        <span class="badge rounded-2 px-2 py-1
                            bg-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} bg-opacity-10
                            text-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }}
                            border border-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} border-opacity-25">
                            {{ $daysPending }}d
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center" style="font-size:0.82rem;">
                        <x-leave-type-badge :type="$leave->type" :labels="$leaveTypeLabels" />
                        <span class="text-muted">{{ $leave->start_date?->format('d/m/Y') }}@if($leave->end_date && $leave->end_date != $leave->start_date) – {{ $leave->end_date->format('d/m/Y') }}@endif</span>
                        <span>{{ rtrim(rtrim(number_format($leave->duration, 2, '.', ''), '0'), '.') }} hari</span>
                        @if($leave->mc_document || $leave->doctor_letter)
                            <span class="badge bg-success bg-opacity-15 text-success fw-normal">File</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('leave_requests.show', $leave->id) }}" class="btn btn-outline-info btn-sm rounded-2 px-2">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form action="{{ route('leave_requests.updateApproval', $leave->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve?')">
                            @csrf
                            <input type="hidden" name="approval_1" value="approved">
                            <button type="submit" class="btn btn-outline-success btn-sm rounded-2 px-3">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2 px-2 reject-btn"
                                data-id="{{ $leave->id }}"
                                data-employee="{{ $leave->employee->name ?? '' }}"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
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

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <input type="hidden" name="approval_1" value="rejected">
                <div class="modal-body">
                    <p class="mb-2">Reject leave request for <span id="rejectEmployeeName" class="fw-bold"></span>?</p>
                    <div class="mb-3">
                        <label class="form-label small">Reason (Optional)</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.reject-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('rejectEmployeeName').textContent = this.dataset.employee;
            document.getElementById('rejectForm').action = '{{ url("leave_requests") }}/' + this.dataset.id + '/approval';
        });
    });
});
</script>

<style>
    .table td { vertical-align: middle; }
    .badge { font-weight: 500; }
</style>
@endsection
