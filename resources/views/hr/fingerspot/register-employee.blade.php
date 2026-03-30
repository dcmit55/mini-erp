@extends('layouts.app')

@section('title', 'Register Employee - Fingerspot')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Register Employee</h5>
                    <p class="text-muted small mb-0">Add employees to the fingerprint device</p>
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

            {{-- Tabs --}}
            <ul class="nav nav-tabs mb-0" id="registerTabs">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-single">
                        <i class="fas fa-user-plus me-2"></i>Single
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-bulk">
                        <i class="fas fa-users me-2"></i>Bulk Register
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- ── SINGLE TAB ─────────────────────────────────────────── --}}
                <div class="tab-pane fade show active" id="tab-single">
                    <div class="card border-0 shadow-sm rounded-bottom-3 rounded-top-0">
                        <div class="card-body p-4">
                            <form action="{{ route('fingerspot.register-employee') }}" method="POST">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $defaultDeviceId }}">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Employee ID on Device <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('pin') is-invalid @enderror"
                                               id="pin" name="pin" value="{{ old('pin') }}"
                                               placeholder="Auto-filled from employee selection" required>
                                        @error('pin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Access Level <span class="text-danger">*</span></label>
                                        <select class="form-select @error('privilege') is-invalid @enderror" name="privilege" required>
                                            <option value="">-- Select --</option>
                                            <option value="1" {{ old('privilege') == '1' ? 'selected' : '' }}>Regular User</option>
                                            <option value="3" {{ old('privilege') == '3' ? 'selected' : '' }}>Sub-admin</option>
                                            <option value="2" {{ old('privilege') == '2' ? 'selected' : '' }}>Administrator</option>
                                        </select>
                                        @error('privilege')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Select Employee <span class="text-danger">*</span></label>
                                    <input type="text" id="employee_search" class="form-control"
                                           placeholder="Ketik nama atau NIK karyawan..." autocomplete="off">
                                    <input type="hidden" id="name" name="name" value="{{ old('name') }}" required>
                                    <div id="employee_dropdown" class="list-group shadow-sm mt-1"
                                         style="display:none;max-height:220px;overflow-y:auto;position:absolute;z-index:1000;min-width:300px;"></div>
                                    <div class="form-text small">Ketik nama atau NIK untuk mencari, lalu pilih dari daftar</div>
                                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Password <span class="text-muted">(optional)</span></label>
                                        <input type="text" class="form-control" name="password" value="{{ old('password') }}" placeholder="Leave blank if not used">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">RFID <span class="text-muted">(optional)</span></label>
                                        <input type="text" class="form-control" name="rfid" value="{{ old('rfid') }}" placeholder="RFID card number">
                                    </div>
                                </div>

                                <div class="d-flex gap-2 pt-3 border-top">
                                    <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">Cancel</a>
                                    <button type="submit" class="btn btn-primary btn-sm rounded-2 px-4">
                                        <i class="fas fa-user-plus me-1"></i> Register to Device
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── BULK TAB ────────────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tab-bulk">
                    <div class="card border-0 shadow-sm rounded-bottom-3 rounded-top-0">
                        <div class="card-body p-4">
                            <form action="{{ route('fingerspot.bulk-register') }}" method="POST" id="bulk-form">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $defaultDeviceId }}">

                                @error('employee_ids')
                                <div class="alert alert-danger py-2 px-3 small mb-3">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                                @enderror

                                <div class="row g-3 mb-3">
                                    {{-- Filter bar --}}
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Cari</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" id="bulk-search" class="form-control" placeholder="Nama atau NIK...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1">Departemen</label>
                                        <select id="bulk-dept" class="form-select form-select-sm">
                                            <option value="">Semua</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1">Status</label>
                                        <select id="bulk-status" class="form-select form-select-sm">
                                            <option value="">Semua</option>
                                            <option value="not_registered">Belum Terdaftar</option>
                                            <option value="registered">Sudah Terdaftar</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold mb-1">Access Level</label>
                                        <select name="privilege" class="form-select form-select-sm @error('privilege') is-invalid @enderror" required>
                                            <option value="1" selected>Regular User</option>
                                            <option value="3">Sub-admin</option>
                                            <option value="2">Administrator</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Select all bar --}}
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-2 mb-0"
                                     style="background:#f8f9fa;border:1px solid #e9ecef;border-bottom:none;">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="bulk-select-all">
                                        <label class="form-check-label small fw-semibold" for="bulk-select-all">
                                            Pilih Semua (<span id="bulk-visible-count">{{ $employees->count() }}</span>)
                                        </label>
                                    </div>
                                    <span class="small text-muted" id="bulk-selected-info">0 dipilih</span>
                                </div>

                                {{-- Table --}}
                                <div style="max-height:420px;overflow-y:auto;border:1px solid #e9ecef;border-radius:0 0 8px 8px;">
                                    <table class="table table-hover table-sm mb-0" id="bulk-table">
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
                                            <tr class="bulk-row"
                                                data-name="{{ strtolower($emp->name) }}"
                                                data-no="{{ strtolower($emp->employee_no) }}"
                                                data-dept="{{ $emp->department_id }}"
                                                data-reg="{{ $emp->device_registered_at ? '1' : '0' }}">
                                                <td>
                                                    <input class="form-check-input bulk-cb" type="checkbox"
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
                                                    <i class="fas fa-users-slash d-block mb-2 fs-4 opacity-25"></i>
                                                    Tidak ada karyawan
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-3 mt-3 border-top gap-3">
                                    <span class="small text-muted">
                                        Device: <span class="font-monospace">{{ $defaultDeviceId ?: '-' }}</span>
                                    </span>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">Batal</a>
                                        <button type="submit" class="btn btn-warning btn-sm rounded-2 px-4 fw-semibold" id="bulk-submit-btn" disabled>
                                            <i class="fas fa-users me-1"></i>
                                            Register (<span id="bulk-btn-count">0</span>) ke Device
                                        </button>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

            </div>{{-- end tab-content --}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Single tab: employee search autocomplete ──────────────────────────────
    const employees = @json($employees->map(fn($e) => ['id' => $e->id, 'employee_no' => $e->employee_no, 'name' => $e->name]));

    const searchInput = document.getElementById('employee_search');
    const nameHidden  = document.getElementById('name');
    const pinInput    = document.getElementById('pin');
    const dropdown    = document.getElementById('employee_dropdown');

    function pinFromNo(no) {
        return String(parseInt(no.replace(/^DCM-/i, ''), 10) || 0);
    }

    const oldName = nameHidden?.value;
    if (oldName) {
        const found = employees.find(e => e.name === oldName);
        if (found) {
            searchInput.value = found.employee_no + ' - ' + found.name;
            pinInput.value    = pinFromNo(found.employee_no);
        }
    }

    searchInput?.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        dropdown.innerHTML = '';
        if (!q) { dropdown.style.display = 'none'; return; }

        const matches = employees.filter(e =>
            e.name.toLowerCase().includes(q) || e.employee_no.toLowerCase().includes(q)
        ).slice(0, 30);

        if (!matches.length) { dropdown.style.display = 'none'; return; }

        matches.forEach(emp => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action py-2 px-3 small';
            item.textContent = emp.employee_no + ' - ' + emp.name;
            item.addEventListener('click', function () {
                searchInput.value = emp.employee_no + ' - ' + emp.name;
                nameHidden.value  = emp.name;
                pinInput.value    = pinFromNo(emp.employee_no);
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(item);
        });
        dropdown.style.display = 'block';
    });

    document.addEventListener('click', e => {
        if (!searchInput?.contains(e.target) && !dropdown?.contains(e.target)) {
            if (dropdown) dropdown.style.display = 'none';
        }
    });

    // ── Bulk tab ──────────────────────────────────────────────────────────────
    const bulkSearch    = document.getElementById('bulk-search');
    const bulkDept      = document.getElementById('bulk-dept');
    const bulkStatus    = document.getElementById('bulk-status');
    const bulkSelectAll = document.getElementById('bulk-select-all');
    const bulkRows      = document.querySelectorAll('.bulk-row');
    const bulkCbs       = document.querySelectorAll('.bulk-cb');

    function visibleRows() {
        return [...bulkRows].filter(r => r.style.display !== 'none');
    }

    function updateBulkCounts() {
        const checked = [...bulkCbs].filter(c => c.checked).length;
        const visible = visibleRows().length;
        document.getElementById('bulk-visible-count').textContent = visible;
        document.getElementById('bulk-selected-info').textContent = checked + ' dipilih';
        document.getElementById('bulk-btn-count').textContent     = checked;
        document.getElementById('bulk-submit-btn').disabled       = checked === 0;

        const visChecked = visibleRows().filter(r => r.querySelector('.bulk-cb').checked).length;
        bulkSelectAll.indeterminate = visChecked > 0 && visChecked < visible;
        bulkSelectAll.checked       = visible > 0 && visChecked === visible;
    }

    function applyBulkFilter() {
        const q    = bulkSearch.value.trim().toLowerCase();
        const dept = bulkDept.value;
        const stat = bulkStatus.value;

        bulkRows.forEach(row => {
            const matchSearch = !q    || row.dataset.name.includes(q) || row.dataset.no.includes(q);
            const matchDept   = !dept || row.dataset.dept === dept;
            const matchStatus = !stat
                || (stat === 'registered'     && row.dataset.reg === '1')
                || (stat === 'not_registered' && row.dataset.reg === '0');

            row.style.display = (matchSearch && matchDept && matchStatus) ? '' : 'none';
        });
        updateBulkCounts();
    }

    bulkSearch?.addEventListener('input', applyBulkFilter);
    bulkDept?.addEventListener('change', applyBulkFilter);
    bulkStatus?.addEventListener('change', applyBulkFilter);

    bulkSelectAll?.addEventListener('change', function () {
        visibleRows().forEach(r => r.querySelector('.bulk-cb').checked = this.checked);
        updateBulkCounts();
    });

    bulkCbs.forEach(cb => cb.addEventListener('change', updateBulkCounts));

    document.getElementById('bulk-form')?.addEventListener('submit', function (e) {
        const count = [...bulkCbs].filter(c => c.checked).length;
        if (!confirm(`Daftarkan ${count} karyawan ke mesin fingerspot?\n\nKaryawan yang sudah terdaftar akan diperbarui.`)) {
            e.preventDefault();
        }
    });

    // Restore bulk tab if there's a validation error for employee_ids
    @error('employee_ids')
    document.querySelector('[data-bs-target="#tab-bulk"]')?.click();
    @enderror

    updateBulkCounts();
});
</script>
@endpush
