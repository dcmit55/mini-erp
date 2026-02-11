<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logistic\Inventory;
use App\Models\Admin\Department;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\ProjectPurchase;
use App\Models\Logistic\Category;
use App\Models\Logistic\Unit;
use App\Models\InternalProject;
use App\Services\ProjectPurchaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectPurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(ProjectPurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        try {
            // Gunakan service yang sudah diperbaiki
            $purchases = $this->purchaseService->getPurchasesWithFilters($request);
            
            $stats = $this->purchaseService->getPurchaseStats();
            
            return view('Procurement.Project-Purchase.index', [
                'purchases' => $purchases,
                'stats' => $stats,
                'departments' => Department::select('id', 'name')->get(),
                'projects' => Project::select('id', 'name')->get(),
                'internal_projects' => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')
                    ->orderBy('project')
                    ->get(),
                'categories' => Category::select('id', 'name')->get(),
                'units' => Unit::select('id', 'name')->get(),
                'suppliers' => Supplier::select('id', 'name')->get(),
                'filters' => $request->all(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Index error: ' . $e->getMessage());
            
            return view('Procurement.Project-Purchase.index', [
                'purchases' => ProjectPurchase::current()->paginate(20),
                'stats' => [
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
                ],
                'departments' => Department::select('id', 'name')->get(),
                'projects' => Project::select('id', 'name')->get(),
                'internal_projects' => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')
                    ->orderBy('project')
                    ->get(),
                'categories' => Category::select('id', 'name')->get(),
                'units' => Unit::select('id', 'name')->get(),
                'suppliers' => Supplier::select('id', 'name')->get(),
                'filters' => $request->all(),
            ]);
        }
    }

    public function create()
    {
        try {
            // Generate PO number dari service
            $poNumber = $this->purchaseService->generatePONumber();
            
            return view('Procurement.Project-Purchase.create', [
                'poNumber' => $poNumber, // Kirim PO number ke view
                'materials' => Inventory::select('id', 'name', 'price', 'unit_id', 'category_id')->get(),
                'departments' => Department::select('id', 'name')->get(),
                'projects' => Project::select('id', 'name')->get(),
                'internal_projects' => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')
                    ->orderBy('project')
                    ->get(),
                'categories' => Category::select('id', 'name')->get(),
                'units' => Unit::select('id', 'name')->get(),
                'jobOrders' => JobOrder::leftJoin('departments', 'job_orders.department_id', '=', 'departments.id')
                    ->leftJoin('projects', 'job_orders.project_id', '=', 'projects.id')
                    ->select(
                        'job_orders.id',
                        'job_orders.name',
                        'job_orders.project_id',
                        'job_orders.department_id',
                        'departments.name as department_name',
                        'projects.name as project_name'
                    )
                    ->get(),
                'suppliers' => Supplier::select('id', 'name')->get(),
            ]);
        } catch (\Exception $e) {
            Log::error('Create view error: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Gagal memuat halaman pembelian: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            if ($request->project_type === 'internal') {
                $request->merge(['department_id' => 24]);
            }
            
            $validated = $this->purchaseService->validatePurchaseRequest($request);
            $purchase = $this->purchaseService->createPurchase($validated);

            return redirect()->route('project-purchases.index')
                ->with('success', 'Purchase Order berhasil dibuat dengan status Pending!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Purchase Order creation error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal membuat Purchase Order: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            // ✅ PERBAIKAN: Ambil current revision saja dan load username
            $purchase = ProjectPurchase::current()
                ->with([
                    'material:id,name,price',
                    'department:id,name',
                    'project:id,name',
                    'internalProject:id,project,job,department,department_id,description',
                    'jobOrder:id,name',
                    'category:id,name',
                    'unit:id,name',
                    'supplier:id,name',
                    'pic:id,username', // ✅ GUNAKAN username
                    'approver:id,username', // ✅ GUNAKAN username
                    'checker:id,username', // ✅ GUNAKAN username
                    'receiver:id,username' // ✅ GUNAKAN username
                ])->find($id);
            
            if (!$purchase) {
                return redirect()->route('project-purchases.index')
                    ->with('error', 'Data purchase order tidak ditemukan (ID: ' . $id . ').');
            }
            
            // Get revision history untuk PO yang sama
            $revisions = ProjectPurchase::where('po_number', $purchase->po_number)
                ->orderBy('created_at', 'desc')
                ->with(['pic:id,username'])
                ->get();
            
            return view('Procurement.Project-Purchase.show', [
                'purchase' => $purchase,
                'revisions' => $revisions,
                'revision_info' => [
                    'total_revisions' => $revisions->count(),
                    'current_revision_id' => $purchase->id,
                    'revision_number' => $revisions->where('created_at', '<=', $purchase->created_at)->count(),
                ]
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('ModelNotFoundException in show: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Show error: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            // ✅ PERBAIKAN: Ambil current revision saja
            $purchase = ProjectPurchase::current()
                ->with([
                    'material:id,name,price',
                    'department:id,name',
                    'project:id,name',
                    'internalProject:id,project,job,department,department_id,description',
                    'jobOrder:id,name',
                    'category:id,name',
                    'unit:id,name',
                    'supplier:id,name',
                    'pic:id,username' // ✅ GUNAKAN username
                ])->findOrFail($id);

            if (!$purchase->canEdit()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diedit.');
            }

            // Get all revisions untuk ditampilkan di edit page
            $revisions = ProjectPurchase::where('po_number', $purchase->po_number)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'revision_at', 'status', 'item_status', 'created_at', 'is_current']);

            return view('Procurement.Project-Purchase.edit', [
                'purchase' => $purchase,
                'revisions' => $revisions,
                'revision_info' => [
                    'total_revisions' => $revisions->count(),
                    'current_revision_id' => $purchase->id,
                    'revision_number' => $revisions->where('created_at', '<=', $purchase->created_at)->count(),
                ],
                'materials' => Inventory::select('id', 'name', 'price')->get(),
                'departments' => Department::select('id', 'name')->get(),
                'projects' => Project::select('id', 'name')->get(),
                'internal_projects' => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')
                    ->orderBy('project')
                    ->get(),
                'categories' => Category::select('id', 'name')->get(),
                'units' => Unit::select('id', 'name')->get(),
                'jobOrders' => JobOrder::leftJoin('departments', 'job_orders.department_id', '=', 'departments.id')
                    ->leftJoin('projects', 'job_orders.project_id', '=', 'projects.id')
                    ->select(
                        'job_orders.id',
                        'job_orders.name',
                        'job_orders.project_id',
                        'job_orders.department_id',
                        'departments.name as department_name',
                        'projects.name as project_name'
                    )
                    ->get(),
                'suppliers' => Supplier::select('id', 'name')->get(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Edit error: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Terjadi kesalahan saat memuat halaman edit.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->canEdit()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diupdate.');
            }

            $validated = $this->purchaseService->validatePurchaseRequest($request, $purchase->id);
            
            // Update purchase akan membuat REVISION baru
            $updatedPurchase = $this->purchaseService->updatePurchase($purchase, $validated);

            return redirect()->route('project-purchases.show', $updatedPurchase->id)
                ->with('success', 'Purchase Order berhasil diperbarui (revisi baru dibuat)!')
                ->with('revision_info', [
                    'old_record_id' => $purchase->id,
                    'new_record_id' => $updatedPurchase->id,
                    'po_number' => $updatedPurchase->po_number,
                    'revision_count' => $updatedPurchase->total_revisions,
                ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Purchase Order update error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal update Purchase Order: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->canDelete()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat dihapus.');
            }

            DB::beginTransaction();
            $purchase->delete();
            DB::commit();

            return redirect()->route('project-purchases.index')
                ->with('success', 'Purchase Order berhasil dihapus!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Order deletion error: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Gagal menghapus Purchase Order: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'resi_number' => 'nullable|string|max:255',
                'finance_notes' => 'nullable|string',
            ]);

            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);
            
            if (!$purchase->canApprove()) {
                return back()->with('error', 'Tidak dapat menyetujui Purchase Order ini.');
            }

            if (!$purchase->isOfflineOrder() && empty($validated['resi_number'])) {
                return back()->with('error', 'Untuk order online, harus mengisi nomor resi.');
            }

            $this->purchaseService->approvePurchase($purchase, $validated);

            return back()->with('success', 'Purchase Order berhasil disetujui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Purchase Order approval error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyetujui Purchase Order: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'finance_notes' => 'required|string',
            ]);

            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);
            
            if (!$purchase->canReject()) {
                return back()->with('error', 'Tidak dapat menolak Purchase Order ini.');
            }
            
            $this->purchaseService->rejectPurchase($purchase, $validated);

            return back()->with('success', 'Purchase Order berhasil ditolak!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Purchase Order rejection error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menolak Purchase Order: ' . $e->getMessage());
        }
    }

    public function updateResi(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'resi_number' => 'nullable|string|max:255',
            ]);

            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);
            
            if (!$purchase->canUpdateResi()) {
                return back()->with('error', 'Tidak dapat mengupdate resi karena PO belum disetujui atau barang sudah dicek.');
            }

            if (!$purchase->isOfflineOrder() && empty($validated['resi_number'])) {
                return back()->with('error', 'Untuk order online, harus mengisi nomor resi.');
            }

            $this->purchaseService->updateResiNumber($purchase, $validated);

            return back()->with('success', 'Nomor resi berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Resi update error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui nomor resi: ' . $e->getMessage());
        }
    }

    public function markAsChecked(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'item_status' => 'required|in:matched,not_matched',
                'actual_quantity' => 'nullable|integer|min:0',
                'note' => 'nullable|string',
            ]);

            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);
            
            if (!$purchase->canCheck()) {
                return back()->with('error', 'Tidak dapat mengecek barang karena PO belum disetujui atau sudah dicek.');
            }

            $this->purchaseService->markAsChecked($purchase, $validated);

            $statusText = $validated['item_status'] === 'matched' ? 'sesuai' : 'tidak sesuai';
            return back()->with('success', 'Barang berhasil ditandai sebagai ' . $statusText . '!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Mark as checked error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menandai barang: ' . $e->getMessage());
        }
    }

    public function markAsReceived($id)
    {
        try {
            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);
            
            if (!$purchase->canMarkAsReceived()) {
                return back()->with('error', 'Tidak dapat menandai sebagai diterima karena PO belum disetujui atau sudah diterima.');
            }

            $this->purchaseService->markAsReceived($purchase);

            return back()->with('success', 'Barang berhasil ditandai sebagai diterima dan ditambahkan ke inventory!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Mark as received error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menandai barang sebagai diterima: ' . $e->getMessage());
        }
    }

    public function markAsNotMatched($id)
    {
        try {
            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()->findOrFail($id);
            
            if (!$purchase->isItemPending()) {
                return back()->with('error', 'Tidak dapat menandai sebagai tidak sesuai karena barang sudah ditandai.');
            }

            $this->purchaseService->markAsNotMatched($purchase);

            return back()->with('success', 'Barang berhasil ditandai sebagai tidak sesuai!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Mark as not matched error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menandai barang sebagai tidak sesuai: ' . $e->getMessage());
        }
    }

    public function getMaterialPrice($id)
    {
        try {
            $material = Inventory::find($id);
            
            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material tidak ditemukan',
                    'price' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'price' => $material->price ?? 0,
                'unit_id' => $material->unit_id ?? null,
                'category_id' => $material->category_id ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Get material price error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'price' => 0
            ]);
        }
    }

    public function getJobOrderDetails($id)
    {
        try {
            $jobOrder = JobOrder::with(['department:id,name', 'project:id,name'])->find($id);
            
            if (!$jobOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job Order tidak ditemukan',
                    'department_id' => null,
                    'project_id' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'department_id' => $jobOrder->department_id,
                'department_name' => $jobOrder->department->name ?? null,
                'project_id' => $jobOrder->project_id,
                'project_name' => $jobOrder->project->name ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Get job order details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'department_id' => null,
                'project_id' => null
            ]);
        }
    }

    public function getInternalProjectDetails($id)
    {
        try {
            $project = $this->purchaseService->getInternalProjectDetails($id);
            return response()->json($project);
        } catch (\Exception $e) {
            Log::error('Get internal project details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'job' => null,
                'department' => null
            ]);
        }
    }

    public function export(Request $request)
    {
        try {
            $purchases = $this->purchaseService->getPurchasesWithFilters($request, false);
            
            return $this->purchaseService->exportToExcel($purchases);
        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengexport data: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        try {
            // ✅ PERBAIKAN: Cari current revision saja
            $purchase = ProjectPurchase::current()
                ->with([
                    'material:id,name,price',
                    'department:id,name',
                    'project:id,name',
                    'internalProject:id,project,job,department,department_id,description',
                    'jobOrder:id,name',
                    'category:id,name',
                    'unit:id,name',
                    'supplier:id,name,address,phone,email',
                    'pic:id,username',
                    'approver:id,username'
                ])->findOrFail($id);

            return view('Procurement.Project-Purchase.print', compact('purchase'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Print error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencetak purchase order: ' . $e->getMessage());
        }
    }
}