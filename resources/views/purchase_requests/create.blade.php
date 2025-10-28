@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-2 flex-shrink-0" style="font-size:1.3rem;">
                    Create Purchase Request
                    @if (isset($selectedInventory) && isset($prefilledType))
                        <small class="text-muted">Restock for {{ $selectedInventory->name }}</small>
                    @endif
                </h2>
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('purchase_requests.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div id="requests-container">
                        <!-- First request form (always visible) -->
                        <div class="request-row">
                            <hr class="mt-0 mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="type" class="form-label">Type</label>
                                    <select name="requests[0][type]" class="form-select type-select" required>
                                        <option value="">Select Type</option>
                                        <option value="new_material"
                                            {{ old('requests.0.type', $prefilledType ?? '') == 'new_material' ? 'selected' : '' }}>
                                            New Material
                                        </option>
                                        <option value="restock"
                                            {{ old('requests.0.type', $prefilledType ?? '') == 'restock' ? 'selected' : '' }}>
                                            Restock</option>
                                    </select>
                                </div>
                                <div class="col-md-8 material-name-group">
                                    <label class="form-label">Material Name</label>
                                    <input type="text" name="requests[0][material_name]"
                                        class="form-control material-name-input"
                                        value="{{ old('requests.0.material_name', '') }}" required>
                                    <select name="requests[0][inventory_id]"
                                        class="form-select select2 material-name-select d-none">
                                        <option value="">Select Material</option>
                                        @foreach ($inventories as $inv)
                                            <option value="{{ $inv->id }}" data-unit="{{ $inv->unit }}"
                                                data-stock="{{ $inv->quantity }}"
                                                {{ old('requests.0.inventory_id', '') == $inv->id ? 'selected' : '' }}>
                                                {{ $inv->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('requests.0.material_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Stock Level</label>
                                    <input type="number" name="requests[0][stock_level]"
                                        class="form-control stock-level-input"
                                        value="{{ old('requests.0.stock_level', '') }}" min="0" step="0.01">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Required Quantity</label>
                                    <input type="number" name="requests[0][required_quantity]" class="form-control"
                                        value="{{ old('requests.0.required_quantity', '') }}" required min="0.01"
                                        step="0.01">
                                </div>
                                <div class="col-md-4 unit-group">
                                    <label class="form-label">Unit</label>
                                    <!-- Select2 for new_material -->
                                    <button type="button" class="btn btn-outline-primary btn-sm add-unit-btn"
                                        data-bs-toggle="modal" data-bs-target="#addUnitModal"
                                        style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                                        + Add Unit
                                    </button>
                                    <select name="requests[0][unit]" class="form-select select2 unit-select d-none"
                                        required>
                                        <option value="">Select Unit</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->name }}"
                                                {{ old('requests.0.unit', '') == $unit->name ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <!-- Input text for restock -->
                                    <input type="text" name="requests[0][unit]" class="form-control unit-input" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Project</label>
                                    <button type="button" class="btn btn-outline-primary btn-sm quickAddProjectBtn"
                                        data-bs-toggle="modal" data-bs-target="#addProjectModal"
                                        style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                                        + Add Project
                                    </button>
                                    <select name="requests[0][project_id]" class="form-select select2 project-select">
                                        <option value="">Select Project</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}"
                                                {{ old('requests.0.project_id', '') == $project->id ? 'selected' : '' }}>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Image (optional)</label>
                                    <input type="file" name="requests[0][img]" class="form-control" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Remark</label>
                                    {{-- Remark readonly jika dari dashboard (low stock items) --}}
                                    <textarea name="requests[0][remark]"
                                        class="form-control remark-textarea {{ isset($selectedInventory) && isset($prefilledType) ? 'bg-light' : '' }}"
                                        rows="3" placeholder="Enter remarks or notes for this request"
                                        {{ isset($selectedInventory) && isset($prefilledType) ? 'readonly' : '' }}>{{ old('requests.0.remark', $defaultRemark ?? '') }}</textarea>
                                    <small class="text-muted">
                                        @if (isset($selectedInventory) && isset($prefilledType))
                                            Auto-filled from dashboard (read-only)
                                        @else
                                            Optional: Add any notes or special instructions
                                        @endif
                                    </small>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-row"
                                        style="display:none;">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between my-4">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-more-btn">
                            <i class="fas fa-plus-circle"></i> Add More Request
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit-request-btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                aria-hidden="true"></span>
                            Submit Request(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="quickAddProjectForm" method="POST" action="{{ route('projects.store.quick') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProjectModalLabel">Quick Add Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label>Project Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                        <label class="mt-2">Qty <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="qty" class="form-control" required>
                        <label class="mt-2">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select Department</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add Project</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Unit Modal -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="unitForm" method="POST" action="{{ route('units.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUnitModalLabel">Add New Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label>Unit Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Unit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Template for cloning -->
    <template id="request-row-template">
        <div class="request-row">
            <hr class="mt-0 mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="requests[INDEX][type]" class="form-select type-select" required>
                        <option value="">Select Type</option>
                        <option value="new_material">New Material</option>
                        <option value="restock">Restock</option>
                    </select>
                </div>
                <div class="col-md-8 material-name-group">
                    <label class="form-label">Material Name</label>
                    <input type="text" name="requests[INDEX][material_name]" class="form-control material-name-input"
                        required>
                    <select name="requests[INDEX][inventory_id]" class="form-select select2 material-name-select d-none">
                        <option value="">Select Material</option>
                        @foreach ($inventories as $inv)
                            <option value="{{ $inv->id }}" data-unit="{{ $inv->unit }}"
                                data-stock="{{ $inv->quantity }}">
                                {{ $inv->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock Level</label>
                    <input type="number" name="requests[INDEX][stock_level]" class="form-control stock-level-input"
                        min="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Required Quantity</label>
                    <input type="number" name="requests[INDEX][required_quantity]" class="form-control" required
                        min="0.01" step="0.01">
                </div>
                <div class="col-md-4 unit-group">
                    <label class="form-label">Unit</label>
                    <button type="button" class="btn btn-outline-primary btn-sm add-unit-btn" data-bs-toggle="modal"
                        data-bs-target="#addUnitModal"
                        style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                        + Add Unit
                    </button>
                    <select name="requests[INDEX][unit]" class="form-select select2 unit-select d-none" required>
                        <option value="">Select Unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="requests[INDEX][unit]" class="form-control unit-input" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Project</label>
                    <button type="button" class="btn btn-outline-primary btn-sm quickAddProjectBtn"
                        data-bs-toggle="modal" data-bs-target="#addProjectModal"
                        style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                        + Add Project
                    </button>
                    <select name="requests[INDEX][project_id]" class="form-select select2 project-select">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Image (optional)</label>
                    <input type="file" name="requests[INDEX][img]" class="form-control" accept="image/*">
                </div>
                {{-- Field Remark untuk row dari index --}}
                <div class="col-md-6">
                    <label class="form-label">Remark</label>
                    <textarea name="requests[INDEX][remark]" class="form-control remark-textarea" rows="3"
                        placeholder="Enter remarks or notes for this request"></textarea>
                    <small class="text-muted">Optional: Add any notes or special instructions</small>
                </div>
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('styles')
    <style>
        .select2-container .select2-selection--single {
            height: calc(2.375rem + 2px);
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }

        .request-row {
            position: relative;
            padding: 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .request-row:hover {
            background-color: #f8f9fa;
        }

        /* Styling untuk readonly remark field */
        .remark-textarea[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .remark-textarea[readonly]:focus {
            background-color: #f8f9fa;
            border-color: #ced4da;
            box-shadow: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let rowIndex = 0; // Start with 0 for first row

            // Track the last active row for modals
            let lastActiveRow = null;
            $(document).on('click', '.quickAddProjectBtn', function() {
                lastActiveRow = $(this).closest('.request-row');
            });
            $(document).on('click', '.add-unit-btn', function() {
                lastActiveRow = $(this).closest('.request-row');
            });

            // Initialize select2 for the first row
            initializeRow(0);

            // Auto-fill form jika ada data dari dashboard
            @if (isset($selectedInventory) && isset($prefilledType))
                autoFillFromDashboard();
                //Remark readonly untuk request dari dashboard
                protectReadonlyRemark();
            @endif

            // Add more rows
            $('#add-more-btn').click(function() {
                rowIndex++;
                let newRow = $('#request-row-template').html().replace(/INDEX/g, rowIndex);
                $('#requests-container').append(newRow);

                // Make remove button visible on first row if we have more than one row
                if ($('.request-row').length > 1) {
                    $('.btn-remove-row').show();
                }

                // Initialize the new row
                initializeRow(rowIndex);

                // Scroll to the new row
                $('html, body').animate({
                    scrollTop: $('.request-row:last').offset().top - 100
                }, 500);
            });

            // Remove row
            $(document).on('click', '.btn-remove-row', function() {
                // Get the parent row
                const row = $(this).closest('.request-row');

                // Remove select2 to prevent memory leaks
                row.find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                // Remove the row
                row.remove();

                // Hide remove button on first row if only one row remains
                if ($('.request-row').length <= 1) {
                    $('.btn-remove-row').hide();
                }
            });

            // Submit form with spinner
            const form = document.querySelector('form[action="{{ route('purchase_requests.store') }}"]');
            const submitBtn = document.getElementById('submit-request-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;
            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Submitting...';
                });
            }

            // Quick Add Project
            $('#quickAddProjectForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success && res.project) {
                            // Add project to all project selects
                            $('.project-select').each(function() {
                                let newOption = new Option(res.project.name, res.project
                                    .id, false, false);
                                $(this).append(newOption);
                            });

                            // Select the new project in the current row
                            if (lastActiveRow && lastActiveRow.length) {
                                lastActiveRow.find('.project-select').val(res.project.id)
                                    .trigger('change');
                            }

                            $('#addProjectModal').modal('hide');
                            form[0].reset();
                        } else {
                            Swal.fire('Error', 'Failed to add project. Please try again.',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add project. Please try again.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Submit form unit via AJAX
            $('#unitForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(unit) {
                        // Add unit to all unit selects
                        $('.unit-select').each(function() {
                            let newOption = new Option(unit.name, unit.name, false,
                                false);
                            $(this).append(newOption);
                        });

                        // Select the new unit in the current row
                        if (lastActiveRow && lastActiveRow.length) {
                            lastActiveRow.find('.unit-select').val(unit.name).trigger('change');
                        }

                        $('#addUnitModal').modal('hide');
                        form[0].reset();
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add unit. Please try again.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    }
                });
            });

            // ketika user mengklik Purchase Request dari low stock items
            function autoFillFromDashboard() {
                const selectedInventory = @json($selectedInventory ?? null);
                const prefilledType = @json($prefilledType ?? null);

                if (selectedInventory && prefilledType) {
                    const firstRow = $('.request-row').first();

                    // Set type field
                    firstRow.find('.type-select').val(prefilledType).trigger('change');

                    // Tunggu sebentar agar DOM terupdate setelah change event
                    setTimeout(function() {
                        if (prefilledType === 'restock') {
                            // Set material select untuk restock
                            firstRow.find('.material-name-select').val(selectedInventory.id).trigger(
                                'change');

                            // Update fields berdasarkan inventory data
                            firstRow.find('.unit-input').val(selectedInventory.unit || '');
                            firstRow.find('.stock-level-input').val(selectedInventory.quantity || '');



                            // PERUBAHAN: Required quantity dibiarkan kosong agar user mengisi sendiri
                            // Tidak auto-fill quantity, biarkan user menentukan sendiri

                        } else if (prefilledType === 'new_material') {
                            // Set material name untuk new material
                            firstRow.find('.material-name-input').val(selectedInventory.name);
                            firstRow.find('.unit-select').val(selectedInventory.unit || '').trigger(
                                'change');
                            firstRow.find('.stock-level-input').val(selectedInventory.quantity || '');
                        }

                        // PERUBAHAN: Update notifikasi untuk memberitahu user mengisi quantity manual
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Auto-filled!',
                                text: `${selectedInventory.name} fill in the required quantity manually.`,
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        }
                    }, 500);
                }
            }

            // Helper function to initialize a row
            function initializeRow(index) {
                const row = $(`[name="requests[${index}][type]"]`).closest('.request-row');

                // Initialize select2 elements
                row.find('.select2').select2({
                    theme: 'bootstrap-5',
                    allowClear: true,
                    dropdownAutoWidth: true,
                    width: '100%',
                });

                // Add image preview functionality
                row.find('input[type="file"]').on('change', function(e) {
                    const input = e.target;
                    const previewContainer = $(input).parent();

                    // Remove any existing preview
                    previewContainer.find('.img-preview-container').remove();

                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewHtml = `
                    <div class="img-preview-container mt-2">
                        <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 100px">
                    </div>
                `;
                            previewContainer.append(previewHtml);
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                });

                // Initialize type select change event
                row.find('.type-select').on('change', function() {
                    toggleMaterialInput($(this));
                });

                // Initialize material name select change event
                row.find('.material-name-select').on('change', function() {
                    updateMaterialFields($(this));
                });

                // Call toggleMaterialInput once to initialize the state
                toggleMaterialInput(row.find('.type-select'));
            }

            // Toggle material input fields based on type
            function toggleMaterialInput(typeSelect) {
                const row = typeSelect.closest('.request-row');
                const type = typeSelect.val();

                if (type === '') {
                    // Initial state: all inputs disabled
                    row.find('.material-name-input').show().prop('required', false).prop('disabled', true);
                    row.find('.material-name-select').hide().addClass('d-none').prop('required', false).prop(
                        'disabled', true);
                    row.find('.material-name-select').next('.select2-container').hide();
                    row.find('.add-unit-btn').hide();
                    row.find('.unit-input').show().prop('readonly', false).prop('disabled', true).val('');
                    row.find('.unit-select').hide().addClass('d-none').prop('disabled', true);
                    row.find('.unit-select').next('.select2-container').hide();
                    row.find('.stock-level-input').prop('readonly', false).prop('disabled', true).val('');
                    row.find('input[name$="[required_quantity]"]').prop('disabled', true).val('');

                } else if (type === 'new_material') {
                    // New material: show manual input for material name
                    row.find('.material-name-input').show().prop('required', true).prop('disabled', false);
                    row.find('.material-name-select').hide().prop('required', false).prop('disabled', true);
                    row.find('.material-name-select').next('.select2-container').hide();
                    row.find('.add-unit-btn').show();
                    row.find('.unit-input').hide().prop('disabled', true).val('');
                    row.find('.unit-select').show().removeClass('d-none').prop('disabled', false);
                    row.find('.unit-select').next('.select2-container').show();
                    row.find('.stock-level-input').prop('readonly', false).prop('disabled', false);
                    row.find('input[name$="[required_quantity]"]').prop('disabled', false);

                } else if (type === 'restock') {
                    // Restock: show select for existing inventory items
                    row.find('.material-name-input').hide().prop('required', false).prop('disabled', true);
                    row.find('.material-name-select').show().removeClass('d-none').prop('required', true).prop(
                        'disabled', false);
                    row.find('.material-name-select').next('.select2-container').show();
                    row.find('.add-unit-btn').hide();
                    row.find('.unit-input').show().prop('readonly', true).prop('disabled', false);
                    row.find('.unit-select').hide().prop('disabled', true);
                    row.find('.unit-select').next('.select2-container').hide();
                    row.find('.stock-level-input').prop('readonly', true).prop('disabled', false);
                    row.find('input[name$="[required_quantity]"]').prop('disabled', false);

                    // Update fields based on selected inventory
                    updateMaterialFields(row.find('.material-name-select'));
                }
            }

            // Update material fields based on selected inventory
            function updateMaterialFields(select) {
                const row = select.closest('.request-row');
                const selected = select.find(':selected');

                if (selected.val()) {
                    row.find('.unit-input').val(selected.data('unit') || '');
                    row.find('.stock-level-input').val(selected.data('stock') || '');
                }
            }
        });
    </script>
@endpush
