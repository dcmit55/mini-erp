@extends('layouts.app')

@section('title', 'Detail — ' . $warningLetter->letter_number)

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('warning-letters.index') }}"
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Detail Warning Letter</h5>
                    <p class="text-muted small mb-0">{{ $warningLetter->letter_number }}</p>
                </div>
                <div class="d-flex gap-2">
                    @can('hr.warning-letter.edit')
                    @if($warningLetter->isEditable())
                        <a href="{{ route('warning-letters.edit', $warningLetter) }}"
                           class="btn btn-primary btn-sm rounded-2 px-3">
                            <i class="fas fa-edit me-1"></i>Edit Draft
                        </a>
                    @endif
                    @endcan
                    @if(in_array($warningLetter->status, ['approved','acknowledged']))
                        <a href="{{ route('warning-letters.pdf', $warningLetter) }}"
                           class="btn btn-outline-danger btn-sm rounded-2 px-3" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Download PDF
                        </a>
                    @endif
                    @can('hr.warning-letter.edit')
                    @if($warningLetter->sp_level === 3 && in_array($warningLetter->status, ['approved','acknowledged']) && $warningLetter->employee->status !== 'inactive')
                        <button type="button" class="btn btn-danger btn-sm rounded-2 px-3"
                                data-bs-toggle="modal" data-bs-target="#terminateModal">
                            <i class="fas fa-user-times me-1"></i>Terminate
                        </button>
                    @endif
                    @endcan
                </div>
            </div>

            {{-- Flash --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row g-3">

                {{-- LEFT: Detail --}}
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                        <div class="card-body p-3">

                            {{-- Status Badges --}}
                            <div class="mb-4">
                                @php
                                    $spBadge = [
                                        1 => 'info', 2 => 'warning', 3 => 'danger'
                                    ];
                                    $spColor = $spBadge[$warningLetter->sp_level] ?? 'secondary';
                                    $sBg = \App\Models\Hr\WarningLetter::STATUS_COLORS[$warningLetter->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $spColor }} bg-opacity-10 text-{{ $spColor }} border border-{{ $spColor }} border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center me-2 {{ $warningLetter->sp_level <= 3 && $warningLetter->sp_level >= 2 ? 'text-dark' : '' }}">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <strong>{{ $warningLetter->spLabel }}</strong>
                                </span>
                                <span class="badge bg-{{ $sBg }} bg-opacity-10 text-{{ $sBg }} border border-{{ $sBg }} border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center {{ $warningLetter->status === 'pending_approval' ? 'text-dark' : '' }}">
                                    <strong>{{ $warningLetter->statusLabel }}</strong>
                                </span>
                                @if($warningLetter->batch_id)
                                    <a href="{{ route('warning-batches.show', $warningLetter->batch) }}"
                                       class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center ms-2 text-decoration-none">
                                        <i class="fas fa-users me-2"></i>Batch
                                    </a>
                                @endif
                            </div>

                            {{-- Employee & Validity --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="bg-light border rounded-2 p-3 h-100">
                                        <h6 class="text-dark fw-medium mb-2">
                                            <i class="fas fa-user me-2 text-primary"></i>Employee
                                        </h6>
                                        <p class="mb-1 fw-medium">{{ $warningLetter->employee->name }}</p>
                                        <p class="small text-muted mb-1">
                                            <i class="fas fa-id-badge me-1"></i>{{ $warningLetter->employee->employee_no }}
                                        </p>
                                        <p class="small text-muted mb-1">
                                            <i class="fas fa-building me-1"></i>{{ $warningLetter->employee->department?->name ?? '—' }}
                                        </p>
                                        <p class="small text-muted mb-0">
                                            <i class="fas fa-briefcase me-1"></i>{{ $warningLetter->employee->position ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="bg-light border rounded-2 p-3 h-100">
                                        <h6 class="text-dark fw-medium mb-2">
                                            <i class="fas fa-calendar me-2 text-primary"></i>Validity
                                        </h6>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label small text-muted mb-0">Tgl. Pelanggaran</label>
                                                <p class="mb-2 fw-medium">{{ $warningLetter->violation_date->format('d/m/Y') }}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small text-muted mb-0">Tgl. Terbit</label>
                                                <p class="mb-2 fw-medium">{{ $warningLetter->issued_date?->format('d/m/Y') ?? '—' }}</p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small text-muted mb-0">Berlaku Sampai</label>
                                                <p class="mb-0 fw-medium">
                                                    @if($warningLetter->valid_until)
                                                        @if($warningLetter->valid_until->isPast())
                                                            <span class="text-danger">{{ $warningLetter->valid_until->format('d/m/Y') }}</span>
                                                            <span class="badge bg-danger ms-1" style="font-size:.65rem;">Expired</span>
                                                        @elseif($warningLetter->valid_until->diffInDays(now()) <= 14)
                                                            <span class="text-warning">{{ $warningLetter->valid_until->format('d/m/Y') }}</span>
                                                            <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">{{ $warningLetter->valid_until->diffForHumans() }}</span>
                                                        @else
                                                            {{ $warningLetter->valid_until->format('d/m/Y') }}
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Violation --}}
                            <div class="bg-light border rounded-2 p-3 mb-3">
                                <h6 class="text-dark fw-medium mb-2">
                                    <i class="fas fa-exclamation-circle me-2 text-primary"></i>Pelanggaran
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-0">Kategori</label>
                                        <p class="mb-0 fw-medium">{{ $warningLetter->violationCategory->name }}</p>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-0">Deskripsi</label>
                                        <p class="mb-0" style="white-space:pre-line;line-height:1.7;">{{ $warningLetter->reason }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- SP3 Final Warning Alert --}}
                            @if($warningLetter->sp_level === 3)
                            <div class="bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-2 p-3 d-flex gap-2">
                                <i class="fas fa-exclamation-triangle text-danger mt-1"></i>
                                <div class="small text-danger">
                                    <strong>Peringatan Terakhir (SP3).</strong> Karyawan ini telah mencapai batas maksimum peringatan.
                                    @if(in_array($warningLetter->status, ['approved','acknowledged']) && $warningLetter->employee->status !== 'inactive')
                                        Gunakan tombol <strong>Terminate</strong> untuk memproses Pemutusan Hubungan Kerja.
                                    @elseif($warningLetter->employee->status === 'inactive')
                                        Karyawan ini sudah berstatus <strong>Inactive</strong>.
                                    @endif
                                </div>
                            </div>
                            @endif

                        </div>

                        <div class="card-footer border-0 bg-light px-3 py-2 d-flex justify-content-between align-items-center">
                            <small class="text-muted">Dibuat oleh <strong>{{ $warningLetter->creator?->name ?? '—' }}</strong> · {{ $warningLetter->created_at->format('d M Y') }}</small>
                            @if($warningLetter->acknowledgment)
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>Acknowledged · {{ $warningLetter->acknowledgment->acknowledged_at->format('d M Y') }}
                                </small>
                            @else
                                <small class="text-muted">Belum acknowledged</small>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Actions & Info --}}
                <div class="col-lg-4">

                    @can('hr.warning-letter.edit')
                    @if(in_array($warningLetter->status, ['draft', 'approved']) || ($warningLetter->sp_level === 3 && $warningLetter->status === 'acknowledged' && $warningLetter->employee->status !== 'inactive'))
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-dark fw-medium mb-3">
                                <i class="fas fa-bolt me-2 text-primary"></i>Actions
                            </h6>
                            <div class="d-grid gap-2">
                                @if($warningLetter->status === 'draft')
                                    <form method="POST" action="{{ route('warning-letters.approve', $warningLetter) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100 rounded-2 btn-sm"
                                                onclick="return confirm('Finalize surat peringatan ini?')">
                                            <i class="fas fa-check-circle me-2"></i>Finalize & Enforce
                                        </button>
                                    </form>
                                    <a href="{{ route('warning-letters.edit', $warningLetter) }}"
                                       class="btn btn-outline-primary w-100 rounded-2 btn-sm">
                                        <i class="fas fa-edit me-2"></i>Edit Draft
                                    </a>
                                    <form method="POST" action="{{ route('warning-letters.destroy', $warningLetter) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger w-100 rounded-2 btn-sm"
                                                onclick="return confirm('Hapus draft ini?')">
                                            <i class="fas fa-trash me-2"></i>Hapus Draft
                                        </button>
                                    </form>
                                @endif
                                @if($warningLetter->status === 'approved' && !$warningLetter->acknowledgment)
                                    <form method="POST" action="{{ route('warning-letters.acknowledge', $warningLetter) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary w-100 rounded-2 btn-sm"
                                                onclick="return confirm('Konfirmasi karyawan telah menerima surat ini?')">
                                            <i class="fas fa-user-check me-2"></i>Confirm Receipt
                                        </button>
                                    </form>
                                @endif
                                @if($warningLetter->sp_level === 3 && in_array($warningLetter->status, ['approved','acknowledged']) && $warningLetter->employee->status !== 'inactive')
                                    <div class="border-top pt-2 mt-1">
                                        <button type="button" class="btn btn-danger w-100 rounded-2 btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#terminateModal">
                                            <i class="fas fa-user-times me-2"></i>Terminate Karyawan
                                        </button>
                                        <div class="text-muted mt-1" style="font-size:0.72rem;">
                                            <i class="fas fa-info-circle me-1"></i>SP3 adalah peringatan terakhir
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                    @endcan

                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <h6 class="text-dark fw-medium mb-3">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Info
                            </h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label small text-muted mb-0">Dibuat oleh</label>
                                    <p class="mb-2 fw-medium">{{ $warningLetter->creator?->name ?? '—' }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-muted mb-0">Tanggal Dibuat</label>
                                    <p class="mb-2 fw-medium">{{ $warningLetter->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-muted mb-0">Sumber</label>
                                    <p class="mb-0 fw-medium text-capitalize">{{ $warningLetter->trigger_source }}</p>
                                </div>
                                @if($warningLetter->acknowledgment)
                                    <div class="col-12 border-top pt-2 mt-1">
                                        <label class="form-label small text-muted mb-0">Acknowledged</label>
                                        <p class="mb-0 fw-medium text-success">{{ $warningLetter->acknowledgment->acknowledged_at->format('d M Y, H:i') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- Terminate Modal --}}
@if($warningLetter->sp_level === 3)
<div class="modal fade" id="terminateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header bg-danger text-white border-0 rounded-top-3">
                <h6 class="modal-title"><i class="fas fa-user-times me-2"></i>Konfirmasi Terminasi Karyawan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2 text-muted small">Anda akan men-terminate karyawan:</p>
                <p class="fw-medium mb-1">{{ $warningLetter->employee->name }}</p>
                <p class="text-muted small mb-3">{{ $warningLetter->employee->employee_no }} — {{ $warningLetter->employee->position }}</p>
                <div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-2 p-3 small">
                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                    Status karyawan akan berubah menjadi <strong>inactive</strong>. Tindakan ini tidak dapat dibatalkan dari sini.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="{{ route('warning-letters.terminate-employee', $warningLetter) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm rounded-2">
                        <i class="fas fa-user-times me-1"></i>Ya, Terminate
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

<style>
.form-control, .form-select { border-color: #e2e8f0; font-size: 0.9rem; }
.btn { font-size: 0.9rem; font-weight: 500; }
.btn-primary { background-color: #4f46e5; border-color: #4f46e5; }
.btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
.badge { font-size: 0.85rem; font-weight: 500; }
.bg-light { background-color: #f8fafc !important; }
.text-muted { color: #6b7280 !important; }
.text-dark { color: #374151 !important; }
.text-primary { color: #4f46e5 !important; }
.rounded-2 { border-radius: .5rem !important; }
.rounded-3 { border-radius: .75rem !important; }
.border { border-color: #e2e8f0 !important; }
.fw-medium { font-weight: 500 !important; }
p { margin-bottom: .25rem; }
.small { font-size: 0.85rem; }
</style>
