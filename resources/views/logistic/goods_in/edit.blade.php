@extends('layouts.app')
@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Edit Goods In</h2>
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
                <form action="{{ route('goods_in.update', $goods_in->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        @if ($goods_in->goods_out_id && $goods_in->goodsOut)
                            {{-- From Goods Out: Show Job Order & Project (readonly) --}}
                            <div class="col-lg-6 mb-3">
                                <label>Job Order</label>
                                <input type="text" class="form-control"
                                    value="{{ $goods_in->goodsOut->jobOrder->name ?? '-' }}" disabled>
                                <input type="hidden" name="job_order_id" value="{{ $goods_in->goodsOut->job_order_id }}">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Project</label>
                                <input type="text" class="form-control"
                                    value="{{ $goods_in->goodsOut->project->name ?? '-' }}" disabled>
                                <input type="hidden" name="project_id" value="{{ $goods_in->goodsOut->project_id }}">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Material</label>
                                <input type="text" class="form-control"
                                    value="{{ $goods_in->goodsOut->inventory->name }}" disabled>
                                <input type="hidden" name="inventory_id" value="{{ $goods_in->goodsOut->inventory_id }}">
                            </div>
                        @else
                            {{-- Independent Goods In: Select Job Order (auto-fill Project) --}}
                            <div class="col-lg-12 mb-3">
                                <label>Job Order</label>
                                <select name="job_order_id" id="job_order_id" class="form-control select2">
                                    <option value="">No Job Order</option>
                                    @foreach ($jobOrders as $jo)
                                        <option value="{{ $jo->id }}" data-project-id="{{ $jo->project_id }}"
                                            data-project-name="{{ $jo->project->name ?? '' }}"
                                            data-department-name="{{ $jo->department->name ?? '' }}"
                                            {{ $goods_in->job_order_id == $jo->id ? 'selected' : '' }}>
                                            {{ $jo->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Hidden Project ID (Auto-filled) -->
                                <input type="hidden" name="project_id" id="project_id"
                                    value="{{ $goods_in->project_id }}">

                                <!-- Project Display (Read-only) -->
                                <div id="project-display" class="mt-2 {{ $goods_in->project_id ? '' : 'd-none' }}">
                                    <small class="text-muted">Project:</small>
                                    <strong id="project-name-text">{{ $goods_in->project->name ?? '' }}</strong>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Material <span class="text-danger">*</span></label>
                                <select name="inventory_id" class="form-control select2" required>
                                    @foreach ($inventories as $inventory)
                                        <option value="{{ $inventory->id }}"
                                            {{ $goods_in->inventory_id == $inventory->id ? 'selected' : '' }}>
                                            {{ $inventory->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-lg-6 mb-3">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="quantity"
                                    class="form-control @error('quantity') is-invalid @enderror"
                                    value="{{ $goods_in->quantity }}" required>
                                <span class="input-group-text unit-label">
                                    {{ $goods_in->inventory ? $goods_in->inventory->unit : 'unit' }}
                                </span>
                            </div>
                            @error('quantity')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Returned At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="returned_at" class="form-control"
                                value="{{ \Carbon\Carbon::parse($goods_in->returned_at)->format('Y-m-d\TH:i') }}" required>
                            <small class="form-text text-muted">
                                Current: {{ \Carbon\Carbon::parse($goods_in->returned_at)->format('m/d/Y, H:i') }}
                            </small>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Returned/In By</label>
                            <input type="text" class="form-control" value="{{ $goods_in->returned_by }}" disabled>
                            @if ($userDept && $userDept->department)
                                <div class="form-text">
                                    Department: {{ $userDept->department->name }}
                                </div>
                            @endif
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea name="remark" id="remark" class="form-control">{{ old('remark', $goods_in->remark) }}</textarea>
                        </div>
                    </div>
                    <a href="{{ route('goods_in.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="goodsin-update-btn">
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
                allowClear: true
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        .focus();
                }, 100);
            });

            @if (!$goods_in->goods_out_id)
                // Auto-fill Project + Department when Job Order is selected
                $('#job_order_id').on('change', function() {
                    const selectedOption = $(this).find(':selected');
                    const projectId = selectedOption.data('project-id');
                    const projectName = selectedOption.data('project-name');
                    const departmentName = selectedOption.data('department-name');

                    if (projectId) {
                        // Auto-fill Project
                        $('#project_id').val(projectId);
                        $('#project-name-text').text(projectName);
                        $('#project-display').removeClass('d-none');
                    } else {
                        // Clear Project
                        $('#project_id').val('');
                        $('#project-name-text').text('');
                        $('#project-display').addClass('d-none');
                    }
                });

                // Trigger on page load to populate project from existing data
                $('#job_order_id').trigger('change');
            @endif

            // Update unit label dynamically when material is selected
            $('select[name="inventory_id"]').on('change', function() {
                const selectedUnit = $(this).find(':selected').data('unit');
                $('.unit-label').text(selectedUnit || 'unit');
            });
            $('select[name="inventory_id"]').trigger('change');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('goods_in.update', $goods_in->id) }}"]');
            const submitBtn = document.getElementById('goodsin-update-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Updating...';
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
            submitBtn.childNodes[2].textContent = ' Update';
        });
    </script>
@endpush
