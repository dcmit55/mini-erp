@extends('layouts.app')

@section('title', 'Stock Adjustment Detail')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="{{ route('stock-adjustments.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <div class="flex-fill">
                <h4 class="mb-0 fw-bold"><i class="bi bi-sliders me-2 text-warning"></i>Stock Adjustment
                    #{{ $stockAdjustment->id }}</h4>
                <small class="text-muted">{{ $stockAdjustment->created_at->format('d M Y H:i') }}</small>
            </div>
            @can('logistic.stock-adjustment.create')
                @php
                    $againType = $stockAdjustment->type === 'initial_stock' ? 'adjustment' : $stockAdjustment->type;
                    $againUrl =
                        route('stock-adjustments.create') .
                        '?inventory_id=' .
                        $stockAdjustment->inventory_id .
                        '&batch_id=' .
                        ($stockAdjustment->batch_id ?? '') .
                        '&type=' .
                        $againType;
                @endphp
                <a href="{{ $againUrl }}" class="btn btn-sm btn-warning">
                    <i class="bi bi-arrow-repeat me-1"></i>Adjust Again
                </a>
            @endcan
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Tipe</dt>
                            <dd class="col-sm-8">
                                <span
                                    class="badge {{ \App\Models\Logistic\StockAdjustment::typeBadgeClass($stockAdjustment->type) }}">
                                    {{ \App\Models\Logistic\StockAdjustment::typeLabel($stockAdjustment->type) }}
                                </span>
                            </dd>

                            <dt class="col-sm-4">Material</dt>
                            <dd class="col-sm-8">
                                {{ optional($stockAdjustment->inventory)->name ?? '—' }}
                                @if (optional($stockAdjustment->inventory)->material_code)
                                    <small class="text-muted">({{ $stockAdjustment->inventory->material_code }})</small>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Batch</dt>
                            <dd class="col-sm-8">{{ optional($stockAdjustment->batch)->batch_number ?? '—' }}</dd>

                            <dt class="col-sm-4">Quantity</dt>
                            <dd class="col-sm-8">
                                @if ((float) $stockAdjustment->qty > 0)
                                    <span class="text-success fw-bold">+{{ number_format($stockAdjustment->qty, 4) }}</span>
                                @else
                                    <span class="text-danger fw-bold">{{ number_format($stockAdjustment->qty, 4) }}</span>
                                @endif
                            </dd>

                            @if ($stockAdjustment->type === 'initial_stock' && !is_null($stockAdjustment->price))
                                <dt class="col-sm-4">Unit Price</dt>
                                <dd class="col-sm-8">Rp {{ number_format($stockAdjustment->price, 4) }}</dd>
                            @endif

                            @if ($stockAdjustment->batch)
                                <dt class="col-sm-4">Sisa Batch Sekarang</dt>
                                <dd class="col-sm-8">{{ number_format($stockAdjustment->batch->qty_remaining, 4) }}</dd>
                            @endif

                            <dt class="col-sm-4">Alasan</dt>
                            <dd class="col-sm-8">{{ $stockAdjustment->reason ?? '—' }}</dd>

                            <dt class="col-sm-4">Dibuat oleh</dt>
                            <dd class="col-sm-8">{{ optional($stockAdjustment->creator)->username ?? '—' }}</dd>

                            <dt class="col-sm-4">Tanggal</dt>
                            <dd class="col-sm-8">{{ $stockAdjustment->created_at->format('d M Y H:i:s') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
