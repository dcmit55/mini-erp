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
use Illuminate\Support\Facades\DB;
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
        try {
            // ✅ COMPLETE: Eager load SEMUA relations yang dibutuhkan
            $query = PurchaseRequest::with([
                'inventory',
                'project',
                'user', // ✅ CRITICAL: Load user untuk requested_by
                'supplier',
                'originalSupplier', // Include original supplier untuk change tracking
                'currency',

                // ✅ NESTED: Nested eager load untuk shipping status
                'preShipping' => function ($q) {
                    $q->with([
                        'shippingDetail' => function ($q2) {
                            $q2->with([
                                'shipping' => function ($q3) {
                                    $q3->with('goodsReceive'); // Load goodsReceive untuk hasBeenReceived()
                                },
                            ]);
                        },
                    ]);
                },

                // ✅ Load shortage items untuk detect unresolved issues
                'shortageItems' => function ($q) {
                    $q->resolvable(); // Only pending/partially_reshipped
                },
            ])->whereHas('user'); // Prevent NULL user errors

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
                $query->where('approval_status', $request->approval_filter);
            }

            // Custom search functionality
            if ($request->filled('custom_search')) {
                $searchValue = $request->input('custom_search');
                $query->where(function ($q) use ($searchValue) {
                    $q->where('material_name', 'LIKE', '%' . $searchValue . '%')
                        ->orWhere('remark', 'LIKE', '%' . $searchValue . '%')
                        ->orWhereHas('supplier', function ($sq) use ($searchValue) {
                            $sq->where('name', 'LIKE', '%' . $searchValue . '%');
                        });
                });
            }

            // DataTables search (optional - jika tidak pakai custom_search)
            if ($request->filled('search.value')) {
                $searchValue = $request->input('search.value');
                $query->where(function ($q) use ($searchValue) {
                    $q->where('material_name', 'LIKE', '%' . $searchValue . '%')
                        ->orWhere('type', 'LIKE', '%' . $searchValue . '%')
                        ->orWhere('remark', 'LIKE', '%' . $searchValue . '%');
                });
            }

            // Sorting
            $columns = ['id', 'type', 'material_name', 'required_quantity', 'qty_to_buy', 'project_id', 'approval_status', 'created_at'];

            if ($request->filled('order')) {
                $orderColumnIndex = $request->input('order.0.column');
                $orderDirection = $request->input('order.0.dir', 'asc');

                if (isset($columns[$orderColumnIndex])) {
                    $query->orderBy($columns[$orderColumnIndex], $orderDirection);
                } else {
                    $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Get total and filtered counts
            $totalRecords = PurchaseRequest::whereHas('user')->count();
            $filteredRecords = $query->count();

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 15);

            // ✅ EXECUTE QUERY DENGAN EAGER LOADING
            $purchaseRequests = $query->skip($start)->take($length)->get();

            // Check if user can view unit price
            $canViewUnitPrice = in_array(auth()->user()->role, ['super_admin', 'admin', 'admin_procurement', 'admin_logistic', 'admin_finance']);

            // Format data for DataTables
            $data = [];
            foreach ($purchaseRequests as $index => $pr) {
                // ✅ SAFETY CHECK: Skip jika user NULL
                if (!$pr->user) {
                    \Log::warning('Purchase Request with missing user', ['id' => $pr->id]);
                    continue;
                }

                try {
                    // ✅ Shipping status SUDAH DI-LOAD, no additional query
                    $shippingStatus = $this->getShippingStatusSafe($pr);

                    $data[] = [
                        'DT_RowIndex' => $start + $index + 1,
                        'DT_RowId' => 'row-' . $pr->id,
                        'DT_status' => $shippingStatus,
                        'id' => $pr->id,
                        'type' => ucfirst(str_replace('_', ' ', $pr->type)),

                        // ✅ Material name dengan stock info (no extra query)
                        'material_name' => $this->formatMaterialNameEditable($pr),

                        // ✅ Quantities (no extra query)
                        'required_quantity' => $this->formatQuantity($pr->required_quantity, $pr->unit),
                        'qty_to_buy' => $this->formatQtyToBuyEditable($pr),

                        // ✅ Project name (already loaded)
                        'project' => $pr->project ? $pr->project->name : '-',

                        // ✅ Supplier select (no extra query)
                        'supplier' => $this->formatSupplierSelect($pr),

                        // ✅ RESTORED: Price & Currency (conditional based on role)
                        'price_per_unit' => $canViewUnitPrice ? $this->formatPriceInput($pr) : '<span class="text-muted">Hidden</span>',
                        'currency' => $canViewUnitPrice ? $this->formatCurrencySelect($pr) : '<span class="text-muted">-</span>',

                        // ✅ Approval status & delivery date
                        'approval_status' => $this->formatApprovalSelect($pr),
                        'delivery_date' => $this->formatDeliveryDateInput($pr),

                        // ✅ RESTORED: Requested By (from eager loaded user)
                        'requested_by' => $pr->user->username ?? 'Unknown',

                        // ✅ RESTORED: Requested At (formatted date)
                        'requested_at' => $pr->created_at ? $pr->created_at->format('Y-m-d H:i') : '-',

                        // ✅ Remark with URL handling
                        'remark' => $this->formatRemarkEditable($pr),

                        // ✅ Actions (conditional based on permissions)
                        'actions' => $this->getActionButtons($pr, $canViewUnitPrice),

                        // ✅ Shortage indicator
                        'has_shortage' => $pr->hasUnresolvedShortage(),
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error formatting PR row', [
                        'pr_id' => $pr->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            // Enhanced error logging
            \Log::error('DataTables Error in PurchaseRequestController', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'draw' => intval($request->input('draw', 0)),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Server error occurred. Please check logs.',
                ],
                500,
            );
        }
    }

    /**
     * Add safe version of getShippingStatus
     */
    private function getShippingStatusSafe($pr)
    {
        try {
            return $pr->getShippingStatus();
        } catch (\Exception $e) {
            \Log::warning('Error getting shipping status', [
                'purchase_request_id' => $pr->id,
                'error' => $e->getMessage(),
            ]);
            return 'not_in_pre_shipping'; // Default fallback
        }
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
            $canDelete = !$pr->hasBeenReceived();
            $deleteTitle = $pr->getShippingStatus() === 'received' ? 'Cannot delete: Already received' : ($pr->hasBeenShipped() ? 'Cannot delete: In shipping' : 'Delete');

            $buttons .=
                '<button type="button"
                        class="btn btn-danger btn-sm btn-delete"
                        data-id="' .
                $pr->id .
                '"
                        data-name="' .
                $pr->material_name .
                '"
                        ' .
                (!$canDelete ? 'disabled title="' . $deleteTitle . '"' : '') .
                '
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom">
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
            // Read-only: tampilkan nama supplier tanpa indicator
            return $pr->supplier ? $pr->supplier->name : '-';
        }

        // Build options untuk Select2
        $options = '<option value="">-</option>';
        foreach (\App\Models\Procurement\Supplier::orderBy('name')->get() as $supplier) {
            $selected = $pr->supplier_id == $supplier->id ? 'selected' : '';
            $options .= '<option value="' . $supplier->id . '" ' . $selected . '>' . $supplier->name . '</option>';
        }

        // Data attributes untuk original supplier (hidden, untuk JS tracking)
        $dataAttrs = '';
        if ($pr->original_supplier_id) {
            $dataAttrs = 'data-original-supplier-id="' . $pr->original_supplier_id . '" ' . 'data-original-supplier-name="' . ($pr->originalSupplier ? htmlspecialchars($pr->originalSupplier->name) : '') . '"';
        }

        // Return select tanpa visual indicator
        // Indicator sudah di-handle via modal dan audit log
        return '<select class="form-select form-select-sm supplier-select"
                    data-id="' .
            $pr->id .
            '" ' .
            $dataAttrs .
            '>' .
            $options .
            '</select>';
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

        // Validasi dasar struktur request
        $request->validate([
            'requests' => 'required|array|min:1',
            'requests.*.type' => 'required|in:new_material,restock',
            'requests.*.material_name' => 'nullable|string|max:255',
            'requests.*.inventory_id' => 'nullable|exists:inventories,id',
            'requests.*.required_quantity' => 'required|numeric|min:0.01',
            'requests.*.unit' => 'required|string|max:50',
            'requests.*.stock_level' => 'nullable|numeric|min:0',
            'requests.*.project_id' => 'nullable|exists:projects,id',
            'requests.*.remark' => 'nullable|string',
            'requests.*.img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Validasi custom per item SEBELUM submit
        $errors = [];
        $allRequestsData = [];
        $hasValidationError = false;

        foreach ($request->requests as $key => $requestData) {
            $itemErrors = [];

            // Validasi: Material name harus ada untuk new_material
            if ($requestData['type'] === 'new_material') {
                if (empty($requestData['material_name'])) {
                    $itemErrors['material_name'] = 'Material name is required for new material type.';
                    $hasValidationError = true;
                } else {
                    // Cek duplikasi nama material
                    $exists = Inventory::whereRaw('LOWER(name) = ?', [strtolower($requestData['material_name'])])->exists();
                    if ($exists) {
                        $itemErrors['material_name'] = 'Material already exists in inventory. Please use restock type.';
                        $hasValidationError = true;
                    }
                }
            }

            // Validasi: Inventory harus ada untuk restock
            if ($requestData['type'] === 'restock') {
                if (empty($requestData['inventory_id'])) {
                    $itemErrors['inventory_id'] = 'Please select an inventory item for restock type.';
                    $hasValidationError = true;
                }
            }

            // Validasi: Required quantity harus > 0
            if (empty($requestData['required_quantity']) || $requestData['required_quantity'] <= 0) {
                $itemErrors['required_quantity'] = 'Quantity must be greater than 0.';
                $hasValidationError = true;
            }

            // Validasi: Unit harus dipilih
            if (empty($requestData['unit'])) {
                $itemErrors['unit'] = 'Unit is required.';
                $hasValidationError = true;
            }

            // JIKA ada error pada item ini, tambahkan ke array errors
            if (!empty($itemErrors)) {
                foreach ($itemErrors as $field => $message) {
                    $errors["requests.{$key}.{$field}"] = $message;
                }
            } else {
                $allRequestsData[] = $requestData;
            }
        }

        // Jika ada error apapun, JANGAN process sama sekali
        if (!empty($errors)) {
            // Simpan data form ke session untuk restore
            session(['form_requests_data' => $request->requests]);

            return redirect()->back()->withErrors($errors)->withInput($request->all())->with('error', 'Please fix the validation errors before submitting.');
        }

        // Semua validasi passed, baru process penyimpanan
        $successCount = 0;
        $processErrors = [];

        foreach ($allRequestsData as $key => $requestData) {
            try {
                // Auto-fill supplier dari inventory untuk restock
                $supplierData = [];
                if ($requestData['type'] === 'restock' && !empty($requestData['inventory_id'])) {
                    $inventory = Inventory::find($requestData['inventory_id']);

                    // Set both supplier_id AND original_supplier_id untuk restock
                    $supplierData['supplier_id'] = $inventory->supplier_id;
                    $supplierData['original_supplier_id'] = $inventory->supplier_id;
                }

                $data = [
                    'type' => $requestData['type'],
                    'material_name' => $requestData['type'] === 'restock' ? Inventory::find($requestData['inventory_id'])->name : $requestData['material_name'],
                    'inventory_id' => $requestData['type'] === 'restock' ? $requestData['inventory_id'] : null,
                    'required_quantity' => $requestData['required_quantity'],
                    'qty_to_buy' => $requestData['required_quantity'],
                    'unit' => $requestData['unit'],
                    'stock_level' => $requestData['stock_level'] ?? null,
                    'project_id' => $requestData['project_id'] ?? null,
                    'remark' => $requestData['remark'] ?? null,
                    'requested_by' => Auth::id(),
                    'approval_status' => 'Pending',
                    // Merge supplier data (includes original_supplier_id for restock)
                    ...$supplierData ?: [
                        'supplier_id' => null,
                        'original_supplier_id' => null, // Explicitly set NULL for new_material
                    ],
                ];

                // Handle image upload
                if (isset($requestData['img']) && $requestData['img']) {
                    $data['img'] = $requestData['img']->store('purchase_requests', 'public');
                }

                PurchaseRequest::create($data);
                $successCount++;
            } catch (\Exception $e) {
                $processErrors[] = 'Error processing item ' . ($key + 1) . ': ' . $e->getMessage();
            }
        }

        // Clear session data setelah berhasil
        session()->forget('form_requests_data');

        // Redirect dengan message
        if ($successCount > 0) {
            $message = "<strong>Success!</strong> {$successCount} purchase request(s) created successfully!";
            if (!empty($processErrors)) {
                $message .= '<br><strong>Warnings:</strong><ul><li>' . implode('</li><li>', $processErrors) . '</li></ul>';
            }

            return redirect()->route('purchase_requests.index')->with('success', $message);
        } else {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->with('error', 'No requests were processed. Errors: ' . implode(', ', $processErrors));
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
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to modify purchase requests.',
                ],
                403,
            );
        }

        $request->validate([
            'material_name' => 'nullable|string|max:255',
            'qty_to_buy' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_change_reason' => 'nullable|string|max:500',
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

        // Track supplier change
        if ($request->filled('supplier_id')) {
            $newSupplierId = $request->supplier_id;
            $currentSupplierId = $purchaseRequest->supplier_id;
            $originalSupplierId = $purchaseRequest->original_supplier_id;

            // CASE 1: First time setting supplier (original_supplier_id is NULL)
            if ($originalSupplierId === null) {
                // Set both supplier_id AND original_supplier_id
                $data['supplier_id'] = $newSupplierId;
                $data['original_supplier_id'] = $newSupplierId;

                \Log::info('Supplier set for the first time', [
                    'purchase_request_id' => $purchaseRequest->id,
                    'supplier_id' => $newSupplierId,
                    'type' => $purchaseRequest->type,
                ]);
            }
            // CASE 2: Supplier change from existing original supplier
            elseif ($newSupplierId != $originalSupplierId) {
                $data['supplier_id'] = $newSupplierId;
                $data['supplier_change_reason'] = $request->supplier_change_reason ?? 'Changed by admin';

                \Log::info('Supplier changed from original', [
                    'purchase_request_id' => $purchaseRequest->id,
                    'old_supplier_id' => $originalSupplierId,
                    'new_supplier_id' => $newSupplierId,
                    'reason' => $data['supplier_change_reason'],
                    'changed_by' => Auth::id(),
                ]);
            }
            // CASE 3: Supplier restored to original (no change tracking needed)
            else {
                $data['supplier_id'] = $newSupplierId;
                // Clear change reason if restored to original
                $data['supplier_change_reason'] = null;

                \Log::info('Supplier restored to original', [
                    'purchase_request_id' => $purchaseRequest->id,
                    'supplier_id' => $newSupplierId,
                ]);
            }
        }

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

        try {
            $purchaseRequest = PurchaseRequest::findOrFail($id);
            $name = $purchaseRequest->material_name;

            // CEK 1: Apakah sudah ada PreShipping?
            $preShipping = $purchaseRequest->preShipping;

            if ($preShipping) {
                // CEK 2: Apakah PreShipping sudah masuk Shipping?
                $shippingDetail = $preShipping->shippingDetail;

                if ($shippingDetail) {
                    // CEK 3: Apakah Shipping sudah punya GoodsReceive?
                    $shipping = \App\Models\Procurement\Shipping::find($shippingDetail->shipping_id);

                    if (!$shipping) {
                        // Shipping sudah dihapus, hapus juga ShippingDetail & PreShipping
                        $shippingDetail->delete();
                        $preShipping->delete();
                    } else {
                        $goodsReceive = \App\Models\Procurement\GoodsReceive::where('shipping_id', $shipping->id)->first();

                        if ($goodsReceive) {
                            $message = "Cannot delete <b>{$name}</b>. This purchase request has already been received on {$goodsReceive->arrived_date}. Contact admin to reverse goods receive first.";

                            if (request()->ajax()) {
                                return response()->json(['success' => false, 'message' => $message], 403);
                            }

                            return redirect()->route('purchase_requests.index')->with('error', $message);
                        }

                        // Data sudah shipping tapi belum receive
                        $message = "Cannot delete <b>{$name}</b>. This purchase request is part of Shipping #{$shipping->id}. Please cancel the shipping first or contact admin.";

                        if (request()->ajax()) {
                            return response()->json(['success' => false, 'message' => $message], 403);
                        }

                        return redirect()->route('purchase_requests.index')->with('error', $message);
                    }
                }

                // PreShipping ada tapi belum shipping - hapus keduanya dengan transaction
                \DB::beginTransaction();
                try {
                    $preShipping->delete();
                    $purchaseRequest->delete();
                    \DB::commit();

                    $message = "Purchase request <b>{$name}</b> deleted successfully. (Pre-shipping group was also removed)";

                    if (request()->ajax()) {
                        return response()->json(['success' => true, 'message' => $message]);
                    }

                    return redirect()->route('purchase_requests.index')->with('success', $message);
                } catch (\Exception $e) {
                    \DB::rollBack();
                    throw $e;
                }
            }

            // PreShipping belum ada - direct delete
            $purchaseRequest->delete();

            $message = "Purchase request <b>{$name}</b> deleted successfully!";

            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('purchase_requests.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error deleting purchase request', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = 'Error deleting purchase request: ' . $e->getMessage();

            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->route('purchase_requests.index')->with('error', $message);
        }
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
