<?php

namespace App\Services;

use App\Models\Procurement\ProjectPurchase; 
use App\Models\InternalProject;
use App\Models\Logistic\Inventory;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\Validator;

class ProjectPurchaseService
{
    public function getPurchasesWithFilters(Request $request, $paginate = true)
    {
        $query = ProjectPurchase::with([ 
            'material:id,name',
            'department:id,name',
            'category:id,name',
            'unit:id,name',
            'supplier:id,name',
            'pic:id,name',
            'project:id,name',
            'internalProject:id,project,job,department',
            'jobOrder:id,name,project_id,department_id'
        ]);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by item_status
        if ($request->filled('item_status')) {
            $query->where('item_status', $request->item_status);
        }
        
        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        
        // Filter by project type
        if ($request->filled('project_type')) {
            $query->where('project_type', $request->project_type);
        }
        
        // Filter by client project
        if ($request->filled('project_id') && $request->filled('project_type') && $request->project_type == 'client') {
            $query->where('project_id', $request->project_id);
        }
        
        // Filter by internal project
        if ($request->filled('internal_project_id') && $request->filled('project_type') && $request->project_type == 'internal') {
            $query->where('internal_project_id', $request->internal_project_id);
        }
        
        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        // Filter by purchase type
        if ($request->filled('purchase_type')) {
            $query->where('purchase_type', $request->purchase_type);
        }
        
        // Search by PO number, material name, supplier, etc.
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('resi_number', 'like', "%{$search}%")
                  ->orWhere('new_item_name', 'like', "%{$search}%")
                  ->orWhereHas('material', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
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
        
        // Order by latest
        $query->orderBy('created_at', 'desc');
        
        if ($paginate) {
            return $query->paginate(20);
        }
        
        return $query->get();
    }
    
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
                    'description' => null
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
                'message' => 'Terjadi kesalahan',
                'project' => null,
                'department' => null,
                'job' => null,
                'description' => null
            ];
        }
    }
    
    public function getPurchaseStats()
    {
        try {
            Log::info('=== GETTING PURCHASE STATS ===');
            
            // Debug: Cek apakah ada data
            $totalCount = ProjectPurchase::count();
            Log::info('Total Purchase Count: ' . $totalCount);
            
            // Query statistik yang sesuai dengan database (menggunakan pending_check, matched, not_matched)
            $stats = ProjectPurchase::query()
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('COALESCE(SUM(invoice_total), 0) as total_amount'),
                    DB::raw("COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending"),
                    DB::raw("COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected"),
                    DB::raw("COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) as approved"),
                    DB::raw("COALESCE(SUM(CASE WHEN item_status IN ('pending_check', 'pending') THEN 1 ELSE 0 END), 0) as pending_check"),
                    DB::raw("COALESCE(SUM(CASE WHEN item_status = 'matched' THEN 1 ELSE 0 END), 0) as matched"),
                    DB::raw("COALESCE(SUM(CASE WHEN item_status = 'not_matched' THEN 1 ELSE 0 END), 0) as not_matched"),
                    DB::raw("COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END), 0) as today"),
                    DB::raw("COALESCE(SUM(CASE WHEN project_type = 'client' THEN 1 ELSE 0 END), 0) as client_projects"),
                    DB::raw("COALESCE(SUM(CASE WHEN project_type = 'internal' THEN 1 ELSE 0 END), 0) as internal_projects")
                ])
                ->first();
            
            Log::info('Raw stats query result:', (array) $stats);
            
            // Pastikan semua key ada dengan nilai default 0
            $result = [
                'total' => (int) ($stats->total ?? 0),
                'total_amount' => (float) ($stats->total_amount ?? 0),
                'pending' => (int) ($stats->pending ?? 0),
                'rejected' => (int) ($stats->rejected ?? 0),
                'approved' => (int) ($stats->approved ?? 0),
                'received' => (int) ($stats->matched ?? 0), // matched = received
                'pending_check' => (int) ($stats->pending_check ?? 0),
                'not_matched' => (int) ($stats->not_matched ?? 0),
                'today' => (int) ($stats->today ?? 0),
                'client_projects' => (int) ($stats->client_projects ?? 0),
                'internal_projects' => (int) ($stats->internal_projects ?? 0),
            ];
            
            Log::info('Final stats to return:', $result);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error in getPurchaseStats: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return default stats on error
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
    
    public function generatePONumber()
    {
        $year = date('Y');
        $month = date('m');
        
        // Cari PO terakhir untuk tahun dan bulan ini
        $lastPO = ProjectPurchase::where('po_number', 'like', "PO/{$year}/{$month}/%")
            ->orderBy('po_number', 'desc')
            ->first();
        
        if ($lastPO) {
            $lastNumber = (int) substr($lastPO->po_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return "PO/{$year}/{$month}/{$newNumber}";
    }
    
    public function validatePurchaseRequest(Request $request, $purchaseId = null)
    {
        Log::info('Validating purchase request:', $request->all());
        
        $rules = [];
        
        $rules = array_merge($rules, [
            'po_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('indo_purchases')->ignore($purchaseId),
            ],
            'date' => 'required|date',
            'project_type' => 'required|in:client,internal',
            'purchase_type' => 'required|in:restock,new_item',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'invoice_total' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'required|exists:departments,id',
            'supplier_type' => 'required|in:existing,new',
            'is_offline_order' => 'required|in:0,1',
            'freight' => 'nullable|numeric|min:0',
            'resi_number' => 'nullable|string|max:255', // Resi number tidak wajib
            'note' => 'nullable|string',
            'material_id' => 'nullable|exists:inventories,id',
        ]);

        // Conditional rules based on project type
        if ($request->project_type === 'client') {
            $rules['job_order_id'] = 'required|exists:job_orders,id';
            $rules['project_id'] = 'nullable|exists:projects,id';
        } else {
            $rules['internal_project_id'] = 'required|exists:internal_projects,id';
        }

        // Conditional rules based on purchase type
        if ($request->purchase_type === 'restock') {
            $rules['material_id'] = 'required|exists:inventories,id';
            $rules['new_item_name'] = 'nullable|string|max:255';
        } else {
            $rules['new_item_name'] = 'required|string|max:255';
            $rules['material_id'] = 'nullable';
        }

        // Conditional rules based on supplier type
        if ($request->supplier_type === 'existing') {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
            $rules['is_offline_order'] = 'required|in:0,1';
        } else {
            $rules['new_supplier_name'] = 'required|string|max:255';
            $rules['new_supplier_contact'] = 'nullable|string|max:100';
            $rules['new_supplier_phone'] = 'nullable|string|max:20';
            $rules['new_supplier_email'] = 'nullable|email|max:100';
            $rules['new_supplier_address'] = 'nullable|string';
            $rules['new_supplier_is_offline_order'] = 'required|in:0,1';
            
            unset($rules['is_offline_order']);
        }
        
        $validated = $request->validate($rules);
        
        // RESI NUMBER TIDAK WAJIB - Hapus validasi khusus untuk online order
        // if ($request->is_offline_order == 0 || ($request->supplier_type === 'new' && $request->new_supplier_is_offline_order == 0)) {
        //     // Online order, resi_number wajib
        //     if (empty($validated['resi_number'])) {
        //         throw new \Illuminate\Validation\ValidationException(
        //             validator()->make([], [])->errors()->add(
        //                 'resi_number',
        //                 'Untuk order online, nomor resi wajib diisi.'
        //             )
        //         );
        //     }
        // }
        
        if ($request->purchase_type === 'new_item' && isset($validated['material_id']) && $validated['material_id'] === null) {
            unset($validated['material_id']);
        }
        
        if ($request->purchase_type === 'new_item' && !isset($validated['new_item_name'])) {
            $validated['new_item_name'] = $request->new_item_name ?? '';
        }
        
        return $validated;
    }
    
    public function createPurchase(array $data)
    {
        DB::beginTransaction();
        
        try {
            Log::info('Creating purchase with data:', $data);
            
            // Handle material_id untuk new_item
            if (isset($data['purchase_type']) && $data['purchase_type'] === 'new_item' && 
                isset($data['material_id'])) {
                unset($data['material_id']);
            }
            
            if (isset($data['purchase_type']) && $data['purchase_type'] === 'restock' && 
                empty($data['material_id'])) {
                throw new \Exception('Material harus dipilih untuk purchase type restock');
            }
            
            // Set default values
            if (!isset($data['freight'])) {
                $data['freight'] = 0;
            }
            
            if (!isset($data['is_offline_order'])) {
                $data['is_offline_order'] = 0;
            }
            
            // Handle internal project
            if ($data['project_type'] === 'internal' && isset($data['internal_project_id'])) {
                $internalProject = InternalProject::find($data['internal_project_id']);
                if ($internalProject) {
                    $data['job_order_id'] = $internalProject->job;
                    if (empty($data['department_id'])) {
                        $department = Department::where('name', $internalProject->department)->first();
                        if ($department) {
                            $data['department_id'] = $department->id;
                        }
                    }
                }
            }
            
            // Calculate prices
            $data['total_price'] = $data['quantity'] * $data['unit_price'];
            $data['invoice_total'] = $data['total_price'] + ($data['freight'] ?? 0);
            
            // Set default statuses
            $data['status'] = 'pending';
            $data['item_status'] = 'pending_check'; // Sesuai dengan database
            
            // FIX: Handle PIC ID dengan benar - Cek langsung di database
            $data['pic_id'] = $this->getValidPicIdFromDatabase();
            
            // Clean up project-related fields
            if ($data['project_type'] === 'client' && empty($data['job_order_id'])) {
                $data['job_order_id'] = null;
            }
            
            if ($data['project_type'] === 'internal') {
                $data['project_id'] = null;
            }
            
            if ($data['project_type'] === 'client') {
                $data['internal_project_id'] = null;
            }
            
            Log::info('Final data for purchase creation:', $data);
            
            $purchase = ProjectPurchase::create($data);
            
            Log::info('Purchase created successfully:', [
                'id' => $purchase->id,
                'po_number' => $purchase->po_number,
                'status' => $purchase->status,
                'item_status' => $purchase->item_status,
                'project_type' => $purchase->project_type,
                'material_id' => $purchase->material_id,
                'new_item_name' => $purchase->new_item_name,
                'resi_number' => $purchase->resi_number,
                'pic_id' => $purchase->pic_id
            ]);
            
            DB::commit();
            return $purchase;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create purchase error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function updatePurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            if ($data['project_type'] === 'internal' && isset($data['internal_project_id'])) {
                $internalProject = InternalProject::find($data['internal_project_id']);
                if ($internalProject) {
                    $data['job_order_id'] = $internalProject->job;
                }
            } elseif ($data['project_type'] === 'client') {
                $data['job_order_id'] = $data['job_order_id'] ?? null;
            }
            
            // Calculate prices
            $data['total_price'] = $data['quantity'] * $data['unit_price'];
            $data['invoice_total'] = $data['total_price'] + ($data['freight'] ?? 0);
            
            $purchase->update($data);
            
            DB::commit();
            return $purchase;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function approvePurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            $updateData = [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'finance_notes' => $data['finance_notes'] ?? null,
            ];
            
            // Hanya set resi_number jika disediakan
            if (isset($data['resi_number']) && !empty($data['resi_number'])) {
                $updateData['resi_number'] = $data['resi_number'];
            }
            
            $purchase->update($updateData);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function rejectPurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
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
    
    public function updateResiNumber(ProjectPurchase $purchase, array $data)
    {
        $updateData = [];
        
        // Update resi_number jika disediakan
        if (isset($data['resi_number'])) {
            $updateData['resi_number'] = $data['resi_number'];
        }
        
        if (!empty($updateData)) {
            $purchase->update($updateData);
        }
    }
    
    public function markAsChecked(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            $updateData = [
                'item_status' => $data['item_status'],
                'checked_at' => now(),
                'checked_by' => auth()->id(),
            ];
            
            // Update actual_quantity jika ada
            if (isset($data['actual_quantity'])) {
                $updateData['actual_quantity'] = $data['actual_quantity'];
            }
            
            // Update note jika ada
            if (isset($data['note'])) {
                $updateData['note'] = $purchase->note ? $purchase->note . "\n[Checked] " . $data['note'] : $data['note'];
            }
            
            // Jika matched (sesuai), tambahkan ke inventory
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
    
    public function markAsReceived(ProjectPurchase $purchase)
    {
        DB::beginTransaction();
        
        try {
            $purchase->update([
                'item_status' => 'matched',
                'received_at' => now(),
                'received_by' => auth()->id(),
            ]);
            
            // Tambahkan ke inventory
            $this->insertToInventory($purchase);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function markAsNotMatched(ProjectPurchase $purchase)
    {
        $purchase->update([
            'item_status' => 'not_matched',
            'checked_at' => now(),
            'checked_by' => auth()->id(),
        ]);
    }
    
    private function insertToInventory(ProjectPurchase $purchase)
    {
        $unitName = $purchase->unit ? $purchase->unit->name : 'pcs';
        
        $inventoryData = [
            'name' => $purchase->purchase_type === 'restock' ? $purchase->material->name : $purchase->new_item_name,
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
        
        if ($purchase->purchase_type === 'restock' && $purchase->material_id) {
            $inventory = Inventory::find($purchase->material_id);
            if ($inventory) {
                $inventory->quantity += $purchase->quantity;
                $inventory->updated_at = now();
                $inventory->save();
            }
        } else {
            Inventory::create($inventoryData);
        }
    }
    
    /**
     * Get valid PIC ID langsung dari database
     */
    private function getValidPicIdFromDatabase()
    {
        try {
            // Cek apakah tabel employees ada
            $tableExists = DB::select("SHOW TABLES LIKE 'employees'");
            
            if (empty($tableExists)) {
                Log::warning('Tabel employees tidak ditemukan');
                
                // Coba tabel users
                $usersTableExists = DB::select("SHOW TABLES LIKE 'users'");
                if (!empty($usersTableExists)) {
                    $firstUserId = DB::table('users')->min('id');
                    if ($firstUserId) {
                        Log::info('Menggunakan user pertama sebagai PIC', ['user_id' => $firstUserId]);
                        return $firstUserId;
                    }
                }
                
                // Jika tidak ada kedua tabel, return null (asumsi kolom nullable)
                Log::error('Tidak ada tabel employees atau users. Setting pic_id ke null.');
                return null;
            }
            
            // Coba gunakan user yang sedang login jika ada
            if (auth()->check()) {
                $userId = auth()->id();
                
                // Cek apakah user_id ada di tabel employees
                $exists = DB::table('employees')->where('id', $userId)->exists();
                if ($exists) {
                    Log::info('Menggunakan user login sebagai PIC', ['employee_id' => $userId]);
                    return $userId;
                }
                
                Log::warning('User ID ' . $userId . ' tidak ditemukan di tabel employees');
            }
            
            // Cari employee pertama
            $firstEmployee = DB::table('employees')->orderBy('id')->first();
            if ($firstEmployee) {
                Log::info('Menggunakan employee pertama sebagai PIC', ['employee_id' => $firstEmployee->id]);
                return $firstEmployee->id;
            }
            
            // Jika tidak ada employee sama sekali
            Log::error('Tabel employees ada tapi kosong. Setting pic_id ke null.');
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error mendapatkan PIC ID: ' . $e->getMessage());
            
            // Fallback: return null atau nilai default
            return null;
        }
    }
    
    /**
     * Export to Excel
     */
    public function exportToExcel($purchases)
    {
        try {
            // Return data untuk di-export
            return [
                'success' => true,
                'purchases' => $purchases,
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
                    'Status',
                    'Item Status',
                    'Resi Number',
                    'Created At'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Export to Excel error: ' . $e->getMessage());
            throw $e;
        }
    }
}