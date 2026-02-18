<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use App\Models\InternalProject;
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
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = MaterialRequest::with([
                'inventory:id,name,quantity,unit',
                'project:id,name,department_id',
                'internalProject:id,project,job,department_id',
                'jobOrder:id,name,project_id',
                'user:id,username,department_id',
                'user.department:id,name'
            ])->latest();

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
                        $q->where('job', 'like', "%{$search}%")
                          ->orWhere('project', 'like', "%{$search}%");
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
                        return '[Client] ' . $req->jobOrder->name;
                    } elseif ($req->project_type === MaterialRequest::PROJECT_TYPE_INTERNAL && $req->internalProject) {
                        return '[Internal] ' . $req->internalProject->job;
                    }
                    return '-';
                })
                // NEW: Kolom Project Type
                ->addColumn('project_type', function ($req) {
                    return $req->project_type === 'client' ? 'Client' : 'Internal';
                })
                ->addColumn('material_name', function ($req) {
                    return e($req->inventory->name ?? '(No Material)');
                })
                ->addColumn('requested_qty', function ($req) {
                    return rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.') . ' ' . ($req->inventory->unit ?? '');
                })
                ->addColumn('remaining_qty', function ($req) {
                    $remaining = $req->qty - $req->processed_qty;
                    return '<span data-bs-toggle="tooltip" title="' . ($req->inventory->unit ?? '') . '">' . rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.') . '</span>';
                })
                ->addColumn('processed_qty', function ($req) {
                    return '<span data-bs-toggle="tooltip" title="' . ($req->inventory->unit ?? '') . '">' . rtrim(rtrim(number_format($req->processed_qty, 2, '.', ''), '0'), '.') . '</span>';
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
                    $isAdmin = $authUser->isReadOnlyAdmin();
                    $isLogisticAdmin = $authUser->isLogisticAdmin();

                    if ($isLogisticAdmin || $isAdmin) {
                        return '<select name="status" class="form-select form-select-sm status-select status-select-rounded status-quick-update"
                                data-id="' . $req->id . '">
                                <option value="pending" ' . ($req->status === 'pending' ? 'selected' : '') . '>Pending</option>
                                <option value="approved" ' . ($req->status === 'approved' ? 'selected' : '') . '>Approved</option>
                                <option value="canceled" ' . ($req->status === 'canceled' ? 'selected' : '') . '>Canceled</option>
                                <option value="delivered" ' . ($req->status === 'delivered' ? 'selected' : '') . ' disabled>Delivered</option>
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
                    $isLogisticAdmin = $authUser->isLogisticAdmin();
                    $isSuperAdmin = $authUser->isSuperAdmin();
                    $isAdmin = $authUser->isReadOnlyAdmin();
                    $isRequestOwner = $authUser->username === $req->requested_by;

                    $actions = '<div class="d-flex flex-nowrap gap-1">';

                    if ($req->inventory) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success material-detail-btn" data-id="' . $req->inventory->id . '" title="Material Detail"><i class="bi bi-info-circle"></i></button>';
                    }

                    if ($req->status === 'approved' && $req->status !== 'canceled' && $req->qty - $req->processed_qty > 0 && ($isLogisticAdmin || $isAdmin)) {
                        $actions .= '<a href="' . route('goods_out.create_with_id', $req->id) . '" class="btn btn-sm btn-success" title="Goods Out"><i class="bi bi-box-arrow-right"></i></a>';
                    }

                    if ($req->status === 'pending' && ($isRequestOwner || $isLogisticAdmin || $isAdmin)) {
                        $actions .= '<a href="' . route('material_requests.edit', $req->id) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                    }

                    $canDelete = false;
                    $deleteTooltip = 'Delete';

                    if (in_array($req->status, ['approved', 'delivered'])) {
                        if ($isSuperAdmin || $isAdmin) {
                            $canDelete = true;
                            $deleteTooltip = 'Delete (Super Admin Only)';
                        }
                    } elseif ($req->status === 'pending') {
                        if ($isRequestOwner || $isSuperAdmin || $isAdmin) {
                            $canDelete = true;
                            $deleteTooltip = $isRequestOwner ? 'Delete Your Request' : 'Delete (Super Admin)';
                        }
                    } elseif ($req->status === 'canceled') {
                        if ($isRequestOwner || $isLogisticAdmin || $isSuperAdmin || $isAdmin) {
                            $canDelete = true;
                            $deleteTooltip = $isRequestOwner ? 'Delete Your Canceled Request' : ($isLogisticAdmin ? 'Delete Canceled Request (Logistic Admin)' : 'Delete Canceled Request (Super Admin)');
                        }
                    }

                    if ($canDelete) {
                        $actions .= '<form action="' . route('material_requests.destroy', $req->id) . '" method="POST" class="delete-form" style="display:inline;">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                <button type="button" class="btn btn-sm btn-danger btn-delete" title="' . $deleteTooltip . '"><i class="bi bi-trash3"></i></button>
                            </form>';
                    }

                    if (in_array($req->status, ['pending', 'approved']) && ($isRequestOwner || $isSuperAdmin)) {
                        $actions .= '<button class="btn btn-sm btn-primary btn-reminder" data-id="' . $req->id . '" title="Remind Logistic"><i class="bi bi-bell"></i></button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['checkbox', 'job_order', 'project_type', 'remaining_qty', 'processed_qty', 'requested_by', 'status', 'remark', 'actions'])
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
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::fromLark()->with('departments', 'status')->notArchived()->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $internalProjects = InternalProject::select('id', 'job', 'project', 'department_id')
            ->with('department')
            ->orderBy('created_at', 'desc')
            ->get();

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

        return view('logistic.material_requests.create', compact(
            'inventories',
            'projects',
            'jobOrders',
            'internalProjects',
            'selectedMaterial',
            'departments',
            'units',
            'defaultPtDcmDepartmentId'
        ));
    }

    /**
     * Store a newly created resource in storage (single).
     */
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material requests.');
        }

        // Validasi dasar: gunakan job_order_id
        $request->validate([
            'project_type'  => 'required|in:client,internal',
            'inventory_id'  => 'required|exists:inventories,id',
            'qty'           => 'required|numeric|min:0.01',
            'job_order_id'  => 'required', // akan divalidasi lebih lanjut
        ]);

        // Validasi conditional berdasarkan tipe proyek
        if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
            $request->validate([
                'job_order_id' => 'required|exists:job_orders,id',
                'project_id'   => 'required|exists:projects,id',
            ]);
        } else {
            $request->validate([
                'job_order_id' => 'required|exists:internal_projects,id',
            ]);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();

            if ($request->qty > $inventory->quantity) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['qty' => 'Requested quantity cannot exceed available inventory quantity.']);
            }

            $data = [
                'inventory_id'   => $request->inventory_id,
                'project_type'   => $request->project_type,
                'qty'            => $request->qty,
                'processed_qty'  => 0,
                'requested_by'   => $user->username,
                'remark'         => $request->remark,
            ];

            if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $data['job_order_id'] = $request->job_order_id;
                $data['project_id']    = $request->project_id;
                $data['internal_project_id'] = null;
            } else {
                $data['internal_project_id'] = $request->job_order_id; // ID internal project
                $data['job_order_id'] = null;
                $data['project_id']    = null;
            }

            $materialRequest = MaterialRequest::create($data);

            DB::commit();

            event(new MaterialRequestUpdated($materialRequest, 'created'));

            $projectName = $materialRequest->project_name;

            return redirect()
                ->route('material_requests.index')
                ->with('success', "Material Request for <b>{$inventory->name}</b> in project <b>{$projectName}</b> created successfully!");
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
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::with('departments', 'status')->notArchived()->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $internalProjects = InternalProject::select('id', 'job', 'project', 'department_id')
            ->with('department')
            ->orderBy('created_at', 'desc')
            ->get();

        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $ptDcmDepartment = Department::where('name', 'PT DCM')->first();
        if (!$ptDcmDepartment) {
            $ptDcmDepartment = Department::orderBy('id')->first();
        }
        $defaultPtDcmDepartmentId = $ptDcmDepartment ? $ptDcmDepartment->id : null;

        return view('logistic.material_requests.bulk_create', compact(
            'inventories',
            'projects',
            'jobOrders',
            'internalProjects',
            'departments',
            'units',
            'defaultPtDcmDepartmentId'
        ));
    }

    /**
     * Store multiple material requests (bulk).
     */
    public function bulkStore(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material requests.');
        }

        // Validasi dasar per baris
        $request->validate([
            'requests.*.project_type'   => 'required|in:client,internal',
            'requests.*.inventory_id'   => 'required|exists:inventories,id',
            'requests.*.qty'            => 'required|numeric|min:0.01',
            'requests.*.job_order_id'   => 'required', // akan divalidasi lebih lanjut
        ]);

        // Validasi conditional per baris
        foreach ($request->requests as $index => $req) {
            if ($req['project_type'] === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $request->validate([
                    "requests.$index.job_order_id" => 'required|exists:job_orders,id',
                    "requests.$index.project_id"    => 'required|exists:projects,id',
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
                    'inventory_id'   => $req['inventory_id'],
                    'project_type'   => $req['project_type'],
                    'qty'            => $req['qty'],
                    'processed_qty'  => 0,
                    'requested_by'   => $user->username,
                    'remark'         => $req['remark'] ?? null,
                ];

                if ($req['project_type'] === MaterialRequest::PROJECT_TYPE_CLIENT) {
                    $data['job_order_id'] = $req['job_order_id'];
                    $data['project_id']    = $req['project_id'];
                    $data['internal_project_id'] = null;
                } else {
                    $data['internal_project_id'] = $req['job_order_id'];
                    $data['job_order_id'] = null;
                    $data['project_id']    = null;
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
        $materialRequest = MaterialRequest::with('inventory', 'project', 'internalProject')->findOrFail($id);
        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $inventories = Inventory::orderBy('name')->get()->map(function ($inventory) {
            $inventory->available_quantity = $inventory->quantity;
            return $inventory;
        });

        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $internalProjects = InternalProject::select('id', 'job', 'project', 'department_id')
            ->with('department')
            ->orderBy('created_at', 'desc')
            ->get();

        $filters = [
            'project'       => $request->input('filter_project'),
            'material'      => $request->input('filter_material'),
            'status'        => $request->input('filter_status'),
            'requested_by'  => $request->input('filter_requested_by'),
            'requested_at'  => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        if ($materialRequest->status !== 'pending') {
            return redirect()->route('material_requests.index')->with('error', 'Only pending requests can be edited.');
        }

        if (auth()->user()->username !== $materialRequest->requested_by && !auth()->user()->isLogisticAdmin() && !auth()->user()->isReadOnlyAdmin()) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'You do not have permission to edit this request.');
        }

        return view('logistic.material_requests.edit', compact(
            'materialRequest',
            'inventories',
            'jobOrders',
            'internalProjects',
            'departments',
            'units',
            'filters'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to edit material requests.');
        }

        $materialRequest = MaterialRequest::findOrFail($id);

        if ($request->has('status') && !$request->has('inventory_id')) {
            return $this->quickUpdate($request, $id);
        }

        $request->validate([
            'project_type'        => 'required|in:client,internal',
            'inventory_id'        => 'required|exists:inventories,id',
            'qty'                 => 'required|numeric|min:0.01',
            'status'              => 'required|in:pending,approved,delivered,canceled',
            'remark'              => 'nullable|string',
            'job_order_id'        => 'required',
        ]);

        if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
            $request->validate([
                'job_order_id' => 'required|exists:job_orders,id',
                'project_id'   => 'required|exists:projects,id',
            ]);
        } else {
            $request->validate([
                'job_order_id' => 'required|exists:internal_projects,id',
            ]);
        }

        if (in_array($materialRequest->status, ['delivered', 'canceled'])) {
            return redirect()->route('material_requests.index')->with('error', 'Delivered or canceled requests cannot be updated.');
        }

        $filters = [
            'project'       => $request->input('filter_project'),
            'material'      => $request->input('filter_material'),
            'status'        => $request->input('filter_status'),
            'requested_by'  => $request->input('filter_requested_by'),
            'requested_at'  => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        DB::beginTransaction();
        try {
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();

            if ($request->qty > $inventory->quantity) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['qty' => 'Requested quantity cannot exceed available inventory quantity.']);
            }

            $updateData = [
                'inventory_id'   => $request->inventory_id,
                'project_type'   => $request->project_type,
                'qty'            => $request->qty,
                'status'         => $request->status,
                'remark'         => $request->remark,
            ];

            if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $updateData['job_order_id'] = $request->job_order_id;
                $updateData['project_id']    = $request->project_id;
                $updateData['internal_project_id'] = null;
            } else {
                $updateData['internal_project_id'] = $request->job_order_id;
                $updateData['job_order_id'] = null;
                $updateData['project_id']    = null;
            }

            if ($request->status === 'approved' && $materialRequest->status !== 'approved') {
                $updateData['approved_at'] = now();
            }

            $materialRequest->update($updateData);

            DB::commit();

            event(new MaterialRequestUpdated($materialRequest, 'updated'));

            return redirect()
                ->route('material_requests.index', $filters)
                ->with('success', "Material Request for <b>{$inventory->name}</b> updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['qty' => 'Failed to update request: ' . $e->getMessage()]);
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
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to update status.'], 403);
        }

        $request->validate(['status' => 'required|in:pending,approved,delivered,canceled']);

        $materialRequest = MaterialRequest::findOrFail($id);

        if ($materialRequest->status === 'delivered') {
            return response()->json(['success' => false, 'message' => 'Delivered requests cannot be updated.'], 422);
        }

        if (!auth()->user()->isLogisticAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $oldStatus = $materialRequest->status;
        $updateData = ['status' => $request->status];
        if ($request->status === 'approved' && $materialRequest->status !== 'approved') {
            $updateData['approved_at'] = now();
        }
        $materialRequest->update($updateData);

        event(new MaterialRequestUpdated($materialRequest, 'status'));

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
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to delete material requests.');
        }

        $materialRequest = MaterialRequest::findOrFail($id);

        $filters = [
            'project'       => $request->input('filter_project'),
            'material'      => $request->input('filter_material'),
            'status'        => $request->input('filter_status'),
            'requested_by'  => $request->input('filter_requested_by'),
            'requested_at'  => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        $authUser = auth()->user();
        $isSuperAdmin = $authUser->isSuperAdmin();
        $isLogisticAdmin = $authUser->isLogisticAdmin();
        $isRequestOwner = $authUser->username === $materialRequest->requested_by;

        if (in_array($materialRequest->status, ['approved', 'delivered'])) {
            if (!$isSuperAdmin) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'Only Super Admin can delete approved or delivered requests.');
            }
        } elseif ($materialRequest->status === 'pending') {
            if (!$isRequestOwner && !$isSuperAdmin) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'Only request owner or Super Admin can delete pending requests.');
            }
        } elseif ($materialRequest->status === 'canceled') {
            if (!$isRequestOwner && !$isLogisticAdmin && !$isSuperAdmin) {
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
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to send reminders material requests.');
        }

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

        $requests = MaterialRequest::with('inventory', 'project', 'jobOrder', 'internalProject')
            ->whereIn('id', $request->selected_ids)
            ->get();

        $data = $requests->map(function ($req) {
            return [
                'id'                => $req->id,
                'material_name'     => $req->inventory->name ?? '-',
                'unit'              => $req->inventory->unit ?? '',
                'job_order_name'    => $req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT
                                        ? ($req->jobOrder->name ?? '-')
                                        : ($req->internalProject->job ?? '-'),
                'project_name'      => $req->project_name,
                'requested_by'      => $req->requested_by,
                'requested_qty'     => rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.'),
                'remaining_qty'     => rtrim(rtrim(number_format($req->remaining_qty, 2, '.', ''), '0'), '.'),
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
            'id'          => $inventory->id,
            'name'        => $inventory->name,
            'category'    => $inventory->category->name ?? '-',
            'quantity'    => rtrim(rtrim(number_format($inventory->quantity, 2, '.', ''), '0'), '.'),
            'unit'        => $inventory->unit ?? '-',
            'price'       => $inventory->price ? number_format($inventory->price, 2, ',', '.') : '0',
            'currency'    => $inventory->currency->name ?? '-',
            'supplier'    => $inventory->supplier->name ?? '-',
            'location'    => $inventory->location->name ?? '-',
            'remark'      => $inventory->remark ?? '-',
            'img_url'     => $inventory->img ? asset('storage/' . $inventory->img) : null,
            'qr_code'     => $inventory->qr_code ?? null,
        ];

        return response()->json($data);
    }
}