@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-project-diagram gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Projects</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-lg-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Project
                        </a>
                        @if (in_array(auth()->user()->role, ['super_admin', 'admin']))
                            <form action="{{ route('projects.sync.lark') }}" method="POST" class="d-inline"
                                id="syncLarkForm">
                                @csrf
                                <button type="button" class="btn btn-info btn-sm flex-shrink-0" id="btnSyncLark"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                                    title="Sync projects from Lark Base">
                                    <i class="fas fa-sync me-1" id="syncIcon"></i>
                                    <span id="syncText">Sync from Lark</span>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('projects.export', request()->query()) }}"
                            class="btn btn-outline-success btn-sm flex-shrink-0">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
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

                <div class="mb-3">
                    <form id="filter-form" method="GET" action="{{ route('projects.index') }}" class="row g-2">
                        <div class="col-lg-2">
                            <select id="filter-quantity" name="quantity" class="form-select select2">
                                <option value="">All Quantity</option>
                                @foreach ($allQuantities as $qty)
                                    <option value="{{ $qty }}" {{ request('quantity') == $qty ? 'selected' : '' }}>
                                        {{ $qty }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-department" name="department" class="form-select select2">
                                <option value="">All Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->name }}"
                                        {{ request('department') == $dept->name ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-status" name="status" class="form-select select2">
                                <option value="">All Status</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}"
                                        {{ request('status') == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('projects.index') }}" class="btn btn-secondary"
                                title="Reset All Filters">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <table class="table table-sm table-hover align-middle" id="datatable">
                    <thead class="table-light align-middle">
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <th>Sales</th>
                            <th>Department</th>
                            <th class="text-center">Quantity</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Stage</th>
                            <th>Project Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $project->name }}</td>
                                <td>
                                    @if ($project->sales)
                                        <span class="badge bg-info" style="font-weight:500;">
                                            {{ $project->sales }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($project->department)
                                        @include('components.department-badge', [
                                            'department' => $project->department->name,
                                        ])
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $project->qty === null ? '-' : $project->qty }}</td>
                                <td>{{ $project->deadline ? \Carbon\Carbon::parse($project->deadline)->translatedFormat('d F Y') : '-' }}
                                </td>
                                <td>
                                    @if ($project->status)
                                        <span class="badge {{ $project->status->badgeClass() }}" style="font-weight:500;">
                                            {{ $project->status->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $project->stage ?? '-' }}</td>
                                <td>
                                    @include('components.project-status-badges', [
                                        'statuses' => $project->project_status,
                                    ])
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $project->created_by ?? '-' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        {{-- <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-warning"
                                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit"><i
                                                class="bi bi-pencil-square"></i></a> --}}
                                        @if (auth()->user()->username === $project->created_by ||
                                                // permission action delete data project
                                                auth()->user()->role === 'super_admin' ||
                                                auth()->user()->role === 'admin')
                                            <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                                class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- <button type="button" class="btn btn-info btn-sm btn-show-image"
                                            title="View Image"
                                            data-img="{{ $project->img ? asset('storage/' . $project->img) : '' }}"
                                            data-name="{{ $project->name }}">
                                            <i class="bi bi-file-earmark-image"></i>
                                        </button> --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <style>
        .artisan-action {
            transition: all 0.3s ease;
        }

        .artisan-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:disabled {
            cursor: not-allowed !important;
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable({
                responsive: true,
                stateSave: true,
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // SweetAlert for delete confirmation
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let form = $(this).closest('form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.btn-show-image', function() {
                const imgSrc = $(this).data('img'); // Ambil URL gambar
                const imgName = $(this).data('name'); // Ambil nama gambar

                if (imgSrc) {
                    // Buat elemen Fancybox secara dinamis
                    Fancybox.show([{
                        src: imgSrc,
                        type: "image",
                        caption: imgName, // Tambahkan caption
                        downloadSrc: imgSrc, // URL untuk tombol download
                    }], {
                        Toolbar: {
                            display: [
                                "zoom", // Tombol zoom
                                "fullscreen", // Tombol fullscreen
                                "download", // Tombol download
                                "close", // Tombol close
                            ],
                        },
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'No image available!',
                    });
                }
            });
        });

        // Artisan Actions
        function initializeArtisanActions() {
            document.querySelectorAll('.artisan-action').forEach(button => {
                button.addEventListener('click', function() {
                    const action = this.dataset.action;

                    Swal.fire({
                        title: 'Processing...',
                        text: `Executing ${action}...`,
                        icon: 'info',
                        scrollbarPadding: false,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`/artisan/${action}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Reload halaman setelah sync berhasil
                                    if (action === 'lark:fetch-job-orders') {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            console.error('Error:', error);
                        });
                });
            });
        }

        // Initialize saat DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeArtisanActions();

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Sync from Lark button handler
            const syncBtn = document.getElementById('btnSyncLark');
            const syncForm = document.getElementById('syncLarkForm');

            if (syncBtn && syncForm) {
                syncBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (confirm(
                            'Sync all projects from Lark?\n\nThis will:\n- Fetch latest data from Lark Base\n- Create/update projects\n- Mark with "Sync from Lark"\n- Deactivate missing projects\n\nContinue?'
                        )) {
                        // Show loading state
                        const syncIcon = document.getElementById('syncIcon');
                        const syncText = document.getElementById('syncText');

                        syncBtn.disabled = true;
                        syncIcon.classList.add('fa-spin');
                        syncText.textContent = 'Syncing...';
                        syncBtn.classList.remove('btn-info');
                        syncBtn.classList.add('btn-secondary');

                        // Submit form
                        syncForm.submit();
                    }
                });
            }
        });
    </script>
@endpush
