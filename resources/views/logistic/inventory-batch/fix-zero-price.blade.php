@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div>
                <h2 class="mb-0 fw-semibold" style="font-size:1.4rem;">
                    <i class="bi bi-tools text-warning me-2"></i>Fix Zero-Price Batches
                </h2>
                <small class="text-muted">Perbaiki batch inventory yang terlanjur masuk dengan harga = 0</small>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <a href="{{ route('inventory-batch.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Batches
                </a>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info border-0 mb-4">
            <div class="d-flex gap-3">
                <div><i class="bi bi-info-circle-fill fs-4"></i></div>
                <div>
                    <strong>Cara kerja fix ini:</strong><br>
                    Batches di bawah ini memiliki <code>unit_price = 0</code>.
                    Costing project menggunakan <strong>weighted average</strong> dari semua active batches,
                    sehingga memperbaiki harga di sini akan <strong>otomatis memperbaiki semua perhitungan costing</strong>
                    tanpa perlu reversal transaksi.<br>
                    Setiap perubahan dicatat di <strong>audit log</strong> (batch notes + who/when).
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="fs-2 fw-bold text-danger">{{ $batches->count() }}</div>
                    <div class="text-muted small">Batches dengan Price = 0</div>
                </div>
            </div>
            <div class="col-sm-4 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="fs-2 fw-bold text-warning">{{ $batches->where('qty_remaining', '>', 0)->count() }}</div>
                    <div class="text-muted small">Masih Ada Stok (Active)</div>
                </div>
            </div>
            <div class="col-sm-4 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="fs-2 fw-bold text-secondary">{{ $batches->where('qty_remaining', '<=', 0)->count() }}</div>
                    <div class="text-muted small">Sudah Habis (Depleted)</div>
                </div>
            </div>
        </div>

        @if ($batches->isEmpty())
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Tidak ada batch dengan price = 0.</strong> Semua batch sudah memiliki harga yang valid.
            </div>
        @else
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Batch dengan Unit Price = 0</strong>
                    <span class="badge bg-dark ms-auto" id="visible-count">{{ $batches->count() }} item</span>
                </div>
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-5">
                            <label class="form-label small mb-1">Cari Material / Batch</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="fzp-search" class="form-control"
                                    placeholder="Nama material atau batch number...">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label small mb-1">Status Stok</label>
                            <select id="fzp-filter-status" class="form-select form-select-sm">
                                <option value="all">Semua</option>
                                <option value="active">Active (ada stok)</option>
                                <option value="depleted">Depleted (habis)</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label small mb-1">Kategori</label>
                            <select id="fzp-filter-category" class="form-select form-select-sm">
                                <option value="all">Semua Kategori</option>
                                @foreach ($batches->pluck('inventory.category.name')->filter()->unique()->sort() as $cat)
                                    <option value="{{ strtolower($cat) }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <button type="button" id="fzp-reset" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Batch Number</th>
                                    <th>Material</th>
                                    <th>Category</th>
                                    <th>Qty</th>
                                    <th>Qty Remaining</th>
                                    <th>Status</th>
                                    <th>Used by GO</th>
                                    <th>Source</th>
                                    <th>Received</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($batches as $i => $batch)
                                    @php
                                        $usage = $usageCounts[$batch->id] ?? null;
                                        $isDepleted = $batch->qty_remaining <= 0;
                                    @endphp
                                    <tr id="row-{{ $batch->id }}"
                                        data-material="{{ strtolower($batch->inventory->name ?? '') }}"
                                        data-batch="{{ strtolower($batch->batch_number) }}"
                                        data-category="{{ strtolower($batch->inventory->category->name ?? '') }}"
                                        data-qty-remaining="{{ $batch->qty_remaining }}">
                                        <td class="text-muted small">{{ $i + 1 }}</td>
                                        <td>
                                            <code class="small">{{ $batch->batch_number }}</code>
                                        </td>
                                        <td>
                                            <div class="fw-semibold" style="font-size:0.85rem;">
                                                {{ $batch->inventory->name ?? 'Unknown' }}
                                            </div>
                                            <small class="text-muted">{{ $batch->inventory->unit ?? '' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary small">
                                                {{ $batch->inventory->category->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="small">{{ number_format($batch->qty, 2) }}</td>
                                        <td>
                                            @if ($isDepleted)
                                                <span class="badge bg-secondary">0 (Depleted)</span>
                                            @else
                                                <span class="text-success fw-semibold small">
                                                    {{ number_format($batch->qty_remaining, 2) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isDepleted)
                                                <span class="badge bg-secondary">Depleted</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Active</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($usage)
                                                <span class="badge bg-danger">
                                                    {{ number_format($usage->total_used, 2) }} ({{ $usage->usage_count }}x
                                                    GO)
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $sourceMap = [
                                                    'initial_stock' => ['bg-info text-dark', 'Initial Stock'],
                                                    'goods_in' => ['bg-primary text-white', 'Goods In'],
                                                    'manual' => ['bg-secondary text-white', 'Manual'],
                                                    'lark' => ['bg-dark text-white', 'Lark'],
                                                ];
                                                [$cls, $lbl] = $sourceMap[$batch->source_type] ?? [
                                                    'bg-secondary text-white',
                                                    ucfirst($batch->source_type ?? '-'),
                                                ];
                                            @endphp
                                            <span class="badge {{ $cls }} small">{{ $lbl }}</span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $batch->received_date ? $batch->received_date->format('d M Y') : '-' }}
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning btn-fix-price"
                                                data-batch-id="{{ $batch->id }}"
                                                data-batch-number="{{ $batch->batch_number }}"
                                                data-material="{{ $batch->inventory->name ?? 'Unknown' }}"
                                                data-qty="{{ number_format($batch->qty, 2) }}"
                                                data-qty-remaining="{{ number_format($batch->qty_remaining, 2) }}"
                                                data-currency="{{ $batch->currency->name ?? 'IDR' }}"
                                                data-currency-id="{{ $batch->currency_id ?? '' }}">
                                                <i class="bi bi-pencil-fill me-1"></i>Set Price
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Fix Price Modal -->
    <div class="modal fade" id="fixPriceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-tools me-2"></i>Set Unit Price
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <strong>Batch:</strong> <span id="modal-batch-number"></span><br>
                        <strong>Material:</strong> <span id="modal-material"></span><br>
                        <strong>Qty:</strong> <span id="modal-qty"></span> |
                        <strong>Remaining:</strong> <span id="modal-qty-remaining"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Currency <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="fix-currency-input">
                            @foreach ($currencies as $cur)
                                <option value="{{ $cur->id }}">{{ $cur->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Currency untuk harga batch ini</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Unit Price (<span id="modal-currency"></span>) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="fix-price-input" step="0.01" min="0.01"
                            placeholder="e.g. 50000">
                        <small class="text-muted">Harga satuan yang benar untuk batch ini</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Alasan Perubahan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="fix-reason-input" rows="2"
                            placeholder="e.g. Harga dari supplier X, invoice no. INV-2025-001"></textarea>
                        <small class="text-muted">Dicatat di audit notes batch</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="btn-confirm-fix">
                        <i class="bi bi-check-circle me-1"></i>Save Price
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let currentBatchId = null;

        $(document).on('click', '.btn-fix-price', function() {
            currentBatchId = $(this).data('batch-id');
            $('#modal-batch-number').text($(this).data('batch-number'));
            $('#modal-material').text($(this).data('material'));
            $('#modal-qty').text($(this).data('qty'));
            $('#modal-qty-remaining').text($(this).data('qty-remaining'));
            $('#modal-currency').text($(this).data('currency'));
            // Set currency dropdown to batch's current currency
            const batchCurrencyId = $(this).data('currency-id');
            if (batchCurrencyId) {
                $('#fix-currency-input').val(batchCurrencyId);
            }
            $('#fix-price-input').val('');
            $('#fix-reason-input').val('');
            $('#fixPriceModal').modal('show');
        });

        $('#btn-confirm-fix').on('click', function() {
            const price = parseFloat($('#fix-price-input').val());
            const reason = $('#fix-reason-input').val().trim();

            if (!price || price <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Harga tidak valid',
                    text: 'Masukkan harga > 0'
                });
                return;
            }
            if (!reason) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Alasan wajib diisi',
                    text: 'Tulis alasan perubahan harga'
                });
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

            $.ajax({
                url: `/inventory-batch/fix-zero-price/${currentBatchId}`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    unit_price: price,
                    reason: reason,
                    currency_id: $('#fix-currency-input').val(),
                },
                success: function(res) {
                    if (res.success) {
                        $('#fixPriceModal').modal('hide');
                        $(`#row-${currentBatchId}`).fadeOut(400, function() {
                            $(this).remove();
                            applyFilters(); // recount visible
                            const remaining = $('.btn-fix-price').length;
                            if (remaining === 0) {
                                location.reload();
                            }
                        });
                        Swal.fire({
                            icon: 'success',
                            title: 'Harga Disimpan!',
                            html: res.message +
                                '<br><small class="text-muted">Costing otomatis terupdate.</small>',
                            timer: 3000,
                            showConfirmButton: false,
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Gagal menyimpan.'
                    });
                },
                complete: function() {
                    btn.prop('disabled', false).html(
                        '<i class="bi bi-check-circle me-1"></i>Save Price');
                }
            });
        });

        /* ── Search & Filter ── */
        function applyFilters() {
            const q = $('#fzp-search').val().toLowerCase().trim();
            const status = $('#fzp-filter-status').val();
            const cat = $('#fzp-filter-category').val();
            let visible = 0;

            $('table tbody tr').each(function() {
                const material = ($(this).data('material') || '').toLowerCase();
                const batch = ($(this).data('batch') || '').toLowerCase();
                const rowCat = ($(this).data('category') || '').toLowerCase();
                const isActive = parseInt($(this).data('qty-remaining') || 0) > 0;

                const matchQ = !q || material.includes(q) || batch.includes(q);
                const matchStatus = status === 'all' ||
                    (status === 'active' && isActive) ||
                    (status === 'depleted' && !isActive);
                const matchCat = cat === 'all' || rowCat === cat;

                const show = matchQ && matchStatus && matchCat;
                $(this).toggle(show);
                if (show) visible++;
            });

            $('#visible-count').text(visible + ' item');
        }

        $('#fzp-search').on('input', applyFilters);
        $('#fzp-filter-status, #fzp-filter-category').on('change', applyFilters);
        $('#fzp-reset').on('click', function() {
            $('#fzp-search').val('');
            $('#fzp-filter-status').val('all');
            $('#fzp-filter-category').val('all');
            applyFilters();
        });
    </script>
@endsection
