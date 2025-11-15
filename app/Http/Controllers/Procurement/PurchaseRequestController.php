<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseRequest;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\Unit;
use App\Models\Production\Project;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\LocationSupplier;
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
     * Display a listing of the resource with server-side processing
     */
    public function index(Request $request)
    {
        // If AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->getDataTablesData($request);
        }

        // For non-AJAX requests, return view with master data for filters
        $suppliers = Supplier::orderBy('name')->get();
        $currencies = Currency::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $locations = LocationSupplier::orderBy('name')->get();

        return view('procurement.purchase_requests.index', compact('suppliers', 'currencies', 'projects', 'locations'));
    }

    /**
     * Server-side processing untuk DataTables
     */
    public function getDataTablesData(Request $request)
    {
        $query = PurchaseRequest::with(['inventory', 'project', 'user', 'supplier', 'currency'])->latest();

        // Apply filters
        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }

        if ($request->filled('project_filter')) {
            $query->where('project_id', $request->project_filter);
        }

        if ($request->filled('supplier_filter')) {
            $query->where('supplier_id', $request->supplier_filter);
        }

        if ($request->filled('approval_filter')) {
            if ($request->approval_filter === 'Pending') {
                $query->where('approval_status', '=', 'Pending');
            } else {
                $query->where('approval_status', $request->approval_filter);
            }
        }

        // Custom search functionality
        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->where('material_name', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('remark', 'LIKE', '%' . $searchValue . '%')
                    ->orWhereHas('project', function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%');
                    })
                    ->orWhereHas('supplier', function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%');
                    });
            });
        }

        // DataTables search
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->where('material_name', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('remark', 'LIKE', '%' . $searchValue . '%')
                    ->orWhereHas('project', function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%');
                    })
                    ->orWhereHas('supplier', function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%');
                    });
            });
        }

        // Sorting
        $columns = ['id', 'type', 'material_name', 'required_quantity', 'qty_to_buy', 'project_id', 'approval_status', 'created_at'];

        if ($request->filled('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');

            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get total and filtered counts
        $totalRecords = PurchaseRequest::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $purchaseRequests = $query->skip($start)->take($length)->get();

        // Check if user can view unit price
        $canViewUnitPrice = in_array(auth()->user()->role, ['super_admin', 'admin', 'admin_procurement', 'admin_logistic', 'admin_finance']);

        // Format data for DataTables
        $data = [];
        foreach ($purchaseRequests as $index => $pr) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'type' => ucfirst(str_replace('_', ' ', $pr->type)),
                'material_name' => $this->formatMaterialNameEditable($pr),
                'required_quantity' => $this->formatQuantity($pr->required_quantity, $pr->unit),
                'qty_to_buy' => $this->formatQtyToBuyEditable($pr),
                'supplier' => $this->formatSupplierSelect($pr),
                'unit_price' => $this->formatPriceInput($pr),
                'currency' => $this->formatCurrencySelect($pr),
                'approval_status' => $this->formatApprovalSelect($pr),
                'delivery_date' => $this->formatDeliveryDateInput($pr),
                'project' => $pr->project ? $pr->project->name : '-',
                'requested_by' => $pr->user ? $pr->user->username : '-',
                'remark' => $this->formatRemarkEditable($pr),
                'created_at' => $pr->created_at->format('d M Y'),
                'actions' => $this->getActionButtons($pr, $canViewUnitPrice),
                'DT_RowId' => 'row-' . $pr->id,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Format material name with stock level info
     */
    private function formatMaterialName($pr)
    {
        if (!$pr->stock_level && !$pr->inventory) {
            return $pr->material_name;
        }

        $stockInfo = $pr->stock_level ?? ($pr->inventory ? $pr->inventory->quantity : 0);
        $unit = $pr->unit ?? ($pr->inventory ? $pr->inventory->unit : '');

        return '<div class="d-flex align-items-center gap-1">
                    <i class="bi bi-info-circle text-secondary" style="cursor: pointer;"
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        title="Current stock: ' .
            number_format($stockInfo, 2) .
            ' ' .
            $unit .
            '"></i>
                    ' .
            $pr->material_name .
            '
                </div>';
    }

    /**
     * Format quantity with unit tooltip
     */
    private function formatQuantity($quantity, $unit)
    {
        $formatted = number_format($quantity, 2);
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return '<span data-bs-toggle="tooltip" data-bs-placement="right" title="' . $unit . '">' . $formatted . '</span>';
    }

    /**
     * Format approval status with badge
     */
    private function formatApprovalStatus($status)
    {
        $badgeClass = match ($status) {
            'Approved' => 'bg-success',
            'Decline' => 'bg-danger',
            default => 'bg-warning',
        };

        $statusText = $status ?? 'Pending';

        return '<span class="badge ' . $badgeClass . '">' . ucfirst($statusText) . '</span>';
    }

    /**
     * Format remark - handle URLs
     */
    private function formatRemark($remark)
    {
        if (!$remark) {
            return '-';
        }

        if (filter_var($remark, FILTER_VALIDATE_URL)) {
            return '<a href="' . $remark . '" target="_blank" rel="noopener noreferrer">' . \Illuminate\Support\Str::limit($remark, 30) . '</a>';
        }

        return \Illuminate\Support\Str::limit($remark, 30);
    }

    /**
     * Get action buttons
     */
    private function getActionButtons($pr, $canViewUnitPrice)
    {
        $buttons = '<div class="d-flex flex-nowrap gap-1">';

        if ($canViewUnitPrice) {
            // Show image button
            $buttons .=
                '<button class="btn btn-info btn-sm btn-show-image"
                        data-id="' .
                $pr->id .
                '"
                        data-img="' .
                ($pr->img ? asset('storage/' . $pr->img) : '') .
                '"
                        data-name="' .
                $pr->material_name .
                '"
                        title="View Image">
                        <i class="bi bi-image"></i>
                    </button>';

            // Edit button
            $buttons .=
                '<a href="' .
                route('purchase_requests.edit', $pr->id) .
                '"
                       class="btn btn-warning btn-sm" title="Edit">
                       <i class="bi bi-pencil-square"></i>
                    </a>';

            // Delete button
            $buttons .=
                '<button type="button" class="btn btn-danger btn-sm btn-delete"
                        data-id="' .
                $pr->id .
                '"
                        data-name="' .
                $pr->material_name .
                '"
                        title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>';
        } else {
            // Show image button only
            $buttons .=
                '<button class="btn btn-info btn-sm btn-show-image"
                        data-id="' .
                $pr->id .
                '"
                        data-img="' .
                ($pr->img ? asset('storage/' . $pr->img) : '') .
                '"
                        data-name="' .
                $pr->material_name .
                '"
                        title="View Image">
                        <i class="bi bi-image"></i>
                    </button>';
        }

        $buttons .= '</div>';
        return $buttons;
    }

    /**
     * Format cells untuk support edit inline
     */
    private function formatMaterialNameEditable($pr)
    {
        $stockInfo = $pr->stock_level ?? ($pr->inventory ? $pr->inventory->quantity : 0);
        $unit = $pr->unit ?? ($pr->inventory ? $pr->inventory->unit : '');

        // Show tooltip hanya untuk type 'restock'
        $tooltipHtml = '';
        if ($pr->type === 'restock') {
            $tooltipHtml =
                '<i class="bi bi-info-circle text-secondary" style="cursor: pointer;"
                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                    title="Current stock: ' .
                number_format($stockInfo, 2) .
                ' ' .
                $unit .
                '"></i>';
        }

        return '<div class="d-flex align-items-center gap-1 material-name-cell"
                data-id="' .
            $pr->id .
            '"
                data-value="' .
            htmlspecialchars($pr->material_name) .
            '"
                data-type="' .
            $pr->type .
            '"
                style="cursor: pointer;">
                ' .
            $tooltipHtml .
            '
                <span class="material-name-text">' .
            htmlspecialchars($pr->material_name) .
            '</span>
            </div>';
    }

    private function formatRemarkEditable($pr)
    {
        if (!$pr->remark) {
            return '<span class="remark-cell" data-id="' .
                $pr->id .
                '" data-value="" style="cursor: pointer;">
                    -
                </span>';
        }

        if (filter_var($pr->remark, FILTER_VALIDATE_URL)) {
            return '<span class="remark-cell" data-id="' .
                $pr->id .
                '" data-value="' .
                htmlspecialchars($pr->remark) .
                '" style="cursor: pointer;">
                    <a href="' .
                $pr->remark .
                '" target="_blank" rel="noopener noreferrer">' .
                \Illuminate\Support\Str::limit($pr->remark, 30) .
                '</a>
                </span>';
        }

        return '<span class="remark-cell" data-id="' .
            $pr->id .
            '" data-value="' .
            htmlspecialchars($pr->remark) .
            '" style="cursor: pointer;">
                ' .
            \Illuminate\Support\Str::limit($pr->remark, 30) .
            '
            </span>';
    }

    private function formatQtyToBuyEditable($pr)
    {
        return '<input type="number" class="form-control form-control-sm qty-to-buy-input"
            data-id="' .
            $pr->id .
            '"
            value="' .
            ($pr->qty_to_buy ?? $pr->required_quantity) .
            '"
            min="0"
            step="0.01"
            style="width: 100px;">';
    }

    private function formatSupplierSelect($pr)
    {
        $canEdit = in_array(auth()->user()->role, ['super_admin', 'admin_procurement', 'admin_logistic', 'admin_finance', 'admin']);

        if (!$canEdit) {
            return $pr->supplier ? $pr->supplier->name : '-';
        }

        $options = '<option value="">-</option>';
        foreach (\App\Models\Procurement\Supplier::orderBy('name')->get() as $supplier) {
            $selected = $pr->supplier_id == $supplier->id ? 'selected' : '';
            $options .= '<option value="' . $supplier->id . '" ' . $selected . '>' . $supplier->name . '</option>';
        }

        return '<select class="form-select form-select-sm supplier-select" data-id="' . $pr->id . '">' . $options . '</select>';
    }

    private function formatPriceInput($pr)
    {
        $canEdit = in_array(auth()->user()->role, ['super_admin', 'admin_procurement', 'admin_logistic', 'admin_finance', 'admin']);

        if (!$canEdit) {
            return $pr->price_per_unit ? number_format($pr->price_per_unit, 2) : '-';
        }

        return '<input type="number" class="form-control form-control-sm price-input"
                data-id="' .
            $pr->id .
            '"
                value="' .
            ($pr->price_per_unit ?? '') .
            '"
                min="0"
                step="0.01"
                style="width: 120px;">';
    }

    private function formatCurrencySelect($pr)
    {
        $canEdit = in_array(auth()->user()->role, ['super_admin', 'admin_procurement', 'admin_logistic', 'admin_finance', 'admin']);

        if (!$canEdit) {
            return $pr->currency ? $pr->currency->name : '-';
        }

        $options = '<option value="">-</option>';
        foreach (\App\Models\Finance\Currency::orderBy('name')->get() as $currency) {
            $selected = $pr->currency_id == $currency->id ? 'selected' : '';
            $options .= '<option value="' . $currency->id . '" ' . $selected . '>' . $currency->name . '</option>';
        }

        return '<select class="form-select form-select-sm currency-select" data-id="' . $pr->id . '">' . $options . '</select>';
    }

    private function formatApprovalSelect($pr)
    {
        $canEdit = in_array(auth()->user()->role, ['super_admin', 'admin_procurement', 'admin_logistic', 'admin_finance', 'admin']);

        if (!$canEdit) {
            return '<span class="badge ' .
                match ($pr->approval_status) {
                    'Approved' => 'bg-success',
                    'Decline' => 'bg-danger',
                    default => 'bg-warning',
                } .
                '">' .
                ucfirst($pr->approval_status ?? 'Pending') .
                '</span>';
        }

        $options = '<option value="">Pending</option>';
        $options .= '<option value="Approved" ' . ($pr->approval_status == 'Approved' ? 'selected' : '') . '>Approved</option>';
        $options .= '<option value="Decline" ' . ($pr->approval_status == 'Decline' ? 'selected' : '') . '>Decline</option>';

        return '<select class="form-select form-select-sm approval-select" data-id="' . $pr->id . '">' . $options . '</select>';
    }

    private function formatDeliveryDateInput($pr)
    {
        $canEdit = in_array(auth()->user()->role, ['super_admin', 'admin_procurement']);

        if (!$canEdit) {
            return $pr->delivery_date ? $pr->delivery_date->format('d M Y') : '-';
        }

        return '<input type="date" class="form-control form-control-sm delivery-date-input"
                data-id="' .
            $pr->id .
            '"
                value="' .
            ($pr->delivery_date ? $pr->delivery_date->format('Y-m-d') : '') .
            '"
                style="width: 150px;">';
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
                // Prepare data
                $data = [
                    'type' => $requestData['type'],
                    'material_name' => $requestData['type'] === 'restock' ? Inventory::find($requestData['inventory_id'])->name : $requestData['material_name'],
                    'inventory_id' => $requestData['type'] === 'restock' ? $requestData['inventory_id'] : null,
                    'required_quantity' => $requestData['required_quantity'],
                    'qty_to_buy' => $requestData['required_quantity'], // ← AUTO-FILL dengan required_quantity
                    'unit' => $requestData['unit'],
                    'stock_level' => $requestData['stock_level'] ?? null,
                    'project_id' => $requestData['project_id'] ?? null,
                    'remark' => $requestData['remark'] ?? null,
                    'requested_by' => Auth::id(),
                    'approval_status' => 'Pending',
                ];

                // Handle image upload
                if (isset($requestData['img']) && $requestData['img']) {
                    $data['img'] = $requestData['img']->store('purchase_requests', 'public');
                }

                PurchaseRequest::create($data);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = 'Row ' . ($key + 1) . ': ' . $e->getMessage();
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
            'remark' => 'nullable|string',
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

        // Jika required_quantity berubah dan qty_to_buy tidak diset manual, ikuti required_quantity
        if ($request->filled('required_quantity')) {
            if (!$request->filled('qty_to_buy') || $data['qty_to_buy'] === null) {
                $data['qty_to_buy'] = $request->required_quantity;
            }
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
            'remark' => 'nullable|string',
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
