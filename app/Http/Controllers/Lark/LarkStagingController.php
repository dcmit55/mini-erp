<?php

namespace App\Http\Controllers\Lark;

use App\Http\Controllers\Controller;
use App\Models\Lark\LarkBtSgCourierId;
use App\Models\Lark\LarkBtSgItemTracking;
use App\Models\Lark\LarkSgBtCourierId;
use App\Models\Lark\LarkSgBtItemTracking;
use App\Models\Lark\LarkStagingInventory;
use App\Models\Logistic\Inventory;
use App\Services\Lark\LarkStagingSyncService;
use App\Services\Lark\LarkInventoryStagingSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class LarkStagingController extends Controller
{
    private LarkStagingSyncService $syncService;
    private LarkInventoryStagingSyncService $inventoryStagingService;

    public function __construct(LarkStagingSyncService $syncService, LarkInventoryStagingSyncService $inventoryStagingService)
    {
        $this->middleware('auth');
        $this->syncService = $syncService;
        $this->inventoryStagingService = $inventoryStagingService;
    }

    /**
     * Show BT-SG Courier IDs
     */
    public function btSgCourierIndex(Request $request)
    {
        if ($request->ajax()) {
            $query = LarkBtSgCourierId::with('items')->select('lark_bt_sg_courier_ids.*')->latest('lark_bt_sg_courier_ids.last_sync_at');

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name ?: '-';
                })
                ->editColumn('type_movement', function ($row) {
                    return $row->type_movement ?: '-';
                })
                ->editColumn('date', function ($row) {
                    return $row->date ? $row->date->format('d M Y') : '-';
                })
                ->addColumn('items_summary', function ($row) {
                    $count = $row->items->count();
                    $totalQty = $row->items_total_qty;
                    if ($count === 0) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="badge bg-info">' . $count . ' items</span> <span class="badge bg-secondary">' . $totalQty . ' qty</span>';
                })
                ->addColumn('items_list', function ($row) {
                    return $row->items_list;
                })
                ->editColumn('project_lark', function ($row) {
                    return $row->project_lark ? substr($row->project_lark, 0, 50) . '...' : '-';
                })
                ->editColumn('transport_cost', function ($row) {
                    return $row->transport_cost ? 'Rp ' . number_format($row->transport_cost, 0, ',', '.') : '-';
                })
                ->editColumn('baggage_cost', function ($row) {
                    return $row->baggage_cost ? 'Rp ' . number_format($row->baggage_cost, 0, ',', '.') : '-';
                })
                ->editColumn('gst_cost', function ($row) {
                    return $row->gst_cost ? 'Rp ' . number_format($row->gst_cost, 0, ',', '.') : '-';
                })
                ->addColumn('total_cost', function ($row) {
                    return ' ' . number_format($row->total_cost_sgd, 2);
                })
                ->editColumn('qty_total', function ($row) {
                    return $row->qty_total ?: '0';
                })
                ->editColumn('cost_per_item', function ($row) {
                    return $row->cost_per_item ? ' ' . number_format($row->cost_per_item, 0, ',', '.') : '-';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->last_sync_at ? $row->last_sync_at->format('d M Y H:i') : '-';
                })
                ->rawColumns(['total_cost', 'items_summary', 'items_list'])
                ->make(true);
        }

        $stats = [
            'total' => LarkBtSgCourierId::count(),
            'today' => LarkBtSgCourierId::whereDate('last_sync_at', today())->count(),
            'total_cost' => LarkBtSgCourierId::sum('transport_cost') + LarkBtSgCourierId::sum('baggage_cost') + LarkBtSgCourierId::sum('gst_cost'),
        ];

        return view('lark.staging.bt_sg_courier', compact('stats'));
    }

    /**
     * Show BT-SG Item Tracking
     */
    public function btSgItemIndex(Request $request)
    {
        if ($request->ajax()) {
            $query = LarkBtSgItemTracking::with(['courier'])->latest('last_sync_at');

            // Filter by project
            if ($request->filled('project')) {
                $query->where('project_lark', $request->project);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('item_name', function ($row) {
                    return $row->item_name ?: '-';
                })
                ->editColumn('status', function ($row) {
                    return $row->status ?: '-';
                })
                ->editColumn('qty', function ($row) {
                    return $row->qty ?? '-';
                })
                ->editColumn('sgd_cost', function ($row) {
                    return $row->sgd_cost ? ' ' . number_format($row->sgd_cost, 2) : '-';
                })
                ->addColumn('project', function ($row) {
                    return $row->project_name ?: '-';
                })
                ->addColumn('courier', function ($row) {
                    return $row->courier?->name ?? '-';
                })
                ->addColumn('status_badge', function ($row) {
                    $colors = [
                        'Pending' => 'warning',
                        'In Transit' => 'info',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'SG Recieved' => 'success',
                    ];
                    $color = $colors[$row->status] ?? 'secondary';
                    return "<span class='badge bg-{$color}'>{$row->status}</span>";
                })
                ->editColumn('last_sync_at', function ($row) {
                    return $row->last_sync_at->format('d M Y H:i');
                })
                ->rawColumns(['status_badge'])
                ->make(true);
        }

        $stats = [
            'total' => LarkBtSgItemTracking::count(),
            'today' => LarkBtSgItemTracking::whereDate('last_sync_at', today())->count(),
            'total_qty' => LarkBtSgItemTracking::sum('qty'),
            'with_project' => LarkBtSgItemTracking::whereNotNull('project_lark')->count(),
        ];

        // Get unique projects and statuses for filters
        $projects = LarkBtSgItemTracking::whereNotNull('project_lark')->distinct()->pluck('project_lark')->filter()->sort()->values();

        $statuses = LarkBtSgItemTracking::whereNotNull('status')->distinct()->pluck('status')->filter()->sort()->values();

        return view('lark.staging.bt_sg_items', compact('stats', 'projects', 'statuses'));
    }

    /**
     * Show SG-BT Courier IDs
     */
    public function sgBtCourierIndex(Request $request)
    {
        if ($request->ajax()) {
            $query = LarkSgBtCourierId::with('items')->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name ?: '-';
                })
                ->editColumn('date', function ($row) {
                    return $row->date ? $row->date->format('d M Y') : '-';
                })
                ->addColumn('items_summary', function ($row) {
                    $count = $row->items->count();
                    $totalQty = $row->items_total_qty;
                    if ($count === 0) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="badge bg-info">' . $count . ' items</span> <span class="badge bg-secondary">' . $totalQty . ' qty</span>';
                })
                ->addColumn('items_list', function ($row) {
                    return $row->items_list;
                })
                ->editColumn('transport_cost', function ($row) {
                    return $row->transport_cost ? 'Rp ' . number_format($row->transport_cost, 0, ',', '.') : '-';
                })
                ->editColumn('baggage_cost', function ($row) {
                    return $row->baggage_cost ? 'Rp ' . number_format($row->baggage_cost, 0, ',', '.') : '-';
                })
                ->editColumn('gst_cost', function ($row) {
                    return $row->gst_cost ? 'Rp ' . number_format($row->gst_cost, 0, ',', '.') : '-';
                })
                ->addColumn('total_cost', function ($row) {
                    return ' ' . number_format($row->total_cost_sgd, 2);
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y H:i');
                })
                ->rawColumns(['total_cost', 'items_summary', 'items_list'])
                ->make(true);
        }

        $stats = [
            'total' => LarkSgBtCourierId::count(),
            'today' => LarkSgBtCourierId::whereDate('created_at', today())->count(),
            'total_cost' => LarkSgBtCourierId::sum('transport_cost') + LarkSgBtCourierId::sum('baggage_cost') + LarkSgBtCourierId::sum('gst_cost'),
        ];

        return view('lark.staging.sg_bt_courier', compact('stats'));
    }

    /**
     * Show SG-BT Item Tracking
     */
    public function sgBtItemIndex(Request $request)
    {
        if ($request->ajax()) {
            $query = LarkSgBtItemTracking::with(['courier'])->latest('last_sync_at');

            // Filter by project
            if ($request->filled('project')) {
                $query->where('project_lark', $request->project);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('courier', function ($row) {
                    return $row->courier?->name ?? '-';
                })
                ->editColumn('item_name', function ($row) {
                    return $row->item_name ?: '-';
                })
                ->editColumn('status', function ($row) {
                    return $row->status ?: '-';
                })
                ->editColumn('qty', function ($row) {
                    return $row->qty ?? '-';
                })
                ->editColumn('sgd_cost', function ($row) {
                    return $row->sgd_cost ? ' ' . number_format($row->sgd_cost, 2) : '-';
                })
                ->addColumn('project', function ($row) {
                    return $row->project_name ?: '-';
                })
                ->addColumn('status_badge', function ($row) {
                    $colors = [
                        'Pending' => 'warning',
                        'In Transit' => 'info',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'BT Recieved' => 'success',
                    ];
                    $color = $colors[$row->status] ?? 'secondary';
                    return "<span class='badge bg-{$color}'>{$row->status}</span>";
                })
                ->editColumn('last_sync_at', function ($row) {
                    return $row->last_sync_at->format('d M Y H:i');
                })
                ->rawColumns(['status_badge'])
                ->make(true);
        }

        $stats = [
            'total' => LarkSgBtItemTracking::count(),
            'today' => LarkSgBtItemTracking::whereDate('last_sync_at', today())->count(),
            'total_qty' => LarkSgBtItemTracking::sum('qty'),
            'with_project' => LarkSgBtItemTracking::whereNotNull('project_lark')->count(),
        ];

        // Get unique projects and statuses for filters
        $projects = LarkSgBtItemTracking::whereNotNull('project_lark')->distinct()->pluck('project_lark')->filter()->sort()->values();

        $statuses = LarkSgBtItemTracking::whereNotNull('status')->distinct()->pluck('status')->filter()->sort()->values();

        return view('lark.staging.sg_bt_items', compact('stats', 'projects', 'statuses'));
    }

    /**
     * Sync BT-SG Courier from Lark
     */
    public function syncBtSgCourier()
    {
        try {
            $stats = $this->syncService->syncBtSgCourier();

            $message = sprintf('BT-SG Courier sync completed! Fetched: %d | Created: %d | Updated: %d', $stats['fetched'], $stats['created'], $stats['updated']);

            if ($stats['errors'] > 0) {
                $message .= sprintf(' | Errors: %d', $stats['errors']);
                return redirect()->route('lark.staging.bt-sg-courier')->with('warning', $message);
            }

            return redirect()->route('lark.staging.bt-sg-courier')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('BT-SG Courier sync failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('lark.staging.bt-sg-courier')
                ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Sync BT-SG Items from Lark
     */
    public function syncBtSgItems()
    {
        try {
            $stats = $this->syncService->syncBtSgItems();

            $message = sprintf('BT-SG Items sync completed! Fetched: %d | Created: %d | Updated: %d', $stats['fetched'], $stats['created'], $stats['updated']);

            if ($stats['errors'] > 0) {
                $message .= sprintf(' | Errors: %d', $stats['errors']);
                return redirect()->route('lark.staging.bt-sg-items')->with('warning', $message);
            }

            return redirect()->route('lark.staging.bt-sg-items')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('BT-SG Items sync failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('lark.staging.bt-sg-items')
                ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Sync SG-BT Courier from Lark
     */
    public function syncSgBtCourier()
    {
        try {
            $stats = $this->syncService->syncSgBtCourier();

            $message = sprintf('SG-BT Courier sync completed! Fetched: %d | Created: %d | Updated: %d', $stats['fetched'], $stats['created'], $stats['updated']);

            if ($stats['errors'] > 0) {
                $message .= sprintf(' | Errors: %d', $stats['errors']);
                return redirect()->route('lark.staging.sg-bt-courier')->with('warning', $message);
            }

            return redirect()->route('lark.staging.sg-bt-courier')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('SG-BT Courier sync failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('lark.staging.sg-bt-courier')
                ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Sync SG-BT Items from Lark
     */
    public function syncSgBtItems()
    {
        try {
            $stats = $this->syncService->syncSgBtItems();

            $message = sprintf('SG-BT Items sync completed! Fetched: %d | Created: %d | Updated: %d', $stats['fetched'], $stats['created'], $stats['updated']);

            if ($stats['errors'] > 0) {
                $message .= sprintf(' | Errors: %d', $stats['errors']);
                return redirect()->route('lark.staging.sg-bt-items')->with('warning', $message);
            }

            return redirect()->route('lark.staging.sg-bt-items')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('SG-BT Items sync failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('lark.staging.sg-bt-items')
                ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // LARK STAGING INVENTORY
    // =========================================================================

    /**
     * Show Lark Staging Inventory listing
     * Data di sini berasal dari sync Lark, belum masuk ke inventory resmi.
     * Admin bisa review, approve, atau reject masing-masing item.
     */
    public function inventoryIndex(Request $request)
    {
        if ($request->ajax()) {
            $query = LarkStagingInventory::query()->latest('last_sync_at');

            // Filter by review_status
            if ($request->filled('review_status')) {
                $query->where('review_status', $request->review_status);
            }

            // Filter by project
            if ($request->filled('project')) {
                $query->where('project_lark', 'like', '%' . $request->project . '%');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('name', function ($row) {
                    return $row->name ?: '-';
                })
                ->editColumn('quantity', function ($row) {
                    $unit = $row->unit ? ' ' . $row->unit : '';
                    return number_format($row->quantity, 2) . $unit;
                })
                ->editColumn('price', function ($row) {
                    return $row->price > 0 ? number_format($row->price, 2) . ' RMB' : '-';
                })
                ->editColumn('project_lark', function ($row) {
                    return $row->project_lark ?: '-';
                })
                ->editColumn('supplier_lark', function ($row) {
                    return $row->supplier_lark ?: '-';
                })
                ->editColumn('source_record_count', function ($row) {
                    return $row->source_record_count > 1 ? '<span class="badge bg-info">' . $row->source_record_count . ' records</span>' : '<span class="badge bg-secondary">1 record</span>';
                })
                ->addColumn('review_status_badge', function ($row) {
                    return $row->review_status_badge;
                })
                ->editColumn('last_sync_at', function ($row) {
                    return $row->last_sync_at ? $row->last_sync_at->format('d M Y H:i') : '-';
                })
                ->addColumn('actions', function ($row) {
                    $approveBtn = $row->review_status !== 'approved' ? '<button class="btn btn-success btn-xs btn-approve me-1" data-id="' . $row->id . '" title="Approve & Push to Inventory"><i class="bi bi-check-lg"></i></button>' : '';
                    $rejectBtn = $row->review_status !== 'rejected' ? '<button class="btn btn-danger btn-xs btn-reject me-1" data-id="' . $row->id . '" title="Reject"><i class="bi bi-x-lg"></i></button>' : '';
                    $resetBtn = $row->review_status !== 'pending' ? '<button class="btn btn-secondary btn-xs btn-reset me-1" data-id="' . $row->id . '" title="Reset to Pending"><i class="bi bi-arrow-counterclockwise"></i></button>' : '';
                    return $approveBtn . $rejectBtn . $resetBtn;
                })
                ->rawColumns(['checkbox', 'review_status_badge', 'source_record_count', 'actions'])
                ->make(true);
        }

        $stats = [
            'total' => LarkStagingInventory::count(),
            'pending' => LarkStagingInventory::where('review_status', 'pending')->count(),
            'approved' => LarkStagingInventory::where('review_status', 'approved')->count(),
            'rejected' => LarkStagingInventory::where('review_status', 'rejected')->count(),
        ];

        $lastSync = LarkStagingInventory::max('last_sync_at');

        return view('lark.staging.inventory', compact('stats', 'lastSync'));
    }

    /**
     * Sync inventory data dari Lark ke staging table
     * Data TIDAK langsung masuk ke inventories - harus di-approve dulu
     */
    public function syncInventory()
    {
        try {
            $stats = $this->inventoryStagingService->syncToStaging();

            $message = sprintf('Lark Inventory Staging sync selesai! Fetched: %d | Filtered: %d | Aggregated: %d materials | Created: %d | Updated: %d | Skipped: %d', $stats['fetched'], $stats['filtered'], $stats['aggregated_groups'] ?? 0, $stats['created'], $stats['updated'], $stats['skipped']);

            if ($stats['errors'] > 0) {
                $message .= sprintf(' | Errors: %d', $stats['errors']);
                return redirect()->route('lark.staging.inventory')->with('warning', $message);
            }

            return redirect()->route('lark.staging.inventory')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Lark inventory staging sync failed', ['error' => $e->getMessage()]);
            return redirect()
                ->route('lark.staging.inventory')
                ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve staging item → push ke tabel inventories
     * AJAX endpoint
     */
    public function approveInventory(Request $request, int $id)
    {
        try {
            $staging = LarkStagingInventory::findOrFail($id);

            DB::transaction(function () use ($staging, $request) {
                // Upsert ke inventories - cari by name saja (source bisa belum ada)
                // Jika sudah ada inventory dengan nama sama (manual), update datanya
                $inventory = Inventory::withTrashed()->where('name', $staging->name)->first();

                if ($inventory) {
                    // Restore jika soft-deleted
                    if ($inventory->trashed()) {
                        $inventory->restore();
                    }
                    $inventory->update([
                        'lark_record_id' => $staging->source_record_ids ?: $staging->lark_record_id,
                        'project_lark' => $staging->project_lark,
                        'quantity' => $staging->quantity,
                        'unit' => $staging->unit,
                        'price' => $staging->price,
                        'currency_id' => $staging->currency_id ?? 6,
                        'supplier_lark' => $staging->supplier_lark,
                        'img' => $staging->img,
                        'last_sync_at' => now(),
                        'source' => 'lark',
                    ]);
                } else {
                    $inventory = Inventory::create([
                        'name' => $staging->name,
                        'lark_record_id' => $staging->source_record_ids ?: $staging->lark_record_id,
                        'project_lark' => $staging->project_lark,
                        'quantity' => $staging->quantity,
                        'unit' => $staging->unit,
                        'price' => $staging->price,
                        'currency_id' => $staging->currency_id ?? 6,
                        'supplier_lark' => $staging->supplier_lark,
                        'img' => $staging->img,
                        'last_sync_at' => now(),
                        'source' => 'lark',
                    ]);
                }

                // Update staging status
                $staging->update([
                    'review_status' => 'approved',
                    'review_note' => $request->input('note'),
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

                Log::info('Staging inventory approved and pushed to inventory', [
                    'staging_id' => $staging->id,
                    'inventory_id' => $inventory->id,
                    'name' => $staging->name,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => "Item <strong>{$staging->name}</strong> telah di-approve dan masuk ke Inventory Listing.",
            ]);
        } catch (\Exception $e) {
            Log::error('Staging approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal approve: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Reject staging item
     * AJAX endpoint
     */
    public function rejectInventory(Request $request, int $id)
    {
        try {
            $staging = LarkStagingInventory::findOrFail($id);

            $staging->update([
                'review_status' => 'rejected',
                'review_note' => $request->input('note'),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Item <strong>{$staging->name}</strong> ditolak.",
            ]);
        } catch (\Exception $e) {
            Log::error('Staging reject failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal reject: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Reset staging item to pending
     * AJAX endpoint
     */
    public function resetInventory(int $id)
    {
        try {
            $staging = LarkStagingInventory::findOrFail($id);

            $staging->update([
                'review_status' => 'pending',
                'review_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Item <strong>{$staging->name}</strong> direset ke pending.",
            ]);
        } catch (\Exception $e) {
            Log::error('Staging reset failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal reset: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Bulk approve selected staging items → push to inventories
     * Accepts: ids[] = array of staging IDs to approve
     * If ids not provided, approves ALL pending items
     */
    public function bulkApproveInventory(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (!empty($ids)) {
                // Approve only selected IDs (any review_status)
                $items = LarkStagingInventory::whereIn('id', $ids)->get();
            } else {
                // Fallback: approve all pending
                $items = LarkStagingInventory::where('review_status', 'pending')->get();
            }

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada item yang dipilih untuk di-approve.',
                ]);
            }

            $approved = 0;
            $errors = 0;
            $userId = auth()->id();

            foreach ($items as $staging) {
                try {
                    DB::transaction(function () use ($staging, $userId) {
                        // Cari by name saja, tidak pakai source di WHERE
                        $inventory = Inventory::withTrashed()->where('name', $staging->name)->first();

                        if ($inventory) {
                            if ($inventory->trashed()) {
                                $inventory->restore();
                            }
                            $inventory->update([
                                'lark_record_id' => $staging->source_record_ids ?: $staging->lark_record_id,
                                'project_lark' => $staging->project_lark,
                                'quantity' => $staging->quantity,
                                'unit' => $staging->unit,
                                'price' => $staging->price,
                                'currency_id' => $staging->currency_id ?? 6,
                                'supplier_lark' => $staging->supplier_lark,
                                'img' => $staging->img,
                                'last_sync_at' => now(),
                                'source' => 'lark',
                            ]);
                        } else {
                            Inventory::create([
                                'name' => $staging->name,
                                'lark_record_id' => $staging->source_record_ids ?: $staging->lark_record_id,
                                'project_lark' => $staging->project_lark,
                                'quantity' => $staging->quantity,
                                'unit' => $staging->unit,
                                'price' => $staging->price,
                                'currency_id' => $staging->currency_id ?? 6,
                                'supplier_lark' => $staging->supplier_lark,
                                'img' => $staging->img,
                                'last_sync_at' => now(),
                                'source' => 'lark',
                            ]);
                        }

                        $staging->update([
                            'review_status' => 'approved',
                            'reviewed_by' => $userId,
                            'reviewed_at' => now(),
                        ]);
                    });
                    $approved++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Bulk approve item failed', [
                        'staging_id' => $staging->id,
                        'name' => $staging->name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $message = "Bulk approve selesai! Approved: {$approved}";
            if ($errors > 0) {
                $message .= " | Errors: {$errors}";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => compact('approved', 'errors'),
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk approve failed', ['error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Bulk approve gagal: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
