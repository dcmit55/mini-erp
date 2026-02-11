@extends('layouts.app')

@section('title', 'Edited Purchases')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">Edited Purchases</h5>
                    <p class="text-muted small mb-0">Purchases that have been edited after approval</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('purchase-approvals.index') }}" 
                       class="btn btn-outline-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-clipboard-check me-1"></i> Purchase Approvals
                    </a>
                    <a href="{{ route('dcm-costings.index') }}" 
                       class="btn btn-outline-success btn-sm rounded-2 px-3">
                        <i class="fas fa-file-invoice-dollar me-1"></i> View DCM Costing
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-edit text-info"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total Edited</h6>
                                    <h4 class="mb-0">{{ count($groupedPurchases) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Synced</h6>
                                    <h4 class="mb-0">
                                        @php
                                            $syncedCount = collect($dcmStatuses)->where('status', 'synced')->count();
                                        @endphp
                                        {{ $syncedCount }}
                                    </h4>
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
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Outdated</h6>
                                    <h4 class="mb-0">
                                        @php
                                            $outdatedCount = collect($dcmStatuses)->where('status', 'outdated')->count();
                                        @endphp
                                        {{ $outdatedCount }}
                                    </h4>
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
                                    <i class="fas fa-times-circle text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Not Exists</h6>
                                    <h4 class="mb-0">
                                        @php
                                            $notExistsCount = collect($dcmStatuses)->where('status', 'not_exists')->count();
                                        @endphp
                                        {{ $notExistsCount }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('purchase-edited.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" 
                                   class="form-control border-1 rounded-2 py-2 px-3" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Search PO number...">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-1 w-100">
                                <button type="submit" class="btn btn-primary rounded-2 px-3 w-30">
                                    <i class="fas fa-search me-1"></i> 
                                </button>
                                <a href="{{ route('purchase-edited.index') }}" 
                                   class="btn btn-outline-secondary rounded-2 px-3">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                @if(session('dcm_id'))
                <br><small class="mt-1">DCM ID: {{ session('dcm_id') }}</small>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
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
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center" style="width: 50px;">No</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">PO Number</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Item</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center">Revisions</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Qty</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Unit Price</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Total</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">DCM Status</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Last Edited</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $startNumber = ($purchases->currentPage() - 1) * $purchases->perPage() + 1;
                                    @endphp
                                    @foreach($groupedPurchases as $poNumber => $data)
                                        @php
                                            $current = $data['current'];
                                            $previous = $data['previous'];
                                            $revisionCount = $data['revision_count'];
                                            $qtyDiff = $previous ? $current->quantity - $previous->quantity : 0;
                                            $priceDiff = $previous ? $current->unit_price - $previous->unit_price : 0;
                                            
                                            // Get DCM status from controller
                                            $dcmStatus = $dcmStatuses[$poNumber] ?? [
                                                'status' => 'unknown',
                                                'color' => 'secondary',
                                                'message' => 'Unknown',
                                                'dcm_id' => null
                                            ];
                                        @endphp
                                        <tr class="border-top">
                                            <!-- No Urut -->
                                            <td class="px-3 py-2 text-center text-muted">
                                                {{ $startNumber + $loop->index }}
                                            </td>
                                            
                                            <!-- PO Number -->
                                            <td class="px-3 py-2">
                                                <strong class="text-primary">{{ $poNumber }}</strong>
                                            </td>
                                            
                                            <!-- Item -->
                                            <td class="px-3 py-2">
                                                <div class="fw-medium">{{ $current->new_item_name ?? ($current->material->name ?? 'N/A') }}</div>
                                                @if($current->department)
                                                <small class="text-muted">
                                                    {{ $current->department->name }}
                                                </small>
                                                @endif
                                            </td>
                                            
                                            <!-- Revisions -->
                                            <td class="px-3 py-2 text-center">
                                                <span class="badge bg-info">{{ $revisionCount }}</span>
                                            </td>
                                            
                                            <!-- Quantity -->
                                            <td class="px-3 py-2 text-end">
                                                <div class="fw-medium">{{ $current->quantity }}</div>
                                                @if($previous && $qtyDiff != 0)
                                                    <small class="{{ $qtyDiff > 0 ? 'text-danger' : 'text-success' }}">
                                                        <i class="fas fa-arrow-{{ $qtyDiff > 0 ? 'up' : 'down' }}"></i>
                                                        {{ abs($qtyDiff) }}
                                                    </small>
                                                @endif
                                            </td>
                                            
                                            <!-- Unit Price -->
                                            <td class="px-3 py-2 text-end">
                                                <div class="fw-medium">Rp {{ number_format($current->unit_price, 0, ',', '.') }}</div>
                                                @if($previous && $priceDiff != 0)
                                                    <small class="{{ $priceDiff > 0 ? 'text-danger' : 'text-success' }}">
                                                        <i class="fas fa-arrow-{{ $priceDiff > 0 ? 'up' : 'down' }}"></i>
                                                        Rp {{ number_format(abs($priceDiff), 0, ',', '.') }}
                                                    </small>
                                                @endif
                                            </td>
                                            
                                            <!-- Total -->
                                            <td class="px-3 py-2 text-end">
                                                <strong class="text-primary">Rp {{ number_format($current->total_price, 0, ',', '.') }}</strong>
                                            </td>
                                            
                                            <!-- DCM Status -->
                                            <td class="px-3 py-2">
                                                <span class="badge bg-{{ $dcmStatus['color'] }} d-flex align-items-center justify-content-center" style="gap: 4px; width: fit-content;">
                                                    @if($dcmStatus['status'] == 'synced')
                                                        <i class="fas fa-check"></i>
                                                    @elseif($dcmStatus['status'] == 'outdated')
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    @elseif($dcmStatus['status'] == 'not_exists')
                                                        <i class="fas fa-times"></i>
                                                    @endif
                                                    <span>{{ $dcmStatus['message'] }}</span>
                                                </span>
                                            </td>
                                            
                                            <!-- Last Edited -->
                                            <td class="px-3 py-2">
                                                {{ $current->updated_at->format('d/m/Y H:i') }}
                                            </td>
                                            
                                            <!-- Actions -->
                                            <td class="px-3 py-2 text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Compare Button -->
                                                    <a href="{{ route('purchase-edited.compare', $poNumber) }}" 
                                                       class="btn btn-outline-info"
                                                       title="Compare revisions">
                                                        <i class="fas fa-code-compare"></i>
                                                    </a>
                                                                                                        
                                                    <!-- Finish/Update DCM Button -->
                                                    <form action="{{ route('purchase-edited.verify', $poNumber) }}" 
                                                          method="POST" 
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-success"
                                                                title="Update DCM Costing with latest data"
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
                        <div class="card-footer border-0 bg-light px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ $purchases->firstItem() }} to {{ $purchases->lastItem() }} of {{ $purchases->total() }} entries
                                </div>
                                <div>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination pagination-sm mb-0">
                                            <!-- Previous Page Link -->
                                            @if($purchases->onFirstPage())
                                                <li class="page-item disabled">
                                                    <span class="page-link py-1 px-3 rounded-2 me-1" aria-label="Previous">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link py-1 px-3 rounded-2 me-1" 
                                                       href="{{ $purchases->previousPageUrl() }}"
                                                       aria-label="Previous">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            @endif

                                            <!-- Page Numbers -->
                                            @php
                                                $current = $purchases->currentPage();
                                                $last = $purchases->lastPage();
                                                $start = max($current - 2, 1);
                                                $end = min($current + 2, $last);
                                                
                                                if ($start > 1) {
                                                    echo '<li class="page-item"><span class="page-link py-1 px-3 rounded-2 me-1">...</span></li>';
                                                }
                                            @endphp
                                            
                                            @for ($i = $start; $i <= $end; $i++)
                                                @if ($i == $current)
                                                    <li class="page-item active">
                                                        <span class="page-link py-1 px-3 rounded-2 me-1">{{ $i }}</span>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link py-1 px-3 rounded-2 me-1" 
                                                           href="{{ $purchases->url($i) }}">{{ $i }}</a>
                                                    </li>
                                                @endif
                                            @endfor
                                            
                                            @if ($end < $last)
                                                <li class="page-item"><span class="page-link py-1 px-3 rounded-2 me-1">...</span></li>
                                            @endif

                                            <!-- Next Page Link -->
                                            @if($purchases->hasMorePages())
                                                <li class="page-item">
                                                    <a class="page-link py-1 px-3 rounded-2" 
                                                       href="{{ $purchases->nextPageUrl() }}"
                                                       aria-label="Next">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            @else
                                                <li class="page-item disabled">
                                                    <span class="page-link py-1 px-3 rounded-2" aria-label="Next">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </span>
                                                </li>
                                            @endif
                                        </ul>
                                    </nav>
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

<style>
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
        height: 42px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    .btn {
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
    }

    .btn-success {
        background-color: #10b981;
        border-color: #10b981;
    }

    .btn-success:hover {
        background-color: #059669;
        border-color: #059669;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(79, 70, 229, 0.04);
    }

    .badge {
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.25rem 0.5rem;
    }

    .card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }

    .text-muted {
        color: #6b7280 !important;
    }

    .text-dark {
        color: #374151 !important;
    }

    .rounded-2 {
        border-radius: 0.5rem !important;
    }

    .rounded-3 {
        border-radius: 0.75rem !important;
    }

    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }

    .border-opacity-25 {
        --bs-border-opacity: 0.25;
    }

    .table td, .table th {
        vertical-align: middle;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.85rem;
    }

    .page-link {
        color: #4f46e5;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        min-width: 36px;
        text-align: center;
    }

    .page-link:hover {
        color: #4338ca;
        background-color: #f8f9fa;
        border-color: #e2e8f0;
    }

    .page-item.active .page-link {
        background-color: #4f46e5;
        border-color: #4f46e5;
        color: white;
    }

    .page-item.disabled .page-link {
        color: #9ca3af;
        background-color: #f9fafb;
        border-color: #e2e8f0;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions dengan loading state
    document.querySelectorAll('form[action*="verify"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Show loading
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            submitButton.disabled = true;
            
            // Re-enable setelah 10 detik (jika ada masalah)
            setTimeout(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 10000);
        });
    });
});
</script>
@endsection