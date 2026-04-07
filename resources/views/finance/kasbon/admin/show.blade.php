@extends('layouts.app')

@section('title', 'Cash Advance Detail — ' . $kasbon->ref_number)

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">

            {{-- Header --}}
            <div class="d-flex align-items-center gap-3 mb-3">
                <a href="{{ route('kasbon.admin.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <span class="font-monospace small text-muted">{{ $kasbon->ref_number }}</span>
                @include('finance.kasbon.admin.partials.status-badge', ['status' => $kasbon->status])
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
                {!! session('success') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                {!! session('error') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="row g-3">
                {{-- Left Column --}}
                <div class="col-12 col-lg-7">

                    {{-- Employee Info --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-light border-0 py-2 px-4">
                            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Employee Information</span>
                        </div>
                        <div class="card-body px-4 py-3">
                            <div class="row g-3 small">
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Full Name</div>
                                    <div class="fw-medium">{{ $kasbon->nama_lengkap }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Employee ID</div>
                                    <div class="fw-medium">{{ $kasbon->nik_karyawan }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Department</div>
                                    <div class="fw-medium">{{ $kasbon->department->name ?? '—' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">WhatsApp</div>
                                    <div class="fw-medium">{{ $kasbon->no_wa }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Submitted At</div>
                                    <div class="fw-medium">{{ $kasbon->submitted_at?->format('d M Y, H:i') ?? '—' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">IP Address</div>
                                    <div class="fw-medium text-muted">{{ $kasbon->ip_address ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Request Detail --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-light border-0 py-2 px-4">
                            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Request Detail</span>
                        </div>
                        <div class="card-body px-4 py-3">
                            <div class="row g-3 small">
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Requested Amount</div>
                                    <div class="fw-semibold">Rp {{ number_format($kasbon->jumlah_diminta, 0, ',', '.') }}</div>
                                </div>
                                @if($kasbon->jumlah_disetujui)
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Approved Amount</div>
                                    <div class="fw-semibold text-success">Rp {{ number_format($kasbon->jumlah_disetujui, 0, ',', '.') }}</div>
                                </div>
                                @endif
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Tenor</div>
                                    <div class="fw-medium">{{ $kasbon->tenor_bulan }} Months</div>
                                </div>
                                @if($kasbon->disbursed_at)
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Disbursed At</div>
                                    <div class="fw-medium">{{ $kasbon->disbursed_at->format('d M Y') }}</div>
                                </div>
                                @endif
                                <div class="col-12">
                                    <div class="text-muted mb-1" style="font-size:.7rem;">Reason</div>
                                    <div class="rounded-2 p-2" style="background:var(--bs-tertiary-bg); border:1px solid var(--bs-border-color);">
                                        {{ $kasbon->alasan }}
                                    </div>
                                </div>
                                @if($kasbon->dokumen_url)
                                <div class="col-12">
                                    <div class="text-muted mb-1" style="font-size:.7rem;">Supporting Document</div>
                                    <a href="{{ asset($kasbon->dokumen_url) }}" target="_blank"
                                       class="btn btn-outline-primary btn-sm rounded-2 px-3">
                                        <i class="fas fa-file me-1"></i> View Document
                                    </a>
                                </div>
                                @endif
                                @if($kasbon->catatan_admin)
                                <div class="col-12">
                                    <div class="text-muted mb-1" style="font-size:.7rem;">Finance Note</div>
                                    <div class="rounded-2 p-2 {{ $kasbon->status === 'rejected' ? 'text-danger' : '' }}"
                                         style="background:var(--bs-tertiary-bg); border:1px solid var(--bs-border-color);">
                                        {{ $kasbon->catatan_admin }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Installments --}}
                    @if($kasbon->installments->count() > 0)
                    @php
                        $totalCicilan = $kasbon->installments->count();
                        $lunasCount   = $kasbon->installments->where('status', 'paid')->count();
                        $persen       = $totalCicilan > 0 ? round(($lunasCount / $totalCicilan) * 100) : 0;
                        $totalSisa    = $kasbon->installments->where('status', '!=', 'paid')->sum('jumlah_cicilan');
                    @endphp
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-light border-0 py-2 px-4 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Installment Schedule</span>
                            <span class="small text-muted">{{ $lunasCount }}/{{ $totalCicilan }} paid</span>
                        </div>
                        <div class="card-body px-4 py-3">
                            <div class="progress mb-3" style="height:6px;">
                                <div class="progress-bar bg-success" style="width:{{ $persen }}%"></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless small mb-0">
                                    <thead>
                                        <tr class="text-muted" style="font-size:.7rem;">
                                            <th>Month</th>
                                            <th>Due Date</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Method</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kasbon->installments as $cicilan)
                                        <tr>
                                            <td>{{ $cicilan->bulan_ke }}</td>
                                            <td style="white-space:nowrap;">{{ $cicilan->due_date->format('d M Y') }}</td>
                                            <td class="text-end">Rp {{ number_format($cicilan->jumlah_cicilan, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                @if($cicilan->status === 'paid')
                                                    <span class="badge bg-success rounded-2">Paid</span>
                                                @elseif($cicilan->status === 'partial')
                                                    <span class="badge bg-warning text-dark rounded-2">Partial</span>
                                                @else
                                                    <span class="badge bg-secondary rounded-2">Unpaid</span>
                                                @endif
                                            </td>
                                            <td class="text-center text-muted" style="font-size:.7rem;">
                                                {{ $cicilan->metode ? str_replace('_', ' ', $cicilan->metode) : '—' }}
                                            </td>
                                            <td class="text-end">
                                                @if($cicilan->status !== 'paid' && in_array($kasbon->status, ['disbursed', 'repaying']))
                                                <button type="button"
                                                    class="btn btn-success btn-sm rounded-2 px-2 py-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalPay"
                                                    data-installment-id="{{ $cicilan->id }}"
                                                    data-bulan="{{ $cicilan->bulan_ke }}"
                                                    data-jumlah="Rp {{ number_format($cicilan->jumlah_cicilan, 0, ',', '.') }}">
                                                    <i class="fas fa-check me-1"></i>Pay
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-top">
                                            <td colspan="2" class="fw-medium small pt-2">Remaining Balance</td>
                                            <td class="text-end fw-semibold text-danger pt-2">Rp {{ number_format($totalSisa, 0, ',', '.') }}</td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

                {{-- Right Column --}}
                <div class="col-12 col-lg-5">

                    {{-- Approve Form (pending/under_review) --}}
                    @if(in_array($kasbon->status, ['pending', 'under_review']))
                    <div class="card border-0 shadow-sm rounded-3 mb-3" style="border-left: 3px solid #198754 !important;">
                        <div class="card-header bg-light border-0 py-2 px-4">
                            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Approve Request</span>
                        </div>
                        <div class="card-body px-4 py-3">
                            <form method="POST" action="{{ route('kasbon.admin.approve', $kasbon->id) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label small fw-medium">Approved Amount (Rp) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="jumlah_disetujui"
                                               class="form-control @error('jumlah_disetujui') is-invalid @enderror"
                                               value="{{ old('jumlah_disetujui', $kasbon->jumlah_diminta) }}"
                                               min="100000" required>
                                        @error('jumlah_disetujui')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-medium">Installment Tenor <span class="text-danger">*</span></label>
                                    <select name="tenor_bulan" class="form-select form-select-sm" required>
                                        @foreach([1, 2, 3, 6, 12] as $t)
                                        <option value="{{ $t }}" {{ old('tenor_bulan', $kasbon->tenor_bulan) == $t ? 'selected' : '' }}>
                                            {{ $t }} Months
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-medium">Note (optional)</label>
                                    <textarea name="catatan_admin" class="form-control form-control-sm" rows="2"
                                              placeholder="Note for employee...">{{ old('catatan_admin') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm w-100 rounded-2"
                                        onclick="return confirm('Approve this cash advance?')">
                                    <i class="fas fa-check me-1"></i>Approve
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Reject Form --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3" style="border-left: 3px solid #dc3545 !important;">
                        <div class="card-header bg-light border-0 py-2 px-4">
                            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Reject Request</span>
                        </div>
                        <div class="card-body px-4 py-3">
                            <form method="POST" action="{{ route('kasbon.admin.reject', $kasbon->id) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label small fw-medium">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="catatan_admin" class="form-control form-control-sm @error('catatan_admin') is-invalid @enderror"
                                              rows="2" placeholder="Enter rejection reason..." required>{{ old('catatan_admin') }}</textarea>
                                    @error('catatan_admin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm w-100 rounded-2"
                                        onclick="return confirm('Reject this cash advance request?')">
                                    <i class="fas fa-times me-1"></i>Reject
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    {{-- Disburse Button --}}
                    @if($kasbon->status === 'approved')
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body px-4 py-3">
                            <p class="small text-muted mb-3">Funds have been handed to the employee? Click below to record the disbursement.</p>
                            <form method="POST" action="{{ route('kasbon.admin.disburse', $kasbon->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-2"
                                        onclick="return confirm('Record fund disbursement for this cash advance?')">
                                    <i class="fas fa-money-bill-wave me-1"></i>Record Disbursement
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    {{-- Audit Timeline --}}
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-light border-0 py-2 px-4">
                            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Activity Log</span>
                        </div>
                        <div class="card-body px-4 py-3">
                            @if($kasbon->auditLogs->isEmpty())
                            <p class="text-muted small mb-0">No activity yet.</p>
                            @else
                            <div class="timeline">
                                @foreach($kasbon->auditLogs as $log)
                                <div class="d-flex gap-3 mb-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="rounded-circle bg-secondary" style="width:8px;height:8px;margin-top:4px;"></div>
                                    </div>
                                    <div class="small">
                                        <div class="fw-medium">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</div>
                                        @if($log->from_status && $log->to_status)
                                        <div class="text-muted" style="font-size:.7rem;">
                                            {{ $log->from_status }} → {{ $log->to_status }}
                                        </div>
                                        @endif
                                        @if($log->note)
                                        <div class="text-muted mt-1" style="font-size:.7rem;">{{ $log->note }}</div>
                                        @endif
                                        <div class="text-muted" style="font-size:.7rem;">
                                            {{ $log->created_at->format('d M Y, H:i') }}
                                            @if($log->actor)· {{ $log->actor->username }}@endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- Modal Pay Installment --}}
<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-semibold">Record Installment Payment</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formPay">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Month <strong id="payMonth">—</strong>:
                        <strong id="payAmount">—</strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Payment Method <span class="text-danger">*</span></label>
                        <select name="metode" class="form-select form-select-sm" required>
                            <option value="cash">Cash</option>
                            <option value="payroll_deduction">Payroll Deduction</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-medium">Note (optional)</label>
                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Remarks...">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-2 px-4">
                        <i class="fas fa-check me-1"></i>Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#modalPay').on('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        const installmentId = btn.getAttribute('data-installment-id');
        const bulan         = btn.getAttribute('data-bulan');
        const jumlah        = btn.getAttribute('data-jumlah');

        $('#payMonth').text(bulan);
        $('#payAmount').text(jumlah);

        const kasbonId = {{ $kasbon->id }};
        $('#formPay').attr('action', `/admin/kasbon/${kasbonId}/installments/${installmentId}/pay`);
    });
});
</script>
@endpush
