@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">
                    {{ isset($project) ? 'Edit Project' : 'Create Project' }}</h2>
                <hr>
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
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <form method="POST" id="project-form"
                    action="{{ isset($project) ? route('projects.update', $project) : route('projects.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($project))
                        @method('PUT')
                    @endif
                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                value="{{ old('name', $project->name ?? '') }}" class="form-control" required>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label for="qty" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="qty" id="qty"
                                value="{{ old('qty', $project->qty ?? '') }}" class="form-control" required>
                            @error('qty')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label for="department_id" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;"
                                data-bs-target="#addDepartmentModal">
                                + Add Department
                            </button>
                            <select name="department_id" id="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id', $project->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="mb-3">
                            <label>Parts (optional)</label>
                            <div id="parts-wrapper">
                                @foreach ($project->parts as $part)
                                    <div class="input-group mb-2">
                                        <input type="text" name="parts[]" class="form-control"
                                            value="{{ $part->part_name }}" placeholder="Part name">
                                        <button type="button" class="btn btn-danger btn-remove-part">Remove</button>
                                    </div>
                                @endforeach
                                @if ($project->parts->isEmpty())
                                    <div class="input-group mb-2">
                                        <input type="text" name="parts[]" class="form-control" placeholder="Part name">
                                        <button type="button" class="btn btn-danger btn-remove-part"
                                            style="display:none;">Remove</button>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-part">Add
                                Part</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <label for="start_date" class="form-label">Start Date (optional)</label>
                            <input type="date" name="start_date" id="start_date"
                                value="{{ old('start_date', isset($project) ? $project->start_date : '') }}"
                                class="form-control">
                            @error('start_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label for="deadline" class="form-label">Deadline (optional)</label>
                            <input type="date" name="deadline" id="deadline"
                                value="{{ old('deadline', isset($project) ? $project->deadline : '') }}"
                                class="form-control">
                            @error('deadline')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label for="finish_date" class="form-label">Finish Date (hanya diisi saat project
                                selesai)</label>
                            <input type="date" name="finish_date" id="finish_date"
                                value="{{ old('finish_date', $project->finish_date ?? '') }}" class="form-control"
                                @if (isset($project) && $project->finish_date && auth()->user()->role !== 'super_admin') readonly @endif>
                            @error('finish_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label for="project_status_id" class="form-label">Project Status <span
                                class="text-danger">*</span></label>
                        @if (auth()->user()->role === 'super_admin')
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#addStatusModal"
                                style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">
                                + Add Status
                            </button>
                        @endif
                        <select name="project_status_id" id="project_status_id" class="form-select select2" required>
                            <option value="">Select Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}"
                                    {{ old('project_status_id', $project->project_status_id ?? '') == $status->id ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_status_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label for="img" class="form-label">Image (optional)</label>
                            <input type="file" name="img" class="form-control" id="img" accept="image/*"
                                onchange="previewImage(event)">
                            <a id="img-preview-link"
                                href="{{ isset($project) && $project->img ? asset('storage/' . $project->img) : '#' }}"
                                data-fancybox="gallery"
                                @if (isset($project) && $project->img) style="display: block;"
                                @else
                                    style="display: none;" @endif>
                                <img id="img-preview"
                                    src="{{ isset($project) && $project->img ? asset('storage/' . $project->img) : '#' }}"
                                    alt="Image Preview" class="mt-2 rounded" style="max-width: 200px;">
                            </a>
                            @error('img')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="project-submit-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                            aria-hidden="true"></span>
                        {{ isset($project) ? 'Update' : 'Save' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="departmentForm" method="POST" action="{{ route('departments.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label>Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="department_name" class="form-control" required>
                        <div id="department-error" class="text-danger mt-1" style="display:none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add Department</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Status Modal -->
    <div class="modal fade" id="addStatusModal" tabindex="-1" aria-labelledby="addStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="statusForm" method="POST" action="{{ route('project-statuses.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStatusModalLabel">Add New Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label>Status Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                        <div id="status-error" class="text-danger mt-1" style="display:none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add Status</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('img-preview');
            const previewLink = document.getElementById('img-preview-link');
            const maxSize = 2 * 1024 * 1024; // 2 MB

            if (input.files && input.files[0]) {
                // Validasi ukuran file
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
            // Validasi tanggal di frontend
            const startDateInput = document.getElementById('start_date');
            const deadlineInput = document.getElementById('deadline');
            const dateForm = startDateInput.closest('form');

            function validateDates(e) {
                const startDate = startDateInput.value;
                const deadline = deadlineInput.value;
                // Hanya validasi jika kedua field terisi
                if (startDate && deadline && startDate > deadline) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Date',
                        text: 'Start Date cannot be later than Deadline.',
                    }).then(() => {
                        startDateInput.focus();
                    });
                    return false;
                }
                return true;
            }

            if (dateForm) {
                dateForm.addEventListener('submit', validateDates);
            }

            // Inisialisasi Fancybox untuk gambar
            Fancybox.bind("[data-fancybox='gallery']", {
                Toolbar: {
                    display: [
                        "zoom", // Tombol zoom
                        "download", // Tombol download
                        "close" // Tombol close
                    ],
                },
                Thumbs: false, // Nonaktifkan thumbnail jika tidak diperlukan
                Image: {
                    zoom: true, // Aktifkan fitur zoom
                },
                Hash: false,
            });

            // Disable submit button and show spinner on form submit
            const form = document.getElementById('project-form');
            const submitBtn = document.getElementById('project-submit-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = submitBtn.textContent.trim() === 'Save' ?
                        ' Saving...' : ' Updating...';
                });
            }
        });

        document.getElementById('add-part').onclick = function() {
            let wrapper = document.getElementById('parts-wrapper');
            let div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `<input type="text" name="parts[]" class="form-control" placeholder="Part name">
        <button type="button" class="btn btn-danger btn-remove-part">Remove</button>`;
            wrapper.appendChild(div);
        };
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-part')) {
                e.target.parentElement.remove();
            }
        });

        $(document).ready(function() {
            $('#departmentForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let errorDiv = $('#department-error');
                errorDiv.hide().text('');
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: {
                        department_name: form.find('[name="department_name"]').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(department) {
                        // Tambahkan ke select2 dan pilih otomatis
                        let newOption = new Option(department.name, department.id, true, true);
                        $('#department_id').append(newOption).val(department.id).trigger(
                            'change');
                        $('#addDepartmentModal').modal('hide');
                        form[0].reset();
                        errorDiv.hide().text('');
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add department. Please try again.';
                        errorDiv.html(msg).show();
                    }
                });
            });
            // Reset error saat modal dibuka ulang
            $('#addDepartmentModal').on('shown.bs.modal', function() {
                $('#department-error').hide().text('');
            });

            $('#statusForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let errorDiv = $('#status-error');
                errorDiv.hide().text('');
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(status) {
                        let newOption = new Option(status.name, status.id, true, true);
                        $('#project_status_id').append(newOption).val(status.id).trigger(
                            'change');
                        $('#addStatusModal').modal('hide');
                        form[0].reset();
                        errorDiv.hide().text('');
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add status. Please try again.';
                        errorDiv.html(msg).show();
                    }
                });
            });
            $('#addStatusModal').on('shown.bs.modal', function() {
                $('#status-error').hide().text('');
            });
        });
    </script>
@endpush
