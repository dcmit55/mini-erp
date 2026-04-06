<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\ProjectPurchase;
use App\Models\Finance\DcmCosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

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
        $projectType = $request->get('project_type');
        
        // Ambil semua item pending, tapi nanti akan dikelompokkan per PO
        $items = ProjectPurchase::with([
                'department', 
                'supplier', 
                'pic', 
                'material', 
                'jobOrder',
                'project',
                'internalProject'
            ])
            ->where('status', 'pending')
            ->where('is_current', 1)
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $items->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('job_order_id', 'like', "%{$search}%")
                  ->orWhere('new_item_name', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('project', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('internalProject', function ($q2) use ($search) {
                      $q2->where('project', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($startDate && $endDate) {
            $items->whereBetween('date', [$startDate, $endDate]);
        }
        
        if ($department) {
            $items->where('department_id', $department);
        }
        
        if ($purchaseType) {
            $items->where('purchase_type', $purchaseType);
        }
        
        if ($projectType) {
            $items->where('project_type', $projectType);
        }
        
        $items = $items->get();
        
        // Kelompokkan items berdasarkan PO number
        $groupedPurchases = [];
        foreach ($items as $item) {
            $poNumber = $item->po_number;
            
            if (!isset($groupedPurchases[$poNumber])) {
                // Inisialisasi grup PO
                $groupedPurchases[$poNumber] = [
                    'po_number' => $poNumber,
                    'date' => $item->date,
                    'department' => $item->department,
                    'supplier' => $item->supplier,
                    'project_type' => $item->project_type,
                    'project' => $item->project,
                    'internalProject' => $item->internalProject,
                    'jobOrder' => $item->jobOrder,
                    'items' => [],
                    'total_items' => 0,
                    'total_quantity' => 0,
                    'total_amount' => 0,
                    'created_at' => $item->created_at,
                    'first_item_id' => $item->id, // Untuk link ke detail
                ];
            }
            
            // Tambahkan item ke grup
            $groupedPurchases[$poNumber]['items'][] = $item;
            $groupedPurchases[$poNumber]['total_items']++;
            $groupedPurchases[$poNumber]['total_quantity'] += $item->quantity;
            $groupedPurchases[$poNumber]['total_amount'] += $item->invoice_total;
        }
        
        // Ubah menjadi collection untuk pagination manual
        $purchasesCollection = collect(array_values($groupedPurchases));
        
        // Sort by created_at desc
        $purchasesCollection = $purchasesCollection->sortByDesc(function($item) {
            return $item['created_at'];
        })->values();
        
        // Pagination manual
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $pagedData = $purchasesCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $purchases = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $purchasesCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
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
            
            // Cari item yang diklik
            $purchase = ProjectPurchase::where('is_current', 1)
                ->with(['department', 'supplier', 'pic', 'material', 'jobOrder'])
                ->findOrFail($id);
            
            DB::beginTransaction();
            
            // Approve SEMUA item dengan PO number yang sama
            $poItems = ProjectPurchase::where('po_number', $purchase->po_number)
                ->where('is_current', 1)
                ->where('status', 'pending')
                ->get();
            
            $approvedCount = 0;
            foreach ($poItems as $item) {
                $updateData = [
                    'status' => 'approved',
                    'finance_notes' => $request->input('finance_notes', $item->finance_notes),
                    'resi_number' => $request->input('resi_number', $item->resi_number),
                ];
                
                if (Schema::hasColumn('indo_purchases', 'approved_at')) {
                    $updateData['approved_at'] = now();
                }
                
                if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                    $updateData['approved_by'] = Auth::id();
                }
                
                $item->update($updateData);
                
                // Create DCM costing untuk setiap item
                $this->createDcmCosting($item, 'approved', $request);
                $approvedCount++;
            }
            
            DB::commit();
            
            $message = $approvedCount . ' item(s) dalam PO ' . $purchase->po_number . ' berhasil di-approve.';
            
            return redirect()->route('purchase-approvals.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Approval error: ' . $e->getMessage());
            
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
            
            // Cari item yang diklik
            $purchase = ProjectPurchase::where('is_current', 1)
                ->with(['department', 'supplier', 'pic', 'material', 'jobOrder'])
                ->findOrFail($id);
            
            DB::beginTransaction();
            
            // Reject SEMUA item dengan PO number yang sama
            $poItems = ProjectPurchase::where('po_number', $purchase->po_number)
                ->where('is_current', 1)
                ->where('status', 'pending')
                ->get();
            
            $rejectedCount = 0;
            foreach ($poItems as $item) {
                $updateData = [
                    'status' => 'rejected',
                    'finance_notes' => $request->finance_notes,
                ];
                
                $item->update($updateData);
                
                // Create DCM costing untuk setiap item
                $this->createDcmCosting($item, 'rejected', $request);
                $rejectedCount++;
            }
            
            DB::commit();
            
            $message = $rejectedCount . ' item(s) dalam PO ' . $purchase->po_number . ' berhasil di-reject.';
            
            return redirect()->route('purchase-approvals.index')
                ->with('success', $message);
                
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
        $processedPOs = [];
        
        foreach ($request->purchase_ids as $purchaseId) {
            try {
                DB::transaction(function () use ($purchaseId, $request, &$approvedCount, &$processedPOs) {
                    $purchase = ProjectPurchase::where('is_current', 1)
                        ->with(['department', 'supplier', 'pic', 'material', 'jobOrder'])
                        ->findOrFail($purchaseId);
                    
                    // Cek apakah PO ini sudah diproses
                    if (in_array($purchase->po_number, $processedPOs)) {
                        return; // Skip, sudah diproses
                    }
                    
                    // Approve SEMUA item dengan PO number yang sama
                    $poItems = ProjectPurchase::where('po_number', $purchase->po_number)
                        ->where('is_current', 1)
                        ->where('status', 'pending')
                        ->get();
                    
                    foreach ($poItems as $item) {
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
                        
                        $item->update($updateData);
                        
                        $this->createDcmCosting($item, 'approved', $request);
                    }
                    
                    $processedPOs[] = $purchase->po_number;
                    $approvedCount += $poItems->count();
                });
                
            } catch (\Exception $e) {
                $failed[] = $purchaseId;
                \Log::error("Bulk approve failed for purchase {$purchaseId}: " . $e->getMessage());
            }
        }
        
        $message = "Berhasil approve {$approvedCount} item(s) dalam " . count($processedPOs) . " PO(s).";
        if (count($failed) > 0) {
            $message .= " Gagal: " . implode(', ', $failed);
        }
        
        return redirect()->route('purchase-approvals.index')
            ->with('success', $message);
    }
    
    /**
     * Bulk reject purchases
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'purchase_ids' => 'required|array',
            'purchase_ids.*' => 'exists:indo_purchases,id',
            'finance_notes' => 'required|string|min:5|max:1000',
        ]);

        $rejectedCount = 0;
        $failed = [];
        $processedPOs = [];

        foreach ($request->purchase_ids as $purchaseId) {
            try {
                DB::transaction(function () use ($purchaseId, $request, &$rejectedCount, &$processedPOs) {
                    $purchase = ProjectPurchase::where('is_current', 1)
                        ->findOrFail($purchaseId);

                    if (in_array($purchase->po_number, $processedPOs)) {
                        return;
                    }

                    $poItems = ProjectPurchase::where('po_number', $purchase->po_number)
                        ->where('is_current', 1)
                        ->where('status', 'pending')
                        ->get();

                    foreach ($poItems as $item) {
                        $item->update([
                            'status' => 'rejected',
                            'finance_notes' => $request->finance_notes,
                        ]);

                        $this->createDcmCosting($item, 'rejected', $request);
                    }

                    $processedPOs[] = $purchase->po_number;
                    $rejectedCount += $poItems->count();
                });

            } catch (\Exception $e) {
                $failed[] = $purchaseId;
                \Log::error("Bulk reject failed for purchase {$purchaseId}: " . $e->getMessage());
            }
        }

        $message = "Berhasil reject {$rejectedCount} item(s) dalam " . count($processedPOs) . " PO(s).";
        if (count($failed) > 0) {
            $message .= " Gagal: " . implode(', ', $failed);
        }

        return redirect()->route('purchase-approvals.index')
            ->with('success', $message);
    }

    /**
     * Deleted purchases log
     */
    public function deletedPurchases(Request $request)
    {
        $search = $request->get('search');

        $query = ProjectPurchase::onlyTrashed()
            ->with(['department', 'supplier', 'pic',
                    'material', 'project', 'internalProject', 'jobOrder', 'category', 'unit',
                    'deletionApprovedBy'])
            ->where('is_current', 1)
            ->orderBy('deleted_at', 'desc');

        if ($search) {
            $query->where('po_number', 'like', "%{$search}%");
        }

        $items = $query->get();

        // Group by PO number
        $grouped = [];
        foreach ($items as $item) {
            $po = $item->po_number;
            if (!isset($grouped[$po])) {
                $grouped[$po] = [
                    'po_number'             => $po,
                    'date'                  => $item->date,
                    'supplier'              => $item->supplier,
                    'department'            => $item->department,
                    'project_type'          => $item->project_type,
                    'project'               => $item->project,
                    'internal_project'      => $item->internalProject,
                    'job_order'             => $item->jobOrder,
                    'deletion_reason'       => $item->deletion_reason,
                    'deletion_requested_at' => $item->deletion_requested_at,
                    'deletion_approved_at'  => $item->deletion_approved_at,
                    'approved_by_user'      => $item->deletionApprovedBy,
                    'requested_by'          => $item->pic,
                    'first_item_id'         => $item->id,
                    'deleted_at'            => $item->deleted_at,
                    'total_amount'          => 0,
                    'items'                 => [],
                ];
            }
            $grouped[$po]['items'][] = [
                'name'       => $item->material_name,
                'qty'        => $item->quantity,
                'unit'       => $item->unit?->name ?? '-',
                'unit_price' => $item->unit_price,
                'total'      => $item->invoice_total,
            ];
            $grouped[$po]['total_amount'] += $item->invoice_total;
        }

        $deletedPurchases = collect(array_values($grouped));

        // Manual pagination
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $pagedData = $deletedPurchases->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData, $deletedPurchases->count(), $perPage, $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('finance.purchase-approvals.deleted-purchases', [
            'deletedPurchases' => $paginated,
            'search'           => $search,
        ]);
    }

    /**
     * Detail view for a single deletion request
     */
    public function viewDeletionDetail($id)
    {
        $purchase = ProjectPurchase::with([
                'department', 'supplier', 'pic', 'material', 'category', 'unit',
                'project', 'internalProject', 'jobOrder'
            ])
            ->where('is_current', 1)
            ->findOrFail($id);

        $poItems = ProjectPurchase::with(['material', 'category', 'unit'])
            ->where('po_number', $purchase->po_number)
            ->where('is_current', 1)
            ->get();

        return view('finance.purchase-approvals.deletion-detail', compact('purchase', 'poItems'));
    }

    /**
     * List deletion requests
     */
    public function deletionRequests()
    {
        $items = ProjectPurchase::with(['department', 'supplier', 'pic', 'material', 'project', 'internalProject', 'jobOrder', 'category', 'unit'])
            ->where('status', 'deletion_requested')
            ->where('is_current', 1)
            ->orderBy('deletion_requested_at', 'desc')
            ->get();

        // Group by PO number
        $grouped = [];
        foreach ($items as $item) {
            $po = $item->po_number;
            if (!isset($grouped[$po])) {
                $grouped[$po] = [
                    'po_number'             => $po,
                    'supplier'              => $item->supplier,
                    'department'            => $item->department,
                    'project_type'          => $item->project_type,
                    'project'               => $item->project,
                    'internal_project'      => $item->internalProject,
                    'job_order'             => $item->jobOrder,
                    'deletion_reason'       => $item->deletion_reason,
                    'deletion_requested_at' => $item->deletion_requested_at,
                    'requested_by'          => $item->pic,
                    'first_item_id'         => $item->id,
                    'date'                  => $item->date,
                    'total_amount'          => 0,
                    'items'                 => [],
                ];
            }
            $grouped[$po]['items'][] = [
                'name'       => $item->material_name,
                'qty'        => $item->quantity,
                'unit'       => $item->unit?->name ?? '-',
                'unit_price' => $item->unit_price,
                'total'      => $item->invoice_total,
            ];
            $grouped[$po]['total_amount'] += $item->invoice_total;
        }

        return view('finance.purchase-approvals.deletion-requests', [
            'deletionRequests' => collect(array_values($grouped)),
        ]);
    }

    /**
     * Approve deletion — soft delete all items in the PO
     */
    public function approveDeletion(Request $request, $id)
    {
        try {
            $purchase = ProjectPurchase::where('is_current', 1)->findOrFail($id);

            if (!$purchase->isDeleteRequested()) {
                return back()->with('error', 'Purchase ini tidak dalam status permintaan hapus.');
            }

            DB::beginTransaction();

            ProjectPurchase::where('po_number', $purchase->po_number)
                ->where('is_current', 1)
                ->get()
                ->each(function ($item) {
                    $item->deletion_approved_by = Auth::id();
                    $item->deletion_approved_at = now();
                    $item->save();
                    $item->delete();
                });

            // Soft delete DCM Costing yang terkait
            DcmCosting::where('po_number', $purchase->po_number)->delete();

            DB::commit();

            return redirect()->route('purchase-approvals.deletion-requests')
                ->with('success', 'Purchase ' . $purchase->po_number . ' berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    /**
     * Reject deletion — revert back to approved
     */
    public function rejectDeletion(Request $request, $id)
    {
        try {
            $purchase = ProjectPurchase::where('is_current', 1)->findOrFail($id);

            DB::beginTransaction();

            ProjectPurchase::where('po_number', $purchase->po_number)
                ->where('is_current', 1)
                ->update([
                    'status'                => 'approved',
                    'deletion_reason'       => null,
                    'deletion_requested_by' => null,
                    'deletion_requested_at' => null,
                ]);

            DB::commit();

            return redirect()->route('purchase-approvals.deletion-requests')
                ->with('success', 'Permintaan hapus untuk ' . $purchase->po_number . ' ditolak, status dikembalikan ke Approved.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak permintaan hapus: ' . $e->getMessage());
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics()
    {
        // Hitung jumlah PO unik yang pending
        $uniquePOs = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->select('po_number')
            ->distinct()
            ->count();
            
        $thisMonthPOs = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->select('po_number')
            ->distinct()
            ->count();
            
        $totalAmount = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->sum('invoice_total');
            
        $avgProcessingDays = ProjectPurchase::where('status', 'pending')
            ->where('is_current', 1)
            ->avg(DB::raw('DATEDIFF(NOW(), created_at)'));
        
        return response()->json([
            'total_pending' => $uniquePOs,
            'this_month' => $thisMonthPOs,
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
        
        // Ambil semua item dalam PO yang sama
        $poItems = ProjectPurchase::where('po_number', $purchase->po_number)
            ->where('is_current', 1)
            ->with(['material', 'category', 'unit'])
            ->get();
        
        return view('finance.purchase-approvals.details', compact('purchase', 'poItems'));
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
            'project_name' => $purchase->project ? $purchase->project->name : ($purchase->internalProject ? $purchase->internalProject->project : ''),
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