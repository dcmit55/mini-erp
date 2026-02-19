@extends('layouts.app')

@section('title', 'Attendance Logs')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Attendance Logs</h5>
        <div>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-upload me-1"></i> Import
            </button>
            <a href="{{ route('attendance-logs.export') }}?{{ http_build_query(request()->except('page')) }}" class="btn btn-sm btn-success">
                <i class="fas fa-download me-1"></i> Export
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
            <form method="GET" action="{{ route('attendance-logs.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Employee</label>
                        <select name="employee_id" class="form-select form-select-sm">
                            <option value="">All Employees</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    [{{ $emp->employee_no }}] {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary" title="Filter">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request()->anyFilled(['start_date', 'end_date', 'employee_id']))
                            <a href="{{ route('attendance-logs.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear filters">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                        <a href="{{ route('attendance-logs.index', ['all' => 1]) }}" class="btn btn-sm btn-info {{ request()->has('all') ? 'active' : '' }}" title="Show all data">
                            <i class="fas fa-list"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Info data yang ditampilkan -->
    @if(request()->has('all'))
        <div class="alert alert-secondary py-2 small" role="alert">
            <i class="fas fa-eye me-1"></i> Menampilkan semua data (tanpa filter). 
            <a href="{{ route('attendance-logs.index') }}" class="alert-link">Kembali ke data import terakhir</a>.
        </div>
    @elseif(request()->filled('start_date') && request()->filled('end_date'))
        <div class="alert alert-info py-2 small" role="alert">
            <i class="fas fa-calendar-alt me-1"></i> Menampilkan data dengan filter tanggal {{ request('start_date') }} s/d {{ request('end_date') }}.
        </div>
    @elseif($latestImportSource)
        <div class="alert alert-info py-2 small" role="alert">
            <i class="fas fa-clock me-1"></i> Menampilkan data dari import terakhir: <strong>{{ $latestImportSource }}</strong>. 
            <a href="{{ route('attendance-logs.index', ['all' => 1]) }}" class="alert-link">Lihat semua data</a>.
        </div>
    @else
        <div class="alert alert-warning py-2 small" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> Belum ada data. Silakan import data terlebih dahulu.
        </div>
    @endif

    <!-- Tabel -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3" style="width: 60px;">No</th>
                            <th>Date</th>
                            <th>Emp No</th>
                            <th>Employee Name</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Total Hrs</th>
                            <th class="pe-3">Source</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-3 text-muted">{{ $logs->firstItem() + $loop->index }}</td>
                                <td>{{ $log->date->format('d/m/Y') }}</td>
                                <td>{{ $log->employee->employee_no ?? '-' }}</td>
                                <td>{{ $log->employee->name ?? '-' }}</td>
                                <td>{{ $log->clock_in ? \Carbon\Carbon::parse($log->clock_in)->format('H:i') : '-' }}</td>
                                <td>{{ $log->clock_out ? \Carbon\Carbon::parse($log->clock_out)->format('H:i') : '-' }}</td>
                                <td>{{ $log->total_hours ? number_format($log->total_hours, 2) : '-' }}</td>
                                <td class="pe-3 text-truncate" style="max-width: 120px;" title="{{ $log->import_source }}">
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($log->import_source, 15) }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                    <p class="small text-muted mb-1">No attendance logs found</p>
                                    @if(request()->anyFilled(['start_date', 'end_date', 'employee_id']))
                                        <a href="{{ route('attendance-logs.index') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-times me-1"></i>Clear Filters
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                                            <i class="fas fa-upload me-1"></i>Import Data
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination super kecil, tanpa prev/next, gaya join -->
        @if($logs->hasPages())
            <div class="card-footer bg-white py-2 px-3">
                <div class="d-flex justify-content-end align-items-center">
                    <div class="pagination-wrapper">
                        {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Import Attendance Logs</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <label for="file" class="form-label small">File (xlsx, xls, csv)</label>
                        <input type="file" name="file" id="file" class="form-control form-control-sm" required accept=".xlsx,.xls,.csv">
                        <div class="form-text small">
                            Kolom: <strong>Name, Date, Clock In, Clock Out</strong>. Karyawan tidak aktif diabaikan.<br>
                            <span class="text-warning">Catatan: File .xls akan otomatis dikonversi jika memungkinkan.</span>
                        </div>
                    </div>
                    <div id="importProgress" class="progress d-none" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%">Processing...</div>
                    </div>
                    <div id="importResult" class="mt-2 small"></div>
                    
                    <!-- Tempat untuk menampilkan failed rows -->
                    <div id="failedRowsContainer" class="mt-3 d-none">
                        <h6 class="small fw-bold">Baris Gagal:</h6>
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Tanggal</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Error</th>
                                    </tr>
                                </thead>
                                <tbody id="failedRowsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="importBtn">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Pagination super kecil - paksa dengan !important */
.pagination-wrapper .pagination {
    margin-bottom: 0 !important;
    display: inline-flex !important;
    gap: 1px !important;
}

/* Sembunyikan Previous dan Next */
.pagination-wrapper .page-item:first-child,
.pagination-wrapper .page-item:last-child {
    display: none !important;
}

/* Gaya tombol seperti join (saling menempel) */
.pagination-wrapper .page-link {
    padding: 0.15rem 0.4rem !important;
    font-size: 0.65rem !important;
    line-height: 1.2 !important;
    border-radius: 0 !important;
    margin-left: -1px !important;
    border: 1px solid #dee2e6 !important;
}

.pagination-wrapper .page-item:first-child .page-link {
    border-top-left-radius: 0.2rem !important;
    border-bottom-left-radius: 0.2rem !important;
    margin-left: 0 !important;
}

.pagination-wrapper .page-item:last-child .page-link {
    border-top-right-radius: 0.2rem !important;
    border-bottom-right-radius: 0.2rem !important;
}

/* Hover dan active */
.pagination-wrapper .page-link:hover {
    background-color: #e9ecef !important;
    z-index: 2;
}

.pagination-wrapper .active .page-link {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: white !important;
    z-index: 3;
}

/* Hilangkan shadow focus */
.pagination-wrapper .page-link:focus {
    box-shadow: none !important;
}
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var $btn = $('#importBtn');
        var $progress = $('#importProgress');
        var $result = $('#importResult');
        var $failedContainer = $('#failedRowsContainer');
        var $failedBody = $('#failedRowsBody');

        $btn.prop('disabled', true);
        $progress.removeClass('d-none');
        $result.html('');
        $failedContainer.addClass('d-none');
        $failedBody.empty();

        $.ajax({
            url: "{{ route('attendance-logs.import.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $progress.addClass('d-none');
                $result.html('<div class="alert alert-success py-1 px-2 mb-0">' + response.message + '</div>');
                setTimeout(function() {
                    $('#importModal').modal('hide');
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                $progress.addClass('d-none');
                
                if (xhr.responseJSON && xhr.responseJSON.failed_rows && xhr.responseJSON.failed_rows.length > 0) {
                    // Tampilkan failed rows
                    var failedRows = xhr.responseJSON.failed_rows;
                    $.each(failedRows, function(index, item) {
                        var row = item.row;
                        var errorMsg = item.error;
                        var nama = row.name || '-';
                        var tanggal = row.date || '-';
                        var clockIn = row.clock_in || '-';
                        var clockOut = row.clock_out || '-';
                        
                        $failedBody.append('<tr>' +
                            '<td>' + nama + '</td>' +
                            '<td>' + tanggal + '</td>' +
                            '<td>' + clockIn + '</td>' +
                            '<td>' + clockOut + '</td>' +
                            '<td class="text-danger">' + errorMsg + '</td>' +
                            '</tr>');
                    });
                    $failedContainer.removeClass('d-none');
                    
                    var message = xhr.responseJSON.message || 'Import completed with errors.';
                    $result.html('<div class="alert alert-warning py-1 px-2 mb-0">' + message + '</div>');
                } else {
                    // Error biasa tanpa detail baris
                    var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload failed.';
                    $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' + errorMsg + '</div>');
                }
                
                $btn.prop('disabled', false);
            }
        });
    });

    $('#importModal').on('hidden.bs.modal', function () {
        $('#importForm')[0].reset();
        $('#importResult').empty();
        $('#importProgress').addClass('d-none');
        $('#failedRowsContainer').addClass('d-none');
        $('#failedRowsBody').empty();
        $('#importBtn').prop('disabled', false);
    });
});
</script>
@endpush