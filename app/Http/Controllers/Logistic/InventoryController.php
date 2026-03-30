<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\Unit;
use App\Models\Admin\User;
use App\Models\Production\Project;
use App\Models\Logistic\Category;
use App\Models\Finance\Currency;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\LocationSupplier;
use App\Models\Logistic\Location;
use Illuminate\Http\Request;
use App\Exports\InventoryExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\ImportInventoryTemplate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\Lark\LarkInventorySyncService;

class InventoryController extends Controller
{
    /**
     * Generate a friendly color for category badge based on category name
     */
    private function getCategoryBadgeColor($categoryName)
    {
        if (!$categoryName) {
            return 'bg-secondary';
        }

        // Predefined friendly colors yang bagus untuk badge
        $colors = [
            'bg-primary', // Blue
            'bg-success', // Green
            'bg-info', // Light Blue
            'bg-warning', // Yellow
            'bg-danger', // Red
            'bg-dark', // Dark
            'bg-secondary', // Gray
        ];

        // Tambahan custom colors dengan CSS classes
        $customColors = ['bg-purple', 'bg-indigo', 'bg-pink', 'bg-orange', 'bg-teal', 'bg-cyan', 'bg-lime', 'bg-amber', 'bg-rose', 'bg-emerald', 'bg-violet', 'bg-sky'];

        $allColors = array_merge($colors, $customColors);

        // Generate hash dari nama category untuk konsistensi
        $hash = crc32(strtolower(trim($categoryName)));
        $colorIndex = abs($hash) % count($allColors);

        return $allColors[$colorIndex];
    }

