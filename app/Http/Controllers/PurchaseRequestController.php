<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\Inventory;
use App\Models\Unit;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Currency;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PurchaseRequestController extends Controller
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
        $requests = PurchaseRequest::with(['inventory', 'project', 'user'])
            ->latest()
            ->get();
        $suppliers = Supplier::orderBy('name')->get();
        $currencies = Currency::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        return view('purchase_requests.index', compact('requests', 'suppliers', 'currencies', 'projects'));
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

        return view('purchase_requests.create', compact('inventories', 'units', 'projects', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'requests' => 'required|array|min:1',
            'requests.*.type' => 'required|in:new_material,restock',
            'requests.*.material_name' => 'required_if:requests.*.type,new_material',
            'requests.*.inventory_id' => 'required_if:requests.*.type,restock|nullable|exists:inventories,id',
            'requests.*.required_quantity' => 'required|numeric|min:0.01',
            'requests.*.unit' => 'required',
            'requests.*.stock_level' => 'nullable|numeric|min:0',
            'requests.*.project_id' => 'nullable|exists:projects,id',
            'requests.*.img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $successCount = 0;
        $errors = [];

        foreach ($request->requests as $key => $requestData) {
            try {
                // Skip if empty data - safety check
                if (empty($requestData['type'])) {
                    continue;
                }

                // For new_material type, check if it already exists
                if ($requestData['type'] === 'new_material') {
                    $exists = Inventory::whereRaw('LOWER(name) = ?', [strtolower($requestData['material_name'])])->exists();
                    if ($exists) {
                        $errors[] = 'Row ' . ($key + 1) . ": Material '{$requestData['material_name']}' already exists in inventory.";
                        continue;
                    }
                }

                $data = $requestData;

                // For restock type, get material name, unit, and stock level from inventory
                if ($requestData['type'] === 'restock' && !empty($requestData['inventory_id'])) {
                    $inventory = Inventory::find($requestData['inventory_id']);
                    $data['material_name'] = $inventory->name;
                    $data['unit'] = $inventory->unit;
                    $data['stock_level'] = $inventory->quantity;
                }

                // Handle image upload if present
                if (isset($request->file('requests')[$key]['img'])) {
                    $file = $request->file('requests')[$key]['img'];
                    $data['img'] = $file->store('purchase_requests', 'public');
                }

                // Add the user ID
                $data['requested_by'] = Auth::id();

                // Create the purchase request
                PurchaseRequest::create($data);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = 'Error in row ' . ($key + 1) . ': ' . $e->getMessage();
            }
        }

        if ($successCount > 0) {
            $message = $successCount . ' purchase request(s) submitted successfully!';
            if (!empty($errors)) {
                return redirect()
                    ->route('purchase_requests.index')
                    ->with('success', $message)
                    ->with('warning', 'Some requests could not be processed: ' . implode('<br>', $errors));
            }
            return redirect()->route('purchase_requests.index')->with('success', $message);
        } else {
            return back()
                ->withInput()
                ->with('error', 'No requests were processed. Errors: ' . implode('<br>', $errors));
        }
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
        $request = PurchaseRequest::findOrFail($id);
        $inventories = Inventory::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        return view('purchase_requests.edit', compact('request', 'inventories', 'units', 'projects', 'departments'));
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
            'stock_level' => 'nullable|numeric|min:0',
            'project_id' => 'nullable|exists:projects,id',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->type === 'new_material') {
            $exists = Inventory::whereRaw('LOWER(name) = ?', [strtolower($request->material_name)])->exists();
            if ($exists) {
                return back()
                    ->withInput()
                    ->withErrors(['material_name' => 'Material already exists in inventory.']);
            }
        }

        $data = $request->all();

        if ($request->type === 'restock' && $request->inventory_id) {
            $inventory = Inventory::find($request->inventory_id);
            $data['material_name'] = $inventory->name;
            $data['unit'] = $inventory->unit;
            $data['stock_level'] = $inventory->quantity;
        }

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('purchase_requests', 'public');
        }

        $purchaseRequest = PurchaseRequest::findOrFail($id);
        $purchaseRequest->update($data);

        return redirect()
            ->route('purchase_requests.index')
            ->with('success', 'Purchase request <strong>' . ($purchaseRequest->material_name ?? '-') . '</strong> updated!');
    }

    public function quickUpdate(Request $request, $id)
    {
        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'price_per_unit' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'approval_status' => 'nullable|in:Approved,Decline',
        ]);

        // Cek supplier ada
        if ($request->filled('supplier_id')) {
            $supplier = Supplier::find($request->supplier_id);
            if (!$supplier) {
                return response()->json(['success' => false, 'message' => 'Supplier not found.'], 422);
            }
        }

        // Cek currency ada
        if ($request->filled('currency_id')) {
            $currency = Currency::find($request->currency_id);
            if (!$currency) {
                return response()->json(['success' => false, 'message' => 'Currency not found.'], 422);
            }
        }

        $purchaseRequest = PurchaseRequest::findOrFail($id);
        $purchaseRequest->update($request->only(['supplier_id', 'price_per_unit', 'currency_id', 'approval_status']));
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($id);
        $purchaseRequest->delete();

        return redirect()
            ->route('purchase_requests.index')
            ->with('success', 'Purchase request <strong>' . ($purchaseRequest->material_name ?? '-') . '</strong> deleted!');
    }
}
