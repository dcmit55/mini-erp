@extends('layouts.app')

@section('title', 'Fingerprint Logs')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Fingerprint Webhook Logs</h5>
                    <p class="text-muted small mb-0">Raw data masuk dari mesin fingerprint</p>
                </div>
                <span class="badge bg-secondary">{{ $logs->total() }} total log</span>
            </div>

            {{-- Filter --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('fingerprint-logs.index') }}">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-2">
                                <input type="text" name="cloud_id" class="form-control form-control-sm"
                                    placeholder="Cloud ID" value="{{ request('cloud_id') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="trans_id" class="form-control form-control-sm"
                                    placeholder="Trans ID" value="{{ request('trans_id') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                    value="{{ request('date_from') }}" placeholder="Dari tanggal">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                    value="{{ request('date_to') }}" placeholder="Sampai tanggal">
                            </div>
                            <div class="col-md-4 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary px-3">
                                    <i class="fas fa-search me-1"></i> Filter
                                </button>
                                @if(request()->hasAny(['cloud_id', 'trans_id', 'date_from', 'date_to']))
                                    <a href="{{ route('fingerprint-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Reset
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width:60px">#</th>
                                    <th>Trans ID</th>
                                    <th>Cloud ID</th>
                                    <th>Event Time</th>
                                    <th>Received At</th>
                                    <th class="text-center" style="width:120px">Payload</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td class="ps-3 text-muted small">{{ $log->id }}</td>
                                    <td>
                                        <code class="small">{{ $log->trans_id ?? '-' }}</code>
                                    </td>
                                    <td>
                                        <code class="small">{{ $log->cloud_id ?? '-' }}</code>
                                    </td>
                                    <td class="small">
                                        {{ $log->event_time ? $log->event_time->setTimezone('Asia/Jakarta')->format('d M Y H:i:s') : '-' }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $log->created_at->setTimezone('Asia/Jakarta')->format('d M Y H:i:s') }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-outline-info py-0 px-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payloadModal"
                                            data-payload="{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}"
                                            data-log-id="{{ $log->id }}">
                                            <i class="fas fa-code me-1"></i>JSON
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Belum ada log fingerprint.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($logs->hasPages())
                <div class="card-footer bg-white border-top-0 py-2">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- Modal Payload --}}
<div class="modal fade" id="payloadModal" tabindex="-1" aria-labelledby="payloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="payloadModalLabel">
                    <i class="fas fa-code me-1"></i> Raw Payload — Log #<span id="modalLogId"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <pre id="payloadContent" class="bg-dark text-success p-3 mb-0" style="font-size:0.8rem; max-height:70vh; overflow-y:auto;"></pre>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="copyPayloadBtn">
                    <i class="fas fa-copy me-1"></i> Copy
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadModal = document.getElementById('payloadModal');
    const payloadContent = document.getElementById('payloadContent');
    const modalLogId = document.getElementById('modalLogId');
    const copyBtn = document.getElementById('copyPayloadBtn');

    payloadModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const payload = btn.getAttribute('data-payload');
        const logId = btn.getAttribute('data-log-id');
        payloadContent.textContent = payload;
        modalLogId.textContent = logId;
    });

    copyBtn.addEventListener('click', function () {
        navigator.clipboard.writeText(payloadContent.textContent).then(() => {
            copyBtn.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="fas fa-copy me-1"></i> Copy';
            }, 2000);
        });
    });
});
</script>
@endpush
