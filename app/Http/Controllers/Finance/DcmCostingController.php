<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\DcmCosting;
use App\Models\Procurement\ProjectPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class DcmCostingController extends Controller
{
    /**
     * Display a listing of DCM costings
     */
    public function index(Request $request)
    {
        $query = DcmCosting::where('is_current', true);
        
        // Apply filters
        if ($search = $request->search) {
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%");
            });
        }
        
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        
        if ($department = $request->department) {
            $query->where('department', 'like', "%{$department}%");
        }
        
        if ($startDate = $request->start_date && $endDate = $request->end_date) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $costings = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $departments = DcmCosting::where('is_current', true)
            ->select('department')->distinct()->orderBy('department')->pluck('department');
        
        return view('finance.dcm-costings.index', compact('costings', 'departments'));
    }
    
    /**
     * Display DCM costing details
     */
    public function show(DcmCosting $costing)
    {
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
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'invoice_total' => 'required|numeric|min:0',
            'department' => 'required|string|max:100',
            'supplier' => 'required|string|max:255',
            'status' => 'required|string|in:pending,approved,rejected',
        ]);
        
        // Set old record to not current if exists
        $existing = DcmCosting::where('po_number', $validated['po_number'])
            ->where('is_current', true)
            ->first();
            
        if ($existing) {
            $existing->update(['is_current' => false]);
        }
        
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
        if (!$costing->is_current) {
            return redirect()->route('dcm-costings.show', $costing->uid)
                ->with('error', 'Cannot edit non-current revision.');
        }
        
        return view('finance.dcm-costings.edit', compact('costing'));
    }
    
    /**
     * Update the specified DCM costing
     */
    public function update(Request $request, DcmCosting $costing)
    {
        if (!$costing->is_current) {
            return back()->with('error', 'Cannot update non-current revision.');
        }
        
        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'invoice_total' => 'required|numeric|min:0',
            'revision_notes' => 'nullable|string|max:500',
        ]);
        
        DB::beginTransaction();
        try {
            $costing->update(['is_current' => false]);
            
            $newData = $costing->toArray();
            unset($newData['id'], $newData['uid']);
            
            $newData['unit_price'] = $validated['unit_price'];
            $newData['total_price'] = $validated['total_price'];
            $newData['invoice_total'] = $validated['invoice_total'];
            
            if ($validated['revision_notes']) {
                $newData['finance_notes'] = ($costing->finance_notes ?? '') . 
                    "\n\n[Revision: " . now()->format('Y-m-d H:i') . "] " . 
                    $validated['revision_notes'];
            }
            
            $newData['uid'] = Str::uuid();
            $newData['revision_at'] = now();
            $newData['is_current'] = true;
            
            $newCosting = DcmCosting::create($newData);
            
            DB::commit();
            
            return redirect()->route('dcm-costings.index')
                ->with('success', 'DCM Costing updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove the specified DCM costing
     */
    public function destroy(DcmCosting $costing)
    {
        DB::beginTransaction();
        try {
            if ($costing->is_current) {
                $previous = DcmCosting::where('po_number', $costing->po_number)
                    ->where('id', '!=', $costing->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($previous) {
                    $previous->update(['is_current' => true]);
                }
            }
            
            $costing->delete();
            
            DB::commit();
            
            return redirect()->route('dcm-costings.index')
                ->with('success', 'DCM Costing deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
    
    /**
     * Export DCM costings to CSV/PDF
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $query = DcmCosting::where('is_current', true);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $costings = $query->orderBy('date', 'desc')->get();
        
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('finance.dcm-costings.export-pdf', compact('costings'));
            return $pdf->download('dcm-costings-' . date('Y-m-d') . '.pdf');
        }
        
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
            
            // UTF-8 BOM
            fwrite($file, "\xEF\xBB\xBF");
            
            // Header
            fputcsv($file, [
                'PO Number',
                'Date',
                'Department',
                'Item Name',
                'Quantity',
                'Unit Price',
                'Total Price',
                'Invoice Total',
                'Supplier',
                'Status'
            ]);

            // Data
            foreach ($costings as $costing) {
                fputcsv($file, [
                    $costing->po_number,
                    $costing->date ? $costing->date->format('d/m/Y') : '',
                    $costing->department,
                    $costing->item_name,
                    $costing->quantity,
                    number_format($costing->unit_price, 2, '.', ''),
                    number_format($costing->total_price, 2, '.', ''),
                    number_format($costing->invoice_total, 2, '.', ''),
                    $costing->supplier,
                    ucfirst($costing->status)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
        if ($costing->is_current) {
            return redirect()->route('dcm-costings.show', $costing->uid)
                ->with('error', 'This is already the current revision.');
        }
        
        DB::beginTransaction();
        try {
            // Set all revisions to not current
            DcmCosting::where('po_number', $costing->po_number)
                      ->update(['is_current' => false]);
            
            // Clone selected revision
            $newData = $costing->toArray();
            unset($newData['id'], $newData['uid']);
            
            $newData['uid'] = Str::uuid();
            $newData['revision_at'] = now();
            $newData['is_current'] = true;
            
            // Add restoration note
            $restoreNote = "\n\n=== RESTORED FROM REVISION ===" .
                          "\nDate: " . now()->format('d/m/Y H:i') .
                          "\nRestored by: " . auth()->user()->name;
            
            $newData['finance_notes'] = ($costing->finance_notes ?? '') . $restoreNote;
            
            $newCosting = DcmCosting::create($newData);
            
            DB::commit();
            
            return redirect()->route('dcm-costings.show', $newCosting->uid)
                ->with('success', 'Restored to previous revision successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to restore revision: ' . $e->getMessage());
        }
    }
    
    /**
     * ================================================
     * EDITED PURCHASE INTEGRATION METHODS
     * ================================================
     */
    
    /**
     * Check for edited purchases and update DCM Costing automatically
     */
    public function checkForUpdates()
    {
        try {
            $editedPOs = ProjectPurchase::select('po_number')
                ->whereNotNull('po_number')
                ->where('status', 'approved')
                ->groupBy('po_number')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('po_number');
            
            $updatedCount = 0;
            
            foreach ($editedPOs as $poNumber) {
                $currentPurchase = ProjectPurchase::where('po_number', $poNumber)
                    ->where('is_current', 1)
                    ->first();
                
                if (!$currentPurchase) continue;
                
                $dcmCosting = DcmCosting::where('po_number', $poNumber)
                    ->where('is_current', true)
                    ->first();
                
                if ($dcmCosting) {
                    if ($this->hasChanges($dcmCosting, $currentPurchase)) {
                        $this->updateDcmFromPurchase($dcmCosting, $currentPurchase);
                        $updatedCount++;
                    }
                } else {
                    $this->createDcmFromPurchase($currentPurchase);
                    $updatedCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Updated {$updatedCount} DCM costings.",
                'updated_count' => $updatedCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Manual update for specific PO
     */
    public function manualUpdate($poNumber)
    {
        try {
            $currentPurchase = ProjectPurchase::where('po_number', $poNumber)
                ->where('is_current', 1)
                ->first();
            
            if (!$currentPurchase) {
                return back()->with('error', 'Purchase not found.');
            }
            
            DB::beginTransaction();
            
            $dcmCosting = DcmCosting::where('po_number', $poNumber)
                ->where('is_current', true)
                ->first();
            
            if ($dcmCosting) {
                $this->updateDcmFromPurchase($dcmCosting, $currentPurchase);
                $message = "DCM Costing updated.";
            } else {
                $this->createDcmFromPurchase($currentPurchase);
                $message = "New DCM Costing created.";
            }
            
            DB::commit();
            
            return back()->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get pending updates
     */
    public function getPendingUpdates()
    {
        try {
            $editedPOs = ProjectPurchase::select('po_number')
                ->whereNotNull('po_number')
                ->where('status', 'approved')
                ->groupBy('po_number')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('po_number');
            
            $pendingUpdates = [];
            
            foreach ($editedPOs as $poNumber) {
                $currentPurchase = ProjectPurchase::where('po_number', $poNumber)
                    ->where('is_current', 1)
                    ->first();
                
                if (!$currentPurchase) continue;
                
                $dcmCosting = DcmCosting::where('po_number', $poNumber)
                    ->where('is_current', true)
                    ->first();
                
                if ($dcmCosting && $this->hasChanges($dcmCosting, $currentPurchase)) {
                    $pendingUpdates[] = [
                        'po_number' => $poNumber,
                        'item_name' => $currentPurchase->new_item_name ?: 
                                     ($currentPurchase->material->name ?? 'N/A'),
                        'needs_update' => true
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'pending_updates' => $pendingUpdates,
                'count' => count($pendingUpdates)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk update
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'po_numbers' => 'required|array',
        ]);
        
        $updatedCount = 0;
        
        foreach ($request->po_numbers as $poNumber) {
            try {
                DB::transaction(function () use ($poNumber, &$updatedCount) {
                    $currentPurchase = ProjectPurchase::where('po_number', $poNumber)
                        ->where('is_current', 1)
                        ->first();
                    
                    if (!$currentPurchase) return;
                    
                    $dcmCosting = DcmCosting::where('po_number', $poNumber)
                        ->where('is_current', true)
                        ->first();
                    
                    if ($dcmCosting) {
                        $this->updateDcmFromPurchase($dcmCosting, $currentPurchase);
                    } else {
                        $this->createDcmFromPurchase($currentPurchase);
                    }
                    
                    $updatedCount++;
                });
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Updated {$updatedCount} DCM costings.",
            'updated' => $updatedCount
        ]);
    }
    
    /**
     * Get statistics
     */
    public function statistics()
    {
        $total = DcmCosting::where('is_current', true)->count();
        $approved = DcmCosting::where('is_current', true)->where('status', 'approved')->count();
        $totalAmount = DcmCosting::where('is_current', true)->sum('invoice_total');
        
        return response()->json([
            'total' => $total,
            'approved' => $approved,
            'total_amount' => $totalAmount,
        ]);
    }
    
    /**
     * ================================================
     * PRIVATE HELPER METHODS
     * ================================================
     */
    
    private function hasChanges($dcmCosting, $purchase)
    {
        return $dcmCosting->quantity != $purchase->quantity ||
               $dcmCosting->unit_price != $purchase->unit_price ||
               $dcmCosting->total_price != $purchase->total_price;
    }
    
    private function updateDcmFromPurchase($dcmCosting, $purchase)
    {
        $dcmCosting->update(['is_current' => false]);
        
        $newData = $dcmCosting->toArray();
        unset($newData['id'], $newData['uid']);
        
        $newData['purchase_id'] = $purchase->id;
        $newData['quantity'] = $purchase->quantity ?? 1;
        $newData['unit_price'] = $purchase->unit_price ?? 0;
        $newData['total_price'] = $purchase->total_price ?? 0;
        $newData['invoice_total'] = $purchase->invoice_total ?? 0;
        
        $updateNote = "\n\n[Updated from edited purchase: " . now()->format('Y-m-d H:i') . "]";
        $newData['finance_notes'] = ($dcmCosting->finance_notes ?? '') . $updateNote;
        
        $newData['uid'] = Str::uuid();
        $newData['revision_at'] = now();
        $newData['is_current'] = true;
        
        DcmCosting::create($newData);
    }
    
    private function createDcmFromPurchase($purchase)
    {
        DcmCosting::create([
            'purchase_id' => $purchase->id,
            'po_number' => $purchase->po_number ?? 'N/A',
            'date' => $purchase->date ?? now(),
            'item_name' => $purchase->new_item_name ?: ($purchase->material->name ?? 'N/A'),
            'quantity' => $purchase->quantity ?? 1,
            'unit_price' => $purchase->unit_price ?? 0,
            'total_price' => $purchase->total_price ?? 0,
            'invoice_total' => $purchase->invoice_total ?? 0,
            'department' => $purchase->department ? $purchase->department->name : 'N/A',
            'supplier' => $purchase->supplier ? $purchase->supplier->name : 'N/A',
            'status' => 'approved',
            'item_status' => 'pending',
            'is_current' => true,
        ]);
    }
}