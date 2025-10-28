<?php

namespace App\Http\Controllers;

use App\Models\GoodsIn;
use App\Models\Project;
use App\Models\GoodsOut;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Helpers\MaterialUsageHelper;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GoodsInExport;
use Illuminate\Support\Facades\Auth;

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

    public function index(Request $request)
    {
        $query = GoodsIn::with(['goodsOut.inventory', 'goodsOut.project', 'inventory', 'project']);

        // Apply filters
        if ($request->has('material') && $request->material !== null) {
            $query->where('inventory_id', $request->material);
        }

        if ($request->has('project') && $request->project !== null) {
            $query->where('project_id', $request->project);
        }

        if ($request->has('qty') && $request->qty !== null) {
            $query->where('quantity', $request->qty);
        }

        if ($request->has('returned_by') && $request->returned_by !== null) {
            $query->where('returned_by', $request->returned_by);
        }

        if ($request->has('returned_at') && $request->returned_at !== null) {
            $query->whereDate('returned_at', $request->returned_at);
        }

        $goodsIns = $query->orderBy('created_at', 'desc')->get();

        // Pass data for filters
        $materials = Inventory::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $quantities = GoodsIn::select('quantity')->distinct()->pluck('quantity');
        $users = User::with('department')->get()->keyBy('username');

        return view('goods_in.index', compact('goodsIns', 'materials', 'projects', 'quantities', 'users'));
    }

    public function export(Request $request)
    {
        // Ambil filter dari request
        $material = $request->material;
        $project = $request->project;
        $qty = $request->qty;
        $returnedBy = $request->returned_by;
        $returnedAt = $request->returned_at;

        // Filter data berdasarkan request
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

        // Buat nama file dinamis
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

        // Ekspor data menggunakan kelas GoodsInExport
        return Excel::download(new GoodsInExport($goodsIns), $fileName);
    }

    public function create($goods_out_id)
    {
        $goodsOut = GoodsOut::with('inventory', 'project')->findOrFail($goods_out_id);

        return view('goods_in.create', compact('goodsOut'));
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
        return view('goods_in.create_independent', compact('inventories', 'projects'));
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
        $projects = Project::with('department')->orderBy('name')->get();
        $userDept = null;
        if ($goods_in->returned_by) {
            $userDept = User::with('department')->where('username', $goods_in->returned_by)->first();
        }
        return view('goods_in.edit', compact('goods_in', 'inventories', 'projects', 'userDept'));
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

    public function destroy(GoodsIn $goods_in)
    {
        // Cek apakah Goods In terkait dengan Goods Out
        if ($goods_in->goods_out_id) {
            return redirect()->route('goods_in.index')->with('error', 'Cannot delete Goods In that is linked to a Goods Out.');
        }

        // Hapus Goods In
        $goods_in->delete();

        // Sinkronkan Material Usage
        MaterialUsageHelper::sync($goods_in->inventory_id, $goods_in->project_id);

        $inventoryName = $goods_in->inventory ? $goods_in->inventory->name : 'Unknown Material';
        $projectName = $goods_in->project ? $goods_in->project->name : 'No Project';

        return redirect()
            ->route('goods_in.index')
            ->with('success', "Goods In <b>{$inventoryName}</b> for <b>{$projectName}</b> deleted successfully!");
    }
}
