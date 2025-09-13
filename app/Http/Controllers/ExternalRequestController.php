<?php

namespace App\Http\Controllers;

use App\Models\ExternalRequest;
use App\Models\Inventory;
use App\Models\Unit;
use App\Models\Project;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExternalRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $allowedRoles = ['super_admin', 'admin_procurement'];
            if (!in_array(auth()->user()->role, $allowedRoles)) {
                abort(403, 'Unauthorized access to Procurement module.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requests = ExternalRequest::with(['inventory', 'project', 'user'])
            ->latest()
            ->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $currencies = \App\Models\Currency::orderBy('name')->get();
        return view('external_requests.index', compact('requests', 'suppliers', 'currencies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $inventories = Inventory::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('external_requests.create', compact('inventories', 'units', 'projects', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:new_material,restock',
            'material_name' => 'required_if:type,new_material',
            'inventory_id' => 'required_if:type,restock|nullable|exists:inventories,id',
            'required_quantity' => 'required|numeric|min:0.01',
            'unit' => 'required',
            'stock_level' => 'required|numeric|min:0',
            'project_id' => 'required|exists:projects,id',
        ]);

        $data = $request->all();
        $data['requested_by'] = Auth::id();

        // Untuk restock, ambil nama material, unit, dan stock dari inventory
        if ($request->type === 'restock' && $request->inventory_id) {
            $inventory = Inventory::find($request->inventory_id);
            $data['material_name'] = $inventory->name;
            $data['unit'] = $inventory->unit;
            $data['stock_level'] = $inventory->quantity;
        }

        ExternalRequest::create($data);

        return redirect()->route('external_requests.index')->with('success', 'External request submitted!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $request = ExternalRequest::findOrFail($id);
        $inventories = Inventory::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        return view('external_requests.edit', compact('request', 'inventories', 'units', 'projects', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:new_material,restock',
            'material_name' => 'required_if:type,new_material',
            'inventory_id' => 'required_if:type,restock|nullable|exists:inventories,id',
            'required_quantity' => 'required|numeric|min:0.01',
            'unit' => 'required',
            'stock_level' => 'required|numeric|min:0',
            'project_id' => 'required|exists:projects,id',
        ]);

        $data = $request->all();

        if ($request->type === 'restock' && $request->inventory_id) {
            $inventory = Inventory::find($request->inventory_id);
            $data['material_name'] = $inventory->name;
            $data['unit'] = $inventory->unit;
            $data['stock_level'] = $inventory->quantity;
        }

        $externalRequest = ExternalRequest::findOrFail($id);
        $externalRequest->update($data);

        return redirect()->route('external_requests.index')->with('success', 'External request updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $externalRequest = ExternalRequest::findOrFail($id);
        $externalRequest->delete();

        return redirect()->route('external_requests.index')->with('success', 'External request deleted!');
    }

    public function quickUpdate(Request $request, $id)
    {
        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'price_per_unit' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'approval_status' => 'nullable|in:Approved,Decline',
        ]);
        $externalRequest = ExternalRequest::findOrFail($id);
        $externalRequest->update($request->only(['supplier_id', 'price_per_unit', 'currency_id', 'approval_status']));
        return response()->json(['success' => true]);
    }
}
