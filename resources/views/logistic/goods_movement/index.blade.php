@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-primary shadow h-100">
                    <div class="card-body">
                        <div class="text-primary font-weight-bold text-uppercase mb-1">Today's Movements</div>
                        <div class="h3 mb-0">{{ $todayMovements }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-warning shadow h-100">
                    <div class="card-body">
                        <div class="text-warning font-weight-bold text-uppercase mb-1">Pending</div>
                        <div class="h3 mb-0">{{ $pendingCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-info shadow h-100">
                    <div class="card-body">
                        <div class="text-info font-weight-bold text-uppercase mb-1">This Week</div>
                        <div class="h3 mb-0">{{ $thisWeekCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-success shadow h-100">
                    <div class="card-body">
                        <div class="text-success font-weight-bold text-uppercase mb-1">Total Records</div>
                        <div class="h3 mb-0">{{ \App\Models\Logistic\GoodsMovement::count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow rounded">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="fas fa-dolly gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Goods Movement Tracker</h2>
                    </div>
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('goods-movement.export') }}" class="btn btn-outline-success btn-sm" title="Export CSV">
                            <i class="bi bi-file-earmark-csv me-1"></i> Export
                        </a>
                        <a href="{{ route('goods-movement.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add Movement
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filters -->
                <form method="GET" id="filterForm" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select name="department_filter" class="form-control form-control-sm" onchange="document.getElementById('filterForm').submit();">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department_filter') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="origin_filter" class="form-control form-control-sm" onchange="document.getElementById('filterForm').submit();">
                                <option value="">All Origins</option>
                                <option value="SG" {{ request('origin_filter') == 'SG' ? 'selected' : '' }}>SG (Singapore)</option>
                                <option value="BT" {{ request('origin_filter') == 'BT' ? 'selected' : '' }}>BT (Batam)</option>
                                <option value="CN" {{ request('origin_filter') == 'CN' ? 'selected' : '' }}>CN (China)</option>
                                <option value="Other" {{ request('origin_filter') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status_filter" class="form-control form-control-sm" onchange="document.getElementById('filterForm').submit();">
                                <option value="">All Status</option>
                                <option value="Pending" {{ request('status_filter') == 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Received" {{ request('status_filter') == 'Received' ? 'selected' : '' }}>Received</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('goods-movement.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-times me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

               <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-sm" id="dataTable">
                        <thead class="table-dark align-middle">
                            <tr>
                                <th width="80">#</th>
                                <th>Date</th>
                                <th>Department</th>
                                <th>Movement Type</th>
                                <th>Goods Type</th>
                                <th>Origin</th>
                                <th>Destination</th>
                                <th>Sender</th>
                                <th>Receiver</th>
                                <th>Items</th>
                                <th>Qty</th>
                                <th width="145">Sender Status</th>
                                <th width="145">Receiver Status</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

    <!-- Floating Action Button for Mobile -->
    <style>
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%);
            color: white;
            border-radius: 50%;
            display: none;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.4);
            text-decoration: none;
            font-size: 24px;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.6);
        }

        @media (max-width: 768px) {
            .fab {
                display: flex;
            }
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .border-left-primary {
            border-left: 4px solid #4e73df;
        }

        .border-left-warning {
            border-left: 4px solid #ffc107;
        }

        .border-left-info {
            border-left: 4px solid #17a2b8;
        }

        .border-left-success {
            border-left: 4px solid #28a745;
        }

        .gradient-icon {
            background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    <a href="{{ route('goods-movement.create') }}" class="fab" title="Add Movement">
        <i class="bi bi-plus"></i>
    </a>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#dataTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("goods-movement.index") }}',
                    data: function(d) {
                        d.department_filter = $('select[name="department_filter"]').val();
                        d.origin_filter = $('select[name="origin_filter"]').val();
                        d.status_filter = $('select[name="status_filter"]').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false },
                    { data: 'movement_date' },
                    { data: 'department' },
                    { data: 'movement_type', orderable: false },
                    { data: 'goods_type', orderable: false },
                    { data: 'origin' },
                    { data: 'destination', orderable: false },
                    { data: 'sender' },
                    { data: 'receiver' },
                    { data: 'total_items' },
                    { data: 'total_quantity' },
                    // ✅ TAMBAHAN: 2 Kolom Status Baru
                    { data: 'sender_status', orderable: false },
                    { data: 'receiver_status', orderable: false },
                    // ✅ AKHIR TAMBAHAN
                    { data: 'status', orderable: false },
                    { data: 'actions', orderable: false },
                ],
                columnDefs: [
                    { targets: [14], visible: true }
                ],
                language: {
                    emptyTable: 'No data available',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ records',
                }
            });

            // Handle status click
            $(document).on('click', '.status-badge', function() {
                const id = $(this).data('id');
                const currentStatus = $(this).data('status');
                const newStatus = currentStatus === 'Pending' ? 'Received' : 'Pending';

                $.ajax({
                    url: '{{ route("goods-movement.updateStatus", ":id") }}'.replace(':id', id),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: newStatus
                    },
                    success: function(response) {
                        table.ajax.reload();
                        showNotification('Status updated to ' + newStatus, 'success');
                    },
                    error: function() {
                        showNotification('Error updating status', 'danger');
                    }
                });
            });

            // Filter on change
            $('select[name="department_filter"], select[name="origin_filter"], select[name="status_filter"]').on('change', function() {
                table.ajax.reload();
            });
        });

        // ✅ TAMBAHAN: Function untuk update sender/receiver status
        function updateStatus(id, statusType, statusValue) {
            $.ajax({
                url: '{{ route("goods-movement.updateSenderReceiverStatus", ":id") }}'.replace(':id', id),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status_type: statusType,
                    status_value: statusValue
                },
                success: function(response) {
                    // Reload table untuk update UI
                    $('#dataTable').DataTable().ajax.reload();
                    showNotification(statusType + ' updated to ' + statusValue, 'success');
                },
                error: function(xhr) {
                    showNotification('Error updating status', 'danger');
                    console.error(xhr);
                }
            });
            return false;
        }

        function showNotification(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

            const html = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="bi bi-${icon} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            $('div[class*="container-fluid"]').prepend(html);

            setTimeout(() => {
                $('.alert').fadeOut(() => $('.alert').remove());
            }, 5000);
        }
    </script>
@endpush
