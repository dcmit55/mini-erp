@extends('layouts.app')
@section('title', 'HR Record')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-primary rounded-3">
                    <i class="fas fa-folder-open text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">HR Record</h4>
                    <p class="text-muted mb-0">Kelola data karyawan, perangkat, dan ekspor data</p>
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

    {{-- Employee Data --}}
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <h6 class="text-uppercase fw-semibold mb-0 tracking-wide">Employee Data</h6>
        </div>
        <div class="row g-3">
            @can('hr.employees.view')
            <div class="col-lg-6">
                <a href="{{ route('employees.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-primary rounded-2">
                                    <i class="fas fa-user-tie text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Employee List</h6>
                                    <p class="text-muted small mb-0">Lihat dan kelola data seluruh karyawan</p>
                                </div>
                                <div class="arrow-icon text-primary">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6">
                <a href="{{ route('employees.create') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-success rounded-2">
                                    <i class="fas fa-user-plus text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Add Employee</h6>
                                    <p class="text-muted small mb-0">Tambah karyawan baru ke sistem</p>
                                </div>
                                <div class="arrow-icon text-success">
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

    {{-- Device & Shift --}}
    @can('hr.attendance.view')
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <h6 class="text-uppercase fw-semibold mb-0 tracking-wide">Device & Shift</h6>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <a href="{{ route('fingerspot.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-info rounded-2">
                                    <i class="fas fa-fingerprint text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Fingerspot Device Management</h6>
                                    <p class="text-muted small mb-0">Kelola perangkat fingerprint secara langsung</p>
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
                <a href="{{ route('session-shifts.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-warning rounded-2">
                                    <i class="fas fa-layer-group text-warning"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Session Shift</h6>
                                    <p class="text-muted small mb-0">Atur jadwal shift dan jam kerja karyawan</p>
                                </div>
                                <div class="arrow-icon text-warning">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Data Export --}}
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="line"></div>
            <h6 class="text-uppercase fw-semibold mb-0 tracking-wide">Data Export</h6>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <a href="{{ route('symcore-export.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-shape icon-md bg-soft-secondary rounded-2">
                                    <i class="fas fa-file-export text-secondary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Export All Employee Data</h6>
                                    <p class="text-muted small mb-0">Ekspor seluruh data karyawan ke format Symcore</p>
                                </div>
                                <div class="arrow-icon text-secondary">
                                    <i class="fas fa-chevron-right fs-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    @endcan

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
