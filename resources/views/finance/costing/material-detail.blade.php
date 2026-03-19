@extends('layouts.app')

@section('styles')
    <style>
        body {
            background: var(--bs-body-bg);
        }

        /* ── Page Header ── */
        .detail-header {
            background: var(--bs-card-bg, var(--bs-body-bg));
            border-bottom: 1px solid var(--bs-border-color);
            padding: .85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            margin-bottom: 1.25rem;
            border-radius: 0 0 12px 12px;
        }

        .detail-header .dh-left {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .detail-header .dh-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--bs-body-color);
        }

        .detail-header .dh-sub {
            font-size: .75rem;
            color: #6c757d;
        }

        .badge-pill {
            font-size: .68rem;
            font-weight: 600;
            padding: .28em .75em;
            border-radius: 20px;
        }

        .badge-linked {
            background: #ede8ff;
            color: #4A25AA;
        }

        /* ── Summary bar (3 cards) ── */
        .summary-bar {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .75rem;
            margin-bottom: 1.25rem;
        }

        .summary-card {
            background: var(--bs-card-bg, var(--bs-body-bg));
            border-radius: 12px;
            border: 1px solid var(--bs-border-color);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .summary-card .sc-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .summary-card.intl .sc-icon {
            background: #fff8e1;
        }

        .summary-card.local .sc-icon {
            background: #e0f7fa;
        }

        .summary-card.usage .sc-icon {
            background: #e8f5e9;
        }

        .summary-card .sc-label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6c757d;
        }

        .summary-card .sc-val {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--bs-body-color);
            line-height: 1.1;
        }

        .summary-card .sc-sub {
            font-size: .7rem;
            color: #adb5bd;
        }

        .progress-bar-line {
            height: 4px;
            border-radius: 2px;
            margin-top: .4rem;
        }

        .summary-card.intl .progress-bar-line {
            background: linear-gradient(90deg, #f4a400, #ffcc80);
        }

        .summary-card.local .progress-bar-line {
            background: linear-gradient(90deg, #17a2b8, #80deea);
        }

        .summary-card.usage .progress-bar-line {
            background: linear-gradient(90deg, #28a745, #a5d6a7);
        }

        /* ── Section block ── */
        .section-block {
            background: var(--bs-card-bg, var(--bs-body-bg));
            border-radius: 14px;
            border: 1px solid var(--bs-border-color);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .section-block .sb-header {
            padding: .8rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .section-block .sb-title {
            font-size: .83rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .45rem;
        }

        .section-block .sb-meta {
            font-size: .72rem;
            color: #6c757d;
        }

        .section-block .sb-total {
            font-size: .9rem;
            font-weight: 700;
            color: #4A25AA;
        }

        /* Exchange rate note */
        .xrate-note {
            background: #fffbf0;
            border-bottom: 1px solid #ffeeba;
            padding: .4rem 1.25rem;
            font-size: .72rem;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .xrate-chip {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: .18em .65em;
            border-radius: 6px;
            font-weight: 600;
            font-size: .7rem;
        }

        /* ── Data table ── */
        .det-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: .8rem;
        }

        .det-tbl thead th {
            font-size: .68rem;
            color: var(--bs-secondary-color, #adb5bd);
            font-weight: 600;
            padding: .45rem .75rem;
            border-bottom: 2px solid var(--bs-border-color);
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .det-tbl tbody td {
            padding: .45rem .75rem;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .det-tbl tbody tr:last-child td {
            border-bottom: none;
        }

        .det-tbl tbody tr:hover {
            background: rgba(143, 18, 254, 0.05);
        }

        .det-tbl .subtotal-row td {
            background: rgba(143, 18, 254, 0.07);
            font-weight: 700;
            border-top: 2px solid var(--bs-border-color);
            font-size: .82rem;
        }

        .det-tbl .text-end {
            text-align: right;
        }

        .det-tbl .text-muted {
            color: #6c757d !important;
        }

        /* Price chips */
        .price-orig {
            color: #e65100;
            font-weight: 600;
        }

        .rate-arrow {
            color: #adb5bd;
            font-size: .7rem;
            margin: 0 .25rem;
        }

        /* Stock badge */
        .stock-badge {
            font-size: .67rem;
            font-weight: 600;
            padding: .2em .6em;
            border-radius: 6px;
            background: #e8f5e9;
            color: #155724;
            white-space: nowrap;
        }

        .stock-badge.sg {
            background: #fff3cd;
            color: #7c5a00;
        }

        /* Source badge */
        .src-badge {
            font-size: .67rem;
            color: #6c757d;
        }

        /* ── Footer total bar ── */
        .total-bar {
            background: linear-gradient(135deg, #1a1433 0%, #2d1b69 100%);
            border-radius: 14px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .total-bar .tb-label {
            color: #b0a8cc;
            font-size: .75rem;
        }

        .total-bar .tb-val {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
        }

        .total-bar .tb-breakdown {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .total-bar .tbi {
            text-align: center;
        }

        .total-bar .tbi .tbi-label {
            font-size: .63rem;
            color: #b0a8cc;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .total-bar .tbi .tbi-val {
            font-size: .88rem;
            font-weight: 700;
            color: #fff;
        }

        @media (max-width: 768px) {
            .summary-bar {
                grid-template-columns: 1fr;
            }

            .total-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $totalAll = $totalMaterialIDR;
        $pctIntl = $totalAll > 0 ? round(($totalIntlIDR / $totalAll) * 100, 1) : 0;
        $pctLocal = $totalAll > 0 ? round(($totalLocalIDR / $totalAll) * 100, 1) : 0;
        $pctUsage = $totalAll > 0 ? round((($totalAll - $totalIntlIDR - $totalLocalIDR) / $totalAll) * 100, 1) : 0;
        // usage = all materials from stock = same as $usageMaterials total
        $totalUsageIDR = $usageMaterials->sum('total_idr');
        $pctUsage = $totalAll > 0 ? round(($totalUsageIDR / $totalAll) * 100, 1) : 0;
    @endphp

    <div class="container-fluid px-4 py-3">

        {{-- ── Page header ── --}}
        <div class="detail-header">
            <div class="dh-left">
                <a href="{{ route('costing.detail', $project->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
                <div>
                    <div class="dh-title"><i class="fas fa-boxes me-2 text-warning"></i>Material Cost Detail</div>
                    <div class="dh-sub">
                        {{ \Illuminate\Support\Str::limit($project->name, 60) }}
                        <span class="badge-pill badge-linked ms-2"><i class="fas fa-link me-1"></i>Linked to Procurement
                            Module</span>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <div style="font-size:.68rem; color:#6c757d; text-transform:uppercase; letter-spacing:.07em;">Total Material
                    Cost</div>
                <div style="font-size:1.2rem; font-weight:800; color:#4A25AA;">{{ $fmt($totalMaterialIDR) }}</div>
            </div>
        </div>

        {{-- ── Summary 3-card bar ── --}}
        <div class="summary-bar">
            {{-- INT'L Purchasing --}}
            <div class="summary-card intl">
                <div class="sc-icon">🌐</div>
                <div class="flex-grow-1">
                    <div class="sc-label">International Purchasing</div>
                    <div class="sc-val">{{ $fmt($totalIntlIDR) }}</div>
                    <div class="sc-sub">Foreign currency items · RMB, SGD, USD → IDR</div>
                    <div class="progress-bar-line" style="width:{{ min($pctIntl, 100) }}%;"></div>
                </div>
                <div style="font-size:1.1rem; font-weight:800; color:#f4a400;">{{ $pctIntl }}%</div>
            </div>
            {{-- Local Purchasing --}}
            <div class="summary-card local">
                <div class="sc-icon">🏠</div>
                <div class="flex-grow-1">
                    <div class="sc-label">Local Purchasing</div>
                    <div class="sc-val">{{ $fmt($totalLocalIDR) }}</div>
                    <div class="sc-sub">IDR items · local vendors in Batam</div>
                    <div class="progress-bar-line" style="width:{{ min($pctLocal, 100) }}%;"></div>
                </div>
                <div style="font-size:1.1rem; font-weight:800; color:#17a2b8;">{{ $pctLocal }}%</div>
            </div>
            {{-- Material Usage --}}
            <div class="summary-card usage">
                <div class="sc-icon">📦</div>
                <div class="flex-grow-1">
                    <div class="sc-label">Material Usage</div>
                    <div class="sc-val">{{ $fmt($totalUsageIDR) }}</div>
                    <div class="sc-sub">Drawn from warehouse stock / inventory</div>
                    <div class="progress-bar-line" style="width:{{ min($pctUsage, 100) }}%;"></div>
                </div>
                <div style="font-size:1.1rem; font-weight:800; color:#28a745;">{{ $pctUsage }}%</div>
            </div>
        </div>

        {{-- ── Section 1: International Purchasing (placeholder — data not yet available) ── --}}
        {{-- This section will be populated from Lark SG→BT shipment data in a future update --}}

        {{-- ── Section 2: Local Purchasing ── --}}
        @if ($localMaterials->isNotEmpty())
            <div class="section-block">
                <div class="sb-header">
                    <div class="sb-title">
                        <span style="background:#e0f7fa;padding:.3em .5em;border-radius:8px;">🏠</span>
                        <span>Local Purchasing</span>
                        <span class="sb-meta">{{ $localMaterials->count() }} items · all prices in IDR · purchased locally
                            in Batam</span>
                    </div>
                    <div class="sb-total">{{ $fmt($totalLocalIDR) }}</div>
                </div>
                <div style="padding:.5rem 0;">
                    <table class="det-tbl">
                        <thead>
                            <tr>
                                <th style="padding-left:1.25rem;">Material</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price (IDR)</th>
                                <th class="text-end">Total Unit Cost</th>
                                <th class="text-end">Vendor</th>
                                <th class="text-end">Total Cost (IDR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($localMaterials as $m)
                                <tr>
                                    <td style="padding-left:1.25rem;">
                                        <div class="fw-semibold">{{ $m['name'] }}</div>
                                    </td>
                                    <td class="text-end text-muted">{{ number_format($m['qty'], 2) }} {{ $m['unit'] }}
                                    </td>
                                    <td class="text-end">{{ number_format($m['unit_price'], 0, ',', '.') }}</td>
                                    <td class="text-end text-muted">{{ number_format($m['total_unit_cost'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end text-muted" style="font-size:.75rem;">—</td>
                                    <td class="text-end fw-semibold">{{ $fmt($m['total_idr']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="subtotal-row">
                                <td colspan="5" style="padding-left:1.25rem;">Subtotal — Local Purchasing</td>
                                <td class="text-end">{{ $fmt($totalLocalIDR) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ── Section 3: Material Usage ── --}}
        @if ($usageMaterials->isNotEmpty())
            <div class="section-block">
                <div class="sb-header">
                    <div class="sb-title">
                        <span style="background:#e8f5e9;padding:.3em .5em;border-radius:8px;">📦</span>
                        <span>Material Usage</span>
                        <span class="sb-meta">Drawn from warehouse inventory · allocated at unit cost</span>
                    </div>
                    <div class="sb-total">{{ $fmt($totalUsageIDR) }}</div>
                </div>
                <div style="padding:.5rem 0;">
                    <table class="det-tbl">
                        <thead>
                            <tr>
                                <th style="padding-left:1.25rem;">Job Order</th>
                                <th>Material</th>
                                <th class="text-end">Qty Used</th>
                                <th class="text-end">Unit Cost (IDR)</th>
                                <th class="text-end">Total Cost</th>
                                <th>Stock Location</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usageMaterials as $m)
                                <tr>
                                    <td style="padding-left:1.25rem;" class="text-muted" style="font-size:.75rem;">
                                        {{ $m['job_order_name'] ?? '—' }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $m['name'] }}</div>
                                    </td>
                                    <td class="text-end text-muted">{{ number_format($m['qty'], 2) }} {{ $m['unit'] }}
                                    </td>
                                    <td class="text-end">{{ number_format($m['unit_price'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($m['total_idr']) }}</td>
                                    <td>
                                        <span class="stock-badge {{ $m['is_intl'] ? 'sg' : '' }}">
                                            {{ $m['stock_location'] }}
                                        </span>
                                    </td>
                                    <td class="src-badge">Inventory</td>
                                </tr>
                            @endforeach
                            <tr class="subtotal-row">
                                <td colspan="6" style="padding-left:1.25rem;">Subtotal — Material Usage</td>
                                <td class="text-end">{{ $fmt($totalUsageIDR) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($usageMaterials->isEmpty() && $localMaterials->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                No material cost data recorded for this project.
            </div>
        @endif

        {{-- ── Footer total bar ── --}}
        <div class="total-bar">
            <div>
                <div class="tb-label">Total Material Cost — All Sources</div>
                <div class="tb-val">{{ $fmt($totalMaterialIDR) }}</div>
            </div>
            <div class="tb-breakdown">
                <div class="tbi">
                    <div class="tbi-label" style="color:#ffcc80;">🌐 INT'L PURCHASING</div>
                    <div class="tbi-val">{{ $fmt($totalIntlIDR) }}</div>
                    <div style="font-size:.65rem;color:#a08060;">{{ $pctIntl }}%</div>
                </div>
                <div class="tbi">
                    <div class="tbi-label" style="color:#80deea;">🏠 LOCAL PURCHASING</div>
                    <div class="tbi-val">{{ $fmt($totalLocalIDR) }}</div>
                    <div style="font-size:.65rem;color:#a0c8cc;">{{ $pctLocal }}%</div>
                </div>
                <div class="tbi">
                    <div class="tbi-label" style="color:#a5d6a7;">📦 MATERIAL USAGE</div>
                    <div class="tbi-val">{{ $fmt($totalUsageIDR) }}</div>
                    <div style="font-size:.65rem;color:#90b090;">{{ $pctUsage }}%</div>
                </div>
            </div>
        </div>

    </div>
@endsection
