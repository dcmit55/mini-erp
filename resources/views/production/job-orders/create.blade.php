@extends('layouts.app')

@section('title', 'Buat Job Order Baru')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('production.job-orders.index') }}" class="btn btn-outline-secondary rounded-3 px-4 mb-3">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                    <h4 class="fw-bold text-dark mb-1">Buat Job Order Baru</h4>
                    <p class="text-muted small mb-0">Lengkapi informasi job order baru</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 d-flex align-items-center mb-4">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger border-0 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span class="fw-semibold">Terjadi kesalahan:</span>
                            </div>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('production.job-orders.store') }}" method="POST" id="joForm">
                        @csrf
                        
                        <!-- Informasi Utama -->
                        <div class="mb-4">
                            <h6 class="fw-semibold text-dark mb-3">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Informasi Utama
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-12 mb-2">
                                    <label for="name" class="form-label fw-medium text-dark small">Nama Job Order <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-3 py-2 px-3 @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}"
                                           placeholder="Masukkan nama job order" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-2">
                                    <label for="project_id" class="form-label fw-medium text-dark small">Project <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-3 py-2 px-3 @error('project_id') is-invalid @enderror" 
                                            id="project_id" 
                                            name="project_id"
                                            required>
                                        <option value="">Pilih Project</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}" 
                                                {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-2">
                                    <label for="department_id" class="form-label fw-medium text-dark small">Department <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-3 py-2 px-3 @error('department_id') is-invalid @enderror" 
                                            id="department_id" 
                                            name="department_id" 
                                            required>
                                        <option value="">Pilih Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" 
                                                {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-12 mb-2">
                                    <label for="description" class="form-label fw-medium text-dark small">Deskripsi</label>
                                    <textarea class="form-control border-1 rounded-3 py-2 px-3 @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="2"
                                              placeholder="Tambahkan deskripsi job order">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="mb-4">
                            <h6 class="fw-semibold text-dark mb-3">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>Timeline
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label for="start_date" class="form-label fw-medium text-dark small">Start Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-light py-2 px-3">
                                            <i class="fas fa-calendar text-muted"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control border-1 rounded-3 py-2 px-3 @error('start_date') is-invalid @enderror" 
                                               id="start_date" 
                                               name="start_date" 
                                               value="{{ old('start_date') }}">
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-2">
                                    <label for="end_date" class="form-label fw-medium text-dark small">End Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-light py-2 px-3">
                                            <i class="fas fa-calendar text-muted"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control border-1 rounded-3 py-2 px-3 @error('end_date') is-invalid @enderror" 
                                               id="end_date" 
                                               name="end_date" 
                                               value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment -->
                        <div class="mb-4">
                            <h6 class="fw-semibold text-dark mb-3">
                                <i class="fas fa-user-check me-2 text-primary"></i>Assignment
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label for="assigned_to" class="form-label fw-medium text-dark small">Assigned To</label>
                                    <select class="form-select border-1 rounded-3 py-2 px-3 @error('assigned_to') is-invalid @enderror" 
                                            id="assigned_to" 
                                            name="assigned_to">
                                        <option value="">Pilih User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" 
                                                {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name ?? $user->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="mb-4">
                            <h6 class="fw-semibold text-dark mb-3">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Note
                            </h6>
                            
                            <div class="mb-2">
                                <label for="notes" class="form-label fw-medium text-dark small">Note</label>
                                <textarea class="form-control border-1 rounded-3 py-2 px-3 @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="2"
                                          placeholder="Tambahkan note">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <div></div>
                            
                            <div class="d-flex gap-2">
                                <a href="{{ route('production.job-orders.index') }}" 
                                   class="btn btn-outline-secondary rounded-3 px-4 py-2">
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-primary rounded-3 px-4 py-2">
                                    <i class="fas fa-save me-2"></i>Simpan Job Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Global Styling */
    body {
        background-color: #f8fafc;
    }

    /* Form Styling */
    .form-control, .form-select {
        border-color: #e2e8f0;
        transition: all 0.2s;
        font-size: 0.9rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.1);
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc2626;
    }

    /* Card Styling */
    .card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }

    /* Section Headers */
    h6.fw-semibold {
        color: #334155;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.95rem;
    }

    h6.fw-semibold i {
        color: #4f46e5;
    }

    /* Label Styling */
    .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
    }

    /* Input Group Styling */
    .input-group-text {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        border-radius: 8px 0 0 8px;
        padding: 0.5rem 0.75rem;
    }

    .input-group .form-control {
        border-radius: 0 8px 8px 0;
        padding: 0.5rem 0.75rem;
    }

    /* Button Styling */
    .btn {
        font-weight: 500;
        transition: all 0.2s;
        font-size: 0.9rem;
        padding: 0.5rem 1.25rem;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        border-color: #4f46e5;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
        border-color: #4338ca;
    }

    .btn-outline-secondary:hover {
        background-color: #f1f5f9;
    }

    /* Required field star */
    .text-danger {
        color: #dc2626 !important;
        font-size: 0.9rem;
    }

    /* Container and Card Spacing */
    .container-fluid.py-4 {
        padding-top: 1.5rem !important;
        padding-bottom: 1.5rem !important;
    }

    .card-body.p-4 {
        padding: 1.5rem !important;
    }

    /* Border radius */
    .rounded-3 {
        border-radius: 8px !important;
    }

    .rounded-4 {
        border-radius: 12px !important;
    }

    /* Smaller spacing */
    .mb-4 {
        margin-bottom: 1.25rem !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mb-2 {
        margin-bottom: 0.75rem !important;
    }

    .mb-1 {
        margin-bottom: 0.5rem !important;
    }

    .g-2 {
        --bs-gutter-x: 0.75rem;
        --bs-gutter-y: 0.75rem;
    }

    /* Textarea height */
    textarea.form-control {
        min-height: 80px;
    }
</style>

<script>
    // Date validation
    document.addEventListener('DOMContentLoaded', function() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');

        // Validasi untuk start_date dan end_date
        if (startDate && endDate) {
            startDate.addEventListener('change', function() {
                if (endDate.value && new Date(this.value) > new Date(endDate.value)) {
                    endDate.value = this.value;
                }
                endDate.min = this.value;
            });

            endDate.addEventListener('change', function() {
                if (startDate.value && new Date(this.value) < new Date(startDate.value)) {
                    alert('End Date tidak boleh lebih awal dari Start Date.');
                    this.value = startDate.value;
                }
            });
        }

        // Loading state saat submit
        const form = document.getElementById('joForm');
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
        });
    });
</script>
@endsection