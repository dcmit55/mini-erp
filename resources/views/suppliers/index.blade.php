@extends('layouts.app')

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .table-responsive {
            overflow: hidden;
        }

        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.375rem;
            --bs-pagination-color: #6c757d;
            --bs-pagination-bg: #fff;
            --bs-pagination-border-width: 1px;
            --bs-pagination-border-color: #dee2e6;
            --bs-pagination-border-radius: 0.375rem;
            --bs-pagination-hover-color: #495057;
            --bs-pagination-hover-bg: #e9ecef;
            --bs-pagination-hover-border-color: #dee2e6;
            --bs-pagination-focus-color: #495057;
            --bs-pagination-focus-bg: #e9ecef;
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(143, 18, 254, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #8F12FE;
            --bs-pagination-active-border-color: #4A25AA;
            --bs-pagination-disabled-color: #6c757d;
            --bs-pagination-disabled-bg: #fff;
            --bs-pagination-disabled-border-color: #dee2e6;
        }

        .page-link {
            transition: all 0.15s ease-in-out;
        }

        .page-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            border-color: #8F12FE;
            box-shadow: 0 2px 4px rgba(143, 18, 254, 0.3);
        }

        .vr-divider {
            width: 1px;
            height: 24px;
            background: #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }

        .datatables-footer-row {
            border-top: 1px solid #eee;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        @media (max-width: 767.98px) {
            .datatables-footer-row {
                flex-direction: column !important;
                gap: 0.5rem;
            }

            .datatables-left {
                flex-direction: column !important;
                gap: 0.5rem;
            }

            .vr-divider {
                display: none;
            }

            .dataTables_paginate {
                justify-content: center !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-4">
        <!-- Card Wrapper -->
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="bi bi-truck gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Suppliers List</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Add Supplier
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
                    <form id="filter-form" method="GET" action="{{ route('suppliers.index') }}" class="row g-2">
                        <div class="col-lg-3">
                            <select name="location_id" id="location_filter" class="form-select select2">
                                <option value="">All Locations</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <select name="status" id="status_filter" class="form-select select2">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                                <option value="blacklisted" {{ request('status') == 'blacklisted' ? 'selected' : '' }}>
                                    Blacklisted</option>
                            </select>
                        </div>
                        <!-- Submit and Reset Buttons -->
                        <div class="col-lg-2 d-flex gap-2">

                            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary w-3">Reset</a>
                        </div>

                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="datatable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Supplier Code</th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Address</th>
                                <th>Referral Link</th>
                                <th>Lead Time</th>
                                <th>Remark</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="align-middle">
                            @foreach ($suppliers as $index => $supplier)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $supplier->supplier_code }}</td>
                                    <td>{{ $supplier->name }}</td>
                                    <td>{{ $supplier->location ? $supplier->location->name : '-' }}</td>
                                    <td>{{ $supplier->address }}</td>
                                    <td>
                                        @if ($supplier->referral_link)
                                            <a href="{{ $supplier->formatted_referral_link }}" target="_blank"
                                                rel="noopener" data-bs-toggle="tooltip"
                                                title="{{ $supplier->referral_link }}">
                                                <i class="bi bi-link-45deg gradient-icon"
                                                    style="font-size: 1.5rem; vertical-align: middle;"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $supplier->lead_time_days ? $supplier->lead_time_days . ' days' : '-' }}
                                    </td>
                                    <td>{{ $supplier->remark }}</td>
                                    <td>
                                        <span class="badge bg-{{ $supplier->status_badge }}">
                                            {{ ucfirst($supplier->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('suppliers.edit', $supplier->id) }}"
                                                class="btn btn-warning btn-sm" data-bs-toggle="tooltip"
                                                data-bs-placement="bottom" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete">
                                                <i class="bi bi-trash3"></i>
                                            </button>
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

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Initialize DataTable tanpa server-side processing (non-AJAX)
                $('#datatable').DataTable({
                    paging: true,

                    info: true,
                    lengthMenu: [10, 15, 25, 50, 100],
                    pageLength: 15,
                    order: [
                        []
                    ],
                    responsive: true
                });

                // Auto-submit form on filter change
                $('#location_filter, #status_filter').on('change', function() {
                    $('#filter-form').submit();
                });


                // Delete functionality dengan AJAX
                $(document).on('click', '.btn-delete', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `You want to delete supplier "${name}"?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const deleteUrl = '/suppliers/' + id;

                            $.ajax({
                                url: deleteUrl,
                                method: 'DELETE',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                        'content'),
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                success: function(response) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        html: `Supplier "${name}" has been deleted.`,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location
                                            .reload(); // Reload page to update table
                                    });
                                },
                                error: function(xhr) {
                                    console.error('Delete error:', xhr);
                                    let errorMsg = 'Something went wrong.';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMsg = xhr.responseJSON.message;
                                    } else if (xhr.responseText) {
                                        errorMsg = xhr.responseText;
                                    }
                                    Swal.fire('Error!', errorMsg, 'error');
                                }
                            });
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
                }).on('select2:open', function() {
                    // Auto-focus search field
                    setTimeout(() => {
                        const searchField = document.querySelector('.select2-search__field');
                        if (searchField) searchField.focus();
                    }, 100);
                });

                // Initialize Bootstrap Tooltip
                $('[data-bs-toggle="tooltip"]').tooltip();
            });

            document.addEventListener("DOMContentLoaded", function() {
                // Initialize Bootstrap Tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    @endpush
