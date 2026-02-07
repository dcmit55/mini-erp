@extends('layouts.app')

@section('styles')
    <style>
        /* Detail rows styling */
        tr.detail-row {
            background-color: #f8f9fa !important;
            display: none;
            /* Hidden by default */
        }

        tr.detail-row td {
            border: none !important;
            padding: 0 !important;
        }

        /* Toggle button styling */
        .toggle-detail,
        .toggle-materials {
            color: #0d6efd;
            text-decoration: none;
            cursor: pointer;
        }

        .toggle-detail:hover,
        .toggle-materials:hover {
            color: #0a58ca;
        }

        .toggle-detail i,
        .toggle-materials i {
            transition: transform 0.2s ease;
        }

        /* Table styling */
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Pagination styling */
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
@endsection

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file-invoice-dollar gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Project Costing Report</h2>
                    </div>
                    {{-- <a href="{{ route('costing.export.all', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i> Export All Projects
                    </a> --}}
                </div>
                <div class="mb-3">
                    <form id="filter-form" method="GET" action="{{ route('costing.report') }}" class="row g-2">
                        <div class="col-lg-3">
                            <input type="text" id="search-input" name="search" class="form-control"
                                placeholder="Search project name..." value="{{ request('search') }}">
                        </div>
                        <div class="col-lg-3">
                            <select id="filter-department" name="department" class="form-select select2"
                                data-placeholder="All Departments">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department }}"
                                        {{ request('department') == $department ? 'selected' : '' }}>
                                        {{ ucfirst($department) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <select id="filter-created-by" name="created_by" class="form-select select2"
                                data-placeholder="All Creators">
                                <option value="">All Created_by</option>
                                <option value="sync_from_lark"
                                    {{ request('created_by') == 'sync_from_lark' ? 'selected' : '' }}>
                                    Sync from Lark
                                </option>
                                <option value="manual" {{ request('created_by') == 'manual' ? 'selected' : '' }}>
                                    Manual Entry
                                </option>
                                @foreach ($createdByOptions as $creator)
                                    @if ($creator && $creator !== 'Sync from Lark')
                                        <option value="{{ $creator }}"
                                            {{ request('created_by') == $creator ? 'selected' : '' }}>
                                            {{ $creator }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-job-order" name="job_order" class="form-select select2"
                                data-placeholder="All Job Orders">
                                <option value="">All Job Orders</option>
                                @foreach ($jobOrders as $jobOrder)
                                    <option value="{{ $jobOrder->id }}"
                                        {{ request('job_order') == $jobOrder->id ? 'selected' : '' }}>
                                        {{ $jobOrder->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <button type="submit" class="btn btn-primary btn-sm me-1">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                            <a href="{{ route('costing.report') }}" class="btn btn-outline-secondary btn-sm"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset</a>
                        </div>
                    </form>
                </div>
                <table class="table table-hover align-middle table-sm" id="costing-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%"></th>
                            <th>Project Name</th>
                            <th>Department</th>
                            <th>Created By</th>
                            <th style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr class="main-project-row" data-project-id="{{ $project->id }}">
                                <td class="text-center">
                                    @if ($project->jobOrders->count() > 0)
                                        <button class="btn btn-link btn-sm toggle-detail p-0" data-id="{{ $project->id }}"
                                            data-has-job-orders="true" title="Show Job Orders">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    @endif
                                </td>
                                <td>{{ $project->name }}</td>
                                <td>
                                    @if ($project->departments->count())
                                        {{ $project->departments->pluck('name')->map(fn($name) => ucfirst($name))->implode(', ') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($project->created_by === 'Sync from Lark')
                                        <span class="">{{ $project->created_by }}</span>
                                    @else
                                        {{ $project->created_by ?? '-' }}
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="viewCosting('{{ $project->id }}')"
                                        title="View Report"><i class="bi bi-eye"></i></button>
                                    <a href="{{ route('costing.export', $project->id) }}" class="btn btn-success btn-sm"
                                        title="Export to Excel"><i class="bi bi-file-earmark-excel"></i></a>
                                </td>
                            </tr>
                            @if ($project->jobOrders->count() > 0)
                                {{-- Detail row - hidden by default, will be shown by JavaScript --}}
                                <tr class="detail-row detail-row-{{ $project->id }}" style="display:none;"
                                    data-project-id="{{ $project->id }}">
                                    <td colspan="5" class="p-0">
                                        <div class="p-3 bg-light">
                                            <h6 class="mb-3"><i class=""></i>Job Orders for
                                                <strong>{{ $project->name }}</strong></Job>
                                                @foreach ($project->jobOrders as $jobOrder)
                                                    <div class="card mb-2 shadow-sm">
                                                        <div class="card-body p-2">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <div class="d-flex align-items-center">
                                                                    <button
                                                                        class="btn btn-link btn-sm toggle-materials p-0 me-2"
                                                                        data-project-id="{{ $project->id }}"
                                                                        data-job-order-id="{{ $jobOrder->id }}"
                                                                        title="Show Materials">
                                                                        <i class="fas fa-chevron-right"></i>
                                                                    </button>
                                                                    <div>
                                                                        {{ $jobOrder->name }}
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            @if ($jobOrder->start_date)
                                                                                <i
                                                                                    class="far fa-calendar me-1"></i>{{ $jobOrder->start_date->format('Y-m-d') }}
                                                                            @endif
                                                                            @if ($jobOrder->department)
                                                                                <i
                                                                                    class="fas fa-building ms-2 me-1"></i>{{ $jobOrder->department->name }}
                                                                            @endif
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="materials-container mt-2"
                                                                id="materials-{{ $jobOrder->id }}" style="display:none;">
                                                                <div class="d-flex justify-content-center py-3">
                                                                    <div class="spinner-border spinner-border-sm text-primary"
                                                                        role="status">
                                                                        <span class="visually-hidden">Loading...</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No projects found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Showing {{ $projects->firstItem() ?? 0 }} to {{ $projects->lastItem() ?? 0 }} of
                        {{ $projects->total() }} projects
                    </div>
                    <nav aria-label="Page navigation">
                        {{ $projects->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="costingModal" tabindex="-1" aria-labelledby="costingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="costingModalLabel">Project Costing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm align-middle table-hover">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th>Job Order</th>
                                <th>Material</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Unit Cost</th>
                                <th>Total Cost (IDR)</th>
                            </tr>
                        </thead>
                        <tbody id="costingTableBody">
                            <!-- Data akan dimuat melalui AJAX -->
                        </tbody>
                    </table>
                    <h5 class="text-end" id="grandTotal">Grand Total: Rp 0</h5>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(function() {
            // Simple toggle for job orders (like shipping management)
            $('.toggle-detail').on('click', function() {
                var projectId = $(this).data('id');
                $('.detail-row-' + projectId).toggle();
                $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
            });

            // Toggle materials for job order (AJAX load)
            $(document).on('click', '.toggle-materials', function(e) {
                e.preventDefault();

                const projectId = $(this).data('project-id');
                const jobOrderId = $(this).data('job-order-id');
                const materialsContainer = $('#materials-' + jobOrderId);
                const icon = $(this).find('i');

                if (materialsContainer.is(':visible')) {
                    materialsContainer.slideUp(300);
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                } else {
                    materialsContainer.slideDown(300);
                    icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');

                    // Load materials if not loaded yet
                    if (!materialsContainer.data('loaded')) {
                        // Show loading spinner
                        materialsContainer.html(`
                            <div class="d-flex justify-content-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        `);

                        // Fetch materials via AJAX
                        $.get('/costing-report/' + projectId + '/job-order/' + jobOrderId + '/materials')
                            .done(function(data) {
                                if (data.success) {
                                    let html = '';

                                    if (data.materials.length === 0) {
                                        html =
                                            '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-1"></i>No materials used in this job order</div>';
                                    } else {
                                        html =
                                            '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
                                        html += '<thead class="table-secondary"><tr>';
                                        html +=
                                            '<th>Material</th><th>Qty</th><th>Unit Price</th>';
                                        html += '<th>Total Unit Cost</th><th>Total Cost (IDR)</th>';
                                        html += '</tr></thead><tbody>';

                                        data.materials.forEach(function(material) {
                                            html += '<tr>';

                                            html += '<td>' + material.material_name + '</td>';
                                            html += '<td>' + material.quantity + ' ' + material
                                                .unit + '</td>';
                                            html += '<td>' + material.unit_price + ' ' +
                                                material.currency + '</td>';
                                            html += '<td class="fw-bold text-success">' +
                                                material.total_unit_cost + ' ' + material
                                                .currency + '</td>';
                                            html += '<td class="fw-bold">' + material
                                                .total_cost_idr + '</td>';
                                            html += '</tr>';
                                        });

                                        html += '</tbody></table></div>';
                                    }

                                    materialsContainer.html(html);
                                    materialsContainer.data('loaded', true);
                                } else {
                                    materialsContainer.html('<div class="alert alert-danger mb-0">' +
                                        data.message + '</div>');
                                }
                            })
                            .fail(function() {
                                materialsContainer.html(
                                    '<div class="alert alert-danger mb-0">Failed to load materials</div>'
                                );
                            });
                    }
                }
            });
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: function() {
                return $(this).data('placeholder');
            },
            allowClear: true
        });

        // Search on Enter key press (NOT on every keystroke)
        $('#search-input').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#filter-form').submit();
            }
        });

        // Auto-submit ONLY on dropdown filter change (NOT search input)
        $('#filter-department, #filter-created-by, #filter-job-order').on('change', function() {
            $('#filter-form').submit();
        });

        function formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        }

        function viewCosting(projectId) {
            fetch(`/costing-report/${projectId}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('costingTableBody');
                    tableBody.innerHTML = '';

                    document.getElementById('costingModalLabel').innerText = `Project Costing: ${data.project}`;

                    data.materials.forEach(material => {
                        const inventory = material.inventory || {
                            id: null,
                            name: 'N/A',
                            price: 0,
                            total_unit_cost: 0,
                            unit: 'N/A',
                            currency: {
                                name: 'N/A'
                            }
                        };

                        const name = inventory.name || 'N/A';
                        const unit = inventory.unit || 'N/A';
                        const price = inventory.price ?? 0;
                        const totalUnitCost = inventory.total_unit_cost ?? 0;
                        const currencyName = (inventory.currency && inventory.currency.name) ? inventory
                            .currency.name : 'N/A';
                        const quantity = material.used_quantity ?? 0;
                        const totalCost = material.total_cost ?? 0;
                        const jobOrderName = material.job_order_name || 'No Job Order';

                        const row = `
                    <tr>
                        <td><span class="badge bg-primary">${jobOrderName}</span></td>
                        <td>${name}</td>
                        <td>${quantity} ${unit}</td>
                        <td>${formatCurrency(price)} ${currencyName}</td>
                        <td class="fw-bold text-success">${formatCurrency(totalUnitCost)} ${currencyName}</td>
                        <td class="fw-bold">${formatCurrency(totalCost)} IDR</td>
                    </tr>
                `;
                        tableBody.innerHTML += row;
                    });

                    document.getElementById('grandTotal').innerHTML =
                        `Grand Total: <span class="text-success fw-bold">${formatCurrency(data.grand_total_idr)} IDR</span>`;

                    const modal = new bootstrap.Modal(document.getElementById('costingModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching costing data:', error);
                    alert('Failed to load costing data. Please try again.');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filter-form');
            const filterBtn = document.getElementById('filter-btn');
            const spinner = filterBtn.querySelector('.spinner-border');
            if (filterForm && filterBtn && spinner) {
                filterForm.addEventListener('submit', function() {
                    filterBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    filterBtn.childNodes[2].textContent = ' Filtering...';
                });
            }
        });
    </script>
@endpush
