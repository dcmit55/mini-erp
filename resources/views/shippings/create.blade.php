@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h4 class="mb-4">Shippings Process Modules</h4>
        <form action="{{ route('shippings.store') }}" method="POST">
            @csrf

            <!-- Blok 1: Form Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Int WBL Number</label>
                            <input type="text" name="international_waybill_no" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Freight Comp</label>
                            <select name="freight_company" class="form-select" required>
                                <option value="">-- Select --</option>
                                @foreach ($freightCompanies as $company)
                                    <option value="{{ $company }}">{{ $company }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Freight Price</label>
                            <input type="number" name="freight_price" class="form-control" min="0" step="0.01"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ETA To Arrived</label>
                            <input type="datetime-local" name="eta_to_arrived" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blok 2: Detail Data -->
            @foreach ($preShippings as $idx => $pre)
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <input type="hidden" name="pre_shipping_ids[]" value="{{ $pre->id }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Purchase Type</label>
                                <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $pre->purchaseRequest->type)) }}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Project Name</label>
                                <div class="fw-semibold">{{ $pre->purchaseRequest->project->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Material Name</label>
                                <div class="fw-semibold">{{ $pre->purchaseRequest->material_name }}</div>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label text-muted mb-0">Qty To Buy</label>
                                <div class="fw-semibold">{{ $pre->purchaseRequest->required_quantity }}</div>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label text-muted mb-0">Unit Type</label>
                                <div class="fw-semibold">{{ $pre->purchaseRequest->unit }}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Supplier</label>
                                <div class="fw-semibold">{{ $pre->purchaseRequest->supplier->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Unit Price</label>
                                <div class="fw-semibold">{{ number_format($pre->purchaseRequest->price_per_unit, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Domestic WBL</label>
                                <div class="fw-semibold">{{ $pre->domestic_waybill_no }}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Domestic Cost</label>
                                <div class="fw-semibold">{{ number_format($pre->domestic_cost, 2) }}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Percentage %</label>
                                <input type="number" name="percentage[]" class="form-control" placeholder="Percentage %"
                                    min="0" max="100" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-0">Int Cost</label>
                                <input type="number" name="int_cost[]" class="form-control" placeholder="Int Cost"
                                    min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <button class="btn btn-primary float-end mt-3" type="submit">Proceed To Shippings Management</button>
        </form>
    </div>
@endsection
