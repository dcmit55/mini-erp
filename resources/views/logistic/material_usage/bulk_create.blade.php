@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h4 class="">Bulk Material Usage</h4>
                <p class="text-muted mb-3">Use this form to record multiple material usage at once. You can add multiple
                    rows for different projects and materials.</p>

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('material_usage.bulk.store') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm" id="bulk-material-table"
                            style="min-width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">Job Order</th>
                                    <th style="width: 20%;">Project <span class="text-muted">(auto)</span></th>
                                    <th style="width: 25%;">Material <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">Used Quantity <span class="text-danger">*</span></th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bulk-rows">
                                @foreach (old('items', [0 => []]) as $index => $item)
                                    <tr class="align-top">
                                        <td data-label="Job Order">
                                            <select name="items[{{ $index }}][job_order_id]"
                                                class="form-select select2 job-order-select">
                                                <option value="">Select Job Order (Optional)</option>
                                                @foreach ($jobOrders as $jo)
                                                    <option value="{{ $jo->id }}"
                                                        data-project-id="{{ $jo->project_id }}"
                                                        data-project-name="{{ $jo->project->name ?? '' }}"
                                                        {{ old("items.$index.job_order_id") == $jo->id ? 'selected' : '' }}>
                                                        {{ $jo->id }} - {{ $jo->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("items.$index.job_order_id")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td data-label="Project">
                                            <input type="hidden" name="items[{{ $index }}][project_id]"
                                                class="project-id-input">
                                            <input type="text" class="form-control project-name-display" readonly
                                                placeholder="Select Job Order first">
                                            @error("items.$index.project_id")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td data-label="Material">
                                            <select name="items[{{ $index }}][inventory_id]"
                                                class="form-select select2 material-select" required>
                                                <option value="">Select Material</option>
                                                @foreach ($inventories as $inventory)
                                                    <option value="{{ $inventory->id }}"
                                                        data-unit="{{ $inventory->unit }}"
                                                        data-stock="{{ $inventory->quantity }}"
                                                        {{ old("items.$index.inventory_id") == $inventory->id ? 'selected' : '' }}>
                                                        {{ $inventory->name }}
                                                        @if ($inventory->category)
                                                            ({{ $inventory->category->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="available-qty-text form-text d-none"></div>
                                            @error("items.$index.inventory_id")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td data-label="Used Quantity">
                                            <div class="input-group">
                                                <input type="number" name="items[{{ $index }}][used_quantity]"
                                                    class="form-control @error("items.$index.used_quantity") is-invalid @enderror"
                                                    step="any" value="{{ old("items.$index.used_quantity") }}"
                                                    required>
                                                <span class="input-group-text unit-label">unit</span>
                                            </div>
                                            @error("items.$index.used_quantity")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td data-label="Action">
                                            <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn btn-outline-primary" id="add-row">+ Add Row</button>
                        <div>
                            <a href="{{ route('material_usage.index') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-success" id="bulk-submit-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                    aria-hidden="true"></span>
                                Submit All
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let rowCount = {{ count(old('items', [0 => []])) }};

            // Initialize Select2 for existing rows
            initializeSelect2();

            // Add row button
            $('#add-row').click(function() {
                addNewRow();
            });

            // Remove row
            $(document).on('click', '.remove-row', function() {
                if ($('#bulk-rows tr').length > 1) {
                    $(this).closest('tr').remove();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Remove',
                        text: 'At least one row is required'
                    });
                }
            });

            // Job Order change event - auto fill project
            $(document).on('change', '.job-order-select', function() {
                const $row = $(this).closest('tr');
                const selectedOption = $(this).find('option:selected');
                const projectId = selectedOption.data('project-id');
                const projectName = selectedOption.data('project-name');

                if (projectId) {
                    $row.find('.project-id-input').val(projectId);
                    $row.find('.project-name-display').val(projectName);
                } else {
                    $row.find('.project-id-input').val('');
                    $row.find('.project-name-display').val('');
                }
            });

            // Material change event - show available quantity
            $(document).on('change', '.material-select', function() {
                const $row = $(this).closest('tr');
                const selectedOption = $(this).find('option:selected');
                const stock = selectedOption.data('stock');
                const unit = selectedOption.data('unit');

                if (stock !== undefined) {
                    $row.find('.available-qty-text')
                        .removeClass('d-none')
                        .html(
                            `<i class="bi bi-info-circle me-1"></i>Available: <strong>${stock} ${unit || 'units'}</strong>`
                            );
                    $row.find('.unit-label').text(unit || 'unit');
                } else {
                    $row.find('.available-qty-text').addClass('d-none');
                }
            });

            // Function to add new row
            function addNewRow() {
                const newRow = `
                    <tr class="align-top">
                        <td data-label="Job Order">
                            <select name="items[${rowCount}][job_order_id]" class="form-select select2 job-order-select">
                                <option value="">Select Job Order (Optional)</option>
                                @foreach ($jobOrders as $jo)
                                    <option value="{{ $jo->id }}" data-project-id="{{ $jo->project_id }}" data-project-name="{{ $jo->project->name ?? '' }}">
                                        {{ $jo->id }} - {{ $jo->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td data-label="Project">
                            <input type="hidden" name="items[${rowCount}][project_id]" class="project-id-input">
                            <input type="text" class="form-control project-name-display" readonly placeholder="Select Job Order first">
                        </td>
                        <td data-label="Material">
                            <select name="items[${rowCount}][inventory_id]" class="form-select select2 material-select" required>
                                <option value="">Select Material</option>
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory->id }}" data-unit="{{ $inventory->unit }}" data-stock="{{ $inventory->quantity }}">
                                        {{ $inventory->name }}
                                        @if ($inventory->category)
                                            ({{ $inventory->category->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="available-qty-text form-text d-none"></div>
                        </td>
                        <td data-label="Used Quantity">
                            <div class="input-group">
                                <input type="number" name="items[${rowCount}][used_quantity]" class="form-control" step="any" required>
                                <span class="input-group-text unit-label">unit</span>
                            </div>
                        </td>
                        <td data-label="Action">
                            <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                        </td>
                    </tr>
                `;

                $('#bulk-rows').append(newRow);
                initializeSelect2();
                rowCount++;
            }

            // Initialize Select2
            function initializeSelect2() {
                $('.select2').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            placeholder: $(this).data('placeholder') || 'Select an option'
                        });
                    }
                });
            }

            // Form submission
            $('#bulk-submit-btn').click(function(e) {
                const spinner = $(this).find('.spinner-border');
                spinner.removeClass('d-none');
                $(this).prop('disabled', true);
            });
        });
    </script>
@endpush
