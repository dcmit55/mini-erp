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
            Log::info('=== PROJECT PURCHASE INDEX START ===');
            Log::info('Request filters:', $request->all());
            
            // 1. Get purchases with filters
            $purchases = $this->purchaseService->getPurchasesWithFilters($request);
            
            // 2. Get stats - DENGAN FALLBACK LANGSUNG JIKA SERVICE GAGAL
            $stats = [];
            
            try {
                $stats = $this->purchaseService->getPurchaseStats();
                Log::info('Stats from service:', $stats);
            } catch (\Exception $serviceError) {
                Log::error('Service getPurchaseStats error: ' . $serviceError->getMessage());
                // Fallback: Hitung manual di controller
                $stats = $this->calculateStatsDirectly();
            }
            
            // 3. Verify stats data
            $manualVerification = [
                'total_check' => ProjectPurchase::count(),
                'approved_check' => ProjectPurchase::where('status', 'approved')->count(),
                'pending_check' => ProjectPurchase::where('status', 'pending')->count(),
                'rejected_check' => ProjectPurchase::where('status', 'rejected')->count(),
                'received_check' => ProjectPurchase::whereIn('item_status', ['matched', 'received'])->count(),
                'pending_check_count' => ProjectPurchase::whereIn('item_status', ['pending_check', 'pending'])->count(),
            ];
            
            Log::info('Manual verification:', $manualVerification);
            
            // 4. Jika stats dari service masih 0, tapi manual verification ada data
            if (($stats['total'] ?? 0) == 0 && $manualVerification['total_check'] > 0) {
                Log::warning('Service returned 0 stats but manual check has data! Using manual calculation.');
                $stats = $this->calculateStatsDirectly();
            }
            
            // 5. Ensure all required keys exist
            $defaultStats = [
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
            
            $stats = array_merge($defaultStats, $stats);
            
            // 6. Debug final stats
            Log::info('Final stats to view:', $stats);
            Log::info('Purchases total: ' . $purchases->total());
            Log::info('=== PROJECT PURCHASE INDEX END ===');
            
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
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return view('Procurement.Project-Purchase.index', [
                'purchases' => ProjectPurchase::query()->paginate(20),
                'stats' => $this->calculateStatsDirectly(),
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
    
    /**
     * Calculate stats directly without service (fallback method)
     */
    private function calculateStatsDirectly()
    {
        try {
            Log::info('Calculating stats directly...');
            
            // Total semua PO
            $total = ProjectPurchase::count();
            
            // Total amount (hanya PO yang approved)
            $totalAmount = ProjectPurchase::where('status', 'approved')->sum('invoice_total');
            
            // Status PO
            $pending = ProjectPurchase::where('status', 'pending')->count();
            $approved = ProjectPurchase::where('status', 'approved')->count();
            $rejected = ProjectPurchase::where('status', 'rejected')->count();
            
            // Status item (sesuai dengan database: pending_check, matched, not_matched)
            $matched = ProjectPurchase::where('item_status', 'matched')->count();
            $pendingCheck = ProjectPurchase::whereIn('item_status', ['pending_check', 'pending'])->count();
            $notMatched = ProjectPurchase::where('item_status', 'not_matched')->count();
            
            // Today's purchases
            $today = ProjectPurchase::whereDate('created_at', today())->count();
            
            // Project types
            $clientProjects = ProjectPurchase::where('project_type', 'client')->count();
            $internalProjects = ProjectPurchase::where('project_type', 'internal')->count();
            
            $stats = [
                'total' => $total,
                'total_amount' => $totalAmount,
                'pending' => $pending,
                'rejected' => $rejected,
                'approved' => $approved,
                'received' => $matched, // matched = received
                'pending_check' => $pendingCheck,
                'not_matched' => $notMatched,
                'today' => $today,
                'client_projects' => $clientProjects,
                'internal_projects' => $internalProjects,
            ];
            
            Log::info('Direct calculation stats:', $stats);
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Direct stats calculation error: ' . $e->getMessage());
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

    public function create()
    {
        try {
            return view('Procurement.Project-Purchase.create', [
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
            // OPSIONAL: Override department_id untuk internal projects
            if ($request->project_type === 'internal') {
                $request->merge(['department_id' => 24]); // Sesuaikan dengan department_id yang sesuai
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
            Log::info("=== SHOW PURCHASE ORDER ===");
            Log::info("Purchase ID: " . $id);
            
            $purchase = ProjectPurchase::with([
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
            
            Log::info("Purchase found: " . ($purchase ? 'YES' : 'NO'));
            
            if (!$purchase) {
                Log::error("Purchase not found with ID: " . $id);
                return redirect()->route('project-purchases.index')
                    ->with('error', 'Data purchase order tidak ditemukan (ID: ' . $id . ').');
            }
            
            return view('Procurement.Project-Purchase.show', compact('purchase'));
            
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
            $purchase = ProjectPurchase::with([
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

            if ($purchase->isApproved() || $purchase->isRejected()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diedit karena sudah disetujui/ditolak oleh Finance.');
            }

            if (!$purchase->canEdit()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diedit.');
            }

            return view('Procurement.Project-Purchase.edit', [
                'purchase' => $purchase,
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
            $purchase = ProjectPurchase::findOrFail($id);

            if ($purchase->isApproved() || $purchase->isRejected()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diupdate karena sudah disetujui/ditolak oleh Finance.');
            }

            if (!$purchase->canEdit()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat diupdate.');
            }

            $validated = $this->purchaseService->validatePurchaseRequest($request, $purchase->id);
            $this->purchaseService->updatePurchase($purchase, $validated);

            return redirect()->route('project-purchases.show', $purchase->id)
                ->with('success', 'Purchase Order berhasil diperbarui!');
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
            $purchase = ProjectPurchase::findOrFail($id);

            if ($purchase->isApproved() || $purchase->isRejected()) {
                return redirect()->route('project-purchases.show', $purchase->id)
                    ->with('error', 'Purchase Order tidak dapat dihapus karena sudah disetujui/ditolak oleh Finance.');
            }

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

            $purchase = ProjectPurchase::findOrFail($id);
            
            // Validasi: untuk online order, harus ada resi_number
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

            $purchase = ProjectPurchase::findOrFail($id);
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

            $purchase = ProjectPurchase::findOrFail($id);
            
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

            $purchase = ProjectPurchase::findOrFail($id);
            
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
            $purchase = ProjectPurchase::findOrFail($id);
            
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
            $purchase = ProjectPurchase::findOrFail($id);
            
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

    /**
     * Export purchases to Excel
     */
    public function export(Request $request)
    {
        try {
            $purchases = $this->purchaseService->getPurchasesWithFilters($request, false); // tanpa pagination
            
            return $this->purchaseService->exportToExcel($purchases);
        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengexport data: ' . $e->getMessage());
        }
    }

    /**
     * Print purchase order
     */
    public function print($id)
    {
        try {
            $purchase = ProjectPurchase::with([
                'material:id,name,price',
                'department:id,name',
                'project:id,name',
                'internalProject:id,project,job,department,department_id,description',
                'jobOrder:id,name',
                'category:id,name',
                'unit:id,name',
                'supplier:id,name,address,phone,email',
                'pic:id,username,name',
                'approver:id,username,name'
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