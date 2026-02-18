@extends('layouts.app')

@section('title', 'Create Job Order')

@section('content')
    <div class="container-fluid py-3">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <a href="{{ route('job-orders.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                        <h5 class="text-dark mb-1 mt-2">Create Form</h5>
                        <p class="text-muted small mb-0">Complete new job order information</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-body p-3">
                        @if (session('success'))
                            <div class="alert alert-success border-0 d-flex align-items-center mb-3 p-2">
                                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 mb-3 p-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <span class="fw-medium">Error:</span>
                                </div>
                                <ul class="mb-0 mt-1 ps-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('job-orders.store') }}" method="POST">
                            @csrf

                            <!-- Informasi Utama -->
                            <div class="mb-4">
                                <h6 class="fw-medium text-dark mb-2">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>Informasi Utama
                                </h6>

                                <div class="row g-2">
                                    <div class="col-md-12 mb-2">
                                        <label for="name" class="form-label small text-dark">Nama Job Order <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control border-1 rounded-2 py-2 px-3 @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name') }}"
                                            placeholder="Masukkan nama job order" required>
                                        @error('name')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label for="project_id" class="form-label small text-dark">Project <span
                                                class="text-danger">*</span></label>
                                        <select
                                            class="form-select border-1 rounded-2 py-2 px-3 @error('project_id') is-invalid @enderror"
                                            id="project_id" name="project_id" required>
                                            <option value="">Pilih Project</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                                    {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('project_id')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label for="department_id" class="form-label small text-dark">Department <span
                                                class="text-danger">*</span></label>
                                        <select
                                            class="form-select border-1 rounded-2 py-2 px-3 @error('department_id') is-invalid @enderror"
                                            id="department_id" name="department_id" required>
                                            <option value="">Pilih Department</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12 mb-2">
                                        <label for="description" class="form-label small text-dark">Deskripsi</label>
                                        <textarea class="form-control border-1 rounded-2 py-2 px-3 @error('description') is-invalid @enderror" id="description"
                                            name="description" rows="2" placeholder="Tambahkan deskripsi job order">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <div class="mb-4">
                                <h6 class="fw-medium text-dark mb-2">Timeline</h6>

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label for="start_date" class="form-label small text-dark">Start Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-end-0 bg-light py-2">
                                                <i class="fas fa-calendar text-muted small"></i>
                                            </span>
                                            <input type="date"
                                                class="form-control border-1 rounded-2 py-2 px-3 @error('start_date') is-invalid @enderror"
                                                id="start_date" name="start_date" value="{{ old('start_date') }}">
                                            @error('start_date')
                                                <div class="invalid-feedback small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label for="end_date" class="form-label small text-dark">End Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-end-0 bg-light py-2">
                                                <i class="fas fa-calendar text-muted small"></i>
                                            </span>
                                            <input type="date"
                                                class="form-control border-1 rounded-2 py-2 px-3 @error('end_date') is-invalid @enderror"
                                                id="end_date" name="end_date" value="{{ old('end_date') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Note -->
                            <div class="mb-4">
                                <h6 class="fw-medium text-dark mb-2">Note</h6>

                                <div class="mb-2">
                                    <label for="notes" class="form-label small text-dark">Note</label>
                                    <textarea class="form-control border-1 rounded-2 py-2 px-3 @error('notes') is-invalid @enderror" id="notes"
                                        name="notes" rows="2" placeholder="Tambahkan note">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 pt-3 border-top">
                                <a href="{{ route('job-orders.index') }}"
                                    class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                    <i class="fas fa-save me-1"></i>Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Form Styling */
        .form-control,
        .form-select {
            border-color: #e2e8f0;
            font-size: 0.9rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc2626;
        }

        /* Labels */
        .form-label.small {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        /* Card Styling */
        .card {
            background: #ffffff;
        }

        /* Section Headers */
        h6.fw-medium {
            color: #334155;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Informasi Utama icon */
        h6.fw-medium i {
            color: #4f46e5;
            font-size: 0.9rem;
        }

        /* Input Group Styling */
        .input-group-text {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            border-radius: 6px 0 0 6px;
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn {
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        .btn-primary:hover {
            background-color: #4338ca;
            border-color: #4338ca;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
        }

        /* Spacing */
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .py-3 {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }

        /* Border radius */
        .rounded-2 {
            border-radius: 8px !important;
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        /* Alert */
        .alert {
            font-size: 0.9rem;
            border-radius: 8px;
        }

        /* Required star */
        .text-danger {
            color: #dc2626 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');

            if (startDate && endDate) {
                startDate.addEventListener('change', function() {
                    if (endDate.value && new Date(this.value) > new Date(endDate.value)) {
                        endDate.value = this.value;
                    }
                    endDate.min = this.value;
                });

                endDate.addEventListener('change', function() {
                    if (startDate.value && new Date(this.value) < new Date(startDate.value)) {
                        alert('End Date cannot be earlier than Start Date.');
                        this.value = startDate.value;
                    }
                });
            }
        });
    </script>
@endsection
