@extends('layouts.app')

@section('title', 'Warning Letters')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Warning Letters</h4>
                    <p class="text-muted mb-0">Manage SP1–SP4 warning letter lifecycle</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('warning-batches.create') }}" class="btn btn-outline-primary rounded-3 px-3">
                        <i class="bi bi-people-fill me-1"></i> Bulk Issue
                    </a>
                    <a href="{{ route('warning-letters.create') }}" class="btn btn-primary rounded-3 px-4">
                        <i class="fas fa-plus me-2"></i>New Warning Letter
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Active SP</h6>
                                    <h3 class="mb-0">{{ $stats['total_active'] }}</h3>
                                </div>
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-3">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Draft</h6>
                                    <h3 class="mb-0">{{ \App\Models\Hr\WarningLetter::where('status','draft')->count() }}</h3>
                                </div>
                                <div class="icon-shape bg-secondary bg-opacity-10 text-secondary rounded-3">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Active SP4</h6>
                                    <h3 class="mb-0 text-danger">{{ $stats['sp4_active'] }}</h3>
                                </div>
                                <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Expiring (14 days)</h6>
                                    <h3 class="mb-0">{{ $stats['expiring_soon'] }}</h3>
                                </div>
                                <div class="icon-shape bg-secondary bg-opacity-10 text-secondary rounded-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('warning-letters.index') }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small">Employee</label>
                                <select name="employee_id" id="filterEmployee" class="form-select form-select-sm select2-employee-filter">
                                    <option value="">All Employees</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->employee_no }} — {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">SP Level</label>
                                <select name="sp_level" class="form-select form-select-sm">
                                    <option value="">All Levels</option>
                                    @foreach([1,2,3,4] as $lvl)
                                        <option value="{{ $lvl }}" {{ request('sp_level') == $lvl ? 'selected' : '' }}>SP{{ $lvl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    @foreach(\App\Models\Hr\WarningLetter::STATUS_LABELS as $key => $label)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">From</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">To</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="{{ route('warning-letters.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table --}}
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
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4 text-center" width="50">No</th>
                                    <th class="border-0">Employee</th>
                                    <th class="border-0 d-none d-xl-table-cell">Department</th>
                                    <th class="border-0">Level</th>
                                    <th class="border-0 d-none d-lg-table-cell">Category</th>
                                    <th class="border-0">Letter No.</th>
                                    <th class="border-0 d-none d-lg-table-cell">Issued</th>
                                    <th class="border-0">Valid Until</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $start = ($letters->currentPage() - 1) * $letters->perPage() + 1; @endphp
                                @forelse($letters as $i => $letter)
                                <tr class="align-middle">
                                    <td class="ps-4 text-center">
                                        <span class="text-muted">{{ $start + $i }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $letter->employee->name }}</span>
                                        <br><small class="text-muted d-xl-none">{{ $letter->employee->department?->name }}</small>
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        <span class="text-muted">{{ $letter->employee->department?->name ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $spColors = [1=>['bg'=>'info','text'=>'white'],2=>['bg'=>'warning','text'=>'dark'],3=>['bg'=>'warning','text'=>'dark'],4=>['bg'=>'danger','text'=>'white']];
                                            $sc = $spColors[$letter->sp_level] ?? ['bg'=>'secondary','text'=>'white'];
                                        @endphp
                                        <span class="badge bg-{{ $sc['bg'] }} text-{{ $sc['text'] }} px-2 py-1 rounded-pill">SP{{ $letter->sp_level }}</span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="text-muted">{{ $letter->violationCategory->name }}</small>
                                    </td>
                                    <td>
                                        <span class="font-monospace" style="font-size:.78rem">{{ $letter->letter_number }}</span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="text-muted">{{ $letter->issued_date?->format('d/m/Y') ?? '—' }}</small>
                                    </td>
                                    <td>
                                        @if($letter->valid_until)
                                            <small class="{{ $letter->valid_until->isPast() ? 'text-danger fw-semibold' : ($letter->valid_until->diffInDays(now()) <= 14 ? 'text-warning fw-semibold' : 'text-muted') }}">
                                                {{ $letter->valid_until->format('d/m/Y') }}
                                            </small>
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $sBg = \App\Models\Hr\WarningLetter::STATUS_COLORS[$letter->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $sBg }} px-2 py-1 rounded-pill {{ $letter->status === 'pending_approval' ? 'text-dark' : '' }}">
                                            {{ $letter->statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('warning-letters.show', $letter) }}"
                                               class="btn btn-sm btn-outline-info border-0 px-2"
                                               data-bs-toggle="tooltip" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($letter->isEditable())
                                                <a href="{{ route('warning-letters.edit', $letter) }}"
                                                   class="btn btn-sm btn-outline-primary border-0 px-2"
                                                   data-bs-toggle="tooltip" title="Edit Draft">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if(in_array($letter->status, ['approved','acknowledged']))
                                                <a href="{{ route('warning-letters.pdf', $letter) }}"
                                                   class="btn btn-sm btn-outline-danger border-0 px-2"
                                                   data-bs-toggle="tooltip" title="Download PDF" target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                        No warning letters found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($letters->hasPages())
                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center px-4 py-3">
                    <small class="text-muted">
                        Showing {{ $letters->firstItem() }}–{{ $letters->lastItem() }} of {{ $letters->total() }} records
                    </small>
                    {{ $letters->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    $('.select2-employee-filter').select2({
        theme: 'bootstrap-5',
        placeholder: 'All Employees',
        allowClear: true,
        width: '100%',
    });
});
</script>
@endpush
