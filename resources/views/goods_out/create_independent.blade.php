@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Create Goods Out</h2>
                <hr>
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('goods_out.store_independent') }}">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label>Material <span class="text-danger">*</span></label>
                            <select name="inventory_id" class="form-select select2" required>
                                <option value="">Select an option</option>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" data-unit="{{ $inventory->unit }}"
                                        data-stock="{{ $inventory->quantity }}"
                                        {{ old('inventory_id') == $inventory->id ? 'selected' : '' }}>
                                        {{ $inventory->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="available-qty" class="form-text d-none"></div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="quantity"
                                    class="form-control @error('quantity') is-invalid @enderror" step="any"
                                    value="{{ old('quantity') }}" required>
                                <span class="input-group-text unit-label">{{ old('unit', 'unit') }}</span>
                            </div>
                            <input type="hidden" name="unit" id="unit-hidden" value="{{ old('unit') }}">
                            @error('quantity')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label>Project</label>
                            <select name="project_id" class="form-select select2">
                                <option value="" class="text-muted">No Project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        data-department="{{ $project->department->name ?? '' }}"
                                        {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="department-text" class="form-text d-none">Department</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label>Requested By <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select select2" required>
                                <option value="">Select an option</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        data-department="{{ $user->department ? $user->department->name : '' }}"
                                        {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->username }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('user_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <div class="col-lg-6 mb-3">
                            <label>Department</label>
                            <input type="text" class="form-control" id="department" value="" disabled>
                            <input type="hidden" name="department" id="department-hidden" value="">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea name="remark" id="remark" class="form-control" rows="2">{{ old('remark') }}</textarea>
                            @error('remark')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <a href="{{ route('goods_out.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="goodsout-submit-btn">
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
                allowClear: true
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            // Update unit label dynamically when material is selected
            $('select[name="inventory_id"]').on('change', function() {
                const selectedUnit = $(this).find(':selected').data('unit');
                $('.unit-label').text(selectedUnit || 'unit');
                $('#unit-hidden').val(selectedUnit || 'unit'); // Update hidden input
            });

            // Update department dynamically when user is selected
            $('select[name="user_id"]').on('change', function() {
                const selectedDepartment = $(this).find(':selected').data('department');
                $('#department').val(selectedDepartment || '');
                $('#department-hidden').val(selectedDepartment || '');
            });

            // Trigger change event on page load to restore old values
            $('select[name="inventory_id"]').trigger('change');
            $('select[name="user_id"]').trigger('change');

            // Update department text when project is selected
            $('select[name="project_id"]').on('change', function() {
                const selected = $(this).find(':selected');
                const department = selected.data('department');
                const $departmentDiv = $('#department-text');
                $departmentDiv.removeClass('d-none text-danger text-warning');

                if (selected.val() && department) {
                    $departmentDiv.text(
                        `Department: ${department.charAt(0).toUpperCase() + department.slice(1)}`);
                } else {
                    $departmentDiv.addClass('d-none').text('Department');
                }
            });
            $('select[name="project_id"]').trigger('change');
        });

        // Handle form submission with spinner
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('goods_out.store_independent') }}"]');
            const submitBtn = document.getElementById('goodsout-submit-btn');
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

        // Update available qty when material is selected
        $('select[name="inventory_id"]').on('change', function() {
            const selected = $(this).find(':selected');
            const selectedUnit = selected.data('unit');
            const selectedStock = selected.data('stock');
            $('.unit-label').text(selectedUnit || 'unit');

            const $availableQty = $('#available-qty');
            $availableQty.removeClass('d-none text-danger text-warning');

            if (selected.val() && selectedStock !== undefined) {
                let colorClass = '';
                if (selectedStock == 0) {
                    colorClass = 'text-danger';
                } else if (selectedStock < 3) {
                    colorClass = 'text-warning';
                }
                $availableQty
                    .text(`Available Qty: ${selectedStock} ${selectedUnit || ''}`)
                    .addClass(colorClass);
            } else {
                $availableQty.addClass('d-none').text('');
            }
        });
        $('select[name="inventory_id"]').trigger('change');
    </script>
@endpush
