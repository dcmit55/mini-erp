@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 780px;">

    {{-- Guest banner --}}
    @guest
    <div class="d-flex align-items-start gap-3 rounded-3 p-3 mb-4"
         style="background: rgba(13,110,253,0.07); border: 1px solid rgba(13,110,253,0.18);">
        <i class="fas fa-info-circle text-primary mt-1"></i>
        <div>
            <div class="fw-semibold text-primary small">Pengajuan Cuti Mandiri</div>
            <div class="text-muted small">Anda dapat mengajukan cuti tanpa login. Pengajuan akan diteruskan ke HR untuk persetujuan.</div>
        </div>
    </div>
    @endguest

    {{-- Alerts --}}
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <strong>Ada masalah dengan inputan:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('leave_requests.store') }}" id="leaveRequestForm" enctype="multipart/form-data">
        @csrf

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
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-department="{{ $emp->department->name ?? '' }}"
                                data-position="{{ $emp->position ?? '' }}"
                                data-hiredate="{{ $emp->hire_date ? \Carbon\Carbon::parse($emp->hire_date)->format('d M Y') : '' }}"
                                data-saldo="{{ $emp->saldo_cuti ?? 0 }}"
                                data-menstruation-approved="{{ $emp->menstruation_leave_approved ? '1' : '0' }}"
                                {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Employee info (muncul setelah pilih) --}}
                <div id="employee-info" class="d-none mt-2 rounded-2 p-3 small"
                     style="background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color);">
                    <div class="row g-2">
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:.7rem;">Departemen</div>
                            <div class="fw-medium" id="info-department">—</div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:.7rem;">Jabatan</div>
                            <div class="fw-medium" id="info-position">—</div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:.7rem;">Saldo Cuti</div>
                            <div class="fw-semibold text-success" id="info-saldo">—</div>
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
                        <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" required value="{{ old('start_date') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small fw-medium">Sampai Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" required value="{{ old('end_date') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small fw-medium">Durasi</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="duration" id="duration" class="form-control bg-light"
                                   min="0.5" max="999.99" step="0.5" required readonly
                                   value="{{ old('duration') }}" placeholder="—">
                            <span class="input-group-text" id="duration_unit">hari</span>
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
                    @foreach ($leaveTypes as $type)
                    @if(isset($leaveTypeLabels[$type]))
                    <div class="col-sm-6">
                        <label class="leave-type-card d-flex align-items-center gap-2 rounded-2 px-3 py-2 w-100"
                               style="cursor:pointer; border: 1px solid var(--bs-border-color); transition: all .15s;">
                            <input class="form-check-input flex-shrink-0 mt-0" type="radio"
                                   name="type" id="type_{{ $type }}" value="{{ $type }}"
                                   {{ old('type') == $type ? 'checked' : '' }} required>
                            <span class="small">{{ $leaveTypeLabels[$type] }}</span>
                        </label>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section: Waktu Izin Keluar (EARLY_LEAVE / PERMISSION_OUT) --}}
        <div id="early-leave-time-section" class="card border-0 shadow-sm rounded-3 mb-3 d-none">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Waktu Izin Keluar</span>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label small fw-medium mb-1" id="leave_time_from_label">Jam Keluar <span class="text-danger">*</span></label>
                        <input type="time" name="leave_time_from" id="leave_time_from"
                               class="form-control form-control-sm"
                               value="{{ old('leave_time_from') }}">
                    </div>
                    <div class="col-12 col-sm-6" id="leave_time_to_col">
                        <label class="form-label small fw-medium mb-1" id="leave_time_to_label">Jam Kembali <span class="text-danger">*</span></label>
                        <input type="time" name="leave_time_to" id="leave_time_to"
                               class="form-control form-control-sm"
                               value="{{ old('leave_time_to') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: MC Upload (SICK only) --}}
        <div id="mc-upload-section" class="card border-0 shadow-sm rounded-3 mb-3 d-none">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Surat Keterangan Sakit</span>
                </div>
                <label class="form-label small fw-medium">Upload Surat Sakit <span class="text-danger">*</span></label>
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
                          placeholder="Tuliskan alasan pengajuan cuti (opsional)...">{{ old('reason') }}</textarea>
            </div>
        </div>

