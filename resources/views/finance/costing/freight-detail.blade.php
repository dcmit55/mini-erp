@extends('layouts.app')

@section('styles')
    <style>
        body {
            background: #f0f2f9;
        }

        /* ── Page Header ── */
        .detail-header {
            background: var(--bs-card-bg, #fff);
            border: none;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
            padding: .85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            margin-bottom: 1.25rem;
            border-radius: 16px;
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
            background: rgba(255, 193, 7, 0.15);
            color: #b8860b;
        }

        /* ── Direction summary ── */
        .dir-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-bottom: 1.25rem;
        }

        .dir-card {
            background: var(--bs-card-bg, #fff);
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: .85rem;
            transition: transform .15s, box-shadow .15s;
        }

        .dir-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(108, 92, 231, .1);
        }

        .dir-card .dc-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .dir-card .dc-dir {
            font-size: .78rem;
            font-weight: 700;
            color: var(--bs-body-color);
        }

        .dir-card .dc-sub {
            font-size: .7rem;
            color: #6c757d;
        }

        .dir-card .dc-val {
            font-size: 1.25rem;
            font-weight: 800;
            color: #6c5ce7;
            margin-left: auto;
        }

        .dir-card.inbound {
            border-left: 4px solid #17a2b8;
        }

        .dir-card.outbound {
            border-left: 4px solid #fd7e14;
        }

        /* ── Section block ── */
        .section-block {
            background: var(--bs-card-bg, #fff);
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .section-block .sb-header {
            padding: .75rem 1.25rem;
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
            color: #6c5ce7;
        }

        /* ── Shipment group ── */
        .shipment-group {
            border-bottom: 1px solid var(--bs-border-color);
        }

        .shipment-group:last-child {
            border-bottom: none;
        }

        .shipment-header {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .55rem 1.25rem;
            background: rgba(108, 92, 231, 0.05);
            font-size: .78rem;
            font-weight: 600;
            color: #6c5ce7;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .shipment-meta {
            font-size: .7rem;
            color: #6c757d;
            font-weight: 400;
            margin-left: .25rem;
        }

        /* ── Data table ── */
        .det-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: .8rem;
        }

        .det-tbl thead th {
            font-size: .68rem;
            color: #adb5bd;
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
            background: rgba(108, 92, 231, 0.04);
        }

        .det-tbl .subtotal-row td {
            background: rgba(108, 92, 231, 0.06);
            font-weight: 700;
            border-top: 2px solid var(--bs-border-color);
        }

        .det-tbl .text-end {
            text-align: right;
        }

        .det-tbl .text-muted {
            color: #6c757d !important;
        }

        /* Status badges */
        .status-badge {
            font-size: .67rem;
            font-weight: 600;
            padding: .25em .65em;
            border-radius: 20px;
            white-space: nowrap;
        }

        .status-delivered {
            background: rgba(25, 135, 84, 0.15);
            color: #198754;
        }

        .status-in-transit {
            background: rgba(255, 193, 7, 0.15);
            color: #856404;
        }

        .status-pending {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        /* Mode chips */
        .mode-chip {
            font-size: .68rem;
            font-weight: 600;
            padding: .2em .6em;
            border-radius: 6px;
            border: 1px solid var(--bs-border-color);
            color: var(--bs-body-color);
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .mode-chip.ferry {
            background: rgba(25, 118, 210, 0.1);
            border-color: rgba(25, 118, 210, .3);
            color: #1976d2;
        }

        .mode-chip.air {
            background: rgba(198, 40, 40, 0.1);
            border-color: rgba(198, 40, 40, .3);
            color: #c62828;
        }

        .mode-chip.road {
            background: rgba(56, 142, 60, 0.1);
            border-color: rgba(56, 142, 60, .3);
            color: #388e3c;
        }

        /* Courier pill */
        .courier-pill {
            font-size: .7rem;
            font-weight: 600;
            padding: .25em .7em;
            border-radius: 20px;
            background: rgba(108, 92, 231, 0.1);
            color: #6c5ce7;
            border: 1px solid rgba(108, 92, 231, 0.25);
        }

        .courier-pill.bt {
            background: rgba(245, 127, 23, 0.1);
            color: #f57f17;
            border-color: rgba(245, 127, 23, 0.3);
        }

        /* ── Footer total bar ── */
        .total-bar {
            background: linear-gradient(135deg, #1a1433 0%, #2d1b69 50%, #4A25AA 100%);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
            box-shadow: 0 4px 20px rgba(74, 37, 170, .2);
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

        @media (max-width: 640px) {
            .dir-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');

        $modeIcon = function ($mode) {
            $m = strtolower($mode ?? '');
            if (str_contains($m, 'ferry') || str_contains($m, 'sea') || str_contains($m, 'ship')) {
                return ['⛴', 'ferry'];
            }
            if (str_contains($m, 'air') || str_contains($m, 'flight')) {
                return ['✈', 'air'];
            }
            return ['🚚', 'road'];
        };

        $statusClass = function ($status) {
            $s = strtolower($status ?? '');
            if ($s === 'delivered') {
                return 'status-delivered';
            }
            if (str_contains($s, 'transit') || str_contains($s, 'progress')) {
                return 'status-in-transit';
            }
            return 'status-pending';
        };
    @endphp

    <div class="container-fluid px-4 py-3">

        {{-- ── Page header ── --}}
        <div class="detail-header">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('costing.detail', $project->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
                <div>
                    <div class="dh-title"><i class="fas fa-shipping-fast me-2 text-warning"></i>Freight Cost</div>
                    <div class="dh-sub">
                        {{ \Illuminate\Support\Str::limit($project->name, 60) }}
                        <span class="badge-pill badge-linked ms-2"><i class="fas fa-link me-1"></i>Linked to Logistics
                            Module</span>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <div style="font-size:.68rem; color:#6c757d; text-transform:uppercase; letter-spacing:.07em;">
                    Total Freight
                    Cost</div>
                <div style="font-size:1.2rem; font-weight:800; color:#6c5ce7;">{{ $fmt($totalFreightIDR) }}</div>
            </div>
        </div>

        {{-- ── Direction summary 2-card ── --}}
        <div class="dir-summary">
            <div class="dir-card inbound">
                <div class="dc-icon">🇸🇬</div>
                <div>
                    <div class="dc-dir">SG → ID · Singapore → Batam</div>
                    <div class="dc-sub">{{ $sgBtCount }} shipments · inbound: materials, samples, components</div>
                </div>
                <div class="dc-val">{{ $fmt($totalSgBtIDR) }}</div>
            </div>
            <div class="dir-card outbound">
                <div class="dc-icon">🇮🇩</div>
                <div>
                    <div class="dc-dir">ID → SG · Batam → Singapore</div>
                    <div class="dc-sub">{{ $btSgCount }} shipments · outbound: finished goods to client</div>
                </div>
                <div class="dc-val">{{ $fmt($totalBtSgIDR) }}</div>
            </div>
        </div>

        {{-- ── SG → BT Section ── --}}
        @if ($sgBtShipments->isNotEmpty())
            <div class="section-block">
                <div class="sb-header">
                    <div class="sb-title">
                        <span class="courier-pill">SG Singapore</span>
                        <i class="fas fa-arrow-right text-muted mx-1" style="font-size:.7rem;"></i>
                        <span class="courier-pill bt">BT Batam</span>
                        <span class="sb-meta ms-2">{{ $sgBtShipments->count() }} shipment(s) · Inbound shipments: materials,
                            samples, components</span>
                    </div>
                    <div class="sb-total">{{ $fmt($totalSgBtIDR) }}</div>
                </div>

                <table class="det-tbl">
                    <thead>
                        <tr>
                            <th style="padding-left:1.25rem;">Description</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th class="text-end">SGD Cost</th>
                            <th class="text-end">Cost (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sgBtShipments as $ship)
                            @foreach ($ship['items'] as $item)
                                <tr>
                                    <td style="padding-left:1.25rem;">
                                        <div class="fw-semibold">{{ $item['name'] }}</div>
                                    </td>
                                    <td class="text-muted">{{ $item['qty'] ?? 1 }}</td>
                                    <td>
                                        <span class="status-badge {{ $statusClass($item['status']) }}">●
                                            {{ $item['status'] }}</span>
                                    </td>
                                    <td class="text-end text-muted" style="font-size:.75rem;">SGD
                                        {{ number_format($item['sgd_cost'] ?? 0, 2) }}</td>
                                    <td class="text-end">
                                        @if (($item['sgd_cost'] ?? 0) > 0)
                                            {{ $fmt(round($item['sgd_cost'] * $sgdRate, 0)) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        <tr class="subtotal-row">
                            <td colspan="4" style="padding-left:1.25rem;">Subtotal — SG → BT
                                ({{ $sgBtShipments->count() }} group(s))</td>
                            <td class="text-end">{{ $fmt($totalSgBtIDR) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ── BT → SG Section ── --}}
        @if ($btSgShipments->isNotEmpty())
            <div class="section-block">
                <div class="sb-header">
                    <div class="sb-title">
                        <span class="courier-pill bt">BT Batam</span>
                        <i class="fas fa-arrow-right text-muted mx-1" style="font-size:.7rem;"></i>
                        <span class="courier-pill">SG Singapore</span>
                        <span class="sb-meta ms-2">{{ $btSgShipments->count() }} shipment(s) · Outbound: finished goods
                            delivered to client</span>
                    </div>
                    <div class="sb-total">{{ $fmt($totalBtSgIDR) }}</div>
                </div>

                <table class="det-tbl">
                    <thead>
                        <tr>
                            <th style="padding-left:1.25rem;">Description</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th class="text-end">SGD Cost</th>
                            <th class="text-end">Cost (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($btSgShipments as $ship)
                            @foreach ($ship['items'] as $item)
                                <tr>
                                    <td style="padding-left:1.25rem;">
                                        <div class="fw-semibold">{{ $item['name'] }}</div>
                                    </td>
                                    <td class="text-muted">{{ $item['qty'] ?? 1 }}</td>
                                    <td>
                                        <span class="status-badge {{ $statusClass($item['status']) }}">●
                                            {{ $item['status'] }}</span>
                                    </td>
                                    <td class="text-end text-muted" style="font-size:.75rem;">SGD
                                        {{ number_format($item['sgd_cost'] ?? 0, 2) }}</td>
                                    <td class="text-end">
                                        @if (($item['sgd_cost'] ?? 0) > 0)
                                            {{ $fmt(round($item['sgd_cost'] * $sgdRate, 0)) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        <tr class="subtotal-row">
                            <td colspan="4" style="padding-left:1.25rem;">Subtotal — BT → SG
                                ({{ $btSgShipments->count() }} group(s))</td>
                            <td class="text-end">{{ $fmt($totalBtSgIDR) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        @if ($sgBtShipments->isEmpty() && $btSgShipments->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-shipping-fast fa-2x mb-2 d-block"></i>
                No freight / courier data recorded for this project.
            </div>
        @endif

        {{-- ── Footer total bar ── --}}
        <div class="total-bar">
            <div>
                <div class="tb-label">Total Freight Cost — All Directions</div>
                <div class="tb-val">{{ $fmt($totalFreightIDR) }}</div>
            </div>
            <div class="tb-breakdown">
                <div class="tbi">
                    <div class="tbi-label" style="color:rgba(128,222,234,0.9);">🇸🇬→🇮🇩 SG → BT INBOUND</div>
                    <div class="tbi-val">{{ $fmt($totalSgBtIDR) }}</div>
                    <div style="font-size:.63rem; color:rgba(160,200,204,0.8);">{{ $sgBtCount }} shipments</div>
                </div>
                <div class="tbi">
                    <div class="tbi-label" style="color:rgba(255,204,128,0.9);">🇮🇩→🇸🇬 BT → SG OUTBOUND</div>
                    <div class="tbi-val">{{ $fmt($totalBtSgIDR) }}</div>
                    <div style="font-size:.63rem; color:rgba(160,128,96,0.8);">{{ $btSgCount }} shipments</div>
                </div>
            </div>
        </div>

    </div>
@endsection
