@extends('layouts.app')
@section('title', 'HR Dashboard')

@section('content')
<style>
:root {
    --purple:#7c3aed; --purple-light:#a78bfa; --purple-dark:#5b21b6;
    --blue:#2563eb; --green:#16a34a; --yellow:#d97706;
    --red:#dc2626; --orange:#ea580c;
    --gray-50:#f9fafb; --gray-100:#f3f4f6; --gray-200:#e5e7eb;
    --gray-400:#9ca3af; --gray-600:#4b5563; --gray-800:#1f2937;
}
*{box-sizing:border-box;}

/* ── Layout ── */
.hrd { max-width:1440px; margin:0 auto; padding:20px 24px 40px; }

/* ── Page Header ── */
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; }
.page-header h1 { font-size:22px; font-weight:700; color:var(--gray-800); }
.page-header p { color:var(--gray-600); font-size:13px; margin-top:2px; }
.period-badge {
    background:white; border:1px solid var(--gray-200); border-radius:8px;
    padding:6px 14px; font-size:12px; color:var(--gray-600);
    box-shadow:0 1px 3px rgba(0,0,0,.06); white-space:nowrap;
}
.period-badge strong { color:var(--purple); }
.dot-live { display:inline-block; width:7px; height:7px; background:#4ade80; border-radius:50%; margin-right:4px; animation:pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ── Alert ── */
.alert-banner {
    background:#fffbeb; border:1px solid #fde68a; border-left:4px solid var(--yellow);
    border-radius:10px; padding:12px 18px; margin-bottom:20px;
    display:flex; align-items:center; gap:12px; font-size:13px;
}
.alert-icon { font-size:20px; }
.alert-text strong { color:var(--yellow); }

/* ── KPI Cards ── */
.kpi-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:14px; margin-bottom:20px; }
.kpi-card {
    background:white; border-radius:12px; padding:16px 18px;
    box-shadow:0 1px 4px rgba(0,0,0,.07); border:1px solid var(--gray-100);
    position:relative; overflow:hidden;
}
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.kpi-card.purple::before { background:var(--purple); }
.kpi-card.blue::before   { background:var(--blue); }
.kpi-card.green::before  { background:var(--green); }
.kpi-card.red::before    { background:var(--red); }
.kpi-card.yellow::before { background:var(--yellow); }
.kpi-card.orange::before { background:var(--orange); }
.kpi-label { font-size:11px; color:var(--gray-400); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
.kpi-value { font-size:28px; font-weight:800; line-height:1; }
.kpi-card.purple .kpi-value { color:var(--purple); }
.kpi-card.blue   .kpi-value { color:var(--blue);   }
.kpi-card.green  .kpi-value { color:var(--green);  }
.kpi-card.red    .kpi-value { color:var(--red);    }
.kpi-card.yellow .kpi-value { color:var(--yellow); }
.kpi-card.orange .kpi-value { color:var(--orange); }
.kpi-sub  { font-size:11px; color:var(--gray-400); margin-top:4px; }
.kpi-icon { position:absolute; right:14px; top:14px; font-size:22px; opacity:.12; }

/* ── Section Titles ── */
.section-title {
    font-size:13px; font-weight:700; color:var(--gray-600);
    text-transform:uppercase; letter-spacing:.7px;
    margin-bottom:12px; display:flex; align-items:center; gap:8px;
}
.section-title::after { content:''; flex:1; height:1px; background:var(--gray-200); }

/* ── Cards ── */
.card { background:white; border-radius:14px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,.07); border:1px solid var(--gray-100); }
.card-title { font-size:14px; font-weight:700; color:var(--gray-800); margin-bottom:4px; }
.card-sub   { font-size:11px; color:var(--gray-400); margin-bottom:16px; }

