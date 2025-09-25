<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\MaterialUsage;
use App\Models\GoodsIn;
use App\Models\GoodsOut;
use App\Models\Department;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProjectCostingExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Inventory;

class ProjectCostingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_finance'];
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
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }

        $projects = $query->with('department')->orderBy('name')->get();

        // Pass data for filters
        $departments = Department::orderBy('name')->pluck('name');

        return view('costing.index', compact('projects', 'departments'));
    }

    public function viewCosting($project_id)
    {
        $project = Project::findOrFail($project_id);

        // Ambil semua material usage untuk project
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency'])
            ->get();

        // Hitung total biaya per material dan konversi ke IDR
        $materials = $usages->map(function ($usage) {
            $inventory = $usage->inventory;
            $price = $inventory->price ?? 0;
            $usedQty = $usage->used_quantity ?? 0;
            $unit = $inventory->unit ?? 'N/A';
            $currency = $inventory->currency ?? (object) ['name' => 'N/A', 'exchange_rate' => 1];
            $exchangeRate = $currency->exchange_rate ?? 1;

            $totalCost = $price * $usedQty;
            $totalCostInIDR = $totalCost * $exchangeRate;

            return (object) [
                'inventory' => (object) [
                    'id' => $inventory->id ?? $usage->inventory_id, // Fallback ke inventory_id jika inventory null
                    'name' => $inventory->name ?? 'N/A',
                    'unit' => $unit,
                    'price' => $price,
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

        $materials = $usages->map(function ($usage) {
            $inventory = $usage->inventory;
            $price = $inventory->price ?? 0;
            $usedQty = $usage->used_quantity ?? 0;
            $currency = $inventory->currency ?? (object) ['name' => 'N/A', 'exchange_rate' => 1];
            $exchangeRate = $currency->exchange_rate ?? 1;

            return [
                'material_name' => $inventory->name ?? 'N/A',
                'used_quantity' => $usedQty,
                'unit' => $inventory->unit ?? 'N/A',
                'unit_price' => $price,
                'currency' => $currency->name ?? 'N/A',
                'total_price' => $price * $usedQty,
                'total_cost' => $price * $usedQty * $exchangeRate,
            ];
        });

        // Ekspor ke Excel
        return Excel::download(new ProjectCostingExport($materials, $project->name), 'project_costing_' . $project->name . '_' . now()->format('Y-m-d') . '.xlsx');
    }
}

// If you want to log $materials, place this inside a controller method where $materials is defined, for example:
// Log::info('Material Data:', $materials->toArray());
