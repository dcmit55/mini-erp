@extends('layouts.app')

@section('title', 'Leave Request Detail')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <div class="d-flex align-items-center gap-3 mb-3">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
                {!! session('success') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="fw-semibold">{{ $leave->employee->name ?? '-' }}</div>
                        <div>
                            @php
                                $badgeClass = match($leave->status ?? 'pending') {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default    => 'warning text-dark',
                                };
                            @endphp
                            <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($leave->status ?? 'pending') }}</span>
                        </div>
                    </div>
                    <div class="text-muted small">{{ $leave->employee->department->name ?? '' }}</div>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Leave Type</div>
                            <div class="fw-medium">{{ $leaveTypeLabels[strtoupper($leave->type)] ?? $leave->type }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Duration</div>
                            <div class="fw-medium">{{ rtrim(rtrim(number_format($leave->duration, 2, '.', ''), '0'), '.') }} hari</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Start Date</div>
                            <div class="fw-medium">{{ $leave->start_date ? $leave->start_date->format('d M Y') : '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">End Date</div>
                            <div class="fw-medium">{{ $leave->end_date ? $leave->end_date->format('d M Y') : '-' }}</div>
                        </div>
                        @if($leave->reason)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Reason</div>
                            <div>{{ $leave->reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Documents --}}
            @if($leave->mc_document || $leave->doctor_letter)
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-light border-0 py-2 px-4">
                    <span class="fw-semibold small">Documents</span>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="d-flex gap-2 flex-wrap">
                        @if($leave->mc_document)
                        <a href="{{ route('leave_requests.document', [$leave->id, 'mc']) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-2 px-3">
                            <i class="fas fa-file-medical me-1"></i> Medical Certificate
                        </a>
                        @endif
                        @if($leave->doctor_letter)
                        <a href="{{ route('leave_requests.document', [$leave->id, 'doctor']) }}" target="_blank" class="btn btn-outline-info btn-sm rounded-2 px-3">
                            <i class="fas fa-file-alt me-1"></i> Doctor Letter
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Approvals --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-light border-0 py-2 px-4">
                    <span class="fw-semibold small">Approval Status</span>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">HR Approval</div>
                            @php $a1 = $leave->approval_1 ?? 'pending'; $a1class = $a1 === 'approved' ? 'success' : ($a1 === 'rejected' ? 'danger' : 'warning text-dark'); @endphp
                            <span class="badge bg-{{ $a1class }}">{{ ucfirst($a1) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Director Approval</div>
                            @php $a2 = $leave->approval_2 ?? 'pending'; $a2class = $a2 === 'approved' ? 'success' : ($a2 === 'rejected' ? 'danger' : 'warning text-dark'); @endphp
                            <span class="badge bg-{{ $a2class }}">{{ ucfirst($a2) }}</span>
                        </div>
                        @if($leave->rejection_reason ?? null)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Rejection Reason</div>
                            <div class="text-danger small">{{ $leave->rejection_reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
