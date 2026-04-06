@extends('layouts.app')

@section('title', 'Deleted Purchases Log')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">Deleted Purchases</h5>
                    <p class="text-muted small mb-0">History of all deleted purchase orders</p>
                </div>
                <a href="{{ route('purchase-approvals.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>

            <!-- Search -->
            <div class="mb-3">
                <form method="GET" action="{{ route('purchase-approvals.deleted-purchases') }}" class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm border-1 rounded-2" style="max-width:260px;"
                           name="search" value="{{ $search }}"
                           placeholder="Search purchase number...">
                    <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('purchase-approvals.deleted-purchases') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    @if($deletedPurchases->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-trash fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No deleted purchases found</h6>
                        </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 small fw-medium px-3 py-2 text-center" style="width:50px;">No</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Purchase Number</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Date</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Department</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Project</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Supplier</th>
                                    <th class="border-0 small fw-medium px-3 py-2 text-center">Items</th>
                                    <th class="border-0 small fw-medium px-3 py-2 text-end">Amount</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Requested By</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Approved By</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Deleted At</th>
                                    <th class="border-0 small fw-medium px-3 py-2 text-end">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $offset = ($deletedPurchases->currentPage() - 1) * $deletedPurchases->perPage(); @endphp
                                @foreach($deletedPurchases as $i => $req)
                                <tr class="border-top">
                                    <td class="px-3 py-2 text-center text-muted small">{{ $offset + $i + 1 }}</td>
                                    <td class="px-3 py-2" style="font-size:0.8rem;">{{ $req['po_number'] }}</td>
                                    <td class="px-3 py-2 small">{{ $req['date'] ? \Carbon\Carbon::parse($req['date'])->format('d/m/Y') : '-' }}</td>
                                    <td class="px-3 py-2 small text-muted">{{ $req['department']->name ?? '-' }}</td>
                                    <td class="px-3 py-2" style="font-size:0.8rem;">
                                        @if($req['project_type'] == 'client')
                                            {{ $req['project']->name ?? '-' }}
                                            @if($req['job_order'])
                                                <span class="text-muted"> · {{ $req['job_order']->name }}</span>
                                            @endif
                                        @else
                                            {{ $req['internal_project']->project ?? '-' }}
                                            @if($req['internal_project']->job ?? '')
                                                <span class="text-muted"> · {{ $req['internal_project']->job }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-2" style="font-size:0.8rem;">{{ $req['supplier']->name ?? '-' }}</td>
                                    <td class="px-3 py-2 small text-center text-muted">{{ count($req['items']) }}</td>
                                    <td class="px-3 py-2 text-end" style="font-size:0.8rem; white-space:nowrap;">Rp {{ number_format($req['total_amount'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 small text-muted">{{ $req['requested_by']->username ?? '-' }}</td>
                                    <td class="px-3 py-2 small text-muted">{{ $req['approved_by_user']->username ?? '-' }}</td>
                                    <td class="px-3 py-2 small text-muted" style="white-space:nowrap;">{{ \Carbon\Carbon::parse($req['deleted_at'])->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 text-end">
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm rounded-2 px-2 py-1"
                                                title="View Detail"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#log-detail-{{ $i }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                {{-- Expand: reason + items --}}
                                <tr class="collapse" id="log-detail-{{ $i }}">
                                    <td colspan="12" class="px-4 py-3 bg-light">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="small text-muted mb-1">Deletion Reason</div>
                                                <div class="alert alert-secondary py-2 mb-0 small">{{ $req['deletion_reason'] ?? '-' }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="small text-muted mb-1">Requested At</div>
                                                <div class="small">{{ $req['deletion_requested_at'] ? \Carbon\Carbon::parse($req['deletion_requested_at'])->format('d/m/Y H:i') : '-' }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="small text-muted mb-1">Approved At</div>
                                                <div class="small">{{ $req['deletion_approved_at'] ? \Carbon\Carbon::parse($req['deletion_approved_at'])->format('d/m/Y H:i') : '-' }}</div>
                                            </div>
                                        </div>
                                        <div class="small text-muted fw-medium mb-2">Items ({{ count($req['items']) }})</div>
                                        <table class="table table-sm table-bordered mb-0" style="font-size:0.82rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Item / Material</th>
                                                    <th class="text-end" style="width:120px;">Qty</th>
                                                    <th class="text-end" style="width:150px;">Unit Price</th>
                                                    <th class="text-end" style="width:150px;">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($req['items'] as $item)
                                                <tr>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td class="text-end">{{ rtrim(rtrim(number_format($item['qty'], 2), '0'), '.') }} {{ $item['unit'] }}</td>
                                                    <td class="text-end">Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                                                    <td class="text-end">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($deletedPurchases->hasPages())
                    <div class="card-footer border-0 bg-light px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing {{ $deletedPurchases->firstItem() }} to {{ $deletedPurchases->lastItem() }} of {{ $deletedPurchases->total() }} entries
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                @if($deletedPurchases->onFirstPage())
                                    <span class="btn btn-outline-secondary btn-sm rounded-2 px-3 disabled" style="font-size:0.78rem;">Previous</span>
                                @else
                                    <a href="{{ $deletedPurchases->previousPageUrl() }}" class="btn btn-outline-primary btn-sm rounded-2 px-3" style="font-size:0.78rem;">Previous</a>
                                @endif
                                <span class="text-muted small">Page {{ $deletedPurchases->currentPage() }} of {{ $deletedPurchases->lastPage() }}</span>
                                @if($deletedPurchases->hasMorePages())
                                    <a href="{{ $deletedPurchases->nextPageUrl() }}" class="btn btn-outline-primary btn-sm rounded-2 px-3" style="font-size:0.78rem;">Next</a>
                                @else
                                    <span class="btn btn-outline-secondary btn-sm rounded-2 px-3 disabled" style="font-size:0.78rem;">Next</span>
                                @endif
                            </div>
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
