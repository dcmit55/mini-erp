@extends('layouts.app')

@section('title', 'Edit Job Order')

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
                    <h4 class="fw-bold text-dark mb-1">Edit Job Order</h4>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark border rounded-3 px-3 py-1">
                            <i class="fas fa-hashtag me-1"></i>{{ $jobOrder->id }}
                        </span>
                        <p class="text-muted small mb-0">Perbarui informasi job order</p>
                    </div>
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

                    <form action="{{ route('production.job-orders.update', $jobOrder->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Informasi Utama -->
                        <div class="mb-5">
                            <h6 class="fw-semibold text-dark mb-4">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Informasi Utama
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label fw-medium text-dark">Nama Job Order <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-3 py-2 px-3 @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $jobOrder->name) }}"
                                           placeholder="Masukkan nama job order" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="project_id" class="form-label fw-medium text-dark">Project <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-3 py-2 px-3 @error('project_id') is-invalid @enderror" 
                                            id="project_id" 
                                            name="project_id"
                                            required>
                                        <option value="">Pilih Project</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}" 
                                                {{ old('project_id', $jobOrder->project_id) == $project->id ? 'selected' : '' }}>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label fw-medium text-dark">Department <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-3 py-2 px-3 @error('department_id') is-invalid @enderror" 
                                            id="department_id" 
                                            name="department_id" 
                                            required>
                                        <option value="">Pilih Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" 
                                                {{ old('department_id', $jobOrder->department_id) == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label fw-medium text-dark">Deskripsi</label>
                                    <textarea class="form-control border-1 rounded-3 py-2 px-3 @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3"
                                              placeholder="Tambahkan deskripsi job order">{{ old('description', $jobOrder->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="mb-5">
                            <h6 class="fw-semibold text-dark mb-4">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>Timeline
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label fw-medium text-dark">Start Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-light">
                                            <i class="fas fa-calendar text-muted"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control border-1 rounded-3 py-2 px-3 @error('start_date') is-invalid @enderror" 
                                               id="start_date" 
                                               name="start_date" 
                                               value="{{ old('start_date', $jobOrder->start_date ? $jobOrder->start_date->format('Y-m-d') : '') }}">
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label fw-medium text-dark">End Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-light">
                                            <i class="fas fa-calendar text-muted"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control border-1 rounded-3 py-2 px-3 @error('end_date') is-invalid @enderror" 
                                               id="end_date" 
                                               name="end_date" 
                                               value="{{ old('end_date', $jobOrder->end_date ? $jobOrder->end_date->format('Y-m-d') : '') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="mb-5">
                            <h6 class="fw-semibold text-dark mb-4">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Note
                            </h6>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label fw-medium text-dark">Note</label>
                                <textarea class="form-control border-1 rounded-3 py-2 px-3 @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="3"
                                          placeholder="Tambahkan note">{{ old('notes', $jobOrder->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Action Buttons -->                            
                            <div class="d-flex gap-3">
                                <a href="{{ route('production.job-orders.index') }}" 
                                   class="btn btn-outline-secondary rounded-3 px-4">
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-primary rounded-3 px-4">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <div class="icon-danger mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Hapus Job Order?</h5>
                    <p class="text-muted">Job order "{{ $jobOrder->name }}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <form action="{{ route('production.job-orders.destroy', $jobOrder->id) }}" method="POST" id="deleteForm">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger rounded-3 px-4">
                            <i class="fas fa-trash me-2"></i>Hapus
                        </button>
                    </div>
                </form>
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
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.1);
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc2626;
    }

    .form-control.bg-light {
        background-color: #f8fafc !important;
    }

    /* Card Styling */
    .card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }

    /* Section Headers */
    h6.fw-semibold {
        color: #334155;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
    }

    h6.fw-semibold i {
        color: #4f46e5;
    }

    /* Badge Styling */
    .badge.bg-light {
        background-color: #e0e7ff !important;
        border: 1px solid #c7d2fe;
        color: #4f46e5;
    }

    /* Input Group Styling */
    .input-group-text {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        border-radius: 8px 0 0 8px;
    }

    .input-group .form-control {
        border-radius: 0 8px 8px 0;
    }

    /* Modal Styling */
    .icon-danger {
        width: 80px;
        height: 80px;
        background-color: #fee2e2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    /* Button Styling */
    .btn {
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
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

    .btn-outline-danger:hover {
        background-color: #fee2e2;
    }

    /* Required field star */
    .text-danger {
        color: #dc2626 !important;
    }

    /* Header Styling */
    .container-fluid.py-4 {
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
    }

    /* Border radius for form elements */
    .rounded-3 {
        border-radius: 12px !important;
    }

    .rounded-4 {
        border-radius: 16px !important;
    }
</style>

<script>
    function confirmDelete() {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

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
    });
</script>
@endsection