{{-- Actions --}}
        <div class="d-flex justify-content-end gap-2">
            @auth
                <a href="{{ route('leave_requests.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-2">Batal</a>
            @else
                <button type="button" class="btn btn-outline-secondary btn-sm px-4 rounded-2" onclick="window.location.reload()">Reset</button>
            @endauth
            <button type="submit" id="submitBtn" class="btn btn-primary btn-sm px-4 rounded-2">
                Kirim
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

    // ── Success (guest) ──────────────────────────────────────────────────────
    @if (session('guest_success'))
        Swal.fire({
            icon: 'success',
            title: 'Pengajuan Terkirim!',
            html: '<p class="mb-2">{{ session('guest_success') }}</p><div class="alert alert-info small mb-0"><i class="bi bi-info-circle me-1"></i>Pengajuan Anda sedang diproses oleh HR.</div>',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0d6efd',
            allowOutsideClick: false
        }).then(function () {
            $('#leaveRequestForm')[0].reset();
            $('#employee_id').val(null).trigger('change');
            $('#employee-info').addClass('d-none');
            $('#leave-balance-info').html('');
        });
    @endif

    // ── Select2 ──────────────────────────────────────────────────────────────
    $('#employee_id').select2({ width: '100%', placeholder: 'Pilih karyawan...', allowClear: true, theme: 'bootstrap-5' });

    $('#employee_id').on('change', function () {
        const opt = this.options[this.selectedIndex];
        const dept    = opt.getAttribute('data-department') || '';
        const pos     = opt.getAttribute('data-position')   || '';
        const saldo   = parseFloat(opt.getAttribute('data-saldo') || 0);

        if ($(this).val()) {
            $('#info-department').text(dept || '—');
            $('#info-position').text(pos  || '—');
            $('#info-saldo').text(saldo + ' hari');
            $('#employee-info').removeClass('d-none');
        } else {
            $('#employee-info').addClass('d-none');
        }
        updateBalanceDisplay();
    });

    // ── Tipe cuti: fixed-day types ───────────────────────────────────────────
    const fixedDayTypes = {
        'EMP_SELF_WEDDING': 3,
        'BIRTH_CHILD_MISCARRIAGE': 2,
        'DEATH_FAMILY_SAME_HOUSE': 1,
        'CHILD_CIRCUMCISION_BAPTISM': 2,
        'SON_DAUGHTER_WEDDING': 2,
        'DEATH_SPOUSE_CHILD_PARENT_IN_LAW': 2,
        'MENSTRUATION': 1,
        'PATERNITY': 2,
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
            $('#leave_time_to_col').removeClass('d-none');
            $('#leave_time_from, #leave_time_to').off('change.time').on('change.time', calcDurationFromTime);
            $('#duration').val('');
            $('#duration_unit').text('jam');

            if (type === 'EARLY_LEAVE') {
                $('#leave_time_from_label').html('Jam Pulang Lebih Awal <span class="text-danger">*</span>');
                $('#leave_time_to_label').html('Jam Pulang Normal <span class="text-danger">*</span>');
            } else {
                $('#leave_time_from_label').html('Jam Keluar <span class="text-danger">*</span>');
                $('#leave_time_to_label').html('Jam Kembali <span class="text-danger">*</span>');
            }
        } else {
            $('#early-leave-time-section').addClass('d-none');
            $('#leave_time_from, #leave_time_to').off('change.time');
            $('#leave_time_from').val('');
            $('#leave_time_to').val('');
            $('#duration_unit').text('hari');
        }
    }

    $('input[name="type"]').on('change', function () {
        const type = $(this).val();
        const $end  = $('#end_date');
        const $dur  = $('#duration');

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

    // ── Hitung hari kerja (exclude Minggu) ───────────────────────────────────
    function countWorkDays(startStr, endStr) {
        const start = new Date(startStr);
        const end   = new Date(endStr);
        let count = 0;
        const cur = new Date(start);
        while (cur <= end) {
            if (cur.getDay() !== 0) count++; // 0 = Minggu
            cur.setDate(cur.getDate() + 1);
        }
        return count;
    }

    // Hitung end_date berdasarkan start + N hari kerja
    function addWorkDays(startStr, days) {
        const date = new Date(startStr);
        let added = 0;
        while (added < days) {
            date.setDate(date.getDate() + 1);
            if (date.getDay() !== 0) added++;
        }
        return date.toISOString().split('T')[0];
    }

    // ── Hitung durasi otomatis ────────────────────────────────────────────────
    function calcDuration() {
        const s = $('#start_date').val(), e = $('#end_date').val();
        if (!s) return;

        const type  = $('input[name="type"]:checked').val();
        const saldo = parseFloat($('#employee_id option:selected').attr('data-saldo') || 0);

        if (e) {
            if (new Date(e) < new Date(s)) {
                $('#duration').val('');
                Swal.fire({ icon: 'warning', title: 'Tanggal Tidak Valid', text: 'Tanggal selesai harus sama atau setelah tanggal mulai', confirmButtonColor: '#dc3545' });
                return;
            }

            let diff = countWorkDays(s, e);

            // Cap ke saldo jika ANNUAL
            if (type === 'ANNUAL' && $('#employee_id').val() && saldo > 0 && diff > saldo) {
                diff = saldo;
                $('#end_date').val(addWorkDays(s, saldo - 1));
                $('#leave-balance-info').html('<i class="bi bi-exclamation-triangle text-warning me-1"></i>Durasi disesuaikan dengan saldo cuti tersisa: <strong>' + saldo + ' hari</strong>');
            }

            if (diff > 0) $('#duration').val(diff);
        }
        updateBalanceDisplay();
    }

    function calcDurationFromTime() {
        const from = $('#leave_time_from').val();
        const to   = $('#leave_time_to').val();
        if (from && to) {
            const [fh, fm] = from.split(':').map(Number);
            const [th, tm] = to.split(':').map(Number);
            const minutes  = (th * 60 + tm) - (fh * 60 + fm);
            if (minutes > 0) {
                const hours = Math.round(minutes / 60 * 100) / 100;
                $('#duration').val(hours);
            } else {
                $('#duration').val('');
            }
        }
    }

    $('#start_date, #end_date').on('change.calc', calcDuration);

    // ── Saldo cuti display ────────────────────────────────────────────────────
    function updateBalanceDisplay() {
        const empId  = $('#employee_id').val();
        const type   = $('input[name="type"]:checked').val();
        const $info  = $('#leave-balance-info');
        const $dur   = $('#duration');

        if (empId && type === 'ANNUAL') {
            const saldo  = parseFloat($('#employee_id option:selected').attr('data-saldo') || 0);
            const durVal = parseFloat($dur.val());
            $dur.attr('max', saldo);
            $dur.removeClass('is-invalid');

            if (!durVal) {
                $info.html('<i class="bi bi-info-circle me-1"></i>Saldo cuti tersedia: <strong>' + saldo + ' hari</strong>');
            } else if (durVal >= saldo) {
                $info.html('<i class="bi bi-exclamation-triangle text-warning me-1"></i>Durasi disesuaikan dengan saldo cuti tersisa: <strong>' + saldo + ' hari</strong>');
            } else {
                $info.html('<i class="bi bi-info-circle me-1"></i>Saldo: <strong>' + saldo + ' hari</strong> — Sisa: <strong>' + (saldo - durVal).toFixed(1) + ' hari</strong>');
            }
        } else {
            $info.html('');
            $dur.removeAttr('max').removeClass('is-invalid');
        }
    }

    $('#duration').on('input', updateBalanceDisplay);
    $('#employee_id, input[name="type"]').on('change', updateBalanceDisplay);

    // ── Submit validation ─────────────────────────────────────────────────────
    $('#leaveRequestForm').on('submit', function (e) {
        const $btn = $('#submitBtn');

if ($btn.prop('disabled')) { e.preventDefault(); return false; }
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...');
    });

    // ── Tooltip ───────────────────────────────────────────────────────────────
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush
