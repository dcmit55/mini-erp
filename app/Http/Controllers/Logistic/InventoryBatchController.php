<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\InventoryBatch;
use App\Models\Logistic\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class InventoryBatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:logistic.inventory-batch.view');
    }

    /**
     * Consistent category badge color — same algorithm as InventoryController.
     */
    private function getCategoryBadgeColor(?string $categoryName): string
    {
        if (!$categoryName) {
            return 'bg-secondary';
        }
        $colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-dark', 'bg-secondary', 'bg-purple', 'bg-indigo', 'bg-pink', 'bg-orange', 'bg-teal', 'bg-cyan', 'bg-lime', 'bg-amber', 'bg-rose', 'bg-emerald', 'bg-violet', 'bg-sky'];
        $hash = crc32(strtolower(trim($categoryName)));
        return $colors[abs($hash) % count($colors)];
    }

    /**
     * List all batches, optionally filtered by inventory.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = InventoryBatch::with(['inventory.category', 'currency'])->whereNull('deleted_at');

            if ($request->filled('inventory_id')) {
                $query->where('inventory_id', $request->inventory_id);
            }

            if ($request->filled('source_type')) {
                $query->where('source_type', $request->source_type);
            }

            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('qty_remaining', '>', 0);
                } elseif ($request->status === 'depleted') {
                    $query->where('qty_remaining', '<=', 0);
                }
            }

            if ($request->filled('date_from')) {
                $query->whereDate('received_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('received_date', '<=', $request->date_to);
            }

            if ($request->filled('category_id')) {
                $query->whereHas('inventory', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            if ($request->filled('waybill')) {
                $query->where('waybill', 'like', '%' . $request->waybill . '%');
            }

            $query->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('material_name', fn($b) => $b->inventory->name ?? '-')
                ->addColumn('material_unit', fn($b) => $b->inventory->unit_name ?? '-')
                ->addColumn('category_name', function ($b) {
                    $name = $b->inventory->category->name ?? null;
                    if (!$name) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="badge ' . $this->getCategoryBadgeColor($name) . '">' . e($name) . '</span>';
                })
                ->addColumn('qty_formatted', function ($b) {
                    $unit = $b->inventory->unit_name ?? '';
                    return number_format($b->qty, 2) . ' ' . $unit;
                })
                ->addColumn('qty_remaining_formatted', function ($b) {
                    $unit = $b->inventory->unit_name ?? '';
                    $pct = $b->qty > 0 ? round(($b->qty_remaining / $b->qty) * 100, 1) : 0;
                    return number_format($b->qty_remaining, 2) . ' ' . $unit . '<br><small class="text-muted">' . $pct . '% remaining</small>';
                })
                ->addColumn('unit_price_formatted', function ($b) {
                    $code = $b->currency->name ?? '';
                    $price = number_format($b->unit_price, 2, '.', ',');
                    return $code ? $code . ' ' . $price : $price;
                })
                ->addColumn('total_value', function ($b) {
                    $code = $b->currency->name ?? '';
                    $val = (float) $b->qty_remaining * (float) $b->unit_price;
                    $formatted = number_format($val, 2, '.', ',');
                    return $code ? $code . ' ' . $formatted : $formatted;
                })
                ->addColumn('status_badge', function ($b) {
                    if ($b->qty_remaining <= 0) {
                        return '<span class="badge bg-secondary">Depleted</span>';
                    }
                    if ($b->qty_remaining < $b->qty) {
                        return '<span class="badge bg-warning text-dark">Partial</span>';
                    }
                    return '<span class="badge bg-success">Full</span>';
                })
                ->addColumn('source_badge', function ($b) {
                    $map = [
                        'initial_stock' => ['bg-info text-dark', 'Initial Stock'],
                        'goods_in' => ['bg-primary', 'Goods In'],
                        'purchase' => ['bg-success', 'Purchase'],
                        'goods_movement' => ['bg-warning text-dark', 'Movement'],
                        'manual' => ['bg-secondary', 'Manual'],
                        'indo_purchase' => ['bg-purple text-white', 'Indo Purchase'],
                        'lark' => ['bg-dark', 'Lark'],
                    ];
                    [$cls, $label] = $map[$b->source_type] ?? ['bg-secondary', ucfirst($b->source_type ?? '-')];
                    return '<span class="badge ' . $cls . '">' . $label . '</span>';
                })
                ->addColumn('received_date_fmt', fn($b) => $b->received_date ? $b->received_date->format('d M Y') : '-')
                ->addColumn('waybill_display', function ($b) {
                    if (!$b->waybill) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="badge bg-light text-dark border font-monospace">' . e($b->waybill) . '</span>';
                })
                ->rawColumns(['qty_remaining_formatted', 'status_badge', 'source_badge', 'category_name', 'waybill_display'])
                ->make(true);
        }

        $inventories = Inventory::orderBy('name')->get(['id', 'name', 'unit']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        // Only show source types that actually exist in the batches table
        $allSourceTypes = [
            'initial_stock' => 'Initial Stock',
            'lark' => 'Lark',
            'goods_in' => 'Goods In',
            'purchase' => 'Purchase',
            'goods_movement' => 'Goods Movement',
            'manual' => 'Manual',
            'indo_purchase' => 'Indo Purchase',
        ];
        $existingSources = InventoryBatch::whereNull('deleted_at')->whereNotNull('source_type')->distinct()->pluck('source_type')->toArray();
        $sourceTypes = array_filter($allSourceTypes, fn($key) => in_array($key, $existingSources), ARRAY_FILTER_USE_KEY);

        // Summary stats
        $totalBatches = InventoryBatch::whereNull('deleted_at')->count();
        $activeBatches = InventoryBatch::whereNull('deleted_at')->where('qty_remaining', '>', 0)->count();
        $depletedBatches = InventoryBatch::whereNull('deleted_at')->where('qty_remaining', '<=', 0)->count();
        $totalStockValue = InventoryBatch::whereNull('deleted_at')->selectRaw('SUM(qty_remaining * unit_price) as total')->value('total') ?? 0;

        return view('logistic.inventory-batch.index', compact('inventories', 'categories', 'sourceTypes', 'totalBatches', 'activeBatches', 'depletedBatches', 'totalStockValue'));
    }

    /**
     * AJAX endpoint: total stock value for Inventory Batches,
     * optionally filtered by category.
     */
    public function batchStockValue(Request $request)
    {
        $query = DB::table('inventory_batches as ib')->join('inventories as i', 'i.id', '=', 'ib.inventory_id')->join('currencies as c', 'c.id', '=', 'ib.currency_id')->whereNull('ib.deleted_at')->whereNull('i.deleted_at')->where('ib.qty_remaining', '>', 0);

        if ($request->filled('category_id')) {
            $query->where('i.category_id', $request->category_id);
        }

        $totalIdr = $query->sum(DB::raw('ib.qty_remaining * ib.unit_price * COALESCE(CAST(c.exchange_rate AS DECIMAL(18,4)), 1)'));

        return response()->json([
            'total_idr' => (float) $totalIdr,
            'total_idr_formatted' => 'IDR ' . number_format((float) $totalIdr, 0, ',', '.'),
        ]);
    }

    /**
     * Fix Zero-Price Batches — Admin Tool
     * Lists all batches where unit_price = 0 so admin can correct them.
     * Each correction is logged via audit (OwenIt) on InventoryBatch.
     */
    public function fixZeroPriceIndex(Request $request)
    {
        $authUser = auth()->user();
        if (!$authUser->isSuperAdmin() && !$authUser->isLogisticAdmin()) {
            abort(403, 'Access denied. Super Admin or Logistic Admin only.');
        }

        $batches = InventoryBatch::with(['inventory.category', 'inventory.currency', 'currency'])
            ->whereNull('deleted_at')
            ->where('unit_price', '=', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        // For each batch, count how many goods_out consumed from it (via stock_usage_batches)
        $batchIds = $batches->pluck('id')->toArray();
        $usageCounts = [];
        if (!empty($batchIds)) {
            $usageCounts = DB::table('stock_usage_batches')->whereIn('batch_id', $batchIds)->selectRaw('batch_id, SUM(qty_used) as total_used, COUNT(*) as usage_count')->groupBy('batch_id')->get()->keyBy('batch_id')->toArray();
        }

        $currencies = \App\Models\Finance\Currency::orderBy('name')->get(['id', 'name']);

        return view('logistic.inventory-batch.fix-zero-price', compact('batches', 'usageCounts', 'currencies'));
    }

    /**
     * Update unit_price for a single zero-price batch.
     * Automatically triggers recalculation for all costing (weighted avg price is computed on-the-fly).
     */
    public function fixZeroPriceUpdate(Request $request, int $batch)
    {
        $authUser = auth()->user();
        if (!$authUser->isSuperAdmin() && !$authUser->isLogisticAdmin()) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'unit_price' => 'required|numeric|min:0.0001',
            'reason' => 'required|string|max:500',
            'currency_id' => 'nullable|integer|exists:currencies,id',
        ]);

        $inventoryBatch = InventoryBatch::whereNull('deleted_at')->where('unit_price', 0)->findOrFail($batch);

        $oldPrice = $inventoryBatch->unit_price;
        $newPrice = $request->unit_price;

        DB::beginTransaction();
        try {
            $updateData = [
                'unit_price' => $newPrice,
                'notes' => trim(($inventoryBatch->notes ? $inventoryBatch->notes . ' | ' : '') . '[FIX] ' . now()->format('Y-m-d H:i') . ' by ' . $authUser->username . ': price changed from 0 to ' . $newPrice . '. Reason: ' . $request->reason),
            ];
            if ($request->filled('currency_id')) {
                $updateData['currency_id'] = (int) $request->currency_id;
            }
            $inventoryBatch->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Batch <strong>{$inventoryBatch->batch_number}</strong> updated: price set to {$newPrice}.",
                'new_price' => $newPrice,
                'batch_number' => $inventoryBatch->batch_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show all batches for a specific inventory (modal/page).
     */
    public function byInventory(Request $request, int $inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        if ($request->ajax()) {
            $query = InventoryBatch::with('currency')->where('inventory_id', $inventoryId)->whereNull('deleted_at')->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('qty_formatted', fn($b) => number_format($b->qty, 2) . ' ' . ($inventory->unit_name ?? ''))
                ->addColumn('qty_remaining_formatted', function ($b) use ($inventory) {
                    $unit = $inventory->unit_name ?? '';
                    $pct = $b->qty > 0 ? round(($b->qty_remaining / $b->qty) * 100, 1) : 0;
                    return number_format($b->qty_remaining, 2) . ' ' . $unit . '<br><small class="text-muted">' . $pct . '% remaining</small>';
                })
                ->addColumn('unit_price_formatted', function ($b) {
                    $code = $b->currency->name ?? '';
                    $price = number_format($b->unit_price, 2, '.', ',');
                    return $code ? $code . ' ' . $price : $price;
                })
                ->addColumn('total_value', function ($b) {
                    $code = $b->currency->name ?? '';
                    $val = (float) $b->qty_remaining * (float) $b->unit_price;
                    $formatted = number_format($val, 2, '.', ',');
                    return $code ? $code . ' ' . $formatted : $formatted;
                })
                ->addColumn('status_badge', function ($b) {
                    if ($b->qty_remaining <= 0) {
                        return '<span class="badge bg-secondary">Depleted</span>';
                    }
                    if ($b->qty_remaining < $b->qty) {
                        return '<span class="badge bg-warning text-dark">Partial</span>';
                    }
                    return '<span class="badge bg-success">Full</span>';
                })
                ->addColumn('source_badge', function ($b) {
                    $map = [
                        'initial_stock' => ['bg-info text-dark', 'Initial Stock'],
                        'goods_in' => ['bg-primary', 'Goods In'],
                        'purchase' => ['bg-success', 'Purchase'],
                        'goods_movement' => ['bg-warning text-dark', 'Movement'],
                        'manual' => ['bg-secondary', 'Manual'],
                        'indo_purchase' => ['bg-purple text-white', 'Indo Purchase'],
                        'lark' => ['bg-dark', 'Lark'],
                    ];
                    [$cls, $label] = $map[$b->source_type] ?? ['bg-secondary', ucfirst($b->source_type ?? '-')];
                    return '<span class="badge ' . $cls . '">' . $label . '</span>';
                })
                ->addColumn('received_date_fmt', fn($b) => $b->received_date ? $b->received_date->format('d M Y') : '-')
                ->addColumn('notes_display', fn($b) => $b->notes ? e($b->notes) : '<span class="text-muted">—</span>')
                ->addColumn('input_date', fn($b) => $b->created_at ? $b->created_at->format('d M Y H:i') : '-')
                ->rawColumns(['qty_remaining_formatted', 'status_badge', 'source_badge', 'notes_display'])
                ->make(true);
        }

        return view('logistic.inventory-batch.by-inventory', compact('inventory'));
    }
}
