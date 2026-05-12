@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Edit Material Request</h2>
                <hr>

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {!! session('success') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('material_requests.update', $materialRequest->id) }}" method="POST"
                    id="editMaterialRequestForm">
                    @csrf
                    @method('PUT')

                    <!-- Hidden filter fields -->
                    <input type="hidden" name="filter_project" value="{{ request('project') }}">
                    <input type="hidden" name="filter_material" value="{{ request('material') }}">
                    <input type="hidden" name="filter_status" value="{{ request('status') }}">
                    <input type="hidden" name="filter_requested_by" value="{{ request('requested_by') }}">
                    <input type="hidden" name="filter_requested_at" value="{{ request('requested_at') }}">

                    <!-- ========== PROJECT TYPE (kiri) + MATERIAL SOURCE (kanan) ========== -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <label class="fw-bold mb-2">Project Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="project_type"
                                        id="projectTypeClient" value="client"
                                        {{ old('project_type', $materialRequest->project_type) == 'client' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="projectTypeClient">Client Project</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="project_type"
                                        id="projectTypeInternal" value="internal"
                                        {{ old('project_type', $materialRequest->project_type) == 'internal' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="projectTypeInternal">Internal Project</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="fw-bold mb-2">Material Source <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="inventory_source" id="sourceStock"
                                        value="stock"
                                        {{ old('inventory_source', $materialRequest->inventory_source ?? 'stock') == 'stock' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="sourceStock">Inventory Stock</label>
                                    <div class="form-text text-muted">From batch inventory</div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="inventory_source"
                                        id="sourceIncoming" value="incoming"
                                        {{ old('inventory_source', $materialRequest->inventory_source) == 'incoming' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="sourceIncoming">Inventory Incoming</label>
                                    <div class="form-text text-muted">From Lark Purchasing</div>
                                </div>
                            </div>
                            <input type="hidden" name="inventory_source" id="hiddenInventorySource"
                                value="{{ old('inventory_source', $materialRequest->inventory_source ?? 'stock') }}">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Kolom Kiri: Job Order + Project Info + Add Internal Button -->
                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label id="jobOrderLabel">Job Order <span class="text-danger">*</span></label>
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary {{ $materialRequest->project_type == 'client' ? 'd-none' : '' }}"
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
                            <div id="projectInfoDisplay"
                                class="mt-1 p-2 bg-light rounded {{ $materialRequest->project_id || $materialRequest->internal_project_id ? '' : 'd-none' }}">
                                <small class="text-muted d-block mb-1">Project:</small>
                                <strong id="projectInfoText" class="text-primary">
                                    @if ($materialRequest->project_type == 'client' && $materialRequest->project)
                                        {{ $materialRequest->project->name }}
                                    @elseif($materialRequest->project_type == 'internal' && $materialRequest->internalProject)
                                        {{ $materialRequest->internalProject->project }}
                                    @endif
                                </strong>
                                <input type="hidden" name="project_id" id="hiddenProjectId"
                                    value="{{ $materialRequest->project_id }}">
                                <input type="hidden" name="internal_project_id" id="hiddenInternalProjectId"
                                    value="{{ $materialRequest->internal_project_id }}">
                            </div>
                        </div>

                        <!-- Kolom Kanan: Material -->
                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label id="materialLabel">Material <span class="text-danger">*</span></label>
                            </div>

                            {{-- Stock select --}}
                            <div id="stockMaterialWrapper">
                                <select id="inventory_id" class="form-select select2"
                                    data-placeholder="Select Material (Stock)">
                                    <option value="">Select Material</option>
                                    @foreach ($inventories as $inv)
                                        <option value="{{ $inv->id }}" data-unit="{{ $inv->unit }}"
                                            data-stock="{{ $inv->quantity }}"
                                            {{ old('inventory_id', $materialRequest->inventory_id) == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="inventory_id" id="hiddenInventoryId"
                                    value="{{ old('inventory_id', $materialRequest->inventory_id) }}">
                                <div id="available-qty" class="form-text d-none"></div>
                                @error('inventory_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Incoming (staging) select --}}
                            <div id="incomingMaterialWrapper" style="display:none;">
                                <select id="staging_inventory_id" class="form-select select2"
                                    data-placeholder="Select Incoming Material">
                                    <option value="">Select Incoming Material</option>
                                    {{-- Indo Purchase option (shown only when MR is linked to an indo purchase) --}}
                                    @if ($materialRequest->indo_purchase_id && $materialRequest->indoPurchase)
                                        @php
                                            $ip = $materialRequest->indoPurchase;
                                            $ipName =
                                                $ip->purchase_type === 'restock' && $ip->material
                                                    ? $ip->material->name
                                                    : $ip->new_item_name ?? 'Unknown Item';
                                            $ipUnit =
                                                optional($ip->unit)->name ?? (optional($ip->material)->unit ?? '');
                                            $ipValue = 'ip_' . $ip->id;
                                        @endphp
                                        <optgroup label="Indo Purchase">
                                            <option value="{{ $ipValue }}" data-unit="{{ $ipUnit }}"
                                                data-qty="{{ $ip->quantity }}" data-received="0"
                                                {{ old('staging_inventory_id', $ipValue) === $ipValue ? 'selected' : '' }}>
                                                {{ $ipName }}{{ $ip->po_number ? ' (PO: ' . $ip->po_number . ')' : '' }}
                                            </option>
                                        </optgroup>
                                    @endif
                                    {{-- Lark Staging options --}}
                                    @if ($stagingInventories->isNotEmpty())
                                        <optgroup label="International Purchase (Lark Staging)">
                                            @foreach ($stagingInventories as $si)
                                                <option value="{{ $si->id }}" data-unit="{{ $si->unit }}"
                                                    data-qty="{{ $si->quantity }}"
                                                    data-received="{{ $si->received_qty ?? 0 }}"
                                                    {{ old('staging_inventory_id', $materialRequest->staging_inventory_id) == $si->id ? 'selected' : '' }}>
                                                    {{ $si->name }}{{ $si->material_code ? ' (' . $si->material_code . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <input type="hidden" name="staging_inventory_id" id="hiddenStagingInventoryId"
                                    value="{{ old('staging_inventory_id', $materialRequest->indo_purchase_id ? 'ip_' . $materialRequest->indo_purchase_id : $materialRequest->staging_inventory_id) }}">
                                <div id="available-incoming-qty" class="form-text d-none"></div>
                                @error('staging_inventory_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- QUANTITY & REMARK -->
                    <div class="row mt-2">
                        <div class="col-lg-6 mb-3">
                            <label>Requested Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="qty"
                                    class="form-control @error('qty') is-invalid @enderror" step="any" required
                                    value="{{ old('qty', $materialRequest->qty) }}" id="qty">
                                <span class="input-group-text unit-label">
                                    {{ $materialRequest->inventory->unit ?? ($materialRequest->stagingInventory->unit ?? 'unit') }}
                                </span>
                            </div>
                            @error('qty')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Remark (Optional)</label>
                            <textarea name="remark" class="form-control">{{ old('remark', $materialRequest->remark) }}</textarea>
                            @error('remark')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- REQUESTED BY & DEPARTMENT (READ ONLY) -->
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label>Requested By</label>
                            <input type="text" class="form-control"
                                value="{{ ucfirst($materialRequest->requested_by) }}" disabled>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label>Department</label>
                            <input type="text" class="form-control"
                                value="{{ $materialRequest->user && $materialRequest->user->department ? ucfirst($materialRequest->user->department->name) : '-' }}"
                                disabled>
                        </div>
                    </div>

                    <!-- STATUS -->
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select"
                                {{ !auth()->user()->can('logistic.material-request.approve') ? 'disabled' : '' }}>
                                <option value="pending" {{ $materialRequest->status === 'pending' ? 'selected' : '' }}>
                                    Pending</option>
                                <option value="approved" {{ $materialRequest->status === 'approved' ? 'selected' : '' }}>
                                    Approved</option>
                                <option value="canceled" {{ $materialRequest->status === 'canceled' ? 'selected' : '' }}>
                                    Canceled</option>
                            </select>
                            @cannot('logistic.material-request.approve')
                                <input type="hidden" name="status" value="{{ $materialRequest->status }}">
                            @endcannot
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('material_requests.index', array_filter(request()->only(['project', 'material', 'status', 'requested_by', 'requested_at']))) }}"
                            class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="update-request-btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="btn-text">Update Request</span>
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
                            <label for="project_qty" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="project_qty" name="qty" min="0"
                                step="any">
                        </div>
                        <div class="mb-3">
                            <label for="project_departments" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select name="department_ids[]" id="project_departments" class="form-select select2" multiple
                                required>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select one or more departments</small>
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

        /* Style untuk loading button */
        .btn-primary:disabled {
            cursor: not-allowed;
            opacity: 0.65;
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
            const currentProjectType = '{{ $materialRequest->project_type }}';
            const currentJobOrderId =
                '{{ $materialRequest->job_order_id ?? $materialRequest->internal_project_id }}';
            const departments = @json($departments);
            const defaultPtDcmDepartmentId = '{{ $defaultPtDcmDepartmentId ?? '' }}';
            const currentInventorySource =
                '{{ old('inventory_source', $materialRequest->inventory_source ?? 'stock') }}';
            const currentStagingId = '{{ old('staging_inventory_id', $materialRequest->staging_inventory_id) }}';
            const stagingInventories = @json($stagingInventories);

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

                // Set current value if exists
                if (currentJobOrderId) {
                    setTimeout(() => {
                        $('#jobOrderSelect').val(currentJobOrderId).trigger('change');
                    }, 100);
                }
            }

            $('input[name="project_type"]').on('change', toggleProjectType);

            // Set initial project type
            $('input[name="project_type"][value="' + currentProjectType + '"]').prop('checked', true);
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

            // ========== MATERIAL SOURCE TOGGLE ==========
            function toggleMaterialSource() {
                const isIncoming = $('#sourceIncoming').is(':checked');
                const $stockWrap = $('#stockMaterialWrapper');
                const $incomingWrap = $('#incomingMaterialWrapper');

                if (isIncoming) {
                    $stockWrap.hide();
                    $incomingWrap.show();
                    $('#hiddenInventoryId').val(''); // clear stock hidden value
                    $('#hiddenInventorySource').val('incoming');
                    // Restore current staging select if exists
                    if (currentStagingId) {
                        setTimeout(() => {
                            $('#staging_inventory_id').val(currentStagingId).trigger('change');
                        }, 100);
                    }
                } else {
                    $incomingWrap.hide();
                    $stockWrap.show();
                    $('#hiddenStagingInventoryId').val(''); // clear staging hidden value
                    $('#hiddenInventorySource').val('stock');
                }
            }

            $('input[name="inventory_source"]').on('change', toggleMaterialSource);

            // ========== UNIT LABEL & AVAILABLE STOCK (Stock) ==========
            $('#inventory_id').on('change', function() {
                const isIncoming = $('#sourceIncoming').is(':checked');
                if (isIncoming) return;
                const selected = $(this).find(':selected');
                const unit = selected.data('unit') || 'unit';
                const stock = selected.data('stock');
                // Sync ke hidden input
                $('#hiddenInventoryId').val(selected.val() || '');
                $('.unit-label').text(unit);
                const $avail = $('#available-qty');
                $avail.removeClass('d-none text-danger text-warning');
                if (selected.val() && stock !== undefined) {
                    let cls = stock == 0 ? 'text-danger' : (stock < 3 ? 'text-warning' : '');
                    $avail.text(`Available Qty: ${stock} ${unit}`).addClass(cls);
                } else {
                    $avail.addClass('d-none').text('');
                }
            }).trigger('change');

            // ========== UNIT LABEL & QTY (Incoming staging) ==========
            $('#staging_inventory_id').on('change', function() {
                const selected = $(this).find(':selected');
                const unit = selected.data('unit') || 'unit';
                const received = selected.data('received') ?? '';
                // Sync ke hidden input
                $('#hiddenStagingInventoryId').val(selected.val() || '');
                $('.unit-label').text(unit);
                const $avail = $('#available-incoming-qty');
                $avail.removeClass('d-none text-danger text-warning text-info');
                if (selected.val()) {
                    $avail.addClass('text-info').text(`Received Qty: ${received} ${unit}`).removeClass(
                        'd-none');
                } else {
                    $avail.addClass('d-none').text('');
                }
            });

            // Init: apply saved source state on page load
            if (currentInventorySource === 'incoming') {
                $('#sourceIncoming').prop('checked', true);
            } else {
                $('#sourceStock').prop('checked', true);
            }
            toggleMaterialSource();

            // ========== HANDLE FORM SUBMIT DENGAN LOADING ==========
            $('#editMaterialRequestForm').on('submit', function() {
                const btn = $('#update-request-btn');
                btn.prop('disabled', true);
                btn.find('.spinner-border').removeClass('d-none');
                btn.find('.btn-text').text(' Updating...');

                // Form akan submit secara normal ke controller
                return true;
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

                // Tampilkan loading
                let submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true);
                submitBtn.html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Adding...'
                );

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
                            Swal.fire('Success', 'Material added successfully', 'success');
                        } else {
                            Swal.fire('Error', 'Failed to add material. Please try again.',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add material. Please try again.';
                        Swal.fire('Error', msg, 'error');
                    },
                    complete: function() {
                        // Kembalikan tombol ke keadaan semula
                        submitBtn.prop('disabled', false);
                        submitBtn.html('Add Material');
                    }
                });
            });

            // ========== SEARCH MATERIAL AUTOCOMPLETE ==========
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

            // ========== QUICK ADD INTERNAL PROJECT ==========
            $('#btnAddInternalProject').on('click', function() {
                $('#addInternalProjectModal').modal('show');
            });

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
                            Swal.fire('Success',
                                    'Project added successfully. Please refresh the page.',
                                    'success')
                                .then(() => {
                                    window.location.reload();
                                });
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
