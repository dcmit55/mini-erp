{{-- resources/views/hr/employees/partials/form/contact-section.blade.php --}}
<div class="form-section mb-4">
    <div class="section-header">
        <h5 class="section-title">
            <i class="bi bi-telephone-fill me-2"></i>Contact Information
        </h5>
        <p class="section-subtitle">How to reach this employee</p>
    </div>
    <div class="section-body">
        <div class="row">
            <!-- Email -->
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           value="{{ old('email', $employee->email ?? '') }}" 
                           placeholder="employee@company.com">
                </div>
                @error('email')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Phone -->
            <div class="col-md-6 mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="{{ old('phone', $employee->phone ?? '') }}" 
                           placeholder="+62 xxx xxxx xxxx"
                           oninput="this.value = this.value.replace(/[^0-9+]/g, '')">
                </div>
                @error('phone')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>

            <!-- Address -->
            <div class="col-md-12 mb-3">
                <label for="address" class="form-label">Full Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"
                          placeholder="Street address, city, postal code...">{{ old('address', $employee->address ?? '') }}</textarea>
                @error('address')
                    <small class="text-danger d-block">
                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                    </small>
                @enderror
            </div>
        </div>
    </div>
</div>