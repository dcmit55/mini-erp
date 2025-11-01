@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- LEFT COLUMN: Material Planning Form (8 cols) -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
                            <div class="d-flex align-items-center mb-2 mb-lg-0">
                                <i class="fas fa-clipboard-list gradient-icon me-2" style="font-size: 1.5rem;"></i>
                                <h2 class="mb-0" style="font-size:1.3rem;">Create Material Planning</h2>
                            </div>
                            <a href="{{ route('material_planning.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Back To List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0 list-unstyled">
                                    @foreach ($errors->all() as $error)
                                        <li><i class="fas fa-exclamation-circle me-2"></i>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <form id="material-planning-form" method="POST" action="{{ route('material_planning.store') }}">
                            @csrf
                            <div id="job-order-groups">
                                <!-- Project Group 1 -->
                                <div class="job-order-group mb-4" data-group-index="0">
                                    <div class="card border-0 bg-light mb-3">
                                        <div class="card-header bg-light border-bottom d-flex align-items-center">
                                            <span class="badge bg-primary rounded-pill me-2">1</span>
                                            <h6 class="mb-0">Project Information</h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label fw-medium">
                                                            <i class="fas fa-project-diagram me-1 text-primary"></i>
                                                            Project/Job Order
                                                        </label>
                                                        <select class="form-select project-select" required>
                                                            <option value="">-- Select Project --</option>
                                                            @foreach ($projects as $project)
                                                                <option value="{{ $project->id }}">{{ $project->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="form-text">Select Project For This Material Planning
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Material List Table -->
                                    <div class="card mb-4">
                                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-boxes me-1 text-primary"></i>
                                                Material List
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-primary btn-add-row">
                                                <i class="fas fa-plus me-1"></i>Add Material
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover material-table mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 15%">Order Type</th>
                                                            <th style="width: 25%">Material Name</th>
                                                            <th style="width: 12%">Qty</th>
                                                            <th style="width: 12%">Unit</th>
                                                            <th style="width: 15%">ETA Date</th>
                                                            <th style="width: 15%" class="request-by-col d-none">Request By
                                                            </th>
                                                            <th style="width: 6%">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="material-row" data-row-index="0">
                                                            <td>
                                                                <input type="hidden" class="row-project-id"
                                                                    name="plans[0][project_id]" value="">

                                                                <select name="plans[0][order_type]"
                                                                    class="form-select form-select-sm order-type" required>
                                                                    <option value="material_req">Material Request</option>
                                                                    <option value="purchase_req">Purchase Request</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="position-relative">
                                                                    <select name="plans[0][material_name_select]"
                                                                        class="form-select form-select-sm material-dropdown">
                                                                        <option value="">-- Choose Material --
                                                                        </option>
                                                                        @foreach ($inventories as $inv)
                                                                            <option value="{{ $inv->name }}">
                                                                                {{ $inv->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <input type="hidden" name="plans[0][material_name]"
                                                                        class="hidden-material-name" value="">
                                                                    <input type="text"
                                                                        class="form-control form-control-sm material-freetext d-none"
                                                                        placeholder="Masukkan nama material">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="plans[0][qty_needed]"
                                                                    class="form-control form-control-sm" min="0.01"
                                                                    step="0.01" placeholder="0.00" required>
                                                            </td>
                                                            <td>
                                                                <select name="plans[0][unit_id]"
                                                                    class="form-select form-select-sm" required>
                                                                    <option value="">-- Unit --</option>
                                                                    @foreach ($units as $unit)
                                                                        <option value="{{ $unit->id }}">
                                                                            {{ $unit->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="date" name="plans[0][eta_date]"
                                                                        class="form-control form-control-sm" required>
                                                                    <span class="input-group-text">
                                                                        <i class="far fa-calendar-alt"></i>
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td class="request-by-cell d-none">
                                                                <input type="text" name="plans[0][requested_by]"
                                                                    class="form-control form-control-sm request-by-input"
                                                                    value="{{ auth()->user()->name }}" disabled>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button"
                                                                    class="btn btn-outline-danger btn-sm btn-remove-row"
                                                                    title="Hapus baris">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button type="button" class="btn btn-info" id="btn-add-job-order">
                                    <i class="fas fa-plus-circle me-1"></i> Add More Project
                                </button>
                                <button type="submit" class="btn btn-primary" id="btn-submit">
                                    <i class="fas fa-save me-1"></i> Submit Planning
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Related Items (4 cols) -->
            <div class="col-lg-4">
                @include('production.material_planning._related_items_card')
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .material-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .material-table thead th {
            font-weight: 500;
        }

        .material-row:hover {
            background-color: rgba(0, 0, 0, .02);
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }

        .job-order-group {
            position: relative;
            transition: all 0.3s;
        }

        .job-order-group:not(:first-child)::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }

        /* Nonaktifkan Request By Kolom */
        .request-by-col,
        .request-by-cell {
            display: none !important;
        }

        /* Responsive: Sidebar jadi full-width di tablet/mobile */
        @media (max-width: 1199px) {
            .col-lg-8 {
                margin-bottom: 30px;
            }

            .related-items-panel {
                position: static !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let isSubmitting = false;

            // ✨ DISABLE Request By inputs
            function disableRequestByInputs() {
                $('.request-by-input').prop('disabled', true).attr('disabled', 'disabled');
            }

            // ✨ LOAD Related Items saat project berubah
            function loadRelatedItems(projectId) {
                if (!projectId) {
                    // Clear related items jika project kosong
                    $('.related-items-container').html(`
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                            <p class="text-muted mt-3 mb-0">
                                Select a project to view<br>related purchase requests
                            </p>
                        </div>
                    `);
                    $('.related-items-badge').text('0');
                    return;
                }

                $.ajax({
                    url: "{{ route('material_planning.related_items', ':id') }}".replace(':id', projectId),
                    method: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        $('.related-items-container').html(`
                            <div class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Loading related items...</p>
                            </div>
                        `);
                    },
                    success: function(response) {
                        if (response.success && response.items.length > 0) {
                            const itemsHtml = response.items.map(item => `
                                <div class="card related-item-card">
                                    <div class="card-body">
                                        <!-- Header Row -->
                                        <div class="related-item-header">
                                            <div class="related-item-material">
                                                <i class="bi bi-box"></i> ${item.material_name}
                                            </div>
                                            <span class="badge bg-warning related-item-status">
                                                ${item.approval_status}
                                            </span>
                                        </div>

                                        <!-- Info Grid -->
                                        <div class="related-item-info">
                                            <div class="info-row">
                                                <span class="info-label">Qty:</span>
                                                <span class="info-value">${item.qty_needed} ${item.unit}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">ETA:</span>
                                                <span class="info-value">${item.eta_date}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Supplier:</span>
                                                <span class="info-value">${item.supplier}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Price:</span>
                                                <span class="info-value">${item.price_per_unit || '-'}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">By:</span>
                                                <span class="info-value">${item.requested_by}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('');

                            $('.related-items-container').html(itemsHtml);
                            $('.related-items-badge').text(response.items.length);
                        } else {
                            $('.related-items-container').html(`
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3 mb-0">No related purchase requests found</p>
                                </div>
                            `);
                            $('.related-items-badge').text('0');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading related items:', error);
                        $('.related-items-container').html(`
                            <div class="alert alert-warning" role="alert">
                                <small>Error loading related items</small>
                            </div>
                        `);
                    }
                });
            }

            // Event: Project selection change
            $(document).on('change', '.project-select', function() {
                const projectId = $(this).val();
                const $group = $(this).closest('.job-order-group');

                // Sync project ID ke semua rows
                $group.find('.material-row').each(function() {
                    $(this).find('.row-project-id').val(projectId);
                });

                // Load related items
                loadRelatedItems(projectId);
            });

            // Event: Add material row
            $(document).on('click', '.btn-add-row', function() {
                const $group = $(this).closest('.job-order-group');
                const $firstRow = $group.find('.material-row').first();
                const $newRow = $firstRow.clone();

                $newRow.find('input, select, textarea').not('.row-project-id').each(function() {
                    if ($(this).is('select')) {
                        $(this).prop('selectedIndex', 0);
                    } else {
                        $(this).val('');
                    }
                });

                $newRow.find('.material-freetext').addClass('d-none');
                $newRow.find('.material-dropdown').removeClass('d-none');

                $group.find('tbody').append($newRow);
                updateAllIndexes();

                $('html, body').animate({
                    scrollTop: $newRow.offset().top - 150
                }, 300);
            });

            // Event: Remove row
            $(document).on('click', '.btn-remove-row', function() {
                const $tbody = $(this).closest('tbody');

                if ($tbody.find('.material-row').length > 1) {
                    $(this).closest('tr').fadeOut(300, function() {
                        $(this).remove();
                        updateAllIndexes();
                    });
                } else {
                    alert('Cannot remove the last row. At least one material is required.');
                }
            });

            // Event: Add project group
            $('#btn-add-job-order').click(function() {
                const $firstGroup = $('.job-order-group').first();
                const $newGroup = $firstGroup.clone();

                $newGroup.find('input, select, textarea').each(function() {
                    if ($(this).is('select')) {
                        $(this).prop('selectedIndex', 0);
                    } else {
                        $(this).val('');
                    }
                });

                const $firstRow = $newGroup.find('.material-row').first();
                $newGroup.find('tbody').empty().append($firstRow);

                const projectNum = $('.job-order-group').length + 1;
                $newGroup.find('.badge').text(projectNum);

                $('#job-order-groups').append($newGroup);
                updateAllIndexes();

                $('html, body').animate({
                    scrollTop: $newGroup.offset().top - 100
                }, 500);
            });

            // Event: Order type change
            $(document).on('change', '.order-type', function() {
                const $row = $(this).closest('tr');

                if ($(this).val() === 'material_req') {
                    $row.find('.material-dropdown').removeClass('d-none');
                    $row.find('.material-freetext').addClass('d-none').val('');
                } else {
                    $row.find('.material-dropdown').addClass('d-none').val('');
                    $row.find('.material-freetext').removeClass('d-none');
                }
            });

            // Update all indexes
            function updateAllIndexes() {
                let globalIndex = 0;

                $('.job-order-group').each(function(groupIdx) {
                    const $group = $(this);
                    $group.attr('data-group-index', groupIdx);

                    $group.find('.material-row').each(function(rowIdx) {
                        const $row = $(this);
                        $row.attr('data-row-index', globalIndex);

                        $row.find('select, input, textarea').each(function() {
                            const $field = $(this);
                            const name = $field.attr('name');

                            if (name && name.includes('plans[')) {
                                const matches = name.match(/plans\[\d+\]\[([^\]]+)\]$/);
                                if (matches) {
                                    const fieldName = matches[1];
                                    const newName = `plans[${globalIndex}][${fieldName}]`;
                                    $field.attr('name', newName);
                                }
                            }
                        });

                        globalIndex++;
                    });
                });

                disableRequestByInputs();
            }

            // Form submission
            $('#material-planning-form').on('submit', function(e) {
                e.preventDefault();

                if (isSubmitting) return false;

                updateAllIndexes();

                let valid = true;
                const errorMessages = [];

                $('.job-order-group').each(function(idx) {
                    const projectId = $(this).find('.project-select').val();
                    if (!projectId) {
                        $(this).find('.project-select').addClass('is-invalid');
                        errorMessages.push(`Project #${idx + 1} is required`);
                        valid = false;
                    }
                });

                if (!valid) {
                    alert('Please fix the errors before submitting.');
                    return false;
                }

                isSubmitting = true;
                $('#btn-submit').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

                this.submit();
            });

            // Validation on change
            $(document).on('change', 'input, select', function() {
                $(this).removeClass('is-invalid');
            });

            // Initialize
            setTimeout(function() {
                updateAllIndexes();
                disableRequestByInputs();
            }, 500);
        });
    </script>
@endpush
