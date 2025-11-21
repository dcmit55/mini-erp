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
                        @if (!isset($selectedInventory) || !isset($prefilledType))
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-more-btn">
                                <i class="fas fa-plus-circle"></i> Add More Request
                            </button>
                        @endif
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
                        <input type="text" name="name" class="form-control mb-2" required>

                        <label class="mt-2">Qty</label>
                        <input type="number" step="any" name="qty" class="form-control mb-2" min="0">

                        <label class="mt-2">Department <span class="text-danger">*</span></label>
                        <select name="department_ids[]" id="quick-add-departments" class="form-select" multiple required>
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
                    <select name="requests[INDEX][type]"
                        class="form-select type-select @error('requests.INDEX.type') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        <option value="new_material">New Material</option>
                        <option value="restock">Restock</option>
                    </select>
                    @error('requests.INDEX.type')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Material Name dengan error display -->
                <div class="col-md-8 material-name-group">
                    <label class="form-label">Material Name</label>
                    <div class="position-relative">
                        <input type="text" name="requests[INDEX][material_name]"
                            class="form-control material-name-input @error('requests.INDEX.material_name') is-invalid @enderror"
                            placeholder="Enter material name" required>

                        @error('requests.INDEX.material_name')
                            <div class="invalid-feedback d-block">
                                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <select name="requests[INDEX][inventory_id]"
                        class="form-select select2 material-name-select d-none @error('requests.INDEX.inventory_id') is-invalid @enderror"
                        data-placeholder="Select material">
                        <option value="">Select Material</option>
                        @foreach ($inventories as $inventory)
                            <option value="{{ $inventory->id }}" data-unit="{{ $inventory->unit }}"
                                data-stock="{{ $inventory->quantity }}">
                                {{ $inventory->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('requests.INDEX.inventory_id')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Stock Level -->
                <div class="col-md-4">
                    <label class="form-label">Stock Level</label>
                    <input type="number" name="requests[INDEX][stock_level]"
                        class="form-control stock-level-input @error('requests.INDEX.stock_level') is-invalid @enderror"
                        readonly>
                    @error('requests.INDEX.stock_level')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Required Quantity -->
                <div class="col-md-4">
                    <label class="form-label">Required Quantity</label>
                    <input type="number" name="requests[INDEX][required_quantity]"
                        class="form-control @error('requests.INDEX.required_quantity') is-invalid @enderror" required
                        min="0.01" step="0.01">
                    @error('requests.INDEX.required_quantity')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Unit -->
                <div class="col-md-4 unit-group">
                    <label class="form-label">Unit</label>
                    <button type="button" class="btn btn-outline-primary btn-sm add-unit-btn" data-bs-toggle="modal"
                        data-bs-target="#addUnitModal"
                        style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                        + Add Unit
                    </button>
                    <select name="requests[INDEX][unit]"
                        class="form-select select2 unit-select d-none @error('requests.INDEX.unit') is-invalid @enderror"
                        data-placeholder="Select unit" required>
                        <option value="">Select Unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="requests[INDEX][unit]"
                        class="form-control unit-input @error('requests.INDEX.unit') is-invalid @enderror" readonly>
                    @error('requests.INDEX.unit')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Project -->
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

                <!-- Image -->
                <div class="col-md-6">
                    <label class="form-label">Image (optional)</label>
                    <input type="file" name="requests[INDEX][img]" class="form-control" accept="image/*">
                </div>

                <!-- Remark -->
                <div class="col-md-12">
                    <label class="form-label">Remark</label>
                    <textarea name="requests[INDEX][remark]" class="form-control remark-textarea" rows="2"
                        placeholder="Enter remarks or notes for this request"></textarea>
                    <small class="text-muted">Optional: Add any notes or special instructions</small>
                </div>

                <!-- Remove Button -->
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-row">
                        <i class="fas fa-trash me-1"></i>Remove
                    </button>
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
            let rowIndex = 0;
            let lastActiveRow = null;

            const allInventories = @json($inventories ?? []);
            const allUnits = @json($units ?? []);
            const allProjects = @json($projects ?? []);

            // Ambil data dari session jika ada error validasi
            const formDataFromSession = @json(session('form_requests_data', []));

            // Click handlers untuk modal
            $(document).on('click', '.quickAddProjectBtn', function() {
                lastActiveRow = $(this).closest('.request-row');
            });
            $(document).on('click', '.add-unit-btn', function() {
                lastActiveRow = $(this).closest('.request-row');
            });

            // Initialize first row
            initializeRow(0);

            // Restore form data dari session jika ada error
            if (formDataFromSession && formDataFromSession.length > 0) {
                restoreFormData(formDataFromSession);
                rowIndex = formDataFromSession.length - 1;
            }

            // Auto-fill from dashboard jika ada
            @if (isset($selectedInventory) && isset($prefilledType))
                autoFillFromDashboard();
                protectReadonlyRemark();
            @endif

            // Add more rows button
            $('#add-more-btn').click(function() {
                rowIndex++;
                let newRow = $('#request-row-template').html().replace(/INDEX/g, rowIndex);
                $('#requests-container').append(newRow);

                if ($('.request-row').length > 1) {
                    $('.btn-remove-row').show();
                }

                initializeRow(rowIndex);

                $('html, body').animate({
                    scrollTop: $('.request-row:last').offset().top - 100
                }, 500);
            });

            // Remove row
            $(document).on('click', '.btn-remove-row', function() {
                const row = $(this).closest('.request-row');

                // Destroy Select2 properly
                row.find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                row.remove();

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

            // Restore form data dari session
            function restoreFormData(formDataFromSession) {
                // Hapus row pertama jika kosong
                if ($('.request-row').length === 1) {
                    const firstRow = $('.request-row').first();
                    const typeInput = firstRow.find('[name="requests[0][type]"]').val();
                    if (!typeInput) {
                        firstRow.remove();
                    }
                }

                formDataFromSession.forEach((data, index) => {
                    // Skip jika sudah ada row dengan index ini
                    if ($('[name="requests[' + index + '][type]"]').length > 0) {
                        restoreRowData(index, data);
                    } else {
                        // Add new row
                        let newRow = $('#request-row-template').html().replace(/INDEX/g, index);
                        $('#requests-container').append(newRow);
                        initializeRow(index);
                        restoreRowData(index, data);
                    }
                });

                // Update rowIndex untuk row berikutnya
                rowIndex = formDataFromSession.length - 1;

                // Show remove button jika lebih dari 1 row
                if ($('.request-row').length > 1) {
                    $('.btn-remove-row').show();
                }
            }

            // Restore data ke specific row
            function restoreRowData(index, data) {
                const row = $('[name="requests[' + index + '][type]"]').closest('.request-row');

                // Restore type
                row.find('[name="requests[' + index + '][type]"]').val(data.type || '').trigger('change');

                setTimeout(() => {
                    // Restore material name atau inventory
                    if (data.type === 'new_material') {
                        row.find('.material-name-input').val(data.material_name || '');
                    } else if (data.type === 'restock') {
                        row.find('.material-name-select').val(data.inventory_id || '').trigger('change');
                    }

                    // Restore unit
                    row.find('[name="requests[' + index + '][unit]"]').val(data.unit || '');
                    if (data.type === 'new_material') {
                        row.find('.unit-select').val(data.unit || '').trigger('change');
                    } else if (data.type === 'restock') {
                        row.find('.unit-input').val(data.unit || '');
                    }

                    // Restore quantities
                    row.find('[name="requests[' + index + '][stock_level]"]').val(data.stock_level || '');
                    row.find('[name="requests[' + index + '][required_quantity]"]').val(data
                        .required_quantity || '');

                    // Restore project dengan destroy dulu
                    const projectSelect = row.find('[name="requests[' + index + '][project_id]"]');
                    if (projectSelect.data('select2')) {
                        projectSelect.select2('destroy');
                    }
                    projectSelect.val(data.project_id || '');
                    initializeProjectSelect(projectSelect);

                    // Restore remark
                    row.find('[name="requests[' + index + '][remark]"]').val(data.remark || '');
                }, 300);
            }

            // Function khusus untuk initialize Project Select2
            function initializeProjectSelect(selectElement) {
                selectElement.select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Project',
                    allowClear: true,
                    width: '100%'
                }).on('select2:open', function() {
                    setTimeout(function() {
                        const searchField = document.querySelector('.select2-search__field');
                        if (searchField) {
                            searchField.focus();
                        }
                    }, 100);
                });
            }

            // Initialize row dengan Select2
            function initializeRow(index) {
                const row = $(`[name="requests[${index}][type]"]`).closest('.request-row');
                // Type select
                row.find('.type-select').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Type',
                    allowClear: true,
                    width: '100%'
                }).on('select2:open', function() {
                    setTimeout(function() {
                        const searchField = document.querySelector('.select2-search__field');
                        if (searchField) searchField.focus();
                    }, 100);
                });

                // Material name select (untuk restock)
                row.find('.material-name-select').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Material',
                    allowClear: true,
                    width: '100%'
                }).on('select2:open', function() {
                    setTimeout(function() {
                        const searchField = document.querySelector('.select2-search__field');
                        if (searchField) searchField.focus();
                    }, 100);
                });

                // Unit select (untuk new material)
                row.find('.unit-select').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Unit',
                    allowClear: true,
                    width: '100%'
                }).on('select2:open', function() {
                    setTimeout(function() {
                        const searchField = document.querySelector('.select2-search__field');
                        if (searchField) searchField.focus();
                    }, 100);
                });

                // Project select dengan inisialisasi khusus
                const projectSelect = row.find('.project-select');
                initializeProjectSelect(projectSelect);

                // Image preview
                row.find('input[type="file"]').on('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            console.log('Image preview:', event.target.result);
                        };
                        reader.readAsDataURL(file);
                    }
                });

                // Type select change event
                row.find('.type-select').on('change', function() {
                    toggleMaterialInput($(this));
                });

                // Material name select change event
                row.find('.material-name-select').on('change', function() {
                    updateMaterialFields($(this));
                });

                // Initialize state
                toggleMaterialInput(row.find('.type-select'));
            }

            // Toggle material input visibility
            function toggleMaterialInput(typeSelect) {
                const row = typeSelect.closest('.request-row');
                const type = typeSelect.val();

                const $materialInput = row.find('.material-name-input');
                const $materialSelect = row.find('.material-name-select');
                const $materialContainer = row.find('.material-name-select').next('.select2-container');
                const $unitInput = row.find('.unit-input');
                const $unitSelect = row.find('.unit-select');
                const $unitContainer = row.find('.unit-select').next('.select2-container');
                const $addUnitBtn = row.find('.add-unit-btn');
                const $stockLevel = row.find('.stock-level-input');
                const $requiredQty = row.find('input[name$="[required_quantity]"]');

                if (type === '') {
                    // Semua disabled
                    $materialInput.show().prop('required', false).prop('disabled', true);
                    $materialSelect.hide().addClass('d-none').prop('required', false).prop('disabled', true);
                    $materialContainer.hide();
                    $unitInput.show().prop('readonly', false).prop('disabled', true).val('');
                    $unitSelect.hide().addClass('d-none').prop('disabled', true);
                    $unitContainer.hide();
                    $addUnitBtn.hide();
                    $stockLevel.prop('readonly', false).prop('disabled', true).val('');
                    $requiredQty.prop('disabled', true).val('');
                } else if (type === 'new_material') {
                    // New material mode
                    $materialInput.show().prop('required', true).prop('disabled', false);
                    $materialSelect.hide().addClass('d-none').prop('required', false).prop('disabled', true);
                    $materialContainer.hide();
                    $unitInput.hide().prop('disabled', true);
                    $unitSelect.show().removeClass('d-none').prop('disabled', false);
                    $unitContainer.show();
                    $addUnitBtn.show();
                    $stockLevel.prop('readonly', false).prop('disabled', false);
                    $requiredQty.prop('disabled', false);
                } else if (type === 'restock') {
                    // Restock mode
                    $materialInput.hide().prop('required', false).prop('disabled', true);
                    $materialSelect.show().removeClass('d-none').prop('required', true).prop('disabled', false);
                    $materialContainer.show();
                    $unitInput.show().prop('readonly', true).prop('disabled', false);
                    $unitSelect.hide().addClass('d-none').prop('disabled', true);
                    $unitContainer.hide();
                    $addUnitBtn.hide();
                    $stockLevel.prop('readonly', true).prop('disabled', false);
                    $requiredQty.prop('disabled', false);
                    updateMaterialFields($materialSelect);
                }
            }

            // Update material fields
            function updateMaterialFields(select) {
                const row = select.closest('.request-row');
                const selectedOption = select.find(':selected');
                const $unitInput = row.find('.unit-input');
                const $stockInput = row.find('.stock-level-input');

                if (selectedOption.val()) {
                    const unit = selectedOption.data('unit') || '';
                    const stock = selectedOption.data('stock') || '';
                    $unitInput.val(unit);
                    $stockInput.val(stock);
                } else {
                    $unitInput.val('');
                    $stockInput.val('');
                }
            }

            // Initialize Select2 untuk Quick Add Department (Multiple)
            $('#addProjectModal').on('shown.bs.modal', function() {
                if (!$('#quick-add-departments').data('select2')) {
                    $('#quick-add-departments').select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Select departments',
                        allowClear: true,
                        closeOnSelect: false, // Keep dropdown open
                        dropdownParent: $('#addProjectModal'),
                        width: '100%'
                    });
                }
            });

            // Quick add project
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
                            // Restore project select dengan proper handling
                            const newOption = new Option(res.project.name, res.project.id, true,
                                true);

                            if (lastActiveRow) {
                                const projectSelect = lastActiveRow.find('.project-select');

                                // Destroy Select2 terlebih dahulu
                                if (projectSelect.data('select2')) {
                                    projectSelect.select2('destroy');
                                }

                                // Tambah option baru
                                projectSelect.append(newOption);

                                // Re-initialize Select2
                                initializeProjectSelect(projectSelect);

                                // Set value
                                projectSelect.val(res.project.id).trigger('change');
                            }

                            // Restore Select2 departments
                            $('#quick-add-departments').val(null).trigger('change');

                            $('#addProjectModal').modal('hide');
                            form[0].reset();
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add project. Please try again.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Quick add unit
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
                        if (lastActiveRow) {
                            const unitSelect = lastActiveRow.find('.unit-select');
                            const newOption = new Option(unit.name, unit.name, true, true);
                            unitSelect.append(newOption).trigger('change');
                        }

                        $('#addUnitModal').modal('hide');
                        form[0].reset();

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Unit "' + unit.name + '" added successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add unit. Please try again.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Auto-fill dari dashboard
            function autoFillFromDashboard() {
                const selectedInventory = @json($selectedInventory ?? null);
                const prefilledType = @json($prefilledType ?? null);

                if (selectedInventory && prefilledType) {
                    const firstRow = $('.request-row').first();
                    firstRow.find('[name="requests[0][type]"]').val(prefilledType).trigger('change');

                    setTimeout(() => {
                        if (prefilledType === 'restock') {
                            firstRow.find('.material-name-select').val(selectedInventory.id).trigger(
                                'change');
                            firstRow.find('[name="requests[0][remark]"]').val(
                                'Low stock alert - Restock needed').prop('readonly', true);
                        }
                    }, 500);
                }
            }

            function protectReadonlyRemark() {
                const remarkFields = $('.remark-textarea');
                remarkFields.prop('readonly', true);
                remarkFields.css({
                    'background-color': '#f8f9fa',
                    'cursor': 'not-allowed',
                    'opacity': '0.8'
                });
            }
        });
    </script>
@endpush
