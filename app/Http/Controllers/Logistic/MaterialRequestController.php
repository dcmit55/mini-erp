<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\InventoryBatch;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use App\Models\InternalProject;
use App\Models\Lark\LarkStagingInventory;
use App\Models\Procurement\IndoPurchase;
use Illuminate\Http\Request;
use App\Events\MaterialRequestUpdated;
use App\Models\Admin\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MaterialRequestExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Cache;

class MaterialRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:logistic.material-request.view');
        $this->middleware('can:logistic.material-request.create')->only(['create', 'store', 'bulkCreate', 'bulkStore']);
        $this->middleware('can:logistic.material-request.edit')->only(['edit', 'update']);
        $this->middleware('can:logistic.material-request.view')->only(['destroy']);
        $this->middleware('can:logistic.material-request.approve')->only(['approve', 'autoGoodsOut', 'quickUpdate']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = MaterialRequest::with(['inventory:id,name,unit', 'stagingInventory:id,name,unit,review_status,processed', 'indoPurchase:id,new_item_name,material_id,unit_id,quantity,purchase_type,status,item_status,created_at,po_number', 'indoPurchase.material:id,name,material_code,unit', 'indoPurchase.unit:id,name', 'project:id,name,department_id', 'internalProject:id,project,job,department_id', 'jobOrder:id,name,project_id', 'user:id,username,department_id', 'user.department:id,name'])->latest();

            // Apply filters
            if ($request->filled('project')) {
                $query->where('project_id', $request->project);
            }
            if ($request->filled('job_order')) {
                $query->where('job_order_id', $request->job_order);
            }
            // NEW: Filter by project_type
            if ($request->filled('project_type')) {
                $query->where('project_type', $request->project_type);
            }
            if ($request->filled('material')) {
                $query->where('inventory_id', $request->material);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('requested_by')) {
                $query->where('requested_by', $request->requested_by);
            }
            if ($request->filled('requested_at')) {
                $query->whereDate('created_at', $request->requested_at);
            }
            // Filter by purchase source (indo_purchase / international)
            if ($request->filled('purchase_source')) {
                if ($request->purchase_source === 'indo_purchase') {
                    $query->whereNotNull('indo_purchase_id');
                } elseif ($request->purchase_source === 'international') {
                    $query->whereNotNull('staging_inventory_id');
                }
            }

            // Custom search
            if ($request->filled('custom_search')) {
                $search = $request->custom_search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('inventory', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('project', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('internalProject', function ($q) use ($search) {
                            $q->where('job', 'like', "%{$search}%")->orWhere('project', 'like', "%{$search}%");
                        })
                        ->orWhere('requested_by', 'like', "%{$search}%")
                        ->orWhere('remark', 'like', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addColumn('checkbox', function ($req) {
                    return $req->status === 'approved' ? '<input type="checkbox" class="select-row" value="' . $req->id . '">' : '';
                })
                ->addColumn('project_name', function ($req) {
                    if ($req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT && $req->project) {
                        return $req->project->name;
                    } elseif ($req->project_type === MaterialRequest::PROJECT_TYPE_INTERNAL && $req->internalProject) {
                        return $req->internalProject->project->value ?? (string) $req->internalProject->project;
                    }
                    return '(No Project)';
                })
                ->addColumn('job_order', function ($req) {
                    if ($req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT && $req->jobOrder) {
                        return $req->jobOrder->name;
                    } elseif ($req->project_type === MaterialRequest::PROJECT_TYPE_INTERNAL && $req->internalProject) {
                        return $req->internalProject->job ?? '-';
                    }
                    return '-';
                })
                // NEW: Kolom Project Type
                ->addColumn('project_type', function ($req) {
                    return $req->project_type === 'client' ? 'Client' : 'Internal';
                })
                ->addColumn('material_name', function ($req) {
                    if ($req->inventory_source === 'incoming') {
                        // Indo purchase source
                        if ($req->indo_purchase_id && $req->indoPurchase) {
                            $p = $req->indoPurchase;
                            if ($p->purchase_type === 'restock' && $p->material) {
                                $name = $p->material->name;
                            } else {
                                $name = $p->new_item_name ?? '(No Material)';
                            }
                            $poStatus = ucfirst($p->status ?? '-');
                            $receiptStatus = ucfirst($p->item_status ?? '-');
                            $badge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Indo Purchase | PO: ' . e($p->po_number ?? '-') . '"><i class="bi bi-bag-check"></i></span>';
                            return $badge . e($name);
                        }
                        // Lark staging source
                        $stagingName = $req->stagingInventory->name ?? null;
                        if ($stagingName) {
                            return '<span class="badge bg-success-subtle text-success border border-success-subtle me-1" title="International Purchase"><i class="bi bi-box-arrow-in-down"></i></span>' . e($stagingName);
                        }
                        return '(No Material)';
                    }
                    // Stock source
                    return $req->inventory->name ?? '(No Material)';
                })
                ->addColumn('requested_qty', function ($req) {
                    if ($req->inventory_source === 'incoming') {
                        if ($req->indo_purchase_id && $req->indoPurchase) {
                            $unit = optional($req->indoPurchase->unit)->name ?? '';
                        } else {
                            $unit = $req->stagingInventory->unit_name ?? '';
                        }
                    } else {
                        $unit = $req->inventory->unit_name ?? '';
                    }
                    return rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.') . ' ' . $unit;
                })
                ->addColumn('remaining_qty', function ($req) {
                    if ($req->inventory_source === 'incoming') {
                        if ($req->indo_purchase_id && $req->indoPurchase) {
                            $unit = optional($req->indoPurchase->unit)->name ?? '';
                        } else {
                            $unit = $req->stagingInventory->unit_name ?? '';
                        }
                    } else {
                        $unit = $req->inventory->unit_name ?? '';
                    }
                    $remaining = $req->qty - $req->processed_qty;
                    return '<span data-bs-toggle="tooltip" title="' . $unit . '">' . rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.') . '</span>';
                })
                ->addColumn('processed_qty', function ($req) {
                    $unit = $req->inventory_source === 'incoming' ? $req->stagingInventory->unit_name ?? '' : $req->inventory->unit_name ?? '';
                    return '<span data-bs-toggle="tooltip" title="' . $unit . '">' . rtrim(rtrim(number_format($req->processed_qty, 2, '.', ''), '0'), '.') . '</span>';
                })
                ->addColumn('requested_by', function ($req) {
                    $department = $req->user && $req->user->department ? ucfirst($req->user->department->name) : '-';
                    return '<span data-bs-toggle="tooltip" title="' . $department . '">' . ucfirst($req->requested_by) . '</span>';
                })
                ->addColumn('requested_at', function ($req) {
                    return $req->created_at ? $req->created_at->format('Y-m-d, H:i') : '-';
                })
                ->addColumn('status', function ($req) {
                    $authUser = auth()->user();

                    if ($authUser->can('logistic.material-request.approve')) {
                        return '<select name="status" class="form-select form-select-sm status-select status-select-rounded status-quick-update"
                                data-id="' .
                            $req->id .
                            '">
                                <option value="pending" ' .
                            ($req->status === 'pending' ? 'selected' : '') .
                            '>Pending</option>
                                <option value="approved" ' .
                            ($req->status === 'approved' ? 'selected' : '') .
                            '>Approved</option>
                                <option value="canceled" ' .
                            ($req->status === 'canceled' ? 'selected' : '') .
                            '>Canceled</option>
                                <option value="delivered" ' .
                            ($req->status === 'delivered' ? 'selected' : '') .
                            ' disabled>Delivered</option>
                            </select>';
                    } else {
                        return '<span class="badge rounded-pill ' . $req->getStatusBadgeClass() . '">' . ucfirst($req->status) . '</span>';
                    }
                })
                ->addColumn('remark', function ($req) {
                    return $req->remark ?? '-';
                })
                ->addColumn('actions', function ($req) {
                    $authUser = auth()->user();
                    $canApprove = $authUser->can('logistic.material-request.approve');
                    $canEdit = $authUser->can('logistic.material-request.edit');
                    $canDelete = $authUser->can('logistic.material-request.delete');
                    $canGoodsOut = $authUser->can('logistic.goods-out.create');
                    $isRequestOwner = $authUser->username === $req->requested_by;
                    $isSuperAdmin = $authUser->isSuperAdmin();

                    $actions = '<div class="d-flex flex-nowrap gap-1">';

                    if ($req->inventory) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success material-detail-btn" data-id="' . $req->inventory->id . '" title="Material Detail"><i class="bi bi-info-circle"></i></button>';
                    }

                    if ($req->status === 'approved' && $req->status !== 'canceled' && $req->qty - $req->processed_qty > 0 && $canGoodsOut) {
                        $actions .= '<a href="' . route('goods_out.create_with_id', $req->id) . '" class="btn btn-sm btn-success" title="Goods Out"><i class="bi bi-box-arrow-right"></i></a>';
                    }

                    if ($req->status === 'pending' && ($isRequestOwner || $canEdit)) {
                        $actions .= '<a href="' . route('material_requests.edit', $req->id) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                    }

                    $showDelete = false;
                    $deleteTooltip = 'Delete';

                    if (in_array($req->status, ['approved', 'delivered'])) {
                        if ($canDelete) {
                            $showDelete = true;
                            $deleteTooltip = 'Delete';
                        }
                    } elseif ($req->status === 'pending') {
                        if ($isRequestOwner || $canDelete) {
                            $showDelete = true;
                            $deleteTooltip = $isRequestOwner ? 'Delete Your Request' : 'Delete';
                        }
                    } elseif ($req->status === 'canceled') {
                        if ($isRequestOwner || $canDelete || $canApprove) {
                            $showDelete = true;
                            $deleteTooltip = $isRequestOwner ? 'Delete Your Canceled Request' : 'Delete Canceled Request';
                        }
                    }
                    $canDelete = $showDelete;

                    if ($canDelete) {
                        $actions .=
                            '<form action="' .
                            route('material_requests.destroy', $req->id) .
                            '" method="POST" class="delete-form" style="display:inline;">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="_token" value="' .
                            csrf_token() .
                            '">
                                <button type="button" class="btn btn-sm btn-danger btn-delete" title="' .
                            $deleteTooltip .
                            '"><i class="bi bi-trash3"></i></button>
                            </form>';
                    }

                    if (in_array($req->status, ['pending', 'approved']) && ($isRequestOwner || $isSuperAdmin)) {
                        $actions .= '<button class="btn btn-sm btn-primary btn-reminder" data-id="' . $req->id . '" title="Remind Logistic"><i class="bi bi-bell"></i></button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['checkbox', 'job_order', 'project_type', 'material_name', 'remaining_qty', 'processed_qty', 'requested_by', 'status', 'remark', 'actions'])
                ->setRowId(function ($req) {
                    return 'row-' . $req->id;
                })
                ->orderColumn('requested_at', 'created_at $1')
                ->make(true);
        }

        // For non-AJAX requests, return view with filter data
        $projects = Cache::remember('material_requests_projects', 300, function () {
            return Project::orderBy('name')->get(['id', 'name']);
        });

        $materials = Cache::remember('material_requests_materials', 300, function () {
            return Inventory::orderBy('name')->get(['id', 'name']);
        });

        $users = Cache::remember('material_requests_users', 300, function () {
            return User::orderBy('username')->get(['username']);
        });

        $jobOrders = Cache::remember('material_requests_job_orders', 300, function () {
            return \App\Models\Production\JobOrder::orderBy('id', 'desc')->get(['id', 'name']);
        });

        // NEW: Daftar project type untuk filter
        $projectTypes = ['client', 'internal'];

        return view('logistic.material_requests.index', compact('projects', 'materials', 'users', 'jobOrders', 'projectTypes'));
    }

    /**
     * Show the form for creating a new resource (single).
     */
    public function create(Request $request)
    {
        $inventories = Inventory::withComputedStock()->orderBy('name')->get();
        $stagingInventories = LarkStagingInventory::select('id', 'name', 'quantity', 'received_qty', 'unit', 'material_code')
            ->whereIn('review_status', ['approved', 'pending'])
            ->where('processed', false)
            ->orderBy('name')
            ->get();
        $projects = Project::fromLark()->with('departments', 'status')->notArchived()->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->where(function ($q) {
                $q->whereNull('description')->orWhere('description', 'not like', 'IP:%');
            })
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $internalProjects = InternalProject::select('id', 'job', 'project', 'department_id')->with('department')->orderBy('created_at', 'desc')->get();

        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $selectedMaterial = null;
        if ($request->has('material_id')) {
            $selectedMaterial = Inventory::find($request->material_id);
        }

        $ptDcmDepartment = Department::where('name', 'PT DCM')->first();
        if (!$ptDcmDepartment) {
            $ptDcmDepartment = Department::orderBy('id')->first();
        }
        $defaultPtDcmDepartmentId = $ptDcmDepartment ? $ptDcmDepartment->id : null;

        return view('logistic.material_requests.create', compact('inventories', 'stagingInventories', 'projects', 'jobOrders', 'internalProjects', 'selectedMaterial', 'departments', 'units', 'defaultPtDcmDepartmentId'));
    }

    /**
     * AJAX: Get unified incoming materials (lark_staging + indo_purchases)
     * filtered by the project associated with the given Job Order.
     * Returns empty array if no job_order_id provided.
     */
    public function getIncomingMaterials(Request $request)
    {
        if (!$request->filled('job_order_id')) {
            return response()->json([]);
        }

        $jobOrder = \App\Models\Production\JobOrder::with('project:id,name')->where('id', $request->job_order_id)->first();

        if (!$jobOrder || !$jobOrder->project) {
            return response()->json([]);
        }

        $project = $jobOrder->project;
        $projectName = $project->name;
        $projectId = $project->id;

        // 1) Lark Staging Inventories — filter by project_lark = project name
        $stagingItems = LarkStagingInventory::select('id', 'name', 'quantity', 'received_qty', 'unit', 'material_code')
            ->whereIn('review_status', ['approved', 'pending'])
            ->where('processed', false)
            ->where('project_lark', $projectName)
            ->orderBy('name')
            ->get()
            ->map(
                fn($s) => [
                    'id' => 'lsi_' . $s->id,
                    'real_id' => $s->id,
                    'source' => 'lark_staging',
                    'name' => $s->name,
                    'material_code' => $s->material_code ?? '',
                    'quantity' => floatval($s->quantity) + floatval($s->received_qty ?? 0),
                    'unit' => $s->unit ?? '',
                ],
            );

        // 2) Indo Purchases — receipt pending, project-scoped (PO can be pending or approved)
        $joId = $request->job_order_id;
        $indoItems = IndoPurchase::with(['unit', 'material:id,name,material_code,unit'])
            ->where(function ($q) use ($projectId, $joId) {
                // Match by project OR by specific JO (items linked directly to the JO)
                $q->where('project_id', $projectId)->orWhere('job_order_id', $joId);
            })
            ->whereIn('status', ['pending', 'approved']) // PO pending OR approved
            ->whereIn('item_status', ['pending', 'pending_check']) // receipt not done yet
            ->where('is_current', true)
            ->orderBy('id')
            ->get()
            ->map(function ($p) {
                // Prefer inventory name for restock, new_item_name for new items
                if ($p->isRestock() && $p->material) {
                    $name = $p->material->name;
                    $materialCode = $p->material->material_code ?? '';
                    $unit = $p->unit ? $p->unit->name : $p->material->unit_name ?? '';
                } else {
                    $name = $p->new_item_name ?? 'Unknown Item';
                    $materialCode = '';
                    $unit = $p->unit ? $p->unit->name : '';
                }

                return [
                    'id' => 'ip_' . $p->id,
                    'real_id' => $p->id,
                    'source' => 'indo_purchase',
                    'name' => $name,
                    'material_code' => $materialCode,
                    'quantity' => floatval($p->quantity ?? 0),
                    'unit' => $unit,
                ];
            });

        $all = $stagingItems->concat($indoItems)->values();

        return response()->json($all);
    }

    /**
     * AJAX: Get staging inventories for Inventory Incoming source.
     *
     * If job_order_id is provided (client project type), filter by the project
     * associated with that Job Order: staging.project_lark must match project.name.
     */
    public function getStagingInventories(Request $request)
    {
        $query = LarkStagingInventory::select('id', 'name', 'quantity', 'received_qty', 'unit', 'material_code', 'project_lark')
            ->whereIn('review_status', ['approved', 'pending'])
            ->where('processed', false);

        // Filter by JO's project: only show staging items whose project_lark matches the project name
        if ($request->filled('job_order_id')) {
            $jobOrder = \App\Models\Production\JobOrder::with('project:id,name')->where('id', $request->job_order_id)->first();

            if ($jobOrder && $jobOrder->project) {
                $query->where('project_lark', $jobOrder->project->name);
            }
        }

        $items = $query->orderBy('name')->get();

        return response()->json($items);
    }

    /**
     * Store a newly created resource in storage (single).
     */
    public function store(Request $request)
    {
        // inventory_source: 'stock' (inventories table) or 'incoming' (lark_staging_inventories)
        $inventorySource = $request->input('inventory_source', 'stock');

        // Validasi dasar
        $request->validate([
            'project_type' => 'required|in:client,internal',
            'inventory_source' => 'required|in:stock,incoming',
            'qty' => 'required|numeric|min:0.01',
            'job_order_id' => 'required',
        ]);

        if ($inventorySource === 'stock') {
            $request->validate([
                'inventory_id' => 'required|exists:inventories,id',
            ]);
        } else {
            $request->validate([
                'staging_inventory_id' => 'required|string',
            ]);
        }

        // Validasi conditional berdasarkan tipe proyek
        if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
            $request->validate([
                'job_order_id' => 'required|exists:job_orders,id',
                'project_id' => 'required|exists:projects,id',
            ]);
        } else {
            $request->validate([
                'job_order_id' => 'required|exists:internal_projects,id',
            ]);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $stagingInventoryId = null;
            $indoPurchaseId = null;
            $materialName = '';
            $inventoryId = null;

            if ($inventorySource === 'stock') {
                // Source: Inventory Stock (batch inventory)
                $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();

                if ($request->qty > $inventory->quantity) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['qty' => 'Requested quantity cannot exceed available inventory stock quantity.']);
                }

                $materialName = $inventory->name;
                $inventoryId = $inventory->id;
            } else {
                // Source: Inventory Incoming — parse prefixed ID (lsi_X = lark staging, ip_X = indo purchase)
                $rawId = $request->staging_inventory_id;
                $stagingInventoryId = null;
                $indoPurchaseId = null;

                if (str_starts_with($rawId, 'ip_')) {
                    $indoPurchaseId = (int) substr($rawId, 3);
                    $purchase = IndoPurchase::where('id', $indoPurchaseId)->lockForUpdate()->first();
                    if (!$purchase) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors(['staging_inventory_id' => 'Indo purchase item not found.']);
                    }
                    $availableQty = floatval($purchase->quantity ?? 0);
                    $unitName = optional($purchase->unit)->name ?? '';
                    if ($request->qty > $availableQty) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors(['qty' => 'Requested quantity cannot exceed available indo purchase quantity (' . $availableQty . ' ' . $unitName . ').']);
                    }
                    $materialName = $purchase->material_name;
                } else {
                    // lsi_ prefix or legacy plain ID
                    $lsiId = str_starts_with($rawId, 'lsi_') ? (int) substr($rawId, 4) : (int) $rawId;
                    $staging = LarkStagingInventory::where('id', $lsiId)->lockForUpdate()->first();
                    if (!$staging) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors(['staging_inventory_id' => 'Staging inventory item not found.']);
                    }
                    $availableQty = floatval($staging->quantity) + floatval($staging->received_qty ?? 0);
                    if ($request->qty > $availableQty) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors(['qty' => 'Requested quantity cannot exceed available incoming inventory quantity (' . $availableQty . ' ' . $staging->unit_name . ').']);
                    }
                    $stagingInventoryId = $staging->id;
                    $materialName = $staging->name;
                }

                $inventoryId = null; // no batch inventory link
            }

            $data = [
                'inventory_id' => $inventoryId,
                'staging_inventory_id' => $inventorySource === 'incoming' ? $stagingInventoryId ?? null : null,
                'indo_purchase_id' => $inventorySource === 'incoming' ? $indoPurchaseId ?? null : null,
                'inventory_source' => $inventorySource,
                'project_type' => $request->project_type,
                'qty' => $request->qty,
                'processed_qty' => 0,
                'requested_by' => $user->username,
                'remark' => $request->remark,
            ];

            if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $data['job_order_id'] = $request->job_order_id;
                $data['project_id'] = $request->project_id;
                $data['internal_project_id'] = null;
            } else {
                $data['internal_project_id'] = $request->job_order_id;
                $data['job_order_id'] = null;
                $data['project_id'] = null;
            }

            $materialRequest = MaterialRequest::create($data);

            DB::commit();

            try {
                event(new MaterialRequestUpdated($materialRequest, 'created'));
            } catch (\Exception $broadcastEx) {
                \Illuminate\Support\Facades\Log::warning('MaterialRequest broadcast failed (store): ' . $broadcastEx->getMessage());
            }

            $projectName = $materialRequest->project_name;
            $sourceLabel = $inventorySource === 'stock' ? 'Inventory Stock' : 'Inventory Incoming';

            $redirect = redirect()
                ->route('material_requests.index')
                ->with('success', "Material Request for <b>{$materialName}</b> (source: {$sourceLabel}) in project <b>{$projectName}</b> created successfully!");

            if ($inventorySource === 'incoming') {
                $redirect = $redirect->with('info_incoming', 'Material Request ini menggunakan <b>Inventory Incoming</b> (Lark Staging). ' . 'Status MR <b>tidak dapat diubah ke Approved</b> secara langsung — ' . "material <b>{$materialName}</b> harus terlebih dahulu di-review dan di-approve oleh Admin Logistik, " . 'kemudian di-push ke <b>Inventory Batch</b>. Setelah proses tersebut selesai, ' . 'MR ini baru dapat diproses ke tahap <b>Goods Out</b>.');
            }

            return $redirect;
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['qty' => 'Failed to create request: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for bulk creation.
     */
    public function bulkCreate()
    {
        $inventories = Inventory::withComputedStock()->orderBy('name')->get();
        $projects = Project::with('departments', 'status')->notArchived()->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->where(function ($q) {
                $q->whereNull('description')->orWhere('description', 'not like', 'IP:%');
            })
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $internalProjects = InternalProject::select('id', 'job', 'project', 'department_id')->with('department')->orderBy('created_at', 'desc')->get();

        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $ptDcmDepartment = Department::where('name', 'PT DCM')->first();
        if (!$ptDcmDepartment) {
            $ptDcmDepartment = Department::orderBy('id')->first();
        }
        $defaultPtDcmDepartmentId = $ptDcmDepartment ? $ptDcmDepartment->id : null;

        return view('logistic.material_requests.bulk_create', compact('inventories', 'projects', 'jobOrders', 'internalProjects', 'departments', 'units', 'defaultPtDcmDepartmentId'));
    }

    /**
     * Store multiple material requests (bulk).
     */
    public function bulkStore(Request $request)
    {
        // Validasi dasar per baris
        $request->validate([
            'requests.*.project_type' => 'required|in:client,internal',
            'requests.*.inventory_id' => 'required|exists:inventories,id',
            'requests.*.qty' => 'required|numeric|min:0.01',
            'requests.*.job_order_id' => 'required', // akan divalidasi lebih lanjut
        ]);

        // Validasi conditional per baris
        foreach ($request->requests as $index => $req) {
            if ($req['project_type'] === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $request->validate([
                    "requests.$index.job_order_id" => 'required|exists:job_orders,id',
                    "requests.$index.project_id" => 'required|exists:projects,id',
                ]);
            } else {
                $request->validate([
                    "requests.$index.job_order_id" => 'required|exists:internal_projects,id',
                ]);
            }
        }

        $user = Auth::user();
        $createdRequests = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->requests as $index => $req) {
                $inventory = Inventory::where('id', $req['inventory_id'])->lockForUpdate()->first();

                if ($req['qty'] > $inventory->quantity) {
                    $errors["requests.$index.qty"] = "Quantity exceeds stock for '{$inventory->name}'.";
                    continue;
                }

                $data = [
                    'inventory_id' => $req['inventory_id'],
                    'project_type' => $req['project_type'],
                    'qty' => $req['qty'],
                    'processed_qty' => 0,
                    'requested_by' => $user->username,
                    'remark' => $req['remark'] ?? null,
                ];

                if ($req['project_type'] === MaterialRequest::PROJECT_TYPE_CLIENT) {
                    $data['job_order_id'] = $req['job_order_id'];
                    $data['project_id'] = $req['project_id'];
                    $data['internal_project_id'] = null;
                } else {
                    $data['internal_project_id'] = $req['job_order_id'];
                    $data['job_order_id'] = null;
                    $data['project_id'] = null;
                }

                $materialRequest = MaterialRequest::create($data);
                $createdRequests[] = $materialRequest;
            }

            if (!empty($errors)) {
                DB::rollBack();
                return back()->withInput()->withErrors($errors);
            }

            DB::commit();

            if (!empty($createdRequests)) {
                event(new MaterialRequestUpdated($createdRequests, 'created'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['bulk' => 'Bulk request failed: ' . $e->getMessage()]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Bulk material requests submitted!',
                'created_requests' => $createdRequests,
            ]);
        }

        $infoList = [];
        foreach ($createdRequests as $req) {
            $infoList[] = "<b>{$req->inventory->name}</b> in project <b>{$req->project_name}</b>";
        }
        $infoString = implode(', ', $infoList);

        return redirect()
            ->route('material_requests.index')
            ->with('success', "Bulk material requests submitted successfully for: {$infoString}");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $materialRequest = MaterialRequest::with('inventory', 'stagingInventory', 'indoPurchase', 'indoPurchase.material', 'indoPurchase.unit', 'project', 'internalProject')->findOrFail($id);
        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $inventories = Inventory::withComputedStock()
            ->orderBy('name')
            ->get()
            ->map(function ($inventory) {
                $inventory->available_quantity = $inventory->quantity;
                return $inventory;
            });

        // Staging inventories (pending/approved, not processed or already linked to this MR)
        $stagingInventories = \App\Models\Lark\LarkStagingInventory::where(function ($q) use ($materialRequest) {
            $q->where('processed', false)->orWhere('id', $materialRequest->staging_inventory_id);
        })
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'quantity', 'received_qty', 'material_code', 'review_status', 'processed']);

        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->where(function ($q) {
                $q->whereNull('description')->orWhere('description', 'not like', 'IP:%');
            })
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $internalProjects = InternalProject::select('id', 'job', 'project', 'department_id')->with('department')->orderBy('created_at', 'desc')->get();

        // TAMBAHKAN: Ambil default department untuk PT DCM
        $ptDcmDepartment = Department::where('name', 'PT DCM')->first();
        if (!$ptDcmDepartment) {
            $ptDcmDepartment = Department::orderBy('id')->first();
        }
        $defaultPtDcmDepartmentId = $ptDcmDepartment ? $ptDcmDepartment->id : null;

        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        if ($materialRequest->status !== 'pending') {
            return redirect()->route('material_requests.index')->with('error', 'Only pending requests can be edited.');
        }

        if (auth()->user()->username !== $materialRequest->requested_by && !auth()->user()->can('logistic.material-request.edit')) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'You do not have permission to edit this request.');
        }

        return view('logistic.material_requests.edit', compact('materialRequest', 'inventories', 'stagingInventories', 'jobOrders', 'internalProjects', 'departments', 'units', 'filters', 'defaultPtDcmDepartmentId'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $materialRequest = MaterialRequest::findOrFail($id);

        // Cek quick update — hanya jika bukan full edit form
        // Full edit form selalu punya 'qty' dan 'inventory_source'
        if ($request->has('status') && !$request->has('qty') && !$request->has('inventory_source')) {
            return $this->quickUpdate($request, $id);
        }

        // Validasi dasar
        $request->validate([
            'project_type' => 'required|in:client,internal',
            'inventory_source' => 'required|in:stock,incoming',
            'qty' => 'required|numeric|min:0.01',
            'status' => 'required|in:pending,approved,delivered,canceled',
            'remark' => 'nullable|string',
            'job_order_id' => 'required',
        ]);

        // Validasi material berdasarkan source
        if ($request->inventory_source === 'stock') {
            $request->validate(['inventory_id' => 'required|exists:inventories,id']);
        } else {
            // Accept lsi_X (lark staging), ip_X (indo purchase), or plain integer ID
            $request->validate(['staging_inventory_id' => 'required|string']);
        }

        // Validasi conditional berdasarkan tipe proyek
        if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
            $request->validate([
                'job_order_id' => 'required|exists:job_orders,id',
                'project_id' => 'required|exists:projects,id',
            ]);
        } else {
            $request->validate([
                'job_order_id' => 'required|exists:internal_projects,id',
            ]);
        }

        // Cek status request
        if (in_array($materialRequest->status, ['delivered', 'canceled'])) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Delivered or canceled requests cannot be updated.'], 422);
            }
            return redirect()->route('material_requests.index')->with('error', 'Delivered or canceled requests cannot be updated.');
        }

        // Ambil filters untuk redirect
        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        DB::beginTransaction();
        try {
            $isIncoming = $request->inventory_source === 'incoming';

            // Cek stok inventory dengan lock (hanya untuk source = stock)
            $inventory = null;
            if (!$isIncoming) {
                $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();

                if ($request->qty > $inventory->quantity) {
                    DB::rollBack();
                    $errorMsg = 'Requested quantity cannot exceed available inventory quantity.';

                    if ($request->ajax()) {
                        return response()->json(['errors' => ['qty' => [$errorMsg]]], 422);
                    }

                    return back()
                        ->withInput()
                        ->withErrors(['qty' => $errorMsg]);
                }
            }

            // Parse incoming material: lsi_X = lark staging, ip_X = indo purchase, plain int = legacy lark staging
            $newStagingInventoryId = null;
            $newIndoPurchaseId = null;
            if ($isIncoming) {
                $rawId = $request->staging_inventory_id;
                if (str_starts_with($rawId, 'ip_')) {
                    $newIndoPurchaseId = (int) substr($rawId, 3);
                    if (!IndoPurchase::where('id', $newIndoPurchaseId)->exists()) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors(['staging_inventory_id' => 'Indo purchase item not found.']);
                    }
                } else {
                    $lsiId = str_starts_with($rawId, 'lsi_') ? (int) substr($rawId, 4) : (int) $rawId;
                    $stagingCheck = LarkStagingInventory::where('id', $lsiId)->first();
                    if (!$stagingCheck) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors(['staging_inventory_id' => 'Staging inventory item not found.']);
                    }
                    $newStagingInventoryId = $stagingCheck->id;
                }
            }

            // Siapkan data update
            $updateData = [
                'inventory_source' => $request->inventory_source,
                'project_type' => $request->project_type,
                'qty' => $request->qty,
                'status' => $request->status,
                'remark' => $request->remark,
            ];

            if ($isIncoming) {
                $updateData['staging_inventory_id'] = $newStagingInventoryId;
                $updateData['indo_purchase_id'] = $newIndoPurchaseId;
                $updateData['inventory_id'] = null;
            } else {
                $updateData['inventory_id'] = $request->inventory_id;
                $updateData['staging_inventory_id'] = null;
                $updateData['indo_purchase_id'] = null;
            }

            // Set relasi berdasarkan tipe project
            if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $updateData['job_order_id'] = $request->job_order_id;
                $updateData['project_id'] = $request->project_id;
                $updateData['internal_project_id'] = null;
            } else {
                $updateData['internal_project_id'] = $request->job_order_id;
                $updateData['job_order_id'] = null;
                $updateData['project_id'] = null;
            }

            // Set approved_at jika status diubah ke approved
            $oldStatus = $materialRequest->status;
            if ($request->status === 'approved' && $materialRequest->status !== 'approved') {
                $updateData['approved_at'] = now();
            }

            // Update data
            $materialRequest->update($updateData);

            DB::commit();

            // Trigger event (wrapped to prevent broadcast failures killing the redirect)
            try {
                event(new MaterialRequestUpdated($materialRequest, 'updated'));
            } catch (\Exception $broadcastEx) {
                \Illuminate\Support\Facades\Log::warning('MaterialRequest broadcast failed (update): ' . $broadcastEx->getMessage());
            }

            // REVISION:             // CRITICAL FIX: Auto goods-out when approving material for already-delivered job order
            // REVISION:             if ($request->status === 'approved' && $oldStatus !== 'approved') {
            // REVISION:                 // Refresh to get updated relationships after transaction commit
            // REVISION:                 $materialRequest = $materialRequest->fresh();
            // REVISION:
            // REVISION:                 // Check if related Job Order is already delivered
            // REVISION:                 $jobOrder = $materialRequest->jobOrder;
            // REVISION:
            // REVISION:                 if ($jobOrder && strtolower($jobOrder->status) === 'delivered') {
            // REVISION:                     // Trigger auto goods-out service
            // REVISION:                     $autoGoodsOutService = app(\App\Services\AutoGoodsOutService::class);
            // REVISION:                     $result = $autoGoodsOutService->processJobOrderDelivery($jobOrder);
            // REVISION:
            // REVISION:                     \Illuminate\Support\Facades\Log::info('MaterialRequest update: Auto goods-out triggered for approved material', [
            // REVISION:                         'material_request_id' => $materialRequest->id,
            // REVISION:                         'job_order_id' => $jobOrder->id,
            // REVISION:                         'job_order_status' => $jobOrder->status,
            // REVISION:                         'result' => $result,
            // REVISION:                     ]);
            // REVISION:                 }
            // REVISION:             }

            // Return response berdasarkan tipe request
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Material Request updated successfully!',
                    'redirect' => route('material_requests.index', $filters),
                ]);
            }

            if ($isIncoming) {
                $freshMR = $materialRequest->fresh(['stagingInventory', 'indoPurchase', 'indoPurchase.material']);
                if ($freshMR->indo_purchase_id && $freshMR->indoPurchase) {
                    $ip = $freshMR->indoPurchase;
                    $materialLabel = $ip->purchase_type === 'restock' && $ip->material ? $ip->material->name : $ip->new_item_name ?? 'Incoming Material';
                } else {
                    $materialLabel = $freshMR->stagingInventory->name ?? 'Incoming Material';
                }
            } else {
                $materialLabel = $inventory->name ?? 'Material';
            }

            return redirect()
                ->route('material_requests.index', $filters)
                ->with('success', "Material Request for <b>{$materialLabel}</b> updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            $errorMsg = 'Failed to update request: ' . $e->getMessage();

            if ($request->ajax()) {
                return response()->json(['error' => $errorMsg], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['qty' => $errorMsg]);
        }
    }
    /**
     * Export material requests to Excel.
     */
    public function export(Request $request)
    {
        $project = $request->project;
        $jobOrder = $request->job_order;
        $material = $request->material;
        $status = $request->status;
        $requestedBy = $request->requested_by;
        $requestedAt = $request->requested_at;

        $query = MaterialRequest::with(['inventory', 'project', 'jobOrder', 'internalProject', 'user.department']);

        if ($project) {
            $query->where('project_id', $project);
        }
        if ($jobOrder) {
            $query->where('job_order_id', $jobOrder);
        }
        if ($material) {
            $query->where('inventory_id', $material);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($requestedBy) {
            $query->where('requested_by', $requestedBy);
        }
        if ($requestedAt) {
            $query->whereDate('created_at', $requestedAt);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'material_requests';
        if ($project) {
            $projectName = Project::find($project)->name ?? 'Unknown Project';
            $fileName .= '_project-' . str_replace(' ', '-', strtolower($projectName));
        }
        if ($jobOrder) {
            $jobOrderName = \App\Models\Production\JobOrder::find($jobOrder)->name ?? 'Unknown JobOrder';
            $fileName .= '_joborder-' . str_replace(' ', '-', strtolower($jobOrderName));
        }
        if ($material) {
            $materialName = Inventory::find($material)->name ?? 'Unknown Material';
            $fileName .= '_material-' . str_replace(' ', '-', strtolower($materialName));
        }
        if ($status) {
            $fileName .= '_status-' . strtolower($status);
        }
        if ($requestedBy) {
            $fileName .= '_requested_by-' . strtolower($requestedBy);
        }
        if ($requestedAt) {
            $fileName .= '_requested_at-' . $requestedAt;
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new MaterialRequestExport($requests), $fileName);
    }

    /**
     * Quick update status via AJAX.
     */
    public function quickUpdate(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,approved,delivered,canceled']);

        $materialRequest = MaterialRequest::with('stagingInventory')->findOrFail($id);

        if ($materialRequest->status === 'delivered') {
            return response()->json(['success' => false, 'message' => 'Delivered requests cannot be updated.'], 422);
        }

        $oldStatus = $materialRequest->status;

        // Validasi: incoming source tidak bisa di-approve jika material belum siap
        if ($request->status === 'approved' && $materialRequest->inventory_source === 'incoming') {
            if ($materialRequest->indo_purchase_id) {
                // Indo Purchase: PO must be approved, receipt received/done, and material must be in inventory batch
                $purchase = IndoPurchase::find($materialRequest->indo_purchase_id);
                if (!$purchase) {
                    return response()->json(['success' => false, 'message' => 'Data PO tidak ditemukan.'], 422);
                }
                if ($purchase->status !== 'approved') {
                    return response()->json(['success' => false, 'message' => 'Material Request tidak dapat di-Approve. Status PO <b>' . e($purchase->po_number ?? '-') . '</b> belum Approved (status saat ini: <b>' . ucfirst($purchase->status) . '</b>).'], 422);
                }
                if (!in_array($purchase->item_status, ['received', 'done', 'matched'])) {
                    return response()->json(['success' => false, 'message' => 'Material Request tidak dapat di-Approve. Penerimaan barang untuk PO <b>' . e($purchase->po_number ?? '-') . '</b> belum selesai (receipt status: <b>' . ucfirst($purchase->item_status) . '</b>).'], 422);
                }
                $existsInBatch = InventoryBatch::where('source_type', InventoryBatch::SOURCE_INDO_PURCHASE)->where('source_id', $purchase->id)->exists();
                if (!$existsInBatch) {
                    return response()->json(['success' => false, 'message' => 'Material Request tidak dapat di-Approve. Material dari PO <b>' . e($purchase->po_number ?? '-') . '</b> belum masuk ke Inventory Batch.'], 422);
                }
            } else {
                // Lark staging: must be reviewed/approved and pushed to inventory batch
                $staging = $materialRequest->stagingInventory;
                if (!$staging || $staging->review_status !== 'approved' || !$staging->processed) {
                    $stagingName = $staging->name ?? '-';
                    $reason = !$staging ? 'data staging tidak ditemukan' : ($staging->review_status !== 'approved' ? 'belum di-approve oleh Admin Logistik' : 'belum di-push ke Inventory Batch');
                    return response()->json(['success' => false, 'message' => 'Material Request ini menggunakan <b>Inventory Incoming</b>. Status tidak dapat diubah ke <b>Approved</b> karena staging inventory (<b>' . e($stagingName) . '</b>) ' . $reason . '.'], 422);
                }
            }
        }

        $updateData = ['status' => $request->status];
        if ($request->status === 'approved' && $materialRequest->status !== 'approved') {
            $updateData['approved_at'] = now();
        }
        $materialRequest->update($updateData);

        try {
            event(new MaterialRequestUpdated($materialRequest, 'status'));
        } catch (\Exception $broadcastEx) {
            \Illuminate\Support\Facades\Log::warning('MaterialRequest broadcast failed (quickUpdate): ' . $broadcastEx->getMessage());
        }

        // CRITICAL FIX: Auto goods-out when approving material for already-delivered job order
        // COMMENTED OUT - UNDER REVISION
        /*
        if ($request->status === 'approved' && $oldStatus !== 'approved') {
            // Check if related Job Order is already delivered
            $jobOrder = $materialRequest->jobOrder;

            if ($jobOrder && strtolower($jobOrder->status) === 'delivered') {
                // Trigger auto goods-out service
                $autoGoodsOutService = app(\App\Services\AutoGoodsOutService::class);
                $result = $autoGoodsOutService->processJobOrderDelivery($jobOrder);

                \Illuminate\Support\Facades\Log::info('QuickUpdate: Auto goods-out triggered for approved material', [
                    'material_request_id' => $materialRequest->id,
                    'job_order_id' => $jobOrder->id,
                    'job_order_status' => $jobOrder->status,
                    'result' => $result,
                ]);
            }
        }
        */

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'old_status' => $oldStatus,
            'new_status' => $materialRequest->status,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $materialRequest = MaterialRequest::findOrFail($id);

        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        $authUser = auth()->user();
        $canDelete = $authUser->can('logistic.material-request.delete');
        $isRequestOwner = $authUser->username === $materialRequest->requested_by;

        if (in_array($materialRequest->status, ['approved', 'delivered'])) {
            if (!$canDelete) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'Only Admin can delete approved or delivered requests.');
            }
        } elseif ($materialRequest->status === 'pending') {
            if (!$isRequestOwner && !$canDelete) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'Only request owner or Admin can delete pending requests.');
            }
        } elseif ($materialRequest->status === 'canceled') {
            if (!$isRequestOwner && !$canDelete) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'You do not have permission to delete this canceled request.');
            }
        }

        event(new MaterialRequestUpdated($materialRequest, 'deleted'));
        $materialRequest->delete();

        $inventory = $materialRequest->inventory ?? Inventory::find($materialRequest->inventory_id);
        $projectName = $materialRequest->project_name;

        return redirect()
            ->route('material_requests.index', $filters)
            ->with('success', "Material Request for <b>{$inventory->name}</b> in project <b>{$projectName}</b> deleted successfully.");
    }

    /**
     * Send reminder to logistic.
     */
    public function sendReminder($id)
    {
        $request = MaterialRequest::findOrFail($id);

        if (!in_array($request->status, ['pending', 'approved']) || $request->processed_qty >= $request->qty) {
            return response()->json(['success' => false, 'message' => 'Reminder not allowed for this request.']);
        }

        event(new \App\Events\MaterialRequestReminder($request));

        return response()->json(['success' => true]);
    }

    /**
     * Get details for bulk goods out.
     */
    public function bulkDetails(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:material_requests,id',
        ]);

        $requests = MaterialRequest::with('inventory', 'project', 'jobOrder', 'internalProject')->whereIn('id', $request->selected_ids)->get();

        $data = $requests->map(function ($req) {
            return [
                'id' => $req->id,
                'material_name' => $req->inventory->name ?? '-',
                'unit' => $req->inventory->unit_name ?? '',
                'job_order_name' => $req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT ? $req->jobOrder->name ?? '-' : $req->internalProject->job ?? '-',
                'project_name' => $req->project_name,
                'requested_by' => $req->requested_by,
                'requested_qty' => rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.'),
                'remaining_qty' => rtrim(rtrim(number_format($req->remaining_qty, 2, '.', ''), '0'), '.'),
            ];
        });

        return response()->json($data);
    }

    /**
     * Get inventory detail for modal.
     */
    public function getInventoryDetail($id)
    {
        $inventory = Inventory::with(['category', 'currency', 'supplier', 'location'])->findOrFail($id);

        $data = [
            'id' => $inventory->id,
            'name' => $inventory->name,
            'category' => $inventory->category->name ?? '-',
            'quantity' => rtrim(rtrim(number_format($inventory->quantity, 2, '.', ''), '0'), '.'),
            'unit' => $inventory->unit_name ?? '-',
            'price' => $inventory->price ? number_format($inventory->price, 2, ',', '.') : '0',
            'currency' => $inventory->currency->name ?? '-',
            'supplier' => $inventory->supplier->name ?? '-',
            'location' => $inventory->location->name ?? '-',
            'remark' => $inventory->remark ?? '-',
            'img_url' => $inventory->img ? asset('storage/' . $inventory->img) : null,
            'qr_code' => $inventory->qr_code ?? null,
        ];

        return response()->json($data);
    }
}
