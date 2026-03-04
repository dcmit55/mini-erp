{{-- resources/views/procurement/Project-Purchase/print.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print PO - {{ $purchase->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #fff;
            color: #000;
            line-height: 1.5;
            padding: 20px;
        }
        
        .print-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header h3 {
            font-size: 20px;
            font-weight: normal;
            color: #333;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            color: #666;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            border: 1px solid #000;
            padding: 15px;
        }
        
        .info-card h4 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-row {
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
        }
        
        .info-value {
            display: inline-block;
        }
        
        /* Project Details */
        .project-details {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .project-details h4 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
        }
        
        .project-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 12px;
        }
        
        .items-table th {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 10px 8px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 11px;
        }
        
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        
        .items-table .text-left { text-align: left; }
        .items-table .text-center { text-align: center; }
        .items-table .text-right { text-align: right; }
        
        .item-name {
            font-weight: 500;
        }
        
        .item-type {
            display: inline-block;
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            margin-top: 3px;
        }
        
        /* Totals Section */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        
        .totals-table {
            width: 350px;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border: 1px solid #000;
            font-size: 13px;
        }
        
        .totals-table .label {
            font-weight: normal;
            background: #f9f9f9;
        }
        
        .totals-table .total-row {
            font-weight: bold;
            background: #f0f0f0;
        }
        
        .totals-table .total-row td {
            font-weight: bold;
        }
        
        .totals-table .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        /* Notes */
        .notes-section {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .notes-section h4 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
        }
        
        .notes-content {
            font-size: 12px;
            line-height: 1.6;
            min-height: 60px;
        }
        
        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin: 40px 0 20px;
        }
        
        .signature-box {
            width: 30%;
            text-align: center;
        }
        
        .signature-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 8px;
            font-size: 12px;
        }
        
        /* Footer */
        .print-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px dashed #999;
            padding-top: 10px;
        }
        
        /* Status Badges (untuk print) */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff;
            border: 1px solid #000;
        }
        
        .status-approved {
            background: #fff;
            border: 1px solid #000;
        }
        
        .status-received {
            background: #fff;
            border: 1px solid #000;
        }
        
        /* Summary Cards */
        .summary-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        
        .summary-card {
            border: 1px solid #000;
            padding: 12px;
            text-align: center;
        }
        
        .summary-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: bold;
        }
        
        /* Print-specific */
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .status-badge {
                border: 1px solid #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Utility */
        .no-print {
            display: block;
        }
        
        .mb-2 { margin-bottom: 10px; }
        .mt-2 { margin-top: 10px; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Tombol Print (hanya muncul di layar) -->
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="{{ route('project-purchases.show', $purchase->uid) }}" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Header -->
        <div class="header">
            <h1>PURCHASE ORDER</h1>
            <h3>{{ $purchase->po_number }}</h3>
            <p>{{ $purchase->date->format('d F Y') }}</p>
        </div>

        <!-- Supplier & Order Info -->
        <div class="info-grid">
            <div class="info-card">
                <h4>Supplier Information</h4>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $purchase->supplier->name ?? '-' }}</span>
                </div>
                @if($purchase->supplier && $purchase->supplier->address)
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value">{{ $purchase->supplier->address }}</span>
                </div>
                @endif
                @if($purchase->supplier && $purchase->supplier->contact_person)
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    <span class="info-value">{{ $purchase->supplier->contact_person }}</span>
                </div>
                @endif
            </div>
            
            <div class="info-card">
                <h4>Order Information</h4>
                <div class="info-row">
                    <span class="info-label">PIC:</span>
                    <span class="info-value">{{ $purchase->pic->username ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department:</span>
                    <span class="info-value">{{ $purchase->department->name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Type:</span>
                    <span class="info-value">{{ $purchase->is_offline_order ? 'Offline' : 'Online' }}</span>
                </div>
                @if($purchase->resi_number)
                <div class="info-row">
                    <span class="info-label">Resi:</span>
                    <span class="info-value">{{ $purchase->resi_number }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Project Details -->
        <div class="project-details">
            <h4>Project Details</h4>
            <div class="project-grid">
                @if($purchase->project_type == 'client')
                    <div>
                        <div class="info-label">Project Type</div>
                        <div class="info-value">Client Project</div>
                    </div>
                    <div>
                        <div class="info-label">Project Name</div>
                        <div class="info-value">{{ $purchase->project->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Job Order</div>
                        <div class="info-value">{{ $purchase->jobOrder->name ?? '-' }}</div>
                    </div>
                @else
                    <div>
                        <div class="info-label">Project Type</div>
                        <div class="info-value">Internal Project</div>
                    </div>
                    <div>
                        <div class="info-label">Project</div>
                        <div class="info-value">{{ $purchase->internalProject->project ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Job</div>
                        <div class="info-value">{{ $purchase->internalProject->job ?? '-' }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="40%">Item Description</th>
                    <th width="10%">Type</th>
                    <th width="10%">Qty</th>
                    <th width="10%">Unit</th>
                    <th width="12%">Unit Price</th>
                    <th width="13%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($poItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">
                            @if($item->purchase_type == 'restock')
                                {{ $item->material->name ?? 'Unknown' }}
                            @else
                                {{ $item->new_item_name }}
                            @endif
                        </div>
                        @if($item->material && $item->material->code)
                            <div><small>Code: {{ $item->material->code }}</small></div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="item-type">
                            {{ $item->purchase_type == 'restock' ? 'Restock' : 'New Item' }}
                        </span>
                    </td>
                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                    <td class="text-center">{{ $item->unit->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0) }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_price, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Cards -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="summary-label">Total Items</div>
                <div class="summary-value">{{ $poItems->count() }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Quantity</div>
                <div class="summary-value">{{ number_format($poItems->sum('quantity')) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Amount</div>
                <div class="summary-value">Rp {{ number_format($poItems->sum('total_price'), 0) }}</div>
            </div>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="amount">Rp {{ number_format($poItems->sum('total_price'), 0) }}</td>
                </tr>
                @if($purchase->freight > 0)
                <tr>
                    <td class="label">Freight Cost</td>
                    <td class="amount">Rp {{ number_format($purchase->freight, 0) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="label"><strong>GRAND TOTAL</strong></td>
                    <td class="amount"><strong>Rp {{ number_format($poTotal, 0) }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        @if($purchase->note)
        <div class="notes-section">
            <h4>Notes</h4>
            <div class="notes-content">{{ $purchase->note }}</div>
        </div>
        @endif

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-title">DIBUAT OLEH</div>
                <div class="signature-line">{{ $purchase->pic->username ?? '______________' }}</div>
                <div style="font-size: 11px; margin-top: 5px;">(PIC)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">DISETUJUI OLEH</div>
                <div class="signature-line">{{ $purchase->approver->username ?? '______________' }}</div>
                <div style="font-size: 11px; margin-top: 5px;">(Finance)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">DITERIMA OLEH</div>
                <div class="signature-line">{{ $purchase->receiver->username ?? '______________' }}</div>
                <div style="font-size: 11px; margin-top: 5px;">(Inventory)</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="print-footer">
            Dicetak pada: {{ now()->format('d/m/Y H:i:s') }} | {{ $purchase->po_number }} | {{ $poItems->count() }} item(s)
        </div>
    </div>

    <script>
        // Auto print (opsional - uncomment jika ingin auto print)
        // window.onload = function() { 
        //     setTimeout(function() { window.print(); }, 500);
        // }
    </script>
</body>
</html>