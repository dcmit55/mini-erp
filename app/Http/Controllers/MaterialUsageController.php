<?php

namespace App\Http\Controllers;

use App\Models\GoodsIn;
use App\Models\GoodsOut;
use Illuminate\Http\Request;
use App\Models\MaterialUsage;
use App\Models\Inventory;
use App\Models\Project;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MaterialUsageExport;
use Illuminate\Support\Facades\Auth;

class MaterialUsageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_logistic', 'admin_mascot', 'admin_costume', 'admin_animatronic', 'admin_finance', 'admin', 'general'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = MaterialUsage::with(['inventory', 'project']);

        // Apply filters
        if ($request->filled('material')) {
            $query->where('inventory_id', $request->material);
        }

        if ($request->filled('project')) {
            if ($request->project === 'no_project') {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', $request->project);
            }
        }

        $usages = $query->get();

        // Add "No Project" option to filter
        $projects = Project::orderBy('name')->get();
        $projects->prepend(
            (object) [
                'id' => 'no_project',
                'name' => 'No Project',
            ],
        );

        $materials = Inventory::orderBy('name')->get();

        return view('material_usage.index', compact('usages', 'projects', 'materials'));
    }

    public function export(Request $request)
    {
        // Ambil filter dari request
        $material = $request->material;
        $project = $request->project;

        // Filter data berdasarkan request
        $query = MaterialUsage::with(['inventory', 'project']);

        if ($material) {
            $query->where('inventory_id', $material);
        }

        if ($project) {
            $query->where('project_id', $project);
        }

        $usages = $query->get();

        // Buat nama file dinamis
        $fileName = 'material_usage';
        if ($material) {
            $materialName = Inventory::find($material)->name ?? 'Unknown Material';
            $fileName .= '_material-' . str_replace(' ', '-', strtolower($materialName));
        }
        if ($project) {
            $projectName = Project::find($project)->name ?? 'Unknown Project';
            $fileName .= '_project-' . str_replace(' ', '-', strtolower($projectName));
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        // Ekspor data menggunakan kelas MaterialUsageExport
        return Excel::download(new MaterialUsageExport($usages), $fileName);
    }

    public function getByInventory(Request $request)
    {
        $inventory_id = $request->query('inventory_id');

        $usages = MaterialUsage::where('inventory_id', $inventoryId)
            ->with(['inventory', 'project'])
            ->get()
            ->map(function ($usage) {
                $unit = $usage->inventory->unit ?? '';
                return [
                    'project_name' => $usage->project->name ?? 'No Project',
                    'goods_out_quantity' => GoodsOut::where('inventory_id', $usage->inventory_id)
                        ->where(function ($q) use ($usage) {
                            if ($usage->project_id) {
                                $q->where('project_id', $usage->project_id);
                            } else {
                                $q->whereNull('project_id');
                            }
                        })
                        ->sum('quantity'),
                    'goods_in_quantity' => GoodsIn::where('inventory_id', $usage->inventory_id)->where('project_id', $usage->project_id)->sum('quantity'),
                    'used_quantity' => $usage->used_quantity,
                    'unit' => $unit,
                ];
            });

        return response()->json($usages);
    }

    public function destroy(MaterialUsage $material_usage)
    {
        // Admin visitor tidak bisa delete
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('material_usage.index')->with('error', 'You do not have permission to delete material usage.');
        }

        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('material_usage.index')->with('error', 'You are not authorized to delete this data.');
        }

        $material_usage->delete();
        return redirect()->route('material_usage.index')->with('success', 'Material usage deleted successfully.');
    }
}
