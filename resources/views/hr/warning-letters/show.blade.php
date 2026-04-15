@extends('layouts.app')

@section('title', 'Detail — ' . $warningLetter->letter_number)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xl-10">

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row g-4">

                {{-- LEFT: Formal Letter Preview --}}
                <div class="col-lg-8">

                    {{-- Breadcrumb / nav --}}
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <a href="{{ route('warning-letters.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </a>
                        <div class="d-flex gap-2">
                            @if($warningLetter->isEditable())
                                <a href="{{ route('warning-letters.edit', $warningLetter) }}" class="btn btn-sm btn-outline-primary rounded-2">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                            @endif
                            @if(in_array($warningLetter->status, ['approved','acknowledged']))
                                <a href="{{ route('warning-letters.pdf', $warningLetter) }}" class="btn btn-sm btn-danger rounded-2" target="_blank">
                                    <i class="bi bi-file-pdf me-1"></i> Download PDF
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Formal Letter Card --}}
                    @php
                        $spBorderColor = [1=>'#0dcaf0', 2=>'#ffc107', 3=>'#fd7e14', 4=>'#dc3545'];
                        $spBgColor     = [1=>'#e8f9fc', 2=>'#fff9e6', 3=>'#fff3e6', 4=>'#fdf0f0'];
                        $spTextColor   = [1=>'#0c7a8f', 2=>'#856404', 3=>'#7d3c00', 4=>'#842029'];
                        $bc = $spBorderColor[$warningLetter->sp_level] ?? '#6c757d';
                        $bg = $spBgColor[$warningLetter->sp_level]     ?? '#f8f9fa';
                        $tc = $spTextColor[$warningLetter->sp_level]   ?? '#333';
                    @endphp

                    <div class="card border-0 shadow rounded-3 overflow-hidden">

                        {{-- Letter Header --}}
                        <div style="background: {{ $bg }}; border-bottom: 3px solid {{ $bc }};" class="px-4 pt-4 pb-3">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background:{{ $bc }}; color:{{ $tc }}; font-size:.75rem; letter-spacing:.03em;">
                                            {{ $warningLetter->spLabel }}
                                        </span>
                                        @php
                                            $statusColors = \App\Models\Hr\WarningLetter::STATUS_COLORS;
                                            $sBg = $statusColors[$warningLetter->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge rounded-pill bg-{{ $sBg }} px-3 py-2 {{ $warningLetter->status === 'pending_approval' ? 'text-dark' : '' }}" style="font-size:.75rem;">
                                            {{ $warningLetter->statusLabel }}
                                        </span>
                                        @if($warningLetter->batch_id)
                                            <a href="{{ route('warning-batches.show', $warningLetter->batch) }}"
                                               class="badge rounded-pill bg-light text-dark border px-3 py-2 text-decoration-none" style="font-size:.75rem;">
                                                <i class="bi bi-people-fill me-1"></i>Batch
                                            </a>
                                        @endif
                                    </div>
                                    <h5 class="fw-bold mb-0 font-monospace" style="color:{{ $tc }}; font-size:1.1rem;">{{ $warningLetter->letter_number }}</h5>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Issued</div>
                                    <div class="fw-semibold small">{{ $warningLetter->issued_date?->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body px-4 py-4">

                            {{-- Employee Info Block --}}
                            <div class="row g-0 mb-4">
                                <div class="col-md-6 pe-md-3">
                                    <div class="p-3 rounded-2 h-100" style="background:#f8f9fa; border-left: 3px solid {{ $bc }};">
                                        <div class="text-uppercase text-muted mb-2" style="font-size:.65rem;letter-spacing:.08em;font-weight:600;">Employee</div>
                                        <div class="fw-bold" style="font-size:1rem;">{{ $warningLetter->employee->name }}</div>
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-person-badge me-1"></i>{{ $warningLetter->employee->employee_no }}
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-building me-1"></i>{{ $warningLetter->employee->department?->name ?? '—' }}
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-briefcase me-1"></i>{{ $warningLetter->employee->position ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 ps-md-3 mt-3 mt-md-0">
                                    <div class="p-3 rounded-2 h-100" style="background:#f8f9fa;">
                                        <div class="text-uppercase text-muted mb-2" style="font-size:.65rem;letter-spacing:.08em;font-weight:600;">Validity</div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted small">Violation Date</span>
                                            <span class="small fw-medium">{{ $warningLetter->violation_date->format('d M Y') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted small">Issued Date</span>
                                            <span class="small fw-medium">{{ $warningLetter->issued_date?->format('d M Y') ?? '—' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted small">Valid Until</span>
                                            <span class="small fw-medium">
                                                @if($warningLetter->valid_until)
                                                    @if($warningLetter->valid_until->isPast())
                                                        <span class="text-danger fw-semibold">{{ $warningLetter->valid_until->format('d M Y') }}</span>
                                                        <span class="badge bg-danger ms-1" style="font-size:.6rem;">Expired</span>
                                                    @elseif($warningLetter->valid_until->diffInDays(now()) <= 14)
                                                        <span class="text-warning fw-semibold">{{ $warningLetter->valid_until->format('d M Y') }}</span>
                                                        <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">{{ $warningLetter->valid_until->diffForHumans() }}</span>
                                                    @else
                                                        {{ $warningLetter->valid_until->format('d M Y') }}
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Divider with label --}}
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="text-uppercase text-muted" style="font-size:.65rem;letter-spacing:.08em;font-weight:600;white-space:nowrap;">Violation</span>
                                <div class="flex-grow-1 border-top"></div>
                            </div>

                            {{-- Violation Category --}}
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="rounded-2 p-2" style="background:{{ $bg }};">
                                    <i class="bi bi-tag-fill" style="color:{{ $tc }};"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size:.72rem;">Category</div>
                                    <div class="fw-semibold">{{ $warningLetter->violationCategory->name }}</div>
                                </div>
                            </div>

                            {{-- Reason --}}
                            <div class="rounded-2 p-3 mb-3" style="background:#f8f9fa; border-left:3px solid #dee2e6;">
                                <div class="text-muted mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600;">Description</div>
                                <p class="mb-0" style="white-space:pre-line; line-height:1.7;">{{ $warningLetter->reason }}</p>
                            </div>

                            {{-- SP4 final warning notice --}}
                            @if($warningLetter->sp_level === 4)
                            <div class="rounded-2 p-3 d-flex gap-2" style="background:#fdf0f0; border:1px solid #f5c6cb;">
                                <i class="bi bi-exclamation-triangle-fill text-danger mt-1"></i>
                                <div class="small text-danger">
                                    <strong>Final Warning.</strong> This is SP4. Any further violation may result in termination of employment.
                                </div>
                            </div>
                            @endif

                        </div>

                        {{-- Letter Footer --}}
                        <div class="card-footer bg-transparent border-top px-4 py-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Created by <strong>{{ $warningLetter->creator?->name ?? '—' }}</strong>
                            </small>
                            @if($warningLetter->acknowledgment)
                                <small class="text-primary">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    Acknowledged · {{ $warningLetter->acknowledgment->acknowledged_at->format('d M Y, H:i') }}
                                </small>
                            @else
                                <small class="text-muted">Not yet acknowledged</small>
                            @endif
                        </div>

                    </div>

                </div>

                {{-- RIGHT: Actions --}}
                <div class="col-lg-4">

                    @if(in_array($warningLetter->status, ['draft', 'approved']))
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-transparent border-bottom px-3 py-2">
                            <span class="fw-semibold small text-uppercase" style="letter-spacing:.05em;">Actions</span>
                        </div>
                        <div class="card-body p-3 d-grid gap-2">

                            {{-- Draft: Finalize + Edit + Delete --}}
                            @if($warningLetter->status === 'draft')
                                <form method="POST" action="{{ route('warning-letters.approve', $warningLetter) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100 rounded-2" onclick="return confirm('Finalize this warning letter?')">
                                        <i class="bi bi-check-circle me-2"></i>Finalize & Enforce
                                    </button>
                                </form>
                                <a href="{{ route('warning-letters.edit', $warningLetter) }}" class="btn btn-outline-secondary w-100 rounded-2">
                                    <i class="bi bi-pencil me-2"></i>Edit Draft
                                </a>
                                <form method="POST" action="{{ route('warning-letters.destroy', $warningLetter) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100 rounded-2 btn-sm" onclick="return confirm('Delete this draft?')">
                                        <i class="bi bi-trash me-2"></i>Delete Draft
                                    </button>
                                </form>
                            @endif

                            {{-- Approved: Confirm Receipt --}}
                            @if($warningLetter->status === 'approved' && !$warningLetter->acknowledgment)
                                <form method="POST" action="{{ route('warning-letters.acknowledge', $warningLetter) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary w-100 rounded-2" onclick="return confirm('Confirm that the employee has received this letter?')">
                                        <i class="bi bi-person-check me-2"></i>Confirm Receipt
                                    </button>
                                </form>
                            @endif

                        </div>
                    </div>
                    @endif

                    {{-- Info Card --}}
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-transparent border-bottom px-3 py-2">
                            <span class="fw-semibold small text-uppercase" style="letter-spacing:.05em;">Info</span>
                        </div>
                        <div class="card-body px-3 py-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Created by</span>
                                <span class="small fw-medium">{{ $warningLetter->creator?->name ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Created on</span>
                                <span class="small fw-medium">{{ $warningLetter->created_at->format('d M Y') }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Source</span>
                                <span class="small fw-medium text-capitalize">{{ $warningLetter->trigger_source }}</span>
                            </div>
                            @if($warningLetter->acknowledgment)
                                <hr class="my-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Acknowledged</span>
                                    <span class="small fw-medium text-primary">{{ $warningLetter->acknowledgment->acknowledged_at->format('d M Y') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>
@endsection
