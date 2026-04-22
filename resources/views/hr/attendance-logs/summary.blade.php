@extends('layouts.app')

@section('title', 'Attendance Summary')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-2 mb-md-3">
                <a href="{{ route('attendance-logs.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                    <i class="fas fa-arrow-left me-1"></i><span class="d-none d-sm-inline"> Back</span>
                </a>
                @can('hr.attendance.edit')
                <div class="d-flex gap-2">
                    <a href="{{ route('national-holidays.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                        <i class="fas fa-flag me-1"></i><span class="d-none d-sm-inline"> National Holidays</span><span class="d-inline d-sm-none"> Nat.Hol</span>
                    </a>
                    <button class="btn btn-sm btn-outline-purple px-3" data-bs-toggle="modal" data-bs-target="#manageHolidayModal">
                        <i class="fas fa-calendar-plus me-1"></i><span class="d-none d-sm-inline"> Company Holidays</span><span class="d-inline d-sm-none"> Co.Hol</span>
                    </button>
                </div>
                @endcan
            </div>

            {{-- Month Navigator --}}
            @php
                $prevMonth = $startOfMonth->copy()->subMonth();
                $nextMonth = $startOfMonth->copy()->addMonth();
                $navParams = $departmentId ? ['department_id' => $departmentId] : [];
            @endphp
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('attendance-logs.summary', array_merge($navParams, ['month' => $prevMonth->month, 'year' => $prevMonth->year])) }}"
                   class="btn btn-sm btn-outline-secondary px-2 px-sm-3">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <span class="fw-semibold text-center month-label">
                    {{ $startOfMonth->isoFormat('MMMM YYYY') }}
                </span>
                <a href="{{ route('attendance-logs.summary', array_merge($navParams, ['month' => $nextMonth->month, 'year' => $nextMonth->year])) }}"
                   class="btn btn-sm btn-outline-secondary px-2 px-sm-3">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="{{ route('session-shifts.live-monitor', ['from' => 'summary']) }}" class="btn btn-sm btn-outline-success px-2 px-sm-3">
                    <i class="fas fa-satellite-dish me-sm-1"></i><span class="d-none d-sm-inline">Live Monitor</span>
                </a>
                <div style="min-width:180px;">
                    <input type="text" id="empSearchSummary" class="form-select form-select-sm"
                           placeholder="Search employee..." autocomplete="off">
                </div>
                <div class="ms-auto">
                    <select id="deptFilter" class="form-select form-select-sm dept-filter-select">
                        <option value="">All Dept</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Legend (horizontal scroll on mobile) --}}
            <div class="legend-scroll-wrap mb-2 mb-md-3">
                <div class="d-flex gap-2 align-items-center legend-inner">
                    <span class="legend-box" style="background:#bbf7d0;border:1.5px solid #22c55e;"></span><small class="legend-text">Present</small>
                    <span class="legend-box" style="background:#fde68a;border:1.5px solid #f59e0b;"></span><small class="legend-text">Late</small>
                    <span class="legend-box" style="background:#fca5a5;border:1.5px solid #ef4444;"></span><small class="legend-text">Alpha</small>
                    <span class="legend-box" style="background:#bfdbfe;border:1.5px solid #3b82f6;"></span><small class="legend-text">Ann.Leave</small>
                    <span class="legend-box" style="background:#a5f3fc;border:1.5px solid #06b6d4;"></span><small class="legend-text">Sick</small>
                    <span class="legend-box" style="background:#c4b5fd;border:1.5px solid #8b5cf6;"></span><small class="legend-text">Oth.Leave</small>
                    <span class="legend-box" style="background:#e0e7ff;border:1.5px solid #6366f1;"></span><small class="legend-text">Unpaid</small>
                    <span class="legend-box" style="background:#cbd5e1;border:1.5px solid #94a3b8;"></span><small class="legend-text">Sun</small>
                    <span class="legend-box" style="background:#fed7aa;border:1.5px solid #f97316;"></span><small class="legend-text">Nat.Hol</small>
                    <span class="legend-box" style="background:#99f6e4;border:1.5px solid #14b8a6;"></span><small class="legend-text">Co.Hol</small>
                    <span class="legend-box" style="background:#fbcfe8;border:1.5px solid #ec4899;"></span><small class="legend-text">Hol-Ded</small>
                    <span class="legend-box" style="background:#fcd34d;border:1.5px solid #d97706;"></span><small class="legend-text">Hol-Unp</small>
                    <span class="legend-box" style="background:#fafafa;border:1.5px solid #e5e7eb;"></span><small class="legend-text">No Data</small>
                </div>
            </div>

            {{-- Main Grid --}}
            <div class="card border-0 shadow-sm">
                <div class="d-md-none text-muted text-center py-1" style="font-size:0.7rem;border-bottom:1px solid #e2e8f0;">
                    <i class="fas fa-arrows-alt-h me-1"></i>Geser kiri-kanan untuk melihat semua tanggal
                </div>
                <div class="card-body p-0">
                    <div class="summary-scroll-wrap">
                        <table class="table table-bordered summary-table mb-0">
                            <thead>
                                <tr>
                                    <th class="sticky-col name-col bg-light" rowspan="2">Employee</th>
                                    @for ($d = 1; $d <= $daysInMonth; $d++)
                                        @php $info = $dayInfo[$d]; @endphp
                                        <th class="day-header text-center
                                            {{ $info['isSunday'] ? 'day-sunday' : '' }}
                                            {{ $info['national'] ? 'day-national' : '' }}
                                            {{ $info['company'] ? 'day-company-'.$info['company']->type : '' }}
                                            {{ !$info['isSunday'] ? 'day-clickable' : '' }}"
                                            title="{{ $info['national'] ? $info['national']->name : ($info['company'] ? $info['company']->name : 'Click to manage holiday') }}"
                                            @if(!$info['isSunday'])
                                                data-date="{{ $info['date'] }}"
                                                data-national-id="{{ $info['national'] ? $info['national']->id : '' }}"
                                                data-national-name="{{ $info['national'] ? $info['national']->name : '' }}"
                                                data-holiday-id="{{ $info['company'] ? $info['company']->id : '' }}"
                                                data-holiday-name="{{ $info['company'] ? $info['company']->name : '' }}"
                                                data-holiday-type="{{ $info['company'] ? $info['company']->type : 'free' }}"
                                                data-holiday-notes="{{ $info['company'] ? ($info['company']->notes ?? '') : '' }}"
                                            @endif
                                            >
                                            {{ $d }}
                                        </th>
                                    @endfor
                                    <th class="text-center summary-header" style="min-width:46px;background:#bbf7d0;color:#166534;">P</th>
                                    <th class="text-center summary-header" style="min-width:46px;background:#fde68a;color:#92400e;">L</th>
                                    <th class="text-center summary-header" style="min-width:46px;background:#fca5a5;color:#991b1b;">A</th>
                                    <th class="text-center summary-header" style="min-width:46px;background:#bfdbfe;color:#1e40af;">Cuti</th>
                                </tr>
                                <tr>
                                    @for ($d = 1; $d <= $daysInMonth; $d++)
                                        @php $info = $dayInfo[$d]; @endphp
                                        <th class="day-name text-center
                                            {{ $info['isSunday'] ? 'day-sunday' : '' }}
                                            {{ $info['national'] ? 'day-national' : '' }}
                                            {{ $info['company'] ? 'day-company-'.$info['company']->type : '' }}">
                                            {{ $info['dayName'] }}
                                        </th>
                                    @endfor
                                    <th class="text-center" style="font-size:0.65rem;background:#bbf7d0;color:#166534;">Present</th>
                                    <th class="text-center" style="font-size:0.65rem;background:#fde68a;color:#92400e;">Late</th>
                                    <th class="text-center" style="font-size:0.65rem;background:#fca5a5;color:#991b1b;">Alpha</th>
                                    <th class="text-center" style="font-size:0.65rem;background:#bfdbfe;color:#1e40af;">Leave</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $emp)
                                    @php $s = $summary[$emp->id]; @endphp
                                    <tr class="summary-row">
                                        <td class="sticky-col name-col">
                                            <div class="fw-semibold" style="font-size:0.78rem;">{{ $emp->name }}</div>
                                        </td>
                                        @for ($d = 1; $d <= $daysInMonth; $d++)
                                            @php
                                                $info   = $dayInfo[$d];
                                                $record = $dailiesMap->get($emp->id . '_' . $info['date'])?->first();
                                                $status = $record?->status;

                                                $initial = '';
                                                if ($info['isSunday']) {
                                                    $cellClass = 'cell-sunday';
                                                    $tooltip   = 'Minggu';
                                                } elseif ($info['national']) {
                                                    $cellClass = 'cell-national';
                                                    $tooltip   = $info['national']->name;
                                                } elseif ($info['company']) {
                                                    $ch = $info['company'];
                                                    $cellClass = match($ch->type) {
                                                        'paid_leave_deduction' => 'cell-company-paid',
                                                        'unpaid'               => 'cell-company-unpaid',
                                                        default                => 'cell-company-free',
                                                    };
                                                    $tooltip = $ch->name . ' (' . $ch->getTypeLabel() . ')';
                                                } elseif (!$status) {
                                                    $cellClass = 'cell-empty';
                                                    $tooltip   = 'No data';
                                                    $initial   = '';
                                                } else {
                                                    [$cellClass, $tooltip, $initial] = match($status) {
                                                        'Present'        => ['cell-present',     'Present',        'P'],
                                                        'Late'           => ['cell-late',        'Late',           'L'],
                                                        'Alpha'          => ['cell-alpha',       'Alpha',          'A'],
                                                        'Annual Leave'   => ['cell-annual',      'Annual Leave',   'Lv'],
                                                        'Sick Leave'     => ['cell-sick',        'Sick Leave',     'Sk'],
                                                        'Unpaid Leave'   => ['cell-unpaid',      'Unpaid Leave',   'Up'],
                                                        'Early Leave'    => ['cell-leave-other', 'Early Leave',    'El'],
                                                        'Permission Out' => ['cell-leave-other', 'Permission Out', 'Po'],
                                                        default          => ['cell-leave-other', $status,          'Ot'],
                                                    };
                                                }
                                            @endphp
                                            <td class="att-cell {{ $cellClass }}" title="{{ $tooltip }}">
                                                @if(!empty($initial))<span class="att-initial">{{ $initial }}</span>@endif
                                            </td>
                                        @endfor
                                        {{-- Summary counts --}}
                                        <td class="text-center fw-semibold count-present">{{ $s['present'] + $s['late'] }}</td>
                                        <td class="text-center fw-semibold count-late">{{ $s['late'] }}</td>
                                        <td class="text-center fw-semibold count-alpha">{{ $s['alpha'] }}</td>
                                        <td class="text-center fw-semibold count-leave">{{ $s['annual'] + $s['sick'] + $s['leave_other'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Edit National Holiday --}}
@can('hr.attendance.edit')
<div class="modal fade" id="editNationalHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fas fa-flag me-2 text-warning"></i>Edit Libur Nasional</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Perubahan ini akan mempengaruhi tampilan grid kehadiran. Gunakan fitur ini untuk pergeseran hari libur nasional.
                </div>
                <form id="editNationalHolidayForm">
                    @csrf
                    <input type="hidden" id="enh_id">
                    <div class="mb-2">
                        <label class="form-label small">Nama Hari Libur</label>
                        <input type="text" id="enh_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" id="enh_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary px-4 flex-grow-1" id="btnSaveNational">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger px-3" id="btnDeleteNational">
                            <i class="fas fa-trash me-1"></i> Hapus
                        </button>
                    </div>
                    <div id="editNationalResult" class="mt-2 small"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan

{{-- Modal: Quick Date Holiday Toggle --}}
@can('hr.attendance.edit')
<div class="modal fade" id="dateHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="dateHolidayTitle">Manage Holiday</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="small text-muted mb-3" id="dateHolidaySubtitle"></p>

                {{-- Remove existing holiday --}}
                <div id="removeHolidaySection" class="d-none">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fas fa-calendar-times me-1"></i>
                        This day is currently marked as a company holiday: <strong id="existingHolidayName"></strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger px-4 w-100 mb-2" id="btnRemoveHoliday">
                        <i class="fas fa-trash me-1"></i> Remove Holiday (mark as working day)
                    </button>
                    <hr class="my-2">
                    <p class="small text-muted">Or update:</p>
                </div>

                {{-- Add / Update form --}}
                <form id="quickHolidayForm">
                    @csrf
                    <input type="hidden" name="date" id="qh_date">
                    <input type="hidden" id="qh_existing_id">
                    <div class="mb-2">
                        <label class="form-label small">Holiday Name</label>
                        <input type="text" name="name" id="qh_name" class="form-control form-control-sm"
                               placeholder="e.g. Company Holiday" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Type</label>
                        <select name="type" id="qh_type" class="form-select form-select-sm">
                            <option value="free">Company Holiday (Free)</option>
                            <option value="paid_leave_deduction">Deduct Annual Leave</option>
                            <option value="unpaid">Unpaid Day Off</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Notes <span class="text-muted">(optional)</span></label>
                        <input type="text" name="notes" id="qh_notes" class="form-control form-control-sm" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-4 w-100" id="btnSaveHoliday">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                    <div id="quickHolidayResult" class="mt-2 small"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan

{{-- Modal: Company Holidays --}}
@can('hr.attendance.edit')
<div class="modal fade" id="manageHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Company Holidays — {{ $startOfMonth->isoFormat('MMMM YYYY') }}
                </h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Add form --}}
                <form id="addHolidayForm" class="border rounded p-3 mb-3 bg-light">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small">Tanggal</label>
                            <input type="date" name="date" id="hol_date" class="form-control form-control-sm"
                                   min="{{ $startOfMonth->format('Y-m-d') }}"
                                   max="{{ $startOfMonth->copy()->endOfMonth()->format('Y-m-d') }}"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Holiday Name</label>
                            <input type="text" name="name" id="hol_name" class="form-control form-control-sm"
                                   placeholder="e.g. Company Holiday" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Type</label>
                            <select name="type" id="hol_type" class="form-select form-select-sm">
                                <option value="free">Company Holiday (Free)</option>
                                <option value="paid_leave_deduction">Deduct Annual Leave</option>
                                <option value="unpaid">Unpaid Day Off</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Notes <span class="text-muted">(optional)</span></label>
                            <input type="text" name="notes" id="hol_notes" class="form-control form-control-sm" placeholder="Optional">
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-sm btn-primary px-4" id="addHolidayBtn">
                            <i class="fas fa-plus me-1"></i> Add
                        </button>
                        <span id="addHolidayResult" class="ms-2 small"></span>
                    </div>
                </form>

                {{-- Existing list --}}
                <div id="holidayListWrap">
                    @if($companyHolidaysList->isEmpty())
                        <p class="text-muted small text-center py-3">No company holidays this month.</p>
                    @else
                        <table class="table table-sm table-hover small">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Notes</th>
                                    <th>Created by</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="holidayListBody">
                                @foreach($companyHolidaysList as $ch)
                                    <tr id="hol-row-{{ $ch->id }}">
                                        <td>{{ $ch->date->format('d/m/Y') }}</td>
                                        <td>{{ $ch->name }}</td>
                                        <td>
                                            @php
                                                $badge = match($ch->type) {
                                                    'paid_leave_deduction' => 'bg-pink text-dark',
                                                    'unpaid'               => 'bg-warning text-dark',
                                                    default                => 'bg-purple text-white',
                                                };
                                            @endphp
                                            <span class="badge {{ $badge }} px-2">{{ $ch->getTypeLabel() }}</span>
                                        </td>
                                        <td>{{ $ch->notes ?? '—' }}</td>
                                        <td>{{ $ch->creator?->username ?? '—' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger border-0 px-2 btn-delete-holiday"
                                                    data-id="{{ $ch->id }}" data-name="{{ $ch->name }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync me-1"></i> Refresh Grid
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

<style>
/* Scrollable grid */
.summary-scroll-wrap {
    overflow-x: auto;
    max-height: 75vh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.summary-table {
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.72rem;
    min-width: max-content;
}

/* Grid lines */
.summary-table th,
.summary-table td {
    border-right: 1px solid #e2e8f0 !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
.summary-table thead tr:first-child th {
    border-top: 1px solid #e2e8f0 !important;
}
.summary-table td:first-child,
.summary-table th:first-child {
    border-left: 1px solid #e2e8f0 !important;
}

/* Sticky name column */
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 3;
    background: #fff;
    box-shadow: 2px 0 4px rgba(0,0,0,0.06);
}

.summary-table thead .sticky-col {
    z-index: 4;
    background: #f8fafc;
}

.name-col {
    min-width: 170px;
    padding: 6px 10px !important;
    white-space: nowrap;
}

/* Header row */
.summary-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8fafc;
    padding: 4px 2px !important;
    font-size: 0.7rem;
    text-align: center;
    white-space: nowrap;
}

.summary-table thead th.sticky-col { z-index: 5; }

.day-header { min-width: 30px; font-weight: 700; }
.day-clickable { cursor: pointer; }
.day-clickable:hover { filter: brightness(0.92); }
.day-name   { font-weight: 400; color: #64748b; font-size:0.62rem; }
.summary-header { min-width: 40px; }

/* Day header background states */
.day-sunday   { background: #cbd5e1 !important; color: #475569 !important; }
.day-national { background: #fed7aa !important; color: #c2410c !important; }
.day-company-free              { background: #99f6e4 !important; color: #0f766e !important; }
.day-company-paid_leave_deduction { background: #fbcfe8 !important; color: #9d174d !important; }
.day-company-unpaid            { background: #fcd34d !important; color: #92400e !important; }

/* Attendance cells */
.att-cell {
    padding: 0 !important;
    cursor: default;
    min-width: 28px;
    width: 28px;
    height: 28px;
    text-align: center;
    vertical-align: middle;
    position: relative;
}
.att-initial {
    font-size: 0.55rem;
    font-weight: 700;
    color: rgba(0,0,0,0.45);
    line-height: 1;
    display: block;
    text-align: center;
}

.cell-present     { background: #bbf7d0 !important; }
.cell-late        { background: #fde68a !important; }
.cell-alpha       { background: #fca5a5 !important; }
.cell-annual      { background: #bfdbfe !important; }
.cell-sick        { background: #a5f3fc !important; }
.cell-unpaid      { background: #e0e7ff !important; }
.cell-leave-other { background: #c4b5fd !important; }
.cell-sunday      { background: #cbd5e1 !important; }
.cell-national    { background: #fed7aa !important; }
.cell-company-free   { background: #99f6e4 !important; }
.cell-company-paid   { background: #fbcfe8 !important; }
.cell-company-unpaid { background: #fcd34d !important; }
.cell-empty       { background: #ffffff !important; }

/* Summary count columns */
.count-present { color: #166534; font-size:0.75rem; }
.count-late    { color: #854d0e; font-size:0.75rem; }
.count-alpha   { color: #991b1b; font-size:0.75rem; }
.count-leave   { color: #1e40af; font-size:0.75rem; }

/* Legend */
.legend-box {
    display: inline-block;
    width: 14px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

/* Legend scroll wrap */
.legend-scroll-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.legend-inner {
    flex-wrap: wrap;
    white-space: nowrap;
}
.legend-text {
    white-space: nowrap;
    margin-right: 4px;
}

/* Month label */
.month-label {
    font-size: 1rem;
    min-width: 120px;
}

/* Dept filter */
.dept-filter-select {
    min-width: 150px;
    max-width: 180px;
}

/* Row highlight on click */
.summary-row { cursor: pointer; }
.summary-row.row-highlighted td {
    outline: 2px solid #6366f1;
    outline-offset: -1px;
    position: relative;
}
.summary-row.row-highlighted .sticky-col {
    background: #eef2ff !important;
}

/* Badge helpers */
.bg-pink   { background-color: #fce7f3 !important; }
.bg-purple { background-color: #7c3aed !important; }
.btn-outline-purple {
    color: #7c3aed;
    border-color: #7c3aed;
}
.btn-outline-purple:hover {
    background: #7c3aed;
    color: #fff;
}

/* ── Mobile ── */
@media (max-width: 767.98px) {
    .container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .py-4 { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }

    .month-label { font-size: 0.85rem; min-width: 90px; }

    .dept-filter-select { min-width: 110px; max-width: 140px; font-size: 0.75rem; }

    .legend-inner { flex-wrap: nowrap; }
    .legend-text  { font-size: 0.65rem; }
    .legend-box   { width: 12px; height: 10px; }

    /* Tighter grid cells on mobile */
    .att-cell { min-width: 22px !important; width: 22px !important; height: 26px !important; }
    .day-header { min-width: 22px !important; }
    .summary-table { font-size: 0.66rem; }
    .name-col { min-width: 120px; }
    .summary-scroll-wrap { max-height: 62vh; }
}
</style>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Employee search
    document.getElementById('empSearchSummary').addEventListener('input', function () {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll('tr.summary-row').forEach(function (row) {
            const name = (row.querySelector('td .fw-semibold')?.textContent || '').toLowerCase();
            row.style.display = (!val || name.includes(val)) ? '' : 'none';
        });
    });

    // Department filter
    $('#deptFilter').on('change', function () {
        var deptId = $(this).val();
        var url    = "{{ route('attendance-logs.summary') }}";
        var params = new URLSearchParams({
            month: {{ $month }},
            year:  {{ $year }},
        });
        if (deptId) params.set('department_id', deptId);
        window.location.href = url + '?' + params.toString();
    });


    // Click on date header to quick-manage holiday (HR only)
    @can('hr.attendance.edit')
    $(document).on('click', '.day-clickable', function () {
        var date        = $(this).data('date');
        var nationalId  = $(this).data('national-id');
        var nationalName= $(this).data('national-name');
        var holidayId   = $(this).data('holiday-id');
        var holidayName = $(this).data('holiday-name');
        var holidayType = $(this).data('holiday-type') || 'free';
        var holidayNotes= $(this).data('holiday-notes') || '';

        // Jika ini libur nasional → buka modal edit national holiday
        if (nationalId) {
            $('#enh_id').val(nationalId);
            $('#enh_name').val(nationalName);
            $('#enh_date').val(date);
            $('#editNationalResult').html('');
            $('#btnSaveNational, #btnDeleteNational').prop('disabled', false);
            new bootstrap.Modal(document.getElementById('editNationalHolidayModal')).show();
            return;
        }

        var d = new Date(date);
        var label = d.toLocaleDateString('en-GB', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

        $('#dateHolidayTitle').text('Holiday — ' + label);
        $('#dateHolidaySubtitle').text(date);
        $('#qh_date').val(date);
        $('#qh_existing_id').val(holidayId || '');
        $('#quickHolidayResult').html('');

        if (holidayId) {
            $('#removeHolidaySection').removeClass('d-none');
            $('#existingHolidayName').text(holidayName);
            $('#qh_name').val(holidayName);
            $('#qh_type').val(holidayType);
            $('#qh_notes').val(holidayNotes);
            $('#btnSaveHoliday').html('<i class="fas fa-save me-1"></i> Update Holiday');
        } else {
            $('#removeHolidaySection').addClass('d-none');
            $('#qh_name').val('');
            $('#qh_type').val('free');
            $('#qh_notes').val('');
            $('#btnSaveHoliday').html('<i class="fas fa-plus me-1"></i> Mark as Holiday');
        }

        var modal = new bootstrap.Modal(document.getElementById('dateHolidayModal'));
        modal.show();
    });

    // Remove holiday via quick modal
    $('#btnRemoveHoliday').on('click', function () {
        var id = $('#qh_existing_id').val();
        if (!id) return;
        $(this).prop('disabled', true);
        $.ajax({
            url: "{{ url('attendance-logs/company-holidays') }}/" + id,
            type: 'POST',
            data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('dateHolidayModal')).hide();
                location.reload();
            },
            error: function () {
                alert('Failed to remove.');
                $('#btnRemoveHoliday').prop('disabled', false);
            }
        });
    });

    // Save/update holiday via quick modal
    $('#quickHolidayForm').on('submit', function (e) {
        e.preventDefault();
        var $btn    = $('#btnSaveHoliday');
        var $result = $('#quickHolidayResult');
        $btn.prop('disabled', true);
        $result.html('<span class="text-muted">Saving...</span>');

        $.ajax({
            url: "{{ route('attendance-logs.company-holidays.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('dateHolidayModal')).hide();
                location.reload();
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Error saving.';
                $result.html('<span class="text-danger">' + msg + '</span>');
                $btn.prop('disabled', false);
            }
        });
    });

    $('#dateHolidayModal').on('hidden.bs.modal', function () {
        $('#quickHolidayResult').html('');
        $('#btnRemoveHoliday').prop('disabled', false);
        $('#btnSaveHoliday').prop('disabled', false);
    });

    // Save national holiday changes
    $('#editNationalHolidayForm').on('submit', function (e) {
        e.preventDefault();
        var id   = $('#enh_id').val();
        var $btn = $('#btnSaveNational');
        $btn.prop('disabled', true);
        $('#editNationalResult').html('<span class="text-muted">Menyimpan...</span>');

        $.ajax({
            url: "{{ url('attendance-logs/national-holidays') }}/" + id,
            type: 'POST',
            data: {
                _method: 'PUT',
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: $('#enh_name').val(),
                date: $('#enh_date').val(),
            },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('editNationalHolidayModal')).hide();
                location.reload();
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Gagal menyimpan.';
                $('#editNationalResult').html('<span class="text-danger">' + msg + '</span>');
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete national holiday
    $('#btnDeleteNational').on('click', function () {
        if (!confirm('Hapus hari libur nasional ini? Tanggal akan dianggap hari kerja biasa.')) return;
        var id   = $('#enh_id').val();
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: "{{ url('attendance-logs/national-holidays') }}/" + id,
            type: 'POST',
            data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('editNationalHolidayModal')).hide();
                location.reload();
            },
            error: function () {
                alert('Gagal menghapus.');
                $btn.prop('disabled', false);
            }
        });
    });
    @endcan

    // Row highlight on name cell click
    $(document).on('click', '.summary-row .name-col', function () {
        var $row = $(this).closest('tr');
        var wasHighlighted = $row.hasClass('row-highlighted');
        $('.summary-row').removeClass('row-highlighted');
        if (!wasHighlighted) {
            $row.addClass('row-highlighted');
        }
    });

    // Add company holiday
    $('#addHolidayForm').on('submit', function (e) {
        e.preventDefault();
        var $btn    = $('#addHolidayBtn');
        var $result = $('#addHolidayResult');
        $btn.prop('disabled', true);
        $result.html('<span class="text-muted">Saving...</span>');

        $.ajax({
            url: "{{ route('attendance-logs.company-holidays.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                $result.html('<span class="text-success"><i class="fas fa-check"></i> Saved. Refresh to see changes.</span>');
                $btn.prop('disabled', false);
                $('#addHolidayForm')[0].reset();
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Error saving.';
                $result.html('<span class="text-danger">' + msg + '</span>');
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete company holiday
    $(document).on('click', '.btn-delete-holiday', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Delete "' + name + '"?')) return;

        var $row = $('#hol-row-' + id);
        $.ajax({
            url: "{{ url('attendance-logs/company-holidays') }}/" + id,
            type: 'POST',
            data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                $row.fadeOut(300, function () { $(this).remove(); });
            },
            error: function () {
                alert('Failed to delete.');
            }
        });
    });

});
</script>
@endpush
