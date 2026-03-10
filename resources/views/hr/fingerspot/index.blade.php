@extends('layouts.app')

@section('title', 'Fingerspot Device Management')

@section('content')
<div class="container-fluid py-4">
    {{-- Header Section --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-primary rounded-3">
                    <i class="fas fa-fingerprint text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">Fingerspot Device Management</h4>
                    <p class="text-muted mb-0">Manage the fingerprint device directly from here</p>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('fingerprint-logs.index') }}" class="btn btn-light">
                <i class="fas fa-list me-2"></i>View Logs
            </a>
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
                            <span class="badge bg-soft-dark fw-normal">
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

    {{-- Menu Categories --}}
    @php
        $menuCategories = [
            'Data Management' => [
                ['employee-list', 'Employee List', 'View employees registered on the device', 'fas fa-users', 'info'],
                ['sync', 'Sync Attendance', 'Pull scan data from the device', 'fas fa-sync-alt', 'primary'],
                ['register-employee', 'Register Employee', 'Add an employee to the device', 'fas fa-user-plus', 'primary'],
                ['register-biometric', 'Register Biometric', 'Enroll fingerprint / face online', 'fas fa-fingerprint', 'success'],
                ['delete-employee', 'Remove Employee', 'Delete a user from the device', 'fas fa-user-minus', 'danger'],
            ],
            'Device Control' => [
                ['device-info', 'Device Info', 'Check device details and status', 'fas fa-microchip', 'info'],
                ['set-timezone', 'Set Timezone', 'Synchronize device time', 'fas fa-clock', 'warning'],
                ['restart', 'Restart Device', 'Reboot the device remotely', 'fas fa-power-off', 'secondary'],
            ]
        ];
    @endphp

    @foreach($menuCategories as $category => $menus)
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <h6 class="text-uppercase fw-semibold mb-0 tracking-wide">{{ $category }}</h6>
        </div>
        
        <div class="row g-3">
            @foreach($menus as $menu)
            <div class="col-lg-6">
                <a href="{{ route('fingerspot.'.$menu[0].'.form') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-{{ $menu[4] }} rounded-2">
                                    <i class="{{ $menu[3] }} text-{{ $menu[4] }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold text-dark">{{ $menu[1] }}</h6>
                                    <p class="text-muted small mb-0">{{ $menu[2] }}</p>
                                </div>
                                <div class="arrow-icon text-{{ $menu[4] }}">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection

@push('styles')
<style>
    /* Custom Styles */
    .icon-shape {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
    }
    
    .icon-shape.icon-lg {
        width: 56px;
        height: 56px;
    }
    
    .icon-shape.icon-md {
        width: 48px;
        height: 48px;
    }
    
    .bg-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .bg-soft-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    .bg-soft-secondary {
        background-color: rgba(108, 117, 125, 0.1);
    }
    
    .bg-soft-dark {
        background-color: rgba(33, 37, 41, 0.1);
    }
    
    .alert-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #0f5132;
        border: none;
    }
    
    .alert-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #842029;
        border: none;
    }
    
    .shadow-xs {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .tracking-wide {
        letter-spacing: 0.5px;
    }
    
    .line {
        width: 4px;
        height: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
    }
    
    /* Menu Card Styles */
    .menu-card {
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .menu-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 4px;
        height: 100%;
        background-color: var(--bs-primary);
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .menu-card:hover::after {
        opacity: 1;
    }
    
    .arrow-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    
    .menu-card:hover .arrow-icon {
        background-color: currentColor;
        color: white !important;
        transform: translateX(4px);
    }
    
    a.text-decoration-none {
        display: block;
    }
    
    /* Warna garis untuk setiap tipe */
    .menu-card[class*="primary"]::after { background-color: #0d6efd; }
    .menu-card[class*="success"]::after { background-color: #198754; }
    .menu-card[class*="danger"]::after { background-color: #dc3545; }
    .menu-card[class*="info"]::after { background-color: #0dcaf0; }
    .menu-card[class*="warning"]::after { background-color: #ffc107; }
    .menu-card[class*="secondary"]::after { background-color: #6c757d; }
</style>
@endpush