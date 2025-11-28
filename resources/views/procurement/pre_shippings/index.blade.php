@extends('layouts.app')

@push('styles')
    <style>
        .card.shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0.5rem;
        }

        .card-header h4 {
            font-size: 1.3rem;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .card-header .text-muted {
            font-size: 0.9rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .header-right {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 1rem;
            align-items: center;
        }

        /* Time/Info Display Cards */
        .info-display-card {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.3s ease;
            min-width: 180px;
        }

        /* Validation feedback styling */
        .group-waybill-input.is-invalid,
        .group-cost-input.is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5;
        }

        .group-waybill-input.is-invalid:focus,
        .group-cost-input.is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Animated glow effect untuk invalid fields */
        @keyframes pulseInvalid {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
        }

        .group-waybill-input.border-danger,
        .group-cost-input.border-danger {
            animation: pulseInvalid 2s infinite;
        }

        /* Valid state - remove immediately after fix */
        .form-control.is-valid {
            border-color: #198754;
        }

        /* Prevent modal backdrop dari mengubah scroll */
        .swal2-shown {
            overflow: visible !important;
        }

        .swal2-backdrop {
            background: rgba(0, 0, 0, 0.1) !important;
        }

        /* Warning message untuk incomplete fields */
        small.text-danger {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .info-display-card:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            transform: translateY(-1px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .info-display-card.primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border: none;
        }

        .info-display-card.primary:hover {
            background: linear-gradient(135deg, #0a58ca 0%, #084298 100%);
            transform: translateY(-1px);
            box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.3);
        }

        .info-display-card i {
            font-size: 1.2rem;
            margin-right: 0.75rem;
        }

        .info-display-content {
            display: flex;
            flex-direction: column;
        }

        .info-display-label {
            font-size: 0.7rem;
            line-height: 1;
            margin-bottom: 0.25rem;
            opacity: 0.9;
        }

        .info-display-value {
            font-size: 0.95rem;
            line-height: 1.2;
            font-weight: 600;
        }

        /* Selected Count Badge */
        .selected-count {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 110, 253, 0.2);
        }

        /* Button Proceed Shipping */
        .btn-proceed-shipping {
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .btn-proceed-shipping:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-proceed-shipping:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .header-right {
                flex-direction: column;
                align-items: stretch;
                justify-content: center;
            }

            .info-display-card {
                width: 100%;
                min-width: unset;
                justify-content: center;
            }

            .header-left {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .card-group-item {
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .card-group-item.selected {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .checkbox-container {
            position: relative;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .allocation-input {
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .allocation-input:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

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

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
        }

        .btn-proceed-shipping {
            position: sticky;
            bottom: 20px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .selected-count {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .auto-save-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background: #198754;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .auto-save-indicator.saving {
            background: #ffc107;
            opacity: 1;
        }

        .auto-save-indicator.saved {
            background: #198754;
            opacity: 1;
            animation: fadeOut 2s ease-in-out forwards;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
            }

            70% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        .group-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
        }

        .cost-method-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .allocated-cost-highlight {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-weight: 600;
        }

        .shipped-group .custom-checkbox {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .shipped-group .allocation-input,
        .shipped-group .allocation-method-select,
        .shipped-group .percentage-input {
            pointer-events: none;
            background-color: #e9ecef !important;
            cursor: not-allowed;
        }

        /* ===== FILTER PILLS STYLING ===== */
        .filter-pills-wrapper {
            padding: 0.75rem 1rem;
        }

        .filter-pills-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-pill {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            border: 1px solid #dee2e6;
            background: white;
            color: #6b7280;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            user-select: none;
        }

        .filter-pill:hover {
            border-color: #8F12FE;
            color: #8F12FE;
            box-shadow: 0 2px 8px rgba(143, 18, 254, 0.1);
        }

        .filter-pill.active {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            border-color: #8F12FE;
            color: white;
        }

        .filter-badge {
            background: rgba(0, 0, 0, 0.1);
            padding: 0.1rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 24px;
            text-align: center;
        }

        .filter-pill.active .filter-badge {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Empty state untuk groups */
        .empty-state-filter {
            text-align: center;
            padding: 2rem;
            background: #f9fafb;
            border-radius: 6px;
            border: 1px dashed #e5e7eb;
        }

        .empty-state-filter i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 0.75rem;
        }

        .empty-state-filter h5 {
            color: #6b7280;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .empty-state-filter p {
            color: #9ca3af;
            margin: 0;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .filter-pill {
                padding: 0.35rem 0.85rem;
                font-size: 0.85rem;
            }

            .filter-badge {
                font-size: 0.7rem;
            }
        }

        .new-group-highlight {
            border: 3px solid #198754 !important;
            box-shadow: 0 0 20px rgba(25, 135, 84, 0.3);
            animation: pulseGreen 2s ease-in-out 3;
        }

        @keyframes pulseGreen {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(25, 135, 84, 0.3);
            }

            50% {
                box-shadow: 0 0 30px rgba(25, 135, 84, 0.6);
            }
        }

        .new-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            z-index: 10;
            animation: bounce 1s ease-in-out infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <!-- Header Container -->
                <div class="row align-items-center">
                    {{-- Left Section: Title & Description --}}
                    <div class="col-md-6">
                        <div class="header-left">
                            <div>
                                <i class="bi bi-truck-front gradient-icon me-2" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 flex-shrink-0">Pre Shipping Management</h4>
                                <p class="text-muted mb-0 small">Grouped by Supplier & Delivery Date</p>
                            </div>
                        </div>
                    </div>

                    {{-- Right Section: Info Display & Buttons --}}
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="header-right">
                            {{-- Selected Count Display --}}
                            <span class="selected-count" id="selected-count" style="display: none;">
                                <i class="fas fa-check-circle"></i>
                                <span id="count-text">0 selected</span>
                            </span>

                            {{-- Proceed to Shipping Button --}}
                            <button type="button" class="btn btn-success btn-proceed-shipping" id="proceed-shipping-btn"
                                style="display: none;" disabled>
                                <i class="fas fa-arrow-right me-2"></i>
                                Proceed to Shipping
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!--  FILTER PILLS SECTION -->
            <div class="filter-pills-wrapper">
                <div class="filter-pills-container" id="filter-pills-container">
                    <button type="button" class="filter-pill" data-filter="all">
                        All
                        <span class="filter-badge">{{ $groupedPreShippings->count() }}</span>
                    </button>

                    <button type="button" class="filter-pill active" data-filter="not-shipped">
                        Not Shipped
                        <span class="filter-badge">
                            {{ $groupedPreShippings->filter(fn($g) => !$g['has_been_shipped'])->count() }}
                        </span>
                    </button>

                    <button type="button" class="filter-pill" data-filter="shipped">
                        Shipped
                        <span class="filter-badge">
                            {{ $groupedPreShippings->filter(fn($g) => $g['has_been_shipped'])->count() }}
                        </span>
                    </button>
                </div>
            </div>

            <div class="card-body" style="padding-top: 0;" id="groups-container">
                @forelse ($groupedPreShippings as $group)
                    <div class="card mb-4 border-primary card-group-item" data-group="{{ $group['group_key'] }}"
                        data-shipped="{{ $group['has_been_shipped'] ? 'true' : 'false' }}"
                        data-filter-group="{{ $group['has_been_shipped'] ? 'shipped' : 'not-shipped' }}">

                        <!-- Group Header with Checkbox -->
                        <div class="card-header group-header">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="checkbox-container flex-shrink-0">
                                    <input type="checkbox" class="form-check-input custom-checkbox group-checkbox"
                                        id="group-{{ $group['group_key'] }}" data-group="{{ $group['group_key'] }}"
                                        {{ $group['has_been_shipped'] ? 'disabled' : '' }}>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <label class="form-check-label m-0" for="group-{{ $group['group_key'] }}">
                                        <small class="text-muted">Supplier:
                                            <strong>{{ $group['supplier']->name ?? 'Unknown Supplier' }}</strong>
                                        </small>
                                    </label>
                                    @if ($group['has_been_shipped'])
                                        <span class="badge bg-success ms-1">
                                            <i class="fas fa-check-circle me-1"></i>Shipped
                                        </span>
                                    @endif
                                </div>
                                <div class="vr"></div>
                                <div class="flex-shrink-0">
                                    <small class="text-muted">Delivery:
                                        <strong>{{ \Carbon\Carbon::parse($group['delivery_date'])->format('d M Y') }}</strong>
                                    </small>
                                </div>
                                <div class="vr"></div>
                                <div class="flex-shrink-0">
                                    <small class="text-muted">Items:
                                        <strong>{{ $group['total_items'] }}</strong>
                                    </small>
                                </div>
                                <div class="vr"></div>
                                <div class="flex-shrink-0">
                                    <small class="text-muted">Total Qty:
                                        <strong>{{ number_format($group['total_quantity'], 2) }}</strong>
                                    </small>
                                </div>
                                <div class="vr"></div>
                                <div class="flex-shrink-0">
                                    <small class="text-muted">Total Value:
                                        <strong>{{ number_format($group['total_value'], 2) }}</strong>
                                    </small>
                                </div>
                                <div class="vr"></div>
                                {{-- Cost Method Badge (aligned to right) --}}
                                <div class="ms-auto flex-shrink-0">
                                    <span class="badge cost-method-badge bg-info">
                                        {{ ucfirst(str_replace('_', ' ', $group['cost_allocation_method'])) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <!-- Group Controls -->
                            <div class="row mb-4">
                                <div class="col-md-3 position-relative">
                                    <label class="form-label">
                                        Domestic Waybill
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control allocation-input group-waybill-input"
                                        data-group="{{ $group['group_key'] }}" value="{{ $group['domestic_waybill_no'] }}"
                                        placeholder="Enter waybill number" required>
                                    <div class="auto-save-indicator"></div>
                                </div>

                                <div class="col-md-2 position-relative">
                                    <label class="form-label">
                                        Domestic Cost
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control allocation-input group-cost-input"
                                            data-group="{{ $group['group_key'] }}"
                                            value="{{ rtrim(rtrim(number_format($group['domestic_cost'] ?? 0, 2, '.', ''), '0'), '.') }}"
                                            min="0" step="0.01" placeholder="0" required>
                                        <span class="input-group-text">
                                            {{ $group['items']->first()->purchaseRequest->currency->name ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="auto-save-indicator"></div>
                                </div>

                                <div class="col-md-3 position-relative">
                                    <label class="form-label">Cost Allocation Method</label>
                                    <select class="form-select allocation-input allocation-method-select"
                                        data-group="{{ $group['group_key'] }}">
                                        <option value="quantity"
                                            {{ ($group['cost_allocation_method'] ?? 'value') == 'quantity' ? 'selected' : '' }}>
                                            By Quantity
                                        </option>
                                        <option value="percentage"
                                            {{ ($group['cost_allocation_method'] ?? 'value') == 'percentage' ? 'selected' : '' }}>
                                            By Percentage
                                        </option>
                                        <option value="value"
                                            {{ ($group['cost_allocation_method'] ?? 'value') == 'value' ? 'selected' : '' }}>
                                            By Value
                                        </option>
                                    </select>
                                    <div class="auto-save-indicator"></div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table table-sm align-middle table-hover">
                                    <thead class="table-primary text-nowrap">
                                        <tr>
                                            <th>Material Name</th>
                                            <th>Project</th>
                                            <th>Qty to Buy</th>
                                            <th>Unit Price</th>
                                            <th>Total Value</th>
                                            <th class="percentage-column {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}"
                                                data-group="{{ $group['group_key'] }}">
                                                Allocation %
                                            </th>
                                            <th>Allocated Cost</th>
                                        </tr>
                                    </thead>
                                    {{-- Add data-group to tbody --}}
                                    <tbody class="item-tbody" data-group="{{ $group['group_key'] }}">
                                        @foreach ($group['items'] as $index => $item)
                                            @php
                                                $qtyToBuy =
                                                    $item->purchaseRequest->qty_to_buy ??
                                                    ($item->purchaseRequest->required_quantity ?? 0);
                                                $unitPrice = $item->purchaseRequest->price_per_unit ?? 0;
                                                $itemValue = $qtyToBuy * $unitPrice;
                                                $currencyName = $item->purchaseRequest->currency->name ?? 'IDR';
                                            @endphp

                                            {{-- Add data-group, data-quantity, data-value to TR --}}
                                            <tr data-item-id="{{ $item->id }}" data-index="{{ $index }}"
                                                data-group="{{ $group['group_key'] }}"
                                                data-quantity="{{ $qtyToBuy }}" data-value="{{ $itemValue }}">

                                                <td>
                                                    <strong class="text-dark">
                                                        {{ $item->purchaseRequest->material_name }}
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $item->purchaseRequest->project->name ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-primary" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="{{ $item->purchaseRequest->unit ?? 'unit' }}">
                                                        {{ number_format($qtyToBuy, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-muted small" data-bs-toggle="tooltip"
                                                        title="{{ $currencyName }}">
                                                        {{ number_format($unitPrice, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold" data-bs-toggle="tooltip"
                                                        title="{{ $currencyName }}">
                                                        {{ number_format($itemValue, 2) }}
                                                    </span>
                                                </td>

                                                {{-- Percentage Input Column --}}
                                                <td class="percentage-column {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}"
                                                    data-group="{{ $group['group_key'] }}">
                                                    <div class="position-relative">
                                                        <input type="number"
                                                            class="form-control form-control-sm allocation-input percentage-input"
                                                            data-index="{{ $index }}"
                                                            data-group="{{ $group['group_key'] }}"
                                                            data-item-id="{{ $item->id }}"
                                                            value="{{ rtrim(rtrim(number_format($item->allocation_percentage ?? 0, 2, '.', ''), '0'), '.') }}"
                                                            min="0" max="100" step="0.01"
                                                            placeholder="0">
                                                        <div class="auto-save-indicator"></div>
                                                    </div>
                                                </td>

                                                {{-- Add .allocated-cost-display class --}}
                                                <td>
                                                    <div class="allocated-cost-cell allocated-cost-highlight">
                                                        <span class="allocated-amount allocated-cost-display"
                                                            data-bs-toggle="tooltip" data-bs-placement="left"
                                                            title="{{ $currencyName }}">
                                                            {{ number_format($item->allocated_cost ?? 0, 2) }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Percentage Total Validation -->
                            <div class="percentage-validation {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}"
                                data-group="{{ $group['group_key'] }}"
                                {{ $group['has_been_shipped'] ? 'style=opacity:0.6;pointer-events:none;' : '' }}>
                                <div class="alert alert-success">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fas fa-calculator me-2"></i>
                                            <strong>Total Percentage:
                                                <span class="total-percentage">
                                                    {{ rtrim(rtrim(number_format($group['items']->sum('allocation_percentage'), 3, '.', ''), '0'), '.') }}
                                                </span>%
                                            </strong>
                                            <small class="text-muted ms-3">
                                                {{ $group['has_been_shipped'] ? '(Locked - Already Shipped)' : '(Target: 100%)' }}
                                            </small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary auto-distribute-btn"
                                            title="Distribute percentage based on item value"
                                            data-group="{{ $group['group_key'] }}"
                                            {{ $group['has_been_shipped'] ? 'disabled' : '' }}>
                                            <i class="fas fa-magic me-1"></i>Auto Distribute
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" id="empty-state-initial">
                        <i class="fas fa-box-open"></i>
                        <h5>No approved purchase requests ready for pre-shipping</h5>
                        <p>Purchase requests need to have supplier and delivery date assigned.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- SECTION: SHORTAGE ITEMS --}}
        <div class="card shadow-sm mb-4" id="shortage-items-section">
            <div class="card-header bg-warning bg-opacity-10">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                            Shortage Items Management
                        </h5>

                        {{-- ⭐ NEW: Tabs untuk Pending vs History --}}
                        <ul class="nav nav-tabs nav-tabs-sm" id="shortage-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab"
                                    data-bs-target="#pending-shortage" type="button" role="tab">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    Pending Resend
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="history-tab" data-bs-toggle="tab"
                                    data-bs-target="#history-shortage" type="button" role="tab">
                                    <i class="bi bi-clock-history me-1"></i>
                                    History
                                </button>
                            </li>
                        </ul>
                    </div>
                    <span class="badge bg-warning text-dark fs-6" id="shortage-count-badge">
                        0 Items
                    </span>
                </div>
            </div>

            <div class="card-body">
                {{-- TAB CONTENT --}}
                <div class="tab-content" id="shortage-tab-content">
                    {{-- TAB 1: Pending Resend --}}
                    <div class="tab-pane fade show active" id="pending-shortage" role="tabpanel">
                        <div id="shortage-loading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2">Loading shortage items...</p>
                        </div>

                        <div id="shortage-items-container" style="display: none;">
                            {{-- Existing table code --}}
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="3%" class="text-center">
                                                <input type="checkbox" id="select-all-shortage" class="form-check-input">
                                            </th>
                                            <th width="12%">Material Name</th>
                                            <th width="10%">Supplier</th>
                                            <th width="8%">Project</th>
                                            <th width="8%" class="text-end">Purchased</th>
                                            <th width="8%" class="text-end">Received</th>
                                            <th width="8%" class="text-end">
                                                <span class="text-danger fw-bold">Shortage</span>
                                            </th>
                                            <th width="10%">Old Domestic WBL</th>
                                            <th width="8%">Status</th>
                                            <th width="8%">Resend Count</th>
                                            <th width="10%">Detected Date</th>
                                            <th width="7%" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="shortage-items-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="shortage-empty-state" style="display: none;">
                            <div class="empty-state-filter">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <h5>No Shortage Items</h5>
                                <p>All goods have been received in full quantity</p>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: History (Reshipped/Canceled) --}}
                    <div class="tab-pane fade" id="history-shortage" role="tabpanel">
                        <div id="history-loading" class="text-center py-4">
                            <div class="spinner-border text-secondary" role="status"></div>
                            <p class="text-muted mt-2">Loading history...</p>
                        </div>

                        <div id="history-items-container" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Material Name</th>
                                            <th>Supplier</th>
                                            <th class="text-end">Shortage Qty</th>
                                            <th>Status</th>
                                            <th class="text-center">Resend Count</th>
                                            <th>Last Updated</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="history-items-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="history-empty-state" style="display: none;">
                            <div class="empty-state-filter">
                                <i class="bi bi-clock-history text-muted"></i>
                                <h5>No History</h5>
                                <p>No reshipped or canceled items found</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form untuk proceed to shipping -->
    <form id="proceed-shipping-form" action="{{ route('shippings.create') }}" method="GET" style="display: none;">
        <input type="hidden" name="group_keys" id="selected-group-keys">
        <input type="hidden" name="shortage_item_ids" id="selected-shortage-ids">
    </form>
@endsection

@push('scripts')
    <script>
        // ===== 1. HELPER FUNCTIONS (Global Scope) =====
        function formatDynamicNumber(number) {
            if (typeof number === 'undefined' || number === null) return '0';

            number = parseFloat(number);
            if (isNaN(number)) return '0';

            let formatted = number.toFixed(2);
            formatted = formatted.replace(/\.?0+$/, '');

            return formatted;
        }

        function formatCurrency(number) {
            if (typeof number === 'undefined' || number === null) return '0.00';

            number = parseFloat(number);
            if (isNaN(number)) return '0.00';

            return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // ===== 2. REAL-TIME CALCULATION FUNCTIONS =====
        function calculateAllocatedCostRealtime(groupKey, method) {
            const $tbody = $(`.item-tbody[data-group="${groupKey}"]`);
            const totalCost = parseFloat($(`.group-cost-input[data-group="${groupKey}"]`).val()) || 0;

            // ⭐ DEBUG LOGGING
            console.log('🔄 Calculate Allocated Cost', {
                groupKey: groupKey,
                method: method,
                totalCost: totalCost,
                tbodyFound: $tbody.length,
                rowsFound: $tbody.find('tr[data-group]').length
            });

            if (totalCost <= 0) {
                console.warn('⚠️ Total cost is zero or invalid');
                $tbody.find('.allocated-cost-display').text('0.00');
                return;
            }

            switch (method) {
                case 'percentage':
                    calculateByPercentageRealtime($tbody, totalCost);
                    break;
                case 'quantity':
                    calculateByQuantityRealtime($tbody, totalCost);
                    break;
                case 'value':
                default:
                    calculateByValueRealtime($tbody, totalCost);
                    break;
            }

            console.log('✅ Calculation complete for group:', groupKey);
        }

        function calculateByPercentageRealtime($tbody, totalCost) {
            // ⭐ FIX: Access TR elements with proper data attributes
            $tbody.find('tr[data-group]').each(function() {
                const percentage = parseFloat($(this).find('.percentage-input').val()) || 0;
                const allocatedCost = (percentage / 100) * totalCost;

                // ⭐ FIX: Update display dengan formatDynamicNumber
                $(this).find('.allocated-cost-display').text(formatDynamicNumber(allocatedCost));
            });
        }

        function calculateByQuantityRealtime($tbody, totalCost) {
            let totalQuantity = 0;

            // ⭐ FIX: Calculate total dari data attributes
            $tbody.find('tr[data-group]').each(function() {
                const qty = parseFloat($(this).data('quantity')) || 0;
                totalQuantity += qty;
            });

            if (totalQuantity <= 0) {
                $tbody.find('.allocated-cost-display').text('0.00');
                return;
            }

            // ⭐ FIX: Distribute cost berdasarkan quantity proportion
            $tbody.find('tr[data-group]').each(function() {
                const qty = parseFloat($(this).data('quantity')) || 0;
                const allocatedCost = (qty / totalQuantity) * totalCost;

                $(this).find('.allocated-cost-display').text(formatDynamicNumber(allocatedCost));
            });
        }

        function calculateByValueRealtime($tbody, totalCost) {
            let totalValue = 0;

            $tbody.find('tr[data-group]').each(function() {
                const value = parseFloat($(this).data('value')) || 0;
                totalValue += value;

                // ⭐ DEBUG
                console.log('  Item value:', value);
            });

            console.log('📊 Total value:', totalValue);

            if (totalValue <= 0) {
                console.warn('⚠️ Total value is zero');
                $tbody.find('.allocated-cost-display').text('0.00');
                return;
            }

            $tbody.find('tr[data-group]').each(function() {
                const value = parseFloat($(this).data('value')) || 0;
                const allocatedCost = (value / totalValue) * totalCost;

                $(this).find('.allocated-cost-display').text(formatDynamicNumber(allocatedCost));

                // ⭐ DEBUG
                console.log('  Allocated cost:', allocatedCost, 'for value:', value);
            });
        }

        // ===== 3. MAIN DOCUMENT READY BLOCK =====
        $(document).ready(function() {
            // ===== 3.1 GLOBAL VARIABLES =====
            let updateTimeout = {};
            let selectedGroups = new Set();
            let selectedShortages = new Set(); // ⭐ SINGLE DECLARATION
            let hasUnsavedChanges = false;
            let isOnline = navigator.onLine;
            let isInitializing = true;

            // ===== 3.2 INITIALIZATION =====
            initializePage();
            loadShortageItems();
            checkSuccessMessage();
            initializeFilterPills();

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // ===== 3.3 EVENT HANDLERS =====

            // Group checkbox selection
            $('.group-checkbox').on('change', handleGroupSelection);

            // Shortage checkbox selection
            $(document).on('change', '.shortage-checkbox', handleShortageSelection);

            // Select all shortage
            $('#select-all-shortage').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.shortage-checkbox').prop('checked', isChecked).trigger('change');
            });

            // Proceed to shipping button
            $('#proceed-shipping-btn').on('click', handleProceedToShipping);

            // Allocation method change
            $('.allocation-method-select').on('change', handleAllocationMethodChange);

            // Input changes dengan real-time calculation
            $('.allocation-input').on('input', handleInputChange);
            $(document).on('input', '.percentage-input', handlePercentageInput);
            $(document).on('input', '.group-cost-input', handleDomesticCostInput);

            // Auto-distribute button
            $(document).on('click', '.auto-distribute-btn', handleAutoDistribute);

            // Bulk resend shortage
            $('#btn-bulk-resend-shortage').on('click', handleBulkResendShortage);

            // Cancel shortage
            $(document).on('click', '.btn-cancel-shortage', handleCancelShortage);

            // History tab click
            $('#history-tab').on('click', function() {
                loadShortageHistory();
            });

            // Visual feedback for inputs
            $('.allocation-input').on('focus', function() {
                $(this).addClass('border-primary');
            }).on('blur', function() {
                $(this).removeClass('border-primary');
            });

            // Online/Offline detection
            window.addEventListener('online', function() {
                isOnline = true;
                showToast('success', 'Connection restored');
            });

            window.addEventListener('offline', function() {
                isOnline = false;
                showToast('error', 'Connection lost. Changes may not be saved.');
            });

            // Warn before page unload if unsaved changes
            window.addEventListener('beforeunload', function(e) {
                if (hasUnsavedChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // ===== 3.4 FUNCTION DEFINITIONS =====

            function initializePage() {
                isInitializing = true;

                $('.allocation-method-select').each(function() {
                    const currentValue = $(this).val() || 'value';
                    $(this).data('previous-value', currentValue);

                    const groupKey = $(this).data('group');
                    updateMethodBadge(groupKey, currentValue);

                    setTimeout(() => {
                        calculateAllocatedCostRealtime(groupKey, currentValue);
                    }, 100);
                });

                $('.percentage-validation').each(function() {
                    const groupKey = $(this).data('group');
                    updatePercentageTotal(groupKey);
                });

                setTimeout(() => {
                    isInitializing = false;
                }, 500);
            }

            function checkSuccessMessage() {
                const urlParams = new URLSearchParams(window.location.search);
                const successMsg = urlParams.get('success');
                const highlightNew = urlParams.get('highlight_groups');

                if (successMsg) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        html: decodeURIComponent(successMsg),
                        timer: 5000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });

                    // Highlight new groups
                    if (highlightNew === 'new') {
                        setTimeout(() => {
                            $('.card-group-item').each(function() {
                                const $card = $(this);
                                const $firstItem = $card.find('[data-created-at]').first();

                                if ($firstItem.length) {
                                    const createdAt = new Date($firstItem.data('created-at'));
                                    const now = new Date();
                                    const diffMinutes = (now - createdAt) / 1000 / 60;

                                    if (diffMinutes < 5) {
                                        $card.addClass('new-group-highlight')
                                            .prepend(
                                                '<span class="badge bg-success new-badge">NEW</span>'
                                            );

                                        if ($('.new-group-highlight').length === 1) {
                                            $('html, body').animate({
                                                scrollTop: $card.offset().top - 100
                                            }, 500);
                                        }

                                        setTimeout(() => {
                                            $card.removeClass('new-group-highlight');
                                            $card.find('.new-badge').fadeOut(300,
                                                function() {
                                                    $(this).remove();
                                                });
                                        }, 10000);
                                    }
                                }
                            });
                        }, 500);
                    }

                    // Clean URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }

            function initializeFilterPills() {
                // Existing filter pills logic...
                const filterPillsContainer = $('#filter-pills-container');
                const groupsContainer = $('#groups-container');

                groupsContainer.attr('data-current-filter', 'not-shipped');
                applyFilterPills('not-shipped');

                $(document).on('click', '.filter-pill', function(e) {
                    e.preventDefault();
                    const filterValue = $(this).data('filter');

                    if ($(this).hasClass('active')) return;

                    $('.filter-pill').removeClass('active');
                    $(this).addClass('active');

                    groupsContainer.attr('data-current-filter', filterValue);
                    applyFilterPills(filterValue);
                });
            }

            function applyFilterPills(filterValue) {
                const $cards = $('.card-group-item');

                $cards.each(function() {
                    const hasBeenShipped = $(this).data('shipped') === true;

                    if (filterValue === 'not-shipped' && !hasBeenShipped) {
                        $(this).show();
                    } else if (filterValue === 'shipped' && hasBeenShipped) {
                        $(this).show();
                    } else if (filterValue === 'all') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // Show empty state if no cards visible
                const visibleCards = $cards.filter(':visible').length;
                if (visibleCards === 0) {
                    if ($('#empty-state-filter').length === 0) {
                        $('#groups-container').append(`
                    <div id="empty-state-filter" class="empty-state-filter">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <h5>No Items</h5>
                        <p>No groups match the current filter</p>
                    </div>
                `);
                    }
                } else {
                    $('#empty-state-filter').remove();
                }
            }

            // ===== SHORTAGE ITEMS FUNCTIONS =====

            function loadShortageItems() {
                $.ajax({
                    url: '{{ route('shortage-items.by-status') }}',
                    method: 'GET',
                    data: {
                        status: 'resolvable'
                    },
                    success: function(response) {
                        const shortageItems = response.shortage_items;
                        const count = response.total_count;

                        $('#shortage-count-badge').text(count + ' Item' + (count !== 1 ? 's' : ''));

                        if (count === 0) {
                            $('#shortage-loading').hide();
                            $('#shortage-items-container').hide();
                            $('#shortage-empty-state').show();
                        } else {
                            populateShortageTable(shortageItems);
                            $('#shortage-loading').hide();
                            $('#shortage-empty-state').hide();
                            $('#shortage-items-container').show();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading shortage items:', xhr);
                        $('#shortage-loading').hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load shortage items'
                        });
                    }
                });
            }

            function loadShortageHistory() {
                $('#history-loading').show();
                $('#history-items-container').hide();
                $('#history-empty-state').hide();

                $.ajax({
                    url: '{{ route('shortage-items.by-status') }}',
                    method: 'GET',
                    data: {
                        status: 'all'
                    },
                    success: function(response) {
                        const allItems = response.shortage_items;
                        const historyItems = allItems.filter(item => ['reshipped', 'fully_reshipped',
                            'canceled'
                        ].includes(item.status));

                        if (historyItems.length === 0) {
                            $('#history-loading').hide();
                            $('#history-empty-state').show();
                        } else {
                            populateHistoryTable(historyItems);
                            $('#history-loading').hide();
                            $('#history-items-container').show();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading history:', xhr);
                        $('#history-loading').hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load history'
                        });
                    }
                });
            }

            function populateShortageTable(items) {
                const tbody = $('#shortage-items-tbody');
                tbody.empty();

                items.forEach((item, index) => {
                    const pr = item.purchase_request;
                    const statusBadge = getStatusBadge(item.status);
                    const oldWbl = item.old_domestic_wbl || '';

                    const row = `
                <tr data-shortage-id="${item.id}">
                    <td class="text-center">
                        <input type="checkbox"
                               class="form-check-input shortage-checkbox"
                               value="${item.id}">
                    </td>
                    <td>
                        <div class="fw-bold">${item.material_name}</div>
                        <small class="text-muted">PR#${pr.id}</small>
                    </td>
                    <td>${pr.supplier ? pr.supplier.name : '-'}</td>
                    <td>${pr.project ? pr.project.name : '-'}</td>
                    <td class="text-end">${parseFloat(item.purchased_qty).toFixed(2)}</td>
                    <td class="text-end text-warning fw-bold">${parseFloat(item.received_qty).toFixed(2)}</td>
                    <td class="text-end">
                        <span class="badge bg-danger">${parseFloat(item.shortage_qty).toFixed(2)}</span>
                    </td>
                    <td>
                        <input type="text"
                               class="form-control form-control-sm old-wbl-input"
                               placeholder="Optional"
                               value="${oldWbl}"
                               data-shortage-id="${item.id}">
                    </td>
                    <td>${statusBadge}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary">${item.resend_count}x</span>
                    </td>
                    <td>
                        <small>${new Date(item.created_at).toLocaleDateString('en-GB')}</small>
                    </td>
                    <td class="text-center">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-cancel-shortage"
                                data-shortage-id="${item.id}"
                                data-bs-toggle="tooltip"
                                title="Cancel this shortage">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>
            `;

                    tbody.append(row);
                });

                $('[data-bs-toggle="tooltip"]').tooltip();
            }

            function populateHistoryTable(items) {
                const tbody = $('#history-items-tbody');
                tbody.empty();

                items.forEach(item => {
                    const pr = item.purchase_request;
                    const statusBadge = getStatusBadge(item.status);

                    const row = `
                <tr>
                    <td>
                        <div class="fw-bold">${item.material_name}</div>
                        <small class="text-muted">PR#${pr.id}</small>
                    </td>
                    <td>${pr.supplier ? pr.supplier.name : '-'}</td>
                    <td class="text-end">
                        <span class="badge bg-secondary">${parseFloat(item.shortage_qty).toFixed(2)}</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td class="text-center">
                        <span class="badge bg-info">${item.resend_count}x</span>
                    </td>
                    <td>
                        <small>${new Date(item.updated_at).toLocaleDateString('en-GB')}</small>
                    </td>
                    <td>
                        <small class="text-muted text-truncate" style="max-width: 200px; display: inline-block;"
                               data-bs-toggle="tooltip" title="${item.notes || '-'}">
                            ${item.notes || '-'}
                        </small>
                    </td>
                </tr>
            `;

                    tbody.append(row);
                });

                $('[data-bs-toggle="tooltip"]').tooltip();
            }

            function getStatusBadge(status) {
                const badges = {
                    'pending': '<span class="badge bg-warning">Pending Resend</span>',
                    'partially_reshipped': '<span class="badge bg-primary">Partially Reshipped</span>',
                    'reshipped': '<span class="badge bg-info">Reshipped</span>',
                    'fully_reshipped': '<span class="badge bg-success">Fully Reshipped</span>',
                    'canceled': '<span class="badge bg-danger">Canceled</span>'
                };

                return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
            }

            // ===== SELECTION HANDLERS =====

            function handleGroupSelection() {
                const groupKey = $(this).data('group');
                const card = $(this).closest('.card-group-item');

                if ($(this).is(':checked')) {
                    selectedGroups.add(groupKey);
                    card.addClass('selected');
                } else {
                    selectedGroups.delete(groupKey);
                    card.removeClass('selected');
                }
                updateProceedButton();
            }

            function handleShortageSelection() {
                const shortageId = $(this).val();

                if ($(this).is(':checked')) {
                    selectedShortages.add(shortageId);
                } else {
                    selectedShortages.delete(shortageId);
                }

                updateProceedButton();
            }

            function updateProceedButton() {
                const totalSelected = selectedGroups.size + selectedShortages.size;

                if (totalSelected > 0) {
                    $('#proceed-shipping-btn').prop('disabled', false).show();
                    $('#selected-count').show();

                    let countText = '';
                    if (selectedGroups.size > 0) {
                        countText += `${selectedGroups.size} group(s)`;
                    }
                    if (selectedShortages.size > 0) {
                        if (countText) countText += ' + ';
                        countText += `${selectedShortages.size} shortage(s)`;
                    }

                    $('#count-text').text(countText);
                } else {
                    $('#proceed-shipping-btn').prop('disabled', true).hide();
                    $('#selected-count').hide();
                }
            }

            function handleProceedToShipping() {
                if (selectedGroups.size === 0 && selectedShortages.size === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one group or shortage item',
                    });
                    return;
                }

                // Validate normal groups
                let incompleteGroups = [];
                selectedGroups.forEach(groupKey => {
                    const card = $(`.card-group-item[data-group="${groupKey}"]`);

                    const waybill = card.find('.group-waybill-input').val();
                    const cost = card.find('.group-cost-input').val();

                    if (!waybill || !cost || parseFloat(cost) <= 0) {
                        incompleteGroups.push(groupKey);
                    }
                });

                if (incompleteGroups.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Incomplete Data',
                        text: `${incompleteGroups.length} group(s) have incomplete Domestic Waybill or Cost. Please fill all required fields.`,
                    });
                    return;
                }

                // Confirm dialog
                const normalCount = selectedGroups.size;
                const shortageCount = selectedShortages.size;

                let confirmHtml = '<div class="text-start">';
                confirmHtml += '<p>You are about to create shipping with:</p>';
                confirmHtml += '<ul>';
                if (normalCount > 0) {
                    confirmHtml += `<li><strong>${normalCount}</strong> normal pre-shipping group(s)</li>`;
                }
                if (shortageCount > 0) {
                    confirmHtml += `<li><strong>${shortageCount}</strong> shortage resend item(s)</li>`;
                }
                confirmHtml += '</ul>';
                confirmHtml += '</div>';

                Swal.fire({
                    title: 'Proceed to Shipping?',
                    html: confirmHtml,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Proceed',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Set hidden input values
                        $('#selected-group-keys').val(JSON.stringify(Array.from(selectedGroups)));
                        $('#selected-shortage-ids').val(JSON.stringify(Array.from(selectedShortages)));

                        const form = document.getElementById('proceed-shipping-form');
                        form.method = 'GET';
                        form.action = "{{ route('shippings.create') }}";
                        form.submit();
                    }
                });
            }

            // ===== ALLOCATION METHOD HANDLERS =====
            function handleAllocationMethodChange() {
                const groupKey = $(this).data('group');
                const method = $(this).val();
                const previousValue = $(this).data('previous-value') || 'value';

                $(this).data('previous-value', method).prop('disabled', true);

                // Calculate dengan method baru
                calculateAllocatedCostRealtime(groupKey, method);

                const data = {
                    domestic_waybill_no: $(`.group-waybill-input[data-group="${groupKey}"]`).val(),
                    domestic_cost: $(`.group-cost-input[data-group="${groupKey}"]`).val(),
                    cost_allocation_method: method,
                    _token: '{{ csrf_token() }}'
                };

                if (method === 'percentage') {
                    const percentages = [];
                    $(`.item-tbody[data-group="${groupKey}"] .percentage-input`).each(function() {
                        percentages.push(parseFloat($(this).val()) || 0);
                    });
                    data.percentages = percentages;
                }

                sendUpdateRequest(groupKey, data, previousValue);
            }

            function handleInputChange() {
                if (isInitializing) return;

                // ⭐ FIX: Get group key dari closest TR dengan data-group
                const $row = $(this).closest('tr[data-group]');
                const groupKey = $row.data('group');

                if (!groupKey) {
                    console.error('⚠️ Group key not found for input change');
                    return;
                }

                clearTimeout(updateTimeout[groupKey]);

                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroup(groupKey);
                }, 1000);
            }

            function handlePercentageInput() {
                if (isInitializing) return;

                // ⭐ FIX: Get group key dari closest TR
                const $row = $(this).closest('tr[data-group]');
                const groupKey = $row.data('group');

                if (!groupKey) {
                    console.error('⚠️ Group key not found for percentage input');
                    return;
                }

                const method = $(`.allocation-method-select[data-group="${groupKey}"]`).val();

                if (method === 'percentage') {
                    updatePercentageTotal(groupKey);
                    calculateAllocatedCostRealtime(groupKey, 'percentage');

                    clearTimeout(updateTimeout[groupKey]);
                    updateTimeout[groupKey] = setTimeout(() => {
                        autoUpdateGroup(groupKey);
                    }, 1500);
                }
            }

            function handleDomesticCostInput() {
                if (isInitializing) return;

                const groupKey = $(this).data('group');

                if (!groupKey) {
                    console.error('⚠️ Group key not found for domestic cost input');
                    return;
                }

                const method = $(`.allocation-method-select[data-group="${groupKey}"]`).val();

                calculateAllocatedCostRealtime(groupKey, method);

                clearTimeout(updateTimeout[groupKey]);
                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroup(groupKey);
                }, 1000);
            }

            function handleAutoDistribute() {
                const groupKey = $(this).data('group');

                let totalValue = 0;
                $(`.item-tbody[data-group="${groupKey}"] tr`).each(function() {
                    const value = parseFloat($(this).data('value')) || 0;
                    totalValue += value;
                });

                if (totalValue <= 0) {
                    const equalPercentage = (100 / $(`.item-tbody[data-group="${groupKey}"] tr`).length).toFixed(2);
                    $(`.item-tbody[data-group="${groupKey}"] .percentage-input`).val(equalPercentage);
                } else {
                    $(`.item-tbody[data-group="${groupKey}"] tr`).each(function() {
                        const value = parseFloat($(this).data('value')) || 0;
                        const percentage = ((value / totalValue) * 100).toFixed(2);
                        $(this).find('.percentage-input').val(percentage);
                    });
                }

                calculateAllocatedCostRealtime(groupKey, 'percentage');
            }

            // ===== SHORTAGE ACTION HANDLERS =====

            function handleBulkResendShortage() {
                const selectedIds = [];
                const oldWaybills = {};

                $('.shortage-checkbox:checked').each(function() {
                    const shortageId = $(this).val();
                    selectedIds.push(shortageId);
                    oldWaybills[shortageId] = $(`.old-wbl-input[data-shortage-id="${shortageId}"]`).val() ||
                        '';
                });

                if (selectedIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one shortage item to resend'
                    });
                    return;
                }

                Swal.fire({
                    icon: 'question',
                    title: 'Confirm Bulk Resend',
                    html: `
                <div class="text-start">
                    <p>You are about to resend <strong>${selectedIds.length}</strong> shortage item(s).</p>
                    <p class="mb-0">This will:</p>
                    <ul class="small">
                        <li>Create new Purchase Request(s) with <code>approval_status = 'Approved'</code></li>
                        <li>Add them to Pre-Shipping groups automatically</li>
                        <li>Update shortage item status to <code>'reshipped'</code></li>
                    </ul>
                </div>
            `,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Resend Now',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        processBulkResend(selectedIds, oldWaybills);
                    }
                });
            }

            function processBulkResend(shortageIds, oldWaybills) {
                const formData = {
                    _token: '{{ csrf_token() }}',
                    shortage_item_ids: shortageIds,
                    old_domestic_wbl: Object.values(oldWaybills)
                };

                Swal.fire({
                    title: 'Processing...',
                    html: 'Creating Purchase Requests and Pre-Shipping entries...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ route('shortage-items.bulk-resend') }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        window.location.href = response.redirect_url;
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to resend shortage items'
                        });
                    }
                });
            }

            function handleCancelShortage() {
                const shortageId = $(this).data('shortage-id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Cancel Shortage Item?',
                    input: 'textarea',
                    inputPlaceholder: 'Optional: Enter reason for cancellation',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'Back',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/shortage-items/${shortageId}/cancel`,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                reason: result.value || null
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Canceled',
                                    text: response.message,
                                    timer: 2000
                                });

                                loadShortageItems();
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message ||
                                        'Failed to cancel'
                                });
                            }
                        });
                    }
                });
            }

            // ===== UTILITY FUNCTIONS =====

            function togglePercentageColumns(groupKey, method) {
                // ⭐ FIX: Select ALL percentage columns (TH + TD) untuk group ini
                const $percentageColumnTH = $(`.percentage-column[data-group="${groupKey}"]`).filter('th');
                const $percentageColumnTD = $(`.percentage-column[data-group="${groupKey}"]`).filter('td');
                const $percentageValidation = $(`.percentage-validation[data-group="${groupKey}"]`);

                console.log('🔄 Toggle Percentage Columns', {
                    groupKey: groupKey,
                    method: method,
                    thFound: $percentageColumnTH.length,
                    tdFound: $percentageColumnTD.length,
                    validationFound: $percentageValidation.length
                });

                if (method === 'percentage') {
                    // ⭐ SHOW: Remove d-none class first, then animate
                    $percentageColumnTH.removeClass('d-none').hide().slideDown(300);
                    $percentageColumnTD.removeClass('d-none').hide().slideDown(300);
                    $percentageValidation.removeClass('d-none').hide().slideDown(300);

                    console.log('✅ Percentage columns shown');
                } else {
                    // ⭐ HIDE: Animate first, then add d-none class after animation complete
                    $percentageColumnTH.slideUp(300, function() {
                        $(this).addClass('d-none');
                    });

                    $percentageColumnTD.slideUp(300, function() {
                        $(this).addClass('d-none');
                    });

                    $percentageValidation.slideUp(300, function() {
                        $(this).addClass('d-none');
                    });

                    console.log('✅ Percentage columns hidden');
                }
            }

            function updatePercentageTotal(groupKey) {
                let total = 0;
                $(`.item-tbody[data-group="${groupKey}"] .percentage-input`).each(function() {
                    total += parseFloat($(this).val()) || 0;
                });

                const $validation = $(`.percentage-validation[data-group="${groupKey}"]`);
                const $totalSpan = $validation.find('.total-percentage');
                const $alert = $validation.find('.alert');

                $totalSpan.text(total.toFixed(2));

                $validation.removeClass('valid invalid');

                if (Math.abs(total - 100) <= 1) {
                    $validation.addClass('valid');
                    $alert.removeClass('alert-warning alert-danger alert-info').addClass('alert-success');
                } else if (total > 105) {
                    $validation.addClass('invalid');
                    $alert.removeClass('alert-success alert-info alert-warning').addClass('alert-danger');
                } else {
                    $validation.addClass('invalid');
                    $alert.removeClass('alert-success alert-danger alert-info').addClass('alert-warning');
                }
            }

            function updateMethodBadge(groupKey, method) {
                const $badge = $(`.cost-method-badge[data-group="${groupKey}"]`);

                $badge.removeClass('bg-primary bg-success bg-info')
                    .addClass(method === 'percentage' ? 'bg-primary' :
                        method === 'quantity' ? 'bg-success' : 'bg-info')
                    .text(method.charAt(0).toUpperCase() + method.slice(1));
            }

            function autoUpdateGroup(groupKey) {
                const data = {
                    domestic_waybill_no: $(`.group-waybill-input[data-group="${groupKey}"]`).val(),
                    domestic_cost: $(`.group-cost-input[data-group="${groupKey}"]`).val(),
                    cost_allocation_method: $(`.allocation-method-select[data-group="${groupKey}"]`).val(),
                    _token: '{{ csrf_token() }}'
                };

                if (data.cost_allocation_method === 'percentage') {
                    const percentages = [];
                    $(`.item-tbody[data-group="${groupKey}"] .percentage-input`).each(function() {
                        percentages.push(parseFloat($(this).val()) || 0);
                    });
                    data.percentages = percentages;
                }

                sendUpdateRequest(groupKey, data);
            }

            function sendUpdateRequest(groupKey, data, previousValue) {
                if (!isOnline) {
                    showToast('error', 'Cannot save: No internet connection');
                    return;
                }

                const $indicator = $(`.auto-save-indicator[data-group="${groupKey}"]`);
                $indicator.removeClass('saved').addClass('saving').css('opacity', '1');
                hasUnsavedChanges = true;

                $.ajax({
                    url: `/pre-shippings/${groupKey}/quick-update`,
                    method: 'POST',
                    data: data,
                    timeout: 10000,
                    success: function(response) {
                        handleUpdateSuccess(response, groupKey);
                    },
                    error: function(xhr, status, error) {
                        handleUpdateError(xhr, status, error, groupKey, previousValue);
                    },
                    complete: function() {
                        setTimeout(() => {
                            $indicator.removeClass('saving').addClass('saved');
                            setTimeout(() => {
                                $indicator.css('opacity', '0');
                            }, 2000);
                        }, 500);
                    }
                });
            }

            function handleUpdateSuccess(response, groupKey) {
                hasUnsavedChanges = false;

                if (response.updated_items) {
                    response.updated_items.forEach(item => {
                        const $row = $(`.item-tbody[data-group="${groupKey}"] tr`).eq(item.index);
                        $row.find('.allocated-cost-display').text(formatDynamicNumber(item.allocated_cost));

                        if (item.allocation_percentage !== null) {
                            $row.find('.percentage-input').val(parseFloat(item.allocation_percentage)
                                .toFixed(2));
                        }
                    });
                }

                const $select = $(`.allocation-method-select[data-group="${groupKey}"]`);
                $select.prop('disabled', false);

                // ⭐ FIX: Update method badge
                const newMethod = response.updated_items[0]?.cost_allocation_method || 'value';
                updateMethodBadge(groupKey, newMethod);

                // ⭐ FIX: Toggle percentage columns AFTER AJAX success
                togglePercentageColumns(groupKey, newMethod);

                // ⭐ FIX: Update percentage total jika method = percentage
                if (newMethod === 'percentage') {
                    updatePercentageTotal(groupKey);
                }
            }

            function handleUpdateError(xhr, status, error, groupKey, previousValue) {
                hasUnsavedChanges = false;

                if (status === 'timeout') {
                    showToast('error', 'Request timeout. Please try again.');
                } else if (xhr.status === 403) {
                    showToast('error', 'Permission denied.');
                } else {
                    showToast('error', xhr.responseJSON?.message || 'Update failed. Please try again.');
                }

                if (previousValue) {
                    const $select = $(`.allocation-method-select[data-group="${groupKey}"]`);
                    $select.val(previousValue).prop('disabled', false);
                }
            }

            function showToast(type, message) {
                const bgColor = type === 'success' ? '#28a745' : '#dc3545';
                const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

                const toast = $(`
            <div class="toast-custom" style="
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideInRight 0.3s ease-out;
            ">
                <i class="bi bi-${icon}"></i>
                <span>${message}</span>
            </div>
        `);

                $('body').append(toast);

                setTimeout(() => {
                    toast.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }

            // ⭐ REMOVED: checkOrphanedPRs() - tidak digunakan lagi karena sudah auto-generate
        });
    </script>
@endpush
