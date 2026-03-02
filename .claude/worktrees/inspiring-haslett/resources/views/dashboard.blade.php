@extends('layouts.app')

@php
    $serverTime = \Carbon\Carbon::now();
@endphp

@section('content')
    <div class="container-fluid py-4">
        <!-- Welcome Header with Clock -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg bg-gradient-brand text-white position-relative overflow-hidden">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="ms-2">
                                        <h3 class="mb-1 fw-bold">Welcome, {{ ucwords($user->username) }}!</h3>
                                        <p class="mb-0 opacity-75">
                                            <i class="fas fa-shield-alt me-2"></i>
                                            {{ ucwords(str_replace('_', ' ', $user->role)) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="dashboard-clock-container">
                                    <div class="rounded-3 p-2 d-inline-block">
                                        <div id="realtime-clock" class="fs-5 fw-bold text-light">00:00</div>
                                        <div id="realtime-date" class="small opacity-75 text-light">Loading date...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Row dengan Link -->
        <div class="row g-4 mb-4">
            <!-- Total Inventory - Link ke Inventory Index -->
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
                                    <div class="metric-value fw-bold fs-3 text-dark">{{ number_format($inventoryCount) }}
                                    </div>
                                    <div class="metric-label text-muted">Total Inventory Items</div>
                                    <div class="metric-trend small">
                                        <span class="text-danger"><i class="fas fa-exclamation-triangle"></i>
                                            {{ $lowStockItems }} Low Stock</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Active Projects - Link ke Projects Index -->
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
                                    <div class="metric-value fw-bold fs-3 text-dark">{{ number_format($activeProjects) }}
                                    </div>
                                    <div class="metric-label text-muted">Active Projects</div>
                                    <div class="metric-trend small">
                                        <span class="text-success"><i class="fas fa-arrow-up"></i>
                                            {{ $projectsThisMonth }}
                                            This Month</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Pending Requests - Link ke Material Requests dengan Filter Pending -->
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
                                    <div class="metric-value fw-bold fs-3 text-dark">{{ number_format($pendingRequests) }}
                                    </div>
                                    <div class="metric-label text-muted">Pending Requests</div>
                                    <div class="metric-trend small">
                                        <span class="text-info"><i class="fas fa-info-circle"></i> {{ $totalRequests }}
                                            Total</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Inventory Value - Only for Super Admin, Admin Finance, and Admin Logistic -->
            @if (in_array($user->role, ['super_admin', 'admin_finance', 'admin_logistic']))
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
                                    <div class="metric-value fw-bold fs-3 text-dark">IDR
                                        {{ number_format($totalInventoryValue, 0, ',', '.') }}</div>
                                    <div class="metric-label text-muted">Total Inventory Value</div>
                                    <div class="metric-trend small">
                                        <span class="text-muted"><i class="fas fa-calculator"></i> Real-time
                                            Calculation</span>
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
                                    @if ($user->role === 'admin_mascot')
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
                                    <div class="metric-value fw-bold fs-3 text-dark">
                                        {{ number_format($completedProjects) }}
                                    </div>
                                    <div class="metric-label text-muted">Completed Projects</div>
                                    <div class="metric-trend small">
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Total
                                            Finished</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Charts and Analytics Row -->
        <div class="row g-4 mb-4">
            <!-- Monthly Trends Chart -->
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 fw-bold">Monthly Trends</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-brand dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" id="trendsFilterBtn">
                                    <i class="fas fa-filter me-1"></i> Last 6 Months
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item trends-filter" href="#" data-months="6">Last 6
                                            Months</a></li>
                                    <li><a class="dropdown-item trends-filter" href="#" data-months="12">Last
                                            Year</a></li>
                                    <li><a class="dropdown-item trends-filter" href="#" data-months="3">Last 3
                                            Months</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="trendsChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Request Status Distribution -->
            <div class="col-xl-4">
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
            </div>
        </div>

        <!-- Activity and Insights -->
        <div class="row g-4 mb-4">
            <!-- Recent Material Requests Section -->
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 fw-bold">Recent Material Requests</h5>
                            <a href="{{ route('material_requests.index') }}" class="btn btn-sm btn-outline-brand">View
                                All</a>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            @foreach ($recentRequests->take(10) as $request)
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
                                                <span
                                                    class="badge {{ $request->getStatusBadgeClass() }}">{{ ucfirst($request->status) }}</span>
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
            </div>

            <!-- Low Stock Items Section -->
            @php
                $canCreatePurchase = in_array($user->role, [
                    'super_admin',
                    'admin_logistic',
                    'admin_procurement',
                    'admin',
                ]);
            @endphp
            @if (isset($veryLowStockItems) && $veryLowStockItems->count() > 0)
                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap low-stock-header">
                                <h5 class="card-title mb-0 fw-bold">
                                    Low Stock Items
                                    <span class="badge bg-danger"
                                        id="lowStockCount">{{ $veryLowStockItems->count() }}</span>
                                </h5>
                                <!-- Filter Controls -->
                                <div class="d-flex gap-2 low-stock-filter-controls">
                                    <select id="lowStockCategorySelect" class="form-select form-select-sm select2"
                                        data-placeholder="All Category" style="min-width:90px;max-width:140px;">
                                        <option value="all">All Category</option>
                                        @foreach ($veryLowStockItems->pluck('category')->filter()->unique('id')->values() as $cat)
                                            <option value="{{ \Illuminate\Support\Str::slug($cat->name) }}">
                                                {{ $cat->name ?? 'Uncategorized' }}</option>
                                        @endforeach
                                    </select>
                                    <select id="lowStockSupplierSelect" class="form-select form-select-sm select2"
                                        data-placeholder="All Supplier" style="min-width:90px;max-width:140px;">
                                        <option value="all">All Supplier</option>
                                        @foreach ($veryLowStockItems->pluck('supplier')->filter()->unique('id')->values() as $sup)
                                            <option value="{{ \Illuminate\Support\Str::slug($sup->name) }}">
                                                {{ $sup->name ?? 'Unknown' }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" id="btnLowStockFilter"><i
                                            class="fas fa-filter me-1"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary" id="btnLowStockReset"><i
                                            class="fas fa-redo me-1"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                            <div id="lowStockListGroup" class="list-group list-group-flush">
                                @foreach ($veryLowStockItems as $item)
                                    <div
                                        class="list-group-item border-0 py-3 low-stock-item {{ \Illuminate\Support\Str::slug($item->category->name ?? 'uncategorized') }} supplier-{{ \Illuminate\Support\Str::slug($item->supplier->name ?? 'unknown') }}">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="activity-icon bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <i class="fas fa-box-open"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <div class="fw-semibold text-dark">
                                                    {{ $item->name }}
                                                    @if ($item->quantity < 2)
                                                        <span class="badge bg-danger ms-1">Critical</span>
                                                    @elseif ($item->quantity < 5)
                                                        <span class="badge bg-warning text-dark ms-1">Very Low</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted">
                                                    <span class="me-2"><i class="fas fa-tag"></i>
                                                        {{ $item->category->name ?? 'Uncategorized' }}</span>
                                                    <span class="me-2"><i class="fas fa-truck"></i>
                                                        {{ $item->supplier->name ?? '-' }}</span>
                                                    <span class="me-2"><i class="fas fa-exclamation-circle"></i> Stok:
                                                        {{ $item->quantity }} {{ $item->unit ?? '-' }}</span>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 ms-2">
                                                @if ($canCreatePurchase)
                                                    <a href="{{ route('purchase_requests.create', ['inventory_id' => $item->id, 'type' => 'restock']) }}"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Create Purchase Request">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Upcoming Deadlines & Department Overview --}}
        <div class="row g-4 mb-4">
            <!-- Department Overview -->
            <div class="col-xl-8 d-flex flex-column">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="card-title mb-0 fw-bold">Department Overview</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div class="row g-3">
                            @foreach ($departmentStats as $dept)
                                <div class="col-md-6 col-lg-4">
                                    <div class="dept-card bg-light rounded-3 p-3 text-center"
                                        data-department-id="{{ $dept->id }}"
                                        data-department-name="{{ $dept->name }}">
                                        <div class="dept-icon mb-2">
                                            <i class="fas fa-building text-brand-icon fs-3"></i>
                                        </div>
                                        <h6 class="fw-bold mb-1">{{ ucfirst($dept->name) }}</h6>
                                        <div class="small text-muted">
                                            {{ $dept->projects_count }} Projects • {{ $dept->users_count }} Users
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Deadlines -->
            <div class="col-xl-4 d-flex flex-column">
                <div class="card border-0 shadow-sm h-100 mb-0">
                    <div class="card-header bg-transparent border-0 py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 fw-bold">Upcoming Deadlines</h5>
                            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-brand">View
                                Projects</a>
                        </div>
                    </div>
                    <div class="card-body p-0" style="height: 100%; max-height: 400px; overflow-y: auto;">
                        @if ($upcomingDeadlines->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach ($upcomingDeadlines as $project)
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
                                                    @if ($project->departments && $project->departments->count())
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
            </div>
        </div>

        <!-- Super Admin Actions -->
        @if ($user->role === 'super_admin')
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="card-title mb-0 fw-bold text-danger">
                                <i class="fas fa-tools me-2"></i> System Administration
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-2">
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="{{ url('/log-viewer') }}" target="_blank"
                                        class="btn btn-outline-dark w-100">
                                        <i class="bi bi-journal-text d-block mb-1"></i>
                                        <small>Log Viewer</small>
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <button class="btn btn-outline-brand w-100 artisan-action" data-action="storage-link">
                                        <i class="bi bi-link d-block mb-1"></i>
                                        <small>Storage Link</small>
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-2 col-sm-4 col-6">
                                    <button class="btn btn-outline-danger w-100 artisan-action" data-action="clear-cache">
                                        <i class="bi bi-trash d-block mb-1"></i>
                                        <small>Clear Cache</small>
                                    </button>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <button class="btn btn-outline-warning w-100 artisan-action"
                                        data-action="config-clear">
                                        <i class="bi bi-gear d-block mb-1"></i>
                                        <small>Clear Config</small>
                                    </button>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <button class="btn btn-outline-success w-100 artisan-action"
                                        data-action="config-cache">
                                        <i class="bi bi-gear-fill d-block mb-1"></i>
                                        <small>Cache Config</small>
                                    </button>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <button class="btn btn-outline-info w-100 artisan-action" data-action="optimize">
                                        <i class="bi bi-lightning d-block mb-1"></i>
                                        <small>Optimize</small>
                                    </button>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <button class="btn btn-outline-secondary w-100 artisan-action"
                                        data-action="optimize-clear">
                                        <i class="bi bi-lightning-fill d-block mb-1"></i>
                                        <small>Clear Optimize</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        /* Professional ERP Dashboard Styles */
        .bg-gradient-brand {
            background: linear-gradient(45deg, #8F12FE, #4A25AA) !important;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Brand Color Classes */
        .text-brand-icon {
            color: #8F12FE !important;
        }

        .text-brand {
            color: #8F12FE !important;
        }

        .bg-brand {
            background-color: rgba(144, 18, 254, 0.1) !important;
        }

        .border-brand {
            border-color: #8F12FE !important;
        }

        /* Role-specific Colors */
        .text-purple {
            color: #8F12FE !important;
        }

        .bg-purple {
            background-color: rgba(144, 18, 254, 0.1) !important;
        }

        .text-pink {
            color: #c2185b !important;
        }

        .bg-pink {
            background-color: rgba(233, 30, 98, 0.1) !important;
        }

        .text-cyan {
            color: #138496 !important;
        }

        .bg-cyan {
            background-color: rgba(23, 163, 184, 0.1) !important;
        }

        .btn-outline-brand {
            color: #8F12FE;
            border-color: #8F12FE;
            background: transparent;
        }

        .btn-outline-brand:hover {
            color: #fff;
            background: linear-gradient(45deg, #8F12FE, #4A25AA);
            border-color: #8F12FE;
            box-shadow: 0 4px 15px rgba(143, 18, 254, 0.2);
        }

        .btn-brand {
            color: #fff;
            background: linear-gradient(45deg, #8F12FE, #4A25AA);
            border-color: #8F12FE;
        }

        .btn-brand:hover {
            color: #fff;
            background: linear-gradient(45deg, #7A0FE8, #3D1F8F);
            border-color: #7A0FE8;
            box-shadow: 0 4px 15px rgba(143, 18, 254, 0.3);
        }

        .card {
            border-radius: 12px !important;
            overflow: hidden !important;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-icon,
        .deadline-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dept-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .dept-card:hover {
            background-color: #eae3fd !important;
            transform: translateY(-2px);
        }

        .list-group-item {
            transition: background-color 0.2s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        /* Chart container styling */
        #trendsChart,
        #requestStatusChart {
            max-height: 300px;
            min-height: 180px;
        }

        /* Animation for loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Custom badge colors */
        .text-bg-pending {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        /* Gradient effects for cards */
        .card {
            border-radius: 12px;
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        /* Professional shadows */
        .shadow-sm {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }

        .shadow-lg {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12) !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .metric-value {
                font-size: 1.5rem !important;
            }

            .dashboard-clock-container {
                text-align: center !important;
                margin-top: 1rem;
            }

            .avatar-wrapper {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .low-stock-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.5rem !important;
            }

            .low-stock-filter-controls {
                width: 100%;
                flex-wrap: wrap;
                gap: 0.5rem !important;
                margin-top: 0.5rem;
            }

            .low-stock-filter-controls select {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
            }

            .low-stock-filter-controls button {
                width: auto !important;
                min-width: 36px !important;
                max-width: 48% !important;
                display: inline-block !important;
            }
        }

        @media (max-width: 768px) {
            #trendsChart {
                min-height: 260px !important;
                max-height: 320px !important;
            }

            .card-body>#trendsChart {
                min-height: 260px !important;
            }

            .card-body {
                padding-bottom: 0.5rem !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            $(".select2").select2({
                theme: "bootstrap-5",
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder');
                }
            }).on("select2:open", function() {
                setTimeout(
                    () => document.querySelector(".select2-search__field").focus(),
                    100
                );
            });

            let selectedCategory = 'all';
            let selectedSupplier = 'all';

            $('#lowStockCategorySelect').on('change', function() {
                selectedCategory = $(this).val();
            });
            $('#lowStockSupplierSelect').on('change', function() {
                selectedSupplier = $(this).val();
            });

            $('#btnLowStockFilter').on('click', function() {
                let visibleCount = 0;
                $('.low-stock-item').each(function() {
                    let show = true;
                    if (selectedCategory !== 'all' && !$(this).hasClass(selectedCategory)) show =
                        false;
                    if (selectedSupplier !== 'all' && !$(this).hasClass('supplier-' +
                            selectedSupplier)) show = false;
                    $(this).toggle(show);
                    if (show) visibleCount++;
                });
                $('#lowStockCount').text(visibleCount);
            });

            $('#btnLowStockReset').on('click', function() {
                $('#lowStockCategorySelect').val('all').trigger('change');
                $('#lowStockSupplierSelect').val('all').trigger('change');
                $('.low-stock-item').show();
                $('#lowStockCount').text($('.low-stock-item').length);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Charts
            initializeTrendsChart();
            initializeRequestStatusChart();

            // Initialize Clock
            initializeClock();

            // Initialize Artisan Actions
            initializeArtisanActions();

            // Department card click handler
            initializeDepartmentCards();
        });

        // Event listener untuk filter trends
        document.querySelectorAll('.trends-filter').forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault();
                const months = parseInt(this.dataset.months);
                const filterText = this.textContent;

                // Update button text
                document.getElementById('trendsFilterBtn').innerHTML =
                    `<i class="fas fa-filter me-1"></i> ${filterText}`;

                // Update chart
                updateTrendsChart(months);
            });
        });

        // Updated Monthly Trends Chart dengan parameter months
        function initializeTrendsChart(months = 6) {
            const ctx = document.getElementById('trendsChart');
            if (!ctx) return;

            // Filter data berdasarkan months
            const allMonthlyData = @json($monthlyData);
            const filteredData = allMonthlyData.slice(-months); // Ambil n bulan terakhir

            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: filteredData.map(item => item.month),
                    datasets: [{
                        label: 'Projects',
                        data: filteredData.map(item => item.projects),
                        borderColor: '#8F12FE',
                        backgroundColor: 'rgba(143, 18, 254, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Goods In',
                        data: filteredData.map(item => item.goods_in),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Goods Out',
                        data: filteredData.map(item => item.goods_out),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Requests',
                        data: filteredData.map(item => item.requests),
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 4,
                            hoverRadius: 6
                        }
                    }
                }
            });
        }

        // Function untuk update chart dengan filter baru
        function updateTrendsChart(months) {
            if (!trendsChart) return;

            const allMonthlyData = @json($monthlyData);
            const filteredData = allMonthlyData.slice(-months);

            trendsChart.data.labels = filteredData.map(item => item.month);
            trendsChart.data.datasets[0].data = filteredData.map(item => item.projects);
            trendsChart.data.datasets[1].data = filteredData.map(item => item.goods_in);
            trendsChart.data.datasets[2].data = filteredData.map(item => item.goods_out);
            trendsChart.data.datasets[3].data = filteredData.map(item => item.requests);

            trendsChart.update();
        }

        // Request Status Doughnut Chart
        function initializeRequestStatusChart() {
            const ctx = document.getElementById('requestStatusChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Approved', 'Delivered'],
                    datasets: [{
                        data: [{{ $pendingRequests }}, {{ $approvedRequests }},
                            {{ $deliveredRequests }}
                        ],
                        backgroundColor: [
                            '#ffc107',
                            '#0d6efd',
                            '#28a745'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        // Real-time Clock
        function initializeClock() {
            const serverTime = new Date("{{ $serverTime->format('Y-m-d\TH:i:sP') }}");
            let clientTime = new Date();
            const timeOffset = serverTime.getTime() - clientTime.getTime();

            function updateClock() {
                const now = new Date(Date.now() + timeOffset);
                const pad = n => n.toString().padStart(2, '0');
                const time = `${pad(now.getHours())}:${pad(now.getMinutes())}`;

                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const months = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                const day = days[now.getDay()];
                const date = now.getDate();
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                const fullDate = `${day}, ${date} ${month} ${year}`;

                const clockElement = document.getElementById('realtime-clock');
                const dateElement = document.getElementById('realtime-date');

                if (clockElement) clockElement.textContent = time;
                if (dateElement) dateElement.textContent = fullDate;
            }

            updateClock();
            setInterval(updateClock, 1000);
        }

        function initializeDepartmentCards() {
            document.querySelectorAll('.dept-card[data-department-id]').forEach(card => {
                card.addEventListener('click', function() {
                    const departmentId = this.dataset.departmentId;
                    const departmentName = this.dataset.departmentName;

                    // Gunakan department name, bukan ID
                    window.location.href =
                        `{{ route('projects.index') }}?department=${encodeURIComponent(departmentName)}`;
                });

                // Tambahkan hover effect
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        }

        // Artisan Actions
        function initializeArtisanActions() {
            document.querySelectorAll('.artisan-action').forEach(button => {
                button.addEventListener('click', function() {
                    const action = this.dataset.action;

                    Swal.fire({
                        title: 'Processing...',
                        text: `Executing ${action}...`,
                        icon: 'info',
                        scrollbarPadding: false,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`/artisan/${action}`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            // Check if response is JSON
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                throw new Error(
                                    'Server returned non-JSON response. Please check your configuration.'
                                    );
                            }

                            return response.json().then(data => {
                                if (!response.ok) {
                                    return Promise.reject(data);
                                }
                                return data;
                            });
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: true,
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message || 'Command failed.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Artisan error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: error.message ||
                                    'An unexpected error occurred. Please check the logs.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                });
            });
        }

        // Auto-refresh data every 5 minutes
        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 300000);
    </script>
@endpush
