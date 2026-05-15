@extends('layouts.app')
@section('title', 'HR Management')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-success rounded-3">
                    <i class="fas fa-tasks text-success fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">HR Management</h4>
                    <p class="text-muted mb-0">Kelola permintaan, persetujuan, administrasi SDM, dan data timing</p>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('hr.dashboard') }}" class="btn btn-sm btn-outline-secondary px-3">
                <i class="fas fa-chart-pie me-1"></i>HR Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-soft-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @php
        $totalLeavePending = ($hrLeavePending ?? 0) + ($directorLeavePending ?? 0);
        $totalOTPending    = ($hrOTPending ?? 0) + ($directorOTPending ?? 0);
    @endphp

    {{-- Requests --}}
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <h6 class="text-uppercase fw-semibold mb-0 tracking-wide">Requests</h6>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <a href="{{ route('leave_requests.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-info rounded-2">
                                    <i class="fas fa-calendar-minus text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Leave Requests</h6>
                                    <p class="text-muted small mb-0">Daftar dan kelola permohonan cuti karyawan</p>
                                </div>
                                <div class="arrow-icon text-info">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6">
                <a href="{{ route('overtime-requests.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-primary rounded-2">
                                    <i class="fas fa-business-time text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Overtime Requests</h6>
                                    <p class="text-muted small mb-0">Daftar dan kelola permohonan lembur karyawan</p>
                                </div>
                                <div class="arrow-icon text-primary">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Approvals --}}
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <h6 class="text-uppercase fw-semibold mb-0 tracking-wide">Approvals</h6>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <a href="{{ route('leave_requests.hr-approvals') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-success rounded-2">
                                    <i class="fas fa-user-check text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-1 fw-semibold">Leave Approvals</h6>
                                        @if($totalLeavePending > 0)
                                            <span class="badge bg-danger rounded-pill" style="font-size:.65rem;">{{ $totalLeavePending > 99 ? '99+' : $totalLeavePending }}</span>
                                        @endif
                                    </div>
                                    <p class="text-muted small mb-0">Proses persetujuan cuti dari HR dan Direktur</p>
                                </div>
                                <div class="arrow-icon text-success">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6">
                <a href="{{ route('overtime-requests.hr-approvals') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-success rounded-2">
                                    <i class="fas fa-user-check text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-1 fw-semibold">Overtime Approvals</h6>
                                        @if($totalOTPending > 0)
                                            <span class="badge bg-danger rounded-pill" style="font-size:.65rem;">{{ $totalOTPending > 99 ? '99+' : $totalOTPending }}</span>
                                        @endif
                                    </div>
                                    <p class="text-muted small mb-0">Proses persetujuan lembur dari HR dan Direktur</p>
                                </div>
                                <div class="arrow-icon text-success">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Administration + Timing Data --}}
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <h6 class="text-uppercase fw-semibold mb-0 tracking-wide">Administration & Data</h6>
        </div>
        <div class="row g-3">
            @can('hr.warning-letter.view')
            <div class="col-lg-6">
                <a href="{{ route('warning-letters.dashboard') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-danger rounded-2">
                                    <i class="bi bi-envelope-exclamation text-danger"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Warning Letter</h6>
                                    <p class="text-muted small mb-0">Kelola surat peringatan (SP) karyawan</p>
                                </div>
                                <div class="arrow-icon text-danger">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endcan

            <div class="col-lg-6">
                <a href="{{ route('overtime-pays.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-warning rounded-2">
                                    <i class="fas fa-calculator text-warning"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Overtime Pay</h6>
                                    <p class="text-muted small mb-0">Hitung dan kelola pembayaran lembur karyawan</p>
                                </div>
                                <div class="arrow-icon text-warning">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- TIMING DATA CARD --}}
            @can('production.timing.view')
            <div class="col-lg-6">
                <a href="{{ route('timings.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-secondary rounded-2">
                                    <i class="fas fa-stopwatch text-secondary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Timing Data</h6>
                                    <p class="text-muted small mb-0">Lihat dan kelola data timing (jam kerja) karyawan</p>
                                </div>
                                <div class="arrow-icon text-secondary">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .icon-shape { display:inline-flex;align-items:center;justify-content:center;text-align:center;vertical-align:middle; }
    .icon-shape.icon-lg { width:56px;height:56px; }
    .icon-shape.icon-md { width:48px;height:48px; }
    .bg-soft-primary  { background-color:rgba(13,110,253,.1); }
    .bg-soft-success  { background-color:rgba(25,135,84,.1); }
    .bg-soft-danger   { background-color:rgba(220,53,69,.1); }
    .bg-soft-warning  { background-color:rgba(255,193,7,.1); }
    .bg-soft-info     { background-color:rgba(13,202,240,.1); }
    .bg-soft-secondary{ background-color:rgba(108,117,125,.1); }
    .alert-soft-success { background-color:rgba(25,135,84,.1);color:#0f5132;border:none; }
    .alert-soft-danger  { background-color:rgba(220,53,69,.1);color:#842029;border:none; }
    .shadow-xs { box-shadow:0 .125rem .25rem rgba(0,0,0,.075); }
    .hover-lift { transition:transform .2s ease,box-shadow .2s ease; }
    .hover-lift:hover { transform:translateY(-2px);box-shadow:0 .5rem 1rem rgba(0,0,0,.15)!important; }
    .tracking-wide { letter-spacing:.5px; }
    .line { width:4px;height:20px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:2px; }
    .menu-card { cursor:pointer;position:relative;overflow:hidden; }
    .menu-card::after { content:'';position:absolute;top:0;right:0;width:4px;height:100%;background-color:var(--bs-primary);opacity:0;transition:opacity .2s ease; }
    .menu-card:hover::after { opacity:1; }
    .arrow-icon { width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all .2s ease; }
    .menu-card:hover .arrow-icon { background-color:currentColor;color:white!important;transform:translateX(4px); }
    a.text-decoration-none { display:block; }
</style>
@endpush