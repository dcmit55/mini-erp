@extends('layouts.app')

@section('title', 'Kasbon — Admin')

@section('content')
<div class="container-fluid py-3">
    <div class="col-12">

        {{-- Header --}}
        <div class="position-relative d-flex align-items-center mb-3" style="min-height:44px;">
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="fw-semibold text-dark">Finance</span>
                <span class="text-muted">/</span>
                <span class="fw-semibold text-dark">Cash Advance</span>
            </div>
            <div class="position-absolute start-50 translate-middle-x text-center d-none d-md-block" style="pointer-events:none;">
                <h5 class="text-dark fw-semibold mb-0">Cash Advance List</h5>
            </div>
            <div class="ms-auto flex-shrink-0 d-flex gap-2">
                <a href="{{ url('/cek-kasbon') }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-search me-1"></i>Check Status
                </a>
                <a href="{{ route('kasbon.create') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-external-link-alt me-1"></i>Request Kasbon
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
            {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Summary Cards --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small mb-1">Awaiting Review</div>
                        <div class="fw-bold fs-4 text-warning">{{ $summary['pending'] }}</div>
                        <div class="text-muted" style="font-size:.7rem;">pending requests</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small mb-1">Active Cash Advances</div>
                        <div class="fw-bold fs-4 text-primary">{{ $summary['active'] }}</div>
                        <div class="text-muted" style="font-size:.7rem;">currently running</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small mb-1">Total Outstanding</div>
                        <div class="fw-bold fs-5 text-danger">Rp {{ number_format($summary['outstanding'], 0, ',', '.') }}</div>
                        <div class="text-muted" style="font-size:.7rem;">disbursed + repaying</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('kasbon.admin.index') }}" class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm rounded-2">
                            <option value="">All</option>
                            @foreach ([
                                'pending'      => 'Pending',
                                'under_review' => 'Under Review',
                                'approved'     => 'Approved',
                                'rejected'     => 'Rejected',
                                'disbursed'    => 'Disbursed',
                                'repaying'     => 'Repaying',
                                'settled'      => 'Settled',
                            ] as $val => $label)
                                <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Department</label>
                        <select name="department_id" class="form-select form-select-sm rounded-2">
                            <option value="">All</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-8 col-md-3">
                        <label class="form-label small text-dark mb-1">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm rounded-2"
                               placeholder="Name, Employee ID, Ref No..." value="{{ request('search') }}">
                    </div>
                    <div class="col-4 col-md-1 d-flex align-items-end">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm rounded-2 px-2">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="{{ route('kasbon.admin.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table (desktop) --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden d-none d-md-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.875rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center" style="width:44px;">No</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Ref No.</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Name / Employee ID</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Department</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-end">Amount</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Tenor</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Status</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Submitted</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-end" style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($kasbons->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-folder-open fa-2x text-muted mb-2 d-block"></i>
                                    <span class="text-muted small">No cash advance requests found.</span>
                                </td>
                            </tr>
                            @else
                            @php $startNumber = ($kasbons->currentPage() - 1) * $kasbons->perPage() + 1; @endphp
                            @foreach($kasbons as $i => $k)
                            <tr>
                                <td class="px-3 py-2 text-center text-muted">{{ $startNumber + $i }}</td>
                                <td class="px-3 py-2">
                                    <span class="font-monospace small">{{ $k->ref_number }}</span>
                                </td>
                                <td class="px-3 py-2 small">
                                    {{ $k->nama_lengkap }}
                                    <span class="text-muted ms-1">{{ $k->nik_karyawan }}</span>
                                </td>
                                <td class="px-3 py-2 text-muted small">{{ $k->department->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-end small">
                                    <div>Rp {{ number_format($k->jumlah_diminta, 0, ',', '.') }}</div>
                                    @if($k->jumlah_disetujui && $k->jumlah_disetujui != $k->jumlah_diminta)
                                    <div class="text-success" style="font-size:.7rem;">✓ Rp {{ number_format($k->jumlah_disetujui, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center small">{{ $k->tenor_bulan }} mo</td>
                                <td class="px-3 py-2 text-center">
                                    @include('finance.kasbon.admin.partials.status-badge', ['status' => $k->status])
                                </td>
                                <td class="px-3 py-2 text-muted small" style="white-space:nowrap;">
                                    {{ $k->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <a href="{{ route('kasbon.admin.show', $k->id) }}"
                                       class="btn btn-outline-info btn-sm rounded-2 px-2 py-1" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                @if(!$kasbons->isEmpty() && $kasbons->hasPages())
                <div class="card-footer border-0 bg-light px-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $kasbons->firstItem() }}–{{ $kasbons->lastItem() }} of {{ $kasbons->total() }}
                        </div>
                        {{ $kasbons->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Cards (mobile) --}}
        <div class="d-md-none">
            @if($kasbons->isEmpty())
            <div class="card border-0 shadow-sm rounded-3 text-center py-5">
                <i class="fas fa-folder-open fa-2x text-muted mb-2 d-block"></i>
                <span class="text-muted small">No cash advance requests found.</span>
            </div>
            @else
            @foreach($kasbons as $k)
            <div class="card border-0 shadow-sm rounded-3 mb-2">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-medium">{{ $k->nama_lengkap }}</div>
                            <div class="text-muted small">{{ $k->nik_karyawan }} · {{ $k->department->name ?? '—' }}</div>
                            <div class="font-monospace" style="font-size:.7rem;">{{ $k->ref_number }}</div>
                        </div>
                        @include('finance.kasbon.admin.partials.status-badge', ['status' => $k->status])
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Rp {{ number_format($k->jumlah_diminta, 0, ',', '.') }} · {{ $k->tenor_bulan }} mo
                        </div>
                        <a href="{{ route('kasbon.admin.show', $k->id) }}" class="btn btn-outline-info btn-sm rounded-2 px-2">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
            @if($kasbons->hasPages())
            <div class="py-2">{{ $kasbons->appends(request()->query())->links() }}</div>
            @endif
            @endif
        </div>

    </div>
</div>

<style>
    .table td { vertical-align: middle; }
</style>
@endsection
