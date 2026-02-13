<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use App\Models\InternalProject;
use App\Rules\ValidProjectSource;
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
     * INDEX – DataTables dengan filter.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = MaterialRequest::with(['inventory:id,name,quantity,unit', 'project:id,name,department_id', 'internalProject:id,project,job', 'jobOrder:id,name,project_id', 'user:id,username,department_id', 'user.department:id,name'])->latest();

            // Filter
            if ($request->filled('project_type')) {
                $query->where('project_type', $request->project_type);
            }
            if ($request->filled('project')) {
                $query->where('project_type', MaterialRequest::PROJECT_TYPE_CLIENT)->where('project_id', $request->project);
            }
            if ($request->filled('internal_project')) {
                $query->where('project_type', MaterialRequest::PROJECT_TYPE_INTERNAL)->where('internal_project_id', $request->internal_project);
            }
            if ($request->filled('job_order')) {
                $query->where('job_order_id', $request->job_order);
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
                            $q->where('project', 'like', "%{$search}%")->orWhere('job', 'like', "%{$search}%");
                        })
                        ->orWhere('requested_by', 'like', "%{$search}%")
                        ->orWhere('remark', 'like', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addColumn('checkbox', function ($req) {
                    return $req->status === 'approved' ? '<input type="checkbox" class="select-row" value="' . $req->id . '">' : '';
                })
                ->addColumn('project_type_display', function ($req) {
                    return $req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT ? 'Client' : 'Internal';
                })
                ->addColumn('project_name', function ($req) {
                    return $req->project_name;
                })
                ->addColumn('job_order', function ($req) {
                    if ($req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
                        return $req->jobOrder ? $req->jobOrder->name : '-';
                    } else {
                        return $req->job_order_id ?? '-';
                    }
                })
                ->addColumn('material_name', function ($req) {
                    return '<span class="material-detail-link gradient-link" data-id="' . ($req->inventory->id ?? '') . '">' . ($req->inventory->name ?? '(No Material)') . '</span>';
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
                                data-id="' .
                            $req->id .
                            '">
                                <option value="pending" ' .
                            ($req->status === 'pending' ? 'selected' : '') .
                            ' title="Waiting for approval - Click to approve or process">Pending</option>
                                <option value="approved" ' .
                            ($req->status === 'approved' ? 'selected' : '') .
                            ' title="Ready for goods out">Approved</option>
                                <option value="canceled" ' .
                            ($req->status === 'canceled' ? 'selected' : '') .
                            ' title="Request canceled">Canceled</option>
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
                    $isLogisticAdmin = $authUser->isLogisticAdmin();
                    $isSuperAdmin = $authUser->isSuperAdmin();
                    $isAdmin = $authUser->isReadOnlyAdmin();
                    $isRequestOwner = $authUser->username === $req->requested_by;

                    $actions = '<div class="d-flex flex-nowrap gap-1">';

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
                ->rawColumns(['checkbox', 'job_order', 'material_name', 'remaining_qty', 'processed_qty', 'requested_by', 'status', 'remark', 'actions'])
                ->setRowId(function ($req) {
                    return 'row-' . $req->id;
                })
                ->orderColumn('requested_at', 'created_at $1')
                ->make(true);
        }

        // Data untuk filter dropdown
        $projects = Cache::remember('material_requests_projects', 300, function () {
            return Project::orderBy('name')->get(['id', 'name']);
        });

        $internalProjects = Cache::remember('material_requests_internal_projects', 300, function () {
            return InternalProject::orderBy('project')
                ->orderBy('job')
                ->get(['id', 'project', 'job']);
        });

        $materials = Cache::remember('material_requests_materials', 300, function () {
            return Inventory::orderBy('name')->get(['id', 'name']);
        });

        $users = Cache::remember('material_requests_users', 300, function () {
            return User::orderBy('username')->get(['username']);
        });

        $jobOrders = Cache::remember('material_requests_job_orders', 300, function () {
            return \App\Models\Production\JobOrder::orderBy('id', 'desc')->get(['id', 'name', 'project_id']);
        });

        return view('logistic.material_requests.index', compact('projects', 'internalProjects', 'materials', 'users', 'jobOrders'));
    }

    /**
     * EXPORT – Ekspor ke Excel.
     */
    public function export(Request $request)
    {
        $projectType = $request->project_type;
        $project = $request->project;
        $internalProject = $request->internal_project;
        $jobOrder = $request->job_order;
        $material = $request->material;
        $status = $request->status;
        $requestedBy = $request->requested_by;
        $requestedAt = $request->requested_at;

        $query = MaterialRequest::with(['inventory', 'project', 'internalProject', 'jobOrder', 'user.department']);

        if ($projectType) {
            $query->where('project_type', $projectType);
        }
        if ($project) {
            $query->where('project_type', MaterialRequest::PROJECT_TYPE_CLIENT)->where('project_id', $project);
        }
        if ($internalProject) {
            $query->where('project_type', MaterialRequest::PROJECT_TYPE_INTERNAL)->where('internal_project_id', $internalProject);
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

        $fileName = 'material_requests_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new MaterialRequestExport($requests), $fileName);
    }

    /**
     * CREATE – Form tambah material request.
     */
    public function create(Request $request)
    {
        $inventories = Inventory::orderBy('name')->get();

        $projects = Project::fromLark()->with('departments', 'status')->notArchived()->orderBy('name')->get();

        $internalProjects = InternalProject::orderBy('project')
            ->orderBy('job')
            ->get(['id', 'project', 'job']);

        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        // 🔥 WAJIB: Cari department PT DCM untuk default value di modal
        $ptDcmDepartment = Department::where('name', 'PT DCM')->first();
        if (!$ptDcmDepartment) {
            $ptDcmDepartment = Department::orderBy('id')->first();
        }
        $defaultPtDcmDepartmentId = $ptDcmDepartment ? $ptDcmDepartment->id : null;

        $selectedMaterial = null;
        if ($request->has('material_id')) {
            $selectedMaterial = Inventory::find($request->material_id);
        }

        return view(
            'logistic.material_requests.create',
            compact(
                'inventories',
                'projects',
                'internalProjects',
                'jobOrders',
                'selectedMaterial',
                'departments',
                'units',
                'defaultPtDcmDepartmentId', // 🔥 WAJIB ADA
            ),
        );
    }

    /**
     * STORE – Simpan material request dengan validasi bersyarat.
     */
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material requests.');
        }

        $rules = [
            'inventory_id' => 'required|exists:inventories,id',
            'project_type' => 'required|in:client,internal',
            'qty' => 'required|numeric|min:0.01',
        ];

        if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
            $rules['job_order_id'] = 'required|exists:job_orders,id';
            $rules['project_id'] = ['required', 'exists:projects,id', new ValidProjectSource()];
        } else {
            $rules['job_order_id'] = 'required';
            $rules['internal_project_id'] = 'required|exists:internal_projects,id';
        }

        $request->validate($rules);

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
                'inventory_id' => $request->inventory_id,
                'project_type' => $request->project_type,
                'job_order_id' => $request->job_order_id,
                'qty' => $request->qty,
                'requested_by' => $user->username,
                'remark' => $request->remark,
            ];

            if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $data['project_id'] = $request->project_id;
                $data['internal_project_id'] = null;
            } else {
                $data['internal_project_id'] = $request->internal_project_id;
                $data['project_id'] = null;
            }

            $materialRequest = MaterialRequest::create($data);

            DB::commit();

            event(new MaterialRequestUpdated($materialRequest, 'created'));

            $projectName = $request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT ? $materialRequest->project->name ?? 'Unknown' : $materialRequest->internalProject->project ?? 'Unknown';

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
     * BULK CREATE – Tampilkan form tambah massal.
     */
    public function bulkCreate()
    {
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::with('departments', 'status')->notArchived()->orderBy('name')->get();
        $internalProjects = InternalProject::orderBy('project')
            ->orderBy('job')
            ->get(['id', 'project', 'job']);
        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);
        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('logistic.material_requests.bulk_create', compact('inventories', 'projects', 'internalProjects', 'jobOrders', 'departments', 'units'));
    }

    /**
     * BULK STORE – Simpan request massal dengan validasi bersyarat per baris.
     */
    public function bulkStore(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material requests.');
        }

        // Validasi dasar per baris - project_type is OPTIONAL (default to client)
        $request->validate([
            'requests.*.inventory_id' => 'required|exists:inventories,id',
            'requests.*.qty' => 'required|numeric|min:0.01',
            'requests.*.project_type' => 'nullable|in:client,internal',
        ]);

        // Validasi bersyarat per baris - DEFAULT TO CLIENT
        foreach ($request->requests as $index => $req) {
            // Set default project_type to 'client' if not specified
            if (!isset($req['project_type']) || empty($req['project_type'])) {
                $req['project_type'] = MaterialRequest::PROJECT_TYPE_CLIENT;
                $request->merge([
                    "requests.$index.project_type" => MaterialRequest::PROJECT_TYPE_CLIENT,
                ]);
            }

            if ($req['project_type'] === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $request->validate([
                    "requests.$index.job_order_id" => 'nullable|exists:job_orders,id', // CHANGED: nullable for bulk client
                    "requests.$index.project_id" => 'required|exists:projects,id',
                ]);

                // Validasi job order milik project yang sama (only if provided)
                if (!empty($req['job_order_id'])) {
                    $jobOrder = \App\Models\Production\JobOrder::find($req['job_order_id']);
                    if ($jobOrder && $jobOrder->project_id != $req['project_id']) {
                        return back()
                            ->withInput()
                            ->withErrors([
                                "requests.$index.job_order_id" => 'Job order tidak sesuai dengan project client.',
                            ]);
                    }
                }
            } else {
                $request->validate([
                    "requests.$index.job_order_id" => 'nullable', // CHANGED: nullable for internal
                    "requests.$index.internal_project_id" => 'required|exists:internal_projects,id',
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
                    'job_order_id' => $req['job_order_id'],
                    'qty' => $req['qty'],
                    'processed_qty' => 0,
                    'requested_by' => $user->username,
                    'remark' => $req['remark'] ?? null,
                ];

                if ($req['project_type'] === MaterialRequest::PROJECT_TYPE_CLIENT) {
                    $data['project_id'] = $req['project_id'];
                    $data['internal_project_id'] = null;
                } else {
                    $data['internal_project_id'] = $req['internal_project_id'];
                    $data['project_id'] = null;
                }

                $createdRequests[] = MaterialRequest::create($data);
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
            $projectDisplay = $req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT ? $req->project->name ?? 'Unknown' : $req->internalProject->project ?? 'Unknown';
            $infoList[] = "<b>{$req->inventory->name}</b> in project <b>{$projectDisplay}</b>";
        }
        $infoString = implode(', ', $infoList);

        return redirect()
            ->route('material_requests.index')
            ->with('success', "Bulk material requests submitted successfully for: {$infoString}");
    }

    /**
     * EDIT – Tampilkan form edit dengan data yang tersimpan.
     */
    public function edit(Request $request, $id)
    {
        $materialRequest = MaterialRequest::with(['inventory', 'project', 'internalProject'])->findOrFail($id);
        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        // Validasi status
        if ($materialRequest->status !== 'pending') {
            return redirect()->route('material_requests.index')->with('error', 'Only pending requests can be edited.');
        }

        // Validasi kepemilikan
        if (auth()->user()->username !== $materialRequest->requested_by && !auth()->user()->isLogisticAdmin() && !auth()->user()->isReadOnlyAdmin()) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'You do not have permission to edit this request.');
        }

        if ($materialRequest->status === 'canceled') {
            return redirect()->route('material_requests.index', $filters)->with('error', 'Canceled requests cannot be edited.');
        }

        // Pastikan relasi masih ada
        if (!$materialRequest->inventory) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'The associated inventory no longer exists.');
        }

        if ($materialRequest->project_type === MaterialRequest::PROJECT_TYPE_CLIENT && !$materialRequest->project) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'The associated client project no longer exists.');
        }

        if ($materialRequest->project_type === MaterialRequest::PROJECT_TYPE_INTERNAL && !$materialRequest->internalProject) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'The associated internal project no longer exists.');
        }

        $inventories = Inventory::orderBy('name')
            ->get()
            ->map(function ($inventory) {
                $inventory->available_quantity = $inventory->quantity;
                return $inventory;
            });

        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $internalProjects = InternalProject::orderBy('project')
            ->orderBy('job')
            ->get(['id', 'project', 'job']);

        return view('logistic.material_requests.edit', [
            'request' => $materialRequest,
            'inventories' => $inventories,
            'jobOrders' => $jobOrders,
            'internalProjects' => $internalProjects,
            'departments' => $departments,
            'units' => $units,
        ]);
    }

    /**
     * UPDATE – Ubah data request dengan validasi bersyarat.
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to edit material requests.');
        }

        $materialRequest = MaterialRequest::findOrFail($id);

        // Quick update status (dari dropdown di tabel)
        if ($request->has('status') && !$request->has('inventory_id')) {
            return $this->quickUpdate($request, $id);
        }

        $rules = [
            'inventory_id' => 'required|exists:inventories,id',
            'project_type' => 'required|in:client,internal',
            'qty' => 'required|numeric|min:0.01',
            'status' => 'required|in:pending,approved,delivered,canceled',
            'remark' => 'nullable|string',
        ];

        if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
            $rules['job_order_id'] = 'nullable|exists:job_orders,id'; // CHANGED: nullable for edit
            $rules['project_id'] = 'required|exists:projects,id';
        } else {
            $rules['job_order_id'] = 'nullable'; // CHANGED: nullable for internal too
            $rules['internal_project_id'] = 'required|exists:internal_projects,id';
        }

        $request->validate($rules);

        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        if (in_array($materialRequest->status, ['delivered', 'canceled'])) {
            return redirect()->route('material_requests.index')->with('error', 'Delivered or canceled requests cannot be updated.');
        }

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
                'inventory_id' => $request->inventory_id,
                'project_type' => $request->project_type,
                'job_order_id' => $request->job_order_id,
                'qty' => $request->qty,
                'status' => $request->status,
                'remark' => $request->remark,
            ];

            if ($request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT) {
                $updateData['project_id'] = $request->project_id;
                $updateData['internal_project_id'] = null;
            } else {
                $updateData['internal_project_id'] = $request->internal_project_id;
                $updateData['project_id'] = null;
            }

            if ($request->status === 'approved' && $materialRequest->status !== 'approved') {
                $updateData['approved_at'] = now();
            }

            $materialRequest->update($updateData);

            DB::commit();

            event(new MaterialRequestUpdated($materialRequest, 'updated'));

            $projectDisplay = $request->project_type === MaterialRequest::PROJECT_TYPE_CLIENT ? Project::find($request->project_id)->name ?? 'Unknown' : InternalProject::find($request->internal_project_id)->project ?? 'Unknown';

            return redirect()
                ->route('material_requests.index', $filters)
                ->with('success', "Material Request for <b>{$inventory->name}</b> in project <b>{$projectDisplay}</b> updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['qty' => 'Failed to update request: ' . $e->getMessage()]);
        }
    }

    /**
     * QUICK UPDATE – Hanya ubah status (dari dropdown di tabel).
     */
    public function quickUpdate(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to update status.'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,approved,delivered,canceled',
        ]);

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
     * DESTROY – Hapus request.
     */
    public function destroy(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to delete material requests.');
        }

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
        $projectDisplay = $materialRequest->project_name;

        return redirect()
            ->route('material_requests.index', $filters)
            ->with('success', "Material Request for <b>{$inventory->name}</b> in project <b>{$projectDisplay}</b> deleted successfully.");
    }

    /**
     * SEND REMINDER – Kirim notifikasi ke admin logistic.
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
     * BULK DETAILS – Untuk popup detail Goods Out (menampilkan material yang dipilih).
     */
    public function bulkDetails(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:material_requests,id',
        ]);

        $requests = MaterialRequest::with('inventory', 'project', 'internalProject', 'jobOrder')->whereIn('id', $request->selected_ids)->get();

        $data = $requests->map(function ($req) {
            return [
                'id' => $req->id,
                'material_name' => $req->inventory->name ?? '-',
                'unit' => $req->inventory->unit ?? '',
                'job_order_name' => $req->project_type === MaterialRequest::PROJECT_TYPE_CLIENT ? $req->jobOrder->name ?? '-' : $req->job_order_id ?? '-',
                'project_name' => $req->project_name,
                'requested_by' => $req->requested_by,
                'requested_qty' => rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.'),
                'remaining_qty' => rtrim(rtrim(number_format($req->remaining_qty, 2, '.', ''), '0'), '.'),
            ];
        });

        return response()->json($data);
    }

    /**
     * STORE FROM PLANNING – Digunakan untuk mengimpor dari Material Planning (client project).
     */
    public function storeFromPlanning($planning)
    {
        try {
            \Log::info('Processing material request from planning: ' . $planning->id . ' - ' . $planning->material_name);

            $inventoryId = \App\Models\Logistic\Inventory::where('name', $planning->material_name)->value('id');

            if (!$inventoryId) {
                $defaultCategory = \App\Models\Logistic\Category::first();
                if (!$defaultCategory) {
                    $defaultCategory = \App\Models\Logistic\Category::create([
                        'name' => 'Default',
                        'description' => 'Default category for material planning',
                    ]);
                }

                $inventory = \App\Models\Logistic\Inventory::create([
                    'name' => $planning->material_name,
                    'quantity' => 0,
                    'unit' => optional($planning->unit)->name ?? 'pcs',
                    'category_id' => $defaultCategory->id,
                    'location' => 'Warehouse',
                    'min_stock_level' => 0,
                    'created_by' => 'system',
                ]);
                $inventoryId = $inventory->id;
            }

            $user = \App\Models\Admin\User::find($planning->requested_by);
            if (!$user) {
                \Log::error('User not found with ID: ' . $planning->requested_by);
                return null;
            }

            $username = $user->username;

            $materialRequest = MaterialRequest::create([
                'inventory_id' => $inventoryId,
                'project_type' => MaterialRequest::PROJECT_TYPE_CLIENT,
                'project_id' => $planning->project_id,
                'internal_project_id' => null,
                'job_order_id' => null,
                'qty' => $planning->qty_needed,
                'processed_qty' => 0,
                'requested_by' => $username,
                'remark' => 'Imported from Material Planning',
                'status' => 'pending',
            ]);

            \Log::info('Material request successfully created with ID: ' . $materialRequest->id);
            return $materialRequest;
        } catch (\Exception $e) {
            \Log::error('Error creating material request: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return null;
        }
    }
}
