@extends('layouts.app')

@section('title', 'Production Efficiency Dashboard')

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="bi bi-speedometer2 text-primary me-2"></i>
                            Production Efficiency Dashboard
                        </h2>
                        <p class="text-muted mb-0">Overview of all projects performance and productivity metrics</p>
                    </div>
                    <div>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="{{ route('efficiency.index') }}" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel me-1"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card shadow-sm border-start border-primary border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Projects</h6>
                                <h3 class="mb-0">{{ $totalProjects }}</h3>
                                <small class="text-muted">Active projects</small>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem; opacity: 0.3;">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card shadow-sm border-start border-success border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Hours</h6>
                                <h3 class="mb-0">{{ number_format($totalHours, 1) }}</h3>
                                <small class="text-muted">Working hours</small>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem; opacity: 0.3;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card shadow-sm border-start border-warning border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Output</h6>
                                <h3 class="mb-0">{{ number_format($totalOutput, 1) }}</h3>
                                <small class="text-muted">Measurement value</small>
                            </div>
                            <div class="text-warning" style="font-size: 2.5rem; opacity: 0.3;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card shadow-sm border-start border-info border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Employees</h6>
                                <h3 class="mb-0">{{ $totalEmployees }}</h3>
                                <small class="text-muted">Unique workers</small>
                            </div>
                            <div class="text-info" style="font-size: 2.5rem; opacity: 0.3;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card shadow-sm border-start border-danger border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Average Efficiency</h6>
                                <h3 class="mb-0">{{ number_format($averageEfficiency, 2) }}%</h3>
                                <small class="text-muted">Percentage</small>
                            </div>
                            <div class="text-danger" style="font-size: 2.5rem; opacity: 0.3;">
                                <i class="bi bi-speedometer"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Accordion -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                            Projects Performance
                        </h5>
                        <span class="badge bg-primary">{{ count($projects) }} Projects</span>
                    </div>
                    <div class="card-body p-0">
                        @if (count($projects) > 0)
                            <div class="accordion accordion-flush" id="projectsAccordion">
                                @foreach ($projects as $index => $project)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $project->id }}">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapse{{ $project->id }}"
                                                aria-expanded="false" aria-controls="collapse{{ $project->id }}">
                                                <div class="d-flex w-100 justify-content-between align-items-center pe-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <span class="badge bg-primary">{{ $index + 1 }}</span>
                                                        <div>
                                                            <strong>{{ $project->name }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ $project->sessions_count }}
                                                                sessions •
                                                                {{ $project->department->name ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex gap-3 align-items-center">
                                                        <div class="text-center">
                                                            <small class="text-muted d-block">Hours</small>
                                                            <strong>{{ number_format($project->total_hours, 1) }}</strong>
                                                        </div>
                                                        <div class="text-center">
                                                            <small class="text-muted d-block">Output</small>
                                                            <strong>{{ number_format($project->total_output, 1) }}</strong>
                                                        </div>
                                                        <div class="text-center">
                                                            <small class="text-muted d-block">Employees</small>
                                                            <span
                                                                class="badge bg-info">{{ $project->employee_count }}</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <small class="text-muted d-block">Efficiency</small>
                                                            <span
                                                                class="badge {{ $project->efficiency > 1 ? 'bg-success' : 'bg-warning' }}">
                                                                {{ number_format($project->efficiency, 2) }}
                                                            </span>
                                                        </div>
                                                        @if ($project->projectStatus)
                                                            <span class="badge"
                                                                style="background-color: {{ $project->projectStatus->color ?? '#6c757d' }}">
                                                                {{ $project->projectStatus->status ?? 'N/A' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $project->id }}" class="accordion-collapse collapse"
                                            aria-labelledby="heading{{ $project->id }}"
                                            data-bs-parent="#projectsAccordion">
                                            <div class="accordion-body">
                                                @if (count($project->jobOrders) > 0)
                                                    <h6 class="mb-3"><i class="bi bi-list-task me-2"></i>Job Orders
                                                        Breakdown</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover align-middle">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th width="5%">#</th>
                                                                    <th width="35%">Job Order</th>
                                                                    <th width="15%">Department</th>
                                                                    <th width="12%" class="text-center">Hours</th>
                                                                    <th width="12%" class="text-center">Output</th>
                                                                    <th width="12%" class="text-center">Employees</th>
                                                                    <th width="12%" class="text-center">Efficiency</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($project->jobOrders as $jIndex => $jobOrder)
                                                                    <tr>
                                                                        <td>{{ $jIndex + 1 }}</td>
                                                                        <td>
                                                                            <strong>{{ $jobOrder->name }}</strong><br>
                                                                            <small
                                                                                class="text-muted">{{ $jobOrder->sessions_count }}
                                                                                sessions</small>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge bg-secondary">
                                                                                {{ $jobOrder->department->name ?? 'N/A' }}
                                                                            </span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <strong>{{ number_format($jobOrder->total_hours, 1) }}</strong>
                                                                            hrs
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <strong>{{ number_format($jobOrder->total_output, 1) }}</strong>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span
                                                                                class="badge bg-info">{{ $jobOrder->employee_count }}</span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span
                                                                                class="badge {{ $jobOrder->efficiency > 1 ? 'bg-success' : 'bg-warning' }}">
                                                                                {{ number_format($jobOrder->efficiency, 2) }}
                                                                                /hr
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                                <!-- Total Row -->
                                                                <tr class="table-secondary fw-bold">
                                                                    <td colspan="3" class="text-end">TOTAL:</td>
                                                                    <td class="text-center">
                                                                        {{ number_format($project->jobOrders->sum('total_hours'), 1) }}
                                                                        hrs
                                                                    </td>
                                                                    <td class="text-center">
                                                                        {{ number_format($project->jobOrders->sum('total_output'), 1) }}
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span
                                                                            class="badge bg-info">{{ $project->jobOrders->sum('employee_count') }}</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        @php
                                                                            $totalJobHours = $project->jobOrders->sum(
                                                                                'total_hours',
                                                                            );
                                                                            $totalJobOutput = $project->jobOrders->sum(
                                                                                'total_output',
                                                                            );
                                                                            $totalJobEfficiency =
                                                                                $totalJobHours > 0
                                                                                    ? round(
                                                                                        $totalJobOutput /
                                                                                            $totalJobHours,
                                                                                        2,
                                                                                    )
                                                                                    : 0;
                                                                        @endphp
                                                                        <span
                                                                            class="badge {{ $totalJobEfficiency > 1 ? 'bg-success' : 'bg-warning' }}">
                                                                            {{ number_format($totalJobEfficiency, 2) }} /hr
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <div class="text-center text-muted py-3">
                                                        <i class="bi bi-inbox"></i>
                                                        <p class="mb-0">No job orders with timing data</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">No projects with timing data in the selected date range</p>
                                <small>Try adjusting the date filter or start recording work sessions</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Auto-set today as end date if empty
            if (!$('input[name="end_date"]').val()) {
                $('input[name="end_date"]').val(new Date().toISOString().split('T')[0]);
            }
        });
    </script>
@endsection
