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

        /* ══ PROJECT CARD — 1 project = 1 card ══ */
        .project-card {
            border-radius: 18px;
            border: none;
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: row;
            background: #fff;
            min-height: 0;
        }

        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(108, 92, 231, .15);
            color: inherit;
            text-decoration: none;
        }

        /* ── Left: photo panel (fixed width, stretches to card height) ── */
        .pc-photo-panel {
            width: 155px;
            min-width: 155px;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            align-self: stretch;
        }

        .pc-photo-panel-inner {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: .85rem .6rem;
        }

        /* Dept gradient backgrounds */
        .mascot-bg {
            background: linear-gradient(145deg, #e0c6ff 0%, #b388ff 50%, #8e6ecf 100%);
        }

        .costume-bg {
            background: linear-gradient(145deg, #ffe5b4 0%, #ffb347 50%, #ff8c00 100%);
        }

        .animatronic-bg {
            background: linear-gradient(145deg, #b3e5fc 0%, #4fc3f7 50%, #0288d1 100%);
        }

        .plush-bg {
            background: linear-gradient(145deg, #fff9c4 0%, #fff176 50%, #f9a825 100%);
        }

        .default-bg {
            background: linear-gradient(145deg, #b2dfdb 0%, #80cbc4 50%, #4db6ac 100%);
        }

        /* Photo image */
        .pc-photo-img {
            width: 90px;
            height: 90px;
            border-radius: 14px;
            object-fit: cover;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .2);
            border: 3px solid rgba(255, 255, 255, .5);
        }

        /* Placeholder when no image */
        .pc-photo-placeholder {
            width: 90px;
            height: 90px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .35);
            border: 2px dashed rgba(255, 255, 255, .65);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
        }

        /* Category badge (sits ABOVE the photo) */
        .pc-cat-badge {
            order: -1;
            /* push to top */
            display: inline-block;
            background: rgba(255, 255, 255, .9);
            color: #333;
            border-radius: 8px;
            padding: 3px 10px;
            font-size: .63rem;
            font-weight: 700;
            letter-spacing: .03em;
            backdrop-filter: blur(4px);
            box-shadow: 0 1px 4px rgba(0, 0, 0, .1);
        }

        /* Lark synced badge (bottom of panel) */
        .lark-tag {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, .8);
            padding: 3px 9px;
            border-radius: 20px;
            font-size: .58rem;
            font-weight: 600;
            color: #444;
            gap: 5px;
            white-space: nowrap;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lark-tag .dot {
            height: 6px;
            width: 6px;
            background: #2ecc71;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* ── Right: content body ── */
        .pc-body {
            flex: 1;
            min-width: 0;
            padding: .9rem 1.15rem .85rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fff;
        }

        /* Project name */
        .pc-name {
            font-size: .92rem;
            font-weight: 700;
            line-height: 1.3;
            color: var(--bs-body-color);
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Section label (ACTUALS / ESTIMATES) */
        .section-title {
            font-size: .6rem;
            font-weight: 800;
            color: #a29bfe;
            letter-spacing: .7px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .section-title.est {
            color: #6c757d;
        }

        /* Dashed divider */
        .dashed-divider {
            border: none;
            border-top: 1px dashed #e5e5e5;
            margin: 7px 0;
        }

        /* Data row */
        .pc-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: .76rem;
            padding: 1.5px 0;
        }

        .pc-row .pc-lbl {
            color: #6c757d;
        }

        .pc-row .pc-val {
            font-weight: 600;
            white-space: nowrap;
        }

        /* Profit row */
        .pc-row.profit .pc-lbl {
            font-weight: 600;
            color: var(--bs-body-color);
        }

        .pc-row.profit .pc-val {
            font-weight: 700;
            color: #27ae60;
        }

        /* Profit % badge */
        .profit-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 6px;
            font-size: .63rem;
            font-weight: 700;
            margin-left: 4px;
            vertical-align: middle;
        }

        .profit-badge.pos {
            background: #e8fbf3;
            color: #27ae60;
        }

        .profit-badge.neg {
            background: #fde8e8;
            color: #e74c3c;
        }

        /* ── Stats strip (INT'L PO / LOCAL PO / USAGE) ── */
        .stats-strip {
            display: flex;
            gap: 5px;
            margin-top: 8px;
        }

        .stats-box {
            flex: 1;
            text-align: center;
            padding: 7px 4px;
            border-radius: 10px;
        }

        .stats-box .label {
            font-size: .55rem;
            font-weight: 700;
            opacity: .65;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .stats-box .value {
            font-size: .8rem;
            font-weight: 700;
            margin-top: 1px;
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

        /* ── Dark mode ── */
        [data-bs-theme="dark"] .filter-bar-card .form-select,
        [data-bs-theme="dark"] .filter-bar-card .form-control {
            background: rgba(255, 255, 255, .06);
            color: var(--bs-body-color);
        }

        [data-bs-theme="dark"] .project-card {
            background: #1e1e2e;
            border: 1px solid rgba(255, 255, 255, .07);
        }

        [data-bs-theme="dark"] .pc-body {
            background: #1e1e2e;
        }

        [data-bs-theme="dark"] .pc-photo-placeholder {
            background: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .25);
        }

        [data-bs-theme="dark"] .dashed-divider {
            border-color: rgba(255, 255, 255, .1);
        }

        [data-bs-theme="dark"] .dept-filter-tabs .nav-link {
            border-color: rgba(255, 255, 255, .15);
            background: transparent;
        }

        [data-bs-theme="dark"] .mascot-bg {
            background: linear-gradient(145deg, #6a3093 0%, #3d1f6e 100%);
        }

        [data-bs-theme="dark"] .costume-bg {
            background: linear-gradient(145deg, #8b4513 0%, #5a2d0c 100%);
        }

        [data-bs-theme="dark"] .animatronic-bg {
            background: linear-gradient(145deg, #1a5276 0%, #0d3349 100%);
        }

        [data-bs-theme="dark"] .plush-bg {
            background: linear-gradient(145deg, #7d6608 0%, #4a3b05 100%);
        }

        [data-bs-theme="dark"] .default-bg {
            background: linear-gradient(145deg, #1a5c57 0%, #0d3330 100%);
        }
    </style>
    {{-- Flatpickr date range picker --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* ── Flatpickr integration tweaks ── */
        .flatpickr-calendar {
            font-family: 'Inter', sans-serif;
        }

        #deadline-range-picker {
            background: #fff !important;
            border: 1.5px solid #ced4da !important;
            border-radius: 8px !important;
        }

        #deadline-range-picker:focus {
            box-shadow: 0 0 0 0.25rem rgba(108, 92, 231, .25);
            border-color: #6c5ce7 !important;
        }

        a[href="{{ route('costing.report') }}"].btn-outline-secondary,
        .btn-reset-filter {
            border: 1.5px solid #adb5bd !important;
            border-radius: 8px !important;
        }

        .flatpickr-day.inRange,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #6c5ce7 !important;
            border-color: #6c5ce7 !important;
        }

        .flatpickr-day.inRange {
            background: rgba(108, 92, 231, .15) !important;
            border-color: transparent !important;
            color: #333 !important;
        }

        .flatpickr-day:hover {
            background: rgba(108, 92, 231, .25) !important;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4 px-4">

        {{-- ── Page header ── --}}
        <div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
            <div class="bg-white p-2 rounded-3 shadow-sm" style="font-size:1.4rem;">📋</div>
            <h2 class="fw-bold m-0" style="font-size:1.25rem;">Project Costing Report</h2>
            <span class="badge rounded-pill text-bg-secondary opacity-75" style="font-size:.7rem;">
                {{ $projects->total() }} project{{ $projects->total() != 1 ? 's' : '' }}
            </span>
            <span class="ms-auto text-muted" style="font-size:.68rem;">
                <i class="fas fa-sync-alt me-1 opacity-50"></i>Lark photos synced · dept-specific source
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
                    {{-- ── Date Range Picker (single input) ── --}}
                    <div class="col-lg-2">
                        <label class="form-label small text-muted mb-1">
                            Deadline Range
                        </label>
                        <div class="position-relative">
                            <span class="position-absolute top-50 translate-middle-y ps-2 text-muted pe-none"
                                style="z-index:5;">
                                <i class="" style="font-size:.75rem;"></i>
                            </span>
                            <input type="text" id="deadline-range-picker" class="form-control form-control-sm ps-4"
                                placeholder="All dates" readonly style="cursor:pointer;">
                            <input type="hidden" id="input-date-from" name="date_from" value="{{ request('date_from') }}">
                            <input type="hidden" id="input-date-to" name="date_to" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-lg-1 d-flex gap-1">
                        <button id="filter-btn" type="submit" class="btn btn-sm btn-primary rounded-pill flex-fill">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('costing.report') }}"
                            class="btn btn-sm btn-outline-secondary btn-reset-filter flex-fill text-center"
                            title="Reset Filters">
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
                            'costume' => '⚔️',
                            'animatronic' => '🤖',
                            'plush' => '🧸',
                            default => '🏢',
                        };

                        // Lark folder label
                        $larkFolder = !empty($typeDept) ? "Lark · {$typeDept} Folder" : 'Lark';

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

                    <div class="col-xl-6 col-lg-6 col-md-12">
                        <a href="{{ route('costing.detail', $project->id) }}" class="project-card">

                            {{-- ══ LEFT: photo panel ══ --}}
                            <div class="pc-photo-panel {{ $bgClass }}">
                                <div class="pc-photo-panel-inner">
                                    {{-- Category badge (top) --}}
                                    @if (!empty($typeDept))
                                        <span class="pc-cat-badge">{{ $typeDept }}</span>
                                    @endif

                                    {{-- Photo or placeholder (center) --}}
                                    @if (!empty($project->photo))
                                        <img src="{{ asset('storage/' . $project->photo) }}" class="pc-photo-img"
                                            alt="{{ $project->name }}">
                                    @else
                                        <div class="pc-photo-placeholder">{{ $deptEmoji }}</div>
                                    @endif

                                    {{-- Lark badge (bottom) --}}
                                    @if (!empty($project->lark_record_id))
                                        <span class="lark-tag">
                                            <span class="dot"></span>{{ $larkFolder }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- ══ RIGHT: content body ══ --}}
                            <div class="pc-body">

                                {{-- Project name --}}
                                <div class="mb-2">
                                    <span class="pc-name" title="{{ $project->name }}">
                                        {{ $project->name }}
                                    </span>
                                </div>

                                {{-- ── ACTUALS ── --}}
                                <div class="section-title">ACTUALS</div>
                                <div class="pc-row">
                                    <span class="pc-lbl">Actual Project Cost</span>
                                    <span class="pc-val">{{ $hasData ? $fmt($actualCost) : '—' }}</span>
                                </div>
                                <div class="pc-row">
                                    <span class="pc-lbl">Estimated Cost</span>
                                    <span class="pc-val">{{ $hasData ? $fmt($actualCost) : '—' }}</span>
                                </div>
                                <div class="pc-row">
                                    <span class="pc-lbl">Total Project Time</span>
                                    <span class="pc-val">{{ $totalHours > 0 ? $totalHours . ' hrs' : '—' }}</span>
                                </div>
                                <div class="pc-row">
                                    <span class="pc-lbl">Total Timing Cost</span>
                                    <span class="pc-val">{{ $totalHours > 0 ? $totalHours . ' hrs' : '—' }}</span>
                                </div>

                                {{-- ── Stats strip ── --}}
                                <div class="stats-strip">
                                    <div class="stats-box bg-warning-subtle text-warning-emphasis">
                                        <div class="label">INT'L PO</div>
                                        <div class="value">{{ $intlPo > 0 ? $fmtK($intlPo) : '—' }}</div>
                                    </div>
                                    <div class="stats-box bg-success-subtle text-success-emphasis">
                                        <div class="label">LOCAL PO</div>
                                        <div class="value">{{ $localPo > 0 ? $fmtK($localPo) : '—' }}</div>
                                    </div>
                                    <div class="stats-box bg-primary-subtle text-primary-emphasis">
                                        <div class="label">USAGE</div>
                                        <div class="value">{{ $usageIdr > 0 ? $fmtK($usageIdr) : '—' }}</div>
                                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
            $('#filter-department, #filter-sales, #filter-job-order').on('change', function() {
                $('#filter-form').submit();
            });

            // ── Flatpickr date range picker ──────────────────────────────────
            const dateFromVal = $('#input-date-from').val();
            const dateToVal = $('#input-date-to').val();

            flatpickr('#deadline-range-picker', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: false,
                showMonths: 2,
                defaultDate: (dateFromVal && dateToVal) ? [dateFromVal, dateToVal] : (dateFromVal ? [
                    dateFromVal
                ] : []),
                onChange: function(selectedDates) {
                    if (selectedDates.length === 0) {
                        $('#input-date-from').val('');
                        $('#input-date-to').val('');
                    } else if (selectedDates.length === 1) {
                        $('#input-date-from').val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                        $('#input-date-to').val('');
                    } else {
                        $('#input-date-from').val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                        $('#input-date-to').val(flatpickr.formatDate(selectedDates[1], 'Y-m-d'));
                        // Auto-submit once both dates are chosen
                        $('#filter-form').submit();
                    }
                },
                onClose: function(selectedDates) {
                    // If user closes with only 1 date selected, submit with just date_from
                    if (selectedDates.length === 1) {
                        $('#input-date-from').val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                        $('#input-date-to').val('');
                        $('#filter-form').submit();
                    }
                }
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
