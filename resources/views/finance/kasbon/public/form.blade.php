@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 780px;">

    {{-- Guest banner --}}
    <div class="d-flex align-items-start gap-3 rounded-3 p-3 mb-4"
         style="background: rgba(13,110,253,0.07); border: 1px solid rgba(13,110,253,0.18);">
        <i class="fas fa-info-circle text-primary mt-1"></i>
        <div>
            <div class="fw-semibold text-primary small">Pengajuan Kasbon Mandiri</div>
            <div class="text-muted small">Isi form di bawah untuk mengajukan kasbon. Tidak perlu login — pengajuan akan diteruskan ke Finance untuk persetujuan.</div>
        </div>
    </div>

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

    <form method="POST" action="{{ route('kasbon.store') }}" id="kasbonForm" enctype="multipart/form-data">
        @csrf

        {{-- Honeypot --}}
        <input type="text" name="website" style="display:none;" tabindex="-1" autocomplete="off">

        {{-- Section: Identitas Karyawan --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Identitas Karyawan</span>
                </div>

                <div class="row g-3">
                    {{-- Pilih Nama --}}
                    <div class="col-12">
                        <label class="form-label small fw-medium">Nama Karyawan <span class="text-danger">*</span></label>
                        <select name="_employee_select" id="employee_select" class="form-select select2" required>
                            <option value="">Pilih nama karyawan...</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}"
                                    data-nama="{{ $emp->name }}"
                                    data-nik="{{ $emp->employee_no }}"
                                    data-department-id="{{ $emp->department_id }}"
                                    data-department-name="{{ $emp->department->name ?? '' }}"
                                    {{ old('nik_karyawan') == $emp->employee_no ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Info auto-fill (muncul setelah pilih) --}}
                    <div id="employee-info" class="col-12 d-none">
                        <div class="rounded-2 p-3 small" style="background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color);">
                            <div class="row g-2">
                                <div class="col-6 col-sm-4">
                                    <div class="text-muted" style="font-size:.7rem;">NIK / ID</div>
                                    <div class="fw-medium" id="info-nik">—</div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <div class="text-muted" style="font-size:.7rem;">Departemen</div>
                                    <div class="fw-medium" id="info-dept">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden fields yang dikirim ke server --}}
                    <input type="hidden" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap') }}" required>
                    <input type="hidden" name="nik_karyawan" id="nik_karyawan" value="{{ old('nik_karyawan') }}" required>
                    <input type="hidden" name="department_id" id="department_id" value="{{ old('department_id') }}" required>

                    @error('nama_lengkap')<div class="col-12"><div class="text-danger small">{{ $message }}</div></div>@enderror
                    @error('nik_karyawan')<div class="col-12"><div class="text-danger small">{{ $message }}</div></div>@enderror
                    @error('department_id')<div class="col-12"><div class="text-danger small">{{ $message }}</div></div>@enderror

                    <div class="col-sm-6">
                        <label class="form-label small fw-medium">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_wa" class="form-control form-control-sm @error('no_wa') is-invalid @enderror"
                               value="{{ old('no_wa') }}" placeholder="08xxxxxxxxxx" required>
                        @error('no_wa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Detail Kasbon --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Detail Kasbon</span>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-medium">Jumlah Kasbon (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="jumlah" id="jumlah" class="form-control @error('jumlah') is-invalid @enderror"
                                   value="{{ old('jumlah') }}" placeholder="2000000" min="100000" step="50000" required>
                            @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text small text-muted">Minimal Rp 100.000</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-medium">Tenor Cicilan <span class="text-danger">*</span></label>
                        <select name="tenor_bulan" id="tenor_bulan" class="form-select form-select-sm @error('tenor_bulan') is-invalid @enderror" required>
                            <option value="">Pilih tenor...</option>
                            @foreach ([1, 2, 3, 6, 12] as $t)
                                <option value="{{ $t }}" {{ old('tenor_bulan', 3) == $t ? 'selected' : '' }}>
                                    {{ $t }} Bulan
                                </option>
                            @endforeach
                        </select>
                        @error('tenor_bulan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Estimasi cicilan --}}
                    <div class="col-12" id="estimasi-cicilan-wrap" style="display:none;">
                        <div class="rounded-2 p-3 small" style="background: rgba(25,135,84,0.07); border: 1px solid rgba(25,135,84,0.2);">
                            <i class="fas fa-calculator text-success me-1"></i>
                            Estimasi cicilan: <strong id="estimasi-cicilan-text">—</strong>/bulan
                            <span class="text-muted ms-1">(perkiraan, final ditentukan Finance)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Alasan --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Keterangan</span>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Alasan Pengajuan <span class="text-danger">*</span></label>
                    <textarea name="alasan" class="form-control form-control-sm @error('alasan') is-invalid @enderror"
                              rows="3" placeholder="Tuliskan alasan pengajuan kasbon (minimal 20 karakter)..." required>{{ old('alasan') }}</textarea>
                    @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text small text-muted"><span id="alasan-count">0</span> karakter</div>
                </div>

                <div>
                    <label class="form-label small fw-medium">Dokumen Pendukung <span class="text-muted">(opsional)</span></label>
                    <input type="file" name="dokumen" class="form-control form-control-sm @error('dokumen') is-invalid @enderror"
                           accept=".pdf,.jpg,.jpeg,.png">
                    @error('dokumen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text small text-muted">PDF, JPG, PNG — maks. 5MB. Contoh: surat keterangan, bukti tagihan.</div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex justify-content-between align-items-center gap-2">
            <div class="small text-muted">
                <i class="fas fa-lock me-1"></i>Data Anda aman &amp; tidak dibagikan ke pihak lain
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4 rounded-2" onclick="history.back()">Batal</button>
                <button type="button" class="btn btn-outline-secondary btn-sm px-4 rounded-2" onclick="window.location.reload()">Reset</button>
                <button type="submit" id="submitBtn" class="btn btn-primary btn-sm px-4 rounded-2">
                    Kirim Pengajuan
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ── Success modal ────────────────────────────────────────────────────────
    @if (session('kasbon_success'))
    const successData = @json(session('kasbon_success'));
    Swal.fire({
        icon: 'success',
        title: 'Pengajuan Terkirim!',
        html: `<p class="mb-2">Nomor referensi Anda:</p>
               <div class="alert alert-info fw-bold fs-5 mb-2">${successData.ref_number}</div>
               <p class="small text-muted mb-2">Simpan nomor ini untuk memantau status pengajuan.</p>
               <a href="/cek-kasbon?ref=${successData.ref_number}" class="btn btn-sm btn-outline-primary">
                   <i class="fas fa-search me-1"></i>Cek Status Sekarang
               </a>`,
        confirmButtonText: 'OK',
        confirmButtonColor: '#0d6efd',
        allowOutsideClick: false,
    }).then(function () {
        $('#kasbonForm')[0].reset();
        $('#employee_select').val(null).trigger('change');
        $('#employee-info').addClass('d-none');
        $('#estimasi-cicilan-wrap').hide();
        $('#alasan-count').text('0');
    });
    @endif

    // ── Select2 employee ─────────────────────────────────────────────────────
    $('#employee_select').select2({
        width: '100%',
        placeholder: 'Pilih nama karyawan...',
        allowClear: true,
        theme: 'bootstrap-5',
    });

    $('#employee_select').on('change', function () {
        const opt = this.options[this.selectedIndex];
        if ($(this).val()) {
            const nama   = opt.getAttribute('data-nama');
            const nik    = opt.getAttribute('data-nik');
            const deptId = opt.getAttribute('data-department-id');
            const dept   = opt.getAttribute('data-department-name');

            $('#nama_lengkap').val(nama);
            $('#nik_karyawan').val(nik);
            $('#department_id').val(deptId);
            $('#info-nik').text(nik || '—');
            $('#info-dept').text(dept || '—');
            $('#employee-info').removeClass('d-none');
        } else {
            $('#nama_lengkap, #nik_karyawan, #department_id').val('');
            $('#employee-info').addClass('d-none');
        }
    });

    // Trigger jika ada old value
    if ($('#employee_select').val()) {
        $('#employee_select').trigger('change');
    }

    // ── Estimasi cicilan ─────────────────────────────────────────────────────
    function updateEstimasi() {
        const jumlah = parseFloat($('#jumlah').val());
        const tenor  = parseInt($('#tenor_bulan').val());
        if (jumlah >= 100000 && tenor > 0) {
            const perBulan = Math.ceil(jumlah / tenor);
            $('#estimasi-cicilan-text').text('Rp ' + perBulan.toLocaleString('id-ID'));
            $('#estimasi-cicilan-wrap').show();
        } else {
            $('#estimasi-cicilan-wrap').hide();
        }
    }

    $('#jumlah, #tenor_bulan').on('input change', updateEstimasi);

    // ── Hitung karakter alasan ───────────────────────────────────────────────
    $('textarea[name="alasan"]').on('input', function () {
        $('#alasan-count').text($(this).val().length);
    }).trigger('input');

    // ── Submit loading ───────────────────────────────────────────────────────
    $('#kasbonForm').on('submit', function (e) {
        const $btn = $('#submitBtn');
        if ($btn.prop('disabled')) { e.preventDefault(); return false; }
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...');
    });
});
</script>
@endpush
