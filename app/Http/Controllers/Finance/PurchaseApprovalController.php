<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\ProjectPurchase;
use App\Models\Finance\DcmCosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PurchaseApprovalController extends Controller
{
    /**
     * Display a listing of purchases pending finance approval
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $department = $request->get('department');
        $purchaseType = $request->get('purchase_type');
        
        $query = ProjectPurchase::with(['department', 'supplier', 'pic', 'material', 'jobOrder'])
            ->where('status', 'pending')
            ->where('is_current', 1)
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('job_order_id', 'like', "%{$search}%")
                  ->orWhere('new_item_name', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        if ($department) {
            $query->where('department_id', $department);
        }
        
        if ($purchaseType) {
            $query->where('purchase_type', $purchaseType);
        }
        
        $purchases = $query->paginate(20);
        $departments = \App\Models\Admin\Department::orderBy('name')->get();
        
        return view('finance.purchase-approvals.index', compact(
            'purchases', 'search', 'startDate', 'endDate', 
            'department', 'purchaseType', 'departments'
        ));
    }
    
    /**
     * Approve purchase by finance
     */
    public function approve(Request $request, $id)
    {
        \Log::info("=== APPROVE PURCHASE ID: {$id} ===");
        
        try {
            $request->validate([
                'finance_notes' => 'nullable|string|max:1000',
                'resi_number' => 'nullable|string|max:255',
            ]);
            
            $purchase = ProjectPurchase::where('is_current', 1)
                ->with(['department', 'supplier', 'pic', 'material', 'jobOrder'])
                ->findOrFail($id);
            
            DB::beginTransaction();
            
            $updateData = [
                'status' => 'approved',
                'finance_notes' => $request->input('finance_notes', $purchase->finance_notes),
                'resi_number' => $request->input('resi_number', $purchase->resi_number),
            ];
            
            if (Schema::hasColumn('indo_purchases', 'approved_at')) {
                $updateData['approved_at'] = now();
            }
            
            if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                $updateData['approved_by'] = Auth::id();
            }
            
            $purchase->update($updateData);
            
            // Create DCM costing
            $this->createDcmCosting($purchase, 'approved', $request);
            
            DB::commit();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase ' . $purchase->po_number . ' approved successfully.'
                ]);
            }
            
            return redirect()->route('purchase-approvals.index')
                ->with('success', 'Purchase ' . $purchase->po_number . ' berhasil di-approve.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Approval error: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('purchase-approvals.index')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Reject purchase by finance
     */
    public function reject(Request $request, $id)
    {
        \Log::info("=== REJECT PURCHASE ID: {$id} ===");
        
        try {
            $request->validate([
                'finance_notes' => 'required|string|min:5|max:1000',
            ]);
            
            $purchase = ProjectPurchase::where('is_current', 1)
                ->with(['department', 'supplier', 'pic', 'material', 'jobOrder'])
                ->findOrFail($id);
            
            DB::beginTransaction();
            
            $updateData = [
                'status' => 'rejected',
                'finance_notes' => $request->finance_notes,
            ];
            
            $purchase->update($updateData);
            
            $this->createDcmCosting($purchase, 'rejected', $request);
            
            DB::commit();
            
            \Log::info("=== REJECT SUCCESS ===");
            
            return redirect()->route('purchase-approvals.index')
                ->with('success', 'Purchase ' . $purchase->po_number . ' berhasil di-reject.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Reject error: ' . $e->getMessage());
            
            return redirect()->route('purchase-approvals.index')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk approve purchases
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'purchase_ids' => 'required|array',
            'purchase_ids.*' => 'exists:indo_purchases,id',
            'finance_notes' => 'nullable|string|max:1000',
        ]);
        
        $approvedCount = 0;
        $failed = [];
        
        foreach ($request->purchase_ids as $purchaseId) {
            try {
                DB::transaction(function () use ($purchaseId, $request) {
                    $purchase = ProjectPurchase::where('is_current', 1)
                        ->with(['department', 'supplier', 'pic', 'material', 'jobOrder'])
                        ->findOrFail($purchaseId);
                    
                    $updateData = [
                        'status' => 'approved',
                        'finance_notes' => $request->finance_notes,
                    ];
                    
                    if (Schema::hasColumn('indo_purchases', 'approved_at')) {
                        $updateData['approved_at'] = now();
                    }
                    
                    if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                        $updateData['approved_by'] = Auth::id();
                    }
                    
                    $purchase->update($updateData);
                    
                    $this->createDcmCosting($purchase, 'approved', $request);
                });
                
                $approvedCount++;
                
            } catch (\Exception $e) {
                $failed[] = $purchaseId;
                \Log::error("Bulk approve failed for purchase {$purchaseId}: " . $e->getMessage());
            }
        }
        
        $message = "Berhasil approve {$approvedCount} purchase(s).";
        if (count($failed) > 0) {
            $message .= " Gagal: " . implode(', ', $failed);
        }
        
        return redirect()->route('purchase-approvals.index')
            ->with('success', $message);
    }
    
    /**
     * Get statistics for dashboard
     */
    public function statistics()
    {
        $totalPending = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->count();
            
        $thisMonth = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        $totalAmount = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->sum('invoice_total');
            
        $avgProcessingDays = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->avg(DB::raw('DATEDIFF(NOW(), created_at)'));
        
        return response()->json([
            'total_pending' => $totalPending,
            'this_month' => $thisMonth,
            'total_amount' => $totalAmount,
            'avg_processing_days' => round($avgProcessingDays ?? 0, 1)
        ]);
    }
    
    /**
     * View purchase details for approval
     */
    public function viewDetails($id)
    {
        $purchase = ProjectPurchase::where('is_current', 1)
            ->with([
                'department', 'supplier', 'pic', 'material',
                'project', 'internalProject', 'jobOrder',
                'category', 'unit'
            ])
            ->findOrFail($id);
        
        return view('finance.purchase-approvals.details', compact('purchase'));
    }
    
    /**
     * Create DCM Costing
     */
    private function createDcmCosting($purchase, $status, $request)
    {
        $dcmData = [
            'purchase_id' => $purchase->id,
            'po_number' => $purchase->po_number ?? 'N/A',
            'date' => $purchase->date ?? now(),
            'purchase_type' => $purchase->purchase_type ?? 'restock',
            'item_name' => $purchase->new_item_name ?: ($purchase->material ? $purchase->material->name : 'N/A'),
            'quantity' => $purchase->quantity ?? 1,
            'unit_price' => $purchase->unit_price ?? 0,
            'total_price' => $purchase->total_price ?? 0,
            'freight' => $purchase->freight ?? 0,
            'invoice_total' => $purchase->invoice_total ?? 0,
            'department' => $purchase->department ? $purchase->department->name : 'N/A',
            'project_type' => $purchase->project_type ?? 'client',
            'project_name' => $purchase->project_name ?? '',
            'job_order' => $purchase->jobOrder ? $purchase->jobOrder->name : '',
            'supplier' => $purchase->supplier ? $purchase->supplier->name : 'N/A',
            'status' => $status,
            'item_status' => $status === 'approved' ? 'pending' : 'not_received',
            'finance_notes' => $request->input('finance_notes', ''),
            'resi_number' => $request->input('resi_number', $purchase->resi_number),
            'approved_at' => $status === 'approved' ? now() : null,
            'is_current' => true,
        ];
        
        return DcmCosting::create($dcmData);
    }
}