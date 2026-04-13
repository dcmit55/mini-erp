@extends('layouts.app')

@section('title', 'Warning Letter Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 fw-bold">Warning Letter Dashboard</h4>
                    <small class="text-muted">Monitor active SPs, approvals, and expiry</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('warning-batches.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people-fill me-1"></i> Bulk SP
                    </a>
                    <a href="{{ route('warning-letters.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> New SP
                    </a>
                </div>
            </div>

            {{-- SP Level Overview --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 text-center p-3">
                        <div class="text-muted small mb-1">Total Active</div>
                        <div class="fs-2 fw-bold text-primary">{{ $stats['total_active'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 text-center p-3 border-top border-3 border-info">
                        <div class="text-muted small mb-1">Active SP1</div>
                        <div class="fs-2 fw-bold text-info">{{ $stats['sp1'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 text-center p-3 border-top border-3 border-warning">
                        <div class="text-muted small mb-1">Active SP2</div>
                        <div class="fs-2 fw-bold text-warning">{{ $stats['sp2'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 text-center p-3 border-top border-3" style="border-color:#fd7e14!important">
                        <div class="text-muted small mb-1">Active SP3</div>
                        <div class="fs-2 fw-bold" style="color:#fd7e14">{{ $stats['sp3'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 text-center p-3 border-top border-3 border-danger">
                        <div class="text-muted small mb-1">Active SP4</div>
                        <div class="fs-2 fw-bold text-danger">{{ $stats['sp4'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm rounded-3 text-center p-3 border-top border-3 border-warning">
                        <div class="text-muted small mb-1">Expiring (14d)</div>
                        <div class="fs-2 fw-bold text-warning">{{ $stats['expiring_soon'] }}</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">

                {{-- Left: Recent Active SP --}}
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Recent Active SPs</span>
                            <a href="{{ route('warning-letters.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="px-3">Employee</th>
                                            <th>SP</th>
                                            <th>Category</th>
                                            <th>Valid Until</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentLetters as $letter)
                                        <tr onclick="window.location='{{ route('warning-letters.show', $letter) }}'" style="cursor:pointer">
                                            <td class="px-3">
                                                <div class="fw-semibold small">{{ $letter->employee->name }}</div>
                                                <div class="text-muted" style="font-size:11px">{{ $letter->employee->department?->name }}</div>
                                            </td>
                                            <td>
                                                @php $c=[1=>'info',2=>'warning',3=>'warning',4=>'danger']; @endphp
                                                <span class="badge bg-{{ $c[$letter->sp_level]??'secondary' }} {{ in_array($letter->sp_level,[2,3])?'text-dark':'' }}">SP{{ $letter->sp_level }}</span>
                                            </td>
                                            <td><small>{{ Str::limit($letter->violationCategory->name, 20) }}</small></td>
                                            <td>
                                                <small class="{{ $letter->valid_until && $letter->valid_until->diffInDays(now()) <= 14 ? 'text-danger fw-semibold' : '' }}">
                                                    {{ $letter->valid_until?->format('d/m/Y') ?? '—' }}
                                                </small>
                                            </td>
                                            <td><span class="badge bg-{{ $letter->statusColor }}">{{ $letter->statusLabel }}</span></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="5" class="text-center py-3 text-muted">No active SPs.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Expiring Soon --}}
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-transparent fw-semibold text-warning">
                            <i class="bi bi-clock-history me-1"></i> Expiring Soon (30 days)
                        </div>
                        <div class="card-body p-0">
                            @forelse($expiringSoon as $letter)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold small">{{ $letter->employee->name }}</div>
                                    <div class="text-muted" style="font-size:11px">{{ $letter->employee->department?->name }} — SP{{ $letter->sp_level }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-danger small fw-semibold">{{ $letter->valid_until->format('d/m/Y') }}</div>
                                    <div class="text-muted" style="font-size:11px">{{ $letter->valid_until->diffForHumans() }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-4 text-muted small">No SPs expiring soon.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            {{-- Bottom: Quick links --}}
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <a href="{{ route('warning-letters.index', ['status' => 'draft']) }}" class="card border-0 shadow-sm rounded-3 text-decoration-none text-dark p-3 d-block">
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary rounded-3 p-2 me-3"><i class="bi bi-file-earmark-text fs-5 text-white"></i></div>
                            <div>
                                <div class="fw-semibold">Draft SP</div>
                                <div class="text-muted small">Not yet finalized</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('warning-batches.index') }}" class="card border-0 shadow-sm rounded-3 text-decoration-none text-dark p-3 d-block">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-3 p-2 me-3"><i class="bi bi-people-fill fs-5 text-white"></i></div>
                            <div>
                                <div class="fw-semibold">{{ $stats['total_batches'] }} Batches</div>
                                <div class="text-muted small">Bulk generation history</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('violation-categories.index') }}" class="card border-0 shadow-sm rounded-3 text-decoration-none text-dark p-3 d-block">
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary rounded-3 p-2 me-3"><i class="bi bi-tags-fill fs-5 text-white"></i></div>
                            <div>
                                <div class="fw-semibold">Violation Categories</div>
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
