<div class="card border-0 shadow-sm h-100 mb-0">
    <div class="card-header bg-transparent border-0 py-3">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fw-bold">Upcoming Deadlines</h5>
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-brand">View Projects</a>
        </div>
    </div>
    <div class="card-body p-0" style="height: 100%; max-height: 400px; overflow-y: auto;">
        @if($upcomingDeadlines->count() > 0)
            <div class="list-group list-group-flush">
                @foreach($upcomingDeadlines as $project)
                    <div class="list-group-item border-0 py-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="deadline-icon bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-semibold">{{ $project->name }}</div>
                                <div class="small text-muted"> Department:
                                    @if($project->departments && $project->departments->count())
                                        {{ $project->departments->pluck('name')->implode(', ') }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="text-danger small fw-semibold">
                                    {{ \Carbon\Carbon::parse($project->deadline)->diffForHumans() }}
                                </div>
                                <div class="text-muted small">
                                    {{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="fas fa-calendar-check fs-1 mb-3 opacity-25"></i>
                <p>No upcoming deadlines in the next 30 days</p>
            </div>
        @endif
    </div>
</div>