/* ── Grids ── */
.grid-2  { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.grid-3  { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:16px; }
.grid-31 { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:16px; }

/* ── Chart Wrapper ── */
.chart-wrap { position:relative; }
.chart-wrap canvas { max-width:100%; }

/* ── Legend List ── */
.legend-list { list-style:none; margin-top:14px; }
.legend-list li { display:flex; align-items:center; justify-content:space-between; padding:5px 0; border-bottom:1px solid var(--gray-100); font-size:12px; }
.legend-list li:last-child { border-bottom:none; }
.legend-dot { width:10px; height:10px; border-radius:3px; margin-right:8px; flex-shrink:0; }
.legend-label { display:flex; align-items:center; color:var(--gray-600); }
.legend-val { font-weight:700; color:var(--gray-800); }

/* ── Heatmap ── */
.heatmap-grid { display:grid; gap:3px; font-size:10px; }
.hm-header { text-align:center; color:var(--gray-400); padding:2px; font-weight:600; font-size:10px; }
.hm-name { color:var(--gray-600); padding:4px 6px; font-size:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:flex; align-items:center; }
.hm-cell { border-radius:4px; height:26px; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:600; color:white; }
.hm-p { background:#16a34a; }
.hm-l { background:#d97706; }
.hm-a { background:#dc2626; }
.hm-n { background:#d1d5db; color:#6b7280; }

/* ── Badge ── */
.badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
.badge-green  { background:#dcfce7; color:#16a34a; }
.badge-yellow { background:#fef3c7; color:#d97706; }
.badge-red    { background:#fee2e2; color:#dc2626; }
.badge-blue   { background:#dbeafe; color:#2563eb; }
.badge-purple { background:#ede9fe; color:#7c3aed; }
.badge-gray   { background:#f3f4f6; color:#6b7280; }

/* ── Data Table ── */
.data-table { width:100%; border-collapse:collapse; font-size:12px; }
.data-table th { text-align:left; padding:8px 10px; background:var(--gray-50); color:var(--gray-400); font-size:10px; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--gray-200); }
.data-table td { padding:8px 10px; border-bottom:1px solid var(--gray-100); color:var(--gray-800); }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:var(--gray-50); }

/* ── Progress Bar ── */
.prog-bar  { background:var(--gray-100); border-radius:4px; height:7px; overflow:hidden; }
.prog-fill { height:100%; border-radius:4px; }

/* ── OT Cards ── */
.ot-cards { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px; }
.ot-card  { background:var(--gray-50); border-radius:10px; padding:14px; border:1px solid var(--gray-200); text-align:center; }
.ot-num   { font-size:26px; font-weight:800; color:var(--purple); }
.ot-label { font-size:10px; color:var(--gray-400); text-transform:uppercase; margin-top:2px; }

/* ── Module Grid ── */
.module-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.module-card {
    background:white; border-radius:10px; padding:14px;
    border:1px solid var(--gray-100); box-shadow:0 1px 3px rgba(0,0,0,.05);
    display:flex; flex-direction:column; gap:6px;
    text-decoration:none; color:inherit;
    transition:transform .15s, box-shadow .15s;
}
.module-card:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.1); color:inherit; }
.module-icon   { font-size:22px; }
.module-name   { font-size:12px; font-weight:700; color:var(--gray-800); }
.module-detail { font-size:11px; color:var(--gray-400); }

/* ── Approval Mini ── */
.approval-stats { display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; }
.approval-card  { flex:1; border-radius:10px; padding:12px; text-align:center; }
.approval-card.pending-hr  { background:#dcfce7; }
.approval-card.pending-dir { background:#dbeafe; }
.approval-card.pending-ot  { background:#fef3c7; }
.approval-card .number { font-size:22px; font-weight:800; }
.approval-card.pending-hr  .number { color:#16a34a; }
.approval-card.pending-dir .number { color:#2563eb; }
.approval-card.pending-ot  .number { color:#d97706; }
.approval-card .label { font-size:10px; font-weight:600; }
.approval-card.pending-hr  .label { color:#16a34a; }
.approval-card.pending-dir .label { color:#2563eb; }
.approval-card.pending-ot  .label { color:#d97706; }
.btn-link {
    display:block; text-align:center; background:var(--purple); color:white;
    padding:8px; border-radius:8px; text-decoration:none;
    font-size:12px; font-weight:600; margin-top:16px;
}
.btn-link:hover { background:var(--purple-dark); color:white; }

/* ── Footer ── */
.dashboard-footer { text-align:center; color:var(--gray-400); font-size:11px; padding-top:24px; border-top:1px solid var(--gray-200); margin-top:24px; }

@media(max-width:1200px){
    .kpi-grid   { grid-template-columns:repeat(3,1fr); }
    .grid-3     { grid-template-columns:1fr 1fr; }
    .module-grid{ grid-template-columns:repeat(2,1fr); }
}
@media(max-width:768px){
    .hrd        { padding:16px; }
    .kpi-grid   { grid-template-columns:repeat(2,1fr); }
    .grid-2,.grid-3,.grid-31 { grid-template-columns:1fr; }
    .ot-cards   { grid-template-columns:repeat(2,1fr); }
    .module-grid{ grid-template-columns:repeat(2,1fr); }
}
</style>

<div class="hrd">

{{-- ── Header ── --}}
<div class="page-header">
    <div>
        <h1>🏢 HR Operations Dashboard</h1>
        <p>
            <span class="dot-live"></span>Live Data &nbsp;·&nbsp;
            Complete HR module summary — Employees, Attendance, Leave, Overtime
        </p>
    </div>
    <div class="period-badge">
        Period: <strong>{{ $now->format('F Y') }}</strong> · Updated: {{ $now->format('d/m/Y H:i') }}
    </div>
</div>

{{-- ── Alert ── --}}
@if($nearExpiredCount > 0)
<div class="alert-banner">
    <div class="alert-icon">⚠️</div>
    <div class="alert-text">
        <strong>Contract Expiry Alert:</strong>
        {{ $nearExpiredCount }} employee(s) have contracts expiring within the next 30 days.
        Employee status will automatically change to "Inactive" when the contract ends.
    </div>
    <a href="{{ route('employees.near-expired') }}" style="margin-left:auto;background:var(--yellow);color:white;padding:5px 14px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;white-space:nowrap;">View Details</a>
</div>
@endif

{{-- ══ KPI CARDS ══ --}}
<div class="kpi-grid">
    <div class="kpi-card purple">
        <div class="kpi-icon">👥</div>
        <div class="kpi-label">Total Employees</div>
        <div class="kpi-value">{{ $totalEmployees }}</div>
        <div class="kpi-sub">All statuses</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-icon">✅</div>
        <div class="kpi-label">Active</div>
        <div class="kpi-value">{{ $activeEmployees }}</div>
        <div class="kpi-sub">{{ $totalEmployees > 0 ? round(($activeEmployees/$totalEmployees)*100,1) : 0 }}% of total</div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-icon">🚫</div>
        <div class="kpi-label">Terminated</div>
        <div class="kpi-value">{{ $terminatedEmployees }}</div>
        <div class="kpi-sub">{{ $totalEmployees > 0 ? round(($terminatedEmployees/$totalEmployees)*100,1) : 0 }}% of total</div>
    </div>
    <div class="kpi-card yellow">
        <div class="kpi-icon">⏰</div>
        <div class="kpi-label">Near Expired</div>
        <div class="kpi-value">{{ $nearExpiredCount }}</div>
        <div class="kpi-sub">Contract &lt;30 days</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-icon">📋</div>
        <div class="kpi-label">Attendance ({{ $attendanceDateLabel }})</div>
        <div class="kpi-value">{{ $attendanceRate }}%</div>
        <div class="kpi-sub">~{{ $todayAttendance }} of {{ $activeEmployees }} active</div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-icon">🕐</div>
        <div class="kpi-label">Pending Approval</div>
        <div class="kpi-value">{{ $pendingLeaveHr + $pendingLeaveDir + $pendingOT }}</div>
        <div class="kpi-sub">Leave &amp; Overtime</div>
    </div>
</div>

{{-- ══ EMPLOYEE ══ --}}
<div class="section-title">👥 Employee Management</div>
<div class="grid-3">
    <div class="card">
        <div class="card-title">Employee Status</div>
        <div class="card-sub">Status distribution of {{ $totalEmployees }} employees</div>
        <div class="chart-wrap" style="height:180px;"><canvas id="chartEmpStatus"></canvas></div>
        <ul class="legend-list">
            <li><span class="legend-label"><span class="legend-dot" style="background:#7c3aed;"></span>Active</span><span class="legend-val">{{ $activeEmployees }}</span></li>
            <li><span class="legend-label"><span class="legend-dot" style="background:#dc2626;"></span>Terminated</span><span class="legend-val">{{ $terminatedEmployees }}</span></li>
            @if($nearExpiredCount)
            <li><span class="legend-label"><span class="legend-dot" style="background:#d97706;"></span>Near Expired</span><span class="legend-val">{{ $nearExpiredCount }}</span></li>
            @endif
        </ul>
    </div>
    <div class="card">
        <div class="card-title">Employees by Department</div>
        <div class="card-sub">Active employee count per department</div>
        <div class="chart-wrap" style="height:230px;"><canvas id="chartDept"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">Employment Type</div>
        <div class="card-sub">Breakdown by employment contract type</div>
        <div class="chart-wrap" style="height:170px;"><canvas id="chartEmpType"></canvas></div>
        <ul class="legend-list" style="margin-top:10px;">
            @php $empTypeColors = ['#2563eb','#7c3aed','#16a34a','#d97706','#ea580c','#ec4899']; @endphp
            @foreach($byEmploymentType as $i => $et)
            <li>
                <span class="legend-label"><span class="legend-dot" style="background:{{ $empTypeColors[$i % count($empTypeColors)] }};"></span>{{ $et->employment_type ?? '-' }}</span>
                <span class="legend-val">{{ $et->total }}</span>
            </li>
            @endforeach
        </ul>
    </div>
</div>

{{-- Contract Expiry Table --}}
@if($nearExpiredList->isNotEmpty())
<div class="card" style="margin-bottom:16px;">
    <div class="card-title">📅 Contracts Ending — Next 30 Days</div>
    <div class="card-sub">{{ $nearExpiredCount }} employee(s) need contract renewal soon — scroll to see all</div>
    <div style="overflow-x:auto;">
        <div style="display:flex; gap:10px; padding:4px 2px 8px; min-width:max-content;">
            @foreach($nearExpiredList as $emp)
            @php $dl = max(0, \Carbon\Carbon::now()->diffInDays($emp->contract_end_date, false)); @endphp
            <div style="
                background:{{ $dl <= 7 ? '#fff1f2' : ($dl <= 14 ? '#fffbeb' : '#f9fafb') }};
                border:1px solid {{ $dl <= 7 ? '#fecdd3' : ($dl <= 14 ? '#fde68a' : '#e5e7eb') }};
                border-radius:10px; padding:12px 14px; min-width:170px; flex-shrink:0;
            ">
                <div style="font-size:10px; font-weight:700; color:var(--gray-400); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px;">
                    {{ $emp->employment_type ?? '-' }}
                </div>
                <div style="font-size:12px; font-weight:700; color:var(--gray-800); margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:150px;">
                    {{ $emp->name }}
                </div>
                <div style="font-size:11px; color:var(--gray-400); margin-bottom:8px;">
                    {{ $emp->department->name ?? '-' }}
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <span style="font-size:10px; color:var(--gray-600);">{{ $emp->contract_end_date->format('d M Y') }}</span>
                    <span style="
                        font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
                        background:{{ $dl <= 7 ? '#fee2e2' : ($dl <= 14 ? '#fef3c7' : '#f3f4f6') }};
                        color:{{ $dl <= 7 ? '#dc2626' : ($dl <= 14 ? '#d97706' : '#6b7280') }};
                    ">{{ $dl }}d</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ══ ATTENDANCE ══ --}}
<div class="section-title">📋 Attendance Management</div>

<div class="grid-31">
    <div class="card">
        <div class="card-title">Attendance Summary {{ $now->format('F Y') }} — Per Employee (Sample)</div>
        <div class="card-sub">Present (P), Late (L), Alpha (A) — top alpha employees</div>
        <div class="chart-wrap" style="height:280px;"><canvas id="chartAttendance"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">Attendance Status Distribution</div>
        <div class="card-sub">{{ $now->format('F Y') }} — aggregate across all employees</div>
        <div class="chart-wrap" style="height:200px;"><canvas id="chartAttPie"></canvas></div>
        <ul class="legend-list">
            <li><span class="legend-label"><span class="legend-dot" style="background:#16a34a;"></span>Present</span><span class="legend-val">{{ $attendanceStats->present_count ?? 0 }} person-days</span></li>
            <li><span class="legend-label"><span class="legend-dot" style="background:#d97706;"></span>Late</span><span class="legend-val">{{ $attendanceStats->late_count ?? 0 }} person-days</span></li>
            <li><span class="legend-label"><span class="legend-dot" style="background:#dc2626;"></span>Alpha</span><span class="legend-val">{{ $attendanceStats->alpha_count ?? 0 }} person-days</span></li>
            <li><span class="legend-label"><span class="legend-dot" style="background:#9ca3af;"></span>Other</span><span class="legend-val">{{ $attendanceStats->other_count ?? 0 }} person-days</span></li>
        </ul>
    </div>
</div>

{{-- Heatmap + Trend --}}
<div class="grid-2">
    {{-- Heatmap --}}
    <div class="card">
        <div class="card-title">🗓️ Attendance Heatmap — {{ $now->format('F Y') }}</div>
        <div class="card-sub">Sample of 10 employees · first {{ count($sampleDates) }} working days</div>
        <div style="overflow-x:auto;margin-top:8px;">
            @php $cols = 1 + count($sampleDates) + 2; @endphp
            <div class="heatmap-grid" style="grid-template-columns:140px repeat({{ count($sampleDates) }},1fr) 52px 52px;">
                <div class="hm-header" style="text-align:left;color:var(--gray-400);">Employee</div>
                @foreach($sampleDates as $sd)
                    <div class="hm-header">{{ $sd->format('d/D') }}</div>
                @endforeach
                <div class="hm-header">P</div>
                <div class="hm-header">L</div>

                @foreach($attendanceHeatmap as $row)
                    <div class="hm-name">{{ $row['name'] }}</div>
                    @foreach($sampleDates as $sd)
                        @php $cell = $row[$sd->format('Y-m-d')] ?? '-'; @endphp
                        <div class="hm-cell {{ $cell === 'P' ? 'hm-p' : ($cell === 'L' ? 'hm-l' : ($cell === 'A' ? 'hm-a' : 'hm-n')) }}">{{ $cell }}</div>
                    @endforeach
                    <div class="hm-cell" style="background:#dcfce7;color:#16a34a;">{{ $row['present_total'] }}</div>
                    <div class="hm-cell" style="background:#fef3c7;color:#d97706;">{{ $row['late_total'] }}</div>
                @endforeach
            </div>
        </div>
        <div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap;">
            <span style="font-size:10px;display:flex;align-items:center;gap:4px;"><span style="background:#16a34a;color:white;padding:1px 6px;border-radius:3px;font-weight:700;">P</span> Present</span>
            <span style="font-size:10px;display:flex;align-items:center;gap:4px;"><span style="background:#d97706;color:white;padding:1px 6px;border-radius:3px;font-weight:700;">L</span> Late</span>
            <span style="font-size:10px;display:flex;align-items:center;gap:4px;"><span style="background:#dc2626;color:white;padding:1px 6px;border-radius:3px;font-weight:700;">A</span> Alpha</span>
            <span style="font-size:10px;display:flex;align-items:center;gap:4px;"><span style="background:#d1d5db;color:#6b7280;padding:1px 6px;border-radius:3px;font-weight:700;">-</span> No Data</span>
        </div>
    </div>

    {{-- Trend Line --}}
    <div class="card">
        <div class="card-title">📈 Daily Attendance Trend — {{ $now->format('F Y') }}</div>
        <div class="card-sub">Present vs Late vs Alpha per working day</div>
        <div class="chart-wrap" style="height:280px;"><canvas id="chartAttTrend"></canvas></div>
    </div>
</div>

{{-- Top Alpha & Late --}}
<div class="grid-2" style="margin-bottom:16px;">
    <div class="card">
        <div class="card-title">🔴 Most Absent Employees ({{ $now->format('F Y') }})</div>
        <div class="card-sub">Requires HR follow-up</div>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Dept</th><th>Alpha</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($topAbsences as $row)
                <tr>
                    <td>{{ $row->employee->name ?? '-' }}</td>
                    <td>{{ $row->employee->department->name ?? '-' }}</td>
                    <td><strong style="color:#dc2626;">{{ $row->alpha_count }}</strong></td>
                    <td>
                        @if($row->alpha_count >= 5) <span class="badge badge-red">Critical</span>
                        @elseif($row->alpha_count >= 3) <span class="badge badge-yellow">Warning</span>
                        @else <span class="badge badge-blue">Monitor</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:16px;color:var(--gray-400);">No absence data this month</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <div class="card-title">🟡 Most Late Employees ({{ $now->format('F Y') }})</div>
        <div class="card-sub">Tardiness of 1–60 minutes per day</div>
        @php $maxLate = $topLate->max('late_count') ?: 1; @endphp
        <table class="data-table">
            <thead><tr><th>Name</th><th>Total Late</th><th>Bar</th></tr></thead>
            <tbody>
                @forelse($topLate as $row)
                <tr>
                    <td>{{ $row->employee->name ?? '-' }}</td>
                    <td><strong style="color:#d97706;">{{ $row->late_count }} day(s)</strong></td>
                    <td style="width:100px;">
                        <div class="prog-bar"><div class="prog-fill" style="width:{{ round(($row->late_count/$maxLate)*100) }}%;background:#d97706;"></div></div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center;padding:16px;color:var(--gray-400);">No late records this month</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══ LEAVE ══ --}}
<div class="section-title">🏖️ Leave Management</div>
<div class="grid-3">
    <div class="card">
        <div class="card-title">Leave Request Status</div>
        <div class="card-sub">All leave requests — {{ $now->format('F Y') }}</div>
        <div class="chart-wrap" style="height:180px;"><canvas id="chartLeaveStatus"></canvas></div>
        <ul class="legend-list">
            <li><span class="legend-label"><span class="legend-dot" style="background:#16a34a;"></span>Approved (HR+Dir)</span><span class="legend-val">{{ $approvedLeaves }}</span></li>
            <li><span class="legend-label"><span class="legend-dot" style="background:#d97706;"></span>HR Approved, Dir Pending</span><span class="legend-val">{{ $pendingLeaveDir }}</span></li>
            <li><span class="legend-label"><span class="legend-dot" style="background:#dc2626;"></span>Rejected</span><span class="legend-val">{{ $rejectedLeaves }}</span></li>
            <li><span class="legend-label"><span class="legend-dot" style="background:#9ca3af;"></span>Pending HR</span><span class="legend-val">{{ $pendingLeaveHr }}</span></li>
        </ul>
    </div>
    <div class="card">
        <div class="card-title">Leave by Type</div>
        <div class="card-sub">Breakdown by leave/permit type</div>
        <div class="chart-wrap" style="height:220px;"><canvas id="chartLeaveType"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">📝 Approval Queue Status</div>
        <div class="card-sub">Dual approval: HR &amp; Director</div>
        <div class="approval-stats">
            <div class="approval-card pending-hr"><div class="number">{{ $pendingLeaveHr }}</div><div class="label">Pending HR</div></div>
            <div class="approval-card pending-dir"><div class="number">{{ $pendingLeaveDir }}</div><div class="label">Pending Director</div></div>
            <div class="approval-card pending-ot"><div class="number">{{ $pendingOT }}</div><div class="label">Pending OT</div></div>
        </div>
        <a href="{{ route('leave_requests.hr-approvals') }}" class="btn-link">Manage Leave Requests</a>
    </div>
</div>

{{-- ══ OVERTIME ══ --}}
<div class="section-title">⏱️ Overtime Management</div>
<div class="card" style="margin-bottom:16px;">
    <div class="card-title">Overtime Overview — {{ $now->format('F Y') }}</div>
    <div class="card-sub">Data from Overtime Approvals &amp; Overtime Pay module</div>
    <div class="ot-cards">
        <div class="ot-card"><div class="ot-num">{{ $pendingOT }}</div><div class="ot-label">⏳ Pending</div></div>
        <div class="ot-card"><div class="ot-num">{{ $thisMonthOT }}</div><div class="ot-label">📅 This Month</div></div>
        <div class="ot-card"><div class="ot-num" style="color:#2563eb;">{{ number_format($totalOTHours,1) }}</div><div class="ot-label">⏱️ Total Hours</div></div>
        <div class="ot-card"><div class="ot-num" style="color:#16a34a;">Rp 0</div><div class="ot-label">💰 OT Pay (This Month)</div></div>
    </div>
    <div class="grid-2" style="margin-bottom:0;">
        <div>
            <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:8px;">Monthly OT Trend</div>
            <div class="chart-wrap" style="height:160px;"><canvas id="chartOTTrend"></canvas></div>
        </div>
        <div>
            <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:8px;">OT by Type (OT Code)</div>
            <div class="chart-wrap" style="height:160px;"><canvas id="chartOTType"></canvas></div>
        </div>
    </div>
</div>


</div>{{-- /hrd --}}

{{-- ══════════════ CHARTS ══════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#6b7280';

const BR = 6;

// Shared pie tooltip — shows label + count + %
const PIE_TOOLTIP = {
    callbacks: {
        label: ctx => {
            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
            const pct   = total > 0 ? ((ctx.raw/total)*100).toFixed(1) : 0;
            return `  ${ctx.label}: ${ctx.raw} (${pct}%)`;
        }
    }
};

function mkChart(id, type, data, options) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, { type, data, options: { responsive:true, maintainAspectRatio:false, ...options } });
}

// 1. Employee Status — Pie
mkChart('chartEmpStatus', 'pie', {
    labels: ['Active','Terminated'@if($nearExpiredCount),'Near Expired'@endif],
    datasets: [{ data:[{{ $activeEmployees }},{{ $terminatedEmployees }}@if($nearExpiredCount),{{ $nearExpiredCount }}@endif], backgroundColor:['#7c3aed','#dc2626'@if($nearExpiredCount),'#d97706'@endif], borderWidth:2, borderColor:'#fff', hoverOffset:6 }]
}, { plugins:{ legend:{display:false}, tooltip: PIE_TOOLTIP } });

// 2. Department — Horizontal Bar
mkChart('chartDept', 'bar', {
    labels: {!! json_encode($byDepartmentActive->pluck('name')->values()) !!},
    datasets: [{ label:'Active', data:{!! json_encode($byDepartmentActive->pluck('employees_count')->values()) !!}, backgroundColor:['#7c3aed','#2563eb','#16a34a','#d97706','#ea580c','#ec4899','#06b6d4','#f97316'], borderRadius:BR, borderSkipped:false }]
}, { indexAxis:'y', plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}, ticks:{stepSize:10}}, y:{grid:{display:false}} } });

// 3. Contract Type — Pie
mkChart('chartEmpType', 'pie', {
    labels: {!! json_encode($byEmploymentType->pluck('employment_type')->map(fn($v)=>$v??'Unknown')->values()) !!},
    datasets: [{ data:{!! json_encode($byEmploymentType->pluck('total')->values()) !!}, backgroundColor:['#2563eb','#7c3aed','#16a34a','#d97706','#ea580c','#ec4899'], borderWidth:2, borderColor:'#fff', hoverOffset:6 }]
}, { plugins:{ legend:{display:false}, tooltip:PIE_TOOLTIP } });


// 5. Attendance Stacked — Horizontal Bar
mkChart('chartAttendance', 'bar', {
    labels: {!! json_encode($topAbsences->take(10)->map(fn($r) => $r->employee->name ?? '-')->values()) !!},
    datasets: [
        { label:'Present', data:{!! json_encode($topAbsences->take(10)->map(fn($r) => $r->present_count ?? 0)->values()) !!}, backgroundColor:'#16a34a', borderRadius:3, stack:'att' },
        { label:'Late',    data:{!! json_encode($topAbsences->take(10)->map(fn($r) => $r->late_count ?? 0)->values()) !!},    backgroundColor:'#d97706', borderRadius:3, stack:'att' },
        { label:'Alpha',   data:{!! json_encode($topAbsences->take(10)->pluck('alpha_count')->values()) !!},                   backgroundColor:'#dc2626', borderRadius:3, stack:'att' }
    ]
}, { indexAxis:'y', plugins:{legend:{position:'top', labels:{boxWidth:10,font:{size:10}}}}, scales:{ x:{stacked:true, grid:{display:false}}, y:{stacked:true, grid:{display:false}} } });

// 6. Attendance Pie
mkChart('chartAttPie', 'pie', {
    labels: ['Present','Late','Alpha','Other'],
    datasets: [{ data:[{{ $attendanceStats->present_count ?? 0 }},{{ $attendanceStats->late_count ?? 0 }},{{ $attendanceStats->alpha_count ?? 0 }},{{ $attendanceStats->other_count ?? 0 }}], backgroundColor:['#16a34a','#d97706','#dc2626','#9ca3af'], borderWidth:2, borderColor:'#fff', hoverOffset:6 }]
}, { plugins:{ legend:{display:false}, tooltip:PIE_TOOLTIP } });

// 7. Attendance Trend — Line
@php
    $trendLabels  = $dailyTrend->map(fn($r) => $r->day->format('d/M'));
    $trendPresent = $dailyTrend->pluck('present');
    $trendLate    = $dailyTrend->pluck('late');
    $trendAlpha   = $dailyTrend->pluck('alpha');
@endphp
mkChart('chartAttTrend', 'line', {
    labels: {!! json_encode($trendLabels->values()) !!},
    datasets: [
        { label:'Present', data:{!! json_encode($trendPresent->values()) !!}, borderColor:'#16a34a', backgroundColor:'rgba(22,163,74,.08)', tension:.4, fill:true, pointRadius:4, pointBackgroundColor:'#16a34a' },
        { label:'Late',    data:{!! json_encode($trendLate->values()) !!},    borderColor:'#d97706', backgroundColor:'rgba(217,119,6,.08)', tension:.4, fill:true, pointRadius:4, pointBackgroundColor:'#d97706' },
        { label:'Alpha',   data:{!! json_encode($trendAlpha->values()) !!},   borderColor:'#dc2626', backgroundColor:'rgba(220,38,38,.07)', tension:.4, fill:true, pointRadius:4, pointBackgroundColor:'#dc2626' }
    ]
}, { plugins:{legend:{position:'top',labels:{boxWidth:10,font:{size:10}}}}, scales:{ x:{grid:{display:false}, ticks:{font:{size:9}}}, y:{grid:{color:'#f3f4f6'}, beginAtZero:true} } });

// 8. Leave Status — Pie
mkChart('chartLeaveStatus', 'pie', {
    labels: ['Approved (HR+Dir)','HR Approved, Dir Pending','Rejected','Pending HR'],
    datasets: [{ data:[{{ $approvedLeaves }},{{ $pendingLeaveDir }},{{ $rejectedLeaves }},{{ $pendingLeaveHr }}], backgroundColor:['#16a34a','#d97706','#dc2626','#9ca3af'], borderWidth:2, borderColor:'#fff', hoverOffset:6 }]
}, { plugins:{ legend:{display:false}, tooltip:PIE_TOOLTIP } });

// 9. Leave Type — Bar
mkChart('chartLeaveType', 'bar', {
    labels: {!! json_encode($leaveByType->pluck('type')->values()) !!},
    datasets: [{ label:'Count', data:{!! json_encode($leaveByType->pluck('total')->values()) !!}, backgroundColor:['#2563eb','#7c3aed','#d97706','#ec4899','#16a34a','#9ca3af'], borderRadius:BR }]
}, { plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}, ticks:{font:{size:10}}}, y:{grid:{color:'#f3f4f6'}, beginAtZero:true, ticks:{stepSize:1}} } });

// 10. OT Trend — Bar
mkChart('chartOTTrend', 'bar', {
    labels: {!! json_encode(collect($otMonthlyTrend)->pluck('month')->values()) !!},
    datasets: [{ label:'OT Hours', data:{!! json_encode(collect($otMonthlyTrend)->pluck('hours')->values()) !!}, backgroundColor:'#7c3aed', borderRadius:BR }]
}, { plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{grid:{color:'#f3f4f6'}, beginAtZero:true} } });

// 11. OT Type — Pie
@php $otLabels = $otByType->pluck('ot_code'); $otData = $otByType->pluck('total'); @endphp
@if($otByType->isNotEmpty())
mkChart('chartOTType', 'pie', {
    labels: {!! json_encode($otLabels->values()) !!},
    datasets: [{ data:{!! json_encode($otData->values()) !!}, backgroundColor:['#7c3aed','#2563eb','#d97706','#16a34a'], borderWidth:2, borderColor:'#fff', hoverOffset:6 }]
}, { plugins:{ legend:{position:'right', labels:{boxWidth:10,font:{size:10}}}, tooltip:PIE_TOOLTIP } });
@else
(function(){ const el=document.getElementById('chartOTType'); if(el){ el.parentElement.innerHTML='<div style="height:160px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;">No OT data this month</div>'; } })();
@endif
</script>
@endsection
