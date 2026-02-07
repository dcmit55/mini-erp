<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\DcmCosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DcmCostingController extends Controller
{
    /**
     * Display a listing of DCM costings
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $department = $request->get('department');
        $search = $request->get('search');
        
        $query = DcmCosting::query();
        
        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhere('job_order', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($status) {
            $query->where('status', $status);
        }
        
        // Date filter
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        // Department filter
        if ($department) {
            $query->where('department', 'like', "%{$department}%");
        }
        
        $costings = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get unique departments for filter dropdown
        $departments = DcmCosting::select('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');
        
        return view('finance.dcm-costings.index', compact(
            'costings',
            'status',
            'startDate',
            'endDate',
            'department',
            'departments',
            'search'
        ));
    }
    
    /**
     * Display DCM costing details
     */
    public function show(DcmCosting $costing)
    {
        return view('finance.dcm-costings.show', compact('costing'));
    }
    
    /**
     * Show the form for creating a new DCM costing
     */
    public function create()
    {
        return view('finance.dcm-costings.create');
    }
    
    /**
     * Store a newly created DCM costing
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_number' => 'required|string|max:100',
            'date' => 'required|date',
            'purchase_type' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'freight' => 'nullable|numeric|min:0',
            'invoice_total' => 'required|numeric|min:0',
            'department' => 'required|string|max:100',
            'project_type' => 'required|string|max:50',
            'project_name' => 'nullable|string|max:255',
            'job_order' => 'nullable|string|max:100',
            'supplier' => 'required|string|max:255',
            'tracking_number' => 'nullable|string|max:100',
            'resi_number' => 'nullable|string|max:100',
            'status' => 'required|string|in:pending,approved,rejected',
            'item_status' => 'required|string|in:pending,received,not_received',
            'finance_notes' => 'nullable|string|max:1000',
        ]);
        
        DcmCosting::create($validated);
        
        return redirect()->route('dcm-costings.index')
            ->with('success', 'DCM Costing created successfully.');
    }
    
    /**
     * Show the form for editing a DCM costing
     */
    public function edit(DcmCosting $costing)
    {
        return view('finance.dcm-costings.edit', compact('costing'));
    }
    
    /**
     * Update the specified DCM costing
     */
    public function update(Request $request, DcmCosting $costing)
    {
        $validated = $request->validate([
            'po_number' => 'required|string|max:100',
            'date' => 'required|date',
            'purchase_type' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'freight' => 'nullable|numeric|min:0',
            'invoice_total' => 'required|numeric|min:0',
            'department' => 'required|string|max:100',
            'project_type' => 'required|string|max:50',
            'project_name' => 'nullable|string|max:255',
            'job_order' => 'nullable|string|max:100',
            'supplier' => 'required|string|max:255',
            'tracking_number' => 'nullable|string|max:100',
            'resi_number' => 'nullable|string|max:100',
            'status' => 'required|string|in:pending,approved,rejected',
            'item_status' => 'required|string|in:pending,received,not_received',
            'finance_notes' => 'nullable|string|max:1000',
        ]);
        
        $costing->update($validated);
        
        return redirect()->route('dcm-costings.show', $costing->uid)
            ->with('success', 'DCM Costing updated successfully.');
    }
    
    /**
     * Remove the specified DCM costing
     */
    public function destroy(DcmCosting $costing)
    {
        $costing->delete();
        
        return redirect()->route('dcm-costings.index')
            ->with('success', 'DCM Costing deleted successfully.');
    }
    
    /**
     * Export DCM costings to Excel/PDF
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $department = $request->get('department');
        
        $query = DcmCosting::query();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        if ($department) {
            $query->where('department', 'like', "%{$department}%");
        }
        
        $costings = $query->orderBy('date', 'desc')->get();
        
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('finance.dcm-costings.export-pdf', compact('costings'));
            return $pdf->download('dcm-costings-' . date('Y-m-d') . '.pdf');
        }
        
        // Export to CSV
        return $this->exportToCSV($costings);
    }
    
    /**
     * Export to CSV
     */
    private function exportToCSV($costings)
    {
        $fileName = 'dcm-costings-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function() use ($costings) {
            $file = fopen('php://output', 'w');
            
            // Tambahkan BOM untuk UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Header CSV
            fputcsv($file, [
                'PO Number',
                'Date',
                'Department',
                'Project Type',
                'Project Name',
                'Job Order',
                'Purchase Type',
                'Item Name',
                'Quantity',
                'Unit Price (Rp)',
                'Total Price (Rp)',
                'Freight (Rp)',
                'Invoice Total (Rp)',
                'Supplier',
                'Status',
                'Item Status',
                'Finance Notes',
                'Tracking Number',
                'Resi Number',
                'Approved At',
                'Created At'
            ]);

            // Data rows
            foreach ($costings as $costing) {
                fputcsv($file, [
                    $costing->po_number,
                    $costing->date ? $costing->date->format('d/m/Y') : '',
                    $costing->department,
                    ucfirst($costing->project_type),
                    $costing->project_name ?? '',
                    $costing->job_order ?? '',
                    ucfirst($costing->purchase_type),
                    $costing->item_name,
                    $costing->quantity,
                    number_format($costing->unit_price, 2, '.', ''),
                    number_format($costing->total_price, 2, '.', ''),
                    number_format($costing->freight ?? 0, 2, '.', ''),
                    number_format($costing->invoice_total, 2, '.', ''),
                    $costing->supplier,
                    ucfirst($costing->status),
                    ucfirst($costing->item_status),
                    $costing->finance_notes ?? '',
                    $costing->tracking_number ?? '',
                    $costing->resi_number ?? '',
                    $costing->approved_at ? $costing->approved_at->format('d/m/Y H:i') : '',
                    $costing->created_at ? $costing->created_at->format('d/m/Y H:i') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Get statistics for dashboard
     */
    public function statistics()
    {
        $total = DcmCosting::count();
        $approved = DcmCosting::where('status', 'approved')->count();
        $pending = DcmCosting::where('status', 'pending')->count();
        $rejected = DcmCosting::where('status', 'rejected')->count();
        $totalAmount = DcmCosting::sum('invoice_total');
        
        return response()->json([
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'total_amount' => $totalAmount,
            'avg_amount' => $total > 0 ? round($totalAmount / $total, 2) : 0
        ]);
    }
    
    /**
     * Print DCM costing
     */
    public function print(DcmCosting $costing)
    {
        $pdf = Pdf::loadView('finance.dcm-costings.print', compact('costing'));
        return $pdf->stream('dcm-costing-' . $costing->uid . '.pdf');
    }
}