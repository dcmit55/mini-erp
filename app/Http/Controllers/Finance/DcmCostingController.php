<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\DcmCosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

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
        
        // HANYA TAMPILKAN YANG CURRENT (REVISI TERBARU)
        $query->where('is_current', true);
        
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
        $departments = DcmCosting::where('is_current', true)
            ->select('department')
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
        // Get all revisions for this PO number
        $revisions = DcmCosting::where('po_number', $costing->po_number)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('finance.dcm-costings.show', compact('costing', 'revisions'));
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
            'resi_number' => 'nullable|string|max:100',
            'status' => 'required|string|in:pending,approved,rejected',
            'item_status' => 'required|string|in:pending,received,not_received',
            'finance_notes' => 'nullable|string|max:1000',
        ]);
        
        // Check if PO already exists, set old ones to not current
        $existingPO = DcmCosting::where('po_number', $validated['po_number'])
            ->where('is_current', true)
            ->first();
            
        if ($existingPO) {
            $existingPO->update(['is_current' => false]);
        }
        
        // Set default revision fields
        $validated['revision_at'] = null;
        $validated['is_current'] = true;
        
        DcmCosting::create($validated);
        
        return redirect()->route('dcm-costings.index')
            ->with('success', 'DCM Costing created successfully.');
    }
    
    /**
     * Show the form for editing a DCM costing
     */
    public function edit(DcmCosting $costing)
    {
        // Hanya boleh edit yang current
        if (!$costing->is_current) {
            return redirect()->route('dcm-costings.show', $costing->uid)
                ->with('error', 'Cannot edit non-current revision. Please edit the current version.');
        }
        
        return view('finance.dcm-costings.edit', compact('costing'));
    }
    
    /**
     * Update the specified DCM costing - CREATE NEW REVISION
     */
    public function update(Request $request, DcmCosting $costing)
    {
        // Hanya boleh update yang current
        if (!$costing->is_current) {
            return redirect()->route('dcm-costings.show', $costing->uid)
                ->with('error', 'Cannot update non-current revision.');
        }
        
        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'freight' => 'nullable|numeric|min:0',
            'invoice_total' => 'required|numeric|min:0',
            'revision_notes' => 'nullable|string|max:500',
        ]);
        
        DB::beginTransaction();
        try {
            // 1. Set old record to not current
            $costing->update(['is_current' => false]);
            
            // 2. Clone all data for new revision
            $newCostingData = $costing->toArray();
            
            // Remove unwanted fields
            unset($newCostingData['id'], $newCostingData['uid'], $newCostingData['created_at'], 
                  $newCostingData['updated_at'], $newCostingData['deleted_at']);
            
            // 3. Update financial values from request
            $newCostingData['unit_price'] = $validated['unit_price'];
            $newCostingData['total_price'] = $validated['total_price'];
            $newCostingData['freight'] = $validated['freight'];
            $newCostingData['invoice_total'] = $validated['invoice_total'];
            
            // 4. Set revision fields
            $newCostingData['uid'] = Str::uuid();
            $newCostingData['revision_at'] = now();
            $newCostingData['is_current'] = true;
            
            // 5. Add revision notes to finance notes
            if (!empty($validated['revision_notes'])) {
                $revisionNote = "\n\n=== REVISION ===" .
                               "\nDate: " . now()->format('d/m/Y H:i') .
                               "\nChanges:" .
                               "\n- Unit Price: Rp " . number_format($costing->unit_price, 0, ',', '.') . 
                                 " → Rp " . number_format($validated['unit_price'], 0, ',', '.') .
                               "\n- Total Price: Rp " . number_format($costing->total_price, 0, ',', '.') . 
                                 " → Rp " . number_format($validated['total_price'], 0, ',', '.') .
                               "\n- Freight: Rp " . number_format($costing->freight ?? 0, 0, ',', '.') . 
                                 " → Rp " . number_format($validated['freight'] ?? 0, 0, ',', '.') .
                               "\n- Invoice Total: Rp " . number_format($costing->invoice_total, 0, ',', '.') . 
                                 " → Rp " . number_format($validated['invoice_total'], 0, ',', '.') .
                               "\nNotes: " . $validated['revision_notes'] .
                               "\n" . str_repeat('=', 15);
                
                $newCostingData['finance_notes'] = ($costing->finance_notes ?? '') . $revisionNote;
            }
            
            // 6. Create new revision
            $newCosting = DcmCosting::create($newCostingData);
            
            DB::commit();
            
            return redirect()->route('dcm-costings.show', $newCosting->uid)
                ->with('success', 'DCM Costing updated successfully. New revision created.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to update DCM Costing: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Remove the specified DCM costing
     */
    public function destroy(DcmCosting $costing)
    {
        DB::beginTransaction();
        try {
            // Jika ini adalah current revision, set revision sebelumnya sebagai current
            if ($costing->is_current) {
                $previousRevision = DcmCosting::where('po_number', $costing->po_number)
                    ->where('id', '!=', $costing->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($previousRevision) {
                    $previousRevision->update(['is_current' => true]);
                }
            }
            
            $costing->delete();
            
            DB::commit();
            
            return redirect()->route('dcm-costings.index')
                ->with('success', 'DCM Costing deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to delete DCM Costing: ' . $e->getMessage());
        }
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
        
        // Hanya export yang current
        $query->where('is_current', true);
        
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
                'Revision Date',
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
                    $costing->revision_at ? $costing->revision_at->format('d/m/Y H:i') : 'Original',
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
        // Hanya hitung yang current
        $total = DcmCosting::where('is_current', true)->count();
        $approved = DcmCosting::where('is_current', true)->where('status', 'approved')->count();
        $pending = DcmCosting::where('is_current', true)->where('status', 'pending')->count();
        $rejected = DcmCosting::where('is_current', true)->where('status', 'rejected')->count();
        $totalAmount = DcmCosting::where('is_current', true)->sum('invoice_total');
        
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
    
    /**
     * View revision history for a PO number
     */
    public function revisions($poNumber)
    {
        $revisions = DcmCosting::where('po_number', $poNumber)
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($revisions->isEmpty()) {
            abort(404, 'No DCM Costing found with PO Number: ' . $poNumber);
        }
        
        $current = $revisions->firstWhere('is_current', true);
        
        return view('finance.dcm-costings.revisions', compact('revisions', 'current', 'poNumber'));
    }
    
    /**
     * Restore to specific revision
     */
    public function restoreRevision(DcmCosting $costing)
    {
        // Hanya bisa restore dari revision yang bukan current
        if ($costing->is_current) {
            return redirect()->route('dcm-costings.show', $costing->uid)
                ->with('error', 'This is already the current revision.');
        }
        
        DB::beginTransaction();
        try {
            // 1. Set semua revision dari PO ini menjadi not current
            DcmCosting::where('po_number', $costing->po_number)
                      ->update(['is_current' => false]);
            
            // 2. Clone data dari revision yang dipilih
            $newCostingData = $costing->toArray();
            
            // Remove unwanted fields
            unset($newCostingData['id'], $newCostingData['uid'], $newCostingData['created_at'], 
                  $newCostingData['updated_at'], $newCostingData['deleted_at']);
            
            // 3. Set new revision fields
            $newCostingData['uid'] = Str::uuid();
            $newCostingData['revision_at'] = now();
            $newCostingData['is_current'] = true;
            
            // 4. Add restoration note
            $restoreNote = "\n\n=== RESTORED FROM PREVIOUS REVISION ===" .
                          "\nDate: " . now()->format('d/m/Y H:i') .
                          "\nOriginal Revision Date: " . ($costing->revision_at ? $costing->revision_at->format('d/m/Y H:i') : 'Original') .
                          "\nRestored by: " . auth()->user()->name .
                          "\n" . str_repeat('=', 40);
            
            $newCostingData['finance_notes'] = ($costing->finance_notes ?? '') . $restoreNote;
            
            // 5. Create new revision
            $newCosting = DcmCosting::create($newCostingData);
            
            DB::commit();
            
            return redirect()->route('dcm-costings.show', $newCosting->uid)
                ->with('success', 'Restored to previous revision successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to restore revision: ' . $e->getMessage());
        }
    }
}