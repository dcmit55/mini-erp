<?php

namespace App\Http\Controllers\Lark;

use App\Http\Controllers\Controller;
use App\Models\Lark\LarkBtSgCourierId;
use App\Models\Lark\LarkBtSgItemTracking;
use App\Models\Lark\LarkSgBtCourierId;
use App\Models\Lark\LarkSgBtItemTracking;
use App\Models\Lark\LarkStagingInventory;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\Unit;
use App\Models\Logistic\Category;
use App\Services\Lark\LarkStagingSyncService;
use App\Services\Lark\LarkInventoryStagingSyncService;
use App\Services\Logistic\StagingInventoryApprovalService;
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
            $query = LarkStagingInventory::query()->orderByRaw("FIELD(review_status, 'pending', 'rejected', 'approved')")->orderBy('last_sync_at', 'desc');

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
                    $name = e($row->name ?: '-');
                    // Wrap in span so JS can update it in-place after edit
                    return '<span class="staging-name-text" data-id="' . $row->id . '">' . $name . '</span>';
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
                ->editColumn('order_date', function ($row) {
                    if (!$row->order_date) {
                        return '-';
                    }
                    try {
                        return \Carbon\Carbon::parse($row->order_date)->format('d M Y');
                    } catch (\Exception $e) {
                        return $row->order_date;
                    }
                })
                ->editColumn('pic', function ($row) {
                    return $row->pic ?: '-';
                })
                ->editColumn('international_waybill', function ($row) {
                    return $row->international_waybill ?: '-';
                })
                ->editColumn('source_record_count', function ($row) {
                    return $row->source_record_count ?? 1;
                })
                ->addColumn('review_status_badge', function ($row) {
                    return $row->review_status_badge;
                })
                ->editColumn('last_sync_at', function ($row) {
                    return $row->last_sync_at ? $row->last_sync_at->format('d M Y H:i') : '-';
                })
                ->addColumn('received_qty_input', function ($row) {
                    if ($row->locked) {
                        // Show read-only value when locked
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">' . '<i class="bi bi-lock-fill me-1 small"></i>' . number_format((float) $row->received_qty, 2) . ($row->unit ? ' ' . e($row->unit) : '') . '</span>';
                    }
                    $val = $row->received_qty !== null ? number_format((float) $row->received_qty, 2) : '';
                    return '<div class="input-group input-group-sm" style="min-width:120px;">' . '<input type="number" step="0.01" min="0.01" class="form-control form-control-sm received-qty-input" ' . 'data-id="' . $row->id . '" value="' . $val . '" placeholder="Enter qty" style="max-width:90px;">' . '<button class="btn btn-outline-secondary btn-xs btn-save-received-qty" data-id="' . $row->id . '" title="Save">' . '<i class="bi bi-check-lg"></i></button>' . '</div>';
                })
                ->addColumn('review_note_display', function ($row) {
                    if (empty($row->review_note)) {
                        return '-';
                    }
                    $note = e($row->review_note);
                    return '<span class="text-truncate d-inline-block" style="max-width:160px;" data-bs-toggle="tooltip" title="' . $note . '">' . $note . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    if ($row->locked) {
                        // Locked = approved; show lock icon and reset button only
                        $resetBtn = '<button class="btn btn-secondary btn-xs btn-reset me-1" data-id="' . $row->id . '" title="Reset to Pending"><i class="bi bi-arrow-counterclockwise"></i></button>';
                        return '<span class="badge bg-success me-1" title="Approved &amp; Locked"><i class="bi bi-lock-fill"></i></span>' . $resetBtn;
                    }
                    $editBtn = '<button class="btn btn-outline-primary btn-sm btn-edit-item me-1" data-id="' . $row->id . '" data-name="' . e($row->name) . '" data-unit="' . e($row->unit) . '" data-price="' . ($row->price ?? '') . '" title="Edit Name, Unit &amp; Price" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-pencil-fill"></i></button>';
                    $approveBtn = $row->review_status !== 'approved' ? '<button class="btn btn-success btn-sm btn-approve me-1" data-id="' . $row->id . '" title="Approve &amp; Push to Inventory"><i class="bi bi-check-lg"></i></button>' : '';
                    $rejectBtn = $row->review_status !== 'rejected' ? '<button class="btn btn-danger btn-sm btn-reject me-1" data-id="' . $row->id . '" title="Reject"><i class="bi bi-x-lg"></i></button>' : '';
                    $resetBtn = $row->review_status !== 'pending' ? '<button class="btn btn-secondary btn-sm btn-reset me-1" data-id="' . $row->id . '" title="Reset to Pending"><i class="bi bi-arrow-counterclockwise"></i></button>' : '';
                    return $editBtn . $approveBtn . $rejectBtn . $resetBtn;
                })
                ->rawColumns(['checkbox', 'name', 'review_note_display', 'review_status_badge', 'received_qty_input', 'actions'])
                ->make(true);
        }

        $stats = [
            'total' => LarkStagingInventory::count(),
            'pending' => LarkStagingInventory::where('review_status', 'pending')->count(),
            'approved' => LarkStagingInventory::where('review_status', 'approved')->count(),
            'rejected' => LarkStagingInventory::where('review_status', 'rejected')->count(),
        ];

        $lastSync = LarkStagingInventory::max('last_sync_at');
        $units = Unit::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('lark.staging.inventory', compact('stats', 'lastSync', 'units', 'categories'));
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
     * Update received_qty for a staging item (AJAX)
     * Admin enters actual received quantity before approving.
     */
    public function updateReceivedQty(Request $request, int $id)
    {
        try {
            $request->validate([
                'received_qty' => ['required', 'numeric', 'min:0.01'],
            ]);

            $staging = LarkStagingInventory::findOrFail($id);

            if ($staging->locked) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Item ini sudah di-approve dan terkunci. Received Qty tidak dapat diubah.',
                    ],
                    422,
                );
            }

            $staging->update(['received_qty' => $request->received_qty]);

            return response()->json([
                'success' => true,
                'message' => 'Received Qty berhasil disimpan.',
                'received_qty' => number_format((float) $staging->received_qty, 2),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->errors()['received_qty'][0] ?? 'Validasi gagal.',
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Update received_qty failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Update item name on a staging record.
     * Allows admin to rename before approve so it matches the known inventory name.
     * AJAX endpoint
     */
    /**
     * Update both item name and unit in a single combined AJAX call.
     * Replaces the separate updateName / updateUnit endpoints for the staging inventory edit modal.
     */
    public function updateItem(Request $request, int $id)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'unit' => ['required', 'string', 'max:100'],
                'price' => ['nullable', 'numeric', 'min:0'],
            ]);

            $staging = LarkStagingInventory::findOrFail($id);

            if ($staging->locked) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Item ini sudah di-approve dan terkunci. Data tidak dapat diubah.',
                    ],
                    422,
                );
            }

            $updateData = [
                'name' => trim($request->name),
                'unit' => trim($request->unit),
            ];
            if ($request->filled('price')) {
                $updateData['price'] = $request->price;
            }

            $staging->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil diperbarui.',
                'name' => $staging->name,
                'unit' => $staging->unit,
                'price' => $staging->price,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Update staging item failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function updateName(Request $request, int $id)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $staging = LarkStagingInventory::findOrFail($id);

            if ($staging->locked) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Item ini sudah di-approve dan terkunci. Nama tidak dapat diubah.',
                    ],
                    422,
                );
            }

            $staging->update(['name' => trim($request->name)]);

            return response()->json([
                'success' => true,
                'message' => 'Nama berhasil diperbarui.',
                'name' => $staging->name,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->errors()['name'][0] ?? 'Validasi gagal.',
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Update staging name failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Update item unit on a staging record.
     * Allows admin to change unit before approve so it matches the inventory unit.
     * AJAX endpoint
     */
    public function updateUnit(Request $request, int $id)
    {
        try {
            $request->validate([
                'unit' => ['required', 'string', 'max:100'],
            ]);

            $staging = LarkStagingInventory::findOrFail($id);

            if ($staging->locked) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Item ini sudah di-approve dan terkunci. Unit tidak dapat diubah.',
                    ],
                    422,
                );
            }

            $staging->update(['unit' => trim($request->unit)]);

            return response()->json([
                'success' => true,
                'message' => 'Unit berhasil diperbarui.',
                'unit' => $staging->unit,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->errors()['unit'][0] ?? 'Validasi gagal.',
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Update staging unit failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Update item price on a staging record.
     * Allows admin to set/fix price before approve.
     * AJAX endpoint
     */
    public function updatePrice(Request $request, int $id)
    {
        try {
            $request->validate([
                'price' => ['required', 'numeric', 'min:0.01'],
            ]);

            $staging = LarkStagingInventory::findOrFail($id);

            if ($staging->locked) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Item ini sudah di-approve dan terkunci. Harga tidak dapat diubah.',
                    ],
                    422,
                );
            }

            $staging->update(['price' => $request->price]);

            return response()->json([
                'success' => true,
                'message' => 'Harga berhasil diperbarui.',
                'price' => $staging->price,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Update staging price failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                ],
                500,
            );
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
            $service = new StagingInventoryApprovalService();

            $service->approve($staging, reviewedBy: auth()->id(), reviewNote: $request->input('note'));

            return response()->json([
                'success' => true,
                'message' => "Item <strong>{$staging->name}</strong> telah di-approve dan masuk ke Inventory Stock.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
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

            $wasLocked = $staging->locked; // capture before update

            $staging->update([
                'review_status' => 'pending',
                'review_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'locked' => false,
                'processed' => false,
            ]);

            $warning = $wasLocked ? ' <span class="text-warning small">(Catatan: data inventory yang sudah dibuat tidak otomatis dihapus)</span>' : '';

            return response()->json([
                'success' => true,
                'message' => "Item <strong>{$staging->name}</strong> direset ke pending." . $warning,
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
                $items = LarkStagingInventory::whereIn('id', $ids)->get();
            } else {
                $items = LarkStagingInventory::where('review_status', 'pending')->get();
            }

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada item yang dipilih untuk di-approve.',
                ]);
            }

            $service = new StagingInventoryApprovalService();
            $approved = 0;
            $errors = 0;
            $userId = auth()->id();

            foreach ($items as $staging) {
                try {
                    $service->approve($staging, reviewedBy: $userId);
                    $approved++;
                } catch (\InvalidArgumentException $e) {
                    $errors++;
                    Log::warning('Bulk approve item skipped (validation)', [
                        'staging_id' => $staging->id,
                        'name' => $staging->name,
                        'error' => $e->getMessage(),
                    ]);
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
