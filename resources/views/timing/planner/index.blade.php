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
            min-height: 300px;
        }

        .btn-xs {
            padding: 0.15rem 0.5rem;
            font-size: 0.72rem;
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
            <div class="ms-lg-auto d-flex align-items-center gap-2">
                {{-- <label class="mb-0 text-muted small fw-semibold" for="planning-date-global">
                    <i class="bi bi-calendar3 me-1"></i>Tanggal Plan:
                </label> --}}
                {{-- <input type="date" id="planning-date-global" class="form-control form-control-sm" style="width:150px;"
                    value="{{ $planningDate }}"
                    onchange="window.location.href='{{ route('timing-planner.index') }}?date='+this.value"> --}}

                <a href="{{ route('mascot-timing.index') }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-stopwatch me-1"></i> Mascot Timing
                </a>
            </div>
        </div>

        {{-- <div class="alert alert-info border-0 py-2 mb-4">
            <i class="bi bi-lightbulb-fill me-2"></i>
            <strong>Cara pakai:</strong> Pilih tanggal di kanan atas → Klik JO → input karyawan → <strong>Simpan
                Plan</strong>.
            Di Mascot/Costume Timing, plan tanggal hari ini otomatis dipakai (fallback ke plan lama jika belum ada).
        </div> --}}

        <div class="row g-4">

            {{-- ══ FULL WIDTH — JO Cards ══ --}}
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-gradient-mascot text-white d-flex align-items-center gap-2">
                        <h5 class="mb-0 text-white"><i class="bi bi-list-task me-2"></i>Job Orders</h5>
                        <span class="badge bg-white text-dark ms-auto" id="jo-count">{{ $jobOrders->count() }}</span>
                    </div>
                    <div class="card-body p-3">
                        <input type="text" id="jo-filter" class="form-control form-control-sm mb-3"
                            placeholder="Cari job order atau project...">

                        <div id="jo-cards-grid" class="row g-2" style="max-height:72vh;overflow-y:auto;padding-right:4px;">
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
                                                'parts' => $p->parts ?? '',
                                                'stage' => $p->stage ?? '',
                                                'session_type' => $p->session_type ?? '',
                                            ];
                                        })
                                        ->values()
                                        ->toArray();
                                @endphp
                                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2 jo-card-wrapper"
                                    data-jo-name="{{ strtolower($jo->name) }}"
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

        </div>
    </div>

    {{-- ══ Plan Editor Modal (full-width) ══ --}}
    <div class="modal fade" id="planEditorModal" tabindex="-1" aria-labelledby="planEditorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down modal-xl" style="max-width:96vw;">
            <div class="modal-content">
                <div class="modal-header bg-gradient-mascot text-white py-2">
                    <div>
                        <h5 class="modal-title mb-0 text-white" id="planEditorModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>Edit Plan
                        </h5>
                        <small id="plan-jo-project" class="opacity-75" style="font-size:.78rem;"></small>
                    </div>
                    <span id="plan-jo-badge" class="badge bg-white text-dark ms-3 fs-6"></span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">

                    <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                        <div id="plan-jo-name" class="fw-semibold text-warning" style="font-size:.95rem;"></div>
                        <div class="d-flex gap-1 ms-auto">
                            <button type="button" class="btn btn-outline-primary btn-xs" id="hot-add-row">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Baris
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-xs" id="hot-remove-row">
                                <i class="bi bi-dash-lg me-1"></i>Hapus Baris
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-xs" id="hot-clear-all">
                                <i class="bi bi-x-lg me-1"></i>Clear All
                            </button>
                            <small class="text-muted ms-2 align-self-center" id="hot-selected-count">0 karyawan
                                diinput</small>
                        </div>
                    </div>

                    <div id="hot-container"></div>

                </div>
                <div class="modal-footer py-2">
                    <button type="button" id="btn-clear-plan" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i>Delete Plan
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="btn-save-plan" class="btn btn-success btn-sm">
                        <i class="bi bi-floppy me-1"></i>Submit Plan
                    </button>
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

        // Stage types with their stages — passed from server, no hardcoded list
        var STAGE_TYPES_MAP = {!! json_encode(
            $stageTypes->mapWithKeys(function ($st) {
                return [
                    $st->id => [
                        'id' => $st->id,
                        'name' => $st->name,
                        'stages' => $st->activeStages->map(
                                fn($s) => [
                                    'id' => $s->id,
                                    'name' => $s->name,
                                    'sequence' => $s->sequence,
                                ],
                            )->values(),
                    ],
                ];
            }),
        ) !!};

        var STAGE_TYPE_NAMES = {!! json_encode($stageTypes->pluck('name', 'id')) !!};
    </script>
    <script>
        (function() {
            'use strict';

            const EMP_NAMES = ALL_EMPLOYEES.map(e => e.name);
            const EMP_MAP = {};
            ALL_EMPLOYEES.forEach(e => EMP_MAP[e.name] = e);

            // ── Stage types & stages (dynamic, from DB, no hardcoded list) ──
            const STAGE_TYPE_LIST = Object.values(STAGE_TYPES_MAP);
            const STAGE_TYPE_NAME_SOURCE = ['', ...STAGE_TYPE_LIST.map(st => st.name)];

            // Map stage_type name → list of stage names (for HOT dropdown source)
            function getStageNamesForType(stageTypeName) {
                const st = STAGE_TYPE_LIST.find(s => s.name === stageTypeName);
                if (!st) return [''];
                return ['', ...st.stages.map(s => `${s.sequence}: ${s.name}`)];
            }

            // Map stage_type name → map of "seq: name" → stage_id
            function getStageIdMap(stageTypeName) {
                const st = STAGE_TYPE_LIST.find(s => s.name === stageTypeName);
                if (!st) return {};
                const map = {};
                st.stages.forEach(s => {
                    map[`${s.sequence}: ${s.name}`] = s.id;
                });
                return map;
            }

            // Resolve stage_id + stageTypeName from stored data (edit mode)
            function resolveStageDisplay(stageTypeId, stageId) {
                if (!stageTypeId || !stageId) return {
                    typeName: '',
                    stageName: ''
                };
                const st = STAGE_TYPES_MAP[stageTypeId];
                if (!st) return {
                    typeName: '',
                    stageName: ''
                };
                const stg = st.stages.find(s => s.id === stageId);
                return {
                    typeName: st.name,
                    stageName: stg ? `${stg.sequence}: ${stg.name}` : '',
                };
            }

            let selectedJoId = null;
            let hotInstance = null;

            // Track per-row stage dropdown sources (since each row can have different type)
            let rowStageSources = {}; // rowIndex -> ['', 'stage name', ...]

            const EMPTY_ROW = () => ({
                name: '',
                position: '',
                department: '',
                task: '',
                parts: '',
                stage_type: '',
                stage: '',
                session_type: '',
            });
            const MIN_ROWS = 10;

            function buildTableData(plannedRows) {
                const rows = plannedRows
                    .map(r => {
                        const id = typeof r === 'object' ? r.employee_id : r;
                        const emp = ALL_EMPLOYEES.find(e => e.id === Number(id));
                        if (!emp) return null;
                        // Resolve display values from IDs (edit mode support)
                        const resolved = resolveStageDisplay(
                            r.stage_type_id || null,
                            r.stage_id || null
                        );
                        return {
                            name: emp.name,
                            position: emp.position,
                            department: emp.department,
                            task: r.task || '',
                            parts: r.parts || '',
                            stage_type: resolved.typeName || '',
                            stage: resolved.stageName || '',
                            session_type: r.session_type || '',
                            _stage_type_id: r.stage_type_id || null,
                            _stage_id: r.stage_id || null,
                        };
                    })
                    .filter(Boolean);
                while (rows.length < MIN_ROWS) rows.push(EMPTY_ROW());
                return rows;
            }

            // ── empIdMap & stageMetaMap tracked outside HOT ──
            let empIdMap = {};

            function syncEmpIdMap() {
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

            function getValidRows() {
                if (!hotInstance) return [];
                const src = hotInstance.getSourceData();
                const result = [];
                src.forEach((row, i) => {
                    const empId = empIdMap[i];
                    if (!empId) return;
                    // Resolve stage_type_id and stage_id from display names
                    const stageTypeName = row.stage_type || '';
                    const stageDisplayName = row.stage || '';
                    const stageIdMap = getStageIdMap(stageTypeName);
                    const stageId = stageIdMap[stageDisplayName] || null;
                    const stEntry = STAGE_TYPE_LIST.find(s => s.name === stageTypeName);
                    result.push({
                        employee_id: empId,
                        task: row.task || '',
                        parts: row.parts || '',
                        stage: stageDisplayName, // keep text for legacy compat
                        stage_type_id: stEntry?.id || null,
                        stage_id: stageId,
                        session_type: row.session_type || '',
                    });
                });
                return result;
            }

            function getValidEmpIds() {
                return Object.values(empIdMap).filter(id => !!id);
            }

            // Custom Stage dropdown renderer (shows greyed placeholder when empty)
            function stageTypeRenderer(hotInstance, td, row, col, prop, value) {
                Handsontable.renderers.TextRenderer.apply(this, arguments);
                if (!value) {
                    td.style.color = '#aaa';
                    td.textContent = '— select type —';
                }
            }

            function stageRenderer(hotInstance, td, row, col, prop, value) {
                Handsontable.renderers.TextRenderer.apply(this, arguments);
                const src = hotInstance.getSourceData();
                const stageTypeName = src[row]?.stage_type || '';
                if (!stageTypeName) {
                    td.style.color = '#ccc';
                    td.style.background = '#fafafa';
                    td.textContent = '(select type first)';
                    return;
                }
                if (!value) {
                    td.style.color = '#aaa';
                    td.textContent = '— select stage —';
                }
            }

            function initHOT(plannedRows) {
                const tableData = buildTableData(
                    Array.isArray(plannedRows) ? plannedRows : []
                );

                if (hotInstance) {
                    hotInstance.destroy();
                    hotInstance = null;
                }

                rowStageSources = {};
                // Pre-compute per-row stage sources from existing data
                tableData.forEach((row, i) => {
                    rowStageSources[i] = getStageNamesForType(row.stage_type || '');
                });

                hotInstance = new Handsontable(document.getElementById('hot-container'), {
                    data: tableData,
                    licenseKey: 'non-commercial-and-evaluation',
                    height: Math.max(420, Math.floor(window.innerHeight * 0.55)),
                    stretchH: 'all',
                    rowHeaders: true,
                    colHeaders: ['Employee Name', 'Position', 'Dept', 'Task', 'Parts', 'Stage Type', 'Stage',
                        'Session Type'
                    ],
                    columns: [{
                            data: 'name',
                            type: 'autocomplete',
                            source: EMP_NAMES,
                            strict: true,
                            filter: true,
                            allowInvalid: false,
                            width: 180,
                        },
                        {
                            data: 'position',
                            type: 'text',
                            width: 120,
                            readOnly: true,
                            className: 'htDimmed'
                        },
                        {
                            data: 'department',
                            type: 'text',
                            width: 120,
                            readOnly: true,
                            className: 'htDimmed'
                        },
                        {
                            data: 'task',
                            type: 'text',
                            width: 150,
                            placeholder: 'e.g., Sculpting...',
                            renderer(hot, td, row, col, prop, value) {
                                Handsontable.renderers.TextRenderer.apply(this, arguments);
                                td.textContent = value || '';
                                td.style.color = value ? '#333' : '#aaa';
                                if (!value) td.textContent = 'e.g., Sculpting...';
                            },
                        },
                        {
                            data: 'parts',
                            type: 'dropdown',
                            source: ['', ...@json($timingParts)],
                            allowInvalid: true,
                            width: 120,
                        },
                        {
                            data: 'stage_type',
                            type: 'dropdown',
                            source: STAGE_TYPE_NAME_SOURCE,
                            allowInvalid: false,
                            width: 160,
                            renderer: stageTypeRenderer,
                        },
                        {
                            data: 'stage',
                            type: 'dropdown',
                            source(query, callback) {
                                // HOT calls source(query, callback) — query is the typed search string,
                                // NOT the row index. Use getSelected() to get the actual row index.
                                const sel = hotInstance ? hotInstance.getSelected() : null;
                                const rowIdx = sel ? sel[0][0] : 0;
                                callback(rowStageSources[rowIdx] || ['']);
                            },
                            allowInvalid: false,
                            width: 240,
                            renderer: stageRenderer,
                        },
                        {
                            data: 'session_type',
                            type: 'dropdown',
                            source: ['', 'mass_production', 'repair'],
                            allowInvalid: false,
                            width: 140,
                            renderer(hot, td, row, col, prop, value) {
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
                        },
                    ],
                    manualColResize: true,
                    contextMenu: ['row_above', 'row_below', '---------', 'remove_row'],
                    outsideClickDeselects: false,
                    afterChange(changes, source) {
                        if (!changes || source === 'loadData' || source === '_fill') return;
                        const fills = [];
                        changes.forEach(([row, prop, oldVal, newVal]) => {
                            if (prop === 'name') {
                                const emp = EMP_MAP[newVal] || null;
                                empIdMap[row] = emp ? emp.id : null;
                                fills.push([row, 'position', emp ? emp.position : '']);
                                fills.push([row, 'department', emp ? emp.department : '']);
                            }
                            if (prop === 'stage_type' && newVal !== oldVal) {
                                // Update per-row stage source and clear stage value
                                rowStageSources[row] = getStageNamesForType(newVal || '');
                                fills.push([row, 'stage', '']);
                            }
                        });
                        if (fills.length) hotInstance.setDataAtRowProp(fills, '_fill');
                        updateSelectedCount();
                    },
                    afterRemoveRow(index, amount) {
                        syncEmpIdMap();
                        // Rebuild rowStageSources after deletion
                        const newSources = {};
                        const src = hotInstance.getSourceData();
                        src.forEach((row, i) => {
                            newSources[i] = getStageNamesForType(row.stage_type || '');
                        });
                        rowStageSources = newSources;
                        updateSelectedCount();
                    },
                    afterCreateRow(index, amount) {
                        for (let i = index; i < index + amount; i++) {
                            empIdMap[i] = null;
                            rowStageSources[i] = [''];
                        }
                        updateSelectedCount();
                    },
                });

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

            /* ── JO card click → open modal ── */
            $(document).on('click', '.jo-planner-card', function() {
                const $card = $(this);
                const joId = $card.data('jo-id');

                $('.jo-planner-card').removeClass('jo-active');
                $card.addClass('jo-active');
                selectedJoId = joId;

                $('#plan-jo-badge').text($card.data('jo-label'));
                $('#plan-jo-name').text($card.data('jo-label'));
                $('#plan-jo-project').text('Project: ' + $card.data('project'));

                initHOT($card.data('planned-rows') || $card.data('planned-ids') || []);

                const modal = new bootstrap.Modal(document.getElementById('planEditorModal'), {
                    backdrop: true
                });
                modal.show();

                document.getElementById('planEditorModal').addEventListener('shown.bs.modal',
            function onShown() {
                    if (hotInstance) hotInstance.render();
                    document.getElementById('planEditorModal').removeEventListener('shown.bs.modal',
                        onShown);
                });
            });

            document.getElementById('planEditorModal').addEventListener('hidden.bs.modal', function() {
                $('.jo-planner-card').removeClass('jo-active');
            });

            /* ── Toolbar ── */
            $('#hot-add-row').on('click', function() {
                if (hotInstance) hotInstance.alter('insert_row_below');
            });
            $('#hot-remove-row').on('click', function() {
                if (!hotInstance) return;
                const sel = hotInstance.getSelected();
                if (sel && sel.length) {
                    if (hotInstance.countRows() > 1) hotInstance.alter('remove_row', sel[0][0]);
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
                            clears.push(
                                [i, 'name', ''], [i, 'position', ''], [i, 'department', ''],
                                [i, 'task', ''], [i, 'parts', ''], [i, 'stage_type', ''], [i, 'stage',
                                    ''
                                ]
                            );
                            empIdMap[i] = null;
                            rowStageSources[i] = [''];
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
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input minimal 1 karyawan'
                    });
                    return;
                }
                const missing = [];
                planRows.forEach((row, i) => {
                    const fields = [];
                    if (!row.task) fields.push('Task');
                    if (!row.parts) fields.push('Parts');
                    if (!row.stage_type_id) fields.push('Stage Type');
                    if (!row.stage_id) fields.push('Stage');
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
                        planning_date: $('#planning-date-global').val(),
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
                        bootstrap.Modal.getInstance(document.getElementById('planEditorModal'))?.hide();
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
                        '<i class="bi bi-floppy me-1"></i>Submit Plan');
                    },
                });
            });

            /* ── Clear ── */
            $('#btn-clear-plan').on('click', function() {
                if (!selectedJoId) return;
                Swal.fire({
                        icon: 'warning',
                        title: 'Hapus Plan?',
                        text: 'Plan JO ini akan dihapus.',
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
                                job_order_id: selectedJoId,
                                planning_date: $('#planning-date-global').val()
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
