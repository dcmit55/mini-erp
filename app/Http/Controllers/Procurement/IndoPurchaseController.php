<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logistic\Inventory;
use App\Models\Admin\Department;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\IndoPurchase;
use App\Models\Logistic\Category;
use App\Models\Logistic\Unit;
use App\Models\InternalProject;
use App\Services\IndoPurchaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndoPurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(IndoPurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    /**
     * INDEX - Menampilkan daftar PO (grouped by PO number)
     */
    public function index(Request $request)
    {
        try {
            $purchases = $this->purchaseService->getPurchasesWithFilters($request);
            $stats = $this->purchaseService->getPurchaseStats();

            return view('procurement.Indo-Purchase.index', [
                'purchases' => $purchases,
                'stats' => $stats,
                'departments' => Department::select('id', 'name')->get(),
                'projects' => Project::select('id', 'name')->get(),
                'internal_projects' => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')->orderBy('project')->get(),
                'categories' => Category::select('id', 'name')->get(),
                'units' => Unit::select('id', 'name')->get(),
                'suppliers' => Supplier::select('id', 'name')->get(),
                'supplierLocations' => \App\Models\Procurement\LocationSupplier::select('id', 'name')->get(),
                'filters' => $request->all(),
                'totalEligible' => IndoPurchase::where('status', 'approved')
                    ->whereIn('item_status', ['pending_check', 'pending'])
                    ->whereNull('received_at')
                    ->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Index error: ' . $e->getMessage());

            return view('procurement.Indo-Purchase.index', [
                'purchases' => IndoPurchase::with(['material:id,name', 'department:id,name', 'category:id,name', 'unit:id,name', 'supplier:id,name', 'pic:id,username', 'checker:id,username', 'approver:id,username', 'receiver:id,username', 'project:id,name', 'internalProject:id,project,job,department,department_id', 'jobOrder:id,name'])->paginate(20),
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
                'internal_projects' => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')->orderBy('project')->get(),
                'categories' => Category::select('id', 'name')->get(),
                'units' => Unit::select('id', 'name')->get(),
                'suppliers' => Supplier::select('id', 'name')->get(),
                'filters' => $request->all(),
            ]);
        }
    }

    /**
     * CREATE - Menampilkan form create
     */
    public function create()
    {
        try {
            $formData = $this->getFormDropdowns();

            return view('procurement.Indo-Purchase.create', $formData);
        } catch (\Exception $e) {
            Log::error('Create view error: ' . $e->getMessage());
            return redirect()
                ->route('indo-purchases.index')
                ->with('error', 'Gagal memuat halaman pembelian: ' . $e->getMessage());
        }
    }

    /**
     * STORE - Menyimpan multiple items
     */
    public function store(Request $request)
    {
        try {
            // Simpan semua input ke session
            $request->flash();

            Log::info('Store request data:', $request->all());

            // Untuk internal project, ambil department_id dari internal project
            if ($request->project_type === 'internal' && $request->filled('internal_project_id')) {
                $internalProject = InternalProject::with(['picUser', 'updateUser', 'department'])->find($request->internal_project_id);

                if ($internalProject) {
                    if ($internalProject->department_id) {
                        $request->merge(['department_id' => $internalProject->department_id]);
                        Log::info('Set department_id from internal project department_id:', [
                            'internal_project_id' => $request->internal_project_id,
                            'department_id' => $internalProject->department_id,
                        ]);
                    } elseif ($internalProject->department) {
                        $department = Department::where('name', $internalProject->department)->first();
                        if ($department) {
                            $request->merge(['department_id' => $department->id]);
                            Log::info('Set department_id from department name:', [
                                'department_name' => $internalProject->department,
                                'department_id' => $department->id,
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
                    return back()->withInput($request->all())->with('error', 'Internal project tidak ditemukan.');
                }
            }

            // Validasi items array
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.purchase_type' => 'required|in:restock,new_item',
                'items.*.material_id' => 'required_if:items.*.purchase_type,restock|exists:inventories,id',
                'items.*.new_item_name' => 'nullable|required_if:items.*.purchase_type,new_item|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.category_id' => 'nullable|required_if:items.*.purchase_type,new_item|exists:categories,id',
                'items.*.unit_id' => 'nullable|required_if:items.*.purchase_type,new_item|exists:units,id',
            ]);

            DB::beginTransaction();

            $createdItems = [];

            // Loop untuk setiap item
            foreach ($request->items as $itemData) {
                // Merge data header dengan item
                $purchaseData = array_merge($request->except(['items', '_token']), $itemData);

                // Validasi data
                $validated = $this->purchaseService->validatePurchaseRequest(new Request($purchaseData));

                Log::info('Validated data:', $validated);

                // Buat purchase order per item
                $purchase = $this->purchaseService->createPurchase($validated);
                $createdItems[] = $purchase;
            }

            DB::commit();

            return redirect()
                ->route('indo-purchases.index')
                ->with('success', 'Purchase Order ' . $request->po_number . ' created successfully (' . count($createdItems) . ' item(s)).');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation errors:', $e->errors());

            return back()->withErrors($e->errors())->withInput($request->all())->with('error', 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Order creation error: ' . $e->getMessage());

            return back()
                ->withInput($request->all())
                ->with('error', 'Failed to create Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * SHOW - Menampilkan detail PO dengan semua item
     */
    public function show($uid)
    {
        try {
            // Ambil 1 item sebagai representasi (first item)
            $purchase = IndoPurchase::with(['material:id,name', 'department:id,name', 'project:id,name', 'internalProject:id,project,job,department,department_id,description', 'jobOrder:id,name', 'category:id,name', 'unit:id,name', 'supplier:id,name', 'pic:id,username', 'approver:id,username', 'checker:id,username', 'receiver:id,username'])
                ->where('uid', $uid)
                ->first();

            if (!$purchase) {
                return redirect()
                    ->route('indo-purchases.index')
                    ->with('error', 'Data purchase order tidak ditemukan (UID: ' . $uid . ').');
            }

            // ===== DEBUGGING =====
            Log::info('=== SHOW METHOD DEBUG ===');
            Log::info('Purchase UID: ' . $uid);
            Log::info('PO Number: ' . $purchase->po_number);

            // Get items with same PO number AND same project (different project = different log)
            $poItemsQuery = IndoPurchase::where('po_number', $purchase->po_number)
                ->where('is_current', true)
                ->with(['material:id,name', 'category:id,name', 'unit:id,name']);

            if ($purchase->project_type === 'client') {
                $poItemsQuery->where('job_order_id', $purchase->job_order_id);
            } else {
                $poItemsQuery->where('internal_project_id', $purchase->internal_project_id);
            }

            $poItems = $poItemsQuery->orderBy('id')->get();

            // Hitung total PO
            $poTotal = $poItems->sum('invoice_total');

            // Hitung status items
            $receivedCount = $poItems->where('item_status', 'matched')->count();
            $pendingCount = $poItems->whereIn('item_status', ['pending', 'pending_check'])->count();

            // Revisions — scoped to same project
            $revisionsQuery = IndoPurchase::where('po_number', $purchase->po_number)
                ->orderBy('created_at', 'desc')
                ->with(['pic:id,username']);

            if ($purchase->project_type === 'client') {
                $revisionsQuery->where('job_order_id', $purchase->job_order_id);
            } else {
                $revisionsQuery->where('internal_project_id', $purchase->internal_project_id);
            }

            $revisions = $revisionsQuery->get();

            return view('procurement.Indo-Purchase.show', [
                'purchase' => $purchase,
                'poItems' => $poItems,
                'totalItems' => $poItems->count(),
                'poTotal' => $poTotal,
                'receivedCount' => $receivedCount,
                'pendingCount' => $pendingCount,
                'revisions' => $revisions,
                'revision_info' => [
                    'total_revisions' => $revisions->count(),
                    'current_revision_id' => $purchase->id,
                    'revision_number' => $revisions->where('created_at', '<=', $purchase->created_at)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Show error: ' . $e->getMessage());
            return redirect()
                ->route('indo-purchases.index')
                ->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }
    /**
     * APPROVE - Approve semua item dalam PO
     */
    public function approve(Request $request, $uid)
    {
        try {
            $validated = $request->validate([
                'resi_number' => 'nullable|string|max:255',
                'finance_notes' => 'nullable|string',
            ]);

            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

            if (!$purchase->canApprove()) {
                return back()->with('error', 'Tidak dapat menyetujui Purchase Order ini.');
            }

            if (!$purchase->isOfflineOrder() && empty($validated['resi_number'])) {
                return back()->with('error', 'Untuk order online, harus mengisi nomor resi.');
            }

            DB::beginTransaction();

            // Approve semua item dengan PO number yang sama
            $poItems = IndoPurchase::where('po_number', $purchase->po_number)->get();

            foreach ($poItems as $item) {
                if ($item->canApprove()) {
                    $this->purchaseService->approvePurchase($item, $validated);
                }
            }

            DB::commit();

            return back()->with('success', 'Purchase Order berhasil disetujui!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Order approval error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyetujui Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * REJECT - Reject semua item dalam PO
     */
    public function reject(Request $request, $uid)
    {
        try {
            $validated = $request->validate([
                'finance_notes' => 'required|string',
            ]);

            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

            if (!$purchase->canReject()) {
                return back()->with('error', 'Tidak dapat menolak Purchase Order ini.');
            }

            DB::beginTransaction();

            // Reject semua item dengan PO number yang sama
            $poItems = IndoPurchase::where('po_number', $purchase->po_number)->get();

            foreach ($poItems as $item) {
                if ($item->canReject()) {
                    $this->purchaseService->rejectPurchase($item, $validated);
                }
            }

            DB::commit();

            return back()->with('success', 'Purchase Order berhasil ditolak!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Order rejection error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menolak Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * UPDATE RESI - Update resi semua item
     */
    public function updateResi(Request $request, $uid)
    {
        try {
            $validated = $request->validate([
                'resi_number' => 'nullable|string|max:255',
            ]);

            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

            if (!$purchase->canUpdateResi()) {
                return back()->with('error', 'Tidak dapat mengupdate resi karena PO belum disetujui atau barang sudah dicek.');
            }

            if (!$purchase->isOfflineOrder() && empty($validated['resi_number'])) {
                return back()->with('error', 'Untuk order online, harus mengisi nomor resi.');
            }

            DB::beginTransaction();

            // Update resi untuk semua item dengan PO number yang sama
            $poItems = IndoPurchase::where('po_number', $purchase->po_number)->get();

            foreach ($poItems as $item) {
                $this->purchaseService->updateResiNumber($item, $validated);
            }

            DB::commit();

            return back()->with('success', 'Nomor resi berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resi update error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui nomor resi: ' . $e->getMessage());
        }
    }

    /**
     * DESTROY - Hapus item
     */
    public function destroy($uid)
    {
        try {
            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

            if (!$purchase->canDelete()) {
                return redirect()->route('indo-purchases.show', $purchase->uid)->with('error', 'Purchase Order tidak dapat dihapus.');
            }

            DB::beginTransaction();

            $materialName = $purchase->material_name;
            $poNumber = $purchase->po_number;

            // Get another item in the same PO group (same project) to redirect to after delete
            $otherItemQuery = IndoPurchase::where('po_number', $purchase->po_number)->where('is_current', true)->where('id', '!=', $purchase->id);

            if ($purchase->project_type === 'client') {
                $otherItemQuery->where('job_order_id', $purchase->job_order_id);
            } else {
                $otherItemQuery->where('internal_project_id', $purchase->internal_project_id);
            }

            $otherItem = $otherItemQuery->first();

            $purchase->delete();

            DB::commit();

            if (!$otherItem) {
                return redirect()
                    ->route('indo-purchases.index')
                    ->with('success', "Material \"{$materialName}\" deleted. PO {$poNumber} has no remaining items for this project.");
            } else {
                return redirect()
                    ->route('indo-purchases.show', $otherItem->uid)
                    ->with('success', "Material \"{$materialName}\" removed from PO {$poNumber} successfully.");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Order deletion error: ' . $e->getMessage());
            return redirect()
                ->route('indo-purchases.index')
                ->with('error', 'Failed to delete material: ' . $e->getMessage());
        }
    }

    /**
     * REQUEST DELETION - Untuk purchase yang sudah approved
     */
    public function requestDeletion(Request $request, $uid)
    {
        $request->validate([
            'deletion_reason' => 'required|string|min:5|max:500',
        ]);

        try {
            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

            if (!$purchase->canRequestDeletion()) {
                return back()->with('error', 'Hanya purchase yang sudah approved yang bisa diminta dihapus.');
            }

            DB::beginTransaction();

            // Update semua item dengan PO number yang sama
            IndoPurchase::where('po_number', $purchase->po_number)
                ->where('is_current', 1)
                ->update([
                    'status' => 'deletion_requested',
                    'deletion_reason' => $request->deletion_reason,
                    'deletion_requested_by' => auth()->id(),
                    'deletion_requested_at' => now(),
                ]);

            DB::commit();

            return redirect()
                ->route('indo-purchases.index')
                ->with('success', 'Permintaan hapus untuk Purchase ' . $purchase->po_number . ' telah dikirim ke Finance.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Request deletion error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengirim permintaan hapus: ' . $e->getMessage());
        }
    }

    /**
     * MARK AS CHECKED
     */
    public function markAsChecked(Request $request, $uid)
    {
        try {
            $validated = $request->validate([
                'item_status' => 'required|in:matched,not_matched',
                'actual_quantity' => 'nullable|numeric|min:0',
                'note' => 'nullable|string',
            ]);

            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

            if (!$purchase->canCheck()) {
                return back()->with('error', 'Tidak dapat mengecek barang karena PO belum disetujui atau sudah dicek.');
            }

            $this->purchaseService->markAsChecked($purchase, $validated);

            $statusText = $validated['item_status'] === 'matched' ? 'sesuai' : 'tidak sesuai';

            // Cek apakah semua item sudah di-check
            $poItems = IndoPurchase::where('po_number', $purchase->po_number)->get();

            $checkedCount = $poItems->whereIn('item_status', ['matched', 'not_matched'])->count();
            $allChecked = $checkedCount === $poItems->count();

            $message = 'Barang berhasil ditandai sebagai ' . $statusText . '!';
            if ($allChecked) {
                $message .= ' Semua item dalam PO sudah dicek.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Mark as checked error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menandai barang: ' . $e->getMessage());
        }
    }

    /**
     * MARK AS RECEIVED
     */
    public function markAsReceived($uid)
    {
        try {
            $purchase = IndoPurchase::with(['project:id,name', 'internalProject:id,project,job,department', 'jobOrder:id,name'])
                ->where('uid', $uid)
                ->firstOrFail();

            Log::info('Attempting to mark as received', [
                'purchase_id' => $purchase->id,
                'status' => $purchase->status,
                'item_status' => $purchase->item_status,
                'is_current' => $purchase->is_current,
                'project_type' => $purchase->project_type,
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
                    'purchase' => $purchase->toArray(),
                ]);

                return back()->with('error', $errorMessage);
            }

            $this->purchaseService->markAsReceived($purchase);

            // Cek apakah semua item sudah diterima
            $poItems = IndoPurchase::where('po_number', $purchase->po_number)->get();

            $receivedCount = $poItems->where('item_status', 'matched')->count();
            $allReceived = $receivedCount === $poItems->count();

            $message = 'Barang berhasil ditandai sebagai diterima dan ditambahkan ke inventory!';
            if ($allReceived) {
                $message .= ' Semua item dalam PO sudah diterima.';
            }

            return back()->with('success', $message);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Purchase not found for markAsReceived: ' . $e->getMessage());
            return redirect()->route('indo-purchases.index')->with('error', 'Data purchase order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Mark as received error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Gagal menandai barang sebagai diterima: ' . $e->getMessage());
        }
    }

    /**
     * BULK MARK AS RECEIVED
     */
    public function bulkReceive(Request $request)
    {
        if ($request->boolean('select_all_eligible')) {
            $uids = IndoPurchase::where('status', 'approved')
                ->whereIn('item_status', ['pending_check', 'pending'])
                ->whereNull('received_at')
                ->pluck('uid')
                ->toArray();
        } else {
            $uids = $request->input('uids', []);
        }

        if (empty($uids)) {
            return back()->with('error', 'Pilih minimal satu purchase order untuk di-receive.');
        }

        $successCount = 0;
        $skippedPos   = [];

        foreach ($uids as $uid) {
            try {
                $purchase = IndoPurchase::with(['project:id,name', 'internalProject:id,project,job,department', 'jobOrder:id,name'])
                    ->where('uid', $uid)
                    ->first();

                if (!$purchase || !$purchase->canMarkAsReceived()) {
                    if ($purchase) $skippedPos[] = $purchase->po_number;
                    continue;
                }

                $this->purchaseService->markAsReceived($purchase);
                $successCount++;
            } catch (\Exception $e) {
                Log::error('Bulk receive error uid=' . $uid . ': ' . $e->getMessage());
                $skippedPos[] = $uid;
            }
        }

        if ($successCount === 0) {
            return back()->with('error', 'Tidak ada item yang berhasil di-receive. Pastikan status PO sudah Approved dan belum pernah diterima.');
        }

        $msg = $successCount . ' purchase order berhasil ditandai received dan ditambahkan ke inventory.';
        if (!empty($skippedPos)) {
            $msg .= ' ' . count($skippedPos) . ' item dilewati: ' . implode(', ', array_slice($skippedPos, 0, 5));
        }

        return back()->with('success', $msg);
    }

    /**
     * MARK AS NOT MATCHED
     */
    public function markAsNotMatched($uid)
    {
        try {
            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

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

    /**
     * EDIT
     */
    public function edit($uid)
    {
        try {
            $purchase = IndoPurchase::with(['material:id,name', 'department:id,name', 'project:id,name', 'internalProject:id,project,job,department,department_id,description', 'jobOrder:id,name', 'category:id,name', 'unit:id,name', 'supplier:id,name', 'pic:id,username'])
                ->where('uid', $uid)
                ->firstOrFail();

            if (!$purchase->canEdit()) {
                return redirect()->route('indo-purchases.show', $purchase->uid)->with('error', 'Purchase Order tidak dapat diedit.');
            }

            // Get items with same PO number AND same project (different project = different log)
            $poItemsQuery = IndoPurchase::where('po_number', $purchase->po_number)->where('is_current', true);

            if ($purchase->project_type === 'client') {
                $poItemsQuery->where('job_order_id', $purchase->job_order_id);
            } else {
                $poItemsQuery->where('internal_project_id', $purchase->internal_project_id);
            }

            $poItems = $poItemsQuery->orderBy('id')->get();

            $revisionsQuery = IndoPurchase::where('po_number', $purchase->po_number)->orderBy('created_at', 'desc');

            if ($purchase->project_type === 'client') {
                $revisionsQuery->where('job_order_id', $purchase->job_order_id);
            } else {
                $revisionsQuery->where('internal_project_id', $purchase->internal_project_id);
            }

            $revisions = $revisionsQuery->get(['id', 'revision_at', 'status', 'item_status', 'created_at', 'is_current']);

            $formData = $this->getFormDropdowns();

            return view(
                'procurement.Indo-Purchase.edit',
                array_merge($formData, [
                    'purchase' => $purchase,
                    'poItems' => $poItems,
                    'revisions' => $revisions,
                    'revision_info' => [
                        'total_revisions' => $revisions->count(),
                        'current_revision_id' => $purchase->id,
                        'revision_number' => $revisions->where('created_at', '<=', $purchase->created_at)->count(),
                    ],
                ]),
            );
        } catch (\Exception $e) {
            Log::error('Edit error: ' . $e->getMessage());
            return redirect()->route('indo-purchases.index')->with('error', 'Terjadi kesalahan saat memuat halaman edit.');
        }
    }

    /**
     * UPDATE - Update semua item dalam PO (MULTIPLE ITEMS SUPPORT)
     */
    public function update(Request $request, $uid)
    {
        try {
            // LOG SEMUA DATA YANG MASUK
            Log::info('=== UPDATE REQUEST START ===');
            Log::info('PO UID: ' . $uid);
            Log::info('All request data:', $request->all());

            $purchase = IndoPurchase::where('uid', $uid)->firstOrFail();

            if (!$purchase->canEdit()) {
                return redirect()->route('indo-purchases.show', $purchase->uid)->with('error', 'Purchase Order tidak dapat diupdate.');
            }

            DB::beginTransaction();

            $updatedItems = [];
            $newItems = [];

            // ===== 1. PROSES EXISTING ITEMS =====
            if ($request->has('items') && is_array($request->items)) {
                Log::info('Processing ' . count($request->items) . ' existing items');

                foreach ($request->items as $index => $itemData) {
                    if (!isset($itemData['id'])) {
                        Log::warning('Item tanpa ID ditemukan:', $itemData);
                        continue;
                    }

                    $item = IndoPurchase::find($itemData['id']);

                    if (!$item) {
                        Log::warning('Item tidak ditemukan: ' . $itemData['id']);
                        continue;
                    }

                    if (!$item->canEdit()) {
                        Log::warning('Item tidak bisa diedit: ' . $item->id . ' status: ' . $item->status);
                        continue;
                    }

                    Log::info('Updating item ID: ' . $item->id, $itemData);

                    // Merge data header dengan item data
                    $mergedData = [
                        'po_number' => $purchase->po_number,
                        'date' => $request->date ?? $purchase->date,
                        'department_id' => $request->department_id ?? $purchase->department_id,
                        'supplier_id' => $request->supplier_id ?? $purchase->supplier_id,
                        'is_offline_order' => $request->is_offline_order ?? $purchase->is_offline_order,
                        'freight' => $request->freight ?? ($purchase->freight ?? 0),
                        'note' => $request->note ?? $purchase->note,
                        'project_type' => $request->project_type ?? $purchase->project_type,

                        // Item specific data
                        'purchase_type' => $itemData['purchase_type'] ?? $item->purchase_type,
                        'material_id' => $itemData['material_id'] ?? $item->material_id,
                        'new_item_name' => $itemData['new_item_name'] ?? $item->new_item_name,
                        'quantity' => $itemData['quantity'] ?? $item->quantity,
                        'unit_price' => $itemData['unit_price'] ?? $item->unit_price,
                        'category_id' => $itemData['category_id'] ?? $item->category_id,
                        'unit_id' => $itemData['unit_id'] ?? $item->unit_id,
                    ];

                    // Tambahkan project specific fields
                    if ($mergedData['project_type'] === 'client') {
                        $mergedData['project_id'] = $request->project_id ?? $item->project_id;
                        $mergedData['job_order_id'] = $request->job_order_id ?? $item->job_order_id;
                        $mergedData['internal_project_id'] = null;
                    } else {
                        $mergedData['internal_project_id'] = $request->internal_project_id ?? $item->internal_project_id;
                        $mergedData['project_id'] = null;
                        $mergedData['job_order_id'] = null;
                    }

                    // Validasi data
                    $validated = $this->purchaseService->validatePurchaseRequest(new Request($mergedData), $item->id);

                    // Update item (buat revisi baru)
                    $updatedItem = $this->purchaseService->updatePurchase($item, $validated);
                    $updatedItems[] = $updatedItem;

                    Log::info('Item updated successfully, new revision ID: ' . $updatedItem->id);
                }
            }

            // ===== 2. PROSES NEW ITEMS =====
            if ($request->has('new_items') && is_array($request->new_items)) {
                Log::info('Processing ' . count($request->new_items) . ' new items');

                foreach ($request->new_items as $index => $itemData) {
                    Log::info('Creating new item:', $itemData);

                    // Merge dengan data header
                    $mergedData = [
                        'po_number' => $purchase->po_number,
                        'date' => $request->date ?? $purchase->date,
                        'department_id' => $request->department_id ?? $purchase->department_id,
                        'supplier_id' => $request->supplier_id ?? $purchase->supplier_id,
                        'is_offline_order' => $request->is_offline_order ?? $purchase->is_offline_order,
                        'freight' => 0, // New items don't have freight
                        'note' => $request->note ?? $purchase->note,
                        'project_type' => $request->project_type ?? $purchase->project_type,

                        // Item specific data
                        'purchase_type' => $itemData['purchase_type'] ?? 'restock',
                        'material_id' => $itemData['material_id'] ?? null,
                        'new_item_name' => $itemData['new_item_name'] ?? null,
                        'quantity' => $itemData['quantity'] ?? 1,
                        'unit_price' => $itemData['unit_price'] ?? 0,
                        'category_id' => $itemData['category_id'] ?? null,
                        'unit_id' => $itemData['unit_id'] ?? null,
                    ];

                    // Tambahkan project specific fields
                    if ($mergedData['project_type'] === 'client') {
                        $mergedData['project_id'] = $request->project_id ?? $purchase->project_id;
                        $mergedData['job_order_id'] = $request->job_order_id ?? $purchase->job_order_id;
                        $mergedData['internal_project_id'] = null;
                    } else {
                        $mergedData['internal_project_id'] = $request->internal_project_id ?? $purchase->internal_project_id;
                        $mergedData['project_id'] = null;
                        $mergedData['job_order_id'] = null;
                    }

                    // Validasi data
                    $validated = $this->purchaseService->validatePurchaseRequest(new Request($mergedData));

                    // Buat item baru
                    $newItem = $this->purchaseService->createPurchase($validated);
                    $newItems[] = $newItem;

                    Log::info('New item created with ID: ' . $newItem->id);
                }
            }

            DB::commit();

            $totalUpdated = count($updatedItems);
            $totalNew = count($newItems);
            $poNum = $purchase->po_number;

            if ($totalUpdated > 0 && $totalNew > 0) {
                $message = "PO {$poNum} updated: {$totalUpdated} material(s) updated, {$totalNew} material(s) added.";
            } elseif ($totalNew > 0) {
                $message = "Successfully added {$totalNew} material(s) to PO {$poNum}.";
            } else {
                $message = "PO {$poNum} updated successfully.";
            }

            Log::info('=== UPDATE REQUEST COMPLETED ===');

            $redirectUid = !empty($updatedItems) ? $updatedItems[0]->uid : (!empty($newItems) ? $newItems[0]->uid : $purchase->uid);
            return redirect()->route('indo-purchases.show', $redirectUid)->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation errors:', $e->errors());

            return back()->withErrors($e->errors())->withInput($request->all())->with('error', 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Order update error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()
                ->withInput($request->all())
                ->with('error', 'Failed to update Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * GET MATERIAL PRICE
     */
    public function getMaterialPrice($id)
    {
        try {
            $material = Inventory::with(['unit:id,name', 'category:id,name'])->find($id);

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material tidak ditemukan',
                    'price' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'price' => $material->price ?? 0,
                'unit_id' => $material->unit_id ?? null,
                'unit_name' => $material->unit->name ?? null,
                'category_id' => $material->category_id ?? null,
                'category_name' => $material->category->name ?? null,
                'material_name' => $material->name ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Get material price error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'price' => 0,
            ]);
        }
    }

    /**
     * GET JOB ORDER DETAILS
     */
    public function getJobOrderDetails($id)
    {
        try {
            $jobOrder = JobOrder::with(['department:id,name', 'project:id,name'])->find($id);

            if (!$jobOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job Order tidak ditemukan',
                    'department_id' => null,
                    'project_id' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'department_id' => $jobOrder->department_id,
                'department_name' => $jobOrder->department->name ?? null,
                'project_id' => $jobOrder->project_id,
                'project_name' => $jobOrder->project->name ?? null,
                'job_order_name' => $jobOrder->name ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Get job order details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'department_id' => null,
                'project_id' => null,
            ]);
        }
    }

    /**
     * GET INTERNAL PROJECT DETAILS
     */
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
                'department' => null,
            ]);
        }
    }

    /**
     * EXPORT
     */
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

    /**
     * PRINT - Cetak semua item dalam PO
     */
    public function print($uid)
    {
        try {
            $purchase = IndoPurchase::with(['material:id,name', 'department:id,name', 'project:id,name', 'internalProject:id,project,job,department,department_id,description', 'jobOrder:id,name', 'category:id,name', 'unit:id,name', 'supplier:id,name,address', 'pic:id,username', 'approver:id,username'])
                ->where('uid', $uid)
                ->firstOrFail();

            // Ambil semua item dengan PO number yang sama
            $poItems = IndoPurchase::where('po_number', $purchase->po_number)
                ->with(['material:id,name', 'category:id,name', 'unit:id,name'])
                ->orderBy('id')
                ->get();

            $poTotal = $poItems->sum('invoice_total');

            return view('procurement.Indo-Purchase.print', compact('purchase', 'poItems', 'poTotal'));
        } catch (\Exception $e) {
            Log::error('Print error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencetak purchase order: ' . $e->getMessage());
        }
    }

    /**
     * GET MATERIALS
     */
    public function getMaterials()
    {
        try {
            $materials = Inventory::with(['unit:id,name', 'category:id,name'])
                ->select('id', 'name', 'unit_id', 'category_id')
                ->get();

            return response()->json([
                'success' => true,
                'materials' => $materials,
            ]);
        } catch (\Exception $e) {
            Log::error('Get materials error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data material',
            ]);
        }
    }

    /**
     * GET PO ITEMS - API untuk mengambil semua item dalam PO
     */
    public function getPOItems($poNumber)
    {
        try {
            $items = IndoPurchase::where('po_number', $poNumber)
                ->with(['material', 'unit', 'category'])
                ->get();

            return response()->json([
                'success' => true,
                'items' => $items,
                'total_items' => $items->count(),
                'total_amount' => $items->sum('invoice_total'),
            ]);
        } catch (\Exception $e) {
            Log::error('Get PO items error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data items',
            ]);
        }
    }

    /**
     * AJAX: cari material by name — dipakai Select2 di form create/edit.
     * Menggantikan @foreach 4414 option di HTML (penyebab utama loading lambat).
     */
    public function searchMaterials(Request $request)
    {
        $q = $request->input('q', '');

        $items = Inventory::with(['unitRelation:id,name', 'category:id,name'])
            ->select('id', 'name', 'unit_id', 'unit', 'category_id')
            ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function ($m) {
                // Use unit_name accessor (FK → fallback varchar)
                $unitId = $m->unit_id;
                $unitName = $m->unit_name ?: null;

                return [
                    'id' => $m->id,
                    'text' => $m->name,
                    'unit_id' => $unitId,
                    'unit_name' => $unitName ?? '',
                    'category_id' => $m->category_id,
                    'category_name' => $m->category->name ?? '',
                ];
            });

        return response()->json(['results' => $items]);
    }

    /**
     * Shared dropdown data untuk form create & edit.
     * Di-cache 10 menit — data ini jarang berubah.
     * Inventory (4414 item), JobOrders (626), Suppliers (383) adalah penyebab utama loading lambat.
     */
    private function getFormDropdowns(): array
    {
        $jobOrders = Cache::remember(
            'pp_job_orders',
            300,
            fn() => JobOrder::with(['department:id,name', 'project:id,name'])
                ->select('id', 'name', 'department_id', 'project_id')
                ->get()
                ->map(
                    fn($jo) => [
                        'id' => $jo->id,
                        'name' => $jo->name,
                        'department_id' => $jo->department_id,
                        'department_name' => $jo->department->name ?? 'N/A',
                        'project_id' => $jo->project_id,
                        'project_name' => $jo->project->name ?? 'N/A',
                    ],
                ),
        );

        $suppliers = Cache::remember('pp_suppliers', 600, fn() => Supplier::select('id', 'name')->orderBy('name')->get());

        $supplierLocations = Cache::remember('pp_supplier_locations', 600, fn() => \App\Models\Procurement\LocationSupplier::select('id', 'name')->get());

        $departments = Cache::remember('pp_departments', 600, fn() => Department::select('id', 'name')->orderBy('name')->get());

        $projects = Cache::remember('pp_projects', 300, fn() => Project::select('id', 'name')->orderBy('name')->get());

        $internalProjects = Cache::remember('pp_internal_projects', 600, fn() => InternalProject::select('id', 'project', 'job', 'department', 'department_id', 'description')->orderBy('project')->get());

        $categories = Cache::remember('pp_categories', 3600, fn() => Category::select('id', 'name')->orderBy('name')->get());

        $units = Cache::remember('pp_units', 3600, fn() => Unit::select('id', 'name')->orderBy('name')->get());

        $currencies = Cache::remember('pp_currencies', 3600, fn() => \App\Models\Finance\Currency::select('id', 'name')->orderBy('name')->get());

        return [
            'jobOrders' => $jobOrders,
            'suppliers' => $suppliers,
            'supplierLocations' => $supplierLocations,
            'departments' => $departments,
            'projects' => $projects,
            'internal_projects' => $internalProjects,
            'categories' => $categories,
            'units' => $units,
            'currencies' => $currencies,
        ];
    }
}
