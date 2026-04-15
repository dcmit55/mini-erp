@extends('layouts.app')

@section('title', 'Bulk Warning Letter')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Bulk Warning Letter</h4>
                    <p class="text-muted mb-0">Bulk warning letter generation history</p>
                </div>
                <a href="{{ route('warning-batches.create') }}" class="btn btn-primary rounded-3 px-4">
                    <i class="fas fa-plus me-2"></i>Create Bulk SP
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">

                    @if(session('success'))
                        <div class="alert alert-success border-0 rounded-0 m-0 d-flex align-items-center px-4 py-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <div class="flex-grow-1">{{ session('success') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4 text-center" width="50">No</th>
                                    <th class="border-0">Batch Name</th>
                                    <th class="border-0 d-none d-lg-table-cell">Category</th>
                                    <th class="border-0 d-none d-lg-table-cell">Incident Date</th>
                                    <th class="border-0 text-center">Employees</th>
                                    <th class="border-0">Status Summary</th>
                                    <th class="border-0 d-none d-xl-table-cell">Created By</th>
                                    <th class="border-0 d-none d-xl-table-cell">Created</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $start = ($batches->currentPage() - 1) * $batches->perPage() + 1; @endphp
                                @forelse($batches as $i => $batch)
                                @php
                                    $letters  = $batch->warningLetters;
                                    $approved = $letters->whereIn('status', ['approved','acknowledged'])->count();
                                    $pending  = $letters->where('status', 'pending_approval')->count();
                                    $rejected = $letters->where('status', 'rejected')->count();
                                    $draft    = $letters->where('status', 'draft')->count();
                                @endphp
                                <tr class="align-middle">
                                    <td class="ps-4 text-center">
                                        <span class="text-muted">{{ $start + $i }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $batch->batch_name }}</span>
                                        @if($batch->incident_description)
                                            <br><small class="text-muted">{{ Str::limit($batch->incident_description, 55) }}</small>
                                        @endif
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="text-muted">{{ $batch->violationCategory->name }}</small>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="text-muted">{{ $batch->incident_date->format('d/m/Y') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary px-2 py-1 rounded-pill">{{ $batch->total_employees }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($approved > 0)<span class="badge bg-success px-2 py-1 rounded-pill">{{ $approved }} approved</span>@endif
                                            @if($pending  > 0)<span class="badge bg-warning text-dark px-2 py-1 rounded-pill">{{ $pending }} pending</span>@endif
                                            @if($rejected > 0)<span class="badge bg-danger px-2 py-1 rounded-pill">{{ $rejected }} rejected</span>@endif
                                            @if($draft    > 0)<span class="badge bg-secondary px-2 py-1 rounded-pill">{{ $draft }} draft</span>@endif
                                        </div>
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        <small class="text-muted">{{ $batch->creator?->name }}</small>
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        <small class="text-muted">{{ $batch->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('warning-batches.show', $batch) }}"
                                           class="btn btn-sm btn-outline-info border-0 px-2"
                                           data-bs-toggle="tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                        No batches yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($batches->hasPages())
                <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center px-4 py-3">
                    <small class="text-muted">
                        Showing {{ $batches->firstItem() }}–{{ $batches->lastItem() }} of {{ $batches->total() }} records
                    </small>
                    {{ $batches->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
</script>
@endsection
