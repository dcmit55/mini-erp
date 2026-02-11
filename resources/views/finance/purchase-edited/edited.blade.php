{{-- resources/views/finance/purchase-approvals/edited.blade.php --}}
@extends('layouts.app')

@section('title', 'Edited Purchases')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">Edited Purchases</h5>
                    <p class="text-muted small mb-0">Purchases that have been edited after approval</p>
                </div>
                <div>
                    <a href="{{ route('purchase-approvals.index') }}" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Approvals
                    </a>
                </div>
            </div>

            <!-- Search -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('purchase-approvals.edited') }}">
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="Search PO number..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    @if(count($groupedPurchases) == 0)
                        <div class="text-center py-5">
                            <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No edited purchases found</h6>
                            <p class="text-muted small">No purchase revisions detected.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0">PO Number</th>
                                        <th class="border-0">Item</th>
                                        <th class="border-0 text-center">Revisions</th>
                                        <th class="border-0 text-end">Qty</th>
                                        <th class="border-0 text-end">Unit Price</th>
                                        <th class="border-0 text-end">Total</th>
                                        <th class="border-0">Last Edited</th>
                                        <th class="border-0 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupedPurchases as $poNumber => $data)
                                        @php
                                            $current = $data['current'];
                                            $previous = $data['previous'];
                                            $revisionCount = $data['revision_count'];
                                            $qtyDiff = $previous ? $current->quantity - $previous->quantity : 0;
                                            $priceDiff = $previous ? $current->unit_price - $previous->unit_price : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $poNumber }}</strong>
                                            </td>
                                            <td>{{ $current->new_item_name ?? ($current->material->name ?? 'N/A') }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $revisionCount }}</span>
                                            </td>
                                            <td class="text-end">
                                                {{ $current->quantity }}
                                                @if($previous && $qtyDiff != 0)
                                                    <br>
                                                    <small class="{{ $qtyDiff > 0 ? 'text-danger' : 'text-success' }}">
                                                        <i class="fas fa-arrow-{{ $qtyDiff > 0 ? 'up' : 'down' }}"></i>
                                                        {{ abs($qtyDiff) }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                Rp {{ number_format($current->unit_price, 0, ',', '.') }}
                                                @if($previous && $priceDiff != 0)
                                                    <br>
                                                    <small class="{{ $priceDiff > 0 ? 'text-danger' : 'text-success' }}">
                                                        <i class="fas fa-arrow-{{ $priceDiff > 0 ? 'up' : 'down' }}"></i>
                                                        Rp {{ number_format(abs($priceDiff), 0, ',', '.') }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong>Rp {{ number_format($current->total_price, 0, ',', '.') }}</strong>
                                            </td>
                                            <td>{{ $current->updated_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Compare Button -->
                                                    <a href="{{ route('purchase-approvals.compare', $poNumber) }}" 
                                                       class="btn btn-outline-info">
                                                        <i class="fas fa-code-compare"></i> Compare
                                                    </a>
                                                    
                                                    <!-- Finish/Checked Button -->
                                                    <form action="{{ route('purchase-approvals.verify-edit', $poNumber) }}" 
                                                          method="POST" 
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-success"
                                                                onclick="return confirm('Update DCM Costing for PO {{ $poNumber }}?')">
                                                            <i class="fas fa-check"></i> Finish
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        @if($purchases->hasPages())
                        <div class="card-footer">
                            {{ $purchases->links() }}
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection