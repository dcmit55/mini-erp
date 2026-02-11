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

class ProjectPurchaseService
{
    public function getPurchasesWithFilters(Request $request, $paginate = true)
    {
        $query = ProjectPurchase::current()->with([ 
            'material:id,name',
            'department:id,name',
            'category:id,name',
            'unit:id,name',
            'supplier:id,name',
            'pic:id,username',
            'project:id,name',
            'internalProject:id,project,job,department',
            'jobOrder:id,name,project_id,department_id'
        ]);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('item_status')) {
            $query->where('item_status', $request->item_status);
        }
        
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        
        if ($request->filled('project_type')) {
            $query->where('project_type', $request->project_type);
        }
        
        if ($request->filled('project_id') && $request->filled('project_type') && $request->project_type == 'client') {
            $query->where('project_id', $request->project_id);
        }
        
        if ($request->filled('internal_project_id') && $request->filled('project_type') && $request->project_type == 'internal') {
            $query->where('internal_project_id', $request->internal_project_id);
        }
        
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->filled('purchase_type')) {
            $query->where('purchase_type', $request->purchase_type);
        }
        
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
        
        $query->orderBy('created_at', 'desc');
        
        if ($paginate) {
            return $query->paginate(20);
        }
        
        return $query->get();
    }
    
    public function getPurchaseStats()
    {
        try {
            $stats = ProjectPurchase::current()
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
            
            return [
                'total' => (int) ($stats->total ?? 0),
                'total_amount' => (float) ($stats->total_amount ?? 0),
                'pending' => (int) ($stats->pending ?? 0),
                'rejected' => (int) ($stats->rejected ?? 0),
                'approved' => (int) ($stats->approved ?? 0),
                'received' => (int) ($stats->matched ?? 0),
                'pending_check' => (int) ($stats->pending_check ?? 0),
                'not_matched' => (int) ($stats->not_matched ?? 0),
                'today' => (int) ($stats->today ?? 0),
                'client_projects' => (int) ($stats->client_projects ?? 0),
                'internal_projects' => (int) ($stats->internal_projects ?? 0),
            ];
            
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
    
    public function generatePONumber()
    {
        $year = date('Y');
        $month = date('m');
        
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
            'po_number' => 'required|string|max:50',
            'date' => 'required|date',
            'purchase_type' => 'required|in:restock,new_item',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'required|exists:departments,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'is_offline_order' => 'required|in:0,1',
            'freight' => 'nullable|numeric|min:0',
            'resi_number' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        if ($request->has('project_type')) {
            $rules['project_type'] = 'required|in:client,internal';
            
            if ($request->project_type === 'client') {
                $rules['job_order_id'] = 'nullable|exists:job_orders,id';
                $rules['project_id'] = 'nullable|exists:projects,id';
            } else {
                $rules['internal_project_id'] = 'nullable|exists:internal_projects,id';
            }
        }

        if ($request->purchase_type === 'restock') {
            $rules['material_id'] = 'required|exists:inventories,id';
            $rules['new_item_name'] = 'nullable|string|max:255';
        } else {
            $rules['new_item_name'] = 'required|string|max:255';
            $rules['material_id'] = 'nullable';
        }
        
        $validated = $request->validate($rules);
        
        if ($request->purchase_type === 'new_item' && isset($validated['material_id']) && $validated['material_id'] === null) {
            unset($validated['material_id']);
        }
        
        if ($request->purchase_type === 'new_item' && !isset($validated['new_item_name'])) {
            $validated['new_item_name'] = $request->new_item_name ?? '';
        }
        
        if (!isset($validated['project_type']) && $request->has('project_type')) {
            $validated['project_type'] = $request->project_type;
        }
        
        Log::info('Validation completed successfully', ['validated_data' => $validated]);
        
        return $validated;
    }
    
    // ============================================
    // PERBAIKAN UTAMA: VALIDASI EMPLOYEE ID
    // ============================================
    
    /**
     * Validasi apakah ID ada di tabel employees
     */
    private function validateEmployeeId($id)
    {
        if (!$id) {
            return null;
        }
        
        try {
            $exists = DB::table('employees')->where('id', $id)->exists();
            return $exists ? $id : null;
        } catch (\Exception $e) {
            Log::warning('Error validating employee ID: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Dapatkan PIC ID yang valid dari employees table
     */
    private function getValidPicIdFromDatabase()
    {
        try {
            // 1. Cek apakah user saat ini ada di employees
            if (auth()->check()) {
                $userId = auth()->id();
                $validatedId = $this->validateEmployeeId($userId);
                if ($validatedId) {
                    return $validatedId;
                }
            }
            
            // 2. Cari employee pertama yang ada
            $firstEmployee = DB::table('employees')->orderBy('id')->first();
            if ($firstEmployee) {
                return $firstEmployee->id;
            }
            
            // 3. Jika tidak ada employee sama sekali, buat emergency record
            Log::emergency('Tabel employees kosong! Membuat emergency employee record.');
            
            try {
                $emergencyId = DB::table('employees')->insertGetId([
                    'name' => 'System Admin',
                    'username' => 'system_admin',
                    'email' => 'admin@system.local',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::warning('Emergency employee created with ID: ' . $emergencyId);
                return $emergencyId;
            } catch (\Exception $e) {
                // Jika gagal membuat emergency record, throw error
                throw new \Exception('Tabel employees kosong dan tidak dapat membuat data emergency. Silakan tambahkan data employee terlebih dahulu.');
            }
            
        } catch (\Exception $e) {
            Log::error('Error mendapatkan PIC ID: ' . $e->getMessage());
            throw $e; // Re-throw agar error jelas
        }
    }
    
    /**
     * Validasi semua user-related fields sebelum insert/update
     */
    private function validateUserFields(array &$data)
    {
        $userFields = ['pic_id', 'checked_by', 'approved_by', 'received_by'];
        
        foreach ($userFields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $validatedId = $this->validateEmployeeId($data[$field]);
                if (!$validatedId) {
                    Log::warning("{$field} ID tidak valid: {$data[$field]}, setting to null");
                    $data[$field] = null;
                }
            }
        }
    }
    
    public function createPurchase(array $data)
    {
        DB::beginTransaction();
        
        try {
            Log::info('Creating purchase with data:', $data);
            
            if (isset($data['purchase_type']) && $data['purchase_type'] === 'new_item' && 
                isset($data['material_id'])) {
                unset($data['material_id']);
            }
            
            if (isset($data['purchase_type']) && $data['purchase_type'] === 'restock' && 
                empty($data['material_id'])) {
                throw new \Exception('Material harus dipilih untuk purchase type restock');
            }
            
            if (!isset($data['freight'])) {
                $data['freight'] = 0;
            }
            
            if (!isset($data['is_offline_order'])) {
                $data['is_offline_order'] = 0;
            }
            
            if (isset($data['project_type']) && $data['project_type'] === 'internal' && isset($data['internal_project_id'])) {
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
            
            $data['total_price'] = $data['quantity'] * $data['unit_price'];
            $data['invoice_total'] = $data['total_price'] + ($data['freight'] ?? 0);
            
            $data['status'] = 'pending';
            $data['item_status'] = 'pending_check';
            
            $data['is_current'] = true;
            $data['revision_at'] = null;
            
            // ✅ PERBAIKAN: Dapatkan PIC ID yang valid
            $picId = auth()->id() ?? $this->getValidPicIdFromDatabase();
            $data['pic_id'] = $this->validateEmployeeId($picId);
            
            // Jika masih null setelah validasi, dapatkan yang valid
            if (!$data['pic_id']) {
                $data['pic_id'] = $this->getValidPicIdFromDatabase();
            }
            
            // ✅ Validasi semua user fields
            $this->validateUserFields($data);
            
            if (isset($data['project_type']) && $data['project_type'] === 'client' && empty($data['job_order_id'])) {
                $data['job_order_id'] = null;
            }
            
            if (isset($data['project_type']) && $data['project_type'] === 'internal') {
                $data['project_id'] = null;
            }
            
            if (isset($data['project_type']) && $data['project_type'] === 'client') {
                $data['internal_project_id'] = null;
            }
            
            Log::info('Final data for purchase creation:', $data);
            
            $purchase = ProjectPurchase::create($data);
            
            Log::info('Purchase created successfully:', [
                'id' => $purchase->id,
                'po_number' => $purchase->po_number,
                'is_current' => $purchase->is_current,
                'pic_id' => $purchase->pic_id
            ]);
            
            DB::commit();
            return $purchase;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create purchase error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function updatePurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            Log::info('🔄 UPDATE PURCHASE - Pure Revision System', [
                'old_record_id' => $purchase->id,
                'po_number' => $purchase->po_number,
                'old_is_current' => $purchase->is_current,
                'update_data' => $data
            ]);

            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa diupdate. Gunakan restore untuk mengaktifkan revisi lain.');
            }

            $quantity = $data['quantity'] ?? $purchase->quantity;
            $unitPrice = $data['unit_price'] ?? $purchase->unit_price;
            $freight = $data['freight'] ?? $purchase->freight ?? 0;
            
            $totalPrice = $quantity * $unitPrice;
            $invoiceTotal = $totalPrice + $freight;
            
            // ✅ PERBAIKAN: Validasi PIC ID
            $picId = auth()->id() ?? $purchase->pic_id;
            $validatedPicId = $this->validateEmployeeId($picId);
            
            if (!$validatedPicId && $purchase->pic_id) {
                $validatedPicId = $this->validateEmployeeId($purchase->pic_id);
            }
            
            if (!$validatedPicId) {
                $validatedPicId = $this->getValidPicIdFromDatabase();
            }
            
            $newData = [
                'po_number' => $purchase->po_number,
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
                'pic_id' => $validatedPicId, // ✅ Gunakan PIC ID yang sudah divalidasi
                'resi_number' => $data['resi_number'] ?? $purchase->resi_number,
                'total_price' => $totalPrice,
                'freight' => $freight,
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
            
            if (isset($data['status']) && $data['status'] === 'pending') {
                $newData['approved_at'] = null;
                $newData['approved_by'] = null;
                $newData['checked_at'] = null;
                $newData['checked_by'] = null;
                $newData['received_at'] = null;
                $newData['received_by'] = null;
            }
            
            // ✅ Validasi semua user fields di data baru
            $this->validateUserFields($newData);
            
            $purchase->update([
                'is_current' => false,
                'updated_at' => now()
            ]);
            
            Log::info('📝 Old record marked as not current', [
                'old_id' => $purchase->id,
                'set_is_current' => false
            ]);
            
            $newRevision = ProjectPurchase::create($newData);
            
            Log::info('✅ NEW REVISION CREATED', [
                'old_record_id' => $purchase->id,
                'new_record_id' => $newRevision->id,
                'po_number' => $newRevision->po_number,
                'is_current' => $newRevision->is_current,
                'revision_at' => $newRevision->revision_at,
                'pic_id' => $newRevision->pic_id,
                'total_records_for_po' => ProjectPurchase::where('po_number', $newRevision->po_number)->count()
            ]);
            
            $newRevision->load([
                'material:id,name',
                'department:id,name',
                'category:id,name',
                'unit:id,name',
                'supplier:id,name',
                'pic:id,username',
                'project:id,name',
                'internalProject:id,project,job,department',
                'jobOrder:id,name,project_id,department_id'
            ]);
            
            DB::commit();
            
            return $newRevision;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Update purchase error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'old_purchase_id' => $purchase->id ?? null
            ]);
            throw $e;
        }
    }
    
    private function handleMaterialId(ProjectPurchase $purchase, array $data)
    {
        $purchaseType = $data['purchase_type'] ?? $purchase->purchase_type;
        
        if ($purchaseType === 'new_item') {
            return null;
        }
        
        return $data['material_id'] ?? $purchase->material_id;
    }
    
    private function handleProjectId(ProjectPurchase $purchase, array $data)
    {
        $projectType = $data['project_type'] ?? $purchase->project_type;
        
        if ($projectType === 'internal') {
            return null;
        }
        
        return $data['project_id'] ?? $purchase->project_id;
    }
    
    private function handleInternalProjectId(ProjectPurchase $purchase, array $data)
    {
        $projectType = $data['project_type'] ?? $purchase->project_type;
        
        if ($projectType === 'client') {
            return null;
        }
        
        return $data['internal_project_id'] ?? $purchase->internal_project_id;
    }
    
    private function handleJobOrderId(ProjectPurchase $purchase, array $data)
    {
        $projectType = $data['project_type'] ?? $purchase->project_type;
        
        if ($projectType === 'internal' && isset($data['internal_project_id'])) {
            $internalProject = InternalProject::find($data['internal_project_id']);
            if ($internalProject) {
                return $internalProject->job;
            }
        }
        
        return $data['job_order_id'] ?? $purchase->job_order_id;
    }
    
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
    
    public function approvePurchase(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa diapprove.');
            }
            
            // ✅ Validasi approved_by
            $approvedById = auth()->id();
            $validatedApprovedById = $this->validateEmployeeId($approvedById);
            
            $updateData = [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $validatedApprovedById,
                'finance_notes' => $data['finance_notes'] ?? null,
            ];
            
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
            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa direject.');
            }
            
            // ✅ Validasi approved_by
            $approvedById = auth()->id();
            $validatedApprovedById = $this->validateEmployeeId($approvedById);
            
            $purchase->update([
                'status' => 'rejected',
                'approved_at' => now(),
                'approved_by' => $validatedApprovedById,
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
    
    public function markAsChecked(ProjectPurchase $purchase, array $data)
    {
        DB::beginTransaction();
        
        try {
            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa di-mark as checked.');
            }
            
            // ✅ Validasi checked_by
            $checkedById = auth()->id();
            $validatedCheckedById = $this->validateEmployeeId($checkedById);
            
            $updateData = [
                'item_status' => $data['item_status'],
                'checked_at' => now(),
                'checked_by' => $validatedCheckedById,
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
    
    public function markAsReceived(ProjectPurchase $purchase)
    {
        DB::beginTransaction();
        
        try {
            if (!$purchase->is_current) {
                throw new \Exception('Hanya current revision yang bisa di-mark as received.');
            }
            
            // ✅ Validasi received_by
            $receivedById = auth()->id();
            $validatedReceivedById = $this->validateEmployeeId($receivedById);
            
            $purchase->update([
                'item_status' => 'matched',
                'received_at' => now(),
                'received_by' => $validatedReceivedById,
            ]);
            
            $this->insertToInventory($purchase);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function markAsNotMatched(ProjectPurchase $purchase)
    {
        if (!$purchase->is_current) {
            throw new \Exception('Hanya current revision yang bisa di-mark as not matched.');
        }
        
        // ✅ Validasi checked_by
        $checkedById = auth()->id();
        $validatedCheckedById = $this->validateEmployeeId($checkedById);
        
        $purchase->update([
            'item_status' => 'not_matched',
            'checked_at' => now(),
            'checked_by' => $validatedCheckedById,
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
    
    public function exportToExcel($purchases)
    {
        try {
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