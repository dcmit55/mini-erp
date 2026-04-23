@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@14/dist/handsontable.full.min.css">
    <style>
        .bg-gradient-mascot {
            background: linear-gradient(135deg, #f9d423 0%, #ff4e50 100%);
        }

        .jo-planner-card {
            cursor: pointer;
            border: 2px solid #dee2e6;
            transition: all 0.25s;
            border-radius: 0.5rem;
        }

        .jo-planner-card:hover:not(.jo-active) {
            border-color: #ffc107;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .jo-planner-card.jo-active {
            border-color: #ff9800 !important;
            background: linear-gradient(135deg, rgba(249, 212, 35, 0.12) 0%, rgba(255, 78, 80, 0.08) 100%);
            box-shadow: 0 4px 14px rgba(249, 212, 35, 0.35);
            transform: translateY(-2px);
        }

        .jo-planner-card.has-plan {
            border-color: #198754;
        }

        .jo-planner-card.has-plan.jo-active {
            border-color: #ff9800 !important;
        }

        #hot-container {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            min-height: 240px;
        }

        .btn-xs {
            padding: 0.15rem 0.5rem;
            font-size: 0.72rem;
        }

        #plan-panel {
            position: sticky;
            top: 72px;
        }

        .handsontable td.row-checked-bg {
            background-color: rgba(25, 135, 84, 0.12) !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
            <div>
                <h2 class="mb-0 fw-semibold" style="font-size:1.4rem;">
                    <i class="bi bi-calendar2-check text-warning me-2"></i>Timing Planner
                </h2>
                <small class="text-muted">Set rencana karyawan per Job Order — besok pagi tinggal klik JO, karyawan otomatis
                    terpilih</small>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <a href="{{ route('mascot-timing.index') }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-stopwatch me-1"></i> Mascot Timing
                </a>
            </div>
        </div>

        <div class="alert alert-info border-0 py-2 mb-4">
            <i class="bi bi-lightbulb-fill me-2"></i>
            <strong>Cara pakai:</strong> Pilih JO di sebelah kiri → centang karyawan di tabel kanan →
            <strong>Simpan Plan</strong>. Besok pagi di Mascot Timing, klik JO → karyawan otomatis terpilih.
        </div>

        <div class="row g-4">

            {{-- ══ LEFT — JO Cards ══ --}}
            <div class="col-lg-6 col-xl-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-gradient-mascot text-white d-flex align-items-center gap-2">
                        <h5 class="mb-0 text-white"><i class="bi bi-list-task me-2"></i>Job Orders</h5>
                        <span class="badge bg-white text-dark ms-auto" id="jo-count">{{ $jobOrders->count() }}</span>
                    </div>
                    <div class="card-body p-3">
                        <input type="text" id="jo-filter" class="form-control form-control-sm mb-3"
                            placeholder="Cari job order atau project...">

                        <div id="jo-cards-grid" class="row g-2" style="max-height:68vh;overflow-y:auto;padding-right:4px;">
                            @forelse($jobOrders as $jo)
                                @php
                                    $deliveryDate = $jo->delivery_date
                                        ? \Carbon\Carbon::parse($jo->delivery_date)
                                        : null;
                                    $daysLeft = $deliveryDate
                                        ? (int) now()
                                            ->startOfDay()
                                            ->diffInDays($deliveryDate->copy()->startOfDay(), false)
                                        : null;
                                    $currentPlan = $plans[$jo->id] ?? collect();
                                    $hasPlan = $currentPlan->isNotEmpty();
                                    if ($daysLeft !== null) {
                                        if ($daysLeft < 0) {
                                            $badge = 'bg-danger';
                                            $dlabel = 'OVERDUE ' . abs($daysLeft) . 'd';
                                        } elseif ($daysLeft === 0) {
                                            $badge = 'bg-danger';
                                            $dlabel = 'DUE TODAY';
                                        } elseif ($daysLeft <= 3) {
                                            $badge = 'bg-warning text-dark';
                                            $dlabel = $daysLeft . 'd left';
                                        } else {
                                            $badge = 'bg-info text-dark';
                                            $dlabel = $daysLeft . 'd left';
                                        }
                                    }
                                @endphp
                                @php
                                    $plannedRowsData = $currentPlan
                                        ->map(function ($p) {
                                            return [
                                                'employee_id' => $p->employee_id,
                                                'task' => $p->task ?? '',
                                                'stage' => $p->stage ?? '',
                                                'session_type' => $p->session_type ?? '',
                                            ];
                                        })
                                        ->values()
                                        ->toArray();
                                @endphp
                                <div class="col-sm-6 col-md-4 jo-card-wrapper" data-jo-name="{{ strtolower($jo->name) }}"
                                    data-jo-project="{{ strtolower($jo->project->name ?? '') }}">
                                    <div class="jo-planner-card p-2 h-100 {{ $hasPlan ? 'has-plan' : '' }}"
                                        data-jo-id="{{ $jo->id }}" data-jo-label="{{ $jo->name }}"
                                        data-project="{{ $jo->project->name ?? 'N/A' }}"
                                        data-last-stage="{{ $lastStages[$jo->id] ?? 0 }}"
                                        data-planned-ids='@json($currentPlan->pluck('employee_id')->toArray())'
                                        data-planned-rows='@json($plannedRowsData)'>

                                        @if ($hasPlan)
                                            <div class="text-end mb-1">
                                                <span class="badge bg-success" style="font-size:0.58rem;">
                                                    <i class="bi bi-calendar2-check me-1"></i>PLANNED ·
                                                    {{ $currentPlan->count() }} Emp
                                                </span>
                                            </div>
                                        @endif

                                        <div class="fw-semibold lh-sm mb-1" style="font-size:0.82rem;">{{ $jo->name }}
                                        </div>
                                        <div class="text-muted mb-1" style="font-size:0.68rem;">
                                            <i class="bi bi-folder2 me-1"></i>{{ $jo->project->name ?? 'N/A' }}
                                        </div>

                                        @if ($deliveryDate)
                                            <span class="badge {{ $badge }}" style="font-size:0.6rem;">
                                                <i class="bi bi-clock me-1"></i>{{ $dlabel }}
                                            </span>
                                            <div class="text-muted" style="font-size:0.6rem;margin-top:2px;">
                                                {{ $deliveryDate->format('d M Y') }}
                                            </div>
                                        @else
                                            <span class="badge bg-secondary" style="font-size:0.6rem;">No deadline</span>
                                        @endif

                                        @if ($hasPlan)
                                            <div class="text-muted mt-1" style="font-size:0.6rem;">
                                                by {{ $currentPlan->first()?->createdBy?->username ?? '-' }}
                                                · {{ $currentPlan->first()?->updated_at?->format('d M H:i') ?? '' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0">Tidak ada job order aktif.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ RIGHT — Plan Editor ══ --}}
            <div class="col-lg-6 col-xl-5">
                <div id="plan-panel" class="card shadow-sm border-0">
                    <div class="card-header bg-gradient-mascot text-white d-flex align-items-center gap-2">
                        <h5 class="mb-0 text-white"><i class="bi bi-pencil-square me-2"></i>Edit Plan</h5>
                        <span id="plan-jo-badge" class="badge bg-white text-dark ms-2 d-none"></span>
                    </div>
                    <div class="card-body p-3">

                        <div id="plan-placeholder" class="text-center text-muted py-5">
                            <i class="bi bi-arrow-left-circle fs-2 d-block mb-2"></i>
                            Pilih Job Order di sebelah kiri untuk mengatur plan
                        </div>

                        <div id="plan-editor-area" class="d-none">
                            <div class="alert alert-warning py-2 mb-3">
                                <div class="fw-semibold" id="plan-jo-name" style="font-size:0.9rem;"></div>
                                <div class="text-muted small" id="plan-jo-project"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-primary btn-xs" id="hot-add-row">
                                        <i class="bi bi-plus-lg me-1"></i>Tambah Baris
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-xs" id="hot-remove-row">
                                        <i class="bi bi-dash-lg me-1"></i>Hapus Baris
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-xs" id="hot-clear-all">
                                        <i class="bi bi-x-lg me-1"></i>Clear All
                                    </button>
                                </div>
                                <small class="text-muted" id="hot-selected-count">0 karyawan diinput</small>
                            </div>

                            <div id="hot-container"></div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="button" id="btn-save-plan" class="btn btn-success flex-grow-1">
                                    <i class="bi bi-floppy me-1"></i>Submit Plan
                                </button>
                                <button type="button" id="btn-clear-plan" class="btn btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i> Delete Plan
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14/dist/handsontable.full.min.js"></script>
    <script>
        var ALL_EMPLOYEES = {!! json_encode(
            $employees->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'name' => $e->name,
                        'position' => $e->position ?? '',
                        'department' => optional($e->department)->name ?? '',
                    ];
                })->values(),
        ) !!};
    </script>
    <script>
        (function() {
            'use strict';

            // ── Custom time editor using native <input type="time"> ──
            const TimeEditor = Handsontable.editors.TextEditor.prototype;
            class NativeTimeEditor extends Handsontable.editors.BaseEditor {
                init() {
                    this.input = this.hot.rootDocument.createElement('input');
                    this.input.type = 'time';
                    this.input.style.cssText =
                        'position:absolute;top:0;left:0;width:100%;height:100%;border:none;padding:2px 4px;font-size:13px;box-sizing:border-box;background:#fff;z-index:9999;';
                    this.input.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === 'Tab') {
                            this.finishEditing(false);
                        } else if (e.key === 'Escape') {
                            this.cancelChanges();
                            this.hot.deselectCell();
                        }
                    });
                }
                open() {
                    const {
                        top,
                        left,
                        width,
                        height
                    } = this.TD.getBoundingClientRect();
                    const rootRect = this.hot.rootElement.getBoundingClientRect();
                    this.input.style.top = (top - rootRect.top) + 'px';
                    this.input.style.left = (left - rootRect.left) + 'px';
                    this.input.style.width = width + 'px';
                    this.input.style.height = height + 'px';
                    this.hot.rootElement.appendChild(this.input);
                    this.input.value = this.originalValue || '';
                    this.input.focus();
                }
                close() {
                    if (this.input.parentNode) this.input.parentNode.removeChild(this.input);
                }
                getValue() {
                    return this.input.value;
                }
                setValue(val) {
                    this.originalValue = val || '';
                    if (this.input) this.input.value = val || '';
                }
                focus() {
                    this.input.focus();
                }
            }
            Handsontable.editors.registerEditor('nativeTime', NativeTimeEditor);

            const EMP_NAMES = ALL_EMPLOYEES.map(e => e.name);
            const EMP_MAP = {}; // name -> employee object
            ALL_EMPLOYEES.forEach(e => EMP_MAP[e.name] = e);

            let selectedJoId = null;
            let hotInstance = null;

            const ALL_STAGES = [
                '1: Design & Prototyping',
                '2: Structure Approval',
                '3: Structure & Sample',
                '4: Visual Review & Paint Prep',
                '5: Adjustment & Finishing (Structure)',
                '6: Final Structure Approval',
                '7: Wrapping & Painting',
                '8: Wrapping Approval',
                '9: Finishing & Approval',
                '10: Final QC & Shipping',
            ];

            const EMPTY_ROW = (defaultStage = '') => ({
                name: '',
                position: '',
                department: '',
                task: '',
                stage: defaultStage,
                session_type: '',
                emp_id: null
            });
            const MIN_ROWS = 10;

            function buildTableData(plannedRows, defaultStage = '') {
                // plannedRows: [{employee_id, task, stage, session_type}] or plain id array (legacy)
                const rows = plannedRows
                    .map(r => {
                        const id = typeof r === 'object' ? r.employee_id : r;
                        const emp = ALL_EMPLOYEES.find(e => e.id === Number(id));
                        if (!emp) return null;
                        return {
                            name: emp.name,
                            position: emp.position,
                            department: emp.department,
                            task: typeof r === 'object' ? (r.task || '') : '',
                            stage: typeof r === 'object' ? (r.stage || defaultStage) : defaultStage,
                            session_type: typeof r === 'object' ? (r.session_type || '') : '',
                            emp_id: emp.id
                        };
                    })
                    .filter(Boolean);
                // Pad to MIN_ROWS
                while (rows.length < MIN_ROWS) rows.push(EMPTY_ROW(defaultStage));
                return rows;
            }

            function getTableData() {
                if (!hotInstance) return [];
                return hotInstance.getSourceData();
            }

            // ── empIdMap: track row -> emp_id OUTSIDE of HOT, 100% reliable ──
            let empIdMap = {}; // { rowIndex: empId|null }

            function syncEmpIdMap() {
                // Rebuild from current source data names
                empIdMap = {};
                const src = hotInstance ? hotInstance.getSourceData() : [];
                src.forEach((row, i) => {
                    const emp = row.name ? EMP_MAP[row.name] || null : null;
                    empIdMap[i] = emp ? emp.id : null;
                });
            }

            function updateSelectedCount() {
                const n = Object.values(empIdMap).filter(id => !!id).length;
                $('#hot-selected-count').text(n + ' karyawan diinput');
            }

            function getValidEmpIds() {
                return Object.values(empIdMap).filter(id => !!id);
            }

            function getValidRows() {
                if (!hotInstance) return [];
                const src = hotInstance.getSourceData();
                const result = [];
                src.forEach((row, i) => {
                    const empId = empIdMap[i];
                    if (!empId) return;
                    result.push({
                        employee_id: empId,
                        task: row.task || '',
                        stage: row.stage || '',
                        session_type: row.session_type || '',
                    });
                });
                return result;
            }

            function initHOT(plannedIds, lastStage = 0) {
                // Compute allowed stages: 1 step back from lastStage, current, all forward
                // lastStage = 0 means no history → show all stages
                const minStageNum = lastStage > 1 ? lastStage - 1 : (lastStage === 1 ? 1 : 0);
                const stageSource = lastStage > 0 ?
                    ['', ...ALL_STAGES.slice(minStageNum - 1)] :
                    ['', ...ALL_STAGES];

                // Default pre-fill: the last stage of the JO (so planner opens on current stage)
                const defaultStage = lastStage > 0 ? ALL_STAGES[lastStage - 1] : '';

                const tableData = buildTableData(plannedIds, defaultStage);

                if (hotInstance) {
                    hotInstance.destroy();
                    hotInstance = null;
                }

                hotInstance = new Handsontable(document.getElementById('hot-container'), {
                    data: tableData,
                    licenseKey: 'non-commercial-and-evaluation',
                    height: 360,
                    stretchH: 'all',
                    rowHeaders: true,
                    colHeaders: ['Employee Name', 'Position', 'Department', 'Task', 'Stage',
                        'Session Type'
                    ],
                    columns: [{
                            data: 'name',
                            type: 'autocomplete',
                            source: EMP_NAMES,
                            strict: true,
                            filter: true,
                            allowInvalid: false,
                            width: 200,
                        },
                        {
                            data: 'position',
                            type: 'text',
                            width: 140,
                            className: 'htDimmed'
                        },
                        {
                            data: 'department',
                            type: 'text',
                            width: 140,
                            className: 'htDimmed'
                        },
                        {
                            data: 'task',
                            type: 'text',
                            placeholder: 'e.g., Sculpting, Sewing...',
                            renderer(hotInstance, td, row, col, prop, value) {
                                Handsontable.renderers.TextRenderer.apply(this, arguments);
                                td.textContent = value || '';
                                td.style.color = value ? '#333' : '#aaa';
                                if (!value) td.textContent = 'e.g., Sculpting...';
                            },
                            width: 160,
                        },
                        {
                            data: 'stage',
                            type: 'dropdown',
                            source: stageSource,
                            allowInvalid: false,
                            width: 220,
                        },
                        {
                            data: 'session_type',
                            type: 'dropdown',
                            source: ['', 'mass_production', 'repair'],
                            allowInvalid: false,
                            renderer(hotInstance, td, row, col, prop, value) {
                                Handsontable.renderers.TextRenderer.apply(this, arguments);
                                if (value === 'repair') {
                                    td.style.background = '#fff3e0';
                                    td.style.color = '#e65100';
                                    td.style.fontWeight = '600';
                                    td.textContent = 'Repair';
                                } else if (value === 'mass_production') {
                                    td.style.background = '';
                                    td.style.color = '#198754';
                                    td.style.fontWeight = '600';
                                    td.textContent = 'Mass Production';
                                } else {
                                    td.style.background = '#fffde7';
                                    td.style.color = '#999';
                                    td.style.fontWeight = '400';
                                    td.textContent = '— select type —';
                                }
                            },
                            width: 140,
                        },
                    ],
                    manualColResize: true,
                    contextMenu: ['row_above', 'row_below', '---------', 'remove_row'],
                    outsideClickDeselects: false,
                    afterChange(changes, source) {
                        if (!changes || source === 'loadData' || source === '_fill') return;
                        const fills = [];
                        changes.forEach(([row, prop, , newVal]) => {
                            if (prop !== 'name') return;
                            const emp = EMP_MAP[newVal] || null;
                            // ── Update empIdMap (our reliable tracker) ──
                            empIdMap[row] = emp ? emp.id : null;
                            fills.push([row, 'position', emp ? emp.position : '']);
                            fills.push([row, 'department', emp ? emp.department : '']);
                        });
                        if (fills.length) {
                            hotInstance.setDataAtRowProp(fills, '_fill');
                        }
                        updateSelectedCount();
                    },
                    afterRemoveRow(index, amount) {
                        // Rebuild map after row deletion
                        syncEmpIdMap();
                        updateSelectedCount();
                    },
                    afterCreateRow(index, amount) {
                        // New rows start with no employee
                        for (let i = index; i < index + amount; i++) empIdMap[i] = null;
                        updateSelectedCount();
                    },
                });

                // Initialize empIdMap from pre-filled planned data
                syncEmpIdMap();
                updateSelectedCount();
            }

            /* ── JO search ── */
            $('#jo-filter').on('input', function() {
                const q = $(this).val().toLowerCase().trim();
                let n = 0;
                $('.jo-card-wrapper').each(function() {
                    const show = !q ||
                        ($(this).data('jo-name') || '').includes(q) ||
                        ($(this).data('jo-project') || '').includes(q);
                    $(this).toggle(show);
                    if (show) n++;
                });
                $('#jo-count').text(n);
            });

            /* ── JO card click ── */
            $(document).on('click', '.jo-planner-card', function() {
                const $card = $(this);
                const joId = $card.data('jo-id');

                if ($card.hasClass('jo-active')) {
                    $card.removeClass('jo-active');
                    selectedJoId = null;
                    $('#plan-placeholder').removeClass('d-none');
                    $('#plan-editor-area').addClass('d-none');
                    $('#plan-jo-badge').addClass('d-none');
                    return;
                }

                $('.jo-planner-card').removeClass('jo-active');
                $card.addClass('jo-active');
                selectedJoId = joId;

                $('#plan-jo-badge').text($card.data('jo-label')).removeClass('d-none');
                $('#plan-jo-name').text($card.data('jo-label'));
                $('#plan-jo-project').text('Project: ' + $card.data('project'));

                $('#plan-placeholder').addClass('d-none');
                $('#plan-editor-area').removeClass('d-none');

                initHOT($card.data('planned-rows') || $card.data('planned-ids') || [], $card.data(
                    'last-stage') || 0);
            });

            /* ── Toolbar ── */
            $('#hot-add-row').on('click', function() {
                if (!hotInstance) return;
                hotInstance.alter('insert_row_below');
            });
            $('#hot-remove-row').on('click', function() {
                if (!hotInstance) return;
                const sel = hotInstance.getSelected();
                if (sel && sel.length) {
                    const row = sel[0][0];
                    if (hotInstance.countRows() > 1) hotInstance.alter('remove_row', row);
                } else {
                    const last = hotInstance.countRows() - 1;
                    if (last >= 0) hotInstance.alter('remove_row', last);
                }
                updateSelectedCount();
            });
            $('#hot-clear-all').on('click', function() {
                if (!hotInstance) return;
                Swal.fire({
                        icon: 'warning',
                        title: 'Kosongkan semua baris?',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                        cancelButtonText: 'Batal'
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        const n = hotInstance.countRows();
                        const clears = [];
                        for (let i = 0; i < n; i++) {
                            clears.push([i, 'name', ''], [i, 'position', ''], [i, 'department', ''], [i,
                                'task', ''
                            ], [i, 'stage', '']);
                            empIdMap[i] = null;
                        }
                        hotInstance.setDataAtRowProp(clears, '_fill');
                        updateSelectedCount();
                    });
            });

            /* ── Save ── */
            $('#btn-save-plan').on('click', function() {
                if (!selectedJoId) return;
                const planRows = getValidRows();
                const empIds = getValidEmpIds();
                if (planRows.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'Input minimal 1 karyawan' });
                    return;
                }
                // Validate required fields per row
                const missing = [];
                planRows.forEach((row, i) => {
                    const fields = [];
                    if (!row.task) fields.push('Task');
                    if (!row.stage) fields.push('Stage');
                    if (!row.session_type) fields.push('Session Type');
                    if (fields.length) missing.push(`Row ${i + 1}: ${fields.join(', ')}`);
                });
                if (missing.length) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data belum lengkap',
                        html: `Kolom berikut harus diisi:<br><ul class="text-start mt-2">${missing.map(m => `<li>${m}</li>`).join('')}</ul>`,
                    });
                    return;
                }
                const btn = $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
                $.ajax({
                    url: '{{ route('timing-planner.save') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        job_order_id: selectedJoId,
                        rows: planRows
                    },
                    success(res) {
                        if (!res.success) return;
                        const $card = $(`.jo-planner-card[data-jo-id="${selectedJoId}"]`);
                        $card.addClass('has-plan').data('planned-ids', empIds).data('planned-rows',
                            planRows);
                        let $b = $card.find('.badge.bg-success').first();
                        const html =
                            `<i class="bi bi-calendar2-check me-1"></i>PLANNED · ${empIds.length} emp`;
                        if ($b.length) {
                            $b.html(html);
                        } else {
                            $card.prepend(
                                `<div class="text-end mb-1"><span class="badge bg-success" style="font-size:0.58rem;">${html}</span></div>`
                            );
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Plan Disimpan!',
                            html: `${empIds.length} karyawan direncanakan.<br><small class="text-muted">${res.employee_names ?? ''}</small>`,
                            timer: 2500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    },
                    error(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Gagal menyimpan.'
                        });
                    },
                    complete() {
                        btn.prop('disabled', false).html(
                            '<i class="bi bi-floppy me-1"></i>Simpan Plan');
                    },
                });
            });

            /* ── Clear ── */
            $('#btn-clear-plan').on('click', function() {
                if (!selectedJoId) return;
                Swal.fire({
                        icon: 'warning',
                        title: 'Hapus Plan?',
                        text: 'Plan JO ini akan dihapus. Sistem akan fallback ke sesi terakhir.',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal'
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        $.ajax({
                            url: '{{ route('timing-planner.clear') }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                job_order_id: selectedJoId
                            },
                            success(res) {
                                if (!res.success) return;
                                if (hotInstance) {
                                    hotInstance.getSourceData().forEach(r => {
                                        r.name = '';
                                        r.position = '';
                                        r.department = '';
                                        r.emp_id = null;
                                    });
                                    hotInstance.render();
                                }
                                updateSelectedCount();
                                const $card = $(`.jo-planner-card[data-jo-id="${selectedJoId}"]`);
                                $card.removeClass('has-plan').data('planned-ids', []);
                                $card.find('.badge.bg-success').parent().remove();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Plan dihapus',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            },
                            error(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message || 'Gagal hapus.'
                                });
                            },
                        });
                    });
            });

        })();
    </script>
@endpush
