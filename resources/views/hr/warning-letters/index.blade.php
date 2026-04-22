@extends('layouts.app')

@section('title', 'Warning Letters')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('warning-letters.dashboard') }}"
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Warning Letters</h5>
                    <p class="text-muted small mb-0">Kelola siklus SP1–SP3 karyawan</p>
                </div>
                <div class="d-flex gap-2">
                    @can('hr.warning-batch.create')
                    <a href="{{ route('warning-batches.create') }}"
                       class="btn btn-outline-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-users me-1"></i>Bulk Issue
                    </a>
                    @endcan
                    @can('hr.warning-letter.create')
                    <a href="{{ route('warning-letters.create') }}"
                       class="btn btn-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-plus me-1"></i>New Letter
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Flash --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Stats --}}
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-file-alt text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Active SP</h6>
                                    <h4 class="mb-0">{{ $stats['total_active'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-pencil-alt text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Draft</h6>
                                    <h4 class="mb-0">{{ \App\Models\Hr\WarningLetter::where('status','draft')->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Active SP3</h6>
                                    <h4 class="mb-0 text-danger">{{ $stats['sp3_active'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Expiring 14 Hari</h6>
                                    <h4 class="mb-0 text-warning">{{ $stats['expiring_soon'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter --}}
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('warning-letters.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <select name="employee_id" class="form-select form-select-sm border-1 rounded-2 select2-employee-filter">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employee_no }} — {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="sp_level" class="form-select form-select-sm border-1 rounded-2">
                                <option value="">All Levels</option>
                                @foreach([1,2,3] as $lvl)
                                    <option value="{{ $lvl }}" {{ request('sp_level') == $lvl ? 'selected' : '' }}>SP{{ $lvl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm border-1 rounded-2">
                                <option value="">All Status</option>
                                @foreach(\App\Models\Hr\WarningLetter::STATUS_LABELS as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control form-control-sm border-1 rounded-2" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control form-control-sm border-1 rounded-2" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-1 d-flex">
                            <div class="d-flex gap-1 w-100">
                                <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3 w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <a href="{{ route('warning-letters.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    @if($letters->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3 d-block"></i>
                            <h6 class="text-muted">Tidak ada warning letter ditemukan</h6>
                            <p class="small text-muted">Buat surat peringatan baru dengan tombol New Letter.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center" style="width:50px;">No</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Employee</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 d-none d-md-table-cell">Department</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Level</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 d-none d-lg-table-cell">Kategori</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">No. Surat</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 d-none d-lg-table-cell">Terbit</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Berlaku S/D</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Status</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $start = ($letters->currentPage() - 1) * $letters->perPage() + 1; @endphp
                                    @foreach($letters as $i => $letter)
                                    <tr class="border-top">
                                        <td class="px-3 py-2 text-center text-muted">{{ $start + $i }}</td>
                                        <td class="px-3 py-2">
                                            <div class="fw-medium" style="font-size:0.85rem;">{{ $letter->employee->name }}</div>
                                            <small class="text-muted d-md-none">{{ $letter->employee->department?->name }}</small>
                                        </td>
                                        <td class="px-3 py-2 small text-muted d-none d-md-table-cell">
                                            {{ $letter->employee->department?->name ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            @php
                                                $spBadge = [
                                                    1 => ['color'=>'info',    'text'=>''],
                                                    2 => ['color'=>'warning', 'text'=>'text-dark'],
                                                    3 => ['color'=>'danger',  'text'=>''],
                                                ];
                                                $sb = $spBadge[$letter->sp_level] ?? ['color'=>'secondary','text'=>''];
                                            @endphp
                                            <span class="badge bg-{{ $sb['color'] }} bg-opacity-10 text-{{ $sb['color'] }} border border-{{ $sb['color'] }} border-opacity-25 rounded-2 px-2 py-1 {{ $sb['text'] }}">
                                                SP{{ $letter->sp_level }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 small text-muted d-none d-lg-table-cell">
                                            {{ $letter->violationCategory->name }}
                                        </td>
                                        <td class="px-3 py-2" style="font-size:0.8rem;">
                                            {{ $letter->letter_number }}
                                        </td>
                                        <td class="px-3 py-2 small text-muted d-none d-lg-table-cell">
                                            {{ $letter->issued_date?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 small">
                                            @if($letter->valid_until)
                                                @php
                                                    $isExpired = $letter->valid_until->isPast();
                                                    $isNear    = !$isExpired && $letter->valid_until->diffInDays(now()) <= 14;
                                                @endphp
                                                <span class="{{ $isExpired ? 'text-danger fw-medium' : ($isNear ? 'text-warning fw-medium' : 'text-muted') }}">
                                                    {{ $letter->valid_until->format('d/m/Y') }}
                                                    @if($isExpired) <i class="fas fa-times-circle ms-1"></i>
                                                    @elseif($isNear) <i class="fas fa-clock ms-1"></i>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 small">
                                            @php $sBg = \App\Models\Hr\WarningLetter::STATUS_COLORS[$letter->status] ?? 'secondary'; @endphp
                                            <span class="badge bg-{{ $sBg }} bg-opacity-10 text-{{ $sBg }} border border-{{ $sBg }} border-opacity-25 rounded-2 px-2 py-1 {{ $letter->status === 'pending_approval' ? 'text-dark' : '' }}">
                                                {{ $letter->statusLabel }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <a href="{{ route('warning-letters.show', $letter) }}"
                                                   class="btn btn-outline-info btn-sm rounded-2 px-2 py-1"
                                                   data-bs-toggle="tooltip" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('hr.warning-letter.edit')
                                                @if($letter->isEditable())
                                                    <a href="{{ route('warning-letters.edit', $letter) }}"
                                                       class="btn btn-outline-primary btn-sm rounded-2 px-2 py-1"
                                                       data-bs-toggle="tooltip" title="Edit Draft">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                                @endcan
                                                @if(in_array($letter->status, ['approved','acknowledged']))
                                                    <a href="{{ route('warning-letters.pdf', $letter) }}"
                                                       class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1"
                                                       data-bs-toggle="tooltip" title="Download PDF" target="_blank">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($letters->hasPages())
                        <div class="card-footer border-0 bg-light px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ $letters->firstItem() }} to {{ $letters->lastItem() }} of {{ $letters->total() }} entries
                                </div>
                                {{ $letters->links() }}
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
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

<style>
.form-control, .form-select { border-color: #e2e8f0; font-size: 0.9rem; }
.form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 0.2rem rgba(79,70,229,.1); }
.btn { font-size: 0.9rem; font-weight: 500; }
.btn-primary { background-color: #4f46e5; border-color: #4f46e5; }
.btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
.table-hover tbody tr:hover { background-color: rgba(79,70,229,.04); }
.badge { font-size: 0.75rem; font-weight: 500; }
.card { background: #fff; border: 1px solid #e2e8f0; }
.text-muted { color: #6b7280 !important; }
.text-dark { color: #374151 !important; }
.rounded-2 { border-radius: .5rem !important; }
.rounded-3 { border-radius: .75rem !important; }
.table td, .table th { vertical-align: middle; }
.btn-sm { padding: .25rem .5rem; font-size: .8rem; }
.fw-medium { font-weight: 500 !important; }
.bg-light { background-color: #f8fafc !important; }
</style>
