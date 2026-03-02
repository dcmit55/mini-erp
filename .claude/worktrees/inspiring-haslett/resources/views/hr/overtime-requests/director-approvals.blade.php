@extends('layouts.app')

@section('title', 'Director Overtime Approvals')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">Director Overtime Approvals</h5>
                    <p class="text-muted small mb-0">Overtime requests waiting for director approval</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-list me-1"></i> All Requests
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-clock text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Pending</h6>
                                    <h4 class="mb-0">{{ $stats['total_pending'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-calendar-alt text-success"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">This Month</h6>
                                    <h4 class="mb-0">{{ $stats['this_month'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-hourglass-half text-info"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total Hours</h6>
                                    <h4 class="mb-0">{{ number_format($stats['total_hours'], 1) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-chart-line text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Avg. Days</h6>
                                    <h4 class="mb-0">{{ $stats['avg_days'] }} days</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('overtime-requests.director-approvals') }}" class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Employee</label>
                            <select name="employee_id" class="form-select border-1 rounded-2 py-2 px-3">
                                <option value="">All</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Department</label>
                            <select name="department_id" class="form-select border-1 rounded-2 py-2 px-3">
                                <option value="">All</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-dark">OT Code</label>
                            <select name="ot_code" class="form-select border-1 rounded-2 py-2 px-3">
                                <option value="">All</option>
                                <option value="Normal Day" {{ request('ot_code') == 'Normal Day' ? 'selected' : '' }}>Normal Day</option>
                                <option value="Sunday" {{ request('ot_code') == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                                <option value="Public Holiday" {{ request('ot_code') == 'Public Holiday' ? 'selected' : '' }}>Public Holiday</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Start Date</label>
                            <input type="date" name="start_date" class="form-control border-1 rounded-2 py-2 px-3" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-dark">End Date</label>
                            <input type="date" name="end_date" class="form-control border-1 rounded-2 py-2 px-3" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-1 w-100">
                                <button type="submit" class="btn btn-primary rounded-2 px-3 w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <a href="{{ route('overtime-requests.director-approvals') }}" class="btn btn-outline-secondary rounded-2 px-3">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    @if($overtimeRequests->isEmpty())
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h6 class="text-muted">No Pending Director Approvals</h6>
                            <p class="small text-muted">All requests have been processed.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center">No</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Employee</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Department</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Project</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">OT Code</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Start</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">End</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Net Hours</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Days</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $startNumber = ($overtimeRequests->currentPage() - 1) * $overtimeRequests->perPage() + 1; @endphp
                                    @foreach($overtimeRequests as $index => $req)
                                    <tr>
                                        <td class="px-3 py-2 text-center text-muted">{{ $startNumber + $index }}</td>
                                        <td class="px-3 py-2">
                                            <span class="fw-medium">{{ $req->employee->name ?? '-' }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ $req->department->name ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2">{{ $req->jobOrder->name ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="badge bg-light text-dark px-2 py-1">{{ $req->ot_code }}</span>
                                        </td>
                                        <td class="px-3 py-2">{{ $req->start_time->format('d/m H:i') }}</td>
                                        <td class="px-3 py-2">{{ $req->end_time->format('d/m H:i') }}</td>
                                        <td class="px-3 py-2 text-end">{{ number_format($req->net_hours, 2) }}</td>
                                        <td class="px-3 py-2">
                                            @php $daysPending = $req->created_at->diffInDays(now()); @endphp
                                            <span class="badge bg-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} bg-opacity-10 text-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} border border-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} border-opacity-25 rounded-2 px-2 py-1">
                                                {{ $daysPending }} days
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <a href="{{ route('overtime-requests.show', $req) }}" class="btn btn-outline-info btn-sm rounded-2 px-2 py-1" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('overtime-requests.approve-director', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this overtime request?')">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-outline-success btn-sm rounded-2 px-2 py-1" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1 reject-btn" 
                                                        data-id="{{ $req->id }}" 
                                                        data-employee="{{ $req->employee->name }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal"
                                                        title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($overtimeRequests->hasPages())
                        <div class="card-footer border-0 bg-light px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ $overtimeRequests->firstItem() }} to {{ $overtimeRequests->lastItem() }} of {{ $overtimeRequests->total() }} entries
                                </div>
                                {{ $overtimeRequests->appends(request()->query())->links() }}
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Overtime Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to reject overtime request for <span id="rejectEmployeeName" class="fw-bold"></span>?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason (Optional)</label>
                        <textarea name="finance_notes" class="form-control" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rejectButtons = document.querySelectorAll('.reject-btn');
    rejectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const employee = this.dataset.employee;
            document.getElementById('rejectEmployeeName').textContent = employee;
            document.getElementById('rejectForm').action = '{{ url("overtime-requests") }}/' + id + '/approve-director';
        });
    });
});
</script>

<style>
    .badge { font-weight: 500; }
    .table td { vertical-align: middle; }
    .btn-sm.rounded-2 { border-radius: 0.5rem; }
</style>
@endsection