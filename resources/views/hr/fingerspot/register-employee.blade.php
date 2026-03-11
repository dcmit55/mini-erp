@extends('layouts.app')

@section('title', 'Register Employee - Fingerspot')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Register Employee</h5>
                    <p class="text-muted small mb-0">Add an employee to the fingerprint device</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    @if(session('success'))
                        <div class="alert alert-success border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('fingerspot.register-employee') }}" method="POST">
                        @csrf

                        {{-- Hidden Device ID --}}
                        <input type="hidden" name="device_id" value="{{ $defaultDeviceId }}">

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="pin" class="form-label small text-dark">Employee ID on Device <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-1 rounded-2 py-2 px-3 @error('pin') is-invalid @enderror"
                                       id="pin" name="pin" value="{{ old('pin') }}"
                                       placeholder="Auto-filled from employee selection" required>
                                @error('pin')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="privilege" class="form-label small text-dark">Access Level <span class="text-danger">*</span></label>
                                <select class="form-select border-1 rounded-2 py-2 px-3 @error('privilege') is-invalid @enderror"
                                        id="privilege" name="privilege" required>
                                    <option value="">-- Select Access Level --</option>
                                    <option value="1" {{ old('privilege') == '1' ? 'selected' : '' }}>Regular User</option>
                                    <option value="3" {{ old('privilege') == '3' ? 'selected' : '' }}>Sub-admin</option>
                                    <option value="2" {{ old('privilege') == '2' ? 'selected' : '' }}>Administrator</option>
                                </select>
                                @error('privilege')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Employee search + Name --}}
                        <div class="mb-3">
                            <label for="employee_search" class="form-label small text-dark">Select Employee <span class="text-danger">*</span></label>
                            <input type="text"
                                   id="employee_search"
                                   class="form-control border-1 rounded-2 py-2 px-3"
                                   placeholder="Ketik nama atau NIK karyawan..."
                                   autocomplete="off">
                            <input type="hidden" id="name" name="name" value="{{ old('name') }}" required>
                            <div id="employee_dropdown" class="list-group shadow-sm mt-1" style="display:none; max-height:220px; overflow-y:auto; position:absolute; z-index:1000; width:auto; min-width:300px;"></div>
                            <div class="form-text small">Ketik nama atau NIK untuk mencari, lalu pilih dari daftar</div>
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label small text-dark">Password <span class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control border-1 rounded-2 py-2 px-3"
                                       id="password" name="password" value="{{ old('password') }}" placeholder="Leave blank if not used">
                            </div>
                            <div class="col-md-6">
                                <label for="rfid" class="form-label small text-dark">RFID <span class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control border-1 rounded-2 py-2 px-3"
                                       id="rfid" name="rfid" value="{{ old('rfid') }}" placeholder="RFID card number">
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-user-plus me-1"></i> Register to Device
                            </button>
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
document.addEventListener('DOMContentLoaded', function () {
    const employees = @json($employees->map(fn($e) => ['id' => $e->id, 'employee_no' => $e->employee_no, 'name' => $e->name]));

    const searchInput  = document.getElementById('employee_search');
    const nameHidden   = document.getElementById('name');
    const pinInput     = document.getElementById('pin');
    const dropdown     = document.getElementById('employee_dropdown');

    function pinFromEmployeeNo(employeeNo) {
        // Strip "DCM-" prefix then remove leading zeros
        const numeric = employeeNo.replace(/^DCM-/i, '');
        return String(parseInt(numeric, 10) || 0);
    }

    // Pre-fill if old value exists
    const oldName = nameHidden.value;
    if (oldName) {
        const found = employees.find(e => e.name === oldName);
        if (found) {
            searchInput.value = found.employee_no + ' - ' + found.name;
            pinInput.value    = pinFromEmployeeNo(found.employee_no);
        }
    }

    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        dropdown.innerHTML = '';

        if (q.length < 1) {
            dropdown.style.display = 'none';
            return;
        }

        const matches = employees.filter(e =>
            e.name.toLowerCase().includes(q) || e.employee_no.toLowerCase().includes(q)
        ).slice(0, 30);

        if (matches.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        matches.forEach(emp => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action py-2 px-3 small';
            item.textContent = emp.employee_no + ' - ' + emp.name;
            item.addEventListener('click', function () {
                searchInput.value = emp.employee_no + ' - ' + emp.name;
                nameHidden.value  = emp.name;
                pinInput.value    = pinFromEmployeeNo(emp.employee_no);
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(item);
        });

        dropdown.style.display = 'block';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
</script>
@endpush
