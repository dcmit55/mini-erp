@extends('layouts.app')

@section('title', 'Batch — ' . $warningBatch->batch_name)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('warning-batches.index') }}" class="btn btn-outline-secondary rounded-3 me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-0">{{ $warningBatch->batch_name }}</h4>
                    <p class="text-muted mb-0">
                        {{ $warningBatch->violationCategory->name }} &middot;
                        {{ $warningBatch->incident_date->format('d F Y') }} &middot;
                        Created by {{ $warningBatch->creator?->name }}
                    </p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 d-flex align-items-center px-4 py-3 mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <div class="flex-grow-1">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Stats --}}
            @php
                $letters  = $warningBatch->warningLetters;
                $approved = $letters->whereIn('status', ['approved','acknowledged'])->count();
                $pending  = $letters->where('status', 'pending_approval')->count();
                $rejected = $letters->where('status', 'rejected')->count();
                $draft    = $letters->where('status', 'draft')->count();
                $acked    = $letters->where('status', 'acknowledged')->count();
            @endphp

            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Total</h6>
                                    <h3 class="mb-0">{{ $warningBatch->total_employees }}</h3>
                                </div>
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-3">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Pending</h6>
                                    <h3 class="mb-0">{{ $pending }}</h3>
                                </div>
                                <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-3">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Approved</h6>
                                    <h3 class="mb-0">{{ $approved }}</h3>
                                </div>
                                <div class="icon-shape bg-success bg-opacity-10 text-success rounded-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Acknowledged</h6>
                                    <h3 class="mb-0">{{ $acked }}</h3>
                                </div>
                                <div class="icon-shape bg-info bg-opacity-10 text-info rounded-3">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Rejected</h6>
                                    <h3 class="mb-0">{{ $rejected }}</h3>
                                </div>
                                <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-3">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Draft</h6>
                                    <h3 class="mb-0">{{ $draft }}</h3>
                                </div>
                                <div class="icon-shape bg-secondary bg-opacity-10 text-secondary rounded-3">
                                    <i class="fas fa-pen"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Incident Description --}}
            @if($warningBatch->incident_description)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-2" style="font-size:.7rem;letter-spacing:.05em">Incident Description</h6>
                    <p class="mb-0" style="white-space:pre-line">{{ $warningBatch->incident_description }}</p>
                </div>
            </div>
            @endif

            {{-- Per-letter table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <h6 class="mb-0 fw-semibold">Per-Employee Details</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4 text-center" width="50">No</th>
                                    <th class="border-0">Employee</th>
                                    <th class="border-0 d-none d-xl-table-cell">Department</th>
                                    <th class="border-0">SP Level</th>
                                    <th class="border-0">Letter No.</th>
                                    <th class="border-0 d-none d-lg-table-cell">Valid Until</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($letters as $i => $letter)
                                <tr class="align-middle">
                                    <td class="ps-4 text-center">
                                        <span class="text-muted">{{ $i + 1 }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $letter->employee->name }}</span>
                                        <br><small class="text-muted d-xl-none">{{ $letter->employee->department?->name }}</small>
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        <span class="text-muted">{{ $letter->employee->department?->name ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $spColors = [1=>['bg'=>'info','text'=>'white'],2=>['bg'=>'warning','text'=>'dark'],3=>['bg'=>'warning','text'=>'dark'],4=>['bg'=>'danger','text'=>'white']];
                                            $sc = $spColors[$letter->sp_level] ?? ['bg'=>'secondary','text'=>'white'];
                                        @endphp
                                        <span class="badge bg-{{ $sc['bg'] }} text-{{ $sc['text'] }} px-2 py-1 rounded-pill">SP{{ $letter->sp_level }}</span>
                                    </td>
                                    <td>
                                        <span class="font-monospace" style="font-size:.78rem">{{ $letter->letter_number }}</span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="{{ $letter->valid_until?->diffInDays(now()) <= 14 ? 'text-warning fw-semibold' : 'text-muted' }}">
                                            {{ $letter->valid_until?->format('d/m/Y') ?? '—' }}
                                        </small>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = ['draft'=>'secondary','pending_approval'=>'warning','approved'=>'success','acknowledged'=>'primary','rejected'=>'danger','expired'=>'dark'];
                                            $sBg = $statusColors[$letter->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $sBg }} px-2 py-1 rounded-pill {{ $letter->status === 'pending_approval' ? 'text-dark' : '' }}">
                                            {{ $letter->statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('warning-letters.show', $letter) }}"
                                               class="btn btn-sm btn-outline-info border-0 px-2"
                                               data-bs-toggle="tooltip" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(in_array($letter->status, ['approved','acknowledged']))
                                                <a href="{{ route('warning-letters.pdf', $letter) }}"
                                                   class="btn btn-sm btn-outline-danger border-0 px-2"
                                                   data-bs-toggle="tooltip" title="Download PDF" target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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
