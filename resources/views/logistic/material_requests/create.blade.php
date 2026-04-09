@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0" style="font-size:1.3rem;">Create Material Request</h2>
                <hr>

                <!-- ========== ALERT MESSAGES ========== -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {!! session('success') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('material_requests.store') }}" id="materialRequestForm">
                    @csrf

                    <!-- PROJECT TYPE RADIO -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="fw-bold mb-2">Project Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="project_type"
                                        id="projectTypeClient" value="client"
                                        {{ old('project_type', 'client') == 'client' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="projectTypeClient">Client Project</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="project_type"
                                        id="projectTypeInternal" value="internal"
                                        {{ old('project_type') == 'internal' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="projectTypeInternal">Internal Project</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- JOB ORDER & MATERIAL -->
                    <div class="row">
                        <!-- Kolom Kiri: Job Order + Project Info + Add Internal Button -->
                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label id="jobOrderLabel">Job Order <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-primary d-none"
                                    id="btnAddInternalProject">
                                    <i class="bi bi-plus-circle"></i> Add Internal Project
                                </button>
                            </div>
                            <select name="job_order_id" id="jobOrderSelect" class="form-select select2"
                                data-placeholder="Select Job Order" required>
                                <option value="">Select Job Order</option>
                            </select>
                            @error('job_order_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                            <!-- PROJECT INFO DISPLAY -->
                            <div id="projectInfoDisplay" class="mt-1 p-2 bg-light rounded d-none">
                                <small class="text-muted d-block mb-1">Project:</small>
                                <strong id="projectInfoText" class="text-primary"></strong>
                                <input type="hidden" name="project_id" id="hiddenProjectId"
                                    value="{{ old('project_id') }}">
                                <input type="hidden" name="internal_project_id" id="hiddenInternalProjectId"
                                    value="{{ old('internal_project_id') }}">
                            </div>
                        </div>

                        <!-- Inventory Radio Type -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="fw-bold mb-2">Material Source <span class="text-danger">*</span></label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="inventory_source"
                                            id="sourceStock" value="stock"
                                            {{ old('inventory_source', 'stock') == 'stock' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold text-primary" for="sourceStock">
                                            <i class="bi bi-boxes me-1"></i>Inventory Stock
                                        </label>
                                        <div class="form-text text-muted">From batch inventory</div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="inventory_source"
                                            id="sourceIncoming" value="incoming"
                                            {{ old('inventory_source') == 'incoming' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold text-success" for="sourceIncoming">
                                            <i class="bi bi-box-arrow-in-down me-1"></i>Inventory Incoming
                                        </label>
                                        <div class="form-text text-muted">From Lark staging</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Material -->
                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label id="materialLabel">Material <span class="text-danger">*</span></label>
                            </div>

                            {{-- Stock select --}}
                            <div id="stockMaterialWrapper">
                                <select name="inventory_id" id="inventory_id" class="form-select select2"
                                    data-placeholder="Select Material (Stock)" required>
                                    <option value="">Select Material</option>
                                    @foreach ($inventories as $inv)
                                        <option value="{{ $inv->id }}" data-unit="{{ $inv->unit }}"
                                            data-stock="{{ $inv->quantity }}"
                                            {{ old('inventory_id', $selectedMaterial?->id) == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="available-qty" class="form-text d-none"></div>
                                @error('inventory_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Incoming (staging) select --}}
                            <div id="incomingMaterialWrapper" style="display:none;">
                                <select name="staging_inventory_id" id="staging_inventory_id"
                                    class="form-select select2"
                                    data-placeholder="Select Incoming Material" disabled>
                                    <option value="">Select Incoming Material</option>
                                    @foreach ($stagingInventories as $si)
                                        <option value="{{ $si->id }}" data-unit="{{ $si->unit }}"
                                            data-qty="{{ $si->quantity }}"
                                            data-received="{{ $si->received_qty ?? 0 }}"
                                            {{ old('staging_inventory_id') == $si->id ? 'selected' : '' }}>
                                            {{ $si->name }}{{ $si->material_code ? ' (' . $si->material_code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="available-incoming-qty" class="form-text d-none"></div>
                                @error('staging_inventory_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <input type="hidden" name="inventory_source" id="hiddenInventorySource"
                                value="{{ old('inventory_source', 'stock') }}">
                        </div>
                    </div>

                    <!-- QUANTITY & REMARK -->
                    <div class="row mt-2">
                        <div class="col-lg-6 mb-3">
                            <label>Requested Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="qty"
                                    class="form-control @error('qty') is-invalid @enderror" step="any" required
                                    value="{{ old('qty') }}">
                                <span class="input-group-text unit-label">unit</span>
                            </div>
                            @error('qty')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Remark (Optional)</label>
                            <textarea name="remark" class="form-control">{{ old('remark') }}</textarea>
                            @error('remark')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <a href="{{ route('material_requests.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="submit-request-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        Submit Request
                    </button>
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
                    <span class="text-danger">Use this feature only if the material is truly not available and is urgently
                        needed.<br>
                        Adding duplicate materials will cause data inconsistency!</span>
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
                                <i class="bi bi-search"></i>
                                Search Material Before Adding <span class="fst-italic">(optional)</span>
                            </small>
                            <input type="text" id="search-material-autocomplete"
                                class="form-control form-control-sm mt-1" placeholder="Type material name to search...">
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
                            <input type="hidden" name="department_id" id="final_department_id"
                                value="{{ $defaultPtDcmDepartmentId ?? '' }}">
                        </div>

                        <!-- Job -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">Job <span class="text-danger">*</span></label>
                            <input type="text" name="job" class="form-control" placeholder="Enter job name"
                                maxlength="200" required>
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

    <!-- MODAL QUICK ADD PROJECT (CLIENT) -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="quickAddProjectForm" method="POST" action="{{ route('projects.store.quick') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Add Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="project-error" class="alert alert-danger d-none"></div>
                        <div class="mb-3">
                            <label for="project_name" class="form-label">Project Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_name" name="name" required
                                placeholder="Enter project name">
                        </div>
                        <div class="mb-3">
                            <label for="project_qty" class="form-label">Quantity <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="project_qty" name="qty" required
                                min="1" placeholder="Enter quantity">
                        </div>
                        <div class="mb-3">
                            <label for="project_departments" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select name="department_ids[]" id="project_departments" class="form-select" multiple
                                required>
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
@endsection

@push('styles')
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .form-check-label { cursor: pointer; }

        .source-radio-active {
            background: rgba(13,110,253,.06);
            border-radius: 0.5rem;
            padding: 0.4rem 0.7rem;
        }
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
        $(document).ready(function() {
            // ========== INISIALISASI SELECT2 ==========
            function initSelect2(selector) {
                $(selector).select2({
                    width: '100%',
                    allowClear: true,
                    theme: 'bootstrap-5',
                    placeholder: function() {
                        return $(this).data('placeholder') || 'Select an option';
                    }
                }).on('select2:open', function() {
                    setTimeout(() => {
                        document.querySelector('.select2-container--open .select2-search__field')
                            ?.focus();
                    }, 100);
                });
            }
            initSelect2('.select2');

            // ========== DATA DARI BACKEND ==========
            const clientJobOrders = @json($jobOrders);
            const internalProjects = @json($internalProjects);
            const departments = @json($departments);
            const defaultPtDcmDepartmentId = '{{ $defaultPtDcmDepartmentId ?? '' }}';

            // ========== RENDER OPSI BERDASARKAN TIPE PROYEK ==========
            function renderClientOptions() {
                let html = '<option value="">Select Job Order</option>';
                clientJobOrders.forEach(jo => {
                    const projectName = jo.project ? jo.project.name : '';
                    html +=
                        `<option value="${jo.id}" data-project-id="${jo.project_id}" data-project-name="${projectName}">${jo.name}</option>`;
                });
                $('#jobOrderSelect').html(html);
                $('#jobOrderLabel').text('Job Order (Client)');
            }

            function renderInternalOptions() {
                let html = '<option value="">Select Job Order</option>';
                internalProjects.forEach(ip => {
                    // PERBAIKAN: gunakan ip.id sebagai value, bukan ip.job
                    html +=
                        `<option value="${ip.id}" data-internal-id="${ip.id}" data-project="${ip.project}">${ip.job}</option>`;
                });
                $('#jobOrderSelect').html(html);
                $('#jobOrderLabel').text('Job Order (Internal)');
            }

            function toggleProjectType() {
                const isClient = $('#projectTypeClient').is(':checked');
                if (isClient) {
                    renderClientOptions();
                    $('#hiddenProjectId, #hiddenInternalProjectId').val('');
                    $('#projectInfoDisplay').addClass('d-none');
                    $('#btnAddInternalProject').addClass('d-none');
                } else {
                    renderInternalOptions();
                    $('#hiddenProjectId, #hiddenInternalProjectId').val('');
                    $('#projectInfoDisplay').addClass('d-none');
                    $('#btnAddInternalProject').removeClass('d-none');
                }
                $('#jobOrderSelect').trigger('change.select2');
            }

            $('input[name="project_type"]').on('change', toggleProjectType);
            toggleProjectType();

            // ========== HANDLE SAAT JOB ORDER DIPILIH ==========
            $('#jobOrderSelect').on('change', function() {
                const isClient = $('#projectTypeClient').is(':checked');
                const selected = $(this).find(':selected');
                const $infoDisplay = $('#projectInfoDisplay');
                const $infoText = $('#projectInfoText');

                if (!selected.val()) {
                    $infoDisplay.addClass('d-none');
                    $('#hiddenProjectId, #hiddenInternalProjectId').val('');
                    return;
                }

                if (isClient) {
                    const projectId = selected.data('project-id');
                    const projectName = selected.data('project-name');
                    if (projectId && projectName) {
                        $('#hiddenProjectId').val(projectId);
                        $('#hiddenInternalProjectId').val('');
                        $infoText.text(projectName);
                        $infoDisplay.removeClass('d-none');
                    } else {
                        $infoDisplay.addClass('d-none');
                    }
                } else {
                    const internalId = selected.data('internal-id');
                    const project = selected.data('project');
                    if (internalId && project) {
                        $('#hiddenInternalProjectId').val(internalId);
                        $('#hiddenProjectId').val('');
                        $infoText.text(project);
                        $infoDisplay.removeClass('d-none');
                    } else {
                        $infoDisplay.addClass('d-none');
                    }
                }
            });

            // ========== TOMBOL ADD INTERNAL PROJECT ==========
            $('#btnAddInternalProject').on('click', function() {
                $('#addInternalProjectModal').modal('show');
            });

            // ========== LOGIKA DEPARTMENT DI MODAL ==========
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
                    $finalHidden.val(defaultPtDcmDepartmentId);
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
                $('#final_department_id').val(defaultPtDcmDepartmentId);
                $('#modal_project_type').val('').trigger('change');

                $('#dept-dropdown-select').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Department',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addInternalProjectModal')
                });

                updateDepartmentField();
            });

            // ========== SUBMIT FORM QUICK ADD INTERNAL PROJECT ==========
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
                    url: '{{ route('internal_projects.quick') }}',
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.internal_project) {
                            const ip = response.internal_project;
                            // PERBAIKAN: value = ip.id, bukan ip.job
                            const newOption = new Option(ip.job, ip.id, true, true);
                            $(newOption).attr('data-internal-id', ip.id).attr('data-project', ip
                                .project);
                            $('#jobOrderSelect').append(newOption).val(ip.id).trigger('change');
                            internalProjects.push({
                                id: ip.id,
                                project: ip.project,
                                job: ip.job
                            });
                            $('#addInternalProjectModal').modal('hide');
                            form[0].reset();
                            Swal.fire('Success', response.message, 'success');
                        }
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let message = xhr.responseJSON?.message ||
                            'Failed to add internal project.';
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

            // ========== UNIT LABEL & AVAILABLE STOCK (Inventory Stock) ==========
            $('#inventory_id').on('change', function() {
                const selected = $(this).find(':selected');
                const unit = selected.data('unit') || 'unit';
                const stock = selected.data('stock');
                $('.unit-label').text(unit);

                const $avail = $('#available-qty');
                $avail.removeClass('d-none text-danger text-warning text-success');

                if (selected.val() && stock !== undefined) {
                    // Notification for selected material (stock)
                    let stockClass = stock == 0 ? 'text-danger' : (stock < 3 ? 'text-warning' : 'text-success');
                    let stockIcon  = stock == 0 ? '⚠️' : (stock < 3 ? '⚠️' : '✅');
                    $avail.html(`${stockIcon} Available Stock: <b>${stock} ${unit}</b>`).addClass(stockClass).removeClass('d-none');

                    // Toast notification
                    if (selected.val()) {
                        const matName = selected.text().trim();
                        let toastType, toastMsg;
                        if (stock == 0) {
                            toastType = 'warning';
                            toastMsg = `<b>${matName}</b> is out of stock! Available: 0 ${unit}`;
                        } else if (stock < 3) {
                            toastType = 'warning';
                            toastMsg = `<b>${matName}</b> selected — Low stock: <b>${stock} ${unit}</b>`;
                        } else {
                            toastType = 'success';
                            toastMsg = `<b>${matName}</b> selected — Available: <b>${stock} ${unit}</b>`;
                        }
                        showMaterialNotif(toastMsg, toastType);
                    }
                } else {
                    $avail.addClass('d-none').text('');
                }
            }).trigger('change');

            // ========== AVAILABLE QTY (Inventory Incoming) ==========
            $('#staging_inventory_id').on('change', function() {
                const selected = $(this).find(':selected');
                const unit      = selected.data('unit') || 'unit';
                const qty       = parseFloat(selected.data('qty') || 0);
                const received  = parseFloat(selected.data('received') || 0);
                const total     = qty + received;
                $('.unit-label').text(unit);

                const $avail = $('#available-incoming-qty');
                $avail.removeClass('d-none text-danger text-warning text-success');

                if (selected.val()) {
                    let cls  = total == 0 ? 'text-danger' : (total < 3 ? 'text-warning' : 'text-success');
                    let icon = total == 0 ? '⚠️' : (total < 3 ? '⚠️' : '✅');
                    $avail.html(`${icon} Incoming Qty: <b>${total} ${unit}</b>`).addClass(cls).removeClass('d-none');

                    const matName = selected.text().trim();
                    let toastType, toastMsg;
                    if (total == 0) {
                        toastType = 'warning';
                        toastMsg  = `<b>${matName}</b> — No incoming stock available (0 ${unit})`;
                    } else if (total < 3) {
                        toastType = 'warning';
                        toastMsg  = `<b>${matName}</b> selected (Incoming) — Low qty: <b>${total} ${unit}</b>`;
                    } else {
                        toastType = 'success';
                        toastMsg  = `<b>${matName}</b> selected (Incoming) — Qty: <b>${total} ${unit}</b>`;
                    }
                    showMaterialNotif(toastMsg, toastType);
                } else {
                    $avail.addClass('d-none').text('');
                    $('.unit-label').text('unit');
                }
            });

            // ========== MATERIAL SOURCE RADIO TOGGLE ==========
            function toggleMaterialSource() {
                const source = $('input[name="inventory_source"]:checked').val();
                $('#hiddenInventorySource').val(source);

                if (source === 'stock') {
                    $('#stockMaterialWrapper').show();
                    $('#incomingMaterialWrapper').hide();
                    $('#inventory_id').prop('disabled', false).prop('required', true);
                    $('#staging_inventory_id').prop('disabled', true).prop('required', false);
                    // trigger change to show available qty
                    if ($('#inventory_id').val()) {
                        $('#inventory_id').trigger('change');
                    } else {
                        $('#available-qty').addClass('d-none');
                        $('.unit-label').text('unit');
                    }
                    $('#available-incoming-qty').addClass('d-none');
                } else {
                    $('#stockMaterialWrapper').hide();
                    $('#incomingMaterialWrapper').show();
                    $('#inventory_id').prop('disabled', true).prop('required', false);
                    $('#staging_inventory_id').prop('disabled', false).prop('required', true);
                    if ($('#staging_inventory_id').val()) {
                        $('#staging_inventory_id').trigger('change');
                    } else {
                        $('#available-incoming-qty').addClass('d-none');
                        $('.unit-label').text('unit');
                    }
                    $('#available-qty').addClass('d-none');
                }
            }

            $('input[name="inventory_source"]').on('change', toggleMaterialSource);
            toggleMaterialSource(); // init on page load

            // ========== TOAST NOTIFICATION HELPER ==========
            function showMaterialNotif(message, type) {
                // Remove existing notif
                $('#materialNotifToast').remove();

                const colors = {
                    success: '#198754',
                    warning: '#fd7e14',
                    info:    '#0dcaf0',
                    danger:  '#dc3545',
                };
                const color = colors[type] || '#198754';

                const toast = $(`
                    <div id="materialNotifToast" style="
                        position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
                        background: ${color}; color: #fff;
                        padding: 0.75rem 1.2rem; border-radius: 0.5rem;
                        box-shadow: 0 4px 16px rgba(0,0,0,0.18);
                        max-width: 320px; font-size: 0.92rem;
                        display: flex; align-items: flex-start; gap: 0.5rem;
                        animation: fadeInUp 0.3s ease;
                    ">
                        <span style="flex:1">${message}</span>
                        <button onclick="$('#materialNotifToast').remove()" style="background:none;border:none;color:#fff;font-size:1rem;cursor:pointer;padding:0;line-height:1;">&times;</button>
                    </div>
                `);
                $('body').append(toast);

                // Play notification sound if available
                try {
                    const audio = new Audio('/sounds/notif.mp3');
                    audio.volume = 0.3;
                    audio.play().catch(() => {});
                } catch(e) {}

                setTimeout(() => { $('#materialNotifToast').fadeOut(400, function(){ $(this).remove(); }); }, 4000);
            }

            // ========== SUBMIT BUTTON SPINNER ==========
            $('#materialRequestForm').on('submit', function() {
                const btn = $('#submit-request-btn');
                btn.prop('disabled', true);
                btn.find('.spinner-border').removeClass('d-none');
                btn.contents().filter(function() {
                    return this.nodeType === 3;
                }).last().replaceWith(' Submitting...');
            });

            // ========== QUICK ADD MATERIAL ==========
            $('#btnQuickAddMaterial').on('click', function(e) {
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
                        document.querySelector('.select2-container--open .select2-search__field')
                            ?.focus();
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
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success && res.material) {
                            let newOption = new Option(res.material.name, res.material.id, true,
                                true);
                            $('#inventory_id').append(newOption).val(res.material.id).trigger(
                                'change');
                            $('#addMaterialModal').modal('hide');
                            form[0].reset();
                        } else {
                            Swal.fire('Error', 'Failed to add material. Please try again.',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add material. Please try again.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            $('#search-material-autocomplete').on('input', function() {
                const keyword = $(this).val().trim();
                const $result = $('#search-material-result');
                if (keyword.length < 2) {
                    $result.html('');
                    return;
                }
                $.ajax({
                    url: "{{ route('inventories.json') }}",
                    data: {
                        q: keyword
                    },
                    success: function(data) {
                        const filtered = data.filter(item => item.name.toLowerCase().includes(
                            keyword.toLowerCase()));
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
                        $result.html(
                            '<span class="text-danger">Failed to search material.</span>');
                    }
                });
            });

            // ========== QUICK ADD PROJECT (CLIENT) ==========
            $('#quickAddProjectForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let errorDiv = $('#project-error');
                errorDiv.hide().text('');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.project) {
                            if (!$('#projectTypeClient').is(':checked')) {
                                $('#projectTypeClient').prop('checked', true).trigger('change');
                            }
                            window.location.reload();
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add project. Please try again.';
                        errorDiv.html(msg).show();
                    }
                });
            });

            $('#addProjectModal').on('shown.bs.modal', function() {
                if (!$('#project_departments').data('select2')) {
                    $('#project_departments').select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Select departments',
                        allowClear: true,
                        closeOnSelect: false,
                        dropdownParent: $('#addProjectModal')
                    });
                }
            });
        });
    </script>
@endpush
