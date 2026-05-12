@extends('layouts.app')

@section('title', 'Edit Draft — ' . $warningLetter->letter_number)

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-7">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('warning-letters.show', $warningLetter) }}"
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Edit Draft</h5>
                    <p class="text-muted small mb-0">{{ $warningLetter->letter_number }} — {{ $warningLetter->spLabel }}</p>
                </div>
                @php
                    $spBadge = [1=>'info',2=>'warning',3=>'warning',4=>'danger'];
                    $spColor = $spBadge[$warningLetter->sp_level] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $spColor }} bg-opacity-10 text-{{ $spColor }} border border-{{ $spColor }} border-opacity-25 rounded-2 px-3 py-2 {{ $warningLetter->sp_level >= 2 && $warningLetter->sp_level <= 3 ? 'text-dark' : '' }}">
                    {{ $warningLetter->spLabel }}
                </span>
            </div>

            {{-- Flash --}}
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Form Card --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('warning-letters.update', $warningLetter) }}">
                        @csrf @method('PUT')

                        <div class="row g-3">

                            {{-- Nomor Surat --}}
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">
                                    Nomor Surat <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="letter_number" id="letterNumberInput"
                                    class="form-control form-control-sm border-1 rounded-2 @error('letter_number') is-invalid @enderror"
                                    placeholder="Contoh: 076 /DCM-SP-1/III/2026"
                                    value="{{ old('letter_number', $warningLetter->letter_number) }}"
                                    autocomplete="off"
                                    required>
                                <div class="form-text">
                                    SP level dibaca otomatis. Sistem mengenali <strong>SP-1</strong>, <strong>SP-2</strong>, <strong>SP-3</strong> di mana saja dalam nomor surat.
                                </div>
                                @error('letter_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Live banner --}}
                            <div class="col-12 pt-0 mt-0">
                                <div id="spDetectedBanner"></div>
                            </div>

                            {{-- Employee (readonly) --}}
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Employee</label>
                                <input type="text"
                                    class="form-control form-control-sm border-1 rounded-2 bg-light"
                                    value="{{ $warningLetter->employee->employee_no }} — {{ $warningLetter->employee->name }}"
                                    readonly>
                                <div class="form-text">Karyawan tidak dapat diubah setelah draft dibuat.</div>
                            </div>

                            {{-- Category --}}
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Kategori Pelanggaran <span class="text-danger">*</span></label>
                                <select name="violation_cat_id"
                                    class="form-select form-select-sm border-1 rounded-2 @error('violation_cat_id') is-invalid @enderror" required>
                                    @foreach($violationCategories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('violation_cat_id', $warningLetter->violation_cat_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('violation_cat_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Date --}}
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Tanggal Pelanggaran <span class="text-danger">*</span></label>
                                <input type="date" name="violation_date"
                                    class="form-control form-control-sm border-1 rounded-2 @error('violation_date') is-invalid @enderror"
                                    value="{{ old('violation_date', $warningLetter->violation_date->format('Y-m-d')) }}"
                                    max="{{ date('Y-m-d') }}" required>
                                @error('violation_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Reason --}}
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Deskripsi Pelanggaran <span class="text-danger">*</span></label>
                                <textarea name="reason" rows="4"
                                    class="form-control form-control-sm border-1 rounded-2 @error('reason') is-invalid @enderror"
                                    required>{{ old('reason', $warningLetter->reason) }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Template --}}
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Template Surat</label>
                                <select name="template_id" class="form-select form-select-sm border-1 rounded-2">
                                    <option value="">-- Default template --</option>
                                    @foreach($templates as $tmpl)
                                        <option value="{{ $tmpl->id }}"
                                            {{ old('template_id', $warningLetter->template_id) == $tmpl->id ? 'selected' : '' }}>
                                            SP{{ $tmpl->sp_level }} — {{ $tmpl->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end border-top mt-3 pt-3">
                            <a href="{{ route('warning-letters.show', $warningLetter) }}"
                               class="btn btn-outline-secondary btn-sm rounded-2 px-3">Batal</a>
                            <button type="submit" class="btn btn-primary btn-sm rounded-2 px-4">
                                <i class="fas fa-save me-1"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

<style>
.form-control, .form-select { border-color: #e2e8f0; font-size: 0.9rem; }
.form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 0.2rem rgba(79,70,229,.1); }
.btn { font-size: 0.9rem; font-weight: 500; }
.btn-primary { background-color: #4f46e5; border-color: #4f46e5; }
.btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
.card { background: #fff; border: 1px solid #e2e8f0; }
.bg-light { background-color: #f8fafc !important; }
.text-muted { color: #6b7280 !important; }
.text-dark { color: #374151 !important; }
.rounded-2 { border-radius: .5rem !important; }
.rounded-3 { border-radius: .75rem !important; }
.fw-medium { font-weight: 500 !important; }
</style>

@push('scripts')
<script>
$(function () {
    const bannerCfg = {
        1: { bg:'#eff6ff', border:'#3b82f6', text:'#1d4ed8', label:'SP1 — First Warning',  icon:'fa-info-circle' },
        2: { bg:'#fefce8', border:'#eab308', text:'#854d0e', label:'SP2 — Second Warning', icon:'fa-exclamation-circle' },
        3: { bg:'#fef2f2', border:'#ef4444', text:'#991b1b', label:'SP3 — Final Warning',  icon:'fa-exclamation-triangle' },
    };

    function detectSp(val) {
        const m = val.match(/\bSP[-\s\/]?([123])\b/i);
        return m ? parseInt(m[1]) : null;
    }

    function updateBanner(level) {
        const $b = $('#spDetectedBanner');
        if (!level) {
            $b.html('<div class="small text-danger"><i class="fas fa-exclamation-circle me-1"></i>SP level tidak terdeteksi. Pastikan nomor surat mengandung SP1, SP2, atau SP3.</div>');
            return;
        }
        const c = bannerCfg[level];
        const extra = level === 3 ? '<br><strong>⚠ SP3 adalah peringatan terakhir.</strong>' : '';
        $b.html(
            `<div class="d-flex align-items-start gap-2 rounded-3 p-2 px-3 border"
                  style="background:${c.bg};border-color:${c.border}30 !important;border-left:4px solid ${c.border} !important;font-size:0.82rem;">
                <i class="fas ${c.icon} mt-1" style="color:${c.border};"></i>
                <div style="color:${c.text};">
                    Terdeteksi sebagai <strong>${c.label}</strong>. SP level akan diperbarui.${extra}
                </div>
             </div>`
        );
    }

    updateBanner(detectSp($('#letterNumberInput').val()));

    $('#letterNumberInput').on('input', function () {
        updateBanner(detectSp($(this).val()));
    });
});
</script>
@endpush
