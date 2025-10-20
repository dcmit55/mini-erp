@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-calendar-plus me-2"></i>Leave Request List
                </h4>
                @if (auth()->user()->canModifyData())
                    <a href="{{ route('leave_requests.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Leave Request
                    </a>
                @endif
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="datatable">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <th>Type Leave</th>
                                <th>Reason</th>
                                <th>Approval 1</th>
                                <th>Approval 2</th>
                                <th>Submitted On</th>
                                @if (auth()->check() && in_array(auth()->user()->role, ['super_admin', 'admin_finance', 'admin_logistic', 'admin_hr']))
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveRequests as $leave)
                                <tr>
                                    <td>{{ $loop->iteration + ($leaveRequests->currentPage() - 1) * $leaveRequests->perPage() }}
                                    </td>
                                    <td>{{ $leave->employee->name ?? '-' }}</td>
                                    <td>{{ $leave->employee->department->name ?? '-' }}</td>
                                    <td>{{ $leave->employee->position ?? '-' }}</td>
                                    <td>{{ $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('d-M-Y') : '-' }}
                                    </td>
                                    <td>{{ $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('d-M-Y') : '-' }}
                                    </td>
                                    <td>{{ rtrim(rtrim(number_format($leave->duration, 2, '.', ''), '0'), '.') }} days</td>
                                    <td>{{ $leaveTypeLabels[strtoupper($leave->type)] ?? $leave->type }}</td>
                                    <td>{{ $leave->reason }}</td>

                                    <!-- HR Approval -->
                                    <td>
                                        <span
                                            class="badge bg-{{ $leave->approval_1 == 'approved' ? 'success' : ($leave->approval_1 == 'rejected' ? 'danger' : 'warning text-dark') }}">
                                            {{ ucfirst($leave->approval_1) }}
                                        </span>

                                        @if (auth()->check() && in_array(auth()->user()->role, ['super_admin', 'admin_finance', 'admin_logistic', 'admin_hr']))
                                            <form method="POST"
                                                action="{{ route('leave_requests.updateApproval', $leave->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <select name="approval_1"
                                                    onchange="if(confirm('{{ strtoupper($leave->type) === 'ANNUAL' && $leave->approval_2 === 'approved' ? 'This will deduct employee leave balance. Continue?' : 'Update approval status?' }}')) { this.form.submit(); }"
                                                    class="form-select form-select-sm d-inline w-auto">
                                                    <option value="pending"
                                                        {{ $leave->approval_1 == 'pending' ? 'selected' : '' }}>Pending
                                                    </option>
                                                    <option value="approved"
                                                        {{ $leave->approval_1 == 'approved' ? 'selected' : '' }}>Approved
                                                    </option>
                                                    <option value="rejected"
                                                        {{ $leave->approval_1 == 'rejected' ? 'selected' : '' }}>Rejected
                                                    </option>
                                                </select>
                                            </form>
                                        @endif
                                    </td>

                                    <!-- Manager Approval -->
                                    <td>
                                        <span
                                            class="badge bg-{{ $leave->approval_2 == 'approved' ? 'success' : ($leave->approval_2 == 'rejected' ? 'danger' : 'warning text-dark') }}">
                                            {{ ucfirst($leave->approval_2) }}
                                        </span>

                                        @if (auth()->check() && in_array(auth()->user()->role, ['super_admin', 'admin_finance', 'admin_logistic', 'admin_hr']))
                                            <form method="POST"
                                                action="{{ route('leave_requests.updateApproval', $leave->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <select name="approval_2"
                                                    onchange="if(confirm('{{ strtoupper($leave->type) === 'ANNUAL' && $leave->approval_1 === 'approved' ? 'This will deduct employee leave balance. Continue?' : 'Update approval status?' }}')) { this.form.submit(); }"
                                                    class="form-select form-select-sm d-inline w-auto">
                                                    <option value="pending"
                                                        {{ $leave->approval_2 == 'pending' ? 'selected' : '' }}>Pending
                                                    </option>
                                                    <option value="approved"
                                                        {{ $leave->approval_2 == 'approved' ? 'selected' : '' }}>Approved
                                                    </option>
                                                    <option value="rejected"
                                                        {{ $leave->approval_2 == 'rejected' ? 'selected' : '' }}>Rejected
                                                    </option>
                                                </select>
                                            </form>
                                        @endif
                                    </td>

                                    <td>{{ $leave->created_at->format('d-M-Y') }}</td>
                                    @if (auth()->user()->canModifyData() && in_array(auth()->user()->role, ['super_admin', 'admin_finance', 'admin_hr']))
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('leave_requests.edit', $leave->id) }}"
                                                    class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('leave_requests.destroy', $leave->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Are you sure to delete this leave request?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger btn-sm" type="submit"
                                                        data-bs-toggle="tooltip" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">No leave requests submitted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable({
                responsive: true,
                stateSave: true,
            });
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
@endpush
