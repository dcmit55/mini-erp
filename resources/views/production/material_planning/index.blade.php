@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-3">
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-clipboard-check gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0" style="font-size:1.3rem;">Material Planning List</h2>
                    </div>
                    @if (auth()->user()->canModifyData())
                        <div>
                            <a href="{{ route('material_planning.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add Material Planning
                            </a>
                        </div>
                    @endif
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-1"></i>
                            Filter Material Planning
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('material_planning.index') }}" id="filter-form">
                            <div class="row g-3">
                                <!-- Department Filter -->
                                <div class="col-md-3">
                                    <label class="form-label">Department</label>
                                    <select name="department_filter" class="form-select form-select-sm">
                                        <option value="">All Department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                {{ request('department_filter') == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Project Filter -->
                                <div class="col-md-3">
                                    <label class="form-label">Project</label>
                                    <select name="project_filter" class="form-select form-select-sm">
                                        <option value="">All Project</option>
                                        @foreach ($allProjects as $proj)
                                            <option value="{{ $proj->id }}"
                                                {{ request('project_filter') == $proj->id ? 'selected' : '' }}>
                                                {{ $proj->name }}
                                                @if ($proj->department)
                                                    ({{ $proj->department->name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Order Type Filter -->
                                <div class="col-md-2">
                                    <label class="form-label">Order Type</label>
                                    <select name="order_type_filter" class="form-select form-select-sm">
                                        <option value="">All Type</option>
                                        <option value="material_req"
                                            {{ request('order_type_filter') == 'material_req' ? 'selected' : '' }}>
                                            Material Request
                                        </option>
                                        <option value="purchase_req"
                                            {{ request('order_type_filter') == 'purchase_req' ? 'selected' : '' }}>
                                            Purchase Request
                                        </option>
                                    </select>
                                </div>

                                <!-- Date From Filter -->
                                <div class="col-md-2">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="date_from" class="form-control form-control-sm"
                                        value="{{ request('date_from') }}">
                                </div>

                                <!-- Date To Filter -->
                                <div class="col-md-2">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="date_to" class="form-control form-control-sm"
                                        value="{{ request('date_to') }}">
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('material_planning.index') }}"
                                        class="btn btn-outline-secondary btn-sm" title="Reset All Filters">
                                        <i class="fas fa-times me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover border" id="planning-table">
                        <thead class="table-light">
                            <tr>
                                <th class="bg-light" width="20%">Job Order / Project Name</th>
                                <th class="bg-light" width="10%">Department</th>
                                <th class="bg-light text-center" width="12%">Created Date</th>
                                <th class="bg-light text-center" width="12%">Last Update</th>
                                <th class="bg-light text-center" width="12%">ETA Date</th>
                                <th class="bg-light text-center" width="10%">Materials Count</th>
                                <th class="bg-light text-center" width="24%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plannings as $projectId => $plans)
                                @php
                                    $project = $projects[$projectId] ?? null;
                                    $eta = $plans->min('eta_date');
                                    $materialsCount = $plans->count();
                                    $stats = $projectStats[$projectId] ?? null;
                                @endphp
                                <tr class="project-row" data-project="{{ $projectId }}">
                                    <td>
                                        <span class="fw-medium">{{ $project ? $project->name : 'Unknown Project' }}</span>
                                    </td>
                                    <td>
                                        @if ($project && $project->department)
                                            <span class="badge bg-info text-dark">
                                                {{ $project->department->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($stats && $stats['created_date'])
                                            <span class="badge bg-success text-white" data-bs-toggle="tooltip"
                                                title="Data Created ">
                                                <i class="fas fa-calendar-plus me-1"></i>
                                                {{ \Carbon\Carbon::parse($stats['created_date'])->format('d M Y') }}
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($stats['created_date'])->format('H:i') }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($stats && $stats['last_update'])
                                            <span class="badge bg-warning text-dark" data-bs-toggle="tooltip"
                                                title="Last Update ">
                                                <i class="fas fa-calendar-edit me-1"></i>
                                                {{ \Carbon\Carbon::parse($stats['last_update'])->format('d M Y') }}
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($stats['last_update'])->format('H:i') }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary text-white">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            {{ \Carbon\Carbon::parse($eta)->format('d M Y') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $materialsCount }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary btn-expand"
                                                data-project="{{ $projectId }}">
                                                <i class="fas fa-chevron-down me-1 toggle-icon"></i> Details
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-project"
                                                data-project="{{ $projectId }}"
                                                data-project-name="{{ $project ? $project->name : 'Unknown Project' }}"
                                                data-bs-toggle="tooltip" title="Delete all materials for this project">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="expand-row d-none" id="expand-{{ $projectId }}">
                                    <td colspan="7" class="p-0">
                                        <div class="p-3 bg-light border-top border-bottom">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped mb-0 border">
                                                    <thead class="table-primary">
                                                        <tr>
                                                            <th>Order Type</th>
                                                            <th>Material Name</th>
                                                            <th class="text-center">Qty Needed</th>
                                                            <th>Unit</th>
                                                            <th>ETA Date</th>
                                                            <th>Requested By</th>
                                                            <th class="text-center">Created At</th>
                                                            <th class="text-center">Updated At</th>
                                                            <th class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($plans as $plan)
                                                            <tr>
                                                                <td>
                                                                    @if ($plan->order_type == 'material_req')
                                                                        <span class="badge bg-success">Material Req</span>
                                                                    @else
                                                                        <span class="badge bg-warning text-dark">Purchase
                                                                            Req</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <strong>{{ $plan->material_name }}</strong>
                                                                </td>
                                                                <td class="text-center">
                                                                    {{ number_format($plan->qty_needed, 2) }}
                                                                </td>
                                                                <td>{{ $plan->unit ? $plan->unit->name : '-' }}</td>
                                                                <td>
                                                                    <i class="far fa-calendar-alt me-1 text-muted"></i>
                                                                    {{ \Carbon\Carbon::parse($plan->eta_date)->format('d M Y') }}
                                                                </td>
                                                                <td>
                                                                    @if ($plan->requester)
                                                                        @php
                                                                            $requester = $plan->requester;
                                                                            $department = $requester->department
                                                                                ? $requester->department->name
                                                                                : 'No Department';
                                                                            $role = ucwords(
                                                                                str_replace(
                                                                                    '_',
                                                                                    ' ',
                                                                                    $requester->role ?? 'No Role',
                                                                                ),
                                                                            );
                                                                            $createdAt = $plan->created_at
                                                                                ? $plan->created_at->format('d M Y H:i')
                                                                                : 'Unknown';
                                                                            $tooltipText = "Created by: {$requester->name}\nUsername: {$requester->username}\nDepartment: {$department}\nRole: {$role}\nCreated at: {$createdAt}";
                                                                        @endphp
                                                                        <span class="user-info-tooltip"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top" data-bs-html="true"
                                                                            title="<div class='text-start'>
                                                                    <strong>Created by:</strong> {{ $requester->name }}<br>
                                                                    <strong>Username:</strong> {{ $requester->username }}<br>
                                                                    <strong>Department:</strong> {{ $department }}<br>
                                                                    <strong>Role:</strong> {{ $role }}<br>
                                                                    <strong>Created at:</strong> {{ $createdAt }}
                                                                </div>"
                                                                            style="cursor: pointer;">
                                                                            <i class="fas fa-user me-1 text-primary"></i>
                                                                            {{ $requester->name }}
                                                                        </span>
                                                                    @elseif($plan->requested_by)
                                                                        @php
                                                                            $user = \App\Models\Admin\User::find(
                                                                                $plan->requested_by,
                                                                            );
                                                                            if ($user) {
                                                                                $department = $user->department
                                                                                    ? $user->department->name
                                                                                    : 'No Department';
                                                                                $role = ucwords(
                                                                                    str_replace(
                                                                                        '_',
                                                                                        ' ',
                                                                                        $user->role ?? 'No Role',
                                                                                    ),
                                                                                );
                                                                                $createdAt = $plan->created_at
                                                                                    ? $plan->created_at->format(
                                                                                        'd M Y H:i',
                                                                                    )
                                                                                    : 'Unknown';
                                                                            }
                                                                        @endphp
                                                                        @if ($user)
                                                                            <span class="user-info-tooltip"
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-placement="top"
                                                                                data-bs-html="true"
                                                                                title="<div class='text-start'>
                                                <strong>Created by:</strong> {{ $user->name }}<br>
                                                <strong>Username:</strong> {{ $user->username }}<br>
                                                <strong>Department:</strong> {{ $department }}<br>
                                                <strong>Role:</strong> {{ $role }}<br>
                                                <strong>Created at:</strong> {{ $createdAt }}
                                            </div>"
                                                                                style="cursor: pointer;">
                                                                                <i
                                                                                    class="fas fa-user me-1 text-primary"></i>
                                                                                {{ $user->name }}
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">
                                                                                <i class="fas fa-user me-1"></i>
                                                                                Unknown User
                                                                            </span>
                                                                        @endif
                                                                    @else
                                                                        <span class="text-muted">
                                                                            <i class="fas fa-user me-1"></i>
                                                                            -
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    <small class="text-muted">
                                                                        {{ $plan->created_at->format('d M Y H:i') }}
                                                                    </small>
                                                                </td>
                                                                <td class="text-center">
                                                                    <small class="text-muted">
                                                                        {{ $plan->updated_at->format('d M Y H:i') }}
                                                                    </small>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button
                                                                        class="btn btn-sm btn-outline-danger btn-delete-item"
                                                                        data-id="{{ $plan->id }}"
                                                                        data-material="{{ $plan->material_name }}"
                                                                        data-project="{{ $project ? $project->name : 'Unknown Project' }}"
                                                                        data-bs-toggle="tooltip"
                                                                        title="Delete this material item">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-clipboard-list text-muted mb-2"
                                                style="font-size: 2.5rem;"></i>
                                            <h5>No material planning data available</h5>
                                            <p class="text-muted">Create your first material planning by clicking the
                                                button above</p>
                                            <a href="{{ route('material_planning.create') }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus-circle me-1"></i> Add Material Planning
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Summary Information -->
                @if (!$plannings->isEmpty())
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <h5 class="text-primary">{{ $plannings->count() }}</h5>
                                            <small class="text-muted">Total Projects</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="text-success">
                                                {{ $plannings->flatten()->where('order_type', 'material_req')->count() }}
                                            </h5>
                                            <small class="text-muted">Material Requests</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="text-warning">
                                                {{ $plannings->flatten()->where('order_type', 'purchase_req')->count() }}
                                            </h5>
                                            <small class="text-muted">Purchase Requests</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="text-info">{{ $plannings->flatten()->count() }}</h5>
                                            <small class="text-muted">Total Materials</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .badge {
            font-size: 0.75em;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .project-row:hover {
            background-color: rgba(0, 0, 0, .02);
        }

        .expand-row {
            transition: all 0.3s ease;
        }

        .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #dee2e6;
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        /* TAMBAHAN: Style untuk user info tooltip */
        .user-info-tooltip {
            transition: all 0.2s ease;
        }

        .user-info-tooltip:hover {
            color: #0d6efd !important;
            text-decoration: underline;
        }

        /* Tooltip styling */
        .tooltip-inner {
            max-width: 300px;
            text-align: left !important;
            background-color: #2c3e50;
            color: white;
            font-size: 0.875rem;
            line-height: 1.4;
            padding: 12px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #2c3e50;
        }

        .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
            border-bottom-color: #2c3e50;
        }

        .tooltip.bs-tooltip-start .tooltip-arrow::before {
            border-left-color: #2c3e50;
        }

        .tooltip.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: #2c3e50;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all tooltips including the new user info tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    html: true,
                    delay: {
                        "show": 300,
                        "hide": 100
                    },
                    trigger: 'hover focus'
                });
            });

            // Enhanced tooltip behavior for user info
            document.querySelectorAll('.user-info-tooltip').forEach(function(element) {
                element.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });

                element.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Expand/Collapse functionality
            document.querySelectorAll('.btn-expand').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var projectId = this.getAttribute('data-project');
                    var row = document.getElementById('expand-' + projectId);
                    var icon = this.querySelector('.toggle-icon');

                    if (row.classList.contains('d-none')) {
                        // Close all other expanded rows first
                        document.querySelectorAll('.expand-row:not(.d-none)').forEach(function(
                            openRow) {
                            openRow.classList.add('d-none');
                            var projectRow = document.querySelector(
                                '.project-row[data-project="' + openRow.id.replace(
                                    'expand-', '') + '"]');
                            if (projectRow) {
                                var btn = projectRow.querySelector('.btn-expand');
                                if (btn) {
                                    btn.querySelector('.toggle-icon').classList.remove(
                                        'fa-chevron-up');
                                    btn.querySelector('.toggle-icon').classList.add(
                                        'fa-chevron-down');
                                    btn.classList.remove('btn-primary');
                                    btn.classList.add('btn-outline-primary');
                                }
                            }
                        });

                        // Open this row
                        row.classList.remove('d-none');
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-primary');
                    } else {
                        // Close this row
                        row.classList.add('d-none');
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline-primary');
                    }
                });
            });

            // Auto-submit filter on change
            document.querySelectorAll('#filter-form select').forEach(function(select) {
                select.addEventListener('change', function() {
                    document.getElementById('filter-form').submit();
                });
            });

            // Delete Project (All Materials) functionality
            document.querySelectorAll('.btn-delete-project').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var projectId = this.getAttribute('data-project');
                    var projectName = this.getAttribute('data-project-name');

                    Swal.fire({
                        title: 'Are you sure?',
                        html: `You are about to delete <strong>ALL</strong> material planning for project:<br><strong>${projectName}</strong><br><br>This action cannot be undone!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete all!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Deleting...',
                                html: 'Please wait while we delete the material planning.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Send delete request
                            fetch(`/material-planning/project/${projectId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content'),
                                        'Content-Type': 'application/json',
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Deleted!',
                                            text: data.message,
                                            icon: 'success',
                                            confirmButtonColor: '#3085d6',
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: data.message,
                                            icon: 'error',
                                            confirmButtonColor: '#3085d6',
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An unexpected error occurred.',
                                        icon: 'error',
                                        confirmButtonColor: '#3085d6',
                                    });
                                });
                        }
                    });
                });
            });

            // Delete Individual Item functionality
            document.querySelectorAll('.btn-delete-item').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var itemId = this.getAttribute('data-id');
                    var materialName = this.getAttribute('data-material');
                    var projectName = this.getAttribute('data-project');

                    Swal.fire({
                        title: 'Are you sure?',
                        html: `You are about to delete material:<br><strong>${materialName}</strong><br>from project: <strong>${projectName}</strong><br><br>This action cannot be undone!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Deleting...',
                                html: 'Please wait while we delete the material.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Send delete request
                            fetch(`/material-planning/${itemId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content'),
                                        'Content-Type': 'application/json',
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Deleted!',
                                            text: data.message,
                                            icon: 'success',
                                            confirmButtonColor: '#3085d6',
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: data.message,
                                            icon: 'error',
                                            confirmButtonColor: '#3085d6',
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An unexpected error occurred.',
                                        icon: 'error',
                                        confirmButtonColor: '#3085d6',
                                    });
                                });
                        }
                    });
                });
            });
        });
    </script>
@endpush
