@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-shield-check me-2"></i>Audit Log
                    </h5>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" id="eventFilter">
                            <option value="">All Events</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                            <option value="deleted">Deleted</option>
                            <option value="restored">Restored</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" id="modelFilter">
                            <option value="">All Models</option>
                            <option value="App\Models\Inventory">Inventory</option>
                            <option value="App\Models\MaterialRequest">Material Request</option>
                            <option value="App\Models\GoodsOut">Goods Out</option>
                            <option value="App\Models\GoodsIn">Goods In</option>
                            <option value="App\Models\Project">Project</option>
                            <option value="App\Models\User">User</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" id="dateFrom" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" id="dateTo" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-sm" id="filterBtn">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="clearBtn">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle" id="auditTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Model</th>
                                <th>Event</th>
                                <th>Changes</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Changes Modal -->
    <div class="modal fade" id="changesModal" tabindex="-1" aria-labelledby="changesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changesModalLabel">Change Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="changesContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const table = $('#auditTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('audit.index') }}",
                    data: function(d) {
                        d.event = $('#eventFilter').val();
                        d.auditable_type = $('#modelFilter').val();
                        d.date_from = $('#dateFrom').val();
                        d.date_to = $('#dateTo').val();
                    }
                },
                columns: [{
                        data: 'formatted_date',
                        name: 'created_at'
                    },
                    {
                        data: 'user_name',
                        name: 'user.username'
                    },
                    {
                        data: 'model_name',
                        name: 'auditable_type'
                    },
                    {
                        data: 'event_badge',
                        name: 'event'
                    },
                    {
                        data: 'changes',
                        name: 'changes',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'ip_address',
                        name: 'ip_address'
                    }
                ],
                order: [
                    []
                ],
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ]
            });

            // Filter functionality
            $('#filterBtn').on('click', function() {
                table.ajax.reload();
            });

            $('#clearBtn').on('click', function() {
                $('#eventFilter, #modelFilter, #dateFrom, #dateTo').val('');
                table.ajax.reload();
            });
        });

        function showChanges(auditId) {
            $('#changesContent').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            $.get(`{{ url('audit/changes') }}/${auditId}`)
                .done(function(data) {
                    let html = `
                        <div class="mb-3">
                            <strong>Model:</strong> ${data.model}<br>
                            <strong>Event:</strong> <span class="badge bg-primary">${data.event}</span><br>
                            <strong>Date:</strong> ${data.created_at}
                        </div>
                    `;

                    if (data.event === 'created') {
                        html += '<h6>New Values:</h6>';
                        html += formatValues(data.new_values);
                    } else if (data.event === 'updated') {
                        html += '<div class="row">';
                        html += '<div class="col-md-6"><h6>Old Values:</h6>' + formatValues(data.old_values) + '</div>';
                        html += '<div class="col-md-6"><h6>New Values:</h6>' + formatValues(data.new_values) + '</div>';
                        html += '</div>';
                    } else if (data.event === 'deleted') {
                        html += '<h6>Deleted Values:</h6>';
                        html += formatValues(data.old_values);
                    }

                    $('#changesContent').html(html);
                })
                .fail(function() {
                    $('#changesContent').html('<div class="alert alert-danger">Failed to load changes.</div>');
                });
        }

        function formatValues(values) {
            if (!values || Object.keys(values).length === 0) {
                return '<p class="text-muted">No data</p>';
            }

            let html = '<table class="table table-sm table-bordered">';
            Object.keys(values).forEach(key => {
                html += `<tr><td><strong>${key}:</strong></td><td>${values[key] || '<em>null</em>'}</td></tr>`;
            });
            html += '</table>';
            return html;
        }
    </script>
@endpush
