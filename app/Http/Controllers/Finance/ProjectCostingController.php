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

        // Apply filters
        if ($request->has('department') && $request->department !== null) {
            $query->whereHas('departments', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }

        $projects = $query->with('departments')->orderBy('name')->get();

        // Pass data for filters
        $departments = Department::orderBy('name')->pluck('name');

        return view('finance.costing.index', compact('projects', 'departments'));
    }

    public function viewCosting($project_id)
    {
        $project = Project::findOrFail($project_id);

        // Ambil semua material usage untuk project
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency'])
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
                'inventory' => (object) [
                    'id' => $inventory->id ?? $usage->inventory_id,
                    'name' => $inventory->name ?? 'N/A',
                    'unit' => $unitName,
                    'price' => $unitPrice,
                    'domestic_freight' => $domesticFreight,
                    'international_freight' => $internationalFreight,
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
            ->with(['inventory.currency'])
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
}

// If you want to log $materials, place this inside a controller method where $materials is defined, for example:
// Log::info('Material Data:', $materials->toArray());
