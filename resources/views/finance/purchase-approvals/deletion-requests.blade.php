@extends('layouts.app')

@section('title', 'Deletion Requests - Finance')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">Deletion Requests</h5>
                    <p class="text-muted small mb-0">Approved purchases requested to be deleted by user</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('purchase-approvals.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('purchase-approvals.deleted-purchases') }}" class="btn btn-outline-dark btn-sm rounded-2 px-3">
                        <i class="fas fa-history me-1"></i> Deleted Purchases
                    </a>
                </div>
            </div>

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

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    @if($deletionRequests->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6 class="text-muted">Tidak ada permintaan penghapusan</h6>
                        </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 small fw-medium px-3 py-2 text-center" style="width:50px;">No</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Purchase Number</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Date</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Department</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Project</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Supplier</th>
                                    <th class="border-0 small fw-medium px-3 py-2 text-center">Items</th>
                                    <th class="border-0 small fw-medium px-3 py-2 text-end">Amount</th>
                                    <th class="border-0 small fw-medium px-3 py-2">Diminta</th>
                                    <th class="border-0 small fw-medium px-3 py-2 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deletionRequests as $i => $req)
                                {{-- Main Row --}}
                                <tr class="border-top">
                                    <td class="px-3 py-2 text-center text-muted small">{{ $i + 1 }}</td>
                                    <td class="px-3 py-2" style="font-size:0.8rem;">{{ $req['po_number'] }}</td>
                                    <td class="px-3 py-2 small">{{ $req['date'] ? \Carbon\Carbon::parse($req['date'])->format('d/m/Y') : '-' }}</td>
                                    <td class="px-3 py-2 small text-muted">{{ $req['department']->name ?? '-' }}</td>
                                    <td class="px-3 py-2" style="font-size:0.8rem;">
                                        @if($req['project_type'] == 'client')
                                            {{ $req['project']->name ?? '-' }}
                                            @if($req['job_order'])
                                                <span class="text-muted"> · {{ $req['job_order']->name }}</span>
                                            @endif
                                        @else
                                            {{ $req['internal_project']->project ?? '-' }}
                                            @if($req['internal_project']->job ?? '')
                                                <span class="text-muted"> · {{ $req['internal_project']->job }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-2" style="font-size:0.8rem;">{{ $req['supplier']->name ?? '-' }}</td>
                                    <td class="px-3 py-2 small text-center text-muted">{{ count($req['items']) }}</td>

                                    <td class="px-3 py-2 text-end" style="font-size:0.8rem; white-space:nowrap;">Rp {{ number_format($req['total_amount'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 small text-muted">{{ \Carbon\Carbon::parse($req['deletion_requested_at'])->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a href="{{ route('purchase-approvals.deletion-detail', $req['first_item_id']) }}"
                                               class="btn btn-outline-info btn-sm rounded-2 px-2 py-1"
                                               title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('procurement.po.delete')
                                            <form action="{{ route('purchase-approvals.approve-deletion', $req['first_item_id']) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Setujui penghapusan {{ $req['po_number'] }}? Semua item akan dihapus permanen.')">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm rounded-2 px-2 py-1" title="Setujui & Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('purchase-approvals.reject-deletion', $req['first_item_id']) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-2 px-2 py-1" title="Tolak">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
