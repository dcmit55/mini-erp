<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\GoodsOut;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Models\MaterialRequest;
use App\Helpers\MaterialUsageHelper;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GoodsOutExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoodsOutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Batasi akses untuk fitur tertentu agar hanya bisa diakses oleh admin_logistic dan super_admin
        $this->middleware(function ($request, $next) {
            if (in_array($request->route()->getName(), ['goods_out.create_with_id', 'goods_out.store', 'goods_out.create_independent', 'goods_out.store_independent', 'goods_out.bulk', 'goods_out.edit', 'goods_out.update', 'goods_out.destroy']) && !in_array(Auth::user()->role, ['admin_logistic', 'super_admin'])) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        })->only(['create', 'store', 'createIndependent', 'storeIndependent', 'bulkGoodsOut', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        // Tambahkan eager loading untuk relasi goodsIns
        $query = GoodsOut::with(['inventory', 'project', 'goodsIns', 'materialRequest', 'user.department']);

        // Apply filters
        if ($request->has('material') && $request->material !== null) {
            $query->where('inventory_id', $request->material);
        }

        if ($request->has('qty') && $request->qty !== null) {
            $query->where('quantity', $request->qty);
        }

        if ($request->has('project') && $request->project !== null) {
            $query->where('project_id', $request->project);
        }

        if ($request->has('requested_at') && $request->requested_at !== null) {
            $query->whereDate('created_at', $request->requested_at);
        }

        if ($request->has('requested_by') && $request->requested_by !== null) {
            $query->where('requested_by', $request->requested_by);
        }

        $goodsOuts = $query->orderBy('created_at', 'desc')->get();

        // Pass data for filters
        $materials = Inventory::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $quantities = GoodsOut::select('quantity')->distinct()->pluck('quantity');
        $users = User::orderBy('username')->get();

        return view('goods_out.index', compact('goodsOuts', 'materials', 'projects', 'quantities', 'users'));
    }

    public function export(Request $request)
    {
        // Ambil filter dari request
        $material = $request->material;
        $qty = $request->qty;
        $project = $request->project;
        $requestedBy = $request->requested_by;
        $requestedAt = $request->requested_at;

        // Filter data berdasarkan request
        $query = GoodsOut::with('inventory', 'project');

        if ($material) {
            $query->where('inventory_id', $material);
        }

        if ($qty) {
            $query->where('quantity', $qty);
        }

        if ($project) {
            $query->where('project_id', $project);
        }

        if ($requestedBy) {
            $query->where('requested_by', $requestedBy);
        }

        if ($requestedAt) {
            $query->whereDate('created_at', $requestedAt);
        }

        $goodsOuts = $query->get();

        // Buat nama file dinamis
        $fileName = 'goods_out';
        if ($material) {
            $materialName = Inventory::find($material)->name ?? 'Unknown Material';
            $fileName .= '_material-' . str_replace(' ', '-', strtolower($materialName));
        }
        if ($qty) {
            $fileName .= '_qty-' . $qty;
        }
        if ($project) {
            $projectName = Project::find($project)->name ?? 'Unknown Project';
            $fileName .= '_project-' . str_replace(' ', '-', strtolower($projectName));
        }
        if ($requestedBy) {
            $fileName .= '_requested_by-' . strtolower($requestedBy);
        }
        if ($requestedAt) {
            $fileName .= '_proceed_at-' . $requestedAt;
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        // Ekspor data menggunakan kelas GoodsOutExport
        return Excel::download(new GoodsOutExport($goodsOuts), $fileName);
    }

    public function create($materialRequestId)
    {
        $materialRequest = MaterialRequest::with('inventory', 'project')->findOrFail($materialRequestId);
        $inventories = Inventory::orderBy('name')->get();
        return view('goods_out.create', compact('materialRequest', 'inventories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_request_id' => 'required|exists:material_requests,id',
            'quantity' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Lock inventory row
            $materialRequest = MaterialRequest::where('id', $request->material_request_id)->lockForUpdate()->first();
            $inventory = Inventory::where('id', $materialRequest->inventory_id)->lockForUpdate()->first();

            // VALIDASI: Quantity tidak boleh melebihi Remaining Quantity
            $remainingQty = $materialRequest->qty - $materialRequest->processed_qty;
            if ($request->quantity > $remainingQty) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Quantity cannot exceed the remaining requested quantity.');
            }

            // Validasi tambahan: stok inventory
            if ($request->quantity > $inventory->quantity) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Quantity cannot exceed the available inventory.');
            }

            // Tambahkan ke processed_qty
            $materialRequest->processed_qty += $request->quantity;

            // Update status jika sudah selesai
            if ($materialRequest->processed_qty >= $materialRequest->qty) {
                $materialRequest->status = 'delivered';
            }

            $materialRequest->save();

            event(new \App\Events\MaterialRequestUpdated($materialRequest, 'status'));

            // Simpan Goods Out
            GoodsOut::create([
                'material_request_id' => $materialRequest->id,
                'inventory_id' => $inventory->id,
                'project_id' => $materialRequest->project_id,
                'requested_by' => $materialRequest->requested_by,
                'quantity' => $request->quantity,
                'remark' => $request->remark,
            ]);

            // Kurangi stok inventory
            $inventory->quantity -= $request->quantity;
            $inventory->save();

            MaterialUsageHelper::sync($inventory->id, $materialRequest->project_id);

            DB::commit();
            return redirect()
                ->route('goods_out.index')
                ->with('success', "Goods Out <b>{$inventory->name}</b> to <b>{$materialRequest->project->name}</b> processed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to process Goods Out: ' . $e->getMessage());
        }
    }

    public function createIndependent()
    {
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::with('department', 'status')->notArchived()->orderBy('name')->get();
        $users = User::with('department')->orderBy('username')->get();
        return view('goods_out.create_independent', compact('inventories', 'projects', 'users'));
    }

    public function storeIndependent(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'project_id' => 'nullable|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Lock inventory row
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();
            $user = User::with('department')->findOrFail($request->user_id);

            // Ambil nama department dari relasi
            $department = $user->department ? $user->department->name : null;

            // Validasi quantity setelah lock
            if ($request->quantity > $inventory->quantity) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['quantity' => 'Quantity cannot exceed the available inventory.']);
            }

            // Kurangi stok di inventory
            $inventory->quantity -= $request->quantity;
            $inventory->save();

            // Simpan Goods Out
            GoodsOut::create([
                'inventory_id' => $request->inventory_id,
                'project_id' => $request->project_id,
                'requested_by' => $user->username,
                'quantity' => $request->quantity,
                'remark' => $request->remark,
            ]);

            // Sync Material Usage hanya jika ada project
            if ($request->filled('project_id')) {
                MaterialUsageHelper::sync($request->inventory_id, $request->project_id);
            }

            DB::commit();
            return redirect()
                ->route('goods_out.index')
                ->with('success', "Goods Out <b>{$inventory->name}</b> created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to process Goods Out: ' . $e->getMessage());
        }
    }

    public function bulkGoodsOut(Request $request)
    {
        $request->validate([
            'goods_out_qty' => 'required|array',
            'goods_out_qty.*' => 'numeric|min:0.001',
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:material_requests,id',
        ]);

        $selectedIds = array_keys($request->goods_out_qty);

        DB::beginTransaction();
        try {
            $materialRequests = MaterialRequest::whereIn('id', $selectedIds)->where('status', 'approved')->lockForUpdate()->get();

            if ($materialRequests->isEmpty()) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No approved material requests found for bulk goods out.'], 422);
            }

            $updatedRequests = [];
            foreach ($materialRequests as $materialRequest) {
                $inventory = Inventory::where('id', $materialRequest->inventory_id)->lockForUpdate()->first();

                $remainingQty = $materialRequest->qty - $materialRequest->processed_qty;
                $qtyToGoodsOut = $request->goods_out_qty[$materialRequest->id];

                // Validasi qty
                if ($qtyToGoodsOut > $remainingQty) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Qty to Goods Out for Material Request {$materialRequest->id} exceeds remaining qty."], 422);
                }
                if ($qtyToGoodsOut > $inventory->quantity) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Insufficient stock for {$inventory->name}."], 422);
                }
                if ($qtyToGoodsOut <= 0) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Qty to Goods Out must be greater than 0.'], 422);
                }

                // Kurangi stok inventory
                $inventory->quantity -= $qtyToGoodsOut;
                $inventory->save();

                // Buat Goods Out
                GoodsOut::create([
                    'material_request_id' => $materialRequest->id,
                    'inventory_id' => $inventory->id,
                    'project_id' => $materialRequest->project_id,
                    'requested_by' => $materialRequest->requested_by,
                    'quantity' => $qtyToGoodsOut,
                    'remark' => 'Bulk Goods Out',
                ]);

                // Update processed_qty dan status material request
                $materialRequest->processed_qty += $qtyToGoodsOut;
                if ($materialRequest->processed_qty >= $materialRequest->qty) {
                    $materialRequest->status = 'delivered';
                }
                $materialRequest->save();

                $updatedRequests[] = $materialRequest->fresh(['inventory', 'project']);

                MaterialUsageHelper::sync($inventory->id, $materialRequest->project_id);
            }

            DB::commit();

            // Broadcast real-time ke semua client
            if ($updatedRequests) {
                event(new \App\Events\MaterialRequestUpdated($updatedRequests, 'status'));
            }

            return response()->json(['success' => true, 'message' => 'Bulk Goods Out processed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Bulk Goods Out failed: ' . $e->getMessage()], 500);
        }
    }

    public function getDetails(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:goods_out,id',
        ]);

        $goodsOuts = GoodsOut::whereIn('id', $request->selected_ids)
            ->with('inventory')
            ->get()
            ->map(function ($goodsOut) {
                return [
                    'id' => $goodsOut->id,
                    'material_name' => $goodsOut->inventory->name,
                    'goods_out_quantity' => $goodsOut->quantity,
                ];
            });

        return response()->json($goodsOuts);
    }

    public function edit($id)
    {
        $goodsOut = GoodsOut::with('inventory', 'project', 'materialRequest')->findOrFail($id);
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::with('department', 'status')->notArchived()->orderBy('name')->get();
        $users = User::with('department')->orderBy('username')->get();

        $fromMaterialRequest = $goodsOut->material_request_id ? true : false;

        return view('goods_out.edit', compact('goodsOut', 'inventories', 'projects', 'users', 'fromMaterialRequest'));
    }

    public function update(Request $request, $id)
    {
        $goodsOut = GoodsOut::findOrFail($id);

        // Jika dari Material Request, pakai project_id lama
        if ($goodsOut->material_request_id) {
            $request->merge([
                'project_id' => $goodsOut->project_id,
                'inventory_id' => $goodsOut->inventory_id,
                'user_id' => User::where('username', $goodsOut->requested_by)->value('id'),
            ]);
        }

        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $goodsOut = GoodsOut::lockForUpdate()->findOrFail($id);
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();
            $materialRequest = $goodsOut->materialRequest;
            $user = User::with('department')->findOrFail($request->user_id);

            $oldQuantity = $goodsOut->quantity;
            $inventory->quantity += $oldQuantity;

            // VALIDASI: Quantity tidak boleh melebihi Remaining Quantity (jika ada material request)
            if ($materialRequest) {
                $remainingQty = $materialRequest->qty - ($materialRequest->processed_qty - $oldQuantity);
                if ($request->quantity > $remainingQty) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['quantity' => 'Quantity cannot exceed the remaining requested quantity.']);
                }
            }

            // Validasi stok inventory
            if ($request->quantity > $inventory->quantity) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['quantity' => 'Quantity cannot exceed the available inventory.']);
            }

            // Kurangi stok dengan quantity baru
            $inventory->quantity -= $request->quantity;
            $inventory->save();

            // Perbarui Material Request dengan quantity baru
            if ($materialRequest) {
                // Kembalikan processed_qty lama
                $materialRequest->processed_qty -= $oldQuantity;
                // Tambahkan processed_qty baru
                $materialRequest->processed_qty += $request->quantity;

                // Perbarui status jika quantity habis
                if ($materialRequest->processed_qty >= $materialRequest->qty) {
                    $materialRequest->status = 'delivered';
                } else {
                    $materialRequest->status = 'approved';
                }

                $materialRequest->save();
            }

            $department = $user->department ? $user->department->name : null;

            // Perbarui Goods Out
            $goodsOut->update([
                'inventory_id' => $request->inventory_id,
                'project_id' => $request->project_id,
                'requested_by' => $user->username,
                'quantity' => $request->quantity,
                'remark' => $request->remark,
            ]);

            MaterialUsageHelper::sync($goodsOut->inventory_id, $goodsOut->project_id);

            DB::commit();

            return redirect()
                ->route('goods_out.index')
                ->with('success', "Goods Out <b>{$inventory->name}</b> to <b>{$materialRequest->project->name}</b> processed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update Goods Out: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        $goodsOut = GoodsOut::withTrashed()->findOrFail($id);

        // Restore Goods Out
        $goodsOut->restore();

        // Kurangi stok di inventory
        $inventory = $goodsOut->inventory;
        if ($inventory) {
            $inventory->quantity -= $goodsOut->quantity;
            $inventory->save();
        }

        // Sinkronkan Material Usage
        MaterialUsageHelper::sync($goodsOut->inventory_id, $goodsOut->project_id);

        return redirect()->route('goods_out.index')->with('success', 'Goods Out restored successfully.');
    }

    public function destroy($id)
    {
        $goodsOut = GoodsOut::findOrFail($id);

        // Cek apakah ada Goods In yang terkait
        if ($goodsOut->goodsIns()->exists()) {
            return redirect()
                ->route('goods_out.index')
                ->with('error', "Cannot delete Goods Out <b>{$goodsOut->id}</b> with related Goods In.");
        }

        // Kembalikan stok ke inventory
        $inventory = $goodsOut->inventory;
        $inventory->quantity += $goodsOut->quantity;
        $inventory->save();

        // Soft delete Goods Out
        $goodsOut->delete();

        MaterialUsageHelper::sync($goodsOut->inventory_id, $goodsOut->project_id);

        return redirect()
            ->route('goods_out.index')
            ->with('success', "Goods Out <b>{$inventory->name}</b> to <b>{$goodsOut->project->name}</b> deleted successfully.");
    }
}
