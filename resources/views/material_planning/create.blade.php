@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
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
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form id="material-planning-form" method="POST" action="{{ route('material_planning.store') }}">
                    @csrf
                    <div id="job-order-groups">
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
                                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="form-text">Select Project For This Material Planning</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                                                    <th style="width: 15%">Request By</th>
                                                    <th style="width: 6%">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="material-row" data-row-index="0">
                                                    <td>
                                                        <!-- Hidden project_id untuk setiap row -->
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
                                                                <option value="">-- Choose Material --</option>
                                                                @foreach ($inventories as $inv)
                                                                    <option value="{{ $inv->name }}">{{ $inv->name }}
                                                                    </option>
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
                                                        <select name="plans[0][unit_id]" class="form-select form-select-sm"
                                                            required>
                                                            <option value="">-- Unit --</option>
                                                            @foreach ($units as $unit)
                                                                <option value="{{ $unit->id }}">{{ $unit->name }}
                                                                </option>
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
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm"
                                                            value="{{ auth()->user()->name }}" readonly>
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
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let isSubmitting = false;

            // Fungsi UTAMA untuk sync project_id ke semua rows dalam group
            function syncProjectIdToRows($group) {
                var projectId = $group.find('.project-select').val();
                console.log('Syncing project ID: ' + projectId + ' to all rows in group');

                $group.find('.material-row').each(function() {
                    $(this).find('.row-project-id').val(projectId);
                    console.log('Set row project_id to: ' + projectId);
                });
            }

            // Fungsi untuk mengupdate semua name attributes dengan index yang benar
            function updateAllIndexes() {
                var globalIndex = 0;

                console.log('=== UPDATING ALL INDEXES ===');

                $('.job-order-group').each(function(groupIdx) {
                    var $group = $(this);
                    $group.attr('data-group-index', groupIdx);

                    console.log('Processing group ' + groupIdx);

                    $group.find('.material-row').each(function(rowIdx) {
                        var $row = $(this);
                        $row.attr('data-row-index', globalIndex);

                        console.log('  Processing row ' + globalIndex + ' (local: ' + rowIdx + ')');

                        // Update SEMUA field dalam row dengan index global yang benar
                        $row.find('select, input, textarea').each(function() {
                            var $field = $(this);
                            var name = $field.attr('name');

                            if (name && name.includes('plans[')) {
                                // Extract field name
                                var matches = name.match(/plans\[\d+\]\[([^\]]+)\]$/);
                                if (matches) {
                                    var fieldName = matches[1];
                                    var newName = 'plans[' + globalIndex + '][' +
                                        fieldName + ']';
                                    $field.attr('name', newName);
                                    console.log('    Updated: ' + name + ' -> ' + newName);
                                }
                            }
                        });

                        globalIndex++;
                    });
                });

                console.log('Total rows indexed: ' + globalIndex);
                console.log('=== END UPDATE INDEXES ===');

                // Sync semua project IDs setelah update index
                $('.job-order-group').each(function() {
                    syncProjectIdToRows($(this));
                });
            }

            // Fungsi untuk update material_name value
            function updateMaterialNameValue($row) {
                var orderType = $row.find('.order-type').val();
                var $hiddenField = $row.find('.hidden-material-name');

                if (orderType === 'material_req') {
                    $hiddenField.val($row.find('.material-dropdown').val());
                } else {
                    $hiddenField.val($row.find('.material-freetext').val());
                }
            }

            // Event: Project selection change
            $(document).on('change', '.project-select', function() {
                var $group = $(this).closest('.job-order-group');
                syncProjectIdToRows($group);
                console.log('Project changed, synced to all rows');
            });

            // Event: Add material row
            $(document).on('click', '.btn-add-row', function() {
                console.log('=== ADDING NEW ROW ===');

                var $group = $(this).closest('.job-order-group');
                var $firstRow = $group.find('.material-row').first();
                var $newRow = $firstRow.clone();

                // Clear all values except structure
                $newRow.find('input, select, textarea').not('.row-project-id').each(function() {
                    if ($(this).is('select')) {
                        $(this).prop('selectedIndex', 0);
                    } else {
                        $(this).val('');
                    }
                });

                // Reset material input visibility
                $newRow.find('.material-freetext').addClass('d-none');
                $newRow.find('.material-dropdown').removeClass('d-none');

                // Append new row
                $group.find('tbody').append($newRow);

                // Update indexes
                updateAllIndexes();

                // Scroll to new row
                $('html, body').animate({
                    scrollTop: $newRow.offset().top - 150
                }, 300);

                console.log('=== ROW ADDED ===');
            });

            // Event: Remove row
            $(document).on('click', '.btn-remove-row', function() {
                var $tbody = $(this).closest('tbody');

                if ($tbody.find('.material-row').length > 1) {
                    console.log('=== REMOVING ROW ===');

                    $(this).closest('tr').fadeOut(300, function() {
                        $(this).remove();
                        updateAllIndexes();
                        console.log('=== ROW REMOVED ===');
                    });
                } else {
                    alert('Tidak dapat menghapus baris terakhir. Minimal harus ada satu material.');
                }
            });

            // Event: Add project group
            $('#btn-add-job-order').click(function() {
                console.log('=== ADDING NEW PROJECT GROUP ===');

                var $firstGroup = $('.job-order-group').first();
                var $newGroup = $firstGroup.clone();

                // Clear all values
                $newGroup.find('input, select, textarea').each(function() {
                    if ($(this).is('select')) {
                        $(this).prop('selectedIndex', 0);
                    } else {
                        $(this).val('');
                    }
                });

                // Reset to single row
                var $firstRow = $newGroup.find('.material-row').first();
                $newGroup.find('tbody').empty().append($firstRow);

                // Update badge number
                var projectNum = $('.job-order-group').length + 1;
                $newGroup.find('.badge').text(projectNum);

                // Append group
                $('#job-order-groups').append($newGroup);

                // Update all indexes
                updateAllIndexes();

                // Scroll to new group
                $('html, body').animate({
                    scrollTop: $newGroup.offset().top - 100
                }, 500);

                console.log('=== PROJECT GROUP ADDED ===');
            });

            // Event: Order type change
            $(document).on('change', '.order-type', function() {
                var $row = $(this).closest('tr');

                if ($(this).val() === 'material_req') {
                    $row.find('.material-dropdown').removeClass('d-none');
                    $row.find('.material-freetext').addClass('d-none').val('');
                } else {
                    $row.find('.material-dropdown').addClass('d-none').val('');
                    $row.find('.material-freetext').removeClass('d-none');
                }

                updateMaterialNameValue($row);
            });

            // Event: Material selection change
            $(document).on('change', '.material-dropdown', function() {
                updateMaterialNameValue($(this).closest('tr'));
            });

            $(document).on('input', '.material-freetext', function() {
                updateMaterialNameValue($(this).closest('tr'));
            });

            // Form submission
            $('#material-planning-form').on('submit', function(e) {
                e.preventDefault();

                if (isSubmitting) {
                    console.log('Already submitting...');
                    return false;
                }

                console.log('=== FORM SUBMISSION ===');

                // Update everything one last time
                $('.material-row').each(function() {
                    updateMaterialNameValue($(this));
                });
                updateAllIndexes();

                // Validate
                var valid = true;
                var errorMessages = [];

                // Check all projects selected
                $('.job-order-group').each(function(idx) {
                    var projectId = $(this).find('.project-select').val();
                    if (!projectId) {
                        $(this).find('.project-select').addClass('is-invalid');
                        errorMessages.push('Project #' + (idx + 1) + ' belum dipilih');
                        valid = false;
                    } else {
                        $(this).find('.project-select').removeClass('is-invalid');
                    }
                });

                // Check all materials filled
                $('.material-row').each(function(idx) {
                    var materialName = $(this).find('.hidden-material-name').val();
                    if (!materialName) {
                        var orderType = $(this).find('.order-type').val();
                        if (orderType === 'material_req') {
                            $(this).find('.material-dropdown').addClass('is-invalid');
                        } else {
                            $(this).find('.material-freetext').addClass('is-invalid');
                        }
                        errorMessages.push('Material #' + (idx + 1) + ' belum diisi');
                        valid = false;
                    }
                });

                // Debug log
                console.log('=== FORM DATA TO BE SUBMITTED ===');
                var formData = $(this).serializeArray();
                var planCount = {};

                formData.forEach(function(item) {
                    if (item.name.includes('plans[')) {
                        console.log(item.name + ' = ' + item.value);

                        // Count plans
                        var matches = item.name.match(/plans\[(\d+)\]/);
                        if (matches) {
                            var idx = matches[1];
                            planCount[idx] = (planCount[idx] || 0) + 1;
                        }
                    }
                });

                console.log('Plans count:', planCount);
                console.log('=== END FORM DATA ===');

                if (!valid) {
                    console.error('Validation failed:', errorMessages);

                    $('#validation-alert').remove();
                    var alert = $(
                        '<div id="validation-alert" class="alert alert-danger alert-dismissible fade show">' +
                        '<strong>Error!</strong><ul class="mb-0">' +
                        errorMessages.map(msg => '<li>' + msg + '</li>').join('') +
                        '</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
                    );
                    $(this).prepend(alert);

                    $('html, body').animate({
                        scrollTop: 0
                    }, 500);

                    return false;
                }

                console.log('Validation passed, submitting...');

                // Disable submit button
                isSubmitting = true;
                $('#btn-submit').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

                // Submit form
                this.submit();
            });

            // Validation on change
            $(document).on('change', 'input, select', function() {
                $(this).removeClass('is-invalid');
            });

            // Initialize
            setTimeout(function() {
                console.log('=== INITIALIZING FORM ===');

                $('.material-row').each(function() {
                    updateMaterialNameValue($(this));
                });

                updateAllIndexes();

                console.log('=== FORM INITIALIZED ===');
            }, 500);
        });
    </script>
@endpush
