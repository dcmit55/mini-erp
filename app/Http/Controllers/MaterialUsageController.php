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
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Auth;

class MaterialUsageController extends Controller
{
    /**
     * Display a listing of material usage records
     */
    public function index(Request $request)
    {
        // Check if AJAX request for DataTables
        if ($request->ajax()) {
            $query = MaterialUsage::with(['inventory', 'project'])
                ->select('material_usages.*')
                ->latest();

            // Apply filters
            if ($request->filled('material')) {
                $query->where('inventory_id', $request->material);
            }

            if ($request->filled('project')) {
                $query->where('project_id', $request->project);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('inventory', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })->orWhereHas('project', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
                });
            }

            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('material_name', function ($item) {
                    return $item->inventory ? $item->inventory->name : 'N/A';
                })
                ->addColumn('project_name', function ($item) {
                    return $item->project ? $item->project->name : 'No Project';
                })
                ->addColumn('goods_out_qty', function ($item) {
                    return GoodsOut::where('inventory_id', $item->inventory_id)
                        ->where(function ($q) use ($item) {
                            if ($item->project_id) {
                                $q->where('project_id', $item->project_id);
                            } else {
                                $q->whereNull('project_id');
                            }
                        })
                        ->sum('quantity') ?? 0;
                })
                ->addColumn('goods_in_qty', function ($item) {
                    return GoodsIn::where('inventory_id', $item->inventory_id)
                        ->where(function ($q) use ($item) {
                            if ($item->project_id) {
                                $q->where('project_id', $item->project_id);
                            } else {
                                $q->whereNull('project_id');
                            }
                        })
                        ->sum('quantity') ?? 0;
                })
                ->addColumn('used_qty', function ($item) {
                    return $item->used_quantity ?? 0;
                })
                ->addColumn('unit', function ($item) {
                    return $item->inventory ? $item->inventory->unit ?? '-' : '-';
                })
                ->addColumn('updated_at', function ($item) {
                    return $item->updated_at->format('d M Y H:i');
                })
                ->addColumn('actions', function ($item) {
                    $actions = '<div class="text-center">';

                    if (Auth::user()->isSuperAdmin()) {
                        $actions .=
                            '<form action="' .
                            route('material_usage.destroy', $item) .
                            '" method="POST" class="d-inline delete-form" style="display:inline;">
                            ' .
                            csrf_field() .
                            method_field('DELETE') .
                            '
                            <button type="button" class="btn btn-sm btn-danger btn-delete" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['checkbox', 'actions'])
                ->make(true);
        }

        // Get filter options untuk dropdown
        $materials = Inventory::select('id', 'name')->orderBy('name')->get();
        $projects = Project::select('id', 'name')->orderBy('name')->get();

        return view('material_usage.index', compact('materials', 'projects'));
    }

    /**
     * Display the specified resource
     */
    public function show(MaterialUsage $materialUsage)
    {
        $materialUsage->load(['inventory', 'project']);

        $goodsOutTotal =
            GoodsOut::where('inventory_id', $materialUsage->inventory_id)
                ->where(function ($q) use ($materialUsage) {
                    if ($materialUsage->project_id) {
                        $q->where('project_id', $materialUsage->project_id);
                    } else {
                        $q->whereNull('project_id');
                    }
                })
                ->sum('quantity') ?? 0;

        $goodsInTotal =
            GoodsIn::where('inventory_id', $materialUsage->inventory_id)
                ->where(function ($q) use ($materialUsage) {
                    if ($materialUsage->project_id) {
                        $q->where('project_id', $materialUsage->project_id);
                    } else {
                        $q->whereNull('project_id');
                    }
                })
                ->sum('quantity') ?? 0;

        return view('material_usage.show', compact('materialUsage', 'goodsOutTotal', 'goodsInTotal'));
    }

    /**
     * Export material usage data to Excel
     */
    public function export(Request $request)
    {
        $query = MaterialUsage::with(['inventory', 'project']);

        $filterParts = [];

        if ($request->filled('material')) {
            $query->where('inventory_id', $request->material);
            $materialName = Inventory::find($request->material)->name ?? 'UnknownMaterial';
            $filterParts[] = 'material-' . str_replace(' ', '-', strtolower($materialName));
        }

        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
            $projectName = Project::find($request->project)->name ?? 'UnknownProject';
            $filterParts[] = 'project-' . str_replace(' ', '-', strtolower($projectName));
        }

        if ($request->filled('search')) {
            $filterParts[] = 'search-' . str_replace(' ', '-', strtolower(substr($request->search, 0, 10)));
        }

        $usages = $query->get()->map(function ($usage) {
            // Check if inventory exists before accessing properties
            return [
                'material' => $usage->inventory ? $usage->inventory->name : 'Unknown',
                'project' => $usage->project ? $usage->project->name : 'No Project',
                'goods_out_qty' =>
                    GoodsOut::where('inventory_id', $usage->inventory_id)
                        ->where(function ($q) use ($usage) {
                            if ($usage->project_id) {
                                $q->where('project_id', $usage->project_id);
                            } else {
                                $q->whereNull('project_id');
                            }
                        })
                        ->sum('quantity') ?? 0,
                'goods_in_qty' =>
                    GoodsIn::where('inventory_id', $usage->inventory_id)
                        ->where(function ($q) use ($usage) {
                            if ($usage->project_id) {
                                $q->where('project_id', $usage->project_id);
                            } else {
                                $q->whereNull('project_id');
                            }
                        })
                        ->sum('quantity') ?? 0,
                'used_qty' => $usage->used_quantity ?? 0,
                'unit' => $usage->inventory ? $usage->inventory->unit ?? '-' : '-',
                'updated_at' => $usage->updated_at->format('d M Y H:i'),
            ];
        });

        $fileName = 'material_usage';
        if (!empty($filterParts)) {
            $fileName .= '_' . implode('_', $filterParts);
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        return \Excel::download(new \App\Exports\MaterialUsageExport($usages), $fileName);
    }

    /**
     * Get material usage by inventory (for detail page)
     */
    public function getByInventory(Request $request)
    {
        $inventory_id = $request->query('inventory_id');

        $usages = MaterialUsage::where('inventory_id', $inventory_id)
            ->with(['inventory', 'project'])
            ->get()
            ->map(function ($usage) {
                // Check if inventory exists
                $unit = $usage->inventory ? $usage->inventory->unit ?? '' : '';
                return [
                    'project_name' => $usage->project ? $usage->project->name : 'No Project',
                    'goods_out_quantity' => GoodsOut::where('inventory_id', $usage->inventory_id)
                        ->where(function ($q) use ($usage) {
                            if ($usage->project_id) {
                                $q->where('project_id', $usage->project_id);
                            } else {
                                $q->whereNull('project_id');
                            }
                        })
                        ->sum('quantity'),
                    'goods_in_quantity' => GoodsIn::where('inventory_id', $usage->inventory_id)
                        ->where(function ($q) use ($usage) {
                            if ($usage->project_id) {
                                $q->where('project_id', $usage->project_id);
                            } else {
                                $q->whereNull('project_id');
                            }
                        })
                        ->sum('quantity'),
                    'used_quantity' => $usage->used_quantity,
                    'unit' => $unit,
                ];
            });

        return response()->json($usages);
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy(MaterialUsage $materialUsage)
    {
        // Only super admin can delete
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Permission denied');
        }

        try {
            $materialUsage->delete();
            return redirect()->route('material_usage.index')->with('success', 'Material usage record deleted successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete material usage record: ' . $e->getMessage());
        }
    }
}
