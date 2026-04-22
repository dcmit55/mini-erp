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
                                @if($kasbon->suku_bunga_persen !== null)
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Interest Rate</div>
                                    @if((float)$kasbon->suku_bunga_persen == 0)
                                        <div class="fw-medium text-success"><i class="fas fa-ban me-1"></i>No Interest</div>
                                    @else
                                        <div class="fw-medium">{{ $kasbon->suku_bunga_persen }}% / month <span class="text-muted" style="font-size:.7rem;">(reducing balance)</span></div>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:.7rem;">Admin Fee</div>
                                    <div class="fw-medium">Rp {{ number_format($kasbon->biaya_admin, 0, ',', '.') }} <span class="text-muted" style="font-size:.7rem;">(month 1)</span></div>
                                </div>
                                @endif
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
                                            <th>Mo.</th>
                                            <th>Due Date</th>
                                            <th class="text-end">Principal</th>
                                            <th class="text-end">Interest+Admin</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Payroll Deduction</th>
                                            <th class="text-center">Cash</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kasbon->installments as $cicilan)
                                        @php
                                            $canAct       = in_array($kasbon->status, ['disbursed', 'repaying']);
                                            $cashTotal    = $cicilan->jumlah_bunga + $cicilan->jumlah_biaya_admin;
                                            $isOverdue    = $cicilan->status !== 'paid' && $cicilan->due_date->isPast();
                                            $cashFormatted = number_format($cashTotal, 0, ',', '.');
                                        @endphp
                                        <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                                            <td class="fw-medium">{{ $cicilan->bulan_ke }}</td>
                                            <td style="white-space:nowrap;">{{ $cicilan->due_date->format('d M Y') }}</td>
                                            <td class="text-end">Rp {{ number_format($cicilan->jumlah_pokok, 0, ',', '.') }}</td>
                                            <td class="text-end">
                                                Rp {{ number_format($cashTotal, 0, ',', '.') }}
                                                @if($cicilan->jumlah_biaya_admin > 0)
                                                    <div class="text-muted" style="font-size:.65rem;">incl. fee Rp {{ number_format($cicilan->jumlah_biaya_admin, 0, ',', '.') }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end fw-medium">Rp {{ number_format($cicilan->jumlah_cicilan, 0, ',', '.') }}</td>

                                            {{-- Kolom: Potong Gaji --}}
                                            <td class="text-center">
                                                @if($cicilan->pokok_paid_at)
                                                    <span class="badge bg-success rounded-2" style="font-size:.65rem;">
                                                        <i class="fas fa-check me-1"></i>{{ $cicilan->pokok_paid_at->format('d/m/Y') }}
                                                    </span>
                                                @elseif($canAct)
                                                    <form method="POST" action="{{ route('kasbon.admin.installment.confirm-pokok', [$kasbon->id, $cicilan->id]) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-primary btn-sm rounded-2 px-2 py-1"
                                                                onclick="return confirm('Confirm principal month {{ $cicilan->bulan_ke }} has been deducted from payroll?')"
                                                                style="font-size:.7rem;">
                                                            <i class="fas fa-cut me-1"></i>Confirm
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>

                                            {{-- Kolom: Cash --}}
                                            <td class="text-center">
                                                @if($cicilan->cash_paid_at)
                                                    <span class="badge bg-success rounded-2" style="font-size:.65rem;">
                                                        <i class="fas fa-check me-1"></i>{{ $cicilan->cash_paid_at->format('d/m/Y') }}
                                                    </span>
                                                @elseif($canAct)
                                                    <form method="POST" action="{{ route('kasbon.admin.installment.confirm-cash', [$kasbon->id, $cicilan->id]) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1"
                                                                onclick="return confirm('Confirm cash received for month {{ $cicilan->bulan_ke }} (Rp {{ $cashFormatted }})?')"
                                                                style="font-size:.7rem;">
                                                            <i class="fas fa-money-bill me-1"></i>Receive
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>

                                            <td class="text-center">
                                                @if($cicilan->status === 'paid')
                                                    <span class="badge bg-success rounded-2">Paid</span>
                                                @elseif($cicilan->status === 'partial')
                                                    <span class="badge bg-warning text-dark rounded-2">Partial</span>
                                                @elseif($isOverdue)
                                                    <span class="badge bg-danger rounded-2">Overdue</span>
                                                @else
                                                    <span class="badge bg-secondary rounded-2">Unpaid</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-top">
                                            <td colspan="4" class="fw-medium small pt-2">Remaining Balance</td>
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
                                {{-- Bunga Toggle --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="tanpaBunga"
                                               {{ old('tanpa_bunga', false) ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-medium" for="tanpaBunga">
                                            Tanpa Bunga <span class="text-muted">(No Interest)</span>
                                        </label>
                                    </div>
                                </div>
                                {{-- Always-submitted hidden input — overwritten by JS when needed --}}
                                <input type="hidden" name="suku_bunga_persen" id="sukuBungaHidden" value="{{ old('suku_bunga_persen', $kasbon->suku_bunga_persen ?? 2) }}">
                                <div id="bungaFields" class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-medium">Interest Rate (% / month)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" id="sukuBungaInput" step="0.01" min="0.01" max="100"
                                                   class="form-control @error('suku_bunga_persen') is-invalid @enderror"
                                                   value="{{ old('suku_bunga_persen', $kasbon->suku_bunga_persen ?? 2) }}">
                                            <span class="input-group-text">%</span>
                                            @error('suku_bunga_persen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-medium">Admin Fee (Rp) <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="biaya_admin" min="0"
                                                   class="form-control @error('biaya_admin') is-invalid @enderror"
                                                   value="{{ old('biaya_admin', $kasbon->biaya_admin ?? 50000) }}" required>
                                            @error('biaya_admin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                {{-- Info shown when no-interest is active --}}
                                <div id="noBungaInfo" class="mb-3 d-none">
                                    <div class="alert alert-info py-2 small mb-0">
                                        <i class="fas fa-info-circle me-1"></i>Cicilan hanya berisi pokok. Tidak ada bunga yang dikenakan.
                                    </div>
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

@endsection

@push('scripts')
<script>
(function () {
    const toggle       = document.getElementById('tanpaBunga');
    const bungaFields  = document.getElementById('bungaFields');
    const noBungaInfo  = document.getElementById('noBungaInfo');
    const visibleInput = document.getElementById('sukuBungaInput');
    const hiddenInput  = document.getElementById('sukuBungaHidden');
    const defaultRate  = '{{ old('suku_bunga_persen', $kasbon->suku_bunga_persen ?? 2) }}';

    if (!toggle) return;

    // Keep hidden input in sync with visible input
    if (visibleInput) {
        visibleInput.addEventListener('input', function () {
            hiddenInput.value = this.value;
        });
    }

    function applyToggle() {
        if (toggle.checked) {
            bungaFields.classList.add('d-none');
            if (noBungaInfo) noBungaInfo.classList.remove('d-none');
            hiddenInput.value = '0';
        } else {
            bungaFields.classList.remove('d-none');
            if (noBungaInfo) noBungaInfo.classList.add('d-none');
            hiddenInput.value = visibleInput ? visibleInput.value || defaultRate : defaultRate;
        }
    }

    toggle.addEventListener('change', applyToggle);
    applyToggle();
})();
</script>
@endpush
