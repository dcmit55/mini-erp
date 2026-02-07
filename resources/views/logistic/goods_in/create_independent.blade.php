@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Create Goods In</h2>
                <hr>
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('goods_in.store_independent') }}">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label>Material <span class="text-danger">*</span></label>
                            <select name="inventory_id" class="form-select select2" required>
                                <option value="">Select Material</option>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" data-unit="{{ $inventory->unit }}">
                                        {{ $inventory->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="quantity"
                                    class="form-control @error('quantity') is-invalid @enderror" step="any" required>
                                <span class="input-group-text unit-label">unit</span>
                            </div>
                            @error('quantity')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label>Job Order <span class="text-danger">*</span></label>
                            <select name="job_order_id" id="job_order_id" class="form-select select2"
                                data-placeholder="Select Job Order" required>
                                <option value="">Select Job Order</option>
                                @foreach ($jobOrders as $jo)
                                    <option value="{{ $jo->id }}" data-project-id="{{ $jo->project_id }}"
                                        data-project-name="{{ $jo->project->name ?? '' }}"
                                        data-department-name="{{ $jo->department->name ?? '' }}"
                                        {{ old('job_order_id') == $jo->id ? 'selected' : '' }}>
                                        {{ $jo->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('job_order_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                            <!-- Hidden Project ID (Auto-filled) -->
                            <input type="hidden" name="project_id" id="project_id" value="{{ old('project_id') }}"
                                required>

                            <!-- Project Display (Read-only) -->
                            <div id="project-display" class="mt-2 {{ old('project_id') ? '' : 'd-none' }}">
                                <small class="text-muted">Project:</small>
                                <strong id="project-name-text"></strong>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Returned/In At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="returned_at" class="form-control"
                                value="{{ old('returned_at', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Returned/In By</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->username }}" disabled>
                            @if (auth()->user()->department)
                                <div class="form-text">
                                    Department: {{ auth()->user()->department->name }}
                                </div>
                            @endif
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label>Remark</label>
                            <textarea name="remark" class="form-control" rows="3">{{ old('remark') }}</textarea>
                        </div>
                    </div>
                    <a href="{{ route('goods_in.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="goodsin-submit-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true,
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            // Auto-fill Project when Job Order is selected
            $('#job_order_id').on('change', function() {
                const selected = $(this).find(':selected');
                const projectId = selected.data('project-id');
                const projectName = selected.data('project-name');
                const departmentName = selected.data('department-name');

                if (projectId && projectName) {
                    $('#project_id').val(projectId);
                    $('#project-name-text').text(projectName);
                    $('#project-display').removeClass('d-none');
                } else {
                    $('#project_id').val('');
                    $('#project-name-text').text('');
                    $('#project-display').addClass('d-none');
                }

                // Show department info if available
                if (departmentName) {
                    // You can display department here if needed in the UI
                    console.log('Department:', departmentName);
                }
            });

            // Trigger on page load for old values
            $('#job_order_id').trigger('change');

            // Update unit label dynamically when material is selected
            $('select[name="inventory_id"]').on('change', function() {
                const selectedUnit = $(this).find(':selected').data('unit');
                $('.unit-label').text(selectedUnit || 'unit');
            });
            $('select[name="inventory_id"]').trigger('change');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('goods_in.store_independent') }}"]');
            const submitBtn = document.getElementById('goodsin-submit-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Submitting...';
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            // submitBtn.disabled = false;
            // spinner.classList.add('d-none');
            // submitBtn.childNodes[2].textContent = ' Submit';
        });
    </script>
@endpush
