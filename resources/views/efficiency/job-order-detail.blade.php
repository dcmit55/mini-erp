@extends('layouts.app')

@section('title', 'Job Order Detail - ' . $jobOrder->name)

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('efficiency.index') }}?start_date={{ $startDate }}&end_date={{ $endDate }}">
                                        Efficiency Dashboard
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('efficiency.project.detail', $jobOrder->project_id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}">
                                        {{ $jobOrder->project->name ?? 'Project' }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item active">{{ $jobOrder->name }}</li>
                            </ol>
                        </nav>
                        <h2 class="mb-1">
                            <i class="bi bi-card-checklist text-primary me-2"></i>
                            {{ $jobOrder->name }}
                        </h2>
                        <p class="text-muted mb-0">
                            <strong>ID:</strong> {{ $jobOrder->id }} |
                            <strong>Department:</strong> <span
                                class="badge bg-secondary">{{ $jobOrder->department->name ?? 'N/A' }}</span>
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('efficiency.project.detail', $jobOrder->project_id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Project
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-primary border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Hours</h6>
                        <h3 class="mb-0">{{ number_format($jobOrderSummary->total_hours, 1) }}</h3>
                        <small class="text-muted">Working hours</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-success border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Output</h6>
                        <h3 class="mb-0">{{ number_format($jobOrderSummary->total_output, 1) }}</h3>
                        <small class="text-muted">Combined value</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-info border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Sessions</h6>
                        <h3 class="mb-0">{{ $jobOrderSummary->total_sessions }}</h3>
                        <small class="text-muted">Work sessions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-warning border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Contributors</h6>
                        <h3 class="mb-0">{{ $jobOrderSummary->total_employees }}</h3>
                        <small class="text-muted">Employees</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Employee Contribution Pie Chart -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-pie-chart text-primary me-2"></i>
                            Employee Contribution (Hours)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="contributionChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Progress Trend (for progress mode) -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up-arrow text-success me-2"></i>
                            Progress Trend
                        </h5>
                    </div>
                    <div class="card-body">
                        @if (count($progressTrend) > 0)
                            <canvas id="progressChart" height="200"></canvas>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">No progress tracking data available</p>
                                <small>This job order uses timer mode, not progress mode</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Contributions Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-people-fill text-primary me-2"></i>
                            Employee Contributions
                        </h5>
                        <span class="badge bg-primary">{{ count($employeeContributions) }} Employees</span>
                    </div>
                    <div class="card-body">
                        @if (count($employeeContributions) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">Employee</th>
                                            <th width="12%">Department</th>
                                            <th width="12%" class="text-center">Total Hours</th>
                                            <th width="10%" class="text-center">% Hours</th>
                                            <th width="12%" class="text-center">Total Output</th>
                                            <th width="10%" class="text-center">Sessions</th>
                                            <th width="10%" class="text-center">Efficiency</th>
                                            <th width="9%" class="text-center">Period</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employeeContributions as $index => $contribution)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    @if ($contribution->employee)
                                                        <div class="d-flex align-items-center">
                                                            @if ($contribution->employee->photo)
                                                                <img src="/storage/{{ $contribution->employee->photo }}"
                                                                    class="rounded-circle me-2" width="32"
                                                                    height="32" style="object-fit: cover;">
                                                            @else
                                                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2"
                                                                    style="width: 32px; height: 32px;">
                                                                    <i class="bi bi-person text-white"></i>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <strong>{{ $contribution->employee->name }}</strong><br>
                                                                <small
                                                                    class="text-muted">{{ $contribution->employee->position ?? 'N/A' }}</small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Unknown Employee</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $contribution->employee->department->name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <strong>{{ number_format($contribution->total_hours, 1) }}</strong> hrs
                                                </td>
                                                <td class="text-center">
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-primary" role="progressbar"
                                                            style="width: {{ $contribution->hours_percentage }}%"
                                                            aria-valuenow="{{ $contribution->hours_percentage }}"
                                                            aria-valuemin="0" aria-valuemax="100">
                                                            {{ number_format($contribution->hours_percentage, 1) }}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <strong>{{ number_format($contribution->total_output, 1) }}</strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $contribution->sessions_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge {{ $contribution->efficiency > 1 ? 'bg-success' : 'bg-warning' }}">
                                                        {{ number_format($contribution->efficiency, 2) }} /hr
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($contribution->first_work_date)->format('d M') }}
                                                        -
                                                        {{ \Carbon\Carbon::parse($contribution->last_work_date)->format('d M') }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="3" class="text-end">TOTAL:</td>
                                            <td class="text-center">
                                                {{ number_format($employeeContributions->sum('total_hours'), 1) }} hrs</td>
                                            <td class="text-center">100%</td>
                                            <td class="text-center">
                                                {{ number_format($employeeContributions->sum('total_output'), 1) }}</td>
                                            <td class="text-center">{{ $employeeContributions->sum('sessions_count') }}
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">No employee contributions found in the selected date range</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        $(document).ready(function() {
            // Employee Contribution Pie Chart
            const contributionData = @json($employeeContributions);

            const employeeNames = contributionData.map(item => item.employee ? item.employee.name : 'Unknown');
            const employeeHours = contributionData.map(item => parseFloat(item.total_hours));

            const contributionCtx = document.getElementById('contributionChart').getContext('2d');
            new Chart(contributionCtx, {
                type: 'doughnut',
                data: {
                    labels: employeeNames,
                    datasets: [{
                        label: 'Hours Worked',
                        data: employeeHours,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)',
                            'rgba(83, 102, 255, 0.8)',
                            'rgba(255, 99, 255, 0.8)',
                            'rgba(99, 255, 132, 0.8)',
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value.toFixed(1)} hrs (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Progress Trend Chart (if data exists)
            @if (count($progressTrend) > 0)
                const progressData = @json($progressTrend->values());

                const progressLabels = progressData.map(item => `${item.date} ${item.time}`);
                const progressValues = progressData.map(item => parseFloat(item.current_progress));
                const progressEmployees = progressData.map(item => item.employee);

                const progressCtx = document.getElementById('progressChart').getContext('2d');
                new Chart(progressCtx, {
                    type: 'line',
                    data: {
                        labels: progressLabels,
                        datasets: [{
                            label: 'Progress %',
                            data: progressValues,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        return 'By: ' + progressEmployees[context.dataIndex];
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Progress (%)'
                                }
                            }
                        }
                    }
                });
            @endif
        });
    </script>
@endsection
