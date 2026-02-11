@extends('layouts.app')

@section('title', 'Compare Revisions - ' . $poNumber)

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">
                        Compare Revisions: <strong>{{ $poNumber }}</strong>
                    </h5>
                    <p class="text-muted small mb-0">Showing differences between revisions</p>
                </div>
                <div>
                    <a href="{{ route('purchase-edited.index') }}" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <!-- Current vs Previous -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Previous Revision</h6>
                            <small class="text-muted">{{ $previous->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Quantity:</strong> {{ $previous->quantity }}
                            </div>
                            <div class="mb-2">
                                <strong>Unit Price:</strong> Rp {{ number_format($previous->unit_price, 0, ',', '.') }}
                            </div>
                            <div class="mb-2">
                                <strong>Total Price:</strong> Rp {{ number_format($previous->total_price, 0, ',', '.') }}
                            </div>
                            <div class="mb-2">
                                <strong>Invoice Total:</strong> Rp {{ number_format($previous->invoice_total, 0, ',', '.') }}
                            </div>
                            <div class="mb-0">
                                <strong>Status:</strong>
                                <span class="badge bg-success">{{ ucfirst($previous->status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-2 border-success shadow-sm h-100">
                        <div class="card-header bg-success bg-opacity-10">
                            <h6 class="mb-0 text-success">Current Revision</h6>
                            <small class="text-muted">{{ $current->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Quantity:</strong> {{ $current->quantity }}
                            </div>
                            <div class="mb-2">
                                <strong>Unit Price:</strong> Rp {{ number_format($current->unit_price, 0, ',', '.') }}
                            </div>
                            <div class="mb-2">
                                <strong>Total Price:</strong> Rp {{ number_format($current->total_price, 0, ',', '.') }}
                            </div>
                            <div class="mb-2">
                                <strong>Invoice Total:</strong> Rp {{ number_format($current->invoice_total, 0, ',', '.') }}
                            </div>
                            <div class="mb-0">
                                <strong>Status:</strong>
                                <span class="badge bg-success">{{ ucfirst($current->status) }}</span>
                                <span class="badge bg-info ms-1">CURRENT</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update DCM Section -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">Update DCM Costing</h6>
                    <p class="text-muted mb-3">
                        Click the button below to update DCM Costing with the latest data from this purchase.
                    </p>
                    
                    <form action="{{ route('purchase-edited.verify', $poNumber) }}" method="POST">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Changes to be updated:</strong>
                                <ul class="mb-0">
                                    @if($previous->quantity != $current->quantity)
                                        <li>Quantity: {{ $previous->quantity }} → {{ $current->quantity }}</li>
                                    @endif
                                    @if($previous->unit_price != $current->unit_price)
                                        <li>Unit Price: Rp {{ number_format($previous->unit_price, 0, ',', '.') }} → 
                                            Rp {{ number_format($current->unit_price, 0, ',', '.') }}</li>
                                    @endif
                                    @if($previous->total_price != $current->total_price)
                                        <li>Total Price: Rp {{ number_format($previous->total_price, 0, ',', '.') }} → 
                                            Rp {{ number_format($current->total_price, 0, ',', '.') }}</li>
                                    @endif
                                </ul>
                            </div>
                            <div>
                                <button type="submit" 
                                        class="btn btn-success"
                                        onclick="return confirm('Update DCM Costing with latest data?')">
                                    <i class="fas fa-check me-1"></i> Finish & Update DCM
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection