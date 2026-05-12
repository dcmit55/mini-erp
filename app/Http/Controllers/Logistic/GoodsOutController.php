<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Admin\User;
use App\Models\Production\Project;
use App\Models\Logistic\GoodsOut;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\StockUsageBatch;
use Illuminate\Http\Request;
use App\Models\Logistic\MaterialRequest;
use App\Helpers\MaterialUsageHelper;
use App\Events\GoodsOutProcessed;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GoodsOutExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoodsOutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:logistic.goods-out.view');
        $this->middleware('can:logistic.goods-out.create')->only(['create', 'store', 'storeIndependent', 'bulkGoodsOut', 'createWithId']);
        $this->middleware('can:logistic.goods-out.edit')->only(['edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        // If AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->getDataTablesData($request);
        }

        // For non-AJAX requests, return view with master data for filters
        $materials = Inventory::orderBy('name')->get(['id', 'name', 'unit']);
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('username')->get();

        return view('logistic.goods_out.index', compact('materials', 'projects', 'users'));
    }

    public function getDataTablesData(Request $request)
    {
        $query = GoodsOut::with(['inventory', 'project', 'goodsIns', 'materialRequest', 'user.department', 'inventoryBatch'])->latest();

        // Apply filters
        if ($request->filled('material_filter')) {
            $query->where('inventory_id', $request->material_filter);
        }

        if ($request->filled('project_filter')) {
            $query->where('project_id', $request->project_filter);
        }

        if ($request->filled('requested_by_filter')) {
            $query->where('requested_by', $request->requested_by_filter);
        }

        if ($request->filled('proceeded_at_filter')) {
            $query->whereDate('created_at', $request->proceeded_at_filter);
        }

        // Custom search functionality
        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('inventory', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                })
                    ->orWhereHas('project', function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%');
                    })
                    ->orWhere('requested_by', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('remark', 'LIKE', '%' . $searchValue . '%');
            });
        }

        // DataTables search
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('inventory', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                })
                    ->orWhereHas('project', function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%');
                    })
                    ->orWhere('requested_by', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('remark', 'LIKE', '%' . $searchValue . '%');
            });
        }

        // Sorting
        $columns = ['id', 'inventory_id', 'quantity', 'project_id', 'requested_by', 'created_at', 'remark'];

        if ($request->filled('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');

            if ($orderColumnIndex == 1) {
                // Material column
                $query->join('inventories', 'goods_out.inventory_id', '=', 'inventories.id')->orderBy('inventories.name', $orderDirection)->select('goods_out.*');
            } elseif ($orderColumnIndex == 3) {
                // Project column
                $query->join('projects', 'goods_out.project_id', '=', 'projects.id')->orderBy('projects.name', $orderDirection)->select('goods_out.*');
            } elseif (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get total and filtered counts
        $totalRecords = GoodsOut::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $goodsOuts = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        foreach ($goodsOuts as $index => $goodsOut) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'material' => $goodsOut->inventory ? $goodsOut->inventory->name : '(No material)',
                'batch_used' => '<button class="btn btn-sm btn-outline-secondary btn-batch-used" data-id="' . $goodsOut->id . '" data-material="' . e($goodsOut->inventory?->name ?? '') . '" title="View batches used"><i class="bi bi-layers"></i></button>',
                'quantity' => $this->formatQuantity($goodsOut),
                'remaining_quantity' => $this->formatRemainingQuantity($goodsOut),
                'project' => $goodsOut->project ? $goodsOut->project->name : '(No project)',
                'job_order' => $goodsOut->jobOrder ? $goodsOut->jobOrder->name : '-',
                'requested_by' => $this->formatRequestedBy($goodsOut),
                'created_at' => $goodsOut->created_at->format('d M Y, H:i'),
                'remark' => $goodsOut->remark ?? '-',
                'actions' => $this->getActionButtons($goodsOut),
                'DT_RowId' => 'row-' . $goodsOut->id,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function formatQuantity($goodsOut)
    {
        $unit = $goodsOut->inventory ? $goodsOut->inventory->unit_name : '';
        $quantity = number_format($goodsOut->quantity, 2);
        $quantity = rtrim(rtrim($quantity, '0'), '.');
        return '<span data-bs-toggle="tooltip" data-bs-placement="right" title="' . $unit . '">' . $quantity . '</span>';
    }

    private function formatRemainingQuantity($goodsOut)
    {
        $unit = $goodsOut->inventory ? $goodsOut->inventory->unit_name : '';
        $remainingQuantity = number_format($goodsOut->remaining_quantity, 2);
        $remainingQuantity = rtrim(rtrim($remainingQuantity, '0'), '.');
        return '<span data-bs-toggle="tooltip" data-bs-placement="right" title="' . $unit . '">' . $remainingQuantity . '</span>';
    }

    private function formatRequestedBy($goodsOut)
    {
        $department = $goodsOut->user && $goodsOut->user->department ? $goodsOut->user->department->name : '';

        if ($department) {
            return '<span data-bs-toggle="tooltip" data-bs-placement="right" title="' . ucfirst($department) . '">' . ucfirst($goodsOut->requested_by) . '</span>';
        }

        return ucfirst($goodsOut->requested_by);
    }

    private function getActionButtons($goodsOut)
    {
        $buttons = '<div class="d-flex flex-nowrap gap-1">';

        // Edit button - only for admin_logistic and super_admin
        if (auth()->user()->can('logistic.goods-out.edit')) {
            $buttons .=
                '<a href="' .
                route('goods_out.edit', $goodsOut->id) .
                '" class="btn btn-sm btn-primary" title="Edit">
                <i class="bi bi-pencil-square"></i>
            </a>';

            // Check delete permission using model method
            if ($goodsOut->canBeDeleted()) {
                $tooltip = $goodsOut->getDeleteTooltip();
                $buttons .=
                    '<button type="button" class="btn btn-sm btn-danger btn-delete"
                    data-id="' .
                    $goodsOut->id .
                    '"
                    data-material="' .
                    ($goodsOut->inventory ? $goodsOut->inventory->name : 'Unknown') .
                    '"
                    title="' .
                    $tooltip .
                    '">
                    <i class="bi bi-trash"></i>
                </button>';
            } else {
                // Show disabled delete button with tooltip explaining why
                $tooltip = $goodsOut->getDeleteTooltip();
                $buttons .=
                    '<button type="button" class="btn btn-sm btn-secondary" disabled
                    title="' .
                    $tooltip .
                    '">
                    <i class="bi bi-trash"></i>
                </button>';
            }
        }

        $buttons .= '</div>';
        return $buttons;
    }

    public function export(Request $request)
    {
        // Ambil filter dari request
        $material = $request->material;
        $qty = $request->qty;
        $project = $request->project;
        $requestedBy = $request->requested_by;
        $requestedAt = $request->requested_at;

        // Filter data berdasarkan request
        $query = GoodsOut::with('inventory', 'project');

        if ($material) {
            $query->where('inventory_id', $material);
        }

        if ($qty) {
            $query->where('quantity', $qty);
        }

        if ($project) {
            $query->where('project_id', $project);
        }

        if ($requestedBy) {
            $query->where('requested_by', $requestedBy);
        }

        if ($requestedAt) {
            $query->whereDate('created_at', $requestedAt);
        }

        $goodsOuts = $query->get();

        // Buat nama file dinamis
        $fileName = 'goods_out';
        if ($material) {
            $materialName = Inventory::find($material)->name ?? 'Unknown Material';
            $fileName .= '_material-' . str_replace(' ', '-', strtolower($materialName));
        }
        if ($qty) {
            $fileName .= '_qty-' . $qty;
        }
        if ($project) {
            $projectName = Project::find($project)->name ?? 'Unknown Project';
            $fileName .= '_project-' . str_replace(' ', '-', strtolower($projectName));
        }
        if ($requestedBy) {
            $fileName .= '_requested_by-' . strtolower($requestedBy);
        }
        if ($requestedAt) {
            $fileName .= '_proceed_at-' . $requestedAt;
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        // Ekspor data menggunakan kelas GoodsOutExport
        return Excel::download(new GoodsOutExport($goodsOuts), $fileName);
    }

    public function create($materialRequestId)
    {
        $materialRequest = MaterialRequest::with('inventory', 'stagingInventory', 'indoPurchase.unit', 'project', 'internalProject')->findOrFail($materialRequestId);

        // Jika incoming source, pastikan material sudah siap sebelum Goods Out
        if ($materialRequest->inventory_source === 'incoming') {
            // ── Gate: Indo Purchase source ──────────────────────────────────
            if ($materialRequest->indo_purchase_id) {
                $purchase = $materialRequest->indoPurchase;
                if (!$purchase) {
                    return redirect()->route('material_requests.index')->with('error', 'Goods Out tidak dapat diproses. Data Indo Purchase tidak ditemukan.');
                }
                $poOk = in_array($purchase->status, ['approved', 'received']);
                $receiptOk = in_array($purchase->item_status, ['received', 'approved', 'done', 'matched']);
                if (!$poOk || !$receiptOk) {
                    $poStatus = ucfirst($purchase->status ?? '-');
                    $receiptStatus = ucfirst($purchase->item_status ?? '-');
                    return redirect()
                        ->route('material_requests.index')
                        ->with('error', "Goods Out tidak dapat diproses. Material Request ini menggunakan <b>Indo Purchase</b>. PO Status harus <b>Approved</b> dan Receipt Status harus <b>Received</b> terlebih dahulu. (PO saat ini: <b>{$poStatus}</b>, Receipt: <b>{$receiptStatus}</b>)");
                }

                // ── Auto-resolve inventory_id if not yet linked (existing data) ──
                if (!$materialRequest->inventory_id) {
                    $resolvedInventoryId = null;
                    if ($purchase->purchase_type === 'restock' && $purchase->material_id) {
                        $resolvedInventoryId = $purchase->material_id;
                    } else {
                        // new_item: find inventory created from this purchase batch
                        $batch = \App\Models\Logistic\InventoryBatch::where('source_type', 'indo_purchase')->where('source_id', $purchase->id)->first();
                        if ($batch) {
                            $resolvedInventoryId = $batch->inventory_id;
                        }
                    }
                    if ($resolvedInventoryId) {
                        $materialRequest->inventory_id = $resolvedInventoryId;
                        $materialRequest->save();
                        $materialRequest->setRelation('inventory', \App\Models\Logistic\Inventory::find($resolvedInventoryId));
                    }
                }
            }

            // ── Gate: Lark Staging source ────────────────────────────────────
            $staging = $materialRequest->stagingInventory;
            if (!$materialRequest->indo_purchase_id) {
                if (!$staging || !$staging->processed) {
                    return redirect()
                        ->route('material_requests.index')
                        ->with('error', 'Goods Out tidak dapat diproses. Material Request ini menggunakan <b>Inventory Incoming</b> dan staging inventory (<b>' . e($staging->name ?? '-') . '</b>) belum di-approve ke Inventory Batch. Hubungi Admin Logistik.');
                }
                // Jika inventory_id di MR belum terisi (data lama), auto-resolve dari staging
                if (!$materialRequest->inventory_id && $staging->processed) {
                    $resolvedInventory = \App\Models\Logistic\Inventory::whereHas('batches', function ($q) use ($staging) {
                        $q->where('source_type', 'lark')->where('source_id', $staging->id);
                    })->first();
                    if ($resolvedInventory) {
                        $materialRequest->inventory_id = $resolvedInventory->id;
                        $materialRequest->save();
                        $materialRequest->setRelation('inventory', $resolvedInventory);
                    }
                }
            }
        }

        $inventories = Inventory::withComputedStock()->orderBy('name')->get();
        return view('logistic.goods_out.create', compact('materialRequest', 'inventories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_request_id' => 'required|exists:material_requests,id',
            'quantity' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Lock inventory row
            $materialRequest = MaterialRequest::where('id', $request->material_request_id)->lockForUpdate()->first();

            // ── Auto-resolve inventory_id for indo_purchase MRs if not yet linked ──
            if (!$materialRequest->inventory_id && $materialRequest->indo_purchase_id) {
                $purchase = $materialRequest->indoPurchase;
                if ($purchase) {
                    if ($purchase->purchase_type === 'restock' && $purchase->material_id) {
                        $materialRequest->inventory_id = $purchase->material_id;
                        $materialRequest->save();
                    } else {
                        $batch = \App\Models\Logistic\InventoryBatch::where('source_type', 'indo_purchase')->where('source_id', $purchase->id)->first();
                        if ($batch) {
                            $materialRequest->inventory_id = $batch->inventory_id;
                            $materialRequest->save();
                        }
                    }
                }
            }

            $inventory = $materialRequest->inventory_id ? Inventory::where('id', $materialRequest->inventory_id)->lockForUpdate()->first() : null;

            if (!$inventory) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Inventory tidak ditemukan. Material Request ini belum terhubung ke Inventory Batch.');
            }

            // VALIDASI: Quantity tidak boleh melebihi Remaining Quantity
            $remainingQty = $materialRequest->qty - $materialRequest->processed_qty;
            if ($request->quantity > $remainingQty) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Quantity cannot exceed the remaining requested quantity.');
            }

            // Validasi tambahan: stok inventory
            if ($request->quantity > $inventory->quantity) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Quantity cannot exceed the available inventory.');
            }

            // Tambahkan ke processed_qty
            $materialRequest->processed_qty += $request->quantity;

            // Update status jika sudah selesai
            if ($materialRequest->processed_qty >= $materialRequest->qty) {
                $materialRequest->status = 'delivered';
            }

            $materialRequest->save();

            event(new \App\Events\MaterialRequestUpdated($materialRequest, 'status'));

            // Simpan Goods Out
            $isIncoming = $materialRequest->inventory_source === 'incoming';
            $specificBatch = null;

            if ($isIncoming && $materialRequest->staging_inventory_id) {
                // Untuk incoming: pakai HANYA batch Lark yang berasal dari staging ini
                $specificBatch = \App\Models\Logistic\InventoryBatch::where('inventory_id', $inventory->id)->where('source_type', 'lark')->where('source_id', $materialRequest->staging_inventory_id)->where('qty_remaining', '>', 0)->lockForUpdate()->first();

                if (!$specificBatch || $specificBatch->qty_remaining < $request->quantity) {
                    DB::rollBack();
                    $available = $specificBatch ? $specificBatch->qty_remaining : 0;
                    return back()
                        ->withInput()
                        ->with('error', "Qty tidak mencukupi di batch incoming yang dipilih. Tersedia: {$available}, Diminta: {$request->quantity}");
                }
            }

            // Capture primary batch for traceability
            $primaryBatch = $isIncoming && $specificBatch ? $specificBatch : $inventory->activeBatches()->orderBy('received_date')->orderBy('id')->first();

            $goodsOut = GoodsOut::create([
                'material_request_id' => $materialRequest->id,
                'inventory_id' => $inventory->id,
                'inventory_batch_id' => $primaryBatch?->id,
                'project_id' => $materialRequest->project_id,
                'job_order_id' => $materialRequest->job_order_id,
                'requested_by' => $materialRequest->requested_by,
                'quantity' => $request->quantity,
                'remark' => $request->remark,
            ]);

            // Kurangi stok: incoming = specific batch only, stock = FIFO
            if ($isIncoming && $specificBatch) {
                $consumed = $specificBatch->consume($request->quantity);
                StockUsageBatch::create([
                    'goods_out_id' => $goodsOut->id,
                    'batch_id' => $specificBatch->id,
                    'qty_used' => $consumed,
                ]);
            } else {
                $usedBatches = $inventory->consumeStock($request->quantity);
                foreach ($usedBatches as $ub) {
                    StockUsageBatch::create([
                        'goods_out_id' => $goodsOut->id,
                        'batch_id' => $ub['batch_id'],
                        'qty_used' => $ub['qty'],
                    ]);
                }
            }

            MaterialUsageHelper::sync($inventory->id, $materialRequest->project_id, $materialRequest->job_order_id);

            DB::commit();

            // DISABLED: GoodsOutProcessed popup notification (annoying, disabled by request)
            // try {
            //     event(new GoodsOutProcessed($goodsOut));
            // } catch (\Exception $broadcastEx) {
            //     \Illuminate\Support\Facades\Log::warning('GoodsOut broadcast failed (non-critical): ' . $broadcastEx->getMessage());
            // }

            return redirect()
                ->route('goods_out.index')
                ->with('success', "Goods Out <b>{$inventory->name}</b> to <b>{$materialRequest->project_name}</b> processed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to process Goods Out: ' . $e->getMessage());
        }
    }

    public function createIndependent()
    {
        $inventories = Inventory::withComputedStock()->orderBy('name')->get();
        $projects = Project::with('departments', 'status')->notArchived()->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::with(['project:id,name', 'department:id,name'])
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id', 'department_id']);
        $users = User::with('department')->orderBy('username')->get();
        return view('logistic.goods_out.create_independent', compact('inventories', 'projects', 'jobOrders', 'users'));
    }

    public function storeIndependent(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Validate job_order belongs to project
            $jobOrder = \App\Models\Production\JobOrder::findOrFail($request->job_order_id);
            if ($jobOrder->project_id != $request->project_id) {
                return back()
                    ->withInput()
                    ->withErrors(['job_order_id' => 'Job Order does not belong to the selected project.']);
            }
            // Lock inventory row
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();
            $user = User::with('department')->findOrFail($request->user_id);

            // Validasi quantity setelah lock
            if ($request->quantity > $inventory->quantity) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['quantity' => 'Quantity cannot exceed the available inventory.']);
            }

            // Kurangi stok di inventory
            // Capture primary (first FIFO) batch for traceability
            $primaryBatch = $inventory->activeBatches()->orderBy('received_date')->orderBy('id')->first();
            $usedBatches = $inventory->consumeStock($request->quantity);

            // Simpan Goods Out
            $goodsOut = GoodsOut::create([
                'inventory_id' => $request->inventory_id,
                'inventory_batch_id' => $primaryBatch?->id,
                'project_id' => $request->project_id,
                'job_order_id' => $request->job_order_id,
                'requested_by' => $user->username,
                'quantity' => $request->quantity,
                'remark' => $request->remark,
            ]);

            // Catat batch yang digunakan (FIFO)
            foreach ($usedBatches as $ub) {
                StockUsageBatch::create([
                    'goods_out_id' => $goodsOut->id,
                    'batch_id' => $ub['batch_id'],
                    'qty_used' => $ub['qty'],
                ]);
            }

            // Sync Material Usage
            MaterialUsageHelper::sync($request->inventory_id, $request->project_id, $request->job_order_id);

            DB::commit();

            // Response JSON untuk AJAX jika diperlukan
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Goods Out {$inventory->name} created successfully.",
                ]);
            }

            return redirect()
                ->route('goods_out.index')
                ->with('success', "Goods Out <b>{$inventory->name}</b> created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Failed to process Goods Out: ' . $e->getMessage(),
                    ],
                    500,
                );
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to process Goods Out: ' . $e->getMessage());
        }
    }

    public function bulkGoodsOut(Request $request)
    {
        $request->validate([
            'goods_out_qty' => 'required|array',
            'goods_out_qty.*' => 'numeric|min:0.001',
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:material_requests,id',
        ]);

        $selectedIds = array_keys($request->goods_out_qty);

        DB::beginTransaction();
        try {
            $materialRequests = MaterialRequest::whereIn('id', $selectedIds)->where('status', 'approved')->lockForUpdate()->get();

            if ($materialRequests->isEmpty()) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No approved material requests found for bulk goods out.'], 422);
            }

            $updatedRequests = [];
            $createdGoodsOuts = []; // Track created goods out for notifications

            foreach ($materialRequests as $materialRequest) {
                $inventory = Inventory::where('id', $materialRequest->inventory_id)->lockForUpdate()->first();

                $remainingQty = $materialRequest->qty - $materialRequest->processed_qty;
                $qtyToGoodsOut = $request->goods_out_qty[$materialRequest->id];

                // Validasi qty
                if ($qtyToGoodsOut > $remainingQty) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Qty to Goods Out for Material Request {$materialRequest->id} exceeds remaining qty."], 422);
                }
                if ($qtyToGoodsOut > $inventory->quantity) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Insufficient stock for {$inventory->name}."], 422);
                }
                if ($qtyToGoodsOut <= 0) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Qty to Goods Out must be greater than 0.'], 422);
                }

                // Kurangi stok inventory
                $isMrIncoming = $materialRequest->inventory_source === 'incoming';
                $specificBatchBulk = null;

                if ($isMrIncoming && $materialRequest->staging_inventory_id) {
                    $specificBatchBulk = \App\Models\Logistic\InventoryBatch::where('inventory_id', $inventory->id)->where('source_type', 'lark')->where('source_id', $materialRequest->staging_inventory_id)->where('qty_remaining', '>', 0)->lockForUpdate()->first();

                    if (!$specificBatchBulk || $specificBatchBulk->qty_remaining < $qtyToGoodsOut) {
                        DB::rollBack();
                        $avail = $specificBatchBulk ? $specificBatchBulk->qty_remaining : 0;
                        return response()->json(['success' => false, 'message' => "Qty tidak mencukupi di batch incoming untuk MR #{$materialRequest->id}. Tersedia: {$avail}"], 422);
                    }
                }

                // Capture primary batch for traceability
                $primaryBatch = $isMrIncoming && $specificBatchBulk ? $specificBatchBulk : $inventory->activeBatches()->orderBy('received_date')->orderBy('id')->first();

                // Buat Goods Out
                $goodsOut = GoodsOut::create([
                    'material_request_id' => $materialRequest->id,
                    'inventory_id' => $inventory->id,
                    'inventory_batch_id' => $primaryBatch?->id,
                    'project_id' => $materialRequest->project_id,
                    'job_order_id' => $materialRequest->job_order_id,
                    'requested_by' => $materialRequest->requested_by,
                    'quantity' => $qtyToGoodsOut,
                    'remark' => 'Bulk Goods Out',
                ]);

                // Catat batch yang digunakan
                if ($isMrIncoming && $specificBatchBulk) {
                    $consumed = $specificBatchBulk->consume($qtyToGoodsOut);
                    StockUsageBatch::create([
                        'goods_out_id' => $goodsOut->id,
                        'batch_id' => $specificBatchBulk->id,
                        'qty_used' => $consumed,
                    ]);
                } else {
                    $usedBatches = $inventory->consumeStock($qtyToGoodsOut);
                    foreach ($usedBatches as $ub) {
                        StockUsageBatch::create([
                            'goods_out_id' => $goodsOut->id,
                            'batch_id' => $ub['batch_id'],
                            'qty_used' => $ub['qty'],
                        ]);
                    }
                }

                $createdGoodsOuts[] = $goodsOut; // Store for notification

                // Update processed_qty dan status material request
                $materialRequest->processed_qty += $qtyToGoodsOut;
                if ($materialRequest->processed_qty >= $materialRequest->qty) {
                    $materialRequest->status = 'delivered';
                }
                $materialRequest->save();

                $updatedRequests[] = $materialRequest->fresh(['inventory', 'project']);

                MaterialUsageHelper::sync($inventory->id, $materialRequest->project_id, $materialRequest->job_order_id);
            }

            DB::commit();

            // Broadcast real-time ke semua client
            foreach ($updatedRequests as $mr) {
                event(new \App\Events\MaterialRequestUpdated($mr, 'status'));
            }

            // DISABLED: GoodsOutProcessed popup notification (annoying, disabled by request)
            // foreach ($createdGoodsOuts as $goodsOut) {
            //     try {
            //         event(new GoodsOutProcessed($goodsOut));
            //     } catch (\Exception $broadcastEx) {
            //         \Illuminate\Support\Facades\Log::warning('GoodsOut bulk broadcast failed (non-critical): ' . $broadcastEx->getMessage());
            //     }
            // }

            return response()->json(['success' => true, 'message' => 'Bulk Goods Out processed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Bulk Goods Out failed: ' . $e->getMessage()], 500);
        }
    }

    public function getDetails(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:goods_out,id',
        ]);

        $goodsOuts = GoodsOut::whereIn('id', $request->selected_ids)
            ->with('inventory')
            ->get()
            ->map(function ($goodsOut) {
                return [
                    'id' => $goodsOut->id,
                    'material_name' => $goodsOut->inventory->name,
                    'goods_out_quantity' => $goodsOut->quantity,
                ];
            });

        return response()->json($goodsOuts);
    }

    public function edit($id)
    {
        $goodsOut = GoodsOut::with('inventory', 'project', 'materialRequest', 'jobOrder.department')->findOrFail($id);
        $inventories = Inventory::withComputedStock()->orderBy('name')->get();
        $projects = Project::with('departments', 'status')->notArchived()->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::with(['project:id,name', 'department:id,name'])
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id', 'department_id']);
        $users = User::with('department')->orderBy('username')->get();

        $fromMaterialRequest = $goodsOut->material_request_id ? true : false;

        return view('logistic.goods_out.edit', compact('goodsOut', 'inventories', 'projects', 'jobOrders', 'users', 'fromMaterialRequest'));
    }

    public function update(Request $request, $id)
    {
        $goodsOut = GoodsOut::findOrFail($id);

        // Jika dari Material Request, pakai project_id dan job_order_id lama
        if ($goodsOut->material_request_id) {
            $request->merge([
                'project_id' => $goodsOut->project_id,
                'job_order_id' => $goodsOut->job_order_id,
                'inventory_id' => $goodsOut->inventory_id,
                'user_id' => User::where('username', $goodsOut->requested_by)->value('id'),
            ]);
        }

        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string',
        ]);

        // Validate job_order belongs to project
        if (!$goodsOut->material_request_id) {
            $jobOrder = \App\Models\Production\JobOrder::findOrFail($request->job_order_id);
            if ($jobOrder->project_id != $request->project_id) {
                return back()
                    ->withInput()
                    ->withErrors(['job_order_id' => 'Job Order does not belong to the selected project.']);
            }
        }

        DB::beginTransaction();
        try {
            $goodsOut = GoodsOut::lockForUpdate()->findOrFail($id);
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();
            $materialRequest = $goodsOut->materialRequest;
            $user = User::with('department')->findOrFail($request->user_id);

            $oldQuantity = $goodsOut->quantity;

            // VALIDASI: Quantity tidak boleh melebihi Remaining Quantity (jika ada material request)
            if ($materialRequest) {
                $remainingQty = $materialRequest->qty - ($materialRequest->processed_qty - $oldQuantity);
                if ($request->quantity > $remainingQty) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['quantity' => 'Quantity cannot exceed the remaining requested quantity.']);
                }
            }

            // Validasi stok inventory (add back old quantity temporarily for calculation)
            $availableAfterReturn = $inventory->quantity + $oldQuantity;
            if ($request->quantity > $availableAfterReturn) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['quantity' => 'Quantity cannot exceed the available inventory.']);
            }

            // Kurangi stok dengan quantity baru (return lama dulu, consume baru)
            $inventory->returnStock($oldQuantity);
            $usedBatches = $inventory->consumeStock($request->quantity);

            // Hapus stock_usage_batches lama, ganti dengan yang baru
            StockUsageBatch::where('goods_out_id', $goodsOut->id)->delete();
            foreach ($usedBatches as $ub) {
                StockUsageBatch::create([
                    'goods_out_id' => $goodsOut->id,
                    'batch_id' => $ub['batch_id'],
                    'qty_used' => $ub['qty'],
                ]);
            }

            // Perbarui Material Request dengan quantity baru
            if ($materialRequest) {
                // Kembalikan processed_qty lama
                $materialRequest->processed_qty -= $oldQuantity;
                // Tambahkan processed_qty baru
                $materialRequest->processed_qty += $request->quantity;

                // Perbarui status jika quantity habis
                if ($materialRequest->processed_qty >= $materialRequest->qty) {
                    $materialRequest->status = 'delivered';
                } else {
                    $materialRequest->status = 'approved';
                }

                $materialRequest->save();
            }

            $department = $user->department ? $user->department->name : null;

            // Perbarui Goods Out
            $goodsOut->update([
                'inventory_id' => $request->inventory_id,
                'project_id' => $request->project_id,
                'job_order_id' => $request->job_order_id,
                'requested_by' => $user->username,
                'quantity' => $request->quantity,
                'remark' => $request->remark,
            ]);

            MaterialUsageHelper::sync($goodsOut->inventory_id, $goodsOut->project_id, $goodsOut->job_order_id);

            DB::commit();

            return redirect()
                ->route('goods_out.index')
                ->with('success', "Goods Out <b>{$inventory->name}</b> to <b>{$materialRequest->project_name}</b> processed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update Goods Out: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        $goodsOut = GoodsOut::withTrashed()->findOrFail($id);

        // Restore Goods Out
        $goodsOut->restore();

        // Kurangi stok di inventory
        $inventory = $goodsOut->inventory;
        if ($inventory) {
            $usedBatches = $inventory->consumeStock($goodsOut->quantity);
            foreach ($usedBatches as $ub) {
                StockUsageBatch::create([
                    'goods_out_id' => $goodsOut->id,
                    'batch_id' => $ub['batch_id'],
                    'qty_used' => $ub['qty'],
                ]);
            }
        }

        // Sinkronkan Material Usage
        MaterialUsageHelper::sync($goodsOut->inventory_id, $goodsOut->project_id, $goodsOut->job_order_id);

        return redirect()->route('goods_out.index')->with('success', 'Goods Out restored successfully.');
    }

    public function destroy($id)
    {
        $goodsOut = GoodsOut::findOrFail($id);

        // Check permission using model method
        if (!$goodsOut->canBeDeleted()) {
            $message = "You don't have permission to delete this Goods Out.";

            // More specific error messages
            if ($goodsOut->goodsIns()->exists()) {
                $message = "Cannot delete Goods Out <b>{$goodsOut->id}</b> with related Goods In.";
            } elseif ($goodsOut->material_request_id && !auth()->user()->isSuperAdmin()) {
                $message = 'Cannot delete Goods Out from Material Request. Super Admin access required.';
            }

            if (request()->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $message,
                    ],
                    403,
                ); // 403 Forbidden
            }

            return redirect()->route('goods_out.index')->with('error', $message);
        }

        // Continue with normal deletion process
        $inventory = $goodsOut->inventory;
        $materialName = $inventory->name;
        $projectName = $goodsOut->project ? $goodsOut->project->name : 'No Project';
        $materialRequest = $goodsOut->materialRequest;

        DB::beginTransaction();
        try {
            // If from material request, update material request status
            if ($materialRequest) {
                // Reduce processed_qty from material request
                $materialRequest->processed_qty -= $goodsOut->quantity;

                // Update status based on remaining quantity
                if ($materialRequest->processed_qty <= 0) {
                    $materialRequest->status = 'approved';
                } elseif ($materialRequest->processed_qty < $materialRequest->qty) {
                    $materialRequest->status = 'approved';
                }

                $materialRequest->save();

                // Broadcast the change
                event(new \App\Events\MaterialRequestUpdated($materialRequest, 'status'));
            }

            // Return stock to inventory
            $inventory->returnStock($goodsOut->quantity);

            // Soft delete Goods Out
            $goodsOut->delete();

            // Sync material usage setelah delete (termasuk null project)
            MaterialUsageHelper::sync($goodsOut->inventory_id, $goodsOut->project_id, $goodsOut->job_order_id);

            DB::commit();

            $successMessage = "Goods Out <b>{$materialName}</b> to <b>{$projectName}</b> deleted successfully.";

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                ]);
            }

            return redirect()->route('goods_out.index')->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();

            $errorMessage = 'Failed to delete Goods Out: ' . $e->getMessage();

            if (request()->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $errorMessage,
                    ],
                    500,
                );
            }

            return redirect()->route('goods_out.index')->with('error', $errorMessage);
        }
    }

    /**
     * Return batch breakdown used for a specific goods_out (for the Batch Used modal).
     * GET /goods-out/{id}/batch-usage
     */
    public function getBatchUsage($id)
    {
        $goodsOut = GoodsOut::with(['stockUsageBatches.batch', 'inventory.unitRelation'])->findOrFail($id);

        $unit = $goodsOut->inventory?->unit_name ?? '';

        $batches = $goodsOut->stockUsageBatches->map(function ($sub) use ($unit) {
            return [
                'batch_number' => $sub->batch?->batch_number ?? '—',
                'qty_used' => (float) $sub->qty_used,
                'unit' => $unit,
            ];
        });

        return response()->json(['batches' => $batches]);
    }
}
