@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="fas fa-dolly gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Goods In Records</h2>
                    </div>
                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('goods_in.create_independent') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Goods In
                        </a>
                        <a href="{{ route('goods_in.export', request()->query()) }}"
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
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="mb-3">
                    <form id="filter-form" method="GET" action="{{ route('goods_in.index') }}" class="row g-2">
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
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        {{ request('project') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-qty" name="qty" class="form-select select2">
                                <option value="">All Quantities</option>
                                @foreach ($quantities as $qty)
                                    <option value="{{ $qty }}" {{ request('qty') == $qty ? 'selected' : '' }}>
                                        {{ $qty }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-returned-by" name="returned_by" class="form-select select2">
                                <option value="">All Returned By</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->username }}"
                                        {{ request('returned_by') == $user->username ? 'selected' : '' }}>
                                        {{ ucfirst($user->username) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <input type="text" id="filter-returned-at" name="returned_at" class="form-control"
                                value="{{ request('returned_at') }}" placeholder="Returned At Date" autocomplete="off">
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <button type="submit" class="btn btn-primary" id="filter-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                    aria-hidden="true"></span>
                                Filter
                            </button>
                            <a href="{{ route('goods_in.index') }}" class="btn btn-outline-secondary btn-sm"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <table class="table table-striped table-hover table-bordered" id="datatable">
                    <thead class="align-middle text-nowrap">
                        <tr>
                            <th></th>
                            <th>Material</th>
                            <th>Qty Returned/In</th>
                            <th>From Project</th>
                            <th>Returned/In By</th>
                            <th>Returned/In At</th>
                            <th>Remark</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @foreach ($goodsIns as $goodsIn)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    @if ($goodsIn->goodsOut && $goodsIn->goodsOut->inventory)
                                        {{ $goodsIn->goodsOut->inventory->name }}
                                    @elseif($goodsIn->inventory)
                                        {{ $goodsIn->inventory->name }}
                                    @else
                                        <span class="text-danger">(no material)</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($goodsIn->goodsOut && $goodsIn->goodsOut->inventory)
                                        {{ rtrim(rtrim(number_format($goodsIn->quantity, 2, '.', ''), '0'), '.') }}
                                        {{ $goodsIn->goodsOut->inventory->unit }}
                                    @elseif($goodsIn->inventory)
                                        {{ rtrim(rtrim(number_format($goodsIn->quantity, 2, '.', ''), '0'), '.') }}
                                        {{ $goodsIn->inventory->unit }}
                                    @else
                                        {{ rtrim(rtrim(number_format($goodsIn->quantity, 2, '.', ''), '0'), '.') }}
                                    @endif
                                </td>
                                <td>
                                    @if ($goodsIn->goodsOut && $goodsIn->goodsOut->project)
                                        {{ $goodsIn->goodsOut->project->name }}
                                    @elseif($goodsIn->project)
                                        {{ $goodsIn->project->name }}
                                    @else
                                        <span class="text-secondary">No Project (Restock/Supplier)</span>
                                    @endif
                                </td>
                                @php
                                    $deptName = $users[$goodsIn->returned_by]->department->name ?? '-';
                                @endphp
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="right" style="cursor: pointer;"
                                        title="{{ $deptName }}">
                                        {{ ucfirst($goodsIn->returned_by) }}
                                    </span>
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($goodsIn->returned_at)->translatedFormat('d F Y, H:i') }}
                                </td>
                                <td>
                                    @if ($goodsIn->remark)
                                        {{ $goodsIn->remark }}
                                    @else
                                        <span class="text-danger">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if (auth()->user()->username === $goodsIn->returned_by ||
                                                in_array(auth()->user()->role, ['admin_logistic', 'super_admin', 'admin_finance', 'admin']))
                                            <a href="{{ route('goods_in.edit', $goodsIn->id) }}"
                                                class="btn btn-sm btn-warning" data-bs-toggle="tooltip"
                                                data-bs-placement="bottom" title="Edit"><i
                                                    class="bi bi-pencil-square"></i></a>
                                        @endif
                                        @if (!$goodsIn->goods_out_id && in_array(auth()->user()->role, ['admin_logistic', 'super_admin', 'admin_finance', 'admin']))
                                            <form action="{{ route('goods_in.destroy', $goodsIn->id) }}" method="POST"
                                                class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                    @if (auth()->user()->isReadOnlyAdmin()) disabled @endif
                                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete">
                                                    <span class="spinner-border spinner-border-sm me-1 d-none"
                                                        role="status" aria-hidden="true"></span>
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        @endif
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
        #datatable th {
            font-size: 0.90rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        #datatable td {
            vertical-align: middle;
        }

        /* Batasi lebar kolom tertentu jika perlu */
        #datatable th,
        #datatable td {
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
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

            const filterForm = document.getElementById('filter-form');
            const filterBtn = document.getElementById('filter-btn');
            const spinner = filterBtn ? filterBtn.querySelector('.spinner-border') : null;

            if (filterForm && filterBtn && spinner) {
                filterForm.addEventListener('submit', function() {
                    filterBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    filterBtn.childNodes[2].textContent = ' Filtering...';
                });
            }

            // SweetAlert for delete confirmation
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let btn = $(this);
                let form = btn.closest('form');
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
                        // Disable tombol dan tampilkan spinner
                        btn.prop('disabled', true);
                        btn.find('.spinner-border').removeClass('d-none');
                        form.submit();
                    }
                });
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            flatpickr("#filter-returned-at", {
                dateFormat: "Y-m-d",
                allowInput: true,
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
