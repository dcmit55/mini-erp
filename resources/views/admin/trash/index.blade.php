@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-trash gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h3 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Trash Bin</h3>
                    </div>

                    <!-- Bulk Action Buttons -->
                    <div class="ms-lg-auto d-flex flex-wrap gap-2">
                        <form class="d-flex flex-wrap gap-2" id="bulk-action-form" method="POST"
                            action="{{ route('trash.bulkAction') }}">
                            @csrf
                            <input type="hidden" name="action" id="bulk-action-type">
                            <button type="button" class="btn btn-success btn-sm flex-shrink-0" id="bulk-restore-btn">
                                <i class="bi bi-bootstrap-reboot me-1"></i> Bulk Restore
                            </button>
                            <button type="button" class="btn btn-danger btn-sm flex-shrink-0" id="bulk-delete-btn">
                                <i class="bi bi-trash3 me-1"></i> Bulk Delete Permanently
                            </button>
                        </form>

                        <!-- ✨ BARU: Additional Actions -->
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                            data-bs-target="#deleteByDateModal">
                            <i class="bi bi-calendar-x me-1"></i> Delete by Date
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#purgeOldModal">
                            <i class="bi bi-hourglass-split me-1"></i> Purge Old Trash
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

                <!-- Trash Tables -->
                @foreach ([
            'inventories' => 'Inventory',
            'projects' => 'Project',
            'materialRequests' => 'Material Request',
            'goodsOuts' => 'Goods Out',
            'goodsIns' => 'Goods In',
            'materialUsages' => 'Material Usage',
            'currencies' => 'Currency',
            'users' => 'User',
            'employees' => 'Employee',
        ] as $var => $label)
                    <h5 class="mt-4">{{ $label }}</h5>
                    <table class="table table-hover table-sm align-middle" id="table-{{ $var }}">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>ID</th>
                                <th>Name/Info</th>
                                <th>Deleted At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($$var as $item)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="select-item" name="selected_ids[]"
                                            value="{{ $item->id }}">
                                        <input type="hidden" name="model_map[{{ $item->id }}]"
                                            value="{{ [
                                                'inventories' => 'inventory',
                                                'projects' => 'project',
                                                'materialRequests' => 'material_request',
                                                'goodsOuts' => 'goods_out',
                                                'goodsIns' => 'goods_in',
                                                'materialUsages' => 'material_usage',
                                                'currencies' => 'currency',
                                                'users' => 'user',
                                                'employees' => 'employee',
                                            ][$var] }}">
                                    </td>
                                    <td>{{ $item->id }}</td>
                                    <td>
                                        @if ($var === 'materialRequests' || $var === 'materialUsages' || $var === 'goodsOuts' || $var === 'goodsIns')
                                            {{ $item->inventory->name ?? '(no material)' }}
                                            @if ($item->project)
                                                ({{ $item->project->name ?? '(no project)' }})
                                            @endif
                                        @elseif(isset($item->name))
                                            {{ $item->name }}
                                        @elseif(isset($item->username))
                                            {{ $item->username }}
                                        @elseif(isset($item->remark))
                                            {{ $item->remark }}
                                        @elseif($var === 'employees')
                                            {{ $item->name }}
                                        @else
                                            (no info)
                                        @endif
                                    </td>
                                    <td>{{ $item->deleted_at }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if ($var === 'goodsOuts')
                                                {{-- Custom restore untuk Goods Out --}}
                                                <form action="{{ route('goods_out.restore', $item->id) }}" method="POST"
                                                    class="restore-form">
                                                    @csrf
                                                    <button type="button" class="btn btn-success btn-sm restore-btn"
                                                        title="Restore with Inventory Update">
                                                        <i class="bi bi-bootstrap-reboot"></i>
                                                    </button>
                                                </form>
                                            @elseif ($var === 'goodsIns')
                                                {{-- Custom restore untuk Goods In --}}
                                                <form action="{{ route('goods_in.restore', $item->id) }}" method="POST"
                                                    class="restore-form">
                                                    @csrf
                                                    <button type="button" class="btn btn-success btn-sm restore-btn"
                                                        title="Restore with Inventory Update">
                                                        <i class="bi bi-bootstrap-reboot"></i>
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Standard restore untuk model lain --}}
                                                <form action="{{ route('trash.restore') }}" method="POST"
                                                    class="restore-form">
                                                    @csrf
                                                    <input type="hidden" name="model"
                                                        value="{{ [
                                                            'inventories' => 'inventory',
                                                            'projects' => 'project',
                                                            'materialRequests' => 'material_request',
                                                            'goodsOuts' => 'goods_out',
                                                            'goodsIns' => 'goods_in',
                                                            'materialUsages' => 'material_usage',
                                                            'currencies' => 'currency',
                                                            'users' => 'user',
                                                            'employees' => 'employee',
                                                        ][$var] }}">
                                                    <input type="hidden" name="id" value="{{ $item->id }}">
                                                    <button class="btn btn-success btn-sm restore-btn" type="button"
                                                        title="Restore">
                                                        <i class="bi bi-bootstrap-reboot"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('trash.forceDelete') }}" method="POST"
                                                class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="model"
                                                    value="{{ [
                                                        'inventories' => 'inventory',
                                                        'projects' => 'project',
                                                        'materialRequests' => 'material_request',
                                                        'goodsOuts' => 'goods_out',
                                                        'goodsIns' => 'goods_in',
                                                        'materialUsages' => 'material_usage',
                                                        'currencies' => 'currency',
                                                        'users' => 'user',
                                                        'employees' => 'employee',
                                                    ][$var] }}">
                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                <button class="btn btn-danger btn-sm delete-btn" type="button"
                                                    title="Delete Permanently"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ✨ BARU: Delete by Date Range Modal -->
    <div class="modal fade" id="deleteByDateModal" tabindex="-1" aria-labelledby="deleteByDateLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="deleteByDateLabel">Delete Trash by Date Range</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deleteByDateForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" id="deleteDateFrom" name="date_from" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" id="deleteDateTo" name="date_to" required>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            All trash records deleted within the specified date range will be <strong>permanently
                                deleted</strong> and cannot be recovered.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteByDateBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✨ BARU: Purge Old Trash Modal -->
    <div class="modal fade" id="purgeOldModal" tabindex="-1" aria-labelledby="purgeOldLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title" id="purgeOldLabel">Purge Old Trash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="purgeOldForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Delete trash older than (days)</label>
                            <input type="number" class="form-control" id="purgeDays" name="days" min="1"
                                max="365" value="30" required>
                            <small class="text-muted">Enter number of days. Trash older than this will be permanently
                                deleted.</small>
                        </div>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <strong>Warning:</strong> All trash records older than the specified days will be
                            <strong>permanently deleted</strong> and cannot be recovered. This action cannot be undone.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmPurgeBtn">Purge</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            @foreach (['inventories', 'projects', 'materialRequests', 'goodsOuts', 'goodsIns', 'materialUsages', 'currencies', 'users', 'employees', 'categories'] as $var)
                $('#table-{{ $var }}').DataTable({
                    responsive: true,
                    stateSave: true,
                    order: [],
                    pageLength: 10,
                    columnDefs: [{
                        orderable: false,
                        targets: 0
                    }]
                });
            @endforeach

            // Bulk action scripts
            function getSelectedIds() {
                return $('.select-item:checked').map(function() {
                    return $(this).val();
                }).get();
            }

            function getModelMap() {
                let map = {};
                $('.select-item:checked').each(function() {
                    let id = $(this).val();
                    let model = $(`input[name="model_map[${id}]"]`).val();
                    map[id] = model;
                });
                return map;
            }

            function appendBulkInputs(form, selectedIds, modelMap) {
                // Hapus input hidden sebelumnya
                form.find('input[name="selected_ids[]"], input[name^="model_map"]').remove();
                // Tambahkan input hidden baru
                selectedIds.forEach(function(id) {
                    form.append(`<input type="hidden" name="selected_ids[]" value="${id}">`);
                    form.append(`<input type="hidden" name="model_map[${id}]" value="${modelMap[id]}">`);
                });
            }

            $('#bulk-restore-btn').on('click', function() {
                let selectedIds = getSelectedIds();
                let modelMap = getModelMap();
                if (selectedIds.length === 0) return;
                if (confirm('Restore selected items?')) {
                    appendBulkInputs($('#bulk-action-form'), selectedIds, modelMap);
                    $('#bulk-action-type').val('restore');
                    $('#bulk-action-form').submit();
                }
            });

            $('#bulk-delete-btn').on('click', function() {
                let selectedIds = getSelectedIds();
                let modelMap = getModelMap();
                if (selectedIds.length === 0) return;
                if (confirm('Delete permanently?')) {
                    appendBulkInputs($('#bulk-action-form'), selectedIds, modelMap);
                    $('#bulk-action-type').val('delete');
                    $('#bulk-action-form').submit();
                }
            });

            // ✨ BARU: Delete by Date Range
            $('#confirmDeleteByDateBtn').on('click', function() {
                const formData = $('#deleteByDateForm').serialize();

                if (!$('#deleteDateFrom').val() || !$('#deleteDateTo').val()) {
                    Swal.fire('Error', 'Please fill in both date fields.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Delete Trash by Date Range?',
                    html: `<strong>${$('#deleteDateFrom').val()}</strong> to <strong>${$('#deleteDateTo').val()}</strong><br>All matching trash records will be permanently deleted.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('trash.deleteByDateRange') }}",
                            method: 'POST',
                            data: formData,
                            success: function(response) {
                                Swal.fire('Success', response.message, 'success');
                                $('#deleteByDateModal').modal('hide');
                                setTimeout(() => location.reload(), 1500);
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Failed to delete trash.', 'error');
                            }
                        });
                    }
                });
            });

            // ✨ BARU: Purge Old Trash
            $('#confirmPurgeBtn').on('click', function() {
                const days = $('#purgeDays').val();

                Swal.fire({
                    title: 'Purge Old Trash?',
                    html: `Trash older than <strong>${days} days</strong> will be permanently deleted. This cannot be undone.`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, purge them!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('trash.purgeOldTrash') }}",
                            method: 'POST',
                            data: {
                                days: days,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Success', response.message, 'success');
                                $('#purgeOldModal').modal('hide');
                                setTimeout(() => location.reload(), 1500);
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Failed to purge trash.', 'error');
                            }
                        });
                    }
                });
            });

            // Event delegation for Restore button SweetAlert2
            $(document).on('click', '.restore-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to restore this item.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, restore it!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Event delegation for Delete Permanently button SweetAlert2
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone. The item will be permanently deleted.",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
