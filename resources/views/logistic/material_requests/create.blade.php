@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow rounded">
        <div class="card-body">
            <h2 class="mb-0" style="font-size:1.3rem;">Create Material Request</h2>
            <hr>

            <!-- ========== ALERT MESSAGES (LANGSUNG) ========== -->
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
                                <input class="form-check-input" type="radio" name="project_type" id="projectTypeClient"
                                       value="client" {{ old('project_type', 'client') == 'client' ? 'checked' : '' }}>
                                <label class="form-check-label" for="projectTypeClient">Client Project</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="project_type" id="projectTypeInternal"
                                       value="internal" {{ old('project_type') == 'internal' ? 'checked' : '' }}>
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
                            <button type="button" class="btn btn-sm btn-outline-primary d-none" id="btnAddInternalProject">
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
                            <input type="hidden" name="project_id" id="hiddenProjectId" value="{{ old('project_id') }}">
                            <input type="hidden" name="internal_project_id" id="hiddenInternalProjectId" value="{{ old('internal_project_id') }}">
                        </div>
                    </div>

                    <!-- Kolom Kanan: Material -->
                    <div class="col-lg-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label>Material <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnQuickAddMaterial">
                                + Quick Add Material
                            </button>
                        </div>
                        <select name="inventory_id" id="inventory_id" class="form-select select2"
                                data-placeholder="Select Material" required>
                            <option value="">Select Material</option>
                            @foreach ($inventories as $inv)
                                <option value="{{ $inv->id }}" data-unit="{{ $inv->unit }}" data-stock="{{ $inv->quantity }}"
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
                </div>

                <!-- QUANTITY & REMARK -->
                <div class="row mt-2">
                    <div class="col-lg-6 mb-3">
                        <label>Requested Quantity <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="qty" class="form-control @error('qty') is-invalid @enderror"
                                   step="any" required value="{{ old('qty') }}">
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
<div class="modal fade" id="confirmAddMaterialModal" tabindex="-1" aria-hidden="true">...</div>
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-hidden="true">...</div>

<!-- ========== MODAL QUICK ADD INTERNAL PROJECT (FINAL, CLEAN) ========== -->
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

<!-- MODAL QUICK ADD PROJECT (CLIENT) -->
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-hidden="true">...</div>

@endsection

@push('styles')
<style>
    /* ... styles yang sama ... */
    .select2-container .select2-selection--single { height: calc(2.375rem + 2px); }
    /* ... */
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
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
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
            html += `<option value="${jo.id}" data-project-id="${jo.project_id}" data-project-name="${projectName}">${jo.name}</option>`;
        });
        $('#jobOrderSelect').html(html);
        $('#jobOrderLabel').text('Job Order (Client)');
    }

    function renderInternalOptions() {
        let html = '<option value="">Select Job Order</option>';
        internalProjects.forEach(ip => {
            html += `<option value="${ip.job}" data-internal-id="${ip.id}" data-project="${ip.project}">${ip.job}</option>`;
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

    // ========== LOGIKA DEPARTMENT DI MODAL (FIX, TIDAK ADA DROPDOWN UNTUK NON-TESTING) ==========
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
            url: '{{ route("internal_projects.quick") }}',
            method: 'POST',
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success && response.internal_project) {
                    const ip = response.internal_project;
                    const newOption = new Option(ip.job, ip.job, true, true);
                    $(newOption).attr('data-internal-id', ip.id).attr('data-project', ip.project);
                    $('#jobOrderSelect').append(newOption).val(ip.job).trigger('change');
                    internalProjects.push({ id: ip.id, project: ip.project, job: ip.job });
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
                    for (let key in errors) { errorHtml += '<li>' + errors[key][0] + '</li>'; }
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

    // ========== UNIT LABEL & AVAILABLE STOCK ==========
    $('#inventory_id').on('change', function() {
        const selected = $(this).find(':selected');
        const unit = selected.data('unit') || 'unit';
        const stock = selected.data('stock');
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

    // ========== SUBMIT BUTTON SPINNER ==========
    $('#materialRequestForm').on('submit', function() {
        const btn = $('#submit-request-btn');
        btn.prop('disabled', true);
        btn.find('.spinner-border').removeClass('d-none');
        btn.contents().filter(function() { return this.nodeType === 3; }).last().replaceWith(' Submitting...');
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
                    $('#inventory_id').append(newOption).val(res.material.id).trigger('change');
                    $('#addMaterialModal').modal('hide');
                    form[0].reset();
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

    $('#search-material-autocomplete').on('input', function() {
        const keyword = $(this).val().trim();
        const $result = $('#search-material-result');
        if (keyword.length < 2) { $result.html(''); return; }
        $.ajax({
            url: "{{ route('inventories.json') }}",
            data: { q: keyword },
            success: function(data) {
                const filtered = data.filter(item => item.name.toLowerCase().includes(keyword.toLowerCase()));
                if (filtered.length > 0) {
                    $result.html(
                        '<b>Similar material(s) found:</b><ul class="mb-0">' +
                        filtered.map(item => `<li>${item.name}</li>`).join('') +
                        '</ul><span class="text-danger">Please make sure you are not adding a duplicate material!</span>'
                    );
                } else {
                    $result.html('<span class="text-success">No similar material found. You can proceed to add this material.</span>');
                }
            },
            error: function() { $result.html('<span class="text-danger">Failed to search material.</span>'); }
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
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success && response.project) {
                    if (!$('#projectTypeClient').is(':checked')) {
                        $('#projectTypeClient').prop('checked', true).trigger('change');
                    }
                    window.location.reload();
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Failed to add project. Please try again.';
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