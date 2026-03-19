@extends('layouts.app')

@section('styles')
    <style>
        /* ── Base ── */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f9;
        }

        /* ── Pagination ── */
        .pagination {
            --bs-pagination-color: #6c5ce7;
            --bs-pagination-bg: var(--bs-body-bg);
            --bs-pagination-border-color: var(--bs-border-color);
            --bs-pagination-hover-color: var(--bs-body-color);
            --bs-pagination-hover-bg: var(--bs-tertiary-bg, #e9ecef);
            --bs-pagination-focus-color: #6c5ce7;
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(108, 92, 231, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #6c5ce7;
            --bs-pagination-active-border-color: #4A25AA;
        }

        .page-link {
            transition: all 0.15s ease-in-out;
        }

        .page-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, .08);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #6c5ce7 0%, #4A25AA 100%);
            border-color: #6c5ce7;
        }

        /* ── Filter bar ── */
        .filter-bar-card {
            background: var(--bs-body-bg);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .filter-bar-card .form-select,
        .filter-bar-card .form-control {
            background: var(--bs-tertiary-bg, #f8f9fa);
            border: none;
            border-radius: 8px;
        }

        .filter-bar-card .btn-primary {
            background: linear-gradient(135deg, #6c5ce7 0%, #4A25AA 100%);
            border: none;
        }

        /* ── Department tab pills ── */
        .dept-filter-tabs .nav-link {
            font-size: .82rem;
            font-weight: 500;
            color: #6c757d;
            border-radius: 20px;
            padding: .35rem 1.1rem;
            border: 1px solid #dee2e6;
            background: var(--bs-body-bg);
            transition: all .15s;
        }

        .dept-filter-tabs .nav-link.active {
            background: linear-gradient(135deg, #6c5ce7 0%, #4A25AA 100%);
            color: #fff;
            border-color: #6c5ce7;
        }

        .dept-filter-tabs .nav-link:not(.active):hover {
            background: rgba(108, 92, 231, .08);
            color: #6c5ce7;
            border-color: rgba(108, 92, 231, .3);
        }

        /* ── Project cards (horizontal split) ── */
        .project-card {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            background: var(--bs-body-bg);
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(108, 92, 231, 0.18);
            color: inherit;
            text-decoration: none;
        }

        /* ── Card icon/image side ── */
        .card-image-bg {
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }

        .mascot-bg {
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
        }

        .costume-bg {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }

        .animatronic-bg {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .plush-bg {
            background: linear-gradient(135deg, #f9d423 0%, #ff4e50 100%);
        }

        .default-bg {
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);
        }

        .card-emoji {
            font-size: 3.5rem;
        }

        /* ── Card right-side content ── */
        .pc-content {
            padding: 1.1rem 1.2rem;
        }

        .project-title {
            font-size: .95rem;
            font-weight: 700;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Section labels ── */
        .section-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: #9e9e9e;
            letter-spacing: 1px;
            margin-bottom: 6px;
            margin-top: 10px;
            text-transform: uppercase;
        }

        /* ── Dashed divider ── */
        .dashed {
            border: none;
            border-top: 1px dashed var(--bs-border-color);
            margin: 10px 0;
        }

        /* ── Cost rows ── */
        .cost-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: .8rem;
            padding: .1rem 0;
        }

        .cost-row .cr-label {
            color: var(--bs-secondary-color, #6c757d);
        }

        .cost-row .cr-val {
            font-weight: 600;
        }

        .cost-row.highlight .cr-val {
            color: #6c5ce7;
            font-weight: 700;
        }

        /* ── Profit chip ── */
        .profit-chip {
            font-size: .62rem;
            font-weight: 700;
            padding: .12em .42em;
            border-radius: 20px;
            margin-left: .3rem;
        }

        .profit-chip.pos {
            background: rgba(39, 174, 96, .15);
            color: #27ae60;
        }

        .profit-chip.neg {
            background: rgba(220, 53, 69, .15);
            color: #dc3545;
        }

        /* ── PO mini boxes ── */
        .po-box {
            flex: 1;
            text-align: center;
            padding: .4rem .3rem;
            border-radius: 8px;
            font-size: .72rem;
        }

        .po-box small {
            display: block;
            color: #9e9e9e;
            font-size: .6rem;
            font-weight: 600;
            letter-spacing: .04em;
        }

        .po-box strong {
            font-size: .78rem;
        }

        .po-box.intl {
            background: rgba(255, 193, 7, .15);
        }

        .po-box.local {
            background: rgba(40, 167, 69, .12);
        }

        .po-box.usage {
            background: rgba(13, 202, 240, .12);
        }

        /* ── Dept badge (icon panel overlay) ── */
        .dept-badge {
            font-size: .62rem;
            font-weight: 700;
            padding: .2em .55em;
            border-radius: 20px;
            flex-shrink: 0;
        }

        /* ── Info badge ── */
        .info-badge {
            font-size: .62rem;
            font-weight: 500;
            padding: .18em .5em;
            border-radius: 6px;
            background: rgba(108, 92, 231, .1);
            color: #6c5ce7;
            border: 1px solid rgba(108, 92, 231, .18);
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--bs-secondary-color, #adb5bd);
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        /* ── Dark mode overrides ── */
        [data-bs-theme="dark"] .filter-bar-card .form-select,
        [data-bs-theme="dark"] .filter-bar-card .form-control {
            background: rgba(255, 255, 255, .06);
            color: var(--bs-body-color);
        }

        [data-bs-theme="dark"] .dept-filter-tabs .nav-link {
            border-color: rgba(255, 255, 255, .15);
            background: transparent;
        }

        [data-bs-theme="dark"] .mascot-bg {
            background: linear-gradient(135deg, #6a3093 0%, #2c3e7a 100%);
        }

        [data-bs-theme="dark"] .costume-bg {
            background: linear-gradient(135deg, #8b2b35 0%, #6b2060 100%);
        }

        [data-bs-theme="dark"] .animatronic-bg {
            background: linear-gradient(135deg, #1a5276 0%, #0e6655 100%);
        }

        [data-bs-theme="dark"] .plush-bg {
            background: linear-gradient(135deg, #7d6608 0%, #922b21 100%);
        }

        [data-bs-theme="dark"] .default-bg {
            background: linear-gradient(135deg, #1a3a5c 0%, #1b2631 100%);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4 px-4">

        {{-- ── Page header ── --}}
        <div class="d-flex align-items-center mb-4 gap-3">
            <div class="bg-white p-2 rounded-3 shadow-sm" style="font-size:1.4rem;">📋</div>
            <h2 class="fw-bold m-0" style="font-size:1.25rem;">Project Costing Report</h2>
            <span class="badge rounded-pill text-bg-secondary opacity-75" style="font-size:.7rem;">
                {{ $projects->total() }} project{{ $projects->total() != 1 ? 's' : '' }}
            </span>
        </div>

        {{-- ── Filter form ── --}}
        <div class="card filter-bar-card mb-4">
            <div class="card-body py-3 px-4">
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
                        <button id="filter-btn" type="submit" class="btn btn-sm btn-primary rounded-pill flex-fill">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('costing.report') }}"
                            class="btn btn-sm btn-outline-secondary border-0 flex-fill text-center" title="Reset Filters">
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
            <div class="row g-4">
                @foreach ($projects as $project)
                    @php
                        // ── Dept badge ──
                        $typeDept = $project->type_dept ?? '';
                        $deptSlug = strtolower($typeDept);
                        $badgeClass = match (true) {
                            str_contains($deptSlug, 'mascot') => 'mascot',
                            str_contains($deptSlug, 'costume') => 'costume',
                            str_contains($deptSlug, 'animatronic') => 'animatronic',
                            str_contains($deptSlug, 'plush') => 'plush',
                            default => 'default',
                        };
                        $bgClass = $badgeClass . '-bg';
                        $deptEmoji = match ($badgeClass) {
                            'mascot' => '🦊',
                            'costume' => '👗',
                            'animatronic' => '🤖',
                            'plush' => '🧸',
                            default => '🏢',
                        };

                        $jobOrderCount = $project->jobOrders->count();
                        $salesName = $project->sales ?? '-';
                        $deadline = $project->deadline
                            ? \Carbon\Carbon::parse($project->deadline)->format('d M Y')
                            : '-';

                        // ── Summary data from controller ──
                        $summary = $cardSummaries[$project->id] ?? [];
                        $intlPo = $summary['intl_po'] ?? 0;
                        $localPo = $summary['local_po'] ?? 0;
                        $usageIdr = $summary['usage_idr'] ?? 0;
                        $totalHours = $summary['total_hours'] ?? 0;

                        $sellingPrice = $intlPo + $localPo;
                        $actualCost = $usageIdr;
                        $profit = $sellingPrice - $actualCost;
                        $profitPct = $sellingPrice > 0 ? round(($profit / $sellingPrice) * 100, 1) : null;
                        $hasData = $sellingPrice > 0 || $actualCost > 0;

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

                    <div class="col-xl-6 col-lg-12">
                        <a href="{{ route('costing.detail', $project->id) }}" class="project-card">
                            <div class="row g-0">

                                {{-- ── LEFT: coloured icon panel ── --}}
                                <div class="col-4 col-md-3 card-image-bg {{ $bgClass }}">
                                    @if (!empty($typeDept))
                                        <span class="badge bg-white text-dark position-absolute top-0 start-0 m-2"
                                            style="font-size:.6rem; opacity:.85;">{{ $typeDept }}</span>
                                    @endif
                                    <span class="card-emoji">{{ $deptEmoji }}</span>
                                    @if (!empty($project->lark_record_id))
                                        <span class="position-absolute bottom-0 end-0 m-2" title="Lark synced"
                                            style="color:#22c55e; font-size:.7rem;">● Lark</span>
                                    @endif
                                </div>

                                {{-- ── RIGHT: detail panel ── --}}
                                <div class="col-8 col-md-9 pc-content">

                                    {{-- Title + meta --}}
                                    <div class="project-title mb-1" title="{{ $project->name }}">
                                        {{ \Illuminate\Support\Str::limit($project->name, 45) }}
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        <span class="info-badge"><i class="fas fa-user me-1"></i>{{ $salesName }}</span>
                                        <span class="info-badge"><i
                                                class="far fa-calendar-alt me-1"></i>{{ $deadline }}</span>
                                        <span class="info-badge"><i class="fas fa-tasks me-1"></i>{{ $jobOrderCount }}
                                            JO</span>
                                    </div>

                                    {{-- ACTUALS section --}}
                                    <div class="section-label">ACTUALS</div>
                                    <div class="cost-row">
                                        <span class="cr-label">Selling Price</span>
                                        <span class="cr-val">{{ $hasData ? $fmt($sellingPrice) : '—' }}</span>
                                    </div>
                                    <div class="cost-row">
                                        <span class="cr-label">Actual Project Cost</span>
                                        <span class="cr-val">{{ $hasData ? $fmt($actualCost) : '—' }}</span>
                                    </div>
                                    <div class="cost-row highlight">
                                        <span class="cr-label fw-semibold" style="color:var(--bs-body-color);">Project
                                            Profit</span>
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

                                    <hr class="dashed">

                                    {{-- PO breakdown mini-boxes --}}
                                    <div class="d-flex gap-1 mt-1">
                                        <div class="po-box intl">
                                            <small>INT'L PO</small>
                                            <strong>{{ $intlPo > 0 ? $fmtK($intlPo) : '—' }}</strong>
                                        </div>
                                        <div class="po-box local">
                                            <small>LOCAL PO</small>
                                            <strong>{{ $localPo > 0 ? $fmtK($localPo) : '—' }}</strong>
                                        </div>
                                        <div class="po-box usage">
                                            <small>USAGE</small>
                                            <strong>{{ $usageIdr > 0 ? $fmtK($usageIdr) : '—' }}</strong>
                                        </div>
                                        <div class="ms-auto d-flex align-items-center">
                                            <a href="{{ route('costing.export', $project->id) }}"
                                                class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:.65rem;"
                                                title="Export Excel"
                                                onclick="event.stopPropagation(); event.preventDefault(); window.location='{{ route('costing.export', $project->id) }}'">
                                                <i class="bi bi-file-earmark-excel"></i>
                                            </a>
                                        </div>
                                    </div>

                                </div>{{-- /right panel --}}
                            </div>{{-- /row --}}
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
