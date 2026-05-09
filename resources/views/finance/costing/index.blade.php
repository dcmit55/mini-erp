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
            min-height: 170px;
            position: relative;
        }

        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(108, 92, 231, .15);
            color: inherit;
            text-decoration: none;
        }

        /* ── WIP card styles ── */
        .pc-wip-card {
            border: 2px solid #ffc107 !important;
            box-shadow: 0 2px 14px rgba(255, 193, 7, .25) !important;
        }

        .pc-wip-card:hover {
            box-shadow: 0 8px 28px rgba(255, 193, 7, .35) !important;
        }

        .pc-wip-ribbon {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            background: #ffc107;
            color: #212529;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
            letter-spacing: .04em;
            pointer-events: none;
            white-space: nowrap;
        }

        /* ── Left: photo panel (fixed width, stretches full card height) ── */
        .pc-photo-panel {
            width: 175px;
            min-width: 175px;
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
            width: 130px;
            height: 130px;
            border-radius: 14px;
            object-fit: cover;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .2);
            border: 3px solid rgba(255, 255, 255, .5);
        }

        /* Placeholder when no image */
        .pc-photo-placeholder {
            width: 130px;
            height: 130px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .35);
            border: 2px dashed rgba(255, 255, 255, .65);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
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

        /* ── Card wrapper ── */
        .pc-card-wrapper {
            position: relative;
        }

        /* ── View Photos button (Fancybox trigger) overlaid on photo panel ── */
        .pc-view-photos-btn {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 20;
            background: rgba(0, 0, 0, .52);
            color: #fff;
            border: 1.5px solid rgba(255, 255, 255, .35);
            border-radius: 20px;
            padding: 3px 12px;
            font-size: .68rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background .15s, border-color .15s;
            backdrop-filter: blur(6px);
            text-decoration: none;
        }

        .pc-view-photos-btn:hover {
            background: rgba(108, 92, 231, .85);
            border-color: rgba(108, 92, 231, .7);
            color: #fff;
        }

        /* ── JO Final Images Carousel (left photo panel) ── */
        .pc-jo-carousel {
            position: absolute;
            inset: 0;
            border-radius: 0;
            overflow: hidden;
            z-index: 2;
        }

        .pc-jo-carousel .carousel-inner,
        .pc-jo-carousel .carousel-item {
            height: 100%;
        }

        .pc-jo-carousel-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .pc-jo-carousel .carousel-control-prev,
        .pc-jo-carousel .carousel-control-next {
            width: 28px;
            opacity: 0.75;
        }

        /* JO name label overlaid at bottom-center of each slide */
        .pc-jo-slide-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, .72) 0%, transparent 100%);
            color: #fff;
            font-size: .62rem;
            font-weight: 600;
            text-align: center;
            padding: 14px 6px 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            pointer-events: none;
            z-index: 5;
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
                    <div class="col-lg-2">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select id="filter-status" name="project_status" class="form-select form-select-sm">
                            <option value="all" {{ request('project_status', 'all') === 'all' ? 'selected' : '' }}>All
                                (WIP + Delivered)</option>
                            <option value="delivered" {{ request('project_status') === 'delivered' ? 'selected' : '' }}>
                                Delivered Only</option>
                            <option value="wip" {{ request('project_status') === 'wip' ? 'selected' : '' }}>WIP Only
                            </option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted mb-1">Project</label>
                        <select id="filter-project" name="project_id" class="form-select form-select-sm select2"
                            data-placeholder="All Projects" data-allow-clear="true">
                            <option value="">All Projects</option>
                            @foreach ($allProjects as $p)
                                <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
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
                        {{-- <button id="filter-btn" type="submit" class="btn btn-sm btn-primary rounded-pill flex-fill">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            <i class="fas fa-search"></i>
                        </button> --}}
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
        {{-- ── Project card grid (wrapped for AJAX replacement) ── --}}
        <div id="costing-grid">
            @include('finance.costing._grid', ['projects' => $projects, 'cardSummaries' => $cardSummaries])
        </div>

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
        (function() {
            var AJAX_URL = '{{ route('costing.report.ajax') }}';
            var loadingTimer = null;
            // Tracks the dept filter set by the tab pills (values like 'Mascot', 'Costume')
            // These are PARTIAL matches used with LIKE '%Mascot%' in controller.
            // They do NOT sync with #filter-department select (whose options are 'DCM Mascot', etc.)
            var deptTabFilter = '{{ request('department', '') }}';

            // ── Collect current filter values ────────────────────────────────────
            function getFilters(page) {
                // deptTabFilter = value from tab pill (e.g. 'Mascot')
                // #filter-department select = exact dept name (e.g. 'DCM Mascot')
                // Tab takes precedence; if a tab is active use that, else use the select
                var deptValue = deptTabFilter || $('#filter-department').val() || '';
                return {
                    project_status: $('#filter-status').val() || '',
                    project_id: $('#filter-project').val() || '',
                    department: deptValue,
                    sales: $('#filter-sales').val() || '',
                    job_order: $('#filter-job-order').val() || '',
                    date_from: $('#input-date-from').val() || '',
                    date_to: $('#input-date-to').val() || '',
                    page: page || 1,
                };
            }

            // ── Fetch and replace grid ───────────────────────────────────────────
            function fetchGrid(page) {
                var params = getFilters(page);

                // Update browser URL
                if (window.history && window.history.pushState) {
                    var qs = $.param(params);
                    window.history.pushState({}, '', '{{ route('costing.report') }}' + '?' + qs);
                }

                $('#costing-grid').css('opacity', 0.45);
                clearTimeout(loadingTimer);
                loadingTimer = setTimeout(function() {
                    $('#costing-grid').html(
                        '<div class="text-center py-5 text-muted"><div class="spinner-border" style="color:#6c5ce7;"></div><br><small class="mt-2 d-block">Loading…</small></div>'
                    );
                }, 600);

                $.ajax({
                    url: AJAX_URL,
                    method: 'GET',
                    data: params,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(res) {
                        clearTimeout(loadingTimer);
                        $('#costing-grid').css('opacity', 1).html(res.html);
                        // Re-bind pagination links inside #costing-grid
                        bindPagination();
                    },
                    error: function() {
                        clearTimeout(loadingTimer);
                        $('#costing-grid').css('opacity', 1).html(
                            '<div class="alert alert-danger">Gagal memuat data. Silakan reload halaman.</div>'
                        );
                    },
                });
            }

            // ── Bind AJAX to pagination links inside #costing-grid ───────────────
            function bindPagination() {
                $(document).off('click.costingPager').on('click.costingPager', '#costing-grid .pagination a', function(
                    e) {
                    e.preventDefault();
                    var href = $(this).attr('href');
                    var page = (new URL(href, window.location.origin)).searchParams.get('page') || 1;
                    fetchGrid(page);
                    $('html, body').animate({
                        scrollTop: $('#costing-grid').offset().top - 80
                    }, 200);
                });
            }

            $(function() {
                // ── Select2 init ─────────────────────────────────────────────────
                // Init all .select2 elements (department, sales, job-order, project)
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    placeholder: function() {
                        return $(this).data('placeholder') || 'Select...';
                    },
                    allowClear: true,
                });

                // Override #filter-project specifically (needs width:100% + search)
                $('#filter-project').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'All Projects',
                    allowClear: true,
                    width: '100%',
                });

                // ── Status, Project, Sales, Job Order → AJAX ────────────────────
                $('#filter-status, #filter-project, #filter-sales, #filter-job-order')
                    .on('change', function() {
                        fetchGrid(1);
                    });

                // ── Department select: clears dept tab filter, resets tab to All ─
                $('#filter-department').on('change', function() {
                    deptTabFilter = ''; // clear tab-level override
                    // Reset tab pills: remove active from all, activate 'All'
                    $('.dept-filter-tabs .nav-link').removeClass('active');
                    $('.dept-filter-tabs .nav-link').first().addClass('active');
                    fetchGrid(1);
                });

                // ── Flatpickr date range picker ──────────────────────────────────
                const dateFromVal = $('#input-date-from').val();
                const dateToVal = $('#input-date-to').val();

                flatpickr('#deadline-range-picker', {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: false,
                    showMonths: 2,
                    defaultDate: (dateFromVal && dateToVal) ? [dateFromVal, dateToVal] : (dateFromVal ?
                        [dateFromVal] : []),
                    onChange: function(selectedDates) {
                        if (selectedDates.length === 0) {
                            $('#input-date-from').val('');
                            $('#input-date-to').val('');
                        } else if (selectedDates.length === 1) {
                            $('#input-date-from').val(flatpickr.formatDate(selectedDates[0],
                                'Y-m-d'));
                            $('#input-date-to').val('');
                        } else {
                            $('#input-date-from').val(flatpickr.formatDate(selectedDates[0],
                                'Y-m-d'));
                            $('#input-date-to').val(flatpickr.formatDate(selectedDates[1],
                                'Y-m-d'));
                            fetchGrid(1);
                        }
                    },
                    onClose: function(selectedDates) {
                        if (selectedDates.length === 1) {
                            $('#input-date-from').val(flatpickr.formatDate(selectedDates[0],
                                'Y-m-d'));
                            $('#input-date-to').val('');
                            fetchGrid(1);
                        }
                        if (selectedDates.length === 0) {
                            fetchGrid(1);
                        }
                    }
                });

                // Initial pagination binding
                bindPagination();

                // ── Dept tab pills: intercept and AJAX-load ──────────────────────
                // NOTE: Tab values ('Mascot', 'Costume') are PARTIAL — they don't match
                // #filter-department options ('DCM Mascot', 'DCM Costume'). So we use
                // deptTabFilter variable directly instead of syncing the select.
                $(document).on('click', '.dept-filter-tabs .nav-link', function(e) {
                    e.preventDefault();
                    var url = $(this).attr('href');
                    var dept = (new URL(url, window.location.origin)).searchParams.get('department') ||
                        '';
                    // Update active state visually
                    $('.dept-filter-tabs .nav-link').removeClass('active');
                    $(this).addClass('active');
                    // Set the dept tab filter variable (used in getFilters)
                    deptTabFilter = dept;
                    // Clear department select (tab takes precedence)
                    $('#filter-department').val('').trigger('change.select2');
                    fetchGrid(1);
                });

            }); // end $(function)

            // ── Fancybox gallery ─────────────────────────────────────────────────
            $(document).on('click', '.btn-open-gallery', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var projectId = $(this).data('project-id');
                var anchors = document.querySelectorAll('[data-fancybox="costing-gallery-' + projectId + '"]');
                if (!anchors.length) return;
                var items = [];
                anchors.forEach(function(a) {
                    items.push({
                        src: a.href,
                        type: 'image',
                        caption: a.dataset.caption || '',
                        downloadSrc: a.href
                    });
                });
                Fancybox.show(items, {
                    startIndex: 0,
                    Toolbar: {
                        display: ['zoom', 'fullscreen', 'download', 'close']
                    }
                });
            });

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
                            const row =
                                `<tr><td><span class="badge bg-primary">${material.job_order_name || 'No Job Order'}</span></td><td>${inventory.name || 'N/A'}</td><td>${material.used_quantity ?? 0} ${inventory.unit || ''}</td><td>${formatCurrency(inventory.price ?? 0)} ${(inventory.currency && inventory.currency.name) ? inventory.currency.name : 'N/A'}</td><td class="fw-bold text-success">${formatCurrency(inventory.total_unit_cost ?? 0)}</td><td class="fw-bold">${formatCurrency(material.total_cost ?? 0)} IDR</td></tr>`;
                            tableBody.innerHTML += row;
                        });
                        document.getElementById('materialTotal').innerHTML =
                            `Material Total: <span class="text-primary fw-bold">${formatCurrency(data.grand_total_material_idr)} IDR</span>`;
                        const modal = new bootstrap.Modal(document.getElementById('costingModal'));
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to load costing data.');
                    });
            }

        }());
    </script>
@endpush
