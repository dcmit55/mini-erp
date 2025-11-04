@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-file-invoice-dollar gradient-icon me-2" style="font-size: 1.5rem;"></i>
                    <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Project Costing Report</h2>
                </div>
                <div class="mb-3">
                    <form id="filter-form" method="GET" action="{{ route('costing.report') }}" class="row g-2">
                        <div class="col-lg-3">
                            <select id="filter-department" name="department" class="form-select select2">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department }}"
                                        {{ request('department') == $department ? 'selected' : '' }}>
                                        {{ ucfirst($department) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <button type="submit" class="btn btn-primary" id="filter-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                    aria-hidden="true"></span>
                                Filter
                            </button>
                            <a href="{{ route('costing.report') }}" class="btn btn-outline-secondary btn-sm"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset</a>
                        </div>
                    </form>
                </div>
                <table class="table table-hover align-middle table-sm" id="datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Project Name</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td>{{ $project->name }}</td>
                                <td>
                                    @if ($project->departments->count())
                                        {{ $project->departments->pluck('name')->map(fn($name) => ucfirst($name))->implode(', ') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="viewCosting('{{ $project->id }}')"
                                        title="View Report"><i class="bi bi-eye"></i></button>
                                    <a href="{{ route('costing.export', $project->id) }}" class="btn btn-success btn-sm"
                                        title="Export to Excel"><i class="bi bi-file-earmark-excel"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                                <th>Material</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Domestic Freight</th>
                                <th>Intl Freight</th>
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
        $(document).ready(function() {
            $('#datatable').DataTable({
                responsive: true,
                stateSave: true,
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
                            domestic_freight: 0,
                            international_freight: 0,
                            total_unit_cost: 0,
                            unit: 'N/A',
                            currency: {
                                name: 'N/A'
                            }
                        };

                        const name = inventory.name || 'N/A';
                        const unit = inventory.unit || 'N/A';
                        const price = inventory.price ?? 0;
                        const domesticFreight = inventory.domestic_freight ?? 0;
                        const internationalFreight = inventory.international_freight ?? 0;
                        const totalUnitCost = inventory.total_unit_cost ?? 0;
                        const currencyName = (inventory.currency && inventory.currency.name) ? inventory
                            .currency.name : 'N/A';
                        const quantity = material.used_quantity ?? 0;
                        const totalCost = material.total_cost ?? 0;

                        const row = `
                    <tr>
                        <td>${name}</td>
                        <td>${quantity} ${unit}</td>
                        <td>${formatCurrency(price)} ${currencyName}</td>
                        <td class="text-info">${formatCurrency(domesticFreight)} ${currencyName}</td>
                        <td class="text-warning">${formatCurrency(internationalFreight)} ${currencyName}</td>
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
