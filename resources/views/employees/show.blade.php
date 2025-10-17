@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
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
                                    Employee Details
                                </h1>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning"
                                    title="Edit Employee">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('employees.timing', $employee) }}" class="btn btn-info"
                                    title="View Timings">
                                    <i class="bi bi-clock"></i>
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Basic Info & Photo -->
            <div class="col-lg-4 mb-4">
                <!-- Profile Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="rounded-circle shadow"
                                style="width: 120px; height: 120px; object-fit: cover;">
                            <span
                                class="position-absolute bottom-0 end-0 badge bg-{{ $employee->status_badge['color'] }} rounded-pill">
                                {{ $employee->status_badge['text'] }}
                            </span>
                        </div>
                        <h4 class="card-title mb-1">{{ $employee->name }}</h4>
                        <p class="text-muted mb-2">{{ $employee->position }}</p>
                        <small class="badge bg-light text-dark">{{ $employee->employee_no }}</small>

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

                <!-- Contact Information -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-telephone"></i> Contact Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="contact-item mb-3">
                            <label class="fw-semibold text-muted small">Email</label>
                            <div>
                                @if ($employee->email)
                                    <a href="mailto:{{ $employee->email }}" class="text-decoration-none">
                                        <i class="bi bi-envelope me-1"></i>{{ $employee->email }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="contact-item mb-3">
                            <label class="fw-semibold text-muted small">Phone</label>
                            <div>
                                @if ($employee->phone)
                                    <a href="tel:{{ $employee->phone }}" class="text-decoration-none">
                                        <i class="bi bi-telephone me-1"></i>{{ $employee->phone }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>

                        <!-- Address in Contact Card -->
                        @if ($employee->address)
                            <div class="contact-item mb-3">
                                <label class="fw-semibold text-muted small">Address</label>
                                <div>
                                    <i class="bi bi-geo-alt me-1"></i>{{ $employee->address }}
                                </div>
                            </div>
                        @endif

                        <div class="contact-item mb-3">
                            <label class="fw-semibold text-muted small">Bank Account</label>
                            <div>
                                @if ($employee->rekening)
                                    <i class="bi bi-credit-card me-1"></i>{{ $employee->formatted_rekening }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Detailed Info -->
            <div class="col-lg-8">
                <!-- Employee Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-person-badge"></i> Employee Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Employee Number</label>
                                <div class="fw-medium">{{ $employee->employee_no }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Department</label>
                                <div class="fw-medium">
                                    @if ($employee->department)
                                        <span class="badge bg-primary">{{ ucfirst($employee->department->name) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Gender & KTP ID -->
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Gender</label>
                                <div class="fw-medium">
                                    @if ($employee->gender)
                                        <span class="badge bg-{{ $employee->gender == 'male' ? 'info' : 'pink' }}">
                                            <i class="bi bi-gender-{{ $employee->gender }}"></i>
                                            {{ ucfirst($employee->gender) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">KTP ID</label>
                                <div class="fw-medium">{{ $employee->formatted_ktp_id }}</div>
                            </div>

                            <!-- Place & Date of Birth -->
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Place of Birth</label>
                                <div class="fw-medium">{{ $employee->place_of_birth ?? '-' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Date of Birth</label>
                                <div class="fw-medium">
                                    @if ($employee->date_of_birth)
                                        {{ $employee->date_of_birth->format('d M Y') }}
                                        <small class="text-muted">({{ $employee->age }} years old)</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Hire Date</label>
                                <div class="fw-medium">
                                    @if ($employee->hire_date)
                                        {{ $employee->hire_date->format('d M Y') }}
                                        <small class="text-muted">({{ $employee->hire_date->diffForHumans() }})</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Salary</label>
                                <div class="fw-medium">{{ $employee->formatted_salary }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Leave Balance</label>
                                <div class="fw-medium">
                                    <span
                                        class="badge bg-{{ $employee->saldo_cuti > 5 ? 'success' : ($employee->saldo_cuti > 0 ? 'warning' : 'danger') }}">
                                        {{ $employee->saldo_cuti ?? 0 }} Days
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-muted small">Status</label>
                                <div>
                                    <span class="badge bg-{{ $employee->status_badge['color'] }}">
                                        {{ $employee->status_badge['text'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if ($employee->notes)
                            <hr>
                            <div>
                                <label class="fw-semibold text-muted small">Notes</label>
                                <div class="mt-1">{{ $employee->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> Documents & Files</h6>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#uploadDocumentModal">
                            <i class="bi bi-plus"></i> Upload
                        </button>
                    </div>
                    <div class="card-body">
                        @if ($employee->documents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Name</th>
                                            <th>Size</th>
                                            <th>Uploaded</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employee->documents as $document)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ strtoupper(str_replace('_', ' ', $document->document_type)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>{{ $document->document_name }}</div>
                                                    @if ($document->description)
                                                        <small class="text-muted">{{ $document->description }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $document->formatted_file_size }}</td>
                                                <td>{{ $document->created_at->format('d M Y') }}</td>
                                                <td class="text-nowrap">
                                                    <a href="{{ $document->file_url }}" target="_blank"
                                                        class="btn btn-outline-primary btn-sm" title="View Document">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('employee-documents.download', $document) }}"
                                                        class="btn btn-outline-success btn-sm" title="Download Document">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-outline-danger btn-sm delete-document-btn"
                                                        data-document-id="{{ $document->id }}"
                                                        data-document-name="{{ $document->document_name }}"
                                                        title="Delete Document">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                                <div class="mt-3 text-muted">No documents uploaded yet</div>
                                <button class="btn btn-primary mt-2" data-bs-toggle="modal"
                                    data-bs-target="#uploadDocumentModal">
                                    Upload First Document
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Timings -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-clock"></i> Recent Timings</h6>
                        <a href="{{ route('employees.timing', $employee) }}" class="btn btn-sm btn-outline-primary">View
                            All</a>
                    </div>
                    <div class="card-body">
                        @if ($employee->timings->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Project</th>
                                            <th>Start-End</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employee->timings->take(5) as $timing)
                                            <tr>
                                                <td>{{ $timing->tanggal }}</td>
                                                <td>
                                                    {{ $timing->project ? $timing->project->name : '-' }}
                                                </td>
                                                <td>{{ $timing->start_time }} - {{ $timing->end_time }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $timing->status == 'completed' ? 'success' : 'warning' }}">
                                                        {{ ucfirst($timing->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="bi bi-clock text-muted" style="font-size: 2rem;"></i>
                                <div class="mt-2 text-muted">No timing records yet</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Document Modal dengan Real-time Validation -->
    <div class="modal fade" id="uploadDocumentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data"
                    id="uploadDocumentForm">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Alert container for form errors -->
                        <div id="modalAlert" class="alert alert-dismissible fade" style="display: none;" role="alert">
                            <div id="modalAlertMessage"></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select name="document_types[]" class="form-select" required id="modalDocumentType">
                                <option value="">Select Type</option>
                                @foreach (\App\Models\EmployeeDocument::getDocumentTypes() as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <div class="valid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Document Name <span class="text-danger">*</span></label>
                            <input type="text" name="document_names[]" class="form-control"
                                placeholder="Enter document name" required id="modalDocumentName">
                            <div class="invalid-feedback"></div>
                            <div class="valid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">File <span class="text-danger">*</span></label>
                            <input type="file" name="documents[]" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required id="modalDocumentFile">
                            <small class="text-muted d-block">Max 5MB. Supported: PDF, DOC, DOCX, JPG, PNG</small>
                            <div class="invalid-feedback"></div>
                            <div class="valid-feedback"></div>

                            <!-- File info display -->
                            <div id="fileInfo" class="mt-2" style="display: none;">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <i class="bi bi-file-earmark me-2"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium" id="fileName"></div>
                                        <small class="text-muted" id="fileSize"></small>
                                    </div>
                                    <div id="fileStatus"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="document_descriptions[]" class="form-control" rows="2" placeholder="Brief description..."
                                id="modalDocumentDescription"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalUploadBtn" disabled>
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                id="uploadSpinner"></span>
                            Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .contact-item label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        /* Gender badge color for female */
        .bg-pink {
            background-color: #e91e63 !important;
        }

        /* KTP ID formatting */
        .fw-medium {
            letter-spacing: 0.5px;
        }

        .card {
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .badge {
            font-size: 0.75rem;
        }

        .table td {
            vertical-align: middle;
        }

        /* Modal validation styling */
        .modal .form-control.is-valid {
            border-color: #198754;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23198754' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 8l3 3 5-5'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .modal .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23dc3545' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 4l8 8M12 4l-8 8'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .modal .form-select.is-valid {
            border-color: #198754;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23198754' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 8l3 3 5-5'/%3e%3c/svg%3e");
            background-position: right 0.75rem center, right 2.25rem center;
            background-size: 16px 12px, calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
            background-repeat: no-repeat;
        }

        .modal .form-select.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23dc3545' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 4l8 8M12 4l-8 8'/%3e%3c/svg%3e");
            background-position: right 0.75rem center, right 2.25rem center;
            background-size: 16px 12px, calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
            background-repeat: no-repeat;
        }

        /* File info styling */
        #fileInfo {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Drag and drop styling for modal */
        .modal .form-control[type="file"].drag-over {
            border-color: #0d6efd !important;
            background-color: #f0f8ff !important;
            border-style: dashed !important;
            border-width: 2px !important;
        }

        /* Loading animation for delete button */
        .delete-document-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Smooth row deletion animation */
        .table tbody tr {
            transition: all 0.3s ease;
        }

        /* Document table hover effects */
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Button hover effects */
        .btn-outline-danger:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }

        .btn-outline-primary:hover,
        .btn-outline-success:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const modal = document.getElementById('uploadDocumentModal');
            const form = document.getElementById('uploadDocumentForm');
            const alertContainer = document.getElementById('modalAlert');
            const alertMessage = document.getElementById('modalAlertMessage');
            const uploadBtn = document.getElementById('modalUploadBtn');
            const uploadSpinner = document.getElementById('uploadSpinner');

            // Form inputs
            const documentType = document.getElementById('modalDocumentType');
            const documentName = document.getElementById('modalDocumentName');
            const documentFile = document.getElementById('modalDocumentFile');
            const documentDescription = document.getElementById('modalDocumentDescription');

            // File info elements
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const fileStatus = document.getElementById('fileStatus');

            // Validation state
            let validationState = {
                type: false,
                name: false,
                file: false
            };

            // Utility functions
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function showAlert(message, type = 'danger') {
                alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
                alertMessage.innerHTML = message;
                alertContainer.style.display = 'block';

                // Auto hide after 5 seconds
                setTimeout(() => {
                    if (alertContainer.classList.contains('show')) {
                        const alert = new bootstrap.Alert(alertContainer);
                        alert.close();
                    }
                }, 5000);
            }

            function hideAlert() {
                if (alertContainer.classList.contains('show')) {
                    const alert = new bootstrap.Alert(alertContainer);
                    alert.close();
                }
            }

            function setValidationFeedback(element, isValid, message) {
                const invalidFeedback = element.parentNode.querySelector('.invalid-feedback');
                const validFeedback = element.parentNode.querySelector('.valid-feedback');

                element.classList.remove('is-valid', 'is-invalid');

                if (isValid) {
                    element.classList.add('is-valid');
                    if (validFeedback) validFeedback.textContent = message;
                    if (invalidFeedback) invalidFeedback.textContent = '';
                } else {
                    element.classList.add('is-invalid');
                    if (invalidFeedback) invalidFeedback.textContent = message;
                    if (validFeedback) validFeedback.textContent = '';
                }
            }

            function updateSubmitButton() {
                const allValid = Object.values(validationState).every(state => state);
                uploadBtn.disabled = !allValid;

                if (allValid) {
                    uploadBtn.classList.remove('btn-secondary');
                    uploadBtn.classList.add('btn-primary');
                } else {
                    uploadBtn.classList.remove('btn-primary');
                    uploadBtn.classList.add('btn-secondary');
                }
            }

            function validateDocumentType() {
                const value = documentType.value;
                const isValid = value !== '';

                validationState.type = isValid;

                if (isValid) {
                    setValidationFeedback(documentType, true, 'Document type selected');
                } else {
                    setValidationFeedback(documentType, false, 'Please select document type');
                }

                updateSubmitButton();
            }

            function validateDocumentName() {
                const value = documentName.value.trim();
                let isValid = false;
                let message = '';

                if (value === '') {
                    message = 'Document name is required';
                } else if (value.length < 3) {
                    message = 'Document name must be at least 3 characters';
                } else if (value.length > 255) {
                    message = 'Document name must not exceed 255 characters';
                } else {
                    isValid = true;
                    message = 'Document name is valid';
                }

                validationState.name = isValid;
                setValidationFeedback(documentName, isValid, message);
                updateSubmitButton();
            }

            function validateDocumentFile() {
                const file = documentFile.files[0];
                let isValid = false;
                let message = '';

                // Hide file info initially
                fileInfo.style.display = 'none';

                if (!file) {
                    message = 'Please select a file';
                } else {
                    // Check file type
                    const allowedTypes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg',
                        'image/png',
                        'image/jpg'
                    ];

                    if (!allowedTypes.includes(file.type)) {
                        message = 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed';
                    } else if (file.size > 5242880) { // 5MB
                        message = 'File size too large. Maximum 5MB allowed';
                    } else {
                        isValid = true;
                        message = `File "${file.name}" is ready for upload`;

                        // Show file info
                        fileName.textContent = file.name;
                        fileSize.textContent = formatFileSize(file.size);
                        fileStatus.innerHTML = '<i class="bi bi-check-circle text-success"></i>';
                        fileInfo.style.display = 'block';
                    }
                }

                if (!isValid && file) {
                    // Show error in file info
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    fileStatus.innerHTML = '<i class="bi bi-exclamation-circle text-danger"></i>';
                    fileInfo.style.display = 'block';
                }

                validationState.file = isValid;
                setValidationFeedback(documentFile, isValid, message);
                updateSubmitButton();
            }

            // Event listeners
            documentType.addEventListener('change', validateDocumentType);
            documentName.addEventListener('input', validateDocumentName);
            documentFile.addEventListener('change', validateDocumentFile);

            // Drag and drop for file input
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                documentFile.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                documentFile.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                documentFile.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                documentFile.classList.add('drag-over');
            }

            function unhighlight(e) {
                documentFile.classList.remove('drag-over');
            }

            documentFile.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    documentFile.files = files;
                    validateDocumentFile();
                }
            }

            // Form submission with error handling
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate all fields before submit
                validateDocumentType();
                validateDocumentName();
                validateDocumentFile();

                if (!Object.values(validationState).every(state => state)) {
                    showAlert(
                        '<i class="bi bi-exclamation-triangle"></i> Please fix the validation errors before submitting.',
                        'warning');
                    return;
                }

                // Show loading state
                uploadBtn.disabled = true;
                uploadSpinner.classList.remove('d-none');
                uploadBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Uploading...';

                hideAlert();

                // Create FormData for proper file upload
                const formData = new FormData(form);

                // Submit via fetch for better error handling
                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Upload failed');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Success
                        showAlert('<i class="bi bi-check-circle"></i> Document uploaded successfully!',
                            'success');

                        // Close modal after delay and reload page
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(modal).hide();
                            location.reload();
                        }, 1500);
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                        showAlert('<i class="bi bi-exclamation-circle"></i> ' + error.message,
                            'danger');
                    })
                    .finally(() => {
                        // Reset button state
                        uploadBtn.disabled = false;
                        uploadSpinner.classList.add('d-none');
                        uploadBtn.innerHTML = 'Upload Document';
                        updateSubmitButton();
                    });
            });

            // Reset form when modal is closed
            modal.addEventListener('hidden.bs.modal', function() {
                form.reset();

                // Clear validation states
                Object.keys(validationState).forEach(key => {
                    validationState[key] = false;
                });

                // Clear validation classes
                form.querySelectorAll('.is-valid, .is-invalid').forEach(element => {
                    element.classList.remove('is-valid', 'is-invalid');
                });

                // Clear feedback messages
                form.querySelectorAll('.invalid-feedback, .valid-feedback').forEach(element => {
                    element.textContent = '';
                });

                // Hide file info and alert
                fileInfo.style.display = 'none';
                hideAlert();

                updateSubmitButton();
            });

            // Initialize button state
            updateSubmitButton();

            // Handle document deletion with SweetAlert
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-document-btn')) {
                    e.preventDefault();

                    const btn = e.target.closest('.delete-document-btn');
                    const documentId = btn.getAttribute('data-document-id');
                    const documentName = btn.getAttribute('data-document-name');

                    Swal.fire({
                        title: 'Delete Document?',
                        html: `Are you sure you want to delete "<strong>${documentName}</strong>"?<br><small class="text-muted">This action cannot be undone.</small>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Delete it!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        focusCancel: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteDocument(documentId, documentName, btn);
                        }
                    });
                }
            });

            // Function to delete document via AJAX
            function deleteDocument(documentId, documentName, btn) {
                // Show loading state
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';
                btn.disabled = true;

                // Show loading toast
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the document.',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/employee-documents/${documentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from table
                            const row = btn.closest('tr');
                            if (row) {
                                row.style.transition = 'all 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-100px)';

                                setTimeout(() => {
                                    row.remove();

                                    // Check if table is empty
                                    const tbody = document.querySelector('.table tbody');
                                    if (tbody && tbody.children.length === 0) {
                                        // Replace table with empty state
                                        const tableContainer = document.querySelector(
                                            '.table-responsive');
                                        if (tableContainer) {
                                            tableContainer.innerHTML = `
                                    <div class="text-center py-4">
                                        <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                                        <div class="mt-3 text-muted">No documents uploaded yet</div>
                                        <button class="btn btn-primary mt-2" data-bs-toggle="modal"
                                            data-bs-target="#uploadDocumentModal">
                                            Upload First Document
                                        </button>
                                    </div>
                                `;
                                        }
                                    }
                                }, 300);
                            }

                            // Show success message
                            Swal.fire({
                                title: 'Deleted!',
                                text: `"${documentName}" has been deleted successfully.`,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });

                            // Update document count in quick stats
                            const docCount = document.querySelector('.fw-bold.text-info');
                            if (docCount) {
                                const currentCount = parseInt(docCount.textContent);
                                docCount.textContent = Math.max(0, currentCount - 1);
                            }
                        } else {
                            throw new Error(data.message || 'Failed to delete document');
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);

                        // Restore button state
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;

                        // Show error message
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to delete document. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    });
            }

            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.classList.contains('show')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });
    </script>
@endpush
