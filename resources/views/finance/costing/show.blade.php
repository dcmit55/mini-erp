@extends('layouts.app')

@section('styles')
    <style>
        body {
            background: #f0f2f9;
        }

        /* ── Breadcrumb ── */
        .costing-breadcrumb {
            font-size: .8rem;
            color: #6c757d;
        }

        .costing-breadcrumb a {
            color: #6c5ce7;
            text-decoration: none;
        }

        .costing-breadcrumb a:hover {
            text-decoration: underline;
        }

        /* ── Hero card ── */
        .hero-card {
            background: var(--bs-card-bg, #fff);
            border-radius: 18px;
            border: none;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .hero-photo-panel {
            width: 220px;
            min-width: 220px;
            min-height: 220px;
            background: linear-gradient(145deg, #4A25AA 0%, #6c5ce7 50%, #8F12FE 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.25rem .75rem;
            position: relative;
            overflow: hidden;
        }

        .hero-photo-panel img {
            width: 120px;
            height: 120px;
            border-radius: 14px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, .25);
            box-shadow: 0 4px 16px rgba(0, 0, 0, .25);
        }

        .hero-photo-panel .initials-box {
            width: 120px;
            height: 120px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .12);
            border: 3px solid rgba(255, 255, 255, .25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
        }

        .hero-dept-chip {
            margin-top: .65rem;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .85);
            background: rgba(255, 255, 255, .15);
            padding: .2em .75em;
            border-radius: 20px;
        }

        .hero-lark-tag {
            margin-top: .45rem;
            font-size: .62rem;
            color: rgba(255, 255, 255, .6);
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .hero-lark-tag .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #28a745;
        }

        /* ── Hero body ── */
        .hero-body {
            flex: 1;
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .hero-top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .75rem;
        }

        .hero-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--bs-body-color);
            line-height: 1.2;
        }

        .hero-meta {
            font-size: .78rem;
            color: #6c757d;
            margin-top: .25rem;
        }

        /* Profit circle */
        .profit-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 4px solid #e9ecef;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profit-circle .pc-val {
            font-size: 1rem;
            font-weight: 800;
            color: #6c5ce7;
            line-height: 1;
        }

        .profit-circle .pc-label {
            font-size: .55rem;
            color: #adb5bd;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* ── Panels grid ── */
        .panels-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: .5rem;
        }

        .info-panel {
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            padding: .75rem 1rem;
            background: var(--bs-tertiary-bg, #f8f9fa);
        }

        .info-panel .ip-label {
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #6c5ce7;
            margin-bottom: .35rem;
        }

        .info-panel .ip-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: .15rem 0;
            border-bottom: 1px solid var(--bs-border-color);
            font-size: .78rem;
        }

        .info-panel .ip-row:last-child {
            border-bottom: none;
        }

        .info-panel .ipr-label {
            color: #6c757d;
        }

        .info-panel .ipr-val {
            font-weight: 600;
            color: var(--bs-body-color);
        }

        /* ── Overhead bar ── */
        .overhead-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .6rem 1rem;
            border-radius: 10px;
            background: rgba(108, 92, 231, .06);
            border: 1px solid rgba(108, 92, 231, .12);
            margin-top: .75rem;
            font-size: .78rem;
        }

        .overhead-bar .ob-chip {
            background: rgba(108, 92, 231, .12);
            padding: .2em .7em;
            border-radius: 6px;
            font-weight: 700;
            color: #6c5ce7;
            font-size: .7rem;
        }

        /* ── Action buttons ── */
        .hero-actions {
            display: flex;
            gap: .5rem;
            margin-top: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── PO badges ── */
        .po-badge {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            padding: .4rem 1rem;
            border-radius: 10px;
            font-size: .72rem;
            font-weight: 600;
            border: 1.5px solid;
        }

        .po-badge .pb-label {
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin-bottom: .1rem;
        }

        .po-badge.intl {
            background: rgba(255, 193, 7, 0.1);
            border-color: #ffc107;
            color: #997404;
        }

        .po-badge.local {
            background: rgba(23, 162, 184, 0.1);
            border-color: #17a2b8;
            color: #0c7989;
        }

        .po-badge.usage {
            background: rgba(40, 167, 69, 0.1);
            border-color: #28a745;
            color: #198754;
        }

        /* Dept badge */
        .dept-badge {
            font-size: .68rem;
            font-weight: 600;
            padding: .25em .65em;
            border-radius: 20px;
        }

        .dept-badge.mascot {
            background: rgba(255, 193, 7, 0.15);
            color: #b8860b;
        }

        .dept-badge.costume {
            background: rgba(23, 162, 184, 0.12);
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

        a.dept-badge {
            text-decoration: none;
            cursor: pointer;
            transition: opacity .15s, box-shadow .15s;
        }

        a.dept-badge:hover {
            opacity: .80;
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }

        /* ── 3 Cost cards ── */
        .cost-card {
            background: var(--bs-card-bg, #fff);
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
            height: 100%;
            overflow: hidden;
            transition: transform .15s, box-shadow .15s;
        }

        .cost-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(108, 92, 231, .12);
        }

        .cost-card .cc-header {
            padding: .85rem 1.15rem .65rem;
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cost-card .cc-title {
            font-size: .82rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .cost-card .cc-total {
            font-size: .92rem;
            font-weight: 800;
            color: #6c5ce7;
        }

        .cost-card .cc-pct {
            font-size: .65rem;
            font-weight: 600;
            background: rgba(108, 92, 231, .1);
            color: #6c5ce7;
            padding: .15em .5em;
            border-radius: 12px;
            margin-left: .35rem;
        }

        .cost-card .cc-body {
            padding: .75rem 1.15rem;
        }

        /* sub-section label */
        .sub-section {
            font-size: .63rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #adb5bd;
            margin: .6rem 0 .3rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .sub-section::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--bs-border-color);
        }

        /* cost table */
        .cost-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: .78rem;
        }

        .cost-tbl th {
            font-size: .65rem;
            color: #adb5bd;
            font-weight: 600;
            padding: .25rem .4rem;
            border-bottom: 1px solid var(--bs-border-color);
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .cost-tbl td {
            padding: .28rem .4rem;
            border-bottom: 1px solid var(--bs-border-color);
            color: var(--bs-body-color);
        }

        .cost-tbl tr:last-child td {
            border-bottom: none;
        }

        .cost-tbl .subtotal-row td {
            border-top: 1px solid var(--bs-border-color);
            font-weight: 700;
            background: rgba(108, 92, 231, 0.04);
        }

        .cost-tbl .text-end {
            text-align: right;
        }

        .cost-tbl .text-muted {
            color: #6c757d !important;
        }

        /* Timing group */
        .timing-group-header {
            background: rgba(108, 92, 231, 0.07);
            padding: .3rem .5rem;
            border-radius: 6px;
            font-size: .73rem;
            font-weight: 600;
            color: #6c5ce7;
            margin: .4rem 0 .2rem;
        }

        /* ── Grand Total bar ── */
        .grand-total-bar {
            background: linear-gradient(135deg, #1a1433 0%, #2d1b69 50%, #4A25AA 100%);
            border-radius: 16px;
            padding: 1.1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.25rem;
            box-shadow: 0 4px 20px rgba(74, 37, 170, .2);
        }

        .grand-total-bar .gt-label {
            color: #b0a8cc;
            font-size: .75rem;
        }

        .grand-total-bar .gt-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
        }

        .grand-total-bar .gt-breakdown {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .grand-total-bar .gt-item {
            text-align: center;
        }

        .grand-total-bar .gt-item .gti-label {
            font-size: .62rem;
            color: #b0a8cc;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .grand-total-bar .gt-item .gti-val {
            font-size: .88rem;
            font-weight: 700;
            color: #fff;
        }

        /* ── Responsive ── */
        @media (max-width: 992px) {
            .hero-photo-panel {
                width: 100%;
                min-width: unset;
                min-height: 200px;
                flex-direction: row;
                padding: 1rem 1.25rem;
                gap: 1rem;
            }

            .panels-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @php
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

        $deptNames = $project->departments->pluck('name');
        $firstDept = strtolower($deptNames->first() ?? '');
        $badgeClass = match (true) {
            str_contains($firstDept, 'mascot') => 'mascot',
            str_contains($firstDept, 'costume') => 'costume',
            str_contains($firstDept, 'animatronic') => 'animatronic',
            default => 'default',
        };
        $deptIcon = match (true) {
            str_contains($firstDept, 'mascot') => '⭐',
            str_contains($firstDept, 'costume') => '👗',
            str_contains($firstDept, 'animatronic') => '🤖',
            default => '🏢',
        };

        $words = array_values(array_filter(explode(' ', $project->name)));
        $initials = strtoupper(substr($words[0] ?? 'P', 0, 1) . substr($words[1] ?? '', 0, 1));

        $deadline = $project->deadline ? \Carbon\Carbon::parse($project->deadline)->format('d M Y') : '-';
        $salesName = $project->sales ?? '-';

        $laborTotal = $totalLaborHours;

        // Overhead = usage cost from stock
        $overheadIDR = $usageCostIDR ?? 0;

        // Cost percentages
        $matPct = $grandTotal > 0 ? round(($totalMaterialIDR / $grandTotal) * 100, 1) : 0;
        $freightPct = $grandTotal > 0 ? round(($totalFreightIDR / $grandTotal) * 100, 1) : 0;
    @endphp

    <div class="container-fluid px-4 py-3">

        {{-- ── Breadcrumb ── --}}
        <nav class="costing-breadcrumb mb-3 d-flex align-items-center gap-1">
            <a href="{{ route('costing.report') }}">All Departments</a>
            <span>/</span>
            @foreach ($deptNames as $dn)
                <a href="{{ route('costing.report', ['department' => $dn]) }}">{{ ucfirst($dn) }}</a>
                <span>/</span>
            @endforeach
            <span class="text-dark fw-semibold">{{ \Illuminate\Support\Str::limit($project->name, 45) }}</span>
            <span class="ms-auto text-muted" style="font-size:.72rem;">
                <i class="fas fa-circle text-success me-1" style="font-size:.45rem;"></i>
                Lark photos synced · dept-specific source
            </span>
        </nav>

        {{-- ── Hero card ── --}}
        <div class="hero-card">
            <div class="d-flex flex-wrap flex-lg-nowrap">

                {{-- LEFT: Photo panel --}}
                @php
                    $rawHeroImg = $project->img ?? '';
                    $heroImgUrl = null;
                    if (!empty($rawHeroImg)) {
                        $firstHeroImg = trim(explode(',', $rawHeroImg)[0]);
                        $heroImgUrl = str_starts_with($firstHeroImg, 'http')
                            ? $firstHeroImg
                            : asset('storage/' . $firstHeroImg);
                    }

                    // JO images for carousel
                    $heroJoImages = $project->jobOrders->filter(fn($jo) => !empty($jo->final_image))->values();
                @endphp
                <div class="hero-photo-panel" style="{{ $heroJoImages->count() > 0 ? 'padding:0; overflow:hidden;' : '' }}">
                    @if ($heroJoImages->count() > 0)
                        {{-- Hidden Fancybox gallery anchors (semua JO images) --}}
                        <div style="display:none;" aria-hidden="true">
                            @foreach ($heroJoImages as $idx => $jo)
                                <a href="{{ asset('storage/' . $jo->final_image) }}" data-fancybox="hero-jo-gallery"
                                    data-caption="{{ e($jo->name) }} — {{ e($project->name) }}"
                                    id="heroGalleryAnchor{{ $idx }}"></a>
                            @endforeach
                        </div>

                        {{-- JO Image Carousel — klik foto buka Fancybox --}}
                        <div id="heroJoCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3500"
                            style="width:100%; height:100%;">
                            <div class="carousel-inner" style="height:100%;">
                                @foreach ($heroJoImages as $idx => $jo)
                                    <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}"
                                        data-gallery-index="{{ $idx }}" style="height:100%; cursor:zoom-in;">
                                        <img src="{{ asset('storage/' . $jo->final_image) }}" alt="{{ e($jo->name) }}"
                                            class="hero-carousel-img" data-gallery-index="{{ $idx }}"
                                            style="width:100%; height:100%; object-fit:contain; background:#111; display:block; cursor:zoom-in;">
                                        {{-- JO name overlay --}}
                                        <div
                                            style="position:absolute; bottom:0; left:0; right:0;
                                            background: linear-gradient(to top, rgba(0,0,0,.75) 0%, transparent 100%);
                                            color:#fff; font-size:.65rem; font-weight:600; text-align:center;
                                            padding:18px 8px 7px; white-space:nowrap; overflow:hidden;
                                            text-overflow:ellipsis; pointer-events:none;">
                                            {{ $jo->name }}
                                        </div>
                                        {{-- Zoom hint overlay --}}
                                        <div
                                            style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,.45);
                                            color:#fff; border-radius:6px; padding:3px 8px; font-size:.65rem;
                                            pointer-events:none; display:flex; align-items:center; gap:4px;">
                                            <i class="bi bi-zoom-in"></i>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($heroJoImages->count() > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#heroJoCarousel"
                                    data-bs-slide="prev" style="width:32px;">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#heroJoCarousel"
                                    data-bs-slide="next" style="width:32px;">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                                {{-- Slide counter badge --}}
                                <div style="position:absolute; top:8px; left:8px; background:rgba(0,0,0,.55);
                                    color:#fff; font-size:.62rem; font-weight:700; padding:2px 8px;
                                    border-radius:10px; pointer-events:none;"
                                    id="heroJoCounter">
                                    1 / {{ $heroJoImages->count() }}
                                </div>
                            @endif
                            {{-- View all button --}}
                            <button type="button" id="btnViewAllPhotos"
                                style="position:absolute; bottom:8px; left:50%; transform:translateX(-50%);
                                    background:rgba(0,0,0,.52); color:#fff; border:1.5px solid rgba(255,255,255,.35);
                                    border-radius:20px; padding:3px 14px; font-size:.68rem; font-weight:600;
                                    cursor:pointer; white-space:nowrap; display:flex; align-items:center; gap:5px;
                                    backdrop-filter:blur(6px); z-index:10; transition:background .15s;">
                                <i class="bi bi-images"></i>
                                View All {{ $heroJoImages->count() }} Photos
                            </button>
                        </div>
                        {{-- Dept chip overlay --}}
                        <div style="position:absolute; bottom:34px; right:8px; pointer-events:none; z-index:10;">
                            <span
                                style="font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em;
                                color:rgba(255,255,255,.85); background:rgba(0,0,0,.42); padding:.2em .6em;
                                border-radius:8px;">{{ $deptIcon }}
                                {{ ucfirst($firstDept ?: 'Project') }}</span>
                        </div>
                    @else
                        {{-- Fallback: project image or initials --}}
                        @if ($heroImgUrl)
                            <a href="{{ $heroImgUrl }}" data-fancybox data-caption="{{ e($project->name) }}"
                                style="display:contents;">
                                <img src="{{ $heroImgUrl }}" alt="{{ $project->name }}" style="cursor:zoom-in;">
                            </a>
                        @else
                            <div class="initials-box">{{ $initials }}</div>
                        @endif
                        <div class="hero-dept-chip">{{ $deptIcon }} {{ ucfirst($firstDept ?: 'Project') }}</div>
                        <div class="hero-lark-tag">
                            <span class="dot"></span> Lark · {{ ucfirst($firstDept ?: 'Project') }} Album
                        </div>
                    @endif
                </div>

                {{-- RIGHT: Body --}}
                <div class="hero-body">
                    <div class="hero-top-row">
                        <div>
                            {{-- Department badges --}}
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @foreach ($deptNames as $dn)
                                    @php
                                        $ds = strtolower($dn);
                                        $bc = match (true) {
                                            str_contains($ds, 'mascot') => 'mascot',
                                            str_contains($ds, 'costume') => 'costume',
                                            str_contains($ds, 'animatronic') => 'animatronic',
                                            default => 'default',
                                        };
                                        $di = match (true) {
                                            str_contains($ds, 'mascot') => '⭐',
                                            str_contains($ds, 'costume') => '👗',
                                            str_contains($ds, 'animatronic') => '🤖',
                                            default => '🏢',
                                        };
                                    @endphp
                                    <a href="{{ route('costing.report', ['department' => $dn]) }}"
                                        class="dept-badge {{ $bc }}"
                                        title="Filter by {{ ucfirst($dn) }}">{{ $di }}
                                        {{ ucfirst($dn) }}</a>
                                @endforeach
                            </div>
                            <div class="hero-title">{{ $project->name }}</div>
                            <div class="hero-meta">
                                <i class="fas fa-user me-1"></i>
                                @if($salesName !== '-')
                                    <a href="{{ route('costing.report', ['sales' => $salesName]) }}"
                                       class="text-decoration-none"
                                       style="color:inherit;"
                                       title="Filter by sales: {{ $salesName }}">{{ $salesName }}</a>
                                @else
                                    {{ $salesName }}
                                @endif
                                <span class="mx-2">·</span>
                                <i class="far fa-calendar me-1"></i>Deadline: {{ $deadline }}
                                <span class="mx-2">·</span>
                                <i class="fas fa-tasks me-1"></i>{{ $project->jobOrders->count() }} Job Orders
                            </div>
                        </div>
                        {{-- Profit margin circle --}}
                        <div class="profit-circle">
                            @php
                                $profitMarginPct = $grandTotal > 0 ? 0 : 0;
                                // If we had selling price: ($sellingPrice - $grandTotal) / $sellingPrice * 100
                            @endphp
                            <div class="pc-val">—</div>
                            <div class="pc-label">Margin</div>
                        </div>
                    </div>

                    {{-- ── ACTUALS + PURCHASE ORDERS side by side ── --}}
                    <div class="panels-grid">
                        {{-- ACTUALS --}}
                        <div class="info-panel">
                            <div class="ip-label">Actuals</div>
                            <div class="ip-row">
                                <span class="ipr-label">Actual Project Cost</span>
                                <span class="ipr-val">{{ $fmt($grandTotal) }}</span>
                            </div>
                            <div class="ip-row">
                                <span class="ipr-label">Total Project Time</span>
                                <span class="ipr-val">{{ $totalLaborHours }} hrs</span>
                            </div>
                            <div class="ip-row">
                                <span class="ipr-label">Material Cost</span>
                                <span class="ipr-val">{{ $fmt($totalMaterialIDR) }}</span>
                            </div>
                            <div class="ip-row">
                                <span class="ipr-label">Freight Cost</span>
                                <span class="ipr-val">{{ $fmt($totalFreightIDR) }}</span>
                            </div>
                        </div>

                        {{-- PURCHASE ORDERS --}}
                        <div class="info-panel">
                            <div class="ip-label">Purchase Orders</div>
                            <div class="ip-row">
                                <span class="ipr-label">INT'L PO Total</span>
                                <span class="ipr-val">{{ $fmt($totalIntlPo) }}</span>
                            </div>
                            <div class="ip-row">
                                <span class="ipr-label">LOCAL PO Total</span>
                                <span class="ipr-val">{{ $fmt($totalLocalPo) }}</span>
                            </div>
                            <div class="ip-row">
                                <span class="ipr-label">PO Grand Total</span>
                                <span class="ipr-val" style="color:#6c5ce7;">{{ $fmt($totalPoIDR) }}</span>
                            </div>
                            <div class="ip-row">
                                <span class="ipr-label">Usage from Stock</span>
                                <span class="ipr-val">{{ $fmt($usageCostIDR) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Overhead bar --}}
                    <div class="overhead-bar">
                        <span class="ob-chip">OVERHEAD</span>
                        <span class="text-muted">Usage from stock / inventory</span>
                        <span class="fw-bold ms-auto">{{ $fmt($overheadIDR) }}</span>
                    </div>

                    {{-- PO badges + Actions --}}
                    <div class="hero-actions">
                        <div class="po-badge intl">
                            <span class="pb-label">INT'L PO</span>
                            <span>{{ $fmtK($totalIntlPo) }}</span>
                        </div>
                        <div class="po-badge local">
                            <span class="pb-label">LOCAL PO</span>
                            <span>{{ $fmtK($totalLocalPo) }}</span>
                        </div>
                        <div class="po-badge usage">
                            <span class="pb-label">USAGE</span>
                            <span>{{ $fmtK($usageCostIDR) }}</span>
                        </div>
                        <div class="ms-auto d-flex gap-2">
                            <a href="{{ route('costing.export', $project->id) }}" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                            </a>
                            <a href="{{ route('costing.report') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── 3 Cost Cards ── --}}
        <div class="row g-3">

            {{-- ── 1. Material Cost ── --}}
            <div class="col-lg-4">
                <div class="cost-card">
                    <div class="cc-header">
                        <div class="cc-title">
                            <i class="fas fa-box text-warning"></i>
                            <a href="{{ route('costing.detail.material', $project->id) }}"
                                class="text-decoration-none text-body">
                                Material Cost
                                <i class="fas fa-external-link-alt ms-1" style="font-size:.65rem; color:#6c5ce7;"></i>
                            </a>
                            <span class="cc-pct">{{ $matPct }}%</span>
                        </div>
                        <div class="cc-total">{{ $fmt($totalMaterialIDR) }}</div>
                    </div>
                    <div class="cc-body">

                        {{-- INTERNATIONAL PURCHASING --}}
                        @if ($intlMaterials->isNotEmpty())
                            <div class="sub-section">
                                <span style="color:#f4a400;font-size:.65rem;">🌐</span>
                                International Purchasing
                            </div>
                            <table class="cost-tbl">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total (IDR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($intlMaterials as $m)
                                        <tr>
                                            <td>{{ $m['name'] }}</td>
                                            <td class="text-end text-muted">{{ number_format($m['qty'], 2) }}
                                                {{ $m['unit'] }}</td>
                                            <td class="text-end text-muted">
                                                {{ number_format($m['unit_price'], 0, ',', '.') }} {{ $m['currency'] }}
                                            </td>
                                            <td class="text-end">Rp {{ number_format($m['total_idr'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="subtotal-row">
                                        <td colspan="3">Subtotal Int'l</td>
                                        <td class="text-end">Rp
                                            {{ number_format($intlMaterials->sum('total_idr'), 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        @endif

                        {{-- LOCAL PURCHASING --}}
                        @if ($localMaterials->isNotEmpty())
                            <div class="sub-section">
                                <span style="color:#17a2b8;font-size:.65rem;">🏠</span>
                                Local Purchasing
                            </div>
                            <table class="cost-tbl">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total (IDR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($localMaterials as $m)
                                        <tr>
                                            <td>{{ $m['name'] }}</td>
                                            <td class="text-end text-muted">{{ number_format($m['qty'], 2) }}
                                                {{ $m['unit'] }}</td>
                                            <td class="text-end text-muted">Rp
                                                {{ number_format($m['unit_price'], 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($m['total_idr'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="subtotal-row">
                                        <td colspan="3">Subtotal Local</td>
                                        <td class="text-end">Rp
                                            {{ number_format($localMaterials->sum('total_idr'), 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        @endif

                        @if ($intlMaterials->isEmpty() && $localMaterials->isEmpty())
                            <div class="text-center text-muted py-3" style="font-size:.8rem;">
                                <i class="fas fa-inbox me-1"></i>No material usage recorded
                            </div>
                        @endif

                        {{-- Total footer --}}
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top fw-bold"
                            style="font-size:.82rem;">
                            <span>Total Material Cost</span>
                            <span class="text-body fw-bold">{{ $fmt($totalMaterialIDR) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── 2. Workmanship Cost (Timings) ── --}}
            <div class="col-lg-4">
                <div class="cost-card">
                    <div class="cc-header">
                        <div class="cc-title">
                            <i class="fas fa-tools text-primary"></i>
                            <a href="{{ route('costing.detail.workmanship', $project->id) }}"
                                class="text-decoration-none text-body">
                                Workmanship Cost
                                <i class="fas fa-external-link-alt ms-1" style="font-size:.65rem; color:#6c5ce7;"></i>
                            </a>
                            <span class="text-muted fw-normal" style="font-size:.68rem;">(Timing Module)</span>
                        </div>
                        <div class="cc-total">{{ $totalLaborHours }} hrs</div>
                    </div>
                    <div class="cc-body">

                        @if ($timings->isNotEmpty())
                            <div class="sub-section">
                                <span style="color:#dc3545;font-size:.65rem;">⏱️</span>
                                Timing Apron Cost
                            </div>
                            @foreach ($timingsByJobOrder as $joGroup)
                                <div class="timing-group-header">
                                    <i class="fas fa-tasks me-1"></i>{{ $joGroup['job_order_name'] }}
                                    <span class="ms-auto float-end text-muted fw-normal"
                                        style="font-size:.68rem;">{{ $joGroup['total_hours'] }} hrs ·
                                        {{ $joGroup['sessions_count'] }} sessions</span>
                                </div>
                                <table class="cost-tbl">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Role</th>
                                            <th>In</th>
                                            <th>Out</th>
                                            <th class="text-end">Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($joGroup['rows'] as $row)
                                            <tr>
                                                <td>{{ $row['employee'] }}</td>
                                                <td class="text-muted">{{ $row['role'] }}</td>
                                                <td class="text-muted">{{ $row['start_time'] }}</td>
                                                <td class="text-muted">{{ $row['end_time'] }}</td>
                                                <td class="text-end fw-semibold">{{ $row['hours'] }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="subtotal-row">
                                            <td colspan="4">Total Hours</td>
                                            <td class="text-end">{{ $joGroup['total_hours'] }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-3" style="font-size:.8rem;">
                                <i class="fas fa-clock me-1"></i>No approved timing data
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top fw-bold"
                            style="font-size:.82rem;">
                            <span>Total Workmanship</span>
                            <span class="text-body fw-bold">{{ $totalLaborHours }} hrs</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── 3. Freight Cost ── --}}
            <div class="col-lg-4">
                <div class="cost-card">
                    <div class="cc-header">
                        <div class="cc-title">
                            <i class="fas fa-shipping-fast text-warning"></i>
                            <a href="{{ route('costing.detail.freight', $project->id) }}"
                                class="text-decoration-none text-body">
                                Freight Cost
                                <i class="fas fa-external-link-alt ms-1" style="font-size:.65rem; color:#6c5ce7;"></i>
                            </a>
                            <span class="cc-pct">{{ $freightPct }}%</span>
                        </div>
                        <div class="cc-total">{{ $fmt($totalFreightIDR) }}</div>
                    </div>
                    <div class="cc-body">

                        @php $couriers = $courierData['couriers'] ?? collect(); @endphp

                        @if (count($couriers) > 0)
                            {{-- SG → BT --}}
                            @php $sgBt = collect($couriers)->where('direction','SG → BT'); @endphp
                            @if ($sgBt->isNotEmpty())
                                <div class="sub-section">
                                    <span style="font-size:.65rem;">🇸🇬→🇮🇩</span> SG → BT
                                </div>
                                <table class="cost-tbl">
                                    <thead>
                                        <tr>
                                            <th>Shipment</th>
                                            <th class="text-end">Cost</th>
                                            <th>Carrier</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sgBt as $c)
                                            @foreach ($c['items'] ?? [] as $item)
                                                <tr>
                                                    <td>{{ is_array($item) ? $item['name'] ?? '—' : $item }}</td>
                                                    <td class="text-end text-muted">—</td>
                                                    <td class="text-muted">{{ $c['courier_name'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                        <tr class="subtotal-row">
                                            <td colspan="1">Subtotal SG → BT</td>
                                            <td class="text-end" colspan="2">Rp
                                                {{ number_format($sgBt->sum('total_idr'), 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endif

                            {{-- BT → SG --}}
                            @php $btSg = collect($couriers)->where('direction','BT → SG'); @endphp
                            @if ($btSg->isNotEmpty())
                                <div class="sub-section">
                                    <span style="font-size:.65rem;">🇮🇩→🇸🇬</span> BT → SG
                                </div>
                                <table class="cost-tbl">
                                    <thead>
                                        <tr>
                                            <th>Shipment</th>
                                            <th class="text-end">Cost</th>
                                            <th>Carrier</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($btSg as $c)
                                            @foreach ($c['items'] ?? [] as $item)
                                                <tr>
                                                    <td>{{ is_array($item) ? $item['name'] ?? '—' : $item }}</td>
                                                    <td class="text-end text-muted">—</td>
                                                    <td class="text-muted">{{ $c['courier_name'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                        <tr class="subtotal-row">
                                            <td colspan="1">Subtotal BT → SG</td>
                                            <td class="text-end" colspan="2">Rp
                                                {{ number_format($btSg->sum('total_idr'), 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endif
                        @else
                            <div class="text-center text-muted py-3" style="font-size:.8rem;">
                                <i class="fas fa-shipping-fast me-1"></i>No courier data recorded
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top fw-bold"
                            style="font-size:.82rem;">
                            <span>Total Freight Cost</span>
                            <span class="text-dark">{{ $fmt($totalFreightIDR) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /row --}}

        {{-- ── Grand Total bar ── --}}
        <div class="grand-total-bar">
            <div>
                <div class="gt-label">Grand Total Project Cost</div>
                <div class="gt-val">{{ $fmt($grandTotal) }}</div>
            </div>
            <div class="gt-breakdown">
                <div class="gt-item">
                    <div class="gti-label">MATERIAL</div>
                    <div class="gti-val">{{ $fmt($totalMaterialIDR) }}</div>
                </div>
                <div class="gt-item">
                    <div class="gti-label">WORKMANSHIP</div>
                    <div class="gti-val">{{ $totalLaborHours }} hrs</div>
                </div>
                <div class="gt-item">
                    <div class="gti-label">FREIGHT</div>
                    <div class="gti-val">{{ $fmt($totalFreightIDR) }}</div>
                </div>
                <div class="gt-item">
                    <div class="gti-label">OVERHEAD</div>
                    <div class="gti-val">{{ $fmt($overheadIDR) }}</div>
                </div>
            </div>
        </div>

    </div>
@endsection
@push('scripts')
    <script>
        // ── Hero JO Carousel: update slide counter ──────────────────────────
        (function() {
            var heroCarousel = document.getElementById('heroJoCarousel');
            if (!heroCarousel) return;

            var counter = document.getElementById('heroJoCounter');
            var total = heroCarousel.querySelectorAll('.carousel-item').length;

            heroCarousel.addEventListener('slid.bs.carousel', function(e) {
                if (counter) {
                    counter.textContent = (e.to + 1) + ' / ' + total;
                }
            });

            // Klik pada foto (carousel-item img) → buka Fancybox mulai dari slide yg aktif
            heroCarousel.addEventListener('click', function(e) {
                var img = e.target.closest('.hero-carousel-img');
                if (!img) return;
                var idx = parseInt(img.dataset.galleryIndex || 0);
                openHeroGallery(idx);
            });

            // Tombol "View All Photos"
            var btnAll = document.getElementById('btnViewAllPhotos');
            if (btnAll) {
                btnAll.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Buka dari slide yang sedang aktif
                    var activeItem = heroCarousel.querySelector('.carousel-item.active');
                    var activeIdx = activeItem ? parseInt(activeItem.dataset.galleryIndex || 0) : 0;
                    openHeroGallery(activeIdx);
                });
            }

            function openHeroGallery(startIdx) {
                var anchors = document.querySelectorAll('[data-fancybox="hero-jo-gallery"]');
                if (!anchors.length) return;
                var items = [];
                anchors.forEach(function(a) {
                    items.push({
                        src: a.href,
                        type: 'image',
                        caption: a.dataset.caption || '',
                        downloadSrc: a.href,
                    });
                });
                Fancybox.show(items, {
                    startIndex: startIdx,
                    Toolbar: {
                        display: ['zoom', 'fullscreen', 'download', 'close'],
                    },
                });
            }
        })();
    </script>
@endpush
