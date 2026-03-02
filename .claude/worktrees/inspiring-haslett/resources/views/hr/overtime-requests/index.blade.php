@extends('layouts.app')

@section('title', 'Overtime Requests')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Overtime Requests</h4>
                    <p class="text-muted mb-0">Manage employee overtime requests</p>
                </div>
                <a href="{{ route('overtime-requests.create') }}" 
                   class="btn btn-primary rounded-3 px-4">
                    <i class="fas fa-plus me-2"></i>New Request
                </a>
            </div>

            <!-- Stats Cards -->
            @if(isset($stats))
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Total Requests</h6>
                                    <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Draft</h6>
                                    <h3 class="mb-0">{{ $stats['draft'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-secondary bg-opacity-10 text-secondary rounded-3">
                                    <i class="fas fa-pen"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Submitted</h6>
                                    <h3 class="mb-0">{{ $stats['submitted'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-info bg-opacity-10 text-info rounded-3">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Approved</h6>
                                    <h3 class="mb-0">{{ $stats['approved'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-success bg-opacity-10 text-success rounded-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Rejected</h6>
                                    <h3 class="mb-0">{{ $stats['rejected'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-3">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('overtime-requests.index') }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small">Employee</label>
                                <select name="employee_id" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Department</label>
                                <select name="department_id" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">OT Code</label>
                                <select name="ot_code" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="Normal Day" {{ request('ot_code') == 'Normal Day' ? 'selected' : '' }}>Normal Day</option>
                                    <option value="Sunday" {{ request('ot_code') == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                                    <option value="Public Holiday" {{ request('ot_code') == 'Public Holiday' ? 'selected' : '' }}>Public Holiday</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Start Date</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">End Date</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-0 rounded-0 m-0 d-flex align-items-center px-4 py-3">
                            <i class="fas fa-check-circle me-2"></i> 
                            <div class="flex-grow-1">{{ session('success') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>  
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger border-0 rounded-0 m-0 d-flex align-items-center px-4 py-3">
                            <i class="fas fa-exclamation-circle me-2"></i> 
                            <div class="flex-grow-1">{{ session('error') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="overtimeTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4 text-center" width="50">No</th>
                                    <th class="border-0">Employee</th>
                                    <th class="border-0 d-none d-xl-table-cell">Department</th>
                                    <th class="border-0 d-none d-xl-table-cell">Project</th>
                                    <th class="border-0">OT</th>
                                    <th class="border-0">Time</th>
                                    <th class="border-0 text-end">Net</th>
                                    <th class="border-0">Approval</th>
                                    <th class="border-0">Overall</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startingNumber = ($overtimeRequests->currentPage() - 1) * $overtimeRequests->perPage() + 1;
                                @endphp
                                @forelse($overtimeRequests as $index => $req)
                                    <tr class="align-middle">
                                        <td class="ps-4 text-center">
                                            <span class="text-muted">{{ $startingNumber + $index }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $req->employee->name ?? '-' }}</span>
                                            <br><small class="text-muted d-xl-none">{{ $req->department->name ?? '' }} {{ $req->jobOrder->name ? ' - '.$req->jobOrder->name : '' }}</small>
                                        </td>
                                        <td class="d-none d-xl-table-cell">
                                            <span class="text-muted">{{ $req->department->name ?? '-' }}</span>
                                        </td>
                                        <td class="d-none d-xl-table-cell">
                                            <span class="fw-medium">{{ $req->jobOrder->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark px-2 py-1 rounded-pill">
                                                @if($req->ot_code == 'Normal Day')
                                                    ND
                                                @elseif($req->ot_code == 'Sunday')
                                                    SUN
                                                @elseif($req->ot_code == 'Public Holiday')
                                                    PH
                                                @else
                                                    {{ $req->ot_code }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $req->start_time->format('d/m H:i') }} - {{ $req->end_time->format('H:i') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-medium">{{ $req->net_hours_formatted }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $hr = $req->hr_approval_status;
                                                $dir = $req->director_approval_status;
                                                $icon = '';
                                                $statusText = '';
                                                if ($hr == 'approved' && $dir == 'approved') {
                                                    $icon = '<i class="fas fa-check-circle text-success"></i>';
                                                    $statusText = 'Approved';
                                                } elseif ($hr == 'rejected' || $dir == 'rejected') {
                                                    $icon = '<i class="fas fa-times-circle text-danger"></i>';
                                                    $statusText = 'Rejected';
                                                } else {
                                                    $icon = '<i class="fas fa-clock text-warning"></i>';
                                                    $statusText = 'Pending';
                                                }
                                            @endphp
                                            <span class="d-inline-flex align-items-center gap-1">
                                                {!! $icon !!}
                                                <span class="small d-none d-md-inline">{{ $statusText }}</span>
                                            </span>
                                            <span class="d-block d-md-none small">
                                                HR: {{ ucfirst($hr) }}, Dir: {{ ucfirst($dir) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $overallStatus = $req->status;
                                                $overallBadgeClass = $overallStatus == 'approved' ? 'bg-success' : ($overallStatus == 'rejected' ? 'bg-danger' : ($overallStatus == 'submitted' ? 'bg-info' : 'bg-secondary'));
                                            @endphp
                                            <span class="badge {{ $overallBadgeClass }} px-3 py-1 rounded-pill">
                                                {{ ucfirst($overallStatus) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <!-- View Details -->
                                                <a href="{{ route('overtime-requests.show', $req->id) }}" 
                                                   class="btn btn-sm btn-outline-info border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- Edit Button (only for draft) -->
                                                @if($req->status == 'draft')
                                                <a href="{{ route('overtime-requests.edit', $req->id) }}" 
                                                   class="btn btn-sm btn-outline-primary border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endif

                                                <!-- Delete Button (only for draft) -->
                                                @if($req->status == 'draft')
                                                <form action="{{ route('overtime-requests.destroy', $req->id) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Delete overtime request #{{ $req->id }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger border-0 px-2 action-btn"
                                                            data-bs-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                
                                                <!-- Submit Button (for draft) -->
                                                @if($req->status == 'draft')
                                                <form action="{{ route('overtime-requests.submit', $req->id) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-success border-0 px-2 action-btn"
                                                            data-bs-toggle="tooltip" title="Submit for Approval">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-clock fa-3x mb-3"></i>
                                                <h5>No Overtime Requests Found</h5>
                                                @if(request()->anyFilled(['employee_id', 'department_id', 'ot_code', 'status', 'start_date', 'end_date']))
                                                    <p class="mb-0">Try adjusting your filters</p>
                                                    <a href="{{ route('overtime-requests.index') }}" 
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-times me-1"></i>Clear Filters
                                                    </a>
                                                @else
                                                    <p class="mb-0">Start by creating your first overtime request</p>
                                                    <a href="{{ route('overtime-requests.create') }}" 
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-plus me-1"></i>New Request
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination Section - DaisyUI Version -->
                @if($overtimeRequests->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div class="text-muted small mb-2 mb-md-0">
                            Menampilkan {{ $overtimeRequests->firstItem() }} - {{ $overtimeRequests->lastItem() }} dari {{ $overtimeRequests->total() }} data
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <!-- DaisyUI Pagination -->
                            <div class="join">
                                <!-- Previous Page Link -->
                                @if($overtimeRequests->onFirstPage())
                                    <button class="join-item btn btn-disabled">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                @else
                                    <a href="{{ $overtimeRequests->previousPageUrl() }}" class="join-item btn">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                @endif
                                
                                <!-- Page Numbers -->
                                @php
                                    $current = $overtimeRequests->currentPage();
                                    $last = $overtimeRequests->lastPage();
                                    
                                    // Menampilkan maksimal 5 nomor halaman
                                    $maxPages = 5;
                                    $start = max($current - floor($maxPages/2), 1);
                                    $end = min($start + $maxPages - 1, $last);
                                    
                                    if ($end - $start < $maxPages - 1) {
                                        $start = max($end - $maxPages + 1, 1);
                                    }
                                    
                                    // Tampilkan tombol halaman pertama jika tidak termasuk dalam range
                                    if ($start > 1) {
                                        echo '<a href="' . $overtimeRequests->url(1) . '" class="join-item btn">1</a>';
                                        if ($start > 2) {
                                            echo '<button class="join-item btn btn-disabled">...</button>';
                                        }
                                    }
                                    
                                    // Tampilkan nomor halaman
                                    for ($i = $start; $i <= $end; $i++) {
                                        if ($i == $current) {
                                            echo '<button class="join-item btn btn-active">' . $i . '</button>';
                                        } else {
                                            echo '<a href="' . $overtimeRequests->url($i) . '" class="join-item btn">' . $i . '</a>';
                                        }
                                    }
                                    
                                    // Tampilkan tombol halaman terakhir jika tidak termasuk dalam range
                                    if ($end < $last) {
                                        if ($end < $last - 1) {
                                            echo '<button class="join-item btn btn-disabled">...</button>';
                                        }
                                        echo '<a href="' . $overtimeRequests->url($last) . '" class="join-item btn">' . $last . '</a>';
                                    }
                                @endphp
                                
                                <!-- Next Page Link -->
                                @if($overtimeRequests->hasMorePages())
                                    <a href="{{ $overtimeRequests->nextPageUrl() }}" class="join-item btn">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                @else
                                    <button class="join-item btn btn-disabled">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                @endif
                            </div>
                            
                            <!-- Info Per Page -->
                            <div class="ms-3 d-none d-md-block">
                                <span class="small text-muted">{{ $overtimeRequests->perPage() }} data per halaman</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Styling for DaisyUI Pagination */
    .join .btn {
        height: 32px;
        min-height: 32px;
        padding: 0 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }
    
    .join .btn-disabled {
        background-color: #f3f4f6;
        color: #9ca3af;
        cursor: not-allowed;
        border-color: #e5e7eb;
    }
    
    .join .btn-active {
        background-color: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    /* Custom Styling */
    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .table td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }

    .table tbody tr {
        transition: all 0.2s;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
    }

    .icon-shape {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    /* Badge styling */
    .badge.bg-warning {
        background-color: #f2a41dff !important;
        color: white !important;
        border: none;
    }

    .badge.bg-success {
        background-color: #04c665ff !important;
        color: white !important;
        border: none;
    }

    .badge.bg-danger {
        background-color: #ef4444 !important;
        color: white !important;
        border: none;
    }

    .badge.bg-secondary {
        background-color: #838b9bff !important;
        color: white !important;
        border: none;
    }

    .badge.bg-light {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        color: #374151 !important;
    }

    .badge.bg-info {
        background-color: #0ea5e9 !important;
        color: white !important;
        border: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .table-responsive {
            border: 0;
        }
        
        .table thead {
            display: none;
        }
        
        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            white-space: normal;
        }
        
        .table tbody td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            min-width: 100px;
            margin-right: 1rem;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
        }
        
        .col-md-2, .col-md-3 {
            margin-bottom: 0.5rem;
        }
        
        .icon-shape {
            width: 32px;
            height: 32px;
        }
        
        .stats-cards .card-body h3 {
            font-size: 1.25rem;
        }
        
        .join {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .join .btn {
            margin: 2px;
            padding: 0.25rem 0.375rem;
            min-width: 32px;
            height: 28px;
        }
        
        .card-footer {
            padding: 1rem !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add data labels for responsive table
    const tableHeaders = document.querySelectorAll('#overtimeTable thead th');
    
    tableHeaders.forEach((header, index) => {
        const text = header.textContent.trim();
        if (text) {
            const cells = document.querySelectorAll(`#overtimeTable tbody td:nth-child(${index + 1})`);
            cells.forEach(cell => {
                cell.setAttribute('data-label', text);
            });
        }
    });

    // Auto-close alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        });
    }, 5000);
});
</script>
@endsection