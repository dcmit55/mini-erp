@extends('layouts.app')

@push('styles')
    <style>
        .card-group-item {
            transition: all 0.3s ease;
            border: 2px solid transparent;
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
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4><i class="fas fa-truck me-2"></i>Pre Shipping Management</h4>
                        <small class="text-muted">Grouped by Supplier & Delivery Date</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="selected-count" id="selected-count" style="display: none;">
                            <i class="fas fa-check-circle me-1"></i>
                            <span id="count-text">0 selected</span>
                        </span>
                        <button type="button" class="btn btn-success btn-proceed-shipping" id="proceed-shipping-btn"
                            style="display: none;" disabled>
                            <i class="fas fa-arrow-right me-2"></i>
                            Proceed to Shipping
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @foreach ($groupedPreShippings as $group)
                    <div class="card mb-4 border-primary card-group-item" data-group="{{ $group['group_key'] }}">
                        <!-- Group Header with Checkbox -->
                        <div class="card-header group-header">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="checkbox-container">
                                        <input type="checkbox" class="form-check-input custom-checkbox group-checkbox"
                                            id="group-{{ $group['group_key'] }}" data-group="{{ $group['group_key'] }}">
                                        <label class="form-check-label" for="group-{{ $group['group_key'] }}"></label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <strong>Supplier:</strong> {{ $group['supplier']->name ?? 'N/A' }}
                                </div>
                                <div class="col-md-2">
                                    <strong>Delivery Date:</strong> {{ date('d M Y', strtotime($group['delivery_date'])) }}
                                </div>
                                <div class="col-md-2">
                                    <strong>Items:</strong> {{ $group['total_items'] }}
                                </div>
                                <div class="col-md-2">
                                    <strong>Total Qty:</strong> {{ number_format($group['total_quantity'], 2) }}
                                </div>
                                <div class="col-md-2">
                                    <strong>Total Value:</strong> ${{ number_format($group['total_value'], 2) }}
                                </div>
                                <div class="col-auto">
                                    <span class="badge cost-method-badge bg-info">
                                        {{ ucfirst(str_replace('_', ' ', $group['cost_allocation_method'])) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Group Controls -->
                            <div class="row mb-4">
                                <div class="col-md-3 position-relative">
                                    <label class="form-label">Domestic Waybill No</label>
                                    <input type="text" class="form-control allocation-input group-waybill-input"
                                        data-group="{{ $group['group_key'] }}" value="{{ $group['domestic_waybill_no'] }}"
                                        placeholder="Enter waybill number">
                                    <div class="auto-save-indicator"></div>
                                </div>
                                <div class="col-md-2 position-relative">
                                    <label class="form-label">Domestic Cost</label>
                                    <input type="number" class="form-control allocation-input group-cost-input"
                                        data-group="{{ $group['group_key'] }}"
                                        value="{{ rtrim(rtrim(number_format($group['domestic_cost'] ?? 0, 3, '.', ''), '0'), '.') }}"
                                        min="0" step="0.001" placeholder="0">
                                    <div class="auto-save-indicator"></div>
                                </div>
                                <div class="col-md-3 position-relative">
                                    <label class="form-label">Cost Allocation Method</label>
                                    <select class="form-select allocation-input allocation-method-select"
                                        data-group="{{ $group['group_key'] }}">
                                        <option value="quantity"
                                            {{ ($group['cost_allocation_method'] ?? 'value') == 'quantity' ? 'selected' : '' }}>
                                            By Quantity (Auto)
                                        </option>
                                        <option value="percentage"
                                            {{ ($group['cost_allocation_method'] ?? 'value') == 'percentage' ? 'selected' : '' }}>
                                            By Percentage (Manual)
                                        </option>
                                        <option value="value"
                                            {{ ($group['cost_allocation_method'] ?? 'value') == 'value' ? 'selected' : '' }}>
                                            By Value (Auto)
                                        </option>
                                    </select>
                                    <div class="auto-save-indicator"></div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Material Name</th>
                                            <th>Project</th>
                                            <th>Required Qty</th>
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
                                            <tr>
                                                <td>
                                                    <strong>{{ $item->purchaseRequest->material_name }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $item->purchaseRequest->project->name ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-semibold">{{ rtrim(rtrim(number_format($item->purchaseRequest->required_quantity, 3, '.', ''), '0'), '.') }}</span>
                                                    <small
                                                        class="text-muted d-block">{{ $item->purchaseRequest->unit }}</small>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-semibold">${{ rtrim(rtrim(number_format($item->purchaseRequest->price_per_unit, 3, '.', ''), '0'), '.') }}</span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-semibold">${{ rtrim(rtrim(number_format($item->purchaseRequest->required_quantity * $item->purchaseRequest->price_per_unit, 3, '.', ''), '0'), '.') }}</span>
                                                </td>
                                                <td
                                                    class="percentage-column {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}">
                                                    <div class="position-relative">
                                                        <input type="number"
                                                            class="form-control form-control-sm allocation-input percentage-input"
                                                            data-index="{{ $index }}"
                                                            data-group="{{ $group['group_key'] }}"
                                                            value="{{ rtrim(rtrim(number_format($item->allocation_percentage ?? 0, 3, '.', ''), '0'), '.') }}"
                                                            min="0" max="100" step="0.001" placeholder="0">
                                                        <div class="auto-save-indicator"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="allocated-cost-cell allocated-cost-highlight">
                                                        $<span
                                                            class="allocated-amount">{{ rtrim(rtrim(number_format($item->allocated_cost ?? 0, 3, '.', ''), '0'), '.') }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Percentage Total Validation -->
                            <div class="percentage-validation {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}"
                                data-group="{{ $group['group_key'] }}">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fas fa-calculator me-2"></i>
                                            <strong>Total Percentage:
                                                <span class="total-percentage">
                                                    {{ rtrim(rtrim(number_format($group['items']->sum('allocation_percentage'), 3, '.', ''), '0'), '.') }}
                                                </span>%
                                            </strong>
                                            <small class="text-muted ms-3">(Should equal 100%)</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary auto-distribute-btn"
                                            data-group="{{ $group['group_key'] }}">
                                            <i class="fas fa-magic me-1"></i>Auto Distribute
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if ($groupedPreShippings->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                        <h5 class="text-muted">No approved purchase requests ready for pre-shipping</h5>
                        <p class="text-muted">Purchase requests need to have supplier and delivery date assigned.</p>
                    </div>
                @endif
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
        // 1. FUNCTION HELPERS
        function formatDynamicNumber(number) {
            if (number == null || number === '') return '0';
            const num = parseFloat(number);
            if (isNaN(num)) return number;
            return num.toFixed(3).replace(/\.?0+$/, '');
        }

        function formatCurrency(number) {
            const formatted = formatDynamicNumber(number);
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 3
            }).format(parseFloat(formatted));
        }

        // 2. SINGLE DOCUMENT READY BLOCK
        $(document).ready(function() {
            let updateTimeout = {};
            let selectedGroups = new Set();
            let hasUnsavedChanges = false;
            let isOnline = navigator.onLine;

            // ===== INITIALIZATION =====
            initializePage();

            // ===== EVENT HANDLERS =====

            // Group checkbox selection
            $('.group-checkbox').on('change', handleGroupSelection);

            // Proceed to shipping
            $('#proceed-shipping-btn').on('click', handleProceedToShipping);

            // Allocation method change
            $('.allocation-method-select').on('change', handleAllocationMethodChange);

            // Input changes (debounced)
            $('.allocation-input').on('input', handleInputChange);

            // Percentage input changes
            $(document).on('input', '.percentage-input', handlePercentageInput);

            // Auto-distribute button
            $(document).on('click', '.auto-distribute-btn', handleAutoDistribute);

            // Visual feedback for inputs
            $('.allocation-input').on('focus', function() {
                $(this).addClass('border-primary');
            }).on('blur', function() {
                $(this).removeClass('border-primary');
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
                // Initialize previous values for allocation method selects
                $('.allocation-method-select').each(function() {
                    const currentValue = $(this).val() || 'value';
                    $(this).data('previous-value', currentValue);

                    const groupKey = $(this).data('group');
                    updateMethodBadge(groupKey, currentValue);
                });

                // Initialize percentage totals
                $('.percentage-validation').each(function() {
                    const groupKey = $(this).data('group');
                    updatePercentageTotal(groupKey);
                });
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
                $('#selected-group-keys').val(JSON.stringify([...selectedGroups]));
                $('#proceed-shipping-form').submit();
            }

            function handleAllocationMethodChange() {
                const method = $(this).val();
                const groupKey = $(this).data('group');
                const previousValue = $(this).data('previous-value') || 'value';

                $(this).data('previous-value', method).prop('disabled', true);

                togglePercentageColumns(groupKey, method);

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
                updatePercentageTotal(groupKey);

                const $indicator = $(this).siblings('.auto-save-indicator');
                $indicator.removeClass('saved').addClass('saving');
                hasUnsavedChanges = true;

                clearTimeout(updateTimeout[groupKey]);
                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroupWithPercentages(groupKey);
                }, 1500);
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

                    if (response.updated_items) {
                        updateAllocatedAmounts(response.updated_items);
                    }

                    if (response.auto_percentages) {
                        updatePercentageInputs(groupKey, response.auto_percentages);
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
                    const $allocatedSpan = $(`.allocated-amount`).filter(function() {
                        return $(this).closest('tr').find(`[data-item-id="${item.id}"]`).length > 0;
                    });

                    if ($allocatedSpan.length) {
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
