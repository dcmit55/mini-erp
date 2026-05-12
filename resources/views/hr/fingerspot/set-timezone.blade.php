@extends('layouts.app')

@section('title', 'Set Timezone - Fingerspot')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Set Device Timezone</h5>
                    <p class="text-muted small mb-0">Synchronize the fingerprint device time</p>
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

                    <form action="{{ route('fingerspot.set-timezone') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="device_id" class="form-label small text-dark">Device ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-1 rounded-2 py-2 px-3 @error('device_id') is-invalid @enderror"
                                   id="device_id" name="device_id" value="{{ old('device_id', $defaultDeviceId) }}" required>
                            @error('device_id')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="timezone" class="form-label small text-dark">Timezone <span class="text-danger">*</span></label>
                            <select class="form-select border-1 rounded-2 py-2 px-3 @error('timezone') is-invalid @enderror"
                                    id="timezone" name="timezone" required>
                                <option value="Asia/Jakarta"  {{ old('timezone', 'Asia/Jakarta') == 'Asia/Jakarta'  ? 'selected' : '' }}>Asia/Jakarta (WIB, UTC+7)</option>
                                <option value="Asia/Makassar" {{ old('timezone') == 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA, UTC+8)</option>
                                <option value="Asia/Jayapura" {{ old('timezone') == 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT, UTC+9)</option>
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-warning rounded-2 px-3 btn-sm">
                                <i class="fas fa-clock me-1"></i> Set Timezone
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
