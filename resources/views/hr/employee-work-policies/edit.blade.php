@extends('layouts.app')

@section('title', 'Edit Work Policy')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('employee-work-policies.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Edit Work Policy</h5>
                    <p class="text-muted small mb-0">{{ $policy->employee->name }} ({{ $policy->employee_no }})</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    @if($errors->any())
                        <div class="alert alert-danger border-0 mb-3 p-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span class="fw-medium">Error:</span>
                            </div>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('employee-work-policies.update', $policy) }}" method="POST" id="workPolicyForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Employee Information (readonly) -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-user me-2 text-primary"></i>Employee Information
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Employee Number</label>
                                    <input type="text" class="form-control bg-light" value="{{ $policy->employee_no }}" readonly disabled>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Employee Name</label>
                                    <input type="text" class="form-control bg-light" value="{{ $policy->employee->name }}" readonly disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Working Hours - Weekday -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-calendar-week me-2 text-primary"></i>Weekday (Mon-Fri)
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label for="weekday_start" class="form-label small text-dark">Start Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('weekday_start') is-invalid @enderror" 
                                           id="weekday_start" 
                                           name="weekday_start" 
                                           value="{{ old('weekday_start', optional($policy->weekday_start)->format('H:i')) }}">
                                    @error('weekday_start')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="weekday_end" class="form-label small text-dark">End Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('weekday_end') is-invalid @enderror" 
                                           id="weekday_end" 
                                           name="weekday_end" 
                                           value="{{ old('weekday_end', optional($policy->weekday_end)->format('H:i')) }}">
                                    @error('weekday_end')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <small class="text-muted">* 1 hour break will be automatically deducted.</small>
                        </div>

                        <!-- Saturday -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-calendar-day me-2 text-primary"></i>Saturday
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label for="saturday_start" class="form-label small text-dark">Start Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('saturday_start') is-invalid @enderror" 
                                           id="saturday_start" 
                                           name="saturday_start" 
                                           value="{{ old('saturday_start', optional($policy->saturday_start)->format('H:i')) }}">
                                    @error('saturday_start')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="saturday_end" class="form-label small text-dark">End Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('saturday_end') is-invalid @enderror" 
                                           id="saturday_end" 
                                           name="saturday_end" 
                                           value="{{ old('saturday_end', optional($policy->saturday_end)->format('H:i')) }}">
                                    @error('saturday_end')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sunday (optional) -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-calendar-day me-2 text-primary"></i>Sunday (optional)
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label for="sunday_start" class="form-label small text-dark">Start Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('sunday_start') is-invalid @enderror" 
                                           id="sunday_start" 
                                           name="sunday_start" 
                                           value="{{ old('sunday_start', optional($policy->sunday_start)->format('H:i')) }}">
                                    @error('sunday_start')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="sunday_end" class="form-label small text-dark">End Time</label>
                                    <input type="time" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('sunday_end') is-invalid @enderror" 
                                           id="sunday_end" 
                                           name="sunday_end" 
                                           value="{{ old('sunday_end', optional($policy->sunday_end)->format('H:i')) }}">
                                    @error('sunday_end')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <small class="text-muted">Leave blank if not working on Sunday</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('employee-work-policies.index') }}" 
                               class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-save me-1"></i>Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
        height: 42px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc2626;
    }

    .form-label.small {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
        font-weight: 500;
        color: #374151;
    }

    .bg-light {
        background-color: #f8fafc !important;
    }

    .btn {
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
    }

    .card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }

    h6.fw-medium {
        color: #334155;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    h6.fw-medium i {
        color: #4f46e5;
    }

    .text-danger {
        color: #dc2626 !important;
    }

    .row.g-2 {
        margin-bottom: -0.5rem;
    }

    .row.g-2 > [class^="col-"] {
        margin-bottom: 0.5rem;
    }

    small.text-muted {
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: block;
    }
</style>
@endsection