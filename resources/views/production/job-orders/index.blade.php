@extends('layouts.app')

@section('title', 'Job Order')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Job Orders</h4>
                <a href="{{ route('production.job-orders.create') }}" class="btn btn-primary rounded-3 px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>Create New
                </a>
            </div>

            <!-- Filter Section -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <form id="filterForm" method="GET" action="{{ route('production.job-orders.index') }}" class="mb-0">
                        <div class="row g-2 align-items-end">
                            <!-- Project Filter -->
                            <div class="col-md-4">
                                <label for="project_filter" class="form-label small mb-1">Project</label>
                                <select name="project_filter" id="project_filter" class="form-select form-select-sm">
                                    <option value="">All Projects</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ request('project_filter') == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Department Filter -->
                            <div class="col-md-4">
                                <label for="department_filter" class="form-label small mb-1">Department</label>
                                <select name="department_filter" id="department_filter" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ request('department_filter') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Search -->
                            <div class="col-md-3">
                                <label for="search" class="form-label small mb-1">Search</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm" 
                                       placeholder="Search job order..." value="{{ request('search') }}">
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-md-1">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    @if(request()->anyFilled(['project_filter', 'department_filter', 'search']))
                                    <a href="{{ route('production.job-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-0 rounded-0 m-0 d-flex align-items-center px-4 py-3">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($jobOrders->isEmpty())
                        <div class="text-center py-5">
                            <h5 class="text-muted">The data is not yet available in the job order list.</h5>
                            <a href="{{ route('production.job-orders.create') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Create Now</a>
                        </div>
                    @else
                        <!-- Grid Container -->
                        <div class="table-responsive-container">
                            <div class="grid-container">
                                <!-- Header Grid -->
                                <div class="grid-header">
                                    <div class="grid-cell">Job Order</div>
                                    <div class="grid-cell">Project</div>
                                    <div class="grid-cell">Department</div>
                                    <div class="grid-cell">Description</div>
                                    <div class="grid-cell">Start Date</div>
                                    <div class="grid-cell">End Date</div>
                                    <div class="grid-cell">Notes</div>
                                    <div class="grid-cell">Actions</div>
                                </div>

                                <!-- Data Grid -->
                                @foreach($jobOrders as $jobOrder)
                                <div class="grid-row">
                                    <!-- Kolom 1: Nama Job Order -->
                                    <div class="grid-cell">
                                        <div class="d-flex align-items-center">
                                            <div class="grid-number">{{ $loop->iteration + (($jobOrders->currentPage() - 1) * $jobOrders->perPage()) }}</div>
                                            <div class="ms-3">
                                                <div class="fw-semibold text-dark">{{ $jobOrder->name }}</div>
                                                <div class="small text-muted">{{ $jobOrder->id }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Kolom 2: Project -->
                                    <div class="grid-cell">
                                        @if($jobOrder->project)
                                            <div class="d-flex align-items-center">
                                                <span>{{ $jobOrder->project->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Kolom 3: Department -->
                                    <div class="grid-cell">
                                        @if($jobOrder->department)
                                            <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">
                                                {{ $jobOrder->department->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Kolom 4: Description -->
                                    <div class="grid-cell">
                                        @if($jobOrder->description)
                                            <div class="description-text" data-bs-toggle="tooltip" data-bs-placement="top" 
                                                 title="{{ $jobOrder->description }}">
                                                <span class="small text-truncate d-inline-block" style="max-width: 200px;">
                                                    {{ Str::limit($jobOrder->description, 50) }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="text-muted small">-</div>
                                        @endif
                                    </div>
                                    
                                    <!-- Kolom 5: Start Date -->
                                    <div class="grid-cell">
                                        @if($jobOrder->start_date)
                                            <div class="date-container">
                                                <span class="text-dark">{{ $jobOrder->start_date->format('d M Y') }}</span>
                                            </div>
                                        @else
                                            <div class="text-muted">-</div>
                                        @endif
                                    </div>
                                    
                                    <!-- Kolom 6: End Date -->
                                    <div class="grid-cell">
                                        @if($jobOrder->end_date)
                                            <div class="date-container">
                                                <span class="text-dark">{{ $jobOrder->end_date->format('d M Y') }}</span>
                                            </div>
                                        @else
                                            <div class="text-muted">-</div>
                                        @endif
                                    </div>
                                    
                                    <!-- Kolom 7: Notes -->
                                    <div class="grid-cell">
                                        @if($jobOrder->notes)
                                            <div class="notes-text" data-bs-toggle="tooltip" data-bs-placement="top" 
                                                 title="{{ $jobOrder->notes }}">
                                                <span class="small text-truncate d-inline-block" style="max-width: 200px;">
                                                    {{ Str::limit($jobOrder->notes, 60) }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="text-muted small">-</div>
                                        @endif
                                    </div>
                                    
                                    <!-- Kolom 8: Actions -->
                                    <div class="grid-cell">
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('production.job-orders.show', $jobOrder->id) }}" 
                                               class="btn btn-outline-info btn-sm border-1 px-3 action-btn"
                                               data-bs-toggle="tooltip" data-bs-title="View Details">
                                                <i class="fas fa-eye"></i>
                                                <span class="ms-1 d-none d-sm-inline">View</span>
                                            </a>
                                            <a href="{{ route('production.job-orders.edit', $jobOrder->id) }}" 
                                               class="btn btn-outline-primary btn-sm border-1 px-3 action-btn"
                                               data-bs-toggle="tooltip" data-bs-title="Edit">
                                                <i class="fas fa-edit"></i>
                                                <span class="ms-1 d-none d-sm-inline">Edit</span>
                                            </a>
                                            <form action="{{ route('production.job-orders.destroy', $jobOrder->id) }}" method="POST" 
                                                  class="d-inline" id="delete-form-{{ $jobOrder->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm border-1 px-3 action-btn delete-btn"
                                                        data-id="{{ $jobOrder->id }}"
                                                        data-name="{{ $jobOrder->name }}"
                                                        data-bs-toggle="tooltip" data-bs-title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                    <span class="ms-1 d-none d-sm-inline">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center text-muted small">
                        <span>Showing {{ $jobOrders->firstItem() }} to {{ $jobOrders->lastItem() }} of {{ $jobOrders->total() }} Job Orders</span>
                        <div>
                            {{ $jobOrders->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Grid Container Styling */
    .grid-container {
        display: grid;
        grid-template-columns: 2fr 1.2fr 1fr 1.5fr 1fr 1fr 1.5fr 1.2fr;
        border-bottom: 1px solid #e2e8f0;
        min-width: 1200px;
    }

    .grid-header {
        display: contents;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        background-color: #f8fafc;
    }

    .grid-header .grid-cell {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        border-top: 1px solid #e2e8f0;
        background-color: #f8fafc;
    }

    .grid-row {
        display: contents;
        transition: all 0.2s;
    }

    .grid-row:hover .grid-cell {
        background-color: #f8faff;
    }

    .grid-cell {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        transition: background-color 0.2s;
        background-color: white;
        font-size: 0.875rem;
    }

    .grid-header .grid-cell:not(:last-child),
    .grid-row .grid-cell:not(:last-child) {
        border-right: 1px solid #f1f5f9;
    }

    /* Grid Number Styling */
    .grid-number {
        width: 32px;
        height: 32px;
        background-color: #e0e7ff;
        color: #4f46e5;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    /* Description Text */
    .description-text {
        cursor: pointer;
        transition: all 0.2s;
        line-height: 1.4;
        color: #334155;
    }

    .description-text:hover {
        color: #4f46e5;
    }

    /* Action Button Styling */
    .action-btn {
        font-size: 0.8rem;
        padding: 0.35rem 0.8rem;
        border: 1px solid;
        border-radius: 6px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        height: 32px;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .action-btn.btn-outline-primary {
        color: #4f46e5;
        border-color: #c7d2fe;
        background-color: #f8fafc;
    }

    .action-btn.btn-outline-primary:hover {
        background-color: #e0e7ff;
        border-color: #4f46e5;
    }

    .action-btn.btn-outline-danger {
        color: #dc2626;
        border-color: #fecaca;
        background-color: #f8fafc;
    }

    .action-btn.btn-outline-danger:hover {
        background-color: #fee2e2;
        border-color: #dc2626;
    }

    .action-btn.btn-outline-info {
        color: #0ea5e9;
        border-color: #bae6fd;
        background-color: #f8fafc;
    }

    .action-btn.btn-outline-info:hover {
        background-color: #e0f2fe;
        border-color: #0ea5e9;
    }

    /* Responsive Styles */
    @media (max-width: 992px) {
        .grid-container {
            display: block;
            min-width: auto;
        }
        
        .grid-header {
            display: none;
        }
        
        .grid-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            margin: 0 1rem 1rem 1rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 1.25rem;
        }
        
        .grid-cell {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 0.5rem 0;
            border: none !important;
            border-bottom: 1px solid #f1f5f9 !important;
            background: transparent !important;
            font-size: 0.875rem;
        }
        
        .grid-cell:before {
            content: attr(data-label);
            display: block;
            font-weight: 600;
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }
        
        .grid-cell:first-child {
            grid-column: 1 / -1;
            border-bottom: 1px solid #e2e8f0 !important;
            padding-bottom: 1rem;
            margin-bottom: 0.5rem;
            flex-direction: row;
            align-items: center;
        }
        
        .grid-cell:first-child:before {
            margin-bottom: 0;
            margin-right: 0.75rem;
        }
        
        .grid-cell:nth-child(4),
        .grid-cell:nth-child(7) {
            grid-column: 1 / -1;
        }
        
        .grid-cell:nth-child(8) {
            grid-column: 1 / -1;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
            margin-top: 0.5rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi tooltip
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Tambahkan label untuk responsive
        const cells = document.querySelectorAll('.grid-cell');
        const labels = ['Job Order', 'Project', 'Department', 'Description', 'Start Date', 'End Date', 'Notes', 'Actions'];
        
        cells.forEach((cell, index) => {
            const labelIndex = index % labels.length;
            cell.setAttribute('data-label', labels[labelIndex]);
        });
        
        // Auto-submit on filter change
        document.getElementById('project_filter')?.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        document.getElementById('department_filter')?.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        // Delete confirmation
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const jobOrderId = this.getAttribute('data-id');
                const jobOrderName = this.getAttribute('data-name');
                const form = document.getElementById('delete-form-' + jobOrderId);
                
                if (confirm(`Delete Job Order: ${jobOrderName}? This action cannot be undone!`)) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection