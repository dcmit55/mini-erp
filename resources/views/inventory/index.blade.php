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
                        <i class="fas fa-warehouse gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Inventory List</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        @if (auth()->user()->isLogisticAdmin() || auth()->user()->isReadOnlyAdmin())
                            <a href="{{ route('inventory.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="bi bi-plus-circle me-1"></i> Create Inventory
                            </a>
                            <button type="button" class="btn btn-success btn-sm flex-shrink-0" data-bs-toggle="modal"
                                data-bs-target="#importModal">
                                <i class="bi bi-filetype-xls me-1"></i> Import
                            </button>
                        @endif
                        <button type="button" id="export-btn" class="btn btn-outline-success btn-sm flex-shrink-0">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </button>
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
                    <form id="filter-form" class="row g-2">
                        <div class="col-lg-2">
                            <select name="category_filter" id="category_filter" class="form-select select2">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_finance', 'admin_procurement', 'admin']))
                            <div class="col-lg-2">
                                <select name="currency_filter" id="currency_filter" class="form-select select2">
                                    <option value="">All Currencies</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-lg-2">
                            <select name="supplier_filter" id="supplier_filter" class="form-select select2">
                                <option value="">All Suppliers</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="location_filter" id="location_filter" class="form-select select2">
                                <option value="">All Locations</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- <div class="col-lg-2">
                            <input type="number" id="min_quantity" class="form-control" placeholder="Min Qty">
                        </div> --}}
                        <div class="col-lg-2">
                            <input type="number" id="max_quantity" class="form-control" placeholder="Filter by Max Qty">
                        </div>
                        <div class="col-lg-2">
                            <input type="text" id="custom-search" class="form-control" placeholder="Search inventory...">
                        </div>
                        <div class="col-lg-2 d-flex align-items-end gap-2">
                            <button type="button" id="reset-filter" class="btn btn-outline-secondary btn-sm"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset</button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered" id="datatable">
                        <thead class="table-dark align-middle text-nowrap">
                            <tr>
                                <th width="50">#</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                @if (in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_finance', 'admin', 'admin_procurement']))
                                    <th>Unit Price</th>
                                @endif
                                <th>Supplier</th>
                                <th>Location</th>
                                <th>Remark</th>
                                <th>Updated At</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="align-middle">
                            {{-- Data akan diisi oleh DataTables AJAX --}}
                        </tbody>
                    </table>
                </div>

                <!-- Modal Show Image -->
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="img-container" class="mb-3"></div>
                                <div id="qr-code-container" class="mb-3"></div>
                                <a id="download-qr-code" class="btn btn-outline-primary btn-sm" href="#"
                                    download="qr-code.png" style="display: none;">
                                    <i class="bi bi-download"></i> Download QR Code
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Import Inventory via XLS -->
                <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('inventory.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="importModalLabel"><i class="bi bi-filetype-xls"
                                            style="color: rgb(0, 129, 65);"></i> Import Inventory</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="xls_file" class="form-label">Upload XLS File <span
                                                class="text-danger">*</span></label>
                                        <input type="file" name="xls_file" id="xls_file" class="form-control"
                                            required accept=".xls,.xlsx">
                                    </div>
                                    <p class="text-muted">
                                        You can Import Inventories via Excel file. Please ensure the file is formatted
                                        correctly.
                                        <br>
                                        <strong>Note:</strong> The file must be in XLS or XLSX format.
                                        <br>
                                        <strong>Column Template:</strong>
                                        <br>
                                        <code>Name, Category, Quantity, Unit, Price, Currency, Supplier, Location</code>
                                    </p>
                                    <a href="{{ route('inventory.template') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-download"></i> Download Template
                                    </a>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" id="import-btn">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                            aria-hidden="true"></span>
                                        Import
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable dengan server-side processing
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('inventory.index') }}",
                    data: function(d) {
                        // Add filter parameters
                        d.category_filter = $('#category_filter').val();
                        d.currency_filter = $('#currency_filter').val();
                        d.supplier_filter = $('#supplier_filter').val();
                        d.location_filter = $('#location_filter').val();
                        d.min_quantity = $('#min_quantity').val();
                        d.max_quantity = $('#max_quantity').val();
                        d.custom_search = $('#custom-search').val();
                    }
                },
                columns: [{
                        data: 'number',
                        name: 'number',
                        orderable: false,
                        searchable: false,
                        width: '2%',
                        className: 'text-center'
                    },
                    {
                        data: 'name',
                        name: 'name',
                        width: '26%'
                    },
                    {
                        data: 'category',
                        name: 'category.name',
                        width: '8%'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        width: '10%',
                    },
                    @if (in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_finance', 'admin', 'admin_procurement', 'admin']))
                        {
                            data: 'price',
                            name: 'price',
                            width: '10%',
                            orderable: true
                        },
                    @endif {
                        data: 'supplier',
                        name: 'supplier.name',
                        width: '15%'
                    },
                    {
                        data: 'location',
                        name: 'location.name',
                        width: '12%'
                    },
                    {
                        data: 'remark',
                        name: 'remark',
                        width: '15%',
                        orderable: false
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at',
                        width: '12%',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return data.timestamp || '';
                            }
                            if (data.display && data.tooltip) {
                                return `<span data-bs-toggle="tooltip" data-bs-placement="right" title="${data.tooltip}">${data.display}</span>`;
                            }
                            return data.display || '-';
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '12%',
                    }
                ],
                order: [
                    [8, 'desc']
                ],
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2"></i>No inventory data available</div>',
                    zeroRecords: '<div class="text-muted py-2">No matching records found</div>',
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                dom: 't<' +
                    '"row datatables-footer-row align-items-center"' +
                    '<"col-md-7 d-flex align-items-center gap-2 datatables-left"l<"vr-divider mx-2">i>' +
                    '<"col-md-5 dataTables_paginate justify-content-end"p>' +
                    '>',
                responsive: true,
                stateSave: false, // Disable state saving karena kita pakai filter sendiri
                drawCallback: function() {
                    // Reinitialize tooltips after table redraw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Filter functionality
            $('#category_filter, #currency_filter, #supplier_filter, #location_filter').on('change', function() {
                table.ajax.reload();
            });

            $('#min_quantity, #max_quantity').on('input', function() {
                table.ajax.reload();
            });

            $('#custom-search').on('input', function() {
                $('#datatable').DataTable().ajax.reload();
            });

            // Reset filter
            $('#reset-filter').on('click', function() {
                $('#category_filter, #currency_filter, #supplier_filter, #location_filter').val('').trigger(
                    'change');
                $('#min_quantity').val('');
                $('#max_quantity').val('');
                $('#custom-search').val('');
                table.ajax.reload();
            });

            // Export functionality
            $('#export-btn').on('click', function() {
                const filters = {
                    category_filter: $('#category_filter').val(),
                    currency_filter: $('#currency_filter').val(),
                    supplier_filter: $('#supplier_filter').val(),
                    location_filter: $('#location_filter').val(),
                    min_quantity: $('#min_quantity').val(),
                    max_quantity: $('#max_quantity').val(),
                    custom_search: $('#custom-search').val()
                };

                const queryParams = new URLSearchParams();

                Object.keys(filters).forEach(key => {
                    if (filters[key] && filters[key] !== '') {
                        queryParams.append(key, filters[key]);
                    }
                });

                const exportUrl = "{{ route('inventory.export') }}" +
                    (queryParams.toString() ? '?' + queryParams.toString() : '');

                window.location.href = exportUrl;
            });

            // Delete functionality dengan AJAX
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Generate URL dengan template yang benar
                        const deleteUrl = '/inventory/' + id;

                        $.ajax({
                            url: deleteUrl,
                            method: 'DELETE', // Atau gunakan 'method' instead of 'type'
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
                                    html: `"${name}" has been deleted.`,
                                    icon: 'success'
                                });
                                table.ajax.reload(null, false);
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

            // --- Spinner Import Button in Modal ---
            const importBtn = document.getElementById('import-btn');
            const importSpinner = importBtn ? importBtn.querySelector('.spinner-border') : null;
            const importForm = importBtn ? importBtn.closest('form') : null;
            const importBtnHtml = importBtn ? importBtn.innerHTML : '';

            if (importForm && importBtn && importSpinner) {
                importForm.addEventListener('submit', function() {
                    importBtn.disabled = true;
                    importSpinner.classList.remove('d-none');
                    importBtn.childNodes[2].textContent = ' Importing...';
                });
            }

            // Reset tombol Spinner Import saat modal dibuka ulang
            $('#importModal').on('shown.bs.modal', function() {
                if (importBtn) {
                    importBtn.disabled = false;
                    importBtn.innerHTML = importBtnHtml;
                }
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // Show Image Modal Handler
            $(document).on('click', '.btn-show-image', function() {
                // Reset modal content
                $('#img-container').html('');
                $('#qr-code-container').html('');
                $('#download-qr-code').hide();

                let img = $(this).data('img');
                let qrcode = $(this).data('qrcode');
                let name = $(this).data('name');

                $('#imageModalLabel').html(
                    `<i class="bi bi-image" style="margin-right: 5px; color: cornflowerblue;"></i> ${name}`
                );

                // Tampilkan gambar jika ada
                $('#img-container').html(img ?
                    `<a href="${img}" data-fancybox="gallery" data-caption="${name}">
                        <img src="${img}" alt="Image" class="img-fluid img-hover rounded" style="max-width:100%;">
                    </a>` :
                    '<span class="text-muted">No Image</span>'
                );

                // Tampilkan QR Code jika ada
                $('#qr-code-container').html(qrcode ?
                    `<div>
                        <img src="${qrcode}" alt="QR Code" class="img-fluid" style="max-width:100%;">
                    </div>` :
                    '<span class="text-muted">No QR Code</span>'
                );

                if (qrcode) {
                    $('#download-qr-code').attr('href', qrcode).show();
                }
            });

            // Initialize Bootstrap Tooltip
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Fancybox if available
            if (typeof Fancybox !== 'undefined') {
                Fancybox.bind("[data-fancybox='gallery']", {
                    Toolbar: {
                        display: [{
                                id: "counter",
                                position: "center"
                            },
                            "zoom",
                            "download",
                            "close"
                        ],
                    },
                    Thumbs: false,
                    Image: {
                        zoom: true,
                    },
                    Hash: false,
                });
            }
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
