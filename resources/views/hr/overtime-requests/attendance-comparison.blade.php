@extends('layouts.app')

@section('title', 'Overtime vs Attendance Comparison')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="position-relative d-flex align-items-center mb-3" style="min-height:52px;">
                <!-- Left: sub-nav tabs -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <a href="{{ route('overtime-pays.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-calculator me-1"></i> Overtime Pay
                    </a>
                    @php
                        $otNotPassedCount = \App\Models\Hr\OvertimeRequest::where('status', 'approved')->where('is_passed', false)->count();
                    @endphp
                    <a href="{{ route('overtime-requests.attendance-comparison') }}" class="btn btn-primary btn-sm rounded-2 px-3 position-relative">
                        <i class="fas fa-chart-bar me-1"></i> OT vs Attendance
                        @if($otNotPassedCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;min-width:1.4em;padding:.25em .45em;">
                                {{ $otNotPassedCount > 99 ? '99+' : $otNotPassedCount }}
                            </span>
                        @endif
                    </a>
                </div>
                <!-- Right: Passed / Not Passed filter buttons -->
                <div class="ms-auto d-flex gap-2 flex-shrink-0">
                    <a href="{{ request()->fullUrlWithQuery(['passed' => '1']) }}"
                       class="btn btn-sm rounded-2 px-3 {{ request('passed') === '1' ? 'btn-success' : 'btn-outline-success' }}">
                        <i class="fas fa-check me-1"></i> Passed
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['passed' => '0']) }}"
                       class="btn btn-sm rounded-2 px-3 position-relative {{ request('passed') === '0' ? 'btn-warning' : 'btn-outline-warning' }}">
                        <i class="fas fa-clock me-1"></i> Not Passed
                        @if($otNotPassedCount > 0 && request('passed') !== '0')
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;min-width:1.4em;padding:.25em .45em;">
                                {{ $otNotPassedCount > 99 ? '99+' : $otNotPassedCount }}
                            </span>
                        @endif
                    </a>
                    @if(request()->has('passed'))
                    <a href="{{ route('overtime-requests.attendance-comparison', request()->except('passed')) }}"
                       class="btn btn-sm btn-outline-secondary rounded-2 px-2" title="Show all">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </div>

            <!-- Statistics -->
            <div class="row g-2 mb-3">
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-clock text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total</h6>
                                    <h4 class="mb-0">{{ $stats['total'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Match</h6>
                                    <h4 class="mb-0">{{ $stats['match'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-times-circle text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Mismatch</h6>
                                    <h4 class="mb-0">{{ $stats['mismatch'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-user-slash text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">No Attendance</h6>
                                    <h4 class="mb-0">{{ $stats['no_attendance'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">No Clock Out</h6>
                                    <h4 class="mb-0">{{ $stats['no_clockout'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('overtime-requests.attendance-comparison') }}" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small text-dark">Employee</label>
                            <select name="employee_id" class="form-select form-select-sm border-1 rounded-2 py-2 px-3">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-dark">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm border-1 rounded-2 py-2 px-3" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-dark">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm border-1 rounded-2 py-2 px-3" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="{{ route('overtime-requests.attendance-comparison') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Not-passed notice -->
            @php $notPassedCount = $overtimeRequests->getCollection()->where('is_passed', false)->count(); @endphp
            @if($notPassedCount > 0)
            <div class="d-flex align-items-center gap-2 mb-2 px-1">
                <span class="badge bg-warning text-dark rounded-pill px-2 py-1" style="font-size:0.7rem;">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $notPassedCount }} belum di-pass pada halaman ini
                </span>
            </div>
            @endif

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4 text-center" width="50">No</th>
                                    <th class="border-0">Employee</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">OT Start</th>
                                    <th class="border-0">OT End</th>
                                    <th class="border-0">Net Hours</th>
                                    <th class="border-0">Clock In</th>
                                    <th class="border-0">Clock Out</th>
                                    <th class="border-0">Att. Hours</th>
                                    <th class="border-0">Match</th>
                                    <th class="border-0">Passed</th>
                                    @if(in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin']))
                                        <th class="border-0 text-center">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                    @if($overtimeRequests->isEmpty())
                                    <tr>
                                        <td colspan="12" class="text-center py-5">
                                            <i class="fas fa-clock fa-2x text-muted mb-2 d-block"></i>
                                            <span class="text-muted small">No overtime requests found</span>
                                        </td>
                                    </tr>
                                    @else
                                    @php $startNumber = ($overtimeRequests->currentPage() - 1) * $overtimeRequests->perPage() + 1; @endphp
                                    @foreach($overtimeRequests as $index => $req)
                                    <tr class="align-middle">
                                        <td class="ps-4 text-center"><span class="text-muted">{{ $startNumber + $index }}</span></td>
                                        <td>{{ $req->employee->name ?? '-' }}</td>
                                        <td><span class="text-muted">{{ $req->start_time->format('d/m/Y') }}</span></td>
                                        <td><span class="text-muted">{{ $req->start_time->format('H:i') }}</span></td>
                                        <td><span class="text-muted">{{ $req->end_time->format('H:i') }}</span></td>
                                        <td><small>{{ $req->net_hours_formatted }}</small></td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $req->attendance?->clock_in ? \Carbon\Carbon::parse($req->attendance->clock_in)->format('H:i') : '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $req->attendance?->clock_out ? \Carbon\Carbon::parse($req->attendance->clock_out)->format('H:i') : '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $req->attendance?->total_hours ? number_format($req->attendance->total_hours, 2) : '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $req->match_status_class }} px-2 py-1 rounded-pill">
                                                {{ $req->match_status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($req->is_passed)
                                                <span class="badge bg-success px-2 py-1 rounded-pill">Yes</span>
                                            @else
                                                <span class="badge bg-secondary px-2 py-1 rounded-pill">No</span>
                                            @endif
                                        </td>
                                        @if(in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin']))
                                            <td class="px-3 py-2 text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <form action="{{ route('overtime-requests.toggle-pass', $req) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm {{ $req->is_passed ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-2 px-2" title="{{ $req->is_passed ? 'Mark as Not Passed' : 'Mark as Passed' }}">
                                                            {{ $req->is_passed ? 'Undo' : 'Pass' }}
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary rounded-2 px-2 edit-att-btn"
                                                        title="Edit Clock In/Out"
                                                        data-id="{{ $req->id }}"
                                                        data-url="{{ route('overtime-requests.update-attendance', $req) }}"
                                                        data-employee="{{ $req->employee->name ?? '' }}"
                                                        data-date="{{ $req->start_time->format('d/m/Y') }}"
                                                        data-clock-in="{{ $req->attendance?->clock_in ? \Carbon\Carbon::parse($req->attendance->clock_in)->format('H:i') : '' }}"
                                                        data-clock-out="{{ $req->attendance?->clock_out ? \Carbon\Carbon::parse($req->attendance->clock_out)->format('H:i') : '' }}"
                                                        data-bs-toggle="modal" data-bs-target="#editAttModal">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                    @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if(!$overtimeRequests->isEmpty() && $overtimeRequests->hasPages())
                    <div class="card-footer border-0 bg-light px-3 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing {{ $overtimeRequests->firstItem() }} to {{ $overtimeRequests->lastItem() }} of {{ $overtimeRequests->total() }} entries
                            </div>
                            {{ $overtimeRequests->appends(request()->query())->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold">Edit Attendance</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAttForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        <span id="editAttEmployee" class="fw-medium text-dark"></span><br>
                        <span id="editAttDate"></span>
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Clock In</label>
                        <input type="time" name="clock_in" id="editClockIn" class="form-control form-control-sm">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-medium">Clock Out</label>
                        <input type="time" name="clock_out" id="editClockOut" class="form-control form-control-sm">
                    </div>
                    <p class="text-muted" style="font-size:0.72rem;">Changes will be saved to daily attendance records.</p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-att-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editAttEmployee').textContent = this.dataset.employee;
        document.getElementById('editAttDate').textContent     = this.dataset.date;
        document.getElementById('editClockIn').value           = this.dataset.clockIn;
        document.getElementById('editClockOut').value          = this.dataset.clockOut;
        document.getElementById('editAttForm').action          = this.dataset.url;
    });
});
</script>

<style>
    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .table td {
        padding: 0.75rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
        font-size: 0.8rem;
    }
    .table tbody tr:hover { background-color: #f8fafc; }
</style>
@endsection