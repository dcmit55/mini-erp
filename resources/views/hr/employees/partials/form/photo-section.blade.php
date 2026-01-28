{{-- resources/views/hr/employees/partials/form/photo-section.blade.php --}}
<div class="form-section mb-4">
    <div class="section-header">
        <h5 class="section-title">
            <i class="bi bi-camera-fill me-2"></i>Employee Photo
        </h5>
    </div>
    <div class="section-body">
        <div class="row justify-content-center">
            <div class="col-md-4 text-center">
                <!-- Photo Preview -->
                <div class="photo-preview-container mb-3">
                    <img id="photo-preview" 
                         src="{{ isset($employee) ? $employee->photo_url : asset('images/default-avatar.png') }}" 
                         alt="Employee Photo" 
                         class="photo-preview"
                         style="width: 150px; height: 150px; object-fit: cover;">
                </div>
                
                <!-- File Input -->
                <div class="mb-3">
                    <input type="file" class="form-control" id="photo" name="photo" 
                           accept="image/jpeg,image/png,image/jpg" 
                           onchange="window.previewPhoto(this)"
                           {{ !isset($employee) ? 'required' : '' }}
                           title="Drag & drop photo here or click to browse">
                    
                    <!-- Validation Messages -->
                    @if(isset($employee))
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle"></i> Max 2MB. Leave empty to keep current photo
                        </small>
                    @else
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle"></i> Max 2MB. Supported: JPG, PNG, JPEG
                        </small>
                    @endif
                    
                    @error('photo')
                        <div class="text-danger small mt-1">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </div>
                    @enderror
                    
                    <!-- Validation Feedback -->
                    <div class="validation-feedback" id="photo-feedback"></div>
                </div>
                
                <!-- Photo Actions -->
                @if(isset($employee) && $employee->photo_url && $employee->photo_url != asset('images/default-avatar.png'))
                    <div class="mt-2">
                        <a href="{{ $employee->photo_url }}" target="_blank" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View Current
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="document.getElementById('photo').value = ''; 
                                         document.getElementById('photo-preview').src = '{{ asset('images/default-avatar.png') }}';">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>