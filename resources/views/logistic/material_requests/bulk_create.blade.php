@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h4 class="">Bulk Material Request</h4>
                <p class="text-muted mb-3">Use this form to create multiple material requests at once. You can add multiple
                    rows for different projects and materials.</p>
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                        data-bs-target="#quickAddProjectModal">+ Quick Add Client Project</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddInternalProject">
                        + Quick Add Internal Project
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddMaterial">
                        + Quick Add Material
                    </button>
                </div>

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
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('material_requests.bulk_store') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm" id="bulk-material-table"
                            style="min-width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%;">Project Type <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">Job Order <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Project <span class="text-muted">(auto)</span></th>
                                    <th style="width: 20%;">Material <span class="text-danger">*</span></th>
                                    <th style="width: 10%;">Quantity <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Remark</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bulk-rows">
                                @foreach (old('requests', [0 => []]) as $index => $request)
                                    <tr class="align-top">
                                        <!-- Project Type -->
                                        <td data-label="Project Type">
                                            <select name="requests[{{ $index }}][project_type]"
                                                    class="form-select project-type-select" required>
                                                <option value="">Select Type</option>
                                                <option value="client" {{ old("requests.$index.project_type") == 'client' ? 'selected' : '' }}>Client Project</option>
                                                <option value="internal" {{ old("requests.$index.project_type") == 'internal' ? 'selected' : '' }}>Internal Project</option>
                                            </select>
                                            @error("requests.$index.project_type")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        <!-- Job Order (dinamis) -->
                                        <td data-label="Job Order">
                                            <select name="requests[{{ $index }}][job_source_id]"
                                                    class="form-select select2 job-order-select"
                                                    data-client-options='@json($jobOrders)'
                                                    data-internal-options='@json($internalProjects)'
                                                    data-old-value="{{ old("requests.$index.job_source_id") }}"
                                                    required>
                                                <option value="">Select Job Order</option>
                                            </select>
                                            <input type="hidden" name="requests[{{ $index }}][job_source_type]" class="job-source-type" value="{{ old("requests.$index.job_source_type") }}">
                                            @error("requests.$index.job_source_id")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        <!-- Project Info (readonly) -->
                                        <td data-label="Project">
                                            <input type="hidden" name="requests[{{ $index }}][project_id]" class="project-id-input" value="{{ old("requests.$index.project_id") }}">
                                            <input type="hidden" name="requests[{{ $index }}][internal_project_id]" class="internal-project-id-input" value="{{ old("requests.$index.internal_project_id") }}">
                                            <input type="text" class="form-control project-name-display" readonly
                                                placeholder="Auto fill" value="{{ old("requests.$index.project_display") }}">
                                            @error("requests.$index.project_id")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        <!-- Material -->
                                        <td data-label="Material">
                                            <select name="requests[{{ $index }}][inventory_id]"
                                                class="form-select select2 material-select" required>
                                                <option value="">Select Material</option>
                                                @foreach ($inventories as $inventory)
                                                    <option value="{{ $inventory->id }}"
                                                        data-unit="{{ $inventory->unit }}"
                                                        data-stock="{{ $inventory->quantity }}"
                                                        {{ old("requests.$index.inventory_id") == $inventory->id ? 'selected' : '' }}>
                                                        {{ $inventory->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="available-qty-text form-text d-none"></div>
                                            @error("requests.$index.inventory_id")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        <!-- Quantity -->
                                        <td data-label="Quantity">
                                            <div class="input-group">
                                                <input type="number" name="requests[{{ $index }}][qty]"
                                                    class="form-control @error("requests.$index.qty") is-invalid @enderror"
                                                    step="any" value="{{ old("requests.$index.qty") }}" required>
                                                <span class="input-group-text unit-label">unit</span>
                                            </div>
                                            @error("requests.$index.qty")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        <!-- Remark -->
                                        <td data-label="Remark">
                                            <textarea name="requests[{{ $index }}][remark]" class="form-control" rows="1">{{ old("requests.$index.remark") }}</textarea>
                                            @error("requests.$index.remark")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        <!-- Action -->
                                        <td data-label="Action">
                                            <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn btn-outline-primary" id="add-row">+ Add Row</button>
                        <button type="submit" class="btn btn-success" id="bulk-submit-btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                aria-hidden="true"></span>
                            Submit All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL QUICK ADD MATERIAL ========== -->
    <div class="modal fade" id="confirmAddMaterialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Add Material!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <b>Please make sure this material does not already exist in the inventory table.</b><br>
                    <span class="text-danger">Use this feature only if the material is truly not available and is
                        urgently needed.<br> Adding duplicate materials will cause data inconsistency!</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmAddMaterial">Yes, I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addMaterialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="quickAddMaterialForm" method="POST" action="{{ route('inventories.store.quick') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header flex-column align-items-start pb-1 pt-3">
                        <h5 class="modal-title w-100 mb-2">Quick Add Material</h5>
                        <div class="w-100 mb-2">
                            <small class="text-muted d-block" style="font-size: 0.92em;">
                                <i class="bi bi-search"></i> Search Material Before Adding <span class="fst-italic">(optional)</span>
                            </small>
                            <input type="text" id="search-material-autocomplete"
                                class="form-control form-control-sm mt-1"
                                placeholder="Type material name to search...">
                            <div id="search-material-result" class="form-text mt-1 mb-0"></div>
                        </div>
                        <button type="button" class="btn-close position-absolute end-0 top-0 m-3"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <label>Material Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>

                        <label class="mt-2">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="quantity" class="form-control" required>

                        <label class="mt-2">Unit <span class="text-danger">*</span></label>
                        <select name="unit" id="unit-select-modal" class="form-select select2" required>
                            <option value="">Select Unit</option>
                            @foreach ($units ?? [] as $unit)
                                <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>

                        <label class="mt-2">Remark (optional)</label>
                        <textarea name="remark" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100">Add Material</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== MODAL QUICK ADD CLIENT PROJECT ========== -->
    <div class="modal fade" id="quickAddProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="quickAddProjectForm" method="POST" action="{{ route('projects.store.quick') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Add Client Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="project-error" class="alert alert-danger d-none"></div>
                        <div class="mb-3">
                            <label for="project_name" class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_name" name="name" required
                                placeholder="Enter project name">
                        </div>
                        <div class="mb-3">
                            <label for="project_qty" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="project_qty" name="qty" required
                                min="1" placeholder="Enter quantity">
                        </div>
                        <div class="mb-3">
                            <label for="project_departments" class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department_ids[]" id="project_departments" class="form-select" multiple required>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">You can select multiple departments</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Project</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== MODAL QUICK ADD INTERNAL PROJECT ========== -->
    <div class="modal fade" id="addInternalProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="quickAddInternalProjectForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Internal Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="internal-project-error" class="alert alert-danger d-none"></div>

                        <!-- Project Type -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">Project Type <span class="text-danger">*</span></label>
                            <select name="project" id="modal_project_type" class="form-select" required>
                                <option value="">Select Project Type</option>
                                <option value="Office">Office</option>
                                <option value="Machine">Machine</option>
                                <option value="Testing">Testing</option>
                                <option value="Facilities">Facilities</option>
                            </select>
                        </div>

                        <!-- Department Section -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">Department <span class="text-danger">*</span></label>
                            <!-- Tampilan untuk non-Testing: teks readonly PT DCM -->
                            <div id="dept-static-display" class="form-control bg-light">
                                PT DCM
                            </div>
                            <!-- Tampilan untuk Testing: dropdown select (awalnya hidden) -->
                            <div id="dept-dropdown-wrapper" style="display: none;">
                                <select id="dept-dropdown-select" class="form-select select2-modal">
                                    <option value="">Select Department</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Hidden field yang akan dikirim sebagai department_id -->
                            <input type="hidden" name="department_id" id="final_department_id" value="{{ $defaultPtDcmDepartmentId ?? '' }}">
                        </div>

                        <!-- Job -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">Job <span class="text-danger">*</span></label>
                            <input type="text" name="job" class="form-control" placeholder="Enter job name" maxlength="200" required>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Enter description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveInternalProject">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                            Save Project
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .select2-container .select2-selection--single {
            height: calc(2.375rem + 2px);
            padding: 0.375rem 0.75rem;
        }
        .select2-selection__rendered {
            line-height: 1.5;
        }
        .unit-label {
            min-width: 50px;
        }
        @media (max-width: 576px) {
            #addMaterialModal .modal-dialog {
                max-width: 98vw;
                margin: 0.5rem auto;
            }
            #addMaterialModal .modal-content {
                padding: 0.5rem;
            }
            #addMaterialModal .modal-header,
            #addMaterialModal .modal-body,
            #addMaterialModal .modal-footer {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }
        @media (max-width: 992px) {
            .table-responsive table,
            .table-responsive thead,
            .table-responsive tbody,
            .table-responsive tr,
            .table-responsive th,
            .table-responsive td {
                display: block !important;
                width: 100% !important;
            }
            .table-responsive thead {
                display: none !important;
            }
            .table-responsive tr {
                margin-bottom: 1.5rem;
                border-bottom: 2px solid #dee2e6;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            }
            .table-responsive td {
                position: relative;
                padding-left: 120px;
                min-height: 40px;
                border: none;
                border-bottom: 1px solid #dee2e6;
                box-sizing: border-box;
                word-break: break-word;
            }
            .table-responsive td:before {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                width: 100px;
                white-space: normal;
                font-weight: 600;
                color: #888;
                content: attr(data-label);
                box-sizing: border-box;
                text-align: left;
            }
            .table-responsive td:last-child {
                border-bottom: 2px solid #dee2e6;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        function initSelect2(row) {
            row.find('.select2').select2({
                width: '100%',
                theme: 'bootstrap-5',
                dropdownAutoWidth: true,
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')?.focus();
                }, 100);
            });

            row.find('.material-select').on('change', function() {
                const unit = $(this).find(':selected').data('unit') || 'unit';
                row.find('.unit-label').text(unit);
            });
        }

        $(document).ready(function() {
            // Initialize first row
            initSelect2($('#bulk-rows tr').first());

            // Function to render job order options based on project type
            function renderJobOrderOptions($select, type) {
                const clientOptions = JSON.parse($select.attr('data-client-options') || '[]');
                const internalOptions = JSON.parse($select.attr('data-internal-options') || '[]');
                const oldValue = $select.data('old-value');
                let html = '<option value="">Select Job Order</option>';

                if (type === 'client') {
                    clientOptions.forEach(jo => {
                        const projectName = jo.project ? jo.project.name : '';
                        html += `<option value="${jo.id}" data-project-id="${jo.project_id}" data-project-name="${projectName}">${jo.name}</option>`;
                    });
                } else if (type === 'internal') {
                    internalOptions.forEach(ip => {
                        html += `<option value="${ip.id}" data-internal-id="${ip.id}" data-project="${ip.project}">${ip.job}</option>`;
                    });
                }
                $select.html(html);
                if (oldValue) {
                    $select.val(oldValue).trigger('change');
                }
            }

            // When project type changes
            $(document).on('change', '.project-type-select', function() {
                const $row = $(this).closest('tr');
                const type = $(this).val();
                const $jobSelect = $row.find('.job-order-select');
                const $sourceType = $row.find('.job-source-type');

                // Clear hidden inputs
                $row.find('.project-id-input, .internal-project-id-input, .project-name-display').val('');
                $sourceType.val(type);

                renderJobOrderOptions($jobSelect, type);
                $jobSelect.trigger('change.select2');
            });

            // When job order is selected
            $(document).on('change', '.job-order-select', function() {
                const $row = $(this).closest('tr');
                const type = $row.find('.project-type-select').val();
                const selected = $(this).find(':selected');

                if (type === 'client') {
                    const projectId = selected.data('project-id');
                    const projectName = selected.data('project-name');
                    $row.find('.project-id-input').val(projectId || '');
                    $row.find('.internal-project-id-input').val('');
                    $row.find('.project-name-display').val(projectName || '');
                } else if (type === 'internal') {
                    const internalId = selected.data('internal-id');
                    const project = selected.data('project');
                    $row.find('.internal-project-id-input').val(internalId || '');
                    $row.find('.project-id-input').val('');
                    $row.find('.project-name-display').val(project || '');
                }
            });

            // Restore old values on page load
            $('.project-type-select').each(function() {
                const $row = $(this).closest('tr');
                const type = $(this).val();
                if (type) {
                    const $jobSelect = $row.find('.job-order-select');
                    renderJobOrderOptions($jobSelect, type);
                    $jobSelect.trigger('change');
                }
            });

            // Add row functionality
            $('#add-row').click(function() {
                let lastRow = $('#bulk-rows tr').last();
                let rowCount = $('#bulk-rows tr').length;

                // Destroy Select2 on last row before cloning
                lastRow.find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        try {
                            $(this).select2('destroy');
                        } catch (e) {
                            console.log('Select2 destroy error:', e);
                        }
                    }
                });

                // Clone without events and data
                let newRow = lastRow.clone(false, false);

                // Remove Select2 artifacts from cloned row
                newRow.find('.select2-container').remove();
                newRow.find('select').removeClass('select2-hidden-accessible');

                // Update names and reset values
                newRow.find('select, input, textarea').each(function() {
                    let $elem = $(this);
                    let name = $elem.attr('name');

                    if (name) {
                        // Update index in name attribute
                        let newName = name.replace(/\[\d+\]/, '[' + rowCount + ']');
                        $elem.attr('name', newName);
                    }

                    // Reset values
                    if ($elem.is('select')) {
                        if ($elem.hasClass('project-type-select')) {
                            $elem.val('');
                        } else {
                            $elem.val('');
                        }
                    } else if ($elem.hasClass('project-id-input') || $elem.hasClass('internal-project-id-input') || $elem.hasClass('project-name-display') || $elem.hasClass('job-source-type')) {
                        $elem.val('');
                    } else if ($elem.is('input[type="number"]') || $elem.is('textarea')) {
                        $elem.val('').removeClass('is-invalid');
                    }
                });

                // Remove error messages
                newRow.find('.text-danger').remove();

                // Reset unit label
                newRow.find('.unit-label').text('unit');

                // Reset available qty display
                newRow.find('.available-qty-text').addClass('d-none').removeClass('text-danger text-warning').text('');

                // Append new row
                $('#bulk-rows').append(newRow);

                // Reinitialize Select2 for both rows
                setTimeout(function() {
                    try {
                        initSelect2(lastRow);
                        initSelect2(newRow);
                    } catch (e) {
                        console.log('Select2 init error:', e);
                    }
                }, 150);
            });

            // Remove row
            $(document).on('click', '.remove-row', function() {
                if ($('#bulk-rows tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            // Update unit label dynamically when material is selected
            $(document).on('change', '.material-select', function() {
                const unit = $(this).find(':selected').data('unit') || 'unit';
                $(this).closest('tr').find('.unit-label').text(unit);
            });
            $('.material-select').trigger('change');

            // Quick Add Internal Project modal logic
            $('#btnAddInternalProject').on('click', function() {
                $('#addInternalProjectModal').modal('show');
            });

            // Department logic in internal project modal
            function updateDepartmentField() {
                const projectType = $('#modal_project_type').val();
                const isTesting = projectType === 'Testing';
                const $staticDisplay = $('#dept-static-display');
                const $dropdownWrapper = $('#dept-dropdown-wrapper');
                const $finalHidden = $('#final_department_id');

                if (isTesting) {
                    $staticDisplay.hide();
                    $dropdownWrapper.show();
                    const selectedDeptId = $('#dept-dropdown-select').val();
                    $finalHidden.val(selectedDeptId || '');
                } else {
                    $staticDisplay.show();
                    $dropdownWrapper.hide();
                    $finalHidden.val('{{ $defaultPtDcmDepartmentId ?? '' }}');
                    $('#dept-dropdown-select').val('').trigger('change');
                }
            }

            $('#modal_project_type').on('change', updateDepartmentField);

            $('#dept-dropdown-select').on('change', function() {
                if ($('#modal_project_type').val() === 'Testing') {
                    $('#final_department_id').val($(this).val());
                }
            });

            $('#addInternalProjectModal').on('shown.bs.modal', function() {
                $('#quickAddInternalProjectForm')[0].reset();
                $('#internal-project-error').addClass('d-none').html('');
                $('#final_department_id').val('{{ $defaultPtDcmDepartmentId ?? '' }}');
                $('#modal_project_type').val('').trigger('change');

                // Initialize Select2 for department dropdown inside modal
                $('#dept-dropdown-select').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Department',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addInternalProjectModal')
                });

                updateDepartmentField();
            });

            // Submit quick add internal project
            $('#quickAddInternalProjectForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = $('#btnSaveInternalProject');
                const spinner = submitBtn.find('.spinner-border');
                const errorDiv = $('#internal-project-error');

                if (!$('#final_department_id').val()) {
                    errorDiv.html('Department is required.').removeClass('d-none');
                    return;
                }

                submitBtn.prop('disabled', true);
                spinner.removeClass('d-none');
                errorDiv.addClass('d-none').html('');

                $.ajax({
                    url: '{{ route("internal_projects.quick") }}',
                    method: 'POST',
                    data: form.serialize(),
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success && response.internal_project) {
                            const ip = response.internal_project;
                            // Add to internal projects data (if needed, but we already have data)
                            // Refresh the internal options in all rows
                            $('.job-order-select').each(function() {
                                const $select = $(this);
                                const currentType = $(this).closest('tr').find('.project-type-select').val();
                                if (currentType === 'internal') {
                                    // Add new option
                                    const newOption = new Option(ip.job, ip.id, false, false);
                                    $(newOption).attr('data-internal-id', ip.id).attr('data-project', ip.project);
                                    $select.append(newOption);
                                    // Optionally select it in the last row
                                    if ($(this).closest('tr').is($('#bulk-rows tr:last'))) {
                                        $select.val(ip.id).trigger('change');
                                    }
                                }
                            });
                            $('#addInternalProjectModal').modal('hide');
                            form[0].reset();
                            Swal.fire('Success', response.message, 'success');
                        }
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let message = xhr.responseJSON?.message || 'Failed to add internal project.';
                        if (errors) {
                            let errorHtml = '<ul class="mb-0">';
                            for (let key in errors) {
                                errorHtml += '<li>' + errors[key][0] + '</li>';
                            }
                            errorHtml += '</ul>';
                            errorDiv.html(errorHtml).removeClass('d-none');
                        } else {
                            errorDiv.html(message).removeClass('d-none');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // Quick Add Material Button with Confirmation
            $('#btnAddMaterial').on('click', function(e) {
                e.preventDefault();
                $('#confirmAddMaterialModal').modal('show');
            });

            $('#btnConfirmAddMaterial').on('click', function() {
                $('#confirmAddMaterialModal').modal('hide');
                setTimeout(() => $('#addMaterialModal').modal('show'), 360);
            });

            function initializeUnitSelect2() {
                $('#unit-select-modal').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Unit',
                    allowClear: true,
                    dropdownParent: $('#addMaterialModal')
                }).on('select2:open', function() {
                    setTimeout(() => {
                        document.querySelector('.select2-container--open .select2-search__field')?.focus();
                    }, 100);
                });
            }

            $('#addMaterialModal').on('shown.bs.modal', function() {
                if ($('#unit-select-modal').data('select2')) {
                    $('#unit-select-modal').select2('destroy');
                }
                initializeUnitSelect2();
            });

            $('#quickAddMaterialForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success && res.material) {
                            let newOption = new Option(res.material.name, res.material.id, true, true);
                            $('select[name*="[inventory_id]"]').each(function() {
                                // Add to all material selects
                                let exists = $(this).find('option[value="' + res.material.id + '"]').length;
                                if (!exists) {
                                    $(this).append(newOption.clone());
                                }
                            });
                            // Optionally select in the last row
                            $('select[name*="[inventory_id]"]').last().val(res.material.id).trigger('change');
                            $('#addMaterialModal').modal('hide');
                            form[0].reset();
                            Swal.fire('Success', 'Material added successfully!', 'success');
                        } else {
                            Swal.fire('Error', 'Failed to add material. Please try again.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || 'Failed to add material. Please try again.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Available Qty per-row
            $(document).on('change', '.material-select', function() {
                const selected = $(this).find(':selected');
                const selectedUnit = selected.data('unit');
                const selectedStock = selected.data('stock');
                const $qtyDiv = $(this).closest('td').find('.available-qty-text');
                $qtyDiv.removeClass('d-none text-danger text-warning');
                if (selected.val() && selectedStock !== undefined) {
                    let colorClass = '';
                    if (selectedStock == 0) {
                        colorClass = 'text-danger';
                    } else if (selectedStock < 3) {
                        colorClass = 'text-warning';
                    }
                    $qtyDiv
                        .text(`Available Qty: ${selectedStock} ${selectedUnit || ''}`)
                        .addClass(colorClass);
                } else {
                    $qtyDiv.addClass('d-none').text('');
                }
            });
            $('.material-select').trigger('change');

            // Search material autocomplete
            $('#search-material-autocomplete').on('input', function() {
                const keyword = $(this).val().trim();
                const $result = $('#search-material-result');
                if (keyword.length < 2) { $result.html(''); return; }
                $.ajax({
                    url: "{{ route('inventories.json') }}",
                    data: { q: keyword },
                    success: function(data) {
                        const filtered = data.filter(item =>
                            item.name.toLowerCase().includes(keyword.toLowerCase())
                        );
                        if (filtered.length > 0) {
                            $result.html(
                                '<b>Similar material(s) found:</b><ul class="mb-0">' +
                                filtered.map(item => `<li>${item.name}</li>`).join('') +
                                '</ul><span class="text-danger">Please make sure you are not adding a duplicate material!</span>'
                            );
                        } else {
                            $result.html(
                                '<span class="text-success">No similar material found. You can proceed to add this material.</span>'
                            );
                        }
                    },
                    error: function() {
                        $result.html('<span class="text-danger">Failed to search material.</span>');
                    }
                });
            });

            // Quick Add Project (Client)
            $('#quickAddProjectForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let errorDiv = $('#project-error');
                errorDiv.hide().text('');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success && response.project) {
                            // Refresh the page or add to client options
                            // Simple: reload the page
                            window.location.reload();
                        } else {
                            errorDiv.html(response.message || 'Failed to add project.').show();
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || 'Failed to add project. Please try again.';
                        errorDiv.html(msg).show();
                    }
                });
            });

            $('#quickAddProjectModal').on('shown.bs.modal', function() {
                if (!$('#project_departments').data('select2')) {
                    $('#project_departments').select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Select departments',
                        allowClear: true,
                        closeOnSelect: false,
                        dropdownParent: $('#quickAddProjectModal')
                    });
                }
            });

            // Handle form submission for bulk create (spinner)
            const form = $('form[action="{{ route('material_requests.bulk_store') }}"]');
            const submitBtn = $('#bulk-submit-btn');
            const spinner = submitBtn.find('.spinner-border');

            if (form.length && submitBtn.length && spinner.length) {
                form.on('submit', function() {
                    submitBtn.prop('disabled', true);
                    spinner.removeClass('d-none');
                });
            }
        });
    </script>
@endpush