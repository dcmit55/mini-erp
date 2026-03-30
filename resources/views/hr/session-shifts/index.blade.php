@extends('layouts.app')
@section('title', 'Session Shifts')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Session Shifts</h4>
                    <p class="text-muted mb-0">Manage shift definitions — auto-detected from employee clock-in time</p>
                </div>
                <a href="{{ route('session-shifts.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Shift
                </a>
            </div>

            {{-- Main Table --}}
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
                        <table class="table table-hover mb-0" id="shiftsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4" style="width:60px;">No</th>
                                    <th class="border-0">Shift</th>
                                    <th class="border-0">Department</th>
                                    <th class="border-0">Employment Type</th>
                                    <th class="border-0">Work Hours</th>
                                    <th class="border-0">Break 1</th>
                                    <th class="border-0">Break 2</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0 text-center" style="width:160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shifts as $index => $shift)
                                    <tr class="align-middle">
                                        <td class="ps-4 text-center">
                                            <span class="table-number">{{ $loop->iteration }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-3 py-1 fw-semibold fs-6">
                                                {{ $shift->type_of_shift }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($shift->department)
                                                <span class="small">{{ $shift->department->name }}</span>
                                            @else
                                                <span class="text-muted small">Default (all departments)</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($shift->for_wna)
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    <i></i>WNA
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    <i></i>WNI
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-3 py-1">
                                                {{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($shift->break_start)
                                                <span class="badge bg-light text-dark border px-3 py-1">
                                                    {{ substr($shift->break_start, 0, 5) }} – {{ substr($shift->break_end, 0, 5) }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($shift->break2_start)
                                                <span class="badge bg-light text-dark border px-3 py-1">
                                                    {{ substr($shift->break2_start, 0, 5) }} – {{ substr($shift->break2_end, 0, 5) }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($shift->is_active)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="fas fa-circle me-1" style="font-size:7px; vertical-align:middle;"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                    <i class="fas fa-circle me-1" style="font-size:7px; vertical-align:middle;"></i>Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('session-shifts.edit', $shift) }}"
                                                   class="btn btn-sm btn-outline-primary border-0 px-2 py-1 action-btn"
                                                   data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('session-shifts.destroy', $shift) }}" method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Delete shift {{ $shift->type_of_shift }} ({{ $shift->department?->name ?? 'Default' }})?\n\nThis cannot be undone.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger border-0 px-2 py-1 action-btn"
                                                            data-bs-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-layer-group fa-3x mb-3"></i>
                                                <h5>No Shifts Defined</h5>
                                                <p class="mb-0">Start by adding a shift definition</p>
                                                <a href="{{ route('session-shifts.create') }}"
                                                   class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                    <i class="fas fa-plus me-1"></i>Add Shift
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Detection info --}}
                <div class="card-footer bg-white border-top py-3 px-4">
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Auto-detection:</strong>
                        When an employee clocks in, the system matches their department + clock-in time against the
                        <strong>Detect Window</strong>. WNA employees are matched to WNA-specific shifts.
                        If no department shift is found, the <em>Default</em> shift is used.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

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
        vertical-align: middle;
    }
    .table tbody tr { transition: all 0.2s; }
    .table tbody tr:hover { background-color: #f8fafc; }
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
    .table td:first-child, .table th:first-child { padding-left: 1.5rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert .btn-close').forEach(btn => btn.click());
    }, 5000);
});
</script>
@endsection
