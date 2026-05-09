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
        $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $avgRate = $totalOperators > 0 ? round($avgHourlyRate) : 0;
        $hasAnyOt = $timingRows->contains('has_ot', true);
        $regularRows = $timingRows->filter(fn($r) => !$r['has_ot'])->values();
        $allOtRows = $timingRows->filter(fn($r) => $r['has_ot'])->values();
    @endphp

    <div class="container-fluid px-4 py-3" id="wm-content">

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
            {{-- Total Operators card --}}
            <div class="stat-card">
                @if ($selectedJobOrderId)
                    @php
                        $hStat = $joApprovalStats[$selectedJobOrderId] ?? null;
                        $hApp = $hStat['approved_sessions'] ?? 0;
                        $hPend = $hStat['pending_sessions'] ?? 0;
                        $hTot = $hApp + $hPend;
                        $hPct = $hTot > 0 ? round(($hApp / $hTot) * 100) : 0;
                        $hColor = $hPct === 100 ? '#00b894' : ($hPct >= 50 ? '#e67e22' : '#e17055');
                    @endphp
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div style="min-width:0;">
                            <div class="stat-label">Total Operators</div>
                            <div class="stat-val" style="font-size:1.55rem;color:{{ $hColor }};">
                                {{ $hApp }}<span
                                    style="font-size:1rem;font-weight:600;color:#adb5bd;">/{{ $hTot }}</span>
                                <span style="font-size:.85rem;font-weight:700;color:{{ $hColor }};"> Approve</span>
                            </div>
                            @if ($hPend > 0)
                                <div class="stat-sub">
                                    <a href="{{ route('timing-approval.index', ['project_id' => $project->id]) }}"
                                        style="color:#e17055;text-decoration:none;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $hPend }} sessions belum
                                        di-approve
                                    </a>
                                </div>
                            @else
                                <div class="stat-sub" style="color:#00b894;"><i class="fas fa-check-circle me-1"></i>All
                                    sessions approved</div>
                            @endif
                        </div>
                        <div style="position:relative;width:52px;height:52px;flex-shrink:0;">
                            <svg width="52" height="52" viewBox="0 0 44 44" style="transform:rotate(-90deg)">
                                <circle cx="22" cy="22" r="18" fill="none" stroke="#f0f2f9"
                                    stroke-width="4.5" />
                                <circle cx="22" cy="22" r="18" fill="none" stroke="{{ $hColor }}"
                                    stroke-width="4.5" stroke-dasharray="{{ round(2 * 3.14159 * 18, 1) }}"
                                    stroke-dashoffset="{{ round(2 * 3.14159 * 18 * (1 - $hPct / 100), 1) }}"
                                    stroke-linecap="round" />
                            </svg>
                            <span
                                style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:.65rem;font-weight:800;color:{{ $hColor }};">{{ $hPct }}%</span>
                        </div>
                    </div>
                @else
                    <div class="stat-label">Total Operators</div>
                    <div class="stat-val">{{ $totalOperators }}</div>
                    <div class="stat-sub">
                        @foreach ($byEmployee->take(3) as $e)
                            {{ $e['name'] }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Total Hours Worked card --}}
            <div class="stat-card">
                @if ($selectedJobOrderId)
                    @php
                        $hrStat = $joApprovalStats[$selectedJobOrderId] ?? null;
                        $hrApp = round($hrStat['approved_hours'] ?? 0, 2);
                        $hrPend = round($hrStat['pending_hours'] ?? 0, 2);
                        $hrTot = round($hrApp + $hrPend, 2);
                        $hrPct = $hrTot > 0 ? round(($hrApp / $hrTot) * 100) : 0;
                        $hrColor = $hrPct === 100 ? '#00b894' : ($hrPct >= 50 ? '#e67e22' : '#e17055');
                    @endphp
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div style="min-width:0;">
                            <div class="stat-label">Total Hours Worked</div>
                            <div class="stat-val" style="font-size:1.55rem;color:{{ $hrColor }};">
                                {{ $hrApp }}<span
                                    style="font-size:1rem;font-weight:600;color:#adb5bd;">/{{ $hrTot }}</span>
                                <span style="font-size:.85rem;font-weight:700;color:{{ $hrColor }};"> hrs</span>
                            </div>
                            @if ($hrPend > 0)
                                <div class="stat-sub" style="color:#e17055;">{{ $hrPend }} hrs belum di-approve</div>
                            @else
                                <div class="stat-sub" style="color:#00b894;"><i class="fas fa-check-circle me-1"></i>All
                                    hours approved</div>
                            @endif
                        </div>
                        <div style="position:relative;width:52px;height:52px;flex-shrink:0;">
                            <svg width="52" height="52" viewBox="0 0 44 44" style="transform:rotate(-90deg)">
                                <circle cx="22" cy="22" r="18" fill="none" stroke="#f0f2f9"
                                    stroke-width="4.5" />
                                <circle cx="22" cy="22" r="18" fill="none" stroke="{{ $hrColor }}"
                                    stroke-width="4.5" stroke-dasharray="{{ round(2 * 3.14159 * 18, 1) }}"
                                    stroke-dashoffset="{{ round(2 * 3.14159 * 18 * (1 - $hrPct / 100), 1) }}"
                                    stroke-linecap="round" />
                            </svg>
                            <span
                                style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:.65rem;font-weight:800;color:{{ $hrColor }};">{{ $hrPct }}%</span>
                        </div>
                    </div>
                @else
                    <div class="stat-label">Total Hours Worked</div>
                    <div class="stat-val">{{ $totalLaborHours }}</div>
                    <div class="stat-sub">Latest: {{ $latestDateFmt }}</div>
                @endif
            </div>
            <div class="stat-card">
                <div class="stat-label">Regular Cost</div>
                <div class="stat-val" style="font-size:1.3rem;">{{ $fmt($totalNormalCost) }}</div>
                <div class="stat-sub">Base rate · avg {{ $fmt($avgRate) }}/hr</div>
            </div>
            <div class="stat-card" style="{{ $totalOtCost > 0 ? 'border-left:3px solid #e67e22;' : '' }}">
                <div class="stat-label" style="{{ $totalOtCost > 0 ? 'color:#e67e22;' : '' }}">OT Cost</div>
                <div class="stat-val"
                    style="font-size:1.3rem; {{ $totalOtCost > 0 ? 'color:#e67e22;' : 'color:#adb5bd;' }}">
                    {{ $totalOtCost > 0 ? $fmt($totalOtCost) : '—' }}
                </div>
                @if ($totalWdOtCost > 0 || $totalWeOtCost > 0)
                    <div class="stat-sub" style="line-height:1.6;">
                        @if ($totalWdOtCost > 0)
                            <span style="color:#e67e22;">WD {{ $fmt($totalWdOtCost) }}</span>
                            ({{ $totalWdOtHours }}h)<br>
                        @endif
                        @if ($totalWeOtCost > 0)
                            <span style="color:#c0392b;">WE {{ $fmt($totalWeOtCost) }}</span>
                            ({{ $totalWeOtHours }}h)
                        @endif
                    </div>
                @else
                    <div class="stat-sub">No OT sessions</div>
                @endif
            </div>
        </div>

        {{-- ── Filter bar ── BELOW stat cards --}}
        <div class="mb-3 p-3" style="background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span
                    style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;white-space:nowrap;">
                    <i class="fas fa-filter me-1" style="color:#6c5ce7;"></i>Filter by Job Order
                </span>
                <div style="min-width:220px;max-width:380px;flex:1 1 220px;">
                    <select name="job_order_id" id="jo-filter-select" class="form-select form-select-sm"
                        style="width:100%;">
                        <option value="">— All Job Orders —</option>
                        @foreach ($projectJobOrders as $jo)
                            @php
                                $jStat = $joApprovalStats[$jo->id] ?? null;
                                $jApp = $jStat['approved_sessions'] ?? 0;
                                $jPend = $jStat['pending_sessions'] ?? 0;
                                $jTot = $jApp + $jPend;
                            @endphp
                            <option value="{{ $jo->id }}" {{ $selectedJobOrderId == $jo->id ? 'selected' : '' }}>
                                {{ $jo->name }}@if ($jTot > 0)
                                    {{-- — {{ $jApp }}/{{ $jTot }}{{ $jPend === 0 ? ' ✓' : '' }} --}}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="jo-clear-btn"
                    class="btn btn-sm btn-outline-secondary {{ $selectedJobOrderId ? '' : 'd-none' }}"
                    style="font-size:.75rem;white-space:nowrap;">
                    <i class="fas fa-times me-1"></i>Clear Filter
                </button>
                {{-- spinner shown during AJAX load --}}
                <span id="wm-loading" class="d-none" style="font-size:.8rem;color:#6c5ce7;">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Loading…
                </span>
            </div>
        </div>

        {{-- ── Main 2-column layout ── --}}
        <div class="d-flex gap-3 align-items-flex-start flex-wrap">

            {{-- LEFT: Timing Log table ── --}}
            <div class="main-col">
                <div class="section-block">
                    <div class="sb-header">
                        <div class="sb-title">
                            <span style="background:rgba(108,92,231,.1);padding:.3em .5em;border-radius:8px;">🕐</span>
                            Regular Timing Log
                            <span class="sb-meta">From Timing Module</span>
                        </div>
                        <div style="font-size:.82rem; font-weight:700; color:#6c5ce7;">{{ $fmt($totalNormalCost) }}</div>
                    </div>

                    <table class="det-tbl">
                        <thead>
                            <tr>
                                <th style="padding-left:1.25rem;">Employee</th>
                                <th>Job Order</th>
                                <th>Day</th>
                                <th>Date</th>
                                <th>Start</th>
                                <th>End</th>
                                <th class="text-end">Reg Hrs</th>
                                <th class="text-end">Rate / hr</th>
                                <th class="text-end">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($regularRows as $row)
                                @php
                                    $emp = $byEmployee->firstWhere('name', $row['employee']);
                                    $rate = $emp['hourly_rate'] ?? 0;
                                @endphp
                                <tr>
                                    <td style="padding-left:1.25rem;">
                                        <div class="emp-name-cell">
                                            <span class="emp-avatar">{{ $row['initials'] }}</span>
                                            <div>
                                                <div class="fw-semibold" style="font-size:.8rem;">{{ $row['employee'] }}
                                                </div>
                                                <div class="text-muted" style="font-size:.68rem;">{{ $row['position'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size:.78rem; max-width:130px;">
                                        <span class="badge bg-light text-dark border"
                                            style="font-size:.7rem; font-weight:600;">{{ $row['job_order'] }}</span>
                                    </td>
                                    <td class="text-muted fw-semibold" style="font-size:.82rem;">{{ $row['day_name'] }}
                                    </td>
                                    <td class="text-muted" style="font-size:.8rem;">{{ $row['date'] }}</td>
                                    <td class="text-muted fw-semibold" style="font-size:.82rem;">{{ $row['start_time'] }}
                                    </td>
                                    <td class="text-muted fw-semibold" style="font-size:.82rem;">{{ $row['end_time'] }}
                                    </td>
                                    <td class="text-end fw-bold" style="color:#6c5ce7;">{{ $row['normal_hours'] }} hrs
                                    </td>
                                    <td class="text-end">
                                        <span class="rate-chip">{{ $rate > 0 ? $fmt($rate) : '—' }}</span>
                                    </td>
                                    <td class="text-end fw-bold" style="font-size:.82rem;">
                                        {{ $row['normal_cost'] > 0 ? $fmt($row['normal_cost']) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-clock me-1"></i>No regular timing data
                                    </td>
                                </tr>
                            @endforelse

                            @if ($regularRows->isNotEmpty())
                                <tr class="total-row">
                                    <td colspan="6" style="padding-left:1.25rem;">Total Regular Hours</td>
                                    <td class="text-end">{{ $byEmployee->sum('normal_hours') }} hrs</td>
                                    <td class="text-end" style="font-size:.8rem; color:#6c757d;">avg {{ $fmt($avgRate) }}
                                    </td>
                                    <td class="text-end">{{ $fmt($totalNormalCost) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- ── Weekday OT Log ── --}}
                <div class="section-block">
                    <div class="sb-header">
                        <div class="sb-title">
                            <span style="background:rgba(230,126,34,.12);padding:.3em .5em;border-radius:8px;">🌙</span>
                            Weekday OT Log
                            <span class="sb-meta">Mon – Fri</span>
                        </div>
                        <div style="font-size:.82rem; font-weight:700; color:#e67e22;">{{ $fmt($totalWdOtCost) }}</div>
                    </div>

                    {{-- Info box --}}
                    <div
                        style="margin:.75rem 1.25rem .5rem; padding:.6rem .9rem; background:rgba(52,152,219,.07); border-left:3px solid #3498db; border-radius:6px; font-size:.75rem; color:#2980b9;">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Aturan OT Hari Kerja (UU Ketenagakerjaan):</strong>
                        Jam OT ke-1 s/d 3 = 1.5× rate reguler &nbsp;·&nbsp; Jam OT ke-4 dan seterusnya = 2× rate reguler.
                        Rate OT dihitung dari rate reguler masing-masing operator.
                    </div>

                    <table class="det-tbl">
                        <thead>
                            <tr>
                                <th style="padding-left:1.25rem;">Employee</th>
                                <th>Day</th>
                                <th>Date</th>
                                <th>OT Start</th>
                                <th>OT End</th>
                                <th class="text-end">OT Hrs</th>
                                <th class="text-end">Base Rate</th>
                                <th class="text-end">Multiplier</th>
                                <th class="text-end">OT Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($weekdayOtRows as $row)
                                <tr>
                                    <td style="padding-left:1.25rem;">
                                        <div class="emp-name-cell">
                                            <span class="emp-avatar">{{ $row['initials'] }}</span>
                                            <div>
                                                <div class="fw-semibold" style="font-size:.8rem;">{{ $row['employee'] }}
                                                </div>
                                                <div class="text-muted" style="font-size:.68rem;">{{ $row['position'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted fw-semibold" style="font-size:.82rem;">{{ $row['day_name'] }}
                                    </td>
                                    <td class="text-muted" style="font-size:.8rem;">{{ $row['date'] }}</td>
                                    <td class="fw-semibold" style="font-size:.82rem; color:#e67e22;">
                                        {{ $row['ot_start'] ?? '—' }}</td>
                                    <td class="fw-semibold" style="font-size:.82rem; color:#e67e22;">
                                        {{ $row['ot_end'] ?? '—' }}</td>
                                    <td class="text-end fw-bold" style="color:#e67e22;">{{ $row['ot_hours'] }} hrs</td>
                                    <td class="text-end">
                                        <span
                                            class="ot-cost-chip">{{ $row['hourly_rate'] > 0 ? $fmt($row['hourly_rate']) : '—' }}</span>
                                    </td>
                                    <td class="text-end fw-semibold" style="font-size:.78rem; color:#e67e22;">
                                        {{ $row['mult_label'] ?? '—' }}</td>
                                    <td class="text-end fw-bold" style="font-size:.82rem; color:#e67e22;">
                                        {{ $row['ot_cost'] > 0 ? $fmt($row['ot_cost']) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-moon me-1"></i>No weekday OT sessions
                                    </td>
                                </tr>
                            @endforelse
                            @if ($hasWdOt)
                                <tr class="total-row">
                                    <td colspan="5" style="padding-left:1.25rem;">Total Weekday OT Hours</td>
                                    <td class="text-end" style="color:#e67e22;">{{ $totalWdOtHours }} hrs</td>
                                    <td colspan="2"></td>
                                    <td class="text-end" style="color:#e67e22;">{{ $fmt($totalWdOtCost) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    {{-- Breakdown sub-table --}}
                    @if ($hasWdOt)
                        @php $wdEmpRows = $byEmployee->filter(fn($e) => $e['wd_ot_cost'] > 0)->values(); @endphp
                        <div style="padding:.75rem 1.25rem 1rem;">
                            <div
                                style="font-size:.75rem; font-weight:700; color:#6c757d; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.5rem;">
                                Breakdown Jam OT (Mixed Multiplier)
                            </div>
                            <table class="det-tbl" style="font-size:.78rem;">
                                <thead>
                                    <tr>
                                        <th style="padding-left:.5rem;">Employee</th>
                                        <th class="text-end">1.5× Hrs</th>
                                        <th class="text-end">1.5× Cost</th>
                                        <th class="text-end">2× Hrs</th>
                                        <th class="text-end">2× Cost</th>
                                        <th class="text-end">Total Weekday OT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($wdEmpRows as $emp)
                                        <tr>
                                            <td style="padding-left:.5rem;">{{ $emp['name'] }}</td>
                                            <td class="text-end" style="color:#e67e22;">
                                                {{ $emp['hrs_1_5x'] > 0 ? $emp['hrs_1_5x'] . ' hrs' : '—' }}</td>
                                            <td class="text-end">
                                                {{ $emp['cost_1_5x'] > 0 ? $fmt($emp['cost_1_5x']) : '—' }}</td>
                                            <td class="text-end" style="color:#e67e22;">
                                                {{ $emp['hrs_2x_wd'] > 0 ? $emp['hrs_2x_wd'] . ' hrs' : '—' }}</td>
                                            <td class="text-end">
                                                {{ $emp['cost_2x_wd'] > 0 ? $fmt($emp['cost_2x_wd']) : '—' }}</td>
                                            <td class="text-end fw-bold" style="color:#e67e22;">
                                                {{ $fmt($emp['wd_ot_cost']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- ── Weekend & Holiday OT Log ── --}}
                <div class="section-block">
                    <div class="sb-header">
                        <div class="sb-title">
                            <span style="background:rgba(192,57,43,.12);padding:.3em .5em;border-radius:8px;">☀️</span>
                            Weekend & Holiday OT Log
                            <span class="sb-meta">Sat · Sun · Holiday</span>
                        </div>
                        <div style="font-size:.82rem; font-weight:700; color:#c0392b;">{{ $fmt($totalWeOtCost) }}</div>
                    </div>

                    {{-- Warning box --}}
                    <div
                        style="margin:.75rem 1.25rem .5rem; padding:.6rem .9rem; background:rgba(230,126,34,.07); border-left:3px solid #e67e22; border-radius:6px; font-size:.75rem; color:#d35400;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Aturan OT Hari Libur / Weekend (UU Ketenagakerjaan):</strong>
                        Sabtu (5-hari kerja): jam ke-1 s/d 8 = 2×, jam ke-9+ = 3× rate reguler &nbsp;·&nbsp;
                        Minggu / Hari Libur Nasional: jam ke-1 s/d 8 = 2×, jam ke-9 = 3×, jam ke-10+ = 4× rate reguler.
                    </div>

                    <table class="det-tbl">
                        <thead>
                            <tr>
                                <th style="padding-left:1.25rem;">Employee</th>
                                <th>Day</th>
                                <th>Date</th>
                                <th>Start</th>
                                <th>End</th>
                                <th class="text-end">OT Hrs</th>
                                <th class="text-end">Base Rate</th>
                                <th class="text-end">Multiplier</th>
                                <th class="text-end">OT Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($weekendOtRows as $row)
                                <tr>
                                    <td style="padding-left:1.25rem;">
                                        <div class="emp-name-cell">
                                            <span class="emp-avatar"
                                                style="background:linear-gradient(135deg,#c0392b,#e74c3c);">{{ $row['initials'] }}</span>
                                            <div>
                                                <div class="fw-semibold" style="font-size:.8rem;">{{ $row['employee'] }}
                                                </div>
                                                <div class="text-muted" style="font-size:.68rem;">{{ $row['position'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted fw-semibold" style="font-size:.82rem;">{{ $row['day_name'] }}
                                    </td>
                                    <td class="text-muted" style="font-size:.8rem;">{{ $row['date'] }}</td>
                                    <td class="fw-semibold" style="font-size:.82rem; color:#c0392b;">
                                        {{ $row['ot_start'] ?? $row['start_time'] }}</td>
                                    <td class="fw-semibold" style="font-size:.82rem; color:#c0392b;">
                                        {{ $row['ot_end'] ?? $row['end_time'] }}</td>
                                    <td class="text-end fw-bold" style="color:#c0392b;">{{ $row['ot_hours'] }} hrs</td>
                                    <td class="text-end">
                                        <span
                                            style="font-size:.7rem; color:#c0392b; font-weight:600;">{{ $row['hourly_rate'] > 0 ? $fmt($row['hourly_rate']) : '—' }}</span>
                                    </td>
                                    <td class="text-end fw-semibold" style="font-size:.78rem; color:#c0392b;">
                                        {{ $row['mult_label'] ?? '—' }}</td>
                                    <td class="text-end fw-bold" style="font-size:.82rem; color:#c0392b;">
                                        {{ $row['ot_cost'] > 0 ? $fmt($row['ot_cost']) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-calendar-times me-1"></i>No weekend / holiday OT sessions
                                    </td>
                                </tr>
                            @endforelse
                            @if ($hasWeOt)
                                <tr class="total-row">
                                    <td colspan="5" style="padding-left:1.25rem;">Total Weekend / Holiday OT Hours</td>
                                    <td class="text-end" style="color:#c0392b;">{{ $totalWeOtHours }} hrs</td>
                                    <td colspan="2"></td>
                                    <td class="text-end" style="color:#c0392b;">{{ $fmt($totalWeOtCost) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    {{-- Breakdown sub-table --}}
                    @if ($hasWeOt)
                        @php
                            $dayMapId = [
                                'Mon' => 'Senin',
                                'Tue' => 'Selasa',
                                'Wed' => 'Rabu',
                                'Thu' => 'Kamis',
                                'Fri' => 'Jumat',
                                'Sat' => 'Sabtu',
                                'Sun' => 'Minggu',
                            ];
                        @endphp
                        <div style="padding:.75rem 1.25rem 1rem;">
                            <div
                                style="font-size:.75rem; font-weight:700; color:#6c757d; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.5rem;">
                                Breakdown per Hari Libur / Weekend
                            </div>
                            <table class="det-tbl" style="font-size:.78rem;">
                                <thead>
                                    <tr>
                                        <th style="padding-left:.5rem;">Employee</th>
                                        <th>Hari</th>
                                        <th class="text-end">2× Hrs</th>
                                        <th class="text-end">2× Cost</th>
                                        <th class="text-end">3× Hrs</th>
                                        <th class="text-end">3× Cost</th>
                                        <th class="text-end">Total Weekend OT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($weekendOtRows as $row)
                                        <tr>
                                            <td style="padding-left:.5rem;">{{ $row['employee'] }}</td>
                                            <td>{{ $dayMapId[$row['day_name']] ?? $row['day_name'] }}</td>
                                            <td class="text-end" style="color:#c0392b;">
                                                {{ $row['hrs_2x_we'] > 0 ? $row['hrs_2x_we'] . ' hrs' : '—' }}</td>
                                            <td class="text-end">
                                                {{ $row['cost_2x_we'] > 0 ? $fmt($row['cost_2x_we']) : '—' }}</td>
                                            <td class="text-end" style="color:#c0392b;">
                                                {{ $row['hrs_3x'] > 0 ? $row['hrs_3x'] . ' hrs' : '—' }}</td>
                                            <td class="text-end">{{ $row['cost_3x'] > 0 ? $fmt($row['cost_3x']) : '—' }}
                                            </td>
                                            <td class="text-end fw-bold" style="color:#c0392b;">
                                                {{ $fmt($row['ot_cost']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- re-open section-block for footer --}}
                <div class="section-block">
                    {{-- Footer inside card --}}
                    <div class="d-flex justify-content-between align-items-center px-4 py-2"
                        style="font-size:.82rem; background:rgba(108,92,231,.04);">
                        <span class="fw-bold">Total Workmanship Cost</span>
                        <div class="text-end">
                            <div style="font-weight:800; color:#6c5ce7;">{{ $fmt($totalLaborCost) }}</div>
                            @if ($totalOtCost > 0)
                                <div style="font-size:.72rem; color:#6c757d;">
                                    Regular {{ $fmt($totalNormalCost) }}
                                    @if ($totalWdOtCost > 0)
                                        + <span style="color:#e67e22;">WD OT {{ $fmt($totalWdOtCost) }}</span>
                                    @endif
                                    @if ($totalWeOtCost > 0)
                                        + <span style="color:#c0392b;">WE OT {{ $fmt($totalWeOtCost) }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
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
                        @if ($emp['wd_ot_cost'] > 0)
                            <div class="ec-row">
                                <span style="color:#e67e22;">WD OT ({{ $emp['wd_ot_hours'] }}h)</span>
                                <span style="color:#e67e22; font-weight:600;">{{ $fmt($emp['wd_ot_cost']) }}</span>
                            </div>
                        @endif
                        @if ($emp['we_ot_cost'] > 0)
                            <div class="ec-row">
                                <span style="color:#c0392b;">WE OT ({{ $emp['we_ot_hours'] }}h)</span>
                                <span style="color:#c0392b; font-weight:600;">{{ $fmt($emp['we_ot_cost']) }}</span>
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
                <div style="font-size:.72rem; color:#b0a8cc; margin-top:.2rem; line-height:1.7;">
                    Regular <span style="color:#fff;">{{ $fmt($totalNormalCost) }}</span>
                    @if ($totalWdOtCost > 0)
                        &nbsp;+&nbsp;WD OT <span style="color:#f39c12;">{{ $fmt($totalWdOtCost) }}</span>
                    @endif
                    @if ($totalWeOtCost > 0)
                        &nbsp;+&nbsp;WE OT <span style="color:#e74c3c;">{{ $fmt($totalWeOtCost) }}</span>
                    @endif
                </div>
            </div>
            <div class="tb-breakdown">
                @foreach ($byEmployee as $emp)
                    <div class="tbi">
                        <div class="tbi-label">{{ strtoupper($emp['name']) }}</div>
                        <div class="tbi-val">{{ $fmt($emp['labor_cost']) }}</div>
                        <div style="font-size:.63rem; color:#a0a0c0; line-height:1.6;">
                            {{ $emp['normal_hours'] }}h reg
                            @if ($emp['wd_ot_hours'] > 0)
                                · <span style="color:#f39c12;">{{ $emp['wd_ot_hours'] }}h WD OT</span>
                            @endif
                            @if ($emp['we_ot_hours'] > 0)
                                · <span style="color:#e74c3c;">{{ $emp['we_ot_hours'] }}h WE OT</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            var BASE_URL = '{{ url()->current() }}';

            function initJoFilter() {
                // Destroy any existing select2 on this element first
                var $sel = $('#jo-filter-select');
                if ($sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2('destroy');
                }

                $sel.select2({
                    theme: 'bootstrap-5',
                    allowClear: true,
                    placeholder: '— All Job Orders —',
                    width: '100%',
                    minimumResultsForSearch: 5,
                }).on('select2:select', function() {
                    loadContent($sel.val());
                }).on('select2:clear', function() {
                    loadContent('');
                });

                // Clear filter button (outside select2)
                $('#jo-clear-btn').off('click').on('click', function() {
                    $sel.val('').trigger('change');
                    loadContent('');
                });
            }

            function loadContent(joId) {
                var url = BASE_URL + (joId ? '?job_order_id=' + encodeURIComponent(joId) : '');

                $('#wm-loading').removeClass('d-none');
                $('#jo-clear-btn').addClass('d-none');

                // Update browser URL without reload
                if (window.history && window.history.pushState) {
                    window.history.pushState({
                        joId: joId
                    }, '', url);
                }

                $.get(url, function(html) {
                    var $newContent = $(html).filter('#wm-content').add($(html).find('#wm-content')).first();
                    if (!$newContent.length) {
                        // fallback: try parsing
                        var $doc = $('<div>').html(html);
                        $newContent = $doc.find('#wm-content');
                    }
                    if ($newContent.length) {
                        $('#wm-content').replaceWith($newContent);
                        // Re-init after DOM swap
                        initJoFilter();
                        // Restore selected value in new select
                        if (joId) {
                            $('#jo-filter-select').val(joId).trigger('change.select2');
                            $('#jo-clear-btn').removeClass('d-none');
                        }
                    }
                }).fail(function() {
                    // Fallback to normal navigation on error
                    window.location.href = url;
                }).always(function() {
                    $('#wm-loading').addClass('d-none');
                });
            }

            // Handle browser back/forward
            window.addEventListener('popstate', function(e) {
                var joId = e.state && e.state.joId ? e.state.joId : '';
                loadContent(joId);
            });

            $(function() {
                initJoFilter();
            });
        }());
    </script>
@endpush
