{{-- resources/views/hr/employees/partials/form/employment-section.blade.php --}}
<div class="form-section mb-4">
    <div class="section-header">
        <h5 class="section-title">
            <i class="bi bi-briefcase-fill me-2"></i>Employment Details
        </h5>
        <p class="section-subtitle">Position and department information</p>
    </div>
    <div class="section-body">
        <div class="row">
            <!-- Department -->
            <div class="col-md-6 mb-3">
                <label for="department_id" class="form-label">
                    Department <span class="text-danger">*</span>
                </label>
                <select name="department_id" id="department_id" class="form-select" required>
                    <option value="">Select Department</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}"
                            {{ old('department_id', $employee->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
                @error('department_id')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Position -->
            <div class="col-md-6 mb-3">
                <label for="position" class="form-label">
                    Position <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                    <input type="text" class="form-control" id="position" name="position"
                           value="{{ old('position', $employee->position ?? '') }}" required>
                </div>
                @error('position')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Hire Date -->
            <div class="col-md-6 mb-3">
                <label for="hire_date" class="form-label">Hire Date</label>
                <input type="date" class="form-control" id="hire_date" name="hire_date"
                       value="{{ old('hire_date', isset($employee) && $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '') }}"
                       max="{{ date('Y-m-d') }}">
                @error('hire_date')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Contract End Date -->
            <div class="col-md-6 mb-3">
                <label for="contract_end_date" class="form-label">Contract End Date</label>
                <input type="date" class="form-control" id="contract_end_date" name="contract_end_date"
                       value="{{ old('contract_end_date', isset($employee) && $employee->contract_end_date ? $employee->contract_end_date->format('Y-m-d') : '') }}"
                       min="{{ old('hire_date', isset($employee) && $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '') }}">
                @error('contract_end_date')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Leave Balance -->
            <div class="col-md-6 mb-3">
                <label for="saldo_cuti" class="form-label">
                    Leave Balance <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control" id="saldo_cuti" name="saldo_cuti"
                           min="0" max="999.99" step="0.5"
                           value="{{ old('saldo_cuti', isset($employee) ? number_format($employee->saldo_cuti, 2, '.', '') : 12) }}"
                           placeholder="12 or 11.5" required>
                    <span class="input-group-text">days</span>
                </div>
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> Annual leave balance in days (can use 0.5 for half day)
                </small>
                @error('saldo_cuti')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>
        </div>
    </div>
</div>