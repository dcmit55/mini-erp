@extends('layouts.app')

@section('title', 'Sync Attendance - Fingerspot')

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
                    <i class="fas fa-sync-alt text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">Sync Attendance</h4>
                    <p class="text-muted mb-0">Pull scan data from the fingerprint device</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Configuration Card --}}
    <div class="card border-0 shadow-xs mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <span class="badge bg-soft-info text-info px-3 py-2">
                        <i class="fas fa-info-circle me-1"></i>Configuration
                    </span>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap gap-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small">Device ID:</span>
                            <span class="badge bg-soft-dark fw-normal text-break">
                                {!! $defaultDeviceId ?: '<span class="text-danger">Not set</span>' !!}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small">Token API:</span>
                            <span class="badge {{ config('fingerspot.api_token') ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }} fw-normal">
                                {{ config('fingerspot.api_token') ? 'Configured' : 'Not set' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-soft-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-soft-info alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
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
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-4">

                    <form action="{{ route('fingerspot.sync') }}" method="POST">
                        @csrf

                        {{-- Hidden Device ID --}}
                        <input type="hidden" name="device_id" value="{{ $defaultDeviceId }}">

                        {{-- Display Device ID (read-only) --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Device ID</label>
                            <div class="form-control bg-light py-2 px-3 rounded-2" style="cursor: default;">
                                {{ $defaultDeviceId ?: 'Not configured' }}
                            </div>
                            <div class="form-text small text-muted">Device ID is configured in the system and cannot be changed</div>
                        </div>

                        {{-- Date Range --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label fw-semibold text-dark">Start Date</label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                       id="start_date" name="start_date" value="{{ old('start_date') }}">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label fw-semibold text-dark">End Date</label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                       id="end_date" name="end_date" value="{{ old('end_date') }}">
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-soft-info py-2 px-3 mb-4 small" id="sync-hint">
                            <i class="fas fa-info-circle me-1"></i>
                            <span id="sync-hint-text">Leave both dates empty to sync <strong>all available data</strong> from device start date to today. This may take longer.</span>
                        </div>

                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('fingerspot.index') }}" class="btn btn-outline-secondary rounded-2 px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-4" id="sync-btn">
                                <i class="fas fa-sync-alt me-2"></i> Sync Now
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-white border-0 px-4 py-3">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="fas fa-clock"></i>
                        <span>Sync may take a few moments depending on the date range</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

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
    
    @media (max-width: 768px) {
        .icon-shape.icon-lg {
            width: 48px;
            height: 48px;
        }
        
        .icon-shape.icon-lg i {
            font-size: 1.25rem !important;
        }
        
        h4 {
            font-size: 1.25rem;
        }
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
    
    .bg-soft-dark {
        background-color: rgba(33, 37, 41, 0.1);
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
    .shadow-xs {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    /* Form Controls */
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.6rem 0.75rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .form-control.bg-light {
        background-color: #f8f9fa;
        border-color: #e9ecef;
    }
    
    /* Card */
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    
    /* Buttons */
    .btn-primary {
        background-color: #0d6efd;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: #0b5ed7;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
    }
    
    .btn-outline-secondary {
        transition: all 0.2s ease;
    }
    
    .btn-outline-secondary:hover {
        transform: translateX(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }
        
        .form-label {
            font-size: 0.85rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert .btn-close').forEach(btn => {
            if (btn) btn.click();
        });
    }, 5000);

    // Update hint text based on date input
    const startInput = document.getElementById('start_date');
    const endInput   = document.getElementById('end_date');
    const hintText   = document.getElementById('sync-hint-text');

    function updateHint() {
        const hasStart = startInput.value !== '';
        const hasEnd   = endInput.value   !== '';

        if (!hasStart && !hasEnd) {
            hintText.innerHTML = 'Leave both dates empty to sync <strong>all available data</strong> from device start date to today. This may take longer.';
        } else if (hasStart && hasEnd) {
            hintText.innerHTML = 'Syncing data from <strong>' + startInput.value + '</strong> to <strong>' + endInput.value + '</strong>.';
        } else if (hasStart) {
            hintText.innerHTML = 'End date not set — will sync from <strong>' + startInput.value + '</strong> to <strong>today</strong>.';
        } else {
            hintText.innerHTML = 'Start date not set — will sync from <strong>device start date</strong> to <strong>' + endInput.value + '</strong>.';
        }
    }

    startInput.addEventListener('change', updateHint);
    endInput.addEventListener('change', updateHint);
});
</script>
@endpush