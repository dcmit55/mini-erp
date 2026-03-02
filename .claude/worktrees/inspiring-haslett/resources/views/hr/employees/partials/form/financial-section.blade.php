{{-- resources/views/hr/employees/partials/form/financial-section.blade.php --}}
<div class="form-section mb-4">
    <div class="section-header">
        <h5 class="section-title">
            <i class="bi bi-credit-card-fill me-2"></i>Financial Information
        </h5>
        <p class="section-subtitle">Salary and banking details</p>
    </div>
    <div class="section-body">
        <div class="row">
            <!-- Salary -->
            <div class="col-md-6 mb-3">
                <label for="salary" class="form-label">Monthly Salary</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" id="salary" name="salary"
                           value="{{ old('salary', $employee->salary ?? '') }}" 
                           placeholder="0" min="0"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                @error('salary')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Bank Account -->
            <div class="col-md-6 mb-3">
                <label for="rekening" class="form-label">Bank Account Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                    <input type="text" class="form-control" id="rekening" name="rekening"
                           value="{{ old('rekening', $employee->rekening ?? '') }}" 
                           placeholder="1234-5678-9012-3456"
                           oninput="this.value = this.value.replace(/[^0-9-]/g, '')">
                </div>
                @error('rekening')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>
        </div>
    </div>
</div>