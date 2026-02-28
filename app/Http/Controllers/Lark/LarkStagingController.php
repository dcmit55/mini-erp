<?php

namespace App\Http\Controllers\Lark;

use App\Http\Controllers\Controller;
use App\Models\Lark\LarkBtSgCourierId;
use App\Models\Lark\LarkBtSgItemTracking;
use App\Models\Lark\LarkSgBtCourierId;
use App\Models\Lark\LarkSgBtItemTracking;
use App\Services\Lark\LarkStagingSyncService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LarkStagingController extends Controller
{
    private LarkStagingSyncService $syncService;

    public function __construct(LarkStagingSyncService $syncService)
    {
        $this->middleware('auth');
        $this->syncService = $syncService;
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

        return view('lark.staging.bt_sg_items', compact('stats'));
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

        return view('lark.staging.sg_bt_items', compact('stats'));
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
}
