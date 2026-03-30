@extends('layouts.app')

@section('title', 'Leave Approvals — Department')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-shape icon-lg bg-soft-primary rounded-3">
                    <i class="fas fa-calendar-check text-primary fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-semibold">Leave Approvals</h4>
                    <p class="text-muted mb-0">Select a department to review pending leave requests</p>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('leave_requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                <i class="fas fa-list me-1"></i> All Leave Requests
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-soft-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Department Cards --}}
    @php
        $deptConfig = [
            'DCM Mascot'              => ['color' => 'warning', 'icon' => 'fas fa-mask'],
            'DCM Costume & DCM Plush' => ['color' => 'purple',  'icon' => 'fas fa-tshirt'],
            'DCM Animatronics'        => ['color' => 'info',    'icon' => 'fas fa-robot'],
            'Logistic'                => ['color' => 'success', 'icon' => 'fas fa-boxes'],
        ];
        $defaultConfig = ['color' => 'primary', 'icon' => 'fas fa-building'];
    @endphp

    <div class="row g-3">
        @foreach($deptPendingCounts as $deptName => $pendingCount)
        @php $cfg = $deptConfig[$deptName] ?? $defaultConfig; @endphp
        <div class="col-lg-6">
            <a href="{{ route('leave_requests.dept-approvals', ['dept' => $deptName]) }}" class="text-decoration-none">
                <div class="card border-0 shadow-xs hover-lift h-100 menu-card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-shape icon-md bg-soft-{{ $cfg['color'] }} rounded-2">
                                <i class="{{ $cfg['icon'] }} text-{{ $cfg['color'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold text-dark">{{ $deptName }}</h6>
                                @if($pendingCount > 0)
                                    <p class="text-danger small mb-0">
                                        <i class="fas fa-clock me-1"></i>{{ $pendingCount }} pending approval{{ $pendingCount > 1 ? 's' : '' }}
                                    </p>
                                @else
                                    <p class="text-success small mb-0">
                                        <i class="fas fa-check-circle me-1"></i>All clear
                                    </p>
                                @endif
                            </div>
                            <div class="arrow-icon text-{{ $cfg['color'] }}">
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
    .bg-soft-purple   { background-color:rgba(111,66,193,.1); }
    .bg-soft-pink     { background-color:rgba(214,51,132,.1); }
    .text-purple      { color:#6f42c1 !important; }
    .text-pink        { color:#d63384 !important; }
    .shadow-xs { box-shadow:0 .125rem .25rem rgba(0,0,0,.075); }
    .hover-lift { transition:transform .2s ease,box-shadow .2s ease; }
    .hover-lift:hover { transform:translateY(-2px);box-shadow:0 .5rem 1rem rgba(0,0,0,.15) !important; }
    .menu-card { cursor:pointer;position:relative;overflow:hidden; }
    .menu-card::after { content:'';position:absolute;top:0;right:0;width:4px;height:100%;background-color:currentColor;opacity:0;transition:opacity .2s ease; }
    .menu-card:hover::after { opacity:.3; }
    .arrow-icon { width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all .2s ease; }
    .menu-card:hover .arrow-icon { background-color:currentColor;color:white !important;transform:translateX(4px); }
    .alert-soft-success { background-color:rgba(25,135,84,.1);color:#0f5132;border:none; }
</style>
@endpush
