@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Create Shipping</h2>
                <hr>
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif
                <form action="{{ route('shippings.store') }}" method="POST">
                    @csrf
                    <!-- Blok 1: Form Header -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">International Waybill Number</label>
                            <input type="text" name="international_waybill_no" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">International Freight Company</label>
                            <select name="freight_company" class="form-select" required>
                                <option value="">Select</option>
                                @foreach ($freightCompanies as $company)
                                    <option value="{{ $company }}">{{ $company }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">International Freight Cost</label>
                            <input type="number" name="freight_price" class="form-control" min="0" step="0.01"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ETA To Arrived</label>
                            <input type="datetime-local" name="eta_to_arrived" class="form-control" required>
                        </div>
                    </div>

                    <!-- Blok 2: Detail Data -->
                    @forelse ($validPreShippings as $idx => $pre)
                        @if ($pre->purchaseRequest)
                            <div class="card mb-2 border">
                                <div class="card-body">
                                    <input type="hidden" name="pre_shipping_ids[]" value="{{ $pre->id }}">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Purchase Type</label>
                                            <div class="fw-semibold">
                                                {{ ucfirst(str_replace('_', ' ', $pre->purchaseRequest->type)) }}
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Material Name</label>
                                            <div class="fw-semibold">
                                                {{ $pre->purchaseRequest->material_name }}
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label text-muted mb-0">Purchased Qty</label>
                                            <div class="fw-semibold">
                                                {{ $pre->purchaseRequest->qty_to_buy ?? $pre->purchaseRequest->required_quantity }}
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label text-muted mb-0">Unit</label>
                                            <div class="fw-semibold">
                                                {{ $pre->purchaseRequest->unit }}
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Supplier</label>
                                            <div class="fw-semibold">
                                                {{ $pre->purchaseRequest->supplier->name ?? '-' }}
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Unit Price</label>
                                            <div class="fw-semibold">
                                                {{ number_format($pre->purchaseRequest->price_per_unit, 2) }}
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Project Name</label>
                                            <div class="fw-semibold">
                                                {{ $pre->purchaseRequest->project->name ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Domestic Waybill</label>
                                            <div class="fw-semibold">{{ $pre->domestic_waybill_no ?? '-' }}</div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Domestic Cost</label>
                                            <div class="fw-semibold">
                                                {{ number_format($pre->allocated_cost ?? 0, 2) }}
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Percentage (%)</label>
                                            <input type="number" name="percentage[]" class="form-control"
                                                placeholder="Percentage (%)" min="0" max="100" step="0.01">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">International Cost</label>
                                            <input type="number" name="int_cost[]" class="form-control"
                                                placeholder="International Cost" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Item tanpa purchaseRequest --}}
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Skipping pre-shipping item (purchase request not found)
                            </div>
                        @endif
                    @empty
                        {{-- EMPTY STATE --}}
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            No valid pre-shipping data available. The selected items may have been deleted.
                            <br>
                            <a href="{{ route('pre-shippings.index') }}" class="btn btn-sm btn-primary mt-2">
                                Back to Pre-Shipping
                            </a>
                        </div>
                    @endforelse

                    @if (!$validPreShippings->isEmpty())
                        <button class="btn btn-primary float-end mt-3" type="submit">
                            Proceed To Shippings
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection
