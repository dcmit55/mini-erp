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
            <div class="col-md-4">
                <div class="card shadow-sm border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Projects</h6>
                                <h2 class="mb-0">{{ $totalProjects }}</h2>
                                <small class="text-muted">Active projects with timing data</small>
                            </div>
                            <div class="text-primary" style="font-size: 3rem; opacity: 0.3;">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Working Hours</h6>
                                <h2 class="mb-0">{{ number_format($totalHours, 1) }}</h2>
                                <small class="text-muted">Hours across all projects</small>
                            </div>
                            <div class="text-success" style="font-size: 3rem; opacity: 0.3;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Output</h6>
                                <h2 class="mb-0">{{ number_format($totalOutput, 1) }}</h2>
                                <small class="text-muted">Combined measurement value</small>
                            </div>
                            <div class="text-warning" style="font-size: 3rem; opacity: 0.3;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
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
                    <div class="card-body">
                        @if (count($projects) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="25%">Project Name</th>
                                            <th width="12%">Department</th>
                                            <th width="10%" class="text-center">Total Hours</th>
                                            <th width="10%" class="text-center">Total Output</th>
                                            <th width="10%" class="text-center">Employees</th>
                                            <th width="10%" class="text-center">Efficiency</th>
                                            <th width="10%" class="text-center">Status</th>
                                            <th width="8%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($projects as $index => $project)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $project->name }}</strong><br>
                                                    <small class="text-muted">{{ $project->sessions_count }}
                                                        sessions</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $project->department->name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <strong>{{ number_format($project->total_hours, 1) }}</strong> hrs
                                                </td>
                                                <td class="text-center">
                                                    <strong>{{ number_format($project->total_output, 1) }}</strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $project->employee_count }}
                                                        people</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge {{ $project->efficiency > 1 ? 'bg-success' : 'bg-warning' }}">
                                                        {{ number_format($project->efficiency, 2) }} /hr
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($project->projectStatus)
                                                        <span class="badge"
                                                            style="background-color: {{ $project->projectStatus->color ?? '#6c757d' }}">
                                                            {{ $project->projectStatus->status ?? 'N/A' }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">N/A</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('efficiency.project.detail', $project->id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                                                        class="btn btn-sm btn-primary" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
