@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                    <i class="fas fa-boxes text-primary fs-5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Total Items</div>
                                <div class="h4 mb-0 fw-bold">{{ $stats['total'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                    <i class="fas fa-clock text-warning fs-5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Pending Review</div>
                                <div class="h4 mb-0 fw-bold text-warning">{{ $stats['pending'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                    <i class="fas fa-check-circle text-success fs-5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Approved</div>
                                <div class="h4 mb-0 fw-bold text-success">{{ $stats['approved'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                    <i class="fas fa-times-circle text-danger fs-5"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Rejected</div>
                                <div class="h4 mb-0 fw-bold text-danger">{{ $stats['rejected'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-filter gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Inventory Incoming</h2>
                            @if ($lastSync)
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>Last sync:
                                    {{ \Carbon\Carbon::parse($lastSync)->format('d M Y H:i') }}
                                </small>
                            @else
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Belum pernah disync</small>
                            @endif
                        </div>
                    </div>

                    <div class="ms-lg-auto">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-lg-end">
                            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-warehouse me-1"></i> Inventory Stock
                            </a>
                            @if (in_array(auth()->user()->role, ['super_admin', 'admin', 'admin_logistic']))
                                {{-- Approve selected rows (shown only when rows are checked) --}}
                                <button type="button" class="btn btn-success btn-sm d-none" id="btnBulkApproveSelected">
                                    <i class="bi bi-check2-all me-1"></i>
                                    Approve Selected (<span id="selectedCount">0</span>)
                                </button>
                                {{-- Approve all pending --}}
                                {{-- <button type="button" class="btn btn-outline-success btn-sm" id="btnBulkApproveAll"
                                    @if ($stats['pending'] === 0) disabled @endif
                                    title="Approve semua item pending dan push ke Inventory Stock">
                                    <i class="bi bi-check2-square me-1"></i>
                                    Approve All Pending
                                    @if ($stats['pending'] > 0)
                                        <span class="badge bg-warning text-dark ms-1">{{ $stats['pending'] }}</span>
                                    @endif
                                </button> --}}
                                <form action="{{ route('lark.staging.sync-inventory') }}" method="POST" class="d-inline"
                                    id="syncStagingForm">
                                    @csrf
                                    <button type="button" class="btn btn-info btn-sm flex-shrink-0" id="btnSyncStaging"
                                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                                        title="Sync data dari Lark ke staging (belum masuk ke inventory). Filter: Destination=BATAM, Status=Sent Out, DEPT!=Stock.">
                                        <i class="fas fa-sync me-1" id="syncStagingIcon"></i>
                                        <span id="syncStagingText">Sync from Lark</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Info alert -->
                <div class="alert alert-info border-0 py-2 mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Cara kerja:</strong> Data dari Lark disync ke halaman ini terlebih dahulu. Admin dapat mereview,
                    lalu <strong class="text-success">Approve</strong> untuk mendorong ke
                    <a href="{{ route('inventory-batch.index') }}" class="alert-link">Inventory Batch</a>,
                    atau <strong class="text-danger">Reject</strong> jika tidak sesuai.
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{!! session('success') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{!! $errors->first() !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filters -->
                <div class="row mb-3 g-2">
                    <div class="col-md-3">
                        <select id="filterReviewStatus" class="form-select form-select-sm select2">
                            <option value="">All Status Review</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="filterProject" class="form-control form-control-sm"
                            placeholder="Filter by Project...">
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <button type="button" id="btnResetFilter" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table id="stagingInventoryTable" class="table table-bordered table-hover" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;" class="text-center">
                                    <input type="checkbox" id="checkAllHeader" title="Select/Deselect All Visible">
                                </th>
                                <th>No</th>
                                <th>Item Name</th>
                                <th>Qty (Lark)</th>
                                <th>Received Qty</th>
                                <th>Price (RMB)</th>
                                <th>Order Date</th>
                                <th>PIC</th>
                                <th>Waybill</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Review Status</th>
                                <th>Note</th>
                                <th>Last Sync</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- Selection summary bar -->
                <div id="selectionBar" class="d-none mt-2 p-2 rounded bg-light border d-flex align-items-center gap-3">
                    <span class="text-muted small">
                        <i class="bi bi-check2-square me-1"></i>
                        <strong id="selectionBarCount">0</strong> item dipilih
                    </span>
                    <button type="button" class="btn btn-success btn-sm" id="btnSelectionApprove">
                        <i class="bi bi-check-lg me-1"></i>Approve Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" id="btnClearSelection">
                        <i class="bi bi-x-circle me-1"></i>Clear selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Note Modal for Approve/Reject -->
    <div class="modal fade" id="noteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noteModalTitle">Catatan (Opsional)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="noteModalReceivedQtyReminder" class="alert alert-warning py-2 small d-none mb-2">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Pastikan <strong>Received Qty</strong> dan <strong>Price</strong> sudah diisi sebelum Approve.
                    </div>
                    <textarea id="noteInput" class="form-control" rows="3" placeholder="Tambahkan catatan review..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnConfirmAction">Konfirmasi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal (combined name + unit) -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">
                        <i class="bi bi-pencil-fill me-2 text-primary"></i>Edit Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editItemId">
                    <div class="mb-3">
                        <label for="editItemName" class="form-label fw-semibold">Item Name <span
                                class="text-danger">*</span></label>
                        <input type="text" id="editItemName" class="form-control" placeholder="Nama item inventory"
                            maxlength="255">
                        <div class="invalid-feedback" id="editItemNameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="editItemUnit" class="form-label fw-semibold">Unit <span
                                class="text-danger">*</span></label>
                        <select id="editItemUnit" class="form-select select2-unit">
                            <option value="">-- Pilih Unit --</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="editItemUnitError"></div>
                    </div>
                    <div class="mb-1">
                        <label for="editItemPrice" class="form-label fw-semibold">Price (RMB)</label>
                        <div class="input-group">
                            <span class="input-group-text">&yen;</span>
                            <input type="number" id="editItemPrice" class="form-control" placeholder="0.00"
                                min="0" step="0.01">
                        </div>
                        <div class="form-text text-muted">Wajib diisi sebelum Approve. Kosongkan jika belum diketahui.
                        </div>
                        <div class="invalid-feedback d-block" id="editItemPriceError"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSaveEditItem">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="editItemSpinner"></span>
                        <i class="bi bi-floppy-fill me-1" id="editItemSaveIcon"></i>Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#filterReviewStatus').select2({
                theme: 'bootstrap-5',
                placeholder: 'All Status Review',
                allowClear: true,
                width: '100%'
            });

            // DataTable
            var table = $('#stagingInventoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('lark.staging.inventory') }}',
                    data: function(d) {
                        d.review_status = $('#filterReviewStatus').val();
                        d.project = $('#filterProject').val();
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false,
                        width: '40px',
                        className: 'text-center'
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '40px'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'received_qty_input',
                        name: 'received_qty',
                        orderable: false,
                        searchable: false,
                        width: '140px'
                    },
                    {
                        data: 'price',
                        name: 'price'
                    },
                    {
                        data: 'order_date',
                        name: 'order_date'
                    },
                    {
                        data: 'pic',
                        name: 'pic'
                    },
                    {
                        data: 'international_waybill',
                        name: 'international_waybill'
                    },
                    {
                        data: 'project_lark',
                        name: 'project_lark'
                    },
                    {
                        data: 'supplier_lark',
                        name: 'supplier_lark'
                    },
                    {
                        data: 'review_status_badge',
                        name: 'review_status',
                        orderable: false
                    },
                    {
                        data: 'review_note_display',
                        name: 'review_note',
                        orderable: false,
                        searchable: false,
                        width: '160px'
                    },
                    {
                        data: 'last_sync_at',
                        name: 'last_sync_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '110px'
                    }
                ],
                order: [
                    [14, 'desc']
                ],
                pageLength: 25,
                language: {
                    processing: '<div class="d-flex align-items-center gap-2"><div class="spinner-border spinner-border-sm text-primary"></div><span>Loading...</span></div>'
                },
                drawCallback: function() {
                    updateHeaderCheckbox();
                    updateSelectionBar();
                }
            });

            // Filter handlers
            $('#filterReviewStatus').on('change', function() {
                table.ajax.reload();
            });

            var filterProjectTimer;
            $('#filterProject').on('input', function() {
                clearTimeout(filterProjectTimer);
                filterProjectTimer = setTimeout(function() {
                    table.ajax.reload();
                }, 400);
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterReviewStatus').val('').trigger('change');
                $('#filterProject').val('');
                table.ajax.reload();
            });

            // ============================================================
            // Action helpers
            // ============================================================
            var pendingActionId = null;
            var pendingActionType = null; // 'approve' | 'reject'

            // Approve button
            $(document).on('click', '.btn-approve', function() {
                pendingActionId = $(this).data('id');
                pendingActionType = 'approve';
                $('#noteModalTitle').text('Approve Item');
                $('#noteInput').val('');
                $('#btnConfirmAction').removeClass('btn-danger').addClass('btn-success').text(
                    'Approve & Push to Inventory');
                // Show reminder about received_qty
                $('#noteModalReceivedQtyReminder').removeClass('d-none');
                $('#noteModal').modal('show');
            });

            // Reject button
            $(document).on('click', '.btn-reject', function() {
                pendingActionId = $(this).data('id');
                pendingActionType = 'reject';
                $('#noteModalTitle').text('Reject Item');
                $('#noteInput').val('');
                $('#btnConfirmAction').removeClass('btn-success').addClass('btn-danger').text('Reject');
                $('#noteModalReceivedQtyReminder').addClass('d-none');
                $('#noteModal').modal('show');
            });

            // Reset button (no note needed)
            $(document).on('click', '.btn-reset', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Reset ke Pending?',
                    text: 'Status item akan direset ke pending.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Reset',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        doAction('reset', id, '');
                    }
                });
            });

            // Save Received Qty button
            $(document).on('click', '.btn-save-received-qty', function() {
                var id = $(this).data('id');
                var $btn = $(this);
                var $input = $btn.closest('.input-group').find('.received-qty-input');
                var qty = $input.val();

                if (!qty || parseFloat(qty) <= 0) {
                    Swal.fire('Peringatan', 'Received Qty harus lebih dari 0.', 'warning');
                    return;
                }

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: '{{ url('lark/staging/inventory') }}/' + id + '/received-qty',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        received_qty: qty
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.html('<i class="bi bi-check-lg text-success"></i>');
                            setTimeout(function() {
                                $btn.prop('disabled', false).html(
                                    '<i class="bi bi-check-lg"></i>');
                            }, 1500);
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                            $btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i>');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON ? xhr.responseJSON.message :
                            'Terjadi kesalahan.';
                        Swal.fire('Error', msg, 'error');
                        $btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i>');
                    }
                });
            });

            // Allow Enter key to save received qty
            $(document).on('keydown', '.received-qty-input', function(e) {
                if (e.key === 'Enter') {
                    $(this).closest('.input-group').find('.btn-save-received-qty').trigger('click');
                }
            });

            // Confirm modal action
            $('#btnConfirmAction').on('click', function() {
                if (!pendingActionId || !pendingActionType) return;
                $('#noteModal').modal('hide');
                doAction(pendingActionType, pendingActionId, $('#noteInput').val());
            });

            function doAction(type, id, note) {
                var urls = {
                    approve: '{{ url('lark/staging/inventory') }}/' + id + '/approve',
                    reject: '{{ url('lark/staging/inventory') }}/' + id + '/reject',
                    reset: '{{ url('lark/staging/inventory') }}/' + id + '/reset',
                };

                $.ajax({
                    url: urls[type],
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        note: note
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                html: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                table.ajax.reload(null, false);
                                // Reload page to update stats
                                setTimeout(function() {
                                    location.reload();
                                }, 500);
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }

            // ============================================================
            // Checkbox selection helpers
            // ============================================================
            function getCheckedIds() {
                var ids = [];
                $('#stagingInventoryTable .row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                return ids;
            }

            function updateSelectionBar() {
                var ids = getCheckedIds();
                var count = ids.length;
                if (count > 0) {
                    $('#selectionBar').removeClass('d-none').addClass('d-flex');
                    $('#selectionBarCount').text(count);
                    $('#selectedCount').text(count);
                    $('#btnBulkApproveSelected').removeClass('d-none');
                } else {
                    $('#selectionBar').removeClass('d-flex').addClass('d-none');
                    $('#selectedCount').text(0);
                    $('#btnBulkApproveSelected').addClass('d-none');
                }
            }

            function updateHeaderCheckbox() {
                var total = $('#stagingInventoryTable .row-checkbox').length;
                var checked = $('#stagingInventoryTable .row-checkbox:checked').length;
                if (total === 0) {
                    $('#checkAllHeader').prop('indeterminate', false).prop('checked', false);
                } else if (checked === total) {
                    $('#checkAllHeader').prop('indeterminate', false).prop('checked', true);
                } else if (checked > 0) {
                    $('#checkAllHeader').prop('indeterminate', true).prop('checked', false);
                } else {
                    $('#checkAllHeader').prop('indeterminate', false).prop('checked', false);
                }
            }

            // Header checkbox: select/deselect all visible rows
            $(document).on('change', '#checkAllHeader', function() {
                var checked = $(this).prop('checked');
                $('#stagingInventoryTable .row-checkbox').prop('checked', checked);
                updateSelectionBar();
            });

            // Individual row checkbox
            $(document).on('change', '.row-checkbox', function() {
                updateHeaderCheckbox();
                updateSelectionBar();
            });

            // Clear selection button
            $('#btnClearSelection').on('click', function() {
                $('#stagingInventoryTable .row-checkbox').prop('checked', false);
                $('#checkAllHeader').prop('checked', false).prop('indeterminate', false);
                updateSelectionBar();
            });

            // ============================================================
            // Bulk Approve helpers
            // ============================================================
            function doBulkApprove(ids) {
                var isSelected = ids && ids.length > 0;
                var countLabel = isSelected ? ids.length : '{{ $stats['pending'] }}';

                Swal.fire({
                    title: isSelected ? 'Approve ' + ids.length + ' Item Terpilih?' :
                        'Approve Semua Pending?',
                    html: (isSelected ?
                            '<strong>' + ids.length + '</strong> item yang dipilih' :
                            'Semua <strong class="text-warning">{{ $stats['pending'] }}</strong> item pending'
                        ) +
                        ' akan di-approve dan masuk ke <strong>Inventory Stock</strong>.<br><br>Lanjutkan?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Approve!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $('#btnBulkApproveSelected, #btnBulkApproveAll, #btnSelectionApprove').prop('disabled',
                        true);

                    var postData = {
                        _token: '{{ csrf_token() }}'
                    };
                    if (isSelected) {
                        postData.ids = ids;
                    }

                    $.ajax({
                        url: '{{ route('lark.staging.inventory.bulk-approve') }}',
                        method: 'POST',
                        data: postData,
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Bulk Approve Selesai!',
                                    html: response.message,
                                    confirmButtonText: 'OK'
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Info', response.message, 'info');
                                $('#btnBulkApproveSelected, #btnBulkApproveAll, #btnSelectionApprove')
                                    .prop('disabled', false);
                            }
                        },
                        error: function(xhr) {
                            var msg = xhr.responseJSON ? xhr.responseJSON.message :
                                'Terjadi kesalahan.';
                            Swal.fire('Error', msg, 'error');
                            $('#btnBulkApproveSelected, #btnBulkApproveAll, #btnSelectionApprove')
                                .prop('disabled', false);
                        }
                    });
                });
            }

            // Approve selected (header button + selection bar button)
            $('#btnBulkApproveSelected, #btnSelectionApprove').on('click', function() {
                var ids = getCheckedIds();
                if (ids.length === 0) {
                    Swal.fire('Info', 'Tidak ada item yang dipilih.', 'info');
                    return;
                }
                doBulkApprove(ids);
            });

            // Approve all pending
            $('#btnBulkApproveAll').on('click', function() {
                doBulkApprove(null);
            });

            // ============================================================
            // Sync from Lark button
            // ============================================================
            $('#btnSyncStaging').on('click', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Sync dari Lark ke Staging?',
                    html: 'Data akan disync dari Lark Base ke tabel staging ini.<br><br>' +
                        '<strong>Filter dari Lark:</strong><br>' +
                        '• Destination: BATAM<br>' +
                        '• Status: Sent Out<br>' +
                        '• DEPT: Bukan Stock<br><br>' +
                        '<strong>Catatan:</strong><br>' +
                        '• Data <strong class="text-warning">TIDAK langsung masuk ke Inventory Stock</strong><br>' +
                        '• Admin perlu me-review dan Approve masing-masing item<br>' +
                        '• Item yang sudah di-Approve/Reject sebelumnya tidak akan direset statusnya<br><br>' +
                        'Lanjutkan?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0dcaf0',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Sync Sekarang!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var $btn = $('#btnSyncStaging');
                        var $icon = $('#syncStagingIcon');
                        var $text = $('#syncStagingText');

                        $btn.prop('disabled', true);
                        $icon.addClass('fa-spin');
                        $text.text('Syncing...');

                        $('#syncStagingForm').submit();
                    }
                });
            });

            // ============================================================
            // Combined Edit Item Modal (Name + Unit)
            // ============================================================
            var editItemModal = new bootstrap.Modal(document.getElementById('editItemModal'));

            // Initialize Select2 for the unit dropdown inside the modal
            $('#editItemModal').on('shown.bs.modal', function() {
                if (!$('#editItemUnit').hasClass('select2-hidden-accessible')) {
                    $('#editItemUnit').select2({
                        theme: 'bootstrap-5',
                        dropdownParent: $('#editItemModal'),
                        placeholder: '-- Pilih Unit --',
                        allowClear: false,
                        width: '100%'
                    });
                }
                $('#editItemName').trigger('focus');
            });

            $(document).on('click', '.btn-edit-item', function() {
                var id = $(this).data('id');
                var name = $(this).data('name') || '';
                var unit = $(this).data('unit') || '';
                var price = $(this).data('price') || '';

                $('#editItemId').val(id);
                $('#editItemName').val(name).removeClass('is-invalid');
                $('#editItemUnit').val(unit).trigger('change').removeClass('is-invalid');
                $('#editItemPrice').val(price).removeClass('is-invalid');
                $('#editItemNameError').text('');
                $('#editItemUnitError').text('');
                $('#editItemPriceError').text('');

                editItemModal.show();
            });

            $('#btnSaveEditItem').on('click', function() {
                var id = $('#editItemId').val();
                var name = $('#editItemName').val().trim();
                var unit = $('#editItemUnit').val();

                // Client-side validation
                var valid = true;
                if (!name) {
                    $('#editItemName').addClass('is-invalid');
                    $('#editItemNameError').text('Nama tidak boleh kosong.');
                    valid = false;
                } else {
                    $('#editItemName').removeClass('is-invalid');
                }
                if (!unit) {
                    $('#editItemUnit').addClass('is-invalid');
                    $('#editItemUnitError').text('Unit tidak boleh kosong.');
                    valid = false;
                } else {
                    $('#editItemUnit').removeClass('is-invalid');
                }
                if (!valid) return;

                var $btn = $(this);
                var $spinner = $('#editItemSpinner');
                var $icon = $('#editItemSaveIcon');
                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $icon.addClass('d-none');

                $.ajax({
                    url: '{{ url('lark/staging/inventory') }}/' + id + '/update-item',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: name,
                        unit: unit,
                        price: $('#editItemPrice').val()
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $icon.removeClass('d-none');

                        if (response.success) {
                            editItemModal.hide();
                            // Update data attributes on the row button
                            $('.btn-edit-item[data-id="' + id + '"]')
                                .data('name', response.name).attr('data-name', response.name)
                                .data('unit', response.unit).attr('data-unit', response.unit)
                                .data('price', response.price).attr('data-price', response
                                    .price);
                            // Update displayed name text (if rendered directly in table)
                            $('.staging-name-text[data-id="' + id + '"]').text(response.name);
                            // Reload table row
                            $('#stagingInventoryTable').DataTable().ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Item berhasil diperbarui.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $icon.removeClass('d-none');
                        var msg = xhr.responseJSON ? xhr.responseJSON.message :
                            'Terjadi kesalahan.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();


        });
    </script>
@endpush
