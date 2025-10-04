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
                                        data-group="{{ $group['group_key'] }}" value="{{ $group['domestic_cost'] }}"
                                        min="0" step="0.01" placeholder="0.00">
                                    <div class="auto-save-indicator"></div>
                                </div>
                                <div class="col-md-3 position-relative">
                                    <label class="form-label">Cost Allocation Method</label>
                                    <select class="form-select allocation-input allocation-method-select"
                                        data-group="{{ $group['group_key'] }}">
                                        <option value="quantity"
                                            {{ ($group['cost_allocation_method'] ?? 'quantity') == 'quantity' ? 'selected' : '' }}>
                                            By Quantity (Auto)
                                        </option>
                                        <option value="percentage"
                                            {{ ($group['cost_allocation_method'] ?? 'quantity') == 'percentage' ? 'selected' : '' }}>
                                            By Percentage (Manual)
                                        </option>
                                        <option value="value"
                                            {{ ($group['cost_allocation_method'] ?? 'quantity') == 'value' ? 'selected' : '' }}>
                                            By Value (Auto)
                                        </option>
                                    </select>
                                    <div class="auto-save-indicator"></div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="loading-spinner spinner-border spinner-border-sm text-primary"
                                        role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
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
                                                        {{-- SUDAH EAGER LOADED, tidak ada N+1 query --}}
                                                        {{ $item->purchaseRequest->project->name ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-semibold">{{ number_format($item->purchaseRequest->required_quantity, 2) }}</span>
                                                    <small
                                                        class="text-muted d-block">{{ $item->purchaseRequest->unit }}</small>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-semibold">${{ number_format($item->purchaseRequest->price_per_unit, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-semibold">${{ number_format($item->purchaseRequest->required_quantity * $item->purchaseRequest->price_per_unit, 2) }}</span>
                                                </td>
                                                <td
                                                    class="percentage-column {{ $group['cost_allocation_method'] != 'percentage' ? 'd-none' : '' }}">
                                                    <div class="position-relative">
                                                        <input type="number"
                                                            class="form-control form-control-sm allocation-input percentage-input"
                                                            data-index="{{ $index }}"
                                                            data-group="{{ $group['group_key'] }}"
                                                            value="{{ $item->allocation_percentage ?? 0 }}" min="0"
                                                            max="100" step="0.001" placeholder="0.000">
                                                        <div class="auto-save-indicator"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="allocated-cost-cell allocated-cost-highlight">
                                                        $<span
                                                            class="allocated-amount">{{ number_format($item->allocated_cost ?? 0, 2) }}</span>
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
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calculator me-2"></i>
                                        <strong>Total Percentage:
                                            <span
                                                class="total-percentage">{{ $group['items']->sum('allocation_percentage') }}</span>%
                                        </strong>
                                        <small class="text-muted ms-3">(Must equal 100%)</small>
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
        $(document).ready(function() {
            let updateTimeout = {};
            let selectedGroups = new Set();

            // Handle checkbox selection
            $('.group-checkbox').on('change', function() {
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
            });

            // Update proceed button state
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

            // Handle proceed to shipping
            $('#proceed-shipping-btn').on('click', function() {
                if (selectedGroups.size === 0) {
                    Swal.fire('Warning', 'Please select at least one group', 'warning');
                    return;
                }

                $('#selected-group-keys').val(JSON.stringify([...selectedGroups]));
                $('#proceed-shipping-form').submit();
            });

            // Handle allocation method change
            $('.allocation-method-select').on('change', function() {
                const method = $(this).val();
                const groupKey = $(this).data('group');

                // Store previous value in case we need to rollback
                const previousValue = $(this).data('previous-value') || 'quantity';
                $(this).data('previous-value', method);

                // Toggle UI immediately
                togglePercentageColumns(groupKey, method);

                // Auto-save method
                const data = {
                    domestic_waybill_no: $(`.group-waybill-input[data-group="${groupKey}"]`).val(),
                    domestic_cost: $(`.group-cost-input[data-group="${groupKey}"]`).val(),
                    cost_allocation_method: method,
                    _token: '{{ csrf_token() }}'
                };

                // Show loading
                const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                const $spinner = $card.find('.loading-spinner');
                $spinner.show();

                $.post(`/pre-shippings/${groupKey}/quick-update`, data)
                    .done(function(response) {
                        if (response.success) {
                            $card.find('.auto-save-indicator').removeClass('saving').addClass('saved');

                            // Update badge immediately
                            const $badge = $card.find('.cost-method-badge');
                            $badge.text(method.charAt(0).toUpperCase() + method.slice(1).replace('_',
                                ' '));

                            // Store successful value
                            $(`.allocation-method-select[data-group="${groupKey}"]`).data(
                                'previous-value', method);

                            showToast('success', 'Method updated successfully');
                        } else {
                            showToast('error', response.message || 'Failed to update method');
                        }
                    })
                    .fail(function(xhr) {
                        showToast('error', 'Failed to save method');

                        // **PERBAIKAN**: Rollback dropdown ke nilai sebelumnya jika gagal
                        $(`.allocation-method-select[data-group="${groupKey}"]`).val(previousValue);
                        togglePercentageColumns(groupKey, previousValue);
                    })
                    .always(function() {
                        $spinner.hide();
                    });
            });

            // Initialize previous values on page load
            $('.allocation-method-select').each(function() {
                $(this).data('previous-value', $(this).val());
            });

            // Auto-update on input changes (debounced)
            $('.allocation-input').on('input', function() {
                const groupKey = $(this).data('group');
                const $indicator = $(this).siblings('.auto-save-indicator');

                $indicator.removeClass('saved').addClass('saving');

                if (updateTimeout[groupKey]) {
                    clearTimeout(updateTimeout[groupKey]);
                }

                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroup(groupKey);
                }, 800);
            });

            // Handle percentage input changes
            $(document).on('input', '.percentage-input', function() {
                const groupKey = $(this).data('group');
                updatePercentageTotal(groupKey);

                const $indicator = $(this).siblings('.auto-save-indicator');
                $indicator.removeClass('saved').addClass('saving');

                if (updateTimeout[groupKey]) {
                    clearTimeout(updateTimeout[groupKey]);
                }

                updateTimeout[groupKey] = setTimeout(() => {
                    autoUpdateGroupWithPercentages(groupKey);
                }, 1000);
            });

            // Toggle percentage columns
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

                // Update method badge
                const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                const $badge = $card.find('.cost-method-badge');
                $badge.text(method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' '));
            }

            // Function khusus untuk update dengan percentages
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
                        const value = parseFloat($(this).val()) || 0;
                        data.percentages.push(value);
                    });

                    // Check if total is close to 100 before sending
                    const total = data.percentages.reduce((sum, val) => sum + val, 0);
                    if (Math.abs(total - 100) > 0.1) {
                        return; // Don't send request yet
                    }
                }

                const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                const $spinner = $card.find('.loading-spinner');
                $spinner.show();

                $.post(`/pre-shippings/${groupKey}/quick-update`, data)
                    .done(function(response) {
                        if (response.success) {
                            updateAllocatedCosts(groupKey, data);
                            $card.find('.auto-save-indicator').removeClass('saving').addClass('saved');
                            showToast('success', 'Percentages updated successfully');
                        } else {
                            showToast('error', response.message || 'Failed to update percentages');
                        }
                    })
                    .fail(function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to update percentages';
                        showToast('error', message);
                    })
                    .always(function() {
                        $spinner.hide();
                    });
            }

            // Update percentage total with validation
            function updatePercentageTotal(groupKey) {
                const $tbody = $(`tbody[data-group="${groupKey}"]`);
                const $validation = $(`.percentage-validation[data-group="${groupKey}"]`);
                let total = 0;

                $tbody.find('.percentage-input').each(function() {
                    const value = parseFloat($(this).val()) || 0;
                    total += value;
                });

                $validation.find('.total-percentage').text(total.toFixed(3));

                const $alert = $validation.find('.alert');
                if (Math.abs(total - 100) <= 0.1) {
                    $validation.removeClass('invalid').addClass('valid');
                    $alert.removeClass('alert-warning alert-danger').addClass('alert-success');
                } else {
                    $validation.removeClass('valid').addClass('invalid');
                    if (total > 100) {
                        $alert.removeClass('alert-success alert-info').addClass('alert-danger');
                    } else {
                        $alert.removeClass('alert-success alert-danger').addClass('alert-warning');
                    }
                }
            }

            // Auto-update group without page reload
            function autoUpdateGroup(groupKey) {
                const data = {
                    domestic_waybill_no: $(`.group-waybill-input[data-group="${groupKey}"]`).val(),
                    domestic_cost: $(`.group-cost-input[data-group="${groupKey}"]`).val(),
                    cost_allocation_method: $(`.allocation-method-select[data-group="${groupKey}"]`).val(),
                    _token: '{{ csrf_token() }}'
                };

                const $card = $(`.card-group-item[data-group="${groupKey}"]`);
                const $spinner = $card.find('.loading-spinner');
                $spinner.show();

                $.post(`/pre-shippings/${groupKey}/quick-update`, data)
                    .done(function(response) {
                        if (response.success) {
                            updateAllocatedCosts(groupKey, data);
                            $card.find('.auto-save-indicator').removeClass('saving').addClass('saved');
                            showToast('success', 'Group updated successfully');
                        } else {
                            showToast('error', response.message || 'Failed to update group');
                        }
                    })
                    .fail(function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to update group';
                        showToast('error', message);
                    })
                    .always(function() {
                        $spinner.hide();
                    });
            }

            // Update allocated costs in real-time
            function updateAllocatedCosts(groupKey, data) {
                const method = data.cost_allocation_method;
                const totalCost = parseFloat(data.domestic_cost) || 0;

                if (totalCost <= 0) return;

                const $tbody = $(`tbody[data-group="${groupKey}"]`);

                if (method === 'quantity') {
                    let totalQty = 0;
                    const quantities = [];

                    $tbody.find('tr').each(function() {
                        const qtyText = $(this).find('td:eq(2) .fw-semibold').text().replace(/,/g, '');
                        const qty = parseFloat(qtyText) || 0;
                        quantities.push(qty);
                        totalQty += qty;
                    });

                    $tbody.find('tr').each(function(index) {
                        if (totalQty > 0) {
                            const allocated = (quantities[index] / totalQty) * totalCost;
                            $(this).find('.allocated-amount').text(allocated.toFixed(2));
                        }
                    });
                } else if (method === 'percentage' && data.percentages) {
                    $tbody.find('tr').each(function(index) {
                        const percentage = data.percentages[index] || 0;
                        const allocated = (percentage / 100) * totalCost;
                        $(this).find('.allocated-amount').text(allocated.toFixed(2));
                    });
                } else if (method === 'value') {
                    let totalValue = 0;
                    const values = [];

                    $tbody.find('tr').each(function() {
                        const valueText = $(this).find('td:eq(4) .fw-semibold').text().replace(/[$,]/g, '');
                        const value = parseFloat(valueText) || 0;
                        values.push(value);
                        totalValue += value;
                    });

                    $tbody.find('tr').each(function(index) {
                        if (totalValue > 0) {
                            const allocated = (values[index] / totalValue) * totalCost;
                            $(this).find('.allocated-amount').text(allocated.toFixed(2));
                        }
                    });
                }
            }

            // Show toast notification
            function showToast(type, message) {
                const toastHtml = `
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

                const $toast = $(toastHtml);
                $('#toast-container').append($toast);

                const toast = new bootstrap.Toast($toast[0]);
                toast.show();

                $toast.on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }

            // Initialize percentage totals for existing data
            $('.allocation-method-select').each(function() {
                const groupKey = $(this).data('group');
                const method = $(this).val();

                if (method === 'percentage') {
                    updatePercentageTotal(groupKey);
                }
            });

            // Auto-save visual feedback
            $('.allocation-input').on('focus', function() {
                $(this).addClass('border-primary');
            }).on('blur', function() {
                $(this).removeClass('border-primary');
            });
        });
    </script>
@endpush
