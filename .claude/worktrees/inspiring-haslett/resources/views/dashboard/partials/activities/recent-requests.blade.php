<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0 py-3">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fw-bold">Recent Material Requests</h5>
            <a href="{{ route('material_requests.index') }}" class="btn btn-sm btn-outline-brand">View All</a>
        </div>
    </div>
    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
        <div class="list-group list-group-flush">
            @foreach($recentRequests->take(10) as $request)
                <div class="list-group-item border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="activity-icon bg-brand bg-opacity-10 text-brand rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 40px; height: 40px;">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-semibold">{{ $request->inventory->name ?? 'N/A' }}</div>
                            <div class="small text-muted">
                                Requested by {{ $request->user->username ?? 'N/A' }} •
                                <span class="badge {{ $request->getStatusBadgeClass() }}">{{ ucfirst($request->status) }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-muted small">
                            {{ $request->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>