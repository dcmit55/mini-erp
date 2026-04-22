@extends('layouts.app')

@section('title', 'New Warning Letter')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-7">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('warning-letters.index') }}"
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">New Warning Letter</h5>
                    <p class="text-muted small mb-0">SP level ditentukan otomatis berdasarkan riwayat karyawan</p>
                </div>
            </div>

            {{-- Flash --}}
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- SP Banner --}}
            @if($terminationFlag)
                <div class="alert mb-3 d-flex align-items-start gap-2 border-0 rounded-3"
                     style="background:#fef2f2;border-left:4px solid #ef4444 !important;">
                    <i class="fas fa-exclamation-triangle text-danger mt-1"></i>
                    <div>
                        <div class="fw-medium text-danger">Karyawan ini memiliki SP3 aktif — Peringatan Terakhir.</div>
                        <div class="text-muted small">SP3 adalah batas maksimum. Karyawan ini harus diproses melalui prosedur <strong>Terminasi (PHK)</strong> — buka detail SP3 dan gunakan tombol <strong>Terminate</strong>.</div>
                    </div>
                </div>
            @elseif($suggestedSpLevel)
                @php
                    $bannerMap = [
                        1 => ['bg'=>'#eff6ff','border'=>'#3b82f6','text'=>'#1d4ed8'],
                        2 => ['bg'=>'#fefce8','border'=>'#eab308','text'=>'#854d0e'],
                        3 => ['bg'=>'#fef2f2','border'=>'#ef4444','text'=>'#991b1b'],
                    ];
                    $bm = $bannerMap[$suggestedSpLevel] ?? ['bg'=>'#f8fafc','border'=>'#94a3b8','text'=>'#475569'];
                @endphp
                <div class="mb-3 d-flex align-items-start gap-2 rounded-3 p-3 border"
                     style="background:{{ $bm['bg'] }};border-color:{{ $bm['border'] }}30 !important;border-left:4px solid {{ $bm['border'] }} !important;">
                    <i class="fas fa-info-circle mt-1" style="color:{{ $bm['border'] }};"></i>
                    <div class="small" style="color:{{ $bm['text'] }};">
                        @if($activeSpLevel)
                            Karyawan ini memiliki <strong>SP{{ $activeSpLevel }} aktif</strong>. Pelanggaran baru akan menghasilkan
                        @else
                            Karyawan ini belum memiliki SP sebelumnya. Pelanggaran ini akan menghasilkan
                        @endif
                        <strong>SP{{ $suggestedSpLevel }}</strong>.
                        @if($suggestedSpLevel === 3)
                            <br><strong>⚠ SP3 adalah peringatan terakhir.</strong> Setelah difinalisasi, karyawan dapat langsung di-terminate.
                        @endif
                    </div>
                </div>
            @endif

            {{-- Form Card --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('warning-letters.store') }}" id="formCreateWL">
                        @csrf

                        <div class="row g-3">
                            {{-- Employee --}}
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" id="employeeSelect"
                                    class="form-select form-select-sm border-1 rounded-2 select2-employee @error('employee_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Karyawan --</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}"
                                            {{ old('employee_id', request('employee_id')) == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->employee_no }} — {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Setelah memilih, SP level akan disarankan otomatis.</div>
                            </div>

                            {{-- Category --}}
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Kategori Pelanggaran <span class="text-danger">*</span></label>
                                <select name="violation_cat_id"
                                    class="form-select form-select-sm border-1 rounded-2 @error('violation_cat_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($violationCategories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('violation_cat_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }} ({{ $cat->severity }})
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
                                    value="{{ old('violation_date', date('Y-m-d')) }}"
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
                                    placeholder="Jelaskan kronologi dan detail pelanggaran..."
                                    required>{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Template --}}
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Template Surat</label>
                                <select name="template_id" class="form-select form-select-sm border-1 rounded-2">
                                    <option value="">-- Default template sesuai SP level --</option>
                                    @foreach($templates as $tmpl)
                                        <option value="{{ $tmpl->id }}" {{ old('template_id') == $tmpl->id ? 'selected' : '' }}>
                                            SP{{ $tmpl->sp_level }} — {{ $tmpl->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end border-top mt-3 pt-3">
                            <a href="{{ route('warning-letters.index') }}"
                               class="btn btn-outline-secondary btn-sm rounded-2 px-3">Batal</a>
                            @if(!$terminationFlag)
                                <button type="submit" class="btn btn-primary btn-sm rounded-2 px-4">
                                    <i class="fas fa-save me-1"></i>Simpan sebagai Draft
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.select2-employee').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari atau pilih karyawan --',
        allowClear: true,
    });
    $('#employeeSelect').on('change', function () {
        const val = $(this).val();
        if (val) window.location.href = '{{ route('warning-letters.create') }}?employee_id=' + val;
    });
});
</script>
@endpush

<style>
.form-control, .form-select { border-color: #e2e8f0; font-size: 0.9rem; }
.form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 0.2rem rgba(79,70,229,.1); }
.btn { font-size: 0.9rem; font-weight: 500; }
.btn-primary { background-color: #4f46e5; border-color: #4f46e5; }
.btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
.card { background: #fff; border: 1px solid #e2e8f0; }
.text-muted { color: #6b7280 !important; }
.text-dark { color: #374151 !important; }
.rounded-2 { border-radius: .5rem !important; }
.rounded-3 { border-radius: .75rem !important; }
.fw-medium { font-weight: 500 !important; }
</style>
