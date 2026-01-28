{{-- resources/views/hr/employees/show.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i>
                                </a>
                                <h1 class="h4 mb-0">
                                    <i class="bi bi-person-badge me-2"></i>
                                    Employee Details
                                </h1>
                            </div>
                            <div class="d-flex gap-2">
                                @can('hr-access')
                                    <a href="{{ route('employees.edit', $employee) }}" 
                                       class="btn btn-warning" title="Edit Employee">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                @endcan
                                <a href="{{ route('employees.timing', $employee) }}" 
                                   class="btn btn-info" title="View Timings">
                                    <i class="bi bi-clock"></i> Timing
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Profile & Contact -->
            <div class="col-lg-4 mb-4">
                <!-- Profile Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" 
                                 class="rounded-circle shadow" 
                                 style="width: 120px; height: 120px; object-fit: cover;">
                            <span class="position-absolute bottom-0 end-0 badge employment-badge 
                                         bg-{{ $employee->employment_type_badge['color'] }} rounded-pill">
                                {{ $employee->employment_type }}
                            </span>
                        </div>
                        <h4 class="card-title mb-1">{{ $employee->name }}</h4>
                        <p class="text-muted mb-2">{{ $employee->position }}</p>
                        <small class="badge bg-light text-dark">{{ $employee->employee_no }}</small>
                        <span class="badge ms-2 bg-{{ $employee->status_badge['color'] }}">
                            {{ ucfirst($employee->status) }}
                        </span>

                        <hr class="my-3">

                        <!-- Quick Stats -->
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold text-primary">{{ $employee->timings->count() }}</div>
                                <small class="text-muted">Timings</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-success">{{ $employee->saldo_cuti ?? 0 }}</div>
                                <small class="text-muted">Leave</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-info">{{ $employee->documents->count() }}</div>
                                <small class="text-muted">Docs</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information (with inline editing) -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-telephone"></i> Contact Information</h6>
                        @can('hr-access')
                            <button class="btn btn-sm btn-outline-warning toggle-edit" 
                                    data-section="contact">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        @endcan
                    </div>
                    
                    <!-- Display Mode -->
                    <div class="card-body" id="contact-display">
                        <div class="contact-item mb-3">
                            <label class="fw-semibold text-muted small">Email</label>
                            <div class="editable-field" data-field="email">
                                @if($employee->email)
                                    <a href="mailto:{{ $employee->email }}" class="text-decoration-none">
                                        <i class="bi bi-envelope me-1"></i>{{ $employee->email }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                                @can('hr-access')
                                    <i class="bi bi-pencil edit-icon text-muted"></i>
                                @endcan
                            </div>
                        </div>

                        <div class="contact-item mb-3">
                            <label class="fw-semibold text-muted small">Phone</label>
                            <div class="editable-field" data-field="phone">
                                @if($employee->phone)
                                    <a href="tel:{{ $employee->phone }}" class="text-decoration-none">
                                        <i class="bi bi-telephone me-1"></i>{{ $employee->phone }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                                @can('hr-access')
                                    <i class="bi bi-pencil edit-icon text-muted"></i>
                                @endcan
                            </div>
                        </div>

                        @if($employee->address)
                            <div class="contact-item mb-3">
                                <label class="fw-semibold text-muted small">Address</label>
                                <div class="editable-field" data-field="address">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $employee->address }}
                                    @can('hr-access')
                                        <i class="bi bi-pencil edit-icon text-muted"></i>
                                    @endcan
                                </div>
                            </div>
                        @endif

                        @if($employee->rekening)
                            <div class="contact-item mb-3">
                                <label class="fw-semibold text-muted small">Bank Account</label>
                                <div class="editable-field" data-field="rekening">
                                    <i class="bi bi-credit-card me-1"></i>{{ $employee->formatted_rekening }}
                                    @can('hr-access')
                                        <i class="bi bi-pencil edit-icon text-muted"></i>
                                    @endcan
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Edit Mode (hidden by default) -->
                    @can('hr-access')
                    <div class="card-body d-none" id="contact-edit">
                        <form id="contact-form" class="ajax-form" 
                              action="{{ route('employees.update.contact', $employee) }}">
                            @csrf @method('PATCH')
                            
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" 
                                       name="email" value="{{ $employee->email }}">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="edit_phone" 
                                       name="phone" value="{{ $employee->phone }}">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_address" class="form-label">Address</label>
                                <textarea class="form-control" id="edit_address" 
                                          name="address" rows="2">{{ $employee->address }}</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_rekening" class="form-label">Bank Account</label>
                                <input type="text" class="form-control" id="edit_rekening" 
                                       name="rekening" value="{{ $employee->rekening }}">
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary btn-sm cancel-edit"
                                        data-section="contact">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-check-circle"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                    @endcan
                </div>
            </div>

            <!-- Right Column - Detailed Info -->
            <div class="col-lg-8">
                <!-- Employee Information (with inline editing) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-person-badge"></i> Employee Information</h6>
                        @can('hr-access')
                            <button class="btn btn-sm btn-outline-warning toggle-edit" 
                                    data-section="basic-info">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        @endcan
                    </div>
                    
                    <!-- Display Mode -->
                    <div class="card-body" id="basic-info-display">
                        <div class="row">
                            <!-- Basic info display fields -->
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Department</label>
                                <div class="editable-field" data-field="department">
                                    @if($employee->department)
                                        <span class="badge bg-primary">{{ ucfirst($employee->department->name) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                    @can('hr-access')
                                        <i class="bi bi-pencil edit-icon text-muted"></i>
                                    @endcan
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Position</label>
                                <div class="editable-field" data-field="position">
                                    {{ $employee->position }}
                                    @can('hr-access')
                                        <i class="bi bi-pencil edit-icon text-muted"></i>
                                    @endcan
                                </div>
                            </div>
                            
                            <!-- More fields... -->
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    @can('hr-access')
                    <div class="card-body d-none" id="basic-info-edit">
                        <form id="basic-info-form" class="ajax-form" 
                              action="{{ route('employees.update.basic', $employee) }}">
                            @csrf @method('PATCH')
                            
                            <div class="row">
                                <!-- Edit form fields -->
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-secondary btn-sm cancel-edit"
                                        data-section="basic-info">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    @endcan
                </div>
                
                <!-- Rest of show page content (skillsets, documents, timings) -->
                <!-- ... -->
                
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('css/hr/employee-styles.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/hr/employee-show.js') }}"></script>
@endpush