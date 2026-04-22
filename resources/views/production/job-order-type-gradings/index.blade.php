@extends('layouts.app')

@section('title', 'Job Order Type Gradings')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Job Order Type Gradings</h5>
                    <p class="text-muted small mb-0">
                        Data synced from Lark
                        @if($lastSync)
                            &middot; Last sync: {{ $lastSync->format('d/m/Y H:i') }}
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div id="syncStatusBadge" class="d-none small"></div>
                    @can('production.jo.edit')
                        <button type="button" class="btn btn-sm btn-outline-primary px-3" id="syncBtn"
                            data-sync-url="{{ route('job-order-type-gradings.sync') }}">
                            <i class="fas fa-sync-alt me-1"></i> Sync from Lark
                        </button>
                    @endcan
                </div>
            </div>

            <!-- Flash messages -->
            @if(session('success'))
                <div class="alert alert-success py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close ms-auto btn-sm" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
                    <button type="button" class="btn-close ms-auto btn-sm" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close ms-auto btn-sm" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('job-order-type-gradings.index') }}">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search grade, grading, job type..."
                                        value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    @if(request()->filled('search'))
                                        <a href="{{ route('job-order-type-gradings.index', request()->except(['search', 'page'])) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="department_id" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary px-3">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                @if(request()->anyFilled(['search', 'category_id', 'department_id']))
                                    <a href="{{ route('job-order-type-gradings.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info bar -->
            @if(!$lastSync)
                <div class="alert alert-warning py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No data yet. Click <strong class="mx-1">Sync from Lark</strong> to fetch the latest data.
                </div>
            @endif

            <!-- Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="border-0 ps-4" style="width: 50px;">No</th>
                                    <th class="border-0">Job Type Grade</th>
                                    <th class="border-0">Score</th>
                                    <th class="border-0">Grading</th>
                                    <th class="border-0">Job Type</th>
                                    <th class="border-0">Product Sub Category</th>
                                    <th class="border-0">Category</th>
                                    <th class="border-0">Department(s)</th>
                                    <th class="border-0">Parent Items</th>
                                    <th class="border-0 pe-4">Last Sync</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($gradings as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="table-number">
                                                {{ $loop->iteration + ($gradings->currentPage() - 1) * $gradings->perPage() }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $item->job_type_grade ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @if($item->score !== null)
                                                <span class="badge bg-primary px-2 py-1">{{ number_format($item->score, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->grading)
                                                @php
                                                    $gradingColor = [
                                                        'A' => 'success',
                                                        'B' => 'primary',
                                                        'C' => 'warning',
                                                        'D' => 'danger',
                                                    ][strtoupper(trim($item->grading))] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $gradingColor }} px-3 py-1">{{ $item->grading }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->job_type ?? '-' }}</td>
                                        <td>{{ $item->product_sub_category ?? '-' }}</td>
                                        <td>
                                            @if($item->category)
                                                <span class="badge bg-light text-dark border px-2 py-1">{{ $item->category->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($item->departments as $dept)
                                                <span class="badge bg-info text-dark px-2 py-1 me-1">{{ $dept->name }}</span>
                                            @empty
                                                <span class="text-muted">-</span>
                                            @endforelse
                                        </td>
                                        <td>{{ $item->parent_items ? \Illuminate\Support\Str::limit($item->parent_items, 30) : '-' }}</td>
                                        <td class="pe-4 text-muted">
                                            {{ $item->last_sync_at ? $item->last_sync_at->format('d/m/Y H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-layer-group fa-3x mb-3"></i>
                                                <h6>No Data Found</h6>
                                                @if(request()->anyFilled(['search', 'category_id', 'department_id']))
                                                    <p class="small">Try adjusting your filters</p>
                                                    <a href="{{ route('job-order-type-gradings.index') }}" class="btn btn-sm btn-outline-primary px-4">
                                                        <i class="fas fa-times me-1"></i>Clear Filters
                                                    </a>
                                                @else
                                                    <p class="small">Start by syncing data from Lark</p>
                                                    @can('production.jo.edit')
                                                        <button type="button" class="btn btn-sm btn-outline-primary px-4" id="syncBtnEmpty">
                                                            <i class="fas fa-sync-alt me-1"></i>Sync from Lark
                                                        </button>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($gradings->hasPages())
                    <div class="card-footer bg-white border-0 py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing {{ $gradings->firstItem() }} to {{ $gradings->lastItem() }} of {{ $gradings->total() }} entries
                            </div>
                            <div>
                                {{ $gradings->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<style>
    .table-number {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        background-color: #eef2ff;
        color: #4f46e5;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        text-align: center;
        transition: all 0.2s;
    }

    tr:hover .table-number {
        background-color: #4f46e5;
        color: white;
    }

    .table th {
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 0.75rem 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .table td {
        padding: 0.75rem 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    .btn-sm {
        font-size: 0.75rem;
    }

    .badge {
        font-weight: 500;
        font-size: 0.7rem;
    }

    .form-control-sm, .form-select-sm {
        font-size: 0.75rem;
    }
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function syncStart() {
        $('#syncBtn, #syncBtnEmpty').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-1"></i> Syncing...');
        $('#syncStatusBadge')
            .removeClass('d-none')
            .html('<span class="badge bg-warning text-dark"><i class="fas fa-spinner fa-spin me-1"></i> Proccessing... </span>');
    }

    function syncDone(msg) {
        $('#syncBtn, #syncBtnEmpty').prop('disabled', false)
            .html('<i class="fas fa-sync-alt me-1"></i> Sync from Lark');
        $('#syncStatusBadge')
            .html('<span class="badge bg-success"><i class="fas fa-check me-1"></i>' + msg + '</span>');
        setTimeout(function () { location.reload(); }, 1500);
    }

    function syncFailed(msg) {
        $('#syncBtn, #syncBtnEmpty').prop('disabled', false)
            .html('<i class="fas fa-sync-alt me-1"></i> Sync from Lark');
        $('#syncStatusBadge')
            .html('<span class="badge bg-danger"><i class="fas fa-times me-1"></i>' + msg + '</span>');
    }

    function doSync() {
        var url = $('#syncBtn').data('sync-url');
        syncStart();
        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            timeout: 120000,
            success: function (res) { syncDone(res.message); },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message : 'Sync gagal, coba lagi.';
                syncFailed(msg);
            }
        });
    }

    $('#syncBtn, #syncBtnEmpty').on('click', doSync);
});
</script>
@endpush
