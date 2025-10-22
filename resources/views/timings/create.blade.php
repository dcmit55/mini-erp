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
                <form action="{{ route('timings.storeMultiple') }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" style="min-width:100%;">
                            <thead class="table-light align-middle">
                                <tr>
                                    <th style="width:9%;">Date</th>
                                    <th style="width:13%;">Project</th>
                                    <th style="width:13%;">Step</th>
                                    <th style="width:12%;">Part</th>
                                    <th style="width:13%;">Employee</th>
                                    <th style="width:5%;">Start</th>
                                    <th style="width:5%;">End</th>
                                    <th style="width:5%;">Qty</th>
                                    <th style="width:9%;">Status</th>
                                    <th style="width:13%;">Remarks</th>
                                    <th style="width:3%;"></th>
                                </tr>
                            </thead>
                            <tbody id="timing-rows">
                                @php
                                    $oldTimings = old('timings') ?? [0 => []];
                                @endphp
                                @foreach ($oldTimings as $i => $timing)
                                    <tr class="timing-row align-top">
                                        <td data-label="Date">
                                            <input type="date" name="timings[{{ $i }}][tanggal]"
                                                class="form-control form-control-sm" required
                                                value="{{ old("timings.$i.tanggal") }}">
                                        </td>
                                        <td data-label="Project">
                                            <select name="timings[{{ $i }}][project_id]"
                                                class="form-select form-select-sm select2 project-select" required>
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}"
                                                        data-department="{{ $project->department->name }}"
                                                        data-parts='@json($project->parts->pluck('part_name'))'
                                                        {{ old("timings.$i.project_id") == $project->id ? 'selected' : '' }}>
                                                        {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div
                                                class="form-text department-text {{ old("timings.$i.project_id") ? '' : 'd-none' }}">
                                                @php
                                                    $selectedProject = $projects->firstWhere(
                                                        'id',
                                                        old("timings.$i.project_id"),
                                                    );
                                                @endphp
                                                {{ $selectedProject && $selectedProject->department ? 'Department: ' . $selectedProject->department->name : 'Department' }}
                                            </div>
                                        </td>
                                        <td data-label="Step">
                                            <input type="text" name="timings[{{ $i }}][step]"
                                                class="form-control form-control-sm" placeholder="Step" required
                                                value="{{ old("timings.$i.step") }}">
                                        </td>
                                        <td data-label="Part">
                                            @php
                                                $selectedProject = $projects->firstWhere(
                                                    'id',
                                                    old("timings.$i.project_id"),
                                                );
                                                $parts = $selectedProject
                                                    ? $selectedProject->parts->pluck('part_name')->toArray()
                                                    : [];
                                                $hasParts = count($parts) > 0;
                                            @endphp
                                            <select name="timings[{{ $i }}][parts]"
                                                class="form-select form-select-sm part-select{{ $errors->has("timings.$i.parts") ? ' is-invalid' : '' }}"
                                                {{ !$hasParts ? 'readonly disabled' : '' }}>
                                                @if (!$hasParts)
                                                    <option value="No Part" selected>No Part</option>
                                                @else
                                                    <option value="">Select Project Part</option>
                                                    @foreach ($parts as $part)
                                                        <option value="{{ $part }}"
                                                            {{ old("timings.$i.parts") == $part ? 'selected' : '' }}>
                                                            {{ $part }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </td>
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
                                        <td data-label="Start Time">
                                            <input type="time" name="timings[{{ $i }}][start_time]"
                                                class="form-control form-control-sm" required
                                                value="{{ old("timings.$i.start_time") }}">
                                        </td>
                                        <td data-label="End Time">
                                            <input type="time" name="timings[{{ $i }}][end_time]"
                                                class="form-control form-control-sm @error("timings.$i.end_time") is-invalid @enderror"
                                                required value="{{ old("timings.$i.end_time") }}">
                                            @error("timings.$i.end_time")
                                                <div class="invalid-feedback"></div>
                                            @enderror
                                        </td>
                                        <td data-label="Output Qty">
                                            <input type="number" name="timings[{{ $i }}][output_qty]"
                                                class="form-control form-control-sm @error("timings.$i.output_qty") is-invalid @enderror"
                                                placeholder="Qty" required value="{{ old("timings.$i.output_qty") }}">
                                            @error("timings.$i.output_qty")
                                                <div class="invalid-feedback"></div>
                                            @enderror
                                        </td>
                                        <td data-label="Status">
                                            <select name="timings[{{ $i }}][status]"
                                                class="form-select form-select-sm" required>
                                                <option value="pending" style="color:red;"
                                                    {{ old("timings.$i.status") == 'pending' ? 'selected' : '' }}>Pending
                                                </option>
                                                <option value="on progress" style="color:orange;"
                                                    {{ old("timings.$i.status") == 'on progress' ? 'selected' : '' }}>On
                                                    Progress</option>
                                                <option value="complete" style="color:green;"
                                                    {{ old("timings.$i.status") == 'complete' ? 'selected' : '' }}>Complete
                                                </option>
                                            </select>
                                        </td>
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
                /* Lebar label, sesuaikan dengan padding-left di atas */
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
        function initSelect2Row($row) {
            $row.find('.select2').select2({
                width: '100%',
                theme: 'bootstrap-5',
                dropdownParent: $row
            });
        }

        $(document).ready(function() {
            initSelect2Row($('.timing-row').first());

            let rowIdx = 1;

            $('#copy-row').click(function() {
                let $lastRow = $('.timing-row').last();

                // Destroy select2 pada row yang akan di-clone
                $lastRow.find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                // Clone row terakhir
                let $newRow = $lastRow.clone();

                // Ambil value dari row sebelumnya
                let prevDate = $lastRow.find('input[name^="timings"][name$="[tanggal]"]').val();
                let prevProject = $lastRow.find('select[name^="timings"][name$="[project_id]"]').val();
                let prevDept = $lastRow.find('input[name^="timings"][name$="[department]"]').val();
                let prevStart = $lastRow.find('input[name^="timings"][name$="[start_time]"]').val();
                let prevEnd = $lastRow.find('input[name^="timings"][name$="[end_time]"]').val();
                let prevPart = $lastRow.find('select[name^="timings"][name$="[parts]"]').val();

                // Reset value dan name index
                $newRow.find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                    // Set value untuk field tertentu, kosongkan yang lain
                    if ($(this).is('[name$="[tanggal]"]')) {
                        $(this).val(prevDate);
                    } else if ($(this).is('[name$="[project_id]"]')) {
                        $(this).val(prevProject);
                    } else if ($(this).is('[name$="[department]"]')) {
                        $(this).val(prevDept);
                    } else if ($(this).is('[name$="[start_time]"]')) {
                        $(this).val(prevStart);
                    } else if ($(this).is('[name$="[end_time]"]')) {
                        $(this).val(prevEnd);
                    } else if ($(this).hasClass('department-input')) {
                        $(this).val('');
                    } else if ($(this).hasClass('part-select')) {
                        $(this).html('<option value="">Select Project Part</option>');
                    } else if (!$(this).is('select')) {
                        $(this).val('');
                    }
                });

                $newRow.find('.btn-remove-row').show();

                // Append row baru
                $('#timing-rows').append($newRow);

                // Inisialisasi select2 pada row baru & row terakhir
                initSelect2Row($newRow);
                initSelect2Row($lastRow);

                // Trigger change project-select di row baru, lalu set part setelah option selesai diisi
                let $projectSelect = $newRow.find('select[name$="[project_id]"]');
                $projectSelect.trigger('change');

                // Tunggu option part selesai diisi, lalu set value part
                setTimeout(function() {
                    $newRow.find('select[name$="[parts]"]').val('');
                }, 150);

                rowIdx++;
            });

            $('#add-row').click(function() {
                let $lastRow = $('.timing-row').last();

                // Destroy select2 pada row yang akan di-clone
                $lastRow.find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                // Clone row terakhir
                let $newRow = $lastRow.clone();

                // Reset value dan name index ke default
                $newRow.find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                    // Reset semua field ke default
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
                        } else if ($(this).hasClass('part-select')) {
                            $(this).html('<option value="">Select Project Part</option>');
                            $(this).val('');
                        } else {
                            $(this).val('');
                        }
                    }
                });

                $newRow.find('.btn-remove-row').show();

                // Append row baru
                $('#timing-rows').append($newRow);

                // Inisialisasi select2 pada row baru & row terakhir
                initSelect2Row($newRow);
                initSelect2Row($lastRow);

                rowIdx++;
            });

            $(document).on('click', '.btn-remove-row', function() {
                if ($('.timing-row').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            // Project change: isi department & parts otomatis
            $(document).on('change', '.project-select', function() {
                let $row = $(this).closest('tr');
                let selected = $(this).find(':selected')[0];
                let department = $(selected).data('department');
                let parts = selected.getAttribute('data-parts');
                let $deptDiv = $row.find('.department-text');
                let $partSelect = $row.find('.part-select');

                // Update department text
                if ($(this).val() && department) {
                    $deptDiv.removeClass('d-none').text('Department: ' + department.charAt(0)
                        .toUpperCase() + department.slice(1));
                } else {
                    $deptDiv.addClass('d-none').text('Department');
                }

                // Update parts select
                if (parts && JSON.parse(parts).length > 0) {
                    $partSelect.prop('disabled', false).prop('readonly', false);
                    $partSelect.html('<option value="">Select Project Part</option>');
                    JSON.parse(parts).forEach(function(part) {
                        $partSelect.append(`<option value="${part}">${part}</option>`);
                    });
                    $partSelect.val('');
                } else {
                    $partSelect.prop('disabled', true).prop('readonly', true);
                    $partSelect.html('<option value="No Part" selected>No Part</option>');
                    $partSelect.val('No Part');
                }
            });

            // Handle submit: disable button & show spinner
            const form = $('form[action="{{ route('timings.storeMultiple') }}"]');
            const submitBtn = $('#timing-submit-btn');
            const spinner = submitBtn.find('.spinner-border');
            const submitBtnHtml = submitBtn.html();

            if (form.length && submitBtn.length && spinner.length) {
                form.on('submit', function() {
                    submitBtn.prop('disabled', true);
                    spinner.removeClass('d-none');
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            // submitBtn.prop('disabled', false);
            // spinner.addClass('d-none');
            // submitBtn.html(submitBtnHtml);
        });
    </script>
@endpush
