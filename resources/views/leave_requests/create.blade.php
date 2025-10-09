@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="mb-4 fw-bold">Create Leave Request</h2>
                <form method="POST" action="{{ route('leave_requests.store') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Name</label>
                            <select name="employee_id" id="employee_id"
                                class="form-select select2 border border-dark bg-white" required>
                                <option value="">Select</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" data-department="{{ $emp->department->name ?? '' }}"
                                        data-position="{{ $emp->position ?? '' }}"
                                        data-hiredate="{{ $emp->hire_date ? \Carbon\Carbon::parse($emp->hire_date)->format('d-m-Y') : '' }}">
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Department</label>
                            <input type="text" id="department"
                                class="form-control form-control-lg border border-dark bg-white" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Position</label>
                            <input type="text" id="position"
                                class="form-control form-control-lg border border-dark bg-white" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Hire Date</label>
                            <input type="text" id="hire_date"
                                class="form-control form-control-lg border border-dark bg-white" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">From Date</label>
                            <input type="date" name="start_date" id="start_date"
                                class="form-control form-control-lg border border-dark bg-white" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">To Date</label>
                            <input type="date" name="end_date" id="end_date"
                                class="form-control form-control-lg border border-dark bg-white" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Leave Duration</label>
                            <div class="input-group">
                                <input type="number" name="duration" id="duration"
                                    class="form-control form-control-lg border border-dark bg-white" min="0.01"
                                    step="0.01" required
                                    value="{{ old('duration', isset($leave) ? $leave->duration : '') }}">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-dark">Reason</label>
                            <textarea name="reason" class="form-control form-control-lg border border-dark bg-white" rows="5"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-dark">Leave Type</label>
                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                @foreach ($leaveTypes as $type)
                                    <div class="col">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="type"
                                                id="type_{{ $type }}" value="{{ $type }}" required>
                                            <label class="form-check-label fw-bold" for="type_{{ $type }}">
                                                {{ $leaveTypeLabels[$type] ?? $type }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('leave_requests.index') }}" class="btn px-4 py-2"
                            style="background:#ff2222;color:#fff;font-weight:bold;">Cancel</a>
                        <button type="submit" class="btn px-4 py-2"
                            style="background:#33e133;color:#fff;font-weight:bold;">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .form-label {
            font-size: 1.1rem;
        }

        .form-control-lg {
            font-size: 1.1rem;
        }
    </style>

    <!-- //RADIO BUTTON CUSTOM -->
    <style>
        .form-check-input[type="radio"] {
            width: 1em;
            height: 1em;
            border: 0.5px solid #000000ff !important;
            background-color: #fff;
            box-shadow: 0 0 0 2px #000000ff;

        }

        .form-check-input[type="radio"]:checked {
            background-color: #000000ff !important;
            border-color: #000000ff !important;
            box-shadow: 0 0 0 3px #000000ff;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            $('#employee_id').select2({
                width: '100%',
                placeholder: 'Select',
                allowClear: true,
                theme: 'bootstrap-5'
            });

            // Event untuk update field lain saat employee dipilih
            $('#employee_id').on('change', function() {
                const selected = this.options[this.selectedIndex];
                $('#department').val(selected.getAttribute('data-department') || '');
                $('#position').val(selected.getAttribute('data-position') || '');
                $('#hire_date').val(selected.getAttribute('data-hiredate') || '');
            });

            $('#start_date, #end_date').on('change', function() {
                let start = $('#start_date').val();
                let end = $('#end_date').val();
                if (start && end) {
                    let d1 = new Date(start);
                    let d2 = new Date(end);
                    let diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
                    $('#duration').val(diff > 0 ? diff + ' days' : '');
                } else {
                    $('#duration').val('');
                }
            });
        });
    </script>
@endpush
