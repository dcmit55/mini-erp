@extends('layouts.app')
@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-chart-line gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Material Usage</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-lg-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('material_usage.export', request()->query()) }}"
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
                    <form id="filter-form" method="GET" action="{{ route('material_usage.index') }}" class="row g-2">
                        <div class="col-lg-2">
                            <select id="filter-material" name="material" class="form-select select2">
                                <option value="">All Materials</option>
                                @foreach ($materials as $material)
                                    <option value="{{ $material->id }}"
                                        {{ request('material') == $material->id ? 'selected' : '' }}>
                                        {{ $material->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-project" name="project" class="form-select select2">
                                <option value="">All Projects</option>
                                <option value="no_project" {{ request('project') == 'no_project' ? 'selected' : '' }}>
                                    No Project
                                </option>
                                @foreach ($projects as $project)
                                    @if ($project->id !== 'no_project')
                                        {{-- Skip the prepended "No Project" --}}
                                        <option value="{{ $project->id }}"
                                            {{ request('project') == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <button type="submit" class="btn btn-primary" id="filter-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                    aria-hidden="true"></span>
                                Filter
                            </button>
                            <a href="{{ route('material_usage.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <table class="table table-bordered table-hover table-striped" id="datatable">
                    <thead class="align-middle">
                        <tr>
                            <th></th>
                            <th>Material</th>
                            <th>Project</th>
                            <th>Used Quantity</th>
                            @if (auth()->user()->role === 'super_admin')
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @foreach ($usages as $usage)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $usage->inventory->name ?? '(No Material)' }}</td>
                                <td>
                                    <span class="badge {{ $usage->project_id ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $usage->project->name ?? 'No Project' }}
                                    </span>
                                </td>
                                <td style="font-weight: bold;">
                                    {{ rtrim(rtrim(number_format($usage->used_quantity, 2, '.', ''), '0'), '.') }}
                                    {{ $usage->inventory->unit ?? '(No Unit)' }}
                                </td>
                                @if (auth()->user()->role === 'super_admin')
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <form action="{{ route('material_usage.destroy', $usage->id) }}" method="POST"
                                                class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                    title="Delete"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
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
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Handle filter submit
            const filterBtn = $('#filter-btn');
            const filterSpinner = filterBtn.find('.spinner-border');
            const filterForm = $('#filter-form');
            const filterBtnHtml = filterBtn.html();

            if (filterForm.length && filterBtn.length && filterSpinner.length) {
                filterForm.on('submit', function() {
                    filterBtn.prop('disabled', true);
                    filterSpinner.removeClass('d-none');
                });
            }
        });
    </script>
@endpush
