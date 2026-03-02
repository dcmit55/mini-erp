@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-4">
                    <i class="fas fa-edit gradient-icon me-2"></i>Edit Goods Movement
                </h2>

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Validation Errors:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('goods-movement.update', $goods_movement) }}" method="POST" id="movementForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- Basic Information Section -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <label class="form-label">Movement Date *</label>
                            <input type="date" name="movement_date" class="form-control" 
                                value="{{ $goods_movement->movement_date->toDateString() }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Department *</label>
                            <select name="department_id" class="form-control" required>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ $goods_movement->department_id == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Movement Type *</label>
                            <select name="movement_type" id="movementType" class="form-control" onchange="updateMovementTypeValues()" required>
                                <option value="Handcarry" {{ $goods_movement->movement_type == 'Handcarry' ? 'selected' : '' }}>Handcarry</option>
                                <option value="Courier" {{ $goods_movement->movement_type == 'Courier' ? 'selected' : '' }}>Courier</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type Value *</label>
                            <select name="movement_type_value" id="movementTypeValue" class="form-control" required>
                                <option value="{{ $goods_movement->movement_type_value }}" selected>
                                    {{ $goods_movement->movement_type_value }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Items Origin *</label>
                            <select name="origin" class="form-control" required>
                                <option value="SG" {{ $goods_movement->origin == 'SG' ? 'selected' : '' }}>Singapore</option>
                                <option value="BT" {{ $goods_movement->origin == 'BT' ? 'selected' : '' }}>Batam</option>
                                <option value="CN" {{ $goods_movement->origin == 'CN' ? 'selected' : '' }}>China</option>
                                <option value="Other" {{ $goods_movement->origin == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Destination *</label>
                            <select name="destination" class="form-control" required>
                                <option value="SG" {{ $goods_movement->destination == 'SG' ? 'selected' : '' }}>Singapore</option>
                                <option value="BT" {{ $goods_movement->destination == 'BT' ? 'selected' : '' }}>Batam</option>
                                <option value="CN" {{ $goods_movement->destination == 'CN' ? 'selected' : '' }}>China</option>
                                <option value="Other" {{ $goods_movement->destination == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- People Information -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Sender *</label>
                            <input type="text" name="sender" class="form-control" 
                                value="{{ $goods_movement->sender }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Receiver *</label>
                            <input type="text" name="receiver" class="form-control" 
                                value="{{ $goods_movement->receiver }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="Pending" {{ $goods_movement->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Received" {{ $goods_movement->status == 'Received' ? 'selected' : '' }}>Received</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" 
                                value="{{ $goods_movement->notes }}">
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-list me-2"></i>Items List</span>
                            <button type="button" class="btn btn-sm btn-light" onclick="addItemRow()">
                                <i class="fas fa-plus me-1"></i>Add Item
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Material</th>
                                            <th width="100">Quantity</th>
                                            <th width="80">Unit</th>
                                            <th width="150">Notes</th>
                                            <th width="50">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsContainer">
                                        @foreach($goods_movement->items as $index => $item)
                                            <tr>
                                                <td>
                                                    <select name="items[{{ $index }}][inventory_id]" class="form-control form-control-sm material-select" required onchange="updateUnit(this)">
                                                        <option value="">-- Select Material --</option>
                                                        @foreach($materials as $m)
                                                            <option value="{{ $m->id }}" data-unit="{{ $m->unit }}" {{ $item->inventory_id == $m->id ? 'selected' : '' }}>
                                                                {{ $m->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm" 
                                                        min="0.01" step="0.01" value="{{ $item->quantity }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="items[{{ $index }}][unit]" class="form-control form-control-sm unit-input" 
                                                        value="{{ $item->unit }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="items[{{ $index }}][notes]" class="form-control form-control-sm" 
                                                        value="{{ $item->notes }}">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('goods-movement.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Movement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    <script>
        let itemCounter = {{ $goods_movement->items->count() }};
        const materials = {!! json_encode($materials->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit])) !!};

        function updateMovementTypeValues() {
            const type = document.getElementById('movementType').value;
            const select = document.getElementById('movementTypeValue');
            const currentValue = "{{ $goods_movement->movement_type_value }}";
            
            if (!type) {
                select.innerHTML = '<option value="">-- Select Type First --</option>';
                return;
            }

            fetch(`{{ route('goods-movement.getMovementTypeValues') }}?type=${type}`)
                .then(response => response.json())
                .then(data => {
                    select.innerHTML = '';
                    
                    data.values.forEach(value => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = value;
                        if (value === currentValue) option.selected = true;
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function addItemRow(materialId = null, quantity = null, unit = null, notes = null) {
            const container = document.getElementById('itemsContainer');
            const row = document.createElement('tr');
            const rowId = itemCounter++;

            let materialOptions = '<option value="">-- Select Material --</option>';
            materials.forEach(m => {
                const selected = m.id == materialId ? 'selected' : '';
                materialOptions += `<option value="${m.id}" data-unit="${m.unit}" ${selected}>${m.name}</option>`;
            });

            row.innerHTML = `
                <td>
                    <select name="items[${rowId}][inventory_id]" class="form-control form-control-sm material-select" required onchange="updateUnit(this)">
                        ${materialOptions}
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowId}][quantity]" class="form-control form-control-sm" 
                        min="0.01" step="0.01" value="${quantity || ''}" required>
                </td>
                <td>
                    <input type="text" name="items[${rowId}][unit]" class="form-control form-control-sm unit-input" 
                        value="${unit || 'pcs'}" required>
                </td>
                <td>
                    <input type="text" name="items[${rowId}][notes]" class="form-control form-control-sm" 
                        value="${notes || ''}">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

            container.appendChild(row);
        }

        function updateUnit(select) {
            const unit = select.options[select.selectedIndex].dataset.unit;
            select.closest('tr').querySelector('.unit-input').value = unit || 'pcs';
        }

        document.getElementById('movementForm').addEventListener('submit', function(e) {
            const itemsCount = document.getElementById('itemsContainer').children.length;
            if (itemsCount === 0) {
                e.preventDefault();
                alert('Please add at least one item');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateMovementTypeValues();
        });
    </script>
@endsection