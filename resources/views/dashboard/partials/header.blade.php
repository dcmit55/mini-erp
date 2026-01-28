@php
    $serverTime = \Carbon\Carbon::now();
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg bg-gradient-brand text-white position-relative overflow-hidden">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="ms-2">
                                <h3 class="mb-1 fw-bold">Welcome, {{ ucwords($user->username) }}!</h3>
                                <p class="mb-0 opacity-75">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    {{ ucwords(str_replace('_', ' ', $user->role)) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="dashboard-clock-container">
                            <div class="rounded-3 p-2 d-inline-block">
                                <div id="realtime-clock" class="fs-5 fw-bold text-light">00:00</div>
                                <div id="realtime-date" class="small opacity-75 text-light">Loading date...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>