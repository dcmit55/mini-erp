@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-header">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Create Shipping</h2>
            </div>
            <div class="card-body">
                {{-- Success Alert --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Warning Alert --}}
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Error Alert --}}
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Alert validation errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('shippings.store') }}" method="POST" id="shipping-form">
                    @csrf

                    {{-- Blok 1: Form Header --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">
                                International Waybill <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="international_waybill_no"
                                class="form-control @error('international_waybill_no') is-invalid @enderror"
                                value="{{ old('international_waybill_no') }}" placeholder="Enter unique international waybill number" required>
                            @error('international_waybill_no')
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                International Freight Company <span class="text-danger">*</span>
                            </label>
                            <select name="freight_company"
                                class="form-select @error('freight_company') is-invalid @enderror" required>
                                <option value="">Select Freight Company</option>
                                @foreach ($freightCompanies as $company)
                                    <option value="{{ $company }}"
                                        {{ old('freight_company') == $company ? 'selected' : '' }}>
                                        {{ $company }}
                                    </option>
                                @endforeach
                            </select>
                            @error('freight_company')
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                International Freight Cost <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="freight_price" id="freight_price" class="form-control"
                                min="0" step="0.01" value="{{ old('freight_price') }}" required>
                            <small class="text-muted">Total international freight cost for this shipment</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                ETA To Arrived <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" name="eta_to_arrived" class="form-control"
                                value="{{ old('eta_to_arrived') }}" required>
                        </div>
                    </div>

                    {{-- Cost Allocation Method Selector --}}
                    <div class="mt-4 mb-4">
                        <div class="mb-3">
                            <h6>
                                <i class="bi bi-calculator me-1"></i>
                                International Cost Allocation Method
                            </h6>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Allocation Method <span class="text-danger">*</span>
                                </label>
                                <select name="int_allocation_method" id="int_allocation_method" class="form-select"
                                    required>
                                    <option value="quantity">By Quantity</option>
                                    <option value="percentage">By Percentage</option>
                                    <option value="value" selected>By Value</option>
                                </select>
                                <small class="text-muted">
                                    Method to allocate international freight cost to each item
                                </small>

                                {{-- Auto-distribute controls --}}
                                <div class="row g-2 mt-2 align-items-center">
                                    <div class="col-md-auto percentage-controls" style="display: none;">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            id="auto-distribute-btn" title="Distribute percentage based on item value">
                                            <i class="fas fa-magic me-1"></i>
                                            Auto Distribute
                                        </button>
                                    </div>

                                    <div class="col-md percentage-validation" style="display: none;">
                                        <div class="alert alert-success mb-0 py-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small>
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    <strong>Total: <span id="total-percentage">0.00</span>%</strong>
                                                </small>
                                                <small class="text-muted">Target: 100%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Total Items: <strong id="total-items">0</strong>
                                </label>
                                <div class="alert alert-info mb-0 py-2">
                                    <small>
                                        <i class="bi bi-info-circle me-1"></i>
                                        Change allocation method to recalculate international cost for each item
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Blok 2: Detail Items --}}
                    @forelse ($validPreShippings as $idx => $pre)
                        @if ($pre->purchaseRequest)
                            <div class="card mb-3 border border-secondary item-card" data-index="{{ $idx }}"
                                data-quantity="{{ $pre->purchaseRequest->qty_to_buy ?? $pre->purchaseRequest->required_quantity }}"
                                data-value="{{ ($pre->purchaseRequest->qty_to_buy ?? $pre->purchaseRequest->required_quantity) * $pre->purchaseRequest->price_per_unit }}">

                                <div class="card-body">
                                    <input type="hidden" name="pre_shipping_ids[]" value="{{ $pre->id }}">

                                    {{-- Row 1: Material Info --}}
                                    <div class="row g-3 align-items-end mb-2">
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
                                            <div class="fw-semibold">{{ $pre->purchaseRequest->unit }}</div>
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

                                    {{-- Row 2: Shipping Details --}}
                                    <div class="row g-3 align-items-top">
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Domestic Waybill</label>
                                            <div class="fw-semibold">{{ $pre->domestic_waybill_no ?? '-' }}</div>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">
                                                Allocated Domestic Cost
                                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                                    data-bs-html="true"
                                                    title="Domestic Allocation Method: <strong>{{ ucfirst($pre->cost_allocation_method ?? 'Value') }}</strong>"
                                                    style="font-size: 0.75rem; cursor: help;"></i>
                                            </label>
                                            <div class="fw-semibold text-primary">
                                                {{ number_format($pre->allocated_cost ?? 0, 2) }}
                                            </div>
                                        </div>

                                        {{-- Percentage input (hanya tampil jika method = percentage) --}}
                                        <div class="col-md-2 percentage-column" style="display: none;">
                                            <label class="form-label text-muted mb-0">
                                                Allocation % <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" name="percentage[]"
                                                class="form-control percentage-input" placeholder="Enter 0-100%"
                                                min="0" max="100" step="0.01"
                                                data-index="{{ $idx }}">
                                        </div>

                                        {{-- International Cost (auto-calculated, readonly) --}}
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">
                                                Allocated Int. Cost
                                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                                    title="Auto-calculated based on allocation method"
                                                    style="font-size: 0.75rem; cursor: help;"></i>
                                            </label>
                                            <input type="number" name="int_cost[]" class="form-control int-cost-input"
                                                placeholder="Calculated" min="0" step="0.01" readonly>
                                        </div>

                                        {{-- Destination --}}
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">
                                                Destination <span class="text-danger">*</span>
                                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                                    title="Final destination for this item"
                                                    style="font-size: 0.75rem; cursor: help;"></i>
                                            </label>
                                            <select name="destination[]" class="form-select" required>
                                                <option value="">Select</option>
                                                <option value="SG" selected>Singapore</option>
                                                <option value="BT">Batam</option>
                                                <option value="CN">China</option>
                                                <option value="MY">Malaysia</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>

                                        {{-- Status Badge --}}
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Status</label>
                                            <div>
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                                    In Transit
                                                </span>
                                            </div>
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

                    {{-- Submit Button --}}
                    @if (!$validPreShippings->isEmpty())
                        <div class="mt-4 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit" id="submit-btn">
                                <i class="bi bi-send-fill me-2"></i>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                Proceed To Shippings
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Highlight calculated fields */
        .int-cost-input[readonly] {
            background-color: #e3f2fd;
            font-weight: 500;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        /* Item card hover effect */
        .item-card {
            transition: all 0.3s ease;
        }

        .item-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Percentage validation colors */
        .percentage-validation.valid .alert {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }

        .percentage-validation.invalid .alert {
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }

        /* Tooltip enhancement */
        .tooltip-inner {
            max-width: 280px;
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            text-align: left;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Update total items count
            const totalItems = $('.item-card').length;
            $('#total-items').text(totalItems);

            // COST ALLOCATION CALCULATION LOGIC
            function calculateIntCosts() {
                const method = $('#int_allocation_method').val();
                const freightPrice = parseFloat($('#freight_price').val()) || 0;

                if (freightPrice <= 0) {
                    $('.int-cost-input').val('');
                    return;
                }

                if (method === 'quantity') {
                    calculateByQuantity(freightPrice);
                } else if (method === 'percentage') {
                    calculateByPercentage(freightPrice);
                } else if (method === 'value') {
                    calculateByValue(freightPrice);
                }
            }

            function calculateByQuantity(totalCost) {
                let totalQuantity = 0;
                $('.item-card').each(function() {
                    const qty = parseFloat($(this).data('quantity')) || 0;
                    totalQuantity += qty;
                });

                if (totalQuantity <= 0) {
                    $('.int-cost-input').val('0');
                    return;
                }

                $('.item-card').each(function() {
                    const qty = parseFloat($(this).data('quantity')) || 0;
                    const allocatedCost = (qty / totalQuantity) * totalCost;
                    $(this).find('.int-cost-input').val(allocatedCost.toFixed(2));
                });
            }

            function calculateByPercentage(totalCost) {
                $('.item-card').each(function() {
                    const percentage = parseFloat($(this).find('.percentage-input').val()) || 0;
                    const allocatedCost = (percentage / 100) * totalCost;
                    $(this).find('.int-cost-input').val(allocatedCost.toFixed(2));
                });

                updatePercentageTotal();
            }

            function calculateByValue(totalCost) {
                let totalValue = 0;
                $('.item-card').each(function() {
                    const value = parseFloat($(this).data('value')) || 0;
                    totalValue += value;
                });

                if (totalValue <= 0) {
                    $('.int-cost-input').val('0');
                    return;
                }

                $('.item-card').each(function() {
                    const value = parseFloat($(this).data('value')) || 0;
                    const allocatedCost = (value / totalValue) * totalCost;
                    $(this).find('.int-cost-input').val(allocatedCost.toFixed(2));
                });
            }

            function updatePercentageTotal() {
                let total = 0;
                $('.percentage-input').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });

                $('#total-percentage').text(total.toFixed(2));

                const $validation = $('.percentage-validation');
                if (Math.abs(total - 100) < 0.1) {
                    $validation.removeClass('invalid').addClass('valid');
                    $validation.find('.alert').removeClass('alert-danger').addClass('alert-success');
                } else {
                    $validation.removeClass('valid').addClass('invalid');
                    $validation.find('.alert').removeClass('alert-success').addClass('alert-danger');
                }
            }

            $('#auto-distribute-btn').on('click', function() {
                let totalValue = 0;
                $('.item-card').each(function() {
                    const value = parseFloat($(this).data('value')) || 0;
                    totalValue += value;
                });

                if (totalValue <= 0) {
                    const equalPercentage = (100 / totalItems).toFixed(2);
                    $('.percentage-input').val(equalPercentage);
                } else {
                    $('.item-card').each(function() {
                        const value = parseFloat($(this).data('value')) || 0;
                        const percentage = (value / totalValue) * 100;
                        $(this).find('.percentage-input').val(percentage.toFixed(2));
                    });
                }

                calculateIntCosts();
            });

            function togglePercentageColumn() {
                const method = $('#int_allocation_method').val();

                if (method === 'percentage') {
                    $('.percentage-column').show();
                    $('.percentage-controls').show();
                    $('.percentage-validation').show();
                    $('.percentage-input').prop('required', true);
                } else {
                    $('.percentage-column').hide();
                    $('.percentage-controls').hide();
                    $('.percentage-validation').hide();
                    $('.percentage-input').prop('required', false).val('');
                }
            }

            // Event handlers
            $('#int_allocation_method').on('change', function() {
                togglePercentageColumn();
                calculateIntCosts();
            });

            $('#freight_price').on('input', function() {
                calculateIntCosts();
            });

            $('.percentage-input').on('input', function() {
                if ($('#int_allocation_method').val() === 'percentage') {
                    calculateIntCosts();
                }
            });

            // Form submit validation
            $('#shipping-form').on('submit', function(e) {
                const method = $('#int_allocation_method').val();

                if (method === 'percentage') {
                    let total = 0;
                    $('.percentage-input').each(function() {
                        total += parseFloat($(this).val()) || 0;
                    });

                    if (Math.abs(total - 100) > 0.5) {
                        e.preventDefault();
                        alert(`Total percentage is ${total.toFixed(2)}%. It should be close to 100%.`);
                        return false;
                    }
                }

                // Show loading spinner
                const $submitBtn = $('#submit-btn');
                $submitBtn.prop('disabled', true);
                $submitBtn.find('.spinner-border').removeClass('d-none');
            });

            // Initial calculation
            togglePercentageColumn();
            calculateIntCosts();
        });
    </script>
@endpush
