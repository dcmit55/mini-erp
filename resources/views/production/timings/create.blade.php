@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-4">Input Timing</h4>
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <form action="{{ route('timings.storeMultiple') }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="min-width:100%;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:7%;">Date <span class="text-danger">*</span></th>
                                    <th style="width:9%;">Job Order</th>
                                    <th style="width:10%;">Project <span class="text-danger">*</span></th>
                                    <th style="width:7%;">Department</th>
                                    <th style="width:9%;">Step</th>
                                    <th style="width:7%;">Part</th>
                                    <th style="width:7%;">Item</th>
                                    <th style="width:10%;">Employee <span class="text-danger">*</span></th>
                                    <th style="width:5%;">Start <span class="text-danger">*</span></th>
                                    <th style="width:5%;">End <span class="text-danger">*</span></th>
                                    <th style="width:5%;">Duration (min) <span class="text-danger">*</span></th>
                                    <th style="width:5%;">Value</th>
                                    <th style="width:7%;">Type Measurement</th>
                                    <th style="width:7%;">Status <span class="text-danger">*</span></th>
                                    <th style="width:10%;">Remarks</th>
                                    <th style="width:3%;"></th>
                                </tr>
                            </thead>
                            <tbody id="timing-rows">
                                @php
                                    $oldTimings = old('timings') ?? [0 => []];
                                @endphp
                                @foreach ($oldTimings as $i => $timing)
                                    <tr class="timing-row align-top">
                                        {{-- Date --}}
                                        <td data-label="Date">
                                            <input type="date" name="timings[{{ $i }}][tanggal]"
                                                class="form-control form-control-sm" required
                                                value="{{ old("timings.$i.tanggal") }}">
                                        </td>
                                        {{-- Job Order --}}
                                        <td data-label="Job Order">
                                            <select name="timings[{{ $i }}][job_order_id]"
                                                class="form-select form-select-sm select2 job-order-select">
                                                <option value="">Optional</option>
                                                @foreach ($jobOrders as $jo)
                                                    <option value="{{ $jo->id }}"
                                                        data-project="{{ $jo->project_id ?? '' }}"
                                                        {{ old("timings.$i.job_order_id") == $jo->id ? 'selected' : '' }}>
                                                        {{ $jo->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        {{-- Project (auto-filled from Job Order, or manual) --}}
                                        <td data-label="Project">
                                            <select name="timings[{{ $i }}][project_id]"
                                                class="form-select form-select-sm select2 project-select" required>
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}"
                                                        data-department="{{ $project->departments->pluck('name')->implode(', ') }}"
                                                        data-parts='@json($project->parts->pluck('part_name'))'
                                                        {{ old("timings.$i.project_id") == $project->id ? 'selected' : '' }}>
                                                        {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        {{-- Department (auto-filled from project) --}}
                                        <td data-label="Department">
                                            <div class="department-text text-muted small pt-1" style="min-height:30px;">
                                                @php
                                                    $selectedProject = $projects->firstWhere(
                                                        'id',
                                                        old("timings.$i.project_id"),
                                                    );
                                                @endphp
                                                {{ $selectedProject ? $selectedProject->departments->pluck('name')->implode(', ') : '—' }}
                                            </div>
                                        </td>
                                        {{-- Step --}}
                                        <td data-label="Step">
                                            <input type="text" name="timings[{{ $i }}][step]"
                                                class="form-control form-control-sm" placeholder="Step"
                                                value="{{ old("timings.$i.step") }}">
                                        </td>
                                        {{-- Part --}}
                                        <td data-label="Part">
                                            <input type="text" name="timings[{{ $i }}][parts]"
                                                class="form-control form-control-sm{{ $errors->has("timings.$i.parts") ? ' is-invalid' : '' }}"
                                                placeholder="Optional" value="{{ old("timings.$i.parts") }}">
                                        </td>
                                        {{-- Item --}}
                                        <td data-label="Item">
                                            <input type="text" name="timings[{{ $i }}][item]"
                                                class="form-control form-control-sm"
                                                placeholder="Optional" value="{{ old("timings.$i.item") }}">
                                        </td>
                                        {{-- Employee --}}
                                        <td data-label="Employee">
                                            <select name="timings[{{ $i }}][employee_id]"
                                                class="form-select form-select-sm select2" required>
                                                <option value="">Select Employee</option>
                                                @foreach ($employees as $emp)
                                                    <option value="{{ $emp->id }}"
                                                        {{ old("timings.$i.employee_id") == $emp->id ? 'selected' : '' }}>
                                                        {{ $emp->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        {{-- Start Time --}}
                                        <td data-label="Start Time">
                                            <input type="time" name="timings[{{ $i }}][start_time]"
                                                class="form-control form-control-sm start-time" required
                                                value="{{ old("timings.$i.start_time") }}">
                                        </td>
                                        {{-- End Time --}}
                                        <td data-label="End Time">
                                            <input type="time" name="timings[{{ $i }}][end_time]"
                                                class="form-control form-control-sm end-time @error("timings.$i.end_time") is-invalid @enderror"
                                                required value="{{ old("timings.$i.end_time") }}">
                                            @error("timings.$i.end_time")
                                                <div class="invalid-feedback"></div>
                                            @enderror
                                        </td>
                                        {{-- Duration (auto-calculated) --}}
                                        <td data-label="Duration (min)">
                                            <input type="number" name="timings[{{ $i }}][duration_minutes]"
                                                class="form-control form-control-sm duration-minutes @error("timings.$i.duration_minutes") is-invalid @enderror"
                                                placeholder="Auto" required min="0"
                                                value="{{ old("timings.$i.duration_minutes") }}">
                                            @error("timings.$i.duration_minutes")
                                                <div class="invalid-feedback"></div>
                                            @enderror
                                        </td>
                                        {{-- Value (optional) --}}
                                        <td data-label="Value">
                                            <input type="number" step="0.01"
                                                name="timings[{{ $i }}][measurement_value]"
                                                class="form-control form-control-sm @error("timings.$i.measurement_value") is-invalid @enderror"
                                                placeholder="Optional" value="{{ old("timings.$i.measurement_value") }}">
                                            @error("timings.$i.measurement_value")
                                                <div class="invalid-feedback"></div>
                                            @enderror
                                        </td>
                                        {{-- Type Measurement (from units table, optional) --}}
                                        <td data-label="Type Measurement">
                                            <select name="timings[{{ $i }}][measurement_type]"
                                                class="form-select form-select-sm select2 measurement-type-select">
                                                <option value="">Optional</option>
                                                @foreach ($units as $unit)
                                                    <option value="{{ $unit }}"
                                                        {{ old("timings.$i.measurement_type") == $unit ? 'selected' : '' }}>
                                                        {{ $unit }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        {{-- Status --}}
                                        <td data-label="Status">
                                            <select name="timings[{{ $i }}][status]"
                                                class="form-select form-select-sm" required>
                                                <option value="pending"
                                                    {{ old("timings.$i.status", 'pending') == 'pending' ? 'selected' : '' }}>
                                                    Pending
                                                </option>
                                                <option value="on progress"
                                                    {{ old("timings.$i.status") == 'on progress' ? 'selected' : '' }}>On
                                                    Progress</option>
                                                <option value="complete"
                                                    {{ old("timings.$i.status") == 'complete' ? 'selected' : '' }}>Complete
                                                </option>
                                            </select>
                                        </td>
                                        {{-- Remarks --}}
                                        <td data-label="Remarks">
                                            <input type="text" name="timings[{{ $i }}][remarks]"
                                                class="form-control form-control-sm" placeholder="Remarks"
                                                value="{{ old("timings.$i.remarks") }}">
                                        </td>
                                        <td data-label="Actions">
                                            <button type="button" class="btn btn-danger btn-sm btn-remove-row"
                                                title="Delete"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="copy-row">
                                <i class="bi bi-files"></i> Copy Last Row
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-row">
                                <i class="bi bi-plus-square"></i> Add Empty Row
                            </button>
                        </div>
                        <small class="text-muted"><span class="text-danger">*</span> = Required &nbsp;|&nbsp; Duration
                            auto-calculated from Start &amp; End time</small>
                    </div>
            </div>
            <div class="card-footer bg-white border-0 d-flex justify-content-end">
                <button type="submit" class="btn btn-success btn-sm" id="timing-submit-btn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    Submit All
                </button>
            </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .form-text.department-text {
            font-size: 0.75em;
            margin-top: 2px;
            margin-bottom: 0;
            color: #888;
            line-height: 1.2;
            padding-left: 2px;
        }

        @media (max-width: 992px) {

            .table-responsive table,
            .table-responsive thead,
            .table-responsive tbody,
            .table-responsive tr,
            .table-responsive th,
            .table-responsive td {
                display: block !important;
                width: 100% !important;
            }

            .table-responsive thead {
                display: none !important;
            }

            .table-responsive tr {
                margin-bottom: 1.5rem;
                border-bottom: 2px solid #dee2e6;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            }

            .table-responsive td {
                position: relative;
                padding-left: 120px;
                min-height: 40px;
                border: none;
                border-bottom: 1px solid #dee2e6;
                box-sizing: border-box;
                width: 100% !important;
                word-break: break-word;
            }

            .table-responsive td:before {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                width: 100px;
                white-space: normal;
                font-weight: 600;
                color: #888;
                content: attr(data-label);
                box-sizing: border-box;
                text-align: left;
            }

            .table-responsive td:last-child {
                border-bottom: 2px solid #dee2e6;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ── Units list for dynamic rows ──
        const allUnits = @json($units->values());

        function initSelect2Row($row) {
            $row.find('.select2').select2({
                width: '100%',
                theme: 'bootstrap-5',
                dropdownParent: $row
            });
        }

        // ── Auto-calculate duration when start/end change ──
        function calcDuration($row) {
            const start = $row.find('.start-time').val();
            const end = $row.find('.end-time').val();
            if (start && end) {
                const [sh, sm] = start.split(':').map(Number);
                const [eh, em] = end.split(':').map(Number);
                const startMin = sh * 60 + sm;
                const endMin = eh * 60 + em;
                const diff = endMin - startMin;
                $row.find('.duration-minutes').val(diff > 0 ? diff : 0);
            }
        }

        $(document).ready(function() {
            initSelect2Row($('.timing-row').first());

            // Live duration calculation
            $(document).on('change', '.start-time, .end-time', function() {
                calcDuration($(this).closest('tr'));
            });

            let rowIdx = 1;

            $('#copy-row').click(function() {
                let $lastRow = $('.timing-row').last();

                $lastRow.find('.select2').each(function() {
                    if ($(this).data('select2')) $(this).select2('destroy');
                });

                let $newRow = $lastRow.clone();

                // Capture values to copy from last row (BEFORE clone index remapping)
                let prevDate = $lastRow.find('input[name$="[tanggal]"]').val();
                let prevProject = $lastRow.find('select[name$="[project_id]"]').val();
                let prevJobOrder = $lastRow.find('select[name$="[job_order_id]"]').val();
                let prevStart = $lastRow.find('input[name$="[start_time]"]').val();
                let prevEnd = $lastRow.find('input[name$="[end_time]"]').val();
                let prevStep = $lastRow.find('input[name$="[step]"]').val();
                let prevParts = $lastRow.find('input[name$="[parts]"]').val();
                let prevItem = $lastRow.find('input[name$="[item]"]').val();
                let prevSessionType = $lastRow.find('select[name$="[session_type]"]').val();

                $newRow.find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                    if ($(this).is('[name$="[tanggal]"]')) {
                        $(this).val(prevDate);
                    } else if ($(this).is('[name$="[project_id]"]')) {
                        $(this).val(prevProject);
                    } else if ($(this).is('[name$="[job_order_id]"]')) {
                        $(this).val(prevJobOrder);
                    } else if ($(this).is('[name$="[start_time]"]')) {
                        $(this).val(prevStart);
                    } else if ($(this).is('[name$="[end_time]"]')) {
                        $(this).val(prevEnd);
                    } else if ($(this).is('[name$="[step]"]')) {
                        $(this).val(prevStep); // ✅ Copy step
                    } else if ($(this).is('[name$="[parts]"]')) {
                        $(this).val(prevParts); // ✅ Copy parts
                    } else if ($(this).is('[name$="[item]"]')) {
                        $(this).val(prevItem); // ✅ Copy item
                    } else if ($(this).is('[name$="[session_type]"]')) {
                        $(this).val(prevSessionType); // ✅ Copy session type
                    } else if ($(this).is('[name$="[employee_id]"]')) {
                        $(this).val(null); // ✅ Reset employee — must reselect
                    } else if ($(this).is('[name$="[duration_minutes]"]')) {
                        $(this).val('');
                    } else if ($(this).is('[name$="[measurement_type]"]')) {
                        $(this).val('');
                    } else if ($(this).is('[name$="[measurement_value]"]')) {
                        $(this).val('');
                    }
                });

                $newRow.find('.btn-remove-row').show();
                $('#timing-rows').append($newRow);
                initSelect2Row($newRow);
                initSelect2Row($lastRow);

                let $projectSelect = $newRow.find('select[name$="[project_id]"]');
                $projectSelect.trigger('change');

                setTimeout(function() {
                    // Recalculate duration if times are set
                    calcDuration($newRow);
                }, 150);

                rowIdx++;
            });



            $('#add-row').click(function() {
                let $lastRow = $('.timing-row').last();

                $lastRow.find('.select2').each(function() {
                    if ($(this).data('select2')) $(this).select2('destroy');
                });

                let $newRow = $lastRow.clone();

                $newRow.find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                    if ($(this).is('input[type="date"]')) {
                        $(this).val('');
                    } else if ($(this).is('input[type="time"]')) {
                        $(this).val('');
                    } else if ($(this).is('input[type="number"]')) {
                        $(this).val('');
                    } else if ($(this).is('input[type="text"]')) {
                        $(this).val('');
                    } else if ($(this).is('select')) {
                        if ($(this).hasClass('project-select')) {
                            $(this).val('');
                        } else {
                            $(this).val('');
                        }
                    }
                });
                // Reset department text
                $newRow.find('.department-text').text('—');

                $newRow.find('.btn-remove-row').show();
                $('#timing-rows').append($newRow);
                initSelect2Row($newRow);
                initSelect2Row($lastRow);

                // Rebuild measurement_type options from allUnits
                let $mtSelect = $newRow.find('select[name$="[measurement_type]"]');
                let mtHtml = '<option value="">— Optional —</option>';
                allUnits.forEach(function(u) {
                    mtHtml += `<option value="${u}">${u}</option>`;
                });
                $mtSelect.html(mtHtml);

                rowIdx++;
            });

            $(document).on('click', '.btn-remove-row', function() {
                if ($('.timing-row').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            // Job Order change: auto-fill project-select from data-project attribute
            $(document).on('change', '.job-order-select', function() {
                let $row = $(this).closest('tr');
                let projectId = $(this).find(':selected').data('project');

                if (projectId) {
                    let $projectSelect = $row.find('.project-select');
                    $projectSelect.val(projectId).trigger('change');
                }
            });

            // Project change: fill department text
            $(document).on('change', '.project-select', function() {
                let $row = $(this).closest('tr');
                let selected = $(this).find(':selected')[0];
                let department = $(selected).data('department') || '';

                let $deptDiv = $row.find('.department-text');
                $deptDiv.text(department ? department.charAt(0).toUpperCase() + department.slice(1) : '—');
            });

            // Submit: disable button & show spinner
            const form = $('form[action="{{ route('timings.storeMultiple') }}"]');
            const submitBtn = $('#timing-submit-btn');
            const spinner = submitBtn.find('.spinner-border');

            if (form.length && submitBtn.length && spinner.length) {
                form.on('submit', function() {
                    submitBtn.prop('disabled', true);
                    spinner.removeClass('d-none');
                });
            }
        });
    </script>
@endpush
