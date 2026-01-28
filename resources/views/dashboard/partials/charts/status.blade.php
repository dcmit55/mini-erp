<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0 py-3">
        <h5 class="card-title mb-0 fw-bold">Request Status</h5>
    </div>
    <div class="card-body">
        <canvas id="requestStatusChart" height="200"></canvas>
        <div class="mt-3">
            <div class="row text-center">
                <div class="col-6 border-end">
                    <div class="fs-4 fw-bold text-warning">{{ $pendingRequests }}</div>
                    <div class="small text-muted">Pending</div>
                </div>
                <div class="col-6">
                    <div class="fs-4 fw-bold text-success">{{ $deliveredRequests }}</div>
                    <div class="small text-muted">Delivered</div>
                </div>
            </div>
        </div>
    </div>
</div>