    public function __construct()
    {
        $this->middleware('auth');
        // Batasi create/edit/delete HANYA untuk super_admin & admin_logistic
        $this->middleware(function ($request, $next) {
            $restrictedRoles = ['super_admin', 'admin_logistic', 'admin_finance', 'admin_procurement', 'admin'];
            $restrictedRoutes = ['inventory.create', 'inventory.import', 'inventory.edit', 'inventory.destroy', 'inventory.store', 'inventory.update'];

            if (in_array($request->route()->getName(), $restrictedRoutes) && !in_array(Auth::user()->role, $restrictedRoles)) {
                abort(403, 'You do not have permission to modify inventory data.');
            }
            return $next($request);
        })->only(['create', 'store', 'import', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        // Jika request AJAX, return data untuk DataTables
        if ($request->ajax()) {
            return $this->getDataTablesData($request);
        }

        // Untuk non-AJAX request, return view dengan data master
        $categories = Category::orderBy('name')->get();
        $currencies = Currency::orderBy('name')->get();
        $suppliers = Supplier::nonBlacklisted()->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        // Get unique project_lark values from inventory
        $projects = Inventory::whereNotNull('project_lark')->distinct()->pluck('project_lark')->sort()->values();

        return view('logistic.inventory.index', compact('categories', 'currencies', 'suppliers', 'locations', 'projects'));
    }

    /**
     * Return total stock value in IDR, optionally filtered by category.
     * Used by the stock value widget on the inventory index page.
     */
    public function stockValue(Request $request)
    {
        $query = DB::table('inventory_batches as ib')->join('inventories as i', 'i.id', '=', 'ib.inventory_id')->join('currencies as c', 'c.id', '=', 'ib.currency_id')->whereNull('ib.deleted_at')->whereNull('i.deleted_at')->where('ib.qty_remaining', '>', 0);

        if ($request->filled('category_id')) {
            $query->where('i.category_id', $request->category_id);
        }

        // SUM(qty_remaining * unit_price * exchange_rate)  → all in IDR
        $totalIdr = $query->sum(DB::raw('ib.qty_remaining * ib.unit_price * COALESCE(CAST(c.exchange_rate AS DECIMAL(18,4)), 1)'));

        // Breakdown by category for the "all" view
        $breakdown = DB::table('inventory_batches as ib')
            ->join('inventories as i', 'i.id', '=', 'ib.inventory_id')
            ->join('currencies as c', 'c.id', '=', 'ib.currency_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'i.category_id')
            ->whereNull('ib.deleted_at')
            ->whereNull('i.deleted_at')
            ->where('ib.qty_remaining', '>', 0)
            ->selectRaw(
                'COALESCE(cat.name, "Uncategorized") as category_name,
                         SUM(ib.qty_remaining * ib.unit_price * COALESCE(CAST(c.exchange_rate AS DECIMAL(18,4)), 1)) as total_idr',
            )
            ->groupBy('cat.name')
            ->orderByDesc('total_idr')
            ->get();

        return response()->json([
            'total_idr' => (float) $totalIdr,
            'total_idr_formatted' => 'Rp ' . number_format((float) $totalIdr, 0, ',', '.'),
            'breakdown' => $breakdown,
        ]);
    }

    public function getDataTablesData(Request $request)
    {
        $query = Inventory::query()
            ->with(['category', 'supplier', 'location', 'currency'])
            ->withComputedStock()
            ->latest();

        // Apply filters dari form filter
        if ($request->filled('category_filter')) {
            $query->where('category_id', $request->category_filter);
        }
        if ($request->filled('material_code_filter')) {
            $query->where('material_code', 'like', '%' . $request->material_code_filter . '%');
        }
        if ($request->filled('supplier_filter')) {
            $query->where('supplier_id', $request->supplier_filter);
        }
        if ($request->filled('location_filter')) {
            $query->where('location_id', $request->location_filter);
        }
        if ($request->filled('min_quantity')) {
            $query->whereHas('batches', function ($q) use ($request) {
                $q->where('qty_remaining', '>=', $request->min_quantity);
            });
        }
        if ($request->filled('max_quantity')) {
            $query->whereHas('batches', function ($q) use ($request) {
                $q->where('qty_remaining', '<=', $request->max_quantity);
            });
        }
        if ($request->filled('unitFilter')) {
            $query->where('unit', $request->unitFilter);
        }
        if ($request->filled('sourceFilter')) {
            if ($request->sourceFilter === 'lark') {
                $query->whereNotNull('lark_record_id');
            } elseif ($request->sourceFilter === 'manual') {
                $query->whereNull('lark_record_id');
            }
        }
        if ($request->filled('project_filter')) {
            $query->where('project_lark', $request->project_filter);
        }

        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('remark', 'like', "%{$searchValue}%")
                    ->orWhere('unit', 'like', "%{$searchValue}%");
            });
        }

        // Search functionality dari DataTables
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('material_code', 'like', "%{$searchValue}%")
                    ->orWhere('remark', 'like', "%{$searchValue}%")
                    ->orWhere('unit', 'like', "%{$searchValue}%")
                    ->orWhereHas('category', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('supplier', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('location', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Sorting dari DataTables
        $columns = ['id', 'name', 'category_name', 'stock', 'unit_price', 'supplier_name', 'location_name', 'remark', 'updated_at'];
        if ($request->filled('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle join sorting jika perlu (category, supplier, location)
            if ($orderColumnIndex == 2) {
                $query->leftJoin('categories', 'inventories.category_id', '=', 'categories.id')->orderBy('categories.name', $orderDirection)->select('inventories.*');
            } elseif ($orderColumnIndex == 5) {
                $query->leftJoin('suppliers', 'inventories.supplier_id', '=', 'suppliers.id')->orderBy('suppliers.name', $orderDirection)->select('inventories.*');
            } elseif ($orderColumnIndex == 6) {
                $query->leftJoin('locations', 'inventories.location_id', '=', 'locations.id')->orderBy('locations.name', $orderDirection)->select('inventories.*');
            } elseif (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            }
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        // Get total records
        $totalRecords = Inventory::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $inventories = $query->skip($start)->take($length)->get();

        // Generate QR codes untuk current page data saja
        foreach ($inventories as $inventory) {
            $qrCodePath = 'storage/qrcodes/' . $inventory->id . '.svg';
            $qrCodeFullPath = public_path($qrCodePath);

            try {
                if (!file_exists($qrCodeFullPath)) {
                    // Ensure directory exists
                    $qrCodeDir = dirname($qrCodeFullPath);
                    if (!is_dir($qrCodeDir)) {
                        mkdir($qrCodeDir, 0755, true);
                    }

                    QrCode::format('svg')
                        ->size(200)
                        ->generate(url('/inventory/detail/' . $inventory->id), $qrCodeFullPath);
                }
                $inventory->qr_code = asset($qrCodePath);
            } catch (\Exception $e) {
                \Log::warning('Failed to generate QR code for inventory', [
                    'inventory_id' => $inventory->id,
                    'error' => $e->getMessage(),
                ]);
                // Fallback: use placeholder or empty
                $inventory->qr_code = null;
            }
        }

        // Format data untuk DataTables
        $data = [];
        foreach ($inventories as $index => $inventory) {
            $rowNumber = $start + $index + 1;

            // Freight Costs
            $domesticFreightValue = number_format($inventory->unit_domestic_freight_cost ?? 0, 2, ',', '.');
            $internationalFreightValue = number_format($inventory->unit_international_freight_cost ?? 0, 2, ',', '.');

            $currencyName = $inventory->currency ? $inventory->currency->name : '';

            // Generate category badge with color
            $categoryBadge = $inventory->category ? '<span class="badge ' . $this->getCategoryBadgeColor($inventory->category->name) . '">' . $inventory->category->name . '</span>' : '<span class="text-muted">-</span>';

            // Source badge (Lark vs Manual)
            $sourceBadge = '';
            if ($inventory->lark_record_id) {
                $sourceBadge = '<span class="badge bg-info"><i class="fas fa-cloud-download-alt"></i> Lark Sync</span>';
            } else {
                $sourceBadge = '<span class="badge bg-secondary"><i class="fas fa-keyboard"></i> Manual</span>';
            }

            $data[] = [
                'DT_RowId' => 'row_' . $inventory->id,
                'number' => $rowNumber,
                'material_code' => $inventory->material_code ?? '-',
                'name' => '<div class="fw-semibold">' . $inventory->name . '</div>',
                'category' => $categoryBadge,
                'stock' => '<span class="fw-semibold">' . number_format($inventory->quantity, 2) . '</span>' . ($inventory->unit ? ' <span class="text-muted">' . $inventory->unit . '</span>' : ''),
                'domestic_freight' => in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_finance', 'admin']) ? '<span class="fw-semibold text-info">' . $domesticFreightValue . '</span> ' . $currencyName : '',
                'international_freight' => in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_finance', 'admin']) ? '<span class="fw-semibold text-warning">' . $internationalFreightValue . '</span> ' . $currencyName : '',
                'supplier' => $inventory->supplier ? $inventory->supplier->name : '-',
                'location' => $inventory->location ? $inventory->location->name : '-',
                'project_lark' => $inventory->project_lark ?? '<span class="text-muted">-</span>',
                'source' => $sourceBadge,
                'remark' => '<div class="text-truncate" style="max-width: 250px;" title="' . strip_tags($inventory->remark ?? '-') . '">' . ($inventory->remark ?? '-') . '</div>',
                'updated_at' => [
                    'display' => $inventory->updated_at ? \Carbon\Carbon::parse($inventory->updated_at)->format('d M Y') : '-',
                    'tooltip' => $inventory->updated_at ? \Carbon\Carbon::parse($inventory->updated_at)->format('H:i') : '',
                    'timestamp' => $inventory->updated_at ? $inventory->updated_at->format('Y-m-d H:i:s') : '',
                ],
                'actions' => $this->getActionButtons($inventory),
                'img' => $inventory->img,
                'qr_code' => $inventory->qr_code,
            ];
        }
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function getActionButtons($inventory)
    {
        $buttons = '<div class="d-flex flex-nowrap gap-1">';

        // Detail button
        $buttons .=
            '<a href="' .
            route('inventory.detail', ['id' => $inventory->id]) .
            '"
                        class="btn btn-sm btn-success" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" title="More Detail">
                        <i class="bi bi-info-circle"></i>
                     </a>';

        // Image & QR button
        $buttons .=
            '<button type="button" class="btn btn-sm btn-secondary btn-show-image"
                        title="View Image & QR Code" data-bs-toggle="modal" data-bs-target="#imageModal"
                        data-img="' .
            ($inventory->img ? asset('storage/' . $inventory->img) : '') .
            '"
                        data-qrcode="' .
            $inventory->qr_code .
            '"
                        data-name="' .
            $inventory->name .
            '">
                        <i class="bi bi-file-earmark-image"></i>
                     </button>';

        // Edit & Delete buttons (hanya untuk admin)
        if (in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_procurement', 'admin_finance', 'admin'])) {
            $buttons .=
                '<a href="' .
                route('inventory.edit', $inventory->id) .
                '"
                            class="btn btn-warning btn-sm" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                         </a>';

            $buttons .=
                '<button type="button" class="btn btn-sm btn-danger btn-delete"
                            data-id="' .
                $inventory->id .
                '" data-name="' .
                $inventory->name .
                '"
                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete">
                            <i class="bi bi-trash3"></i>
                         </button>';
        }

        $buttons .= '</div>';
        return $buttons;
    }

    public function export(Request $request)
    {
        // Filter data berdasarkan request (sama seperti di getDataTablesData)
        $query = Inventory::query()->with(['category', 'supplier', 'location', 'currency']);

        // Apply filters yang sama dengan DataTables
        if ($request->filled('category_filter')) {
            $query->where('category_id', $request->category_filter);
        }
        if ($request->filled('material_code_filter')) {
            $query->where('material_code', 'like', '%' . $request->material_code_filter . '%');
        }
        if ($request->filled('supplier_filter')) {
            $query->where('supplier_id', $request->supplier_filter);
        }
        if ($request->filled('location_filter')) {
            $query->where('location_id', $request->location_filter);
        }
        if ($request->filled('min_quantity')) {
            $query->whereHas('batches', function ($q) use ($request) {
                $q->where('qty_remaining', '>=', $request->min_quantity);
            });
        }
        if ($request->filled('max_quantity')) {
            $query->whereHas('batches', function ($q) use ($request) {
                $q->where('qty_remaining', '<=', $request->max_quantity);
            });
        }
        if ($request->filled('unitFilter')) {
            $query->where('unit', $request->unitFilter);
        }
        if ($request->filled('project_filter')) {
            $query->where('project_lark', $request->project_filter);
        }
        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('material_code', 'like', "%{$searchValue}%")
                    ->orWhere('remark', 'like', "%{$searchValue}%")
                    ->orWhere('unit', 'like', "%{$searchValue}%");
            });
        }

        $inventories = $query->get();

        // Generate dynamic filename berdasarkan filter aktif
        $fileName = 'inventory';
        $filterParts = [];

        if ($request->filled('category_filter')) {
            $categoryName = Category::find($request->category_filter)->name ?? 'Unknown';
            $filterParts[] = 'category-' . str_replace(' ', '-', strtolower($categoryName));
        }
        if ($request->filled('currency_filter')) {
            $currencyName = Currency::find($request->currency_filter)->name ?? 'Unknown';
            $filterParts[] = 'currency-' . str_replace(' ', '-', strtolower($currencyName));
        }
        if ($request->filled('supplier_filter')) {
            $supplierName = Supplier::find($request->supplier_filter)->name ?? 'Unknown';
            $filterParts[] = 'supplier-' . str_replace(' ', '-', strtolower($supplierName));
        }
        if ($request->filled('location_filter')) {
            $locationName = Location::find($request->location_filter)->name ?? 'Unknown';
            $filterParts[] = 'location-' . str_replace(' ', '-', strtolower($locationName));
        }
        if ($request->filled('max_quantity')) {
            $filterParts[] = 'max-qty-' . $request->max_quantity;
        }
        if ($request->filled('custom_search')) {
            $filterParts[] = 'search-' . str_replace(' ', '-', strtolower(substr($request->custom_search, 0, 10)));
        }

        if (!empty($filterParts)) {
            $fileName .= '_' . implode('_', $filterParts);
        }

        $fileName .= '_' . Carbon::now()->format('Y-m-d') . '.xlsx';

        // Update InventoryExport untuk menerima role info
        $showCurrencyAndPrice = in_array(auth()->user()->role, ['super_admin', 'admin_logistic', 'admin_finance', 'admin']);

        return Excel::download(new InventoryExport($inventories, $showCurrencyAndPrice), $fileName);
    }

    public function create()
    {
        $currencies = Currency::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::nonBlacklisted()->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $supplierLocations = LocationSupplier::orderBy('name')->get();
        $projects = Project::notArchived()->orderBy('name')->get();

        return view('logistic.inventory.create', compact('currencies', 'units', 'categories', 'suppliers', 'locations', 'supplierLocations', 'projects'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'admin') {
            return redirect()->back()->with('error', 'You do not have permission to submit inventory data.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:inventories,name,NULL,id,deleted_at,NULL',
            'category_id' => 'required|exists:categories,id',
            'project_id' => 'nullable|exists:projects,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'new_unit' => 'required_if:unit,__new__|nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit_domestic_freight_cost' => 'nullable|numeric|min:0',
            'unit_international_freight_cost' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'location_id' => 'nullable|exists:locations,id',
            'remark' => 'nullable|string',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $inventory = new Inventory();
        $inventory->name = $request->name;
        $inventory->category_id = $request->category_id;
        $inventory->project_id = $request->project_id;
        $inventory->unit = $request->unit;
        $inventory->unit_domestic_freight_cost = $request->unit_domestic_freight_cost;
        $inventory->unit_international_freight_cost = $request->unit_international_freight_cost;
        $inventory->supplier_id = $request->supplier_id;
        $inventory->currency_id = $request->currency_id;
        $inventory->location_id = $request->location_id;
        $inventory->remark = $request->remark;

        // Simpan unit baru jika ada
        if ($request->unit === '__new__' && $request->new_unit) {
            $unit = Unit::firstOrCreate(['name' => $request->new_unit]);
            $inventory->unit = $unit->name;
        }

        // Upload Image if exists
        if ($request->hasFile('img')) {
            $imagePath = $request->file('img')->store('inventory_images', 'public');
            if ($imagePath) {
                $inventory->img = $imagePath;
            }
        }

        $inventory->save();

        // Create initial batch for the opening stock
        if ($request->quantity > 0) {
            \App\Models\Logistic\InventoryBatch::create([
                'batch_number' => \App\Models\Logistic\InventoryBatch::generateBatchNumber($inventory->id),
                'inventory_id' => $inventory->id,
                'qty' => $request->quantity,
                'qty_remaining' => $request->quantity,
                'unit_price' => $request->price ?? 0,
                'currency_id' => $request->currency_id ?? null,
                'received_date' => now()->toDateString(),
                'source_type' => \App\Models\Logistic\InventoryBatch::SOURCE_INITIAL_STOCK,
                'source_id' => $inventory->id,
            ]);
        }

        // Warning message untuk field kosong
        $warningMessage = null;
        if (!$inventory->currency_id || !$request->price) {
            $warningMessage = "Price or Currency is empty for <b>{$inventory->name}</b>. Please update it as soon as possible, as it will affect the cost calculation!";
        }

        return redirect()
            ->route('inventory.index')
            ->with([
                'success' => "Inventory <b>{$inventory->name}</b> created successfully.",
                'warning' => $warningMessage,
            ]);
    }

    public function storeQuick(Request $request)
    {
        if (auth()->user()->role === 'admin') {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to store inventory data.',
                ],
                403,
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:inventories,name,NULL,id,deleted_at,NULL',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $validator->errors()->first(),
                    ],
                    422,
                );
            }
            return back()->withErrors($validator)->withInput();
        }

        $unit = Unit::firstOrCreate(['name' => $request->unit]);

        $material = Inventory::create([
            'name' => $request->name,
            'unit' => $unit->name,
            'remark' => $request->remark ? $request->remark . ' <span style="color: orange;">(From Quick Add)</span>' : '<span style="color: orange;">(From Quick Add)</span>',
        ]);

        // Create initial batch
        if ((float) $request->quantity > 0) {
            \App\Models\Logistic\InventoryBatch::create([
                'batch_number' => \App\Models\Logistic\InventoryBatch::generateBatchNumber($material->id),
                'inventory_id' => $material->id,
                'qty' => $request->quantity,
                'qty_remaining' => $request->quantity,
                'unit_price' => $request->price ?? 0,
                'currency_id' => $request->currency_id ?? null,
                'received_date' => now()->toDateString(),
                'source_type' => \App\Models\Logistic\InventoryBatch::SOURCE_INITIAL_STOCK,
                'source_id' => $material->id,
            ]);
        }

        return response()->json(['success' => true, 'material' => $material]);
    }

    public function json(Request $request)
    {
        // return Inventory::select('id', 'name')->get();
        // Mengembalikan data inventory dalam format JSON
        $q = $request->q;
        $query = Inventory::select('id', 'name');
        if ($q) {
            // Escape karakter khusus untuk LIKE query
            $escapedQ = addcslashes($q, '%_\\');
            $query->where('name', 'like', '%' . $escapedQ . '%');
        }
        return response()->json($query->get());
        // bisa juga pakai paginate/dataTables untuk ribuan data
    }

    public function edit($id)
    {
        $inventory = Inventory::findOrFail($id);
        $currencies = Currency::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::nonBlacklisted()->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $supplierLocations = LocationSupplier::orderBy('name')->get();
        $projects = Project::notArchived()->orderBy('name')->get();

        return view('logistic.inventory.edit', compact('inventory', 'currencies', 'units', 'categories', 'suppliers', 'locations', 'supplierLocations', 'projects'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        if (auth()->user()->role === 'admin') {
            return redirect()->back()->with('error', 'You do not have permission to edit inventory data.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventories,name,' . $inventory->id . ',id,deleted_at,NULL',
            'category_id' => 'required|exists:categories,id',
            'project_id' => 'nullable|exists:projects,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'unit_id' => 'nullable|exists:units,id',
            'new_unit' => 'required_if:unit,__new__|nullable|string|max:255',
            'currency_id' => 'nullable|exists:currencies,id',
            'price' => 'nullable|numeric|min:0',
            'unit_domestic_freight_cost' => 'nullable|numeric|min:0',
            'unit_international_freight_cost' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'location_id' => 'nullable|exists:locations,id',
            'remark' => 'nullable|string',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Update data inventory
        $inventory->name = $request->name;
        $inventory->category_id = $request->category_id;
        $inventory->project_id = $request->project_id;
        $inventory->unit = $request->unit;
        $inventory->currency_id = $request->currency_id;
        $inventory->unit_domestic_freight_cost = $request->unit_domestic_freight_cost;
        $inventory->unit_international_freight_cost = $request->unit_international_freight_cost;
        $inventory->supplier_id = $request->supplier_id;
        $inventory->location_id = $request->location_id;
        $inventory->remark = $request->remark;

        // Always create a new INIT batch on each stock update to preserve full history.
        // This means each manual edit of qty/price becomes a traceable batch record.
        $newQty = (float) $request->quantity;
        $newPrice = (float) ($request->price ?? 0);

        // Get the last INIT batch price for comparison (detect actual price change)
        $currentPrice = (float) ($inventory->batches()
            ->where('source_type', \App\Models\Logistic\InventoryBatch::SOURCE_INITIAL_STOCK)
            ->orderByDesc('id')->value('unit_price') ?? 0);

        // Create new INIT batch when qty > 0 or price has changed
        if ($newQty > 0 || ($newPrice > 0 && $newPrice !== $currentPrice)) {
            \App\Models\Logistic\InventoryBatch::create([
                'batch_number' => \App\Models\Logistic\InventoryBatch::generateInitBatchNumber($inventory->id),
                'inventory_id' => $inventory->id,
                'qty' => $newQty,
                'qty_remaining' => $newQty,
                'unit_price' => $newPrice,
                'currency_id' => $request->currency_id ?? null,
                'received_date' => now()->toDateString(),
                'source_type' => \App\Models\Logistic\InventoryBatch::SOURCE_INITIAL_STOCK,
                'source_id' => $inventory->id,
                'notes' => 'Stock update via Edit Inventory — ' . now()->format('d M Y H:i'),
            ]);
        }

        // Simpan unit baru jika ada
        if ($request->unit === '__new__' && $request->new_unit) {
            $unit = Unit::firstOrCreate(['name' => $request->new_unit]);
            $inventory->unit = $unit->name;
            $inventory->unit_id = $unit->id;
        } else {
            // Find unit_id dari unit name
            $unit = Unit::where('name', $request->unit)->first();
            $inventory->unit = $request->unit;
            $inventory->unit_id = $unit ? $unit->id : null;
        }

        // Upload image jika ada
        if ($request->hasFile('img')) {
            if ($inventory->img && Storage::disk('public')->exists($inventory->img)) {
                Storage::disk('public')->delete($inventory->img);
            }
            $imgPath = $request->file('img')->store('inventory_images', 'public');
            $inventory->img = $imgPath;
        }

        $inventory->save();

        $warningMessage = null;
        if (!$inventory->currency_id || !$request->price) {
            $warningMessage = "Price or Currency is empty for <b>{$inventory->name}</b>. Please update it as soon as possible, as it will affect the cost calculation!";
        }

        return redirect()
            ->route('inventory.index')
            ->with([
                'success' => "Inventory <b>{$inventory->name}</b> updated successfully.",
                'warning' => $warningMessage,
            ]);
    }

    public function import(Request $request)
    {
        if (auth()->user()->role === 'admin') {
            return redirect()->back()->with('error', 'You do not have permission to import inventory data.');
        }

        $request->validate([
            'xls_file' => 'required|mimes:xls,xlsx',
        ]);

        $data = Excel::toArray([], $request->file('xls_file'))[0];

        $errors = []; // Array untuk menyimpan kesalahan
        $warnings = []; // Array untuk menyimpan peringatan
        $successCount = 0; // Counter untuk data yang berhasil diimpor

        foreach ($data as $index => $row) {
            if ($index === 0) {
                continue;
            } // Skip header row

            // Validasi nama inventory
            $inventoryName = $row[0] ?? null;
            if (!$inventoryName) {
                $errors[] = "Row <b>{$index}</b> Error: Inventory name is required.";
                continue; // Skip jika nama inventory kosong
            }

            // Validasi category
            $categoryName = $row[1] ?? null; // Ambil category dari kolom kedua
            $category = null;
            if ($categoryName) {
                $category = Category::whereRaw('LOWER(name) = LOWER(?)', [$categoryName])->first();
                if (!$category) {
                    // Tambahkan kategori baru jika tidak ditemukan
                    $category = Category::create(['name' => $categoryName]);
                }
            }

            // Validasi unit
            $unitName = $row[3] ?? '-';
            $unit = Unit::firstOrCreate(['name' => $unitName]); // Tambahkan unit baru jika belum ada

            // Bersihkan data harga
            $price = str_replace([',', '$'], '', $row[4] ?? null);
            $price = is_numeric($price) ? $price : 0; // Jika harga kosong atau tidak valid, set ke 0

            // Validasi currency
            $currencyName = $row[5] ?? '-';
            $currency = Currency::where('name', $currencyName)->first();
            if (!$currency && $currencyName !== '-') {
                $errors[] = "Row <b>{$index}</b> Error: Invalid currency '{$currencyName}'.";
                continue; // Skip jika currency tidak valid
            }

            $supplierName = $row[6] ?? null;
            $supplier = $supplierName ? Supplier::firstOrCreate(['name' => $supplierName]) : null;

            $locationName = $row[7] ?? null;
            $location = $locationName ? Location::firstOrCreate(['name' => $locationName]) : null;

            $inventory = new Inventory();
            $inventory->name = $inventoryName;
            $inventory->category_id = $category ? $category->id : null;
            $inventory->unit = $unit->name;
            $inventory->currency_id = $currency ? $currency->id : null;
            $inventory->supplier_id = $supplier ? $supplier->id : null;
            $inventory->location_id = $location ? $location->id : null;
            $inventory->remark = $row[8] ?? null;

            // Cek jika inventory sudah ada
            $existingInventory = Inventory::where('name', $inventory->name)->first();
            if ($existingInventory) {
                $errors[] = "Row <b>{$index}</b> Error: Duplicate inventory <b>{$inventory->name}</b>.";
                continue;
            }

            $inventory->save();

            // Create initial batch for opening stock
            $importQty = is_numeric($row[2]) ? (float) $row[2] : 0;
            if ($importQty > 0) {
                \App\Models\Logistic\InventoryBatch::create([
                    'batch_number' => \App\Models\Logistic\InventoryBatch::generateBatchNumber($inventory->id),
                    'inventory_id' => $inventory->id,
                    'qty' => $importQty,
                    'qty_remaining' => $importQty,
                    'unit_price' => $price,
                    'currency_id' => $currency ? $currency->id : null,
                    'received_date' => now()->toDateString(),
                    'source_type' => \App\Models\Logistic\InventoryBatch::SOURCE_INITIAL_STOCK,
                    'source_id' => $inventory->id,
                ]);
            }

            $successCount++;

            // Tambahkan warning jika currency atau price kosong
            if (!$inventory->currency_id || !$price) {
                $warnings[] = "Price or Currency is empty for <b>{$inventory->name}</b>. Please update it as soon as possible, as it will affect the cost calculation!";
            }
        }

        // Kirim kesalahan ke session
        if (!empty($errors)) {
            session()->flash('error', implode('<br>', $errors));
        }

        // Kirim peringatan ke session
        if (!empty($warnings)) {
            session()->flash('warning', implode('<br>', $warnings));
        }

        // Kirim jumlah data yang berhasil dan gagal ke session
        $totalRows = count($data) - 1; // Total baris dikurangi header
        $failedCount = $totalRows - $successCount;

        $redirectData = [
            'success' => "<b>{$successCount}</b> rows imported successfully.",
        ];

        if ($failedCount > 0) {
            $redirectData['warning'] = "<b>{$failedCount}</b> rows failed to import.";
        }

        return redirect()->route('inventory.index')->with($redirectData);
    }

    public function downloadTemplate()
    {
        return Excel::download(new ImportInventoryTemplate(), 'inventory_template.xlsx');
    }

    public function detail($id)
    {
        $inventory = Inventory::findOrFail($id);
        $projects = Project::orderBy('name')->get();
        $users = User::with('department')->orderBy('username')->get();

        return view('logistic.inventory.detail', compact('inventory', 'projects', 'users'));
    }

    public function destroy($id)
    {
        if (auth()->user()->role === 'admin') {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to delete inventory data.',
                ],
                403,
            );
        }

        $inventory = Inventory::findOrFail($id);
        $name = $inventory->name;
        $inventory->delete();

        // Return JSON response untuk AJAX
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Inventory {$name} deleted successfully.",
            ]);
        }

        // Fallback untuk form submission biasa
        return redirect()
            ->route('inventory.index')
            ->with('success', "Inventory <b>{$name}</b> deleted successfully.");
    }

    /**
     * Sync inventories from Lark Base
     * Following iSyment pattern: Controller as trigger, Service handles logic
     */
    public function syncFromLark(LarkInventorySyncService $syncService)
    {
        try {
            $stats = $syncService->sync();

            $message = sprintf('Lark sync completed! Fetched: %d | Filtered: %d | Aggregated: %d materials | Created: %d | Updated: %d | Skipped: %d', $stats['fetched'], $stats['filtered'], $stats['aggregated_groups'] ?? 0, $stats['created'], $stats['updated'], $stats['skipped']);

            if (isset($stats['deactivated']) && $stats['deactivated'] > 0) {
                $message .= sprintf(' | Deactivated: %d', $stats['deactivated']);
            }

            if ($stats['errors'] > 0) {
                $message .= sprintf(' | Errors: %d', $stats['errors']);
                return redirect()->route('inventory.index')->with('warning', $message);
            }

            return redirect()->route('inventory.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Lark inventory sync failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('inventory.index')
                ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get raw Lark response for debugging
     * Only accessible by super admin
     */
    public function getLarkRawData(LarkInventorySyncService $syncService)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        try {
            $data = $syncService->getRawData();

            return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'error' => 'Failed to fetch Lark data',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
