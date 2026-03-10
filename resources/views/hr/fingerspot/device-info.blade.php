@extends('layouts.app')

@section('title', 'Device Info - Fingerspot')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Device Info</h5>
                    <p class="text-muted small mb-0">Check device details and status</p>
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

                    <form action="{{ route('fingerspot.device-info') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="device_id" class="form-label small text-dark">Device ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-1 rounded-2 py-2 px-3 @error('device_id') is-invalid @enderror"
                                   id="device_id" name="device_id" value="{{ old('device_id', $defaultDeviceId) }}" required>
                            @error('device_id')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-info rounded-2 px-3 btn-sm text-white">
                                <i class="fas fa-microchip me-1"></i> Get Device Info
                            </button>
                        </div>
                    </form>

                    @if(session('device_info'))
                    <div class="mt-4">
                        <h6 class="fw-medium mb-2">Device Information:</h6>
                        <pre class="bg-light p-3 rounded small" style="max-height:400px; overflow:auto">{{ json_encode(session('device_info'), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
