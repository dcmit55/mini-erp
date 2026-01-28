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
                        data-bs-target="#quickAddProjectModal">+
                        Quick Add Project</button>
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
                                    <th style="width: 25%;">Project <span class="text-danger">*</span></th>
                                    <th style="width: 25%;">Material <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Quantity <span class="text-danger">*</span></th>
                                    <th style="width: 25%;">Remark (optional)</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bulk-rows">
                                @foreach (old('requests', [0 => []]) as $index => $request)
                                    <tr class="align-top">
                                        <td data-label="Project">
                                            <select name="requests[{{ $index }}][project_id]"
                                                class="form-select select2 project-select" required>
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}"
                                                        data-department="{{ $project->departments->pluck('name')->implode(', ') }}"
                                                        {{ old("requests.$index.project_id") == $project->id ? 'selected' : '' }}>
                                                        {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="department-text form-text d-none">Department</div>
                                            @error("requests.$index.project_id")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td data-label="Material">
                                            <select name="requests[{{ $index }}][inventory_id]"
                                                class="form-select select2 material-select" required>
                                                <option value="">Select Material</option>
                                                @foreach ($inventories as $inventory)
                                                    <option value="{{ $inventory->id }}" data-unit="{{ $inventory->unit }}"
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
                                        <td data-label="Remark">
                                            <textarea name="requests[{{ $index }}][remark]" class="form-control" rows="1">{{ old("requests.$index.remark") }}</textarea>
                                            @error("requests.$index.remark")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>
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

            <!-- Confirmation Modal Before Quick Add Material -->
            <div class="modal fade" id="confirmAddMaterialModal" tabindex="-1"
                aria-labelledby="confirmAddMaterialModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmAddMaterialModalLabel">Confirm Add Material!</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <b>Please make sure this material does not already exist in the inventory table.</b><br>
                            <span class="text-danger">Use this feature only if the material is truly not available and is
                                urgently needed.<br>
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

            <!-- Add Project Modal -->
            <div class="modal fade" id="quickAddProjectModal" tabindex="-1" aria-labelledby="quickAddProjectModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <form id="quickAddProjectForm" method="POST" action="{{ route('projects.store.quick') }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="quickAddProjectModalLabel">Quick Add Project</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="project-error" class="alert alert-danger d-none"></div>

                                <!-- Project Name -->
                                <div class="mb-3">
                                    <label for="project_name" class="form-label">Project Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="project_name" name="name" required
                                        placeholder="Enter project name">
                                </div>

                                <!-- Quantity -->
                                <div class="mb-3">
                                    <label for="project_qty" class="form-label">Quantity <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="project_qty" name="qty" required
                                        min="1" placeholder="Enter quantity">
                                </div>

                                <!-- Departments -->
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
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            row.find('.material-select').on('change', function() {
                const unit = $(this).find(':selected').data('unit') || 'unit';
                row.find('.unit-label').text(unit);
            });
        }

        $(document).ready(function() {
            initSelect2($('#bulk-rows tr').first());

            $('#add-row').click(function() {
                let lastRow = $('#bulk-rows tr').last(); // Ambil baris terakhir

                // Check if Select2 is initialized before destroying
                lastRow.find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                // Clone the last row
                let newRow = lastRow.clone();
                let rowCount = $('#bulk-rows tr').length;

                // Reset select elements
                newRow.find('select').each(function() {
                    let name = $(this).attr('name').replace(/\d+/, rowCount);
                    $(this).attr('name', name);

                    // If it's the project-select, copy the value from the last row
                    if ($(this).hasClass('project-select')) {
                        let previousValue = lastRow.find('.project-select').val();
                        $(this).val(previousValue).trigger('change');
                    } else {
                        // Clear the value for other select elements
                        $(this).val('').trigger('change');
                    }
                });

                // Reset input elements
                newRow.find('input').each(function() {
                    let name = $(this).attr('name').replace(/\d+/, rowCount);
                    $(this).attr('name', name).val('').removeClass('is-invalid');
                });

                // Reset textarea elements
                newRow.find('textarea').each(function() {
                    let name = $(this).attr('name').replace(/\d+/, rowCount);
                    $(this).attr('name', name).val('');
                });

                // Hapus error message pada row baru
                newRow.find('.text-danger').remove();

                // Reset unit label
                newRow.find('.unit-label').text('unit');

                // Reset available qty di row baru
                newRow.find('.available-qty-text').addClass('d-none').removeClass(
                    'text-danger text-warning').text('');

                // Append the new row
                $('#bulk-rows').append(newRow);

                // Reinitialize Select2 for the new row
                initSelect2(newRow);

                // Reinitialize Select2 for the last row
                initSelect2(lastRow);
            });

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

            // Trigger change event on page load to restore old values
            $('.material-select').trigger('change');

            // Quick Add Project (Bulk)
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
                            // Tambahkan ke semua select2 project di setiap row
                            $('.project-select').each(function() {
                                let exists = $(this).find('option[value="' + res.project
                                    .id + '"]').length;
                                if (!exists) {
                                    let newOption = new Option(res.project.name, res
                                        .project.id, false, false);
                                    $(this).append(newOption);
                                }
                            });
                            // Pilih otomatis di row terakhir
                            $('.project-select').last().val(res.project.id).trigger('change');
                            $('#quickAddProjectForm').closest('.modal').modal('hide');
                            form[0].reset();

                            // Reset select2 untuk departments jika ada
                            $('#project_departments').val(null).trigger('change');

                            Swal.fire('Success', 'Project added successfully!', 'success');
                        } else {
                            Swal.fire('Error', res.message ||
                                'Failed to add project. Please try again.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add project. Please try again.';
                        if (xhr.responseJSON?.errors) {
                            // Tampilkan validation errors
                            let errorList = Object.values(xhr.responseJSON.errors).flat().join(
                                '<br>');
                            msg = errorList;
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Inisialisasi Select2 untuk multiple department di modal Quick Add Project
            $('#project_departments').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Department',
                allowClear: true,
                dropdownParent: $('#quickAddProjectModal')
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            // Quick Add Material Button with Confirmation
            $('#btnAddMaterial').off('click').on('click', function(e) {
                e.preventDefault();
                $('#confirmAddMaterialModal').modal('show');
            });

            $('#btnConfirmAddMaterial').off('click').on('click', function() {
                $('#confirmAddMaterialModal').modal('hide');
                setTimeout(function() {
                    $('#addMaterialModal, #quickAddMaterialModal').modal('show');
                }, 360);
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

            // Quick Add Material (existing code, no changes needed)
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

            // Department per-row
            $(document).on('change', '.project-select', function() {
                const selected = $(this).find(':selected');
                const department = selected.data('department');
                const $deptDiv = $(this).closest('td').find('.department-text');
                $deptDiv.removeClass('d-none text-danger text-warning');
                if (selected.val() && department) {
                    $deptDiv.text(
                        `Department: ${department.charAt(0).toUpperCase() + department.slice(1)}`);
                } else {
                    $deptDiv.addClass('d-none').text('Department');
                }
            });
            $('.project-select').trigger('change');

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

            // Untuk modal Quick Add Material
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

            // Handle form submission for bulk create
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
