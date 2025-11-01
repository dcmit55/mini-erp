<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseRequest;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\Unit;
use App\Models\Production\Project;
use App\Models\Procurement\Supplier;
use App\Models\Finance\Currency;
use App\Models\Admin\User;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PurchaseRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
        return view('procurement.purchase_requests.index', compact('requests', 'suppliers', 'currencies', 'projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $inventories = Inventory::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        // PENAMBAHAN: Variabel untuk auto-fill data dari dashboard
        // Menangani parameter inventory_id dan type dari URL query string
        $selectedInventory = null;
        $prefilledType = null;
        $defaultRemark = null; //Default remark untuk auto-fill

        // Cek apakah ada parameter untuk auto-fill dari dashboard
        if ($request->has('inventory_id') && $request->has('type')) {
            // Load inventory dengan relasi category dan supplier untuk ditampilkan di alert
            $selectedInventory = Inventory::with(['category', 'supplier'])->find($request->inventory_id);
            // Set type berdasarkan parameter, default ke restock untuk low stock items
            $prefilledType = $request->type === 'restock' ? 'restock' : 'new_material';
            // Set default remark untuk request dari dashboard (low stock items)
            $defaultRemark = 'From low stock item';
        }

        // PENAMBAHAN: Pass variabel tambahan untuk auto-fill ke view
        return view(
            'procurement.purchase_requests.create',
            compact(
                'inventories',
                'units',
                'projects',
                'departments',
                'selectedInventory', // Data inventory yang dipilih dari dashboard
                'prefilledType', // Type yang sudah ditentukan (restock/new_material)
                'defaultRemark', // Default remark untuk auto-fill
            ),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (auth()->user()->isReadOnlyAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to modify purchase requests.');
        }

        $request->validate([
            'requests' => 'required|array|min:1',
            'requests.*.type' => 'required|in:new_material,restock',
            'requests.*.material_name' => 'required_if:requests.*.type,new_material',
            'requests.*.inventory_id' => 'required_if:requests.*.type,restock|nullable|exists:inventories,id',
            'requests.*.required_quantity' => 'required|numeric|min:0.01',
            'requests.*.unit' => 'required',
            'requests.*.stock_level' => 'nullable|numeric|min:0',
            'requests.*.project_id' => 'nullable|exists:projects,id',
            'requests.*.remark' => 'nullable|string|max:1000',
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

                // Auto-fill qty_to_buy with required_quantity
                $data['qty_to_buy'] = $data['required_quantity'] ?? null;

                // Handle image upload if present
                if (isset($request->file('requests')[$key]['img'])) {
                    $file = $request->file('requests')[$key]['img'];
                    $data['img'] = $file->store('purchase_requests', 'public');
                }

                // Add the user ID
                $data['requested_by'] = Auth::id();

                $data['approval_status'] = 'Pending'; // Set default status

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
        return view('procurement.purchase_requests.edit', compact('request', 'inventories', 'units', 'projects', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->isReadOnlyAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to modify purchase requests.');
        }

        $request->validate([
            'type' => 'required|in:new_material,restock',
            'material_name' => 'required_if:type,new_material',
            'inventory_id' => 'required_if:type,restock|nullable|exists:inventories,id',
            'required_quantity' => 'required|numeric|min:0.01',
            'unit' => 'required',
            'stock_level' => 'nullable|numeric|min:0',
            'project_id' => 'nullable|exists:projects,id',
            'remark' => 'nullable|string|max:1000',
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
        if (auth()->user()->isReadOnlyAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to modify purchase requests.');
        }

        $request->validate([
            'material_name' => 'nullable|string|max:255',
            'qty_to_buy' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'price_per_unit' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'approval_status' => 'nullable|in:Pending,Approved,Decline',
            'delivery_date' => 'nullable|date',
            'remark' => 'nullable|string|max:1000',
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

        $data = $request->only(['qty_to_buy', 'supplier_id', 'price_per_unit', 'currency_id', 'approval_status', 'delivery_date', 'remark']);
        if ($purchaseRequest->type === 'new_material' && $request->filled('material_name')) {
            $data['material_name'] = $request->material_name;
        }
        $purchaseRequest->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        if (auth()->user()->isReadOnlyAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to modify purchase requests.');
        }

        $purchaseRequest = PurchaseRequest::findOrFail($id);
        $purchaseRequest->delete();

        return redirect()
            ->route('purchase_requests.index')
            ->with('success', 'Purchase request <strong>' . ($purchaseRequest->material_name ?? '-') . '</strong> deleted!');
    }

    public function storeFromPlanning($planning)
    {
        try {
            \Log::info('Processing purchase request from planning: ' . $planning->id . ' - ' . $planning->material_name);

            // Cek apakah material ada di inventory
            $inventoryId = \App\Models\Logistic\Inventory::where('name', $planning->material_name)->value('id');

            // Ambil user berdasarkan ID dari planning
            $user = \App\Models\Admin\User::find($planning->requested_by);
            if (!$user) {
                \Log::error('User not found with ID: ' . $planning->requested_by);
                return null;
            }

            $purchaseRequest = \App\Models\Procurement\PurchaseRequest::create([
                'inventory_id' => $inventoryId, // Bisa null untuk material baru
                'material_name' => $planning->material_name,
                'project_id' => $planning->project_id,
                'required_quantity' => $planning->qty_needed,
                'unit' => optional($planning->unit)->name ?? '-',
                'requested_by' => $user->id, // Gunakan user ID, bukan planning->requested_by
                'remark' => 'Imported from Material Planning',
                'type' => $inventoryId ? 'restock' : 'new_material',
            ]);

            \Log::info('Purchase request successfully created with ID: ' . $purchaseRequest->id);

            return $purchaseRequest;
        } catch (\Exception $e) {
            \Log::error('Error creating purchase request: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
}
