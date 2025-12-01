@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-lg rounded-3 border-0">
            <div class="card-header bg-gradient-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-dolly fs-3 me-3"></i>
                    <h2 class="mb-0">Add Goods Movement</h2>
                </div>
            </div>
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle fs-4 me-3"></i>
                            <div>
                                <strong>Validation Errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('goods-movement.store') }}" method="POST" id="movementForm">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light border-0 py-3">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-info-circle me-2"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar-alt text-primary me-1"></i>Movement Date *
                                    </label>
                                    <input type="date" name="movement_date" class="form-control form-control-lg"
                                        value="{{ old('movement_date', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-building text-info me-1"></i>Department *
                                    </label>
                                    <select name="department_id" class="form-select form-select-lg" required>
                                        <option value="">-- Select Department --</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-shipping-fast text-warning me-1"></i>Movement Type *
                                    </label>
                                    <select name="movement_type" id="movementType" class="form-select form-select-lg" onchange="updateMovementTypeValues()" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="Handcarry" {{ old('movement_type') == 'Handcarry' ? 'selected' : '' }}>
                                            Handcarry
                                        </option>
                                        <option value="Courier" {{ old('movement_type') == 'Courier' ? 'selected' : '' }}>
                                            Courier
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-user-tag text-success me-1"></i>Type Value *
                                    </label>
                                    <div class="input-group">
                                        <select name="movement_type_value" id="movementTypeValue" class="form-select form-select-lg" required>
                                            <option value="">-- Select Type First --</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-info" id="detailPopupBtn"
                                            onclick="openHandcarryPopup()" style="display: none;">
                                            <i class="fas fa-calculator"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-map-marker text-danger me-1"></i>Items Origin *
                                    </label>
                                    <select name="origin" class="form-select form-select-lg" required>
                                        <option value="">-- Select --</option>
                                        <option value="SG" {{ old('origin') == 'SG' ? 'selected' : '' }}>Singapore (SG)</option>
                                        <option value="BT" {{ old('origin') == 'BT' ? 'selected' : '' }}>Batam (BT)</option>
                                        <option value="CN" {{ old('origin') == 'CN' ? 'selected' : '' }}>China (CN)</option>
                                        <option value="Other" {{ old('origin') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-location-arrow text-info me-1"></i>Destination *
                                    </label>
                                    <select name="destination" class="form-select form-select-lg" required>
                                        <option value="">-- Select --</option>
                                        <option value="SG" {{ old('destination') == 'SG' ? 'selected' : '' }}>Singapore (SG)</option>
                                        <option value="BT" {{ old('destination') == 'BT' ? 'selected' : '' }}>Batam (BT)</option>
                                        <option value="CN" {{ old('destination') == 'CN' ? 'selected' : '' }}>China (CN)</option>
                                        <option value="Other" {{ old('destination') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- People Information -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light border-0 py-3">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-users me-2"></i>People Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-user-check text-primary me-1"></i>Sender *
                                    </label>
                                    <input type="text" name="sender" class="form-control form-control-lg"
                                        placeholder="e.g., John Doe" value="{{ old('sender') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-user-clock text-info me-1"></i>Receiver *
                                    </label>
                                    <input type="text" name="receiver" class="form-control form-control-lg"
                                        placeholder="e.g., Jane Smith" value="{{ old('receiver') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-sticky-note text-warning me-1"></i>Notes
                                    </label>
                                    <input type="text" name="notes" class="form-control form-control-lg"
                                        placeholder="Optional notes" value="{{ old('notes') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp Parser Section - COLLAPSIBLE -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-gradient-success text-white py-3" style="cursor: pointer;" onclick="toggleWhatsAppParser()">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">
                                    <i class="fab fa-whatsapp fs-5 me-2"></i>Parse from WhatsApp (Optional)
                                </span>
                                <i class="fas fa-chevron-down transition-all" id="parserToggleIcon"></i>
                            </div>
                        </div>
                        <div class="card-body" id="whatsappParserBody" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Paste WhatsApp Message</label>
                                <textarea id="whatsappMessage" class="form-control" rows="5"
                                    placeholder="Paste one item per line&#10;Example:&#10;Johnny Walker Hat * 3 * pcs * Important&#10;Airbrush DCM * 2 * box"></textarea>
                            </div>

                            <!-- Format Guide -->
                            <div class="alert alert-info border-0 shadow-sm mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle fs-4 me-3 text-info"></i>
                                    <div>
                                        <strong class="d-block mb-2">📋 Supported Formats:</strong>
                                        <div class="small">
                                            <code>Material Name * Quantity * Unit * Notes</code><br>
                                            <code>Material Name * Quantity</code><br>
                                            <code>Material Name - Quantity - Unit</code>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-success btn-lg shadow" id="parseBtn" onclick="parseWhatsApp()">
                                <i class="fas fa-magic me-2"></i>Parse Items
                            </button>
                            <small class="text-muted d-block mt-2">
                                💡 Tip: Paste one item per line for best results
                            </small>
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
                                            <th width="150">Material Type *</th>
                                            <th width="180">Reference</th>
                                            <th width="150">GDS Item</th>
                                            <th>Material</th>
                                            <th width="100">Quantity</th>
                                            <th width="80">Unit</th>
                                            <th width="150">Notes</th>
                                            <th width="50">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsContainer">
                                        <!-- Items akan ditambahkan di sini -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-3 justify-content-end">
                        <a href="{{ route('goods-movement.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                            <i class="fas fa-save me-2"></i>Save Movement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Handcarry Details Modal - MODERN DESIGN -->
    <div class="modal fade" id="handcarryModal" tabindex="-1" aria-labelledby="handcarryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="handcarryForm" class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-gradient-primary text-white border-0 py-3">
            <h5 class="modal-title fw-bold" id="handcarryModalLabel">
                <i class="fas fa-calculator me-2"></i>Handcarry Cost Calculator
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
            <!-- Ferry Ticket Depart -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="fw-semibold mb-0">
                                <i class="fas fa-ship text-primary me-2"></i>Ferry Ticket Depart
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" min="0" class="form-control form-control-lg" id="ferry_depart" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-lg" id="currency_depart">
                                <option value="IDR">IDR</option>
                                <option value="SGD">SGD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ferry Ticket Return -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="fw-semibold mb-0">
                                <i class="fas fa-ship text-info me-2"></i>Ferry Ticket Return
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" min="0" class="form-control form-control-lg" id="ferry_return" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-lg" id="currency_return">
                                <option value="IDR">IDR</option>
                                <option value="SGD">SGD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Baggage Fees -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="fw-semibold mb-0">
                                <i class="fas fa-suitcase text-warning me-2"></i>Baggage Fees
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" min="0" class="form-control form-control-lg" id="baggage" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-lg" id="currency_baggage">
                                <option value="IDR">IDR</option>
                                <option value="SGD">SGD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Porter Fees -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="fw-semibold mb-0">
                                <i class="fas fa-hand-holding-usd text-success me-2"></i>Porter Fees
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" min="0" class="form-control form-control-lg" id="porter" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-lg" id="currency_porter">
                                <option value="IDR">IDR</option>
                                <option value="SGD">SGD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Handcarry Fee -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="fw-semibold mb-0">
                                <i class="fas fa-user-tag text-danger me-2"></i>Handcarry Fee
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" min="0" class="form-control form-control-lg" id="handcarry_fee" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-lg" id="currency_handcarry">
                                <option value="IDR">IDR</option>
                                <option value="SGD">SGD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Extra Cost -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="fw-semibold mb-0">
                                <i class="fas fa-plus-circle text-secondary me-2"></i>Extra Cost
                            </label>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" min="0" class="form-control form-control-lg" id="extra_cost" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-lg" id="currency_extra">
                                <option value="IDR">IDR</option>
                                <option value="SGD">SGD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Cost -->
            <div class="card border-0 shadow-lg bg-gradient-success text-white">
                <div class="card-body">
                    <label class="fw-bold fs-5 mb-2">
                        <i class="fas fa-money-bill-wave me-2"></i>Overall Cost (IDR)
                    </label>
                    <input type="text" class="form-control form-control-lg fw-bold text-center fs-4 bg-white"
                        id="overall_cost" readonly placeholder="Rp 0.00">
                </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                <i class="fas fa-times me-2"></i>Close
            </button>
            <button type="button" class="btn btn-primary btn-lg px-5 shadow" onclick="calculateHandcarryTotal()">
                <i class="fas fa-calculator me-2"></i>Calculate
            </button>
          </div>
        </form>
      </div>
    </div>


  <!-- ✅ TAMBAHAN: Courier Details Modal - MODERN DESIGN -->
    <div class="modal fade" id="courierModal" tabindex="-1" aria-labelledby="courierModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="courierForm" class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-gradient-primary text-white border-0 py-3">
            <h5 class="modal-title fw-bold" id="courierModalLabel">
                <i class="fas fa-calculator me-2"></i>LCL Cost Calculator
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">

            <!-- Row 1: Invoice No -->
            <div class="row mb-3">
                <div class="col-12">
                    <label class="fw-semibold mb-2">
                        <i class="fas fa-file-invoice text-primary me-2"></i>Invoice No
                    </label>
                    <input type="text" class="form-control form-control-lg" id="invoice_no" placeholder="Enter invoice number">
                </div>
            </div>

            <!-- Row 2: Description -->
            <div class="row mb-3">
                <div class="col-12">
                    <label class="fw-semibold mb-2">
                        <i class="fas fa-align-left text-info me-2"></i>Description
                    </label>
                    <input type="text" class="form-control form-control-lg" id="description" placeholder="Enter description">
                </div>
            </div>

            <!-- Row 3: GST -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="fw-semibold mb-2">
                        <i class="fas fa-percent text-warning me-2"></i>GST
                    </label>
                    <input type="number" step="any" min="0" class="form-control form-control-lg" id="gst" placeholder="0.00">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">&nbsp;</label>
                    <select class="form-select form-select-lg" id="currency_gst">
                        <option value="IDR">IDR</option>
                        <option value="SGD">SGD</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>

            <!-- Row 4: ZR Charges -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="fw-semibold mb-2">
                        <i class="fas fa-plus-circle text-danger me-2"></i>ZR Charges
                    </label>
                    <input type="number" step="any" min="0" class="form-control form-control-lg" id="zr_charges" placeholder="0.00">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">&nbsp;</label>
                    <select class="form-select form-select-lg" id="currency_zr">
                        <option value="IDR">IDR</option>
                        <option value="SGD">SGD</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>

            <!-- Row 5: Extra Charges -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="fw-semibold mb-2">
                        <i class="fas fa-plus-circle text-secondary me-2"></i>Extra Charges
                    </label>
                    <input type="number" step="any" min="0" class="form-control form-control-lg" id="extra_charges" placeholder="0.00">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">&nbsp;</label>
                    <select class="form-select form-select-lg" id="currency_extra">
                        <option value="IDR">IDR</option>
                        <option value="SGD">SGD</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>

            <!-- Overall Cost -->
            <div class="card border-0 shadow-lg bg-gradient-success text-white mt-4">
                <div class="card-body">
                    <label class="fw-bold fs-5 mb-2">
                        <i class="fas fa-money-bill-wave me-2"></i>Overall Cost (IDR)
                    </label>
                    <input type="text" class="form-control form-control-lg fw-bold text-center fs-4 bg-white"
                        id="overall_cost_courier_lcl" readonly placeholder="Rp 0.00">
                </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                <i class="fas fa-times me-2"></i>Close
            </button>
            <button type="button" class="btn btn-primary btn-lg px-5 shadow" onclick="calculateCourierLCLTotal()">
                <i class="fas fa-calculator me-2"></i>Calculate
            </button>
          </div>
        </form>
      </div>
    </div>
    <!-- ✅ AKHIR TAMBAHAN: Courier Details Modal -->


    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        #parserToggleIcon {
            transition: transform 0.3s ease;
        }

        #parserToggleIcon.rotated {
            transform: rotate(180deg);
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .form-control-lg, .form-select-lg {
            border-radius: 0.5rem;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control-lg:focus, .form-select-lg:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .card {
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .btn {
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .modal-content {
            border-radius: 1rem;
            overflow: hidden;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>

    <script>
    let itemCounter = 0;
    const materials = {!! json_encode($materials->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit])) !!};
    let projectsData = [];
    let goodsReceivesData = [];

    // Fetch data on page load
    document.addEventListener('DOMContentLoaded', function() {
        fetchProjects();
        fetchGoodsReceives();

        if (document.getElementById('itemsContainer').children.length === 0) {
            addItemRow();
        }

        const oldMovementType = "{{ old('movement_type') }}";
        if (oldMovementType) {
            updateMovementTypeValues();
        }

        document.getElementById('movementForm').addEventListener('submit', function(e) {
            const itemsCount = document.getElementById('itemsContainer').children.length;
            if (itemsCount === 0) {
                e.preventDefault();
                alert('Please add at least one item');
            }
        });
    });

    async function fetchProjects() {
        try {
            const response = await fetch('{{ route("goods-movement.getProjects") }}');
            const data = await response.json();
            projectsData = data.projects;
        } catch (error) {
            console.error('Error fetching projects:', error);
        }
    }

    async function fetchGoodsReceives() {
        try {
            const response = await fetch('{{ route("goods-movement.getGoodsReceives") }}');
            const data = await response.json();
            goodsReceivesData = data.goodsReceives;
        } catch (error) {
            console.error('Error fetching goods receives:', error);
        }
    }

    function toggleWhatsAppParser() {
        const body = document.getElementById('whatsappParserBody');
        const icon = document.getElementById('parserToggleIcon');

        if (body.style.display === 'none') {
            body.style.display = 'block';
            icon.classList.add('rotated');
        } else {
            body.style.display = 'none';
            icon.classList.remove('rotated');
        }
    }

   function updateMovementTypeValues() {
        const type = document.getElementById('movementType').value;
        const select = document.getElementById('movementTypeValue');
        const handcarryBtn = document.getElementById('detailPopupBtn');

        if (!type) {
            select.innerHTML = '<option value="">-- Select Type First --</option>';
            handcarryBtn.style.display = 'none';
            return;
        }

        // ✅ PERBAIKAN: Tampilkan button sesuai type
        if (type === 'Handcarry') {
            handcarryBtn.style.display = 'block';
            handcarryBtn.onclick = function() { openHandcarryPopup(); };
            handcarryBtn.title = 'Calculate Handcarry Cost';
            handcarryBtn.innerHTML = '<i class="fas fa-calculator"></i>';
        } else if (type === 'Courier') {
            handcarryBtn.style.display = 'block';
            handcarryBtn.onclick = function() { openCourierPopup(); };
            handcarryBtn.title = 'Calculate Courier Cost';
            handcarryBtn.innerHTML = '<i class="fas fa-calculator"></i>';
        } else {
            handcarryBtn.style.display = 'none';
        }

        fetch(`{{ route('goods-movement.getMovementTypeValues') }}?type=${type}`)
            .then(response => response.json())
            .then(data => {
                select.innerHTML = '<option value="">-- Select Value --</option>';

                data.values.forEach(value => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                select.innerHTML = '<option value="">Error loading values</option>';
            });
    }
    function openCourierPopup() {
        var modal = new bootstrap.Modal(document.getElementById('courierModal'));
        modal.show();
    }



 function addItemRow() {
        const container = document.getElementById('itemsContainer');
        const row = document.createElement('tr');
        const rowId = itemCounter++;

        row.innerHTML = `
            <td>
                <select name="items[${rowId}][material_type]" class="form-control form-control-sm material-type-select"
                    onchange="handleMaterialTypeChange(this, ${rowId})" required>
                    <option value="">-- Select Type --</option>
                    <option value="Project">Project</option>
                    <option value="Goods Receive">Goods Receive</option>
                    <option value="Restock">Restock</option>
                    <option value="New Material">New Material</option>
                </select>
            </td>
            <td>
                <select name="items[${rowId}][reference_id]" class="form-control form-control-sm reference-select"
                    style="display:none;" disabled>
                    <option value="">-- Select First --</option>
                </select>
            </td>
            <td>
                <select name="items[${rowId}][goods_receive_detail_id]" class="form-control form-control-sm gds-receive-select"
                    style="display:none;" onchange="handleGoodsReceiveItemChange(this, ${rowId})" disabled>
                    <option value="">-- Select Item --</option>
                </select>
            </td>
            <td>
                <input type="text" name="items[${rowId}][material_display]" class="form-control form-control-sm material-display"
                    placeholder="Material Name" readonly style="display:none;">
                <select name="items[${rowId}][inventory_id]" class="form-control form-control-sm material-select"
                    style="display:none;" onchange="updateUnit(this)" disabled>
                    <option value="">-- Select Material --</option>
                </select>
                <input type="text" name="items[${rowId}][new_material_name]" class="form-control form-control-sm new-material-input"
                    placeholder="Enter material name" style="display:none;" disabled>
            </td>
            <td>
                <input type="number" name="items[${rowId}][quantity]" class="form-control form-control-sm quantity-input"
                    min="0.01" step="0.01" placeholder="0.00" disabled required>
            </td>
            <td>
                <input type="text" name="items[${rowId}][unit]" class="form-control form-control-sm unit-input"
                    value="pcs" disabled>
            </td>
            <td>
                <input type="text" name="items[${rowId}][notes]" class="form-control form-control-sm notes-input"
                    placeholder="Optional" disabled>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        container.appendChild(row);
    }

    // ✅ TAMBAH: Debug form submission
        document.getElementById('movementForm').addEventListener('submit', function(e) {
        const itemsCount = document.getElementById('itemsContainer').children.length;

        if (itemsCount === 0) {
            e.preventDefault();
            alert('❌ Tambahkan minimal 1 item');
            return;
        }

        const rows = document.querySelectorAll('#itemsContainer tr');
        let hasError = false;
        let errorMessages = [];

        rows.forEach((row, index) => {
            const materialType = row.querySelector('[name*="material_type"]')?.value;
            const projectId = row.querySelector('[name*="project_id"]')?.value;
            const goodsReceiveId = row.querySelector('[name*="goods_receive_id"]')?.value;
            const goodsReceiveDetailId = row.querySelector('[name*="goods_receive_detail_id"]')?.value;
            const inventoryId = row.querySelector('[name*="inventory_id"]')?.value;
            const newMaterialName = row.querySelector('[name*="new_material_name"]')?.value;
            const quantity = row.querySelector('[name*="quantity"]')?.value;

            // Validasi
            if (!materialType) {
                errorMessages.push(`Row ${index + 1}: Material Type harus dipilih`);
                hasError = true;
            }

            if (materialType === 'Project' && !projectId) {
                errorMessages.push(`Row ${index + 1}: Project harus dipilih`);
                hasError = true;
            }

            // ✅ PERBAIKAN: Validasi untuk Goods Receive lebih ketat
            if (materialType === 'Goods Receive') {
                if (!goodsReceiveId) {
                    errorMessages.push(`Row ${index + 1}: Goods Receive harus dipilih`);
                    hasError = true;
                }
                if (!goodsReceiveDetailId) {
                    errorMessages.push(`Row ${index + 1}: Goods Receive Item harus dipilih`);
                    hasError = true;
                }
            }

            if (materialType === 'Restock' && !inventoryId) {
                errorMessages.push(`Row ${index + 1}: Material harus dipilih`);
                hasError = true;
            }

            if (materialType === 'New Material' && !newMaterialName) {
                errorMessages.push(`Row ${index + 1}: Nama material baru harus diisi`);
                hasError = true;
            }

            if (!quantity || parseFloat(quantity) <= 0) {
                errorMessages.push(`Row ${index + 1}: Quantity harus lebih dari 0`);
                hasError = true;
            }
        });

        if (hasError) {
            e.preventDefault();
            console.error('❌ Validation errors:', errorMessages);
            alert('❌ Error:\n\n' + errorMessages.join('\n'));
            return;
        }

        // Log form data
        const formData = new FormData(this);
    });

    function handleMaterialTypeChange(select, rowId) {
    const row = select.closest('tr');
    const type = select.value;

    const referenceSelect = row.querySelector('.reference-select');
    const gdsReceiveSelect = row.querySelector('.gds-receive-select');
    const materialDisplay = row.querySelector('.material-display');
    const materialSelect = row.querySelector('.material-select');
    const newMaterialInput = row.querySelector('.new-material-input');
    const quantityInput = row.querySelector('.quantity-input');
    const unitInput = row.querySelector('.unit-input');
    const notesInput = row.querySelector('.notes-input');

        // RESET SEMUA FIELD
    referenceSelect.removeAttribute('name');
    referenceSelect.style.display = 'none';
    referenceSelect.disabled = true;
    referenceSelect.innerHTML = '<option value="">-- Select First --</option>';

    gdsReceiveSelect.removeAttribute('name');
    gdsReceiveSelect.style.display = 'none';
    gdsReceiveSelect.disabled = true;
    gdsReceiveSelect.innerHTML = '<option value="">-- Select Item --</option>';

    materialDisplay.style.display = 'none';
    materialDisplay.value = '';

    materialSelect.removeAttribute('name');
    materialSelect.style.display = 'none';
    materialSelect.disabled = true;
    materialSelect.innerHTML = '<option value="">-- Select Material --</option>';

    newMaterialInput.removeAttribute('name');
    newMaterialInput.style.display = 'none';
    newMaterialInput.disabled = true;
    newMaterialInput.value = '';

    quantityInput.disabled = true;
    quantityInput.value = '';
    quantityInput.readOnly = false;

    unitInput.disabled = true;
    unitInput.value = 'pcs';
    unitInput.readOnly = false;

    notesInput.disabled = true;
    notesInput.value = '';

    if (!type) return;

        switch(type) {
        case 'Project':
            // ✅ Set attribute name dengan benar
            referenceSelect.setAttribute('name', `items[${rowId}][project_id]`);
            referenceSelect.style.display = 'block';
            referenceSelect.disabled = false;

            projectsData.forEach(project => {
                const option = document.createElement('option');
                option.value = project.id;
                option.textContent = project.name;
                referenceSelect.appendChild(option);
            });

            quantityInput.disabled = false;
            unitInput.disabled = false;
            notesInput.disabled = false;
            break;

             case 'Goods Receive':
            // ✅ Set BOTH attribute names untuk Goods Receive
            referenceSelect.setAttribute('name', `items[${rowId}][goods_receive_id]`);
            referenceSelect.style.display = 'block';
            referenceSelect.disabled = false;

            goodsReceivesData.forEach(gr => {
                const option = document.createElement('option');
                option.value = gr.id;
                const date = new Date(gr.created_at).toLocaleDateString();
                option.textContent = `${gr.international_waybill_no} - ${date}`;
                referenceSelect.appendChild(option);
            });

                referenceSelect.onchange = function() {
                loadGoodsReceiveItems(this.value, rowId);
            };

            // ✅ Show GDS Receive detail select
            gdsReceiveSelect.setAttribute('name', `items[${rowId}][goods_receive_detail_id]`);
            gdsReceiveSelect.style.display = 'block';
            gdsReceiveSelect.disabled = false;

            // ✅ PENTING: Jangan disable unit untuk Goods Receive
            unitInput.disabled = false;
            notesInput.disabled = false;
            break;

            case 'Restock':
            materialSelect.setAttribute('name', `items[${rowId}][inventory_id]`);
            materialSelect.style.display = 'block';
            materialSelect.disabled = false;
            materials.forEach(m => {
                const option = document.createElement('option');
                option.value = m.id;
                option.textContent = m.name;
                option.dataset.unit = m.unit;
                materialSelect.appendChild(option);
            });

            quantityInput.disabled = false;
            unitInput.disabled = false;
            notesInput.disabled = false;
            break;

        case 'New Material':
            newMaterialInput.setAttribute('name', `items[${rowId}][new_material_name]`);
            newMaterialInput.style.display = 'block';
            newMaterialInput.disabled = false;

            quantityInput.disabled = false;
            unitInput.disabled = false;
            notesInput.disabled = false;
            break;
    }
}

    async function loadGoodsReceiveItems(goodsReceiveId, rowId) {
        const row = document.querySelector(`tr:has(select[name="items[${rowId}][material_type]"])`);
        const gdsReceiveSelect = row.querySelector('.gds-receive-select');

        if (!goodsReceiveId) {
            gdsReceiveSelect.innerHTML = '<option value="">-- Select Item --</option>';
            gdsReceiveSelect.disabled = true;
            return;
        }

        try {
            const response = await fetch(`{{ route('goods-movement.getGoodsReceiveItems') }}?goods_receive_id=${goodsReceiveId}`);
            const data = await response.json();

            gdsReceiveSelect.innerHTML = '<option value="">-- Select Item --</option>';
            gdsReceiveSelect.disabled = false;

            data.items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.material_name} (${item.received_qty})`;
                option.dataset.materialName = item.material_name;
                option.dataset.quantity = item.received_qty;
                option.dataset.unit = 'pcs';
                option.dataset.projectName = item.project_name || '';
                gdsReceiveSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading goods receive items:', error);
        }
    }

   function handleGoodsReceiveItemChange(select, rowId) {
        const row = select.closest('tr');
        const selectedOption = select.options[select.selectedIndex];
        const materialDisplay = row.querySelector('.material-display');
        const quantityInput = row.querySelector('.quantity-input');
        const unitInput = row.querySelector('.unit-input');

        if (!select.value) return;

        // ✅ Tampilkan material name
        materialDisplay.style.display = 'block';
        materialDisplay.value = selectedOption.dataset.materialName;

        // ✅ PERBAIKAN: Set value tapi JANGAN disable!
        quantityInput.value = selectedOption.dataset.quantity;
        quantityInput.disabled = false;  // ✅ JANGAN DISABLE
        quantityInput.readOnly = true;   // ✅ READ ONLY SAJA

        unitInput.value = selectedOption.dataset.unit || 'pcs';
        unitInput.disabled = false;  // ✅ JANGAN DISABLE
        unitInput.readOnly = true;   // ✅ READ ONLY SAJA
    }

    function updateUnit(select) {
        const unit = select.options[select.selectedIndex].dataset.unit;
        select.closest('tr').querySelector('.unit-input').value = unit || 'pcs';
    }

    async function parseWhatsApp() {
        const message = document.getElementById('whatsappMessage').value;

        if (!message.trim()) {
            alert('Please paste a WhatsApp message');
            return;
        }

        const btn = document.getElementById('parseBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Parsing...';

        try {
            const response = await fetch('{{ route("goods-movement.parseWhatsApp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();

            if (data.items.length > 0) {
                document.getElementById('itemsContainer').innerHTML = '';
                itemCounter = 0;

                data.items.forEach(item => {
                    addItemRow();
                    const lastRow = document.querySelector('#itemsContainer tr:last-child');
                    lastRow.querySelector('.material-type-select').value = 'Restock';
                    handleMaterialTypeChange(lastRow.querySelector('.material-type-select'), itemCounter - 1);
                    lastRow.querySelector('.material-select').value = item.inventory_id;
                    lastRow.querySelector('.quantity-input').value = item.quantity;
                    lastRow.querySelector('.unit-input').value = item.unit;
                    lastRow.querySelector('.notes-input').value = item.notes || '';
                });

                document.getElementById('whatsappMessage').value = '';
                showParseNotification(`Successfully parsed ${data.count} item(s)!`, 'success');
            } else {
                showParseNotification('No items found. Please check the format and item names.', 'warning');
            }
        } catch (error) {
            console.error(error);
            showParseNotification('Error parsing message. Please try again.', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    function openHandcarryPopup() {
        var modal = new bootstrap.Modal(document.getElementById('handcarryModal'));
        modal.show();
    }

    const kurs = { IDR: 1, SGD: 11500, USD: 16000 };

    function calculateHandcarryTotal() {
        let total = 0;
        total += parseFloat(document.getElementById('ferry_depart').value || 0) * kurs[document.getElementById('currency_depart').value];
        total += parseFloat(document.getElementById('ferry_return').value || 0) * kurs[document.getElementById('currency_return').value];
        total += parseFloat(document.getElementById('baggage').value || 0) * kurs[document.getElementById('currency_baggage').value];
        total += parseFloat(document.getElementById('porter').value || 0) * kurs[document.getElementById('currency_porter').value];
        total += parseFloat(document.getElementById('handcarry_fee').value || 0) * kurs[document.getElementById('currency_handcarry').value];
        total += parseFloat(document.getElementById('extra_cost').value || 0) * kurs[document.getElementById('currency_extra').value];

        document.getElementById('overall_cost').value = 'Rp ' + total.toLocaleString('id-ID', {minimumFractionDigits: 2});
    }

         function calculateCourierLCLTotal() {
        let total = 0;
        total += parseFloat(document.getElementById('gst').value || 0) * kurs[document.getElementById('currency_gst').value];
        total += parseFloat(document.getElementById('zr_charges').value || 0) * kurs[document.getElementById('currency_zr').value];
        total += parseFloat(document.getElementById('extra_charges').value || 0) * kurs[document.getElementById('currency_extra').value];

        document.getElementById('overall_cost_courier_lcl').value = 'Rp ' + total.toLocaleString('id-ID', {minimumFractionDigits: 2});
    }
    function showParseNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show mt-2`;
        alertDiv.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const whatsappCard = document.querySelector('.card.border-0.shadow-sm.mb-4:nth-child(4)');
        whatsappCard.querySelector('.card-body').appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    </script>
@endsection
