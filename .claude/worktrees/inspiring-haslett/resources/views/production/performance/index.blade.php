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
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form id="filter-form" class="row g-3">
                            <!-- Date Range Filters -->
                            <div class="col-md-2">
                                <label for="start_date" class="form-label fw-bold small">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                                    value="{{ $startDate->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label fw-bold small">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                                    value="{{ $endDate->format('Y-m-d') }}">
                            </div>

                            <!-- Department Filter with Select2 -->
                            <div class="col-md-2">
                                <label for="department_id" class="form-label fw-bold small">Department</label>
                                <select name="department_id" id="department_id"
                                    class="form-select form-select-sm select2-filter">
                                    <option value="">All Departments</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Job Order Filter with Select2 -->
                            <div class="col-md-2">
                                <label for="job_order_id" class="form-label fw-bold small">Job Order</label>
                                <select name="job_order_id" id="job_order_id"
                                    class="form-select form-select-sm select2-filter">
                                    <option value="">All Job Orders</option>
                                    @foreach ($jobOrders as $jo)
                                        <option value="{{ $jo->id }}" {{ $jobOrderId == $jo->id ? 'selected' : '' }}>
                                            {{ $jo->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Employee Filter with Select2 -->
                            <div class="col-md-2">
                                <label for="employee_id" class="form-label fw-bold small">Employee</label>
                                <select name="employee_id" id="employee_id"
                                    class="form-select form-select-sm select2-filter">
                                    <option value="">All Employees</option>
                                    @foreach ($employees as $emp)
                                        <option value="{{ $emp->id }}"
                                            {{ $employeeId == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Reset Button -->
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('performanceEmployee.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

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
                                            class="badge bg-{{ $ranking->productivity_score >= 80 ? 'success' : ($ranking->productivity_score >= 60 ? 'warning' : 'danger') }}"
                                            style="font-size: 1rem;">
                                            {{ number_format($ranking->productivity_score, 2) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($ranking->productivity_score >= 80)
                                            <span class="badge bg-success">Excellent</span>
                                        @elseif ($ranking->productivity_score >= 60)
                                            <span class="badge bg-warning">Good</span>
                                        @else
                                            <span class="badge bg-danger">Poor</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
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
            // Initialize Select2 for filter dropdowns
            $('.select2-filter').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'Select...',
                width: '100%'
            });

            // Auto-filter when any filter changes (without button click)
            $('#start_date, #end_date, #department_id, #job_order_id, #employee_id').on('change', function() {
                applyFilters();
            });

            // Function to apply filters via AJAX
            function applyFilters() {
                const params = new URLSearchParams({
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    department_id: $('#department_id').val() || '',
                    job_order_id: $('#job_order_id').val() || '',
                    employee_id: $('#employee_id').val() || '',
                    format: 'json'
                });

                // Show loading state
                const tableBody = $('table tbody');
                tableBody.html(
                    '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading...</td></tr>'
                );

                $.ajax({
                    url: `{{ route('performanceEmployee.index') }}?${params.toString()}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            updateTable(response.data.rankings);
                            updateStatistics(response.data.rankings);
                        }
                    },
                    error: function() {
                        tableBody.html(
                            '<tr><td colspan="7" class="text-center text-danger py-4">Error loading data</td></tr>'
                        );
                    }
                });
            }

            // Function to update table with rankings
            function updateTable(rankings) {
                const tableBody = $('table tbody');

                if (rankings.length === 0) {
                    tableBody.html(
                        '<tr><td colspan="7" class="text-center text-muted py-4">No data available</td></tr>');
                    return;
                }

                let html = '';
                rankings.forEach((rank, index) => {
                    const scoreColor = rank.productivity_score >= 80 ? 'success' :
                        rank.productivity_score >= 60 ? 'warning' : 'danger';
                    const scoreLabel = rank.productivity_score >= 80 ? 'Excellent' :
                        rank.productivity_score >= 60 ? 'Good' : 'Poor';

                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    ${rank.employee_photo ? `<img src="/storage/${rank.employee_photo}" class="rounded-circle me-2" width="35" height="35" style="object-fit: cover;">` : '<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;"><i class="bi bi-person text-white"></i></div>'}
                                    <div>
                                        <h6 class="mb-0">${rank.employee_name}</h6>
                                        <small class="text-muted">${rank.position || 'N/A'}</small>
                                    </div>
                                </div>
                            </td>
                            <td>${rank.department || 'N/A'}</td>
                            <td>${rank.total_sessions || 0}</td>
                            <td class="text-nowrap">${(rank.total_working_minutes / 60).toFixed(2)}h</td>
                            <td><span class="badge bg-${scoreColor}">${rank.productivity_score.toFixed(2)}%</span></td>
                            <td class="text-center">
                                <span class="badge bg-${scoreColor}">${scoreLabel}</span>
                            </td>
                            <td class="text-center">
                                <a href="/performanceEmployee/${rank.employee_id}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    `;
                });

                tableBody.html(html);
            }

            // Function to update statistics
            function updateStatistics(rankings) {
                if (rankings.length > 0) {
                    const avgScore = rankings.reduce((sum, r) => sum + r.productivity_score, 0) / rankings.length;
                    const maxScore = Math.max(...rankings.map(r => r.productivity_score));
                    const minScore = Math.min(...rankings.map(r => r.productivity_score));
                    const totalHours = rankings.reduce((sum, r) => sum + r.total_working_minutes, 0) / 60;

                    // Update statistics cards (if they exist)
                    // This would require updating the HTML structure or using callbacks
                } else {
                    // Hide statistics if no data
                }
            }

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
