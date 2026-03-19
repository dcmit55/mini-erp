@extends('layouts.app')

@section('styles')
    <style>
        body {
            background: var(--bs-body-bg);
        }

        /* ── Breadcrumb ── */
        .costing-breadcrumb {
            font-size: .8rem;
            color: #6c757d;
        }

        .costing-breadcrumb a {
            color: #4A25AA;
            text-decoration: none;
        }

        .costing-breadcrumb a:hover {
            text-decoration: underline;
        }

        /* ── Project hero card ── */
        .hero-card {
            background: var(--bs-card-bg, var(--bs-body-bg));
            border-radius: 16px;
            border: 1px solid var(--bs-border-color);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .hero-img-wrap {
            width: 130px;
            min-width: 130px;
            height: 130px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #8F12FE, #4A25AA);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .hero-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ── ACTUALS / ESTIMATES panels ── */
        .panel-section {
            font-size: .75rem;
        }

        .panel-section .ps-label {
            font-size: .63rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #4A25AA;
            margin-bottom: .4rem;
        }

        .panel-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: .18rem 0;
            border-bottom: 1px solid var(--bs-border-color);
            font-size: .8rem;
        }

        .panel-row:last-child {
            border-bottom: none;
        }

        .panel-row .pr-label {
            color: var(--bs-secondary-color, #6c757d);
        }

        .panel-row .pr-val {
            font-weight: 600;
            color: var(--bs-body-color);
        }

        .profit-badge {
            font-size: .7rem;
            font-weight: 700;
            padding: .15em .55em;
            border-radius: 20px;
            margin-left: .35rem;
        }

        .profit-pos {
            background: rgba(25, 135, 84, 0.15);
            color: #198754;
        }

        .profit-neg {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        /* ── PO footer badges ── */
        .po-badge {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            padding: .5rem 1.2rem;
            border-radius: 10px;
            font-size: .72rem;
            font-weight: 600;
            border: 1.5px solid;
        }

        .po-badge .pb-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin-bottom: .15rem;
        }

        .po-badge.intl {
            background: rgba(255, 193, 7, 0.12);
            border-color: #ffc107;
            color: #997404;
        }

        .po-badge.local {
            background: rgba(23, 162, 184, 0.12);
            border-color: #17a2b8;
            color: #0c7989;
        }

        .po-badge.usage {
            background: rgba(40, 167, 69, 0.12);
            border-color: #28a745;
            color: #198754;
        }

        /* ── Cost section cards ── */
        .cost-card {
            background: var(--bs-card-bg, var(--bs-body-bg));
            border-radius: 14px;
            border: 1px solid var(--bs-border-color);
            height: 100%;
        }

        .cost-card .cc-header {
            padding: .85rem 1rem .65rem;
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
            font-size: .88rem;
            font-weight: 700;
            color: #4A25AA;
        }

        .cost-card .cc-body {
            padding: .75rem 1rem;
        }

        /* sub-section label inside cost card */
        .sub-section {
            font-size: .65rem;
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
            font-size: .68rem;
            color: var(--bs-secondary-color, #adb5bd);
            font-weight: 600;
            padding: .25rem .4rem;
            border-bottom: 1px solid var(--bs-border-color);
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
            background: rgba(143, 18, 254, 0.04);
        }

        .cost-tbl .text-end {
            text-align: right;
        }

        .cost-tbl .text-muted {
            color: #6c757d !important;
        }

        /* workmanship timing */
        .timing-group-header {
            background: rgba(143, 18, 254, 0.07);
            padding: .3rem .5rem;
            border-radius: 6px;
            font-size: .73rem;
            font-weight: 600;
            color: #8F12FE;
            margin: .4rem 0 .2rem;
        }

        /* Grand Total bar */
        .grand-total-bar {
            background: linear-gradient(135deg, #1a1433 0%, #2d1b69 100%);
            border-radius: 14px;
            padding: 1.1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .grand-total-bar .gt-label {
            color: #b0a8cc;
            font-size: .8rem;
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
            font-size: .65rem;
            color: #b0a8cc;
        }

        .grand-total-bar .gt-item .gti-val {
            font-size: .85rem;
            font-weight: 700;
            color: #fff;
        }

        /* Profit margin progress */
        .pm-bar {
            height: 8px;
            border-radius: 4px;
            background: var(--bs-border-color);
            overflow: hidden;
        }

        .pm-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #4A25AA, #8F12FE);
        }

        /* Dept badge */
        .dept-badge {
            font-size: .72rem;
            font-weight: 600;
            padding: .3em .75em;
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

        $laborTotal = $totalLaborHours; // hours only, no rate

        // Overhead = usage cost from stock
        $overheadIDR = $usageCostIDR ?? 0;
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
            <div class="p-3">
                <div class="d-flex gap-3 align-items-start flex-wrap">

                    {{-- Project image --}}
                    @php
                        $rawHeroImg = $project->img ?? '';
                        $heroImgUrl = null;
                        if (!empty($rawHeroImg)) {
                            $firstHeroImg = trim(explode(',', $rawHeroImg)[0]);
                            $heroImgUrl = str_starts_with($firstHeroImg, 'http')
                                ? $firstHeroImg
                                : asset('storage/' . $firstHeroImg);
                        }
                    @endphp
                    <div class="hero-img-wrap">
                        @if ($heroImgUrl)
                            <img src="{{ $heroImgUrl }}" alt="{{ $project->name }}">
                        @else
                            {{ $initials }}
                        @endif
                    </div>

                    {{-- Header info --}}
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                            <div>
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
                                        <span class="dept-badge {{ $bc }}">{{ $di }}
                                            {{ ucfirst($dn) }}</span>
                                    @endforeach
                                </div>
                                <h4 class="mb-1 fw-bold" style="font-size:1.25rem;">{{ $project->name }}</h4>
                                <div class="text-muted" style="font-size:.78rem;">
                                    <i class="fas fa-user me-1"></i>{{ $salesName }}
                                    <span class="mx-2">·</span>
                                    <i class="far fa-calendar me-1"></i>Deadline: {{ $deadline }}
                                    <span class="mx-2">·</span>
                                    <i class="fas fa-tasks me-1"></i>{{ $project->jobOrders->count() }} Job Orders
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted" style="font-size:.7rem;">Profit Margin</div>
                                @php
                                    $profitMarginPct = $grandTotal > 0 ? 0 : 0;
                                    // If we had selling price: ($sellingPrice - $grandTotal) / $sellingPrice * 100
                                @endphp
                                <div class="fw-bold" style="font-size:1.4rem; color:#8F12FE;">—</div>
                                <div class="text-muted" style="font-size:.7rem;">No selling price set</div>
                            </div>
                        </div>

                        {{-- ── ACTUALS + ESTIMATES side by side ── --}}
                        <div class="row g-3 mt-1">
                            {{-- ACTUALS --}}
                            <div class="col-lg-5 col-md-6 panel-section">
                                <div class="ps-label">Actuals</div>
                                <div class="panel-row">
                                    <span class="pr-label">Actual Project Cost</span>
                                    <span class="pr-val">{{ $fmt($grandTotal) }}</span>
                                </div>
                                <div class="panel-row">
                                    <span class="pr-label">Total Project Time</span>
                                    <span class="pr-val">{{ $totalLaborHours }} hrs</span>
                                </div>
                                <div class="panel-row">
                                    <span class="pr-label">Material Cost</span>
                                    <span class="pr-val">{{ $fmt($totalMaterialIDR) }}</span>
                                </div>
                                <div class="panel-row">
                                    <span class="pr-label">Freight Cost</span>
                                    <span class="pr-val">{{ $fmt($totalFreightIDR) }}</span>
                                </div>
                            </div>

                            {{-- Divider --}}
                            <div class="col-auto d-none d-lg-flex">
                                <div style="width:1px;background:var(--bs-border-color);"></div>
                            </div>

                            {{-- ESTIMATES / QUOTES ──  from DcmCosting PO data --}}
                            <div class="col-lg-5 col-md-6 panel-section">
                                <div class="ps-label">Purchase Orders</div>
                                <div class="panel-row">
                                    <span class="pr-label">INT'L PO Total</span>
                                    <span class="pr-val">{{ $fmt($totalIntlPo) }}</span>
                                </div>
                                <div class="panel-row">
                                    <span class="pr-label">LOCAL PO Total</span>
                                    <span class="pr-val">{{ $fmt($totalLocalPo) }}</span>
                                </div>
                                <div class="panel-row">
                                    <span class="pr-label">PO Grand Total</span>
                                    <span class="pr-val text-primary">{{ $fmt($totalPoIDR) }}</span>
                                </div>
                                <div class="panel-row">
                                    <span class="pr-label">Usage from Stock</span>
                                    <span class="pr-val">{{ $fmt($usageCostIDR) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- ── PO badge row ── --}}
                        <div class="d-flex gap-2 mt-3 flex-wrap">
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
                            <div class="ms-auto d-flex gap-2 align-items-center">
                                <a href="{{ route('costing.export', $project->id) }}"
                                    class="btn btn-sm btn-outline-success">
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

            {{-- Lark album label --}}
            <div class="px-3 pb-2 d-flex align-items-center gap-1"
                style="font-size:.7rem; color:var(--bs-secondary-color, #6c757d);">
                <span style="width:8px;height:8px;border-radius:50%;background:#28a745;display:inline-block;"></span>
                Lark · {{ ucfirst($firstDept ?: 'Project') }} Album
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
                                <i class="fas fa-external-link-alt ms-1" style="font-size:.65rem; color:#8F12FE;"></i>
                            </a>
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
                                <i class="fas fa-external-link-alt ms-1" style="font-size:.65rem; color:#8F12FE;"></i>
                            </a>
                            <span class="text-muted fw-normal" style="font-size:.7rem;">(Timing Module)</span>
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
                                <i class="fas fa-external-link-alt ms-1" style="font-size:.65rem; color:#8F12FE;"></i>
                            </a>
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
                                                    <td>{{ $item }}</td>
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
                                                    <td>{{ $item }}</td>
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
