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
            $purchases = $this->purchaseService->getPurchasesWithFilters($request);
            $stats = $this->purchaseService->getPurchaseStats();

            return view('procurement.Project-Purchase.index', [
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
                'supplierLocations' => \App\Models\Procurement\LocationSupplier::select('id', 'name')->get(),
                'filters' => $request->all(),
            ]);

        } catch (\Exception $e) {
            Log::error('Index error: ' . $e->getMessage());

            return view('procurement.Project-Purchase.index', [
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
            // HAPUS BARIS INI - TIDAK PERLU GENERATE PO NUMBER
            // $poNumber = $this->purchaseService->generatePONumber();

            $jobOrders = JobOrder::with(['department:id,name', 'project:id,name'])
                ->select('id', 'name', 'department_id', 'project_id')
                ->get()
                ->map(function($jobOrder) {
                    return [
                        'id' => $jobOrder->id,
                        'name' => $jobOrder->name,
                        'department_id' => $jobOrder->department_id,
                        'department_name' => $jobOrder->department->name ?? 'N/A',
                        'project_id' => $jobOrder->project_id,
                        'project_name' => $jobOrder->project->name ?? 'N/A',
                    ];
                });

            $materials = Inventory::with(['unit:id,name', 'category:id,name'])
                ->select('id', 'name', 'price', 'unit_id', 'category_id')
                ->get();

            $units = Unit::select('id', 'name')->get();
            $categories = Category::select('id', 'name')->get();

            return view('procurement.Project-Purchase.create', [
                // HAPUS VARIABLE poNumber DARI SINI
                // 'poNumber' => $poNumber,
                'materials' => $materials,
                'departments' => Department::select('id', 'name')->get(),
                'projects' => Project::select('id', 'name')->get(),
                'internal_projects' => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')
                    ->orderBy('project')
                    ->get(),
                'categories' => $categories,
                'units' => $units,
                'jobOrders' => $jobOrders,
                'suppliers' => Supplier::select('id', 'name')->get(),
                'supplierLocations' => \App\Models\Procurement\LocationSupplier::select('id', 'name')->get(),
            ]);
        } catch (\Exception $e) {
            Log::error('Create view error: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Gagal memuat halaman pembelian: ' . $e->getMessage());
        }
    }

    /**
     * STORE METHOD DENGAN VALIDASI PO NUMBER DARI USER
     */
    public function store(Request $request)
    {
        try {
            // Simpan semua input ke session
            $request->flash();

            Log::info('Store request data:', $request->all());

            // PERBAIKAN: Untuk internal project, ambil department_id dari internal project
            if ($request->project_type === 'internal' && $request->filled('internal_project_id')) {
                $internalProject = InternalProject::find($request->internal_project_id);
                
                if ($internalProject) {
                    if ($internalProject->department_id) {
                        $request->merge(['department_id' => $internalProject->department_id]);
                        Log::info('Set department_id from internal project department_id:', [
                            'internal_project_id' => $request->internal_project_id,
                            'department_id' => $internalProject->department_id
                        ]);
                    } elseif ($internalProject->department) {
                        $department = Department::where('name', $internalProject->department)->first();
                        if ($department) {
                            $request->merge(['department_id' => $department->id]);
                            Log::info('Set department_id from department name:', [
                                'department_name' => $internalProject->department,
                                'department_id' => $department->id
                            ]);
                        } else {
                            $request->merge(['department_id' => 19]);
                            Log::info('Set department_id to default 19');
                        }
                    } else {
                        $request->merge(['department_id' => 19]);
                        Log::info('Set department_id to default 19 (no department info)');
                    }
                } else {
                    return back()
                        ->withInput($request->all())
                        ->with('error', 'Internal project tidak ditemukan.');
                }
            }

            // Validasi data (termasuk po_number dari user)
            $validated = $this->purchaseService->validatePurchaseRequest($request);
            
            Log::info('Validated data:', $validated);

            // Buat purchase order
            $purchase = $this->purchaseService->createPurchase($validated);

            return redirect()->route('project-purchases.index')
                ->with('success', 'Purchase Order berhasil dibuat dengan nomor: ' . $purchase->po_number);
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation errors:', $e->errors());
            
            return back()
                ->withErrors($e->errors())
                ->withInput($request->all())
                ->with('error', 'Validasi gagal. Periksa kembali form Anda.');
                
        } catch (\Exception $e) {
            Log::error('Purchase Order creation error: ' . $e->getMessage());
            
            return back()
                ->withInput($request->all())
                ->with('error', 'Gagal membuat Purchase Order: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
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
                    'pic:id,username',
                    'approver:id,username',
                    'checker:id,username',
                    'receiver:id,username'
                ])->find($id);

            if (!$purchase) {
                return redirect()->route('project-purchases.index')
                    ->with('error', 'Data purchase order tidak ditemukan (ID: ' . $id . ').');
            }

            $revisions = ProjectPurchase::where('po_number', $purchase->po_number)
                ->orderBy('created_at', 'desc')
                ->with(['pic:id,username'])
                ->get();

            return view('procurement.Project-Purchase.show', [
                'purchase' => $purchase,
                'revisions' => $revisions,
                'revision_info' => [
                    'total_revisions' => $revisions->count(),
                    'current_revision_id' => $purchase->id,
                    'revision_number' => $revisions->where('created_at', '<=', $purchase->created_at)->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Show error: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
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
                    'pic:id,username'
                ])->findOrFail($id);

            if (!$purchase->canEdit()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diedit.');
            }

            $revisions = ProjectPurchase::where('po_number', $purchase->po_number)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'revision_at', 'status', 'item_status', 'created_at', 'is_current']);

            return view('procurement.Project-Purchase.edit', [
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
        } catch (\Exception $e) {
            Log::error('Edit error: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Terjadi kesalahan saat memuat halaman edit.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->canEdit()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diupdate.');
            }

            $validated = $this->purchaseService->validatePurchaseRequest($request, $purchase->id);
            $updatedPurchase = $this->purchaseService->updatePurchase($purchase, $validated);

            return redirect()->route('project-purchases.show', $updatedPurchase->id)
                ->with('success', 'Purchase Order berhasil diperbarui (revisi baru dibuat)!');
        } catch (\Exception $e) {
            Log::error('Purchase Order update error: ' . $e->getMessage());
            return back()->withInput($request->all())->with('error', 'Gagal update Purchase Order: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
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

            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->canApprove()) {
                return back()->with('error', 'Tidak dapat menyetujui Purchase Order ini.');
            }

            if (!$purchase->isOfflineOrder() && empty($validated['resi_number'])) {
                return back()->with('error', 'Untuk order online, harus mengisi nomor resi.');
            }

            $this->purchaseService->approvePurchase($purchase, $validated);

            return back()->with('success', 'Purchase Order berhasil disetujui!');
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

            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->canReject()) {
                return back()->with('error', 'Tidak dapat menolak Purchase Order ini.');
            }

            $this->purchaseService->rejectPurchase($purchase, $validated);

            return back()->with('success', 'Purchase Order berhasil ditolak!');
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

            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->canUpdateResi()) {
                return back()->with('error', 'Tidak dapat mengupdate resi karena PO belum disetujui atau barang sudah dicek.');
            }

            if (!$purchase->isOfflineOrder() && empty($validated['resi_number'])) {
                return back()->with('error', 'Untuk order online, harus mengisi nomor resi.');
            }

            $this->purchaseService->updateResiNumber($purchase, $validated);

            return back()->with('success', 'Nomor resi berhasil diperbarui!');
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

            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->canCheck()) {
                return back()->with('error', 'Tidak dapat mengecek barang karena PO belum disetujui atau sudah dicek.');
            }

            $this->purchaseService->markAsChecked($purchase, $validated);

            $statusText = $validated['item_status'] === 'matched' ? 'sesuai' : 'tidak sesuai';
            return back()->with('success', 'Barang berhasil ditandai sebagai ' . $statusText . '!');
        } catch (\Exception $e) {
            Log::error('Mark as checked error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menandai barang: ' . $e->getMessage());
        }
    }

    public function markAsReceived($id)
    {
        try {
            $purchase = ProjectPurchase::current()
                ->with([
                    'project:id,name',
                    'internalProject:id,project,job,department',
                    'jobOrder:id,name'
                ])
                ->findOrFail($id);

            Log::info('Attempting to mark as received', [
                'purchase_id' => $id,
                'status' => $purchase->status,
                'item_status' => $purchase->item_status,
                'is_current' => $purchase->is_current,
                'project_type' => $purchase->project_type
            ]);

            if (!$purchase->canMarkAsReceived()) {
                $errorMessage = 'Tidak dapat menandai sebagai diterima. ';
                
                if ($purchase->status !== 'approved') {
                    $errorMessage .= 'Status PO: ' . $purchase->status . '. ';
                }
                
                if (!in_array($purchase->item_status, ['pending_check', 'pending'])) {
                    $errorMessage .= 'Status barang: ' . $purchase->item_status . '. ';
                }
                
                if (!$purchase->is_current) {
                    $errorMessage .= 'Ini bukan revision terbaru. ';
                }
                
                Log::warning('Cannot mark as received', [
                    'reason' => $errorMessage,
                    'purchase' => $purchase->toArray()
                ]);
                
                return back()->with('error', $errorMessage);
            }

            $this->purchaseService->markAsReceived($purchase);

            return back()->with('success', 'Barang berhasil ditandai sebagai diterima dan ditambahkan ke inventory dengan informasi project!');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Purchase not found for markAsReceived: ' . $e->getMessage());
            return redirect()->route('project-purchases.index')
                ->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Mark as received error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Gagal menandai barang sebagai diterima: ' . $e->getMessage());
        }
    }

    public function markAsNotMatched($id)
    {
        try {
            $purchase = ProjectPurchase::current()->findOrFail($id);

            if (!$purchase->isItemPending()) {
                return back()->with('error', 'Tidak dapat menandai sebagai tidak sesuai karena barang sudah ditandai.');
            }

            $this->purchaseService->markAsNotMatched($purchase);

            return back()->with('success', 'Barang berhasil ditandai sebagai tidak sesuai!');
        } catch (\Exception $e) {
            Log::error('Mark as not matched error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menandai barang sebagai tidak sesuai: ' . $e->getMessage());
        }
    }

    public function getMaterialPrice($id)
    {
        try {
            $material = Inventory::with(['unit:id,name', 'category:id,name'])->find($id);

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
                'unit_name' => $material->unit->name ?? null,
                'category_id' => $material->category_id ?? null,
                'category_name' => $material->category->name ?? null,
                'material_name' => $material->name ?? null
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
                'project_name' => $jobOrder->project->name ?? null,
                'job_order_name' => $jobOrder->name ?? null
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

            return view('procurement.Project-Purchase.Print', compact('purchase'));
        } catch (\Exception $e) {
            Log::error('Print error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencetak purchase order: ' . $e->getMessage());
        }
    }

    public function getMaterials()
    {
        try {
            $materials = Inventory::with(['unit:id,name', 'category:id,name'])
                ->select('id', 'name', 'price', 'unit_id', 'category_id')
                ->get();

            return response()->json([
                'success' => true,
                'materials' => $materials
            ]);
        } catch (\Exception $e) {
            Log::error('Get materials error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data material'
            ]);
        }
    }
}