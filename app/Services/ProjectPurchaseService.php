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
            'jobOrder:id,name'
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
    
    /**
     * VALIDASI PURCHASE REQUEST - VERSI FIX UNTUK INTERNAL PROJECT
     */
    public function validatePurchaseRequest(Request $request, $purchaseId = null)
    {
        Log::info('Validating purchase request:', $request->all());
        
        $rules = [
            'date' => 'required|date',
            'purchase_type' => 'required|in:restock,new_item',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'department_id' => 'required|exists:departments,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'is_offline_order' => 'sometimes|boolean',
            'freight' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'project_type' => 'required|in:client,internal',
        ];

        // Validasi berdasarkan purchase_type
        if ($request->purchase_type === 'restock') {
            $rules['material_id'] = 'required|exists:inventories,id';
        } else { // new_item
            $rules['new_item_name'] = 'required|string|max:255';
            $rules['category_id'] = 'required|exists:categories,id';
            $rules['unit_id'] = 'required|exists:units,id';
        }

        // VALIDASI BERDASARKAN PROJECT TYPE - INI YANG KRUSIAL
        if ($request->project_type === 'client') {
            // CLIENT PROJECT: memerlukan project_id dan job_order_id
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['job_order_id'] = 'required|string|exists:job_orders,id';
        } else {
            // INTERNAL PROJECT: hanya memerlukan internal_project_id
            $rules['internal_project_id'] = 'required|exists:internal_projects,id';
            // Pastikan job_order_id TIDAK divalidasi untuk internal project
            if (isset($rules['job_order_id'])) {
                unset($rules['job_order_id']);
            }
        }

        $messages = [
            'date.required' => 'Tanggal harus diisi',
            'purchase_type.required' => 'Tipe pembelian harus dipilih',
            'quantity.required' => 'Jumlah harus diisi',
            'quantity.min' => 'Jumlah minimal 1',
            'unit_price.required' => 'Harga satuan harus diisi',
            'unit_price.min' => 'Harga satuan tidak boleh negatif',
            'department_id.required' => 'Department harus dipilih',
            'department_id.exists' => 'Department tidak valid',
            'supplier_id.required' => 'Supplier harus dipilih',
            'supplier_id.exists' => 'Supplier tidak valid',
            'material_id.required' => 'Material harus dipilih untuk restock',
            'material_id.exists' => 'Material tidak valid',
            'new_item_name.required' => 'Nama item baru harus diisi',
            'category_id.required' => 'Kategori harus dipilih untuk item baru',
            'category_id.exists' => 'Kategori tidak valid',
            'unit_id.required' => 'Satuan harus dipilih untuk item baru',
            'unit_id.exists' => 'Satuan tidak valid',
            'project_type.required' => 'Tipe project harus dipilih',
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
        }
        
        Log::info('Validation completed successfully', ['validated_data' => $validated]);
        
        return $validated;
    }
    
    private function validateForeignKeys(array $data)
    {
        $errors = [];
        
        if (isset($data['department_id']) && !DB::table('departments')->where('id', $data['department_id'])->exists()) {
            $errors[] = "Department ID {$data['department_id']} tidak valid";
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
            
            if (empty($purchaseData['po_number'])) {
                $purchaseData['po_number'] = $this->generatePONumber();
            }
            
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
    
    private function preparePurchaseData(array $data)
    {
        $purchaseData = [
            'date' => $data['date'],
            'purchase_type' => $data['purchase_type'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'department_id' => $data['department_id'],
            'supplier_id' => $data['supplier_id'],
            'is_offline_order' => $data['is_offline_order'] ?? false,
            'freight' => $data['freight'] ?? 0,
            'note' => $data['note'] ?? null,
            'project_type' => $data['project_type'],
        ];

        if ($data['purchase_type'] === 'restock') {
            $purchaseData['material_id'] = $data['material_id'];
            $purchaseData['new_item_name'] = null;
            $purchaseData['category_id'] = null;
            $purchaseData['unit_id'] = null;
        } else {
            $purchaseData['material_id'] = null;
            $purchaseData['new_item_name'] = $data['new_item_name'];
            $purchaseData['category_id'] = $data['category_id'];
            $purchaseData['unit_id'] = $data['unit_id'];
        }

        // PERBAIKAN: Set data berdasarkan project type dengan benar
        if ($data['project_type'] === 'client') {
            $purchaseData['project_id'] = $data['project_id'];
            $purchaseData['job_order_id'] = $data['job_order_id'];
            $purchaseData['internal_project_id'] = null;
        } else {
            $purchaseData['internal_project_id'] = $data['internal_project_id'];
            $purchaseData['project_id'] = null;
            $purchaseData['job_order_id'] = null; // PASTIKAN job_order_id = null untuk internal project
        }

        $purchaseData['total_price'] = $data['quantity'] * $data['unit_price'];
        $purchaseData['invoice_total'] = $purchaseData['total_price'] + ($purchaseData['freight'] ?? 0);

        return $purchaseData;
    }
    
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
            
            $totalPrice = $quantity * $unitPrice;
            $invoiceTotal = $totalPrice + $freight;
            
            $picId = $this->getValidPicId();
            
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
                'pic_id' => $picId,
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
            
            $purchase->update([
                'is_current' => false,
                'updated_at' => now()
            ]);
            
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
        
        // PERBAIKAN: Untuk internal project, job_order_id harus null
        if ($projectType === 'internal') {
            return null;
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
    
    private function insertToInventory(ProjectPurchase $purchase)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error inserting to inventory: ' . $e->getMessage());
            throw $e;
        }
    }
    
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