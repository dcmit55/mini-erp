@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <form action="{{ route('shippings.create') }}" method="POST" id="pre-shippings-form">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered" id="preShippingTable">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Purchase Type</th>
                            <th>Project Name</th>
                            <th>Material Name</th>
                            <th>Qty To Buy</th>
                            <th>Unit Type</th>
                            <th>Supplier</th>
                            <th>Unit Price</th>
                            <th>Domestic WBL NO</th>
                            <th>Same Supplier Selection</th>
                            <th>Percentage if same supplier</th>
                            <th>Domestic Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $req)
                            <tr>
                                <td>
                                    <input type="checkbox" class="row-check" name="pre_shipping_ids[]"
                                        value="{{ $req->preShipping->id }}">
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $req->type)) }}</td>
                                <td>{{ $req->project->name ?? '-' }}</td>
                                <td>{{ $req->material_name }}</td>
                                <td>{{ $req->required_quantity }}</td>
                                <td>{{ $req->unit }}</td>
                                <td>{{ $req->supplier->name ?? '-' }}</td>
                                <td>{{ $req->price_per_unit }}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm domestic-waybill-input"
                                        value="{{ $req->preShipping->domestic_waybill_no ?? '' }}">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="same-supplier-checkbox"
                                        {{ $req->preShipping->same_supplier_selection ?? false ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <input type="number" min="0" max="100" step="0.01"
                                        class="form-control form-control-sm percentage-input"
                                        value="{{ $req->preShipping->percentage_if_same_supplier ?? '' }}">
                                </td>
                                <td>
                                    <input type="number" min="0" step="0.01"
                                        class="form-control form-control-sm domestic-cost-input"
                                        value="{{ $req->preShipping->domestic_cost ?? '' }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary mt-3 float-end">Proceed To Shippings</button>
        </form>
    @endsection

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Inisialisasi DataTable
                var table = $('#preShippingTable').DataTable();

                // Handle select all
                $('#selectAll').on('change', function() {
                    var checked = $(this).prop('checked');
                    $('.row-check').prop('checked', checked);
                });

                // Handle form submit
                $('#pre-shippings-form').on('submit', function(e) {
                    var checkedBoxes = $('.row-check:checked');
                    if (checkedBoxes.length === 0) {
                        e.preventDefault();
                        alert('Pilih minimal satu data!');
                        return false;
                    }
                });
            });

            // Inline autosave logic (tidak diubah)
            function quickUpdate(id, data, input) {
                $.post('/pre-shippings/' + id + '/quick-update', Object.assign(data, {
                        _token: '{{ csrf_token() }}'
                    }))
                    .fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to save', 'error');
                        if (input) $(input).addClass('is-invalid');
                    })
                    .done(function() {
                        if (input) $(input).removeClass('is-invalid').addClass('is-valid');
                        setTimeout(() => {
                            if (input) $(input).removeClass('is-valid');
                        }, 1000);
                    });
            }

            // Debounced handler for text/number input
            $('.domestic-waybill-input, .percentage-input, .domestic-cost-input').each(function() {
                let $input = $(this);
                let handler = debounce(function() {
                    let id = $input.closest('tr').data('id');
                    let data = {};
                    if ($input.hasClass('domestic-waybill-input')) data.domestic_waybill_no = $input.val();
                    if ($input.hasClass('percentage-input')) data.percentage_if_same_supplier = $input.val();
                    if ($input.hasClass('domestic-cost-input')) data.domestic_cost = $input.val();
                    quickUpdate(id, data, $input);
                }, 500);

                $input.on('change blur', handler);
            });

            // Checkbox handler
            $('.same-supplier-checkbox').on('change', function() {
                let id = $(this).closest('tr').data('id');
                quickUpdate(id, {
                    same_supplier_selection: $(this).is(':checked') ? 1 : 0
                }, this);
            });
        </script>
    @endpush
