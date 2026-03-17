<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\InventoryBatch;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InventoryBatchController extends Controller
{
    /**
     * List all batches, optionally filtered by inventory.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = InventoryBatch::with(['inventory', 'currency'])->whereNull('deleted_at');

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

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('material_name', fn($b) => $b->inventory->name ?? '-')
                ->addColumn('material_unit', fn($b) => $b->inventory->unit ?? '-')
                ->addColumn('qty_formatted', function ($b) {
                    $unit = $b->inventory->unit ?? '';
                    return number_format($b->qty, 2) . ' ' . $unit;
                })
                ->addColumn('qty_remaining_formatted', function ($b) {
                    $unit = $b->inventory->unit ?? '';
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
                    ];
                    [$cls, $label] = $map[$b->source_type] ?? ['bg-secondary', ucfirst($b->source_type ?? '-')];
                    return '<span class="badge ' . $cls . '">' . $label . '</span>';
                })
                ->addColumn('received_date_fmt', fn($b) => $b->received_date ? $b->received_date->format('d M Y') : '-')
                ->rawColumns(['qty_remaining_formatted', 'status_badge', 'source_badge'])
                ->make(true);
        }

        $inventories = Inventory::orderBy('name')->get(['id', 'name', 'unit']);

        // Only show source types that actually exist in the batches table
        $allSourceTypes = [
            'initial_stock' => 'Initial Stock',
            'lark' => 'Lark',
            'goods_in' => 'Goods In',
            'purchase' => 'Purchase',
            'goods_movement' => 'Goods Movement',
            'manual' => 'Manual',
        ];
        $existingSources = InventoryBatch::whereNull('deleted_at')->whereNotNull('source_type')->distinct()->pluck('source_type')->toArray();
        $sourceTypes = array_filter($allSourceTypes, fn($key) => in_array($key, $existingSources), ARRAY_FILTER_USE_KEY);

        // Summary stats
        $totalBatches = InventoryBatch::whereNull('deleted_at')->count();
        $activeBatches = InventoryBatch::whereNull('deleted_at')->where('qty_remaining', '>', 0)->count();
        $depletedBatches = InventoryBatch::whereNull('deleted_at')->where('qty_remaining', '<=', 0)->count();
        $totalStockValue = InventoryBatch::whereNull('deleted_at')->selectRaw('SUM(qty_remaining * unit_price) as total')->value('total') ?? 0;

        return view('logistic.inventory-batch.index', compact('inventories', 'sourceTypes', 'totalBatches', 'activeBatches', 'depletedBatches', 'totalStockValue'));
    }

    /**
     * Show all batches for a specific inventory (modal/page).
     */
    public function byInventory(Request $request, int $inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        if ($request->ajax()) {
            $query = InventoryBatch::with('currency')->where('inventory_id', $inventoryId)->whereNull('deleted_at');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('qty_formatted', fn($b) => number_format($b->qty, 2) . ' ' . ($inventory->unit ?? ''))
                ->addColumn('qty_remaining_formatted', function ($b) use ($inventory) {
                    $unit = $inventory->unit ?? '';
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
                    ];
                    [$cls, $label] = $map[$b->source_type] ?? ['bg-secondary', ucfirst($b->source_type ?? '-')];
                    return '<span class="badge ' . $cls . '">' . $label . '</span>';
                })
                ->addColumn('received_date_fmt', fn($b) => $b->received_date ? $b->received_date->format('d M Y') : '-')
                ->rawColumns(['qty_remaining_formatted', 'status_badge', 'source_badge'])
                ->make(true);
        }

        return view('logistic.inventory-batch.by-inventory', compact('inventory'));
    }
}
