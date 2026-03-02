@php
    $canCreatePurchase = in_array($user->role, [
        'super_admin',
        'admin_logistic',
        'admin_procurement',
        'admin',
    ]);
@endphp

<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0 py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap low-stock-header">
            <h5 class="card-title mb-0 fw-bold">
                Low Stock Items
                <span class="badge bg-danger" id="lowStockCount">{{ $veryLowStockItems->count() }}</span>
            </h5>
            <!-- Filter Controls -->
            <div class="d-flex gap-2 low-stock-filter-controls">
                <select id="lowStockCategorySelect" class="form-select form-select-sm select2" 
                        data-placeholder="All Category" style="min-width:90px;max-width:140px;">
                    <option value="all">All Category</option>
                    @foreach($veryLowStockItems->pluck('category')->filter()->unique('id')->values() as $cat)
                        @if($cat)
                            <option value="{{ \Illuminate\Support\Str::slug($cat->name) }}">
                                {{ $cat->name ?? 'Uncategorized' }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <select id="lowStockSupplierSelect" class="form-select form-select-sm select2" 
                        data-placeholder="All Supplier" style="min-width:90px;max-width:140px;">
                    <option value="all">All Supplier</option>
                    @foreach($veryLowStockItems->pluck('supplier')->filter()->unique('id')->values() as $sup)
                        @if($sup)
                            <option value="{{ \Illuminate\Support\Str::slug($sup->name) }}">
                                {{ $sup->name ?? 'Unknown' }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-primary" id="btnLowStockFilter">
                    <i class="fas fa-filter me-1"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="btnLowStockReset">
                    <i class="fas fa-redo me-1"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
        <div id="lowStockListGroup" class="list-group list-group-flush">
            @foreach($veryLowStockItems as $item)
                <div class="list-group-item border-0 py-3 low-stock-item 
                          {{ \Illuminate\Support\Str::slug($item->category->name ?? 'uncategorized') }} 
                          supplier-{{ \Illuminate\Support\Str::slug($item->supplier->name ?? 'unknown') }}">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="activity-icon bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 40px; height: 40px;">
                                <i class="fas fa-box-open"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-semibold text-dark">
                                {{ $item->name }}
                                @if($item->quantity < 2)
                                    <span class="badge bg-danger ms-1">Critical</span>
                                @elseif($item->quantity < 5)
                                    <span class="badge bg-warning text-dark ms-1">Very Low</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                <span class="me-2"><i class="fas fa-tag"></i> {{ $item->category->name ?? 'Uncategorized' }}</span>
                                <span class="me-2"><i class="fas fa-truck"></i> {{ $item->supplier->name ?? '-' }}</span>
                                <span class="me-2"><i class="fas fa-exclamation-circle"></i> Stok: {{ $item->quantity }} {{ $item->unit ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 ms-2">
                            @if($canCreatePurchase)
                                <a href="{{ route('purchase_requests.create', ['inventory_id' => $item->id, 'type' => 'restock']) }}" 
                                   class="btn btn-sm btn-outline-danger" title="Create Purchase Request">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>