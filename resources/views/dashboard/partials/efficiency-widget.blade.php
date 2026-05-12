{{-- Production Efficiency Widget --}}
<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="bi bi-speedometer2 text-primary me-2"></i> Production Efficiency (This Month)
            </h5>
            <a href="{{ route('efficiency.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-right me-1"></i>View Details
            </a>
        </div>
    </div>
    <div class="card-body">
        @if (isset($totalProductionHours) && ($totalProductionHours > 0 || $totalProductionOutput > 0))
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-sm-6">
                    <div class="text-center p-3 bg-light rounded">
                        <div class="text-primary mb-2" style="font-size: 2rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h3 class="mb-0">{{ number_format($totalProductionHours, 1) }}</h3>
                        <small class="text-muted">Hours Worked</small>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="text-center p-3 bg-light rounded">
                        <div class="text-warning mb-2" style="font-size: 2rem;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h3 class="mb-0">{{ number_format($totalProductionOutput, 0) }}</h3>
                        <small class="text-muted">Total Output</small>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="text-center p-3 bg-light rounded">
                        <div class="text-success mb-2" style="font-size: 2rem;">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h3 class="mb-0">{{ $activeProductionProjects }}</h3>
                        <small class="text-muted">Active Projects</small>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Productivity Rate</small>
                    <small class="text-muted fw-bold">
                        {{ $totalProductionHours > 0 ? number_format($totalProductionOutput / $totalProductionHours, 2) : '0.00' }}
                        units/hr
                    </small>
                </div>
                <div class="progress" style="height: 8px;">
                    @php
                        $targetPerHour = 10; // Set your target
                        $actualRate = $totalProductionHours > 0 ? $totalProductionOutput / $totalProductionHours : 0;
                        $percentage = min(($actualRate / $targetPerHour) * 100, 100);
                    @endphp
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%;"
                        aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>

            {{-- Monthly Trends Section --}}
            @if (isset($monthlyData) && count($monthlyData) > 0)
                <hr class="my-4">
                <h6 class="mb-3 fw-bold">
                    <i class="bi bi-graph-up text-info me-2"></i>6-Month Production Trends
                </h6>
                <div class="row g-2">
                    @foreach (array_slice($monthlyData, -6) as $data)
                        <div class="col-6 col-md-2">
                            <div class="text-center p-2 border rounded">
                                <small class="text-muted d-block"
                                    style="font-size: 0.75rem;">{{ $data['month'] }}</small>
                                <div class="fw-bold text-primary">{{ $data['projects'] }}</div>
                                <small class="text-muted" style="font-size: 0.7rem;">Projects</small>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-center p-2 bg-light rounded">
                            <i class="bi bi-arrow-down-circle text-success me-2"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Goods In</small>
                                <strong>{{ array_sum(array_column($monthlyData, 'goods_in')) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-center p-2 bg-light rounded">
                            <i class="bi bi-arrow-up-circle text-danger me-2"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Goods Out</small>
                                <strong>{{ array_sum(array_column($monthlyData, 'goods_out')) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-center p-2 bg-light rounded">
                            <i class="bi bi-clipboard-check text-primary me-2"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Requests</small>
                                <strong>{{ array_sum(array_column($monthlyData, 'requests')) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-3 text-center">
                <a href="{{ route('efficiency.index') }}" class="btn btn-sm btn-link">
                    <i class="bi bi-graph-up me-1"></i>View Full Efficiency Dashboard
                </a>
            </div>
        @else
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                <p class="mt-2 mb-0">No production data this month</p>
                <small>Start recording timing data to see efficiency metrics</small>
                <div class="mt-3">
                    <a href="{{ route('timings.index') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Start Recording
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
</div>
<div class="progress" style="height: 8px;">
    @php
        $targetPerHour = 10; // Set your target
        $actualRate = $totalProductionHours > 0 ? $totalProductionOutput / $totalProductionHours : 0;
        $percentage = min(($actualRate / $targetPerHour) * 100, 100);
    @endphp
    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%;"
        aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
    </div>
</div>
</div>

<div class="mt-3 text-center">
    <a href="{{ route('efficiency.index') }}" class="btn btn-sm btn-link">
        <i class="bi bi-graph-up me-1"></i>View Full Dashboard
    </a>
</div>
@else
<div class="text-center text-muted py-4">
    <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
    <p class="mt-2 mb-0">No production data this month</p>
    <small>Start recording timing data to see efficiency metrics</small>
</div>
@endif
</div>
</div>
