@extends('layouts.app')

@push('styles')
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #8F12FE, #4A25AA);
        }

        .text-primary {
            color: #6610f2 !important;
        }

        .info-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #8F12FE;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .action-btn {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .btn-primary.action-btn {
            background: linear-gradient(135deg, #8F12FE, #6610f2);
            border-color: #8F12FE;
        }

        .btn-success.action-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .btn-info.action-btn {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }

        .btn-warning.action-btn {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #212529;
        }

        .btn-secondary.action-btn {
            background: linear-gradient(135deg, #6c757d, #495057);
        }

        .image-container {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-placeholder {
            color: #adb5bd;
            font-size: 4rem;
        }

        .detail-table th {
            background-color: #f8f9fc;
            font-weight: 600;
            color: #495057;
            border-color: #e3e6f0;
            padding: 1rem;
        }

        .detail-table td {
            padding: 1rem;
            border-color: #e3e6f0;
        }

        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .quantity-badge {
            background: linear-gradient(135deg, #8F12FE, #6610f2);
            color: white;
        }

        .price-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .breadcrumb .breadcrumb-item,
        .breadcrumb .breadcrumb-item a {
            color: #8F12FE !important;
            /* Warna ungu branding */
            font-weight: 500;
            text-decoration: none;
        }

        .breadcrumb .breadcrumb-item.active {
            color: #4A25AA !important;
            /* Ungu lebih gelap untuk item aktif */
        }

        /* Enhanced Modal Styles */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #8F12FE, #4A25AA);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }

        .modal-header .modal-title {
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .modal-body {
            padding: 2rem;
            background: #fafbfc;
        }

        .modal-footer {
            background: #ffffff;
            border-top: 1px solid #e9ecef;
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: .375rem 2.25rem .375rem .75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #8F12FE;
            box-shadow: 0 0 0 0.2rem rgba(143, 18, 254, 0.15);
        }

        .input-group {
            position: relative;
        }

        .input-group-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            font-size: 0.9rem;
        }

        .btn-modal {
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            border: none;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, #8F12FE, #6610f2);
            color: white;
        }

        .btn-modal-primary:hover {
            background: linear-gradient(135deg, #7a0fdb, #5a0ed9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(143, 18, 254, 0.3);
        }

        .btn-modal-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .btn-modal-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #3d4448);
            transform: translateY(-2px);
        }

        .btn-modal-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-modal-success:hover {
            background: linear-gradient(135deg, #218838, #1aa086);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        /* Material Usage Table Styling */
        .usage-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .usage-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #495057;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
            border: none;
        }

        .usage-table td {
            padding: 0.875rem 0.75rem;
            border: none;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.9rem;
        }

        .usage-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .usage-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Loading State */
        .loading-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: #6c757d;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #8F12FE;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive Modal Enhancements */
        @media (max-width: 768px) {
            .action-btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .card-body {
                padding: 1rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .modal-footer {
                padding: 1rem;
                flex-direction: column;
            }

            .btn-modal {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .usage-table th,
            .usage-table td {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .modal-dialog {
                margin: 1rem;
            }

            .modal-header {
                padding: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }
        }

        .dt-container {
            padding: .5rem;
        }

        #materialUsageModal .dataTables_filter {
            margin-bottom: .5rem !important;
        }

        .datatables-footer-row {
            border-top: 1px solid #eee;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .datatables-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .vr-divider {
            width: 1px;
            height: 24px;
            background: #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }

        .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        @media (max-width: 767.98px) {
            .datatables-footer-row {
                flex-direction: column !important;
                gap: 0.5rem;
            }

            .datatables-left {
                flex-direction: column !important;
                gap: 0.5rem;
            }

            .vr-divider {
                display: none;
            }

            .dataTables_paginate {
                justify-content: center !important;
            }
        }

        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.375rem;
            --bs-pagination-color: #6c757d;
            --bs-pagination-bg: #fff;
            --bs-pagination-border-width: 1px;
            --bs-pagination-border-color: #dee2e6;
            --bs-pagination-border-radius: 0.375rem;
            --bs-pagination-hover-color: #495057;
            --bs-pagination-hover-bg: #e9ecef;
            --bs-pagination-hover-border-color: #dee2e6;
            --bs-pagination-focus-color: #495057;
            --bs-pagination-focus-bg: #e9ecef;
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(143, 18, 254, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #8F12FE;
            --bs-pagination-active-border-color: #4A25AA;
            --bs-pagination-disabled-color: #6c757d;
            --bs-pagination-disabled-bg: #fff;
            --bs-pagination-disabled-border-color: #dee2e6;
        }

        .page-link {
            transition: all 0.15s ease-in-out;
        }

        .page-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            border-color: #8F12FE;
            box-shadow: 0 2px 4px rgba(143, 18, 254, 0.3);
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}"
                                class="text-decoration-none">Inventory</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $inventory->name }}</li>
                    </ol>
                </nav>

                <!-- Main Card -->
                <div class="card info-card shadow-lg">
                    <div class="card-header gradient-bg text-white py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-cube me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ $inventory->name }}</h4>
                                    <small class="opacity-75">Inventory Details</small>
                                </div>
                            </div>
                            <div class="text-end">
                                @if ($inventory->category)
                                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                        {{ $inventory->category->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- Alert Messages -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show mx-3 mt-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                            <i class="fas fa-times-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card-body p-4">
                        <div class="row">
                            <!-- Details Section -->
                            <div class="col-lg-6 mb-4">
                                <h5 class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>
                                    Material Details
                                </h5>
                                <div class="table-responsive">
                                    <table class="table detail-table table-hover">
                                        <tbody>
                                            <tr>
                                                <th scope="row" style="width: 40%;">
                                                    <i class="fas fa-tag me-2 text-muted"></i>Name
                                                </th>
                                                <td class="fw-semibold">{{ $inventory->name }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <i class="fas fa-layer-group me-2 text-muted"></i>Category
                                                </th>
                                                <td>
                                                    @if ($inventory->category)
                                                        <span
                                                            class="badge badge-custom bg-primary">{{ $inventory->category->name }}</span>
                                                    @else
                                                        <span class="text-muted">No Category</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <i class="fas fa-boxes me-2 text-muted"></i>Quantity
                                                </th>
                                                <td>
                                                    <span class="badge badge-custom quantity-badge">
                                                        {{ number_format($inventory->quantity, 2) }}
                                                        {{ $inventory->unit ?? '' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row" style="width: 30%;">
                                                    <i class="fas fa-dollar-sign me-2 text-muted"></i>Price Information
                                                </th>
                                                <td>
                                                    @if (in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_finance']))
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <small class="text-muted">Unit Price:</small><br>
                                                                <span class="badge bg-secondary text-white">
                                                                    {{ number_format($inventory->price ?? 0, 2) }}
                                                                    {{ $inventory->currency->name ?? '' }}
                                                                </span>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <small class="text-muted">Domestic Freight:</small><br>
                                                                <span class="badge bg-info text-white">
                                                                    {{ number_format($inventory->unit_domestic_freight_cost ?? 0, 2) }}
                                                                    {{ $inventory->currency->name ?? '' }}
                                                                </span>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <small class="text-muted">International Freight:</small><br>
                                                                <span class="badge bg-warning text-dark">
                                                                    {{ number_format($inventory->unit_international_freight_cost ?? 0, 2) }}
                                                                    {{ $inventory->currency->name ?? '' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-2">
                                                            <div class="col-12">
                                                                <small class="text-muted">Total Unit Cost:</small><br>
                                                                <span class="badge badge-custom price-badge">
                                                                    {{ number_format($inventory->total_unit_cost, 2) }}
                                                                    {{ $inventory->currency->name ?? '' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Access restricted</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <i class="fas fa-truck me-2 text-muted"></i>Supplier
                                                </th>
                                                <td>{{ $inventory->supplier ? $inventory->supplier->name : 'No Supplier' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>Location
                                                </th>
                                                <td>{{ $inventory->location ? $inventory->location->name : 'No Location' }}
                                                </td>
                                            </tr>
                                            @if ($inventory->remark)
                                                <tr>
                                                    <th scope="row">
                                                        <i class="fas fa-sticky-note me-2 text-muted"></i>Remark
                                                    </th>
                                                    <td>{!! $inventory->remark !!}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Image Section -->
                            <div class="col-lg-6 mb-4">
                                <h5 class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-image me-2 text-primary"></i>
                                    Material Image
                                </h5>
                                <div class="image-container">
                                    @if ($inventory->img)
                                        <a href="{{ asset('storage/' . $inventory->img) }}" data-fancybox="gallery"
                                            data-caption="{{ $inventory->name }}">
                                            <img src="{{ asset('storage/' . $inventory->img) }}"
                                                class="img-fluid rounded shadow-sm" alt="{{ $inventory->name }}"
                                                style="max-height: 300px; width: 100%; object-fit: cover;">
                                        </a>
                                    @else
                                        <div class="text-center p-5">
                                            <i class="fas fa-image image-placeholder"></i>
                                            <p class="text-muted mt-3">No Image Available</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center py-4"
                        style="background: linear-gradient(135deg, #f8f9fc, #e9ecef); border-top: none;">
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('material_requests.create', ['material_id' => $inventory->id]) }}"
                                class="btn btn-info action-btn">
                                <i class="fas fa-plus-circle me-2"></i>Request Material
                            </a>
                            <button type="button" class="btn btn-primary action-btn" data-bs-toggle="modal"
                                data-bs-target="#goodsInModal">
                                <i class="fas fa-arrow-down me-2"></i>Goods In
                            </button>
                            @if (auth()->user()->isLogisticAdmin())
                                <button type="button" class="btn btn-success action-btn" data-bs-toggle="modal"
                                    data-bs-target="#goodsOutModal">
                                    <i class="fas fa-arrow-up me-2"></i>Goods Out
                                </button>
                            @endif
                            <button type="button" class="btn btn-warning action-btn" id="viewMaterialUsage">
                                <i class="fas fa-chart-line me-2"></i>View Usage
                            </button>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('inventory.index') }}" class="btn btn-secondary action-btn">
                                <i class="fas fa-arrow-left me-2"></i>Back to Inventory
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal for Goods In -->
            <div class="modal fade" id="goodsInModal" tabindex="-1" aria-labelledby="goodsInModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('goods_in.store_independent') }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="goodsInModalLabel">
                                    <i class="fas fa-arrow-down me-2"></i>Goods In - {{ $inventory->name }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="inventory_id" value="{{ $inventory->id }}">

                                <div class="mb-3">
                                    <label for="goodsin_project_id" class="form-label">
                                        <i class="fas fa-project-diagram me-1"></i>Project
                                    </label>
                                    <div class="input-group">
                                        <select name="project_id" id="goodsin_project_id" class="form-select">
                                            <option value="">Select Project (Optional)</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="goodsin_quantity" class="form-label">
                                        <i class="fas fa-calculator me-1"></i>Quantity <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="quantity" id="goodsin_quantity" class="form-control"
                                            required min="0.01" step="any" placeholder="Enter quantity">
                                        <span class="input-group-text">{{ $inventory->unit ?? 'Units' }}</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="goodsin_returned_at" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Returned Date <span
                                            class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="date" name="returned_at" id="goodsin_returned_at"
                                            class="form-control" required value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="goodsin_remark" class="form-label">
                                        <i class="fas fa-comment me-1"></i>Remark
                                    </label>
                                    <textarea name="remark" id="goodsin_remark" class="form-control" rows="3"
                                        placeholder="Add any additional notes..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-modal btn-modal-primary" id="goodsin-submit-btn">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                                    <i class="fas fa-check me-1"></i>Submit Goods In
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal for Goods Out -->
            <div class="modal fade" id="goodsOutModal" tabindex="-1" aria-labelledby="goodsOutModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('goods_out.store_independent') }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="goodsOutModalLabel">
                                    <i class="fas fa-arrow-up me-2"></i>Goods Out - {{ $inventory->name }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="inventory_id" value="{{ $inventory->id }}">

                                <!-- Stock Info Card -->
                                <div class="alert alert-info d-flex align-items-center mb-3"
                                    style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); border: none; border-radius: 8px;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>Available Stock:</strong> {{ number_format($inventory->quantity, 2) }}
                                        {{ $inventory->unit ?? 'Units' }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="goodsout_project_id" class="form-label">
                                        <i class="fas fa-project-diagram me-1"></i>Project
                                    </label>
                                    <div class="input-group">
                                        <select name="project_id" id="goodsout_project_id" class="form-select">
                                            <option value="">Select Project (Optional)</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="goodsout_user_id" class="form-label">
                                        <i class="fas fa-user me-1"></i>Assigned User <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select name="user_id" id="goodsout_user_id" class="form-select" required>
                                            <option value="" disabled selected>Select User</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->username }}
                                                    @if ($user->department)
                                                        - {{ $user->department->name }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="goodsout_quantity" class="form-label">
                                        <i class="fas fa-calculator me-1"></i>Quantity <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="quantity" id="goodsout_quantity"
                                            class="form-control" required min="0.01" step="any"
                                            max="{{ $inventory->quantity }}" placeholder="Enter quantity">
                                        <span class="input-group-text">{{ $inventory->unit ?? 'Units' }}</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="goodsout_remark" class="form-label">
                                        <i class="fas fa-comment me-1"></i>Remark
                                    </label>
                                    <textarea name="remark" id="goodsout_remark" class="form-control" rows="3"
                                        placeholder="Add purpose or additional notes..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-modal btn-modal-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-modal btn-modal-success" id="goodsout-submit-btn">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                                    <i class="fas fa-check me-1"></i>Submit Goods Out
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal for Material Usage -->
            <div class="modal fade" id="materialUsageModal" tabindex="-1" aria-labelledby="materialUsageModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="materialUsageModalLabel">
                                <i class="fas fa-chart-line me-2"></i>Material Usage Report - {{ $inventory->name }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Loading State -->
                            <div id="usage-loading" class="loading-container">
                                <div class="loading-spinner"></div>
                                <span>Loading material usage data...</span>
                            </div>

                            <!-- Content -->
                            <div id="usage-content" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table usage-table" id="materialUsageDataTable">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-project-diagram me-1"></i>Project</th>
                                                <th><i class="fas fa-arrow-up me-1"></i>Goods Out Qty</th>
                                                <th><i class="fas fa-arrow-down me-1"></i>Goods In Qty</th>
                                                <th><i class="fas fa-calculator me-1"></i>Used Qty</th>
                                                <th><i class="fas fa-percentage me-1"></i>Usage Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody id="materialUsageTable">
                                            <!-- Data akan dimuat melalui AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endsection


        @push('scripts')
            <script>
                $(document).on('click', '#viewMaterialUsage', function(e) {
                    e.preventDefault();
                    const inventoryId = {{ $inventory->id }};

                    // Show modal and loading state
                    $('#materialUsageModal').modal('show');
                    $('#usage-loading').show();
                    $('#usage-content').hide();

                    // Clear previous data
                    $('#materialUsageTable').empty();

                    // Fetch data via AJAX
                    $.ajax({
                        url: "{{ route('material_usage.get_by_inventory') }}",
                        method: "GET",
                        data: {
                            inventory_id: inventoryId
                        },
                        success: function(response) {
                            $('#usage-loading').hide();
                            $('#usage-content').show();

                            $('#materialUsageTable').empty();

                            if (Array.isArray(response) && response.length > 0) {
                                response.forEach(function(usage) {
                                    const usageRate = usage.goods_out_quantity > 0 ?
                                        ((usage.used_quantity / usage.goods_out_quantity) * 100)
                                        .toFixed(1) : '0.0';

                                    const badgeClass = usageRate >= 80 ? 'bg-danger' :
                                        usageRate >= 50 ? 'bg-warning' : 'bg-success';

                                    $('#materialUsageTable').append(`
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <strong>${usage.project_name || 'No Project'}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">${usage.goods_out_quantity} {{ $inventory->unit ?? 'Units' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">${usage.goods_in_quantity} {{ $inventory->unit ?? 'Units' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">${usage.used_quantity} {{ $inventory->unit ?? 'Units' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge ${badgeClass}">${usageRate}%</span>
                                            </td>
                                        </tr>
                                    `);
                                });

                                // Initialize DataTables with enhanced styling
                                $('#materialUsageDataTable').DataTable({
                                    destroy: true,
                                    paging: true,
                                    searching: true,
                                    ordering: true,
                                    pageLength: 10,
                                    language: {
                                        search: "_INPUT_",
                                        searchPlaceholder: "Search usage data...",
                                        lengthMenu: "Show _MENU_ entries",
                                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                        infoEmpty: "No data available",
                                        emptyTable: "No material usage data found",
                                        zeroRecords: "No matching records found"
                                    },
                                    dom: '<"dataTables_filter"f>' +
                                        't<' +
                                        '"row datatables-footer-row align-items-center"' +
                                        '<"col-md-7 d-flex align-items-center gap-2 datatables-left"l<"vr-divider mx-2">i>' +
                                        '<"col-md-5 dataTables_paginate justify-content-end"p>' +
                                        '>',
                                    responsive: true
                                });
                            } else {
                                $('#materialUsageTable').append(`
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No material usage data found</p>
                                        </td>
                                    </tr>
                                `);
                            }
                        },
                        error: function(xhr) {
                            $('#usage-loading').hide();
                            $('#usage-content').show();
                            $('#materialUsageTable').append(`
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                                        <p class="text-danger mb-0">Failed to load material usage data</p>
                                        <small class="text-muted">Please try again later</small>
                                    </td>
                                </tr>
                            `);
                        }
                    });
                });

                $(document).ready(function() {
                    // Store original button content
                    const goodsInBtn = $('#goodsin-submit-btn');
                    const goodsInBtnHtml = goodsInBtn.html();
                    const goodsOutBtn = $('#goodsout-submit-btn');
                    const goodsOutBtnHtml = goodsOutBtn.html();

                    // Form submission handlers with enhanced UX
                    $('#goodsInModal form').on('submit', function() {
                        goodsInBtn.prop('disabled', true);
                        goodsInBtn.find('.spinner-border').removeClass('d-none');
                        goodsInBtn.find('i:not(.spinner-border)').addClass('d-none');
                    });

                    $('#goodsOutModal form').on('submit', function() {
                        goodsOutBtn.prop('disabled', true);
                        goodsOutBtn.find('.spinner-border').removeClass('d-none');
                        goodsOutBtn.find('i:not(.spinner-border)').addClass('d-none');
                    });

                    // Reset buttons when modals are opened
                    $('#goodsInModal').on('shown.bs.modal', function() {
                        goodsInBtn.prop('disabled', false);
                        goodsInBtn.html(goodsInBtnHtml);

                        // Initialize Select2
                        $('#goodsin_project_id').select2({
                            dropdownParent: $('#goodsInModal'),
                            width: '100%',
                            theme: 'bootstrap-5',
                            allowClear: true,
                            placeholder: 'Select Project (Optional)'
                        });
                    });

                    $('#goodsOutModal').on('shown.bs.modal', function() {
                        goodsOutBtn.prop('disabled', false);
                        goodsOutBtn.html(goodsOutBtnHtml);

                        // Focus on first input
                        $('#goodsout_user_id').focus();

                        // Initialize Select2
                        $('#goodsout_project_id, #goodsout_user_id').select2({
                            dropdownParent: $('#goodsOutModal'),
                            width: '100%',
                            theme: 'bootstrap-5',
                            allowClear: true
                        });
                    });

                    // Real-time validation for goods out quantity
                    $('#goodsout_quantity').on('input', function() {
                        const maxStock = {{ $inventory->quantity }};
                        const enteredQty = parseFloat($(this).val()) || 0;

                        if (enteredQty > maxStock) {
                            $(this).addClass('is-invalid');
                            if (!$(this).next('.invalid-feedback').length) {
                                $(this).after(
                                    `<div class="invalid-feedback">Quantity cannot exceed available stock (${maxStock})</div>`
                                );
                            }
                        } else {
                            $(this).removeClass('is-invalid');
                            $(this).next('.invalid-feedback').remove();
                        }
                    });

                    // Enhanced modal cleanup
                    $('.modal').on('hidden.bs.modal', function() {
                        // Jangan hapus option pada select!
                        $(this).find('.select2-container').remove();
                        // Hanya reset value, jangan reset html option
                        $(this).find('select').val('').trigger('change');
                        $(this).find('form')[0]?.reset();
                        $(this).find('.is-invalid').removeClass('is-invalid');
                        $(this).find('.invalid-feedback').remove();
                    });
                });

                // Initialize Fancybox for image gallery
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Fancybox !== 'undefined') {
                        Fancybox.bind("[data-fancybox='gallery']", {
                            Toolbar: {
                                display: [{
                                        id: "counter",
                                        position: "center"
                                    },
                                    "zoom", "download", "close"
                                ],
                            },
                            Thumbs: false,
                            Image: {
                                zoom: true
                            },
                            Hash: false,
                        });
                    }
                });
            </script>
        @endpush
