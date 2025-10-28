<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\GoodsOut;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FinalProjectSummaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Project::query()->latest();

        // Filter department
        $departments = Department::orderBy('name')->pluck('name', 'id');
        if ($request->filled('department')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }

        // Search project name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $projects = $query->with('department')->orderBy('name')->get();

        return view('final_project_summary.index', compact('projects', 'departments'));
    }

    public function show(Project $project)
    {
        // Ambil semua material yang digunakan dalam proyek
        $materials = GoodsOut::where('project_id', $project->id)
            ->with([
                'inventory' => function ($q) {
                    $q->withTrashed()->with('currency');
                },
            ])
            ->get();

        // Hitung total biaya per material dan konversi ke IDR
        $materials = $materials->map(function ($item) {
            $inventory = $item->inventory;
            $price = $inventory ? $inventory->price : 0;
            $quantity = $item->quantity ?? 0;
            $exchangeRate = $inventory && $inventory->currency ? $inventory->currency->exchange_rate : 1;
            $totalCost = $price * $quantity;
            $totalCostInIDR = $totalCost * $exchangeRate;
            $item->total_cost = $totalCostInIDR;
            return $item;
        });

        // Hitung grand total dalam IDR
        $grandTotal = $materials->sum('total_cost');

        // Hitung project day count
        $dayCount = null;
        if ($project->start_date) {
            $start = \Carbon\Carbon::parse($project->start_date);
            if ($project->finish_date) {
                $end = \Carbon\Carbon::parse($project->finish_date);
            } else {
                $end = \Carbon\Carbon::now();
            }
            $dayCount = $start->diffInDays($end) + 1; // +1 agar hari mulai dihitung
        }

        // Ambil semua parts project
        $parts = $project->parts ?? collect();

        // Siapkan array output per part
        $parts = $project->parts ?? collect();

        $partOutputs = [];
        if ($parts->count()) {
            $timings = \App\Models\Timing::where('project_id', $project->id)->select('parts', DB::raw('SUM(output_qty) as total_qty'))->groupBy('parts')->pluck('total_qty', 'parts');

            foreach ($parts as $part) {
                $qty = $timings[$part->part_name] ?? 0;
                $partOutputs[] = [
                    'name' => $part->part_name,
                    'qty' => $qty,
                ];
            }
        }

        $manpowerCount = \App\Models\Timing::where('project_id', $project->id)->distinct('employee_id')->count('employee_id');

        return view('final_project_summary.show', compact('project', 'grandTotal', 'dayCount', 'partOutputs', 'manpowerCount'));
    }

    public function viewCosting($project_id)
    {
        $project = Project::findOrFail($project_id);

        // Ambil semua material yang digunakan dalam proyek
        $materials = GoodsOut::where('project_id', $project_id)
            ->with([
                'inventory' => function ($q) {
                    $q->withTrashed()->with('currency');
                },
            ])
            ->get();

        // Hitung total biaya per material dan konversi ke IDR
        $materials = $materials->map(function ($item) {
            $inventory = $item->inventory;
            // ... (proses perhitungan)
            $price = $inventory ? $inventory->price : 0;
            $quantity = $item->quantity ?? 0;
            $exchangeRate = $inventory->currency->exchange_rate ?? 1;
            $totalCost = $price * $quantity;
            $totalCostInIDR = $totalCost * $exchangeRate;
            $item->total_cost = $totalCostInIDR;
            return $item;
        });

        // Hitung grand total dalam IDR
        $grand_total_idr = $materials->sum('total_cost');

        return response()->json([
            'project' => $project->name,
            'materials' => $materials,
            'grand_total_idr' => $grand_total_idr,
        ]);
    }

    public function ajaxSearch(Request $request)
    {
        try {
            $query = Project::query();

            if ($request->filled('department')) {
                $query->whereHas('department', function ($q) use ($request) {
                    $q->where('name', $request->department);
                });
            }
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $projects = $query->orderBy('name')->get();

            $html = view('final_project_summary.project_table', compact('projects'))->render();

            return response()->json([
                'html' => $html,
                'count' => $projects->count(),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'html' => '<tr class="no-data-row"><td colspan="3" class="text-center text-muted py-4"><i class="bi bi-exclamation-triangle"></i> Error loading data</td></tr>',
                    'count' => 0,
                    'success' => false,
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
