@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="{{ route('performanceEmployee.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Rankings
            </a>
        </div>

        <!-- Employee Info Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-user-circle text-primary me-2"></i>
                            {{ $employee->name }}
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-building me-2"></i>
                            <strong>Department:</strong> {{ $employee->department->name ?? '-' }}
                        </p>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <strong>Period:</strong> {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <h5 class="text-muted mb-2">Overall Productivity Score</h5>
                        <h1
                            class="display-4 mb-0
                            @if ($report['overall_score'] >= 100) text-success
                            @elseif($report['overall_score'] >= 85) text-primary
                            @elseif($report['overall_score'] >= 70) text-warning
                            @else text-danger @endif">
                            {{ number_format($report['overall_score'], 2) }}%
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="mb-2">Total Sessions</h6>
                        <h3 class="mb-0">{{ $report['total_sessions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="mb-2">Total Work Time</h6>
                        <h3 class="mb-0">{{ floor($report['total_working_minutes'] / 60) }}h
                            {{ $report['total_working_minutes'] % 60 }}m</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="mb-2">Standard Minutes</h6>
                        <h3 class="mb-0">{{ number_format($report['total_standard_minutes'], 0) }} min</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h6 class="mb-2">Avg Efficiency</h6>
                        <h3 class="mb-0">{{ number_format($report['average_efficiency'], 2) }}%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('performanceEmployee.show', $employee->id) }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ $startDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ $endDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Update Period
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Detailed Sessions Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Work Session Details
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="sessions-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Job Order</th>
                                <th>Date</th>
                                <th>Time Range</th>
                                <th class="text-center">Work Minutes</th>
                                <th class="text-center">Output</th>
                                <th class="text-center">Standard Minutes</th>
                                <th class="text-center">Efficiency</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['sessions'] as $index => $session)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $session->job_order_name }}</strong><br>
                                        <small class="text-muted">{{ $session->project_name }}</small>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($session->date)->format('d M Y') }}</td>
                                    <td>
                                        <small>
                                            {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            {{ $session->duration_minutes }} min
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        {{ $session->output_value ?? 0 }} {{ $session->measurement_type }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            {{ number_format($session->standard_minutes, 0) }} min
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge
                                            @if ($session->efficiency >= 100) bg-success
                                            @elseif($session->efficiency >= 85) bg-primary
                                            @elseif($session->efficiency >= 70) bg-warning
                                            @else bg-danger @endif">
                                            {{ number_format($session->efficiency, 2) }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No work sessions found for this period.
                                    </td>
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
            // Initialize DataTable for better UX
            $('#sessions-table').DataTable({
                order: [
                    [2, 'desc']
                ], // Sort by date descending
                pageLength: 25,
                language: {
                    search: "Search sessions:",
                    lengthMenu: "Show _MENU_ sessions per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ sessions",
                    infoEmpty: "No sessions available",
                    infoFiltered: "(filtered from _MAX_ total sessions)"
                }
            });
        });
    </script>
@endpush
