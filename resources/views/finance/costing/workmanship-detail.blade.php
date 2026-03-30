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
            background: rgba(108, 92, 231, .1);
            color: #6c5ce7;
        }

        /* ── OT badge ── */
        .ot-badge {
            font-size: .62rem;
            font-weight: 700;
            padding: .18em .55em;
            border-radius: 6px;
            background: rgba(255, 159, 67, .15);
            color: #e67e22;
            border: 1px solid rgba(230, 126, 34, .3);
            vertical-align: middle;
            margin-left: .3rem;
        }

        .ot-cost-chip {
            font-size: .68rem;
            color: #e67e22;
            font-weight: 600;
        }

        /* ── Stat cards row ── */
        .stat-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .75rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: var(--bs-card-bg, #fff);
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
            padding: .9rem 1.1rem;
            transition: transform .15s, box-shadow .15s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(108, 92, 231, .1);
        }

        .stat-card .stat-label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6c757d;
            margin-bottom: .2rem;
        }

        .stat-card .stat-val {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--bs-body-color);
            line-height: 1;
        }

        .stat-card .stat-sub {
            font-size: .72rem;
            color: #adb5bd;
            margin-top: .15rem;
        }

        /* ── Two-column layout ── */
        .main-col {
            flex: 1 1 0;
            min-width: 0;
        }

        .side-col {
            width: 280px;
            flex-shrink: 0;
        }

        @media (max-width: 900px) {
            .side-col {
                width: 100%;
            }

            .stat-row {
                grid-template-columns: repeat(2, 1fr);
            }
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

        /* ── Timing table ── */
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

        .det-tbl .total-row td {
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

        /* Employee avatar */
        .emp-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .68rem;
            font-weight: 700;
            color: #fff;
            margin-right: .4rem;
            flex-shrink: 0;
            background: linear-gradient(135deg, #6c5ce7, #8F12FE);
        }

        .emp-name-cell {
            display: flex;
            align-items: center;
        }

        /* Rate cell */
        .rate-chip {
            font-size: .7rem;
            color: #6c5ce7;
            font-weight: 600;
        }

        /* ── Per-employee side card ── */
        .emp-card {
            background: var(--bs-card-bg, #fff);
            border-radius: 14px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
            padding: .85rem 1rem;
            margin-bottom: .65rem;
            transition: transform .15s;
        }

        .emp-card:hover {
            transform: translateY(-1px);
        }

        .emp-card .ec-name {
            font-size: .82rem;
            font-weight: 700;
        }

        .emp-card .ec-pos {
            font-size: .7rem;
            color: #6c757d;
        }

        .emp-card .ec-row {
            display: flex;
            justify-content: space-between;
            font-size: .78rem;
            padding: .18rem 0;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .emp-card .ec-row:last-child {
            border-bottom: none;
        }

        .emp-card .ec-total {
            font-size: .9rem;
            font-weight: 800;
            color: #6c5ce7;
        }

        /* ── Hours summary card ── */
        .hours-summary {
            background: var(--bs-card-bg, #fff);
            border-radius: 14px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
            padding: .85rem 1rem;
            margin-bottom: .65rem;
        }

        .hours-summary .hs-row {
            display: flex;
            justify-content: space-between;
            font-size: .8rem;
            padding: .2rem 0;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .hours-summary .hs-row:last-child {
            border-bottom: none;
        }

        .hours-summary .hs-total {
            font-weight: 700;
            font-size: .88rem;
            margin-top: .3rem;
        }

        /* ── Work sessions timeline ── */
        .sessions-card {
            background: var(--bs-card-bg, #fff);
            border-radius: 14px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
            padding: .85rem 1rem;
        }

        .session-item {
            display: flex;
            gap: .6rem;
            align-items: flex-start;
            padding: .45rem 0;
            border-bottom: 1px dotted var(--bs-border-color);
        }

        .session-item:last-child {
            border-bottom: none;
        }

        .session-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c5ce7, #8F12FE);
            margin-top: .35rem;
            flex-shrink: 0;
        }

        .session-meta {
            font-size: .73rem;
            color: #6c757d;
        }

        .session-emp {
            font-size: .77rem;
            font-weight: 600;
            color: var(--bs-body-color);
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
            gap: 1.5rem;
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

        /* ── Side section label ── */
        .side-label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6c757d;
            margin-bottom: .5rem;
        }
    </style>
@endsection

@section('content')
    @php
        $fmt     = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $avgRate = $totalOperators > 0 ? round($avgHourlyRate) : 0;
        $hasAnyOt = $timingRows->contains('has_ot', true);
    @endphp

    <div class="container-fluid px-4 py-3">

        {{-- ── Page header ── --}}
        <div class="detail-header">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('costing.detail', $project->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
                <div>
                    <div class="dh-title"><i class="fas fa-clock me-2 text-primary"></i>Workmanship Cost Detail</div>
                    <div class="dh-sub">
                        {{ \Illuminate\Support\Str::limit($project->name, 60) }}
                        <span class="badge-pill badge-linked ms-2"><i class="fas fa-link me-1"></i>sourced from Timing
                            Module</span>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <div style="font-size:.68rem; color:#6c757d; text-transform:uppercase; letter-spacing:.07em;">Total
                    Workmanship Cost</div>
                <div style="font-size:1.2rem; font-weight:800; color:#6c5ce7;">{{ $fmt($totalLaborCost) }}</div>
            </div>
        </div>

        {{-- ── Stat row ── --}}
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-label">Total Operators</div>
                <div class="stat-val">{{ $totalOperators }}</div>
                <div class="stat-sub">
                    @foreach ($byEmployee->take(3) as $e)
                        {{ $e['name'] }}{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Hours Worked</div>
                <div class="stat-val">{{ $totalLaborHours }}</div>
                <div class="stat-sub">Latest: {{ $latestDateFmt }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Regular Cost</div>
                <div class="stat-val" style="font-size:1.3rem;">{{ $fmt($totalNormalCost) }}</div>
                <div class="stat-sub">Base rate · avg {{ $fmt($avgRate) }}/hr</div>
            </div>
            <div class="stat-card" style="{{ $totalOtCost > 0 ? 'border-left:3px solid #e67e22;' : '' }}">
                <div class="stat-label" style="{{ $totalOtCost > 0 ? 'color:#e67e22;' : '' }}">OT Cost</div>
                <div class="stat-val" style="font-size:1.3rem; {{ $totalOtCost > 0 ? 'color:#e67e22;' : 'color:#adb5bd;' }}">
                    {{ $totalOtCost > 0 ? $fmt($totalOtCost) : '—' }}
                </div>
                <div class="stat-sub">{{ $totalOtCost > 0 ? 'Included in total cost' : 'No OT sessions' }}</div>
            </div>
        </div>

        {{-- ── Main 2-column layout ── --}}
        <div class="d-flex gap-3 align-items-flex-start flex-wrap">

            {{-- LEFT: Timing Log table ── --}}
            <div class="main-col">
                <div class="section-block">
                    <div class="sb-header">
                        <div class="sb-title">
                            <span style="background:rgba(108,92,231,.1);padding:.3em .5em;border-radius:8px;">⏱️</span>
                            Timing Log
                            <span class="sb-meta">From Timing Module</span>
                        </div>
                        <div style="font-size:.82rem; font-weight:700; color:#6c5ce7;">{{ $fmt($totalLaborCost) }}</div>
                    </div>

                    <table class="det-tbl">
                        <thead>
                            <tr>
                                <th style="padding-left:1.25rem;">Employee</th>
                                <th>Start</th>
                                <th>End</th>
                                <th class="text-end">Reg hrs</th>
                                @if ($hasAnyOt)
                                    <th class="text-end" style="color:#e67e22;">OT hrs</th>
                                @endif
                                <th class="text-end">Rate / hr</th>
                                <th class="text-end">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($timingRows as $row)
                                @php
                                    $emp  = $byEmployee->firstWhere('name', $row['employee']);
                                    $rate = $emp['hourly_rate'] ?? 0;
                                @endphp
                                <tr>
                                    <td style="padding-left:1.25rem;">
                                        <div class="emp-name-cell">
                                            <span class="emp-avatar">{{ $row['initials'] }}</span>
                                            <div>
                                                <div class="fw-semibold" style="font-size:.8rem;">
                                                    {{ $row['employee'] }}
                                                    @if ($row['has_ot'])
                                                        <span class="ot-badge">OT</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size:.68rem;">{{ $row['position'] }} · {{ $row['date'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted fw-semibold" style="font-size:.82rem;">{{ $row['start_time'] }}</td>
                                    <td class="text-muted fw-semibold" style="font-size:.82rem;">{{ $row['end_time'] }}</td>
                                    <td class="text-end fw-bold" style="color:#6c5ce7;">
                                        {{ $row['normal_hours'] }}
                                        @if ($row['normal_cost'] > 0)
                                            <div style="font-size:.65rem; color:#6c757d; font-weight:400;">{{ $fmt($row['normal_cost']) }}</div>
                                        @endif
                                    </td>
                                    @if ($hasAnyOt)
                                        <td class="text-end">
                                            @if ($row['ot_hours'] > 0)
                                                <span class="fw-bold" style="color:#e67e22;">{{ $row['ot_hours'] }}</span>
                                                <div class="ot-cost-chip">{{ $fmt($row['ot_cost']) }}</div>
                                                @if ($row['ot_code'])
                                                    <div style="font-size:.62rem; color:#adb5bd;">{{ $row['ot_code'] }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-end">
                                        <span class="rate-chip">{{ $rate > 0 ? $fmt($rate) : '—' }}</span>
                                        @if ($row['has_ot'] && $row['ot_rate'] > 0)
                                            <div class="ot-cost-chip">OT {{ $fmt($row['ot_rate']) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold" style="font-size:.82rem;">
                                        {{ $row['total_cost'] > 0 ? $fmt($row['total_cost']) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $hasAnyOt ? 7 : 6 }}" class="text-center text-muted py-4">
                                        <i class="fas fa-clock me-1"></i>No approved timing data
                                    </td>
                                </tr>
                            @endforelse

                            @if ($timingRows->isNotEmpty())
                                <tr class="total-row">
                                    <td colspan="3" style="padding-left:1.25rem;">Total — {{ $totalOperators }} operators</td>
                                    <td class="text-end">{{ $byEmployee->sum('normal_hours') }} hrs</td>
                                    @if ($hasAnyOt)
                                        <td class="text-end" style="color:#e67e22;">{{ $byEmployee->sum('ot_hours') }} hrs</td>
                                    @endif
                                    <td class="text-end" style="font-size:.8rem; color:#6c757d;">avg {{ $fmt($avgRate) }}</td>
                                    <td class="text-end">{{ $fmt($totalLaborCost) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    {{-- Footer inside card --}}
                    <div class="d-flex justify-content-between align-items-center px-4 py-2"
                        style="font-size:.82rem; border-top:1px solid var(--bs-border-color); background:rgba(108,92,231,.04);">
                        <span class="fw-bold">Total Workmanship Cost</span>
                        <span style="font-weight:800; color:#6c5ce7;">{{ $fmt($totalLaborCost) }}</span>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Per-employee + summary ── --}}
            <div class="side-col">

                {{-- Per-employee cards --}}
                <div class="side-label">Per-Employee Breakdown</div>
                @foreach ($byEmployee as $emp)
                    <div class="emp-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="emp-avatar"
                                style="width:30px;height:30px;font-size:.75rem;">{{ $emp['initials'] }}</span>
                            <div>
                                <div class="ec-name">{{ $emp['name'] }}</div>
                                <div class="ec-pos">{{ $emp['position'] }} · {{ $emp['sessions'] }} sessions</div>
                            </div>
                            <div class="ms-auto ec-total">{{ $fmt($emp['labor_cost']) }}</div>
                        </div>
                        <div class="ec-row">
                            <span class="text-muted">Reg Hours</span>
                            <span class="fw-semibold">{{ $emp['normal_hours'] }} hrs</span>
                        </div>
                        @if ($emp['ot_hours'] > 0)
                            <div class="ec-row">
                                <span style="color:#e67e22;">OT Hours</span>
                                <span class="fw-semibold" style="color:#e67e22;">{{ $emp['ot_hours'] }} hrs</span>
                            </div>
                        @endif
                        <div class="ec-row">
                            <span class="text-muted">Reg Cost</span>
                            <span>{{ $emp['hourly_rate'] > 0 ? $fmt($emp['normal_cost']) : '—' }}</span>
                        </div>
                        @if ($emp['ot_cost'] > 0)
                            <div class="ec-row">
                                <span style="color:#e67e22;">OT Cost</span>
                                <span style="color:#e67e22; font-weight:600;">{{ $fmt($emp['ot_cost']) }}</span>
                            </div>
                        @endif
                        <div class="ec-row">
                            <span class="text-muted">Rate/hr</span>
                            <span>{{ $emp['hourly_rate'] > 0 ? $fmt($emp['hourly_rate']) : '—' }}</span>
                        </div>
                    </div>
                @endforeach

                {{-- Total card --}}
                <div class="emp-card" style="border:1.5px solid rgba(108,92,231,.25);background:rgba(108,92,231,.05);">
                    <div class="ec-row">
                        <span class="text-muted fw-semibold">Total</span>
                        <span style="font-weight:800; color:#6c5ce7;">{{ $fmt($totalLaborCost) }}</span>
                    </div>
                </div>

                {{-- Hours Summary --}}
                <div class="side-label" style="margin-top:.85rem;">Hours Summary</div>
                <div class="hours-summary">
                    @foreach ($byEmployee as $emp)
                        <div class="hs-row">
                            <span class="text-muted">{{ $emp['name'] }}</span>
                            <span>{{ $emp['hours'] }} hrs</span>
                        </div>
                    @endforeach
                    <div class="hs-row hs-total">
                        <span>Total Hours</span>
                        <span>{{ $totalLaborHours }} hrs</span>
                    </div>
                    <div class="hs-row">
                        <span class="text-muted">Avg Rate</span>
                        <span>{{ $fmt($avgRate) }}/hr</span>
                    </div>
                </div>

                {{-- Work Sessions timeline --}}
                @if ($workSessions->isNotEmpty())
                    <div class="side-label" style="margin-top:.85rem;">Work Session</div>
                    <div class="sessions-card">
                        @foreach ($workSessions as $ws)
                            <div class="session-item">
                                <div class="session-dot"></div>
                                <div>
                                    <div class="session-emp">{{ implode(' — ', $ws['employees']) }}</div>
                                    <div class="session-meta">{{ $ws['date'] }} · {{ $ws['hours'] }} hrs</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>

        {{-- ── Footer total bar ── --}}
        <div class="total-bar">
            <div>
                <div class="tb-label">Total Workmanship Cost — All Operators</div>
                <div class="tb-val">{{ $fmt($totalLaborCost) }}</div>
                @if ($totalOtCost > 0)
                    <div style="font-size:.72rem; color:#f39c12; margin-top:.2rem;">
                        Regular {{ $fmt($totalNormalCost) }} + OT {{ $fmt($totalOtCost) }}
                    </div>
                @endif
            </div>
            <div class="tb-breakdown">
                @foreach ($byEmployee as $emp)
                    <div class="tbi">
                        <div class="tbi-label">{{ strtoupper($emp['name']) }}</div>
                        <div class="tbi-val">{{ $fmt($emp['labor_cost']) }}</div>
                        <div style="font-size:.63rem; color:#a0a0c0;">
                            {{ $emp['hours'] }} hrs
                            @if ($emp['ot_hours'] > 0)
                                · <span style="color:#f39c12;">{{ $emp['ot_hours'] }}h OT</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
@endsection
