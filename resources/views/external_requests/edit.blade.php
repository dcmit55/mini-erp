@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h4 class="mb-3">External Request</h4>
                <form method="POST" action="{{ route('external_requests.update', $request->id) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="new_material"
                                    {{ old('type', $request->type) == 'new_material' ? 'selected' : '' }}>New Material
                                </option>
                                <option value="restock" {{ old('type', $request->type) == 'restock' ? 'selected' : '' }}>
                                    Restock</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="material-name-group">
                            <label class="form-label">Material Name</label>
                            <input type="text" name="material_name" id="material_name_input" class="form-control"
                                value="{{ old('material_name', $request->material_name) }}"
                                {{ $request->type == 'new_material' ? 'required' : '' }}>

                            <select name="inventory_id" id="material_name_select" class="form-select select2 d-none">
                                <option value="">Select Material</option>
                                @foreach ($inventories as $inv)
                                    <option value="{{ $inv->id }}" data-unit="{{ $inv->unit }}"
                                        data-stock="{{ $inv->quantity }}"
                                        {{ old('inventory_id', $request->inventory_id) == $inv->id ? 'selected' : '' }}>
                                        {{ $inv->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock Level</label>
                            <input type="number" name="stock_level" id="stock_level_input" class="form-control" required
                                min="0" step="0.01" value="{{ old('stock_level', $request->stock_level) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Required Quantity</label>
                            <input type="number" name="required_quantity" class="form-control" required min="0.01"
                                step="0.01" value="{{ old('required_quantity', $request->required_quantity) }}">
                        </div>
                        <div class="col-md-4" id="unit-group">
                            <label class="form-label">Unit</label>
                            <!-- Select2 untuk new_material -->
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#addUnitModal"
                                style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                                + Add Unit
                            </button>
                            <select id="unit-select" name="unit" class="form-select select2 d-none" required>
                                <option value="">Select Unit</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->name }}"
                                        {{ old('unit', $request->unit) == $unit->name ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                            <!-- Input text untuk restock -->
                            <input type="text" name="unit" id="unit_input" class="form-control" readonly
                                value="{{ old('unit', $request->unit) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="quickAddProjectBtn"
                                data-bs-toggle="modal" data-bs-target="#addProjectModal"
                                style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                                + Add Project
                            </button>
                            <select name="project_id" class="form-select select2" required>
                                <option value="">Select Project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        {{ old('project_id', $request->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="img" class="form-label">Image (optional)</label>
                            <input type="file" name="img" class="form-control" id="img" accept="image/*"
                                onchange="previewImage(event)">
                            <a id="img-preview-link" href="{{ $request->img ? asset('storage/' . $request->img) : '#' }}"
                                data-fancybox="gallery"
                                @if ($request->img) style="display: block;" @else style="display: none;" @endif>
                                <img id="img-preview" src="{{ $request->img ? asset('storage/' . $request->img) : '#' }}"
                                    alt="Image Preview" class="mt-2 rounded" style="max-width: 200px;">
                            </a>
                            @error('img')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary" id="submit-request-btn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                aria-hidden="true"></span>
                            Submit Request
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
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                dropdownAutoWidth: true,
                width: '100%',
            });

            $('#unit-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Unit',
                allowClear: true,
                width: '100%',
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            function updateMaterialFields() {
                const type = $('#type').val();
                if (type === 'restock') {
                    const selected = $('#material_name_select option:selected');
                    $('#unit_input').val(selected.data('unit') || '');
                    $('#stock_level_input').val(selected.data('stock') || '');
                }
            }

            function toggleMaterialInput() {
                const type = $('#type').val();
                if (type === '') {
                    // Kondisi awal: semua input disable
                    $('#material_name_input').show().prop('required', false).prop('disabled', true);
                    $('#material_name_select').hide().addClass('d-none').prop('required', false).prop('disabled',
                        true);
                    $('#material_name_select').next('.select2-container').hide();
                    $('#unit_input').show().prop('readonly', false).prop('disabled', true).val('');
                    $('#unit-select').hide().addClass('d-none').prop('disabled', true);
                    $('#unit-select').next('.select2-container').hide();
                    $('#stock_level_input').prop('readonly', false).prop('disabled', true).val('');
                    $('input[name="required_quantity"]').prop('disabled', true).val('');
                } else if (type === 'new_material') {
                    $('#material_name_input').show().prop('required', true).prop('disabled', false);
                    $('#material_name_select').hide().prop('required', false).prop('disabled', true);
                    $('#material_name_select').next('.select2-container').hide();
                    $('#unit-select').show().removeClass('d-none').prop('disabled', false);
                    $('#unit-select').next('.select2-container').show();
                    $('#unit_input').hide().prop('disabled', true).val('');
                    $('#stock_level_input').prop('readonly', false).prop('disabled', false);
                    $('input[name="required_quantity"]').prop('disabled', false);
                } else if (type === 'restock') {
                    $('#material_name_input').hide().prop('required', false).prop('disabled', true);
                    $('#material_name_select').show().prop('required', true).prop('disabled', false);
                    $('#material_name_select').next('.select2-container').show();
                    $('#unit_input').show().prop('readonly', true).prop('disabled', false);
                    $('#unit-select').hide().addClass('d-none').prop('disabled', true);
                    $('#unit-select').next('.select2-container').hide();
                    // Jika material dipilih, autofill unit
                    const selected = $('#material_name_select option:selected');
                    $('#unit_input').val(selected.data('unit') || '');
                    $('#stock_level_input').prop('readonly', true).prop('disabled', false);
                    $('input[name="required_quantity"]').prop('disabled', false);
                    updateMaterialFields();
                }
            }

            // Inisialisasi awal
            toggleMaterialInput();

            $('#type').on('change', function() {
                toggleMaterialInput();
            });

            $('#material_name_select').on('change', function() {
                updateMaterialFields();
            });

            // Quick Add Unit
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
                        let newOption = new Option(unit.name, unit.name, true, true);
                        $('#unit-select').append(newOption).val(unit.name).trigger('change');
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

            // Spinner submit
            const form = document.querySelector(
                'form[action="{{ route('external_requests.update', $request->id) }}"]');
            const submitBtn = document.getElementById('submit-request-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;
            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Submitting...';
                });
            }
        });

        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('img-preview');
            const previewLink = document.getElementById('img-preview-link');
            const maxSize = 2 * 1024 * 1024; // 2 MB

            if (input.files && input.files[0]) {
                if (input.files[0].size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File too large',
                        text: 'Maximum file size is 2 MB.',
                    });
                    input.value = '';
                    if (preview) preview.src = '';
                    if (previewLink) previewLink.href = '#';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview) preview.src = e.target.result;
                    if (previewLink) {
                        previewLink.href = e.target.result;
                        preview.style.display = 'block';
                        previewLink.style.display = 'block';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                if (preview) preview.src = '';
                if (previewLink) previewLink.href = '#';
                if (preview) preview.style.display = 'none';
                if (previewLink) previewLink.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            Fancybox.bind("[data-fancybox='gallery']", {
                Toolbar: {
                    display: ["zoom", "download", "close"]
                },
                Thumbs: false,
                Image: {
                    zoom: true
                },
                Hash: false,
            });
        });
    </script>
@endpush
