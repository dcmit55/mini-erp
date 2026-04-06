@extends('layouts.app')

@section('title', 'New Stock Adjustment')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="{{ route('stock-adjustments.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <div>
                <h4 class="mb-0 fw-bold"><i class="bi bi-sliders me-2 text-warning"></i>New Stock Adjustment</h4>
                <small class="text-muted">Input penyesuaian stok manual</small>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>Terjadi kesalahan:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form action="{{ route('stock-adjustments.store') }}" method="POST" id="adjustmentForm">
                            @csrf

                            {{-- Adjustment Type: only 2 types shown --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Tipe Adjustment <span
                                        class="text-danger">*</span></label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type" id="typeInitial"
                                            value="initial_stock"
                                            {{ old('type', 'adjustment') === 'initial_stock' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeInitial">
                                            <span class="badge bg-info">Initial Stock</span>
                                            <small class="text-muted d-block" style="font-size:.7rem;">Add Old Stock
                                                & Create new batch number </small>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type" id="typeAdjustment"
                                            value="adjustment"
                                            {{ in_array(old('type', 'adjustment'), ['adjustment', 'correction']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeAdjustment">
                                            <span class="badge bg-warning text-dark">Adjustment</span>
                                            <small class="text-muted d-block" style="font-size:.7rem;">Koreksi qty &amp;
                                                harga batch</small>
                                        </label>
                                    </div>
                                </div>
                                @error('type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Project (optional) --}}
                            <div class="mb-3">
                                <label for="project_id" class="form-label fw-semibold">Project <span class="text-muted fw-normal">(Opsional)</span></label>
                                <select name="project_id" id="project_id"
                                    class="form-select select2-project @error('project_id') is-invalid @enderror">
                                    <option value="">— Pilih Project —</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}"
                                            {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Material --}}
                            <div class="mb-3">
                                <label for="inventory_id" class="form-label fw-semibold">Material <span
                                        class="text-danger">*</span></label>
                                <select name="inventory_id" id="inventory_id"
                                    class="form-select @error('inventory_id') is-invalid @enderror">
                                    <option value="">— Pilih Material —</option>
                                    @foreach ($inventories as $inv)
                                        <option value="{{ $inv->id }}"
                                            {{ old('inventory_id') == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->name }}{{ $inv->material_code ? ' (' . $inv->material_code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('inventory_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Batch (hidden for initial_stock) --}}
                            <div class="mb-3" id="batchSection">
                                <label for="batch_id" class="form-label fw-semibold">
                                    Batch <span class="text-danger">*</span>
                                </label>
                                <select name="batch_id" id="batch_id"
                                    class="form-select @error('batch_id') is-invalid @enderror">
                                    <option value="">— Pilih Batch —</option>
                                </select>
                                <div class="form-text text-muted" id="batchHint">Pilih material terlebih dahulu.</div>
                                @error('batch_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Qty --}}
                            <div class="mb-3">
                                <label for="qty" class="form-label fw-semibold">
                                    Quantity <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="qty" id="qty"
                                        class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty') }}"
                                        step="0.0001" placeholder="">
                                    <span class="input-group-text text-muted" id="unitLabel">pcs</span>
                                </div>
                                <div class="form-text text-muted" id="qtyHint">Positif = tambah, Negatif = kurangi stok.
                                </div>
                                @error('qty')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Unit Price — required for initial_stock, optional for adjustment --}}
                            <div class="mb-3" id="priceSection">
                                <label for="price" class="form-label fw-semibold">
                                    Unit Price
                                    <span id="priceRequiredStar" class="text-danger"
                                        @if (old('type', 'adjustment') !== 'initial_stock') style="display:none" @endif>*</span>

                                </label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted" id="currencyLabel">IDR</span>
                                    <input type="number" name="price" id="price"
                                        class="form-control @error('price') is-invalid @enderror"
                                        value="{{ old('price') }}" step="0.0001" min="0" placeholder="0">
                                </div>
                                <div class="form-text text-muted" id="priceHint">
                                    Isi jika ingin mengubah harga per unit batch ini (revaluasi harga).
                                </div>
                                @error('price')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Reason --}}
                            <div class="mb-4">
                                <label for="reason" class="form-label fw-semibold">Alasan / Keterangan</label>
                                <textarea name="reason" id="reason" rows="3" class="form-control @error('reason') is-invalid @enderror"
                                    placeholder="Contoh: stock opname, koreksi input salah, initial stock gudang baru...">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="bi bi-floppy-fill me-1"></i>Simpan Adjustment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ── Pre-fill from "Adjust Again" URL params ───────────────────────
            var urlParams = new URLSearchParams(window.location.search);
            var prefillInv = urlParams.get('inventory_id');
            var prefillBatch = urlParams.get('batch_id');
            var prefillType = urlParams.get('type');

            // Map legacy 'correction' → 'adjustment' since correction is removed from UI
            @if (!old('type'))
                if (prefillType) {
                    var mappedType = (prefillType === 'correction') ? 'adjustment' : prefillType;
                    $('input[name="type"][value="' + mappedType + '"]').prop('checked', true);
                }
            @endif

            // ── Select2 init ──────────────────────────────────────────────────
            $('#inventory_id').select2({
                theme: 'bootstrap-5',
                placeholder: '— Pilih Material —',
                allowClear: true,
                width: '100%'
            }).on('select2:open', function() {
                setTimeout(() => document.querySelector('.select2-search__field')?.focus(), 100);
            });

            $('.select2-project').select2({
                theme: 'bootstrap-5',
                placeholder: '— Pilih Project —',
                allowClear: true,
                width: '100%'
            });

            $('#batch_id').select2({
                theme: 'bootstrap-5',
                placeholder: '— Pilih Batch —',
                allowClear: true,
                width: '100%'
            });

            // ── Type toggle ───────────────────────────────────────────────────
            var currentType = () => $('input[name="type"]:checked').val();

            function applyTypeUI() {
                var type = currentType();
                var $qty = $('#qty');
                var $price = $('#price');

                if (type === 'initial_stock') {
                    $('#batchSection').addClass('d-none');
                    $('#batch_id').val(null).trigger('change');
                    $('#qtyHint').html(
                        '<i class="bi bi-info-circle me-1 text-info"></i>Qty awal batch baru yang akan dibuat.');
                    $qty.attr('placeholder', 'Contoh: 100').removeAttr('min');
                    $('#priceHint').text('Harga per unit untuk batch baru ini (wajib diisi).');
                    // Price: required for initial stock
                    $price.attr('required', true).attr('min', '0.0001');
                    $('#priceRequiredStar').show();

                } else {
                    $('#batchSection').removeClass('d-none');
                    $('#qtyHint').html('Positif = tambah stok &nbsp;|&nbsp; Negatif = kurangi stok.');
                    $qty.attr('placeholder', 'Contoh: -5 atau +10').removeAttr('min');
                    $('#priceHint').text('Opsional — isi jika harga per unit batch berubah (revaluasi harga).');
                    // Price: optional for adjustment
                    $price.removeAttr('required').attr('min', '0');
                    $('#priceRequiredStar').hide();

                }
            }

            applyTypeUI();
            $('input[name="type"]').on('change', applyTypeUI);

            // ── Load batches when material changes ────────────────────────────
            function loadBatches(inventoryId, selectBatchId) {
                var $sel = $('#batch_id');
                var $hint = $('#batchHint');

                $sel.empty().append('<option value="">— Pilih Batch —</option>').trigger('change');
                $('#unitLabel').text('pcs');
                $('#currencyLabel').text('IDR');
                $('#price').val('');

                if (!inventoryId) {
                    $hint.text('Pilih material terlebih dahulu.');
                    return;
                }

                $hint.html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');

                $.ajax({
                    url: '{{ route('stock-adjustments.batches') }}',
                    data: {
                        inventory_id: inventoryId
                    },
                    success: function(res) {
                        // Update unit & currency labels from material
                        $('#unitLabel').text(res.unit || 'pcs');
                        $('#currencyLabel').text(res.currency || 'IDR');

                        var batches = res.batches || [];
                        if (batches.length === 0) {
                            $hint.text('Tidak ada batch untuk material ini.');
                            return;
                        }

                        batches.forEach(function(b) {
                            var currCode = b.currency || res.currency || 'IDR';
                            var opt = new Option(
                                b.batch_number + ' — Sisa: ' + parseFloat(b.qty_remaining)
                                .toFixed(2) +
                                ' | Harga: ' + currCode + ' ' + parseFloat(b.unit_price ||
                                    0).toLocaleString(),
                                b.id
                            );
                            // Store data on the option element for price prefill
                            $(opt).data('unit_price', b.unit_price).data('currency', b
                                .currency || res.currency);
                            $sel.append(opt);
                        });

                        $sel.trigger('change');
                        $hint.text(batches.length + ' batch tersedia.');

                        if (selectBatchId) {
                            $sel.val(selectBatchId).trigger('change');
                        }
                    },
                    error: function() {
                        $hint.text('Gagal memuat batch.');
                    }
                });
            }

            $('#inventory_id').on('change', function() {
                loadBatches($(this).val(), null);
            });

            // ── When batch selected, pre-fill price field ─────────────────────
            $('#batch_id').on('change', function() {
                var $opt = $(this).find('option:selected');
                var unitPrice = $opt.data('unit_price');
                var currency = $opt.data('currency');

                if (unitPrice !== undefined && parseFloat(unitPrice) > 0) {
                    $('#price').val(parseFloat(unitPrice));
                }
                if (currency) {
                    $('#currencyLabel').text(currency);
                }
            });

            // ── Init / repopulate ─────────────────────────────────────────────
            @if (old('inventory_id'))
                loadBatches('{{ old('inventory_id') }}', '{{ old('batch_id') }}');
            @else
                if (prefillInv) {
                    $('#inventory_id').val(prefillInv).trigger('change');
                    loadBatches(prefillInv, prefillBatch);
                }
            @endif
        });
    </script>
@endpush
