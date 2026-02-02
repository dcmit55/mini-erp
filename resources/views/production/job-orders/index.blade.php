@extends('layouts.app')

@section('title', 'Job Order')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header dengan hanya tombol Buat Baru -->
            <div class="d-flex justify-content-end mb-4">
                <a href="{{ route('production.job-orders.create') }}" class="btn btn-primary rounded-3 px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>Buat Baru
                </a>
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
                            <img src="https://illustrations.popsy.co/gray/not-found.svg" alt="empty" style="width: 150px;" class="mb-3">
                            <h5 class="text-muted">Belum ada Job Order</h5>
                            <p class="text-muted small">Mulai dengan membuat perintah kerja pertama Anda.</p>
                            <a href="{{ route('production.job-orders.create') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Buat Sekarang</a>
                        </div>
                    @else
                        <!-- Grid Container dengan garis tipis -->
                        <div class="grid-container">
                            <!-- Header Grid -->
                            <div class="grid-header">
                                <div class="grid-cell">Job Order</div>
                                <div class="grid-cell">Project</div>
                                <div class="grid-cell">Department</div>
                                <div class="grid-cell">Assigned By</div>
                                <div class="grid-cell">Start Date</div>
                                <div class="grid-cell">End Date</div>
                                <div class="grid-cell">Notes</div>
                                <div class="grid-cell">Action Button</div>
                            </div>

                            <!-- Data Grid -->
                            @foreach($jobOrders as $jobOrder)
                            <div class="grid-row">
                                <!-- Kolom 1: Nama Job Order -->
                                <div class="grid-cell">
                                    <div class="d-flex align-items-center">
                                        <div class="grid-number me-3">{{ $loop->iteration }}</div>
                                        <div class="fw-semibold text-dark">{{ $jobOrder->name }}</div>
                                    </div>
                                </div>
                                
                                <!-- Kolom 2: Project -->
                                <div class="grid-cell">
                                    <span class="text-dark">
                                        @if($jobOrder->project)
                                            {{ $jobOrder->project->name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </span>
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
                                
                                <!-- Kolom 4: Assigned By -->
                                <div class="grid-cell">
                                    @if($jobOrder->assignee)
                                        <div class="d-flex align-items-center">
                                            <div class="assignee-avatar me-2">
                                                <div class="avatar-circle bg-primary text-white">
                                                    {{ substr($jobOrder->assignee->username, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="small fw-medium">{{ $jobOrder->assignee->username }}</div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
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
                                        <a href="{{ route('production.job-orders.edit', $jobOrder->id) }}" 
                                           class="btn btn-outline-primary btn-sm border-1 px-3 action-btn"
                                           data-bs-toggle="tooltip" data-bs-title="Edit">
                                            <i class="fas fa-edit"></i>
                                            <span class="ms-1 d-none d-sm-inline">Edit</span>
                                        </a>
                                        <form action="{{ route('production.job-orders.destroy', $jobOrder->id) }}" method="POST" 
                                              onsubmit="return confirm('Hapus job order ini?')" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-outline-danger btn-sm border-1 px-3 action-btn"
                                                    data-bs-toggle="tooltip" data-bs-title="Hapus">
                                                <i class="fas fa-trash"></i>
                                                <span class="ms-1 d-none d-sm-inline">Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center text-muted small">
                        <span>Total: <strong>{{ $jobOrders->total() }}</strong> Job Orders</span>
                        <div>
                            {{ $jobOrders->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Global Background */
    body {
        background-color: #f8fafc;
    }

    /* Grid Container Styling */
    .grid-container {
        display: grid;
        grid-template-columns: 2fr 1.2fr 1fr 1.2fr 1fr 1fr 1.5fr 1.2fr;
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

    /* Grid garis vertikal tipis */
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

    /* Assignee Avatar */
    .avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
        background-color: #4f46e5;
        flex-shrink: 0;
    }

    /* Date Container */
    .date-container {
        font-size: 0.875rem;
        color: #334155;
    }

    /* Notes Styling */
    .notes-text {
        cursor: pointer;
        transition: all 0.2s;
        line-height: 1.4;
        color: #334155;
    }

    .notes-text:hover {
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

    /* Badge Styling */
    .badge {
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    /* Pagination Styling */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        border-radius: 6px !important;
        margin: 0 2px;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
    }

    .page-item.active .page-link {
        background-color: #4f46e5;
        border-color: #4f46e5;
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
    }

    /* Horizontal Scroll Container */
    .table-responsive-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Responsive Styles */
    @media (max-width: 1400px) {
        .grid-container {
            min-width: 1100px;
        }
        
        .grid-cell {
            padding: 1rem;
        }
    }

    @media (max-width: 1200px) {
        .grid-container {
            min-width: 1000px;
        }
        
        .action-btn span {
            display: none;
        }
        
        .action-btn {
            min-width: 34px;
            padding: 0.35rem;
        }
        
        .grid-cell {
            padding: 0.875rem 1rem;
        }
    }

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
        
        .grid-cell:last-child,
        .grid-cell:nth-last-child(2) {
            border-bottom: none !important;
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
        
        /* Job Order Cell */
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
        
        /* Notes Cell */
        .grid-cell:nth-child(7) {
            grid-column: 1 / -1;
            border-top: 1px solid #f1f5f9;
            padding-top: 1rem;
            margin-top: 0.5rem;
        }
        
        /* Actions Cell */
        .grid-cell:nth-child(8) {
            grid-column: 1 / -1;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
            margin-top: 0.5rem;
        }
        
        /* Align dates and text */
        .grid-cell:nth-child(5),
        .grid-cell:nth-child(6) {
            align-items: flex-start;
        }
        
        .date-container {
            font-size: 0.875rem;
        }
        
        .action-btn span {
            display: inline-block !important;
        }
        
        .action-btn {
            height: 34px;
        }
    }

    @media (max-width: 768px) {
        .grid-row {
            margin: 0 0.75rem 1rem 0.75rem;
            padding: 1rem;
        }
        
        .grid-cell:first-child {
            padding-bottom: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .grid-cell:nth-child(7),
        .grid-cell:nth-child(8) {
            padding-top: 0.875rem;
            margin-top: 0.5rem;
        }
    }

    @media (max-width: 576px) {
        .grid-row {
            grid-template-columns: 1fr;
            padding: 0.875rem;
        }
        
        .grid-cell:not(:first-child):not(:nth-child(7)):not(:nth-child(8)) {
            grid-column: 1 / -1;
        }
        
        .action-btn {
            width: 100%;
            margin-bottom: 0.5rem;
            justify-content: center;
        }
        
        .grid-cell:nth-child(8) .d-flex {
            flex-direction: column;
            width: 100%;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
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
        const labels = ['Job Order', 'Project', 'Department', 'Assigned By', 'Start Date', 'End Date', 'Catatan', 'Aksi'];
        
        cells.forEach((cell, index) => {
            const labelIndex = index % labels.length;
            cell.setAttribute('data-label', labels[labelIndex]);
        });
        
        // Wrap grid container in responsive div
        const gridContainer = document.querySelector('.grid-container');
        if (gridContainer) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive-container';
            gridContainer.parentNode.insertBefore(wrapper, gridContainer);
            wrapper.appendChild(gridContainer);
        }
    });
</script>
@endsection