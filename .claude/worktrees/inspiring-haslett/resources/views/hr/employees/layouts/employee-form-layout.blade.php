{{-- resources/views/hr/employees/layouts/employee-form-layout.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ $cancelRoute ?? route('employees.index') }}" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h2 class="mb-0" style="font-size:1.3rem;">
                            <i class="{{ $icon ?? 'bi bi-person-fill' }} me-2"></i>
                            {{ $title ?? 'Employee Form' }}
                        </h2>
                    </div>
                    @if(isset($employee) && ($showActions ?? false))
                        <div class="d-flex gap-2">
                            <a href="{{ route('employees.show', $employee) }}" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="{{ route('employees.timing', $employee) }}" 
                               class="btn btn-outline-info btn-sm">
                                <i class="bi bi-clock"></i> Timing
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card-body">
                <!-- Validation Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Validation Error!</strong> Please check the form below.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                <!-- Form -->
                <form method="POST" action="{{ $formAction ?? '#' }}" enctype="multipart/form-data" 
                      id="employee-form" class="needs-validation" novalidate>
                    @csrf
                    @if(isset($method))
                        @method($method)
                    @endif
                    
                    @yield('form-content')
                    
                    <!-- Form Actions -->
                    <div class="form-actions py-3 border-top">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ $cancelRoute ?? route('employees.index') }}" 
                               class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" 
                                      id="submit-spinner"></span>
                                <i class="bi bi-check-circle me-1"></i>
                                {{ $submitText ?? 'Save Employee' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    @stack('modals')
@endsection

@push('styles')
    <style>
        .form-section {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #ffffff;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        .form-section:hover {
            border-color: #dee2e6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .section-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0;
        }
        .section-body {
            padding: 1.5rem;
        }
        .photo-preview {
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Photo preview function
            window.previewPhoto = function(input) {
                const file = input.files[0];
                const preview = document.getElementById('photo-preview');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            };
            
            // Form submission handler
            const form = document.getElementById('employee-form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = document.getElementById('submit-btn');
                    const spinner = document.getElementById('submit-spinner');
                    
                    if (submitBtn && spinner) {
                        submitBtn.disabled = true;
                        spinner.classList.remove('d-none');
                    }
                });
            }
            
            // Auto-dismiss alerts
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    if (alert.classList.contains('show')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
@endpush