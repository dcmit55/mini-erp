@extends('layouts.app')

@section('title', 'Employee Work Policies')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Employee Work Policies</h4>
                    <p class="text-muted mb-0">Manage standard working hours per employee</p>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-upload me-1"></i> Import
                    </button>
                    <a href="{{ route('employee-work-policies.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> New Policy
                    </a>
                </div>
            </div>

            <!-- Simple Filter -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('employee-work-policies.index') }}">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by name or employee number..." 
                                           value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    @if(request()->has('search') && request('search') != '')
                                    <a href="{{ route('employee-work-policies.index') }}" 
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>
                            @if(request()->filled('search'))
                            <div class="col-md-2">
                                <a href="{{ route('employee-work-policies.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                            @endif
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
                        <table class="table table-hover mb-0" id="policiesTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4" style="width: 70px;">No</th>
                                    <th class="border-0">Employee No</th>
                                    <th class="border-0">Employee Name</th>
                                    <th class="border-0">Weekday Time</th>
                                    <th class="border-0">Weekday Total</th>
                                    <th class="border-0">Saturday Time</th>
                                    <th class="border-0">Saturday Total</th>
                                    <th class="border-0 text-center" style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($policies as $index => $policy)
                                    <tr class="align-middle">
                                        <td class="ps-4 text-center" style="vertical-align: middle;">
                                            <span class="table-number">
                                                {{ ($policies->currentPage() - 1) * $policies->perPage() + $loop->iteration }}
                                            </span>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <span class="fw-medium">{{ $policy->employee_no }}</span>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <a href="{{ route('employees.show', $policy->employee_id) }}" class="text-decoration-none">
                                                {{ $policy->employee->name ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            @if($policy->weekday_start && $policy->weekday_end)
                                                <span class="badge bg-light text-dark border px-3 py-1">
                                                    {{ \Carbon\Carbon::parse($policy->weekday_start)->format('H:i') }} - 
                                                    {{ \Carbon\Carbon::parse($policy->weekday_end)->format('H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <span class="badge bg-light text-dark border px-3 py-1">
                                                {{ number_format($policy->weekday_hours, 2) }} hrs
                                            </span>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            @if($policy->saturday_start && $policy->saturday_end)
                                                <span class="badge bg-light text-dark border px-3 py-1">
                                                    {{ \Carbon\Carbon::parse($policy->saturday_start)->format('H:i') }} - 
                                                    {{ \Carbon\Carbon::parse($policy->saturday_end)->format('H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <span class="badge bg-light text-dark border px-3 py-1">
                                                {{ number_format($policy->saturday_hours, 2) }} hrs
                                            </span>
                                        </td>
                                        <td class="text-center" style="vertical-align: middle;">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="{{ route('employee-work-policies.edit', $policy) }}" 
                                                   class="btn btn-sm btn-outline-primary border-0 px-3 py-1 action-btn"
                                                   data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                                
                                                <form action="{{ route('employee-work-policies.destroy', $policy) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this work policy?\n\nThis action will remove the policy and cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger border-0 px-3 py-1 action-btn"
                                                            data-bs-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-clock fa-3x mb-3"></i>
                                                <h5>No Work Policies Found</h5>
                                                @if(request()->filled('search'))
                                                    <p class="mb-0">Try adjusting your search</p>
                                                    <a href="{{ route('employee-work-policies.index') }}" 
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-times me-1"></i>Clear Search
                                                    </a>
                                                @else
                                                    <p class="mb-0">Start by creating a work policy for an employee</p>
                                                    <a href="{{ route('employee-work-policies.create') }}" 
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-plus me-1"></i>Create Policy
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
                @if($policies->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $policies->firstItem() }} to {{ $policies->lastItem() }} of {{ $policies->total() }} entries
                        </div>
                        <div>
                            {{ $policies->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Import (tetap menggunakan include, tidak diembed) -->
@include('hr.employee-work-policies.import-modal')

<style>
    .table-number {
        display: inline-block;
        width: 36px;
        height: 36px;
        line-height: 36px;
        background-color: #eef2ff;
        color: #4f46e5;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    
    tr:hover .table-number {
        background-color: #4f46e5;
        color: white;
        transform: scale(1.05);
    }

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
        border-bottom: 1px solid #f1f5f9;
    }

    .table tbody tr {
        transition: all 0.2s;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    .action-btn {
        border-radius: 6px;
        transition: all 0.2s;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .action-btn:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
    }

    .badge.bg-light {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        color: #374151 !important;
        font-weight: 500;
        white-space: nowrap;
    }

    .fw-medium {
        font-weight: 500;
    }

    .input-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }

    .input-group-sm .form-control {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Kolom spacing */
    .table td:first-child {
        padding-left: 1.5rem;
    }
    
    .table th:first-child {
        padding-left: 1.5rem;
    }

    /* Responsive untuk mobile */
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
            padding: 0.75rem 0;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }
        
        .table tbody td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            min-width: 120px;
            margin-right: 1rem;
        }
        
        /* Kolom No khusus untuk mobile */
        .table tbody td:first-child {
            display: block;
            text-align: center;
            padding: 0.5rem 0 1rem 0;
            border-bottom: 2px solid #eef2ff;
        }
        
        .table tbody td:first-child:before {
            content: "No";
            display: block;
            margin-bottom: 0.5rem;
            min-width: auto;
        }
        
        .table-number {
            margin: 0 auto;
        }
        
        .action-btn {
            width: 100%;
            margin-top: 0.5rem;
            text-align: center;
        }
        
        .d-flex.gap-2 {
            flex-direction: column;
            width: 100%;
        }
        
        /* Hilangkan border pada row terakhir */
        .table tbody tr:last-child td {
            border-bottom: none;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Set data-label untuk responsive
    const tableHeaders = document.querySelectorAll('#policiesTable thead th');
    
    tableHeaders.forEach((header, index) => {
        const text = header.textContent.trim();
        if (text) {
            const cells = document.querySelectorAll(`#policiesTable tbody td:nth-child(${index + 1})`);
            cells.forEach(cell => {
                cell.setAttribute('data-label', text);
            });
        }
    });

    // Auto-hide alerts setelah 5 detik
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        });
    }, 5000);
});

// AJAX Import (tetap ada di sini)
$(document).ready(function() {
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var $btn = $('#importBtn');
        var $progress = $('#importProgress');
        var $result = $('#importResult');
        var $failedContainer = $('#failedRowsContainer');
        var $failedBody = $('#failedRowsBody');

        $btn.prop('disabled', true);
        $progress.removeClass('d-none');
        $result.html('');
        $failedContainer.addClass('d-none');
        $failedBody.empty();

        $.ajax({
            url: "{{ route('employee-work-policies.import') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $progress.addClass('d-none');
                $result.html('<div class="alert alert-success py-1 px-2 mb-0">' + response.message + '</div>');
                setTimeout(function() {
                    $('#importModal').modal('hide');
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                $progress.addClass('d-none');
                if (xhr.responseJSON && xhr.responseJSON.failed_rows) {
                    var failedRows = xhr.responseJSON.failed_rows;
                    $.each(failedRows, function(index, item) {
                        var row = item.row;
                        var errorMsg = item.error;
                        $failedBody.append('<tr>' +
                            '<td>' + (row.employee_no || '-') + '</td>' +
                            '<td>' + (row.name || '-') + '</td>' +
                            '<td class="text-danger">' + errorMsg + '</td>' +
                            '</tr>');
                    });
                    $failedContainer.removeClass('d-none');
                    var message = xhr.responseJSON.message || 'Import completed with errors.';
                    $result.html('<div class="alert alert-warning py-1 px-2 mb-0">' + message + '</div>');
                } else {
                    var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload failed.';
                    $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' + errorMsg + '</div>');
                }
                $btn.prop('disabled', false);
            }
        });
    });

    $('#importModal').on('hidden.bs.modal', function () {
        $('#importForm')[0].reset();
        $('#importResult').empty();
        $('#importProgress').addClass('d-none');
        $('#failedRowsContainer').addClass('d-none');
        $('#failedRowsBody').empty();
        $('#importBtn').prop('disabled', false);
    });
});
</script>
@endsection