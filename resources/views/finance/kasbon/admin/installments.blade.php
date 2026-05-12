@extends('layouts.app')

@section('title', 'Installment Monitoring')

@section('content')
<div class="container-fluid py-3">
    <div class="col-12">

        {{-- Header --}}
        <div class="position-relative d-flex align-items-center mb-3" style="min-height:44px;">
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <a href="{{ route('kasbon.admin.index') }}" class="text-muted text-decoration-none small">Cash Advance</a>
                <span class="text-muted">/</span>
                <span class="fw-semibold text-dark">Installment Monitoring</span>
            </div>
            <div class="ms-auto flex-shrink-0">
                <a href="{{ url('/cek-kasbon') }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-search me-1"></i>Check Status
                </a>
            </div>
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

        {{-- Summary Cards --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small mb-1">Unpaid This Month</div>
                        <div class="fw-bold fs-4 text-danger">{{ $summary['unpaid'] }}</div>
                        <div class="text-muted" style="font-size:.7rem;">installments</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small mb-1">Paid This Month</div>
                        <div class="fw-bold fs-4 text-success">{{ $summary['paid'] }}</div>
                        <div class="text-muted" style="font-size:.7rem;">installments</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small mb-1">Outstanding Amount</div>
                        <div class="fw-bold fs-5 text-danger">Rp {{ number_format($summary['total_unpaid'], 0, ',', '.') }}</div>
                        <div class="text-muted" style="font-size:.7rem;">unpaid this month</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('kasbon.admin.installments') }}" class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Month</label>
                        <input type="month" name="month" class="form-control form-control-sm rounded-2"
                               value="{{ $month }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Department</label>
                        <select name="department_id" class="form-select form-select-sm rounded-2">
                            <option value="">All</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-dark mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm rounded-2">
                            <option value="all"    {{ $status === 'all'    ? 'selected' : '' }}>All</option>
                            <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid"   {{ $status === 'paid'   ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-1 d-flex align-items-end">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm rounded-2 px-2">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="{{ route('kasbon.admin.installments') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.875rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center" style="width:44px;">No</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Name</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Employee ID</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Department</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Ref No.</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Month</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2">Due Date</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-end">Amount</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-center">Status</th>
                                <th class="border-0 text-muted fw-normal px-3 py-2 text-end" style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($installments->isEmpty())
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="fas fa-check-circle fa-2x text-muted mb-2 d-block"></i>
                                    <span class="text-muted small">No installments found for this period.</span>
                                </td>
                            </tr>
                            @else
                            @php $no = ($installments->currentPage() - 1) * $installments->perPage() + 1; @endphp
                            @foreach($installments as $i => $cicilan)
                            @php
                                $isOverdue = $cicilan->status !== 'paid' && $cicilan->due_date->isPast();
                            @endphp
                            <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                                <td class="px-3 py-2 text-center text-muted">{{ $no + $i }}</td>
                                <td class="px-3 py-2 small">{{ $cicilan->kasbon->nama_lengkap }}</td>
                                <td class="px-3 py-2 small text-muted font-monospace">{{ $cicilan->kasbon->nik_karyawan }}</td>
                                <td class="px-3 py-2 text-muted small">{{ $cicilan->kasbon->department->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('kasbon.admin.show', $cicilan->kasbon_id) }}"
                                       class="font-monospace small text-decoration-none">
                                        {{ $cicilan->kasbon->ref_number }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-center small">{{ $cicilan->bulan_ke }}</td>
                                <td class="px-3 py-2 small" style="white-space:nowrap;">
                                    {{ $cicilan->due_date->format('d M Y') }}
                                    @if($isOverdue)
                                        <span class="badge bg-danger rounded-2 ms-1" style="font-size:.65rem;">Overdue</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-end small">Rp {{ number_format($cicilan->jumlah_cicilan, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if($cicilan->status === 'paid')
                                        <span class="badge bg-success rounded-2">Paid</span>
                                    @elseif($cicilan->status === 'partial')
                                        <span class="badge bg-warning text-dark rounded-2">Partial</span>
                                    @else
                                        <span class="badge bg-secondary rounded-2">Unpaid</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-end">
                                    @if($cicilan->status !== 'paid')
                                    <button type="button"
                                        class="btn btn-success btn-sm rounded-2 px-2 py-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalPay"
                                        data-kasbon-id="{{ $cicilan->kasbon_id }}"
                                        data-installment-id="{{ $cicilan->id }}"
                                        data-name="{{ $cicilan->kasbon->nama_lengkap }}"
                                        data-bulan="{{ $cicilan->bulan_ke }}"
                                        data-jumlah="Rp {{ number_format($cicilan->jumlah_cicilan, 0, ',', '.') }}">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @else
                                    <span class="text-muted small">{{ $cicilan->paid_at?->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                @if(!$installments->isEmpty() && $installments->hasPages())
                <div class="card-footer border-0 bg-light px-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $installments->firstItem() }}–{{ $installments->lastItem() }} of {{ $installments->total() }}
                        </div>
                        {{ $installments->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- Modal Pay --}}
<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-semibold">Record Payment</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formPay">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        <strong id="payName">—</strong> · Month <strong id="payMonth">—</strong>:
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
        const btn           = e.relatedTarget;
        const kasbonId      = btn.getAttribute('data-kasbon-id');
        const installmentId = btn.getAttribute('data-installment-id');

        $('#payName').text(btn.getAttribute('data-name'));
        $('#payMonth').text(btn.getAttribute('data-bulan'));
        $('#payAmount').text(btn.getAttribute('data-jumlah'));
        $('#formPay').attr('action', `/admin/kasbon/${kasbonId}/installments/${installmentId}/pay`);
    });
});
</script>
@endpush
