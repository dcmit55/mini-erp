<div class="row g-4 mb-4">
    <!-- Total Inventory -->
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('inventory.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="metric-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="fas fa-boxes fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="metric-value fw-bold fs-3 text-dark">{{ number_format($inventoryCount) }}</div>
                            <div class="metric-label text-muted">Total Inventory Items</div>
                            <div class="metric-trend small">
                                <span class="text-danger">
                                    <i class="fas fa-exclamation-triangle"></i> {{ $lowStockItems }} Low Stock
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Active Projects -->
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('projects.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="metric-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="fas fa-project-diagram fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="metric-value fw-bold fs-3 text-dark">{{ number_format($activeProjects) }}</div>
                            <div class="metric-label text-muted">Active Projects</div>
                            <div class="metric-trend small">
                                <span class="text-success">
                                    <i class="fas fa-arrow-up"></i> {{ $projectsThisMonth }} This Month
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Pending Requests -->
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('material_requests.index') }}?status=pending" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="metric-icon bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                <i class="fas fa-clock fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="metric-value fw-bold fs-3 text-dark">{{ number_format($pendingRequests) }}</div>
                            <div class="metric-label text-muted">Pending Requests</div>
                            <div class="metric-trend small">
                                <span class="text-info">
                                    <i class="fas fa-info-circle"></i> {{ $totalRequests }} Total
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Inventory Value or Alternative -->
    @if(in_array($user->role, ['super_admin', 'admin_finance', 'admin_logistic']))
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="metric-icon bg-info bg-opacity-10 text-info rounded-3 p-3">
                                <i class="fas fa-dollar-sign fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="metric-value fw-bold fs-3 text-dark">
                                IDR {{ number_format($totalInventoryValue, 0, ',', '.') }}
                            </div>
                            <div class="metric-label text-muted">Total Inventory Value</div>
                            <div class="metric-trend small">
                                <span class="text-muted">
                                    <i class="fas fa-calculator"></i> Real-time Calculation
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Alternative Metrics for Other Roles -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            @if($user->role === 'admin_mascot')
                                <div class="metric-icon bg-purple text-purple rounded-3 p-3">
                                    <i class="fas fa-theater-masks fs-4"></i>
                                </div>
                            @elseif($user->role === 'admin_costume')
                                <div class="metric-icon bg-pink text-pink rounded-3 p-3">
                                    <i class="fas fa-tshirt fs-4"></i>
                                </div>
                            @elseif($user->role === 'admin_animatronic')
                                <div class="metric-icon bg-cyan text-cyan rounded-3 p-3">
                                    <i class="fas fa-robot fs-4"></i>
                                </div>
                            @else
                                <div class="metric-icon bg-brand text-brand-icon rounded-3 p-3">
                                    <i class="fas fa-calendar-check fs-4"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="metric-value fw-bold fs-3 text-dark">{{ number_format($completedProjects) }}</div>
                            <div class="metric-label text-muted">Completed Projects</div>
                            <div class="metric-trend small">
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i> Total Finished
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>