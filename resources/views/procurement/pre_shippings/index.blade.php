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
                                            <th
                                                class="percentage-column {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}">
                                                Allocation %
                                            </th>
                                            <th>Allocated Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody data-group="{{ $group['group_key'] }}">
                                        @foreach ($group['items'] as $index => $item)
                                            <tr data-item-id="{{ $item->id }}" data-index="{{ $index }}">
                                                <td>
                                                    <strong
                                                        class="text-dark">{{ $item->purchaseRequest->material_name }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $item->purchaseRequest->project->name ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-primary" data-bs-toggle="tooltip"
                                                        data-bs-placement="right"
                                                        title="{{ $item->purchaseRequest->unit }}">
                                                        {{ rtrim(rtrim(number_format($item->purchaseRequest->qty_to_buy ?? $item->purchaseRequest->required_quantity, 3, '.', ''), '0'), '.') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-success" data-bs-toggle="tooltip"
                                                        data-bs-placement="left"
                                                        title="{{ $item->purchaseRequest->currency->name ?? '-' }}">
                                                        {{ number_format($item->purchaseRequest->price_per_unit, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $itemValue =
                                                            ($item->purchaseRequest->qty_to_buy ??
                                                                $item->purchaseRequest->required_quantity) *
                                                            $item->purchaseRequest->price_per_unit;
                                                        $currencyName = $item->purchaseRequest->currency->name ?? '-';
                                                    @endphp
                                                    <span class="fw-bold text-success" data-bs-toggle="tooltip"
                                                        data-bs-placement="left" title="{{ $currencyName }}">
                                                        {{ number_format($itemValue, 2) }}
                                                    </span>
                                                </td>
                                                <td
                                                    class="percentage-column {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}">
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
                                                <td>
                                                    <div class="allocated-cost-cell allocated-cost-highlight">
                                                        <span class="allocated-amount" data-bs-toggle="tooltip"
                                                            data-bs-placement="left" title="{{ $currencyName }}">
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
                                                {{ $group['has_been_shipped'] ? '(Locked - Already Shipped)' : '(Should equal 100%)' }}
                                            </small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary auto-distribute-btn"
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
    </div>

    <!-- Form untuk proceed to shipping -->
    <form id="proceed-shipping-form" action="{{ route('shippings.create') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="group_keys" id="selected-group-keys">
    </form>
@endsection

@push('scripts')
    <script>
        $(function() {
            // ===== FILTER PILLS LOGIC =====
            let currentFilter = 'not-shipped';
            const filterPillsContainer = $('#filter-pills-container');
            const groupsContainer = $('#groups-container');

            // Inisialisasi: Set data attribute pada container
            groupsContainer.attr('data-current-filter', 'not-shipped');

            // Terapkan filter default saat page load
            applyFilterPills('not-shipped'); // Apply filter "Not Shipped" default

            // Event handler untuk filter pills
            $(document).on('click', '.filter-pill', function(e) {
                e.preventDefault();

                const filterValue = $(this).data('filter');
                const $button = $(this);

                // Skip jika sudah active
                if ($button.hasClass('active')) {
                    return;
                }

                // Update active state
                $('.filter-pill').removeClass('active');
                $button.addClass('active');

                // Update current filter
                currentFilter = filterValue;
                groupsContainer.attr('data-current-filter', filterValue);

                // Apply filter
                applyFilterPills(filterValue);

                console.log('Filter applied:', filterValue);
            });

            /**
             * Apply filter dengan smooth fade animation
             */
            function applyFilterPills(filterValue) {
                const groups = $('.card-group-item');
                let visibleCount = 0;

                groups.each(function() {
                    const $group = $(this);
                    const filterGroup = $group.data('filter-group');
                    let shouldShow = false;

                    if (filterValue === 'all') {
                        shouldShow = true;
                    } else if (filterValue === 'not-shipped' && filterGroup === 'not-shipped') {
                        shouldShow = true;
                    } else if (filterValue === 'shipped' && filterGroup === 'shipped') {
                        shouldShow = true;
                    }

                    if (shouldShow) {
                        $group.stop(true, false).fadeIn(300);
                        visibleCount++;
                    } else {
                        $group.stop(true, false).fadeOut(300);
                    }
                });

                // Update empty state setelah animasi
                setTimeout(() => {
                    if (visibleCount === 0) {
                        showEmptyState(filterValue);
                    } else {
                        hideEmptyState();
                    }
                }, 300);
            }

            /**
             * Tampilkan empty state
             */
            function showEmptyState(filterValue) {
                $('.empty-state-filter').remove();

                const messages = {
                    'all': {
                        icon: 'fa-inbox',
                        title: 'No pre-shipping groups available'
                    },
                    'not-shipped': {
                        icon: 'fa-paper-plane',
                        title: 'No items waiting to ship'
                    },
                    'shipped': {
                        icon: 'fa-check-circle',
                        title: 'No shipped items yet'
                    }
                };

                const config = messages[filterValue] || messages['all'];
                const emptyHtml = `
                    <div class="empty-state-filter">
                        <i class="fas ${config.icon}"></i>
                        <h5>${config.title}</h5>
                    </div>
                `;

                groupsContainer.append(emptyHtml);
            }

            /**
             * Hide empty state
             */
            function hideEmptyState() {
                $('.empty-state-filter').fadeOut(200, function() {
                    $(this).remove();
                });
            }
        });

        // 1. FUNCTION HELPERS
        function formatDynamicNumber(number) {
            if (number == null || number === '') return '0';
            const num = parseFloat(number);
            if (isNaN(num)) return number;
            return num.toFixed(2).replace(/\.?0+$/, '');
        }

        function formatCurrency(number) {
            const formatted = formatDynamicNumber(number);
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 3
            }).format(parseFloat(formatted));
        }

        // Real-time calculation functions
        function calculateAllocatedCostRealtime(groupKey, method) {
            const $tbody = $(`tbody[data-group="${groupKey}"]`);
            const domesticCost = parseFloat($(`.group-cost-input[data-group="${groupKey}"]`).val()) || 0;

            if (domesticCost <= 0) {
                // If no domestic cost, set all allocated costs to 0
                $tbody.find('.allocated-amount').text('0');
                return;
            }

            if (method === 'percentage') {
                calculateByPercentageRealtime($tbody, domesticCost);
            } else if (method === 'quantity') {
                calculateByQuantityRealtime($tbody, domesticCost);
            } else if (method === 'value') {
                calculateByValueRealtime($tbody, domesticCost);
            }
        }

        function calculateByPercentageRealtime($tbody, totalCost) {
            $tbody.find('tr').each(function() {
                const $row = $(this);
                const $percentageInput = $row.find('.percentage-input');
                const $allocatedAmount = $row.find('.allocated-amount');

                const percentage = parseFloat($percentageInput.val()) || 0;
                const allocatedCost = (percentage / 100) * totalCost;

                $allocatedAmount.text(formatDynamicNumber(allocatedCost));
            });
        }

        function calculateByQuantityRealtime($tbody, totalCost) {
            // Calculate total quantity first
            let totalQuantity = 0;
            const quantities = [];

            $tbody.find('tr').each(function() {
                const $row = $(this);
                const qtyText = $row.find('td:eq(2) .fw-semibold').text().replace(/,/g, '');
                const qty = parseFloat(qtyText) || 0;
                quantities.push(qty);
                totalQuantity += qty;
            });

            if (totalQuantity <= 0) {
                $tbody.find('.allocated-amount').text('0');
                return;
            }

            // Distribute cost based on quantity ratio
            $tbody.find('tr').each(function(index) {
                const $row = $(this);
                const $allocatedAmount = $row.find('.allocated-amount');
                const qty = quantities[index];
                const allocatedCost = (qty / totalQuantity) * totalCost;

                $allocatedAmount.text(formatDynamicNumber(allocatedCost));
            });
        }

        function calculateByValueRealtime($tbody, totalCost) {
            let totalValue = 0;
            const values = [];

            $tbody.find('tr').each(function() {
                const $row = $(this);
                const qtyText = $row.find('td:eq(2) .fw-semibold').text().replace(/,/g, '');
                const priceText = $row.find('td:eq(3) .fw-semibold').text().replace(/[$,]/g, '');
                const qty = parseFloat(qtyText) || 0;
                const price = parseFloat(priceText) || 0;
                const value = qty * price;
                values.push(value);
                totalValue += value;
            });

            if (totalValue <= 0) {
                $tbody.find('.allocated-amount').text('0');
                return;
            }

            $tbody.find('tr').each(function(index) {
                const $row = $(this);
                const $allocatedAmount = $row.find('.allocated-amount');
                const value = values[index];
                const allocatedCost = (value / totalValue) * totalCost;

                // Debug log untuk memastikan update berjalan
                console.log(`Updating row ${index}: ${allocatedCost}`);

                $allocatedAmount.text(formatDynamicNumber(allocatedCost));
            });
        }

        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        // 2. SINGLE DOCUMENT READY BLOCK
        $(document).ready(function() {
            let updateTimeout = {};
            let selectedGroups = new Set();
            let hasUnsavedChanges = false;
            let isOnline = navigator.onLine;
            let isInitializing = true;

            // ===== INITIALIZATION =====
            initializePage();

            // ===== EVENT HANDLERS =====

            // Group checkbox selection
            $('.group-checkbox').on('change', handleGroupSelection);

            // Proceed to shipping
            $('#proceed-shipping-btn').on('click', handleProceedToShipping);

            // Allocation method change
            $('.allocation-method-select').on('change', handleAllocationMethodChange);

            // Input changes dengan real-time calculation
            $('.allocation-input').on('input', handleInputChange);

            // Percentage input changes dengan real-time calculation
            $(document).on('input', '.percentage-input', handlePercentageInput);

            // Domestic cost input changes dengan real-time calculation
            $(document).on('input', '.group-cost-input', handleDomesticCostInput);

            // Auto-distribute button
            $(document).on('click', '.auto-distribute-btn', handleAutoDistribute);

            // Visual feedback for inputs
            $('.allocation-input').on('focus', function() {
                $(this).addClass('border-primary');
            }).on('blur', function() {
                $(this).removeClass('border-primary');
            });

            // Clear validation error saat user mulai typing
            $(document).on('input', '.group-waybill-input, .group-cost-input', function() {
                const $input = $(this);
                const value = $input.val();
                const groupKey = $input.data('group');

                // Clear error styling jika user mulai input
                if (value && value.trim() !== '') {
                    // Untuk waybill input
                    if ($input.hasClass('group-waybill-input')) {
                        if (value.trim().length > 0) {
                            $input.removeClass('border-danger is-invalid');
                        }
                    }
                    // Untuk cost input
                    else if ($input.hasClass('group-cost-input')) {
                        if (parseFloat(value) > 0) {
                            $input.removeClass('border-danger is-invalid');
                        }
                    }
                }
            });

            // Connection status monitoring
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

            // ===== FUNCTION DEFINITIONS =====

            function initializePage() {
                isInitializing = true;

                $('.allocation-method-select').each(function() {
                    const currentValue = $(this).val() || 'value';
                    $(this).data('previous-value', currentValue);

                    const groupKey = $(this).data('group');
                    updateMethodBadge(groupKey, currentValue);

                    // Initialize real-time calculation for each group
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

            function handleProceedToShipping() {
                if (selectedGroups.size === 0) {
                    Swal.fire('Warning', 'Please select at least one group', 'warning');
                    return;
                }

                // VALIDASI CLIENT-SIDE: Cek apakah semua group punya domestic_waybill_no dan domestic_cost
                let incompleteGroups = [];
                let hasInvalidWaybill = false;
                let hasInvalidCost = false;
                let firstInvalidElement = null; // Track first invalid element

                selectedGroups.forEach(groupKey => {
                    const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                    const waybill = $card.find(`.group-waybill-input[data-group="${groupKey}"]`).val();
                    const cost = $card.find(`.group-cost-input[data-group="${groupKey}"]`).val();

                    // VISUAL FEEDBACK: Add invalid class ke input yang kosong/invalid
                    const $waybillInput = $card.find(`.group-waybill-input[data-group="${groupKey}"]`);
                    const $costInput = $card.find(`.group-cost-input[data-group="${groupKey}"]`);

                    if (!waybill || waybill.trim() === '') {
                        incompleteGroups.push(groupKey + ' (missing Domestic Waybill No)');
                        $waybillInput.addClass('border-danger is-invalid');
                        hasInvalidWaybill = true;

                        // Track first invalid element
                        if (!firstInvalidElement) {
                            firstInvalidElement = $waybillInput;
                        }
                    } else {
                        $waybillInput.removeClass('border-danger is-invalid');
                    }

                    if (!cost || parseFloat(cost) <= 0) {
                        incompleteGroups.push(groupKey + ' (missing/invalid Domestic Cost)');
                        $costInput.addClass('border-danger is-invalid');
                        hasInvalidCost = true;

                        // Track first invalid element
                        if (!firstInvalidElement) {
                            firstInvalidElement = $costInput;
                        }
                    } else {
                        $costInput.removeClass('border-danger is-invalid');
                    }
                });

                // Jika ada yang incomplete, tampilkan error dengan scroll ke invalid fields
                if (incompleteGroups.length > 0) {
                    // Save current scroll position sebelum modal muncul
                    const currentScrollTop = $(window).scrollTop();

                    Swal.fire({
                        icon: 'error',
                        title: 'Incomplete Data',
                        html: '<div style="text-align: left;">' +
                            'Cannot proceed to shipping. Please fill in the following fields:<br><br>' +
                            '<strong style="color: #dc3545;">❌ Missing/Invalid fields:</strong><br>' +
                            incompleteGroups.map(g => '• ' + g).join('<br>') +
                            '<br><br><strong style="color: #0d6efd;">ℹ️ Required:</strong><br>' +
                            '• Domestic Waybill No (cannot be empty)<br>' +
                            '• Domestic Cost (must be greater than 0)' +
                            '</div>',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545',
                        allowOutsideClick: false, // Prevent closing by clicking outside
                        allowEscapeKey: false, // Prevent closing by pressing Escape
                        didOpen: function(modal) {
                            // Set focus ke first invalid input untuk better UX
                            if (firstInvalidElement && firstInvalidElement.length) {
                                setTimeout(() => {
                                    firstInvalidElement.focus();
                                }, 300);
                            }
                        }
                    }).then((result) => {
                        // Restore scroll position setelah modal ditutup
                        if (result.isConfirmed) {
                            // Scroll ke first invalid field dengan smooth animation
                            if (firstInvalidElement && firstInvalidElement.length) {
                                $('html, body').animate({
                                    scrollTop: firstInvalidElement.offset().top - 100
                                }, 500, function() {
                                    // Set focus ke input setelah scroll selesai
                                    firstInvalidElement.focus();
                                    // Add highlight effect
                                    firstInvalidElement.addClass('highlight-invalid');
                                    setTimeout(() => {
                                        firstInvalidElement.removeClass(
                                            'highlight-invalid');
                                    }, 2000);
                                });
                            } else {
                                // Fallback: Restore original scroll position
                                $(window).scrollTop(currentScrollTop);
                            }
                        }
                    });

                    return;
                }

                // Jika valid, clear visual feedback dan lanjutkan proceed
                selectedGroups.forEach(groupKey => {
                    const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                    $card.find('.group-waybill-input, .group-cost-input').removeClass(
                        'border-danger is-invalid');
                });

                // Jika valid, lanjutkan proceed
                Swal.fire({
                    icon: 'info',
                    title: 'Proceed to Shipping?',
                    text: `You are about to proceed ${selectedGroups.size} group(s) to shipping.`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Proceed',
                    confirmButtonColor: '#198754',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#selected-group-keys').val(JSON.stringify([...selectedGroups]));
                        $('#proceed-shipping-form').submit();
                    }
                });
            }

            function handleAllocationMethodChange() {
                const groupKey = $(this).data('group');
                const method = $(this).val();
                const previousValue = $(this).data('previous-value') || 'value';

                $(this).data('previous-value', method).prop('disabled', true);

                togglePercentageColumns(groupKey, method);

                // Pastikan ini dipanggil SEBELUM AJAX request
                calculateAllocatedCostRealtime(groupKey, method);

                const data = {
                    domestic_waybill_no: $(`.group-waybill-input[data-group="${groupKey}"]`).val(),
                    domestic_cost: $(`.group-cost-input[data-group="${groupKey}"]`).val(),
                    cost_allocation_method: method,
                    percentages: method === 'percentage' ? [] : undefined,
                    _token: '{{ csrf_token() }}'
                };

                sendUpdateRequest(groupKey, data, previousValue);
            }

            function handleInputChange() {
                const groupKey = $(this).data('group');
                const $indicator = $(this).siblings('.auto-save-indicator');

                $indicator.removeClass('saved').addClass('saving');
                hasUnsavedChanges = true;

                clearTimeout(updateTimeout[groupKey]);
                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroup(groupKey);
                }, 800);
            }

            function handlePercentageInput() {
                const groupKey = $(this).data('group');
                const currentMethod = $(`.allocation-method-select[data-group="${groupKey}"]`).val();

                // Real-time calculation saat input percentage berubah
                if (currentMethod === 'percentage') {
                    calculateAllocatedCostRealtime(groupKey, 'percentage');
                }

                updatePercentageTotal(groupKey);

                const $indicator = $(this).siblings('.auto-save-indicator');
                $indicator.removeClass('saved').addClass('saving');
                hasUnsavedChanges = true;

                clearTimeout(updateTimeout[groupKey]);
                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroupWithPercentages(groupKey);
                }, 1500);
            }

            // Handle domestic cost input changes
            function handleDomesticCostInput() {
                const groupKey = $(this).data('group');
                const currentMethod = $(`.allocation-method-select[data-group="${groupKey}"]`).val();

                // Real-time calculation saat domestic cost berubah
                calculateAllocatedCostRealtime(groupKey, currentMethod);

                const $indicator = $(this).siblings('.auto-save-indicator');
                $indicator.removeClass('saved').addClass('saving');
                hasUnsavedChanges = true;

                clearTimeout(updateTimeout[groupKey]);
                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroup(groupKey);
                }, 800);
            }

            function handleAutoDistribute() {
                const groupKey = $(this).data('group');
                const $tbody = $(`tbody[data-group="${groupKey}"]`);

                let totalValue = 0;
                const itemValues = [];

                $tbody.find('tr').each(function() {
                    const qtyText = $(this).find('td:eq(2) .fw-semibold').text().replace(/,/g, '');
                    const priceText = $(this).find('td:eq(3) .fw-semibold').text().replace(/[$,]/g, '');
                    const value = (parseFloat(qtyText) || 0) * (parseFloat(priceText) || 0);
                    itemValues.push(value);
                    totalValue += value;
                });

                if (totalValue > 0) {
                    $tbody.find('.percentage-input').each(function(index) {
                        const percentage = (itemValues[index] / totalValue) * 100;
                        $(this).val(formatDynamicNumber(percentage));
                    });

                    // Trigger real-time calculation after auto-distribute
                    calculateAllocatedCostRealtime(groupKey, 'percentage');
                    updatePercentageTotal(groupKey);

                    setTimeout(() => autoUpdateGroupWithPercentages(groupKey), 500);
                    showToast('success', 'Percentages auto-distributed based on item values');
                } else {
                    showToast('warning', 'Cannot auto-distribute: no item values found');
                }
            }

            function updateProceedButton() {
                const count = selectedGroups.size;
                const $countElement = $('#selected-count');
                const $proceedBtn = $('#proceed-shipping-btn');
                const $countText = $('#count-text');

                if (count > 0) {
                    $countElement.show();
                    $proceedBtn.show().prop('disabled', false);
                    $countText.text(`${count} selected`);
                } else {
                    $countElement.hide();
                    $proceedBtn.hide().prop('disabled', true);
                }
            }

            function togglePercentageColumns(groupKey, method) {
                const $tbody = $(`tbody[data-group="${groupKey}"]`);
                const $validation = $(`.percentage-validation[data-group="${groupKey}"]`);
                const $table = $tbody.closest('table');

                if (method === 'percentage') {
                    $table.find('.percentage-column').removeClass('d-none');
                    $validation.removeClass('d-none');
                    updatePercentageTotal(groupKey);
                } else {
                    $table.find('.percentage-column').addClass('d-none');
                    $validation.addClass('d-none');
                }

                updateMethodBadge(groupKey, method);
            }

            function updateMethodBadge(groupKey, method) {
                const $badge = $(`.card-group-item[data-group="${groupKey}"] .cost-method-badge`);
                $badge.text(method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' '));
            }

            function sendUpdateRequest(groupKey, data, previousValue) {
                const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                const $spinner = $card.find('.loading-spinner');
                $spinner.show();

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
                        $(`.allocation-method-select[data-group="${groupKey}"]`).prop('disabled',
                            false);
                        $spinner.hide();
                    }
                });
            }

            function handleUpdateSuccess(response, groupKey) {
                const $card = $(`.card-group-item[data-group="${groupKey}"]`);

                if (response.success) {
                    $card.find('.auto-save-indicator').removeClass('saving').addClass('saved');
                    hasUnsavedChanges = false;

                    // **PERBAIKAN**: Update allocated amounts dengan response dari server
                    if (response.updated_items) {
                        updateAllocatedAmounts(response.updated_items);
                    }

                    if (response.auto_percentages) {
                        updatePercentageInputs(groupKey, response.auto_percentages);
                        // Trigger real-time calculation setelah update percentages
                        setTimeout(() => {
                            calculateAllocatedCostRealtime(groupKey, 'percentage');
                        }, 100);
                    }

                    showToast('success', 'Updated successfully');
                } else {
                    showToast(response.warning ? 'warning' : 'error', response.message);
                }
            }

            function handleUpdateError(xhr, status, error, groupKey, previousValue) {
                let errorMessage = 'Failed to save';

                if (status === 'timeout') errorMessage = 'Request timeout. Please try again.';
                else if (xhr.status === 422) errorMessage = xhr.responseJSON?.message ||
                    'Validation error occurred.';
                else if (xhr.status >= 500) errorMessage = 'Server error. Please try again later.';
                else if (xhr.status === 0) errorMessage = 'Network connection error.';

                showToast('error', errorMessage);

                // Rollback changes
                if (previousValue) {
                    $(`.allocation-method-select[data-group="${groupKey}"]`).val(previousValue);
                    togglePercentageColumns(groupKey, previousValue);
                    // Recalculate dengan previous method
                    calculateAllocatedCostRealtime(groupKey, previousValue);
                }
            }

            function updatePercentageTotal(groupKey) {
                const $tbody = $(`tbody[data-group="${groupKey}"]`);
                const $validation = $(`.percentage-validation[data-group="${groupKey}"]`);
                let total = 0;
                let hasValues = false;

                $tbody.find('.percentage-input').each(function() {
                    const value = parseFloat($(this).val()) || 0;
                    if (value > 0) hasValues = true;
                    total += value;
                });

                $validation.find('.total-percentage').text(formatDynamicNumber(total));
                updateValidationState($validation, total, hasValues);
            }

            function updateValidationState($validation, total, hasValues) {
                const $alert = $validation.find('.alert');
                $validation.removeClass('valid invalid');

                if (!hasValues) {
                    $alert.removeClass('alert-success alert-warning alert-danger').addClass('alert-info');
                } else if (Math.abs(total - 100) <= 1) {
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

            function autoUpdateGroup(groupKey) {
                const data = {
                    domestic_waybill_no: $(`.group-waybill-input[data-group="${groupKey}"]`).val(),
                    domestic_cost: $(`.group-cost-input[data-group="${groupKey}"]`).val(),
                    cost_allocation_method: $(`.allocation-method-select[data-group="${groupKey}"]`).val(),
                    _token: '{{ csrf_token() }}'
                };

                sendSimpleUpdateRequest(groupKey, data, 'Group updated successfully');
            }

            function autoUpdateGroupWithPercentages(groupKey) {
                const data = {
                    domestic_waybill_no: $(`.group-waybill-input[data-group="${groupKey}"]`).val(),
                    domestic_cost: $(`.group-cost-input[data-group="${groupKey}"]`).val(),
                    cost_allocation_method: $(`.allocation-method-select[data-group="${groupKey}"]`).val(),
                    _token: '{{ csrf_token() }}'
                };

                if (data.cost_allocation_method === 'percentage') {
                    data.percentages = [];
                    $(`tbody[data-group="${groupKey}"] .percentage-input`).each(function() {
                        data.percentages.push(parseFloat($(this).val()) || 0);
                    });

                    const total = data.percentages.reduce((sum, val) => sum + val, 0);
                    if (Math.abs(total - 100) > 0.1) return; // Don't send if not close to 100%
                }

                sendSimpleUpdateRequest(groupKey, data, 'Percentages updated successfully');
            }

            function sendSimpleUpdateRequest(groupKey, data, successMessage) {
                const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                const $spinner = $card.find('.loading-spinner');
                $spinner.show();

                $.post(`/pre-shippings/${groupKey}/quick-update`, data)
                    .done(function(response) {
                        if (response.success) {
                            $card.find('.auto-save-indicator').removeClass('saving').addClass('saved');
                            hasUnsavedChanges = false;

                            // Update allocated amounts dari response
                            if (response.updated_items) {
                                updateAllocatedAmounts(response.updated_items);
                            }

                            showToast('success', successMessage);
                        } else {
                            showToast('error', response.message || 'Failed to update');
                        }
                    })
                    .fail(function(xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed to update');
                    })
                    .always(function() {
                        $spinner.hide();
                    });
            }

            function updateAllocatedAmounts(updatedItems) {
                updatedItems.forEach(function(item) {
                    // Find allocated amount by matching data attributes
                    const $allocatedSpan = $(`.allocated-amount`).filter(function() {
                        const $row = $(this).closest('tr');
                        // You might need to add data-item-id to the rows for better matching
                        return $row.find(`[data-item-id="${item.id}"]`).length > 0;
                    });

                    // If exact match not found, use index-based matching
                    if ($allocatedSpan.length === 0) {
                        const $allAllocatedSpans = $(`.allocated-amount`);
                        if ($allAllocatedSpans.length > 0) {
                            // Update all allocated amounts from the response
                            updatedItems.forEach(function(responseItem, index) {
                                if ($allAllocatedSpans.eq(index).length) {
                                    $allAllocatedSpans.eq(index).text(formatDynamicNumber(
                                        responseItem.allocated_cost));
                                }
                            });
                        }
                    } else {
                        $allocatedSpan.text(formatDynamicNumber(item.allocated_cost));
                    }
                });
            }

            function updatePercentageInputs(groupKey, autoPercentages) {
                const $tbody = $(`tbody[data-group="${groupKey}"]`);
                $tbody.find('.percentage-input').each(function(index) {
                    if (autoPercentages[index] !== undefined) {
                        $(this).val(formatDynamicNumber(autoPercentages[index]));
                    }
                });
                updatePercentageTotal(groupKey);
            }

            function showToast(type, message) {
                // Remove existing toasts to prevent spam
                $(`.toast`).remove();

                const bgClass = type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger';
                const iconClass = type === 'success' ? 'check-circle' : 'exclamation-triangle';

                const toastHtml = `
                    <div class="toast align-items-center text-white bg-${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-${iconClass} me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `;

                const $toast = $(toastHtml);
                $('body').append($toast);

                const toast = new bootstrap.Toast($toast[0]);
                toast.show();

                $toast.on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }
        });
    </script>
@endpush
