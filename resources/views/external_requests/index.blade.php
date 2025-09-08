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

        /* Custom Badge Colors */
        .bg-purple {
            background-color: #6f42c1 !important;
            color: white !important;
        }

        .bg-indigo {
            background-color: #6610f2 !important;
            color: white !important;
        }

        .bg-pink {
            background-color: #d63384 !important;
            color: white !important;
        }

        .bg-orange {
            background-color: #fd7e14 !important;
            color: white !important;
        }

        .bg-teal {
            background-color: #20c997 !important;
            color: white !important;
        }

        .bg-cyan {
            background-color: #0dcaf0 !important;
            color: white !important;
        }

        .bg-lime {
            background-color: #84cc16 !important;
            color: white !important;
        }

        .bg-amber {
            background-color: #f59e0b !important;
            color: white !important;
        }

        .bg-rose {
            background-color: #f43f5e !important;
            color: white !important;
        }

        .bg-emerald {
            background-color: #10b981 !important;
            color: white !important;
        }

        .bg-violet {
            background-color: #8b5cf6 !important;
            color: white !important;
        }

        .bg-sky {
            background-color: #0ea5e9 !important;
            color: white !important;
        }

        /* Badge hover effects */
        .badge {
            transition: all 0.2s ease-in-out;
        }

        .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
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
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="fas fa-external-link-alt gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">External Requests</h2>
                    </div>
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('external_requests.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Request
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

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered" id="datatable">
                        <thead class="table-dark align-middle text-nowrap">
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Material Name</th>
                                <th class="text-start">Required Qty</th>
                                <th class="text-start">Stock Level</th>
                                <th>Project</th>
                                <th>Requested By</th>
                                <th>Requested At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $index => $req)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $req->type)) }}</td>
                                    <td>{{ $req->material_name }}</td>
                                    <td class="text-start">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{{ $req->unit }}">
                                            {{ $req->required_quantity }}
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{{ $req->unit }}">
                                            {{ $req->stock_level }}
                                        </span>
                                    </td>
                                    <td>{{ $req->project->name ?? '-' }}</td>
                                    <td>{{ $req->user->username ?? '-' }}</td>
                                    <td>{{ $req->created_at->format('d M Y, H:i') }}</td>
                                    <td>
                                        <a href="{{ route('external_requests.edit', $req->id) }}"
                                            class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $req->id }}"
                                            data-name="{{ $req->material_name }}" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $req->id }}"
                                            action="{{ route('external_requests.destroy', $req->id) }}" method="POST"
                                            style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No external request data available</div>',
                    zeroRecords: '<div class="text-muted py-2">No matching records found</div>',
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                dom: 't<"row datatables-footer-row align-items-center"<"col-md-7 d-flex align-items-center gap-2 datatables-left"l<"vr-divider mx-2">i><"col-md-5 dataTables_paginate justify-content-end"p>>',
            });

            // SweetAlert delete
            $('.btn-delete').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                Swal.fire({
                    title: 'Delete?',
                    text: `Delete external request "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete-form-' + id).submit();
                    }
                });
            });

            // Inisialisasi tooltip
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
