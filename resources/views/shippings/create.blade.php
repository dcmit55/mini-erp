@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h4>Shippings Process Modules</h4>
        <form action="{{ route('shippings.store') }}" method="POST">
            @csrf
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6 mb-2">
                            <label>Int WBL Number</label>
                            <input type="text" name="international_waybill_no" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Freight Comp</label>
                            <select name="freight_company" class="form-select" required>
                                <option value="">-- Select --</option>
                                @foreach ($freightCompanies as $company)
                                    <option value="{{ $company }}">{{ $company }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Freight Price</label>
                            <input type="number" name="freight_price" class="form-control" min="0" step="0.01"
                                required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>ETA To Arrived</label>
                            <input type="datetime-local" name="eta_to_arrived" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    @foreach ($preShippings as $idx => $pre)
                        <div class="border p-3 mb-3 rounded-3" style="background:#fafbfc;">
                            <input type="hidden" name="pre_shipping_ids[]" value="{{ $pre->id }}">
                            <div class="row align-items-center mb-2">
                                <div class="col-md-2 fw-semibold">
                                    {{ ucfirst(str_replace('_', ' ', $pre->externalRequest->type)) }}</div>
                                <div class="col-md-2">{{ $pre->externalRequest->project->name ?? '-' }}</div>
                                <div class="col-md-2">{{ $pre->externalRequest->material_name }}</div>
                                <div class="col-md-1">{{ $pre->externalRequest->required_quantity }}</div>
                                <div class="col-md-1">{{ $pre->externalRequest->unit }}</div>
                                <div class="col-md-2">{{ $pre->externalRequest->supplier->name ?? '-' }}</div>
                                <div class="col-md-2">{{ $pre->externalRequest->price_per_unit }}</div>
                            </div>
                            <div class="row align-items-center mb-2">
                                <div class="col-md-2 small text-muted">Domestic WBL:<br><span
                                        class="fw-semibold">{{ $pre->domestic_waybill_no }}</span></div>
                                <div class="col-md-2 small text-muted">Domestic Cost:<br><span
                                        class="fw-semibold">{{ $pre->domestic_cost }}</span></div>
                                <div class="col-md-2">
                                    <input type="number" name="percentage[]" class="form-control"
                                        placeholder="Percentage %" min="0" max="100" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="int_cost[]" class="form-control" placeholder="Int Cost"
                                        min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <button class="btn btn-primary float-end" type="submit">Proceed To Shippings Management</button>
        </form>
    </div>
@endsection
