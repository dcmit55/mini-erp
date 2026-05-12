@extends('layouts.app')

@section('title', 'Warning Letter Dashboard')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('hr.management') }}" class="btn btn-sm btn-outline-secondary px-3">
                        <i class="fas fa-arrow-left me-1"></i><span class="d-none d-sm-inline">Back</span>
                    </a>
                    <div>
                        <h5 class="text-dark mb-1 mt-2">Warning Letter Dashboard</h5>
                        <p class="text-muted small mb-0">Monitor active SPs, approvals, and expiry</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('warning-letters.index') }}"
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-list me-1"></i>All Letters
                    </a>
                    @can('hr.warning-batch.create')
                    <a href="{{ route('warning-batches.create') }}"
                       class="btn btn-outline-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-users me-1"></i>Bulk SP
                    </a>
                    @endcan
                    @can('hr.warning-letter.create')
                    <a href="{{ route('warning-letters.create') }}"
                       class="btn btn-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-plus me-1"></i>New SP
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Stats --}}
            <div class="row g-2 mb-3">
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-file-alt text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total Active</h6>
                                    <h4 class="mb-0">{{ $stats['total_active'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-file-alt text-info"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Active SP1</h6>
                                    <h4 class="mb-0 text-info">{{ $stats['sp1'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-file-alt text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Active SP2</h6>
                                    <h4 class="mb-0 text-warning">{{ $stats['sp2'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Active SP3</h6>
                                    <h4 class="mb-0 text-danger">{{ $stats['sp3'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left:3px solid #ef4444 !important;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-user-times text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Inactive</h6>
                                    <h4 class="mb-0 text-danger">{{ \App\Models\Hr\Employee::where('status','inactive')->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Expiring (14d)</h6>
                                    <h4 class="mb-0 text-warning">{{ $stats['expiring_soon'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="row g-3">

                {{-- Recent Active SPs --}}
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                        <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="small text-dark fw-medium">
                                <i class="fas fa-file-alt me-2 text-primary"></i>Recent Active SPs
                            </span>
                            <a href="{{ route('warning-letters.index') }}"
                               class="btn btn-outline-secondary btn-sm rounded-2 px-2 py-1"
                               style="font-size:0.75rem;">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0 small text-dark fw-medium px-3 py-2">Employee</th>
                                            <th class="border-0 small text-dark fw-medium px-3 py-2">SP</th>
                                            <th class="border-0 small text-dark fw-medium px-3 py-2">Category</th>
                                            <th class="border-0 small text-dark fw-medium px-3 py-2">Valid Until</th>
                                            <th class="border-0 small text-dark fw-medium px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentLetters as $letter)
                                        <tr class="border-top" onclick="window.location='{{ route('warning-letters.show', $letter) }}'" style="cursor:pointer;">
                                            <td class="px-3 py-2">
                                                <div class="fw-medium" style="font-size:0.84rem;">{{ $letter->employee->name }}</div>
                                                <div class="text-muted" style="font-size:0.72rem;">{{ $letter->employee->department?->name }}</div>
                                            </td>
                                            <td class="px-3 py-2">
                                                @php $spC=[1=>'info',2=>'warning',3=>'warning',4=>'danger']; $sc=$spC[$letter->sp_level]??'secondary'; @endphp
                                                <span class="badge bg-{{ $sc }} bg-opacity-10 text-{{ $sc }} border border-{{ $sc }} border-opacity-25 rounded-2 px-2 {{ in_array($letter->sp_level,[2,3])?'text-dark':'' }}">
                                                    SP{{ $letter->sp_level }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 small text-muted">{{ Str::limit($letter->violationCategory->name, 22) }}</td>
                                            <td class="px-3 py-2 small">
                                                <span class="{{ $letter->valid_until && $letter->valid_until->diffInDays(now()) <= 14 ? 'text-danger fw-medium' : 'text-muted' }}">
                                                    {{ $letter->valid_until?->format('d/m/Y') ?? '—' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2">
                                                @php $sBg = \App\Models\Hr\WarningLetter::STATUS_COLORS[$letter->status] ?? 'secondary'; @endphp
                                                <span class="badge bg-{{ $sBg }} bg-opacity-10 text-{{ $sBg }} border border-{{ $sBg }} border-opacity-25 rounded-2 px-2" style="font-size:0.72rem;">
                                                    {{ $letter->statusLabel }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted small">No active SPs.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Expiring Soon --}}
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                        <div class="card-header bg-light border-bottom px-3 py-2">
                            <span class="small text-dark fw-medium">
                                <i class="fas fa-clock me-2 text-warning"></i>Expiring Soon (30 days)
                            </span>
                        </div>
                        <div class="card-body p-0">
                            @forelse($expiringSoon as $letter)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <div>
                                    <div class="fw-medium" style="font-size:0.84rem;">{{ $letter->employee->name }}</div>
                                    <div class="text-muted" style="font-size:0.72rem;">
                                        {{ $letter->employee->department?->name }} —
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-2 px-1" style="font-size:0.65rem;">SP{{ $letter->sp_level }}</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-danger small fw-medium">{{ $letter->valid_until->format('d/m/Y') }}</div>
                                    <div class="text-muted" style="font-size:0.72rem;">{{ $letter->valid_until->diffForHumans() }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-4 text-muted small">No SPs expiring soon.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            {{-- Quick Links --}}
            <div class="row g-2 mt-1">
                <div class="col-md-4">
                    <a href="{{ route('warning-letters.index', ['status' => 'draft']) }}"
                       class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 d-block">
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="fas fa-pencil-alt text-secondary"></i>
                            </div>
                            <div>
                                <div class="fw-medium text-dark" style="font-size:0.88rem;">Draft SP</div>
                                <div class="text-muted small">Not yet finalized</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('warning-batches.index') }}"
                       class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 d-block">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="fas fa-users text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-medium text-dark" style="font-size:0.88rem;">{{ $stats['total_batches'] }} Batches</div>
                                <div class="text-muted small">Bulk generation history</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('violation-categories.index') }}"
                       class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 d-block">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                <i class="fas fa-tags text-info"></i>
                            </div>
                            <div>
                                <div class="fw-medium text-dark" style="font-size:0.88rem;">Violation Categories</div>
                                <div class="text-muted small">Manage violation categories</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

<style>
.btn { font-size: 0.9rem; font-weight: 500; }
.btn-primary { background-color: #4f46e5; border-color: #4f46e5; }
.btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
.table-hover tbody tr:hover { background-color: rgba(79,70,229,.04); }
.badge { font-size: 0.75rem; font-weight: 500; }
.card { background: #fff; border: 1px solid #e2e8f0; }
.bg-light { background-color: #f8fafc !important; }
.text-muted { color: #6b7280 !important; }
.text-dark { color: #374151 !important; }
.text-primary { color: #4f46e5 !important; }
.rounded-2 { border-radius: .5rem !important; }
.rounded-3 { border-radius: .75rem !important; }
.table td, .table th { vertical-align: middle; }
.fw-medium { font-weight: 500 !important; }
</style>
