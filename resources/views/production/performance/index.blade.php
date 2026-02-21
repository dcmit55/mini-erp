@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-trophy gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Employee Productivity Ranking</h2>
                    </div>

                    <div class="ms-lg-auto d-flex flex-wrap gap-2">
                        <button type="button" id="export-btn" class="btn btn-success btn-sm flex-shrink-0">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                        </button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filters -->
                <form id="filter-form" method="GET" action="{{ route('performanceEmployee.index') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label fw-bold">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ $startDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label fw-bold">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ $endDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="department_id" class="form-label fw-bold">Department</label>
                            <select name="department_id" id="department_id" class="form-select">
                                <option value="">All Departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="job_order_id" class="form-label fw-bold">Job Order</label>
                            <select name="job_order_id" id="job_order_id" class="form-select">
                                <option value="">All Job Orders</option>
                                @foreach ($jobOrders as $jo)
                                    <option value="{{ $jo->id }}" {{ $jobOrderId == $jo->id ? 'selected' : '' }}>
                                        {{ $jo->id }} - {{ $jo->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="employee_id" class="form-label fw-bold">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">All Employees</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="{{ route('performanceEmployee.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Period Info -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <strong>Period:</strong> {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}
                    @if ($departmentId)
                        | <strong>Department:</strong>
                        {{ $departments->firstWhere('id', $departmentId)->name ?? 'Unknown' }}
                    @endif
                    @if ($jobOrderId)
                        | <strong>Job Order:</strong>
                        {{ $jobOrders->firstWhere('id', $jobOrderId)->id ?? 'Unknown' }}
                    @endif
                    @if ($employeeId)
                        | <strong>Employee:</strong>
                        {{ $employees->firstWhere('id', $employeeId)->name ?? 'Unknown' }}
                    @endif
                    | <strong>Total Employees:</strong> {{ $rankings->count() }}
                </div>

                <!-- Rankings Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="rankings-table">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center" style="width: 60px;">Rank</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th class="text-center" style="width: 150px;">Work Time</th>
                                <th class="text-center" style="width: 150px;">Standard Minutes</th>
                                <th class="text-center" style="width: 120px;">Productivity</th>
                                <th class="text-center" style="width: 100px;">Level</th>
                                <th class="text-center" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rankings as $ranking)
                                <tr>
                                    <td class="text-center fw-bold">
                                        @if ($ranking->rank == 1)
                                            <i class="fas fa-trophy text-warning"></i>
                                        @elseif ($ranking->rank == 2)
                                            <i class="fas fa-medal text-secondary"></i>
                                        @elseif ($ranking->rank == 3)
                                            <i class="fas fa-medal text-danger"></i>
                                        @endif
                                        {{ $ranking->rank }}
                                    </td>
                                    <td>
                                        <strong>{{ $ranking->employee_name }}</strong>
                                    </td>
                                    <td>{{ $ranking->department_name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            {{ floor($ranking->total_working_minutes / 60) }}h
                                            {{ $ranking->total_working_minutes % 60 }}m
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            {{ number_format($ranking->total_standard_minutes, 0) }} min
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge
                                            @if ($ranking->productivity_score >= 100) bg-success
                                            @elseif($ranking->productivity_score >= 85) bg-primary
                                            @elseif($ranking->productivity_score >= 70) bg-warning
                                            @else bg-danger @endif
                                        "
                                            style="font-size: 1rem;">
                                            {{ number_format($ranking->productivity_score, 2) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($ranking->productivity_score >= 100)
                                            <span class="badge bg-success">Excellent</span>
                                        @elseif ($ranking->productivity_score >= 85)
                                            <span class="badge bg-primary">Good</span>
                                        @elseif ($ranking->productivity_score >= 70)
                                            <span class="badge bg-warning">Average</span>
                                        @else
                                            <span class="badge bg-danger">Poor</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('performanceEmployee.show', ['employee' => $ranking->employee_id, 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        No productivity data found for the selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($rankings->count() > 0)
                    <!-- Statistics Summary -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Average Productivity</h6>
                                    <h3 class="mb-0">{{ number_format($rankings->avg('productivity_score'), 2) }}%</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Highest Score</h6>
                                    <h3 class="mb-0">{{ number_format($rankings->max('productivity_score'), 2) }}%</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Lowest Score</h6>
                                    <h3 class="mb-0">{{ number_format($rankings->min('productivity_score'), 2) }}%</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">Total Work Hours</h6>
                                    <h3 class="mb-0">
                                        {{ number_format($rankings->sum('total_working_minutes') / 60, 0) }}h
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Export button handler
            $('#export-btn').on('click', function() {
                const params = new URLSearchParams({
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    department_id: $('#department_id').val() || '',
                    job_order_id: $('#job_order_id').val() || '',
                    employee_id: $('#employee_id').val() || ''
                });

                window.location.href = `{{ route('performanceEmployee.export') }}?${params.toString()}`;
            });
        });
    </script>
@endpush
