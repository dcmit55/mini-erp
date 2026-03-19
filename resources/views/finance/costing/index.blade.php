@extends('layouts.app')

@section('styles')
    <style>
        /* ── Pagination ── */
        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.375rem;
            --bs-pagination-color: var(--bs-secondary-color, #6c757d);
            --bs-pagination-bg: var(--bs-body-bg);
            --bs-pagination-border-width: 1px;
            --bs-pagination-border-color: var(--bs-border-color);
            --bs-pagination-border-radius: 0.375rem;
            --bs-pagination-hover-color: var(--bs-body-color);
            --bs-pagination-hover-bg: var(--bs-tertiary-bg, #e9ecef);
            --bs-pagination-hover-border-color: var(--bs-border-color);
            --bs-pagination-focus-color: var(--bs-body-color);
            --bs-pagination-focus-bg: var(--bs-tertiary-bg, #e9ecef);
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(143, 18, 254, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #8F12FE;
            --bs-pagination-active-border-color: #4A25AA;
            --bs-pagination-disabled-color: var(--bs-secondary-color, #6c757d);
            --bs-pagination-disabled-bg: var(--bs-body-bg);
            --bs-pagination-disabled-border-color: var(--bs-border-color);
        }

        .page-link {
            transition: all 0.15s ease-in-out;
        }

        .page-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, .1);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            border-color: #8F12FE;
            box-shadow: 0 2px 4px rgba(143, 18, 254, .3);
        }

        /* ── Project Cards (compact cost-card style) ── */
        .project-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 14px;
            overflow: hidden;
            transition: box-shadow .25s, transform .25s;
            background: var(--bs-card-bg, var(--bs-body-bg));
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .project-card:hover {
            box-shadow: 0 6px 22px rgba(74, 37, 170, .18);
            transform: translateY(-2px);
            color: inherit;
            text-decoration: none;
        }

        /* ── Card header row (dept-badge + name + chevron) ── */
        .project-card .pc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .75rem 1rem .6rem;
            border-bottom: 1px solid var(--bs-border-color);
            gap: .5rem;
        }

        .project-card .pc-title {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .84rem;
            font-weight: 700;
            min-width: 0;
        }

        .project-card .pc-title .project-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Dept badges ── */
        .dept-badge {
            font-size: .62rem;
            font-weight: 600;
            padding: .18em .55em;
            border-radius: 20px;
            letter-spacing: .02em;
            flex-shrink: 0;
        }

        .dept-badge.mascot {
            background: rgba(255, 193, 7, 0.18);
            color: #b8860b;
        }

        .dept-badge.costume {
            background: rgba(23, 162, 184, 0.15);
            color: #0c7989;
        }

        .dept-badge.animatronic {
            background: rgba(111, 0, 168, 0.12);
            color: #9c4dcc;
        }

        .dept-badge.default {
            background: var(--bs-secondary-bg, #e9ecef);
            color: var(--bs-secondary-color, #495057);
        }

        /* ── Card body ── */
        .project-card .pc-body {
            padding: .65rem 1rem;
            font-size: .78rem;
        }

        /* ── Meta row (sales / deadline / JO) ── */
        .info-badge {
            font-size: .62rem;
            font-weight: 500;
            padding: .18em .5em;
            border-radius: 6px;
            background: rgba(74, 37, 170, .1);
            color: #7c52d6;
            border: 1px solid rgba(74, 37, 170, .18);
        }

        /* ── Cost rows ── */
        .cost-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: .18rem 0;
            font-size: .78rem;
        }

        .cost-row .cr-label {
            color: var(--bs-secondary-color, #6c757d);
        }

        .cost-row .cr-val {
            font-weight: 600;
            color: var(--bs-body-color);
        }

        .cost-row.highlight .cr-label {
            color: var(--bs-body-color);
            font-weight: 600;
        }

        .cost-row.highlight .cr-val {
            color: #8F12FE;
            font-weight: 700;
            font-size: .82rem;
        }

        /* Profit badge inline */
        .profit-chip {
            font-size: .63rem;
            font-weight: 700;
            padding: .13em .45em;
            border-radius: 20px;
            margin-left: .3rem;
        }

        .profit-chip.pos {
            background: rgba(25, 135, 84, .15);
            color: #198754;
        }

        .profit-chip.neg {
            background: rgba(220, 53, 69, .15);
            color: #dc3545;
        }

        /* ── PO badge row ── */
        .po-tag {
            font-size: .62rem;
            font-weight: 600;
            padding: .22em .6em;
            border-radius: 6px;
            border: 1px solid;
        }

        .po-tag.intl {
            background: rgba(255, 193, 7, .12);
            border-color: rgba(255, 193, 7, .4);
            color: #997404;
        }

        .po-tag.local {
            background: rgba(23, 162, 184, .1);
            border-color: rgba(23, 162, 184, .35);
            color: #0c7989;
        }

        .po-tag.usage {
            background: rgba(40, 167, 69, .1);
            border-color: rgba(40, 167, 69, .35);
            color: #198754;
        }

        /* Department filter tabs */
        .dept-filter-tabs .nav-link {
            font-size: .82rem;
            font-weight: 500;
            color: #6c757d;
            border-radius: 20px;
            padding: .35rem 1rem;
            border: 1px solid transparent;
            transition: all .15s;
        }

        .dept-filter-tabs .nav-link.active {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            color: #fff;
            border-color: #8F12FE;
        }

        .dept-filter-tabs .nav-link:not(.active):hover {
            background: rgba(143, 18, 254, .08);
            color: #8F12FE;
            border-color: rgba(143, 18, 254, .2);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--bs-secondary-color, #adb5bd);
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4 px-4">

        {{-- ── Page header ── --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-file-invoice-dollar gradient-icon" style="font-size:1.5rem;"></i>
                <h2 class="mb-0" style="font-size:1.3rem;">Project Costing Report</h2>
                <span class="badge bg-secondary ms-1" style="font-size:.7rem;">
                    {{ $projects->total() }} project{{ $projects->total() != 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        {{-- ── Filter form ── --}}
        <div class="card shadow-sm rounded mb-3">
            <div class="card-body py-3">
                <form id="filter-form" method="GET" action="{{ route('costing.report') }}"
                    class="row g-2 align-items-end">
                    <div class="col-lg-3">
                        <label class="form-label small text-muted mb-1">Project Name</label>
                        <input type="text" id="search-input" name="search" class="form-control form-control-sm"
                            placeholder="Search project name…" value="{{ request('search') }}">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted mb-1">Department</label>
                        <select id="filter-department" name="department" class="form-select form-select-sm select2"
                            data-placeholder="All Departments">
                            <option value="">All Departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department }}"
                                    {{ request('department') == $department ? 'selected' : '' }}>
                                    {{ ucfirst($department) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted mb-1">Creator / Sales</label>
                        <select id="filter-sales" name="sales" class="form-select form-select-sm select2"
                            data-placeholder="All Creators">
                            <option value="">All Creators</option>
                            @foreach ($salesOptions as $sales)
                                <option value="{{ $sales }}" {{ request('sales') == $sales ? 'selected' : '' }}>
                                    {{ $sales }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted mb-1">Job Order</label>
                        <select id="filter-job-order" name="job_order" class="form-select form-select-sm select2"
                            data-placeholder="All Job Orders">
                            <option value="">All Job Orders</option>
                            @foreach ($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}"
                                    {{ request('job_order') == $jobOrder->id ? 'selected' : '' }}>
                                    {{ $jobOrder->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted mb-1">Deadline Month</label>
                        <select id="filter-deadline-month" name="deadline_month" class="form-select form-select-sm select2"
                            data-placeholder="All Months">
                            <option value="">All Months</option>
                            @foreach ($deadlineMonths as $month)
                                <option value="{{ $month }}"
                                    {{ request('deadline_month') == $month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 d-flex gap-1">
                        <button id="filter-btn" type="submit" class="btn btn-sm btn-primary flex-fill">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('costing.report') }}"
                            class="btn btn-sm btn-outline-secondary flex-fill text-center" title="Reset Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Department tab pills ── --}}
        @php
            $activeDept = request('department', '');
            $deptTabs = [
                '' => ['label' => 'All', 'icon' => 'fas fa-th-large', 'emoji' => ''],
                'Costume' => ['label' => 'Costume', 'icon' => 'fas fa-tshirt', 'emoji' => '👗'],
                'Mascot' => ['label' => 'Mascot', 'icon' => 'fas fa-star', 'emoji' => '⭐'],
                'Animatronics' => ['label' => 'Animatronics', 'icon' => 'fas fa-robot', 'emoji' => '🤖'],
                'Plush' => ['label' => 'Plush', 'icon' => 'fas fa-cube', 'emoji' => '🧸'],
            ];
        @endphp
        <ul class="nav dept-filter-tabs mb-3 gap-1">
            @foreach ($deptTabs as $deptVal => $deptMeta)
                @php
                    $isActive =
                        ($deptVal === '' && $activeDept === '') ||
                        ($deptVal !== '' && stripos($activeDept, $deptVal) !== false);
                @endphp
                <li class="nav-item">
                    <a class="nav-link {{ $isActive ? 'active' : '' }}"
                        href="{{ route('costing.report', array_merge(request()->except('department', 'page'), $deptVal !== '' ? ['department' => $deptVal] : [])) }}">
                        @if ($deptMeta['emoji'])
                            {{ $deptMeta['emoji'] }}
                        @else
                            <i class="{{ $deptMeta['icon'] }} me-1"></i>
                        @endif
                        {{ $deptMeta['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- ── Project card grid ── --}}
        @if ($projects->isEmpty())
            <div class="empty-state">
                <i class="fas fa-inbox d-block"></i>
                <p class="mb-0 fw-semibold">No projects found</p>
                <small>Try adjusting your filters or <a href="{{ route('costing.report') }}">reset</a> them.</small>
            </div>
        @else
            <div class="row g-3">
                @foreach ($projects as $project)
                    @php
                        // ── Dept badge ──
                        $typeDept = $project->type_dept ?? '';
                        $deptSlug = strtolower($typeDept);
                        $badgeClass = match (true) {
                            str_contains($deptSlug, 'mascot') => 'mascot',
                            str_contains($deptSlug, 'costume') => 'costume',
                            str_contains($deptSlug, 'animatronic') => 'animatronic',
                            default => 'default',
                        };
                        $deptIcon = match (true) {
                            str_contains($deptSlug, 'mascot') => '⭐',
                            str_contains($deptSlug, 'costume') => '👗',
                            str_contains($deptSlug, 'animatronic') => '🤖',
                            default => '🏢',
                        };

                        $jobOrderCount = $project->jobOrders->count();
                        $salesName = $project->sales ?? '-';
                        $deadline = $project->deadline
                            ? \Carbon\Carbon::parse($project->deadline)->format('d M Y')
                            : '-';

                        // ── Avatar initials ──
                        $words = array_values(array_filter(explode(' ', $project->name)));

                        // ── Summary data from controller ──
                        $summary = $cardSummaries[$project->id] ?? [];
                        $intlPo = $summary['intl_po'] ?? 0;
                        $localPo = $summary['local_po'] ?? 0;
                        $usageIdr = $summary['usage_idr'] ?? 0;
                        $totalHours = $summary['total_hours'] ?? 0;

                        // Selling price = INT'L PO + LOCAL PO
$sellingPrice = $intlPo + $localPo;
// Actual cost   = material usage from stock (IDR)
$actualCost = $usageIdr;
$profit = $sellingPrice - $actualCost;
$profitPct = $sellingPrice > 0 ? round(($profit / $sellingPrice) * 100, 1) : null;
$hasData = $sellingPrice > 0 || $actualCost > 0;

// Format helpers
$fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
$fmtK = function ($n) {
    if ($n >= 1_000_000) {
        return 'Rp ' . number_format($n / 1_000_000, 1) . 'M';
    }
    if ($n >= 1_000) {
        return 'Rp ' . number_format($n / 1_000, 0) . 'k';
    }
    return 'Rp ' . number_format($n, 0);
                        };
                    @endphp

                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <a href="{{ route('costing.detail', $project->id) }}" class="project-card">

                            {{-- ── HEADER: dept badge + name + chevron ── --}}
                            <div class="pc-header">
                                <div class="pc-title">
                                    @if (!empty($typeDept))
                                        <span class="dept-badge {{ $badgeClass }}">{{ $deptIcon }}
                                            {{ $typeDept }}</span>
                                    @endif
                                    <span class="project-name" title="{{ $project->name }}">
                                        {{ \Illuminate\Support\Str::limit($project->name, 38) }}
                                    </span>
                                    @if (!empty($project->lark_record_id))
                                        <span title="Lark synced"
                                            style="color:#22c55e; font-size:.55rem; flex-shrink:0;">●</span>
                                    @endif
                                </div>
                                <i class="fas fa-chevron-right text-muted" style="font-size:.65rem; flex-shrink:0;"></i>
                            </div>

                            {{-- ── BODY ── --}}
                            <div class="pc-body">

                                {{-- Meta: sales, deadline, JO count --}}
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <span class="info-badge"><i class="fas fa-user me-1"></i>{{ $salesName }}</span>
                                    <span class="info-badge"><i
                                            class="far fa-calendar-alt me-1"></i>{{ $deadline }}</span>
                                    <span class="info-badge"><i class="fas fa-tasks me-1"></i>{{ $jobOrderCount }}
                                        JO</span>
                                </div>

                                {{-- Cost rows ── Selling / Actual / Profit ── --}}
                                <div class="cost-row">
                                    <span class="cr-label">Selling Price</span>
                                    <span class="cr-val">{{ $hasData ? $fmt($sellingPrice) : '—' }}</span>
                                </div>
                                <div class="cost-row">
                                    <span class="cr-label">Actual Project Cost</span>
                                    <span class="cr-val">{{ $hasData ? $fmt($actualCost) : '—' }}</span>
                                </div>
                                <div class="cost-row highlight">
                                    <span class="cr-label">Project Profit</span>
                                    <span class="cr-val">
                                        @if ($hasData && $sellingPrice > 0)
                                            {{ $fmt($profit) }}
                                            @if ($profitPct !== null)
                                                <span class="profit-chip {{ $profit >= 0 ? 'pos' : 'neg' }}">
                                                    {{ $profit >= 0 ? '+' : '' }}{{ $profitPct }}%
                                                </span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>

                                {{-- PO tags + Export ── --}}
                                <div class="d-flex align-items-center gap-1 flex-wrap pt-2 mt-2"
                                    style="border-top:1px solid var(--bs-border-color);">
                                    @if ($intlPo > 0)
                                        <span class="po-tag intl" title="INT'L PO">INT'L PO {{ $fmtK($intlPo) }}</span>
                                    @endif
                                    @if ($localPo > 0)
                                        <span class="po-tag local" title="LOCAL PO">LOCAL PO {{ $fmtK($localPo) }}</span>
                                    @endif
                                    @if ($usageIdr > 0)
                                        <span class="po-tag usage" title="Material Usage from Stock">USAGE
                                            {{ $fmtK($usageIdr) }}</span>
                                    @endif
                                    @if (!$hasData)
                                        <span class="text-muted" style="font-size:.62rem;">No data yet</span>
                                    @endif
                                    <a href="{{ route('costing.export', $project->id) }}"
                                        class="btn btn-xs btn-outline-success py-0 px-2 ms-auto" style="font-size:.65rem;"
                                        title="Export Excel"
                                        onclick="event.stopPropagation(); event.preventDefault(); window.location='{{ route('costing.export', $project->id) }}'">
                                        <i class="bi bi-file-earmark-excel"></i> Export
                                    </a>
                                </div>

                            </div>{{-- /pc-body --}}
                        </a>{{-- /project-card --}}
                    </div>{{-- /col --}}
                @endforeach
            </div>{{-- /row --}}

            {{-- ── Pagination ── --}}
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted small">
                    Showing {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }}
                    of {{ $projects->total() }} projects
                </div>
                <nav aria-label="Page navigation">
                    {{ $projects->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        @endif

    </div>{{-- /container-fluid --}}


    <!-- Costing Detail Modal -->
    <div class="modal fade" id="costingModal" tabindex="-1" aria-labelledby="costingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="costingModalLabel">Project Costing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Material Costs -->
                    <h6 class="mb-3"><i class="fas fa-box me-2 text-primary"></i>Material Costs</h6>
                    <table class="table table-sm align-middle table-hover">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th>Job Order</th>
                                <th>Material</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Unit Cost</th>
                                <th>Total Cost (IDR)</th>
                            </tr>
                        </thead>
                        <tbody id="costingTableBody">
                            <!-- Data akan dimuat melalui AJAX -->
                        </tbody>
                    </table>
                    <div class="text-end mb-4">
                        <h6 id="materialTotal">Material Total: <span class="text-primary fw-bold">Rp 0</span></h6>
                    </div>

                    <!-- Labor Costs -->
                    <h6 class="mb-3"><i class="fas fa-users me-2 text-success"></i>Timings Approval</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">Total Hours</small>
                                    <h5 id="laborHours" class="mb-0 text-success">0 hrs</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">Approved Sessions</small>
                                    <h5 id="laborSessions" class="mb-0 text-success">0</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">Job Orders</small>
                                    <h5 id="laborJobOrders" class="mb-0 text-success">0</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="laborByJobOrder" class="mb-4">
                        <!-- Labor breakdown by job order -->
                    </div>

                    <!-- Courier Costs -->
                    <h6 class="mb-3"><i class="fas fa-shipping-fast me-2 text-warning"></i>Courier Costs</h6>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">BT → SG Couriers</small>
                                    <h5 id="courierBtSgCount" class="mb-0 text-warning">0</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">SG → BT Couriers</small>
                                    <h5 id="courierSgBtCount" class="mb-0 text-warning">0</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">Total Items</small>
                                    <h5 id="courierItemsCount" class="mb-0 text-warning">0</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">Total Cost</small>
                                    <h5 id="courierTotalCost" class="mb-0 text-warning">SGD 0</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="courierDetails" class="mb-4">
                        <!-- Courier details will be loaded here -->
                    </div>

                    {{-- <!-- Inventory Items Breakdown -->
                    <h6 class="mb-3"><i class="fas fa-boxes me-2 text-info"></i>Goods Movement</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">Unique Items</small>
                                    <h5 id="inventoryItemsCount" class="mb-0 text-info">0 items</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <small class="text-muted">Total Transactions</small>
                                    <h5 id="inventoryTransactionsCount" class="mb-0 text-info">0</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="inventoryItemsDetails" class="mb-4">
                        <!-- Inventory items details -->
                    </div> --}}

                    <!-- Grand Total -->
                    <hr>
                    <h5 class="text-end" id="grandTotal">Material Total: <span class="text-success fw-bold">Rp 0</span>
                    </h5>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(function() {

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // Search on Enter key press (NOT on every keystroke)
            $('#search-input').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    $('#filter-form').submit();
                }
            });

            // Auto-submit ONLY on dropdown filter change (NOT search input)
            $('#filter-department, #filter-sales, #filter-job-order, #filter-deadline-month').on('change',
                function() {
                    $('#filter-form').submit();
                });

        }); // end $(function)

        function formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        }

        function viewCosting(projectId) {
            fetch(`/costing-report/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('costingTableBody');
                    tableBody.innerHTML = '';

                    document.getElementById('costingModalLabel').innerText = `Project Costing: ${data.project}`;

                    data.materials.forEach(material => {
                        const inventory = material.inventory || {
                            id: null,
                            name: 'N/A',
                            price: 0,
                            total_unit_cost: 0,
                            unit: 'N/A',
                            currency: {
                                name: 'N/A'
                            }
                        };

                        const name = inventory.name || 'N/A';
                        const unit = inventory.unit || 'N/A';
                        const price = inventory.price ?? 0;
                        const totalUnitCost = inventory.total_unit_cost ?? 0;
                        const currencyName = (inventory.currency && inventory.currency.name) ? inventory
                            .currency.name : 'N/A';
                        const quantity = material.used_quantity ?? 0;
                        const totalCost = material.total_cost ?? 0;
                        const jobOrderName = material.job_order_name || 'No Job Order';

                        const row = `
                    <tr>
                        <td><span class="badge bg-primary">${jobOrderName}</span></td>
                        <td>${name}</td>
                        <td>${quantity} ${unit}</td>
                        <td>${formatCurrency(price)} ${currencyName}</td>
                        <td class="fw-bold text-success">${formatCurrency(totalUnitCost)} ${currencyName}</td>
                        <td class="fw-bold">${formatCurrency(totalCost)} IDR</td>
                    </tr>
                `;
                        tableBody.innerHTML += row;
                    });

                    // Update Material Total
                    document.getElementById('materialTotal').innerHTML =
                        `Material Total: <span class="text-primary fw-bold">${formatCurrency(data.grand_total_material_idr)} IDR</span>`;

                    // ===== POPULATE LABOR DATA =====
                    const labor = data.labor || {};
                    const totalHours = labor.total_hours || 0;
                    const approvedSessions = labor.approved_sessions_count || 0;
                    const laborByJobOrder = labor.by_job_order || [];

                    document.getElementById('laborHours').innerText = totalHours.toFixed(2) + ' hrs';
                    document.getElementById('laborSessions').innerText = approvedSessions;
                    document.getElementById('laborJobOrders').innerText = laborByJobOrder.length;

                    // Labor breakdown by job order
                    const laborContainer = document.getElementById('laborByJobOrder');
                    laborContainer.innerHTML = '';

                    if (laborByJobOrder.length > 0) {
                        let laborHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                        laborHtml += '<thead class="table-light"><tr>';
                        laborHtml +=
                            '<th>Job Order</th><th>Hours</th><th>Minutes</th><th>Sessions</th><th>Employees</th>';
                        laborHtml += '</tr></thead><tbody>';

                        laborByJobOrder.forEach(jo => {
                            const jobOrderName = jo.job_order_name || 'No Job Order';
                            const hours = jo.total_hours || 0;
                            const minutes = jo.total_minutes || 0;
                            const sessions = jo.sessions_count || 0;
                            const employees = jo.unique_employees || 0;
                            const employeeNames = (jo.employee_names || []).join(', ');

                            laborHtml += '<tr>';
                            laborHtml += `<td><span class="badge bg-success">${jobOrderName}</span></td>`;
                            laborHtml += `<td class="fw-bold">${hours.toFixed(2)}</td>`;
                            laborHtml += `<td>${minutes}</td>`;
                            laborHtml += `<td>${sessions}</td>`;
                            laborHtml +=
                                `<td><span class="badge bg-info" title="${employeeNames}">${employees}</span></td>`;
                            laborHtml += '</tr>';
                        });

                        laborHtml += '</tbody></table></div>';
                        laborContainer.innerHTML = laborHtml;
                    } else {
                        laborContainer.innerHTML =
                            '<div class="alert alert-info"><i class="fas fa-info-circle me-1"></i>No approved labor timing data for this project</div>';
                    }

                    // ===== POPULATE COURIER DATA =====
                    const courier = data.courier || {};
                    const btSgCount = courier.bt_sg_count || 0;
                    const sgBtCount = courier.sg_bt_count || 0;
                    const courierItems = courier.total_items || 0;
                    const courierTotalSgd = courier.total_sgd || 0;
                    const couriers = courier.couriers || [];

                    document.getElementById('courierBtSgCount').innerText = btSgCount;
                    document.getElementById('courierSgBtCount').innerText = sgBtCount;
                    document.getElementById('courierItemsCount').innerText = courierItems;
                    document.getElementById('courierTotalCost').innerText = 'SGD ' + formatCurrency(courierTotalSgd);

                    // Courier details
                    const courierContainer = document.getElementById('courierDetails');
                    courierContainer.innerHTML = '';

                    if (couriers.length > 0) {
                        let courierHtml =
                            '<div class="table-responsive"><table class="table table-sm table-bordered table-hover">';
                        courierHtml += '<thead class="table-light"><tr>';
                        courierHtml +=
                            '<th>Courier ID</th><th>Direction</th><th>Date</th><th>Items</th><th>Transport (IDR)</th><th>Baggage (IDR)</th><th>GST (IDR)</th><th>Total SGD</th>';
                        courierHtml += '</tr></thead><tbody>';

                        couriers.forEach(c => {
                            const courierName = c.courier_name || 'Unknown';
                            const direction = c.direction || '-';
                            const date = c.date || '-';
                            const itemsCount = c.items_count || 0;
                            const itemsList = (c.items || []).slice(0, 3).join(', ');
                            const moreItems = c.items_count > 3 ? ` (+${c.items_count - 3} more)` : '';
                            const transport = c.transport_cost || 0;
                            const baggage = c.baggage_cost || 0;
                            const gst = c.gst_cost || 0;
                            const totalSgd = c.total_sgd || 0;

                            courierHtml += '<tr>';
                            courierHtml += `<td><small>${courierName}</small></td>`;
                            courierHtml +=
                                `<td><span class="badge ${direction.includes('BT →') ? 'bg-primary' : 'bg-info'}">${direction}</span></td>`;
                            courierHtml += `<td>${date}</td>`;
                            courierHtml +=
                                `<td><small title="${c.items ? c.items.join(', ') : ''}">${itemsCount} items: ${itemsList}${moreItems}</small></td>`;
                            courierHtml += `<td>Rp ${formatCurrency(transport)}</td>`;
                            courierHtml += `<td>Rp ${formatCurrency(baggage)}</td>`;
                            courierHtml += `<td>Rp ${formatCurrency(gst)}</td>`;
                            courierHtml +=
                                `<td class="fw-bold text-warning">SGD ${formatCurrency(totalSgd)}</td>`;
                            courierHtml += '</tr>';
                        });

                        courierHtml += '</tbody></table></div>';
                        courierContainer.innerHTML = courierHtml;
                    } else {
                        courierContainer.innerHTML =
                            '<div class="alert alert-info"><i class="fas fa-info-circle me-1"></i>No courier data for this project</div>';
                    }

                    // // ===== POPULATE INVENTORY ITEMS DATA =====
                    // const inventoryItems = data.inventory_items || {};
                    // const totalItems = inventoryItems.total_items || 0;
                    // const totalTransactions = inventoryItems.total_transactions || 0;
                    // const items = inventoryItems.items || [];

                    // document.getElementById('inventoryItemsCount').innerText = totalItems + ' items';
                    // document.getElementById('inventoryTransactionsCount').innerText = totalTransactions;

                    // // Inventory items details
                    // const inventoryContainer = document.getElementById('inventoryItemsDetails');
                    // inventoryContainer.innerHTML = '';

                    // if (items.length > 0) {
                    //     let inventoryHtml =
                    //         '<div class="table-responsive"><table class="table table-sm table-bordered table-hover">';
                    //     inventoryHtml += '<thead class="table-light"><tr>';
                    //     inventoryHtml +=
                    //         '<th>Material Name</th><th>Total Qty</th><th>Unit</th><th>Unit Cost</th><th>Currency</th><th>Total Cost</th><th>Txn</th><th>Job Orders</th>';
                    //     inventoryHtml += '</tr></thead><tbody>';

                    //     items.forEach(item => {
                    //         const name = item.inventory_name || 'N/A';
                    //         const totalQty = item.total_quantity || 0;
                    //         const unit = item.unit || '';
                    //         const unitCost = item.unit_cost || 0;
                    //         const currency = item.currency || 'SGD';
                    //         const totalCost = item.total_cost || 0;
                    //         const txnCount = item.transactions_count || 0;
                    //         const jobOrders = (item.job_orders || []).join(', ') || '-';

                    //         inventoryHtml += '<tr>';
                    //         inventoryHtml += `<td class="fw-bold">${name}</td>`;
                    //         inventoryHtml += `<td class="text-end">${totalQty}</td>`;
                    //         inventoryHtml += `<td>${unit}</td>`;
                    //         inventoryHtml +=
                    //             `<td class="text-end text-primary">${formatCurrency(unitCost)}</td>`;
                    //         inventoryHtml += `<td><span class="badge bg-secondary">${currency}</span></td>`;
                    //         inventoryHtml +=
                    //             `<td class="text-end fw-bold text-success">${formatCurrency(totalCost)}</td>`;
                    //         inventoryHtml +=
                    //             `<td class="text-center"><span class="badge bg-info">${txnCount}</span></td>`;
                    //         inventoryHtml += `<td class="small">${jobOrders}</td>`;
                    //         inventoryHtml += '</tr>';
                    //     });

                    //     inventoryHtml += '</tbody></table></div>';
                    //     inventoryContainer.innerHTML = inventoryHtml;
                    // } else {
                    //     inventoryContainer.innerHTML =
                    //         '<div class="alert alert-info"><i class="fas fa-info-circle me-1"></i>No inventory items data for this project</div>';
                    // }

                    // Update Grand Total (Material Only for now)
                    document.getElementById('grandTotal').innerHTML =
                        `Grand Total: <span class="text-success fw-bold">${formatCurrency(data.grand_total_material_idr)} IDR</span>`;

                    const modal = new bootstrap.Modal(document.getElementById('costingModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching costing data:', error);
                    alert('Failed to load costing data. Please try again.');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filter-form');
            const filterBtn = document.getElementById('filter-btn');
            if (filterForm && filterBtn) {
                const spinner = filterBtn.querySelector('.spinner-border');
                filterForm.addEventListener('submit', function() {
                    filterBtn.disabled = true;
                    if (spinner) spinner.classList.remove('d-none');
                });
            }
        });
    </script>
@endpush
