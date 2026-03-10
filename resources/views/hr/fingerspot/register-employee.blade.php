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

                        <div class="mb-3">
                            <label for="device_id" class="form-label small text-dark">Device ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-1 rounded-2 py-2 px-3 @error('device_id') is-invalid @enderror"
                                   id="device_id" name="device_id" value="{{ old('device_id', $defaultDeviceId) }}" required>
                            @error('device_id')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="pin" class="form-label small text-dark">Employee ID on Device <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-1 rounded-2 py-2 px-3 @error('pin') is-invalid @enderror"
                                       id="pin" name="pin" value="{{ old('pin') }}" placeholder="e.g. 528" required>
                                <div class="form-text small">Number only — matches the numeric part of the Employee NIK (e.g. DCM-0528 → 528)</div>
                                @error('pin')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="privilege" class="form-label small text-dark">Access Level <span class="text-danger">*</span></label>
                                <select class="form-select border-1 rounded-2 py-2 px-3 @error('privilege') is-invalid @enderror"
                                        id="privilege" name="privilege" required>
                                    <option value="">-- Select Access Level --</option>
                                    <option value="0" {{ old('privilege') == '0' ? 'selected' : '' }}>Regular User</option>
                                    <option value="3" {{ old('privilege') == '3' ? 'selected' : '' }}>Sub-admin</option>
                                    <option value="2" {{ old('privilege') == '2' ? 'selected' : '' }}>Administrator</option>
                                </select>
                                @error('privilege')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label small text-dark">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-1 rounded-2 py-2 px-3 @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" maxlength="100" required>
                            @error('name')
                                <div class="invalid-feedback small">{{ $message }}</div>
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
