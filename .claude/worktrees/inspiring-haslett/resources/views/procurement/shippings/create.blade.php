@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-header">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Create Shipping</h2>
            </div>
            <div class="card-body">
                {{-- SUCCESS/INFO MESSAGE --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        {!! session('success') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        {!! session('info') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- VALIDATION ERRORS (User-facing) --}}
                @if (session('validation_errors'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" style="font-size: 1.2rem;"></i>
                            <div class="flex-grow-1">
                                <strong>Validation Errors:</strong>
                                <p class="mb-2 mt-1">The following items were <b>skipped</b> due to validation failures:</p>
                                <ul class="mb-0">
                                    @foreach (session('validation_errors') as $error)
                                        <li>{!! $error !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- VALIDATION WARNINGS --}}
                @if (session('validation_warnings'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-circle me-2 flex-shrink-0" style="font-size: 1.2rem;"></i>
                            <div class="flex-grow-1">
                                <strong>Warnings:</strong>
                                <p class="mb-2 mt-1">Please review the following items before proceeding:</p>
                                <ul class="mb-0">
                                    @foreach (session('validation_warnings') as $warning)
                                        <li>{!! $warning !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- ❌ GENERAL ERRORS --}}
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-x-circle me-2"></i>
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Laravel validation errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('shippings.store') }}" method="POST" id="shipping-form">
                    @csrf
                    {{-- Blok 1: Form Header --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">
                                International Waybill <span class="text-danger">*</span>
                                <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="Must be unique. Cannot use same waybill number twice."
                                    style="font-size: 0.75rem; cursor: help;"></i>
                            </label>
                            <input type="text" name="international_waybill_no"
                                class="form-control @error('international_waybill_no') is-invalid @enderror"
                                value="{{ old('international_waybill_no') }}" placeholder="e.g., AWB-2024-001" required>
                            @error('international_waybill_no')
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-3">
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

                        {{-- FREIGHT METHOD --}}
                        <div class="col-md-3">
                            <label class="form-label">
                                International Freight Method <span class="text-danger">*</span>
                                <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="Air Freight may incur extra cost for oversized items"
                                    style="font-size: 0.75rem; cursor: help;"></i>
                            </label>
                            <select name="freight_method" id="freight_method"
                                class="form-select @error('freight_method') is-invalid @enderror" required>
                                <option value="Sea Freight"
                                    {{ old('freight_method', 'Sea Freight') == 'Sea Freight' ? 'selected' : '' }}>
                                    Sea Freight
                                </option>
                                <option value="Air Freight" {{ old('freight_method') == 'Air Freight' ? 'selected' : '' }}>
                                    Air Freight
                                </option>
                            </select>
                            @error('freight_method')
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
                            <small class="text-muted">Total base international freight cost</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                ETA To Arrived <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" name="eta_to_arrived" class="form-control"
                                value="{{ old('eta_to_arrived') }}" required>
                        </div>
                    </div>

                    {{-- SECTION 2: COST ALLOCATION METHOD SELECTOR --}}
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
                                    <option value="quantity"
                                        {{ old('int_allocation_method') == 'quantity' ? 'selected' : '' }}>
                                        By Quantity
                                    </option>
                                    <option value="percentage"
                                        {{ old('int_allocation_method') == 'percentage' ? 'selected' : '' }}>
                                        By Percentage
                                    </option>
                                    <option value="value"
                                        {{ old('int_allocation_method', 'value') == 'value' ? 'selected' : '' }}>
                                        By Value
                                    </option>
                                </select>
                                <small class="text-muted">
                                    Method to allocate international freight cost to each item
                                </small>

                                {{-- Auto-distribute controls for percentage method --}}
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
                                <div class="alert alert-success mb-0 py-2">
                                    <small>
                                        <i class="bi bi-info-circle me-1"></i>
                                        Change allocation method to recalculate international cost for each item
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 3: DETAIL ITEMS - WITH ALL ORIGINAL DATA --}}
                    @forelse ($allItems as $idx => $item)
                        @php
                            $isShortage = $item->is_shortage ?? false;
                            $pr = $item->purchaseRequest;

                            if (!$pr) {
                                continue; // Skip if no PR
                            }

                            $purchasedQty = $isShortage
                                ? $item->shortage_qty
                                : $pr->qty_to_buy ?? $pr->required_quantity;

                            $unit = $pr->unit ?? '-';
                            $unitPrice = $pr->price_per_unit ?? 0;
                            $itemValue = $purchasedQty * ($pr->price_per_unit ?? 0);
                            $currencyName = $pr->currency ? $pr->currency->name : '-';
                        @endphp

                        {{-- Item Card --}}
                        <div class="card item-card mb-3 border {{ $isShortage ? 'border-warning' : '' }}"
                            data-index="{{ $idx }}" data-quantity="{{ $purchasedQty }}"
                            data-price="{{ $unitPrice }}" data-value="{{ $itemValue }}">

                            <div class="card-body">
                                {{-- Shortage Badge --}}
                                @if ($isShortage)
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-arrow-repeat me-1"></i>
                                            SHORTAGE RESEND
                                        </span>
                                        <small class="text-muted">
                                            Resend #{{ $item->resend_count + 1 }} |
                                            Original PR#{{ $pr->id }}
                                        </small>
                                    </div>
                                @endif

                                {{-- Hidden Inputs untuk Item Identification --}}
                                <input type="hidden" name="items[{{ $idx }}][item_id]"
                                    value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $idx }}][item_type]"
                                    value="{{ $item->item_type }}">

                                {{-- ROW 1: Material Info --}}
                                <div class="row g-3 align-items-end mb-2">
                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">Purchase Type</label>
                                        <div class="fw-semibold">
                                            {{ ucfirst(str_replace('_', ' ', $pr->type)) }}
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Material Name</label>
                                        <div class="fw-bold">
                                            {{ $pr->material_name }}
                                            @if ($isShortage)
                                                <small class="text-warning">(Resend)</small>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label text-muted mb-0">Purchased Qty</label>
                                        <div class="fw-semibold">{{ $purchasedQty }}</div>
                                    </div>

                                    <div class="col-md-1">
                                        <label class="form-label text-muted mb-0">Unit</label>
                                        <div class="fw-semibold">{{ $unit }}</div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">Supplier</label>
                                        <div class="fw-semibold">
                                            {{ $pr->supplier->name ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">Unit Price</label>
                                        <div class="fw-semibold">{{ number_format($unitPrice, 2) }}</div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">Project Name</label>
                                        <div class="fw-semibold">
                                            {{ $pr->project->name ?? '-' }}
                                        </div>
                                    </div>
                                </div>

                                {{-- ROW 2: Shipping Details --}}
                                <div class="row g-3 align-items-top">
                                    @if (!$isShortage)
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Domestic Waybill</label>
                                            <div><small>{{ $item->domestic_waybill_no ?? '-' }}</small></div>
                                        </div>
                                    @else
                                        <div class="col-md-2">
                                            <label class="form-label text-muted mb-0">Old Domestic Waybill</label>
                                            <div><small>{{ $item->domestic_waybill_no ?? '-' }}</small></div>
                                        </div>
                                    @endif

                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">
                                            Allocated Domestic Cost
                                            <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                                data-bs-html="true"
                                                title="<strong>Domestic Allocation Method:</strong><br>{{ ucfirst($item->cost_allocation_method ?? 'Value') }}"
                                                style="font-size: 0.75rem; cursor: help;"></i>
                                        </label>
                                        <div class="fw-semibold text-primary">
                                            @if ($isShortage)
                                                <span class="text-muted">-</span>
                                            @else
                                                {{ number_format($item->allocated_cost ?? 0, 2) }}
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Percentage Column (show only if method = percentage) --}}
                                    <div class="col-md-2 percentage-column" style="display: none;">
                                        <label class="form-label text-muted mb-0">
                                            Allocation % <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="percentage[]" class="form-control percentage-input"
                                            placeholder="0-100%" min="0" max="100" step="0.01"
                                            data-index="{{ $idx }}" value="{{ old('percentage.' . $idx, 0) }}">
                                    </div>

                                    {{-- Base International Cost (readonly, auto-calculated) --}}
                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">
                                            Base Int. Cost
                                            <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip"
                                                title="Auto-calculated from freight price"
                                                style="font-size: 0.75rem; cursor: help;"></i>
                                        </label>
                                        <input type="number" name="int_cost[]" class="form-control base-cost-display"
                                            placeholder="Auto-calculated" min="0" step="0.01" readonly
                                            value="{{ old('int_cost.' . $idx, 0) }}"
                                            style="background-color: #e3f2fd; font-weight: 500; color: #1976d2;">
                                    </div>

                                    {{-- Extra Cost (only for Air Freight) --}}
                                    <div class="col-md-2 extra-cost-column" style="display: none;">
                                        <label class="form-label text-muted mb-0">
                                            Extra Cost (Optional)
                                            <i class="bi bi-info-circle text-warning" data-bs-toggle="tooltip"
                                                title="For oversized/overweight items"
                                                style="font-size: 0.75rem; cursor: help;"></i>
                                        </label>
                                        <input type="number" name="extra_cost[]" class="form-control extra-cost-input"
                                            placeholder="0.00" min="0" step="0.01"
                                            value="{{ old('extra_cost.' . $idx, 0) }}" data-index="{{ $idx }}">
                                    </div>

                                    {{-- Extra Cost Reason --}}
                                    <div class="col-md-2 extra-cost-reason-column" style="display: none;">
                                        <label class="form-label text-muted mb-0">Reason
                                            <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                                title="Optional: Explain the reason for extra cost"
                                                style="font-size: 0.75rem; cursor: help;"></i>
                                        </label>
                                        <input type="text" name="extra_cost_reason[]"
                                            class="form-control extra-cost-reason-input"
                                            placeholder="e.g., Oversized: 150x100x80cm" maxlength="255"
                                            value="{{ old('extra_cost_reason.' . $idx) }}">
                                    </div>

                                    {{-- Final Int. Cost Display --}}
                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">
                                            Final Int. Cost
                                            <i class="bi bi-info-circle text-success" data-bs-toggle="tooltip"
                                                title="Final International Cost = Base Cost + Extra Cost"
                                                style="font-size: 0.75rem; cursor: help;"></i>
                                        </label>
                                        <input type="number" class="form-control final-cost-display" placeholder="0.00"
                                            min="0" step="0.01" readonly value="0.00"
                                            style="background-color: #d1f2eb; font-weight: 600; color: #0f5132; border: 1px solid #a3cfbb;">
                                    </div>

                                    {{-- Destination --}}
                                    <div class="col-md-2">
                                        <label class="form-label text-muted mb-0">
                                            Destination <span class="text-danger">*</span>
                                        </label>
                                        <select name="destination[]" class="form-select" required>
                                            <option value="">Select</option>
                                            <option value="SG"
                                                {{ old('destination.' . $idx, 'SG') == 'SG' ? 'selected' : '' }}>
                                                Singapore</option>
                                            <option value="BT"
                                                {{ old('destination.' . $idx) == 'BT' ? 'selected' : '' }}>Batam</option>
                                            <option value="CN"
                                                {{ old('destination.' . $idx) == 'CN' ? 'selected' : '' }}>China</option>
                                            <option value="MY"
                                                {{ old('destination.' . $idx) == 'MY' ? 'selected' : '' }}>Malaysia
                                            </option>
                                            <option value="Other"
                                                {{ old('destination.' . $idx) == 'Other' ? 'selected' : '' }}>Other
                                            </option>
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
                    @empty
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            No valid items found. Please go back and select items.
                        </div>
                    @endforelse

                    {{-- Submit Button --}}
                    @if (!$allItems->isEmpty())
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
        .base-cost-display[readonly] {
            background-color: #e3f2fd !important;
            font-weight: 500;
            color: #1976d2 !important;
            border: 1px solid #bbdefb;
        }

        /* Final cost display */
        .final-cost-display[readonly] {
            background-color: #d1f2eb !important;
            font-weight: 500;
            color: #0f5132 !important;
            border: 1px solid #a3cfbb;
        }

        /* Focus state for readonly inputs (prevent outline) */
        .base-cost-display[readonly]:focus,
        .final-cost-display[readonly]:focus {
            outline: none;
            box-shadow: none;
        }

        /* Item card hover effect */
        .item-card {
            transition: all 0.3s ease;
        }

        .item-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Item dengan extra cost - warning indicator */
        .item-card.border-warning {
            border-left: 4px solid #ffc107 !important;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
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

        /* Extra cost input styling */
        .extra-cost-input {
            border-left: 3px solid #ffc107;
            transition: all 0.3s ease;
        }

        .extra-cost-input:focus {
            border-left-color: #ff9800;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        .extra-cost-input.border-warning {
            background: linear-gradient(to right, #fff3cd 0%, #ffffff 100%);
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

            // ===== FREIGHT METHOD CHANGE HANDLER =====
            $('#freight_method').on('change', function() {
                const method = $(this).val();

                if (method === 'Air Freight') {
                    $('.extra-cost-column').slideDown(300);
                    $('.extra-cost-reason-column').slideDown(300);
                    showAirFreightInfo();
                } else {
                    $('.extra-cost-column').slideUp(300);
                    $('.extra-cost-reason-column').slideUp(300);
                    $('.extra-cost-input').val('0');
                    $('.extra-cost-reason-input').val('');
                }

                calculateIntCosts();
            });

            // ===== SHOW AIR FREIGHT INFO MESSAGE =====
            function showAirFreightInfo() {
                const alertHtml = `
                    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert" id="air-freight-alert">
                        <i class="bi bi-airplane me-2"></i>
                        <strong>Air Freight Selected</strong> -
                        You can now add extra cost for oversized/overweight items.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;

                if ($('#air-freight-alert').length === 0) {
                    $('.row.g-3.mb-4').after(alertHtml);
                    setTimeout(() => {
                        $('#air-freight-alert').fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            }

            // ===== COST CALCULATION MAIN FUNCTION =====
            function calculateIntCosts() {
                const method = $('#int_allocation_method').val();
                const freightPrice = parseFloat($('#freight_price').val()) || 0;

                if (freightPrice <= 0) {
                    console.warn('⚠️ Freight price is zero or invalid');
                    $('.base-cost-display').val('');
                    // Use .val() instead of .text()
                    $('.final-cost-display').val('0.00');
                    return;
                }

                // Calculate BASE allocation
                if (method === 'quantity') {
                    calculateByQuantity(freightPrice);
                } else if (method === 'percentage') {
                    calculateByPercentage(freightPrice);
                } else if (method === 'value') {
                    calculateByValue(freightPrice);
                }

                // Update final costs (base + extra)
                updateFinalCosts();
            }

            // ===== CALCULATE BY QUANTITY =====
            function calculateByQuantity(totalCost) {
                let totalQuantity = 0;
                $('.item-card').each(function() {
                    const qty = parseFloat($(this).data('quantity')) || 0;
                    totalQuantity += qty;
                });

                if (totalQuantity <= 0) {
                    $('.base-cost-display').val('0');
                    return;
                }

                $('.item-card').each(function() {
                    const qty = parseFloat($(this).data('quantity')) || 0;
                    const allocatedCost = (qty / totalQuantity) * totalCost;
                    $(this).find('.base-cost-display').val(allocatedCost.toFixed(2));
                });
            }

            // ===== CALCULATE BY PERCENTAGE =====
            function calculateByPercentage(totalCost) {
                $('.item-card').each(function() {
                    const percentage = parseFloat($(this).find('.percentage-input').val()) || 0;
                    const allocatedCost = (percentage / 100) * totalCost;
                    $(this).find('.base-cost-display').val(allocatedCost.toFixed(2));
                });

                updatePercentageTotal();
            }

            // ===== CALCULATE BY VALUE =====
            function calculateByValue(totalCost) {
                let totalValue = 0;
                $('.item-card').each(function() {
                    const value = parseFloat($(this).data('value')) || 0;
                    totalValue += value;
                });

                if (totalValue <= 0) {
                    $('.base-cost-display').val('0');
                    return;
                }

                $('.item-card').each(function() {
                    const value = parseFloat($(this).data('value')) || 0;
                    const allocatedCost = (value / totalValue) * totalCost;
                    $(this).find('.base-cost-display').val(allocatedCost.toFixed(2));
                });
            }

            // ===== UPDATE FINAL COSTS (BASE + EXTRA) =====
            function updateFinalCosts() {
                $('.item-card').each(function() {
                    const $card = $(this);
                    const baseCost = parseFloat($card.find('.base-cost-display').val()) || 0;
                    const extraCost = parseFloat($card.find('.extra-cost-input').val()) || 0;

                    const finalCost = baseCost + extraCost;

                    // Use .val() instead of .text()
                    $card.find('.final-cost-display').val(finalCost.toFixed(2));

                    // Visual indicator for extra cost
                    if (extraCost > 0) {
                        $card.addClass('border-warning');
                        $card.find('.extra-cost-input').addClass('border-warning bg-warning-subtle');
                    } else {
                        $card.removeClass('border-warning');
                        $card.find('.extra-cost-input').removeClass('border-warning bg-warning-subtle');
                    }
                });

                updateGrandTotal();
            }

            // ===== UPDATE PERCENTAGE TOTAL =====
            function updatePercentageTotal() {
                let total = 0;
                $('.percentage-input').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });

                $('#total-percentage').text(total.toFixed(2));

                const $validation = $('.percentage-validation');
                if (Math.abs(total - 100) < 0.5) {
                    $validation.removeClass('invalid').addClass('valid');
                    $validation.find('.alert').removeClass('alert-danger').addClass('alert-success');
                } else {
                    $validation.removeClass('valid').addClass('invalid');
                    $validation.find('.alert').removeClass('alert-success').addClass('alert-danger');
                }
            }

            // ===== UPDATE GRAND TOTAL =====
            function updateGrandTotal() {
                let grandTotal = 0;
                $('.item-card').each(function() {
                    // Use .val() instead of .text()
                    const finalCost = parseFloat($(this).find('.final-cost-display').val()) || 0;
                    grandTotal += finalCost;
                });

                $('#grand-total-display').text(grandTotal.toFixed(2));
            }

            // ===== TOGGLE PERCENTAGE COLUMN =====
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

            // ===== EVENT LISTENERS =====

            // Allocation method change
            $('#int_allocation_method').on('change', function() {
                togglePercentageColumn();
                calculateIntCosts();
            });

            // Freight price change
            $('#freight_price').on('input', function() {
                calculateIntCosts();
            });

            // Percentage input change
            $(document).on('input', '.percentage-input', function() {
                if ($('#int_allocation_method').val() === 'percentage') {
                    calculateIntCosts();
                }
            });

            // Extra cost input change
            $(document).on('input', '.extra-cost-input', function() {
                updateFinalCosts();
            });

            // Auto-fill reason when extra cost is entered
            $(document).on('blur', '.extra-cost-input', function() {
                const $card = $(this).closest('.item-card');
                const extraCost = parseFloat($(this).val()) || 0;
                const $reasonInput = $card.find('.extra-cost-reason-input');

                if (extraCost > 0 && !$reasonInput.val()) {
                    $reasonInput.attr('placeholder', 'Please provide reason for extra cost');
                    $reasonInput.addClass('border-warning');
                } else {
                    $reasonInput.removeClass('border-warning');
                }
            });

            // Auto Distribute button
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

            // ===== FORM SUBMIT VALIDATION =====
            let isFormSubmitting = false;

            $('#shipping-form').on('submit', function(e) {
                // Jika sudah submit, cegah submit kedua
                if (isFormSubmitting) {
                    console.warn('❌ Form already submitting - prevented duplicate');
                    e.preventDefault();
                    return false;
                }

                const method = $('#int_allocation_method').val();

                // Validate percentage total
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

                // Validate extra cost has reason (Air Freight only)
                if ($('#freight_method').val() === 'Air Freight') {
                    let hasExtraCostWithoutReason = false;

                    $('.item-card').each(function() {
                        const extraCost = parseFloat($(this).find('.extra-cost-input').val()) || 0;
                        const reason = $(this).find('.extra-cost-reason-input').val().trim();

                        if (extraCost > 0 && !reason) {
                            $(this).find('.extra-cost-reason-input').addClass('is-invalid');
                            hasExtraCostWithoutReason = true;
                        }
                    });

                    if (hasExtraCostWithoutReason) {
                        e.preventDefault();
                        alert('Please provide reason for all extra costs in Air Freight mode.');
                        return false;
                    }
                }

                // SET FLAG & DISABLE BUTTON
                isFormSubmitting = true;

                const $submitBtn = $('#submit-btn');
                $submitBtn.prop('disabled', true);
                $submitBtn.find('.spinner-border').removeClass('d-none');
                $submitBtn.find('i.bi-send-fill').addClass('d-none');

                // Safety timeout - reset flag after 60 seconds
                setTimeout(() => {
                    isFormSubmitting = false;
                    $submitBtn.prop('disabled', false);
                    $submitBtn.find('.spinner-border').addClass('d-none');
                    $submitBtn.find('i.bi-send-fill').removeClass('d-none');
                    alert('⚠️ Request timeout. Please try again.');
                }, 60000);
            });

            // Remove validation error on input
            $(document).on('input', '.extra-cost-reason-input', function() {
                $(this).removeClass('is-invalid');
            });

            // ===== INITIAL SETUP =====
            togglePercentageColumn();
            calculateIntCosts();

            // Check if Air Freight is pre-selected
            if ($('#freight_method').val() === 'Air Freight') {
                $('.extra-cost-column').show();
                $('.extra-cost-reason-column').show();
            }

            setTimeout(() => {
                $('.alert-info').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 8000);
        });
    </script>
@endpush
