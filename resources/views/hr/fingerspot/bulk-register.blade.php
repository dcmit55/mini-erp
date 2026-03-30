@extends('layouts.app')

@section('title', 'Bulk Register Employee - Fingerspot')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-3">
        <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="icon-shape icon-lg bg-soft-primary rounded-3">
            <i class="fas fa-users text-primary fs-4"></i>
        </div>
        <div>
            <h4 class="mb-1 fw-semibold">Bulk Register to Device</h4>
            <p class="text-muted mb-0 small">Pilih satu atau beberapa karyawan untuk didaftarkan ke mesin fingerspot sekaligus</p>
        </div>
    </div>

    @foreach (['success','warning','error','info'] as $type)
        @if (session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-{{ $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle') }} me-2"></i>
            {{ session($type) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    @endforeach

    <form action="{{ route('fingerspot.bulk-register') }}" method="POST" id="bulk-form">
        @csrf
        <input type="hidden" name="device_id" value="{{ $defaultDeviceId }}">

        <div class="row g-3">
            {{-- Left: Filter + Table --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold mb-1">Cari Karyawan</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="search-input" class="form-control"
                                        placeholder="Nama atau NIK..." value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Departemen</label>
                                <select id="dept-filter" class="form-select form-select-sm">
                                    <option value="">Semua Departemen</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Status</label>
                                <select id="status-filter" class="form-select form-select-sm">
                                    <option value="" {{ !request('filter') ? 'selected' : '' }}>Semua</option>
                                    <option value="not_registered" {{ request('filter') === 'not_registered' ? 'selected' : '' }}>Belum Terdaftar</option>
                                    <option value="registered" {{ request('filter') === 'registered' ? 'selected' : '' }}>Sudah Terdaftar</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom bg-light">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="select-all">
                                <label class="form-check-label small fw-semibold" for="select-all">
                                    Pilih Semua (<span id="visible-count">{{ $employees->count() }}</span>)
                                </label>
                            </div>
                            <span class="text-muted small" id="selected-info">0 dipilih</span>
                        </div>

                        <div style="max-height: 520px; overflow-y: auto;">
                            <table class="table table-hover table-sm mb-0" id="emp-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width:40px;"></th>
                                        <th class="small">NIK</th>
                                        <th class="small">Nama</th>
                                        <th class="small">Departemen</th>
                                        <th class="small text-center">PIN</th>
                                        <th class="small text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($employees as $emp)
                                    <tr class="emp-row"
                                        data-name="{{ strtolower($emp->name) }}"
                                        data-no="{{ strtolower($emp->employee_no) }}"
                                        data-dept="{{ $emp->department_id }}">
                                        <td>
                                            <input class="form-check-input emp-checkbox" type="checkbox"
                                                name="employee_ids[]" value="{{ $emp->id }}">
                                        </td>
                                        <td class="small font-monospace">{{ $emp->employee_no }}</td>
                                        <td class="small fw-semibold">{{ $emp->name }}</td>
                                        <td class="small text-muted">{{ $emp->department->name ?? '-' }}</td>
                                        <td class="small text-center font-monospace">{{ $emp->device_pin }}</td>
                                        <td class="text-center">
                                            @if ($emp->device_registered_at)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;">
                                                    <i class="fas fa-check me-1"></i>Terdaftar
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:0.65rem;">
                                                    Belum
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4 small">
                                            <i class="fas fa-users-slash mb-2 d-block fs-4 opacity-25"></i>
                                            Tidak ada karyawan ditemukan
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Options + Submit --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 sticky-top" style="top: 80px;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-cog me-2 text-muted"></i>Opsi Registrasi</h6>
                    </div>
                    <div class="card-body px-4 py-3">

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Access Level <span class="text-danger">*</span></label>
                            <select name="privilege" class="form-select form-select-sm @error('privilege') is-invalid @enderror" required>
                                <option value="1" selected>Regular User</option>
                                <option value="3">Sub-admin</option>
                                <option value="2">Administrator</option>
                            </select>
                            <div class="form-text small">Berlaku untuk semua karyawan yang dipilih</div>
                            @error('privilege')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="p-3 rounded-3 mb-4" style="background:rgba(var(--bs-primary-rgb),0.06);border:1px solid rgba(var(--bs-primary-rgb),0.15);">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-fingerprint text-primary"></i>
                                <span class="small fw-semibold">Device Target</span>
                            </div>
                            <div class="font-monospace small text-muted">{{ $defaultDeviceId ?: 'Tidak dikonfigurasi' }}</div>
                        </div>

                        <div class="p-3 rounded-3 mb-4" id="summary-box" style="background:#f8f9fa;border:1px solid #e9ecef;">
                            <div class="small fw-semibold mb-2 text-muted">Ringkasan</div>
                            <div class="d-flex justify-content-between small">
                                <span>Dipilih</span>
                                <span class="fw-bold" id="summary-count">0 karyawan</span>
                            </div>
                        </div>

                        @error('employee_ids')
                        <div class="alert alert-danger py-2 px-3 small mb-3">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                        @enderror

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm rounded-2" id="submit-btn" disabled>
                                <i class="fas fa-user-plus me-2"></i>
                                Register ke Device (<span id="btn-count">0</span>)
                            </button>
                            <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2">
                                Batal
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput  = document.getElementById('search-input');
    const deptFilter   = document.getElementById('dept-filter');
    const statusFilter = document.getElementById('status-filter');
    const selectAll    = document.getElementById('select-all');
    const rows         = document.querySelectorAll('.emp-row');
    const checkboxes   = document.querySelectorAll('.emp-checkbox');

    function getVisibleRows() {
        return [...rows].filter(r => r.style.display !== 'none');
    }

    function updateCounts() {
        const checked    = [...checkboxes].filter(c => c.checked).length;
        const visible    = getVisibleRows().length;
        document.getElementById('visible-count').textContent = visible;
        document.getElementById('selected-info').textContent = checked + ' dipilih';
        document.getElementById('summary-count').textContent = checked + ' karyawan';
        document.getElementById('btn-count').textContent     = checked;
        document.getElementById('submit-btn').disabled       = checked === 0;

        const visibleChecked = getVisibleRows().filter(r => r.querySelector('.emp-checkbox').checked).length;
        selectAll.indeterminate = visibleChecked > 0 && visibleChecked < visible;
        selectAll.checked       = visible > 0 && visibleChecked === visible;
    }

    function applyFilters() {
        const q    = searchInput.value.trim().toLowerCase();
        const dept = deptFilter.value;
        const stat = statusFilter.value;

        rows.forEach(row => {
            const matchSearch = !q || row.dataset.name.includes(q) || row.dataset.no.includes(q);
            const matchDept   = !dept || row.dataset.dept === dept;
            const badge       = row.querySelector('.badge');
            const isReg       = badge && badge.classList.contains('bg-success-subtle');
            const matchStatus = !stat
                || (stat === 'registered'     &&  isReg)
                || (stat === 'not_registered' && !isReg);

            row.style.display = (matchSearch && matchDept && matchStatus) ? '' : 'none';
        });

        updateCounts();
    }

    searchInput.addEventListener('input', applyFilters);
    deptFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    selectAll.addEventListener('change', function () {
        getVisibleRows().forEach(row => {
            row.querySelector('.emp-checkbox').checked = this.checked;
        });
        updateCounts();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateCounts));

    // Confirm before submit
    document.getElementById('bulk-form').addEventListener('submit', function (e) {
        const count = [...checkboxes].filter(c => c.checked).length;
        if (!confirm(`Daftarkan ${count} karyawan ke mesin fingerspot?\n\nKaryawan yang sudah terdaftar akan diperbarui.`)) {
            e.preventDefault();
        }
    });

    updateCounts();
});
</script>
@endpush
