<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\GoodsIn;
use App\Models\Production\Project;
use App\Models\Logistic\GoodsOut;
use App\Models\Logistic\Inventory;
use Illuminate\Http\Request;
use App\Helpers\MaterialUsageHelper;
use App\Models\Admin\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GoodsInExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoodsInController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Allow admin to access create/edit/delete pages, but block submit
        $this->middleware(function ($request, $next) {
            $writeRoutes = ['goods_in.store', 'goods_in.store_independent', 'goods_in.update', 'goods_in.destroy'];
            if (in_array($request->route()->getName(), $writeRoutes) && auth()->user()->isReadOnlyAdmin()) {
                abort(403, 'You do not have permission to submit or delete Goods In data.');
            }
            return $next($request);
        })->only(['store', 'storeIndependent', 'update', 'destroy']);
    }

    // Server-side processing untuk index
    public function index(Request $request)
    {
        // Jika AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->getDataTablesData($request);
        }

        // Untuk non-AJAX requests, return view dengan master data untuk filters
        $materials = Inventory::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('username')->get();

        return view('logistic.goods_in.index', compact('materials', 'projects', 'users'));
    }

    // Method untuk server-side processing
    private function getDataTablesData(Request $request)
    {
        $query = GoodsIn::with(['goodsOut.inventory', 'goodsOut.project', 'inventory', 'project'])->latest();

        // Apply filters
        if ($request->filled('material_filter')) {
            $query->where('inventory_id', $request->material_filter);
        }

        if ($request->filled('project_filter')) {
            $query->where('project_id', $request->project_filter);
        }

        if ($request->filled('returned_by_filter')) {
            $query->where('returned_by', $request->returned_by_filter);
        }

        if ($request->filled('returned_at_filter')) {
            $query->whereDate('returned_at', $request->returned_at_filter);
        }

        // Custom search functionality
        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('inventory', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                })
                    ->orWhereHas('project', function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%');
                    })
                    ->orWhere('returned_by', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('remark', 'LIKE', '%' . $searchValue . '%');
            });
        }

        // Sorting
        $columns = ['id', 'inventory_id', 'quantity', 'project_id', 'returned_by', 'returned_at', 'remark'];

        if ($request->filled('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');

            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderBy('returned_at', 'desc');
            }
        } else {
            $query->orderBy('returned_at', 'desc');
        }

        // Get total and filtered counts
        $totalRecords = GoodsIn::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $goodsIns = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        foreach ($goodsIns as $index => $goodsIn) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'material' => $goodsIn->goodsOut && $goodsIn->goodsOut->inventory ? $goodsIn->goodsOut->inventory->name : ($goodsIn->inventory ? $goodsIn->inventory->name : '(no material)'),
                'quantity' => $this->formatQuantity($goodsIn),
                'project' => $goodsIn->goodsOut && $goodsIn->goodsOut->project ? $goodsIn->goodsOut->project->name : ($goodsIn->project ? $goodsIn->project->name : '(no project)'),
                'returned_by' => $this->formatReturnedBy($goodsIn),
                'returned_at' => $goodsIn->returned_at->format('d M Y, H:i'),
                'remark' => $goodsIn->remark ?? '-',
                'actions' => $this->getActionButtons($goodsIn),
                'DT_RowId' => 'row-' . $goodsIn->id,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    // Helper method untuk format quantity
    private function formatQuantity($goodsIn)
    {
        $unit = $goodsIn->goodsOut && $goodsIn->goodsOut->inventory ? $goodsIn->goodsOut->inventory->unit : ($goodsIn->inventory ? $goodsIn->inventory->unit : '');

        $quantity = number_format($goodsIn->quantity, 2);
        $quantity = rtrim(rtrim($quantity, '0'), '.');

        return '<span data-bs-toggle="tooltip" data-bs-placement="right" title="' . $unit . '">' . $quantity . '</span>';
    }

    // Helper method untuk format returned by
    private function formatReturnedBy($goodsIn)
    {
        $user = User::where('username', $goodsIn->returned_by)->first();
        $department = $user && $user->department ? $user->department->name : '';

        if ($department) {
            return '<span data-bs-toggle="tooltip" data-bs-placement="right" title="' . ucfirst($department) . '">' . ucfirst($goodsIn->returned_by) . '</span>';
        }

        return ucfirst($goodsIn->returned_by);
    }

    // Helper method untuk action buttons
    private function getActionButtons($goodsIn)
    {
        $buttons = '<div class="d-flex flex-nowrap gap-1">';

        // Edit button
        if (auth()->user()->username === $goodsIn->returned_by || in_array(auth()->user()->role, ['admin_logistic', 'super_admin', 'admin_finance', 'admin'])) {
            $buttons .=
                '<a href="' .
                route('goods_in.edit', $goodsIn->id) .
                '" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit">
                <i class="bi bi-pencil-square"></i>
            </a>';
        }

        // Delete button - only for independent goods in (not linked to goods out)
        if (!$goodsIn->goods_out_id && in_array(auth()->user()->role, ['admin_logistic', 'super_admin', 'admin_finance', 'admin'])) {
            $buttons .=
                '<button type="button" class="btn btn-sm btn-danger btn-delete"
                data-id="' .
                $goodsIn->id .
                '"
                data-material="' .
                ($goodsIn->inventory ? $goodsIn->inventory->name : 'Unknown') .
                '"
                title="Delete">
                <i class="bi bi-trash3"></i>
            </button>';
        }

        $buttons .= '</div>';
        return $buttons;
    }

    public function export(Request $request)
    {
        // Dapatkan filter dari request
        $material = $request->material;
        $project = $request->project;
        $qty = $request->qty;
        $returnedBy = $request->returned_by;
        $returnedAt = $request->returned_at;

        // Query dengan filter
        $query = GoodsIn::with(['goodsOut.project', 'project']);

        if ($material) {
            $query->where('inventory_id', $material);
        }

        if ($project) {
            $query->where('project_id', $project);
        }

        if ($qty) {
            $query->where('quantity', $qty);
        }

        if ($returnedBy) {
            $query->where('returned_by', $returnedBy);
        }

        if ($returnedAt) {
            $query->whereDate('returned_at', $returnedAt);
        }

        $goodsIns = $query->get();

        // Generate dynamic file name
        $fileName = 'goods_in';
        if ($material) {
            $materialName = Inventory::find($material)->name ?? 'Unknown Material';
            $fileName .= '_material-' . str_replace(' ', '-', strtolower($materialName));
        }
        if ($project) {
            $projectName = Project::find($project)->name ?? 'Unknown Project';
            $fileName .= '_project-' . str_replace(' ', '-', strtolower($projectName));
        }
        if ($qty) {
            $fileName .= '_qty-' . $qty;
        }
        if ($returnedBy) {
            $fileName .= '_returned_by-' . strtolower($returnedBy);
        }
        if ($returnedAt) {
            $fileName .= '_returned_at-' . $returnedAt;
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        // Download Excel file
        return Excel::download(new GoodsInExport($goodsIns), $fileName);
    }

    public function create($goods_out_id)
    {
        $goodsOut = GoodsOut::with('inventory', 'project')->findOrFail($goods_out_id);

        return view('logistic.goods_in.create', compact('goodsOut'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'goods_out_id' => 'required|exists:goods_out,id',
            'quantity' => 'required|numeric|min:0.01',
            'returned_at' => 'required',
            'remark' => 'nullable|string',
        ]);

        $goodsOut = GoodsOut::findOrFail($request->goods_out_id);

        // Validasi jumlah pengembalian
        if ($request->quantity > $goodsOut->remaining_quantity) {
            return back()->with('error', 'Returned quantity cannot exceed Remaining Quantity to Goods In.');
        }

        // Tambahkan stok ke inventory
        $inventory = $goodsOut->inventory;
        if (!$inventory) {
            return back()->with('error', 'Inventory not found.');
        }

        // Validasi pastikan stok inventory tidak menjadi negatif
        if ($inventory->quantity + $request->quantity < 0) {
            return back()->with('error', 'Inventory stock cannot be negative.');
        }

        // Kurangi jumlah Goods Out
        $inventory->quantity += $request->quantity;
        $inventory->save();

        // Simpan Goods In (tambahkan inventory_id dan project_id)
        GoodsIn::create([
            'goods_out_id' => $goodsOut->id,
            'inventory_id' => $goodsOut->inventory_id,
            'project_id' => $goodsOut->project_id,
            'quantity' => $request->quantity,
            'returned_by' => Auth::user()->username,
            'returned_at' => $request->returned_at,
            'remark' => $request->remark,
        ]);

        // Sinkronkan data penggunaan material
        MaterialUsageHelper::sync($inventory->id, $goodsOut->project_id);

        $projectName = $goodsOut->project ? $goodsOut->project->name : 'No Project';

        return redirect()
            ->route('goods_in.index')
            ->with('success', "Goods In <b>{$inventory->name}</b> for <b>{$projectName}</b> added successfully!");
    }

    public function createIndependent()
    {
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        return view('logistic.goods_in.create_independent', compact('inventories', 'projects'));
    }

    public function storeIndependent(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'project_id' => 'nullable|exists:projects,id',
            'quantity' => 'required|numeric|min:0.01',
            'returned_at' => 'required',
            'remark' => 'nullable|string',
        ]);

        $inventory = Inventory::findOrFail($request->inventory_id);

        // Validasi tambahan: Pastikan stok inventory tidak menjadi negatif
        if ($inventory->quantity + $request->quantity < 0) {
            return back()->with('error', 'Inventory stock cannot be negative.');
        }

        // Tambahkan stok ke inventory
        $inventory->quantity += $request->quantity;
        $inventory->save();

        // Simpan Goods In
        GoodsIn::create([
            'goods_out_id' => null, // Tidak terkait dengan Goods Out
            'inventory_id' => $request->inventory_id,
            'project_id' => $request->project_id,
            'quantity' => $request->quantity,
            'returned_by' => Auth::user()->username,
            'returned_at' => $request->returned_at,
            'remark' => $request->remark,
        ]);

        if ($request->filled('project_id')) {
            MaterialUsageHelper::sync($request->inventory_id, $request->project_id);
        }

        return redirect()
            ->route('goods_in.index')
            ->with('success', "Goods In independent <b>{$inventory->name}</b> created successfully.");
    }

    public function bulkGoodsIn(Request $request)
    {
        $request->validate([
            'goods_in_quantities' => 'required|array',
            'goods_in_quantities.*' => 'numeric|min:0.01',
        ]);

        foreach ($request->goods_in_quantities as $goodsOutId => $quantity) {
            $goodsOut = GoodsOut::findOrFail($goodsOutId);

            if ($quantity > $goodsOut->remaining_quantity) {
                return response()->json(['error' => "Quantity for Goods Out ID {$goodsOutId} exceeds remaining quantity."], 422);
            }

            $inventory = $goodsOut->inventory;

            // Update inventory stock
            $inventory->quantity += $quantity;
            $inventory->save();

            // Create Goods In record
            GoodsIn::create([
                'goods_out_id' => $goodsOut->id,
                'inventory_id' => $goodsOut->inventory_id,
                'project_id' => $goodsOut->project_id,
                'quantity' => $quantity,
                'returned_by' => Auth::user()->username,
                'returned_at' => now(),
                'remark' => 'Bulk Goods In',
            ]);

            MaterialUsageHelper::sync($goodsOut->inventory_id, $goodsOut->project_id);
        }

        return response()->json(['success' => 'Bulk Goods In processed successfully.']);
    }

    public function edit(GoodsIn $goods_in)
    {
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::with('departments')->orderBy('name')->get();
        $userDept = null;
        if ($goods_in->returned_by) {
            $userDept = User::with('department')->where('username', $goods_in->returned_by)->first();
        }
        return view('logistic.goods_in.edit', compact('goods_in', 'inventories', 'projects', 'userDept'));
    }

    public function update(Request $request, GoodsIn $goods_in)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'project_id' => 'nullable|exists:projects,id',
            'quantity' => 'required|numeric|min:0.01',
            'returned_at' => 'required',
            'remark' => 'nullable|string',
        ]);

        // Jika terkait Goods Out, validasi sisa qty
        if ($goods_in->goods_out_id) {
            $goodsOut = $goods_in->goodsOut;
            $totalOtherGoodsIn = $goodsOut->goodsIns()->where('id', '!=', $goods_in->id)->sum('quantity');
            $maxQty = $goodsOut->quantity - $totalOtherGoodsIn;

            if ($request->quantity > $maxQty) {
                return back()
                    ->withInput()
                    ->withErrors(['quantity' => "Returned quantity cannot exceed remaining quantity to Goods In ({$maxQty})."]);
            }
            // Paksa inventory_id dan project_id tetap sama
            $request->merge([
                'inventory_id' => $goodsOut->inventory_id,
                'project_id' => $goodsOut->project_id,
            ]);
        }

        $goods_in->update([
            'inventory_id' => $request->inventory_id,
            'project_id' => $request->project_id,
            'quantity' => $request->quantity,
            'returned_at' => $request->returned_at,
            'remark' => $request->remark,
        ]);

        if ($request->filled('project_id')) {
            MaterialUsageHelper::sync($request->inventory_id, $request->project_id);
        }

        $inventory = Inventory::findOrFail($request->inventory_id);
        $projectName = $request->project_id ? Project::findOrFail($request->project_id)->name : 'No Project';

        return redirect()
            ->route('goods_in.index')
            ->with('success', "Goods In <b>{$inventory->name}</b> from <b>{$projectName}</b> updated successfully!");
    }

    public function restore(Request $request, $id)
    {
        // Ubah ke parameter $id
        $goods_in = GoodsIn::onlyTrashed()->findOrFail($id); // Query manual

        DB::beginTransaction();
        try {
            $inventory = $goods_in->inventory;
            $projectId = $goods_in->project_id;
            $inventoryId = $goods_in->inventory_id;

            // Restore record
            $goods_in->restore();

            // Kembalikan stok inventory
            if ($inventory) {
                $inventory->quantity += $goods_in->quantity;
                $inventory->save();
            }

            // Re-sync Material Usage
            MaterialUsageHelper::sync($inventoryId, $projectId);

            DB::commit();

            $inventoryName = $inventory ? $inventory->name : 'Unknown Material';
            $projectName = $goods_in->project ? $goods_in->project->name : 'No Project';

            return redirect()
                ->route('trash.index')
                ->with('success', "Goods In <b>{$inventoryName}</b> for <b>{$projectName}</b> restored successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('trash.index')
                ->with('error', 'Failed to restore Goods In: ' . $e->getMessage());
        }
    }

    public function destroy(GoodsIn $goods_in)
    {
        DB::beginTransaction();
        try {
            $inventory = $goods_in->inventory;
            $goodsOut = $goods_in->goodsOut;
            $projectId = $goods_in->project_id;
            $inventoryId = $goods_in->inventory_id;

            // Kembalikan stok inventory
            if ($inventory) {
                $inventory->quantity -= $goods_in->quantity;
                $inventory->save();
            }

            // Hapus Goods In
            $goods_in->delete();

            // Sinkronkan Material Usage (akan otomatis update)
            MaterialUsageHelper::sync($inventoryId, $projectId);

            DB::commit();

            $inventoryName = $inventory ? $inventory->name : 'Unknown Material';
            $projectName = $goods_in->project ? $goods_in->project->name : 'No Project';

            return redirect()
                ->route('goods_in.index')
                ->with('success', "Goods In <b>{$inventoryName}</b> for <b>{$projectName}</b> deleted successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('goods_in.index')
                ->with('error', 'Failed to delete Goods In: ' . $e->getMessage());
        }
    }
}
