<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Production\Project;
use App\Models\Logistic\MaterialUsage;
use App\Models\Logistic\GoodsIn;
use App\Models\Logistic\GoodsOut;
use App\Models\Admin\Department;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProjectCostingExport;
use App\Exports\AllProjectsCostingExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Logistic\Inventory;

class ProjectCostingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_finance', 'admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // ❗ HANYA ambil project yang sudah closed
        $query = Project::where('stage', 'closed');

        // Search filter
        if ($request->has('search') && $request->search !== null) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Apply filters
        if ($request->has('department') && $request->department !== null) {
            $query->whereHas('departments', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }

        // Filter by created_by
        if ($request->has('created_by') && $request->created_by !== null) {
            if ($request->created_by === 'sync_from_lark') {
                $query->where('created_by', 'Sync from Lark');
            } elseif ($request->created_by === 'manual') {
                $query->where('created_by', '!=', 'Sync from Lark')->orWhereNull('created_by');
            } else {
                // Filter by specific username
                $query->where('created_by', $request->created_by);
            }
        }

        // Filter by job order - show only projects that have this job order
        if ($request->has('job_order') && $request->job_order !== null) {
            $query->whereHas('jobOrders', function ($q) use ($request) {
                $q->where('id', $request->job_order);
            });
        }

        $projects = $query
            ->with(['departments', 'jobOrders.materialRequests', 'jobOrders.department'])
            ->latest() // Sort by most recent first
            ->paginate(10);

        // Pass data for filters
        $departments = Department::orderBy('name')->pluck('name');

        // Get unique created_by values for filter
        $createdByOptions = Project::selectRaw('DISTINCT created_by')->whereNotNull('created_by')->orderBy('created_by')->pluck('created_by');

        // Get all job orders for filter dropdown
        $jobOrders = \App\Models\Production\JobOrder::select('id', 'name')->orderBy('name')->get();

        return view('finance.costing.index', compact('projects', 'departments', 'createdByOptions', 'jobOrders'));
    }

    public function viewCosting($project_id)
    {
        // ❗ Validasi: hanya project closed yang bisa di-view costing
        $project = Project::where('id', $project_id)->where('stage', 'closed')->firstOrFail();

        // Ambil semua material usage untuk project dengan eager load jobOrder
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        // ===== LABOR COST FROM APPROVED TIMINGS =====
        $approvedTimings = \App\Models\Production\Timing::where('project_id', $project_id)
            ->where('approval_status', 'approved') // ❗ ONLY APPROVED
            ->with(['jobOrder', 'employee'])
            ->get();

        $totalLaborMinutes = $approvedTimings->sum('duration_minutes') ?? 0;
        $totalLaborHours = round($totalLaborMinutes / 60, 2);
        $approvedTimingsCount = $approvedTimings->count();

        // Group timings by job order for detailed breakdown
        $timingsByJobOrder = $approvedTimings
            ->groupBy('job_order_id')
            ->map(function ($timings, $jobOrderId) {
                $jobOrder = $timings->first()->jobOrder;
                $totalMinutes = $timings->sum('duration_minutes');
                $totalHours = round($totalMinutes / 60, 2);

                return [
                    'job_order_id' => $jobOrderId,
                    'job_order_name' => $jobOrder ? $jobOrder->name : 'No Job Order',
                    'total_hours' => $totalHours,
                    'total_minutes' => $totalMinutes,
                    'sessions_count' => $timings->count(), // Match frontend variable name
                    'unique_employees' => $timings->pluck('employee.name')->unique()->count(), // Count employees
                    'employee_names' => $timings->pluck('employee.name')->unique()->values()->toArray(), // List for tooltip
                ];
            })
            ->values();

        // Hitung total biaya per material dengan rumus baru dan konversi ke IDR
        $materials = $usages->map(function ($usage) {
            $inventory = $usage->inventory;

            // RUMUS: Unit Price + Domestic Freight + International Freight
            $unitPrice = $inventory->price ?? 0;
            $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
            $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
            $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;

            $usedQty = $usage->used_quantity ?? 0;

            // Get unit name - support both old (varchar) and new (relation) data
            $unitName = 'N/A';
            if ($inventory->unit_id) {
                try {
                    $unitRelation = $inventory->unit;
                    if ($unitRelation) {
                        $unitName = $unitRelation->name;
                    }
                } catch (\Exception $e) {
                    $unitName = $inventory->unit ?? 'N/A';
                }
            } elseif (!empty($inventory->unit)) {
                $unitName = $inventory->unit;
            }

            $currency = $inventory->currency ?? (object) ['name' => 'N/A', 'exchange_rate' => 1];
            $exchangeRate = $currency->exchange_rate ?? 1;

            $totalCost = $totalUnitCost * $usedQty;
            $totalCostInIDR = $totalCost * $exchangeRate;

            return (object) [
                'job_order_name' => $usage->jobOrder ? $usage->jobOrder->name : 'No Job Order',
                'inventory' => (object) [
                    'id' => $inventory->id ?? $usage->inventory_id,
                    'name' => $inventory->name ?? 'N/A',
                    'unit' => $unitName,
                    'price' => $unitPrice,
                    'total_unit_cost' => $totalUnitCost,
                    'currency' => $currency,
                ],
                'used_quantity' => $usedQty,
                'total_price' => $totalCost,
                'total_cost' => $totalCostInIDR,
            ];
        });

        // Hitung grand total dalam IDR
        $grand_total_material_idr = $materials->sum('total_cost');

        // ===== MATERIAL INVENTORY ITEMS (from GoodsOut) =====
        $goodsOut = \App\Models\Logistic\GoodsOut::where('project_id', $project_id)
            ->with(['inventory.currency', 'jobOrder'])
            ->get();
        
        // Group by inventory item
        $inventoryItems = $goodsOut->groupBy('inventory_id')->map(function ($items, $inventoryId) {
            $firstItem = $items->first();
            $inventory = $firstItem->inventory;
            $totalQty = $items->sum('quantity');
            
            // Calculate unit cost in SGD
            $currency = $inventory->currency ?? (object)['name' => 'N/A', 'exchange_rate' => 1];
            $unitPrice = $inventory->price ?? 0;
            $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
            $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
            $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;
            
            return [
                'inventory_id' => $inventoryId,
                'inventory_name' => $inventory->name ?? 'N/A',
                'unit' => $inventory->unit ?? 'N/A',
                'total_quantity' => $totalQty,
                'unit_cost' => $totalUnitCost,
                'currency' => $currency->name ?? 'SGD',
                'total_cost' => $totalQty * $totalUnitCost,
                'transactions_count' => $items->count(),
                'job_orders' => $items->pluck('jobOrder.name')->filter()->unique()->values()->toArray(),
            ];
        })->values();

        // ===== LARK GOODS MOVEMENT ITEMS (Logistics Cost) =====
        // Check if Lark fields exist in database (migration might not be run yet)
        $larkItemsGrouped = collect([]);
        try {
            $larkItems = \App\Models\Logistic\GoodsMovementItem::where('project_id', $project_id)
                ->whereNotNull('lark_item_name')
                ->with(['goodsMovement'])
                ->get();
            
            // Group by item name untuk costing
            $larkItemsGrouped = $larkItems->groupBy('lark_item_name')->map(function ($items, $itemName) {
                $totalQty = $items->sum('quantity');
                $avgUnitCost = $items->avg('lark_unit_cost');
                $totalCost = $items->sum('lark_total_cost');
                $currency = $items->first()->lark_currency ?? 'SGD';
                
                return [
                    'item_name' => $itemName,
                    'total_quantity' => $totalQty,
                    'unit' => $items->first()->unit ?? 'pcs',
                    'avg_unit_cost' => round($avgUnitCost, 4),
                    'total_cost' => round($totalCost, 2),
                    'currency' => $currency,
                    'transactions_count' => $items->count(),
                    'shipment_directions' => $items->pluck('goodsMovement.shipment_direction')->filter()->unique()->values()->toArray(),
                ];
            })->values();
        } catch (\Exception $e) {
            // Lark fields not available yet (migration not run)
            \Log::info('Lark fields not available in goods_movement_items table');
        }

        return response()->json([
            'project' => $project->name,
            'materials' => $materials,
            'grand_total_material_idr' => $grand_total_material_idr,
            // ===== LABOR COST DATA =====
            'labor' => [
                'total_hours' => $totalLaborHours,
                'total_minutes' => $totalLaborMinutes,
                'approved_sessions_count' => $approvedTimingsCount,
                'by_job_order' => $timingsByJobOrder,
            ],
            // ===== INVENTORY ITEMS BREAKDOWN =====
            'inventory_items' => [
                'total_items' => $inventoryItems->count(),
                'total_transactions' => $goodsOut->count(),
                'items' => $inventoryItems,
            ],
            // ===== LARK LOGISTICS ITEMS =====
            'lark_logistics' => [
                'total_items' => $larkItemsGrouped->count(),
                'total_cost' => $larkItemsGrouped->sum('total_cost'),
                'total_transactions' => $larkItems->count(),
                'items' => $larkItemsGrouped,
            ],
            // ===== COMBINED TOTALS =====
            'summary' => [
                'total_material_cost_idr' => $grand_total_material_idr,
                'total_labor_hours' => $totalLaborHours,
                'total_lark_logistics_cost' => $larkItemsGrouped->sum('total_cost'),
                'job_order_count' => $usages->pluck('job_order_id')->unique()->count(),
                'approved_timing_count' => $approvedTimingsCount,
            ],
        ]);
    }

    public function exportCosting($project_id)
    {
        // ❗ Validasi: hanya project closed yang bisa di-export
        $project = Project::where('id', $project_id)->where('stage', 'closed')->firstOrFail();

        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'jobOrder'])
            ->get();

        // Gunakan rumus yang sama dengan viewCosting()
        $materials = $usages->map(function ($usage) {
            $inventory = $usage->inventory;

            // RUMUS LENGKAP: Unit Price + Domestic Freight + International Freight
            $unitPrice = $inventory->price ?? 0;
            $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
            $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
            $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;

            $usedQty = $usage->used_quantity ?? 0;
            $currency = $inventory->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
            $exchangeRate = $currency->exchange_rate ?? 1;

            $totalPrice = $totalUnitCost * $usedQty;
            $totalCostInIDR = $totalPrice * $exchangeRate;

            return [
                'job_order_name' => $usage->jobOrder ? $usage->jobOrder->name : 'No Job Order',
                'material_name' => $inventory->name ?? 'N/A',
                'used_quantity' => $usedQty,
                'unit' => $inventory->unit ?? 'N/A',
                'unit_price' => $unitPrice,
                'domestic_freight' => $domesticFreight,
                'international_freight' => $internationalFreight,
                'total_unit_cost' => $totalUnitCost,
                'currency' => $currency->name ?? 'IDR',
                'total_price' => $totalPrice,
                'total_cost' => $totalCostInIDR,
            ];
        });

        return Excel::download(new ProjectCostingExport($materials, $project->name), 'project_costing_' . str_replace(' ', '_', $project->name) . '_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportAllProjects(Request $request)
    {
        try {
            // ❗ Hanya export project yang sudah closed
            $query = Project::where('stage', 'closed');

            if ($request->has('department') && $request->department !== null) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('name', $request->department);
                });
            }

            $projects = $query->with('departments')->orderBy('name')->get();
            $projectsData = [];

            foreach ($projects as $project) {
                $usages = MaterialUsage::where('project_id', $project->id)
                    ->with(['inventory.currency', 'inventory.unit'])
                    ->get();

                if ($usages->isEmpty()) {
                    continue;
                }

                $materials = [];
                $projectTotal = 0;

                foreach ($usages as $usage) {
                    $inventory = $usage->inventory;

                    if (!$inventory) {
                        continue;
                    }

                    $currency = $inventory->currency;
                    $unitPrice = $inventory->price ?? 0;
                    $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
                    $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
                    $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;

                    $exchangeRate = $currency ? $currency->exchange_rate ?? 1 : 1;
                    $usedQty = $usage->used_quantity ?? 0;
                    $totalCostIdr = $totalUnitCost * $usedQty * $exchangeRate;

                    $projectTotal += $totalCostIdr;

                    // Get unit name - FIXED: Check relation loaded properly
                    $unitName = 'N/A';

                    // Check if unit relation is loaded (not just varchar field)
                    if ($inventory->relationLoaded('unit') && $inventory->unit && is_object($inventory->unit)) {
                        // Unit is loaded as relation object
                        $unitName = $inventory->unit->name ?? 'N/A';
                    } elseif (!empty($inventory->unit) && is_string($inventory->unit)) {
                        // Unit is varchar field (legacy data)
                        $unitName = $inventory->unit;
                    }

                    $materials[] = [
                        'material_name' => $inventory->name ?? 'N/A',
                        'qty' => $usedQty,
                        'unit' => $unitName,
                        'currency' => $currency ? $currency->code ?? 'IDR' : 'IDR',
                        'unit_price' => $unitPrice,
                        'domestic_freight' => $domesticFreight,
                        'intl_freight' => $internationalFreight,
                        'total_unit_cost' => $totalUnitCost,
                        'total_cost_idr' => $totalCostIdr,
                    ];
                }

                if (!empty($materials)) {
                    $projectsData[] = [
                        'project_name' => $project->name,
                        'materials' => $materials,
                        'grand_total' => $projectTotal,
                    ];
                }
            }

            if (empty($projectsData)) {
                return back()->with('error', 'No projects with material usage found.');
            }

            $filename = 'all_projects_costing_' . now()->format('Y-m-d_His') . '.xlsx';

            return Excel::download(new AllProjectsCostingExport($projectsData), $filename);
        } catch (\Exception $e) {
            Log::error('Export All Projects Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get materials breakdown by job order (AJAX endpoint)
     */
    public function getJobOrderMaterials($project_id, $job_order_id)
    {
        try {
            $project = Project::findOrFail($project_id);
            $jobOrder = \App\Models\Production\JobOrder::findOrFail($job_order_id);

            // Get material usages for this job order
            $usages = MaterialUsage::where('project_id', $project_id)
                ->where('job_order_id', $job_order_id)
                ->with(['inventory.currency', 'inventory.unit', 'jobOrder'])
                ->get();

            // Calculate costs per material
            $materials = $usages->map(function ($usage) {
                $inventory = $usage->inventory;

                // RUMUS: Unit Price + Domestic Freight + International Freight
                $unitPrice = $inventory->price ?? 0;
                $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
                $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
                $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;

                $usedQty = $usage->used_quantity ?? 0;

                // Get unit name
                $unitName = 'N/A';
                if ($inventory->unit_id) {
                    try {
                        $unitRelation = $inventory->unit;
                        if ($unitRelation) {
                            $unitName = $unitRelation->name;
                        }
                    } catch (\Exception $e) {
                        $unitName = $inventory->unit ?? 'N/A';
                    }
                } elseif (!empty($inventory->unit)) {
                    $unitName = $inventory->unit;
                }

                $currency = $inventory->currency ?? (object) ['name' => 'N/A', 'exchange_rate' => 1];
                $exchangeRate = $currency->exchange_rate ?? 1;

                $totalCost = $totalUnitCost * $usedQty;
                $totalCostInIDR = $totalCost * $exchangeRate;

                return [
                    'job_order_name' => $usage->jobOrder ? $usage->jobOrder->name : 'No Job Order',
                    'material_name' => $inventory->name ?? 'N/A',
                    'quantity' => number_format($usedQty, 2),
                    'unit' => $unitName,
                    'unit_price' => number_format($unitPrice, 2),
                    'domestic_freight' => number_format($domesticFreight, 2),
                    'international_freight' => number_format($internationalFreight, 2),
                    'total_unit_cost' => number_format($totalUnitCost, 2),
                    'total_cost_idr' => 'Rp ' . number_format($totalCostInIDR, 2, ',', '.'),
                    'currency' => $currency->name ?? 'N/A',
                ];
            });

            return response()->json([
                'success' => true,
                'job_order' => [
                    'id' => $jobOrder->id,
                    'name' => $jobOrder->name,
                ],
                'materials' => $materials,
                'total_materials' => $materials->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}

// If you want to log $materials, place this inside a controller method where $materials is defined, for example:
// Log::info('Material Data:', $materials->toArray());
