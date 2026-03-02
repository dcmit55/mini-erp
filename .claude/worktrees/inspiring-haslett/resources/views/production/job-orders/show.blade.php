@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-12">

                <!-- HEADER -->
                <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
                    <!-- Back Button -->
                    <div>
                        <a href="{{ route('job-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>

                    <!-- Title di tengah -->
                    <div class="text-center">
                        <h4 class="mb-0 text-dark">Detail</h4>
                        <p class="text-muted mb-0 small">#{{ $jobOrder->id }}</p>
                    </div>

                    <!-- Spacer untuk balance -->
                    <div style="width: 80px;"></div>
                </div>

                <!-- MAIN CONTENT -->
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-6">
                        <!-- Basic Information -->
                        <div class="card mb-3">
                            <div class="card-header py-2 bg-light">
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="mb-2">
                                    <div class="text-muted small">Job Order ID</div>
                                    <div class="fw-medium">#{{ $jobOrder->id }}</div>
                                </div>

                                <div class="mb-2">
                                    <div class="text-muted small">Name</div>
                                    <div class="fw-medium">{{ $jobOrder->name ?? 'N/A' }}</div>
                                </div>

                                <div class="mb-2">
                                    <div class="text-muted small">Description</div>
                                    <div class="text-muted small">
                                        {{ $jobOrder->description ?: 'No description' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted small">Notes</div>
                                    <div class="text-muted small">
                                        {{ $jobOrder->notes ?: 'No notes' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="card">
                            <div class="card-header py-2 bg-light">
                                <h6 class="mb-0">Timeline</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="text-muted small">Start Date</div>
                                        <div class="fw-medium">
                                            @if ($jobOrder->start_date)
                                                {{ \Carbon\Carbon::parse($jobOrder->start_date)->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <div class="text-muted small">End Date</div>
                                        <div class="fw-medium">
                                            @if ($jobOrder->end_date)
                                                {{ \Carbon\Carbon::parse($jobOrder->end_date)->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small">Duration</div>
                                        <div class="fw-medium">
                                            @if ($jobOrder->start_date && $jobOrder->end_date)
                                                @php
                                                    $days = \Carbon\Carbon::parse($jobOrder->start_date)->diffInDays(
                                                        \Carbon\Carbon::parse($jobOrder->end_date),
                                                    );
                                                @endphp
                                                {{ $days }} days
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-lg-6">
                        <!-- Relations -->
                        <div class="card mb-3">
                            <div class="card-header py-2 bg-light">
                                <h6 class="mb-0">Relations</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="text-muted small">Project</div>
                                        <div class="fw-medium">
                                            @if ($jobOrder->project)
                                                {{ $jobOrder->project->name }}
                                            @else
                                                <span class="text-danger small">Not assigned</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <div class="text-muted small">Department</div>
                                        <div class="fw-medium">
                                            @if ($jobOrder->department)
                                                {{ $jobOrder->department->name }}
                                            @else
                                                <span class="text-danger small">Not assigned</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- HAPUS Assigned To section -->

                                    <div class="col-md-6 mb-2">
                                        <div class="text-muted small">Created by</div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle me-1 text-muted fa-sm"></i>
                                            <span class="fw-medium">
                                                @if ($jobOrder->creator)
                                                    {{ $jobOrder->creator->username }}
                                                @else
                                                    <span class="text-muted small">System</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="card">
                            <div class="card-header py-2 bg-light">
                                <h6 class="mb-0">System Information</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="text-muted small">Created At</div>
                                        <div>
                                            <div class="fw-medium">{{ $jobOrder->created_at->format('d/m/Y H:i') }}</div>
                                            <div class="text-muted small">{{ $jobOrder->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <div class="text-muted small">Updated At</div>
                                        <div>
                                            <div class="fw-medium">{{ $jobOrder->updated_at->format('d/m/Y H:i') }}</div>
                                            <div class="text-muted small">{{ $jobOrder->updated_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <!-- Delete Button -->
                    <form action="{{ route('job-orders.destroy', $jobOrder->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete Job Order #{{ $jobOrder->id }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </form>

                    <!-- Edit Button (ungu outline) -->
                    <a href="{{ route('job-orders.edit', $jobOrder->id) }}" class="btn btn-outline-purple btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .card {
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .card-body {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .btn-outline-purple {
            color: #6f42c1;
            border-color: #6f42c1;
        }

        .btn-outline-purple:hover {
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        .text-muted.small {
            font-size: 0.8rem;
        }

        .fw-medium {
            font-weight: 500;
        }

        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mt-2 {
            margin-top: 0.5rem !important;
        }

        .py-2 {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
    </style>
@endpush
