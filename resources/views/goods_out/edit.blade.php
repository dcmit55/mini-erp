@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Edit Goods Out</h2>
                <hr>
                @if ($fromMaterialRequest)
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>Note:</strong> Some fields (Material, Project, Requested By) cannot be changed because this
                        Goods Out comes from a Material Request.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('goods_out.update', $goodsOut->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="mb-3">
                            <label>Material <span class="text-danger">*</span></label>
                            <select name="inventory_id" class="form-select select2"
                                {{ $fromMaterialRequest ? 'disabled' : '' }} required>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" data-unit="{{ $inventory->unit }}"
                                        data-stock="{{ $inventory->quantity }}"
                                        {{ $inventory->id == $goodsOut->inventory_id ? 'selected' : '' }}>
                                        {{ $inventory->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="available-qty" class="form-text d-none"></div>
                            @if ($fromMaterialRequest)
                                <input type="hidden" name="inventory_id" value="{{ $goodsOut->inventory_id }}">
                            @endif
                        </div>
                        <div class="mb-3">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="quantity"
                                    class="form-control @error('quantity') is-invalid @enderror"
                                    value="{{ old('quantity', $goodsOut->quantity) }}" step="any" required>
                                <span class="input-group-text unit-label">{{ $goodsOut->inventory->unit }}</span>
                            </div>
                            @error('quantity')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label>Project <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select select2"
                                {{ $fromMaterialRequest ? 'disabled' : '' }}>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        {{ $project->id == $goodsOut->project_id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($fromMaterialRequest)
                                <input type="hidden" name="project_id" value="{{ $goodsOut->project_id }}">
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label>Requested By <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select select2" {{ $fromMaterialRequest ? 'disabled' : '' }}
                                required>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        data-department="{{ $user->department ? $user->department->name : '' }}"
                                        {{ $user->username == $goodsOut->requested_by ? 'selected' : '' }}>
                                        {{ $user->username }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($fromMaterialRequest)
                                <input type="hidden" name="user_id"
                                    value="{{ $users->firstWhere('username', $goodsOut->requested_by)?->id }}">
                            @endif
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Department</label>
                            <input type="text" class="form-control" id="department" value="{{ $goodsOut->department }}"
                                disabled>
                        </div>
                    </div>

                    <div class="row">
                        <div class="mb-3">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea name="remark" id="remark" class="form-control" rows="2">{{ old('remark', $goodsOut->remark ?? '') }}</textarea>
                        </div>
                    </div>

                    <a href="{{ route('goods_out.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="goodsout-update-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Update
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
                placeholder: 'Select an option',
                allowClear: true
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            // Set initial department value
            const initialDepartment = $('select[name="user_id"]').find(':selected').data('department');
            $('#department').val(initialDepartment || '');

            // Update department dynamically when user is selected
            $('select[name="user_id"]').on('change', function() {
                const selectedDepartment = $(this).find(':selected').data('department');
                $('#department').val(selectedDepartment || '');
            });

            @if ($fromMaterialRequest)
                $('select[name="project_id"]').prop('disabled', true);
            @endif
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('goods_out.update', $goodsOut->id) }}"]');
            const submitBtn = document.getElementById('goodsout-update-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Updating...';
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            // submitBtn.disabled = false;
            // spinner.classList.add('d-none');
            // submitBtn.childNodes[2].textContent = ' Update';
        });

        // Update available quantity and unit label when inventory changes
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
