@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Goods Movement Detail</h5>
                <div class="gap-2">
                    <a href="{{ route('goods-movement.edit', $goods_movement) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <a href="{{ route('goods-movement.index') }}" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Movement Info -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <strong>Date:</strong><br>
                        {{ $goods_movement->movement_date->format('d M Y') }}
                    </div>
                    <div class="col-md-3">
                        <strong>Department:</strong><br>
                        {{ $goods_movement->department->name }}
                    </div>
                    <div class="col-md-3">
                        <strong>Origin:</strong><br>
                        <span class="badge bg-primary">{{ $goods_movement->origin }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong><br>
                        <span class="badge bg-{{ $goods_movement->status === 'Received' ? 'success' : 'warning' }}">
                            {{ $goods_movement->status }}
                        </span>
                    </div>
                </div>

                <hr>

                <!-- Movement Type Info -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <strong>Movement Type:</strong><br>
                        {{ $goods_movement->movement_type }}
                    </div>
                    <div class="col-md-3">
                        <strong>Type Value:</strong><br>
                        {{ $goods_movement->movement_type_value }}
                    </div>
                    <div class="col-md-3">
                        <strong>Destination:</strong><br>
                        <span class="badge bg-info">{{ $goods_movement->destination }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Created By:</strong><br>
                        {{ $goods_movement->creator->username ?? '-' }}
                    </div>
                </div>

                <hr>

                <!-- People Info -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <strong>Sender:</strong><br>
                        {{ $goods_movement->sender }}
                    </div>
                    <div class="col-md-4">
                        <strong>Receiver:</strong><br>
                        {{ $goods_movement->receiver }}
                    </div>
                    <div class="col-md-4">
                        <strong>Created At:</strong><br>
                        {{ $goods_movement->created_at->format('d M Y H:i') }}
                    </div>
                </div>

                @if($goods_movement->notes)
                    <div class="mb-4">
                        <strong>Notes:</strong><br>
                        {{ $goods_movement->notes }}
                    </div>
                @endif

                <hr>

                <!-- Items Table -->
                <h6 class="mb-3"><i class="fas fa-list me-2"></i>Items ({{ $goods_movement->items->count() }})</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Material</th>
                                <th width="80">Quantity</th>
                                <th width="80">Unit</th>
                                <th>Notes</th>
                                <th width="180">Transfer to Inventory Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($goods_movement->items as $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $item->material_type }}</span>
                                    </td>
                                    <td>
                                        @if($item->material_type === 'Goods Receive')
                                            {{ $item->goodsReceiveDetail?->material_name ?? '-' }}
                                        @elseif($item->material_type === 'New Material')
                                            {{ $item->new_material_name ?? '-' }}
                                        @else
                                            {{ $item->inventory?->name ?? '-' }}
                                        @endif
                                    </td>
                                    <td>{{ number_format($item->quantity, 2) }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td>{{ $item->notes ?? '-' }}</td>
                                    <td>
                                        @if($item->transferred_to_inventory)
                                            <button class="btn btn-sm btn-success" disabled>
                                                <i class="bi bi-check-circle me-1"></i>Transferred
                                            </button>
                                            <br>
                                            <small class="text-muted">
                                                {{ $item->transferred_at->format('d M Y H:i') }}
                                            </small>
                                        @else
                                            <button class="btn btn-sm btn-primary transfer-btn" 
                                                data-item-id="{{ $item->id }}"
                                                onclick="transferToInventory({{ $item->id }})">
                                                <i class="bi bi-arrow-right-circle me-1"></i>Proceed Transfer
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No items</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function transferToInventory(itemId) {
    Swal.fire({
        title: 'Transfer Confirmation',
        text: 'Transfer This Item ? ',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Transfer To Inventory',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request
            $.ajax({
                url: `/goods-movement-item/${itemId}/transfer-to-inventory`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload page untuk update tampilan
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error While Tranfering Data';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage
                    });
                }
            });
        }
    });
}
</script>
@endpush