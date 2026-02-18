<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Procurement\ProjectPurchase;
use App\Models\Finance\DcmCosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseEditedController extends Controller
{
    /**
     * Display list of edited purchases
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        // Ambil PO yang memiliki lebih dari 1 revision DAN sudah approved
        $editedPOs = ProjectPurchase::select('po_number')
            ->whereNotNull('po_number')
            ->where('status', 'approved')
            ->groupBy('po_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('po_number');
        
        if (empty($editedPOs)) {
            $purchases = collect();
            $groupedPurchases = [];
            $dcmStatuses = [];
        } else {
            // Hanya ambil REVISI TERBARU (is_current = 1) dari setiap PO yang diedit
            $query = ProjectPurchase::with(['department', 'supplier', 'material'])
                ->whereIn('po_number', $editedPOs)
                ->where('is_current', 1)
                ->orderBy('updated_at', 'desc');
            
            if ($search) {
                $query->where('po_number', 'like', "%{$search}%");
            }
            
            $purchases = $query->paginate(20);
            
            // Untuk setiap PO, ambil juga revision sebelumnya untuk perbandingan
            $groupedPurchases = [];
            $dcmStatuses = [];
            
            foreach ($purchases as $purchase) {
                // Ambil previous revision untuk perbandingan
                $previous = ProjectPurchase::where('po_number', $purchase->po_number)
                    ->where('id', '!=', $purchase->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $groupedPurchases[$purchase->po_number] = [
                    'current' => $purchase,
                    'previous' => $previous,
                    'revision_count' => ProjectPurchase::where('po_number', $purchase->po_number)->count()
                ];
                
                // Cek status DCM Costing
                $dcmStatuses[$purchase->po_number] = $this->getDcmStatusForView($purchase->po_number);
            }
        }
        
        return view('finance.purchase-edited.index', compact(
            'purchases', 'groupedPurchases', 'search', 'dcmStatuses'
        ));
    }
    
    /**
     * Get DCM status for view
     */
    private function getDcmStatusForView($poNumber)
    {
        $currentDcm = DcmCosting::where('po_number', $poNumber)
            ->where('is_current', true)
            ->first();
        
        $currentPurchase = ProjectPurchase::where('po_number', $poNumber)
            ->where('is_current', 1)
            ->first();
        
        if (!$currentDcm) {
            return [
                'status' => 'not_exists',
                'color' => 'danger',
                'message' => 'Not in DCM',
                'dcm_id' => null
            ];
        }
        
        if (!$currentPurchase) {
            return [
                'status' => 'purchase_not_found',
                'color' => 'warning',
                'message' => 'Purchase not found',
                'dcm_id' => $currentDcm->id
            ];
        }
        
        // Check if data is synced
        $purchaseItemName = $this->getItemName($currentPurchase);
        $isSynced = 
            $currentDcm->quantity == $currentPurchase->quantity &&
            $currentDcm->unit_price == $currentPurchase->unit_price &&
            $currentDcm->total_price == $currentPurchase->total_price &&
            $currentDcm->item_name == $purchaseItemName;
        
        return [
            'status' => $isSynced ? 'synced' : 'outdated',
            'color' => $isSynced ? 'success' : 'warning',
            'message' => $isSynced ? 'Synced' : 'Outdated',
            'dcm_id' => $currentDcm->id,
            'is_synced' => $isSynced
        ];
    }
    
    /**
     * Compare revisions
     */
    public function compare($poNumber)
    {
        $revisions = ProjectPurchase::with(['department', 'supplier'])
            ->where('po_number', $poNumber)
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($revisions->count() < 2) {
            return redirect()->route('purchase-edited.index')
                ->with('error', 'Tidak ada revisi untuk PO: ' . $poNumber);
        }
        
        $current = $revisions->first();
        $previous = $revisions->skip(1)->first();
        
        // Get DCM Costing data for comparison
        $dcmCostings = DcmCosting::where('po_number', $poNumber)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('finance.purchase-edited.compare', compact(
            'current', 'previous', 'poNumber', 'revisions', 'dcmCostings'
        ));
    }
    
    /**
     * Verify and update DCM Costing - FIXED VERSION
     */
    public function verify(Request $request, $poNumber)
    {
        try {
            DB::beginTransaction();
            
            // 1. Get current purchase with relations
            $currentPurchase = ProjectPurchase::with(['department', 'supplier'])
                ->where('po_number', $poNumber)
                ->where('is_current', 1)
                ->firstOrFail();
            
            // 2. Set all old DCM Costings to not current
            DcmCosting::where('po_number', $poNumber)
                ->update(['is_current' => false]);
            
            // 3. Determine project type
            $projectType = $this->determineProjectType($currentPurchase);
            
            // 4. Create NEW DCM Costing with ALL required fields
            $dcmData = [
                'uid' => Str::uuid(),
                'purchase_id' => $currentPurchase->id,
                'po_number' => $currentPurchase->po_number,
                'date' => $currentPurchase->date ?? now(),
                'purchase_type' => $currentPurchase->purchase_type ?? 'direct',
                'item_name' => $this->getItemName($currentPurchase),
                'quantity' => $currentPurchase->quantity ?? 1,
                'unit_price' => $currentPurchase->unit_price ?? 0,
                'total_price' => $currentPurchase->total_price ?? 0,
                'freight' => $currentPurchase->freight ?? 0,
                'invoice_total' => $currentPurchase->invoice_total ?? 0,
                'department' => $currentPurchase->department ? $currentPurchase->department->name : 'N/A',
                'project_type' => $projectType,
                'supplier' => $currentPurchase->supplier ? $currentPurchase->supplier->name : 'N/A',
                'project_name' => $currentPurchase->project_name ?? null,
                'job_order' => $currentPurchase->job_order ?? null,
                'resi_number' => $currentPurchase->resi_number ?? null,
                'status' => 'approved',
                'item_status' => 'pending',
                'finance_notes' => 'Synced from edited purchase - ' . now()->format('Y-m-d H:i:s'),
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'is_current' => true,
                'created_by' => auth()->id(),
                'revision_at' => now(),
            ];
            
            $newDcmCosting = DcmCosting::create($dcmData);
            
            // 5. Update purchase reference
            $currentPurchase->update([
                'dcm_costing_id' => $newDcmCosting->id,
                'last_synced_at' => now(),
                'synced_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            \Log::info('DCM Costing updated successfully', [
                'po_number' => $poNumber,
                'purchase_id' => $currentPurchase->id,
                'dcm_id' => $newDcmCosting->id,
                'user' => auth()->user()->name ?? 'Unknown',
            ]);
            
            return redirect()->route('purchase-edited.index')
                ->with('success', '✅ PO ' . $poNumber . ' berhasil diupdate ke DCM Costing!')
                ->with('dcm_id', $newDcmCosting->id);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('DCM Update Failed', [
                'po_number' => $poNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => auth()->user()->name ?? 'Unknown',
            ]);
            
            return redirect()->route('purchase-edited.index')
                ->with('error', '❌ Gagal sinkron: ' . $e->getMessage());
        }
    }
    
    /**
     * Determine project type from purchase data
     */
    private function determineProjectType($purchase)
    {
        // Check if purchase has project_type
        if (!empty($purchase->project_type)) {
            return $purchase->project_type;
        }
        
        // Check from project name
        if (!empty($purchase->project_name)) {
            $projectName = strtolower($purchase->project_name);
            if (str_contains($projectName, 'internal')) {
                return 'internal';
            } elseif (str_contains($projectName, 'client')) {
                return 'client';
            }
        }
        
        // Default value
        return 'general';
    }
    
    /**
     * Bulk verify multiple POs
     */
    public function bulkVerify(Request $request)
    {
        $request->validate([
            'po_numbers' => 'required|array|min:1',
        ]);
        
        $successCount = 0;
        $failed = [];
        
        foreach ($request->po_numbers as $poNumber) {
            try {
                DB::transaction(function () use ($poNumber, &$successCount, &$failed) {
                    $currentPurchase = ProjectPurchase::with(['department', 'supplier'])
                        ->where('po_number', $poNumber)
                        ->where('is_current', 1)
                        ->first();
                    
                    if (!$currentPurchase) {
                        throw new \Exception("Purchase not found");
                    }
                    
                    // Update DCM Costing
                    DcmCosting::where('po_number', $poNumber)
                        ->update(['is_current' => false]);
                    
                    // Determine project type
                    $projectType = $this->determineProjectType($currentPurchase);
                    
                    DcmCosting::create([
                        'uid' => Str::uuid(),
                        'purchase_id' => $currentPurchase->id,
                        'po_number' => $currentPurchase->po_number,
                        'date' => $currentPurchase->date ?? now(),
                        'purchase_type' => $currentPurchase->purchase_type ?? 'direct',
                        'item_name' => $this->getItemName($currentPurchase),
                        'quantity' => $currentPurchase->quantity ?? 1,
                        'unit_price' => $currentPurchase->unit_price ?? 0,
                        'total_price' => $currentPurchase->total_price ?? 0,
                        'freight' => $currentPurchase->freight ?? 0,
                        'invoice_total' => $currentPurchase->invoice_total ?? 0,
                        'department' => $currentPurchase->department ? $currentPurchase->department->name : 'N/A',
                        'project_type' => $projectType,
                        'supplier' => $currentPurchase->supplier ? $currentPurchase->supplier->name : 'N/A',
                        'project_name' => $currentPurchase->project_name ?? null,
                        'status' => 'approved',
                        'item_status' => 'pending',
                        'finance_notes' => 'Bulk updated - ' . now()->format('Y-m-d H:i:s'),
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                        'is_current' => true,
                        'created_by' => auth()->id(),
                        'revision_at' => now(),
                    ]);
                    
                    $successCount++;
                });
            } catch (\Exception $e) {
                $failed[] = $poNumber . ': ' . $e->getMessage();
                \Log::error('Bulk update failed for ' . $poNumber, ['error' => $e->getMessage()]);
                continue;
            }
        }
        
        $message = "✅ Berhasil sinkron {$successCount} PO ke DCM Costing.";
        
        if (!empty($failed)) {
            $message .= " ❌ Gagal: " . implode('; ', $failed);
        }
        
        return redirect()->route('purchase-edited.index')
            ->with('success', $message);
    }
    
    /**
     * Quick check for a single PO
     */
    public function check($poNumber)
    {
        $currentPurchase = ProjectPurchase::where('po_number', $poNumber)
            ->where('is_current', 1)
            ->first();
        
        if (!$currentPurchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ]);
        }
        
        $currentDcm = DcmCosting::where('po_number', $poNumber)
            ->where('is_current', true)
            ->first();
        
        $differences = [];
        if ($currentDcm) {
            $purchaseItemName = $this->getItemName($currentPurchase);
            if ($currentDcm->quantity != $currentPurchase->quantity) {
                $differences[] = 'Quantity';
            }
            if ($currentDcm->unit_price != $currentPurchase->unit_price) {
                $differences[] = 'Unit Price';
            }
            if ($currentDcm->total_price != $currentPurchase->total_price) {
                $differences[] = 'Total Price';
            }
            if ($currentDcm->item_name != $purchaseItemName) {
                $differences[] = 'Item Name';
            }
        }
        
        return response()->json([
            'success' => true,
            'po_number' => $poNumber,
            'dcm_exists' => !is_null($currentDcm),
            'has_differences' => !empty($differences),
            'differences' => $differences,
            'needs_update' => empty($currentDcm) || !empty($differences)
        ]);
    }
    
    /**
     * Get count for badge
     */
    public function getCount()
    {
        $count = ProjectPurchase::select('po_number')
            ->whereNotNull('po_number')
            ->where('status', 'approved')
            ->groupBy('po_number')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        return response()->json(['count' => $count]);
    }
    
    /**
     * Get item name from purchase
     */
    private function getItemName(ProjectPurchase $purchase)
    {
        if (!empty($purchase->new_item_name)) {
            return $purchase->new_item_name;
        }
        
        if ($purchase->material && $purchase->material->name) {
            return $purchase->material->name;
        }
        
        return $purchase->item_name ?? 'N/A';
    }
}