{{-- resources/views/hr/employees/partials/form/basic-info-section.blade.php --}}
<div class="form-section mb-4">
    <div class="section-header">
        <h5 class="section-title">
            <i class="bi bi-person-badge-fill me-2"></i>Basic Information
        </h5>
        <p class="section-subtitle">Required employee identification details</p>
    </div>
    <div class="section-body">
        <div class="row">
            <!-- Employee Number -->
            <div class="col-md-6 mb-3">
                <label for="employee_no" class="form-label">
                    Employee Number <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">DCM-</span>
                    <input type="text" class="form-control" id="employee_no" name="employee_no"
                           value="{{ old('employee_no', isset($employee) ? $employee->employee_number_only : '') }}"
                           placeholder="0001" maxlength="10" required
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                <small class="text-muted">Enter 4-digit number (e.g., 0001)</small>
                @error('employee_no')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
                <div class="validation-feedback" id="employee-no-feedback"></div>
            </div>

            <!-- Full Name -->
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">
                    Full Name <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name', $employee->name ?? '') }}" 
                           placeholder="Enter full name" required>
                </div>
                @error('name')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Employment Type -->
            <div class="col-md-4 mb-3">
                <label for="employment_type" class="form-label">
                    Employment Type <span class="text-danger">*</span>
                </label>
                <select name="employment_type" id="employment_type" class="form-select" required>
                    <option value="">Select Type</option>
                    @foreach ($employmentTypes as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('employment_type', $employee->employment_type ?? '') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('employment_type')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Employment Status -->
            <div class="col-md-4 mb-3">
                <label for="status" class="form-label">
                    Employment Status <span class="text-danger">*</span>
                </label>
                <select name="status" id="status" class="form-select" required>
                    <option value="active"
                        {{ old('status', $employee->status ?? 'active') == 'active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="inactive" 
                        {{ old('status', $employee->status ?? '') == 'inactive' ? 'selected' : '' }}>
                        Inactive
                    </option>
                    <option value="terminated" 
                        {{ old('status', $employee->status ?? '') == 'terminated' ? 'selected' : '' }}>
                        Terminated
                    </option>
                </select>
                @error('status')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Gender -->
            <div class="col-md-4 mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select name="gender" id="gender" class="form-select">
                    <option value="">Select Gender</option>
                    <option value="male" 
                        {{ old('gender', $employee->gender ?? '') == 'male' ? 'selected' : '' }}>
                        Male
                    </option>
                    <option value="female" 
                        {{ old('gender', $employee->gender ?? '') == 'female' ? 'selected' : '' }}>
                        Female
                    </option>
                </select>
                @error('gender')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>
        </div>
    </div>
</div>