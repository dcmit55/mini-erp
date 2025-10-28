@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Create Material Request</h2>
                <hr>
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
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
                <form method="POST" action="{{ route('material_requests.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label>Project <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                                    data-bs-target="#addProjectModal">
                                    + Quick Add Project
                                </button>
                            </div>
                            <select name="project_id" id="project_id" class="form-select select2" required>
                                <option value="">Select an option</option>
                                @foreach ($projects as $proj)
                                    <option value="{{ $proj->id }}" data-department="{{ $proj->department->name }}"
                                        {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                                        {{ $proj->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="department" class="form-text d-none">Department</div>
                            @error('project_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label>Material <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnQuickAddMaterial">
                                    + Quick Add Material
                                </button>
                            </div>
                            <select name="inventory_id" id="inventory_id" class="form-select select2" required>
                                <option value="">Select an option</option>
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
                    </div>

                    <div class="row">
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
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Submit Request
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
                        <label class="mt-2">Unit <span class="text-danger">*</span></label>
                        <input type="text" name="unit" class="form-control" required>
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
                        <h5 class="modal-title">Quick Add Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label>Project Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                        <label class="mt-2">Quantity <span class="text-danger">*</span></label>
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
                placeholder: 'Select an option',
                allowClear: true,
                theme: 'bootstrap-5',
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            // Update unit label dynamically when material is selected
            $('select[name="inventory_id"]').on('change', function() {
                const selectedUnit = $(this).find(':selected').data('unit');
                $('.unit-label').text(selectedUnit || 'unit');
            });
            // Trigger saat halaman load jika sudah ada value terpilih
            $('select[name="inventory_id"]').trigger('change');

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
                            let newOption = new Option(res.project.name, res.project.id, true,
                                true);
                            $('select[name="project_id"]').append(newOption).val(res.project.id)
                                .trigger('change');
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

            // Saat tombol Quick Add Material diklik, tampilkan modal konfirmasi
            $('#btnQuickAddMaterial').off('click').on('click', function(e) {
                e.preventDefault();
                $('#confirmAddMaterialModal').modal('show');
            });

            // Jika user konfirmasi, baru buka modal Quick Add Material
            $('#btnConfirmAddMaterial').off('click').on('click', function() {
                $('#confirmAddMaterialModal').modal('hide');
                setTimeout(function() {
                    $('#addMaterialModal').modal('show');
                }, 360); // beri jeda agar modal tidak tumpang tindih
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
                        $result.html(
                            '<span class="text-danger">Failed to search material.</span>');
                    }
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
            const form = document.querySelector('form[action="{{ route('material_requests.store') }}"]');
            const submitBtn = document.getElementById('submit-request-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Submitting...';
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            // submitBtn.disabled = false;
            // spinner.classList.add('d-none');
            // submitBtn.childNodes[2].textContent = ' Submit Request';
        });
    </script>
@endpush
