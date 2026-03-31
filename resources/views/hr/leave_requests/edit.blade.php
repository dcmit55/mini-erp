@extends('layouts.app')

@section('title', 'Edit Leave Request')

@section('content')
<div class="container py-4" style="max-width: 780px;">

    <div class="d-flex align-items-center gap-3 mb-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Alerts --}}
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <strong>Ada masalah dengan inputan:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        {!! session('error') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('leave_requests.update', $leave->id) }}"
          id="leaveRequestForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Section: Employee --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Informasi Karyawan</span>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Nama Karyawan <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employee_id" class="form-select select2" required>
                        <option value="">Pilih karyawan...</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-department="{{ $emp->department->name ?? '' }}"
                                data-position="{{ $emp->position ?? '' }}"
                                data-hiredate="{{ $emp->hire_date ? \Carbon\Carbon::parse($emp->hire_date)->format('d M Y') : '' }}"
                                data-saldo="{{ $emp->saldo_cuti ?? 0 }}"
                                data-menstruation-approved="{{ $emp->menstruation_leave_approved ? '1' : '0' }}"
                                {{ old('employee_id', $leave->employee_id) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Employee info panel --}}
                <div id="employee-info" class="mt-2 rounded-2 p-3 small {{ $leave->employee_id ? '' : 'd-none' }}"
                     style="background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color);">
                    <div class="row g-2">
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:.7rem;">Department</div>
                            <div class="fw-medium" id="info-department">{{ $leave->employee->department->name ?? '—' }}</div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:.7rem;">Position</div>
                            <div class="fw-medium" id="info-position">{{ $leave->employee->position ?? '—' }}</div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:.7rem;">Saldo Cuti</div>
                            <div class="fw-semibold text-success" id="info-saldo">{{ ($leave->employee->saldo_cuti ?? 0) }} hari</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Tanggal --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Periode Cuti</span>
                </div>

                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label small fw-medium">Dari Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" required
                               value="{{ old('start_date', $leave->start_date ? $leave->start_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small fw-medium">Sampai Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" required
                               value="{{ old('end_date', $leave->end_date ? $leave->end_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small fw-medium">
                            Durasi
                            <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Gunakan 0.5 untuk setengah hari"></i>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="duration" id="duration" class="form-control"
                                   min="0.5" max="999.99" step="0.5" required
                                   value="{{ old('duration', $leave->duration) }}" placeholder="1">
                            <span class="input-group-text">hari</span>
                        </div>
                        <div id="leave-balance-info" class="mt-1 small"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Tipe Cuti --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Jenis Cuti</span>
                </div>

                <div class="row g-2">
                    @foreach($leaveTypes as $type)
                    <div class="col-sm-6">
                        <label class="leave-type-card d-flex align-items-center gap-2 rounded-2 px-3 py-2 w-100"
                               style="cursor:pointer; border: 1px solid var(--bs-border-color); transition: all .15s;">
                            <input class="form-check-input flex-shrink-0 mt-0" type="radio"
                                   name="type" id="type_{{ $type }}" value="{{ $type }}"
                                   {{ old('type', $leave->type) == $type ? 'checked' : '' }} required>
                            <span class="small">{{ $leaveTypeLabels[$type] ?? $type }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section: Waktu Izin Keluar (EARLY_LEAVE / PERMISSION_OUT) --}}
        <div id="early-leave-time-section" class="card border-0 shadow-sm rounded-3 mb-3 {{ in_array($leave->type, ['EARLY_LEAVE', 'PERMISSION_OUT']) ? '' : 'd-none' }}">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Waktu Izin Keluar</span>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label small fw-medium mb-1">Jam Keluar <span class="text-danger">*</span></label>
                        <input type="time" name="leave_time_from" id="leave_time_from"
                               class="form-control form-control-sm"
                               value="{{ old('leave_time_from', $leave->leave_time_from ? \Carbon\Carbon::parse($leave->leave_time_from)->format('H:i') : '') }}">
                    </div>
                    <div class="col-12 col-sm-6 {{ $leave->type === 'PERMISSION_OUT' ? '' : 'd-none' }}" id="leave_time_to_col">
                        <label class="form-label small fw-medium mb-1">Jam Kembali <span class="text-danger">*</span></label>
                        <input type="time" name="leave_time_to" id="leave_time_to"
                               class="form-control form-control-sm"
                               value="{{ old('leave_time_to', $leave->leave_time_to ? \Carbon\Carbon::parse($leave->leave_time_to)->format('H:i') : '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: MC Upload (SICK only) --}}
        <div id="mc-upload-section" class="card border-0 shadow-sm rounded-3 mb-3 d-none">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Medical Certificate</span>
                </div>
                @if($leave->mc_document)
                <div class="alert alert-info small py-2 mb-2">
                    <i class="fas fa-paperclip me-1"></i> File sudah diupload. Upload baru untuk mengganti.
                </div>
                @endif
                <label class="form-label small fw-medium">Upload MC {{ $leave->mc_document ? '(opsional — kosongkan untuk tetap pakai file lama)' : '' }}</label>
                <input type="file" name="mc_document" id="mc_document" class="form-control form-control-sm"
                       accept=".pdf,.jpg,.jpeg,.png">
                <div class="text-muted small mt-1">PDF, JPG, PNG &mdash; maks. 5MB</div>
            </div>
        </div>

        {{-- Section: Doctor Letter (MENSTRUATION only) --}}
        <div id="doctor-letter-section" class="card border-0 shadow-sm rounded-3 mb-3 d-none">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Surat Keterangan Dokter</span>
                </div>
                @if($leave->doctor_letter)
                <div class="alert alert-info small py-2 mb-2">
                    <i class="fas fa-paperclip me-1"></i> File sudah diupload. Upload baru untuk mengganti.
                </div>
                @endif
                <label class="form-label small fw-medium">Upload Surat Dokter <span class="text-danger">*</span></label>
                <input type="file" name="doctor_letter" id="doctor_letter" class="form-control form-control-sm"
                       accept=".pdf,.jpg,.jpeg,.png">
                <div class="text-muted small mt-1">Surat keterangan bahwa karyawan mengalami sakit saat haid. PDF, JPG, PNG &mdash; maks. 5MB</div>
            </div>
        </div>

        {{-- Section: Alasan --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Keterangan</span>
                </div>
                <textarea name="reason" class="form-control form-control-sm" rows="3"
                          placeholder="Tuliskan alasan pengajuan cuti (opsional)...">{{ old('reason', $leave->reason) }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('leave_requests.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-2">Batal</a>
            <button type="submit" id="submitBtn" class="btn btn-primary btn-sm px-4 rounded-2">
                <i class="fas fa-save me-1"></i>Simpan Perubahan
            </button>
        </div>

    </form>
</div>
@endsection

@push('styles')
<style>
    .leave-type-card:hover {
        border-color: var(--bs-primary) !important;
        background: rgba(13,110,253,.04);
    }
    .leave-type-card:has(input:checked) {
        border-color: var(--bs-primary) !important;
        background: rgba(13,110,253,.07);
        color: var(--bs-primary);
        font-weight: 500;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {

    // ── Select2 ───────────────────────────────────────────────────────────────
    $('#employee_id').select2({ width: '100%', placeholder: 'Pilih karyawan...', allowClear: true, theme: 'bootstrap-5' });

    $('#employee_id').on('change', function () {
        const opt  = this.options[this.selectedIndex];
        const dept = opt.getAttribute('data-department') || '';
        const pos  = opt.getAttribute('data-position')   || '';
        const saldo = parseFloat(opt.getAttribute('data-saldo') || 0);

        if ($(this).val()) {
            $('#info-department').text(dept  || '—');
            $('#info-position').text(pos     || '—');
            $('#info-saldo').text(saldo + ' hari');
            $('#employee-info').removeClass('d-none');
        } else {
            $('#employee-info').addClass('d-none');
        }
        updateBalanceDisplay();
    });

    // ── Tipe cuti: fixed-day types ────────────────────────────────────────────
    const fixedDayTypes = {
        'EMP_SELF_WEDDING': 3, 'BIRTH_CHILD_MISCARRIAGE': 2,
        'DEATH_FAMILY_SAME_HOUSE': 1, 'CHILD_CIRCUMCISION_BAPTISM': 2,
        'SON_DAUGHTER_WEDDING': 2, 'DEATH_SPOUSE_CHILD_PARENT_IN_LAW': 2,
        'MENSTRUATION': 1, 'PATERNITY': 2,
    };
    function updateDocumentSections(type) {
        if (type === 'SICK') {
            $('#mc-upload-section').removeClass('d-none');
        } else {
            $('#mc-upload-section').addClass('d-none');
            $('#mc_document').val('');
        }
        if (type === 'MENSTRUATION') {
            $('#doctor-letter-section').removeClass('d-none');
        } else {
            $('#doctor-letter-section').addClass('d-none');
            $('#doctor_letter').val('');
        }
        if (type === 'EARLY_LEAVE' || type === 'PERMISSION_OUT') {
            $('#early-leave-time-section').removeClass('d-none');
            if (type === 'PERMISSION_OUT') {
                $('#leave_time_to_col').removeClass('d-none');
            } else {
                $('#leave_time_to_col').addClass('d-none');
                $('#leave_time_to').val('');
            }
        } else {
            $('#early-leave-time-section').addClass('d-none');
            $('#leave_time_from').val('');
            $('#leave_time_to').val('');
        }
    }

    $('input[name="type"]').on('change', function () {
        const type = $(this).val();
        const $end = $('#end_date'), $dur = $('#duration');

        if (fixedDayTypes[type]) {
            $end.prop('readonly', true).css('opacity', '.6');
            $dur.prop('readonly', true).css('opacity', '.6').val(fixedDayTypes[type]);
            $('#start_date').off('change.fixed').on('change.fixed', function () {
                if ($(this).val()) {
                    const start = new Date($(this).val());
                    const end   = new Date(start);
                    end.setDate(end.getDate() + fixedDayTypes[type] - 1);
                    $end.val(end.toISOString().split('T')[0]);
                    $dur.val(fixedDayTypes[type]);
                }
            });
            if ($('#start_date').val()) $('#start_date').trigger('change.fixed');
        } else {
            $end.prop('readonly', false).css('opacity', '');
            $dur.prop('readonly', false).css('opacity', '');
            $('#start_date').off('change.fixed');
            $('#start_date, #end_date').off('change.calc').on('change.calc', calcDuration);
        }
        updateBalanceDisplay();
        updateDocumentSections(type);
    });

    // ── Hitung durasi otomatis ─────────────────────────────────────────────────
    function calcDuration() {
        const s = $('#start_date').val(), e = $('#end_date').val();
        if (s && e) {
            const diff = Math.floor((new Date(e) - new Date(s)) / 86400000) + 1;
            if (diff > 0) {
                $('#duration').val(diff);
                if (diff === 1) $('#leave-balance-info').html('<i class="bi bi-lightbulb text-info me-1"></i>Gunakan 0.5 untuk setengah hari');
            } else {
                $('#duration').val('');
                if (diff < 0) Swal.fire({ icon: 'warning', title: 'Tanggal Tidak Valid', text: 'Tanggal selesai harus sama atau setelah tanggal mulai', confirmButtonColor: '#dc3545' });
            }
        }
    }
    $('#start_date, #end_date').on('change.calc', calcDuration);

    // ── Saldo cuti display ─────────────────────────────────────────────────────
    function updateBalanceDisplay() {
        const empId = $('#employee_id').val();
        const type  = $('input[name="type"]:checked').val();
        const $info = $('#leave-balance-info');
        const $dur  = $('#duration');

        if (empId && type === 'ANNUAL') {
            const saldo  = parseFloat($('#employee_id option:selected').attr('data-saldo') || 0);
            const durVal = parseFloat($dur.val());
            $dur.attr('max', saldo);
            if (durVal && durVal > saldo) {
                $dur.addClass('is-invalid');
                $info.html('<i class="bi bi-exclamation-triangle text-danger me-1"></i>Melebihi saldo! Tersedia: <strong>' + saldo + ' hari</strong>');
            } else if (durVal) {
                $dur.removeClass('is-invalid');
                $info.html('<i class="bi bi-info-circle me-1"></i>Saldo: <strong>' + saldo + ' hari</strong> — Sisa: <strong>' + (saldo - durVal).toFixed(1) + ' hari</strong>');
            } else {
                $dur.removeClass('is-invalid');
                $info.html('<i class="bi bi-info-circle me-1"></i>Saldo cuti tersedia: <strong>' + saldo + ' hari</strong>');
            }
        } else {
            $info.html('');
            $dur.removeAttr('max').removeClass('is-invalid');
        }
    }

    $('#duration').on('input', updateBalanceDisplay);
    $('#employee_id, input[name="type"]').on('change', updateBalanceDisplay);

    // ── Init: tampilkan section dokumen sesuai tipe yang sudah tersimpan ───────
    const savedType = $('input[name="type"]:checked').val();
    if (savedType) updateDocumentSections(savedType);

    // ── Submit guard ──────────────────────────────────────────────────────────
    $('#leaveRequestForm').on('submit', function (e) {
        const $btn = $('#submitBtn');
        if ($btn.prop('disabled')) { e.preventDefault(); return false; }
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
    });

    // ── Tooltip ───────────────────────────────────────────────────────────────
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush
