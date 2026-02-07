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
        
        // PASTIKAN VIEW PATH INI BENAR:
        // Jika view ada di resources/views/finance/purchase-approvals/index.blade.php
        return view('finance.purchase-approvals.index', compact(
            'purchases', 'search', 'startDate', 'endDate', 'department', 'purchaseType', 'departments'
        ));
    }
    
    /**
     * Approve purchase by finance
     */
    public function approve(Request $request, $id)
    {
        \Log::info("=== APPROVE PURCHASE ID: {$id} ===");
        \Log::info("User: " . (auth()->user() ? auth()->user()->name : 'Guest'));
        \Log::info("Input: " . json_encode($request->all()));
        
        try {
            $request->validate([
                'finance_notes' => 'nullable|string|max:1000',
                'tracking_number' => 'nullable|string|max:255',
                'resi_number' => 'nullable|string|max:255',
            ]);
            
            $purchase = ProjectPurchase::with(['department', 'supplier', 'pic', 'material', 'jobOrder'])->findOrFail($id);
            
            \Log::info("Purchase before update:", [
                'po_number' => $purchase->po_number,
                'status' => $purchase->status,
                'finance_notes' => $purchase->finance_notes
            ]);
            
            DB::beginTransaction();
            
            // PERBAIKAN: Hanya update kolom yang ADA di tabel
            $updateData = [
                'status' => 'approved',
                'finance_notes' => $request->input('finance_notes', $purchase->finance_notes),
                'tracking_number' => $request->input('tracking_number', $purchase->tracking_number),
                'resi_number' => $request->input('resi_number', $purchase->resi_number),
            ];
            
            // Hanya tambahkan approved_at jika kolomnya ada
            if (Schema::hasColumn('indo_purchases', 'approved_at')) {
                $updateData['approved_at'] = now();
            }
            
            // Hanya tambahkan approved_by jika kolomnya ada  
            if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                $updateData['approved_by'] = Auth::id();
            }
            
            \Log::info("Updating purchase with data:", $updateData);
            
            $purchase->update($updateData);
            
            \Log::info("Purchase after update:", [
                'status' => $purchase->status,
                'updated_at' => $purchase->updated_at
            ]);
            
            // Buat DCM costing TANPA field pic
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
                // FIELD pic DIHAPUS karena sudah tidak ada di database
                'status' => 'approved',
                'item_status' => 'pending',
                'finance_notes' => $request->input('finance_notes', ''),
                'tracking_number' => $request->input('tracking_number', ''),
                'resi_number' => $request->input('resi_number', ''),
                'approved_at' => now(),
            ];
            
            \Log::info("Creating DCM costing with data:", $dcmData);
            
            $costing = DcmCosting::create($dcmData);
            
            \Log::info("DCM Costing created with ID: " . $costing->id);
            
            DB::commit();
            
            \Log::info("=== APPROVE SUCCESS ===");
            
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
            \Log::error('Trace: ' . $e->getTraceAsString());
            
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
            
            $purchase = ProjectPurchase::with(['department', 'supplier', 'pic', 'material', 'jobOrder'])->findOrFail($id);
            
            DB::beginTransaction();
            
            // PERBAIKAN: Hanya update kolom yang ADA
            $updateData = [
                'status' => 'rejected',
                'finance_notes' => $request->finance_notes,
            ];
            
            $purchase->update($updateData);
            
            // Create DCM costing rejected TANPA field pic
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
                // FIELD pic DIHAPUS
                'status' => 'rejected',
                'item_status' => 'not_received',
                'finance_notes' => $request->finance_notes,
                'approved_at' => now(),
            ];
            
            DcmCosting::create($dcmData);
            
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
     * Get statistics for dashboard
     */
    public function statistics()
    {
        $totalPending = ProjectPurchase::where('status', 'pending')->count();
        $thisMonth = ProjectPurchase::where('status', 'pending')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $totalAmount = ProjectPurchase::where('status', 'pending')->sum('invoice_total');
        $avgProcessingDays = ProjectPurchase::where('status', 'pending')
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
        $purchase = ProjectPurchase::with([
            'department', 'supplier', 'pic', 'material',
            'project', 'internalProject', 'jobOrder',
            'category', 'unit'
        ])->findOrFail($id);
        
        // PASTIKAN VIEW PATH INI BENAR:
        // Jika view ada di resources/views/finance/purchase-approvals/details.blade.php
        return view('finance.purchase-approvals.details', compact('purchase'));
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
                    $purchase = ProjectPurchase::with(['department', 'supplier', 'pic', 'material', 'jobOrder'])->findOrFail($purchaseId);
                    
                    // PERBAIKAN: Hanya update kolom yang ADA
                    $updateData = [
                        'status' => 'approved',
                        'finance_notes' => $request->finance_notes,
                    ];
                    
                    // Hanya tambahkan jika kolom ada
                    if (Schema::hasColumn('indo_purchases', 'approved_at')) {
                        $updateData['approved_at'] = now();
                    }
                    
                    if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                        $updateData['approved_by'] = Auth::id();
                    }
                    
                    $purchase->update($updateData);
                    
                    // Create DCM costing TANPA field pic
                    DcmCosting::create([
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
                        // FIELD pic DIHAPUS
                        'status' => 'approved',
                        'item_status' => 'pending',
                        'finance_notes' => $request->finance_notes,
                        'approved_at' => now(),
                    ]);
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
}