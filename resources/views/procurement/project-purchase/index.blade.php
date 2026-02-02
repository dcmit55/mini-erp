{{-- resources/views/logistic/project-purchase/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Project Purchase - Store')

@section('styles')
<style>
    .project-purchase-container {
        display: flex;
        min-height: calc(100vh - 120px);
        gap: 20px;
        padding: 20px;
        background-color: #f8f9fa;
    }
    
    .left-panel {
        flex: 0 0 280px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 25px;
        height: fit-content;
    }
    
    .right-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .top-right-panel {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 20px;
    }
    
    .middle-right-panel {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 20px;
    }
    
    .main-content-panel {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 25px;
        flex: 1;
    }
    
    .section-title {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
        font-size: 1.25rem;
    }
    
    .info-item {
        margin-bottom: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    
    .info-label {
        font-weight: 600;
        color: #555;
        font-size: 0.9rem;
        margin-bottom: 4px;
    }
    
    .info-value {
        color: #2c3e50;
        font-size: 1rem;
    }
    
    .nav-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin-top: 10px;
    }
    
    .nav-item {
        background: #f8f9fa;
        padding: 12px 15px;
        border-radius: 8px;
        text-decoration: none;
        color: #2c3e50;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
        text-align: center;
        font-size: 0.9rem;
    }
    
    .nav-item:hover {
        background: #3498db;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
    }
    
    .status-card {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    
    .status-card.in-stock {
        background: rgba(46, 204, 113, 0.1);
        border-left: 4px solid #2ecc71;
    }
    
    .status-card.out-of-stock {
        background: rgba(231, 76, 60, 0.1);
        border-left: 4px solid #e74c3c;
    }
    
    .status-card.check-goods {
        background: rgba(241, 196, 15, 0.1);
        border-left: 4px solid #f1c40f;
    }
    
    .status-icon {
        font-size: 1.5rem;
        margin-right: 15px;
    }
    
    .status-title {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .status-desc {
        color: #666;
        font-size: 0.9rem;
    }
    
    .table-container {
        overflow-x: auto;
        margin-top: 20px;
    }
    
    .project-purchase-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }
    
    .project-purchase-table thead {
        background-color: #3498db;
        color: white;
    }
    
    .project-purchase-table th {
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .project-purchase-table tbody tr {
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }
    
    .project-purchase-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .project-purchase-table td {
        padding: 14px 12px;
        color: #444;
        font-size: 0.9rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-action {
        padding: 6px 12px;
        border-radius: 5px;
        font-size: 0.8rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-view {
        background: #3498db;
        color: white;
    }
    
    .btn-edit {
        background: #f39c12;
        color: white;
    }
    
    .btn-delete {
        background: #e74c3c;
        color: white;
    }
    
    .btn-action:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    
    .search-filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .search-box {
        flex: 1;
        min-width: 300px;
        position: relative;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 20px 12px 40px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.95rem;
    }
    
    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #777;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-primary {
        background: #3498db;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        background: #2980b9;
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
    }
    
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-success {
        background: #d5f4e6;
        color: #27ae60;
    }
    
    .badge-warning {
        background: #fef9e7;
        color: #f39c12;
    }
    
    .badge-danger {
        background: #fdedec;
        color: #e74c3c;
    }
    
    @media (max-width: 1200px) {
        .project-purchase-container {
            flex-direction: column;
        }
        
        .left-panel {
            flex: none;
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="project-purchase-container">
    <!-- Left Panel -->
    <div class="left-panel">
        <h3 class="section-title">Project Purchase Details</h3>
        
        <div class="info-item">
            <div class="info-label">Tanggal</div>
            <div class="info-value">{{ $projectPurchase->tanggal ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">PO Number</div>
            <div class="info-value">{{ $projectPurchase->po_number ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">No Pesanan</div>
            <div class="info-value">{{ $projectPurchase->no_pesanan ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Item Description</div>
            <div class="info-value">{{ $projectPurchase->item_description ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Department</div>
            <div class="info-value">{{ $projectPurchase->department ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Projects</div>
            <div class="info-value">{{ $projectPurchase->projects ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Job Orders</div>
            <div class="info-value">{{ $projectPurchase->job_orders ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Tracking</div>
            <div class="info-value">{{ $projectPurchase->tracking ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Supplier</div>
            <div class="info-value">{{ $projectPurchase->supplier ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">PIC</div>
            <div class="info-value">{{ $projectPurchase->pic ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Qty</div>
            <div class="info-value">{{ $projectPurchase->qty ?? 'N/A' }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Unit Price</div>
            <div class="info-value">Rp {{ number_format($projectPurchase->unit_price ?? 0, 0, ',', '.') }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Total Price</div>
            <div class="info-value">Rp {{ number_format($projectPurchase->total_price ?? 0, 0, ',', '.') }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Freight</div>
            <div class="info-value">Rp {{ number_format($projectPurchase->freight ?? 0, 0, ',', '.') }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Invoice Total</div>
            <div class="info-value">Rp {{ number_format($projectPurchase->invoice_total ?? 0, 0, ',', '.') }}</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Note</div>
            <div class="info-value">{{ $projectPurchase->note ?? 'No notes' }}</div>
        </div>
    </div>
    
    <!-- Right Panel -->
    <div class="right-panel">
        <!-- Top Right Panel -->
        <div class="top-right-panel">
            <h3 class="section-title">Navigation</h3>
            <div class="nav-grid">
                <a href="#" class="nav-item">Production</a>
                <a href="#" class="nav-item">Material Request</a>
                <a href="#" class="nav-item">Inventory List</a>
                <a href="#" class="nav-item">Inventory Listing</a>
                <a href="#" class="nav-item">Store / Dyla</a>
                <a href="#" class="nav-item active">Project Purchase</a>
                <a href="#" class="nav-item">Purchase Tracking</a>
                <a href="#" class="nav-item">Goods In</a>
                <a href="#" class="nav-item">Goods Out</a>
                <a href="#" class="nav-item">Check Goods Arrive</a>
                <a href="#" class="nav-item">Projects Ledger & Report</a>
                <a href="#" class="nav-item">Finance</a>
            </div>
        </div>
        
        <!-- Middle Right Panel -->
        <div class="middle-right-panel">
            <h3 class="section-title">Material Status</h3>
            
            <div class="status-card in-stock">
                <div class="status-icon">✅</div>
                <div>
                    <div class="status-title">Materials has Stock</div>
                    <div class="status-desc">Items available in inventory</div>
                </div>
            </div>
            
            <div class="status-card out-of-stock">
                <div class="status-icon">⚠️</div>
                <div>
                    <div class="status-title">Materials request is out of stock</div>
                    <div class="status-desc">Need to order more materials</div>
                </div>
            </div>
            
            <div class="status-card check-goods">
                <div class="status-icon">📦</div>
                <div>
                    <div class="status-title">Check the goods that arrive</div>
                    <div class="status-desc">Verify incoming shipments</div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Panel -->
        <div class="main-content-panel">
            <div class="search-filter-bar">
                <div class="search-box">
                    <i class="search-icon">🔍</i>
                    <input type="text" placeholder="Search project purchases...">
                </div>
                
                <div class="filter-actions">
                    <button class="btn-primary">
                        + New Purchase
                    </button>
                    <button class="btn-primary">
                        Export Data
                    </button>
                </div>
            </div>
            
            <h3 class="section-title">Project Purchase List</h3>
            
            <div class="table-container">
                <table class="project-purchase-table">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Tanggal</th>
                            <th>Item Description</th>
                            <th>Department</th>
                            <th>Projects</th>
                            <th>Supplier</th>
                            <th>Qty</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projectPurchases as $purchase)
                        <tr>
                            <td>{{ $purchase->po_number }}</td>
                            <td>{{ $purchase->tanggal }}</td>
                            <td>{{ Str::limit($purchase->item_description, 30) }}</td>
                            <td>{{ $purchase->department }}</td>
                            <td>{{ $purchase->projects }}</td>
                            <td>{{ Str::limit($purchase->supplier, 20) }}</td>
                            <td>{{ $purchase->qty }}</td>
                            <td>Rp {{ number_format($purchase->total_price, 0, ',', '.') }}</td>
                            <td>
                                @if($purchase->status == 'completed')
                                <span class="badge badge-success">Completed</span>
                                @elseif($purchase->status == 'pending')
                                <span class="badge badge-warning">Pending</span>
                                @else
                                <span class="badge badge-danger">Cancelled</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view">View</button>
                                    <button class="btn-action btn-edit">Edit</button>
                                    <button class="btn-action btn-delete">Delete</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 30px;">
                                No project purchases found. Click "New Purchase" to add one.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($projectPurchases->hasPages())
            <div style="margin-top: 20px; display: flex; justify-content: center;">
                {{ $projectPurchases->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    // Implement search logic here
                    console.log('Searching for:', this.value);
                }
            });
        }
        
        // Button actions
        document.querySelectorAll('.btn-view').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const poNumber = row.cells[0].textContent;
                alert(`View details for PO: ${poNumber}`);
            });
        });
        
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const poNumber = row.cells[0].textContent;
                alert(`Edit purchase: ${poNumber}`);
            });
        });
        
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const poNumber = row.cells[0].textContent;
                if (confirm(`Are you sure you want to delete PO: ${poNumber}?`)) {
                    // Implement delete logic here
                    row.style.opacity = '0.5';
                    setTimeout(() => {
                        row.remove();
                    }, 300);
                }
            });
        });
        
        // New Purchase button
        document.querySelector('.btn-primary').addEventListener('click', function() {
            alert('Create new project purchase');
            // Implement new purchase logic here
        });
    });
</script>
@endsection