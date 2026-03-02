@extends('layouts.app')

@section('title', 'Overtime vs Attendance Comparison')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="position-relative d-flex align-items-center mb-3" style="min-height:52px;">
                <!-- Left: Back button -->
                <div class="flex-shrink-0">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
                <!-- Center: Title (absolute) -->
                <div class="position-absolute start-50 translate-middle-x text-center" style="pointer-events:none;">
                    <p class="text-muted small mb-0">Check if overtime end time matches clock out time</p>
                </div>
                <!-- Right: action buttons -->
                <div class="ms-auto d-flex gap-2 flex-shrink-0">
                    <a href="{{ route('overtime-requests.hr-approvals') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-user-check me-1"></i> HR Approvals
                    </a>
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-list me-1"></i> All Requests
                    </a>
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

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">No</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Employee</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Date</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">OT Start</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">OT End</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Net Hours</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Clock In</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Clock Out</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Att. Hours</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Match Status</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Passed</th>
                                    @if(in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin']))
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center">Action</th>
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
                                    <tr>
                                        <td class="px-3 py-2 text-center text-muted">{{ $startNumber + $index }}</td>
                                        <td class="px-3 py-2">
                                            <span class="fw-medium">{{ $req->employee->name ?? '-' }}</span>
                                        </td>
                                        <td class="px-3 py-2">{{ $req->start_time->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">{{ $req->start_time->format('H:i') }}</td>
                                        <td class="px-3 py-2">{{ $req->end_time->format('H:i') }}</td>
                                        <td class="px-3 py-2">{{ $req->net_hours_formatted }}</td>
                                        <td class="px-3 py-2">
                                            @if($req->attendance && $req->attendance->clock_in)
                                                {{ \Carbon\Carbon::parse($req->attendance->clock_in)->format('H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($req->attendance && $req->attendance->clock_out)
                                                {{ \Carbon\Carbon::parse($req->attendance->clock_out)->format('H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($req->attendance && $req->attendance->total_hours)
                                                {{ number_format($req->attendance->total_hours, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="badge bg-{{ $req->match_status_class }} px-3 py-1 rounded-pill">
                                                {{ $req->match_status_text }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($req->is_passed)
                                                <span class="badge bg-success px-3 py-1 rounded-pill">Yes</span>
                                            @else
                                                <span class="badge bg-secondary px-3 py-1 rounded-pill">No</span>
                                            @endif
                                        </td>
                                        @if(in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin']))
                                            <td class="px-3 py-2 text-center">
                                                <form action="{{ route('overtime-requests.toggle-pass', $req) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $req->is_passed ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-2 px-3" title="{{ $req->is_passed ? 'Mark as Not Passed' : 'Mark as Passed' }}">
                                                        {{ $req->is_passed ? 'Undo Pass' : 'Pass' }}
                                                    </button>
                                                </form>
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

<style>
    .badge { font-weight: 500; }
    .table td { vertical-align: middle; }
    .btn-sm.rounded-2 { border-radius: 0.5rem; }
    .form-select-sm, .form-control-sm { font-size: 0.875rem; padding-top: 0.25rem; padding-bottom: 0.25rem; }
</style>
@endsection