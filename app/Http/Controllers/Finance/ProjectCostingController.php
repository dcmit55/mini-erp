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
        $query = Project::query();

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
        $project = Project::findOrFail($project_id);

        // Ambil semua material usage untuk project dengan eager load jobOrder
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

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
        $grand_total_idr = $materials->sum('total_cost');

        return response()->json([
            'project' => $project->name,
            'materials' => $materials,
            'grand_total_idr' => $grand_total_idr,
        ]);
    }

    public function exportCosting($project_id)
    {
        $project = Project::findOrFail($project_id);

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
            $query = Project::query();

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
