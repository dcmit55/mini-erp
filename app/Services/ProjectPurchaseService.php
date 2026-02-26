<?php

namespace App\Services;

use App\Models\Procurement\ProjectPurchase;
use App\Models\InternalProject;
use App\Models\Logistic\Inventory;
use App\Models\Admin\Department;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProjectPurchaseService
{
    /**
     * GET PURCHASES WITH FILTERS - GROUPED BY PO NUMBER
     */
    public function getPurchasesWithFilters(Request $request, $paginate = true)
    {
        try {
            // Build base query untuk mendapatkan PO numbers yang unik
            $poNumbersQuery = ProjectPurchase::current()
                ->select('po_number', 'department_id', 'project_type', 'supplier_id', 
                         'status', 'date', 'created_at')
                ->distinct('po_number');
            
            // Apply filters
            if ($request->filled('status')) {
                $poNumbersQuery->where('status', $request->status);
            }
            
            if ($request->filled('item_status')) {
                // Filter by item status - cek apakah ada item dengan status tertentu dalam PO
                $poNumbersQuery->whereExists(function($query) use ($request) {
                    $query->select(DB::raw(1))
                          ->from('project_purchases as pp2')
                          ->whereRaw('pp2.po_number = project_purchases.po_number')
                          ->where('pp2.is_current', true)
                          ->where('pp2.item_status', $request->item_status);
                });
            }
            
            if ($request->filled('department_id')) {
                $poNumbersQuery->where('department_id', $request->department_id);
            }
            
            if ($request->filled('project_type')) {
                $poNumbersQuery->where('project_type', $request->project_type);
            }
            
            if ($request->filled('supplier_id')) {
                $poNumbersQuery->where('supplier_id', $request->supplier_id);
            }
            
            if ($request->filled('date')) {
                $poNumbersQuery->whereDate('date', $request->date);
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $poNumbersQuery->where(function($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                      ->orWhere('resi_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('project', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('internalProject', function($q) use ($search) {
                          $q->where('job', 'like', "%{$search}%")
                            ->orWhere('project', 'like', "%{$search}%");
                      });
                });
            }
            
            // Get unique PO numbers dengan ordering
            $poNumbers = $poNumbersQuery->orderBy('created_at', 'desc')
                ->pluck('po_number')
                ->unique()
                ->values();
            
            // Build collection of purchases (one per PO)
            $purchases = collect();
            foreach ($poNumbers as $poNumber) {
                $firstItem = ProjectPurchase::current()
                    ->with([
                        'material:id,name',
                        'department:id,name',
                        'category:id,name',
                        'unit:id,name',
                        'supplier:id,name',
                        'pic:id,username',
                        'project:id,name',
                        'internalProject:id,project,job,department,department_id',
                        'jobOrder:id,name',
                        'approver:id,username'
                    ])
                    ->where('po_number', $poNumber)
                    ->first();
                
                if ($firstItem) {
                    // Add status badge classes for view
                    $firstItem->status_badge_class = $this->getStatusBadgeClass($firstItem->status);
                    $firstItem->status_text = $this->getStatusText($firstItem->status);
                    
                    // Add group info
                    $firstItem->group_info = $this->getGroupInfo($poNumber);
                    
                    $purchases->push($firstItem);
                }
            }
            
            // Sort by created_at desc (already ordered, but ensure)
            $purchases = $purchases->sortByDesc('created_at')->values();
            
            if ($paginate) {
                return $this->paginateCollection($purchases, $request);
            }
            
            return $purchases;
            
        } catch (\Exception $e) {
            Log::error('Error in getPurchasesWithFilters: ' . $e->getMessage());
            
            // Return empty collection on error
            if ($paginate) {
                return new LengthAwarePaginator([], 0, 20, $request->page ?? 1);
            }
            
            return collect();
        }
    }
    
    /**
     * Paginate collection manually
     */
    private function paginateCollection(Collection $items, Request $request)
    {
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        
        $currentPageItems = $items->slice($offset, $perPage)->values();
        
        return new LengthAwarePaginator(
            $currentPageItems,
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
    
    /**
     * Get status badge class
     */
    private function getStatusBadgeClass($status)
    {
        return match($status) {
            'pending' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary'
        };
    }
    
    /**
     * Get status text
     */
    private function getStatusText($status)
    {
        return match($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($status)
        };
    }
    
    /**
     * GET GROUP INFORMATION FOR PO NUMBER
     */
    public function getGroupInfo($poNumber)
    {
        try {
            $items = ProjectPurchase::where('po_number', $poNumber)
                ->where('is_current', true)
                ->with(['material:id,name', 'category:id,name', 'unit:id,name'])
                ->get();
            
            if ($items->isEmpty()) {
                return [
                    'total_items' => 0,
                    'total_quantity' => 0,
                    'total_amount' => 0,
                    'received_count' => 0,
                    'not_matched_count' => 0,
                    'pending_count' => 0,
                    'materials' => []
                ];
            }
            
            $receivedCount = $items->where('item_status', 'matched')->count();
            $notMatchedCount = $items->where('item_status', 'not_matched')->count();
            $pendingCount = $items->whereIn('item_status', ['pending_check', 'pending'])->count();
            
            return [
                'total_items' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_amount' => $items->sum('invoice_total'),
                'received_count' => $receivedCount,
                'not_matched_count' => $notMatchedCount,
                'pending_count' => $pendingCount,
                'materials' => $items->map(function($item) {
                    return [
                        'name' => $item->purchase_type === 'restock' 
                            ? ($item->material->name ?? 'Unknown') 
                            : ($item->new_item_name ?? 'Unknown'),
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->total_price,
                        'category' => $item->category->name ?? '-',
                        'unit' => $item->unit->name ?? '-'
                    ];
                })
            ];
            
        } catch (\Exception $e) {
            Log::error("Error getting group info for PO {$poNumber}: " . $e->getMessage());
            
            return [
                'total_items' => 1,
                'total_quantity' => 0,
                'total_amount' => 0,
                'received_count' => 0,
                'not_matched_count' => 0,
                'pending_count' => 1,
                'materials' => [
                    ['name' => 'Error loading items', 'quantity' => 0]
                ]
            ];
        }
    }
    
    /**
     * GET PURCHASE STATISTICS - GROUPED BY PO NUMBER
     */
    public function getPurchaseStats()
    {
        try {
            // Get unique PO numbers
            $uniquePOs = ProjectPurchase::current()
                ->select('po_number')
                ->distinct()
                ->get()
                ->pluck('po_number');
            
            $stats = [
                'total' => $uniquePOs->count(),
                'total_amount' => 0,
                'pending' => 0,
                'rejected' => 0,
                'approved' => 0,
                'received' => 0,
                'pending_check' => 0,
                'not_matched' => 0,
                'today' => 0,
                'client_projects' => 0,
                'internal_projects' => 0,
            ];
            
            foreach ($uniquePOs as $poNumber) {
                $poItems = ProjectPurchase::where('po_number', $poNumber)
                    ->where('is_current', true)
                    ->get();
                
                if ($poItems->isEmpty()) continue;
                
                // Use first item for PO-level info
                $firstItem = $poItems->first();
                
                // Count by status
                if ($firstItem->status === 'pending') $stats['pending']++;
                if ($firstItem->status === 'rejected') $stats['rejected']++;
                if ($firstItem->status === 'approved') $stats['approved']++;
                
                // Count by receipt status
                $allReceived = $poItems->every(function($item) {
                    return $item->item_status === 'matched';
                });
                
                $anyNotMatched = $poItems->contains(function($item) {
                    return $item->item_status === 'not_matched';
                });
                
                $anyPending = $poItems->contains(function($item) {
                    return in_array($item->item_status, ['pending_check', 'pending']);
                });
                
                if ($allReceived) $stats['received']++;
                if ($anyNotMatched) $stats['not_matched']++;
                if ($anyPending) $stats['pending_check']++;
                
                // Total amount for all items in PO
                $stats['total_amount'] += $poItems->sum('invoice_total');
                
                // Today's PO
                if ($firstItem->created_at->isToday()) $stats['today']++;
                
                // Project type
                if ($firstItem->project_type === 'client') $stats['client_projects']++;
                if ($firstItem->project_type === 'internal') $stats['internal_projects']++;
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Error in getPurchaseStats: ' . $e->getMessage());
            
            return [
                'total' => 0,
                'total_amount' => 0,
                'pending' => 0,
                'rejected' => 0,
                'approved' => 0,
                'received' => 0,
                'pending_check' => 0,
                'not_matched' => 0,
                'today' => 0,
                'client_projects' => 0,
                'internal_projects' => 0,
            ];
        }
    }
    
    /**
     * GET INTERNAL PROJECT DETAILS
     */
    public function getInternalProjectDetails($id)
    {
        try {
            $project = InternalProject::with(['department:id,name'])->find($id);
            
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Internal Project tidak ditemukan',
                    'project' => null,
                    'department' => null,
                    'job' => null,
                    'description' => null,
                    'department_id' => null
                ];
            }
            
            return [
                'success' => true,
                'project' => $project->project,
                'department' => $project->department,
                'job' => $project->job,
                'description' => $project->description,
                'department_id' => $project->department_id ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('Get internal project details error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'project' => null,
                'department' => null,
                'job' => null,
                'description' => null,
                'department_id' => null
            ];
        }
    }
    
    /**
     * VALIDATE PURCHASE REQUEST - DEPARTMENT TIDAK REQUIRE
     */
    public function validatePurchaseRequest(Request $request, $purchaseId = null)
    {
        Log::info('Validating purchase request:', $request->all());
        
        $rules = [
            'po_number' => 'required|string|max:50',
            'date' => 'required|date',
            // DEPARTMENT TIDAK REQUIRE - HAPUS VALIDASI
            // 'department_id' => 'required|exists:departments,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'is_offline_order' => 'sometimes|boolean',
            'freight' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'project_type' => 'required|in:client,internal',
            'purchase_type' => 'required|in:restock,new_item',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ];

        // Validasi berdasarkan purchase_type
        if ($request->purchase_type === 'restock') {
            $rules['material_id'] = 'required|exists:inventories,id';
        } else { // new_item
            $rules['new_item_name'] = 'nullable|required|string|max:255';
            $rules['category_id'] = 'nullable|required|exists:categories,id';
            $rules['unit_id'] = 'nullable|required|exists:units,id';
        }

        // Validasi berdasarkan project type
        if ($request->project_type === 'client') {
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['job_order_id'] = 'required|exists:job_orders,id';
        } else {
            $rules['internal_project_id'] = 'required|exists:internal_projects,id';
        }

        $messages = [
            'po_number.required' => 'Nomor PO harus diisi',
            'date.required' => 'Tanggal harus diisi',
            // HAPUS MESSAGE DEPARTMENT
            // 'department_id.required' => 'Department harus dipilih',
            'supplier_id.required' => 'Supplier harus dipilih',
            'project_type.required' => 'Tipe project harus dipilih',
            'purchase_type.required' => 'Tipe pembelian harus dipilih',
            'quantity.required' => 'Jumlah harus diisi',
            'quantity.min' => 'Jumlah minimal 0.01',
            'unit_price.required' => 'Harga satuan harus diisi',
            'unit_price.min' => 'Harga satuan tidak boleh negatif',
            'material_id.required' => 'Material harus dipilih untuk restock',
            'material_id.exists' => 'Material tidak valid',
            'new_item_name.required' => 'Nama item baru harus diisi',
            'category_id.required' => 'Kategori harus dipilih untuk item baru',
            'category_id.exists' => 'Kategori tidak valid',
            'unit_id.required' => 'Satuan harus dipilih untuk item baru',
            'unit_id.exists' => 'Satuan tidak valid',
            'project_id.required' => 'Project harus dipilih untuk client project',
            'project_id.exists' => 'Project tidak valid',
            'job_order_id.required' => 'Job Order harus dipilih untuk client project',
            'job_order_id.exists' => 'Job Order tidak valid',
            'internal_project_id.required' => 'Internal project harus dipilih',
            'internal_project_id.exists' => 'Internal project tidak valid',
        ];

        $validated = $request->validate($rules, $messages);
        
        // Hapus job_order_id dari validated data jika project_type = internal
        if ($validated['project_type'] === 'internal' && isset($validated['job_order_id'])) {
            unset($validated['job_order_id']);
            unset($validated['project_id']);
        }
        
        // Hapus internal_project_id jika project_type = client
        if ($validated['project_type'] === 'client' && isset($validated['internal_project_id'])) {
            unset($validated['internal_project_id']);
        }
        
        Log::info('Validation completed successfully', ['validated_data' => $validated]);
        
        return $validated;
    }
    
    /**
     * VALIDATE FOREIGN KEYS - Department tidak wajib
     */
    private function validateForeignKeys(array $data)
    {
        $errors = [];
        
        // Department ID tidak wajib, hanya validasi jika ada
        if (isset($data['department_id']) && !empty($data['department_id'])) {
            if (!DB::table('departments')->where('id', $data['department_id'])->exists()) {
                $errors[] = "Department ID {$data['department_id']} tidak valid";
            }
        }
        
        if (isset($data['supplier_id']) && !DB::table('suppliers')->where('id', $data['supplier_id'])->exists()) {
            $errors[] = "Supplier ID {$data['supplier_id']} tidak valid";
        }
        
        if (isset($data['purchase_type']) && $data['purchase_type'] === 'restock' && isset($data['material_id'])) {
            if (!DB::table('inventories')->where('id', $data['material_id'])->exists()) {
                $errors[] = "Material ID {$data['material_id']} tidak valid";
            }
        }
        
        if (isset($data['project_type']) && $data['project_type'] === 'client') {
            if (isset($data['project_id']) && !DB::table('projects')->where('id', $data['project_id'])->exists()) {
                $errors[] = "Project ID {$data['project_id']} tidak valid";
            }
            if (isset($data['job_order_id']) && !DB::table('job_orders')->where('id', $data['job_order_id'])->exists()) {
                $errors[] = "Job Order ID {$data['job_order_id']} tidak valid";
            }
        }
        
        if (isset($data['project_type']) && $data['project_type'] === 'internal' && isset($data['internal_project_id'])) {
            if (!DB::table('internal_projects')->where('id', $data['internal_project_id'])->exists()) {
                $errors[] = "Internal Project ID {$data['internal_project_id']} tidak valid";
            }
        }
        
        if (isset($data['purchase_type']) && $data['purchase_type'] === 'new_item') {
            if (isset($data['category_id']) && !DB::table('categories')->where('id', $data['category_id'])->exists()) {
                $errors[] = "Category ID {$data['category_id']} tidak valid";
            }
            if (isset($data['unit_id']) && !DB::table('units')->where('id', $data['unit_id'])->exists()) {
                $errors[] = "Unit ID {$data['unit_id']} tidak valid";
            }
        }
        
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
    }
    
    /**
     * GET VALID PIC ID
     */
    private function getValidPicId()
    {
        if (auth()->check()) {
            $userId = auth()->id();
            
            $exists = DB::table('employees')->where('id', $userId)->exists();
            if ($exists) {
                return $userId;
            }
            
            $userExists = DB::table('users')->where('id', $userId)->exists();
            if ($userExists) {
                return $userId;
            }
        }
        
        $firstEmployee = DB::table('employees')->orderBy('id')->first();
        if ($firstEmployee) {
            return $firstEmployee->id;
        }
        
        $firstUser = DB::table('users')->orderBy('id')->first();
        if ($firstUser) {
            return $firstUser->id;
        }
        
        throw new \Exception('Tidak ada PIC yang valid. Silakan tambahkan data employee/user terlebih dahulu.');
    }
    
    /**
     * CREATE PURCHASE - Satu record per item
     */
    public function createPurchase(array $data)
    {
        DB::beginTransaction();
        
        try {
            Log::info('Creating purchase with data:', $data);
            
            $this->validateForeignKeys($data);
            
            $purchaseData = $this->preparePurchaseData($data);
            $purchaseData['pic_id'] = $this->getValidPicId();
            $purchaseData['status'] = 'pending';
            $purchaseData['item_status'] = 'pending_check';
            $purchaseData['is_current'] = true;
            
            // Calculate totals
            $purchaseData['total_price'] = $purchaseData['quantity'] * $purchaseData['unit_price'];
            $purchaseData['invoice_total'] = $purchaseData['total_price'] + ($purchaseData['freight'] ?? 0) + ($purchaseData['other_costs'] ?? 0);
            
            Log::info('Final data for purchase creation:', $purchaseData);
            
            $purchase = ProjectPurchase::create($purchaseData);
            
            Log::info('Purchase created successfully:', [
                'id' => $purchase->id,
                'po_number' => $purchase->po_number,
                'pic_id' => $purchase->pic_id
            ]);
            
            DB::commit();
            return $purchase;
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error in createPurchase: ' . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                if (str_contains($e->getMessage(), 'department_id')) {
                    throw new \Exception('Department yang dipilih tidak valid. Silakan pilih department lain.');
                }
                if (str_contains($e->getMessage(), 'supplier_id')) {
                    throw new \Exception('Supplier yang dipilih tidak valid. Silakan pilih supplier lain.');
                }
                if (str_contains($e->getMessage(), 'pic_id')) {
                    throw new \Exception('PIC tidak valid. Silakan login ulang atau hubungi administrator.');
                }
                throw new \Exception('Terjadi kesalahan foreign key constraint. Periksa kembali data Anda.');
            }
            
            throw $e;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create purchase error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * PREPARE PURCHASE DATA
     */
    private function preparePurchaseData(array $data)
    {
        $purchaseData = [
            'po_number' => $data['po_number'],
            'date' => $data['date'],
            'purchase_type' => $data['purchase_type'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'department_id' => $data['department_id'] ?? null, // Bisa null
            'supplier_id' => $data['supplier_id'],
            'is_offline_order' => $data['is_offline_order'] ?? false,
            'freight' => $data['freight'] ?? 0,
            'other_costs' => $data['other_costs'] ?? 0,
            'note' => $data['note'] ?? null,
            'project_type' => $data['project_type'],
        ];

        // Handle based on purchase type
        if ($data['purchase_type'] === 'restock') {
            $purchaseData['material_id'] = $data['material_id'];
            $purchaseData['new_item_name'] = null;
            $purchaseData['category_id'] = $data['category_id'] ?? null;
            $purchaseData['unit_id'] = $data['unit_id'] ?? null;
        } else {
            $purchaseData['material_id'] = null;
            $purchaseData['new_item_name'] = $data['new_item_name'];
            $purchaseData['category_id'] = $data['category_id'];
            $purchaseData['unit_id'] = $data['unit_id'];
        }

        // Handle based on project type
        if ($data['project_type'] === 'client') {
            $purchaseData['project_id'] = $data['project_id'];
            $purchaseData['job_order_id'] = $data['job_order_id'];
            $purchaseData['internal_project_id'] = null;
        } else {
            $purchaseData['internal_project_id'] = $data['internal_project_id'];
            $purchaseData['project_id'] = null;
            $purchaseData['job_order_id'] = null;
        }

        return $purchaseData;
    }
    
    /**
     * UPDATE PURCHASE - Create new revision
     */
    public function updatePurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            Log::info('🔄 UPDATE PURCHASE', [
                'old_record_id' => $purchase->id,
                'po_number' => $purchase->po_number
            ]);

            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa diupdate.');
            }

            $this->validateForeignKeys($data);

            $quantity = $data['quantity'] ?? $purchase->quantity;
            $unitPrice = $data['unit_price'] ?? $purchase->unit_price;
            $freight = $data['freight'] ?? $purchase->freight ?? 0;
            $otherCosts = $data['other_costs'] ?? $purchase->other_costs ?? 0;
            
            $totalPrice = $quantity * $unitPrice;
            $invoiceTotal = $totalPrice + $freight + $otherCosts;
            
            $picId = $this->getValidPicId();
            
            $newData = [
                'po_number' => $data['po_number'] ?? $purchase->po_number,
                'date' => $data['date'] ?? $purchase->date,
                'purchase_type' => $data['purchase_type'] ?? $purchase->purchase_type,
                'material_id' => $this->handleMaterialId($purchase, $data),
                'new_item_name' => $data['new_item_name'] ?? $purchase->new_item_name,
                'quantity' => $quantity,
                'actual_quantity' => $data['actual_quantity'] ?? $purchase->actual_quantity,
                'unit_price' => $unitPrice,
                'department_id' => $data['department_id'] ?? $purchase->department_id,
                'project_type' => $data['project_type'] ?? $purchase->project_type,
                'project_id' => $this->handleProjectId($purchase, $data),
                'internal_project_id' => $this->handleInternalProjectId($purchase, $data),
                'job_order_id' => $this->handleJobOrderId($purchase, $data),
                'category_id' => $data['category_id'] ?? $purchase->category_id,
                'unit_id' => $data['unit_id'] ?? $purchase->unit_id,
                'supplier_id' => $data['supplier_id'] ?? $purchase->supplier_id,
                'is_offline_order' => $data['is_offline_order'] ?? $purchase->is_offline_order,
                'pic_id' => $picId,
                'resi_number' => $data['resi_number'] ?? $purchase->resi_number,
                'total_price' => $totalPrice,
                'freight' => $freight,
                'other_costs' => $otherCosts,
                'invoice_total' => $invoiceTotal,
                'status' => $data['status'] ?? $purchase->status,
                'item_status' => $data['item_status'] ?? $purchase->item_status,
                'checked_at' => $data['checked_at'] ?? $purchase->checked_at,
                'checked_by' => $data['checked_by'] ?? $purchase->checked_by,
                'note' => $this->handleNote($purchase, $data),
                'finance_notes' => $data['finance_notes'] ?? $purchase->finance_notes,
                'approved_at' => $data['approved_at'] ?? $purchase->approved_at,
                'approved_by' => $data['approved_by'] ?? $purchase->approved_by,
                'received_at' => $data['received_at'] ?? $purchase->received_at,
                'received_by' => $data['received_by'] ?? $purchase->received_by,
                'is_current' => true,
                'revision_at' => now(),
            ];
            
            // Reset approval if status changed back to pending
            if (isset($data['status']) && $data['status'] === 'pending') {
                $newData['approved_at'] = null;
                $newData['approved_by'] = null;
                $newData['checked_at'] = null;
                $newData['checked_by'] = null;
                $newData['received_at'] = null;
                $newData['received_by'] = null;
            }
            
            // Mark old record as not current
            $purchase->update([
                'is_current' => false,
                'updated_at' => now()
            ]);
            
            // Create new revision
            $newRevision = ProjectPurchase::create($newData);
            
            Log::info('✅ NEW REVISION CREATED', [
                'old_record_id' => $purchase->id,
                'new_record_id' => $newRevision->id,
                'po_number' => $newRevision->po_number
            ]);
            
            DB::commit();
            
            return $newRevision;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Update purchase error', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Handle material ID for update
     */
    private function handleMaterialId(ProjectPurchase $purchase, array $data)
    {
        $purchaseType = $data['purchase_type'] ?? $purchase->purchase_type;
        
        if ($purchaseType === 'new_item') {
            return null;
        }
        
        return $data['material_id'] ?? $purchase->material_id;
    }
    
    /**
     * Handle project ID for update
     */
    private function handleProjectId(ProjectPurchase $purchase, array $data)
    {
        $projectType = $data['project_type'] ?? $purchase->project_type;
        
        if ($projectType === 'internal') {
            return null;
        }
        
        return $data['project_id'] ?? $purchase->project_id;
    }
    
    /**
     * Handle internal project ID for update
     */
    private function handleInternalProjectId(ProjectPurchase $purchase, array $data)
    {
        $projectType = $data['project_type'] ?? $purchase->project_type;
        
        if ($projectType === 'client') {
            return null;
        }
        
        return $data['internal_project_id'] ?? $purchase->internal_project_id;
    }
    
    /**
     * Handle job order ID for update
     */
    private function handleJobOrderId(ProjectPurchase $purchase, array $data)
    {
        $projectType = $data['project_type'] ?? $purchase->project_type;
        
        if ($projectType === 'internal') {
            return null;
        }
        
        return $data['job_order_id'] ?? $purchase->job_order_id;
    }
    
    /**
     * Handle note with revision tracking
     */
    private function handleNote(ProjectPurchase $purchase, array $data)
    {
        $newNote = $data['note'] ?? '';
        $oldNote = $purchase->note ?? '';
        
        if (!empty($newNote) && $newNote !== $oldNote) {
            $timestamp = now()->format('d/m/Y H:i:s');
            return "[Revisi {$timestamp}]\n" . 
                   $newNote . "\n\n" . 
                   "--- Sebelumnya ---\n" . 
                   $oldNote;
        }
        
        return $oldNote;
    }
    
    /**
     * APPROVE PURCHASE
     */
    public function approvePurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa diapprove.');
            }
            
            $purchase->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'finance_notes' => $data['finance_notes'] ?? null,
                'resi_number' => $data['resi_number'] ?? $purchase->resi_number,
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * REJECT PURCHASE
     */
    public function rejectPurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa direject.');
            }
            
            $purchase->update([
                'status' => 'rejected',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'finance_notes' => $data['finance_notes'],
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * UPDATE RESI NUMBER
     */
    public function updateResiNumber(ProjectPurchase $purchase, array $data)
    {
        if (!$purchase->is_current) {
            throw new \Exception('Hanya current revision yang bisa update resi number.');
        }
        
        $updateData = [];
        
        if (isset($data['resi_number'])) {
            $updateData['resi_number'] = $data['resi_number'];
        }
        
        if (!empty($updateData)) {
            $purchase->update($updateData);
        }
    }
    
    /**
     * MARK AS CHECKED
     */
    public function markAsChecked(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa di-mark as checked.');
            }
            
            $updateData = [
                'item_status' => $data['item_status'],
                'checked_at' => now(),
                'checked_by' => auth()->id(),
            ];
            
            if (isset($data['actual_quantity'])) {
                $updateData['actual_quantity'] = $data['actual_quantity'];
            }
            
            if (isset($data['note'])) {
                $updateData['note'] = $purchase->note ? $purchase->note . "\n[Checked] " . $data['note'] : $data['note'];
            }
            
            if ($data['item_status'] === 'matched') {
                $this->insertToInventory($purchase);
            }
            
            $purchase->update($updateData);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * MARK AS RECEIVED
     */
    public function markAsReceived(ProjectPurchase $purchase)
    {
        DB::beginTransaction();
        
        try {
            Log::info('Starting markAsReceived process', [
                'purchase_id' => $purchase->id,
                'po_number' => $purchase->po_number,
                'status' => $purchase->status,
                'item_status' => $purchase->item_status
            ]);

            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa di-mark as received.');
            }

            if ($purchase->status !== 'approved') {
                throw new \Exception('PO harus disetujui terlebih dahulu. Status saat ini: ' . $purchase->status);
            }

            if (!in_array($purchase->item_status, ['pending_check', 'pending'])) {
                throw new \Exception('Barang sudah ditandai: ' . $purchase->item_status);
            }

            $purchase->update([
                'item_status' => 'matched',
                'received_at' => now(),
                'received_by' => auth()->id(),
            ]);

            Log::info('Purchase updated successfully', [
                'new_item_status' => $purchase->item_status,
                'received_at' => $purchase->received_at
            ]);

            $this->insertToInventory($purchase);

            DB::commit();
            
            Log::info('markAsReceived completed successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('markAsReceived error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * INSERT TO INVENTORY
     */
    private function insertToInventory(ProjectPurchase $purchase)
    {
        try {
            $unitName = $purchase->unit ? $purchase->unit->name : 'pcs';
            
            if ($purchase->purchase_type === 'restock' && $purchase->material_id) {
                $inventory = Inventory::find($purchase->material_id);
                if ($inventory) {
                    $inventory->quantity += $purchase->quantity;
                    $inventory->updated_at = now();
                    $inventory->save();
                    
                    Log::info('Inventory updated for restock', [
                        'material_id' => $inventory->id,
                        'old_quantity' => $inventory->quantity - $purchase->quantity,
                        'new_quantity' => $inventory->quantity,
                        'added' => $purchase->quantity
                    ]);
                }
            } else {
                $inventoryData = [
                    'name' => $purchase->new_item_name,
                    'quantity' => $purchase->quantity,
                    'unit_id' => $purchase->unit_id,
                    'unit' => $unitName,
                    'price' => $purchase->unit_price,
                    'supplier_id' => $purchase->supplier_id,
                    'category_id' => $purchase->category_id,
                    'remark' => $purchase->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                $newInventory = Inventory::create($inventoryData);
                
                Log::info('New inventory item created', [
                    'inventory_id' => $newInventory->id,
                    'name' => $newInventory->name,
                    'quantity' => $newInventory->quantity
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error inserting to inventory: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * MARK AS NOT MATCHED
     */
    public function markAsNotMatched(ProjectPurchase $purchase)
    {
        if (!$purchase->is_current) {
            throw new \Exception('Hanya current revision yang bisa di-mark as not matched.');
        }
        
        $purchase->update([
            'item_status' => 'not_matched',
            'checked_at' => now(),
            'checked_by' => auth()->id(),
        ]);
    }
    
    /**
     * EXPORT TO EXCEL
     */
    public function exportToExcel($purchases)
    {
        try {
            // Group purchases by PO number for export
            $groupedPurchases = $purchases->groupBy('po_number');
            
            $exportData = [];
            foreach ($groupedPurchases as $poNumber => $items) {
                $firstItem = $items->first();
                
                foreach ($items as $index => $item) {
                    $exportData[] = [
                        'PO Number' => $poNumber,
                        'Date' => $firstItem->date->format('d/m/Y'),
                        'Project Type' => ucfirst($firstItem->project_type),
                        'Purchase Type' => $item->purchase_type === 'restock' ? 'Restock' : 'Item Baru',
                        'Material/Item Name' => $item->purchase_type === 'restock' 
                            ? ($item->material->name ?? 'Unknown') 
                            : ($item->new_item_name ?? 'Unknown'),
                        'Quantity' => $item->quantity,
                        'Unit Price' => 'Rp ' . number_format($item->unit_price, 0),
                        'Total Price' => 'Rp ' . number_format($item->total_price, 0),
                        'Supplier' => $firstItem->supplier->name ?? 'Unknown',
                        'Department' => $firstItem->department->name ?? 'Unknown',
                        'Status PO' => ucfirst($firstItem->status),
                        'Status Item' => $this->getItemStatusText($item->item_status),
                        'Resi Number' => $firstItem->resi_number ?? '-',
                        'Created At' => $firstItem->created_at->format('d/m/Y H:i:s')
                    ];
                }
            }
            
            return [
                'success' => true,
                'data' => $exportData,
                'headers' => [
                    'PO Number',
                    'Date',
                    'Project Type',
                    'Purchase Type',
                    'Material/Item Name',
                    'Quantity',
                    'Unit Price',
                    'Total Price',
                    'Supplier',
                    'Department',
                    'Status PO',
                    'Status Item',
                    'Resi Number',
                    'Created At'
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Export to Excel error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get item status text
     */
    private function getItemStatusText($status)
    {
        return match($status) {
            'pending' => 'Pending',
            'pending_check' => 'Pending Check',
            'matched' => 'Received',
            'not_matched' => 'Not Matched',
            default => ucfirst($status)
        };
    }
    
    /**
     * GET ALL ITEMS FOR A PO NUMBER
     */
    public function getPOItems($poNumber)
    {
        return ProjectPurchase::where('po_number', $poNumber)
            ->where('is_current', true)
            ->with(['material', 'unit', 'category', 'supplier'])
            ->get();
    }
    
    /**
     * UPDATE STATUS FOR ALL ITEMS IN A PO
     */
    public function updatePOStatus($poNumber, $status, $itemStatus = null)
    {
        $items = ProjectPurchase::where('po_number', $poNumber)
            ->where('is_current', true)
            ->get();
        
        foreach ($items as $item) {
            $item->status = $status;
            if ($itemStatus) {
                $item->item_status = $itemStatus;
            }
            $item->save();
        }
    }
}