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
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif
                <form action="{{ route('material_requests.update', $request->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="filter_project" value="{{ request('project') }}">
                    <input type="hidden" name="filter_material" value="{{ request('material') }}">
                    <input type="hidden" name="filter_status" value="{{ request('status') }}">
                    <input type="hidden" name="filter_requested_by" value="{{ request('requested_by') }}">
                    <input type="hidden" name="filter_requested_at" value="{{ request('requested_at') }}">
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label>Job Order <span class="text-danger">*</span></label>
                            </div>
                            <select name="job_order_id" id="job_order_id" class="form-select select2"
                                data-placeholder="Select Job Order" required>
                                <option value="">Select Job Order</option>
                                @foreach ($jobOrders as $jo)
                                    <option value="{{ $jo->id }}" data-project-id="{{ $jo->project_id }}"
                                        data-project-name="{{ $jo->project->name ?? '' }}"
                                        {{ old('job_order_id', $request->job_order_id) == $jo->id ? 'selected' : '' }}>
                                        {{ $jo->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('job_order_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                            <!-- Hidden Project ID (Auto-filled) -->
                            <input type="hidden" name="project_id" id="project_id"
                                value="{{ old('project_id', $request->project_id) }}" required>

                            <!-- Project Display (Read-only) -->
                            <div id="project-display" class="mt-2 {{ $request->project_id ? '' : 'd-none' }}">
                                <small class="text-muted">Project:</small>
                                <strong id="project-name-text">{{ $request->project->name ?? '' }}</strong>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label>Material <span class="text-danger">*</span></label>
                                {{-- <button type="button" class="btn btn-sm btn-outline-primary" id="btnQuickAddMaterial">
                                    + Quick Add Material
                                </button> --}}
                            </div>
                            <select name="inventory_id" class="form-select select2" data-placeholder="Select Material"
                                required>
                                @foreach ($inventories as $inv)
                                    <option value="{{ $inv->id }}" data-unit="{{ $inv->unit }}"
                                        data-stock="{{ $inv->quantity }}"
                                        {{ old('inventory_id', $request->inventory_id) == $inv->id ? 'selected' : '' }}>
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

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label>Requested Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="qty" class="form-control" value="{{ $request->qty }}"
                                    step="any" required>
                                <span class="input-group-text unit-label">
                                    {{ $request->invr ? $request->inv->unit : 'unit' }}
                                </span>
                            </div>
                            @error('qty')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label>Remark</label>
                            <textarea name="remark" class="form-control" rows="1">{{ $request->remark }}</textarea>
                            @error('remark')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label>Requested By</label>
                            <input type="text" class="form-control" value="{{ ucfirst($request->requested_by) }}"
                                disabled>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label>Department</label>
                            <input type="text" class="form-control"
                                value="{{ $request->user && $request->user->department ? ucfirst($request->user->department->name) : '-' }}"
                                disabled>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select"
                                {{ !in_array(auth()->user()->role, ['admin_logistic', 'super_admin']) ? 'disabled' : '' }}>
                                <option value="pending" {{ $request->status === 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="approved" {{ $request->status === 'approved' ? 'selected' : '' }}>Approved
                                </option>
                                <option value="canceled" {{ $request->status === 'canceled' ? 'selected' : '' }}>Canceled
                                </option>
                            </select>
                            @if (!in_array(auth()->user()->role, ['admin_logistic', 'super_admin']))
                                <input type="hidden" name="status" value="{{ $request->status }}">
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('material_requests.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="update-request-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                            aria-hidden="true"></span>
                        Update Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal Before Quick Add Material -->
    <div class="modal fade" id="confirmAddMaterialModal" tabindex="-1" aria-labelledby="confirmAddMaterialModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmAddMaterialModalLabel">Confirm Add Material!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <b>Please make sure this material does not already exist in the inventory table.</b><br>
                    <span class="text-danger">Use this feature only if the material is truly not available and
                        is urgently needed.<br>
                        Adding duplicate materials will cause data inconsistency!</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmAddMaterial">Yes, I
                        Understand</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Material Modal -->
    <div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel"
        aria-hidden="true">
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

                        <!-- Unit menjadi Select2 -->
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
    <!-- Add Project Modal -->
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
                        <div id="project-error" class="alert alert-danger d-none"></div>

                        <!-- Project Name -->
                        <div class="mb-3">
                            <label for="project_name" class="form-label">Project Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="project_name" name="name" class="form-control" required>
                        </div>

                        <!-- Quantity -->
                        <div class="mb-3">
                            <label for="project_qty" class="form-label">Quantity</label>
                            <input type="number" id="project_qty" name="qty" class="form-control" min="0"
                                step="any">
                        </div>

                        <!-- Departments -->
                        <div class="mb-3">
                            <label for="project_departments" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select id="project_departments" name="department_ids[]" class="form-select select2"
                                multiple="multiple" required>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select one or more departments</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
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
            /* Tinggi elemen form Bootstrap */
            padding: 0.375rem 0.75rem;
            /* Padding elemen form Bootstrap */
            font-size: 1rem;
            /* Ukuran font Bootstrap */
            line-height: 1.5;
            /* Line height Bootstrap */
            border: 1px solid #ced4da;
            /* Border Bootstrap */
            border-radius: 0.375rem;
            /* Border radius Bootstrap */
        }

        .select2-selection__rendered {
            line-height: 1.5;
            /* Line height Bootstrap */
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: calc(2.375rem + 2px);
            /* Tinggi elemen form Bootstrap */
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
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                allowClear: true,
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select an option';
                }
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            // Auto-fill project_id when Job Order is selected
            $('#job_order_id').on('change', function() {
                const selectedOption = $(this).find(':selected');
                const projectId = selectedOption.data('project-id');
                const projectName = selectedOption.data('project-name');

                if (projectId && projectName) {
                    $('#project_id').val(projectId);
                    $('#project-display').removeClass('d-none');
                    $('#project-name-text').text(projectName);
                } else {
                    $('#project_id').val('');
                    $('#project-display').addClass('d-none');
                }
            });

            // Trigger on page load if job order already selected
            if ($('#job_order_id').val()) {
                $('#job_order_id').trigger('change');
            }

            // Update unit label dynamically when material is selected
            $('select[name="inventory_id"]').on('change', function() {
                const selectedUnit = $(this).find(':selected').data('unit');
                $('.unit-label').text(selectedUnit || 'unit');
            });
            // Trigger saat halaman load jika sudah ada value terpilih
            $('select[name="inventory_id"]').trigger('change');

            // Untuk halaman create, edit, bulk create
            $('#btnQuickAddMaterial').off('click').on('click', function(e) {
                e.preventDefault();
                $('#confirmAddMaterialModal').modal('show');
            });

            $('#btnConfirmAddMaterial').off('click').on('click', function() {
                $('#confirmAddMaterialModal').modal('hide');
                setTimeout(function() {
                    $('#addMaterialModal, #quickAddMaterialModal').modal('show');
                }, 360);
            });

            // Form submit handler
            // Quick Add Project with Auto-Select
            $('#quickAddProjectForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let errorDiv = $('#project-error');
                errorDiv.hide().text('').addClass('d-none');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.project) {
                            const projectId = response.project.id;
                            const projectName = response.project.name;
                            // if controller returns departments: build string
                            const deptString = response.project.departments ? response.project
                                .departments.map(d => d.name).join(', ') : '';

                            const $projectSelect = $('select[name="project_id"]');

                            if ($projectSelect.find(`option[value="${projectId}"]`).length ===
                                0) {
                                let newOption = new Option(projectName, projectId, false,
                                    false);
                                $(newOption).attr('data-department', deptString);
                                $projectSelect.append(newOption);
                            }

                            $projectSelect.val(projectId).trigger('change');
                            $('#addProjectModal').modal('hide');
                            form[0].reset();
                            form.find('.select2').val(null).trigger('change');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add project. Please try again.';
                        errorDiv.html(msg).removeClass('d-none').show();
                    }
                });
            });

            // Initialize Select2 untuk project departments di modal
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

            // Update department text ketika project di-select
            $('select[name="project_id"]').on('change', function() {
                const selected = $(this).find(':selected');
                const department = selected.data('department');
                const $departmentDiv = $('#department');

                if ($departmentDiv.length > 0) {
                    $departmentDiv.removeClass('d-none text-danger text-warning');

                    if ($(this).val() && department) {
                        $departmentDiv.text(
                            `Department: ${department.charAt(0).toUpperCase() + department.slice(1)}`);
                    } else {
                        $departmentDiv.addClass('d-none').text('Department');
                    }
                }
            });

            // Initialize Select2 untuk unit di modal
            function initializeUnitSelect2() {
                $('#unit-select-modal').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Unit',
                    allowClear: true,
                    dropdownParent: $('#addMaterialModal')
                }).on('select2:open', function() {
                    setTimeout(function() {
                        document.querySelector('.select2-container--open .select2-search__field')
                            ?.focus();
                    }, 100);
                });
            }

            // Re-initialize Select2 saat modal ditampilkan
            $('#addMaterialModal').on('shown.bs.modal', function() {
                // Destroy existing Select2 instance jika ada
                if ($('#unit-select-modal').data('select2')) {
                    $('#unit-select-modal').select2('destroy');
                }
                initializeUnitSelect2();
            });

            // Quick Add Material
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
                            $('select[name="inventory_id"]').append(newOption).val(res.material
                                .id).trigger('change');
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

            // Search material autocomplete
            // Untuk modal Quick Add Material di halaman bulk create & edit
            $('#quickAddMaterialForm').closest('.modal').on('shown.bs.modal', function() {
                const $input = $(this).find('#search-material-autocomplete');
                const $result = $(this).find('#search-material-result');
                $input.off('input').on('input', function() {
                    const keyword = $(this).val().trim();
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
                            const filtered = data.filter(item =>
                                item.name.toLowerCase().includes(keyword
                                    .toLowerCase())
                            );
                            if (filtered.length > 0) {
                                $result.html(
                                    '<b>Similar material(s) found:</b><ul class="mb-0">' +
                                    filtered.map(item => `<li>${item.name}</li>`)
                                    .join('') +
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
                                '<span class="text-danger">Failed to search material.</span>'
                            );
                        }
                    });
                });
            });
        });

        // Update available quantity and unit label when inventory is selected
        $('select[name="inventory_id"]').on('change', function() {
            const selected = $(this).find(':selected');
            const selectedUnit = selected.data('unit');
            const selectedStock = selected.data('stock');
            $('.unit-label').text(selectedUnit || 'unit');

            const $availableQty = $('#available-qty');
            $availableQty.removeClass('d-none text-danger text-warning');

            if (selected.val() && selectedStock !== undefined) {
                let colorClass = '';
                if (selectedStock == 0) {
                    colorClass = 'text-danger';
                } else if (selectedStock < 3) {
                    colorClass = 'text-warning';
                }
                $availableQty
                    .text(`Available Qty: ${selectedStock} ${selectedUnit || ''}`)
                    .addClass(colorClass);
            } else {
                $availableQty.addClass('d-none').text('');
            }
        });
        // Trigger saat halaman load jika sudah ada value terpilih
        $('select[name="inventory_id"]').trigger('change');

        // Update department text when project is selected
        $('select[name="project_id"]').on('change', function() {
            const selected = $(this).find(':selected');
            const department = selected.data('department');
            const $departmentDiv = $('#department');
            $departmentDiv.removeClass('d-none text-danger text-warning');

            if (selected.val() && department) {
                $departmentDiv.text(`Department: ${department.charAt(0).toUpperCase() + department.slice(1)}`);
            } else {
                $departmentDiv.addClass('d-none').text('Department');
            }
        });
        // Trigger saat halaman load jika sudah ada value terpilih
        $('select[name="project_id"]').trigger('change');

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector(
                'form[action="{{ route('material_requests.update', $request->id) }}"]');
            const submitBtn = document.getElementById('update-request-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Updating...';
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            // submitBtn.disabled = false;
            // spinner.classList.add('d-none');
            // submitBtn.childNodes[2].textContent = ' Update Request';
        });
    </script>
@endpush
