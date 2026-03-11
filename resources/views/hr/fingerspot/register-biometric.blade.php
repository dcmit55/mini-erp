@extends('layouts.app')

@section('title', 'Register Biometric - Fingerspot')

@section('content')
<div class="container-fluid py-4">

    {{-- Back Button --}}
    <div class="mb-4">
        <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-primary rounded-3">
                    <i class="fas fa-fingerprint text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">Register Biometric</h4>
                    <p class="text-muted mb-0">Enroll fingerprint or face recognition online to the device</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-soft-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-soft-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <form action="{{ route('fingerspot.register-biometric') }}" method="POST">
                        @csrf

                        {{-- Device ID Field (Read Only) --}}
                        <div class="mb-4">
                            <label for="device_id" class="form-label fw-semibold text-dark">
                                Device ID <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-microchip text-primary"></i>
                                </span>
                                <input type="text" 
                                       class="form-control border-0 bg-light"
                                       id="device_id" 
                                       name="device_id" 
                                       value="C2656C741B331925" 
                                       readonly
                                       style="background-color: #e9ecef !important; cursor: not-allowed;">
                            </div>
                            <div class="form-text text-muted small mt-1">
                                <i class="fas fa-info-circle me-1"></i>
                                Device ID can be found on the device sticker or in the Fingerspot admin panel
                            </div>
                        </div>

                        {{-- Employee Select --}}
                        <div class="mb-4">
                            <label for="employee_select" class="form-label fw-semibold text-dark">
                                Employee <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-user text-primary"></i>
                                </span>
                                <select class="form-select border-0 bg-light"
                                        id="employee_select" required>
                                    <option value="">-- Select Employee --</option>
                                    @foreach($employees as $emp)
                                        @php
                                            $pin = ltrim(substr($emp->employee_no, 4), '0') ?: '0';
                                        @endphp
                                        <option value="{{ $pin }}" data-name="{{ $emp->name }}" {{ old('pin') == $pin ? 'selected' : '' }}>
                                            {{ $emp->employee_no }} — {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- PIN (auto-filled, hidden) --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">
                                Employee ID on Device
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-id-card text-primary"></i>
                                </span>
                                <input type="text"
                                       class="form-control border-0 @error('pin') is-invalid @enderror"
                                       id="pin"
                                       name="pin"
                                       value="{{ old('pin') }}"
                                       readonly
                                       style="background-color: #e9ecef !important; cursor: not-allowed;"
                                       placeholder="Auto-filled from employee selection">
                            </div>
                            @error('pin')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted small mt-1">
                                <i class="fas fa-info-circle me-1"></i>
                                Auto-filled from employee number (e.g. DCM-0528 → 528)
                            </div>
                        </div>

                        {{-- Biometric Type Field --}}
                        <div class="mb-4">
                            <label for="verification" class="form-label fw-semibold text-dark">
                                Biometric Type <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-fingerprint text-primary"></i>
                                </span>
                                <select class="form-select border-0 bg-light @error('verification') is-invalid @enderror"
                                        id="verification" name="verification" required>
                                    <option value="">-- Select Biometric Type --</option>
                                    <optgroup label="Fingerprint">
                                        @for($i=0; $i<=9; $i++)
                                            <option value="{{ $i }}" {{ old('verification') == $i ? 'selected' : '' }}>
                                                Finger {{ $i+1 }} 
                                                @if($i==0) (Right Thumb)
                                                @elseif($i==1) (Right Index)
                                                @elseif($i==2) (Right Middle)
                                                @elseif($i==3) (Right Ring)
                                                @elseif($i==4) (Right Little)
                                                @elseif($i==5) (Left Thumb)
                                                @elseif($i==6) (Left Index)
                                                @elseif($i==7) (Left Middle)
                                                @elseif($i==8) (Left Ring)
                                                @elseif($i==9) (Left Little)
                                                @endif
                                            </option>
                                        @endfor
                                    </optgroup>
                                    <optgroup label="Other">
                                        <option value="12" {{ old('verification') == '12' ? 'selected' : '' }}>Face Recognition</option>
                                        <option value="13" {{ old('verification') == '13' ? 'selected' : '' }}>Vein Recognition</option>
                                    </optgroup>
                                </select>
                            </div>
                            @error('verification')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Registration Process Notes --}}
                        <div class="alert alert-soft-info mb-4 py-3 px-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-info-circle mt-1"></i>
                                <div>
                                    <small class="fw-semibold d-block mb-1">Registration Process:</small>
                                    <small class="text-muted d-block">1. Make sure the device is online and powered on</small>
                                    <small class="text-muted d-block">2. Employee must already be registered on the device (Employee ID exists)</small>
                                    <small class="text-muted d-block">3. Follow the instructions on the device when registration starts</small>
                                    <small class="text-muted d-block">4. Place finger or face according to the device prompt</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary rounded-2 px-4">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-4">
                                <i class="fas fa-fingerprint me-2"></i> Start Registration
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-white border-0 px-4 py-3">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="fas fa-clock"></i>
                        <span>Registration may take 10-30 seconds. Please wait until device responds.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('employee_select').addEventListener('change', function () {
    document.getElementById('pin').value = this.value;
});
// Trigger on page load if old value exists
(function () {
    var sel = document.getElementById('employee_select');
    if (sel.value) document.getElementById('pin').value = sel.value;
})();
</script>
@endpush

@push('styles')
<style>
    /* Icon Shape */
    .icon-shape {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .icon-shape.icon-lg {
        width: 56px;
        height: 56px;
    }
    
    /* Background Soft Colors */
    .bg-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .bg-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    /* Alert Styles */
    .alert-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #0f5132;
        border: none;
        border-radius: 10px;
    }
    
    .alert-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #842029;
        border: none;
        border-radius: 10px;
    }
    
    .alert-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
        color: #055160;
        border: none;
        border-radius: 10px;
    }
    
    /* Card Shadow */
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    /* Form Controls */
    .form-control:focus, .form-select:focus {
        box-shadow: none;
        border-color: #86b7fe;
    }
    
    .input-group-text {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .form-control.bg-light, .form-select.bg-light {
        border-left: none;
        padding-left: 0;
    }
    
    .form-control.bg-light:focus, .form-select.bg-light:focus {
        background-color: #f8f9fa;
    }
    
    /* Readonly field style */
    .form-control[readonly] {
        background-color: #e9ecef;
        opacity: 1;
    }
    
    /* Card */
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    
    /* Button */
    .btn-primary {
        background-color: #0d6efd;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: #0b5ed7;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }
    
    .btn-outline-secondary:hover {
        transform: translateX(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .icon-shape.icon-lg {
            width: 48px;
            height: 48px;
        }
        
        h4 {
            font-size: 1.25rem;
        }
    }
</style>
@endpush