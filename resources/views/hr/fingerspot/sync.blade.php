@extends('layouts.app')

@section('title', 'Sync Attendance - Fingerspot')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Sync Attendance</h5>
                    <p class="text-muted small mb-0">Pull scan data from the fingerprint device</p>
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
                    @if(session('info'))
                        <div class="alert alert-info border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-info-circle me-2"></i> {{ session('info') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('fingerspot.sync') }}" method="POST">
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
                                <label for="start_date" class="form-label small text-dark">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-1 rounded-2 py-2 px-3 @error('start_date') is-invalid @enderror"
                                       id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label small text-dark">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-1 rounded-2 py-2 px-3 @error('end_date') is-invalid @enderror"
                                       id="end_date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-sync-alt me-1"></i> Sync Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
