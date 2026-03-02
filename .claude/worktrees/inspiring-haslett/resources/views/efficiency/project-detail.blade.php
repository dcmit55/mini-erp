@extends('layouts.app')

@section('title', 'Project Detail - ' . $project->name)

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
                                <li class="breadcrumb-item active">{{ $project->name }}</li>
                            </ol>
                        </nav>
                        <h2 class="mb-1">
                            <i class="bi bi-folder2-open text-primary me-2"></i>
                            {{ $project->name }}
                        </h2>
                        <p class="text-muted mb-0">
                            Department: <span class="badge bg-secondary">{{ $project->department->name ?? 'N/A' }}</span>
                            | Status:
                            @if ($project->status)
                                <span class="badge" style="background-color: {{ $project->status->color ?? '#6c757d' }}">
                                    {{ $project->status->status ?? 'N/A' }}
                                </span>
                            @else
                                <span class="badge bg-secondary">N/A</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('efficiency.index') }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
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
                        <h3 class="mb-0">{{ number_format($projectSummary->total_hours, 1) }}</h3>
                        <small class="text-muted">Working hours</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-success border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Output</h6>
                        <h3 class="mb-0">{{ number_format($projectSummary->total_output, 1) }}</h3>
                        <small class="text-muted">Combined value</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-info border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Sessions</h6>
                        <h3 class="mb-0">{{ $projectSummary->total_sessions }}</h3>
                        <small class="text-muted">Work sessions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-warning border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Employees</h6>
                        <h3 class="mb-0">{{ $projectSummary->total_employees }}</h3>
                        <small class="text-muted">Contributors</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up text-primary me-2"></i>
                            Progress Timeline (Hours vs Output)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="timelineChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Orders Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-task text-primary me-2"></i>
                            Job Orders Breakdown
                        </h5>
                        <span class="badge bg-primary">{{ count($jobOrders) }} Job Orders</span>
                    </div>
                    <div class="card-body">
                        @if (count($jobOrders) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">Job Order</th>
                                            <th width="12%">Department</th>
                                            <th width="12%" class="text-center">Total Hours</th>
                                            <th width="12%" class="text-center">Total Output</th>
                                            <th width="10%" class="text-center">Employees</th>
                                            <th width="10%" class="text-center">Sessions</th>
                                            <th width="12%" class="text-center">Efficiency</th>
                                            <th width="7%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($jobOrders as $index => $jobOrder)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $jobOrder->name }}</strong><br>
                                                    <small class="text-muted">{{ $jobOrder->id }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $jobOrder->department->name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <strong>{{ number_format($jobOrder->total_hours, 1) }}</strong> hrs
                                                </td>
                                                <td class="text-center">
                                                    <strong>{{ number_format($jobOrder->total_output, 1) }}</strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $jobOrder->employee_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-light text-dark">{{ $jobOrder->sessions_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge {{ $jobOrder->efficiency > 1 ? 'bg-success' : 'bg-warning' }}">
                                                        {{ number_format($jobOrder->efficiency, 2) }} /hr
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('efficiency.job-order.detail', $jobOrder->id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                                                        class="btn btn-sm btn-primary" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="3" class="text-end">TOTAL:</td>
                                            <td class="text-center">{{ number_format($jobOrders->sum('total_hours'), 1) }}
                                                hrs</td>
                                            <td class="text-center">{{ number_format($jobOrders->sum('total_output'), 1) }}
                                            </td>
                                            <td class="text-center">-</td>
                                            <td class="text-center">{{ $jobOrders->sum('sessions_count') }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">No job orders with timing data in the selected date range</p>
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
            // Timeline Chart Data
            const timelineData = @json($timeline);

            const labels = timelineData.map(item => item.date);
            const hoursData = timelineData.map(item => parseFloat(item.hours));
            const outputData = timelineData.map(item => parseFloat(item.output));

            const ctx = document.getElementById('timelineChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Hours Worked',
                            data: hoursData,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4
                        },
                        {
                            label: 'Output Produced',
                            data: outputData,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            yAxisID: 'y1',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Hours'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Output'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                    }
                }
            });
        });
    </script>
@endsection
